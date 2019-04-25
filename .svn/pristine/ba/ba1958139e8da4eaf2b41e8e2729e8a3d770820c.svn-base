<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-15
 * Time: 上午9:24
 */

namespace app\publish\service;
use app\common\exception\QueueException;
use app\common\model\joom\JoomActionLog;
use app\common\model\joom\JoomProduct;
use app\common\model\joom\JoomVariant;
use app\common\model\joom\JoomProductInfo;
use app\common\service\CommonQueuer;
use service\joom\JoomApi;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;

class JoomItemUpdateService
{
    public static function common($config)
    {
        $service = JoomApi::instance($config);
        return $service->loader('Product');
    }

    /**
     * 修改更新状态
     */
    public static function updateStatus($product_id)
    {
        try{
            if($product_id)
            {
                $where=[
                    'product_id'=>['=',$product_id],
                    'status'=>['<>',0],
                ];
                Db::startTrans();
                try{
                    //如果全部更新了，则修改更新状态为0
                    if(!(JoomActionLog::where($where)->limit(1)->find()))
                    {
                        JoomProduct::where('product_id','=',$product_id)->update(['lock_update'=>0]);
                    }
                    Db::commit();
                }catch (PDOException $exp){
                    Db::rollback();
                    throw new Exception("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (DbException $exp){
                    Db::rollback();
                    throw new Exception("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (Exception $exp){
                    Db::rollback();
                    throw new Exception("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }

            }
        }catch (Exception $exp){
            throw new Exception("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }


    }

    /**
     * 更新商品信息
     * @param $product 日志id
     */
    public static function updateProduct($product)
    {
        try{
            if($product['product']['shop']['access_token'])
            {
                $code = $product['product']['shop']['code'];

                $config['access_token']= $product['product']['shop']['access_token'];

                $service = self::common($config);

                $product_id = $product['product_id'];

                $post=$product['new_data'];

                if(isset($post['main_image']) && $post['main_image'])
                {
                    $post['main_image']= (new JoomPublishHelper())->translateImgToFullPath($post['main_image'], $code);

                }

                if(isset($post['extra_images']) && $post['extra_images'])
                {
                    $post['extra_images']=(new JoomPublishHelper())->translateImgToFullPath(explode('|', $post['original_images']), $code);
                }

                if(isset($post['description']))
                {
                    $post['description'] =str_replace(chr(10),"\n",nl2br($post['description']));;

                }

                $post['id']=$product_id;
                $post['access_token']=$config['access_token'];
                $response = $service->updateProduct($post);
                try{
                    Db::startTrans();
                    if($response['state']==true && $response['code']==0)
                    {
                        $log['status']=1;
                        //JoomProduct::where('product_id','=',$product_id)->update($product['new_data']);
                        (new JoomProduct())->isUpdate(true)->allowField(true)->save($product['new_data'],['product_id'=>$product_id]);
                        (new JoomProductInfo())->isUpdate(true)->allowField(true)->save($product['new_data'],['product_id'=>$product_id]);
                        (new self())->updateStatus($product_id);
                    }else{
                        $log['status']=2;
                        JoomProductInfo::update(['message'=>$response['message']??'未知错误'],['id'=>$product['product']['id']]);
                    }
                    $log['message']=$response['message']??'';
                    $log['run_time']=time();
                    JoomActionLog::where('id','=',$product['id'])->update($log);
                    Db::commit();
                } catch (\Exception $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }
            }
        }catch (\Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }


    }

    /**
     * 更新变体信息
     * @param $product
     */
    public static function updateVariant($product)
    {
        try{
            if($product['product']['shop']['access_token'])
            {
                $config['access_token']= $product['product']['shop']['access_token'];

                $code= $product['product']['shop']['code'];

                $service = self::common($config);

                if($product['variant_id'])
                {
                    $variant = JoomVariant::where('variant_id','=',$product['variant_id'])->find();
                }else{
                    $variant='';
                }

                if($variant)
                {
                    $product['sku']=$variant['sku'];
                    $product_id = $product['product_id'];
                    $post=$product['new_data'];

                    $post['sku']=$product['sku'];

                    if(isset($post['main_image']) && $post['main_image'])
                    {
                        $post['main_image']= (new JoomPublishHelper())->translateImgToFullPath($post['main_image'], $code);

                    }


                    $post['access_token']=$config['access_token'];
                    $response = $service->updateVariant($post);
                    try{
                        Db::startTrans();
                        if($response['state']==true && $response['code']==0)
                        {
                            JoomVariant::where('variant_id','=',$product['variant_id'])
                                ->update($product['new_data']);
                            $log['status']=1;
                            (new self())->updateStatus($product_id);
                        }else{
                            $log['status']=2;
                            JoomProductInfo::update(['message'=>$response['message']??'未知错误'],['id'=>$product['product']['id']]);
                        }
                        $log['message']=$response['message']??'';
                        $log['run_time']=time();
                        JoomActionLog::where('id','=',$product['id'])->update($log);
                        Db::commit();
                    } catch (\Exception $exp){
                        Db::rollback();
                        throw new Exception("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                    }

                }

            }

        }catch (\Exception $exp){
            throw new Exception("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * 商品下架
     * @param $id
     */
    public static function disableProduct($product)
    {
        try{
            if($product['product']['shop']['access_token'])
            {
                $config['access_token']= $product['product']['shop']['access_token'];
                $service = self::common($config);

                $product_id = $product['product_id'];
                $post['id']=$product_id;
                $post['access_token']=$config['access_token'];
                $response = $service->disableProduct($post);
                Db::startTrans();
                try{
                    if($response['state']==true && $response['code']==0)
                    {
                        $log['status']=1;
                        //下架，所有sku的enabled状态都是Disabled
                        JoomVariant::where('product_id','=',$product_id)
                            ->update(['enabled'=>0]);
                        JoomProduct::update(['manual_end_time'=>time()],['product_id'=>$product_id]);
                        (new self())->updateStatus($product_id);
                    }else{
                        $log['status']=2;
                    }
                    try {
                        if (isset($product['product']['end_type']) && $product['product']['end_type'] == 2) {//侵权下架回写
                            $backWriteData = [
                                'goods_id' => $product['goods_id'],
                                'goods_tort_id' => $product['product']['new_data'],
                                'channel_id' => 7,
                                'status' => $log['status'],
                            ];
                            (new CommonQueuer(\app\goods\queue\GoodsTortListingQueue::class))->push($backWriteData);//回写
                        }
                    } catch (\Exception $e) {
                        //不处理
                    }

                    $log['message']=$response['message']??'';
                    $log['run_time']=time();
                    JoomActionLog::where('id','=',$product['id'])->update($log);
                    Db::commit();
                }catch (PDOException $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (DbException $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (Exception $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }
            }

        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * 商品上架
     * @param $product
     */
    public static function enableProduct($product)
    {
        try{
            if($product['product']['shop']['access_token'])
            {
                $config['access_token']= $product['product']['shop']['access_token'];
                $service = self::common($config);
                $product_id=$product['product_id'];
                $post['id']=$product['product_id'];
                $post['access_token']=$config['access_token'];
                $response = $service->enableProduct($post);

                Db::startTrans();
                try{

                    if($response['state']==true && $response['code']==0)
                    {
                        $log['status']=1;
                        //上架，所有sku的enabled状态都是Enabled
                        JoomVariant::where('product_id','=',$product_id)
                            ->update(['enabled'=>1]);
                        (new self())->updateStatus($product_id);
                    }else{
                        $log['status']=2;
                    }
                    $log['message']=$response['message'];
                    $log['run_time']=time();
                    JoomActionLog::where('id','=',$product['id'])->update($log);
                    Db::commit();
                }catch (PDOException $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (DbException $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (Exception $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }

            }

        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }

    /**
     * 上架变体
     * @param $product
     */
    public static function enableVariant($product)
    {
        try{
            if($product['product']['shop']['access_token'])
            {
                $config['access_token']= $product['product']['shop']['access_token'];
                $service = self::common($config);

                if($product['variant_id'])
                {
                    $variant = JoomVariant::where('variant_id','=',$product['variant_id'])->find();
                }else{
                    $variant='';
                }

                if($variant)
                {
                    $product_id = $product['product_id'];
                    $product['sku']=$variant['sku'];
                    $post['sku']=$product['sku'];
                    $post['access_token']=$config['access_token'];
                    $response = $service->enableVariation($post);

                    Db::startTrans();
                    try{

                        if($response['state']==true && $response['code']==0)
                        {
                            $log['status']=1;
                            //下架，所有sku的enabled状态都是Disabled
                            JoomVariant::where('variant_id','=',$product['variant_id'])
                                ->update(['enabled'=>1]);
                            (new self())->updateStatus($product_id);
                        }else{
                            $log['status']=2;
                        }
                        $log['message']=$response['message'];
                        $log['run_time']=time();
                        JoomActionLog::where('id','=',$product['id'])->update($log);
                        Db::commit();
                    }catch (PDOException $exp){
                        Db::rollback();
                        throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                    }catch (DbException $exp){
                        Db::rollback();
                        throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                    }catch (Exception $exp){
                        Db::rollback();
                        throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                    }
                }

            }

        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * 下架变体
     * @param $product
     */
    public static function disableVariant($product)
    {
        try{
            if($product['product']['shop']['access_token'])
            {
                $config['access_token']= $product['product']['shop']['access_token'];
                $service = self::common($config);

                if($product['variant_id'])
                {
                    $variant = JoomVariant::where('variant_id','=',$product['variant_id'])->find();
                }else{
                    $variant='';
                }

                if($variant)
                {

                    $product_id = $product['product_id'];

                    $product['sku']=$variant['sku'];
                    $post['sku']=$product['sku'];
                    $post['access_token']=$config['access_token'];
                    $response = $service->disableVariation($post);


                    Db::startTrans();
                    try{

                        if($response['state']==true && $response['code']==0)
                        {
                            $log['status']=1;
                            //下架，所有sku的enabled状态都是False
                            JoomVariant::where('variant_id','=',$product['variant_id'])
                                ->update(['enabled'=>0]);

                            (new self())->updateStatus($product_id);

                        }else{
                            $log['status']=2;
                        }
                        $log['message']=$response['message'];
                        $log['run_time']=time();
                        JoomActionLog::where('id','=',$product['id'])->update($log);
                        Db::commit();
                    }catch (PDOException $exp){
                        Db::rollback();
                        throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                    }catch (DbException $exp){
                        Db::rollback();
                        throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                    }catch (Exception $exp){
                        Db::rollback();
                        throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                    }
                }
            }

        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * 修改库存
     * @param $product
     */
    public static function updateInventory($product)
    {
        try{
            if($product['product']['shop']['access_token'])
            {
                $config['access_token']= $product['product']['shop']['access_token'];
                $service = self::common($config);

                if($product['variant_id'])
                {
                    $variant = JoomVariant::where('variant_id','=',$product['variant_id'])->find();
                }else{
                    $variant='';
                }

                if($variant)
                {

                    $product_id = $product['product_id'];

                    $product['sku']=$variant['sku'];
                    $post=$product['new_data'];
                    $post['sku']=$product['sku'];
                    $post['access_token']=$config['access_token'];
                    $response = $service->updateInventory($post);
                    Db::startTrans();
                    try{

                        if($response['state']==true && $response['code']==0)
                        {
                            $log['status']=1;
                            JoomVariant::where('variant_id','=',$product['variant_id'])->update($product['new_data']);
                            (new self())->updateStatus($product_id);

                        }else{
                            $log['status']=2;
                        }
                        $log['message']=$response['message'];
                        $log['run_time']=time();
                        JoomActionLog::where('id','=',$product['id'])->update($log);
                        Db::commit();
                    }catch (PDOException $exp){
                        Db::rollback();
                        throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                    }catch (DbException $exp){
                        Db::rollback();
                        throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                    }catch (Exception $exp){
                        Db::rollback();
                        throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                    }

                }
            }

        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * 更新物流
     * @param $product
     */
    public static function updateShipping($product)
    {
        try{
            if($product['product']['shop']['access_token'])
            {
                $config['access_token']= $product['product']['shop']['access_token'];
                $service = self::common($config);

                $post = $product['new_data'];

                $post['access_token']=$config['access_token'];

                $post['id']=$product['product_id'];

                $response = $service->updateShipping($post);

                Db::startTrans();
                try{
                    if($response['state']==true && $response['code']==0)
                    {
                        $log['status']=1;
                    }else{
                        $log['status']=2;
                    }
                    $log['message']=$response['message'];
                    $log['run_time']=time();
                    JoomActionLog::where('id','=',$product['id'])->update($log);
                    Db::commit();
                }catch (PDOException $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (DbException $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (Exception $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }

            }
        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
    /**
     * 批量更新多个物流信息
     * @param $product
     */
    public static function updateQipaMultiShipping($product)
    {
        try{
            if($product['product']['shop']['access_token'])
            {
                $config['access_token']= $product['product']['shop']['access_token'];

                $service = self::common($config);

                $product_id = $product['product_id'];

                $max_shipping = JoomVariant::where('product_id','=',$product_id)->max('shipping');
                $response=[];
                if($product['new_data'] && $product['old_data'])
                {
                    $data = self::splitMultiShippingData($product['new_data'],$product['old_data'],0,$max_shipping);
                    $post = $data;
                    $post['access_token']=$config['access_token'];
                    $post['id']=$product_id;
                    $response = $service->updateMultiShipping($post);
                }else{
                    $log['message']='原始wish expres不存在，无法更新';
                }


                Db::startTrans();
                try{
                    if(!empty($response))
                    {
                        if($response['state']==true && $response['code']==0)
                        {
                            $log['status']=1;
                            JoomProductInfo::where('product_id','=',$product_id)
                                ->update(['all_country_shipping'=>$product['new_data']]);
                            JoomProduct::where('product_id','=',$product_id)->update(['wish_express_countries'=>$data['wish_express_add_countries']]);
                        }else{
                            $log['status']=2;
                        }
                        $log['message']=$response['message'];
                    }
                    $log['run_time']=time();
                    JoomActionLog::where('id','=',$product['id'])->update($log);
                    Db::commit();
                    return $log;
                }catch (PDOException $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (DbException $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (Exception $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }
            }
        }catch (Exception $exp){
            throw new  QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }

    /**
     * 更新多个物流信息
     * @param $product
     */
    public static function updateMultiShipping($product)
    {
        try{
            if($product['product']['shop']['access_token'])
            {
                $config['access_token']= $product['product']['shop']['access_token'];

                $service = self::common($config);

                $product_id = $product['product_id'];
                $response=[];
                if($product['new_data'] && $product['old_data'])
                {
                    $data = self::splitMultiShippingData($product['new_data'],$product['old_data'],1);

                    $post = $data;

                    $post['access_token']=$config['access_token'];

                    $post['id']=$product_id;

                    $response = $service->updateMultiShipping($post);
                }else{
                    $log['message']='原始wish express不存在，无法进行修改';
                }

                Db::startTrans();
                try{
                    if(!empty($response))
                    {
                        if($response['state']==true && $response['code']==0)
                        {
                            $log['status']=1;
                            JoomProductInfo::where('product_id','=',$product_id)
                                ->update(['all_country_shipping'=>$product['new_data']]);
                            JoomProduct::where('product_id','=',$product_id)->update(['wish_express_countries'=>$data['wish_express_add_countries']]);
                        }else{
                            $log['status']=2;
                        }
                        $log['message']=$response['message'];
                    }
                    $log['run_time']=time();
                    JoomActionLog::where('id','=',$product['id'])->update($log);
                    Db::commit();
                    return $log;
                }catch (PDOException $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (DbException $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (Exception $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
                }
            }
        }catch (Exception $exp){
            throw new  QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }

    /**
     * 拼接wish更新商品运费数据
     * @param array $post
     * @return array $return
     */
    public static  function splitMultiShippingData(array $express_data, $old_express_data=[],$special=0,$max_shipping=0)
    {
        try{
            //dump($express_data);dump($old_express_data);
            //批量修改，修改了的发生变化，没有修改的照旧，单个修改，按照提交的数据处理
            $return=[];
            $postCountry=[];
            $use_product_shipping_countries=[]; //使用产品运费
            $shipping_price=[]; //自定义运费
            $disabled_countries=[]; //不能购买国家
            $wish_express_add_countries=[];//wish express国家
            $wish_express_remove_countries=[]; //不使用wish express国家

            foreach ($express_data as $key => $shipping) {
                $data = $shipping['ProductCountryShipping'];
                //运费设置
                if (is_string($data['use_product_shipping'])) {
                    $use_product_shipping_countries[] = $data['country_code'];
                } else {
                    if($data['use_product_shipping']==0) //自定义运费
                    {
                        $shipping_price[$data['country_code']] = $data['shipping_price'];
                    }elseif($data['use_product_shipping']==1){ //使用产品设置的运费
                        $use_product_shipping_countries[] = $data['country_code'];
                    }elseif($data['use_product_shipping']==2){ //原价基础上增加
                        $shipping_price[$data['country_code']] = $data['shipping_price']+$max_shipping;
                    }
                }
                //不能购买国家设置
                if ($data['enabled'] == false || $data['enabled'] == "False") {
                    $disabled_countries[] = $data['country_code'];
                }

                if ($data['wish_express']) {
                    $wish_express_add_countries[] = $data['country_code'];
                } else {
                    $wish_express_remove_countries[] = $data['country_code'];
                }
                array_push($postCountry,$data['country_code']);
            }

            if($special) //单个修改,没有设置的通通disabled
            {
//            $countrys= WishListingHelper::wishExpressData();
//            foreach ($countrys as $country)
//            {
//                $code = $country['country_code'];
//                if(!in_array($code,$postCountry))
//                {
//                    //array_push($use_product_shipping_countries,$code);
//                    array_push($disabled_countries,$code);
//                    //array_push($wish_express_remove_countries,$code);
//                }
//            }

                foreach ($old_express_data as $shipping)
                {
                    $data = $shipping['ProductCountryShipping'];
                    $code = $data['country_code'];
                    if(!in_array($code,$postCountry)) //没有设置的国家
                    {
                        array_push($disabled_countries,$code);
                    }
                }

            }else{ //批量修改，没有修改的使用原有数据
                foreach ($old_express_data as $shipping)
                {
                    $data = $shipping['ProductCountryShipping'];
                    $code = $data['country_code'];
                    if(!in_array($code,$postCountry)) //没有设置的国家
                    {

                        if (is_string($data['use_product_shipping'])) {
                            //$use_product_shipping_countries[] = $data['country_code'];
                            array_push($use_product_shipping_countries,$code);
                        } else {
                            if($data['use_product_shipping']==0) //自定义运费
                            {
                                $shipping_price[$code] = $data['shipping_price'];
                            }elseif($data['use_product_shipping']==1){ //使用产品设置的运费
                                //$use_product_shipping_countries[] = $code;
                                array_push($use_product_shipping_countries,$code);
                            }elseif($data['use_product_shipping']==2){ //原价基础上增加
                                $shipping_price[$data['country_code']] = $data['shipping_price']+$max_shipping;
                            }
                        }
                        //不能购买国家设置
                        if ($data['enabled'] == 'False') {
                            //$disabled_countries[] = $data['country_code'];
                            array_push($disabled_countries,$code);
                        }

                        if ($data['wish_express']=='True') {
                            //$wish_express_add_countries[] = $data['country_code'];
                            array_push($wish_express_add_countries,$code);
                        } elseif($data['wish_express']=='False') {
                            //$wish_express_remove_countries[] = $data['country_code'];
                            array_push($wish_express_remove_countries,$code);
                        }
                    }
                }
            }
            $return=[
                'use_product_shipping_countries'=> implode(',', $use_product_shipping_countries),
                'disabled_countries'=> implode(',', $disabled_countries),
                'wish_express_add_countries'=> implode(',', $wish_express_add_countries),
                'wish_express_remove_countries'=> implode(',', $wish_express_remove_countries),
            ];
            $return = array_merge($return,$shipping_price);
            return $return;
        }catch (Exception $exp){
            throw new  QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }catch (\Throwable $exp){
            throw new  QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }


    }

    /**
     * 移除商品相册
     * @param $product
     */
    public static function removeExtraImages($product)
    {
        if($product['product']['shop']['access_token'])
        {
            $config['access_token']= $product['product']['shop']['access_token'];
            $service = self::common($config);
            $post['access_token']=$config['access_token'];
            $response = $service->removeExtraImages($product['product_id']);

            if($response['state']==true && $response['code']==0)
            {
                $log['status']=1;
            }else{
                $log['status']=2;
            }
            $log['message']=$response['message'];
            $log['run_time']=time();
            JoomActionLog::where('id','=',$product['id'])->update($log);
        }
    }
}