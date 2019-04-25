<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-7
 * Time: 上午10:06
 */

namespace app\publish\service;
use app\common\exception\QueueException;

use app\common\model\joom\JoomProduct;
use app\common\model\joom\JoomProductInfo;

use app\common\model\joom\JoomVariant;
use think\console\command\make\Model;
use think\Db;
use app\goods\service\GoodsImage;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;

class JoomPublishHelper
{
    protected $channel_id=7;

    /**
     * 上传变体数据
     * @param array $varints
     * @return string
     */
    public function addVarints($product,$variants,$api)
    {
        set_time_limit(0);
        try{
            $product_id='';
            $code = $product['shop']['code'];
            foreach ($variants as $key => $variant)
            {

                if($key==0 && empty($product['product_id']))
                {
                    $product_id=$this->addProduct($product, $variant, $api,$code);
                }else{
                    $variant['parent_sku'] = $product['parent_sku'];
                    $product_id = $this->addVariant($variant, $api,$code);
                }
            }
            return $product_id;
        }catch(Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
        }catch (\Throwable $exp){
            throw new QueueException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
    /**
     * 刊登商品信息
     * @param array $product
     * @return string
     */
    public function addProduct($product,$variant,$api,$code)
    {
        set_time_limit(0);
        try{
            $product_id='';
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
            $data['description'] =str_replace('<br />','',$data['description']);

            $data['parent_sku']=$product['parent_sku'];
            $data['tags']=$product['tags'];
            $data['sku']=$product['sku'];
            $data['inventory']=$product['inventory'];
            $data['price']=$product['price'];
            $data['shipping']=$product['shipping'];
            $data['shipping_time']=$product['shipping_time'];
            $data['shipping_weight'] = $variant['shipping_weight'];
            $data['color']=$product['color'];
            $data['size']=$product['size'];

            $main_image=$variant['main_image'];

            $data['variant_main_image']= $this->translateImgToFullPath($main_image, $code);

            $extra_images = explode('|', $product['original_images']);

            array_shift($extra_images);

            $data['extra_images']=$this->translateImgToFullPath($extra_images, $code);

            $data['msrp']=$product['msrp'];
            $data['product_main_image'] = $this->translateImgToFullPath($product['main_image'],$code);

//            if($product['brand'])
//            {
//                $data['brand']=$product['brand'];
//            }

            if (preg_match('/^http(s)?:\\/\\/.+/', $product['landing_page_url']))
            {
                $data['landing_page_url']=$product['landing_page_url'];
            }

            if($product['upc'])
            {
                $data['upc']=$product['upc'];
            }

            $data['dangerous_kind'] = $product['dangerous_kind'];

            $response = $api->postProduct($data);
            
            if(!empty($response)){
                $update=array();
                if(isset($response['state']) && $response['state'] && isset($response['code']) && $response['code']==0)
                {
                    $update['status']=1;
                    $update['message'] = '';
                }elseif(isset($response['code']) && is_null($response['code'])){
                    $update['status']=3;//未知异常
                    $update['message'] = $response['message'];
                }elseif(isset($response['code']) && $response['code']!=0){
                    $update['status']=2;//刊登失败
                    $update['message'] = $response['message'];
                }

                if(isset($response['code']) && is_int($response['code']))
                {
                    $update['code']    = $response['code'];
                }else{
                    $update['code']    = 0;
                }

                $update['run_time'] = time();
                $updatep=[];
                if(isset($response['data']['Product']))
                {
                    $product_id = $response['data']['Product']['id']; //产品id
                    $review_status =deal_review_status($response['data']['Product']['review_status']); //审核状态
                    $number_saves = $response['data']['Product']['number_saves']; //收藏量
                    $number_sold = $response['data']['Product']['number_sold']; //销售量
                    $date_uploaded= strtotime($response['data']['Product']['date_uploaded']);
                    if(isset($response['data']['Product']['is_promoted']))
                    {
                        $is_promoted = $response['data']['Product']['is_promoted'];
                    }else{
                        $is_promoted='False';
                    }

                    $updatep= [
                        'enabled'=>1,
                        'is_promoted' =>$is_promoted=='True'?1:0,
                        'number_saves'=>$number_saves,
                        'product_id'=>$product_id,
                        'review_status'=>$review_status,
                        'number_sold'=>$number_sold,
                        'date_uploaded'=>$date_uploaded,
                        'main_image'=>$response['data']['Product']['main_image']?:'',
                    ];

                    $diagnosis = json_encode($response['data']['Product']['diagnosis']);

                    if(isset($response['data']['Product']['variants']))
                    {
                        $variants = $response['data']['Product']['variants'];
                        foreach ($variants as $V)
                        {
                            if($V['Variant']['sku']==$variant['sku'])
                            {
                                $update['variant_id'] = $V['Variant']['id'];//变体Id
                                $update['product_id'] = $V['Variant']['product_id'];//
                                $update['enabled'] = $V['Variant']['enabled']=='True'?1:0;
                                if(isset($V['Variant']['main_image']))
                                {
                                    $update['main_image'] =$V['Variant']['main_image']?:'';
                                }
                                break;
                            }
                        }
                    }
                }

                $where['id']=['=',$product['id']];
                Db::startTrans();
                try{
                    if($updatep)
                    {
                        JoomProduct::where($where)->update($updatep);
                    }
                    if($product_id)
                    {
                        JoomProductInfo::where($where)->update(['product_id'=>$product_id,'review_note'=>$diagnosis]);
                    }
                    JoomVariant::where('id','=',$variant['id'])->update($update);
                    Db::commit();
                }catch (PDOException $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (DbException $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (Exception $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
                }

                return $product_id;
            }else{
                throw new QueueException("接口返回数据空");
            }

        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
        }catch (\Throwable $exp){
            throw new QueueException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * 上传单个变体数据
     * @param type $product
     * @param type $api
     */
    public  function addVariant($variant,$api,$code)
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
            $data['shipping_weight'] = $variant['shipping_weight'];
            $data['main_image']=$this->translateImgToFullPath($variant['main_image'], $code);

            $response = $api->postVariant($data);
            if(!empty($response))
            {
                $update=array();
                if(isset($response['state']) && $response['state'] && isset($response['code']) && $response['code']==0)
                {
                    $update['status']=1;
                    $update['message'] = '';
                }elseif(isset($response['code']) && is_null($response['code'])){
                    $update['status']=3;//未知异常
                    $update['message'] = $response['message'];
                }elseif(isset($response['code']) && $response['code']!=0){
                    $update['status']=2;//刊登失败
                    $update['message'] = $response['message'];
                }


                if(isset($response['code']) && is_int($response['code']))
                {
                    $update['code']    = $response['code'];
                }else{
                    $update['code']    = 0;
                }

                $update['run_time'] =time();

                if(isset($response['data']['Variant']))
                {
                    $update['variant_id'] = $response['data']['Variant']['id'];//变体Id
                    $update['product_id'] = $response['data']['Variant']['product_id'];//
                    if (isset($response['data']['Variant']['main_image']))
                    {
                        $update['main_image'] = $response['data']['Variant']['main_image']?:'';//
                    }
                    $product_id = $update['product_id'];
                    $update['enabled']    = $response['data']['Variant']['enabled']=='True'?1:0;
                }

                $id = $variant['id'];
                $where['id']=['=',$id];
                Db::startTrans();
                try{
                    JoomVariant::where($where)->update($update);
                    Db::commit();
                }catch (PDOException $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (DbException $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
                }catch (Exception $exp){
                    Db::rollback();
                    throw new QueueException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
                }
                return $product_id;
            }else{
                throw new QueueException("接口返回数据为空");
            }

        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
        }catch (\Throwable $exp){
            throw new QueueException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
    /**
     * 将图片路径转换成对应的账号路径
     * @param type $images
     * @param type $code
     * @return type
     */
    public  function translateImgToFullPath($images,$code)
    {
        try{
            if(is_array($images))
            {
                foreach ($images as $key => &$img)
                {
                    $img = str_replace(config('picture_base_url'),'',$img);
                    if(strpos($img,'http')!==false)
                    {
                        $img = $img;
                    }else{
                        $img = GoodsImage::getThumbPath($img, 0,0,$code);
                    }
                }
                return implode('|',$images);
            }else{

                $images = str_replace(config('picture_base_url'),'',$images);

                if(strpos($images,'http')!==false)
                {
                    return $images;
                }else{

                    return GoodsImage::getThumbPath($images, 0,0,$code);
                }
            }
        }catch (Exception $exp){
            throw new QueueException( $exp->getMessage());
        }catch (\Throwable $exp){
            throw new QueueException($exp->getMessage());
        }

    }
}