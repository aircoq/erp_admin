<?php
namespace app\publish\task;

use app\common\exception\TaskException;
use app\common\model\Goods;
use app\common\model\GoodsSku;
use app\common\model\GoodsSkuAlias;
use app\publish\service\AliexpressTaskHelper;
use think\Exception;
use app\publish\exception\AliDownProductException;
use app\index\service\AbsTasker;
use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressAccount;
use service\aliexpress\AliexpressApi;
use think\Db;
use app\common\service\Twitter;
/**
 * Created by ZendStudio
 * User: HotZr
 * Date: 17-4-5
 * Time: 下午2:16
 * Doc: 速卖通商户商品基础数据抓取任务
 */
class AliexpressGrabGoods extends AbsTasker
{
    public function getName()
    {
        return "Aliexpress-抓取商品基础数据";
    }
    
    public function getDesc()
    {
        return "Aliexpress-抓取商品基础数据";
    }
    
    public function getCreator()
    {
        return "曾锐";
    }
    
    public function getParamRule()
    {
        return [];
    }
    
    
    public function execute()
    {
        set_time_limit(0);
        try {
            //获取所有已授权并启用账号
            $accountList = Cache::store('AliexpressAccount')->getAccounts();

            //检测设置队列
            $redis = Cache::handler();
            if (!$redis->lLen('queue:ali_down_product')) {
                if (!empty($accountList)) {
                    foreach ($accountList as $item) {
                        if ($item['is_invalid'] && $item['is_authorization']) {
                            $redis->lPush('queue:ali_down_product', json_encode([
                                'account_id' => $item['id'],
                            ]));
                        }
                    }
                }
            }
            $account = $redis->rPop('queue:ali_down_product');

            $account = json_decode($account, true);

            if(!isset($accountList[$account['account_id']])){
                throw new TaskException("ID为{$account['account_id']}的账号不存在");
            }

            $account = $accountList[$account['account_id']];

	        $last_execute_time =  Cache::store('account')->aliexpressLastUpdateTime($account['id']);

	        if(!isset($last_execute_time['last_rsyn_listing_time']))
	        {
		        $can = true;
	        }else{
		        $now = time();
		        $leftTime = ( $now- $last_execute_time['last_rsyn_listing_time'])/60; //间隔多少分钟
                if(isset($account['download_listing']))
                {
                    if($leftTime>=$account['download_listing'])
                    {
                        $can = true;
                    }else{
                        $can = false;
                    }
                }else{
                    $can = true;
                }
	        }
            //组装配置信息
            $config = [
                'id'                =>  $account['id'],
                'code'=>$account['code'],
                'client_id'         =>  $account['client_id'],
                'client_secret'     =>  $account['client_secret'],
                'accessToken'       =>  $account['access_token'],
                'refreshtoken'      =>  $account['refresh_token'],
            ];
	        if($can)
	        {
		        $last_execute_time['last_rsyn_listing_time']=time();

		        Cache::store('account')->aliexpressLastUpdateTime($account['id'], $last_execute_time);
		        $this->downProduct($config);
	        }


        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
        
    }

    public function downProduct($config)
    {
        $helpServer = new AliexpressTaskHelper();
        $productStatusType = $helpServer->getProductStatus();
        $total = 0;
        $postProductServer = AliexpressApi::instance($config)->loader('PostProduct');
        foreach($productStatusType as $status)
        {
            $page = 1;
            $totalPage = 0;
            do{
                //获取产品列表
                $response = $helpServer->getAliProductList($postProductServer,$status,$page);


                $totalPage = $response['totalPage'];
                $total += count($response['data']);

                if(!empty($response['data']))
                {
                    foreach($response['data'] as $product)
                    {
                        //获取产品详细信息
                        $productDetail = $helpServer->getAliProductDetail($postProductServer,$product['productId']);

                        //检测本地是否最新
                        if(Cache::store('AliexpressProductCache')->checkModifiedTime($config['id'],$product['productId'],strtotime($product['gmtModified']))){
                            continue;
                        }
                        $productData = $productInfoData = $productSkuData = [];
                        $goods_id = 0;
                        $goods_spu = '';
                        $productData = [
                            'account_id'=>$config['id'],    //平台账号
                            'product_id'=>$product['productId'],//平台产品ID
                            'product_status_type'=>isset($productDetail['productStatusType'])?$productDetail['productStatusType']:'',//平台产品状态
                            'subject'=>isset($product['subject'])?$product['subject']:'',//平台产品标题
                            'delivery_time'=>isset($productDetail['deliveryTime'])?$productDetail['deliveryTime']:0,//备货期限
                            'category_id'=>isset($productDetail['categoryId'])?$productDetail['categoryId']:0,//分类ID
                            'product_price'=>isset($productDetail['productPrice'])?$productDetail['productPrice']:'',//一口价
                            'product_unit'=>isset($productDetail['productUnit'])?$productDetail['productUnit']:'',//商品单位
                            'package_type'=>(isset($productDetail['packageType'])&&$productDetail['packageType'])?1:0,//是否打包销售
                            'lot_num'=>isset($productDetail['lotNum'])?$productDetail['lotNum']:0,//每包件数
                            'package_length'=>isset($productDetail['packageLength'])?$productDetail['packageLength']:0,//商品包装长度
                            'package_width'=>isset($productDetail['packageWidth'])?$productDetail['packageWidth']:0,//商品包装宽度
                            'package_height'=>isset($productDetail['packageHeight'])?$productDetail['packageHeight']:0,//商品包装高度
                            'gross_weight'=>isset($productDetail['grossWeight'])?$productDetail['grossWeight']:0,//商品毛重
                            'is_pack_sell'=>isset($productDetail['isPackSell'])&&$productDetail['isPackSell']?1:0,//是否自定义计重
                            'base_unit'=>isset($productDetail['baseUnit'])?$productDetail['baseUnit']:'',//几件内不增加邮费
                            'add_unit'=>isset($productDetail['addUnit'])?$productDetail['addUnit']:'',//每次增加的件数
                            'add_weight'=>isset($productDetail['addWeight'])?$productDetail['addWeight']:'',//每次增加的重量
                            'ws_valid_num'=>isset($productDetail['wsValidNum'])?$productDetail['wsValidNum']:0,//商品有效天数
                            'bulk_order'=>isset($productDetail['bulkOrder'])?$productDetail['bulkOrder']:'',//批发最小数量
                            'bulk_discount'=>isset($productDetail['bulkDiscount'])?$productDetail['bulkDiscount']:'',//折扣率
                            'reduce_strategy'=>isset($productDetail['reduceStrategy'])?$productDetail['reduceStrategy']:'',//库存扣减策略
                            'currency_code'=>isset($product['currencyCode'])?$product['currencyCode']:'',//货币单位
                            'gmt_create'=>isset($product['gmtCreate'])?strtotime($product['gmtCreate']):0,//产品发布时间
                            'gmt_modified'=>isset($product['gmtModified'])?strtotime($product['gmtModified']):0,//最后更新时间
                            'ws_offline_date'=>isset($product['wsOfflineDate'])?strtotime($product['wsOfflineDate']):0,//下架时间
                            'ws_display'=>isset($product['wsDisplay'])?$product['wsDisplay']:'',//下架原因
                            'product_min_price'=>isset($product['productMinPrice'])?$product['productMinPrice']:0,//最小价格
                            'product_max_price'=>isset($product['productMaxPrice'])?$product['productMaxPrice']:0,//最大价格
                            'promise_template_id'=>isset($productDetail['promiseTemplateId'])?$productDetail['promiseTemplateId']:'',//服务模板
                            'sizechart_id'=>isset($productDetail['sizechartId'])?$productDetail['sizechartId']:'',//尺码模板
                            'freight_template_id'=>isset($productDetail['freightTemplateId'])?$productDetail['freightTemplateId']:'',//运费模板
                            'owner_member_seq'=>isset($product['ownerMemberSeq'])?$product['ownerMemberSeq']:'',//商品所属人loginId
                            'owner_member_id'=>isset($product['ownerMemberId'])?$product['ownerMemberId']:'',//商品所属人Seq
                            'imageurls'=>isset($productDetail['imageURLs'])?$productDetail['imageURLs']:'',//图片地址
                            'group_id'=>(isset($productDetail['groupIds'])&&!empty($productDetail['groupIds']))?json_encode($productDetail['groupIds']):json_encode([]),
                            'coupon_start_date'=>isset($productDetail['couponStartDate'])?strtotime($productDetail['couponStartDate']):0,//卡券商品开始有效期
                            'coupon_end_date'=>isset($productDetail['couponEndDate'])?strtotime($productDetail['couponEndDate']):0,//卡券商品结束有效期
                            'src'=>isset($product['src']) ? $product['src'] : '',//产品类型
                            'is_image_dynamic'=>isset($productDetail['isImageDynamic'])?$productDetail['isImageDynamic']:'',//是否是动态图产品
                            'status'=>2,
                        ];
                        $productInfoData = [
                            'product_id'=>isset($product['productId'])?$product['productId']:'',
                            'detail'=>isset($productDetail['detail'])?$productDetail['detail']:'',
                            'mobile_detail'=>isset($productDetail['mobileDetail'])?$productDetail['mobileDetail']:'',
                            'product_attr'=>isset($productDetail['aeopAeProductPropertys'])?json_encode($productDetail['aeopAeProductPropertys']):'',
                            'multimedia'=>isset($productDetail['aeopAEMultimedia'])?$productDetail['aeopAEMultimedia']:'',
                        ];

                        if(isset($productDetail['aeopAeProductSKUs']) && !empty($productDetail['aeopAeProductSKUs']))
                        {
                            foreach($productDetail['aeopAeProductSKUs'] as $sku){
                                $sku_code = isset($sku['skuCode'])?$sku['skuCode']:'';
                                $goods_sku_id = self::findSku($sku_code);
                                $local_spu = self::findSpu($goods_sku_id);
                                if(empty($goods_id)){
                                    $goods_id = $local_spu['goods_id'];
                                }
                                if(empty($goods_spu)){
                                    $goods_spu = $local_spu['spu'];
                                }
                                $productSkuData[] = [
                                    'product_id'=>isset($product['productId'])?$product['productId']:'',
                                    'sku_price'=>isset($sku['skuPrice'])?$sku['skuPrice']:'',
                                    'sku_code'=>$sku_code,
                                    'sku_stock'=>isset($sku['skuStock'])?$sku['skuStock']:'',
                                    'ipm_sku_stock'=>isset($sku['ipmSkuStock'])?$sku['ipmSkuStock']:'',
                                    'merchant_sku_id'=>isset($sku['id'])?$sku['id']:'',
                                    'currency_code'=>isset($sku['currencyCode'])?$sku['currencyCode']:'',
                                    'sku_attr'=>isset($sku['aeopSKUProperty'])?json_encode($sku['aeopSKUProperty']):'',
                                    'goods_sku_id'=>$goods_sku_id
                                ];
                            }

                        }
                        //$productData['goods_id'] = $goods_id;
                        //$productData['goods_spu'] = $goods_spu;
                        if(empty($productData)||empty($productInfoData)||empty($productSkuData)){
                            continue;
                        }
                        $helpServer->saveAliProduct($productData,$productInfoData,$productSkuData);
                    }
                }

                $page++;
            }while($page<=$totalPage);
        }
    }

    private static function findSku(string $sku_code)
    {
        if(empty($sku_code))
        {
            return 0;
        }
        //查询sku
        $sku = GoodsSku::where(['sku'=>$sku_code])->field('id')->find();
        if(!empty($sku)){
            return $sku['id'];
        }
        //查询sku别名
        $alias = GoodsSkuAlias::where(['alias' => $sku_code])->field('sku_id')->find();
        if(!empty($alias)){
            return $alias['sku_id'];
        }
        return 0;
    }

    private static function findSpu(int $skuId)
    {
        if(empty($skuId)){
            return false;
        }
        $goods_sku = GoodsSku::get($skuId);
        if(empty($goods_sku)){
            return false;
        }
        $goods = Goods::get($goods_sku['goods_id']);
        if(empty($goods)){
            return false;
        }
        return [
            'goods_id'=>$goods['id'],
            'spu'=>$goods['spu']
            ];
    }
}