<?php
/**
 * Created by PhpStorm.
 * User: ZxH
 * Date: 2019/3/27
 * Time: 17:13
 */

namespace app\goods\service;


use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\amazon\AmazonAccount;
use app\common\model\ebay\EbayAccount;
use app\common\model\Goods;
use app\common\model\GoodsTortDescription;
use app\common\model\TortEmailAttachment;
use app\common\model\wish\WishAccount;
use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use app\common\service\ImportExport;
use app\index\service\DownloadFileService;
use app\index\service\User;
use app\order\service\OrderService;
use app\report\model\ReportExportFiles;
use PDO;
use phpzip\PHPZip;
use think\Db;
use think\db\Query;
use think\Exception;
use app\common\validate\GoodsTortDescription as GoodsTortDescriptionValidate;

class TortImport
{
    /**
     * 导出队列的临界值
     * @var int
     */
    public const ENQUEUE_NUM = 500;

    /**
     * 一次导入的最大值
     * @var int
     */
    public const MAX_IMPORT_NUM = 200;

    /**
     * 侵权类型
     * @var array
     */
    public const TYPE = [
        '图片侵权' => 1,
        '商标侵权' => 2,
        '著作权侵权' => 3,
        '外观设计侵权' => 4,
        '禁限售产品' => 5,
        '其他知识产权侵权' => 6
    ];

    /**
     * 导出的字段
     * @var array
     */
    protected const EXPORT_FIELD = [
        //'spu', '侵权平台', '站点', '账号', '侵权描述', '侵权时间', '录入时间'
        ['title' => 'SPU', 'key' => 'spu'],
        ['title' => '侵权平台', 'key' => 'tort_channel'],
        ['title' => '站点', 'key' => 'site_code'],
        ['title' => '账号', 'key' => 'tort_account'],
        ['title' => '侵权描述', 'key' => 'remark'],
        ['title' => '侵权时间', 'key' => 'tort_time'],
        ['title' => '侵权类型', 'key' => 'tort_type'],
        ['title' => '侵权邮件内容', 'key' => 'email_content'],
        ['title' => '录入时间', 'key' => 'create_time'],
    ];

    /**
     * 邮件内容 上传图片的格式
     * @var array
     */
    public const EMAIL_CONTENT_IMAGE_TYPE = ['pjpeg', 'jpeg', 'jpg', 'gif', 'bmp', 'png'];

    /**
     * 导入的文件格式
     * @var array
     */
    public const MIME_TYPE = ['xls', 'xlsx'];

    /**
     * 导入成功的记录数
     * @var int
     */
    protected $successCount = 0;

    /**
     * 导入失败的记录数
     * @var int
     */
    protected $failCount = 0;

    /**
     * 导入时的空行数
     * @var int
     */
    protected $emptyRows = 0;

    /**
     * 获取数据列表/查询的sql语句
     * @param array $where
     * @param bool $isSelect
     * @return array|string
     * @throws
     */
    protected static function getList($where, $isSelect = true)
    {
        $fields = "g.*,gs.spu,gs.channel_id as goods_channel_id";
        $query = GoodsTortDescription::alias('g')
            ->join('goods gs', 'g.goods_id = gs.id', 'left')
            ->field($fields)
            ->where($where)
            ->order('create_time desc');
        if (!$isSelect) {
            return $query->select(false);
        } else {
            $list = $query->select();
        }
        $channels = Cache::store('channel')->getChannel();
        $channels = array_column($channels, 'title', 'id');
        $user = new User();
        $orderService = new OrderService();
        foreach ($list as $key => $value) {
            $userInfo = $user->getUser($value['create_id']);
            $accountName = $orderService->getAccountName($value['channel_id'], $value['account_id']);
            $list[$key]['tort_channel'] = $value['channel_id'] ? $channels[$value['channel_id']] : '';
            $list[$key]['tort_time'] = date('Y-m-d', $value['tort_time']);
            $list[$key]['create_time'] = date('Y-m-d', $value['create_time']);
            $list[$key]['create'] = $userInfo['realname'] ?? '';
            $list[$key]['tort_account'] = $accountName ? : '';
            $list[$key]['email_content'] = ''; //侵权邮箱内容留空
        }
        return $list;
    }

    /**
     * 导出方法
     * @param array $param
     * @return null
     * @throws Exception
     */
    public static function export($param)
    {
        set_time_limit(0);
        ini_set('memory_limit', '128M');
        $goodsHelp = new GoodsHelp();
        $where = $goodsHelp->tortWhere($param);
        $count = $goodsHelp->getTortCount($where);
        if ($count <= 0) {
            throw new JsonErrorException('导出的数据为空');
        }
        $header = static::EXPORT_FIELD;

        $userInfo = Common::getUserInfo();
        $downFileName = isset($param['file_name']) ? $param['file_name'] : '侵权记录_' . date('YmdHis');
        $downFileName .= "({$userInfo['realname']}).csv";
        if ($count <= static::ENQUEUE_NUM) {
            //小于500直接下载导出
            $list = static::getList($where);
            $file = [
                'name' => '侵权记录_',
                'path' => 'tort'
            ];
            $ExcelExport = new DownloadFileService();
            return $ExcelExport->export($list, $header, $file);
        }

        $sql = static::getList($where, false);
        $page = 1;
        $pageSize = 10000;
        $pageTotal = ceil($count / $pageSize);
        $fileName = str_replace('.csv', '', $downFileName);
        $fileDirPath = ROOT_PATH . 'public' . DS . 'download' . DS . 'tort';
        $filePath = $fileDirPath . DS . $downFileName;

        $aHeader = array_column($header, 'title');
        $fp = fopen($filePath, 'w+');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, $aHeader);
        fclose($fp);

        $channels = Cache::store('channel')->getChannel();
        $channels = array_column($channels, 'title', 'id');

        $orderService = new OrderService();
        do {
            $offset = ($page - 1) * $pageSize;
            $doSql = $sql . " limit  {$offset},{$pageSize}";
            $Q = new Query();
            $a = $Q->query($doSql, [], true, true);
            $fp = fopen($filePath, 'a');
            while ($v = $a->fetch(PDO::FETCH_ASSOC)) {
                $row = [];
                $row['spu'] = $v['spu'];
                $row['tort_channel'] = $v['channel_id'] ? $channels[$v['channel_id']] : '';
                $row['tort_time'] = date('Y-m-d', $v['tort_time']);
                $row['create_time'] = date('Y-m-d', $v['create_time']);
                $row['remark'] = $v['remark'];
                $row['site_code'] = $v['site_code'];
                $accountName = $orderService->getAccountName($v['channel_id'], $v['account_id']);
                $row['tort_account'] = $accountName ? : '';
                $row['tort_type'] = array_search($v['tort_type'], TortImport::TYPE) ? : '';
                $row['email_content'] = ''; //留空
                $rowContent = [];
                foreach ($header as $h) {
                    $field = $h['key'];
                    $value = $row[$field] ?? '';
                    $rowContent[] = $value;
                }
                fputcsv($fp, $rowContent);
            }
            unset($a);
            unset($Q);
            fclose($fp);
            $page++;
        } while ($page <= $pageTotal);
        $zipPath = $fileDirPath . DS . $fileName . ".zip";
        $phpZip = new PHPZip();
        $zipData = [
            [
                'name' => $fileName,
                'path' => $filePath
            ]
        ];
        $phpZip->saveZip($zipData, $zipPath);
        @unlink($filePath);
        $applyRecord = ReportExportFiles::get($param['apply_id']);
        $applyRecord['exported_time'] = time();
        $applyRecord['download_url'] = '/download/tort/' . $fileName . ".zip";
        $applyRecord['status'] = 1;
        $applyRecord->isUpdate()->save();
    }

    /**
     * @param string $file
     * @return array
     * @throws JsonErrorException
     */
    public function import($file = '')
    {
        set_time_limit(0);
        $importService = new ImportExport();
        $path = $importService->uploadFile($file, 'tort_import');
        if (!$path) {
            throw new JsonErrorException('文件上传失败');
        }
        $importData = $importService->excelImport($path);
        if (empty($importData)) {
            throw new JsonErrorException('导入数据为空！');
        }
        if (count($importData) > static::MAX_IMPORT_NUM) {
            throw new JsonErrorException('导入数据每次最多200条！');
        }
        //限制每次导入的数据记录数
        $this->checkHeader($importData);

        $i = 1;
        foreach ($importData as $key => $vo) {
            $i++;
            //处理单元格的空字符
            $importData[$key]['侵权平台'] = trim(preg_replace(["/^(\s|\&nbsp\;|　|\xc2\xa0)/", "/(\s|\&nbsp\;|　|\xc2\xa0)$/"], "", param($vo, '侵权平台')));
            $importData[$key]['账号'] = trim(preg_replace(["/^(\s|\&nbsp\;|　|\xc2\xa0)/", "/(\s|\&nbsp\;|　|\xc2\xa0)$/"], "", param($vo, '账号')));
            $importData[$key]['站点'] = trim(preg_replace(["/^(\s|\&nbsp\;|　|\xc2\xa0)/", "/(\s|\&nbsp\;|　|\xc2\xa0)$/"], "", param($vo, '站点')));
            $importData[$key]['SPU'] = trim(preg_replace(["/^(\s|\&nbsp\;|　|\xc2\xa0)/", "/(\s|\&nbsp\;|　|\xc2\xa0)$/"], "", param($vo, 'SPU')));
            $importData[$key]['侵权类型'] = trim(preg_replace(["/^(\s|\&nbsp\;|　|\xc2\xa0)/", "/(\s|\&nbsp\;|　|\xc2\xa0)$/"], "", param($vo, '侵权类型')));
            $importData[$key]['侵权描述'] = trim(preg_replace(["/^(\s|\&nbsp\;|　|\xc2\xa0)/", "/(\s|\&nbsp\;|　|\xc2\xa0)$/"], "", param($vo, '侵权描述')));
            $importData[$key]['侵权邮件内容'] = trim(preg_replace(["/^(\s|\&nbsp\;|　|\xc2\xa0)/", "/(\s|\&nbsp\;|　|\xc2\xa0)$/"], "", param($vo, '侵权邮件内容')));
            $importData[$key]['侵权时间'] = trim(preg_replace(["/^(\s|\&nbsp\;|　|\xc2\xa0)/", "/(\s|\&nbsp\;|　|\xc2\xa0)$/"], "", param($vo, '侵权时间')));
            //过滤空行
            $rowTemp = array_filter($importData[$key]);
            if (empty($rowTemp)) {
                unset($importData[$key]);
                $this->emptyRows++;
                continue;
            }
            $errorString = "第 {$i} 行 ";
            if (!param($vo, '侵权平台')) {
                $errorString .= '[ 侵权平台不能为空 ]： 数据不能为空（注：格式正确才能导入）';
                throw new JsonErrorException($errorString, 400);
            }
            if (!param($vo, '账号')) {
                $errorString .= '[ 账号不能为空 ]： 数据不能为空（注：格式正确才能导入）';
                throw new JsonErrorException($errorString, 400);
            }
            if (!param($vo, 'SPU')) {
                $errorString .= '[ SPU不能为空 ]： 数据不能为空（注：格式正确才能导入）';
                throw new JsonErrorException($errorString, 400);
            }
            if (!param($vo, '侵权类型')) {
                $errorString .= '[ 侵权类型不能为空 ]： 数据不能为空（注：格式正确才能导入）';
                throw new JsonErrorException($errorString, 400);
            }
        }
        $result = $this->combineArr($importData);
        $result = $this->filterRepeatDatabase($result);
        $errorMsg = $result['errorMsg'];
        if (empty($result['data']) || count($result['data']) == 0) {
            //没有一条记录可以导入
            $msgArr = $this->combineError($errorMsg);
            return [
                'result' => -1,
                'message' => $msgArr,
                'success_count' => $this->successCount,
                'error_count' => $this->failCount
            ];
        }
        $goodsTortDescription = new GoodsTortDescription();

        Db::startTrans();
        try {
            $this->successCount = $goodsTortDescription->insertAll($result['data']);
            //推送消息
            foreach ($result['data'] as $v) {
                $goodsInfo = (new GoodsHelp())->getGoodsInfo($v['goods_id']);
                $orderService = new OrderService();
                $accountName = $orderService->getAccountName($v['channel_id'], $v['account_id']);
                GoodsNotice::sendTortDescription($v['goods_id'], $goodsInfo, $v['channel_id'], $v['site_code'], $accountName, $v['remark']);
            }
            Db::commit();
            if (!empty($errorMsg)) {
                $msgArr = $this->combineError($errorMsg);
                //部分导入成功
                return [
                    'result' => 0,
                    'message' => $msgArr,
                    'success_count' => $this->successCount,
                    'error_count' => $this->failCount
                ];
            }
            //全部导入成功
            return [
                'result' => 1,
                'message' => '导入成功！',
                'success_count' => $this->successCount,
                'error_count' => $this->failCount
            ];
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage(), 500);
            Db::rollback();
        }
    }

    /**
     * 检查头
     * @params array $result
     * @$throws Exception
     */
    protected function checkHeader($result)
    {
        if (!$result) {
            throw new Exception("未收到该文件的数据");
        }
        $headers = [
            '侵权平台', '账号', '站点', 'SPU', '侵权类型', '侵权描述', '侵权邮件内容', '侵权时间'
        ];
        $row = reset($result);
        $aRowFiles = array_keys($row);
        $aDiffRowField = array_diff($headers, $aRowFiles);
        if (!empty($aDiffRowField)) {
            throw new Exception("缺少列名[" . implode(';', $aDiffRowField) . "]");
        }
    }

    /**
     * 组装数据 只做了4大平台的账号筛选
     * @param array $data
     * @return array
     * @throws JsonErrorException
     */
    protected function combineArr($data)
    {
        $i = 1;
        try {
            $resultData = [];
            $errorMsg = [];
            foreach ($data as $ko => $vo) {
                $i++; //记录行数
                $channel = param($vo, '侵权平台');
                $account = param($vo, '账号');
                $spu = param($vo, 'SPU');
                $siteCode = param($vo, '站点');
                $tortType = param($vo, '侵权类型');
                $remark = param($vo, '侵权描述');
                $emailContent = param($vo, '侵权邮件内容');
                $tortTime = param($vo, '侵权时间');
                $accountInfo = null;
                $channelId = 0;

                $goods = Goods::get(['spu' => $spu]);
                if (is_null($goods)) {
                    $tmp = [
                        'row' => $i + $this->emptyRows + $this->failCount,
                        'spu' => $spu
                    ];
                    $this->failCount++;
                    $errorMsg['spu'][] = $tmp; //spu不存在的情况
                    continue;
                }
                //账号只做了四大平台的
                switch (strtolower($channel)) {
                    case 'ebay' :
                        $accountInfo = EbayAccount::get(['code' => $account]);
                        $channelId = ChannelAccountConst::channel_ebay;
                        break;
                    case 'amazon' :
                        $accountInfo = AmazonAccount::get(['code' => $account]);
                        $channelId = ChannelAccountConst::channel_amazon;
                        break;
                    case 'wish' :
                        $accountInfo = WishAccount::get(['code' => $account]);
                        $channelId = ChannelAccountConst::channel_wish;
                        break;
                    case 'aliexpress' :
                        $accountInfo = AliexpressAccount::get(['code' => $account]);
                        $channelId = ChannelAccountConst::channel_aliExpress;
                        break;
                    default:
                        $channelId = 0;
                        $accountInfo = null;
                }
                if ($channelId == 0) {
                    $tmp = [
                        'row' => $i + $this->emptyRows + $this->failCount,
                        'channel' => $channel
                    ];
                    $this->failCount++;
                    $errorMsg['channel'][] = $tmp;//不属于四大平台
                    continue;
                }
                //站点的限制，未做

                //平台下账号匹配 限制
                if (is_null($accountInfo)) {
                    $tmp = [
                        'row' => $i + $this->emptyRows + $this->failCount,
                        'account' => $account
                    ];
                    $this->failCount++;
                    $errorMsg['account'][] = $tmp; //平台下的账号不匹配
                    continue;
                }
                //侵权平台的限制
                if (!array_key_exists($tortType, static::TYPE)) {
                    $tmp = [
                        'row' => $i + $this->emptyRows + $this->failCount,
                        'tort_type' => $tortType
                    ];
                    $this->failCount++;
                    $errorMsg['tort_type'][] = $tmp;  //侵权类型不符合
                    continue;
                }
                $tmpOne['channel_id'] = $channelId ? $channelId : 0;
                $tmpOne['account_id'] = $accountInfo ? $accountInfo->id : 0;
                $tmpOne['goods_id'] = $goods ? $goods->id : 0;
                $userInfo = Common::getUserInfo();
                $tmpOne['create_id'] = $userInfo['user_id'];
                $tmpOne['site_code'] = $siteCode;
                $tmpOne['tort_type'] = static::TYPE[$tortType];
                $tmpOne['remark'] = $remark;
                $tmpOne['email_content'] = $emailContent;
                $tmpOne['tort_time'] = \PHPExcel_Shared_Date::ExcelToPHP($tortTime);
                $tmpOne['create_time'] = time();
                $resultData[] = $tmpOne;
            }
            return [
                'data' => $resultData,
                'errorMsg' => $errorMsg
            ];
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }

    /**
     * 过滤掉数据库中已有的重复的 SPU 平台 账号 站点
     * @param $result
     * @return array
     * @throws null
     */
    protected function filterRepeatDatabase($result)
    {
        $data = $result['data'];
        $errorMsg = $result['errorMsg'];
        $i = 1;
        foreach ($data as $k => $v) {
            $i++; //记录行数
            $row = GoodsTortDescription::get(function ($query) use ($v) {
                $query->where('goods_id', '=', $v['goods_id'])
                    ->where('channel_id', '=', $v['channel_id'])
                    ->where('account_id', '=', $v['account_id'])
                    ->Where('site_code', '=', $v['site_code'])
                    ->where('tort_type', '=', $v['tort_type']);
            });
            if ($row) {
                $tmp = [
                    'row' => $i + $this->emptyRows + $this->failCount,
                    'repeat' => "{$i}行重复了"
                ];
                $this->failCount++;
                $errorMsg['repeat'][] = $tmp;
                unset($data[$k]);
                continue;
            }
        }
        return [
            'data' => $data,
            'errorMsg' => $errorMsg
        ];
    }

    /**
     * 导入时 部分的错误  进行合并
     * @return array
     */
    protected function combineError($errorMsg)
    {
        $returnErrMsg = [];
        if (isset($errorMsg['spu'])) {
            foreach ($errorMsg['spu'] as $v) {
                $tmp['row'] = $v['row'];
                $tmp['item_id'] = 'SPU: 【' . $v['spu'] . '】 不存在';
                $returnErrMsg[] = $tmp;
            }
        }


        if (isset($errorMsg['channel'])) {
            foreach ($errorMsg['channel'] as $v) {
                $tmp['row'] = $v['row'];
                $tmp['item_id'] = '侵权平台: 【' . $v['channel'] . '】 不在ebay,amazon,aliexpree,wish四大平台中';
                $returnErrMsg[] = $tmp;
            }
        }

        if (isset($errorMsg['account'])) {
            foreach ($errorMsg['account'] as $v) {
                $tmp['row'] = $v['row'];
                $tmp['item_id'] = '账号: 【' . $v['account'] . '】 匹配失败';
                $returnErrMsg[] = $tmp;
            }
        }

        if (isset($errorMsg['tort_type'])) {
            foreach ($errorMsg['tort_type'] as $v) {
                $tmp['row'] = $v['row'];
                $tmp['item_id'] = '侵权类型: 【' . $v['tort_type'] . '】 不匹配';
                $returnErrMsg[] = $tmp;
            }
        }

        if (isset($errorMsg['repeat'])) {
            foreach ($errorMsg['repeat'] as $v) {
                $tmp['row'] = $v['row'];
                $tmp['item_id'] = '此条记录已存在，已过滤';
                $returnErrMsg[] = $tmp;
            }
        }
        return $returnErrMsg;
    }

    /**
     * 侵权列表 service里的对外新增方法
     * @param $param
     * @throws \think\exception\DbException
     */
    public function create($param)
    {
        try {
            $userInfo = Common::getUserInfo();
            $goodsInfo = Goods::get(['spu' => $param['spu']]);
            $goodsId = $goodsInfo->id;
            $data = [
                'goods_id' => $goodsId,
                'channel_id' => $param['channel_id'],
                'account_id' => $param['account_id'],
                'site_code' => $param['site_code'],
                'tort_type' => static::TYPE[$param['tort_type']],
                'remark' => $param['remark'],
                'email_content' => $param['email_content'],
                'tort_time' => $param['tort_time'],
                'create_id' => $userInfo['user_id'],
                'create_time' => $param['create_time']
            ];
            //存在邮件图片则需要验证
            if (isset($param['email_img'])) {
                $imgPathList = json_decode($param['email_img'], true);
                $validate = new GoodsTortDescriptionValidate();
                if (!$validate->scene('email')->check(['email_img' => $imgPathList])) {
                    throw new Exception($validate->getError());
                }
                unset($param['email_img']);
            }

            Db::startTrans();
            try {
                $row = GoodsTortDescription::create($data);
                //处理邮件有图片的情况
                if (isset($imgPathList)) {
                    foreach ($imgPathList as &$v) {
                        $v['tort_id'] = $row->id;
                        $v['creator_id'] = $userInfo['user_id'];
                    }
                    $tortEmailAttachment = new TortEmailAttachment();
                    $tortEmailAttachment->saveAll($imgPathList);
                }
                //推送消息
                $orderService = new OrderService();
                $accountName = $orderService->getAccountName($param['channel_id'], $param['account_id']);
                GoodsNotice::sendTortDescription($goodsId, $goodsInfo, $param['channel_id'], $param['site_code'], $accountName, $param['remark']);
                Db::commit();

                return true;
            } catch (Exception $e) {
                Db::rollback();
                throw new Exception($e->getMessage());
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function getTortById($id)
    {
        $row = GoodsTortDescription::get($id);
        $row->tort_type = array_search($row->tort_type, static::TYPE);
        return $row;
    }

    /**
     * 侵权列表 service里的对外修改方法
     * 此修改时spu不能改
     * @param $param
     */
    public function update($param)
    {
        try {
            $userInfo = Common::getUserInfo();
            $row = GoodsTortDescription::get($param['id']);
            $param['tort_type'] = static::TYPE[$param['tort_type']];
            unset($param['spu']);
            //存在邮件图片则需要验证
            if (isset($param['email_img'])) {
                $imgPathList = json_decode($param['email_img'], true);
                $validate = new GoodsTortDescriptionValidate();
                if (!$validate->scene('email')->check(['email_img' => $imgPathList])) {
                    throw new Exception($validate->getError());
                }
                unset($param['email_img']);
            }
            Db::startTrans();
            try {
                $row->allowField(true)->save($param);
                if (isset($imgPathList)) {
                    foreach ($imgPathList as &$v) {
                        $v['tort_id'] = $row->id;
                        $v['creator_id'] = $userInfo['user_id'];
                    }
                    $tortEmailAttachment = new TortEmailAttachment();
                    $tortEmailAttachment->saveAll($imgPathList);
                }

                Db::commit();
                return true;
            } catch (Exception $e) {
                Db::rollback();
                throw new JsonErrorException($e->getMessage());
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 邮件内容 文件上传
     * @baseData 文件经过base64加密后的数据
     * @pathName 上传的目录名
     * @extension 文件的后缀
     */
    public function uploadAttachment($baseData, $pathName)
    {
        if (!$baseData) {
            throw new JsonErrorException('未检测到文件');
        }
        $baseData = trim($baseData);
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $baseData, $match)) {
            if (!in_array($match[2], static::EMAIL_CONTENT_IMAGE_TYPE)) {
                throw new JsonErrorException('文件类型有误');
            } else {
                $extension = $match[2];
            }
        } else {
            throw new JsonErrorException('文件类型有误');
        }
        $dir = date('Y-m-d');
        $base_path = ROOT_PATH . 'public' . DS . 'upload' . DS . $pathName . DS . $dir;

        if (!is_dir($base_path) && !mkdir($base_path, 0755, true)) {
            throw new JsonErrorException('目录创建失败');
        }
        try {
            $uniqueCode = md5($baseData); //生成图片唯一码
            $fileName = $pathName . date('YmdHis') . '.' . $extension;
            $start = strpos($baseData, ',');
            $content = substr($baseData, $start + 1);
            file_put_contents($base_path . DS . $fileName, base64_decode(str_replace(" ", "+", $content)));
            return [
                'message' => '上传成功',
                'path' => $base_path . DS . $fileName,
                'unique_code' => $uniqueCode
            ];
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage());
        }
    }
}