<?php
namespace app\publish\service;

use app\common\cache\driver\Lock;
use app\common\model\amazon\AmazonListing;
use app\common\model\amazon\AmazonListingDetail;
use app\common\model\amazon\AmazonPublishProduct;
use app\common\model\amazon\AmazonPublishProductDetail;
use app\common\model\ChannelUserAccountMap;
use app\common\model\GoodsSkuMap;
use app\common\service\CommonQueuer;
use app\goods\queue\GoodsTortListingQueue;
use app\internalletter\service\InternalLetterService;
use think\Exception;
use think\Db;
use app\common\cache\Cache;
use app\common\service\Common;
use app\common\service\ChannelAccountConst;
use app\listing\service\AmazonActionLogsHelper;
use Waimao\AmazonMws\AmazonProductList as AmazonProductListSdk;
use Waimao\AmazonMws\AmazonConfig;
use Waimao\AmazonMws\AmazonFeed;
use Waimao\AmazonMws\AmazonFeedList;
use Waimao\AmazonMws\AmazonFeedResult;
use Waimao\AmazonMws\AmazonProductInfo;
use Waimao\AmazonMws\AmazonSubscribe;



class AmazonPublishHelper
{
    private $baseUrl;

    private $lock = null;

    //亚马逊通知类型
    protected $amazon_notie_type = ['AnyOfferChanged','FeedProcessingFinished','FeePromotion','FulfillmentOrderStatus','ReportProcessingFinished'];


    public function __construct()
    {
        $this->lock = new Lock();
        $this->baseUrl = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . '/';
    }

    /**
     * Amazon修改日志刊登接口
     * publishActionLog
     * @param $actionType 1:FBA/FBM || 2:Price || 3:Inventory || 4:ItemName || 5:Description || 6: DeleteListing
     * @param $product
     * @param bool $isActionLog 是否刊登修改日志
     * @return string
     */
    public function publishActionLog($actionType, $product)
    {
        if (empty($product)) {
            throw new Exception('生成的xml为空');
        }
        $lists = isset($product[0]) ? $product : [$product];
        $xml = '';
        $feedType = '';
        foreach ($lists as $key=>$product) {
            if (!is_array($product)) {
                $product = $product->toArray();
            }
            if (empty($product['seller_sku'])) {
                throw new Exception('参数 seller_sku 为空');
            }
            $messageId = $key + 1;
            switch ($actionType) {
                case 1:
                    $xml .= $this->xmlFulfillmentType($product, $messageId, true);
                    $feedType = AmazonPublishConfig::FEED_TYPE_INVENTORY_AVAILABILITY_DATA;
                    break;
                case 2:
                    $xml .= $this->xmlPrice($product, $messageId, true);
                    $feedType = AmazonPublishConfig::FEED_TYPE_PRICING_DATA;
                    break;
                case 3:
                    $xml .= $this->xmlQuantity($product, $messageId, true);
                    $feedType = AmazonPublishConfig::FEED_TYPE_INVENTORY_AVAILABILITY_DATA;
                    break;
                case 4:
                    $xml .= $this->xmlItemName($product, $messageId);
                    $feedType = AmazonPublishConfig::FEED_TYPE_PRODUCT_DATA;
                    break;
                case 5:
                    $xml .= $this->xmlItemDescription($product, $messageId);
                    $feedType = AmazonPublishConfig::FEED_TYPE_PRODUCT_DATA;
                    break;
                case 6:
                    $xml .= $this->xmlDeleteListing($product, $messageId);
                    $feedType = AmazonPublishConfig::FEED_TYPE_PRODUCT_DATA;
                    break;
                case 7:
                    $xml .= $this->xmlImage($product, $messageId, true);
                    $feedType = AmazonPublishConfig::FEED_TYPE_PRODUCT_IMAGE_DATA;
                    break;
                case 8:
                    $xml .= $this->xmlHeelProduct($product, $messageId);
                    $feedType = AmazonPublishConfig::FEED_TYPE_PRODUCT_DATA;
                    break;
                default:
                    $xml .= '';
                    $feedType = '';
                    break;
            }
        }

        if (empty($xml)) {
            throw new Exception('生成的xml为空');
        }

        $apiParams = $this->apiParams($lists[0]['account_id']);
        $feedContent = $this->combineXmlEnvelope($apiParams['merchantId'], $feedType, $xml);

        return $this->submitFeed($apiParams, $feedContent, $feedType);
    }

    /**
     * Amazon新品刊登接口
     * publish
     * @param $actionType 1:Product || 2:Price || 3:Inventory || 4:FBA || 5:Image || 6:Relationship
     * @param $product
     * @param bool $isActionLog 是否刊登修改日志
     * @return string
     */
    public function publish($actionType, $product)
    {
        switch ($actionType) {
            case 1:
                $xml = $product;
                $feedType = AmazonPublishConfig::FEED_TYPE_PRODUCT_DATA;
                break;
            case 2:
                $xml = $this->xmlPrice($product);
                $feedType = AmazonPublishConfig::FEED_TYPE_PRICING_DATA;
                break;
            case 3:
                $xml = $this->xmlQuantity($product);
                $feedType = AmazonPublishConfig::FEED_TYPE_INVENTORY_AVAILABILITY_DATA;
                break;
            case 4:
                $xml = $this->xmlFBA($product);
                $feedType = AmazonPublishConfig::FEED_TYPE_INVENTORY_AVAILABILITY_DATA;
                break;
            case 5:
                $xml = $this->xmlImage($product);
                $feedType = AmazonPublishConfig::FEED_TYPE_PRODUCT_IMAGE_DATA;
                break;
            case 6:
                $xml = $this->xmlRelationship($product);
                $feedType = AmazonPublishConfig::FEED_TYPE_PRODUCT_RELATIONSHIP_DATA;
                break;
            default:
                break;
        }

        $apiParams = $this->apiParams($product['account_id']);

        $feedContent = $this->combineXmlEnvelope($apiParams['merchantId'], $feedType, $xml);
        //var_dump($feedContent);exit;
        return $this->submitFeed($apiParams, $feedContent, $feedType);
    }


    /**
     * Amazon新品刊登接口
     * publish
     * @param $actionType 1:Product || 2:Price || 3:Inventory || 4:FBA || 5:Image || 6:Relationship
     * @param $product
     * @param bool $isActionLog 是否刊登修改日志
     * @return string
     */
    public function getXmlByActionType($actionType, $product, $index = 1)
    {
        switch ($actionType) {
            case 1:
                $xml = $product;
                $feedType = AmazonPublishConfig::FEED_TYPE_PRODUCT_DATA;
                break;

            case 2:
                $xml = $this->xmlPrice($product);
                $feedType = AmazonPublishConfig::FEED_TYPE_PRICING_DATA;
                break;

            case 3:
                $xml = $this->xmlQuantity($product);
                $feedType = AmazonPublishConfig::FEED_TYPE_INVENTORY_AVAILABILITY_DATA;
                break;

            case 4:
                $xml = $this->xmlFBA($product);
                $feedType = AmazonPublishConfig::FEED_TYPE_INVENTORY_AVAILABILITY_DATA;
                break;

            case 5:
                $xml = $this->xmlImage($product);
                $feedType = AmazonPublishConfig::FEED_TYPE_PRODUCT_IMAGE_DATA;
                break;

            case 6:
                $xml = $this->xmlRelationship($product, $index);
                $feedType = AmazonPublishConfig::FEED_TYPE_PRODUCT_RELATIONSHIP_DATA;
                break;
            default:
                $xml = "";
                break;
        }
        return $xml;
    }


    public function publishProductByType($accountId, $xml, $feedType, $max = 3)
    {
        try {
            $apiParams = $this->apiParams($accountId);
            $result = $this->submitFeed($apiParams, $xml, $feedType);
            return $result;
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(). '|'. $e->getLine(). '|'. $e->getFile());
        }
    }


    /**
     * xmlFulfillmentType
     * @param array $product :
     *        if $isActionLog = true:  $product['product']['seller_sku'], $product['new_value']['fulfillment_type']
     *        if $isActionLog = false: $product['seller_sku'], $product['fulfillment_type']:1-FMB;2-FBA
     * @param int $message_id
     * @param bool $isActionLog
     * @return mixed
     */
    public function xmlFulfillmentType($product, $message_id = 1, $isActionLog = false)
    {
        //actionLog
        $seller_sku = $product['seller_sku'];
        if ($isActionLog) {
            $fulfillment_type = $product['new_value']['fulfillment_type'];
            //newProduct
        } else {
            $fulfillment_type = $product['fulfillment_type'];
        }

        if ($fulfillment_type == 1) {
            $search = ['{$message_id}', '{$seller_sku}', '{$fulfillmentCenterID}'];
            $replace = [$message_id, $seller_sku, AmazonPublishConfig::$fulfillmentCenterID[0]];
            $xml = str_replace($search, $replace, AmazonPublishConfig::XML_FULFILLMENT_TYPE_MFN);
        } elseif ($fulfillment_type == 2) {
            $search = ['{$message_id}', '{$seller_sku}', '{$fulfillmentCenterID}'];
            $replace = [$message_id, $seller_sku, AmazonPublishConfig::$fulfillmentCenterID[1]];
            $xml = str_replace($search, $replace, AmazonPublishConfig::XML_FULFILLMENT_TYPE_AFN);
        }
        return $xml;
    }

    /**
     * xmlFBA
     * @param array $product :
     *        if $isActionLog = true:  $product['product']['seller_sku'], $product['new_value']['fulfillment_type']
     *        if $isActionLog = false: $product['seller_sku'], $product['fulfillment_type']:1-FMB;2-FBA
     * @param int $message_id
     * @param bool $isActionLog
     * @return mixed
     */
    public function xmlFBA($product, $message_id = 1, $isActionLog = false)
    {
        //actionLog
        if ($isActionLog) {
            $seller_sku = $product['product']['seller_sku'];
            //newProduct
        } else {
            $seller_sku = $product['seller_sku'];
        }

        $search = ['{$message_id}', '{$seller_sku}', '{$fulfillmentCenterID}'];
        $replace = [$message_id, $seller_sku, AmazonPublishConfig::$fulfillmentCenterID[1]];
        $xml = str_replace($search, $replace, AmazonPublishConfig::XML_FULFILLMENT_TYPE_AFN);
        return $xml;
    }

    /**
     * xmlQuantity
     * @param array $product :
     *        if $isActionLog = true:  $product['product']['seller_sku'], $product['new_value']['quantity']
     *        if $isActionLog = false: $product['seller_sku'], $product['quantity']
     * @param int $message_id
     * @param bool $isActionLog
     * @return mixed
     */
    public function xmlQuantity($product, $message_id = 1, $isActionLog = false)
    {
        $seller_sku = $product['seller_sku'];
        //actionLog
        if ($isActionLog) {
            $qunatity = $product['new_value']['quantity'];
            //newProduct
        } else {
            $qunatity = $product['quantity'];
        }
        $search = ['{$message_id}', '{$seller_sku}', '{$qunatity}'];
        $replace = [$message_id, $seller_sku, $qunatity];
        $xml = str_replace($search, $replace, AmazonPublishConfig::XML_QUANTITY);
        return $xml;
    }

    /**
     * xmlPrice
     * @param array $product :
     *        if $isActionLog = true:  $product['product']['seller_sku'], $product['new_value']['price']
     *        if $isActionLog = false: $product['seller_sku'], $product['price']
     * @param int $message_id
     * @param bool $isActionLog
     * @return mixed
     */
    public function xmlPrice(array $product, $message_id = 1, $isActionLog = false)
    {
        //actionLog
        $seller_sku = $product['seller_sku'];
        if ($isActionLog) {
            $price = $product['new_value']['price'];
            //newProduct
        } else {
            $price = $product['price'];
        }
        $currency = isset(AmazonPublishConfig::$baseCurrencyCode['DEFAULT']) ? AmazonPublishConfig::$baseCurrencyCode['DEFAULT'] : 'DEFAULT';

        $search = ['{$message_id}', '{$seller_sku}', '{$price}', '{$currency}'];
        $replace = [$message_id, $seller_sku, $price, $currency];
        $xml = str_replace($search, $replace, AmazonPublishConfig::XML_PRICE);
        return $xml;
    }

    /**
     * xmlItemName
     * @param array $product :
     *        if $isActionLog = true:  $product['product']['seller_sku'], $product['new_value']['itemname']
     *        if $isActionLog = false: newProduct won't call this method
     * @param int $message_id
     * @param bool $isActionLog
     * @return mixed
     */
    public function xmlItemName(array $product, $message_id = 1)
    {
        //actionLog
        $seller_sku = $product['seller_sku'];
        $item_name = $product['new_value']['itemname'];

        $search = ['{$message_id}', '{$seller_sku}', '{$item_name}'];
        $replace = [$message_id, $seller_sku, $item_name];
        $xml = str_replace($search, $replace, AmazonPublishConfig::XML_ITEM_NAME);
        return $xml;
    }

    /**
     * xmlItemName
     * @param array $product :
     *        if $isActionLog = true:  $product['product']['seller_sku'], $product['new_value']['description']
     *        if $isActionLog = false: newProduct won't call this method
     * @param int $message_id
     * @param bool $isActionLog
     * @return mixed
     */
    public function xmlItemDescription(array $product, $message_id = 1)
    {
        //actionLog
        $seller_sku = $product['seller_sku'];
        $item_name = $product['new_value']['description'];

        $search = ['{$message_id}', '{$seller_sku}', '{$description}'];
        $replace = [$message_id, $seller_sku, $item_name];
        $xml = str_replace($search, $replace, AmazonPublishConfig::XML_ITEM_DESCRIPTION);
        return $xml;
    }

    /**
     * xmlImage
     * @param array $product :
     *        if $isActionLog = true:  $product['product']['seller_sku'], $product['new_value']['image']
     *        if $isActionLog = false: $product['seller_sku'], $product['image']
     * @param int $message_id
     * @param bool $isActionLog
     * @return mixed
     */
    public function xmlImage(array $product, $message_id = 1, $isActionLog = false)
    {
        //actionLog
        if ($isActionLog) {
            $seller_sku = $product['seller_sku'];
            $tmpImage = $product['new_value']['image'];

            $image = [];
            foreach ($tmpImage as $val) {
                $tmp = [];
                $tmp['url'] = $val['path'];
                if ($val['is_default'] == 1 || $val['is_swatch'] == 1) {
                    if ($val['is_default'] == 1) {
                        $tmp['type'] = 'main';
                        $image[] = $tmp;
                    }
                    if ($val['is_swatch'] == 1) {
                        $tmp['type'] = 'swatch';
                        $image[] = $tmp;
                    }
                } else {
                    $tmp['type'] = 'pt';
                    $image[] = $tmp;
                }
            }

            //newProduct
        } else {
            $seller_sku = $product['seller_sku'];
            $image = $product['image'];
        }
        if (empty($image)) {
            return '';
        }

        $account = Cache::store('AmazonAccount')->getAccount($product['account_id']);
        $xml = '';
        $index = 1;
        $xmlToPublishServer = new AmazonXsdToXmlService();
        foreach ($image as $img) {
            $imgType = $img['type'];
            $image_location = $xmlToPublishServer->checkImgUrl($img['url'], $account['account_name']);
            if ($imgType == 'main') {
                $search = ['{$message_id}', '{$seller_sku}', '{$image_location}'];
                $replace = [$message_id, $seller_sku, $image_location];
                $xml .= str_replace($search, $replace, AmazonPublishConfig::XML_IMAGE_MAIN);
                $message_id++;
            } elseif ($imgType == 'swatch') {
                $search = ['{$message_id}', '{$seller_sku}', '{$image_location}'];
                $replace = [$message_id, $seller_sku, $image_location];
                $xml .= str_replace($search, $replace, AmazonPublishConfig::XML_IMAGE_SWATCH);
                $message_id++;
            } elseif ($imgType == 'pt') {
                if ($index >7) {
                    continue;
                }
                $search = ['{$message_id}', '{$seller_sku}', '{$index}', '{$image_location}'];
                $replace = [$message_id, $seller_sku, $index, $image_location];
                $xml .= str_replace($search, $replace, AmazonPublishConfig::XML_IMAGE_PT);
                $index++;
                $message_id++;
            }
        }

        return $xml;
    }

    /**
     * xmlImage
     * @param array $product :
     *        if $isActionLog = true:  $product['product']['seller_sku'], $product['new_value']['image']
     *        if $isActionLog = false: $product['seller_sku'], $product['image']
     * @param int $message_id
     * @param bool $isActionLog
     * @return mixed
     */
    public function xmlHeelProduct(array $product, $message_id = 1)
    {
        //actionLog
        //$seller_sku = $product['seller_sku'];
        //$item_name = $product['new_value']['description'];
        //
        //$search = ['{$message_id}', '{$seller_sku}', '{$description}'];
        //$replace = [$message_id, $seller_sku, $item_name];
        //$xml = str_replace($search, $replace, AmazonPublishConfig::XML_ITEM_DESCRIPTION);
        //return $xml;


        //验证状号状态；
        //$account = $this->checkAccount($heelSale['account_id']);
        //$currency = AmazonCategoryXsdConfig::getCurrencyBySite($account['site']);

        $msgXml = '<Message>
                        <MessageID>'. $message_id. '</MessageID>
                        <OperationType>Update</OperationType>
                    <Product>
                       <SKU>' . $product['seller_sku'] . '</SKU>
                        <StandardProductID>
                            <Type>ASIN</Type>
                            <Value>' . $product['new_value']['asin'] . '</Value>
                        </StandardProductID>
                    </Product>    
          </Message>';

        //$xml = $this->buildEnvelope($account['merchant_id'], 'Product', $msgXml);
        return $msgXml;
    }

    /**
     * xmlRelationship
     * @param array $product :
     *        if $isActionLog = true:  $product['product']['seller_sku'], $product['new_value']['seller_spu'], $product['new_value']['relation_type']
     *        if $isActionLog = false: $product['seller_sku'], $product['seller_spu'], $product['relation_type']
     * @param int $message_id
     * @param bool $isActionLog
     * @return mixed
     */
    public function xmlRelationship(array $product, $message_id = 1, $isActionLog = false)
    {
        //actionLog
        if ($isActionLog) {
            $seller_sku = $product['product']['seller_sku'];
            $seller_spu = $product['new_value']['seller_spu'];
            $relation_type = isset($product['new_value']['relation_type']) ? $product['new_value']['relation_type'] : 'Variation';
        } else {
            $seller_sku = $product['seller_sku'];
            $seller_spu = $product['seller_spu'];
            $relation_type = isset($product['relation_type']) ? $product['relation_type'] : 'Variation';
        }

        $search = ['{$message_id}', '{$seller_spu}', '{$seller_sku}', '{$relation_type}'];
        $replace = [$message_id, $seller_spu, $seller_sku, $relation_type];
        $xml = str_replace($search, $replace, AmazonPublishConfig::XML_RELATIONSHIP);
        return $xml;
    }

    /**
     * xmlDeleteListing
     * @param array $product :
     *        if $isActionLog = true:  $product['product']['seller_sku']
     *        if $isActionLog = false: newProduct won't call this method
     * @param int $message_id
     * @param bool $isActionLog
     * @return mixed
     */
    public function xmlDeleteListing(array $product, $message_id = 1)
    {
        //actionLog
        $seller_sku = $product['seller_sku'];

        $search = ['{$message_id}', '{$seller_sku}'];
        $replace = [$message_id, $seller_sku];
        $xml = str_replace($search, $replace, AmazonPublishConfig::XML_DELETE_LISTING);
        return $xml;
    }

    /**
     * 组装刊登XML的信封及头部内容
     * combineXmlEnvelope
     * @param $merchant_id
     * @param $xml
     * @return mixed
     */
    public function combineXmlEnvelope($merchant_id, $feedType, $xml)
    {
        if (!$feedType || !$xml) {
            return false;
        }
        $xmlEnvelope = AmazonPublishConfig::XML_ENVELOPE_START
            . AmazonPublishConfig::XML_HEADER
            . AmazonPublishConfig::$xml_message_type[$feedType]
            . $xml
            . AmazonPublishConfig::XML_ENVELOPE_END;
        return str_replace('{$merchant_identifier}', $merchant_id, $xmlEnvelope);
    }


    /**
     * MWS刊登Feed提交接口
     * submitFeed
     * @param array $apiParams
     * @param $feedContent
     * @param $feedType
     * @return string
     */
    public function submitFeed(array $apiParams, $feedContent, $feedType)
    {
        $amz = new AmazonFeed($apiParams);

        $amz->setFeedType($feedType);
        $amz->setMarketplaceIds($apiParams['marketplaceId']);
        $amz->setFeedContent($feedContent);

        $amz->submitFeed();
        $response = $amz->getResponse();
        $feedSubmissionId = isset($response['FeedSubmissionId']) ? $response['FeedSubmissionId'] : '';
        return $feedSubmissionId;
    }

    /**
     * 通过帐号ID及$feedSubmissionId, 获取状态并取得结果的接口
     * publishResult
     * @param $account_id
     * @param $feedSubmissionId
     * @return bool|string
     */
    public function publishResult($account_id, $feedSubmissionId)
    {
        try {
            $apiParams = $this->apiParams($account_id);
            return $this->feedResult($apiParams, $feedSubmissionId);
        } catch (\Exception $e) {
            Cache::handler()->hSet('task:amazon:getPublishResult-exception', $account_id. '-'. $feedSubmissionId, $e->getMessage());
            return false;
        }
    }

    /**
     * MWS刊登一次性获取状态并取得结果的接口
     * feedResult
     * @param array $apiParams
     * @param $feedSubmissionId
     * @return bool|string
     */
    public function feedResult(array $apiParams, $feedSubmissionId)
    {
        $feedStatus = $this->feedSubmissionList($apiParams, $feedSubmissionId);
        if ($feedStatus == '_DONE_') {
            return $this->feedSubmissionResult($apiParams, $feedSubmissionId);
        } else {
            return false;
        }
    }

    /**
     * MWS刊登Feed结果状态接口
     * feedSubmissionList
     * @param array $apiParams
     * @param $feedSubmissionId
     * @return bool|string
     */
    public function feedSubmissionList(array $apiParams, $feedSubmissionId)
    {
        $amz = new AmazonFeedList($apiParams);
        $amz->setFeedIds($feedSubmissionId);
        $amz->fetchFeedSubmissions();
        return $amz->getFeedStatus();
    }

    /**
     * MWS刊登Feed结果内容接口
     * feedSubmissionResult
     * @param array $apiParams
     * @param $feedSubmissionId
     * @return bool|string
     */
    public function feedSubmissionResult(array $apiParams, $feedSubmissionId)
    {
        $amz = new AmazonFeedResult($apiParams, $feedSubmissionId);
        //$amz->setFeedId($feedSubmissionId);
        $amz->fetchFeedResult();
        return $amz->getRawFeed();
    }

    /**
     * 更新listing修改日志的status及message
     * saveActionLogResult
     * @param string $xml
     * @param $action_log_id
     */
    public function saveActionLogResult($xml = '', $action_log_id)
    {
        $resultArr = $this->xmlToArray($xml);

        $successful = $resultArr['Message']['ProcessingReport']['ProcessingSummary']['MessagesSuccessful'];
        //$error = $resultArr['Message']['ProcessingReport']['ProcessingSummary']['MessagesWithError'];
        //$warning = $resultArr['Message']['ProcessingReport']['ProcessingSummary']['MessagesWithWarning'];

        $message = '';
        if ($successful >= 1) {
            $status = 1;
        } else {
            $status = 2;
        }
        $result = [];
        if (!empty($resultArr['Message']['ProcessingReport']['Result'])) {
            $result = $resultArr['Message']['ProcessingReport']['Result'];
            if (isset($result['MessageID'])) {
                $result = [$result];
            }
        }
        foreach ($result as $val) {
            $message .= $val['ResultDescription'] . '|';
        }
        if (!empty($message)) {
            $message = substring(trim($message, '|'), 0, 1000);
        }
        $where = ['id' => $action_log_id];
        $data = ['status' => $status, 'message' => $message];
        (new AmazonActionLogsHelper())->updateActionLogStatus($where, $data);
    }

    /**
     * @Title 刊登成功后，加入listing数据；
     * @param $product_id
     * @return bool
     * @throws Exception
     */
    public function updateListingFromProductId($product_id) {
        $product = AmazonPublishProduct::where(['id' => $product_id])->field('id,account_id,goods_id,spu,publish_status')->find();
        if (!$product) {
            throw new Exception('刊登记录不存在');
        }
        if ($product['publish_status'] != AmazonPublishConfig::PUBLISH_STATUS_FINISH) {
            throw new Exception('产品未刊登完成');
        }
        $details = AmazonPublishProductDetail::where(['product_id' => $product_id, 'type' => 1])->column('*', 'publish_sku');
        if (empty($details)) {
            throw new Exception('没有找着子产品数据');
        }

        $listingModel = new AmazonListing();
        $ldModel = new AmazonListingDetail();
        $goodsSkuMap = new GoodsSkuMap();

        //检查SKU有否在listing表里面记录数据，有的说明已经运行过了；
        $skus = [];
        foreach ($details as $detail) {
            $count = $listingModel->where(['seller_sku' => $detail['publish_sku']])->count();
            if (empty($count)) {
                $skus[] = $detail['publish_sku'];
            }
        }

        //用SKUS去同步数据；
        $lists = $this->getProductFromSku($skus, $product['account_id']);
        if (empty($lists)) {
            return;
        }

        $time = time();
        $user = Common::getUserInfo();
        $accountInfo = Cache::store('AmazonAccount')->getAccount($product['account_id']);
        $currency = AmazonCategoryXsdConfig::getCurrencyBySite($accountInfo['site']);

        foreach ($lists as $key=>$val) {
            try {
                Db::startTrans();
                $detail = $details[$key];
                $data = $val->getData();

                $main = [];
                $main['goods_id'] = $product['goods_id'];
                $main['spu'] = $product['spu'];
                $main['sku'] = $detail['sku'];
                $main['account_id'] = $product['account_id'];
                $main['site'] = $accountInfo['site'];
                $main['seller_sku'] = $detail['publish_sku'];

                $main['currency'] = $currency;
                $main['price'] = $detail['standard_price'];
                $main['image_url'] = $detail['main_image'];
                $main['item_name'] = !empty($data['AttributeSets'][0]['Title'])? $data['AttributeSets'][0]['Title'] : $detail['title'];

                $main['asin1'] = empty($data['Identifiers']['MarketplaceASIN']['ASIN'])? '' : $data['Identifiers']['MarketplaceASIN']['ASIN'];
                $main['publish_detail_id'] = $detail['id'];

                $main['create_user_id'] = $user['user_id'];
                $main['create_time'] = $time;
                $main['modify_time'] = $time;

                $sku_map = $goodsSkuMap->field('sku_id,sku_code,goods_id,sku_code_quantity')->where([
                    'channel_id' => ChannelAccountConst::channel_amazon,
                    'account_id' => $product['account_id'],
                    'channel_sku' => $detail['publish_sku'],
                ])->find();

                if (!empty($sku_map)) {
                    $main['sku_id'] = $sku_map['sku_id'] ?? '';
                    //sku_quantity
                    $sku_quantity = '';
                    $sku_code_quantity = json_decode($sku_map['sku_code_quantity'], true);
                    if (is_array($sku_code_quantity) && !empty($sku_code_quantity)) {
                        //初始化一下
                        $skuQuantityArr = [];
                        foreach ($sku_code_quantity as $skuQuantity) {
                            $skuQuantityArr[] = $skuQuantity['sku_code'] . '*' . $skuQuantity['quantity'];
                        }
                        $sku_quantity = implode(',', $skuQuantityArr);
                    } else {
                        $sku_quantity = $data['sku'];
                    }
                    $main['sku_quantity'] = $sku_quantity;
                }

                $lid = $listingModel->insertGetId($main);

                $ldModel->insert([
                    'listing_id' => $lid,
                    'description' => $detail['description']
                ]);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                throw new Exception($e->getMessage());
            }
        }

        return true;
    }

    /**
     * @Title 通过publish_sku去亚马逊wms上面去拿产品
     * @param array $skus
     * @param $account_id
     * @return array
     */
    public function getProductFromSku(Array $skus, $account_id) {
        //帐号参数；
        $apiParams = $this->apiParams($account_id);
        $data = [];
        foreach ($skus as $val) {
            $result = $this->getMatchingProductForSku($val, $apiParams);
            if ($result !== false) {
                $data[$val] = $result;
            }
        }
        return $data;
    }

    /**
     * @Title 传入一个sku或SKU数组，返回一个对象或对象数组；
     * @deprecated 建议传入一个sku,因为如果传入多个SKU，返回的结果将没有辨别哪个对象结果是哪个SKU的；
     * @param $sku Mixed [array || string]
     * @param $apiParams
     * @return array|bool|\Waimao\AmazonMws\AmazonProduct
     */
    public function getMatchingProductForSku($sku, $apiParams) {
        $sdkhelp = new AmazonProductListSdk($apiParams);
        $sdkhelp->setIdType('SellerSKU');
        $sdkhelp->setProductIds($sku);
        $sdkhelp->fetchProductList();
        $lists = $sdkhelp->getProduct();
        unset($sdkhelp);
        if ($lists === false) {
            return false;
        }
        if (is_array($sku)) {
            return $lists;
        }
        return $lists[0];
    }

    /**
     * @param $productObj
     * @param $detail
     */
    public function buildListingfromMatchingProduct($productObj, $detail, $product = []) {
    }

    /**
     * 更新刊登记录的status及message
     * savePublishRecordResult
     * @param string $xml
     * @param $id
     */
    public function savePublishRecordResult($xml = '', $id = 0)
    {
        //更新单条刊登记录
        if ($id > 0) {

            //更新多条刊登记录
        } else {

        }
    }

    public function xmlToArray($xml = '')
    {
        if (!$xml) {
            return false;
        }
        $xmlResult = simplexml_load_string($xml);
        return json_decode(json_encode($xmlResult), true);
    }

    public function apiParams($account_id = 0)
    {
        if (!is_numeric($account_id) || $account_id === 0) {
            throw new Exception('Amazon帐号ID参数错误，不是数字，或者是0');
        }
        $accountInfo = Cache::store('AmazonAccount')->getAccount($account_id);

        $site = trim(strtoupper($accountInfo['site']));
        $apiParams = [
            'merchantId' => trim($accountInfo['merchant_id']),
            'marketplaceId' => AmazonConfig::$marketplaceId[$site],
            'keyId' => trim(paramNotEmpty($accountInfo, 'developer_access_key_id', $accountInfo['access_key_id'])),
            'secretKey' => trim(paramNotEmpty($accountInfo, 'developer_secret_key', $accountInfo['secret_key'])),
            'authToken' => trim(paramNotEmpty($accountInfo, 'auth_token','')),
            'amazonServiceUrl' => AmazonConfig::$serverUrl[$site]. '/'
        ];
        return $apiParams;
    }


    /**
     *根据asin获取竞价
     *
     */
    public function pricingForASIN($params)
    {

        $apiParams = $this->apiParams($params['account_id']);
        $productInfo = new AmazonProductInfo($apiParams);

        $productInfo->setASINs($params['asin']);
        //商品的当前有竞争力的价格
        $productInfo->fetchCompetitivePricing();
        //价格最低的在售商品的价格信息
        //$productInfo->fetchLowestOffer($params['asin']);

        //自己的商品的价格信息
        //$productInfo->fetchMyPrice($params['asin']);
        $product = $productInfo->getProduct();

        if($product){
            return json_decode(json_encode($product), true);
        }

        return [];
    }


    public function getSellerIdBySku(int $sku_id)
    {
        $accounts = AmazonListing::where(['sku_id' => $sku_id])->group('account_id')->column('account_id');
        if (empty($accounts)) {
            return [];
        }
        return ChannelUserAccountMap::where(['account_id' => ['in', $accounts], 'channel_id' => ChannelAccountConst::channel_amazon])->column('seller_id');
    }


    public function getSellerIdByGoodsId(int $goods_id)
    {
        $accounts = AmazonListing::where(['goods_id' => $goods_id])->group('account_id')->column('account_id');
        if (empty($accounts)) {
            return [];
        }
        return ChannelUserAccountMap::where(['account_id' => ['in', $accounts], 'channel_id' => ChannelAccountConst::channel_amazon])->column('seller_id');
    }


    /**
     * amazon平台侵权下架；
     * @param $params
     */
    public function infingeEnd($params)
    {
        //$params = [
        //    'tort_id' => $row['tort_id'],//侵权id
        //    'goods_id' => $row['goods_id'],//商品id
        //    'ban_shop_id' => explode(',', $row['ban_shop_id']),//不用下架的店铺id
        //    'notice_channel' => explode(',', $row['notice_channel']),//需要通知的渠道id
        //    'reason' => $row['reason'],//原因
        //    'channel_id' => $row['channel_id']//需要下架的渠道；
        //];

        //推送需要下架的产品和需要下架的平台，如果不存在则无法执行；
        if (empty($params) || empty($params['goods_id']) || empty($params['channel_id'])) {
            return;
        }

        //参数类型不对，停止；
        if (!is_numeric($params['channel_id']) || !is_numeric($params['goods_id'])) {
            return;
        }

        $channel_id = ChannelAccountConst::channel_amazon;

        //是否需要下架；
        $end = $params['channel_id'] == $channel_id;

        //是否需要通知；
        $params['notice_channel'] = (empty($params['notice_channel']) || !is_array($params['notice_channel'])) ? [] : $params['notice_channel'];
        $notice = in_array($channel_id, $params['notice_channel']);

        //不需要下架也不需要通知，可以直接终止了
        if ($end === false && $notice === $end) {
            return;
        }

        //忽略的店铺；
        $lgnore = [];
        if ($end && empty($params['ban_shop_id'])) {
            $lgnore = $params['ban_shop_id'];
        }

        $where['goods_id'] = $params['goods_id'];
        //$where['quantity'] = ['>', 0];//去掉数量查询，存在的就传

        $model = new AmazonListing();
        $list = $model->field('id,amazon_listing_id,goods_id,account_id,spu,quantity')->where($where)->select();

        //查询侵权下架的数据,没有则终止；
        if (empty($list)) {
            return;
        }

        //处理下架；
        $acctionService = new AmazonActionLogsHelper();

        //找出帐号绑定的用户ID；
        $account_ids = [];
        $newList = [];
        foreach ($list as $val) {
            $account_ids[] = $val['account_id'];
            $newList[] = $val->toArray();
        }
        $users = [];
        if (!empty($account_ids)) {
            $users = ChannelUserAccountMap::where([
                'channel_id' => $channel_id,
                'account_id' => ['in', $account_ids]
            ])->column('seller_id', 'account_id');
        }

        foreach ($newList as $key => $val) {
            $title = 'SPU:'.$val['spu'].'侵权';
            $msg = 'SPU:'.$val['spu'].'因侵权原因已在erp平台已下架，请及时处理对应平台。';
            //需要下架才给回写数据；
            if ($end && !in_array($val['account_id'], $lgnore)) {
                $msg .= '根据平台配置，将由ERP自动处理下架';

                $data = [
                    'goods_id' => $val['goods_id'],//商品id
                    'goods_tort_id' => $params['tort_id'],//侵权下架id
                    'listing_id' => $val['id'],//listing_id
                    'channel_id' => $channel_id,//平台id
                    'item_id' => $val['amazon_listing_id'],//平台listing唯一码
                    'status' => '0'//状态 0 待下架   1 下架成功 2 下架失败
                ];
                //初始化回写
                //(new GoodsTortListingQueue($data))->execute();
                (new CommonQueuer(GoodsTortListingQueue::class))->push($data);

                //推送进下架队列；
                $actionData = [];
                $actionData[] = [
                    'amazon_listing_id' => $val['id'],
                    'account_id' => $val['account_id'],
                    'new_value' => 0,
                    'old_value' => $val['quantity'],
                ];
                $callback_type = 1;
                $callback_param = json_encode(['tort_id' => $params['tort_id']]);
                $acctionService->editListingData(json_encode($actionData), 'quantity', 0, '', 0, $callback_type, $callback_param);
            }

            //发送钉钉消息
            if (!empty($users[$val['account_id']])) {
                $receive_id = $users[$val['account_id']];
                //$receive_id = 2293;
                $dingParams = [
                    'receive_ids'=> $receive_id,
                    'title'=> $title,
                    'content'=> $msg,
                    'type'=> 13,
                    'dingtalk'=> 1,
                    'create_id' => 1
                ];
                InternalLetterService::sendLetter($dingParams);
            }
        }
    }


    /**
     * @title 侵权下架完成后执行的回调函数；
     * @desc 在AmazonActionLogsHelper::updateActionLogStatus，会通过call_user_func_array调用
     * @param $action
     */
    public function backInfingeEnd ($action)
    {
        if (empty($action['amazon_listing_id'])) {
            $action->save(['callback_status' => 1]);
            return;
        }

        $params = empty($action['callback_param']) ? [] : json_decode($action['callback_param'], true);
        if (empty($params['tort_id'])) {
            $action->save(['callback_status' => 1]);
            return;
        }

        //查询listing;
        $listing = AmazonListing::where(['id' => $action['amazon_listing_id']])->find();
        if (empty($listing)) {
            $action->save(['callback_status' => 1]);
            return;
        }
        //渠道ID；
        $channel_id = ChannelAccountConst::channel_amazon;

        $data = [
            'goods_id' => $listing['goods_id'],//商品id
            'goods_tort_id' => $params['tort_id'],//侵权下架id
            'listing_id' => $listing['id'],//listing_id
            'channel_id' => $channel_id,//平台id
            'item_id' => $listing['amazon_listing_id'],//平台listing唯一码
            'status' => $action['status']//状态 0 待下架   1 下架成功 2 下架失败
        ];
        //初始化回写
        //(new GoodsTortListingQueue($data))->execute();
        (new CommonQueuer(GoodsTortListingQueue::class))->push($data);

        $seller_id = ChannelUserAccountMap::where(['channel_id' => $channel_id, 'account_id' => $action['account_id']])->value('seller_id');
        //$seller_id = 2293;
        if (empty($seller_id)) {
            $action->save(['callback_status' => 1]);
            return;
        }

        //发送钉钉消息；
        $dingParams = [
            'receive_ids'=> $seller_id,
            'title'=> 'SPU:'.$listing['spu'].'侵权处理',
            'content'=> 'SPU:'.$listing['spu'].'因侵权原因已在erp平台已下架，Amazon平台产品已自动下架成功。',
            'type'=> 13,
            'dingtalk'=> 1,
            'create_id' => 1
        ];
        InternalLetterService::sendLetter($dingParams);
        $action->save(['callback_status' => 1]);
    }


    /**
     * amazon平台SKU集体下架；
     * @param $params
     */
    public function skuInventory($params)
    {
        //$params = [
        //    'tort_id' => $row['tort_id'],//侵权id
        //    'goods_id' => $row['goods_id'],//商品id
        //    'ban_shop_id' => []//不用下架的店铺id
        //    'notice_channel' => []//需要通知的渠道id
        //    'reason' => $row['reason'],//原因
        //    'channel_id' => $row['channel_id'],
        //    'created_id'=>$user_id,
        //    'type'=>1,
        //    'sku_id'=>1
        //];

        //推送需要下架的产品和需要下架的平台，如果不存在则无法执行；
        if (empty($params) || empty($params['sku_id'])) {
            return;
        }

        //参数类型不对，停止；
        if (!is_numeric($params['sku_id']) || !is_numeric($params['goods_id'])) {
            return;
        }

        $channel_id = ChannelAccountConst::channel_amazon;

        $where['sku_id'] = $params['sku_id'];
        //$where['quantity'] = ['>', 0];//去掉数量查询，存在的就传

        $model = new AmazonListing();
        $list = $model->field('id,amazon_listing_id,goods_id,sku_id,account_id,spu,quantity')->where($where)->select();

        //查询侵权下架的数据,没有则终止；
        if (empty($list)) {
            return;
        }

        //处理下架；
        $acctionService = new AmazonActionLogsHelper();

        foreach ($list as $key => $val) {
            $data = [
                'goods_id' => $val['goods_id'],//商品id
                'goods_tort_id' => $params['tort_id'],//侵权下架id
                'listing_id' => $val['id'],//listing_id
                'channel_id' => $channel_id,//平台id
                'item_id' => $val['amazon_listing_id'],//平台listing唯一码
                'sku_id' => $val['sku_id'],
                'status' => 0//状态 0 待下架   1 下架成功 2 下架失败
            ];
            //初始化回写
            //(new GoodsTortListingQueue($data))->execute();
            (new CommonQueuer(GoodsTortListingQueue::class))->push($data);

            //推送进下架队列；
            $actionData = [];
            $actionData[] = [
                'amazon_listing_id' => $val['id'],
                'account_id' => $val['account_id'],
                'new_value' => 0,
                'old_value' => $val['quantity'],
            ];
            //回调类型2；
            $callback_type = 2;
            $callback_param = json_encode(['tort_id' => $params['tort_id']]);
            $acctionService->editListingData(json_encode($actionData), 'quantity', 0,  '', 0, $callback_type, $callback_param);
        }
    }


    /**
     * @title 侵权下架完成后执行的回调函数；
     * @desc 在AmazonActionLogsHelper::updateActionLogStatus，会通过call_user_func_array调用
     * @param $action
     */
    public function backSkuInventory($action)
    {
        if (empty($action['amazon_listing_id'])) {
            $action->save(['callback_status' => 1]);
            return;
        }

        $params = empty($action['callback_param']) ? [] : json_decode($action['callback_param'], true);
        if (empty($params['tort_id'])) {
            $action->save(['callback_status' => 1]);
            return;
        }

        //查询listing;
        $listing = AmazonListing::where(['id' => $action['amazon_listing_id']])->find();
        if (empty($listing)) {
            $action->save(['callback_status' => 1]);
            return;
        }
        //渠道ID；
        $channel_id = ChannelAccountConst::channel_amazon;

        $data = [
            'goods_id' => $listing['goods_id'],//商品id
            'goods_tort_id' => $params['tort_id'],//侵权下架id
            'listing_id' => $listing['id'],//listing_id
            'channel_id' => $channel_id,//平台id
            'item_id' => $listing['amazon_listing_id'],//平台listing唯一码
            'status' => $action['status'],//状态 0 待下架   1 下架成功 2 下架失败
            'sku_id' => $listing['sku_id']
        ];
        //初始化回写
        //(new GoodsTortListingQueue($data))->execute();
        (new CommonQueuer(GoodsTortListingQueue::class))->push($data);
        $action->save(['callback_status' => 1]);
    }


    /**
     *根据asin获取最低价
     *
     */
    public function lowestOfferForAsin($params)
    {

        $apiParams = $this->apiParams($params['account_id']);
        $productInfo = new AmazonProductInfo($apiParams);

        $productInfo->setASINs($params['asin']);
        //价格最低的在售商品的价格信息
        $productInfo->fetchLowestOffer();
        $product = $productInfo->getProduct();

        if($product){
            return json_decode(json_encode($product), true);
        }

        return [];
    }


    /**
     * @param $data
     * @return array
     * @throws Exception
     * 亚马逊获取通知默认地址
     */
    public function noticeApiParams($data)
    {
        $apiParams = $this->apiParams($data['account_id']);

        if(isset($data['sqsQueueUrl']) && $data['sqsQueueUrl']){
             $apiParams['sqsQueueUrl'] = $data['sqsQueueUrl'];
        }

        return $apiParams;
    }


    /**
     * @param $account_id
     * @return bool
     * 指定要接收通知的新目标
     */
    public function registerDestination($data)
    {
        $apiParams = $this->noticeApiParams($data);
        $service = new AmazonSubscribe($apiParams);
        $result = $service->RegisterDestination();

        return $result;
    }


    /**
     * @param $data
     * @return bool
     * @throws Exception
     * 为指定的通知类型和目标创建新订阅
     */
    public function createSubscription($data)
    {
        $apiParams = $this->noticeApiParams($data);
        $service = new AmazonSubscribe($apiParams);

        $data_notice_type = \GuzzleHttp\json_decode($data['notice_type'],true);
        foreach ($data_notice_type as $val){

                //目前只是支持AnyOfferChanged
                if($val['name'] == 'AnyOfferChanged' && in_array($val['name'], $this->amazon_notie_type)){

                    //根据checked为1,则需要指定通知类型和目标创建新订阅
                    if($val['checked']){
                        $service->CreateSubscription($val['name']);
                    }else{
                        //如果checked为0,则需要删除指定类型和目标新订阅
                        $service->DeleteSubscription($val['name']);
                    }
                }
        }

        //可以添加操作日志
        return true;
    }
}