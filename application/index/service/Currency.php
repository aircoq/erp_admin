<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 17-2-27
 * Time: 上午10:03
 */

namespace app\index\service;

use app\common\model\Currency as CurrencyModel;
use app\common\model\CurrencyRate;
use app\common\service\CommonCurls;
use erp\AbsServer;
use app\common\cache\Cache;
use think\Db;
use think\Exception;

class Currency extends AbsServer
{
    protected $model = CurrencyModel::class;

    /*
     * 网址：http://srh.bankofchina.com/search/whpj/search.jsp
     * 币种代码映射
     */
    const currency = [
        'AUD' => ['澳大利亚元' => 1325],          //澳大利亚元
        'CAD' => ['加拿大元' => 1324],          //加拿大元
        'CHF' => ['瑞士法郎' => 1317],          //瑞士法郎
        'DKK' => ['丹麦克朗' => 1321],          //丹麦克朗
        'EUR' => ['欧元' => 1326],          //欧元
        'GBP' => ['英镑' => 1314],          //英镑
        'HKD' => ['港币' => 1315],          //港币
        'INR' => ['印度卢比' => 3900],          //印度卢比
        'MYR' => ['林吉特' => 2890],          // 林吉特  马元
        'JPY' => ['日元' => 1323],          //日元
        'MOP' => ['澳门元' => 1327],          //澳门元
        'NOK' => ['挪威克朗' => 1322],          //挪威克朗
        'NZD' => ['新西兰元' => 1330],          //新西兰元
        'PHP' => ['菲律宾比索' => 1328],          //菲律宾比索
        'RUR' => ['卢布' => 1843],          //卢布
        'SEK' => ['瑞典克朗' => 1320],          //瑞典克朗
        'SGD' => ['新加坡元' => 1375],          //新加坡元
        'THB' => ['泰国铢' => 1329],          //泰铢
        'TWD' => ['新台币' => 2895],          //新台币
        'USD' => ['美元' => 1316],          //美元
        'SAR' => ['沙特里亚尔' => 4418],          //沙特里亚尔
    ];

    /**
     * @var redis缓存键
     */
    private static $redis_cache_key = 'table:currency:currencyRate';

    /**
     * @var 查询频率限制
     */
    private static $select_frequency_prefix = 'lock:currency:frequency:';

    /**
     * @var 静态属性缓存
     */
    private static $rate_cache_map = [];

    /** $channel_id
     * @param string $code
     * @return array
     */
    public function getCurrency($code = '')
    {
        $model = new CurrencyModel();
        $result = [];
        if ($code) {
            $model->where('code', 'in', $code);
        }
        $model->field('code,system_rate');
        $datas = $model->select();
        foreach ($datas as $data) {
            $result[$data['code']] = $data['system_rate'];
        }
        return $result;
    }

    /**
     * @desc 获取指定币种对目标币种特定日期的汇率($form_code对$to_code汇率)
     * @author linpeng
     * @date 2019-1-8 17:26:19
     * @param string $form_code //Y 指定币种
     * @param string $date //N 日期，如:2019-01-08,默认当前时间
     * @param string $to_code //N 目标币种,默认CNY
     */
    public static function getCurrencyRateByTime($form_code, $date = '', $to_code = 'CNY')
    {
        $rate = 0;
        //简单校验
        if (empty($form_code)) {
            return $rate;
        }
        $form_code = strtoupper($form_code);
        if (!$date) {
            $date = date('Y-m-d');
        }
        $to_code = strtoupper($to_code);
        //相同币种，直接返回1
        if ($form_code == $to_code) {
            $rate = 1;
            return $rate;
        }
        //获取对应的汇率
        $form_rate = $form_code == 'CNY' ? 1 : self::getCurrencyRate($form_code, $date);
        $to_rate = $to_code == 'CNY' ? 1 : self::getCurrencyRate($to_code, $date);
        //计算$form_code对$to_code汇率
        if ($form_rate && $to_rate) {
            $rate = sprintf('%.6f', $form_rate / $to_rate);
        }
        return $rate;
    }

    /**
     * @desc 获取指定币种对系统本位币(CNY)汇率
     * @author wangwei
     * @date 2019-1-10 20:28:37
     * @param string $code //Y 币种代码
     * @param string $date //N 日期，如:2019-01-08,默认当前时间
     */
    public static function getCurrencyRate($code, $date = '')
    {
        $rate = 0;
        /**
         * 1、简单校验
         */
        if (empty($code)) {
            return $rate;
        }
        $code = strtoupper($code);
        if (!$date) {
            $date = date('Y-m-d');
        }
        $date_time = strtotime($date);
        if (!$date_time) {
            return $rate;
        }
        $release_day = floor(($date_time + 28800) / 86400);

        /**
         * 2、获取数据
         */
        //1、态缓存里已存在,直接取缓存里
        $only_key = "{$code}_{$release_day}";
        if ($rate = paramNotEmpty(self::$rate_cache_map, $only_key)) {
            return $rate;
        }
        //2、态缓存里没有，取redis缓存
        if (!$rate = Cache::handler()->hGet(self::$redis_cache_key, $only_key)) {
            $gcrftRe = self::getCurrencyRateFromTable($code, $date);
            $rate = $gcrftRe['rate'];
            if (!$gcrftRe['ask']) {
                return $rate;
            }
            //更新redis
            Cache::handler()->hSet(self::$redis_cache_key, $only_key, $rate);
        }
        //设置静态属性
        self::$rate_cache_map[$only_key] = $rate;

        return $rate;
    }

    /**
     * @desc 从汇率表里取汇率(取不到重url下载)
     * @author wangwei
     * @date 2019-1-10 18:11:39
     * @param string $code //Y 币种代码
     * @param string $date //N 日期(默认当前日期),如:2019-01-10
     */
    public static function getCurrencyRateFromTable($code, $date = '')
    {
        $return = [
            'ask' => 0,
            'message' => 'getCurrencyRateFromTable error',
            'rate' => 0,
        ];

        /**
         * 1、参数校验
         */
        if (empty($code)) {
            $return['message'] = 'code not empty';
            return $return;
        }
        $code = strtoupper($code);
        if (empty($date)) {
            $date = date('Y-m-d');
        } else if (date('Y-m-d', strtotime($date)) != $date) {
            $return['message'] = 'date format error';
            return $return;
        }
        $release_day = floor((strtotime($date) + 28800) / 86400);

        /**
         * 2、从表里获取汇率
         */
        $model = new CurrencyRate();
        $where = [
            'release_day' => $release_day,
            'code' => $code
        ];
        if ($cr_row = $model->field('rate')->where($where)->order('type desc, update_time desc')->find()) {
            $return['ask'] = 1;
            $return['message'] = 'success';
            $return['rate'] = $cr_row['rate'];
            return $return;
        }

        /**
         * 3、表里获取不到，从url获取
         */
        $gcrfcRe = self::getCurrencyRateFromCurl($code, $date);
        if (!$gcrfcRe['ask']) {
            $return['message'] = "getCurrencyRateFromCurl error:{$gcrfcRe['message']}";
            return $return;
        }

        $release_date = $gcrfcRe['time'];
        $release_time = strtotime($gcrfcRe['time']);
        $rate = $gcrfcRe['rate'];
        $name = $gcrfcRe['name'] ? $gcrfcRe['name'] : $code;

        /**
         * 4、更新汇率表数据
         */
        //更新 或 插入 currency表
        $c_row = [
            'update_time' => time(),
            'official_rate' => $rate,
            'update_id' => 0,
            'official_update_time' => $release_time,
        ];
        $currencyModel = new CurrencyModel();
        if (!$c_has = $currencyModel->field('id,official_update_time')->where(['code' => $code])->find()) {
            $c_row['name'] = $name;
            $c_row['code'] = $code;
            $c_row['symbol'] = $code;
            $c_row['create_time'] = time();
            $c_row['create_id'] = 0;
            $currencyModel->save($c_row);
            $cur_id = $currencyModel->id;
        } else {
            //如果官方汇率未更新时间小于当前时间，更新官方汇率
            if ($release_time > $c_has['official_update_time']) {
                $c_row['id'] = $c_has['id'];
                $currencyModel->isUpdate(true)->save($c_row);
                //更新redis缓存
                $only_key = "{$code}_{$release_day}";
                Cache::handler()->hSet(self::$redis_cache_key, $only_key, $rate);
            }
            $cur_id = $c_has['id'];
        }
        if (!$cur_id) {
            $return['message'] = "oper currency table error";
            return $return;
        }
        //插入currency_rate表
        $cr_add = [
            'cur_id' => $cur_id,
            'code' => $code,
            'rate' => $rate,
            'type' => 0,
            'release_day' => $release_day,
            'release_date' => $release_date,
            'create_time' => time(),
            'update_time' => $release_time
        ];
        $model->save($cr_add);

        /**
         * 5、整理返回数据
         */
        $return['ask'] = 1;
        $return['message'] = 'success';
        $return['rate'] = $rate;
        return $return;
    }

    /**
     * @desc 从中行获取指定币种指定时间汇率
     * @author wangwei
     * @date 2019-1-10 18:11:39
     * @param string $code //Y 币种代码
     * @param string $date //Y 日期,如:2019-01-10
     * @param boolean $latest 最新数据
     * @param string $col 页面数据列
     */
    public static function getCurrencyRateFromCurl($code, $date, $latest = false, $col = '中行折算价')
    {
        $return = [
            'ask' => 0,
            'message' => 'getCurrencyFromCurlOne error',
            'time' => '',
            'rate' => 0,
            'name' => '',
        ];

        /**
         * 1、参数校验
         */
        if (empty($code)) {
            $return['message'] = 'code not empty';
            return $return;
        }
        $code = strtoupper($code);
        //获取币种映射编码
        $pjname = 0;
        if (param(self::currency, $code)) {
            $pjname = array_values(self::currency[$code]);
        }
        if (!$pjname) {
            $return['message'] = "暂不支持的币种:{$code}";
            return $return;
        }
        if (empty($date)) {
            $return['message'] = 'date not empty';
            return $return;
        }
        if (date('Y-m-d', strtotime($date)) != $date) {
            $return['message'] = 'date format error';
            return $return;
        }

        /**
         * 2、从中行获取汇率
         */
        $erectDate = $date;
        $nothing = $date;
        $config = [
            'reqUrl' => "http://srh.bankofchina.com/search/whpj/search.jsp?erectDate={$erectDate}&nothing={$nothing}&pjname={$pjname[0]}",
            'req_method' => 'post'
        ];
        $curl = new CommonCurls($config);
        if (!$html = $curl->execCurlConfigs()) {
            $return['message'] = 'html is empty';
            return $return;
        }
        $html = preg_replace("/[\t\n\r]+/", "", $html);
        $html = preg_replace('/\s(?=\s)/', '', $html);
        $html = preg_replace('/[\n\r\t]/', '', $html);
        $tablePattern = '/<div class="BOC_main publish">(.*?)<\/table>/';
        if (!preg_match($tablePattern, $html, $table)) {
            $return['message'] = 'html match error';
            return $return;
        }
        $table = $table[0];
        $pattern1 = '/<tr>(.*)<\/tr>/';
        if (!preg_match($pattern1, $table, $res2)) {
            $return['message'] = 'table match error:001';
            return $return;
        }
        $res2 = strval($res2[0]);
        if (!preg_match_all('/<tr([\s\S]*?)>([\s\S]*?)<\/tr>/', $res2, $matched)) {
            $return['message'] = 'table match error:002';
            return $return;
        }
        $td = [];
        $th = [];
        foreach ($matched[2] as $k => $v) {
            preg_match_all('/<td>([\s\S]*?)<\/td>/', $v, $matched_td);
            preg_match_all('/<th>([\s\S]*?)<\/th>/', $v, $matched_th);
            $td[] = $matched_td[1];
            $th[] = $matched_th[1];
        }
        $td = array_filter($td);
        $th = array_filter($th);
        $datas = [];
        foreach ($td as $v) {
            $datas[] = array_combine($th[0], $v);
        }

        // 默认返回列表的最后一条，经发现，数据第一条是最新的数据
        $last_data = $latest === true ? current($datas) : end($datas);

        /**
         * 3、整理返回数据
         */
        if (!$time = trim(paramNotEmpty($last_data, '发布时间'))) {
            $return['message'] = '未获取到发布时间';
            return $return;
        }
        if (!$rate = trim(paramNotEmpty($last_data, $col))) {
            $return['message'] = "未获取到{$col}";
            return $return;
        }
        if (!is_numeric($rate)) {
            $return['message'] = "{$col}非数字";
            return $return;
        }
        $return['ask'] = 1;
        $return['message'] = 'success';
        $return['time'] = str_replace('.', '-', $time);
        $return['rate'] = $rate / 100;
        $return['name'] = trim(paramNotEmpty($last_data, '货币名称', ''));
        return $return;
    }

    /**
     * @params boolean $latest 同步最新新的汇率
     */
    public function updateOfficialRate($latest = false)
    {
        $currencyModel = new currencyModel();
        $currentDate = date('Y-m-d');
        $time = strtotime($currentDate);
        $flag = Cache::store('Currency')->setAutoUpdateLock($time);
        if(!$flag){
            throw new Exception('今天已经执行过了，每天最多执行一次');
        }

        // 默认查询条件
        $where = ['official_update_time' => ['<', $time]];
        if($latest === true){ $where = []; }

        $currencyList = $currencyModel->field('id,code')->where($where)->order('sort asc')->select();

        $ins = 0;
        foreach ($currencyList as $currency) {
            if ($currency['code'] == 'CNY') {
                continue;
            }
            $onlineRate = self::getCurrencyRateFromCurl($currency['code'], $currentDate, $latest);
            if ($onlineRate['message'] == 'success') {
                $currencyModel = new currencyModel();
                $updateTime = strtotime($onlineRate['time']);
                $currencyData = array(
                    'official_rate' => $onlineRate['rate'],
                    'official_update_time' => $updateTime,
                    'system_rate' => $onlineRate['rate'],
                );
                Db::startTrans();
                try {
                    $currencyModel->allowField(true)->save($currencyData, ['id' => $currency['id']]);
                    $data['code'] = $currency['code'];
                    $data['cur_id'] = $currency['id'];
                    $data['rate'] = $onlineRate['rate'];
                    $data['create_time'] = time();
                    $data['update_time'] = $updateTime;
                    $currencyRateModel = new CurrencyRate();
                    $currencyRateModel->save($data);
                    Db::commit();
                    Cache::handler()->del('currency');
                    Cache::handler()->del('cache:Currency');
                    $ins++;
                } catch (Exception $ex) {
                    Db::rollback();
                    continue;
                }
            }
            continue;
        }
        return ['message'=>"成功同步".$ins."条数据"];
    }

}