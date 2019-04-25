<?php

namespace app\listing\service;

use app\common\cache\Cache;
use app\common\model\amazon\AmazonListing;
use app\common\model\amazon\AmazonListingDetail;
use app\common\service\UniqueQueuer;
use app\listing\queue\AmazonUpdateListingSkuMap;
use app\publish\service\AmazonPublishConfig;
use think\Db;
use think\Exception;
use Waimao\AmazonMws\AmazonConfig;
use Waimao\AmazonMws\AmazonReportConfig;
use Waimao\AmazonMws\AmazonReportRequest;
use Waimao\AmazonMws\AmazonReportRequestList;
use Waimao\AmazonMws\AmazonReport;
/**
 * @node amazon平台在线listing助手
 * Class AmazonListingHelper
 * @package app\listing\service
 */

class AmazonListingHelper
{
    /** @var string reportType类型  */
    private $reportType = '';

    private $listingModel = null;

    private $listingDetailModel = null;

    public function __construct()
    {
        empty($this->listingModel) && $this->listingModel = new AmazonListing();
        empty($this->listingDetailModel) && $this->listingDetailModel = new AmazonListingDetail();
    }

    public function setReportType($reprot_type)
    {
        $this->reportType = $reprot_type;
    }

    public function requestReport(array $apiParams, array $timeLimits)
    {
        if (empty($this->reportType)) {
            throw new Exception('未知的reportType类型');
        }

        $amz = new AmazonReportRequest($apiParams);
        $amz->setMarketplaces($apiParams['marketplaceId']);
        $amz->setReportType($this->reportType);
        $amz->setTimeLimits($timeLimits['StartDate'],$timeLimits['EndDate']);
        $amz->requestReport();
        return $amz->getReportRequestId();
    }

    public function reportRequestList(array $apiParams, $reportRequestId = 0)
    {
        if (empty($this->reportType)) {
            throw new Exception('未知的reportType类型');
        }

        $amz2 = new AmazonReportRequestList($apiParams);
        $amz2->setRequestIds($reportRequestId);
        $amz2->setReportTypes($this->reportType);
        $amz2->fetchRequestList();
        //结果列表，之前只返回第一个reportrequestId；可能会丢失后面的数据，应该取出列表来查看，并返回；
        $resultLists = $amz2->getList();
        if(empty($resultLists)) {
            return [];
        }
        $returnLists = [];
        foreach($resultLists as $val) {
            //这种情况不属于本次请求的类别；
            if ($val['ReportType'] != $this->reportType) {
                continue;
            }
            if($val['ReportProcessingStatus'] == '_DONE_' && !empty($val['GeneratedReportId'])) {
                $returnLists[] = $val['GeneratedReportId'];
            }
        }
        return $returnLists;
    }

    public function saveReport(array $apiParams, $reportId, $reportPath = '')
    {
        $amz3 = new AmazonReport($apiParams, $reportId);
        $amz3->setReportId($reportId);
        $amz3->fetchReport();
        //$code = $amz3->getReportCode();
        return $amz3->saveReport($reportPath);
    }

    public function apiParams(array $accountInfo)
    {
        $apiParams = [
            'merchantId' => trim($accountInfo['merchant_id']),
            'marketplaceId' => AmazonConfig::$marketplaceId[strtoupper($accountInfo['site'])],
            'keyId' => trim(paramNotEmpty($accountInfo, 'developer_access_key_id', $accountInfo['access_key_id'])),
            'secretKey' => trim(paramNotEmpty($accountInfo, 'developer_secret_key', $accountInfo['secret_key'])),
            'authToken' => trim(paramNotEmpty($accountInfo, 'auth_token','')),
            'amazonServiceUrl' => AmazonConfig::$serverUrl[strtoupper($accountInfo['site'])]
        ];

        return $apiParams;
    }

    public function getFormattedTimestamp($timestamp = '')
    {
        $timestamp = (trim($timestamp) != '') ? $timestamp : time();
        return gmdate("Y-m-d\TH:i:s\Z", $timestamp);
    }


    /**
     * @title 读取报告内容
     * @desc 每个amazon的站点可能编码会不一样，如果直接把内容保存到CSV可能会丢失某些编码，
     *      应该直接读取内容，然后解析保存
     * @param $apiParams
     * @param $reportId
     * @return bool
     */
    public function readReport($account, $reportId, $isSaved)
    {
        try {
            $apiParams = $this->apiParams($account);
            $amz3 = new AmazonReport($apiParams, $reportId);
            $amz3->setReportId($reportId);
            $amz3->fetchReport();
            $report = $amz3->getUtf8Report();
            if ($isSaved) {
                $path = ROOT_PATH . 'public/amazon/' . 'listing_' .$reportId.'_'. $account['site']. '-'. date('Y-m-d-H-i-s') . '.xls';
                $amz3->saveReport($path);
            }
            return $report;
        } catch (Exception $e) {
            return false;
        }
    }


    public function matchTitle () {
        $title = ['item-name', 'item-description', 'listing-id', 'seller-sku', 'price', 'quantity', 'open-date', 'image-url', 'item-is-marketplace', 'product-id-type', 'zshop-shipping-fee', 'item-note', 'item-condition', 'zshop-category1', 'zshop-browse-path', 'zshop-storefront-feature', 'asin1', 'asin2', 'asin3', 'will-ship-internationally', 'expedited-shipping', 'zshop-boldface', 'product-id', 'bid-for-featured-placement', 'add-delete', 'pending-quantity', 'fulfillment-channel', 'Business Price', 'Quantity Price Type', 'Quantity Lower Bound 1', 'Quantity Price 1', 'Quantity Lower Bound 2', 'Quantity Price 2', 'Quantity Lower Bound 3', 'Quantity Price 3', 'Quantity Lower Bound 4', 'Quantity Price 4', 'Quantity Lower Bound 5', 'Quantity Price 5', 'merchant-shipping-group'];
    }


    /** @var array 标准的title */
    public $standardTitle = ['item-name', 'item-description', 'listing-id', 'seller-sku', 'price', 'quantity', 'open-date', 'image-url', 'item-is-marketplace', 'product-id-type', 'zshop-shipping-fee', 'item-note', 'item-condition', 'zshop-category1', 'zshop-browse-path', 'zshop-storefront-feature', 'asin1', 'asin2', 'asin3', 'will-ship-internationally', 'expedited-shipping', 'zshop-boldface', 'product-id', 'bid-for-featured-placement', 'add-delete', 'pending-quantity', 'fulfillment-channel', 'Business Price', 'Quantity Price Type', 'Quantity Lower Bound 1', 'Quantity Price 1', 'Quantity Lower Bound 2', 'Quantity Price 2', 'Quantity Lower Bound 3', 'Quantity Price 3', 'Quantity Lower Bound 4', 'Quantity Price 4', 'Quantity Lower Bound 5', 'Quantity Price 5', 'merchant-shipping-group'];

    public function getSiteTitle($site)
    {
        if (empty($this->siteMapConfig[$site]) || $this->siteMapConfig[$site] == '*') {
            return array_combine($this->standardTitle, $this->standardTitle);
        }
        return $this->siteMapConfig[$site];
    }

    /**
     * @var array
     */
    public $siteMapConfig = [
        'US' => '*',
        'UK' => '*',
        'DE' => '*',
        'CA' => '*',
        'FR' => '*',
        'IT' => '*',
        'JP'=>[
            '商品名' => 'item-name',
            '出品ID' => 'listing-id',
            '出品者SKU' => 'seller-sku',
            '価格' => 'price',
            '数量' => 'quantity',
            '出品日' => 'open-date',
            '商品IDタイプ' => 'product-id-type',
            'コンディション説明' => 'item-note',
            'コンディション' => 'item-condition',
            '国外へ配送可' => 'zshop-category1',
            '迅速な配送' => 'zshop-browse-path',
            '商品ID' => 'product-id',
            '在庫数' => 'pending-quantity',
            'フルフィルメント・チャンネル' => 'fulfillment-channel',
            'merchant-shipping-group' => 'merchant-shipping-group',
        ],
        'ES' => '*',
        'AU' => '*',
        'MX' => '*',
        'IN' => '*',
    ];


    public function chunkReport($report, $site)
    {
        $test = false;
        $row = 0;
        $report = explode("\n", $report);
        $title = [];
        $siteTitle = $this->getSiteTitle($site);
        $data = [];
        foreach ($report as $key=>$val) {
            if ($key == 0) {
                $title = explode("\t", $val);
            } else {
                if (empty($val)) {
                    continue;
                }
                $tmpVal = explode("\t", $val);
                $tmp = ['item-name' => '', 'item-description' => '', 'listing-id' => '', 'seller-sku' => '', 'price' => '', 'quantity' => '', 'open-date' => '', 'image-url' => '', 'item-is-marketplace' => '', 'product-id-type' => '', 'zshop-shipping-fee' => '', 'item-note' => '', 'item-condition' => '', 'zshop-category1' => '', 'zshop-browse-path' => '', 'zshop-storefront-feature' => '', 'asin1' => '', 'asin2' => '', 'asin3' => '', 'will-ship-internationally' => '', 'expedited-shipping' => '', 'zshop-boldface' => '', 'product-id' => '', 'bid-for-featured-placement' => '', 'add-delete' => '', 'pending-quantity' => '', 'fulfillment-channel' => '', 'Business Price' => '', 'Quantity Price Type' => '', 'Quantity Lower Bound 1' => '', 'Quantity Price 1' => '', 'Quantity Lower Bound 2' => '', 'Quantity Price 2' => '', 'Quantity Lower Bound 3' => '', 'Quantity Price 3' => '', 'Quantity Lower Bound 4' => '', 'Quantity Price 4' => '', 'Quantity Lower Bound 5' => '', 'Quantity Price 5' => '', 'merchant-shipping-group' => ''];

                foreach ($title as $k=>$v) {
                    if (!empty($siteTitle[$v])) {
                        $tmp[$siteTitle[$v]] = $tmpVal[$k] ?? '';
                    }
                }
                array_push($data, $tmp);
                //测试的时候，只拿前面100行；
                if ($test && ++$row >= 100) {
                    break;
                }
            }
        }
        return $data;
    }


    public function syncListing($params)
    {
        $this->setReportType(AmazonReportConfig::REPORT_TYPE_LISTINGS_DATA);

        $accountId = $params['account_id'];
        $reportRequestId = $params['reportRequestId'];

        $accountCache = Cache::store('AmazonAccount');
        $accountInfo = $accountCache->getTableRecord($accountId);

        $apiParams = $this->apiParams($accountInfo);
        $reportIdArr = $this->reportRequestList($apiParams, $reportRequestId);

        if ($reportIdArr) {
            foreach($reportIdArr as $reportId) {
                $this->updateListingByReportId($accountInfo, $reportId, false);
            }
        }
    }


    /**
     * 读取报告内容并且更新listing
     * @param $account int 帐号
     * @param $reportId string 报告ID
     * @param bool $isSaved 是否保存报告csv
     * @return bool
     */
    public function updateListingByReportId($account, $reportId, $isSaved = false)
    {
        $report = $this->readReport($account, $reportId, $isSaved);

        if (empty($report)) {
            return false;
        }
        $report = $this->chunkReport($report, $account['site']);

        if (empty($report)) {
            return false;
        }

        //初始化UPC集合
        $this->initUpcDump();
        //$start = time();
        //echo '报告下载完成时间', $start, "\r\n";
        //处理报告;
        foreach ($report as $val) {
            try {
                $this->insertListingByLine($val, $account);
            } catch (Exception $e) {
                throw new Exception($e->getMessage(). '|'. $e->getFile(). '|'. $e->getLine());
            }
        }
        //$end = time();
        //echo '报告保存完成时间', $end, "\r\n";
        //echo '报告处理消耗时间', $end - $start, "\r\n";

        //推送UPC；
        if (!empty($this->listingUpcs)) {
            $this->pushListingUpcs($this->listingUpcs);
        }
        return true;
    }


    /** @var array UPC集合 */
    private $listingUpcs = [];


    /**
     * 初始化UPC集合
     */
    private function initUpcDump()
    {
        $this->listingUpcs = [];
    }


    /**
     * 推送UPC到生成UPC的地方排重
     * @param $upcs
     */
    public function pushListingUpcs($upcs)
    {
        $upcs = array_merge(array_unique(array_filter($upcs)));
        $push = [];
        foreach ($upcs as $key=>$upc) {
            $k = floor($key/100);
            $push[$k][] = $upc;
        }

        try {
            $url = 'http://172.20.0.180:7001/api/readd';
            $upcs = ['upcs' => []];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            foreach ($push as $key=>$val) {
                $upcs['upcs'] = $val;

                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($upcs));
                curl_exec ($ch);
            }
            curl_close($ch);
        } catch (Exception $e) {
            throw new Exception('推送UPC排重失败：'. $e->getMessage());
        }
    }

    private function insertListingByLine($data, $account)
    {
        if (!$data || empty($data['listing-id'])) {
            return true;
        }
        $params = [];
        $params['amazon_listing_id'] = (string)$data['listing-id'];
        $params['item_name'] = $data['item-name'] ?? '';

        $params['seller_sku'] = empty($data['seller-sku'])? '' : trim(addslashes($data['seller-sku']));
        $params['price'] = empty($data['price'])? 0 : number_format($data['price'], 2, '.', '');
        if (!empty($data['open-date'])) {
            $params['open_date'] = $this->setDateTimeFormat($data['open-date']);
        }
        if (!empty($data['image-url'])) {
            $params['image_url'] = (string)$data['image-url'];
        }

        if (!empty($data['item-is-marketplace'])) {
            $params['item_is_marketplace'] = (string)$data['item-is-marketplace'];
        }
        if (!empty(empty($data['product-id-type']))) {
            $params['product_id_type'] = (int)$data['product-id-type'];
        }
        if (!empty($data['zshop-shipping-fee'])) {
            $params['zshop_shipping_fee'] = (float)$data['zshop-shipping-fee'];
        }

        if (!empty($data['item-note'])) {
            $params['item_note'] = $data['item-note'] ?? '';
        }
        if (!empty($data['zshop-category1'])) {
            $params['zshop_category1'] = (string)$data['zshop-category1'];
        }
        if (!empty($data['zshop-browse-path'])) {
            $params['zshop_browse_path'] = (string)$data['zshop-browse-path'];
        }
        if (!empty($data['zshop-storefront-feature'])) {
            $params['zshop_storefront_feature'] = (string)$data['zshop-storefront-feature'];
        }

        if (isset($data['asin1']) && $data['asin1']) {
            $params['asin1'] = (string)$data['asin1'];
        }
        if (isset($data['asin2']) && !empty($data['asin2'])) {
            $params['asin2'] = (string)$data['asin2'];
        }
        if (isset($data['asin3']) && !empty($data['asin3'])) {
            $params['asin3'] = (string)$data['asin3'];
        }
        if (!empty($data['product-id'])) {
            $params['product_id'] = (string)$data['product-id'];
        }

        if(isset($data['product-id']) && $data['product-id'] && $data['product-id-type'] == 1){
            $params['asin1'] = $data['product-id'];
        }

        //d-fr法国账号，没有asin数据，但product_id可能就是asin数据
        if ($account['site'] == 'FR') {
            $params['asin1'] = $params['product_id'];
        }

        if (!empty($data['will-ship-internationally'])) {
            $params['will_ship_internationally'] = (int)$data['will-ship-internationally'];
        }
        if (!empty($data['expedited-shipping'])) {
            $params['expedited_shipping'] = (int)$data['expedited-shipping'];
        }
        if (!empty($data['zshop-boldface'])) {
            $params['zshop_boldface'] = (string)$data['zshop-boldface'];
        }
        if (!empty($data['bid-for-featured-placement'])) {
            $params['bid_for_featured_placement'] = (string)$data['bid-for-featured-placement'];
        }
        if (!empty($data['add-delete'])) {
            $params['add_delete'] = (string)$data['add-delete'];
        }
        if (!empty($data['pending-quantity'])) {
            $params['pending_quantity'] = (int)$data['pending-quantity'];
        }

        //判断配送状态
        $params['fulfillment_type'] = AmazonReportConfig::FULFILLMENT_STATUS_MERCHANT;
        if(isset($data['fulfillment-channel'])){
            $params['fulfillment_channel'] = trim(substr($data['fulfillment-channel'], 0, 7));
            if($params['fulfillment_channel'] == 'AMAZON_'){
                $params['fulfillment_type'] = AmazonReportConfig::FULFILLMENT_STATUS_AMAZON;	//amazon配送状态(2)
            }
        }

        //只针对卖家配送状态记录，更新销售状态（通过库存是否为0）和写入库存量
        //如果是amazon配送（FBA），因为库存量获取不到，暂时默认为上架状态。
        //在此接口不更新amazon配送的销售状态！另一接口获取FBA库存量和确认销售状态。
        $params['seller_status'] = AmazonReportConfig::SELLER_STATUS_ONLINE;
        if ($params['fulfillment_type'] == AmazonReportConfig::FULFILLMENT_STATUS_MERCHANT){
            if (isset($data['quantity']) && ((int)$data['quantity'] > 0)) {
                $params['quantity'] = $data['quantity'];
                $params['seller_status'] = AmazonReportConfig::SELLER_STATUS_ONLINE;
            }else{
                $params['seller_status'] = AmazonReportConfig::SELLER_STATUS_OFFLINE;
            }
        }

        $params['description'] = $params_desc['description'] = $data['item-description'] ?? '';

        $params['account_id'] = $account['id'];
        $params['site'] = $account['site'];
        $params['currency'] = isset(AmazonPublishConfig::$baseCurrencyCode[$account['site']]) ? AmazonPublishConfig::$baseCurrencyCode[$account['site']] : 'DEFAULT';

        $this->saveListingToDb($params, $account);
    }

    private function saveListingToDb($params, $account)
    {
        //3拿取listingInfo并排重；
        $where = [
            'account_id' => $account['id'],
            'seller_sku' => $params['seller_sku'],
        ];
        $listingInfo = $this->getListingInfo($where, $params['amazon_listing_id']);

        //记录用来更新skumap
        $listing_id = 0;
        Db::startTrans();
        try {
            if (!isset($listingInfo['id'])) {//不存在
                $params['create_time'] = time();
                $params['modify_time'] = time();

                //detail模板；
                $data_detail = ['description' => $params['description']];
                unset($params['description']);

                $listing_id = $this->listingModel->insertGetId($params);

                if ($listing_id > 0) {
                    $data_detail['listing_id'] = $listing_id;
                    $this->listingDetailModel->insert($data_detail);
                }
            } else {
                $listing_id = $listingInfo['id'];

                $params['publish_detail_id'] = $listingInfo['publish_detail_id'];
                $params['modify_time'] = time();

                //detail模板；
                $data_detail = ['description' => $params['description']];
                unset($params['description']);

                $this->listingModel->update($params, ['id' => $listing_id]);
                $this->listingDetailModel->update($data_detail, ['listing_id'=>$listing_id]);
            }
            Db::commit();

            //当新生成UPC时，收集UPC，再推送到排重报务器
            if (empty($listingInfo['id']) && empty($listingInfo['publish_detail_id']) && !empty($listing_id)) {
                if (is_numeric($params['product_id']) && strlen($params['product_id']) == 12) {
                    $this->listingUpcs[] = $params['product_id'];
                }

                //没有绑定SKU；
                if (empty($listingInfo['sku_quantity'])) {
                    (new UniqueQueuer(AmazonUpdateListingSkuMap::class))->push($listing_id);
                }
            }
        } catch (\Exception $exception) {
            Db::rollback();
            throw new Exception('MSG:'. $exception->getMessage(). '['. json_encode($params). ']'. '|FILE:'. $exception->getFile(). '|Line:'. $exception->getLine());
        }
    }


    public function getListingInfo($where, $listingId)
    {
        if (empty($where['account_id']) || empty($where['seller_sku'])) {
            return [];
        }

        $listings = $this->listingModel->where($where)->field('id,amazon_listing_id,sku_quantity,publish_detail_id')->select();
        if (empty($listings)) {
            return [];
        }
        if (count($listings) == 1) {
            return $listings[0]->toArray();
        } else {
            $delIds = [];
            $publish_detail_id = 0;
            $listingInfo = [];
            foreach ($listings as $key=>$val) {
                if ($val['publish_detail_id'] > $publish_detail_id) {
                    $publish_detail_id = $val['publish_detail_id'];
                }
                if ($val['amazon_listing_id'] == $listingId) {
                    $listingInfo = $val->toArray();
                } else {
                    $delIds[] = $val['id'];
                }
            }
            if (empty($listingInfo)) {
                $listingInfo = $listings[0]->toArray();
                $listingInfo['publish_detail_id'] = max($listingInfo['publish_detail_id'], $publish_detail_id);
                array_shift($delIds);
            }
            if (!empty($delIds)) {
                $this->listingModel->where(['id' => ['in', $delIds]])->delete();
                $this->listingDetailModel->where(['listing_id' => ['in', $delIds]])->delete();
            }

            return $listingInfo;
        }

    }

    protected function convertToUtf8($string = '')
    {
        $encode = mb_detect_encoding($string, array("ISO-8859-1","ASCII","UTF-8","GB2312","GBK","BIG5"));
        if ($encode){
            //不是utf-8,则进行转换
            if(!mb_check_encoding($string,'UTF-8')){
                $string = iconv($encode,"UTF-8",$string);
            }
        }else{
            $string = iconv("UTF-8","UTF-8//IGNORE",$string);	//识别不了的编码就截断输出
        }
        return $string;
    }

    /**
     * @desc 时间格式转换（不改变时间，不计算时区，拼接格式化日期）
     * @param $datetime
     * @param $type 类型：jp的特殊日期处理
     * @return string
     */
    protected function setDateTimeFormat($datetime,$type = '')
    {
        if (empty($datetime)) return 0;
        try {

            $mydate  = '';
            $mytime  = '';
            $myyear  = '';
            $mymonth = '';
            $myday   = '';
            $date_arr = explode(" ",$datetime);
            if ($date_arr){
                if (isset($date_arr[0]) && $date_arr[0]) $mydate = $date_arr[0];
                if (isset($date_arr[1]) && $date_arr[1]) $mytime = $date_arr[1];
            }
            if ($mydate){
                $mydate_arr = explode("/",$mydate);
                if (count($mydate_arr) > 1){
                    if ($type == 'jp'){
                        $myyear  = $mydate_arr[0];
                        $mymonth = $mydate_arr[1];
                        $myday   = $mydate_arr[2];
                    }else{
                        $myyear  = $mydate_arr[2];
                        $mymonth = $mydate_arr[1];
                        $myday   = $mydate_arr[0];
                    }
                    $mydate  = $myyear.'-'.$mymonth.'-'.$myday;
                }
            }
            $formatDate = $mydate.' '.$mytime;
            $time = (int)strtotime($formatDate);
            ////超过最大值，直接返回0；
            //$time = $time > 2147483647 ? 0 : $time;
            return $time;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(). '|'. $e->getFile(). '|'. $e->getLine());
        }
    }


    /**
     * 返回此天新刊登成功的数量；
     * @param string $day
     * @param array $accountIds
     * @return array
     */
    public function getPublishListingTotal($day, $accountIds = []) : array
    {
        $where['seller_status'] = ['<>', AmazonReportConfig::SELLER_STATUS_CANCEL];
        $where['create_time'] = ['BETWEEN', [strtotime($day), strtotime($day) + 86399]];
        if ($accountIds) {
            $where['account_id'] = ['IN', (array)$accountIds];
        }
        $model = new AmazonListing();
        $data = $model->where($where)->group('account_id')->field('COUNT(id) total,account_id')->select();
        $result = [];
        foreach ($data as $val) {
            $result[$val['account_id']] = $val['total'];
        }
        return $result;
    }


    /**
     * 返回某时间段新刊登成功的数量；
     * @param string $day
     * @param array $accountIds
     * @return array
     */
    public function getPublishListingTotalByTime(array $dayArr, $accountIds = []) : array
    {
        $where['seller_status'] = ['<>', AmazonReportConfig::SELLER_STATUS_CANCEL];
        $where['create_time'] = ['BETWEEN', [strtotime($dayArr[0]), strtotime($dayArr[1])]];
        if ($accountIds) {
            $where['account_id'] = ['IN', (array)$accountIds];
        }
        $model = new AmazonListing();
        $data = $model->where($where)->group('account_id')->field('COUNT(id) total,account_id')->select();
        $result = [];
        foreach ($data as $val) {
            $result[$val['account_id']] = $val['total'];
        }
        return $result;
    }


    /**
     * 返回帐号所有成功的listing数量；
     * @param string $day
     * @param array $accountIds
     * @return array
     */
    public function getPublishListingAllTotal($accountIds = []) : array
    {
        $where['seller_status'] = ['<>', AmazonReportConfig::SELLER_STATUS_CANCEL];
        $where['modify_time'] = ['>=', strtotime('-2 days')];
        if ($accountIds) {
            $where['account_id'] = ['IN', (array)$accountIds];
        }
        $model = new AmazonListing();
        $data = $model->where($where)->group('account_id')->field('COUNT(id) total,account_id')->select();
        $result = [];
        foreach ($data as $val) {
            $result[$val['account_id']] = $val['total'];
        }
        return $result;
    }

}
