<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-4-21
 * Time: 上午10:40
 */

namespace app\publish\service;


use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\common\model\pandao\PandaoAccount;
use app\common\model\pandao\PandaoActionLog;
use app\common\model\pandao\PandaoProduct;
use app\common\model\pandao\PandaoProductInfo;
use app\common\model\pandao\PandaoVariant;
use app\common\service\CommonQueuer;
use app\common\service\UniqueQueuer;
use app\publish\queue\PandaoProductInsertDb;
use service\pandao\PandaoApi;
use service\pandao\operation\Account;
use think\Exception;
use think\Db;
use app\goods\service\GoodsImage;
use think\exception\PDOException;

class PandaoApiService
{
    /**
     * 同步产品数据
     * @param $data
     * @throws QueueException
     */
    public static function rsyncProduct($data)
    {
        try{

            if($data['product']['account']['access_token'])
            {
                $config['access_token']= $data['product']['account']['access_token'];
                $product_id = $data['product_id'];
                $post['id']=$product_id;
                $response = PandaoApi::instance($config)->loader('Product')->product($post);

                if(isset($response['code']) && $response['code']==0)
                {
                    $log['status']=1;
                    $log['message']=$response['message'];
                }else{
                    $log['status']=2;
                    $log['message']=$response['message'];
                }
                $log['run_time']=time();
                $product = [];
                if(isset($response['data']['Product'])){
                    $product = self::managerReturnProduct($response['data']['Product']);
                }
                Db::startTrans();
                try{
                    PandaoActionLog::where('id',$data['id'])->update($log);
                    if($product){
                        (new PandaoProduct())->allowField(true)->save($product,['product_id'=>$product['product_id']]);
                        (new PandaoProductInfo())->allowField(true)->save($product,['product_id'=>$product['product_id']]);
                        $variants = $product['variants'];
                        if($variants){
                            foreach ($variants as $variant){
                                (new PandaoVariant())->allowField(true)->save($variant,['variant_id'=>$variant['variant_id']]);
                            }
                        }
                    }
                    Db::commit();
                }catch (PDOException $exp){
                    Db::rollback();
                    throw new Exception($exp->getMessage());
                }
            }
        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
    public static function managerReturnProduct($item){

        $product=[];
        $productId = $item['id'];
        $product['product_id']=$productId;
        $product['date_uploaded']=$item['date_uploaded'];
        $product['last_updated']=$item['last_updated'];
        $product['enabled']=$item['enabled'];
        $product['parent_sku']=$item['parent_sku'];
        $product['name']=$item['name'];
        $product['brand']=$item['brand'];
        $product['description']=$item['description'];
        $product['landing_page_url']=$item['landing_page_url']==NULL?'':$item['landing_page_url'];
        $product['upc']=$item['upc'];
        $product['main_image']=$item['main_image'];
        $product['original_image_url']=$item['original_image_url'];
        $product['name']=$item['name'];
        $product['is_promoted']=$item['is_promoted']=='True'?1:0;
        $product['extra_images']=implode('|',$item['extra_images']);
        $variants=[];
        if(isset($item['variants']) && $item['variants']){
            $rows = $item['variants'];
            foreach ($rows as $line){
                $row = $line['Variant'];
                $variantId = $row['id'];
                unset($row['id']);
                $variant = $row;
                $variant['variant_id']=$variantId;
                $variant['product_id']=$productId;
                $variants[]=$variant;
            }
        }
        $product['variants']=$variants;
        return $product;
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
                    if(!(PandaoActionLog::where($where)->limit(1)->find()))
                    {
                        PandaoProduct::where('product_id','=',$product_id)->update(['lock_update'=>0]);
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

            if($product['product']['account']['access_token'])
            {
                $code = $product['product']['account']['code'];

                $config['access_token']= $product['product']['account']['access_token'];

                $product_id = $product['product_id'];

                $post=$product['new_data'];

                if(isset($post['main_image']) && $post['main_image'])
                {
                    $post['main_image']= self::translateImgToFullPath($post['main_image'], $code);
                }

                if(isset($post['extra_images']) && $post['extra_images'])
                {
                    $post['extra_images']=self::translateImgToFullPath(explode('|', $post['original_images']), $code);
                }

                if(isset($post['description']) && $post['description'])
                {
                    $post['description'] =str_replace(chr(10),"\n",nl2br($post['description']));
                }

                $post['id']=$product_id;
                $post['access_token']=$config['access_token'];
                $response = PandaoApi::instance($config)->loader('Product')->update($post);

                Db::startTrans();
                try{
                    if(isset($response['code']) && $response['code']==0)
                    {
                        $log['status']=1;
                        $log['message']=$response['message'];
                        //WishWaitUploadProduct::where('product_id','=',$product_id)->update($product['new_data']);
                        (new PandaoProduct())->isUpdate(true)->allowField(true)->save($product['new_data'],['product_id'=>$product_id]);
                        (new PandaoProductInfo())->isUpdate(true)->allowField(true)->save($product['new_data'],['product_id'=>$product_id]);
                        (new self())->updateStatus($product_id);
                    }else{
                        $log['status']=2;
                        $log['message']=$response['error_description'];
                    }

                    $log['run_time']=time();
                    PandaoActionLog::where('id','=',$product['id'])->update($log);
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
     * 更新变体信息
     * @param $product
     */
    public static function updateVariant($product)
    {
        try{
            if($product['product']['account']['access_token'])
            {
                $config['access_token']= $product['product']['account']['access_token'];

                $code= $product['product']['account']['code'];


                if($product['variant_id'])
                {
                    $variant = PandaoVariant::where('variant_id','=',$product['variant_id'])->find();
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
                        $post['main_image']= self::translateImgToFullPath($post['main_image'], $code);

                    }

                    $post['access_token']=$config['access_token'];
                    $response = PandaoApi::instance($config)->loader('Variant')->update($post);
                    Db::startTrans();
                    try{
                        if(isset($response['code']) && $response['code']==0)
                        {
                            PandaoVariant::where('variant_id','=',$product['variant_id'])
                                ->update($product['new_data']);
                            $log['status']=1;
                            $log['message']=$response['message'];
                            (new self())->updateStatus($product_id);
                        }else{
                            $log['status']=2;
                            $log['error_description']=$response['error_description'];
                        }

                        $log['run_time']=time();
                        PandaoActionLog::where('id','=',$product['id'])->update($log);
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
     * 商品下架
     * @param $id
     */
    public static function disableProduct($product)
    {
        try{
            if($product['product']['account']['access_token'])
            {
                $config['access_token']= $product['product']['account']['access_token'];
                $product_id = $product['product_id'];
                $post['id']=$product_id;
                $post['access_token']=$config['access_token'];
                $response = PandaoApi::instance($config)->loader('Product')->disable($post);

                Db::startTrans();
                try{
                    if(isset($response['code']) && $response['code']==0)
                    {
                        $log['status']=1;
                        $log['message']=$response['message'];
                        //下架，所有sku的enabled状态都是Disabled
                        PandaoVariant::where('product_id','=',$product_id)
                            ->update(['enabled'=>0]);
                        PandaoProduct::update(['manual_end_time'=>time()],['product_id'=>$product_id]);
                        (new self())->updateStatus($product_id);
                    }else{
                        $log['status']=2;
                        $log['message']=$response['error_description'];
                    }
                    if ($product['product']['end_type'] == 2) {//侵权下架回写
                        $backWriteData = [
                            'goods_id' => $product['goods_id'],
                            'goods_tort_id' => $product['product']['new_data'],
                            'channel_id' => 8,
                            'status' => $log['status'],
                        ];
                        (new CommonQueuer(\app\goods\queue\GoodsTortListingQueue::class))->push($backWriteData);//回写
                    }
                    $log['run_time']=time();
                    PandaoActionLog::where('id','=',$product['id'])->update($log);
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
            if($product['product']['account']['access_token'])
            {
                $config['access_token']= $product['product']['account']['access_token'];

                $product_id=$product['product_id'];
                $post['id']=$product['product_id'];
                $post['access_token']=$config['access_token'];
                $response = PandaoApi::instance($config)->loader('Product')->enable($post);

                Db::startTrans();
                try{

                    if(isset($response['code']) && $response['code']==0)
                    {
                        $log['status']=1;
                        $log['message']=$response['message'];
                        //上架，所有sku的enabled状态都是Enabled
                        PandaoVariant::where('product_id','=',$product_id)
                            ->update(['enabled'=>1]);
                        (new self())->updateStatus($product_id);
                    }else{
                        $log['status']=2;
                        $log['error_description']=$response['error_description'];
                    }
                    $log['run_time']=time();
                    PandaoActionLog::where('id','=',$product['id'])->update($log);
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
            if($product['product']['account']['access_token'])
            {
                $config['access_token']= $product['product']['account']['access_token'];


                if($product['variant_id'])
                {
                    $variant = PandaoVariant::where('variant_id','=',$product['variant_id'])->find();
                }else{
                    $variant='';
                }

                if($variant)
                {
                    $product_id = $product['product_id'];
                    $product['sku']=$variant['sku'];
                    $post['sku']=$product['sku'];
                    $post['access_token']=$config['access_token'];
                    $response = PandaoApi::instance($config)->loader('Variant')->enable($post);

                    Db::startTrans();
                    try{

                        if(isset($response['code']) && $response['code']==0)
                        {
                            $log['status']=1;
                            $log['message']=$response['message'];
                            //下架，所有sku的enabled状态都是Disabled
                            PandaoVariant::where('variant_id','=',$product['variant_id'])
                                ->update(['enabled'=>1]);
                            (new self())->updateStatus($product_id);
                        }else{
                            $log['status']=2;
                            $log['message']=$response['error_description'];
                        }

                        $log['run_time']=time();
                        PandaoActionLog::where('id','=',$product['id'])->update($log);
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
            if($product['product']['account']['access_token'])
            {
                $config['access_token']= $product['product']['account']['access_token'];


                if($product['variant_id'])
                {
                    $variant = PandaoVariant::where('variant_id','=',$product['variant_id'])->find();
                }else{
                    $variant='';
                }

                if($variant)
                {

                    $product_id = $product['product_id'];
                    $product['sku']=$variant['sku'];
                    $post['sku']=$product['sku'];
                    $post['access_token']=$config['access_token'];
                    $response = PandaoApi::instance($config)->loader('Variant')->diable($post);

                    Db::startTrans();
                    try{

                        if(isset($response['code']) && $response['code']==0)
                        {
                            $log['status']=1;
                            $log['message']=$response['message'];
                            //下架，所有sku的enabled状态都是False
                            PandaoVariant::where('variant_id','=',$product['variant_id'])
                                ->update(['enabled'=>0]);
                            (new self())->updateStatus($product_id);
                        }else{
                            $log['status']=2;
                            $log['message']=$response['error_description'];
                        }
                        $log['run_time']=time();
                        PandaoActionLog::where('id','=',$product['id'])->update($log);
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

    public static function refreshToken($account_id){
        $account = Cache::store('PandaoAccountCache')->getAccountById($account_id);
        if($account){
            $response = (new Account)->refreshToken($account);
            if(isset($response['access_token']) && isset($response['refresh_token']))
            {
                $updateData['is_authorization'] = 1;
                $updateData['access_token'] = $response['access_token'];
                $updateData['refresh_token'] = $response['refresh_token'];
                $updateData['expiry_time'] =time()+$response['expires_in'];
                $model = new PandaoAccount();
                if($model->allowField(true)->save($updateData,['id'=>$account_id]))
                {
                    foreach($updateData as $key=>$val) {
                        Cache::store('PandaoAccountCache')->updateTableRecord($account_id, $key, $val);
                    }
                }
            }else{
                throw new Exception("刷新token失败！");
            }
        }
    }
    public static function downloadFile($account_id,$job_id,$url){
        try{
            $dir= ROOT_PATH.'public'.DS.'pandao'.DS;
            $filename = $account_id.'|'.$job_id.'.csv';
            if (!empty($dir) && !is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            ob_start();
            curl_exec($ch);
            $return_content = ob_get_contents();
            ob_end_clean();
            curl_setopt($ch, CURLOPT_SSLVERSION, 3);
            $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($return_code == 200) {
                $fp = fopen($dir . $filename, "a"); //将文件绑定到流
                if ($fp) {
                    fwrite($fp, $return_content); //写入文件
                    fclose($fp);
                }
            } else {
                throw new Exception($return_code);
            }
            curl_close($ch);
            return true;
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }
    }
    /***
     * @param $config
     * @param array $params
     * @return mixed
     * @throws \think\Exception
     */
    public static function downloadJob($config,$params=[])
    {
        $response = PandaoApi::instance($config)->handler('Product')->downloadJob($params);
        return $response;
    }

    /***
     * @param $config
     * @param array $params
     * @return mixed
     * @throws \think\Exception
     */
    public static function downloadJobStatus($config,$params=[])
    {
        $response = PandaoApi::instance($config)->handler('Product')->downloadJobStatus($params);
        return $response;
    }
    /***
     * @param $config
     * @param array $params
     * @return mixed
     * @throws \think\Exception
     */
    public static function retrieveProduct($config,$params)
    {
        $response = PandaoApi::instance($config)->handler('Product')->product($params);
        return $response;
    }
    /**
     * 上传变体数据
     * @param array $varints
     * @return string
     */
    public static function addVarints($product,$variants,$config)
    {
        set_time_limit(0);
        try{
            $product_id='';
            foreach ($variants as $key => $variant)
            {

                if($key==0 && empty($product['product_id']))
                {
                    $product_id=self::addProduct($product, $variant, $config);
                }else{
                    $variant['parent_sku'] = $product['parent_sku'];
                    self::addVariant($variant, $config,$product['account']['code']);
                }
            }
            return $product_id;
        }catch(Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }
    /**
     * 刊登商品信息
     * @param array $product
     * @return string
     */
    public static function addProduct($product,$variant,$config)
    {
        set_time_limit(0);
        try{
            $product_id='';
            $product['vid'] = $variant['vid'];
            $product['sku'] = $variant['sku'];
            $product['inventory'] = $variant['inventory'];
            $product['price'] = $variant['price'];
            $product['shipping'] = $variant['shipping'];
            $product['shipping_time'] = $variant['shipping_time'];
            $product['color'] = $variant['color'];
            $product['size'] = $variant['size'];
            $product['msrp'] = $variant['msrp'];

            $data =[];

            $data['name']=$product['name'];

            $data['description'] =str_replace(chr(10),"\n",nl2br($product['description']));;

            $data['parent_sku']=$product['parent_sku'];
            $data['tags']=$product['tags'];
            $data['sku']=$product['sku'];
            $data['inventory']=$product['inventory'];
            $data['price']=$product['price'];
            $data['shipping']=$product['shipping'];
            $data['shipping_time']=$product['shipping_time'];
            $data['color']=$product['color'];
            $data['size']=$product['size'];

            $main_image=$product['main_image'];

            $data['main_image']= self::translateImgToFullPath($main_image, $product['account']['code']);

            $extra_images = explode('|', $product['original_images']);

            array_shift($extra_images);

            $data['extra_images']=self::translateImgToFullPath($extra_images, $product['account']['code']);

            $data['msrp']=$product['msrp'];


            if($product['brand'])
            {
                $data['brand']=$product['brand'];
            }
            $regex = '/^(http|https|ftp):\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\’:+!]*([^<>\”])*$/';
            preg_match($regex, $product['landing_page_url'],$match);
            if ($match)
            {
                $data['landing_page_url']=$product['landing_page_url'];
            }

            if($product['upc'])
            {
                $data['upc']=$product['upc'];
            }

            $response = PandaoApi::instance($config)->loader('Product')->add($data);

            if(!empty($response)){
                $updatep=$update=[];
                if(isset($response['data']['Product']))
                {
                    $product_id = $response['data']['Product']['id']; //产品id
                    $review_status =$response['data']['Product']['enabled']; //审核状态
                    $number_saves = isset($response['data']['Product']['number_saves'])?$response['data']['Product']['number_saves']:0; //收藏量
                    $number_sold = isset($response['data']['Product']['number_sold'])?$response['data']['Product']['number_sold']:0; //销售量
                    $last_updated= strtotime($response['data']['Product']['last_updated']);
                    if(isset($response['data']['Product']['is_promoted']))
                    {
                        $is_promoted = $response['data']['Product']['is_promoted'];
                    }else{
                        $is_promoted='False';
                    }

                    $updatep= [
                        'is_promoted' =>$is_promoted=='True'?1:0,
                        'number_saves'=>$number_saves,
                        'product_id'=>$product_id,
                        'review_status'=>$review_status=='True'?1:0,
                        'number_sold'=>$number_sold,
                        'last_updated'=>$last_updated,
                        'publish_status'=>1,
                    ];

                    if(isset($response['data']['Product']['variants']))
                    {
                        $variants = $response['data']['Product']['variants'];
                        foreach ($variants as $V)
                        {
                            if($V['Variant']['sku']==$variant['sku'])
                            {
                                $update['variant_id'] = $V['Variant']['id'];//变体Id
                                $update['product_id'] = isset($V['Variant']['product_id'])?$V['Variant']['product_id']:$product_id;//
                                $update['enabled'] = $V['Variant']['enabled']=='True'?1:0;
                                break;
                            }
                        }
                    }
                }
                if(isset($response['code']) && $response['code']==0)
                {
                    $update['status']=1;
                    $updatep['publish_message']=$update['message'] = '';
                    $updatep['publish_status']=1;
                }else{
                    if(isset($response['message'])){
                        $update['message'] = $response['message'];
                        $updatep['publish_message'] = $response['message'];
                    }elseif(isset($response['error_description'])){
                        $update['message'] = $response['error_description'];
                        $updatep['publish_message'] = $response['error_description'];
                    }
                    $update['status']=2;//刊登失败
                    $updatep['publish_status']=-1;
                }

                if(isset($response['code']) && is_int($response['code']))
                {
                    $update['code'] = $response['code'];
                }else{
                    $update['code'] = 0;
                }

                $update['run_time'] = date('Y-m-d H:i:s',time());
                $where['id']=['=',$product['id']];
                Db::startTrans();
                try{
                    if($updatep)
                    {
                        PandaoProduct::where($where)->update($updatep);
                    }
                    if($product_id)
                    {
                        PandaoProductInfo::where($where)->update(['product_id'=>$product_id]);
                    }

                    PandaoVariant::where('vid','=',$variant['vid'])->update($update);
                    Db::commit();
                }catch (PDOException $exp){
                    Db::rollback();
                    throw new QueueException($exp->getMessage());
                }catch (DbException $exp){
                    Db::rollback();
                    throw new QueueException($exp->getMessage());
                }catch (Exception $exp){
                    Db::rollback();
                    throw new QueueException($exp->getMessage());
                }catch (\Exception $exp){
                    Db::rollback();
                    throw new QueueException($exp->getMessage());
                }
                return $product_id;
            }else{
                throw new QueueException("接口返回数据空");
            }
        }catch (Exception $exp){
            throw new QueueException($exp->getFile().$exp->getLine().$exp->getMessage());
        }catch (\Throwable $exp){
            throw new QueueException($exp->getMessage());
        }
    }

    /**
     * 上传单个变体数据
     * @param type $product
     * @param type $api
     */
    public static function addVariant($variant,$config,$code)
    {

        set_time_limit(0);
        try{
            $product_id='';
            $data = [];
            $data['parent_sku']=$variant['parent_sku'];
            $data['sku']=$variant['sku'];
            $data['inventory']=$variant['inventory'];
            $data['price']=$variant['price'];
            $data['shipping']=$variant['shipping'];
            $data['color']=$variant['color'];
            $data['size']=$variant['size'];
            $data['msrp']=$variant['msrp'];
            $data['shipping_time']=$variant['shipping_time'];
            $data['main_image']=self::translateImgToFullPath($variant['main_image'], $code);

            $response = PandaoApi::instance($config)->loader('Variant')->add($data);
            dump($response);
            if(!empty($response))
            {
                $update=$updateProduct=[];

                if(isset($response['data']['Variant']))
                {
                    $update['variant_id'] = $response['data']['Variant']['id'];//变体Id
                    $update['product_id'] = isset($response['data']['Variant']['product_id'])?$response['data']['Variant']['product_id']:'';//
                    $product_id = $update['product_id'];
                    $update['enabled']    = $response['data']['Variant']['enabled']=='True'?1:0;
                }
                if(isset($response['code']) && $response['code']==0)
                {
                    $update['status']=1;
                    $update['message'] =  $updateProduct['publish_message']='';
                    $updateProduct['publish_status']=1;
                }else{
                    $update['status']=2;//刊登失败
                    $updateProduct['publish_status']=-1;
                    if(isset($response['message'])){
                        $update['message'] = $response['message'];
                        $updatep['publish_message'] = $response['message'];
                    }elseif(isset($response['error_description'])){
                        $update['message'] = $response['error_description'];
                        $updatep['publish_message'] = $response['error_description'];
                    }
                }

                if(isset($response['code']) && is_int($response['code']))
                {
                    $update['code']    = $response['code'];
                }else{
                    $update['code']    = 0;
                }

                $update['run_time'] = date('Y-m-d H:i:s',time());
                $vid = $variant['vid'];
                $where['vid']=['=',$vid];
                Db::startTrans();
                try{
                    if($updateProduct){
                        PandaoProduct::where('id',$variant['pid'])->update($updateProduct);
                    }
                    PandaoVariant::where($where)->update($update);
                    Db::commit();
                }catch (PDOException $exp){
                    Db::rollback();
                    throw new QueueException($exp->getMessage());
                }catch (DbException $exp){
                    Db::rollback();
                    throw new QueueException($exp->getMessage());
                }catch (Exception $exp){
                    Db::rollback();
                    throw new QueueException($exp->getMessage());
                }
                return $product_id;
            }else{
                throw new QueueException("接口返回数据为空");
            }

        }catch (Exception $exp){
            throw new QueueException($exp->getFile().$exp->getLine().$exp->getMessage());
        }catch (\Throwable $exp){
            throw new QueueException($exp->getMessage());
        }
    }
    /**
     * 将图片路径转换成对应的账号路径
     * @param type $images
     * @param type $code
     * @return type
     */
    public static function translateImgToFullPath($images,$code)
    {
        try{
            if(is_array($images))
            {
                foreach ($images as $key => &$img)
                {
                    $img = str_replace(config('picture_base_url'),'',$img);
                    if(strpos($img,'self')!==false)
                    {
                        if(strpos($img,'http')!==false)
                        {
                            $img = $img;
                        }else{
                            $img = GoodsImage::getThumbPath($img, 0,0);
                        }
                    }else{
                        if(strpos($img,'http')!==false)
                        {
                            $img = $img;
                        }else{
                            $img = GoodsImage::getThumbPath($img, 0,0,$code);
                        }
                    }
                }
                return implode('|',$images);
            }else{

                $images = str_replace(config('picture_base_url'),'',$images);
                if(strpos($images,'self')!==false)
                {
                    if(strpos($images,'http')!==false)
                    {
                        return $images;
                    }else{
                        return GoodsImage::getThumbPath($images, 0,0);
                    }
                }else{
                    if(strpos($images,'http')!==false)
                    {
                        return $images;
                    }else{
                        return GoodsImage::getThumbPath($images, 0,0,$code);
                    }
                }
            }
        }catch (Exception $exp){
            throw new QueueException( $exp->getMessage());
        }catch (\Throwable $exp){
            throw new QueueException($exp->getMessage());
        }
    }
}