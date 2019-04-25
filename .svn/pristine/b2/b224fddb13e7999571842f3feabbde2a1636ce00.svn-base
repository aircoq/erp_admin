<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2019/3/7
 * Time: 15:34
 */

namespace app\publish\service;


use app\common\cache\Cache;
use app\common\cache\driver\Lock;
use app\common\model\amazon\AmazonProductXsd;
use app\common\model\amazon\AmazonPublishProduct;
use app\common\model\amazon\AmazonPublishProductDetail;
use app\common\model\amazon\AmazonPublishProductSubmission;
use app\common\model\amazon\AmazonPublishTask;
use app\common\model\amazon\AmazonUpcParam;
use app\common\model\amazon\AmazonXsdTemplate as AmazonXsdTemplateModel;
use app\common\model\amazon\AmazonXsdTemplateDetail;
use app\common\model\GoodsPublishMap;
use app\common\service\ChannelAccountConst;
use app\common\service\CommonQueuer;
use app\common\service\UniqueQueuer;
use app\publish\queue\AmazonPublishErrorUpdateQueuer;
use app\publish\queue\AmazonPublishImageQueuer;
use app\publish\queue\AmazonPublishPriceQueuer;
use app\publish\queue\AmazonPublishQuantityQueuer;
use app\publish\queue\AmazonPublishRelationQueuer;
use app\publish\queue\AmazonPublishSysncQueuer;
use app\publish\queue\AmazonPublishUpdateTemplateQueuer;
use app\report\queue\StatisticByPublishSpuQueue;
use think\Db;
use think\Exception;

class AmazonPublishResultService
{

    private $baseUrl;

    private $lock = null;

    public function __construct()
    {
        $this->lock = new Lock();
        $this->baseUrl = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . '/';
    }


    /**
     * 处理产品刊登记录；
     * @param $subid
     * @return bool
     */
    public function handelPublishResult($subid)
    {
        $submissionModel = new AmazonPublishProductSubmission();
        $submissionData = $submissionModel->where(['id' => $subid])->find();
        if (empty($submissionData)) {
            return false;
        }

        //被关闭的状态停止访问；
        if ($submissionData['status'] == 3 || $submissionData['status'] == 9 || empty($submissionData['pids'])) {
            return false;
        }

        $field = $this->getResultUpdateField($submissionData['type']);
        $field_status = str_replace('upload_', '', $field). '_status';

        //一天前的submissionId不用管，会有专门处理的
        if ($submissionData['create_time'] < (time() - 86400)) {
            //return false;
        }

        //帐号不存在，跳过；
        $account = Cache::store('AmazonAccount')->getAccount($submissionData['account_id']);
        if (empty($account)) {
            return false;
        }

        //1.加锁，失败则证明重了，需要下次处理；
        $lockParams = ['action' => 'publish', 'type' => $submissionData['type'], 'id' => $submissionData['id']];
        //此处使用唯一锁,锁住120秒，足够完成所有查询了；
        $this->lock->unlock($lockParams);
        if (!$this->lock->uniqueLock($lockParams, 120)) {
            return false;
        }

        $productIdArr = explode(',', $submissionData['pids']);

        $productModel = new AmazonPublishProduct();
        $detailModel = new AmazonPublishProductDetail();
        $publishHelp = new AmazonPublishHelper();

        //通过submission_id获取刊登结果；
        $feedResult = $publishHelp->publishResult($account['id'], $submissionData['submission_id']);
        //请求一次，则加一次请求次数，记录最后请求时间
        $submissionModel->update(['request_number' => $submissionData['request_number'] + 1, 'last_request_time' => time()], ['id' => $subid]);
        if ($feedResult === false) {
            //为了避免出现卡死在刊登流程中的情况，每几次来，都把状态正在刊登中的记录更新一下时间；
            if ($submissionData['status'] == 1) {
                $productModel->update(['update_time' => time()], ['id' => ['in', $productIdArr], 'publish_status' => 1]);
            }
            $this->lock->unlock($lockParams);
            return false;
        }
        $resultArr = $publishHelp->xmlToArray($feedResult);

        //刊登结果，可能有警告信息，也可能有失败信息；
        $results = [];
        if (!empty($resultArr['Message']['ProcessingReport']['Result'])) {
            $results = $resultArr['Message']['ProcessingReport']['Result'];
            if (isset($results['MessageID'])) {
                $results = [$results];
            }
        }

        //错误数量；
        if (empty($resultArr['Message']['ProcessingReport']['ProcessingSummary']['MessagesProcessed'])) {
            $this->lock->unlock($lockParams);
            return false;
        }
        $allErr = false;
        if ($resultArr['Message']['ProcessingReport']['ProcessingSummary']['MessagesProcessed'] == $resultArr['Message']['ProcessingReport']['ProcessingSummary']['MessagesWithError']) {
            $allErr = true;
        }

        $errors = $this->getPublicResultErrors($results);
        $mainErr = empty($errors['Error']['main']) ? '' : $errors['Error']['main']. '|';

        //详情的状态；
        $fieldArr = [1 => 'upload_product', 'upload_relation', 'upload_quantity', 'upload_image', 'upload_price'];

        /*---------------------------------------- 以下处理每一个产品刊登记录 ----------------------------------------*/
        $creator_id = 0;    //创建者ID；
        $copyProductIdArr = $productIdArr;
        $skipTotal = 0;
        $pushMoreQueue = false;
        $productErrors = [];
        foreach ($productIdArr as $key=>$product_id) {

            /*------------------- 以下是一条记录 -------------------*/
            $product = $productModel->where(['id' => $product_id])->find();
            $detailList = $detailModel->where(['product_id' => $product['id']])->select();

            //刊登成功，跳出；
            if (empty($product) || $product['publish_status'] == 2 || empty($detailList) || count($detailList) < 2) {
                unset($copyProductIdArr[$key]);
                $skipTotal++;
                continue;
            }
            //创建者ID；
            $creator_id = $product['creator_id'] ?? 0;
            //是否刊登成单体
            $single = false;
            //标明是单体时刊登为单体；详情只有两条时（因为只有一条SKU），刊登为单体；没有变体数据时，刊登为单体；
            if ((isset($product['is_single']) && $product['is_single'] == 1) || count($detailList) <= 2 || empty($product['theme_name'])) {
                $single = true;
            }

            //SKU的条数，当SKU为2时，为特殊处理；
            $total = count($detailList);
            //用来统计所有sku当前类型是否成功，假设为成功的，但只要有一个是失败的，则失败；
            $field_status_val = 2;
            //用来更新详情
            $detailUpdate = [];
            /** @var int $publish_status product的刊登总记录 遍历product的这五个状态, 默认改为刊登中，成功数到5个，则是成功，有失败就是失败；否则就是刊登中；*/
            $publish_status = AmazonPublishConfig::PUBLISH_STATUS_UNDERWAY;
            $detail_field_status_array = [];
            $detailErrors = [];
            foreach ($detailList as $detail) {

                //找出原有的错误和警告；
                $err = json_decode($detail['error_message'], true);
                $war = json_decode($detail['warning_message'], true);
                $err = empty($err) ? [] : $err;
                $war = empty($war) ? [] : $war;

                //用来装每个SKU需要更新的东西；
                $tmp = [];

                if (!empty($errors['Warning']['sku'][$detail['publish_sku']])) {
                    $war[$field] = $errors['Warning']['sku'][$detail['publish_sku']];
                    $tmp['warning_message'] = json_encode($war, JSON_UNESCAPED_UNICODE);
                }
                //如果全错；
                if ($allErr) {
                    //全错时，原先成功了的，就不要改成错误的了；
                    if ($detail[$field] == 1) {
                        $tmp[$field] = 1; //成功；
                    } else {
                        if ($detail['type'] == 0) {
                            $err[$field] = $mainErr;
                            if (!empty($errors['Error']['sku'][$detail['publish_sku']])) {
                                $err[$field] = $mainErr. $errors['Error']['sku'][$detail['publish_sku']];
                            }
                        } else {
                            if (!empty($errors['Error']['sku'][$detail['publish_sku']])) {
                                $err[$field] = $errors['Error']['sku'][$detail['publish_sku']];
                            } else {
                                $err[$field] = $mainErr;
                            }
                        }
                        if ($field === 'upload_product' && !empty($errors['Error']['sku'][$detail['publish_sku']])) {
                            $productErrors[] = [
                                'error' => $errors['Error']['sku'][$detail['publish_sku']],
                                'product_template_id' => $product['product_template_id'],
                                'category_template_id' => $product['category_template_id'],
                            ];
                        }
                        $detailErrors[] = ['detail_id' => $detail['id'], 'field' => $field, 'error' => $err[$field]];
                        $tmp['error_message'] = json_encode($err, JSON_UNESCAPED_UNICODE);
                        $tmp[$field] = 2; //失败；
                    }
                } else {    //非全错，则按SKU来找错
                    if (!empty($errors['Error']['sku'][$detail['publish_sku']])) {
                        if ($detail['type'] == 0) {
                            $err[$field] = $mainErr. $errors['Error']['sku'][$detail['publish_sku']];
                        } else {
                            $err[$field] = $errors['Error']['sku'][$detail['publish_sku']];
                        }
                        //上传产品的报错，因为可以能要修改模板，所以另外处理
                        if ($field === 'upload_product') {
                            $productErrors[] = [
                                'error' => $errors['Error']['sku'][$detail['publish_sku']],
                                'product_template_id' => $product['product_template_id'],
                                'category_template_id' => $product['category_template_id'],
                            ];
                        }
                        $detailErrors[] = ['detail_id' => $detail['id'], 'field' => $field, 'error' => $err[$field]];
                        $tmp['error_message'] = json_encode($err, JSON_UNESCAPED_UNICODE);
                        $tmp[$field] = 2; //失败；
                    } else {
                        $tmp[$field] = 1; //成功；
                    }
                }

                //spu
                if ($detail['type'] == 0) {
                    //当单体时，，那SPU都应该是成功的，因为这条记录根本就没上传；
                    if ($single === true) {
                        $tmp[$field] = 1;
                    }

                } else {    //sku
                    //当单体时，刊登关系应该全是成功的；
                    if ($single === true && $submissionData['type'] == 2) {
                        $tmp[$field] = 1; //成功；
                    }
                }

                //装下这这一个detail需要更新的东西；
                $detailUpdate[$detail['id']] = $tmp;
                //只要详情有一个是2的，就是失败的，就需要重新刊登；
                if ($tmp[$field] == 2) {
                    $field_status_val = 0;
                }

                //下面记算出product是应该什么个状态；
                foreach ($fieldArr as $detail_field) {
                    $tmpStatus = ($detail_field == $field) ? $tmp[$field] : $detail[$detail_field];
                    //当有错时，只要有一个错，那总刊登状态就是错；
                    if (empty($detail_field_status_array[$detail_field])) {
                        $detail_field_status_array[$detail_field] = $tmpStatus;
                    } else {
                        //详情成功，主体在不是未成功和出错时，才可以附值；
                        if ($tmpStatus == 1 && (!in_array($detail_field_status_array[$detail_field], [0, 2]))) {
                            $detail_field_status_array[$detail_field] = $tmpStatus;
                        }
                        //详情未成功，主体在不是出错时可以附值；
                        if ($tmpStatus == 0 && $detail_field_status_array[$detail_field] != 2) {
                            $detail_field_status_array[$detail_field] = $tmpStatus;
                        }
                        //详情出错，主体就是出错状态；
                        if ($tmpStatus == 2) {
                            $detail_field_status_array[$detail_field] = $tmpStatus;
                        }
                    }
                }
            }

            $success_num = 0;
            foreach ($fieldArr as $detail_field) {
                if ($detail_field_status_array[$detail_field] == 2) {
                    $publish_status = AmazonPublishConfig::PUBLISH_STATUS_ERROR;
                } else if ($detail_field_status_array[$detail_field] == 1) {
                    $success_num++;
                }
            }
            if ($success_num == 5) {
                $publish_status = AmazonPublishConfig::PUBLISH_STATUS_FINISH;
            }

            //使推送的时间和记录的时间一致，防止出现不一致的情况；
            $time = time();

            //当产品刊登有小的操作；
            if ($submissionData['type'] == 1) {
                //更新模板成功失败次数；
                if ($field_status_val == 2) {
                    $pushMoreQueue = true;
                    $this->updateTemplateUseTotal($product, 'success_total');
                } else {
                    $this->updateTemplateUseTotal($product, 'error_total');
                }
            }

            //产品上传成功后，关系，库存，图片，价格1天内都会无限上传
            if (
                $submissionData['type'] > 1 &&
                $publish_status == AmazonPublishConfig::PUBLISH_STATUS_ERROR &&
                $product['create_time'] > strtotime('-1 days')
            ) {
                $publish_status = AmazonPublishConfig::DETAIL_PUBLISH_STATUS_NONE;
            }

            try {
                Db::startTrans();
                //先更新产品；
                $productModel->update([
                    'publish_status' => $publish_status,
                    $field_status => $field_status_val,
                    'update_time' => $time
                ], ['id' => $product_id]);

                //更新详情
                foreach ($detailUpdate as $detail_id=>$update) {
                    $detailModel->update($update, ['id' => $detail_id]);
                }

                //每改成功一个修改一个，这样如果在执行这个大循时断时，已经修改过的就不用再执行了；
                unset($copyProductIdArr[$key]);

                //每执行一个产品，把产品ID更新的一次，等执行完成后，再把ID全部写回去；
                if (!empty($copyProductIdArr)) {
                    $submissionModel->update(['pids' => implode(',', $copyProductIdArr), 'total' => count($copyProductIdArr)], ['id' => $subid]);
                } else {
                    $submissionModel->update(['pids' => implode(',', $productIdArr), 'total' => count($productIdArr), 'status' => 2], ['id' => $subid]);
                }

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $copyProductIdArr[$key] = $product['id'];
                //这里，如果上面被回滚了，那下面那一块的代码也没必要执行了，反而会添乱；
                if ($publish_status == AmazonPublishConfig::PUBLISH_STATUS_FINISH) {
                    continue;
                }
            }

            //如果产品刊登记录全部完成
            if ($publish_status == AmazonPublishConfig::PUBLISH_STATUS_FINISH) {
                //刊登成功后push到"SPU上架实时统计队列"
                $param = [
                    'channel_id' => ChannelAccountConst::channel_amazon,
                    'account_id' => $product['account_id'],
                    'shelf_id' => $product['creator_id'],
                    'goods_id' => $product['goods_id'],
                    'times'    => 1, //实时=1
                    'quantity' => ($total - 1),
                    'dateline' => $time
                ];
                (new CommonQueuer(StatisticByPublishSpuQueue::class))->push($param);

                //去更新一下当前商品的listing;
                (new UniqueQueuer(AmazonPublishSysncQueuer::class))->push($product['id']);

                //更新任务状态；
                AmazonPublishTask::update(['status' => 2], ['product_id' => $product_id, 'status' => ['in', [0, 1, 3]]]);

                //更新刊登记录表；
                $goodsMapModel = new GoodsPublishMap();
                $goodsStatusArr = $goodsMapModel->where(['channel' => 2, 'spu' => $product['spu']])->value('publish_status');
                $upData = [strval($submissionData['account_id'])];
                if (!empty($goodsStatusArr)) {
                    $upData = (array)json_decode($goodsStatusArr, true);
                    $upData[] = strval($submissionData['account_id']);
                    $upData = array_unique($upData);
                }
                $upData = json_encode($upData);
                $goodsMapModel->where(['channel' => 2, 'spu' => $product['spu']])->update(['publish_status' => $upData]);
            }

            //刊登错误，更新任务状态到未开始；
            if ($publish_status == AmazonPublishConfig::PUBLISH_STATUS_ERROR) {
                AmazonPublishTask::update(['status' => 0], ['product_id' => $product_id, 'status' => 1]);
            }

            //处理刊登的错误
            if (!empty($detailErrors)) {
                foreach ($detailErrors as $val) {
                    $this->publishErrorToQueue($val['detail_id'], $val['field'], $val['error']);
                }
                unset($detailErrors);
            }
        }
        /*---------------------------------------- 以上处理每一个产品刊登记录 ----------------------------------------*/

        //如果有需要推到更多队列的，在这里一点集中推，而不是有一次推一次，那样会把堆集刊登记录打乱；
        if ($pushMoreQueue && $creator_id > 0) {
            //产品刊登成功，放进各各队列；
            //(new AmazonPublishRelationQueuer($product['account_id']))->execute();
            (new UniqueQueuer(AmazonPublishRelationQueuer::class))->push($creator_id);
            (new UniqueQueuer(AmazonPublishQuantityQueuer::class))->push($creator_id);
            (new UniqueQueuer(AmazonPublishImageQueuer::class))->push($creator_id);
            (new UniqueQueuer(AmazonPublishPriceQueuer::class))->push($creator_id);
        }

        //假出现这种情况，一条submission记录里面的刊登记录全部成功，导致上面最后一个事务不会完成，这一个submission记录一天内都不会结束，一直在请求，然后还会重复写数据；
        if ($skipTotal == count($productIdArr)) {
            $submissionModel->update(['pids' => implode(',', $productIdArr), 'total' => count($productIdArr), 'status' => 2], ['id' => $subid]);
        }

        //查看是否有需要处理刊登的模板错误
        if (!empty($productErrors)) {
            try {
                $this->handelPublishProductError($productErrors);
            } catch (Exception $e) {
            }
        }

        $this->lock->unlock($lockParams);
        return true;
    }


    /**
     * @title 拿submission表type类型对应的字段；
     * @param $type
     * @return string
     */
    private function getResultUpdateField($type)
    {
        //需要更新的字段 ；
        switch ($type) {
            //上传了产品
            case 1:
                $field = 'upload_product';
                break;
            //上传了对应关系
            case 2:
                $field = 'upload_relation';
                break;
            //上传了数量
            case 3:
                $field = 'upload_quantity';
                break;
            //上传了图片
            case 4:
                $field = 'upload_image';
                break;
            //上传了价格
            case 5:
                $field = 'upload_price';
                break;
            default:
                $field = '';
        }
        return $field;
    }


    /**
     * @title 分析出刊登结果里面的错误；
     * @param $results
     * @return array
     */
    public function getPublicResultErrors($results)
    {
        $error = [];
        foreach ($results as $err) {
            if (empty($err['ResultCode'])) {
                continue;
            }
            if (!empty($err['AdditionalInfo']['SKU'])) {
                $publish_sku = $err['AdditionalInfo']['SKU'];
                $error[$err['ResultCode']]['sku'][$publish_sku] = empty($error[$err['ResultCode']]['sku'][$publish_sku]) ? $err['ResultDescription'] : $error[$err['ResultCode']]['sku'][$publish_sku] . '|' . $err['ResultDescription'];
            } else {
                $error[$err['ResultCode']]['main'] = empty($error[$err['ResultCode']]['main']) ? $err['ResultDescription'] : $error[$err['ResultCode']]['main'] . '|' . $err['ResultDescription'];
            }
        }
        return $error;
    }

    /**
     * @title 更新产品模板和分类模板；
     * 拿取通知成功后，加上或者减去数字
     * @param $product
     * @param $num
     */
    private function updateTemplateUseTotal($product, $field, $num = 1)
    {
        $templateModel = new AmazonXsdTemplateModel();

        //更新分类模板成功数；
        $cateTemplate = $templateModel->get($product['category_template_id']);
        if ($cateTemplate) {
            $templateModel->update(
                [$field => ($cateTemplate[$field] + $num)],
                ['id' => $cateTemplate['id']]
            );
        }

        //更新产品模板成功数；
        $productTemplate = $templateModel->get($product['product_template_id']);
        if ($productTemplate) {
            $templateModel->update(
                [$field => ($productTemplate[$field] + $num)],
                ['id' => $productTemplate['id']]
            );
        }
    }


    /**
     * @var array
     */
    private $publishError = [
        [
            'field' => ['upload_product'],
            'error' => 'invalid attribute value(s): standard_product_id', //无效UPC，自动换UPC；
            'type' => 1
        ],
        [
            'field' => ['upload_product'],
            'error' => '無効な値が設定されています。standard_product_id', //无效UPC，自动换UPC；
            'type' => 1
        ],
        [
            'field' => ['upload_product'],
            'error' => 'Missing Attributes standard_product_id', //无效UPC，自动换UPC；
            'type' => 1
        ],
        [
            'field' => ['upload_relation', 'upload_quantity', 'upload_image', 'upload_price'],
            'error' => 'the SKU was not created due to another error.', //产品未创建；
            'type' => 2
        ],
        [
            'field' => ['upload_image'],
            'error' => 'We could not access the media at URL', //图片上传失败；
            'type' => 3
        ],
    ];

    /**
     * 处理刊登错误，放进处理队列；
     * @param $product_id int 刊登记录ID
     * @param $error string 错误
     * @return bool 是否有处理
     */
    public function publishErrorToQueue($detail_id, $field, $error)
    {
        if (empty($detail_id) || empty($field) || empty($error)) {
            return false;
        }
        $queue = new UniqueQueuer(AmazonPublishErrorUpdateQueuer::class);
        foreach ($this->publishError as $val) {
            if (in_array($field, $val['field']) && strpos($error, $val['error']) !== false) {
                $queue->push(['detail_id' => $detail_id, 'type' => $val['type'], 'field' => $field]);
                //(new AmazonPublishErrorUpdateQueuer(['detail_id' => $detail_id, 'type' => $val['type'], 'field' => $field]))->execute();
                return true;
            }
        }
        return false;
    }


    /**
     * 队列处理刊登错误；
     * @param $detail_id
     * @param $type
     */
    public function handlePublishError($detail_id, $type, $field)
    {
        switch ($type) {
            case 1:
                $result = $this->updateDetailUpc($detail_id, $field, true);
                break;
            case 2:
                $result = $this->updateSkuNotCreate($detail_id, $field);
                break;
            case 3:
                $result = $this->updateImageNotAccess($detail_id, $field);
                break;
            default:
                $result = false;
                break;
        }

        return $result;
    }


    /**
     * UPC错误自动更换UPC，然后等待下一次自动重新刊登；
     * @param $detail_id
     * @return bool
     */
    public function updateDetailUpc($detail_id, $field, $lose_upc_param = false)
    {
        if (empty($field)) {
            $field = 'upload_product';
        }
        $detailModel = new AmazonPublishProductDetail();
        $detail = $detailModel->where(['id' => $detail_id])
            ->field('id,type,product_id,product_id_type,product_id_value,upload_product')
            ->find();
        if (empty($detail) || empty($detail['product_id'])) {
            return false;
        }
        //这个SKU产品状态不是错误，则执行下面的；
        if ($detail['upload_product'] == 2) {
            //找出旧的UPC，然后标记UPC参数错误
            $oldUpc = $detail['product_id_value'];
            if (!empty($oldUpc) && $lose_upc_param) {
                $upcWhere['header'] = substr($oldUpc, 0, 1);
                $upcWhere['code'] = substr($oldUpc, 1, 5);
                (new AmazonUpcParam())->loseParam($upcWhere);
            }

            $upcs = (new AmazonUpcService())->getUpc(1);
            if (empty($upcs[0])) {
                return false;
            }

            //换上UPC保存，并且把状态改成可上传；
            $detailModel->update(['product_id_value' => $upcs[0], $field => 0], ['id' => $detail_id]);
        }

        //查询一下还剩余的差的个数，如果不为0，则放弃，为0则把SPU调整为待刊登；
        $count = $detailModel->where(['product_id' => $detail['product_id'], $field => 2, 'type' => 1])->count();
        if ($count == 0) {
            AmazonPublishProduct::update(['publish_status' => 0, 'product_status' => 0], ['id' => $detail['product_id'], 'publish_status' => ['<>', 2]]);
            return true;
        }
        return false;
    }


    /**
     * 报sku没有创建，那直接返回创建就好了；
     * @param $detail_id
     * @return bool
     */
    public function updateSkuNotCreate($detail_id, $field)
    {
        $detailModel = new AmazonPublishProductDetail();
        $detail = $detailModel->where(['id' => $detail_id])->field('id,product_id,'. $field)->find();
        if (empty($detail) || empty($detail['product_id'])) {
            return false;
        }
        //这个SKU产品状态不是错误，则停止；
        if ($detail[$field] != 2) {
            return true;
        }
        //重置所有状态为未刊登；
        $detailModel->Update([
            'upload_product' => 0,
            'upload_relation' => 0,
            'upload_quantity' => 0,
            'upload_image' => 0,
            'upload_price' => 0
        ], ['id' => $detail_id]);

        //查询一下还剩余的差的个数，如果不为0，则放弃，为0则把SPU调整为待刊登；
        $count = $detailModel->where([
            'product_id' => $detail['product_id'],
            $field => 2,
            'type' => 1
        ])->count();
        if ($count > 0) {
            return false;
        }

        AmazonPublishProduct::update([
            'publish_status' => 0,
            'product_status' => 0,
            'relation_status' => 0,
            'quantity_status' => 0,
            'image_status' => 0,
            'price_status' => 0
        ], ['id' => $detail['product_id'], 'publish_status' => ['<>', 2]]);
        return true;
    }


    /**
     * 报图片没有访问到时，把刊登记录设为刊登中，以便继续自动刊登
     * @param $detail_id
     * @return bool
     */
    public function updateImageNotAccess($detail_id, $field)
    {
        $detailModel = new AmazonPublishProductDetail();
        $detail = $detailModel->where(['id' => $detail_id])->field('id,product_id,upload_image')->find();
        if (empty($detail) || empty($detail['product_id'])) {
            return false;
        }
        //这个SKU产品状态不是错误，则停止；
        if ($detail[$field] != 2) {
            return true;
        }

        AmazonPublishProduct::update([
            'publish_status' => 0,
            'image_status' => 0,
        ], ['id' => $detail['product_id'], 'product_status' => 2, 'publish_status' => ['<>', 2]]);
        return true;
    }


    public $templateErrors = [
        1 => '@A value is required for the "(.+)" field\.@',    //必填字段；
        2 => '@Missing Attributes (.+)\.@', //有可能是必填字段，或者是属性没有填，暂时标记为必填字段；
        //3 => '@One of \'{.+}\' is expected\.@',
    ];


    /**
     *  处理产品上传报错，根据报错修改模板；
     */
    public function handelPublishProductError($productErrors)
    {
        $lists = [];
        foreach ($productErrors as $error) {
            foreach ($this->templateErrors as $k=>$v) {
                $result = preg_match_all($v, $error['error'], $data);
                if (!$result || empty($data[1])) {
                    continue;
                }
                $pid = $error['product_template_id'];
                $cid = $error['category_template_id'];
                foreach ($data[1] as $val) {
                    $lists[$k. $val. $pid. $cid] = ['type' => $k, 'name' => $val, 'pid' => $pid, 'cid' => $cid];
                }
            }
        }
        $queue = new UniqueQueuer(AmazonPublishUpdateTemplateQueuer::class);
        foreach ($lists as $val) {
            $queue->push($val);
        }
    }


    public function autoUpdateTemplate($name, $pid, $cid, $type)
    {
        switch ($type) {
            case 1:
            case 2:
                return $this->updateRequiredTemplate($name, $pid, $cid);
                break;
            default:
                return true;
                break;
        }
    }


    /**
     * 添加或标记模板里面的必填字段；
     * @param $name
     * @param $pid
     * @param $cid
     * @return bool
     */
    public function updateRequiredTemplate($name, $pid, $cid)
    {
        $name = $this->handelErrorName($name);

        //得出0不是path, 1存在,则认为是path;
        $nameType = (int)(strpos($name, ',') !== false);
        $eleModel = new AmazonXsdTemplateModel();
        $detailModel = new AmazonXsdTemplateDetail();

        $templates = $eleModel->where(['id' => ['in', [$pid, $cid]]])
            ->field('name,class_type_id,class_name')
            ->column('*', 'id');
        if (count($templates) < 2) {
            return false;
        }
        //找出产品分类模板；
        //$pTemplate = $templates[$pid];
        $cTemplate = $templates[$cid];

        //先判断在不是产品模板里面；
        $where = ['data_type' => 0];
        if ($nameType) {
            $where['path'] = $name;
        } else {
            $where['name'] = $name;
        }
        $xsdModel = new AmazonProductXsd();
        $ele = $xsdModel->where($where)->find();
        if (!empty($ele) && $ele['has_child'] == 1) {
            return false;
        }
        if (!empty($ele) && $ele['has_child'] == 0) {
            $templateDetail = $detailModel->where(['amazon_xsd_template_id' => $pid, 'amazon_element_relation_id' => $ele['id']])
                ->field('id,name,required')->find();
            //如果存在，则判断是不是必填，是必填则停止，不是必填则更新为必填；
            if (!empty($templateDetail)) {
                if ($templateDetail['required'] == 1) {
                    return true;
                } else {
                    $templateDetail->save(['required' => 1]);
                    Cache::store('AmazonTemplate')->setTemplateAttr($pid, []);
                    return true;
                }
            }
            //以上判断不存在，则应该加一条必填数据
            $newTemplateDetail = $this->buildTemplateDetail($ele, $pid);
            $newTemplateDetail['sort'] = $detailModel->where(['amazon_xsd_template_id' => $pid])->count('id') + 1;
            $detailModel->insert($newTemplateDetail);
            Cache::store('AmazonTemplate')->setTemplateAttr($pid, []);
            return true;
        }


        /**
         * 查看元素是否在分类模板；
         * 需要先查找dataType;
         */
        $categoryIds = explode(',', $cTemplate['class_type_id']);
        $bigCategoryId = $categoryIds[0];
        $subCategoryId = $categoryIds[1] ?? 0;
        //去amazon_product_xsd定位这个
        $ele = $this->searchXsdElement($name, $nameType, $bigCategoryId, $subCategoryId);
        //如果连这个元素都没有找着，直接返回空就成了
        if (empty($ele) || !empty($ele['has_child'])) {
            return false;
        }
        //去amazon_xsd_template_detail表找数据，如果不存在则添加；
        $templateDetail = $detailModel->where(['amazon_xsd_template_id' => $cid, 'amazon_element_relation_id' => $ele['id']])
            ->field('id,required')->find();
        //如果存在，则判断是不是必填，是必填则停止，不是必填则更新为必填；
        if (!empty($templateDetail)) {
            if ($templateDetail['required'] == 1) {
                return true;
            } else {
                $templateDetail->save(['required' => 1]);
                Cache::store('AmazonTemplate')->setTemplateAttr($cid, []);
                return true;
            }
        }
        //以上判断不存在，则应该加一条必填数据
        $newTemplateDetail = $this->buildTemplateDetail($ele, $cid);
        $newTemplateDetail['sort'] = $detailModel->where(['amazon_xsd_template_id' => $cid])->count('id') + 1;
        $detailModel->insert($newTemplateDetail);
        Cache::store('AmazonTemplate')->setTemplateAttr($cid, []);
        return true;
    }


    public function buildTemplateDetail($xsdData, $templateId)
    {
        return [
            'amazon_xsd_template_id' => $templateId,
            'name' => $xsdData['name'],
            'node_tree' => $xsdData['path'],
            'amazon_element_relation_id' => $xsdData['id'],
            'required' => 1,
            'sku' => 0,
            'create_time' => time(),
            'create_id' => '1'
        ];
    }


    public function searchXsdElement($name, $nameType, $bigCategoryId, $subCategoryId, $parentName = 'ProductData')
    {
        $ele = [];
        if ($parentName == 'ProductType') {
            if (empty($subCategoryId)) {
                return [];
            }
            $lists = AmazonProductXsd::where(['pid' => $bigCategoryId, 'id' => $subCategoryId])->field('id,name,has_child,path')->select();
        } else {
            $lists = AmazonProductXsd::where(['pid' => $bigCategoryId])->field('id,name,has_child,path')->select();
        }
        foreach ($lists as $val) {
            $val = $val->toArray();
            //如果上一级是productType，那此时是在子节点里面，就应该选择对的子节点；
            if ($parentName == 'ProductType') {
                if ($val['id'] != $subCategoryId) {
                    continue;
                }
                //没有子节点，可以跳过，没有往下找的必要；
                if ($val['has_child'] == 0) {
                    continue;
                }
                $tmp = $this->searchXsdElement($name, $nameType, $val['id'], 0, $val['name']);
                if (!empty($tmp)) {
                    return $tmp;
                }
            } else {
                if ($nameType) {
                    if ($val['path'] == $name) {
                        return $val;
                    }
                } else {
                    if ($val['name'] == $name) {
                        return $val;
                    }
                }
                //没有子节点，可以跳过，没有往下找的必要；
                if ($val['has_child'] == 0) {
                    continue;
                }
                $tmp = $this->searchXsdElement($name, $nameType, $val['id'], $subCategoryId, $val['name']);
                if (!empty($tmp)) {
                    return $tmp;
                }
            }
        }

        return $ele;
    }


    public function handelErrorName($name)
    {
        //先把_单词换成驼锋法；
        $arr = explode('_', $name);
        $arr = array_map(function($val){
            return ucfirst($val);
        }, $arr);
        $name = implode('', $arr);
        $name = str_replace('/', ',', $name);

        //多余的字替换出去；
        $name = str_ireplace('Message,', '', $name);
        $indent = stripos($name, 'ProductData,');
        if ($indent !== false) {
            $name = substr($name, $indent + 12);
        }

        //替换成正确保存的路径；
        $name = trim($name, ' ,');
        return $name;
    }
}