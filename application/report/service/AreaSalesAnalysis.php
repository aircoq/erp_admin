<?php

namespace app\report\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\joom\JoomShop;
use app\common\model\Order;
use app\common\service\Common;
use app\common\service\CommonQueuer;
use app\common\service\OrderStatusConst;
use app\report\model\ReportExportFiles;
use app\report\queue\AreaSalesAnalysisQueue;
use think\Db;
use think\Exception;
use think\Loader;
use app\common\service\ChannelAccountConst;
use app\common\traits\Export;
use app\order\service\OrderRuleExecuteService;
use app\order\service\AuditOrderService;


Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/10/11
 * Time: 20:55
 */
class AreaSalesAnalysis
{
    use Export;
    protected $orderModel;   //定义订单模型
    private $where = [];
    private $group = '';
    private $having = '';
    private $fieldCase = '';

    /** 构造函数
     * OrderHelp constructor.
     */
    public function __construct()
    {
        if (is_null($this->orderModel)) {
            $this->orderModel = new Order();
        }
    }
    /**
     * 标题
     */
    public function title()
    {
        $title = [
            'country_name' => [
                'title' => 'country_name',
                'remark' => '国家',
                'is_show' => 1
            ],
            'now_order_total' => [
                'title' => 'now_order_total',
                'remark' => '订单量',
                'is_show' => 1
            ],
            'now_pay_total' => [
                'title' => 'now_pay_total',
                'remark' => '订单总额(CNY)',
                'is_show' => 1
            ],
            'until_price' => [
                'title' => 'until_price',
                'remark' => '客单价(CNY)',
                'is_show' => 1
            ],
            'pre_order_total' => [
                'title' => 'pre_order_total',
                'remark' => '上期订单量',
                'is_show' => 1
            ],
            'pre_pay_total' => [
                'title' => 'pre_pay_total',
                'remark' => '上期订单额(CNY)',
                'is_show' => 1
            ],
            'order_proportion' => [
                'title' => 'order_proportion',
                'remark' => '订单量环比(%)',
                'is_show' => 1
            ],
            'pay_proportion' => [
                'title' => 'pay_proportion',
                'remark' => '订单额环比(%)',
                'is_show' => 1
            ],
            'country_code' => [
                'title' => 'country_code',
                'remark' => '国家简码',
                'is_show' => 0
            ],

        ];
        return $title;
    }



    /**
     * 列表详情
     * @param $page
     * @param $pageSize
     * @param $params
     * @return array
     */
    public function lists($page, $pageSize, $params)
    {
        $result = $this->doSearch($params,0,0);
        $count = count($result);
        $start = ($page - 1) * $pageSize;
        $result = array_slice($result, $start, $pageSize);
        $result = [
            'data' => $result,
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize
        ];
        return $result;
    }

    /**
     * 查询数据
     * @param array $params
     * @return object
     */
    public function shippedExport($params,$page,$pageSize){
        $this->where($params);
        $field = $this->field();
        if(!empty($pageSize) && !empty($page) ){
            $list = $this->orderModel->field($field)->where($this->where)->group($this->group)->having($this->having)->page($page,$pageSize)->select(); //数据库查询返回数据
        }else{
            $list = $this->orderModel->field($field)->where($this->where)->group($this->group)->having($this->having)->select(); //数据库查询返回数据
        }
        return $list;
    }

    /**
     * 查询总数
     * @param array $condition
     * @param array $join
     * @return int|string
     */
    protected function doCount(array $params = [])
    {
        $this->where($params);
        $total = $this->orderModel->where($this->where)->group($this->group)->having($this->having)->count();
        return $total;
    }
    /**
     * 组装返回的数据
     * @return string
     */
    public function doSearch($params,$page=0,$pageSize=0)
    {
        $list=$this->shippedExport($params,$page,$pageSize);
        $orderRuleExecuteService = new OrderRuleExecuteService();
        $countryArr = Cache::store('country')->getCountry();
        $data = [];
        foreach ($list as $k => $v) {
            $pay_total=$orderRuleExecuteService->convertCurrency($v['currency_code'], 'CNY', $v['pay_total']);
            if($v['type']=='no'){
                continue;
            }
            if($v['type']=='pre'){
                $data[$v['country_code']]['pre_pay_total'] = round($pay_total,2) ;
                $data[$v['country_code']]['pre_order_total'] = $v['total'];
            }
            if($v['type']=='now'){
                $data[$v['country_code']]['now_pay_total']=  round($pay_total,2);
                $data[$v['country_code']]['now_order_total']= $v['total'];
            }

            if(!isset($data[$v['country_code']]['now_pay_total'])){
                $data[$v['country_code']]['now_pay_total']=0;
            }
            if(!isset($data[$v['country_code']]['now_order_total'])){
                $data[$v['country_code']]['now_order_total']=0;
            }
            if(!isset($data[$v['country_code']]['pre_pay_total'])){
                $data[$v['country_code']]['pre_pay_total']=0;
                if((isset($params['date_b']) && empty($params['date_b']))){
                    $data[$v['country_code']]['pre_pay_total']='--';
                }
            }
            if(!isset($data[$v['country_code']]['pre_order_total'])){
                $data[$v['country_code']]['pre_order_total']=0;
                if((isset($params['date_b']) && empty($params['date_b']))){
                    $data[$v['country_code']]['pre_order_total']='--';
                }
            }
            $data[$v['country_code']]['until_price']= !empty($data[$v['country_code']]['now_order_total']) ? round($data[$v['country_code']]['now_pay_total'] / $data[$v['country_code']]['now_order_total'], 2) : '--';
            $data[$v['country_code']]['order_proportion'] = (!empty($data[$v['country_code']]['pre_order_total']) && $data[$v['country_code']]['pre_order_total']!='--') ? (round($data[$v['country_code']]['now_order_total'] / $data[$v['country_code']]['pre_order_total'], 2) * 100).'%' : '--';
            $data[$v['country_code']]['pay_proportion'] = (!empty($data[$v['country_code']]['pre_pay_total']) && $data[$v['country_code']]['pre_pay_total']!='--') ? (round($data[$v['country_code']]['now_pay_total'] / $data[$v['country_code']]['pre_pay_total'], 2) * 100).'%'  : '--';

            $data[$v['country_code']]['country_name'] = isset($countryArr[$v['country_code']])?$countryArr[$v['country_code']]['country_cn_name']:'';
            $data[$v['country_code']]['country_code'] = $v['country_code']??'';



        }
        unset($list);
        if(isset($data['UK']) && isset($data['GB'])  ){
            $data['UK']['pre_pay_total']=round(($data['UK']['pre_pay_total']=='--'?0:$data['UK']['pre_pay_total'])+($data['GB']['pre_pay_total']=='--'?0:$data['GB']['pre_pay_total']),2);
            $data['UK']['pre_order_total']=round(($data['UK']['pre_order_total']=='--'?0:$data['UK']['pre_order_total'])+($data['GB']['pre_order_total']=='--'?0:$data['GB']['pre_order_total']),2);
            $data['UK']['now_pay_total']=round(($data['UK']['now_pay_total']=='--'?0:$data['UK']['now_pay_total'])+($data['GB']['now_pay_total']=='--'?0:$data['GB']['now_pay_total']),2);
            $data['UK']['now_order_total']=round(($data['UK']['now_order_total']=='--'?0:$data['UK']['now_order_total'])+($data['GB']['now_order_total']=='--'?0:$data['GB']['now_order_total']),2);
            $data['UK']['until_price']=($data['UK']['until_price']=='--'?0:$data['UK']['until_price'])+($data['GB']['until_price']=='--'?0:$data['GB']['until_price']);

            $data['UK']['order_proportion'] = (!empty($data['UK']['pre_order_total']) && $data['UK']['pre_order_total']!='--') ? (round($data['UK']['now_order_total'] / $data['UK']['pre_order_total'], 2) * 100).'%' : '--';
            $data['UK']['pay_proportion'] = (!empty($data['UK']['pre_pay_total']) && $data['UK']['pre_pay_total']!='--') ? (round($data['UK']['now_pay_total'] / $data['UK']['pre_pay_total'], 2) * 100).'%'  : '--';

            unset($data['GB']);

        }
        /**
         * desc:如果按订单量查询  那么把对应的上一期的记录给过滤出去
         */
        if((isset($params['total_b']) && $params['total_b']!='') || ( isset($params['total_e']) && $params['total_e']!='') ){
            $s=$params['total_b']??0;
            $e=$params['total_e']??0;
            foreach ($data as $key=>$val){
                    if($s && $e){
                        if(!($val[$params['total_type']]>=$s &&  $val[$params['total_type']]<=$e)){
                            unset($data[$key]);
                        }
                    }
                    if($s && !$e){
                        if(!$val[$params['total_type']]>=$s ){
                            unset($data[$key]);
                        }
                    }
                    if(!$s && $e){
                        if(!($val[$params['total_type']]>=0 &&  $val[$params['total_type']]<=$e)){
                            unset($data[$key]);
                        }
                    }
                    if($s==0 &&  $e==0){
                        if($val[$params['total_type']]!=0 ){
                            unset($data[$key]);
                        }
                    }
                }
        }
        unset($list);
        return array_values($data);

    }


    /**
     * 获取字段信息
     * @return string
     */
    public function field()
    {
        $field = 'shipping_time,' .       //发货时间
            'channel_id,' .              //平台
            'channel_account_id,' .      //平台账号
            'country_code,' .            //国家
            'pay_time,' .                //付款时间
            'currency_code,' .           //支付币种
            'pay_fee,' .                 //支付费用
            'order_time,' .               //下单时间
            'pay_time,' .                  //支付时间
            'count(id) as total,' .         //订单总数
            'sum(pay_fee) as pay_total,' .     //订单支付总数
            'sum(pay_fee)/count(id) as until_price,'.     //客单价
            $this->fieldCase     //客单价
        ;
        return $field;
    }

    /**
     * 查询条件
     * @param $params
     * @param $where
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function where(&$params)
    {
        $this->where['status'] = ['<>', OrderStatusConst::SaidInvalid];
        $this->where['order_number'] = ['notlike','%\_R'];
        //平台
        if (isset($params['channel_id']) && !empty($params['channel_id'])) {
            $this->where['channel_id'] = ['eq', $params['channel_id']];
        }
        //站点
        if (isset($params['site_code']) && !empty($params['site_code'])) {
            $this->where['site_code'] = ['eq', $params['site_code']];
        }
        //账号简称
        if (isset($params['account_id']) && !empty($params['account_id'])) {
            $this->where['channel_account_id'] = ['eq', $params['account_id']];
            if ($params['channel_id'] == ChannelAccountConst::channel_Joom) {
                //joom平台特殊操作
                if ($shop_id = param($params, 'shop_id')) {
                    $this->where['channel_account_id'] = ['=', $shop_id];
                } else {
                    $joomShopModel = new JoomShop();
                    $account_ids = $joomShopModel->field('id')->where(['joom_account_id' => $params['account_id']])->select();
                    $account_ids = array_column($account_ids, 'id');
                    $this->where['channel_account_id'] = ['in', $account_ids];
                }
            }
        }

        //销售员
        if (isset($params['seller_id']) && !empty($params['seller_id'])) {
            $this->where['seller_id'] = ['=', trim($params['seller_id'])];
        }
        //国家
        if (isset($params['country_code']) && !empty($params['country_code'])) {
            $country_codes=json_decode($params['country_code'], true);
            $this->where['country_code'] = ['in', $country_codes];
            if(in_array('GB',json_decode($params['country_code'], true)) || in_array('UK',json_decode($params['country_code'], true)) ){
                $this->where['country_code'] = ['in', array_merge(['GB','UK'],$country_codes)];
            }
        }
        if (isset($params['keys']) && !empty($params['keys'])) {
            $this->where['country_code'] = ['in', json_decode($params['keys'], true)];
        }

        //时间查询
        if (isset($params['snDate'])) {
            $cut=0;
            if(isset($params['date_b']) && !empty($params['date_b']) && isset($params['date_e']) && !empty($params['date_e'])){
                $cut =strtotime($params['date_e'])-strtotime($params['date_b']); //时间切割点为8天
            }
            $params['pre_s']=isset($params['date_b']) && !empty($params['date_b']) ? strtotime($params['date_b']) - $cut:0;
            $params['real_s'] = isset($params['pre_s']) && !empty($params['pre_s']) ? date('Y-m-d',$params['pre_s'] ) : 0; //算出上一期的开始时间
            $params['date_e'] = isset($params['date_e']) && !empty($params['date_e']) ? $params['date_e'] : 0;
            $time_type=$params['snDate'];
            if(isset($params['date_b']) && !empty($params['date_b']) && isset($params['date_e']) && !empty($params['date_e'])){
                $pre_s=strtotime($params['date_b']) - $cut;
                $pre_e = strtotime($params['date_b']) ; //算出上一期的结束时间点

                $now_e = strtotime($params['date_e'].' 23:59:59'); //当前的结束时间点
                $this->group="country_code,";
                $this->group.="case  when ".$time_type." >= ". $pre_s." and ".$time_type." < ". $pre_e ." then 'pre' ";
                $this->group.="WHEN ".$time_type ." >= ".$pre_e." AND ".$time_type." <= ".$now_e." THEN 'now'  ";
                $this->group.="ELSE 'no' end";

                $this->fieldCase.=$this->group."  as type";

            }
            if(empty($params['date_b']) || empty($params['date_e'])){
                $now_e = strtotime($params['date_e'].' 23:59:59') ; //算出上一期的结束时间点
                $pre_e=0;
                $this->group="country_code, ";
                $this->group.="case WHEN ".$time_type ." >= ".$pre_e." AND ".$time_type." <= ".$now_e ." THEN 'now'  ";
                $this->group.="ELSE 'no' end";
                $this->fieldCase.=$this->group."  as type";
            }

            switch ($time_type) {
                case 'shipping_time':
                    $condition = timeCondition($params['real_s'], $params['date_e']);
                    if (!is_array($condition)) {
                        return json(['message' => '日期格式错误'], 400);
                    }
                    if (!empty($condition)) {
                        $this->where['shipping_time'] = $condition;
                    }
                    break;
                case 'pay_time':
                    $condition = timeCondition($params['real_s'], $params['date_e']);
                    if (!is_array($condition)) {
                        return json(['message' => '日期格式错误'], 400);
                    }
                    if (!empty($condition)) {
                        $this->where['pay_time'] = $condition;
                    }
                    break;
                case 'order_time':
                    $condition = timeCondition($params['real_s'], $params['date_e']);
                    if (!is_array($condition)) {
                        return json(['message' => '日期格式错误'], 400);
                    }
                    if (!empty($condition)) {
                        $this->where['order_time'] = $condition;
                    }
                    break;

                default:

            }
        }
        if (isset($params['total_type'])) {
            $total_b = isset($params['total_b']) ? $params['total_b'] : 0;
            $total_e = isset($params['total_e']) ? $params['total_e'] : 0;
            if(!empty($total_b) || !empty($total_e) ){
                $having='';
                if (!empty($total_b) && !empty($total_e)) {
                    $having=' between '.$total_b.' and '.$total_e;
                }
                if (!empty($total_b) && empty($total_e)) {
                    $having='>= '.$total_b;
                }
                if (empty($total_b) && !empty($total_e)) {
                    $having='<= '.$total_e;
                }
                switch ($params['total_type']) {
                    case 'now_order_total':
                        $this->having = "   count(id) ".$having ;
                        break;
                    case 'now_pay_total':
                        $this->having =  "  sum(pay_fee)   ".$having;
                        break;
                    case 'until_price':
                        $this->having =  "   sum(pay_fee)/count(id) ".$having ;
                        break;
                    default;
                }
            }

        }

    }
    /**
     * @title 生成导出用户名
     * @param $params
     * @return string
     */
    public function newExportFileName($params)
    {
        $channel_name='';
        $account_name='';
        if(isset($params['channel_name'])  && !empty($params['channel_name'])){
            $channel_name= $params['channel_name'].'平台';
        }
        if(isset($params['account_name'])  && !empty($params['account_name'])){
            $account_name= $params['account_name'].'账号';
        }
        $fileName = $channel_name.$account_name.'区域销量分析';
        $date_b = isset($params['date_b']) ? $params['date_b'] : date('Y-m-d',$params['date_b']);
        $date_e = isset($params['date_e']) ? $params['date_e'] :  date('Y-m-d',$params['date_e']);
        $fileName .=  $date_b . '~' .$date_e.'.xlsx';
        return $fileName;
    }

    /**
     * 导出数据至excel文件
     * @param array $params
     * @return bool
     * @throws Exception
     */
    public function exportOnLine($params)
    {
        set_time_limit(0);
        $userId = Common::getUserInfo()->toArray()['user_id'];
        $cache = Cache::handler();
        $lastApplyTime = $cache->hget('hash:export_area_analysis', $userId);
        if ($lastApplyTime && time() - $lastApplyTime < 5) {
            throw new JsonErrorException('请求过于频繁', 400);
        } else {
            $cache->hset('hash:export_apply', $userId, time());
        }
        try {
            //获取导出文件名
            $fileName = $this->newExportFileName($params);
            $downLoadDir = '/download/area_analysis/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir.$fileName;
            $titleData = $this->title();
            $remark = [];
            if (!empty($field)) {
                $title = [];
                foreach ($field as $k => $v) {
                    if (isset($titleData[$v])) {
                        array_push($title, $v);
                        array_push($remark, $titleData[$v]['remark']);
                    }
                }
            } else {
                $title = [];
                foreach ($titleData as $k => $v) {
                    if ($v['is_show'] == 1) {
                        array_push($title, $k);
                        array_push($remark, $v['remark']);
                    }
                }
            }
            $titleOrderData = [];
            foreach ($remark as $t => $tt){
                $titleOrderData[$tt] = 'string';
            }
            $writer = new \XLSXWriter();
            $writer->writeSheetHeader('Sheet1', $titleOrderData);
            $doSearch=$this->doSearch($params,'','');
            $count =$this->doCount($params);

            if ($count > 500) {
                //加入队列
                Db::startTrans();
                try {
                    $model = new ReportExportFiles();
                    $model->applicant_id     = $userId;
                    $model->apply_time       = time();
                    $model->export_file_name =$fileName;
                    $model->status =0;
                    if(!$model->save()){
                        throw new Exception('导出请求创建失败',500);
                    }
                    $params['file_name'] = $model->export_file_name;
                    $params['apply_id'] = $model->id;
                    (new CommonQueuer(AreaSalesAnalysisQueue::class))->push($params);
                    Db::commit();
                    return ['join_queue' => 1, 'message' => '已加入导出队列'];
                } catch (\Exception $ex) {
                    Db::rollback();
                    throw new JsonErrorException('申请导出失败');
                }
            } else {

                foreach ($doSearch as $a => $r) {

                    $mapOne = $this->getMapOne($r);

                    $writer->writeSheetRow('Sheet1', $mapOne);
                }
                $writer->writeToFile($fullName);
            }
            $auditOrderService = new AuditOrderService();
            $result = $auditOrderService->record($fileName, $fullName);
            return $result;
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }



    /**
     * 导出数据至excel文件
     * @param array $params
     * @return bool
     * @throws Exception
     */
    public function export(array $params)
    {
        set_time_limit(0);
        try {
            ini_set('memory_limit', '4096M');
            if (!isset($params['apply_id']) || empty($params['apply_id'])) {
                throw new Exception('导出申请id获取失败');
            }
            if (!isset($params['file_name']) || empty($params['file_name'])) {
                throw new Exception('导出文件名未设置');
            }
            $fileName = $params['file_name'];
            $downLoadDir = '/download/area_analysis/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }

            $fullName = $saveDir . $fileName;

            $count =$this->doCount($params)+1;
            $pageSize = 500;
            $loop = ceil($count / $pageSize);
            //创建excel对象
            $writer = new \XLSXWriter();
            $titleData = $this->title();
            $remark = [];
            if (!empty($field)) {
                $title = [];
                foreach ($field as $k => $v) {
                    if (isset($titleData[$v])) {
                        array_push($title, $v);
                        array_push($remark, $titleData[$v]['remark']);
                    }
                }
            } else {
                $title = [];
                foreach ($titleData as $k => $v) {
                    if ($v['is_show'] == 1) {
                        array_push($title, $k);
                        array_push($remark, $v['remark']);
                    }
                }
            }
            $titleOrderData = [];
            foreach ($remark as $t => $tt){
                $titleOrderData[$tt] = 'string';
            }
            $writer->writeSheetHeader('Sheet1', $titleOrderData);
            $mapOne=[];
            //分批导出
            for ($i = 0; $i < $loop; $i++) {
                $data=$this->doSearch($params,$i,$pageSize);
                foreach ($data as $k => $r) {
                    foreach ($title as $filed){
                        $mapOne[$filed]=$r[$filed];
                    }
                    $writer->writeSheetRow('Sheet1', $mapOne);
                }
            }
            $writer->writeToFile($fullName);
            if (is_file($fullName)) {
                $applyRecord['exported_time'] = time();
                $applyRecord['download_url'] = $downLoadDir . $fileName;
                $applyRecord['status'] = 1;
                (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
            } else {
                throw new Exception('文件写入失败');
            }
        } catch (\Exception $ex) {
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $ex->getMessage();
            (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
            Cache::handler()->hset(
                'hash:report_export',
                $params['apply_id'].'_'.time(),
                '申请id: ' . $params['apply_id'] . ',导出失败:' . $ex->getMessage() . ',错误行数：' . $ex->getLine());
        }
    }

    /**
     * 将对象转化数组数据（单一个）
     * @param $data
     * @return array
     */
    private function getMapOne($data)
    {
        $one = [
            'country_name' => $data['country_name'],
            'now_order_total' => $data['now_order_total'],
            'now_pay_total' => $data['now_pay_total'],
            'until_price' => $data['until_price'],
            'pre_order_total' => $data['pre_order_total'],
            'pre_pay_total' => $data['pre_pay_total'],
            'order_proportion' => $data['order_proportion'],
            'pay_proportion' => $data['pay_proportion'],
        ];
        return $one;
    }


}