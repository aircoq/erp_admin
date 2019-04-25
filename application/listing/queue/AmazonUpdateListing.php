<?php
namespace app\listing\queue;

use service\shipping\operation\El;
use think\Db;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\common\service\ChannelAccountConst;
use think\Exception;
use app\publish\service\AmazonPublishConfig;
use Waimao\AmazonMws\AmazonReportConfig;
use app\common\model\amazon\AmazonListing as AmazonListingModel;
use app\common\model\amazon\AmazonListingDetail;;


class AmazonUpdateListing extends  SwooleQueueJob
{
    private $accountInfo;
    private $redisAmazonAccount;
    private $redisAmazonListing;
    private $listingModel;
    private $listingDetailModel;
    private $path;

    //listing抓取配置
    public static $siteMapConfig = [
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
    ];

    public function getName():string
    {
	    return 'amazon读取listing文件并插入到数据表(队列)';
    }
    public function getDesc(): string {
        return 'amazon读取listing文件并插入到数据表(队列)';
    }

    public function getAuthor(): string {
        return 'fuyifa';
    }

    public function init()
    {
        $this->redisAmazonAccount = Cache::store('AmazonAccount');
        $this->redisAmazonListing = Cache::store('AmazonListing');
        $this->listingModel = new AmazonListingModel;
        $this->listingModel->query('set names utf8mb4');
        $this->listingDetailModel = new AmazonListingDetail;
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        set_time_limit(0);
        try {
            $job = $this->params;
            if (!$job) {
                throw new Exception('job 为空');
            }
            $this->path = $path = $job['path'];
            $this->accountInfo = $this->redisAmazonAccount->getTableRecord($job['account_id']);
            if (!file_exists($path)) {
                throw new Exception($path . ' File not existed!');
            }

            $this->handleReportContent($path);

            //Excel里面的产品只有在售的，在售的产品都已更新了modify_time,因此未更新此时间的产品则标记为删除；
            $data = ['seller_status' => AmazonReportConfig::SELLER_STATUS_CANCEL];
            //把当前帐号修改时间在一天之前的，改为删除；
            $where['account_id'] = $job['account_id'];
            $where['modify_time'] = ['<', time() - 60 * 60 * 24];

            // $this->listingModel->update($data, $where);
            $this->redisAmazonAccount->setListingLastUpdateLog($job['account_id'], date('Y-m-d H:i:s'), 'Update Listing Success!');
            @unlink($path);
        } catch (Exception $exp) {
            throw new Exception($exp->getMessage() . $exp->getFile() . $exp->getLine());
        }
    }

    /**
     * 处理报告内容；
     * @param $path
     */
    protected function handleReportContent($path)
    {
        $current_time_zone = date_default_timezone_get();
        date_default_timezone_set('Etc/GMT');

        $handle = fopen($path, 'r');

        $key = 0;
        while ($line = fgets($handle)) {
            $rows = explode("\t", $line);
            //第一行是标题；
            if ($key == 0) {
                foreach ($rows as $k => $value) {

                    $value = trim($value);
                    if(strpos($path, 'JP') !== false){
                        $siteMap = self::$siteMapConfig['JP'];
                        if(isset($siteMap[$value]) && $siteMap[$value]){
                            $value = $siteMap[$value];
                        }
                    }

                    ${'index_' . $k} = $value;
                }

            //后续才是内容
            } else {
                if (empty($rows)) {
                    continue;
                }

                foreach ($rows as $k => $value) {
                    $value = trim($value);
                    $data[${'index_' . $k}] = $result[$key][${'index_' . $k}] = $value;
                }

                $this->insertListingByLine($data);
                unset($data);
            }
            $key++;
        }

        fclose($handle);

        date_default_timezone_set($current_time_zone);
    }

    private function insertListingByLine($data)
    {
        if (!$data || empty($data['listing-id'])) {
            return true;
        }
        $params = [];
        $params['amazon_listing_id'] = (string)$data['listing-id'];
        $params['item_name'] = empty($data['item-name']) ? '' : $this->convertToUtf8($data['item-name']);
        //标题超长则截断
        /*if(strlen($params['item_name']) > 250){
                $params['item_name'] = mb_strcut($params['item_name'], 0, 250, 'utf-8');
        }*/

        $params['seller_sku'] = empty($data['seller-sku'])? '' : trim(addslashes((string)$this->convertToUtf8($data['seller-sku'])));
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
            $params['item_note'] = trim($this->convertToUtf8($data['item-note']));
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
        if ($this->accountInfo['site'] == 'FR') {
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

        $params['description'] = $params_desc['description'] = isset($data['item-description']) ? $this->convertToUtf8($data['item-description']) : '';

        $params['account_id'] = $this->accountInfo['id'];
        $params['site'] = $this->accountInfo['site'];
        $params['currency'] = isset(AmazonPublishConfig::$baseCurrencyCode[$this->accountInfo['site']]) ? AmazonPublishConfig::$baseCurrencyCode[$this->accountInfo['site']] : 'DEFAULT';

        $this->saveListingToDb($params);
    }

    private function saveListingToDb($params)
    {

        //$listingInfo = $this->redisAmazonListing->listingUpdateTime($this->accountInfo['id'], $params['amazon_listing_id']);

        $where = [
            'account_id'=>$this->accountInfo['id'],
            'amazon_listing_id'=>$params['amazon_listing_id'],
        ];
        $listingInfo = (new AmazonListingModel)->field('id')->where($where)->find();

        if($listingInfo){
            $listingInfo = $listingInfo->toArray();
        }

        //如果是从本地插入进去的，可能会在刊登成功后就把数据插入到表里，而不是人这里进去的；
        $update = false;
        if (empty($listingInfo)) {
            $where = [
                'asin1' => $params['asin1'],
                'seller_type' => 1
            ];
            $listing = $this->listingModel->where($where)->field('id')->find();
            if (!empty($listing)) {
                $update = true;
                $listingInfo = ['id' => $listing['id']];
            }
        }

        Db::startTrans();
        try {
            //insert to db
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
                    $listingUpdateTime = [
                        'id'=>$listing_id,
                        'last_update_time'=>$params['create_time']
                    ];
                     //$this->redisAmazonListing->listingUpdateTime($this->accountInfo['id'], $params['amazon_listing_id'], $listingUpdateTime);

                    // (new UniqueQueuer(AmazonUpdateListingSkuMap::class))->push($listing_id);
                }
            } else {
                $listing_id = $listingInfo['id'];

                if(isset($params['asin1']) && $params['asin1']){

                    $field = 'id';
                    $where = [
                        'account_id'=> $this->accountInfo['id'],
                        'amazon_listing_id'=>'',
                        'asin1' => $params['asin1'],
                        'seller_sku' => $params['seller_sku'],
                        'seller_type' => 1
                    ];

                    $listingInfo = (new AmazonListingModel)->field($field)->where($where)->find();

                    if($listingInfo){

                        $this->listingModel->where(['id' => $listing_id])->delete();

                        $listingInfo = $listingInfo->toArray();
                        $listing_id = $listingInfo['id'];

                    }
                }

                $params['modify_time'] = time();

                //detail模板；
                $data_detail = ['description' => $params['description']];
                unset($params['description']);

                $result = $this->listingModel->update($params, ['id' => $listing_id]);

                if ($result) {
                    $this->listingDetailModel->update($data_detail, ['listing_id'=>$listing_id]);

                    $listingUpdateTime = [
                        'id'=>$listing_id,
                        'last_update_time'=>$params['modify_time']
                    ];

                     //$this->redisAmazonListing->listingUpdateTime($this->accountInfo['id'], $params['amazon_listing_id'], $listingUpdateTime);

                    if ($update) {
                        // (new UniqueQueuer(AmazonUpdateListingSkuMap::class))->push($listing_id);
                    }
                }
            }
            Db::commit();
            //$this->redisAmazonListing->listingUpdateTime($this->accountInfo['id'], $params['amazon_listing_id'], $listingUpdateTime);
        } catch (Exception $exception) {
            Db::rollback();
            throw new Exception('MSG:'. $exception->getMessage(). '; FILE:'. $exception->getFile(). 'Line:'. $exception->getLine());
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
        if (empty($datetime)) return false;
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
        return (int)strtotime($formatDate);
    }
}
