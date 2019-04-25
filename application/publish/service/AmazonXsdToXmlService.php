<?php
namespace app\publish\service;

use app\common\cache\Cache;
use app\common\cache\driver\Channel;
use app\common\cache\driver\Lock;
use app\common\model\amazon\AmazonCategoryXsd;
use app\common\model\amazon\AmazonPublishProductAttach;
use app\common\model\amazon\AmazonPublishProductDetail;
use app\common\model\amazon\AmazonPublishProductSubmission;
use app\common\model\amazon\AmazonXsdTemplate;
use app\common\model\amazon\AmazonXsdTemplateDetail;
use app\common\model\amazon\AmazonXsdTemplateVariant;
use app\common\model\GoodsSkuMap;
use app\common\model\GoodsTortDescription;
use app\common\service\ChannelAccountConst;
use app\common\service\UniqueQueuer;
use app\goods\service\GoodsHelp;
use app\goods\service\GoodsImage;
use app\goods\service\GoodsSkuMapService;
use app\publish\queue\AmazonPublishProductResultQueuer;
use think\Db;
use think\Exception;
use app\common\model\amazon\AmazonCategoryAttributeXsd;
use app\common\model\amazon\AmazonPublishProduct;
use app\common\model\amazon\AmazonPublishProductJson;
use app\common\model\amazon\AmazonPublishProductVariant;
use Waimao\AmazonMws\AmazonMultiFeed;

class AmazonXsdToXmlService
{

    /** @var int 自增 */
    public $messageId = 1;

    public $max_time = 86400 * 5;

    /** @var int 每条记录最大刊登条数 */
    public $max_total = 500;

    /** @var int 放进拿取结果时间 */
    public $waitResultTime = 300;

    /** @var Lock|null 加锁 */
    public $lock = null;

    /** @var null 产品Model */
    public $productModel = null;

    /** @var null 详情Model */
    public $detailModel = null;

    /** @var null submissionModel */
    public $submissionModel = null;

    /** @var bool true:正式生产,false:测试 */
    public $productionPublish = true;

    public function __construct()
    {
        $this->lock = new Lock();
        $this->productModel = new AmazonPublishProduct();
        $this->detailModel = new AmazonPublishProductDetail();
        $this->submissionModel = new AmazonPublishProductSubmission();
    }


    public function headerXml($index = 1)
    {
        $header = '<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amznenvelope.xsd"><Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>merchantId</MerchantIdentifier></Header><MessageType>Product</MessageType><PurgeAndReplace>false</PurgeAndReplace>';
        return $header;
    }


    public function floorXml()
    {
        return "</AmazonEnvelope>";
    }


    public $rePublishErrArr = [
        'General error: 1205 Lock wait timeout exceeded',
        'General error: 2006 MySQL server has gone away',
        'QuotaExceeded', //QuotaExceeded:You exceeded your quota of 30.0 requests per 1 hour for operation Feeds/2009-01-01/SubmitFeed. Your quota will reset on Mon Mar 25 02:25:00 UTC 2019
        'RequestThrottled',    //RequestThrottled:Request is throttled,
        //'AccessDenied',    //AccessDenied:Access to Feeds.SubmitFeed is denied
        //'InvalidMarketplace',   //InvalidMarketplace:Feed rejected
    ];


    /** @var array 提交时出现的报错 */
    public $submitErrArr = [
        ['code' => 'QuotaExceeded', 'replace' => false], //QuotaExceeded:You exceeded your quota of 30.0 requests per 1 hour for operation Feeds/2009-01-01/SubmitFeed. Your quota will reset on Mon Mar 25 02:25:00 UTC 2019
        ['code' => 'RequestThrottled', 'replace' => false],    //RequestThrottled:Request is throttled,
        ['code' => 'AccessDenied', 'replace' => '请找帐号部检查帐号授权或帐号是否降级'],    //AccessDenied:Access to Feeds.SubmitFeed is denied
        ['code' => 'InvalidMarketplace', 'replace' => '请找帐号部检查帐号是否开通当前站点权限'],   //InvalidMarketplace:Feed rejected
    ];

    /**
     * 找到相匹配的错误信息，把替换信息返回去
     * @param $error_message
     * @return mixed
     */
    public function checkSubmitErr($error_message)
    {
        foreach ($this->submitErrArr as $err) {
            if (strpos($error_message, $err['code']) !== false) {
                return $err['replace'];
            }
        }
        return $error_message;
    }


    /**
     * @title 放进获取结果队列；
     * @param $subid submission表ID
     * @param int $wait 等待时间，最小120，默认便用类标时间
     */
    public function pushResultQueue($subid, $wait = 0)
    {
        $wait = empty($wait) && $wait < 120 ? $this->waitResultTime : $wait;
        (new UniqueQueuer(AmazonPublishProductResultQueuer::class))->push($subid, $wait);
    }


    /**
     * @title 根据帐号和刊登类别刊登
     * @param $accountId
     * @param int $type 1 => 'product', 2 => 'relation', 3 => 'quantity', 4 => 'image', 5 => 'price'
     * @return bool true,完成; false,未完成
     * @throws Exception
     */
    public function publishTypeXmlFromAccount($accountId, $type = 1)
    {
        //验证状号状态；
        $account = $this->checkAccount($accountId, true);
        $time = time();
        $typeArr = [1 => 'product', 2 => 'relation', 3 => 'quantity', 4 => 'image', 5 => 'price'];

        //1.加锁，失败则证明重了，需要下次处理；
        $lockParams = ['action' => 'publish', 'type' => $typeArr[$type], 'account_id' => $accountId];
        //$this->lock->unlock($lockParams);
        //此处使用唯一锁,锁住120秒，足够完成所有查询了；
        if (!$this->lock->uniqueLock($lockParams, 120)) {
            return false;
        }

        $where = [
            'account_id' => $accountId,
            'update_time' => ['>', $time - $this->max_time], //几天前的重新刊登；
        ];

        if ($type == 1) {
            $where['timer'] = 0;
            $where['publish_status'] = 0;
            $where['product_status'] = 0;
        } else {
            //产品在记录；
            $where['publish_status'] = ['in', [0,1]];
            //产品已刊登完成；
            $where['product_status'] = 2;
            $where[$typeArr[$type]. '_status'] = 0;
        }

        //2.找出所有待刊登的的记录；
        $pids = $this->productModel->where($where)->field('id')->limit($this->max_total)->column('id');
        //需要刊登的ID；
        $pids = empty($pids) ? [] : $pids;

        /*
         * amazon_publish_product_submission状态说明；
         * status: 0.未提交；1.提交中；2.已完成，3意外失败；
         */
        $subData = $this->submissionModel->where([
                'account_id' => $accountId,
                'create_time' => ['>', $time - 86400],
                'type' => $type,
                'status' => 0
            ])->limit(100)->select();

        //装要送过去生成xml的submission组ID；
        $subToPublishIds = $this->getSubToPublishIds($subData, $pids, $type);

        $postDataTypeArr = [
            1 => '_POST_PRODUCT_DATA_',
            2 => '_POST_PRODUCT_RELATIONSHIP_DATA_',
            3 => '_POST_INVENTORY_AVAILABILITY_DATA_',
            4 => '_POST_PRODUCT_IMAGE_DATA_',
            5 => '_POST_PRODUCT_PRICING_DATA_',
        ];

        //详情的状态；
        $fieldArr = [1 => 'upload_product', 'upload_relation', 'upload_quantity', 'upload_image', 'upload_price'];
        $buildXmlType = [1 => 'Product', 2 => 'Relationship', 3 => 'Inventory', 4 => 'ProductImage', 5 => 'Price'];
        //刊登帐号；
        $publishHelper  = new AmazonPublishHelper();

        foreach ($subToPublishIds as $subid => $pidArr) {
            try {
                //定义messageId，从1开始；
                $this->initMessageId();
                //单次刊登的XML；
                $msgXml = $this->buildTypexmlFromSubData($subid, $pidArr, $type);

                //刊登成功是没有数据的；
                if (empty($msgXml)) {
                    continue;
                }

                //连接上头部和足部；
                $xml = $this->buildEnvelope($account['merchant_id'], $buildXmlType[$type], $msgXml);

                //保存缓存
                //Cache::store('AmazonTemplate')->savePublishXml($subid, $xml);

                //以下开始上传XML，并保存提交结果ID；
                $submissionId = $publishHelper->publishProductByType($account['id'], $xml, $postDataTypeArr[$type]);

                //没有获取submissionId，放入重新刊登队列；在task里面拿出来进行刊登
                if(empty($submissionId)){
                    $this->changeSubDataStatus($subid, $pidArr, $type, 0);

                    $start_time = $this->submissionModel->where(['id' => $subid])->value('create_time');
                    if ($start_time > 0) {
                        $monuts = floor((time() - $start_time) / 60);
                        if ($fieldArr[$type] == 'upload_product' && $monuts > 240) {
                            $notice = json_encode([
                                $fieldArr[$type] => '自动刊登失败，首次刊登时间为：'. date('Y-m-d H:i', $start_time). '，已持续提交刊登：'. $monuts. '分钟，排除网络故障后请检查授权的方面原因，可以对比一下最近有没有下载到订单信息，确认没有问题后，可以用刊登记录列表编辑按钮，进行重新编辑刊登。'
                            ], JSON_UNESCAPED_UNICODE);
                            $this->productModel->update(['publish_status' => 3], ['id' => ['in', $pidArr], 'publish_status' => ['<>', 2]]);
                            $this->detailModel->update(
                                [$fieldArr[$type] => 2, 'error_message' => $notice, 'warning_message' => '[]'],
                                ['product_id' => ['in', $pidArr], $fieldArr[$type] => ['<>', 1]]
                            );
                        } else if ($monuts > 120) {
                            $notice = json_encode([
                                $fieldArr[$type] => '自动刊登失败，首次刊登时间为：'. date('Y-m-d H:i', $start_time). '，已持续提交刊登：'. $monuts. '分钟，排除网络故障后请检查授权的方面原因，可以对比一下最近有没有下载到订单信息，上传状态为待上传时，将继续尝试提交刊登，直到240分钟后将上传状态标记为上传失败。'
                            ], JSON_UNESCAPED_UNICODE);
                            $this->detailModel->update(
                                [$fieldArr[$type] => 2, 'warning_message' => $notice],
                                ['product_id' => ['in', $pidArr], $fieldArr[$type] => ['<>', 1]]
                            );
                        }
                    }
                    continue;
                } else {
                    $this->submissionModel->update(['submission_id' => $submissionId, 'last_request_time' => $time], ['id' => $subid]);
                    $this->pushResultQueue($subid);
                }
            } catch (Exception $e) {
                //本来是想失败，但是后来好像这种失没有什么意思，只的超时，或者访问次数过多，导致失败;
                $this->lock->unlock($lockParams);
                throw new Exception($e->getMessage());
            }
        }

        //解锁
        $this->lock->unlock($lockParams);
        return true;
    }

    public function buildTypexmlFromAccount($account, $type, $errMessage = '')
    {
        $time = time();
        $typeArr = [1 => 'product', 2 => 'relation', 3 => 'quantity', 4 => 'image', 5 => 'price'];

        $where = [
            'account_id' => $account['id'],
            'update_time' => ['>', $time - $this->max_time], //几天前的重新刊登；
        ];

        if ($type == 1) {
            $where['timer'] = 0;
            $where['publish_status'] = 0;
            $where['product_status'] = 0;
        } else {
            //产品在记录；
            $where['publish_status'] = ['in', [0,1]];
            //产品已刊登完成；
            $where['product_status'] = 2;
            $where[$typeArr[$type]. '_status'] = 0;
        }

        //2.找出所有待刊登的的记录；
        $pids = $this->productModel->where($where)->field('id')->limit($this->max_total)->column('id');
        //需要刊登的ID；
        $pids = empty($pids) ? [] : $pids;

        /*
         * amazon_publish_product_submission状态说明；
         * status: 0.未提交；1.提交中；2.已完成，3意外失败；
         */
        $subData = $this->submissionModel->where([
            'account_id' => $account['id'],
            'create_time' => ['>', $time - 86400],
            'type' => $type,
            'status' => 0
        ])->limit(100)->select();

        //装要送过去生成xml的submission组ID；
        $subToPublishIds = $this->getSubToPublishIds($subData, $pids, $type);

        $postDataTypeArr = [
            1 => '_POST_PRODUCT_DATA_',
            2 => '_POST_PRODUCT_RELATIONSHIP_DATA_',
            3 => '_POST_INVENTORY_AVAILABILITY_DATA_',
            4 => '_POST_PRODUCT_IMAGE_DATA_',
            5 => '_POST_PRODUCT_PRICING_DATA_',
        ];

        $buildXmlType = [1 => 'Product', 2 => 'Relationship', 3 => 'Inventory', 4 => 'ProductImage', 5 => 'Price'];

        foreach ($subToPublishIds as $subid => $pidArr) {
            //上面帐号刊登出错；
            if (!empty($errMessage)) {
                //剩下的肯定是有返回错误信息的；
                $notice = json_encode([
                    'upload_'. $typeArr[$type] => $errMessage
                ], JSON_UNESCAPED_UNICODE);

                try {
                    Db::startTrans();
                    $this->submissionModel->Update(['status' => 3], ['id' => $subid]);
                    $this->productModel->update(['publish_status' => 3, $typeArr[$type]. '_status' => 0], ['id' => ['in', $pidArr], 'publish_status' => ['<>', 2]]);
                    $this->detailModel->update(
                        ['upload_'. $typeArr[$type] => 2, 'error_message' => $notice, 'warning_message' => '[]'],
                        ['product_id' => ['in', $pidArr], 'upload_'. $typeArr[$type] => ['<>', 1]]
                    );
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    throw $e;
                }
                continue;
            }

            //定义messageId，从1开始；
            $this->initMessageId();
            //单次刊登的XML；
            $msgXml = $this->buildTypexmlFromSubData($subid, $pidArr, $type);

            //刊登成功是没有数据的；
            if (empty($msgXml)) {
                continue;
            }

            //连接上头部和足部；
            $xml = $this->buildEnvelope($account['merchant_id'], $buildXmlType[$type], $msgXml);
            return [
                'account_id' => $account['id'],
                'xml' => $xml,
                'type' => $postDataTypeArr[$type],
                'subid' => $subid,
                'pids' => $pidArr
            ];
        }
        return [];
    }


    /**
     * @title 根据帐号和刊登类别刊登
     * @param $accountId
     * @param int $type 1 => 'product', 2 => 'relation', 3 => 'quantity', 4 => 'image', 5 => 'price'
     * @return bool true,完成; false,未完成
     * @throws Exception
     */
    public function publishTypeXmlFromSeller($sellerId, $type = 1)
    {
        $time = time();
        $typeArr = [1 => 'product', 2 => 'relation', 3 => 'quantity', 4 => 'image', 5 => 'price'];

        //1.加锁，失败则证明重了，需要下次处理；
        $lockParams = ['action' => 'publish', 'type' => $typeArr[$type], 'seller_id' => $sellerId];
        //$this->lock->unlock($lockParams);
        //此处使用唯一锁,锁住120秒，足够完成所有查询了；
        if (!$this->lock->uniqueLock($lockParams, 120)) {
            return false;
        }

        $where = [
            'creator_id' => $sellerId,
            'update_time' => ['>', $time - $this->max_time], //几天前的重新刊登；
        ];

        if ($type == 1) {
            //1)找未定时刊登的；
            $where['timer'] = 0;
            $where['publish_status'] = 0;
            $where['product_status'] = 0;
            $accountIds = $this->productModel->where($where)->group('account_id')->field('account_id')->limit(100)->column('account_id');

            //2找定时刊登的；
            $where['timer'] = ['BETWEEN', [$time - $this->max_time, $time + 900]];
            $where['publish_status'] = 1;
            $accountIds2 = $this->productModel->where($where)->group('account_id')->field('account_id')->limit(100)->column('account_id');
            $accountIds = array_values(array_unique(array_merge($accountIds, $accountIds2)));
        } else {
            //产品在记录；
            $where['publish_status'] = ['in', [0,1]];
            //产品已刊登完成；
            $where['product_status'] = 2;
            $where[$typeArr[$type]. '_status'] = 0;
            $accountIds = $this->productModel->where($where)->group('account_id')->field('account_id')->limit(100)->column('account_id');
        }

        //2.找出所有待刊登的的记录；
        if (empty($accountIds)) {
            $this->lock->unlock($lockParams);
            return true;
        }

        $datas = [];
        foreach ($accountIds as $accountId) {
            $errMessage = '';
            $account = [];
            try {
                //验证状号状态；
                $account = $this->checkAccount($accountId, true);
            } catch (Exception $e) {
                $errMessage = $e->getMessage();
                $account = $this->amazonAccount;
            }
            if (empty($account)) {
                continue;
            }
            $data = $this->buildTypexmlFromAccount($account, $type, $errMessage);
            if (!empty($data)) {
                $datas[] = $data;
            }
        }

        //返回的参数为空，则解锁，跳过；
        if (empty($datas)) {
            $this->lock->unlock($lockParams);
            return true;
        }

        //详情的状态；
        $fieldArr = [1 => 'upload_product', 'upload_relation', 'upload_quantity', 'upload_image', 'upload_price'];

        try {
            //curl并发提交；
            $help = new AmazonMultiFeed();
            //添加数据；
            $help->addFeedRequestList($datas);
            //提交；
            $help->submit();
            //获取结果；
            $responseList = $help->getResponses();
        } catch (Exception $e) {
            $this->lock->unlock($lockParams);
            throw new Exception($e->getMessage());
        }

        //组合结果；
        foreach ($datas as $val) {
            try {
                $xml = $val['xml'];
                $subid = $val['subid'];
                $pidArr = $val['pids'];

                //保存缓存
                //Cache::store('AmazonTemplate')->savePublishXml($subid, $xml);

                $response = $responseList[$val['account_id']] ?? [];

                //成功了；
                if (isset($response['status']) && $response['status'] === 1) {
                    $this->submissionModel->update(['submission_id' => $response['submissionId'], 'last_request_time' => $time], ['id' => $subid]);
                    $this->pushResultQueue($subid);
                    continue;
                }

                $typeStatusArr = [1 => 'product_status', 2 => 'relation_status', 3 => 'quantity_status', 4 => 'image_status', 5 => 'price_status'];
                //两种情况，一种没有返回错误信息；
                if (!empty($response['error_message'])) {

                    $response['error_message'] = $this->checkSubmitErr( $response['error_message']);

                    //忽略的错误；
                    if ($response['error_message'] === false) {
                        $this->changeSubDataStatus($subid, $pidArr, $type);
                        continue;
                    }

                    //剩下的肯定是有返回错误信息的；
                    $notice = json_encode([
                        $fieldArr[$type] => $response['error_message']
                    ], JSON_UNESCAPED_UNICODE);

                    try {
                        Db::startTrans();
                        $this->submissionModel->Update(['status' => 3], ['id' => $subid]);
                        $this->productModel->update(['publish_status' => 3, $typeStatusArr[$type] => 0], ['id' => ['in', $pidArr], 'publish_status' => ['<>', 2]]);
                        $this->detailModel->update(
                            [$fieldArr[$type] => 2, 'error_message' => $notice, 'warning_message' => '[]'],
                            ['product_id' => ['in', $pidArr], $fieldArr[$type] => ['<>', 1]]
                        );
                        Db::commit();
                    } catch (Exception $e) {
                        Db::rollback();
                        throw $e;
                    }
                    continue;
                }

                //以下为出错了的；
                $start_time = $this->submissionModel->where(['id' => $subid])->value('create_time');
                //算出分钟数；
                $monuts = floor((time() - $start_time) / 60);
                if ($fieldArr[$type] == 'upload_product' && $monuts > 240) {
                    $notice = json_encode([
                        $fieldArr[$type] => '自动刊登失败，首次刊登时间为：'. date('Y-m-d H:i', $start_time). '，已持续提交刊登：'. $monuts. '分钟，请联系帐号部检查帐号授权和是否降级'
                    ], JSON_UNESCAPED_UNICODE);
                    try {
                        Db::startTrans();
                        $this->submissionModel->Update(['status' => 3], ['id' => $subid]);
                        $this->productModel->update(['publish_status' => 3, $typeStatusArr[$type] => 0], ['id' => ['in', $pidArr], 'publish_status' => ['<>', 2]]);
                        $this->detailModel->update(
                            [$fieldArr[$type] => 2, 'error_message' => $notice, 'warning_message' => '[]'],
                            ['product_id' => ['in', $pidArr], $fieldArr[$type] => ['<>', 1]]
                        );
                        Db::commit();
                    } catch (Exception $e) {
                        Db::rollback();
                        throw $e;
                    }
                    continue;
                }

                //以下是需要重试的；
                $this->changeSubDataStatus($subid, $pidArr, $type);
                if ($monuts > 120) {
                    $notice = json_encode([
                        $fieldArr[$type] => '自动刊登失败，首次刊登时间为：'. date('Y-m-d H:i', $start_time). '，已持续提交刊登：'. $monuts. '分钟，排除网络故障后请检查授权的方面原因，可以对比一下最近有没有下载到订单信息，上传状态为待上传时，将继续尝试提交刊登，直到240分钟后将上传状态标记为上传失败。'
                    ], JSON_UNESCAPED_UNICODE);
                    $this->detailModel->update(
                        [$fieldArr[$type] => 2, 'warning_message' => $notice],
                        ['product_id' => ['in', $pidArr], $fieldArr[$type] => ['<>', 1]]
                    );
                }
            } catch (Exception $e) {
                $this->lock->unlock($lockParams);
                throw new $e;
            }
        }

        //解锁；
        $this->lock->unlock($lockParams);
    }


    public function getSubToPublishIds($subData, $pids, $type = 1)
    {
        //装要送过去生成xml的submission组ID；
        $subToPublishIds = [];

        //已使用的ID；
        $allIds = [];
        //把这些没有刊登的送进去刊登；
        foreach ($subData as $sub) {
            //查看pids个数；
            $tmp = [];
            $total = 0;
            if (!empty($sub['pids'])) {
                $tmp = explode(',', $sub['pids']);
                foreach ($tmp as $key => $pid) {
                    if (in_array($pid, $allIds)) {
                        unset($tmp[$key]);
                    }
                }
                //重新计算一下个数；
                $total = count($tmp);
                $allIds = array_merge($allIds, $tmp);

                //清除PIDS里面已经存在的元素；
                foreach ($pids as $key => $pid) {
                    if (in_array($pid, $tmp)) {
                        unset($pids[$key]);
                    }
                }
            }

            //超过最大刊登数了直接送过去刊登；
            if ($total < $this->max_total) {
                $total = count($tmp);
                if (!empty($pids)) {
                    //算出需要截取数组的个数；
                    $splice_total = $this->max_total - $total;
                    $newids = array_splice($pids, 0, $splice_total);
                    //放回去的刊登ID；
                    $tmp = array_merge($tmp, $newids);
                    //把新的PID放回进去排重；
                    $allIds = array_merge($allIds, $newids);
                }
            }

            //更新一次表；
            $update = [];
            $update['pids'] = implode(',', $tmp);
            $update['total'] = count($tmp);
            if (empty($tmp)) {
                $update['status'] = 3;
            }
            $this->submissionModel->update($update, ['id' => $sub['id']]);

            if (!empty($tmp)) {
                $subToPublishIds[$sub['id']] = $tmp;
            }
        }

        //经过上面处理，如果还有多条的刊登记录；则需要生成新的submissionData；
        if ($pids) {
            $account = $this->getPublishAccount();
            $pids = array_chunk($pids, $this->max_total);
            foreach ($pids as $pidArr) {
                $newsub = [
                    'pids' => implode(',', $pidArr),
                    'total' => count($pidArr),
                    'type' => $type,    //刊登关系
                    'account_id' => $account['id'] ?? 0,
                    'submission_id' => '',
                    'create_time' => time(),
                ];
                $subId = $this->submissionModel->insertGetId($newsub);
                $subToPublishIds[$subId] = $pidArr;
            }
        }

        return $subToPublishIds;
    }


    /**
     * 根据一条submission数据生成产品xml；
     * @param $subid
     * @param $pidArr
     * @return string
     * @throws Exception
     */
    public function buildTypexmlFromSubData($subid, $pidArr, $type)
    {
        $typeStatusArr = [1 => 'product_status', 2 => 'relation_status', 3 => 'quantity_status', 4 => 'image_status', 5 => 'price_status'];
        $detailStatusArr = [1 => 'upload_product', 2 => 'upload_relation', 3 => 'upload_quantity', 4 => 'upload_image', 5 => 'upload_price'];
        try {
            $time = time();
            //从这里一起开始，如果开始刊登中不成功，则返回，下次继续；
            try {
                Db::startTrans();
                //把当前这条标记为刊登中；
                $this->submissionModel->update(['status' => 1, 'last_request_time' => $time], ['id' => $subid]);
                //标成功的和失败的标记为1；
                $this->productModel->update(
                    ['publish_status' => 1, $typeStatusArr[$type] => 1, 'update_time' => $time],
                    ['id' => ['in', $pidArr], 'publish_status' => ['<>', 2]]    //这里很重要，只把待登的变成已刊登
                );
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                return false;
            }

            //初始的刊登记录条数；
            $startTotal = count($pidArr);

            $xml = '';
            $perror = [];
            $pcode = [];
            $success = [];
            foreach ($pidArr as $key=>$pid) {
                try {
                    $data = $this->buildTypeSimpleXml($pid, $type);
                } catch (\Exception $e) {
                    $data['code'] = 10;
                    $data['message'] = $e->getMessage();
                }
                $pcode[$pid] = $data['code'];
                //出现问题；
                if ($data['code'] !== 0) {
                    unset($pidArr[$key]);
                    if (empty($data['message'])) {
                        $error = json_encode([$detailStatusArr[$type] => $this->getBuildXmlError($data['code'])], JSON_UNESCAPED_UNICODE);
                    } else {
                        $error = json_encode([$detailStatusArr[$type] => $data['message']], JSON_UNESCAPED_UNICODE);
                    }
                    $perror[$pid] =  $error;
                    //Cache::handler()->hSet('task:amazon:publish:error:'. $pid, $type, $error);
                } else {    //成功；
                    if ($data['xml'] === '') {
                        unset($pidArr[$key]);
                        $success[] = $pid;
                    }
                    $xml .= $data['xml'];
                }
            }
            //如果有出错的，或者成功的；
            if ($startTotal != count($pidArr)) {
                try {
                    Db::startTrans();
                    //先解决错误；
                    if (!empty($perror)) {
                        foreach ($perror as $pid=>$error) {
                            //有些错误不是刊登中的错误，可能是服务器的错误，需要忽略掉，下回刊登时能重新刊登上
                            if ($this->ignoreError($error)) {
                                $this->productModel->update(
                                    ['publish_status' => 0, $typeStatusArr[$type] => 0, 'update_time' => time()],
                                    ['id' => $pid, 'publish_status' => 1]    //这里很重要，是把刊登中的还原成待刊登的
                                );
                                continue;
                            }
                            if ($pcode[$pid] == 5 && $type != 1) {
                                $this->productModel->update(['publish_status' => 0, 'product_status' => 0, $typeStatusArr[$type] => 0], ['id' => $pid, 'publish_status' => ['<>', 2]]);
                            } else {
                                $this->productModel->update(['publish_status' => 3, $typeStatusArr[$type] => 0], ['id' => $pid, 'publish_status' => ['<>', 2]]);
                                $this->detailModel->update(
                                    [$detailStatusArr[$type] => 2, 'error_message' => $error],
                                    ['product_id' => $pid, $detailStatusArr[$type] => ['<>', 1]]
                                );
                            }
                        }
                    }
                    if (!empty($success)) {
                        $this->productModel->update([$typeStatusArr[$type] => 2], ['id' => ['in', $success]]);
                    }
                    //为空将状态改成未上传就好了；
                    if (empty($pidArr)) {
                        $this->submissionModel->update(['pids' => '', 'status' => 0, 'total' => 0], ['id' => $subid]);
                        Db::commit();
                        return false;
                    } else {
                        $this->submissionModel->update(['pids' => implode(',', $pidArr), 'total' => count($pidArr)], ['id' => $subid]);
                    }
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    throw new Exception($e->getMessage());
                }
            }
            return $xml;
        } catch (\Exception $e) {
            $this->changeSubDataStatus($subid, $pidArr, $type);
            return false;
        }
    }


    /**
     * 更新状态；
     * @param $subid
     * @param $pidArr
     */
    public function changeSubDataStatus($subid, $pidArr, $type, $status = 0)
    {
        $typeStatusArr = [1 => 'product_status', 2 => 'relation_status', 3 => 'quantity_status', 4 => 'image_status', 5 => 'price_status'];
        try {
            $time = time();
            Db::startTrans();
            //重新标记回未刊登；
            $this->productModel->update(['publish_status' => $status, $typeStatusArr[$type] => $status, 'update_time' => $time], ['id' => ['in', $pidArr], 'publish_status' => 1]);
            $this->submissionModel->update(['status' => $status, 'last_request_time' => $time], ['id' => $subid]);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
        }
    }


    /**
     * 是否忽略当前的错误
     * @param $msg
     * @return bool
     */
    public function ignoreError($msg)
    {
        if (empty($msg)) {
            return false;
        }
        $ignores = [
            'General error: 1205 Lock wait timeout exceeded',
            'General error: 2006 MySQL server has gone away',
        ];

        foreach ($ignores as $val) {
            if (strpos($msg, $val) !== false) {
                return true;
            }
        }
        return false;
    }


    /**
     * 根据不同的类型组成不同的XML；
     * @param $pid
     * @param $type
     * @return array|bool|string
     */
    public function buildTypeSimpleXml($pid, $type)
    {
        switch ($type) {
            case 1:
                $data = $this->buildProductSimpleXml($pid);
                break;
            case 2:
                $data = $this->buildRelationXml($pid, true);
                break;
            case 3:
                $data = $this->buildQuantityXml($pid, true);
                break;
            case 4:
                $data = $this->buildImageXml($pid, true);
                break;
            case 5:
                $data = $this->buildPriceXml($pid, true);
                break;
            default:
                $data = ['code' => 0, 'message' => '', 'xml' => ''];
                break;
        }
        return $data;
    }

    /**
     * @TITLE 生成需要上传最后的xml
     * @param $merchantId
     * @param $messageType
     * @param $msgXml
     * @return string
     * @throws Exception
     */
    private function buildEnvelope($merchantId, $messageType, $msgXml)
    {
        $typeArr = ['FulfillmentCenter', 'Inventory', 'Listings', 'OrderAcknowledgement', 'OrderAdjustment', 'OrderFulfillment', 'Override', 'Price', 'ProcessingReport', 'Product', 'ProductImage', 'Relationship', 'SettlementReport'];
        if (!in_array($messageType, $typeArr)) {
            throw new Exception('组成xml时，出现示知MessageType:' . $messageType);
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amznenvelope.xsd"><Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>' . $merchantId . '</MerchantIdentifier></Header><MessageType>' . $messageType . '</MessageType><PurgeAndReplace>false</PurgeAndReplace>';
        $xml .= $msgXml;
        $xml .= '</AmazonEnvelope>';

        return $xml;
    }

    /**
     * 检查一下amazon帐号ID
     * @param $accountId
     * @return mixed
     * @throws Exception
     */
    public function checkAccount($accountId, $reset = false)
    {

        if (!empty($this->amazonAccount) && !$reset) {
            return $this->amazonAccount;
        }
        //验证状号状态；
        $this->amazonAccount = $account = Cache::store('AmazonAccount')->getAccount($accountId);
        if (empty($account)) {
            throw new Exception('刊登记录的Amazon帐号不存在或无效');
        }
        if (empty($account['is_authorization'])) {
            throw new Exception('刊登记录的Amazon帐号未授权');
        }
        if (empty($account['status'])) {
            throw new Exception('刊登记录的Amazon帐号系统状态为停用');
        }
        if (empty($account['site'])) {
            throw new Exception('刊登记录的Amazon帐号没有设置站点');
        }
        $full = false;
        //第三方授权有没有；
        if (!empty($account['developer_access_key_id']) && !empty($account['developer_secret_key']) && !empty($account['auth_token'])) {
            $full = true;
        }
        //本帐号授权有没有；
        if (!empty($account['access_key_id']) && !empty($account['secret_key'])) {
            $full = true;
        }
        if ($full === false) {
            throw new Exception('刊登记录的Amazon帐号授权信息不完整');
        }

        return $this->amazonAccount;
    }

    public function getMessageId()
    {
        return $this->messageId++;
    }

    public function initMessageId()
    {
        $this->messageId = 1;
    }

    /**
     * 根据保存的产品模板ID找出产品元素，以nodetree为键;
     * @param $product
     * @return array
     */
    private function buildProductTemplateData($product)
    {

        $templateModel = new AmazonXsdTemplate();
        $templateDetailModel = new AmazonXsdTemplateDetail();

        $nodeArr = [];
        $product_info = $product['product_info'];
        //找出产品的模板数据，为空，真接反回空数组；
        if (empty($product_info)) {
            return $nodeArr;
        }

        $product_template_id = $product['product_template_id'];
        if ($product_template_id) {
            $list = $templateDetailModel->where(['amazon_xsd_template_id' => $product_template_id])->column('node_tree', 'name');
        } else {
            //先找出产品的默认模板；
            $template = $templateModel->where(['site' => $product['site'], 'type' => 2])->find();
            $list = $templateDetailModel->where(['amazon_xsd_template_id' => $template['id']])->column('node_tree', 'name');
        }

        $product_info = is_string($product_info) ? json_decode($product_info, true) : $product_info;

        //如果产品模板中存在该元素的node_tree则使用node_tree组成数组；
        foreach ($product_info as $key => $val) {
            if (strpos($key, '@') !== false) {
                $tmpArr = explode('@', $key);
                if (count($tmpArr) == 2 && isset($list[$tmpArr[0]])) {
                    $nodeArr[$list[$tmpArr[0]]]['attribute'] = ['name' => $tmpArr[1], 'value' => $val];
                }
            }
            if (isset($list[$key])) {
                $nodeArr[$list[$key]]['value'] = $val;
            }
        }

        return $nodeArr;
    }

    /**
     * 找出分类的元素，以node_tree为键放进数组；
     * @param $product
     * @return array
     * @throws Exception
     */
    private function buildFirstCategoryTemplateData($product, &$class_type_id, &$list)
    {
        $nodeArr = [];

        $templateModel = new AmazonXsdTemplate();
        //$templateModel->removeWhereField();
        $templateDetailModel = new AmazonXsdTemplateDetail();

        $category_template_id = $product['category_template_id'];

        //找出分类模板记录；
        $template = $templateModel->get($category_template_id);
        if (empty($template)) {
            throw new Exception('分类模板错误，ID:' . $category_template_id . '不存在;' . json_encode($template) . '；MYSQLSQL:' . $templateModel->getLastSql());
        }

        //从这里带出去class_type_id；
        $class_type_id = $template['class_type_id'];

        if ($category_template_id) {
            //找出分类具体数据；
            $list = $templateDetailModel->where(['amazon_xsd_template_id' => $category_template_id])->column('node_tree', 'name');
            if (empty($list)) {
                throw new Exception('分类模板详情错误，分类ID:' . $category_template_id . '不存在;' . json_encode($list) . '；MYSQLSQL:' . $templateDetailModel->getLastSql());
            }
        } else {
            //因为分类模板没有默认的，所以直接返回空；
            throw new Exception('刊登记录未选择分类模板,记录ID：' . $product['id']);
        }

        $category_info = $product['category_info'];
        $category_info = is_string($category_info) ? json_decode($category_info, true) : (array)$category_info;

        //如果产品模板中存在该元素的node_tree则使用node_tree组成数组；
        foreach ($category_info as $key => $val) {
            if (strpos($key, '@') !== false) {
                $tmpArr = explode('@', $key);
                if (count($tmpArr) == 2 && isset($list[$tmpArr[0]])) {
                    $nodeArr[$list[$tmpArr[0]]]['attribute'] = ['name' => $tmpArr[1], 'value' => $val];
                }
            }
            if (isset($list[$key])) {
                $nodeArr[$list[$key]]['value'] = $val;
            }
        }

        //department；
        empty($product['department']) || $nodeArr['Department'] = $product['department'];

        return $nodeArr;
    }

    /**
     * 找出分类的元素，以node_tree为键放进数组；
     * @param $product
     * @param $detail
     * @param $class_type_id
     * @return array
     * @throws Exception
     */
    private function buildCategoryVariantData($detail, $list)
    {
        $nodeArr = [];
        $single = $this->getPublishPattern($detail['product_id']);

        //type为1，才会有变体数据；
        if ($detail['type'] == 1) {
            $variant_info = is_string($detail['variant_info']) ? json_decode($detail['variant_info'], true) : (array)$detail['variant_info'];
            //如果产品模板中存在该元素的node_tree则使用node_tree组成数组；
            foreach ($variant_info as $key => $val) {
                if (strpos($key, '@') !== false) {
                    $tmpArr = explode('@', $key);
                    if (count($tmpArr) == 2 && isset($list[$tmpArr[0]])) {
                        $nodeArr[$list[$tmpArr[0]]]['attribute'] = ['name' => $tmpArr[1], 'value' => $val];
                    }
                }
                if (isset($list[$key])) {
                    $nodeArr[$list[$key]]['value'] = $val;
                }
            }
            //不是单体时，标明这是子集；
            //if (!$single) {
            //}
            $nodeArr['VariationData,Parentage'] = 'child';
        } else {
            //不是多体时标明这是父级
            if (!$single) {
                $nodeArr['VariationData,Parentage'] = 'parent';
            }
        }

        return $nodeArr;
    }

    /**
     * 根据XSD的json安装产品元素；
     * @param $nodeArr
     * @return string
     */
    public function buildProductXmlFromXsdJson($nodeArr)
    {
        $sequence = Cache::store('AmazonTemplate')->getProductSequence();
        $newNodeArr = [];
        foreach ($nodeArr as $key => $val) {
            //$val = preg_replace('@&([\w\d]*);@', '&\1 ;', $val);
            //格式转化；
            $this->transformKeyValue($key, $val);
            $newKey = str_replace('/', ',', $key);
            $newKey = trim($newKey, ',');
            $newNodeArr[$newKey] = $val;
        }

        $xml = $this->buildXmlfromSequence($sequence, $newNodeArr);
        return $xml;
    }


    /**
     * 根据XSD的json安装分类元素；
     * @param $nodeArr
     * @param $site
     * @return string
     */
    public function buildCategoryXmlFromXsdCache($nodeArr, $site, $class_type_id)
    {
        $sequence = Cache::store('AmazonTemplate')->getCategorySequence($site, $class_type_id);
        $newNodeArr = [];
        foreach ($nodeArr as $key => $val) {
            //$val = preg_replace('@&([\w\d]*);@', '&\1 ;', $val);
            //格式转化；
            $this->transformKeyValue($key, $val);
            $newKey = str_replace('/', ',', $key);
            $newKey = trim($newKey, ',');

            //因为有些字段保存的时候，只能是字符串，在变体VariationData的数据，只是以VariationData开头的，在些统一一下；
            if (strpos($key, 'VariationData,') != false) {
                $newKey = substr($key, strpos($key, 'VariationData,'));
            }
            $newNodeArr[$newKey] = $val;
        }
        $xml = $this->buildXmlfromSequence($sequence, $newNodeArr);
        $xml = '<ProductData>' . $xml . '</ProductData>';
        return $xml;
    }

    /**
     * 转换键值格式；
     * @param $key
     * @param $val
     */
    public function transformKeyValue(&$key, &$val)
    {
        //时间类型转化一下；
        if (strPos($key, 'Date') !== false) {
            if (is_string($val) && (strtotime($val) !== false)) {
                $val = gmdate("Y-m-d\TH:i:s\Z", strtotime($val));
            } else if (is_array($val) && (strtotime($val['value']) !== false)) {
                $val['value'] = gmdate("Y-m-d\TH:i:s\Z", strtotime($val['value']));
            }
        }
    }

    /**
     * 根据有序列的数组，把现有数据转换成正确序列的xml
     * @param $sequence
     * @param $nodeArr
     * @param string $tree
     * @return string
     */
    private function buildXmlFromSequence($sequence, $nodeArr, $tree = '')
    {
        $xml = '';
        foreach ($sequence as $key => $val) {
            //如果键是ProductData,则留个地方在这里!!!组建product模板时使用;
            if ($key == 'ProductData') {
                $xml .= '<ProductData></ProductData>';
                continue;
            }

            $nodetree = trim($tree . ',' . $key, ',');
            //xsd树，有下级的话是数组；
            if (is_array($val)) {
                //对变体VariationData内的数据，做另外处理；
                if (in_array($key, ['VariationData', 'Department'])) {
                    $nodetree = $key;
                }
                $childXml = $this->buildXmlFromSequence($val, $nodeArr, $nodetree);
                if (!empty($childXml)) {
                    $xml .= '<' . $key . '>';
                    $xml .= $childXml;
                    $xml .= '</' . $key . '>';
                }
            } else {
                if (in_array($key, ['Department']) && !empty($nodeArr[$key])) {
                    $xml .= $this->fillValue($key, $nodeArr[$key]);
                }
                //如果不为空；
                if (!empty($nodeArr[$nodetree])) {

                    //先拿取要往里面填的值；
                    $tmp = $nodeArr[$nodetree];
                    //值的属性；
                    $attr = '';

                    //值有4种形式，1.字符串,2.[0=>值，1.值]，3.['value' => 值],4.['value' => [值，值，值];
                    //先把value里面的值区分出来，顺便找出属性；
                    if (is_array($tmp) && isset($tmp['value'])) {
                        $value = $tmp['value'];
                        if (!empty($tmp['attribute'])) {
                            $attr = ' ' . $tmp['attribute']['name'] . '="' . $tmp['attribute']['value'] . '"';
                        }
                    } else {
                        $value = $tmp;
                    }

                    //此时4种形式分成了两种情况，1.字符串,2.[0=>值，1.值]，并且如果存在属性也找出来了；
                    if (is_array($value) && isset($value[0])) {
                        foreach ($tmp as $tval) {
                            if ($tval !== '') {
                                $xml .= $this->fillValue($key, $tval, $attr);
                            }
                        }
                    } else {
                        //值不为空，才加标签，空标签会报错；
                        if ($value !== '') {
                            //如果发现&，外面加一层套；
                            $xml .= $this->fillValue($key, $value, $attr);
                        }
                    }
                } else {
                    //当数据为空时，以下元素也要传空标签；
                    if (in_array($key, [])) {
                        $xml .= '<' . $key . '>';
                        $xml .= '</' . $key . '>';
                    }
                }
            }
        }
        return $xml;
    }

    private function fillValue($key, $value, $attr = '')
    {
        $xml = '';
        //如果发现&，外面加一层套；
        if ($this->checkStrIsSpecial($value)) {
            $value = $this->handelDesc($value);
        }
        $xml .= '<' . $key . $attr . '>';
        $xml .= $value;
        $xml .= '</' . $key . '>';

        return $xml;
    }

    /**
     * 检查字符串进入xml是否需要套cdata
     * @param $str
     * @return bool
     */
    private function checkStrIsSpecial($str): bool
    {
        if (strpos($str, '<![CDATA[') === 0) {
            return false;
        }
        foreach ($this->encodeCharacterArr as $char) {
            if (strpos($str, $char) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 解析出来的产品的顺序的JSON；
     * @var string
     */
    private $productSequenceJson = '{"Product":{"SKU":"","StandardProductID":{"Type":"","Value":""},"GtinExemptionReason":"","RelatedProductID":{"Type":"","Value":""},"ProductTaxCode":"","LaunchDate":"","DiscontinueDate":"","ReleaseDate":"","ExternalProductUrl":"","Condition":{"ConditionType":"","ConditionNote":""},"Rebate":{"RebateStartDate":"","RebateEndDate":"","RebateMessage":"","RebateName":""},"ItemPackageQuantity":"","NumberOfItems":"","LiquidVolume":"","DescriptionData":{"Title":"","Brand":"","Designer":"","Description":"","BulletPoint":"","ItemDimensions":{"Length":"","Width":"","Height":"","Weight":""},"PackageDimensions":{"Length":"","Width":"","Height":"","Weight":""},"PackageWeight":"","ShippingWeight":"","MerchantCatalogNumber":"","MSRP":"","MSRPWithTax":"","MaxOrderQuantity":"","SerialNumberRequired":"","Prop65":"","CPSIAWarning":"","CPSIAWarningDescription":"","LegalDisclaimer":"","Manufacturer":"","MfrPartNumber":"","SearchTerms":"","PlatinumKeywords":"","Memorabilia":"","Autographed":"","UsedFor":"","ItemType":"","OtherItemAttributes":"","TargetAudience":"","SubjectContent":"","IsGiftWrapAvailable":"","IsGiftMessageAvailable":"","PromotionKeywords":"","IsDiscontinuedByManufacturer":"","DeliveryScheduleGroupID":"","DeliveryChannel":"","ExternalProductInformation":"","MaxAggregateShipQuantity":"","RecommendedBrowseNode":"","MerchantShippingGroupName":"","FEDAS_ID":"","TSDAgeWarning":"","TSDWarning":"","TSDLanguage":"","OptionalPaymentTypeExclusion":"","DistributionDesignation":"","ExternalTestingCertification":"","Battery":{"AreBatteriesIncluded":"","AreBatteriesRequired":"","BatterySubgroup":{"BatteryType":"","NumberOfBatteries":""}},"BatteryCellType":"","BatteryWeight":"","NumberOfLithiumMetalCells":"","NumberOfLithiumIonCells":"","LithiumBatteryPackaging":"","LithiumBatteryEnergyContent":"","LithiumBatteryWeight":"","ItemWeight":"","ItemVolume":"","FlashPoint":"","GHSClassificationClass":"","SupplierDeclaredDGHZRegulation":"","HazmatUnitedNationsRegulatoryID":"","SafetyDataSheetURL":""},"DiscoveryData":{"Priority":"","BrowseExclusion":"","RecommendationExclusion":""},"ProductData":[],"ShippedByFreight":"","EnhancedImageURL":"","Amazon-Vendor-Only":{"Cost":""},"Amazon-Only":{"Tier":"","PurchasingCategory":"","PurchasingSubCategory":"","PackagingType":"","UnderlyingAvailability":"","ReplenishmentCategory":"","DropShipStatus":"","OutOfStockWebsiteMessage":""},"RegisteredParameter":"","NationalStockNumber":"","UnspscCode":""}}';

    private $amazonAccount = [];
    private $publishPattern = [];
    private $encodeCharacterArr = ['&', '<', '>'];

    private $templateModel = null;
    private $templateDetailModel = null;
    private $templateVariantModel = null;
    private $categoryDetailList = null;

    /**
     * 用于发布xml时返回亚马逊帐号；
     */
    public function getPublishAccount()
    {
        return $this->amazonAccount;
    }

    /**
     * 发布产品时集中实例化几个必需的对象
     */
    //public function publishProductInit()
    //{
    //    $this->templateModel = new AmazonXsdTemplate();
    //    $this->templateDetailModel = new AmazonXsdTemplateDetail();
    //    $this->templateVariantModel = new AmazonXsdTemplateVariant();
    //}

    /**
     * 设置刊登模式
     * @param $product_id
     * @param $pattern
     */
    public function setPublishPattern($product_id, bool $pattern)
    {
        $this->publishPattern[$product_id] = $pattern;
    }

    /**
     * 拿取刊登模式；
     * @param $product_id
     * @return mixed
     * @throws Exception
     */
    public function getPublishPattern($product_id)
    {
        if (!isset($this->publishPattern[$product_id])) {
            throw new Exception('未知产品ID刊登模式');
        }
        return $this->publishPattern[$product_id];
    }

    public function checkProductDetailStatus($detailList)
    {
        $productCache = Cache::store('AmazonPublish');
        $count = count($detailList);
        $product_status = 0;
        $all_status = 0;
        $statusArr = ['upload_product', 'upload_relation', 'upload_quantity', 'upload_image', 'upload_price'];
        foreach ($detailList as $detail) {
            foreach ($statusArr as $status) {
                if ($detail[$status] == 1) {
                    if ($status == 'upload_product') {
                        $product_status++;
                    }
                    $all_status++;
                }
            }
        }
        $product_id = $detailList[0]['product_id'];
        //完全刊登完成了；
        if ($all_status == $count * 5) {
            Db::name('amazon_publish_product')->where(['id' => $product_id])->update(['publish_status' => AmazonPublishConfig::PUBLISH_STATUS_FINISH]);
            $productCache->delCache($product_id);
            return 'all_finish';
        }

        //产品刊登完成了；
        if ($product_status == $count) {
            return 'product_finish';
        }

        //需从产品开始刊登起；
        return '';
    }

    /**
     * 给xml里面添加<![CDATA[]]>
     * @param $data
     * @return array|string
     */
    private function handelDesc($data)
    {
        if (is_string($data)) {
            if (empty($data)) {
                return '';
            }
            return '<![CDATA[' . $data . ']]>';
        } else {
            $newData = [];
            foreach ($data as $v) {
                if (!empty($v)) {
                    $newData[] = '<![CDATA[' . $v . ']]>';
                }
            }
            return $newData;
        }
    }

    /**
     * 分析出publish_sku;有4种情况：
     * 1.未刊登不足20；
     * 2.未刊登20长度；
     * 3.失败20长度；
     * 4.失败不够20长度
     * @param $detail
     * @param $uid
     * @param $accountId
     * @return mixed
     * @throws Exception
     */
    private function getPublishSku($detail, $uid, $accountId, $save_map = 1, $is_virtual_send = 0)
    {
        $skumap = new GoodsSkuMapService();

        //不保存映射关系，没到条件重新创建重创；
        //父SKU不创建映射关系；
        if ($save_map == 0 || $detail['type'] == 0) {
            if (strpos($detail['publish_sku'], $detail['sku']) !== false && strlen($detail['publish_sku']) - strlen($detail['sku']) >= 4) {
                $publish_sku = $detail['publish_sku'];
            } else {
                $publish_sku = $skumap->createSkuNotInTable($detail['publish_sku'], $accountId, ChannelAccountConst::channel_amazon);
            }
            if ($publish_sku !== $detail['publish_sku']) {
                AmazonPublishProductDetail::update(['publish_sku' => $publish_sku], ['id' => $detail['id']]);
            }
            return ['result' => true, 'sku_code' => $publish_sku];
        }

        if (empty($detail['binding_goods'])) {
            $detail['binding_goods'] = $detail['sku'] . '*1';
        }

        $combine_sku = str_replace(',', '|', $detail['binding_goods']);
        $publish_map_data = [
            'sku_code' => $detail['sku'],
            'channel_sku' => $detail['publish_sku'],
            'channel_id' => ChannelAccountConst::channel_amazon,
            'account_id' => $accountId,
            'combine_sku' => $combine_sku,
            'is_virtual_send' => $is_virtual_send,
        ];
        $skuData = $skumap->amazonAddSkuCodeWithQuantity($publish_map_data, $uid);
        if ($skuData['result'] === true) {
            $publish_sku = $skuData['sku_code'];
            if ($publish_sku !== $detail['publish_sku']) {
                AmazonPublishProductDetail::update(['publish_sku' => $publish_sku], ['id' => $detail['id']]);
            }
        }

        return $skuData;
    }


    public function getBuildXmlError($code)
    {
        $errors = [
            0 => '生成XML成功',
            1 => '刊登产品不存在',
            2 => '刊登产品已禁止Amazon平台上架',
            3 => '刊登产品变体字段未找到',
            4 => '刊登产品SPU和SKU顺序不对应',
            5 => '因产品未上传成功，上传父子关系失败',
        ];

        return $errors[$code] ?? '未知错误CODE出错';
    }


    /**
     * 组成产品XML
     * @param $product_id
     * @return bool|string
     * @throws Exception
     */
    public function buildProductSimpleXml($product_id)
    {
        //反回的信息；
        $message = ['code' => 0, 'message' => '', 'xml' => ''];

        $templateVariantModel = new AmazonXsdTemplateVariant();

        $product = $this->productModel->where(['id' => $product_id])->find();
        $detailList = $this->detailModel->where(['product_id' => $product_id])->select();

        //产品不存在；
        if (empty($product)) {
            $message['code'] = 1;
            return $message;
        }

        //产品平台是否可上架；
        if ($this->productionPublish && !(new GoodsHelp())->getPlatformForChannel($product['goods_id'], ChannelAccountConst::channel_amazon)) {
            $message['code'] = 2;
            return $message;
        }

        $site = AmazonCategoryXsdConfig::getSiteByNum($product['site']);
        //检查产品是否在Amazon当前站点侵权；
        $tortCount = GoodsTortDescription::where(['channel_id' => ChannelAccountConst::channel_amazon, 'goods_id' => $product['goods_id'], 'site_code' => $site])->count();
        if ($tortCount > 0) {
            $message['code'] = 2;
            $message['message'] = '商品:'. $product['spu']. '在Amazon平台'. $site. '站点存在侵权下架记录，禁止上架';
            return $message;
        }

        //是否刊登成单体
        $single = false;
        //标明是单体时刊登为单体；详情只有两条时（因为只有一条SKU），刊登为单体；没有变体数据时，刊登为单体；
        if ((isset($product['is_single']) && $product['is_single'] == 1) || count($detailList) <= 2 || empty($product['theme_name'])) {
            $single = true;
        }
        $this->setPublishPattern($product_id, $single);

        //分类的大小分类ID；
        $class_type_id = '';
        //把分类的元素带出来；
        $categoryElements = [];

        //找出产品模板的和分类模板数据相关的数据；
        $productNode = $this->buildProductTemplateData($product);
        $firstCategoryNode = $this->buildFirstCategoryTemplateData($product, $class_type_id, $categoryElements);

        /* $theme_name 变体名！！！
         * 这里标示一下，如果为空则以下全部产品刊登为单体，则第一个循环就应该跳过！！！
         */
        $theme_name = $product['theme_name'];
        //用来装变体元素；
        $variantElements = [];
        if (!empty($theme_name)) {
            $variant = $templateVariantModel->where(['id' => $theme_name])->field('id,name,relation_field')->find();
            if (!isset($variant)) {
                $message['code'] = 3;
                return $message;
            }
            $theme_name = $variant['name'];
            //变体的name;
            $variantField = json_decode($variant['relation_field'], true);
            //把变体用到字段装进变量保存；
            foreach ($variantField as $field) {
                $field = trim($field);
                $variantElements[$field] = $categoryElements[$field];
            }
        }

        //父产品XML
        $parentXml = '';
        //父产品刊登状态
        $patentStatus = true;
        //子产品XML,父子XML分离，是因为有时xml刊登父产品成功，子产品不成功，但是建立关系时，确提示父产品不存在；
        $msgXml = '';

        foreach ($detailList as $key => $detail) {
            $tmpXml = '';
            //记录父产品状态
            if ($detail['type'] == 0) {
                $patentStatus = ($detail['upload_product'] == 1) ? true : false;
            }
            //子SKU已上传成功的产品跳过
            if ($detail['upload_product'] == 1 && $detail['type'] !== 0) {
                continue;
            }

            //单体，更新状态后跳过第一个；
            if ($single && $detail['type'] == 0) {
                if ($key != 0) {
                    $message['code'] = 4;
                    return $message;
                }
                //如果已经设置为刊登成功，则跳过
                if ($detail['upload_product'] == 1) {
                    continue;
                }
                //所有产品的关系直接标为已完成；
                $this->detailModel->update(['upload_relation' => AmazonPublishConfig::DETAIL_PUBLISH_STATUS_FINISH], ['product_id' => $product['id']]);

                //把父产品的所有上传标记为已上传，这样下面所有部聚全都会跳过;；
                //['upload_product', 'upload_relation', 'upload_quantity', 'upload_image', 'upload_price'];
                $this->detailModel->update([
                    'upload_product' => AmazonPublishConfig::DETAIL_PUBLISH_STATUS_FINISH,
                    'upload_relation' => AmazonPublishConfig::DETAIL_PUBLISH_STATUS_FINISH,
                    'upload_quantity' => AmazonPublishConfig::DETAIL_PUBLISH_STATUS_FINISH,
                    'upload_image' => AmazonPublishConfig::DETAIL_PUBLISH_STATUS_FINISH,
                    'upload_price' => AmazonPublishConfig::DETAIL_PUBLISH_STATUS_FINISH,
                ], ['id' => $detail['id']]);
                continue;
            }

            //装所有元素以nodetree的键的形式装入数组；
            $nodeArr = [];

            //1.编辑出发布SKU;
            $is_virtual_send = empty($product['is_virtual_send']) ? 0 : 1;
            $skuData = $this->getPublishSku($detail, $product['creator_id'], $product['account_id'], $product['save_map'], $is_virtual_send);
            if ($skuData['result'] !== true) {
                return ['code' => 10, 'message' => $skuData['message']];
            }
            $nodeArr['Product,SKU'] = $skuData['sku_code'];

            //2.往nodeArr里装元素，这里不用管顺序，因为顺序是以解析出来的JSON为顺序的；
            //主表里的公用数据；
            $nodeArr['Product,DescriptionData,ItemType'] = $product['item_type'];

            //以下为推荐节点，如果为空，则用
            $recommend_node = empty($detail['recommend_node']) ? $product['recommend_node'] : $detail['recommend_node'];
            $nodeArr['Product/DescriptionData/RecommendedBrowseNode'] = array_slice(explode(',', $recommend_node), 0, 2);

            $nodeArr['Product,DescriptionData,Brand'] = $product['brand'];

            //3.以下是变体才有的属性；
            if ($key > 0) {
                //upc不能为空
                if (empty($detail['product_id_type']) || empty($detail['product_id_value'])) {
                    throw new Exception('product_id_type 或 product_id_value不能为空');
                }
                $nodeArr['Product,StandardProductID,Type'] = $detail['product_id_type'];
                $nodeArr['Product,StandardProductID,Value'] = $detail['product_id_value'];

                //以下两为不为空才填；
                if (!empty($detail['condition_type'])) {
                    $nodeArr['Product,Condition,ConditionType'] = $detail['condition_type'];
                }
                if (!empty($detail['condition_note'])) {
                    $nodeArr['Product,Condition,ConditionNote'] = $detail['condition_note'];
                }
                //partnumber
                $nodeArr['Product,DescriptionData,MfrPartNumber'] = $this->handelDesc($detail['part_number']);
            }

            $nodeArr['Product,DescriptionData,Title'] = $this->handelDesc($detail['title']);
            //先解析json，如果是数组，则放数组，不是数组，把值原样放上去；
            $searchTerms = json_decode($detail['search_Terms'], true);
            if (is_array($searchTerms)) {
                $nodeArr['Product,DescriptionData,SearchTerms'] = $this->handelDesc($searchTerms);
            } else {
                $nodeArr['Product,DescriptionData,SearchTerms'] = $this->handelDesc($detail['search_Terms']);
            }
            $nodeArr['Product,DescriptionData,BulletPoint'] = $this->handelDesc(json_decode($detail['bullet_point'], true));
            $nodeArr['Product,DescriptionData,Description'] = $this->handelDesc($detail['description']);

            //产品模板的属性需要一起去组装XML;
            $nodeArr = array_merge($productNode, $nodeArr);

            //匹配出需要的XML;
            $productXml = $this->buildProductXmlFromXsdJson($nodeArr);

            //分类和变体数据，组成nodetrrArr；
            $categoryNode = [];
            //!!!没有选变体，是不会有变体数据的，这里可以忽略！！！
            if (!empty($theme_name)) {
                $categoryNode = $this->buildCategoryVariantData($detail, $variantElements);
                //单体不需要填变体数据；
                if (!$single) {
                    $categoryNode['VariationData,VariationTheme'] = $theme_name;
                }
            } else {
                //传这一点防止报错；
                //$nodeArr['VariationData,Parentage'] = 'child';
            }

            //组合产主记录表的节点和详情表的分类节点；
            $categoryNode = array_merge($firstCategoryNode, $categoryNode);
            $categoryXml = $this->buildCategoryXmlFromXsdCache($categoryNode, $product['site'], $class_type_id);

            //单独的一个message;
            $tmpXml .= '<Message><MessageID>' . $this->getMessageId() . '</MessageID><OperationType>Update</OperationType>';
            $tmpXml .= $productXml;
            $tmpXml .= '</Message>';

            //XML里面的ProductData分类xml给替换出来；
            $tmpXml = str_replace('<ProductData></ProductData>', $categoryXml, $tmpXml);

            //这里区分父产品和子产品；
            if ($detail['type'] == 0) {
                $parentXml = $tmpXml;
            } else {
                $msgXml .= $tmpXml;
            }
        }

        //如果以上没有生成子xml,而且父产品状态也是成功的；
        if (empty($msgXml) && $patentStatus) {
            return $message;
        }

        //连接父子产品；
        $message['xml'] = $parentXml . $msgXml;
        return $message;
    }

    /**
     * 组成产品XML
     * @param $product_id
     * @return bool|string
     * @throws Exception
     */
    public function buildProductXml($product_id)
    {
        $templateVariantModel = new AmazonXsdTemplateVariant();

        $productCache = Cache::store('AmazonPublish');
        $product = $productCache->getProduct($product_id);
        $detailList = $productCache->getDetail($product_id);

        if (empty($product)) {
            throw new Exception('无效的刊登记录ID：' . $product_id);
        }

        if (!(new GoodsHelp())->getPlatformForChannel($product['goods_id'], ChannelAccountConst::channel_amazon)) {
            throw new Exception('产品已被下架或已侵权下架，禁止刊登');
        }

        //检测下产品详情状态，看是产品刊登完成了，还是全部完成了，还是需从头开始；
        $detailStatusResult = $this->checkProductDetailStatus($detailList);
        if ($detailStatusResult === 'all_finish') { //已全部刊登完成
            return 'all_finish';
        } else if ($detailStatusResult == 'product_finish') {
            if ($product['publish_status'] != AmazonPublishConfig::PUBLISH_STATUS_UNDERWAY) {
                Db::name('amazon_publish_product')->where(['id' => $product_id])->update(['publish_status' => AmazonPublishConfig::PUBLISH_STATUS_UNDERWAY]);
                $productCache->updateProduct($product_id, ['publish_status' => AmazonPublishConfig::PUBLISH_STATUS_UNDERWAY]);
            }
            return true;
        }

        //只能刊登未上传和失败的刊登记录，状态此时在刊登中，表示已有一个进程在刊登了，这里就直接结束了；
        //if ($product['publish_status'] == AmazonPublishConfig::PUBLISH_STATUS_UNDERWAY) {
        //    return 'publish_underway';
        //}

        //以上从非刊登中下来，在这里改一下状态，改成正在刊登中；
        if ($product['publish_status'] != AmazonPublishConfig::PUBLISH_STATUS_UNDERWAY) {
            Db::name('amazon_publish_product')->where(['id' => $product_id])->update(['publish_status' => AmazonPublishConfig::PUBLISH_STATUS_UNDERWAY, 'update_time' => time()]);
            $productCache->updateProduct($product_id, ['publish_status' => AmazonPublishConfig::PUBLISH_STATUS_UNDERWAY, 'update_time' => time()]);
        }

        //是否刊登成单体
        $single = false;
        //标明是单体时刊登为单体；详情只有两条时（因为只有一条SKU），刊登为单体；没有变体数据时，刊登为单体；
        if ((isset($product['is_single']) && $product['is_single'] == 1) || count($detailList) <= 2 || empty($product['theme_name'])) {
            $single = true;
        }
        $this->setPublishPattern($product_id, $single);

        //验证状号状态；
        $account = $this->checkAccount($product['account_id']);

        //实际化一些需要的对象；
        //$this->publishProductInit();


        //分类的大小分类ID；
        $class_type_id = '';
        //把分类的元素带出来；
        $categoryElements = [];

        //找出产品模板的和分类模板数据相关的数据；
        $productNode = $this->buildProductTemplateData($product);
        $firstCategoryNode = $this->buildFirstCategoryTemplateData($product, $class_type_id, $categoryElements);

        /* $theme_name 变体名！！！
         * 这里标示一下，如果为空则以下全部产品刊登为单体，则第一个循环就应该跳过！！！
         */
        $theme_name = $product['theme_name'];
        //用来装变体元素；
        $variantElements = [];
        if (!empty($theme_name)) {
            $variant = $templateVariantModel->where(['id' => $theme_name])->field('id,name,relation_field')->find();
            if (!isset($variant)) {
                throw new Exception('变体字段未找到');
            }
            $theme_name = $variant['name'];
            //变体的name;
            $variantField = json_decode($variant['relation_field'], true);
            //把变体用到字段装进变量保存；
            foreach ($variantField as $field) {
                $field = trim($field);
                $variantElements[$field] = $categoryElements[$field];
            }
        }

        //父产品XML
        $parentXml = '';
        //父产品刊登状态
        $patentStatus = true;
        //子产品XML,父子XML分离，是因为有时xml刊登父产品成功，子产品不成功，但是建立关系时，确提示父产品不存在；
        $msgXml = '';

        foreach ($detailList as $key => $detail) {
            $tmpXml = '';
            //记录父产品状态
            if ($detail['type'] == 0) {
                $patentStatus = ($detail['upload_product'] == 1) ? true : false;
            }
            //子SKU已上传成功的产品跳过
            if ($detail['upload_product'] == 1 && $detail['type'] !== 0) {
                continue;
            }

            //单体，更新状态后跳过第一个；
            if ($single && $detail['type'] == 0) {
                if ($key != 0) {
                    throw new Exception('详情记录的第一个不是父体');
                }
                //如果已经设置为刊登成功，则跳过
                if ($detail['upload_product'] == 1) {
                    continue;
                }
                //所有产品的关系直接标为已完成；
                $this->detailModel->update(['upload_relation' => 1], ['product_id' => $product['id']]);
                $productCache->updateDetail($product['id'], ['publish_sku' => 'ALL', 'data' => ['upload_relation' => 1]]);

                //把父产品的所有上传标记为已上传，这样下面所有部聚全都会跳过;；
                //['upload_product', 'upload_relation', 'upload_quantity', 'upload_image', 'upload_price'];
                $this->detailModel->update([
                    'upload_product' => 1,
                    'upload_relation' => 1,
                    'upload_quantity' => 1,
                    'upload_image' => 1,
                    'upload_price' => 1,
                ], ['id' => $detail['id']]);
                //更新缓存；
                $productCache->updateDetail($product['id'], ['id' => $detail['id'], 'data' => [
                    'upload_product' => 1,
                    'upload_relation' => 1,
                    'upload_quantity' => 1,
                    'upload_image' => 1,
                    'upload_price' => 1,
                ]]);
                continue;
            }

            //装所有元素以nodetree的键的形式装入数组；
            $nodeArr = [];

            //单独的一个message;
            $tmpXml .= '<Message><MessageID>' . $this->getMessageId() . '</MessageID><OperationType>Update</OperationType>';

            //1.编辑出发布SKU;
            $is_virtual_send = empty($product['is_virtual_send']) ? 0 : 1;
            $skuData = $this->getPublishSku($detail, $product['creator_id'], $product['account_id'], $product['save_map'], $is_virtual_send);
            if ($skuData['result'] !== true) {
                throw new Exception($skuData['message']);
            }
            $nodeArr['Product,SKU'] = $skuData['sku_code'];

            //2.往nodeArr里装元素，这里不用管顺序，因为顺序是以解析出来的JSON为顺序的；
            //主表里的公用数据；
            $nodeArr['Product,DescriptionData,ItemType'] = $product['item_type'];

            //以下为推荐节点，如果为空，则用
            $recommend_node = empty($detail['recommend_node']) ? $product['recommend_node'] : $detail['recommend_node'];
            $nodeArr['Product/DescriptionData/RecommendedBrowseNode'] = array_slice(explode(',', $recommend_node), 0, 2);

            $nodeArr['Product,DescriptionData,Brand'] = $product['brand'];

            //3.以下是变体才有的属性；
            if ($key > 0) {
                //upc不能为空
                if (empty($detail['product_id_type']) || empty($detail['product_id_value'])) {
                    throw new Exception('product_id_type 或 product_id_value不能为空');
                }
                $nodeArr['Product,StandardProductID,Type'] = $detail['product_id_type'];
                $nodeArr['Product,StandardProductID,Value'] = $detail['product_id_value'];

                //以下两为不为空才填；
                if (!empty($detail['condition_type'])) {
                    $nodeArr['Product,Condition,ConditionType'] = $detail['condition_type'];
                }
                if (!empty($detail['condition_note'])) {
                    $nodeArr['Product,Condition,ConditionNote'] = $detail['condition_note'];
                }
                //partnumber
                $nodeArr['Product,DescriptionData,MfrPartNumber'] = $this->handelDesc($detail['part_number']);
            }

            $nodeArr['Product,DescriptionData,Title'] = $this->handelDesc($detail['title']);
            //先解析json，如果是数组，则放数组，不是数组，把值原样放上去；
            $searchTerms = json_decode($detail['search_Terms'], true);
            if (is_array($searchTerms)) {
                $nodeArr['Product,DescriptionData,SearchTerms'] = $this->handelDesc($searchTerms);
            } else {
                $nodeArr['Product,DescriptionData,SearchTerms'] = $this->handelDesc($detail['search_Terms']);
            }
            $nodeArr['Product,DescriptionData,BulletPoint'] = $this->handelDesc(json_decode($detail['bullet_point'], true));
            $nodeArr['Product,DescriptionData,Description'] = $this->handelDesc($detail['description']);

            //产品模板的属性需要一起去组装XML;
            $nodeArr = array_merge($productNode, $nodeArr);

            //匹配出需要的XML;
            $productXml = $this->buildProductXmlFromXsdJson($nodeArr);
            $tmpXml .= $productXml;
            $tmpXml .= '</Message>';

            //分类和变体数据，组成nodetrrArr；
            $categoryNode = [];
            //!!!没有选变体，是不会有变体数据的，这里可以忽略！！！
            if (!empty($theme_name)) {
                $categoryNode = $this->buildCategoryVariantData($detail, $variantElements);
                //单体不需要填变体数据；
                if (!$single) {
                    $categoryNode['VariationData,VariationTheme'] = $theme_name;
                }
            } else {
                //传这一点防止报错；
                //$nodeArr['VariationData,Parentage'] = 'child';
            }

            //组合产主记录表的节点和详情表的分类节点；
            $categoryNode = array_merge($firstCategoryNode, $categoryNode);
            $categoryXml = $this->buildCategoryXmlFromXsdCache($categoryNode, $product['site'], $class_type_id);

            //XML里面的ProductData分类xml给替换出来；
            $tmpXml = str_replace('<ProductData></ProductData>', $categoryXml, $tmpXml);

            //这里区分父产品和子产品；
            if ($detail['type'] == 0) {
                $parentXml = $tmpXml;
            } else {
                $msgXml .= $tmpXml;
            }
        }

        //如果以上没有生成子xml,而且父产品状态也是成功的；
        if (empty($msgXml) && $patentStatus) {
            return true;
        }

        //连接父子产品；
        $msgXml = $parentXml . $msgXml;
        $xml = $this->buildEnvelope($account['merchant_id'], 'Product', $msgXml);

        return $xml;
    }

    /**
     * @Title 组成对应关系xml
     * @Description 组成对应关系，如果详情只有一条，直接结束，如果详情全部是变体，没有父级，直接结束，因为每一条都是一个产品
     * @param $product_id
     * @return bool|string
     * @throws Exception
     */
    public function buildRelationXml($product_id, $simple = false)
    {
        $message = ['code' => 0, 'message' => '', 'xml' => ''];
        $product = $this->productModel->where(['id' => $product_id])->find();
        $detailList = $this->detailModel->where(['product_id' => $product_id])->select();

        if (empty($product)) {
            $message['code'] = 1;
            return $message;
        }

        //侵权下架；
        if ($this->productionPublish && !(new GoodsHelp())->getPlatformForChannel($product['goods_id'], ChannelAccountConst::channel_amazon)) {
            $message['code'] = 2;
            return $message;
        }

        if ($product['publish_status'] == AmazonPublishConfig::PUBLISH_STATUS_FINISH) {
            return $message;
        }

        //单独的一个message;
        $msgXml = '';

        $pXml = '';
        $cXml = '';
        foreach ($detailList as $key => $detail) {
            //已上传成功的产品报错；
            if ($detail['upload_product'] != 1) {
                $message['code'] = 5;
                return $message;
            }
            //已上传关系的产品跳过
            if ($detail['upload_relation'] == 1 && $detail['type'] != 0) {
                continue;
            }

            if ($detail['type'] == 0) {
                $pXml = '<ParentSKU>' . $detail['publish_sku'] . '</ParentSKU>';
            } else {
                $cXml .= '<Relation><SKU>' . $detail['publish_sku'] . '</SKU><Type>Variation</Type></Relation>';
            }
        }

        //是否刊登成单体
        //标明是单体时刊登为单体；详情只有两条时（因为只有一条SKU），刊登为单体；没有变体数据时，刊登为单体；
        if ((isset($product['is_single']) && $product['is_single'] == 1) || count($detailList) <= 2 || empty($product['theme_name'])) {
            $this->productModel->update(['relation_status' => 2], ['id' => $product_id]);
            $this->detailModel->update(['upload_relation' => 1], ['product_id' => $product_id]);
            return $message;
        }

        //$cXml为空，则为全部都上传成功关系了，直接返回true;
        if (empty($cXml)) {
            return $message;
        }

        $msgXml .= '<Message><MessageID>'. $this->getMessageId(). '</MessageID><OperationType>Update</OperationType><Relationship>';
        $msgXml .= $pXml . $cXml;
        $msgXml .= '</Relationship></Message>';

        if ($simple) {
            $message['xml'] = $msgXml;
        } else {
            $account = $this->checkAccount($product['account_id']);
            $message['xml'] = $this->buildEnvelope($account['merchant_id'], 'Relationship', $msgXml);
        }

        return $message;
    }

    /**
     * 组成价格xml
     * @param $product_id
     * @return bool|string
     * @throws Exception
     */
    public function buildPriceXml($product_id, $simple = false)
    {
        $message = ['code' => 0, 'message' => '', 'xml' => ''];
        $product = $this->productModel->where(['id' => $product_id])->find();
        $detailList = $this->detailModel->where(['product_id' => $product_id])->select();

        if (empty($product)) {
            $message['code'] = 1;
            return $message;
        }

        //侵权下架；
        if ($this->productionPublish && !(new GoodsHelp())->getPlatformForChannel($product['goods_id'], ChannelAccountConst::channel_amazon)) {
            $message['code'] = 2;
            return $message;
        }

        if ($product['publish_status'] == AmazonPublishConfig::PUBLISH_STATUS_FINISH) {
            return $message;
        }

        //美国美元，英国英磅，加拿大加元，日本日元，其它欧元；
        //$currencyArr = ['US' => 'USD', 'UK' => 'GBP', 'CA' => 'CAD', 'JP' => 'JPY', 'DE' => 'EUR', 'FR' => 'EUR', 'IT' => 'EUR', 'MX' => 'MXN'];
        $account = $this->checkAccount($product['account_id']);
        $currency = AmazonCategoryXsdConfig::getCurrencyBySite($account['site']);

        $msgXml = '';
        foreach ($detailList as $key => $detail) {
            //未上传产品成功是不能传价格的，报错；
            if ($detail['upload_product'] != 1) {
                $message['code'] = 5;
                return $message;
            }
            //已上传价格的产品跳过
            if ($detail['upload_price'] == 1) {
                continue;
            }
            //父体是不需要上传金钱的；
            if ($detail['type'] == 0) {
                $this->detailModel->update(['upload_price' => 1], ['id' => $detail['id']]);
                continue;
            }

            //单独的一个message;
            $msgXml .= '<Message><MessageID>' . $this->getMessageId() . '</MessageID><OperationType>Update</OperationType>';

            //连接price;
            $msgXml .= '<Price>';
            $msgXml .= '<SKU>' . $detail['publish_sku'] . '</SKU>';
            $msgXml .= '<StandardPrice currency="' . $currency . '">' . $detail['standard_price'] . '</StandardPrice>';

            if (!empty($detail['sale_price']) && !empty($detail['sale_start_date']) && !empty($detail['sale_end_date'])) {
                $startDate = gmdate("Y-m-d\TH:i:s\Z", $detail['sale_start_date']);
                $endDate = gmdate("Y-m-d\TH:i:s\Z", $detail['sale_end_date']);
                if (strtotime($startDate) && strtotime($endDate)) {
                    $msgXml .= '<Sale>';
                    $msgXml .= '<StartDate>' . $startDate . '</StartDate>';
                    $msgXml .= '<EndDate>' . $endDate . '</EndDate>';
                    $msgXml .= '<SalePrice currency="' . $currency . '">' . $detail['sale_price'] . '</SalePrice>';
                    $msgXml .= '</Sale>';
                }
            }
            $msgXml .= '</Price>';
            $msgXml .= '</Message>';
        }

        //如果以上没有生成xml,唯一结果是全部产品价格已上传成功；
        if (empty($msgXml)) {
            return $message;
        }

        if ($simple) {
            $message['xml'] = $msgXml;
        } else {
            $message['xml'] = $this->buildEnvelope($account['merchant_id'], 'Price', $msgXml);
        }

        return $message;
    }

    /**
     * 组成库存xml
     * @param $product_id
     * @return bool|string
     * @throws Exception
     */
    public function buildQuantityXml($product_id, $simple = false)
    {
        $message = ['code' => 0, 'message' => '', 'xml' => ''];
        $product = $this->productModel->where(['id' => $product_id])->find();
        $detailList = $this->detailModel->where(['product_id' => $product_id])->select();

        if (empty($product)) {
            $message['code'] = 1;
            return $message;
        }

        //侵权下架；
        if ($this->productionPublish && !(new GoodsHelp())->getPlatformForChannel($product['goods_id'], ChannelAccountConst::channel_amazon)) {
            $message['code'] = 2;
            return $message;
        }

        if ($product['publish_status'] == AmazonPublishConfig::PUBLISH_STATUS_FINISH) {
            return $message;
        }

        $msgXml = '';
        $messageId = 1;
        foreach ($detailList as $key => $detail) {
            //已上传价格的产品跳过
            if ($detail['upload_product'] != 1) {
                $message['code'] = 5;
                return $message;
            }
            //已上传价格的产品跳过
            if ($detail['upload_quantity'] == 1) {
                continue;
            }
            if ($detail['type'] == 0) {
                $this->detailModel->allowField(true)->update(['upload_quantity' => 1], ['id' => $detail['id']]);
                continue;
            }
            //未上传产品成功是不能传价格的，报错；
            $msgXml .= '<Message><MessageID>' . $this->getMessageId() . '</MessageID><OperationType>Update</OperationType>';

            //连接price;
            $msgXml .= '<Inventory>';
            $msgXml .= '<SKU>' . $detail['publish_sku'] . '</SKU>';
            $msgXml .= '<Quantity>' . ceil($detail['quantity']) . '</Quantity>';
            $msgXml .= '</Inventory>';
            $msgXml .= '</Message>';

            //$messageId每组成一个message就加一次；
            $messageId++;
        }

        //如果以上没有生成xml,唯一结果是全部产品库存已上传成功；
        if (empty($msgXml)) {
            return $message;
        }

        if ($simple) {
            $message['xml'] = $msgXml;
        } else {
            $account = $this->checkAccount($product['account_id']);
            $message['xml'] = $this->buildEnvelope($account['merchant_id'], 'Inventory', $msgXml);
        }

        return $message;
    }

    /**
     * 组成图片xml
     * @param $product_id
     * @return bool|string
     * @throws Exception
     */
    public function buildImageXml($product_id, $simple = false)
    {
        $message = ['code' => 0, 'message' => '', 'xml' => ''];
        $product = $this->productModel->where(['id' => $product_id])->find();
        $detailList = $this->detailModel->where(['product_id' => $product_id])->select();

        if (empty($product)) {
            $message['code'] = 1;
            return $message;
        }

        //侵权下架；
        if ($this->productionPublish && !(new GoodsHelp())->getPlatformForChannel($product['goods_id'], ChannelAccountConst::channel_amazon)) {
            $message['code'] = 2;
            return $message;
        }

        if ($product['publish_status'] == AmazonPublishConfig::PUBLISH_STATUS_FINISH) {
            return $message;
        }
        //验证状号状态；
        $account = $this->checkAccount($product['account_id']);

        //是否刊登成单体
        $single = false;
        //标明是单体时刊登为单体；详情只有两条时（因为只有一条SKU），刊登为单体；没有变体数据时，刊登为单体；
        if ((isset($product['is_single']) && $product['is_single'] == 1) || count($detailList) <= 2 || empty($product['theme_name'])) {
            $single = true;
        }

        $msgXml = '';
        foreach ($detailList as $key => $detail) {
            //未上传产品成功是不能传图片的，报错；
            if ($detail['upload_product'] != 1) {
                $message['code'] = 5;
                return $message;
            }
            //已上传价格的产品跳过
            if ($detail['upload_image'] == 1) {
                continue;
            }

            //父产品可以不用传主图，没图就不传；
            if ($detail['type'] == 0 && (empty(trim($detail['main_image'])) || $single === true)) {
                $this->detailModel->update(['upload_image' => 1], ['id' => $detail['id']]);
                continue;
            }

            //单独的一个message;
            $msgXml .= '<Message><MessageID>' . $this->getMessageId() . '</MessageID><OperationType>Update</OperationType>';

            //连接image;
            $msgXml .= '<ProductImage>';
            $msgXml .= '<SKU>' . $detail['publish_sku'] . '</SKU>';
            $msgXml .= '<ImageType>Main</ImageType>';
            $msgXml .= '<ImageLocation>' . $this->checkImgUrl($detail['main_image'], $account['code']) . '</ImageLocation>';
            $msgXml .= '</ProductImage>';
            $msgXml .= '</Message>';

            //变体有多的图片
            if ($detail['type'] == 1) {
                //swatch图片；
                if (!empty($detail['swatch_image'])) {
                    $msgXml .= '<Message><MessageID>' . $this->getMessageId() . '</MessageID><OperationType>Update</OperationType>';
                    $msgXml .= '<ProductImage>';
                    $msgXml .= '<SKU>' . $detail['publish_sku'] . '</SKU>';
                    $msgXml .= '<ImageType>Swatch</ImageType>';
                    $msgXml .= '<ImageLocation>' . $this->checkImgUrl($detail['swatch_image'], $account['code']) . '</ImageLocation>';
                    $msgXml .= '</ProductImage>';
                    $msgXml .= '</Message>';
                }
                //other图片；
                if (!empty($detail['other_image'])) {
                    $other_image = is_string($detail['other_image']) ? json_decode($detail['other_image'], true) : $detail['other_image'];
                    foreach ($other_image as $ik => $img) {
                        //PT图片只有8张 PT1-PT8
                        if ($ik >= 8) {
                            break;
                        }
                        $msgXml .= '<Message><MessageID>' . $this->getMessageId() . '</MessageID><OperationType>Update</OperationType>';
                        $msgXml .= '<ProductImage>';
                        $msgXml .= '<SKU>' . $detail['publish_sku'] . '</SKU>';
                        $msgXml .= '<ImageType>PT' . ($ik + 1) . '</ImageType>';
                        $msgXml .= '<ImageLocation>' . $this->checkImgUrl($img, $account['code']) . '</ImageLocation>';
                        $msgXml .= '</ProductImage>';
                        $msgXml .= '</Message>';
                    }
                }
            }
        }

        //如果以上没有生成xml,唯一结果是全部产品库存已上传成功；
        if (empty($msgXml)) {
            return $message;
        }

        if ($simple) {
            $message['xml'] = $msgXml;
        } else {
            $message['xml'] = $this->buildEnvelope($account['merchant_id'], 'ProductImage', $msgXml);
        }

        return $message;
    }

    /**
     * 检查图片URL如果不是以http://或https://开头的，加上bash_url;
     * @param $url
     * @return string
     */
    public function checkImgUrl($url, $accountName)
    {
        $url = $this->getUploadImageUrl($url, $accountName);
        $baseUrl = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;
        if (!preg_match('@^(?:(?:http://)|(?:https://)).*$@', $url)) {
            $url = $baseUrl . $url;
        }
        return $url;
    }

    /**
     * 处理图片URL
     * @param $imageUrl
     * @param $accountName
     * @return string
     */
    public function getUploadImageUrl($imageUrl, $accountName)
    {
        static $imageModel = null;
        if ($imageModel == null) {
            $imageModel = new GoodsImage();
        }
        return $imageModel->getThumbPath($imageUrl, 1001, 1001, $accountName, true);
    }


    /**
     * 组成跟卖价格xml
     * @param $product_id
     * @return bool|string
     * @throws Exception
     */
    public function heelSalePriceXml($heelSale)
    {

        $account = $this->checkAccount($heelSale['account_id']);
        $currency = AmazonCategoryXsdConfig::getCurrencyBySite($account['site']);

        $msgXml = '<Message>
                <MessageID>1</MessageID>
                <OperationType>Update</OperationType>
                <Price>
                    <SKU>' . $heelSale['sku'] . '</SKU>
                    <StandardPrice currency="' . $currency . '">' . $heelSale['price'] . '</StandardPrice>
                </Price>
            </Message>';

        //如果以上没有生成xml,唯一结果是全部产品价格已上传成功；
        if (empty($msgXml)) {
            return true;
        }

        //验证状号状态；
        $account = $this->checkAccount($heelSale['account_id']);
        $xml = $this->buildEnvelope($account['merchant_id'], 'Price', $msgXml);

        return $xml;
    }


    /**
     * 跟卖组成库存xml
     * @param $product_id
     * @return bool|string
     * @throws Exception
     */
    public function heelSaleQuantityXml($heelSale)
    {
        //验证状号状态；
        $account = $this->checkAccount($heelSale['account_id']);

        $messageId = 1;
        $msgXml = '<Message>
                    <MessageID>' . $messageId . '</MessageID>
                    <OperationType>Update</OperationType>
                    <Inventory>
                    <SKU>' . $heelSale['sku'] . '</SKU>
                    <Quantity>' . ceil($heelSale['quantity']) . '</Quantity>
                    </Inventory>
          </Message>';

        //如果以上没有生成xml,唯一结果是全部产品库存已上传成功；
        if (empty($msgXml)) {
            return true;
        }

        $xml = $this->buildEnvelope($account['merchant_id'], 'Inventory', $msgXml);

        return $xml;
    }


    /**
     * 跟卖组成产品XML
     * @param $product_id
     * @return bool|string
     * @throws Exception
     */
    public function heelSaleProductXml($heelSale)
    {
        //验证状号状态；
        $account = $this->checkAccount($heelSale['account_id']);
        $currency = AmazonCategoryXsdConfig::getCurrencyBySite($account['site']);

        $msgXml = '<Message>
                        <MessageID>1</MessageID>
                        <OperationType>Update</OperationType>
                    <Product>
                       <SKU>' . $heelSale['sku'] . '</SKU>
                        <StandardProductID>
                            <Type>ASIN</Type>
                            <Value>' . $heelSale['asin'] . '</Value>
                        </StandardProductID>
                    </Product>    
          </Message>';

        $xml = $this->buildEnvelope($account['merchant_id'], 'Product', $msgXml);
        return $xml;
    }
}