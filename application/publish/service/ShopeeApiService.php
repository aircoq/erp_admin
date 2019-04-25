<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-5-25
 * Time: 下午5:41
 */

namespace app\publish\service;

use app\common\cache\Cache;
use app\common\model\shopee\ShopeeActionLog;
use app\common\model\shopee\ShopeeProduct;
use app\common\model\shopee\ShopeeProductInfo;
use app\common\model\shopee\ShopeeVariant;
use app\publish\helper\shopee\ShopeeHelper;
use app\system\controller\Time;
use service\shopee\ShopeeApi;
use think\Db;
use think\Exception;
use think\exception\PDOException;

class ShopeeApiService
{
    private static $service;
    public static function factory($config){
        if(is_null(self::$service)){
            self::$service = ShopeeApi::instance($config)->handler('Item');
        }
        return self::$service;
    }

    /***
     * 修改商品重量
     * @param $product
     * @param $account
     */
    public static function updateItemWeight($product,$account)
    {

    }
    public static function online($product)
    {
        
    }
    public static function rsyncProduct($product,$account)
    {
        $itemId = (int)$product['product_id'];
        $response = ShopeeApiService::GetItemDetail($account,$itemId);
        if(isset($response['item']) && $response['item']){
            $item = ShopeeApiService::managerItemDetailData($response['item']);
            if(isset($item['product']) && $item['product'] && isset($item['info']) && isset($item['variants'])){
                ShopeeApiService::saveItemData($account['id'],$item);
            }
        }
        self::writeResponseData($product['id'],$response);
    }

    /**
     * 修改变体库存
     * @param $product
     * @param $account
     * @throws PDOException
     */
    public static function updateVariationStock($product,$account){
        $service = self::factory($account);
        $params=[
            'item_id'=>(int)$product['product_id'],
            'variation_id'=>(int)$product['variant_id'],
            'stock'=>(int)$product['new_data']['stock'],
        ];
        $response = $service->update_variation_stock($params);
        if(!isset($response['error'])){
            self::updateLockProduct($product['product_id']);
        }
        self::writeResponseData($product['id'],$response);
    }

    /**
     * 修改变体价格
     * @param $product
     * @param $account
     * @throws PDOException
     */
    public static function updateVariationPrice($product,$account)
    {
        $service = self::factory($account);
        $params=[
            'item_id'=>(int)$product['product_id'],
            'variation_id'=>(int)$product['variant_id'],
            'price'=>(int)$product['new_data']['price'],
        ];
        $response = $service->update_variation_price($params);
        if(!isset($response['error'])){
            self::updateLockProduct($product['product_id']);
        }
        self::writeResponseData($product['id'],$response);
    }

    public static function updateLogistics($product,$account){

    }
    public static function updateDiscount($product,$account)
    {
        
    }
    public static function enableVariant($product,$account){

    }
    public static function disableVariant($product,$account){

    }
    public static function updateInventory($product,$account){

    }
    public static function disableProduct($product,$account){

    }
    public static function enableProduct($product,$account){

    }
    public static function updateVariant($product,$account){

    }

    public static function updateStock($product,$account)
    {
        $service = self::factory($account);
        $params=[
            'item_id'=>(int)$product['product_id'],
            'stock'=>(int)$product['new_data']['stock'],
        ];
        $response = $service->update_stock($params);
        if(!isset($response['error'])){
            self::updateLockProduct($product['product_id']);
        }
        self::writeResponseData($product['id'],$response);
    }
    public static function updatePrice($product,$account)
    {
        $service = self::factory($account);
        $params=[
            'item_id'=>(int)$product['product_id'],
            'price'=>(int)$product['new_data']['price'],
        ];
        $response = $service->update_price($params);
        if(!isset($response['error'])){
            self::updateLockProduct($product['product_id']);
        }
        self::writeResponseData($product['id'],$response);
    }
    public static function deleteItem($product,$account){

    }
    public static function deleteVariation($product,$account){

    }

    public static function updateItem($product,$account){
        $service = self::factory($account);
        $data = $product['new_data'];
        $itemId = $product['product_id'];

        $productData = ShopeeProduct::where('item_id',$itemId)->with(['info','variants'])->find();
        $productData= $productData->toArray();
        $variants = $productData['variants'];
        $productInfo = $productData['info'];

        $row = array_merge($productData,$productInfo);

        $params['item_id']=$itemId;
        if(isset($data['category_id']) && $data['category_id']){
            $params['category_id']=$data['category_id'];
        }else{
            $params['category_id']=$row['category_id'];
        }

        if(isset($data['name']) && $data['name']){
            $params['name']=$data['name'];
        }else{
            $params['name']=$row['name'];
        }

        if(isset($data['description']) && $data['description']){
            $params['description']=$data['description'];
        }else{
            $params['description']=$row['description'];
        }

        if(isset($data['description']) && $data['description']){
            $params['description']=$data['description'];
        }else{
            $params['description']=$row['description'];
        }

        if(isset($data['variations']) && $data['variations']){
            $variations = $data['variations'];
            foreach ($variants as $variant){
                $variation['name']=$variant['name'];
                $variation['stock']=(int)$variant['stock'];
                $variation['price']=(float)$variant['price'];
                $variation['variation_sku']=$variant['variation_sku'];
                $variation['variation_id']=$variant['variation_id'];
                $variations[]=$variation;
            }
            $params['variations']=$variations;
        }
        if(isset($data['images']) && $data['images']){
            $imgs = json_decode($data['images'],true);
            $images_string = (new WishPublishHelper())->translateImgToFullPath($imgs,'rondaful');
            if($images_string){
                $imgs  = explode("|",$images_string);
                foreach ($imgs as $img){
                    $image['url']=$img;
                    $images[]=$image;
                }
            }
            $params['images']=$images;
        }

        if(isset($data['attributes']) && $data['attributes']){
            $attrs = json_decode($product['attributes'],true);
            if($attrs){
                foreach ($attrs as $attr){
                    $attribute['attributes_id']=$attr['attribute_id'];
                    $attribute['value']=$attr['attribute_value'];
                    $attributes[]=$attribute;
                }
            }
            $params['attributes']=$attributes;
        }

        if(isset($data['logistics']) && $data['logistics']){
            $logistics = json_decode($data['logistics'],true);
            $params['logistics']=$logistics;
        }
        if(isset($data['wholesales']) && $data['wholesales']){
            $wholesales = json_decode($data['wholesales'],true);
            if($wholesales){
                foreach ($wholesales as &$wholesale){
                    $wholesale['min']=(int)$wholesale['min'];
                    $wholesale['max']=(int)$wholesale['max'];
                    $wholesale['unit_price']=(float)$wholesale['unit_price'];
                }
            }
            $params['wholesales']=$wholesales;
        }

        if(isset($data['weight']) && $data['weight']){
            $params['weight']=(float)$data['weight'];
        }else{
            $params['weight']=(float)$row['weight'];
        }
        $response = $service->update_price($params);
        if(!isset($response['error'])){
            self::updateLockProduct($product['product_id']);
        }
        self::writeResponseData($product['id'],$response);
    }
    public static function updateLockProduct($item_id)
    {
        ShopeeProduct::where('item_id',$item_id)->setField('lock_update',1);
    }
    public static function writeResponseData($id,$response)
    {
        $msg = isset($response['msg'])?$response['msg']:'';
        if(isset($response['error'])){
            $status=-1;
        }else{
            $status=1;
        }
        $data['message']=$msg;
        $data['status']=$status;
        $data['run_time']=time();

        Db::startTrans();
        try{
            ShopeeActionLog::where('id',$id)->update($data);
            Db::commit();
        }catch (PDOException $exp){
            Db::rollback();
            throw $exp;
        }
    }
    public static function postProduct($product,$variants,$config){
        $itemId=0;
        if(empty($product['item_id'])){
            $itemId = self::addItem($product,$variants,$config);
        }else{
            $itemId = self::addVariation($product,$variants,$config);
        }
        return $itemId;
    }
    public static function addVariation($product,$variants,$config){
        $params['item_id']=$product['item_id'];
        if($variants){
            foreach ($variants as $variant){
                $variation['name']=$variant['name'];
                $variation['stock']=$variant['stock'];
                $variation['price']=$variant['price'];
                $variation['variation_sku']=$variant['variation_sku'];
                $variations[]=$variation;
            }
        }
        $params['variations']=json_encode($variants);
        $response = ShopeeApi::instance($config)->loader('Item')->add_variations($params);

        $item_id=0;
        $variations=[];
        $msg= isset($response['msg'])?$response['msg']:$response['error'];
        if(isset($response['item_id']) && $response['item_id']){
            $item_id = $response['item_id'];
            $data=[
                'item_id'=>$item_id,
                'publish_status'=>1,
                'publish_message'=>$msg,
            ];
        }else{
            $data=[
                'publish_status'=>-1,
                'publish_message'=>$msg,
                'message'=>$msg,
                'run_time'=>time(),
            ];
        }
        if(isset($response['variations']) && $response['variations']){
            $variants=  $response['variations'];
        }
        $productModel = new ShopeeProduct();
        Db::startTrans();
        try{
            $productModel->allowField(true)->save($data,['id'=>$product['id']]);
            if($variants){
                foreach ($variations as $variation){
                    $update['variation_id']=$variation['variation_id'];
                    $update['item_id']=$item_id;
                    ShopeeVariant::where(['pid'=>$product['id'],'variation_sku'=>$variation['variation_sku']])
                        ->update($update);
                }
            }
            Db::commit();
        }catch (PDOException $exp){
            Db::rollback();
            throw $exp;
        }finally{
            return $item_id;
        }
    }

    private static function formatData($product,$variants){
        $params['category_id']=(int)$product['category_id'];
        $params['name']=$product['name'];
        $params['description']=$product['description'];

        if(empty($variants)){
            $params['price']=(float)$product['price'];
            $params['stock']=(int)$product['stock'];
        }

        $params['item_sku']=$product['item_sku'];

        $params['weight']=(float)$product['weight'];

        if($product['package_length']){
            $params['package_length']=(int)$product['package_length'];
        }
        if($product['package_width']){
            $params['package_width']=(int)$product['package_width'];
        }
        if($product['package_height']){
            $params['package_height']=(int)$product['package_height'];
        }
        if($product['days_to_ship']>=7 && $product['days_to_ship']<=30){
            $params['days_to_ship']=(int)$product['days_to_ship'];
        }

        $variations=$attributes=$images=$logistics=$wholesales=[];

        if($variants){
            foreach ($variants as $variant){
                $variation['name']=$variant['name'];
                $variation['stock']=(int)$variant['stock'];
                $variation['price']=(float)$variant['price'];
                $variation['variation_sku']=$variant['variation_sku'];
                $variations[]=$variation;
            }
        }
        $imgs = json_decode($product['images'],true);
        $images_string = (new WishPublishHelper())->translateImgToFullPath($imgs,'rondaful');
        if($images_string){
            $imgs  = explode("|",$images_string);
            foreach ($imgs as $img){
                $image['url']=$img;
                $images[]=$image;
            }
        }

        $attrs = json_decode($product['attributes'],true);
        if($attrs){
            foreach ($attrs as $attr){
                if (!isset($attr['attribute_value'])) {
                    continue;
                }
                $attribute['attributes_id']=$attr['attribute_id'];
                $attribute['value']=$attr['attribute_value'];
                $attributes[]=$attribute;
            }
        }
        $logistics = json_decode($product['logistics'],true);
        $wholesales = json_decode($product['wholesales'],true);

        if($wholesales){
            foreach ($wholesales as &$wholesale){
                $wholesale['min']=(int)$wholesale['min'];
                $wholesale['max']=(int)$wholesale['max'];
                $wholesale['unit_price']=(float)$wholesale['unit_price'];
            }
        }

        $params['variations']=($variations);
        $params['images']=($images);
        $params['attributes']=($attributes);
        $params['logistics']=$logistics;
        $params['wholesales']=($wholesales);

        return $params;
    }

    public static function addItem($product,$variants,$config)
    {
        $params = self::formatData($product,$variants);
        Cache::handler()->hset('shopee:debug:additem:send',$product['id'], json_encode($params));
//        $response = ShopeeApi::instance($config)->loader('Item')->add($params);
        $response = ShopeeApi::instance($config)->loader('Item')->update_stock($params);
        Cache::handler()->hset('shopee:debug:additem:response',$product['id'], json_encode($response));
        $helper = new ShopeeHelper();
        $message = $helper->checkResponse($response, 'item');
        if ($message !== true) {
//            ShopeeProductInfo::update(['message'=>$message], ['id'=>$product['id']]);
            ShopeeProduct::update(['publish_status'=>-1, 'publish_message'=>$message], ['id'=>$product['id']]);
            return false;
        }
        ShopeeProduct::update(['publish_status'=>1], ['id'=>$product['id']]);
        $res = $helper->updateProductWithItem($response, $product['id']);
        if ($res !== true) {
            $message = 'error:上传成功，但是更新本地信息的时候发生错误，错误信息：'.$res;
            ShopeeProduct::update(['publish_message'=>$message], ['id'=>$product['id']]);
        }
    }
    public static function GetDiscountDetail($config,$params){
        $response = ShopeeApi::instance($config)->loader('Discount')->detail($params);
        return $response;
    }
    public static function getDiscountList($config,$params)
    {
        $response = ShopeeApi::instance($config)->loader('Discount')->get($params);
        return $response;
    }
    /**
     * Use this call to get all supported Logistic Channel
     * @param $config
     * @return mixed
     * @throws Exception
     */
    public static function GetLogistics($config){
        $response = ShopeeApi::instance($config)->loader('Logistics')->getLogistics([]);
        return $response;
    }
    public static function GetItemDetail($config,$ItemId)
    {
        $params['item_id'] = (int)$ItemId;
        $response = ShopeeApi::instance($config)->loader('Item')->getItemDetail($params);
        return $response;
    }

    /**
     * 保存item数据
     * @param $account_id
     * @param $product
     * @throws \think\Exception
     */
    public static function saveItemData($accountId,$item){
        $cacheDriver = Cache::store('ShopeeAccount');

        $product = $item['product'];
        $product['account_id']=$accountId;
        $info = $item['info'];
        $variants = $item['variants'];
        $id=0;
        $oldVariants = 0;
        $variantsCache=$productCache=[];
        if(isset($product['item_id']) && $product['item_id']){
            $itemId = $product['item_id'];
            $productCache = $cacheDriver->getProductCache($accountId,$itemId);
            $model = new ShopeeProduct();
            if(!$productCache && $productData = $model->field('id')->where('item_id',$itemId)->find()){
                $id = $productData['id'];
            }elseif($productCache){
                $id = $productCache['id'];
            }
            if($productCache){
                $variantsCache = $productCache['variants'];
            }
            if ($id) {
                $oldVariants = ShopeeVariant::where('pid',$id)->column('vid','variation_id');
            }


            Db::startTrans();
            try{
                if($id){
                    $product['id']=$id;
                    $model->allowField(true)->save($product,['id'=>$product['id']]);
                }else{
                    $model->allowField(true)->save($product);
                    $id = $model->getData('id');
                }
                foreach ($variants as &$variant){
                    $variant['pid'] = $id;
                    $variant['publish_status'] = 3;
                    if ($oldVariants) {
                        isset($oldVariants[$variant['variation_id']]) && $variant['vid'] = $oldVariants[$variant['variation_id']];
                    } else if($variantsCache){
                        foreach ($variantsCache as $cache){
                            if($variant['variation_id']==$cache['variation_id']){
                                $variant['vid'] = $cache['vid'];
                            }
                        }
                    }
                }
                $resultSets = (new ShopeeVariant())->allowField(true)->saveAll($variants);

                $info['id']=$id;

                $infoModel = new ShopeeProductInfo();

                if($infoData = $infoModel->where('id',$id)->find()){
                    $infoModel->allowField(true)->save($info,['id'=>$info['id']]);
                }else{
                    $infoModel->allowField(true)->save($info);
                }

                Db::commit();

//                foreach ($resultSets as $set){
//                    $variantsCache[]=['vid'=>$set['vid'],'variation_id'=>$set['variation_id']];
//                }
//                $caches=[
//                    'id'=>$id,
//                    'item_id'=>$itemId,
//                    'variants'=>$variantsCache,
//                ];
//                $cacheDriver->setProductCache($accountId,$itemId,$caches);
            }catch (PDOException $exp){
                Db::rollback();
                throw new Exception($exp->getMessage());
            }
        }
    }
    /**
     * 整合item详情数据
     * @param $item
     * @return array
     */
    public static function managerItemDetailData($item){


        $product = $info = $variants = [];
        //商品信息
        $product['item_id']=$item['item_id'];
        $product['name']=$item['name'];
        $product['category_id']=$item['category_id'];
        $product['original_price']=$item['original_price'];
        $product['price']=$item['price'];
        $product['images']=json_encode($item['images']);
        $product['days_to_ship']=$item['days_to_ship'];
        $product['stock']=$item['stock'];
        $product['cmt_count']=$item['cmt_count'];
        $product['likes']=$item['likes'];
        $product['views']=$item['views'];
        $product['rating_star']=$item['rating_star'];
        $product['sales']=$item['sales'];
        $product['weight']=$item['weight'];
        $product['package_height']=isset($item['package_height'])?$item['package_height']:0;
        $product['package_width']=isset($item['package_width'])?$item['package_width']:0;
        $product['package_length']=isset($item['package_length'])?$item['package_length']:0;
        $product['item_sku']=$item['item_sku'];
        $product['has_variation']=$item['has_variation'];
        $product['status']=$item['status'];
        $product['currency']=$item['currency'];
        $product['create_time']=$item['create_time'];
        $product['update_time']=$item['update_time'];
        $product['publish_status']=1;

        //商品其他信息
        $info['description']=$item['description'];
        $info['attributes']=isset($item['attributes'])?json_encode($item['attributes']):[];
        $info['logistics']=isset($item['logistics'])?json_encode($item['logistics']):[];
        $info['wholesales']=isset($item['wholesales'])?json_encode($item['wholesales']):[];
        $info['item_id']=$item['item_id'];


        //变体数据
        if(isset($item['has_variation']) && $item['has_variation']){
            $variants = isset($item['variations'])?$item['variations']:[];
        }

        $return =[
            'product'=>$product,
            'info'=>$info,
            'variants'=>$variants,
        ];
        return $return ;
    }
}