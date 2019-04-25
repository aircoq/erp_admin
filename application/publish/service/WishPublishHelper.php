<?php

/**
 * Description of WishPublishHelper
 * @datetime 2017-5-13  11:42:25
 * @author joy
 */

namespace app\publish\service;
use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\common\model\wish\WishWaitUploadProductInfo;
use think\Db;
use app\common\model\wish\WishWaitUploadProduct;
use app\common\model\wish\WishWaitUploadProductVariant;
use app\goods\service\GoodsImage;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;

class WishPublishHelper {
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
//            foreach ($variants as $key => $variant)
//            {
//
//                if($key==0 && empty($product['product_id']))
//                {
//                    $product_id=$this->addProduct($product, $variant, $api);
//                }else{
//                    $variant['parent_sku'] = $product['parent_sku'];
//                    $product_id = $this->addVariant($variant, $api,$product['account']['code'],$product['uid']);
//                }
//            }


            //2018-10-22:解决第一个变体使用主图的问题-pan
            $product_id=$this->postPorduct($product, $api);
            //echo $product_id;
            foreach($variants as $key => $variant)
            {
                $variant['parent_sku'] = $product['parent_sku'];
                $product_id = $this->postVariant($variant, $api,$product['account']['code'],$product['uid']);
                //echo $product_id;
            }

            return $product_id;
        }catch(Exception $exp){
            throw new QueueException($exp->getMessage());
        }catch (\Throwable $exp){
            throw new QueueException($exp->getMessage());
        }
    }

//-------------------------------------------------------------------------------
    /**
     * 取代addProduct方法-pan
     * @param $product
     * @param $api
     */
    public function postPorduct($product,$api)
    {
        set_time_limit(0);
        try{
            $product_id='';
//            $product['vid'] = $variant['vid'];
//            $product['sku'] = $variant['sku'];
//            $product['inventory'] = $variant['inventory'];
//            $product['price'] = $variant['price'];
//            $product['shipping'] = $variant['shipping'];
//            $product['shipping_time'] = $variant['shipping_time'];
//            $product['color'] = $variant['color'];
//            $product['size'] = $variant['size'];
//            $product['msrp'] = $variant['msrp'];

            $data =[];

            $data['name']=$product['name'];


            $data['description'] =str_replace(chr(10),"\n",nl2br($product['description']));;

            $data['tags']=$product['tags'];
            $data['sku']=$product['parent_sku'];

            $data['inventory']=$product['inventory'];
            $data['price']=$product['highest_price'];
            $data['shipping']=$product['highest_shipping'];
            //$data['shipping_time']=$product['shipping_time'];

            $data['parent_sku']=$product['parent_sku'];


            //$data['color']=$product['color'];
            //$data['size']=$product['size'];

            //$main_image=$variant['main_image']; //替换
            $main_image=$product['main_image'];

            $data['main_image']= $this->translateImgToFullPath($main_image, $product['account']['code']);

            $extra_images = explode('|', $product['original_images']);

            //array_shift($extra_images);

            $data['extra_images']=$this->translateImgToFullPath($extra_images, $product['account']['code']);

            //$data['msrp']=$product['msrp'];


            if($product['brand'])
            {
                $data['brand']=$product['brand'];
            }

            if (preg_match('/^http(s)?:\\/\\/.+/', $product['landing_page_url']))
            {
                $data['landing_page_url']=$product['landing_page_url'];
            }

            if($product['upc'])
            {
                $data['upc']=$product['upc'];
            }

            //添加物流价格-pan
            $disabled_countries=null; //屏蔽的国家
            $wish_express_add_countries=null; //wish_express
            if (!is_null($product['all_country_shipping']))
            {
                $obj=json_decode($product['all_country_shipping'],true);
                $tmp=null;

                foreach($obj as $v)
                {
                    $key=$v['ProductCountryShipping']['country_code'];
                    if (intval($v['ProductCountryShipping']['closed'])==0)
                    {

                        if ($v['ProductCountryShipping']['use_product_shipping']==1)
                        {
                            $tmp[$key]=$data['shipping'];
                        } else if ($v['ProductCountryShipping']['use_product_shipping']==2)
                        {
                            $tmp[$key]=($data['shipping']+$v['ProductCountryShipping']['shipping_price']); //在原的基础上加
                        } else {
                            $tmp[$key]=$v['ProductCountryShipping']['shipping_price'];
                        }
                        if ($v['ProductCountryShipping']['wish_express']==1)
                        {
                            $wish_express_add_countries[]=$key;
                        }
                    } else {
                        $disabled_countries[]=$key;
                    }

                }
                if ($tmp!=null)
                {
                    $data['country_shipping_prices']=json_encode($tmp);
                }
            }


            $response = $api->postProduct($data);
            if (isset($product['uid']) && $product['uid'] == 1730) {
                Cache::handler()->set('wish:debug:wlw:productresponse', json_encode($response));
            }
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


                $update['run_time'] = date('Y-m-d H:i:s',time());
                $updatep=[];
                if(isset($response['data']['Product']))
                {
                    $product_id = $response['data']['Product']['id']; //产品id
                    $review_status =deal_review_status($response['data']['Product']['review_status']); //审核状态
                    $number_saves = $response['data']['Product']['number_saves']; //收藏量
                    $number_sold = $response['data']['Product']['number_sold']; //销售量
                    $last_updated= str2time($response['data']['Product']['last_updated']);
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
                        'publish_status'=>1,
                        'date_uploaded'=>strtotime(date("Y-m-d"),time()), //让先设置一个当前时间-pan
                        'review_status'=>$review_status,
                        'number_sold'=>$number_sold,
                        'last_updated'=>$last_updated,
                    ];


                    //这里要是有要屏蔽的国家走一下修改物流流程-pan,2018-11-2
                    if ($disabled_countries!=null || $wish_express_add_countries!=null)
                    {
                        $express_data = [];
                        $express_data['id']=$product_id;
                        $express_data['access_token']=$api->access_token;
                        if ($disabled_countries!=null)
                            $express_data['disabled_countries']=implode(',',$disabled_countries);
                        if ($wish_express_add_countries!=null)
                            $express_data['wish_express_add_countries']=implode(',',$wish_express_add_countries);

                        $response2 = $api->updateMultiShipping($express_data);

                    }


                } //end Product




                $where['id']=['=',$product['id']];
                Db::startTrans();
                try{
                    if($updatep)
                    {
                        WishWaitUploadProduct::where($where)->update($updatep);
                    }
                    if($product_id)
                    {
                        WishWaitUploadProductInfo::where($where)->update(['product_id'=>$product_id]);
                    }

                    //WishWaitUploadProductVariant::where('vid','=',$variant['vid'])->update($update);
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
                throw new QueueException("接口返回数据空");
            }

        }catch (Exception $exp){
            throw new QueueException($exp->getFile().$exp->getLine().$exp->getMessage());
        }catch (\Throwable $exp){
            throw new QueueException($exp->getMessage());
        }
    }



    /***
     * 取代addVariant方法-pan
     * @param $variant
     * @param $api
     * @param $code
     * @param int $uid
     */
    public function postVariant($variant,$api,$code,$uid=0)
    {
        set_time_limit(0);
        try{
            $product_id='';
            $data = [];
            $data['parent_sku']=$variant['parent_sku'];
            $data['sku']=$variant['sku'];
            $data['inventory']=$variant['inventory'];
            $data['price']=$variant['price'];
//            $data['shipping']=$variant['shipping'];
            $data['color']=$variant['color'];
            $data['size']=$variant['size'];
            $data['msrp']=$variant['msrp'];
            $data['shipping_time']=$variant['shipping_time'];
            $data['main_image']=$this->translateImgToFullPath($variant['main_image'], $code);
            if ($uid == 1730) {
                Cache::handler()->set('wish:debug:wlw:variantpost_'.$variant['vid'], json_encode($data));
            }
            $response = $api->postVariant($data);
            if ($uid == 1730) {
                Cache::handler()->set('wish:debug:wlw:variantresponse_'.$variant['vid'], json_encode($response));
            }
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

                $update['run_time'] = date('Y-m-d H:i:s',time());

                if(isset($response['data']['Variant']))
                {
                    $update['variant_id'] = $response['data']['Variant']['id'];//变体Id
                    $update['product_id'] = $response['data']['Variant']['product_id'];//
                    $product_id = $update['product_id'];
                    $update['enabled']    = $response['data']['Variant']['enabled']=='True'?1:0;
                }

                $vid = $variant['vid'];
                $where['vid']=['=',$vid];
                Db::startTrans();
                try{
                    WishWaitUploadProductVariant::where($where)->update($update);
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

 //-------------------------------------------------------------------------------

    /**
     * 刊登商品信息
     * @param array $product
     * @return string
     */
    public function addProduct($product,$variant,$api)//作废
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

            //$main_image=$variant['main_image']; //替换
            $main_image=$product['main_image'];

		    $data['main_image']= $this->translateImgToFullPath($main_image, $product['account']['code']);

		    $extra_images = explode('|', $product['original_images']);

		    array_shift($extra_images);

		    $data['extra_images']=$this->translateImgToFullPath($extra_images, $product['account']['code']);

		    $data['msrp']=$product['msrp'];


		    if($product['brand'])
		    {
			    $data['brand']=$product['brand'];
		    }

		    if (preg_match('/^http(s)?:\\/\\/.+/', $product['landing_page_url']))
		    {
			    $data['landing_page_url']=$product['landing_page_url'];
		    }

			if($product['upc'])
			{
				$data['upc']=$product['upc'];
			}

			//添加物流价格-pan
//            if (!is_null($product['all_country_shipping']))
//            {
//                $obj=json_decode($product['all_country_shipping'],true);
//                $tmp=null;
//                foreach($obj as $v)
//                {
//                    if ($v['ProductCountryShipping']['use_product_shipping']=='0'
//                        || $v['ProductCountryShipping']['use_product_shipping']=='False')
//                    {
//                        $key=$v['ProductCountryShipping']['country_code'];
//                        $tmp[$key]=$v['ProductCountryShipping']['shipping_price'];
//                    }
//                }
//                if ($tmp!=null)
//                {
//                    $data['country_shipping_prices']=json_encode($tmp);
//                }
//            }


		    $response = $api->postProduct($data);
            if (isset($product['uid']) && $product['uid'] == 1730) {
                Cache::handler()->set('wish:debug:wlw:productresponse', json_encode($response));
            }
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


                $update['run_time'] = date('Y-m-d H:i:s',time());
                $updatep=[];
                if(isset($response['data']['Product']))
                {
                    $product_id = $response['data']['Product']['id']; //产品id
                    $review_status =deal_review_status($response['data']['Product']['review_status']); //审核状态
                    $number_saves = $response['data']['Product']['number_saves']; //收藏量
                    $number_sold = $response['data']['Product']['number_sold']; //销售量
                    $last_updated= str2time($response['data']['Product']['last_updated']);
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
                        'publish_status'=>1,
                        'review_status'=>$review_status,
                        'number_sold'=>$number_sold,
                        'last_updated'=>$last_updated,
                    ];

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
                        WishWaitUploadProduct::where($where)->update($updatep);
                    }
                    if($product_id)
                    {
                        WishWaitUploadProductInfo::where($where)->update(['product_id'=>$product_id]);
                    }

                    WishWaitUploadProductVariant::where('vid','=',$variant['vid'])->update($update);
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
    public  function addVariant($variant,$api,$code,$uid=0) //作废
    {

        set_time_limit(0);
        try{
            $product_id='';
	        $data = [];
            $data['parent_sku']=$variant['parent_sku'];
            $data['sku']=$variant['sku'];
            $data['inventory']=$variant['inventory'];
            $data['price']=$variant['price'];
//            $data['shipping']=$variant['shipping'];
            $data['color']=$variant['color'];
            $data['size']=$variant['size'];
            $data['msrp']=$variant['msrp'];
            $data['shipping_time']=$variant['shipping_time'];
            $data['main_image']=$this->translateImgToFullPath($variant['main_image'], $code);
            if ($uid == 1730) {
                Cache::handler()->set('wish:debug:wlw:variantpost_'.$variant['vid'], json_encode($data));
            }
            $response = $api->postVariant($data);
            if ($uid == 1730) {
                Cache::handler()->set('wish:debug:wlw:variantresponse_'.$variant['vid'], json_encode($response));
            }
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

                $update['run_time'] = date('Y-m-d H:i:s',time());

                if(isset($response['data']['Variant']))
                {
                    $update['variant_id'] = $response['data']['Variant']['id'];//变体Id
                    $update['product_id'] = $response['data']['Variant']['product_id'];//
                    $product_id = $update['product_id'];
                    $update['enabled']    = $response['data']['Variant']['enabled']=='True'?1:0;
                }

                $vid = $variant['vid'];
                $where['vid']=['=',$vid];
                Db::startTrans();
                try{
                    WishWaitUploadProductVariant::where($where)->update($update);
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
    public  function translateImgToFullPath($images,$code)
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
                            $img = GoodsImage::getThumbPath($img, 0,0, '', true);
                        }
                    }else{
                        if(strpos($img,'http')!==false)
                        {
                            $img = $img;
                        }else{
                            $img = GoodsImage::getThumbPath($img, 0,0,$code, true);
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
