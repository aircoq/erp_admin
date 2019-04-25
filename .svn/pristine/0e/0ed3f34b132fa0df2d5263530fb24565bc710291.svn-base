<?php

namespace app\listing\task;

use app\index\service\AbsTasker;
use app\common\cache\Cache;
use think\Db;
use app\common\model\wish\WishWaitUploadProduct;
use app\common\model\wish\WishWaitUploadProductVariant;
use app\common\service\Twitter;
use app\common\exception\TaskException;
use app\publish\queue\WishQueue;

/**
 * 将本地csv文件数据写入数据库
 *
 * @author RondaFul
 */
class WishProductDownload extends AbsTasker{
    
    public function getName()
    {
        return "将wish产品信息写入数据库";
    }

    public function getDesc()
    {
        return "将wish产品信息写入数据库";
    }

    public function getCreator()
    {
        return "joy";
    }
    
    public function getParamRule()
    {
        return [];
    }
     
    public  function execute()
    {
        set_time_limit(0);
        
        $productModel = new WishWaitUploadProduct();
        
        $variantModel = new WishWaitUploadProductVariant();
        
        $jobs = (new WishQueue('Cache:wishGoodsInsert'))->lists();

        try{
            if(is_array($jobs) && !empty($jobs))
            {
                foreach ($jobs as $job)
                {
                    $accountjob = explode("_", $job);  

                    list($accountid,$job_id) = $accountjob;

                    $account['id'] = $accountid;

                    $file = './wish_product/'.$job.'.csv';

                    if(file_exists($file))
                    {

                        $handle = fopen($file,'a+');
                        $line = 0;
                        $data=[];
                        while(! feof($handle))
                        {
                            $row = fgetcsv($handle);
                            if(is_array($row))
                            {
                                if($line>0 && count($row) == 30 && $row[0]!='Product ID')
                                {
                                        $data[] = $row;
                                }
                                ++$line;
                            }
                        }

                        fclose($handle);
                                           
                        $result =  $this->dealData($data,$productModel,$variantModel,$account);
                        
                        if($result)
                        {
                            @unlink($file); //删除csv文件
	                        (new WishQueue('Cache:wishGoodsInsert'))->remove($job);
                        }                              
                        unset($data);
                    }          
                }            
            } 
        }catch (TaskException $exp) {
            throw  new Exception($exp->getMessage().$exp->getFile().$exp->getLine());
        }
        
    }
    
    public function dealData(array $data,$productModel,$variantModel,$account)
    {
        set_time_limit(0);
        $exec_status=true;
        $service = new \app\listing\service\WishListingHelper;
        $skuMapModel = new \app\common\model\GoodsSkuMap;
        $goodsSkuModel = new \app\common\model\GoodsSku;
        foreach($data as $k => $row)
        {

            try{
                Db::startTrans(); //开启事物
                $product_id = $row[0]; //产品id
                $name = $row[1]; //标题
                $description = $row[2]; //描述
                $number_saves = (int)$row[3]; //收藏
                $number_sold = (int)$row[4]; //售出
                $parent_sku = $row[5]; //spu
                $upc = $row[6]; //upc
                $landing_page_url = $row[7]; //详情页面
                $is_promoted = $row[19]; //是否促销
                $review_status = $row[20]; //审核状态
                $main_image= $row[22]; //主图
                $extra_images=$row[23]; //附图
                $tags  = $row[24]; //tags
                $brand = $row[25]; //品牌
                $last_updated = $row[26]; //更新时间

                $date_uploaded = $row[27]; //刊登时间

                $warning_id = $row[28];
                $wish_express_countries=$row[29];


                $variant_id = $row[8]; //变体id
                $sku = $row[9]; //sku
                $size = $row[10];
                $color = $row[11];

                $msrp = (float)str_replace('$','',$row[12]);

                $cost = (float)str_replace('$','',$row[13]);

                $price = (float)str_replace('$','',$row[14]);

                $shipping = (float)str_replace('$','',$row[15]);

                $inventory = (int)$row[16];
                $shipping_time = $row[17];
                $enabled = $row[18];


                if($tags)
                {
                    $tagsArr = explode("|", $tags);
                    $attr = [];
                    if(is_array($tagsArr) && $tagsArr)
                    {
                        foreach($tagsArr as $arr)
                        {
                            $tag = explode(",", $arr);

                            if(is_array($tag) && $tag && count($tag)==2)
                            {
                                list($tagid,$tagname) = $tag;
                                $attr[] = str_replace("name:", "", $tagname);
                            }

                            unset($tag);unset($tagid);unset($tagname);
                        }
                        $newTags = implode(",",$attr);
                    }
                }else{
                    $newTags ='';
                }
                unset($tags);

                $product = [
                    'product_id'=>$product_id,
                    'name'=>$name,
                    'main_image'=>$main_image,
                    'extra_images'=>$extra_images,
                    'description'=>$description,
                    'number_saves'=>$number_saves,
                    'number_sold'=>$number_sold,
                    'parent_sku'=>$parent_sku,
                    'upc'=>$upc,
                    'landing_page_url'=>$landing_page_url,
                    'is_promoted'=>$is_promoted,
                    'review_status'=>$review_status,
                    'brand'=>$brand,
                    'last_updated'=> strtotime(str_replace('T'," ",$last_updated)),
                    'tags'=>$newTags,
                    'date_uploaded'=>strtotime(str_replace('T'," ",$date_uploaded)),
                    'warning_id'=>$warning_id,
                    'wish_express_countries'=>$wish_express_countries,
                ];

                //Wish产品变体信息
                $variant=[
                    'variant_id'=>$variant_id,
                    'sku'=>$sku,
                    'main_image'=>$main_image,
                    'size'=>$size,
                    'color'=>$color,
                    'msrp'=>$msrp,
                    'price'=>$price,
                    'shipping'=>$shipping,
                    'shipping_time'=>$shipping_time,
                    'enabled'=>$enabled,
                    'inventory'=>$inventory,
                    'cost'=>$cost,
                    'status'=>1, //如果不存在刊登记录，则将此标记为1
                ];
               
                if($product_id)
                {
                    $pp = WishWaitUploadProduct::where(['product_id'=>$product_id])->find();

                    if($pp)
                    {
                        //如果记录中lock_product为0则更新
                        $pid = $pp['id'];
	                    WishWaitUploadProduct::where(['product_id'=>$product_id,'lock_product'=>0])->update($product);
                    }else{
                        //不存在记录则新生成id
                        $pid=abs(Twitter::instance()->nextId(3,$account['id']));

                        $product['id'] = $pid;

                        $product['accountid']  = $account['id'];
                        
                        $time = time();
                        
                        $product['addtime']= $time;
                        
                        time_partition(\app\common\model\wish\WishWaitUploadProduct::class, $product['addtime']);

                        WishWaitUploadProduct::insert($product);
                    }
                 }

                if($variant_id)
                {
                    if(empty($product_id))
                    {
                        $product_id = self::getProductId($data, $k);
                        if($product_id)
                        {
                            $pp = $productModel->get(['product_id'=>$product_id]);   
                            $pp = is_object($pp)?$pp->toArray():$pp;
                            $pid = $pp['id'];
                        }
                    }

                    $variant['product_id'] = $product_id;
                    
                    if($skuStatus = $skuMapModel->getSkuSellStatus($sku, $account['id']))
                    {
                        $sellStatus = $skuStatus['sku']['status'];
                    }else{
                        $skuStatus = $goodsSkuModel->field('status')->where(['sku'=>$sku])->find();
                        if($skuStatus)
                        {
                            $skuStatus= is_object($skuStatus)?$skuStatus->toArray():$skuStatus;
                            $sellStatus = $skuStatus['status'];
                        }else{
                            $sellStatus='';
                        }
                    }
                    
                    if(is_numeric($sellStatus))
                    {
                        $variant['sell_status']=  $sellStatus;
                    }

                    //如果变体中存在相关记录
                    if(WishWaitUploadProductVariant::where(['variant_id'=>$variant_id])->find())
                    {
                        //如果存在相关记录，则更新
	                    WishWaitUploadProductVariant::where(['variant_id'=>$variant_id,'lock_variant'=>0])->update($variant);
                        //$variantModel->isUpdate(true)->update($variant,['variant_id'=>$variant_id,'lock_variant'=>0]);
                    }else{
                        //不存在记录则新生成id
                        $variant['vid']=abs(Twitter::instance()->nextId(3,$account['id']));
                        $variant['pid']=$pid;
                        $time = time();
                        $variant['add_time']= $time;
                        time_partition(\app\common\model\wish\WishWaitUploadProductVariant::class,$variant['add_time']);
                        WishWaitUploadProductVariant::insert($variant);
                        //$variantModel->isUpdate(false)->save($variant);
                    }
                    //如果变体的父id存在，则更新product表中的price,inventory,shipping
                    if($pid)
                    {
                        $update=$service->ProductStat($variantModel,['pid'=>$pid]);
                        if($update)
                        {
                             //$productModel->isUpdate(true)->update($update, ['id'=>$pid]);
                             WishWaitUploadProduct::where(['id'=>$pid])->update($update);
                        }
                    }                    
                 } 
                 
	            Db::commit(); //提交事物
            }catch (\Exception $exp) {
                Db::rollback();//事物回滚
	            throw  new TaskException($exp->getMessage().$exp->getFile().$exp->getLine());
            }    
        }
        return $exec_status;
    }
    
    public static function getProductId(array $data,$k)
    {
        while ($k>0) 
         {
            if(empty($data[$k][0]))
            {                
                 $k=$k-1;
                 continue;
            }else{ 
                if(preg_match("/^[a-z\d]*$/i",$data[$k][0]))
                {
                   return $data[$k][0];
                }else{
                    return '';
                }
                
            }
        }	     
    }
     
}
