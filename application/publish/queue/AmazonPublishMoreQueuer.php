<?php

namespace app\publish\queue;

use app\common\service\CommonQueuer;
use app\publish\service\AmazonPublishConfig;
use think\Db;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use think\Exception;
use app\common\model\amazon\AmazonPublishProduct;
use app\common\model\amazon\AmazonPublishProductJson;
use app\common\model\amazon\AmazonPublishProductVariant;
use app\common\model\amazon\AmazonPublishProductDetail;
use app\publish\service\AmazonPublishHelper;
use app\publish\service\AmazonXsdToXmlService;
use app\common\model\amazon\AmazonPublishProductSubmission;
use app\common\service\UniqueQueuer;
use app\goods\service\GoodsImage;

class AmazonPublishMoreQueuer extends SwooleQueueJob
{
    private $accountCache;
    private $accountInfo;
    private $redisAmazonAccount;
    private $redisAmazonListing;
    private $marketplace;

    public function getName(): string
    {
        return 'amazon刊登-上传价格|图片|库存';
    }

    public function getDesc(): string
    {
        return 'amazon刊登-上传产品价格图片库存';
    }

    public function getAuthor(): string
    {
        return '冬';
    }

    public function init()
    {
        $this->accountCache = Cache::store('Account');
        $this->redisAmazonAccount = Cache::store('AmazonAccount');
    }

    public static function swooleTaskMaxNumber():int
    {
        return 20;
    }

    /** @var array 重进队列的元素 */
    public $errArr = [
        'General error: 1205 Lock wait timeout exceeded',
        'General error: 2006 MySQL server has gone away',
    ];

    public function execute()
    {
        return true;
        //队列已停用；
        set_time_limit(0);
        $id = $this->params;
        if (empty($id)) {
            return;
        }
        $errorType = '';
        try {

            $xmlhelp = new AmazonXsdToXmlService();

            //以下为type相对应的XML和上传xml时所选择的类型
            //type:1产品，2对应关系，3数量，4图片，5价钱
            $methodArr = [3 => 'buildQuantityXml', 4 => 'buildImageXml', 5 => 'buildPriceXml'];
            $republishArr = [3 => 'quantity', 4 => 'image', 5 => 'price'];
            $amazonPublishTypeArr = [
                1 => '_POST_PRODUCT_DATA_',
                2 => '_POST_PRODUCT_RELATIONSHIP_DATA_',
                3 => '_POST_INVENTORY_AVAILABILITY_DATA_',
                4 => '_POST_PRODUCT_IMAGE_DATA_',
                5 => '_POST_PRODUCT_PRICING_DATA_'
            ];
            $exceptionDsc = [
                1 => '产品', '产品关系', '库存', '图片', '价格'
            ];
            $errorStatusArr = [ 1 => 'upload_product', 'upload_relation', 'upload_quantity', 'upload_image', 'upload_price'];
            //状态用来标记库存，图片，价格的刊登结果，如果3个全部被跳过，则应该更改刊登状态为完成；
            $all_finish = true;
            foreach ($methodArr as $type => $method) {

                $errorType = $errorStatusArr[$type];
                $xml = $xmlhelp->$method($id);
                //为true则是已上传成功,此模式上传成功，直接跳过，验证下一模式；
                if ($xml === true) {
                    continue;
                }

                //已全部完成就没必要继续了；
                if ($xml === 'all_finish') {
                    return true;
                }

                $all_finish = false;
                $account = $xmlhelp->getPublishAccount();

                //以下开始上传XML，并保存提交结果ID；
                $publishHelper = new AmazonPublishHelper();
                $submissionId = $publishHelper->publishProductByType($account['id'], $xml, $amazonPublishTypeArr[$type]);

                if (empty($submissionId)) {
                    Cache::handler()->rPush('task:amazon:RePublishList:'. $republishArr[$type], $id);
                    continue;
                    //throw new Exception('刊登记录ID：' . $id . '上传'. $exceptionDsc[$type]. '时出借，未获取提交结果submissionId');
                }

                $submissionModel = new AmazonPublishProductSubmission();
                //关闭旧的通知
                $oldSubmissionIds = $submissionModel->where(['product_id' => $id, 'type' => $type, 'status' => 0])->column('id');
                if (!empty($oldSubmissionIds)) {
                    $submissionModel->update(['status' => 9], ['id' => ['in', $oldSubmissionIds]]);
                }

                //type:1产品，2对应关系，3数量，4图片，5价钱
                $submissionData = [
                    'product_id' => $id,
                    'type' => $type,
                    'account_id' => $account['id'],
                    'submission_id' => $submissionId,
                    'create_time' => time()
                ];

                //保存的记录的ID，放入队列用来取值；
                $insert_id = $submissionModel->insertGetId($submissionData);

                //保存缓存
                (new UniqueQueuer(AmazonPublishProductResultQueuer::class))->push($insert_id, 60 * 3);
            }

            //以上过程全被跳过，说明刊登已完成，应该在这里修改状态；
            //if ($all_finish) {
            //    (new AmazonPublishProduct())->update(['publish_status' => AmazonPublishConfig::PUBLISH_STATUS_FINISH], ['id' => $id]);
            //}

        } catch (QueueException $exp) {
            $this->handlerException($id, $exp->getMessage(), $errorType, $republishArr[$type]);
        } catch (Exception $exp) {
            $this->handlerException($id, $exp->getMessage(), $errorType, $republishArr[$type]);
        }
    }

    public function handlerException($id, $err, $errorType, $queue) {
        foreach ($this->errArr as $str) {
            if(strpos($err, $str) !== false) {
                return Cache::handler()->rPush('task:amazon:RePublishList:'. $queue, $id);
            }
        }

        (new AmazonPublishProduct())->update(['publish_status' => AmazonPublishConfig::PUBLISH_STATUS_ERROR], ['id' => $id]);
        Cache::store('AmazonPublish')->updateProduct($id, ['publish_status' => AmazonPublishConfig::PUBLISH_STATUS_ERROR]);
        if ($errorType) {
            $errorData = [
                $errorType => 2,
                'error_message' => json_encode([$errorType => $err], JSON_UNESCAPED_UNICODE)
            ];
            (new AmazonPublishProductDetail())->update($errorData, ['product_id' => $id]);
            Cache::store('AmazonPublish')->updateDetail($id, ['publish_sku' => 'ALL', 'data' => [$errorType => 2]]);
        }
        throw  new QueueException($err);
    }

}