<?php
namespace app\listing\service;

use app\common\model\amazon\AmazonListing;
use app\listing\validate\AmazonListingValidate;
use app\publish\service\AmazonPublishHelper;
use app\publish\service\AmazonPublishResultService;
use think\Loader;
use think\Exception;
use app\common\exception\JsonErrorException;
use think\Db;
use think\Validate;
use app\common\cache\Cache;
use app\common\service\Common;
use app\common\service\UniqueQueuer;
use app\common\model\amazon\AmazonActionLog;
use app\listing\queue\AmazonActionLogQueue;

class AmazonActionLogsHelper
{
    protected $lang = 'zh';
    private $model;
    private $listCondition = [];
    private $baseUrl;
    const TYPE_FULFILLMENT_TYPE = 'fulfillment_type';
    const TYPE_PRICE = 'price';
    const TYPE_QUANTITY = 'quantity';
    const TYPE_ITEMNAME = 'itemname';
    const TYPE_DESCRIPTION = 'description';
    const TYPE_SELLER_STATUS = 'seller_status';
    const TYPE_IMAGE = 'image';
    const TYPE_HEEL_PRODUCT = 'asin';
    public static $scence_type = [
        self::TYPE_FULFILLMENT_TYPE => 1,
        self::TYPE_PRICE => 2,
        self::TYPE_QUANTITY => 3,
        self::TYPE_ITEMNAME => 4,
        self::TYPE_DESCRIPTION => 5,
        self::TYPE_SELLER_STATUS => 6,
        self::TYPE_IMAGE => 7,
        self::TYPE_HEEL_PRODUCT => 8,
    ];

    /** @var array 处理刊登结果的回调方法 */
    public $callback_type_method = [
        0 => [],
        1 => ['class' => 'app\publish\service\AmazonPublishHelper', 'method' => 'backInfingeEnd'],
        2 => ['class' => 'app\publish\service\AmazonPublishHelper', 'method' => 'backSkuInventory'],
        3 => ['class' => 'app\publish\service\AmazonHeelSaleLogService', 'method' => 'backUploadProduct'],
        4 => ['class' => 'app\publish\service\AmazonHeelSaleLogService', 'method' => 'backUploadPriceAndQuantity'],
        5 => ['class' => 'app\publish\service\AmazonHeelSaleLogService', 'method' => 'backUpLowerPriceAndQuantity'],
    ];

    public function __construct()
    {
        $this->model = new AmazonActionLog;
        $this->baseUrl = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . '/';
    }


    /**
     * 设置刊登语言
     * @param $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }


    /**
     * 获取刊登语言
     * @return string
     */
    public function getLang()
    {
        return $this->lang ?? 'zh';
    }

    /**
     * 组合SQL条件
     * combineWhere
     * @param array $param
     * @return array
     */
    private function combineWhere(array $param)
    {
        if (isset($param['amazon_listing_id'])) {
            $this->listCondition = [
                'amazon_listing_id' => $param['amazon_listing_id']
            ];
        } elseif (isset($param['account_id']) && isset($param['type'])) {
            $this->listCondition = [
                'account_id' => $param['account_id'],
                'type' => ['IN', $param['type']],
            ];
        }
    }

    /**
     * 查询产品总数
     * @param array $wheres
     * @return int
     */
    public function getCount(array $param)
    {
        $this->combineWhere($param);
        $count = $this->model->where($this->listCondition)->count();
        return $count;
    }

    /**
     * 获取导出列表信息
     * getList
     * @param array $param
     * @param $page
     * @param $pageSize
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getList(array $param, $page, $pageSize)
    {
        if ($this->lang == 'zh') {
            $const = [
                'itemname' => '标题',
                'price' => '价格',
                'quantity' => '数量',
                'fulfillment_type' => '发货类型',
                'description' => '描述',
                'seller_status' => '销售状态',
            ];
            $statusArr = ['未执行', '执行成功', '执行失败'];
        } else {
            $const = [
                'itemname' => 'title',
                'price' => 'price',
                'quantity' => 'quantity',
                'fulfillment_type' => 'fulfillment type',
                'description' => 'description',
                'seller_status' => 'seller status',
            ];
            $statusArr = ['Didn\'t start', 'successful', 'failed'];
        }

        $count = $this->getCount($param);
        $data = [
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        if ($count > 0) {
            //$lists = $this->model->with(['user'=>function($query){$query->field('id,realname');}])->where($this->listCondition)->page($page)->limit($pageSize)->select();
            $lists = $this->model->field(true)->where($this->listCondition)->order('create_time', 'desc')->page($page)->limit($pageSize)->select();
            if ($lists) {
                $user = [];
                if ($this->lang == 'zh') {
                    $from = '由';
                    $to = '改为';
                } else {
                    $from = ' Change from ';
                    $to = ' to ';
                }
                foreach ($lists as &$d) {
                    $user_id = $d['create_id'];
                    if (!isset($user[$user_id])) {
                        $user[$user_id] = Cache::store('user')->getOneUser($user_id);
                    }
                    $d['user'] = ['realname' => empty($user[$user_id]['realname']) ? '-' : $user[$user_id]['realname']];

                    $d['status'] = $statusArr[$d['status']]?? '-';
                    if (is_array($d['new_value'])) {
                        $log = '';

                        foreach ($d['new_value'] as $name => $v) {
                            if ($name != 'sku') {
                                if (isset($const[$name])) {
                                    //发货类型再次映射
                                    if ($name == 'fulfillment_type') {
                                        $shipments = [1 => '卖家', 'FBA'];
                                        $log = $log . $const[$name] . ':'. $from. '[' . ($shipments[$d['old_value'][$name]]?? '-') . ']'. $to. '[' . ($shipments[$d['new_value'][$name]]?? '-') . ']' . '<br />';
                                    } else {
                                        $log = $log . $const[$name] . ':'. $from. '[' . $d['old_value'][$name] . ']'. $to. '[' . $d['new_value'][$name] . ']' . '<br />';
                                    }
                                } else {
                                    $new_value = $d['new_value'][$name];
                                    if (!is_string($new_value)) {
                                        $new_value = json_encode($new_value);
                                    }
                                    $log = $log . $name . ':'. $from. '[' . $d['old_value'][$name] . ']'. $to. '[' . $new_value . ']' . '<br />';
                                }
                            } else {
                                if ($this->lang == 'zh') {
                                    $log = $log . '修改了SKU信息。';
                                } else {
                                    $log = $log . ' Changed SKU information.';
                                }
                            }

                        }
                    } else {
                        $log = $d['new_value'];
                    }
                    $d['log'] = $log;
                }

                $data['data'] = $lists;

            }
        } else {
            $data['data'] = [];
        }

        return $data;
    }


    /**
     * 修改产品数据
     * @param $data string josn格式，例：[{"amazon_listing_id":"0831UBC0232","account_id":3247,"new_value":"1000","old_value":100}]
     * @param $scene string 'quantity'
     * @param $uid
     * @param string $remark
     * @param int $cron_time 定时执行时间
     * @param int $callback_type 回调类型，接到通知后自动去调用回调方法
     * @return array
     */
    public function editListingData($data, $scene, $uid, $remark = '', $cron_time = 0, $callback_type = 0, $callback_param = '')
    {
        try {
            $products = json_decode($data, true);

            $validate = new AmazonListingValidate;
            if ($error = $validate->checkEdit($products, $scene)) {
                return ['result' => false, 'message' => $error];
            }
            $timestamp = time();
            foreach ($products as $key => &$product) {
                $where = [
                    //***这里比较重要，之前这里是amazon_listing_id，后因为listing拉取会慢，所以两表通过id来关联；
                    'id' => ['=', $product['amazon_listing_id']],
                    'account_id' => ['=', $product['account_id']],
                ];

                $row = AmazonListing::where($where)->field('*')->find();
                if ($row) {
                    $amazon_listing_id = $row['id'];
                    $account_id = $row['account_id'];

                    $new_data = [$scene => $this->checkNewDataFromType($product['new_value'], $scene)];
                    $old_data = [$scene => $product['old_value']];

                    $map = [
                        'amazon_listing_id' => ['=', $amazon_listing_id],
                        'account_id' => ['=', $account_id],
                        'new_value' => ['=', json_encode($new_data)],
                        'create_id' => ['=', $uid],
                        'status' => ['=', 0],
                    ];

                    $log = [
                        'amazon_listing_id' => $amazon_listing_id,
                        'account_id' => $product['account_id'],
                        'seller_sku' => $row['seller_sku'],
                        'site' => 0,
                        'type' => self::$scence_type[$scene],
                        'callback_type' => $callback_type,
                        'callback_param' => $callback_param,
                        'old_value' => json_encode($old_data),
                        'new_value' => json_encode($new_data),
                        'create_id' => $uid,
                        'create_time' => time(),
                        'cron_time' => is_string($cron_time) ? strtotime($cron_time) : 0,
                        'remark' => $remark,
                    ];

                    if ($this->saveAmazonActionLog($map, $log)) {
                        AmazonListing::where($where)->update(['is_action_log' => 1]);
                    }
                } else {
                    throw new JsonErrorException("没有Listing-ID:{$product['amazon_listing_id']}的记录");
                }
            }
            if ($this->lang == 'zh') {
                return ['result' => true, 'message' => '修改成功'];
            } else {
                return ['result' => true, 'message' => 'Modification is successful.'];
            }

        } catch (JsonErrorException $exp) {
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }


    /**
     * 根据不同的更改类别来过滤不同的数据；
     * @param $data
     * @param $type
     * @return array
     */
    public function checkNewDataFromType($data, $type)
    {
        switch ($type) {
            case self::TYPE_IMAGE:
                $data = $this->checkImageData($data);
                break;
            case self::TYPE_QUANTITY:
                $data = intval($data);
                break;
            default:
                break;
        }
        return $data;
    }


    /**
     * 过滤图片数据，防止图片过多；
     * @param $images
     * @return array
     */
    public function checkImageData($images)
    {
        $new = [];
        $default = '';
        $swatch = '';
        $pt = [];
        foreach ($images as $val) {
            if ($val['is_default'] == 1 || $val['is_swatch'] == 1) {
                if ($val['is_default'] == 1 && $default == '') {
                    $default = $val['path'];
                }
                if ($val['is_swatch'] == 1 && $swatch == '') {
                    $swatch = $val['path'];
                }
            } else {
                if (count($pt) < 7 && !in_array($val['path'], $pt)) {
                    $pt[] = $val['path'];
                }
            }
        }
        if ($default != '' && $default == $swatch) {
            $new[] = ['is_default' => 1, 'is_swatch' => true, 'path' => $default];
        } else {
            if ($default != '') {
                $new[] = ['is_default' => 1, 'is_swatch' => false, 'path' => $default];
            }
            if ($swatch != '') {
                $new[] = ['is_default' => 0, 'is_swatch' => true, 'path' => $swatch];
            }
        }
        foreach ($pt as $val) {
            $new[] = ['is_default' => 0, 'is_swatch' => false, 'path' => $val];
        }
        return $new;
    }

    /**
     * 保存修改日志
     * @param $where
     * @param $data
     */
    public function saveAmazonActionLog($where, $data)
    {
        try {
            $model = new AmazonActionLog();
            if ($has = AmazonActionLog::where($where)->find()) {
                $id = $has['id'];
                $return = AmazonActionLog::where('id', '=', $has['id'])->update($data);
            } else {
                $return = $model->save($data);
                $id = $model->id;
            }

            //$cron_time = isset($data['cron_time']) ? strtotime($data['cron_time']) : 0;
            //这里已经更换队列执行方式，不再在这里进行PUSH队列
            //(new UniqueQueuer(AmazonActionLogQueue::class))->push($id, $cron_time);
            return $return;
        } catch (JsonErrorException $exp) {
            throw new JsonErrorException($exp->getFile() . $exp->getLine() . $exp->getMessage());
        }
    }

    /**
     * @title 获取未刊登成功的修改日志
     * getActionLogsByAccount
     * @param $accountId
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getActionLogsByAccount($accountId)
    {
        $where = ['account_id' => $accountId, 'status' => ['IN', [0, 2]]];
        return (new AmazonActionLog())->with('product')->where($where)->field(true)->select();
    }


    public function getSubmissionIdByAccount(int $accountId, $accountType, $maxRunNum = 30)
    {
        $where = [
            'type' => $accountType,
            'account_id' => $accountId,
            'status' => 0,
            'create_time' => ['>', strtotime('-1 days')],
            'request_number' => ['<', $maxRunNum],
        ];
        $model = new AmazonActionLog();
        $listings = $model->where($where)->limit(1000)->select();

        if (empty($listings)) {
            return 0;
        }
        $ids = [];
        foreach ($listings as $val) {
            $ids[] = $val['id'];
        }

        try {
            $help = new AmazonPublishHelper();
            $submissionId = $help->publishActionLog($accountType, $listings);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $model->update(['status' => 2, 'run_time' => time(), 'message' => $msg], ['id' => ['in', $ids]]);
            throw new Exception($e);
        }

        $update = [
            'status' => empty($submissionId) ? 0 : 3,
            'submission_id' => (int)$submissionId,
            'run_time' => time()
        ];

        foreach ($listings as $val) {
            $tmp = $update;
            $tmp['request_number'] = $val['request_number'] + 1;
            if ($tmp['request_number'] >= $maxRunNum && $update['status'] == 0) {
                $tmp['status'] = 2;
                $tmp['message'] = '上传超过限制次数';
            }

            $model->update($tmp, ['id' => $val['id']]);
        }

        return $submissionId;
    }


    public function actionLogResult($accountId, $submissionId)
    {
        $where = [
            'account_id' => $accountId,
            'submission_id' => $submissionId,
            //'status' => 3 //可以重复；
        ];
        $lists = $this->model->where($where)->order('id desc')->select();
        if (empty($lists)) {
            return true;
        }
        $feedResult = (new AmazonPublishHelper())->publishResult($accountId, $submissionId);
        if (empty($feedResult)) {
            return false;
        }
        $serv = new AmazonPublishHelper();
        $resultArr = $serv->xmlToArray($feedResult);


        //刊登结果，可能有警告信息，也可能有失败信息；
        $errorNum = $resultArr['Message']['ProcessingReport']['ProcessingSummary']['MessagesWithError'];
        $errors = [];
        if ($errorNum > 0) {
            $results = [];
            if (!empty($resultArr['Message']['ProcessingReport']['Result'])) {
                $results = $resultArr['Message']['ProcessingReport']['Result'];
                if (isset($results['MessageID'])) {
                    $results = [$results];
                }
            }

            $resultServ = new AmazonPublishResultService();
            $errors = $resultServ->getPublicResultErrors($results);
            $errors = empty($errors['Error']['sku']) ? [] : $errors['Error']['sku'];
        }

        foreach ($lists as $val) {
            if (!empty($errors[$val['seller_sku']])) {
                $update = ['status' => 2, 'message' => $errors[$val['seller_sku']], 'run_time' => time()];
            } else {
                $update = ['status' => 1, 'run_time' => time()];
            }
            $val->save($update);

            //检测是否需要回调；
            $callback = $this->callback_type_method[$val['callback_type']] ?? [];
            if (empty($callback)) {
                continue;
            }
            try {
                call_user_func_array([(new $callback['class']()), $callback['method']], [$val]);
            } catch (Exception $e) {
                $val->save(['callback_status' => 2, 'message' => $e->getMessage()]);
            }
        }
    }
}