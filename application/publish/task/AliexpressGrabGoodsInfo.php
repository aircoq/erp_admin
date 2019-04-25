<?php 
namespace app\publish\task;
/**
 * Created by ZendStudio
 * User: HotZr
 * Date: 17-4-5
 * Time: 下午2:16
 * Doc: 速卖通商户商品详情数据抓取任务
 * info : 这里用原生的SQL语句，不是我不想用Model或者DB，只是因为用了出现几次抓取会出现内存溢出，而且效率好低，且速卖通接口改动频率几年难改一次，所以才写原生。
 */
 
use app\index\service\AbsTasker;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\aliexpress\AliexpressProduct;
use service\aliexpress\AliexpressApi;
use think\Db;
class AliexpressGrabGoodsInfo extends AbsTasker
{
    
    public function getName()
    {
        return "速卖通抓取商品详情数据（废弃任务）";
    }
    
    public function getDesc()
    {
        return "速卖通抓取商品详情数据";
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
        $intTime = time()-24*3600;
        $AliexpressProduct = new AliexpressProduct();
        $PostProduct = AliexpressApi::instance()->loader('PostProduct');
        $Result = $AliexpressProduct->field('id,product_id,account_id,pull_time')->where('product_id','>',0)->where('pull_time','<',$intTime)->limit(5000)->select();
        foreach ($Result as $Product)
        {
            $AliexpressAccount = new AliexpressAccount();
            $user=$AliexpressAccount->where('id','=',$Product->account_id)->field('id,client_id,access_token,client_secret,refresh_token')->find();
            $arrTemp = $user->toArray();
            /*
            //时时获取access_token，防止抓取失败，这段代码待服务器的access_token抓取任务正常后请删除可加快速度！
            $arrAccessToken = $PostProduct->getTokenByRefreshToken($arrTemp);
            $arrTemp['access_token'] = $arrAccessToken['access_token'];
            //时时获取access_token，防止抓取失败，这段代码待服务器的access_token抓取任务正常后请删除可加快速度！
            */
            $PostProduct->setConfig($arrTemp);
            $arrProduct = $PostProduct->findAeProductById($Product->product_id);
            //如果数据有错误代码就不要执行了。
            if(is_null($arrProduct) || array_key_exists('error_code', $arrProduct))continue;
            //第一步、更新主表
            $strSql = "UPDATE `aliexpress_product` SET
                            `bulk_order`='".(isset($arrProduct['bulkOrder'])?$arrProduct['bulkOrder']:0)."',
                            `lot_num`='".(isset($arrProduct['lotNum'])?$arrProduct['lotNum']:0)."',
                            `package_type`='".(int)$arrProduct['packageType']."',
                            
                            `subject`='".addcslashes($arrProduct['subject'],"'")."',
                            `reduce_strategy`='".$arrProduct['reduceStrategy']."',
                            `product_unit`='".$arrProduct['productUnit']."',
                            `ws_offline_date`='".strtotime(substr($arrProduct['wsOfflineDate'],0,14))."',
                            `package_length`='".$arrProduct['packageLength']."',
                            `ws_display`='".$arrProduct['wsDisplay']."',
                            
                            `package_height`='".$arrProduct['packageHeight']."',
                            `package_width`='".$arrProduct['packageWidth']."',
                            `is_pack_sell`='".(int)$arrProduct['isPackSell']."',
                            `currency_code`='".$arrProduct['currencyCode']."',
                            `owner_member_seq`='".$arrProduct['ownerMemberSeq']."',
                            `category_id`='".$arrProduct['categoryId']."',
                            
                            `product_status_type`='".$arrProduct['productStatusType']."',
                            
                            `owner_member_id`='".$arrProduct['ownerMemberId']."',
                            
                            `gross_weight`='".$arrProduct['grossWeight']."',
                            `group_id`='".implode(',',$arrProduct['groupIds'])."',
                            `ws_valid_num`='".$arrProduct['wsValidNum']."',
                            `delivery_time`='".$arrProduct['deliveryTime']."',
                            `bulk_discount`='".(isset($arrProduct['bulkDiscount'])?$arrProduct['bulkDiscount']:0)."',
                            `base_unit`='".(isset($arrProduct['baseUnit'])?$arrProduct['baseUnit']:0)."',
                            `add_weight`='".(isset($arrProduct['addWeight'])?$arrProduct['addWeight']:0)."',
                            `add_unit`='".(isset($arrProduct['addUnit'])?$arrProduct['addUnit']:0)."',
                                
                            `product_price`='".$arrProduct['productPrice']."',
                            `pull_time`='".time()."'
                             WHERE `product_id`={$arrProduct['productId']};";
            //第二步、更新详情表
            $strSqlInfo = "UPDATE `aliexpress_product_info` SET 
                            `freight_template_id`='".$arrProduct['freightTemplateId']."',
                            `is_image_dynamic`='".(int)$arrProduct['isImageDynamic']."',
                            `imageurls`='".$arrProduct['imageURLs']."',
                            `detail`='".htmlspecialchars($arrProduct['detail'],ENT_QUOTES)."',
                            `mobile_detail`='".(isset($arrProduct['mobileDetail'])?htmlspecialchars($arrProduct['mobileDetail'],ENT_QUOTES):'')."',
                            
                            `promise_template_id`='".$arrProduct['promiseTemplateId']."',
                            
                            `coupon_start_date`='".(isset($arrProduct['couponStartDate'])?$arrProduct['couponStartDate']:0)."',
                            `coupon_end_date`='".(isset($arrProduct['couponEndDate'])?$arrProduct['couponEndDate']:0)."',
                            `sizechart_id`='".(isset($arrProduct['sizechartId'])?$arrProduct['sizechartId']:0)."',
                            `promise_template_id`='".(isset($arrProduct['promiseTemplateId'])?$arrProduct['promiseTemplateId']:0)."',
                            `src`='".(isset($arrProduct['src'])?$arrProduct['src']:'')."'
                             WHERE `product_id`={$arrProduct['productId']};";
            Db::execute($strSql);
            Db::execute($strSqlInfo);
            //第三步、更新普通属性表
            $strSqlAttr = '';
            foreach ($arrProduct['aeopAeProductPropertys'] as $val)
            {
                $strSqlAttr .="('".$Product->product_id."','". $Product->id."','".(isset($val['attrNameId'])?$val['attrNameId']:0)."','".(isset($val['attrValueId'])?$val['attrValueId']:0)."','".(isset($val['attrName'])?addcslashes($val['attrName'],"'"):'')."','".(isset($val['attrValue'])?addslashes($val['attrValue']):'')."','".time()."','".time()."'),";
            }
            $strSqlAttr = rtrim($strSqlAttr,',');
            if(!empty($strSqlAttr))
            {
                $strSqlDelAttr = "DELETE FROM `aliexpress_product_attr` WHERE `product_id`={$Product->id};";
                Db::execute($strSqlDelAttr);
                $strSqlAttr ="INSERT INTO `aliexpress_product_attr` (`product_id`, `ap_id`, `attr_name_id`, `attr_value_id`, `attr_name`, `attr_value`, `create_time`, `update_time`) VALUES ".$strSqlAttr.";";
                Db::execute($strSqlAttr);
            }
            //第四步、更新SKU属性表
            if(isset($arrProduct['aeopAeProductSKUs']) && count($arrProduct['aeopAeProductSKUs']>0))
            {
                foreach($arrProduct['aeopAeProductSKUs'] as $sku)
                {
                   // echo "SELECT `product_id` FROM `aliexpress_product_sku` WHERE `ap_id`={$Product->id} and `sku_code`='".$sku['skuCode']."';";exit;
                    //第一种、情况SKU CODE有值的，则根据 本地商品ID和SKU CODE查询记录，进行增或改操作
                    if(isset($sku['skuCode']) && !empty($sku['skuCode']))
                    {
                        $strSqlSku = '';
                        if(Db::query("SELECT `product_id` FROM `aliexpress_product_sku` WHERE `ap_id`={$Product->id} and `sku_code`='".$sku['skuCode']."';"))
                        {
                            $strSqlSku = '';
                            $strSqlSku = "UPDATE `aliexpress_product_sku` SET ".
                                "`product_id`='" . $Product->product_id."',".
                                "`sku_price`='" .(isset($sku['skuPrice'])?$sku['skuPrice']:0)."',".
                                "`sku_stock`='" .(($sku['ipmSkuStock']>0)?(int)$sku['skuStock']:0)."',".
                                "`ipm_sku_stock`='" .(isset($sku['ipmSkuStock'])?$sku['ipmSkuStock']:'')."',".
                                "`merchant_sku_id`='" .(isset($sku['id'])?$sku['id']:'')."',".
                                "`currency_code`='" .(isset($sku['currencyCode'])?$sku['currencyCode']:'USD')."',".
                                "`update_time`='" .time()."' WHERE `ap_id`={$Product->id} and `sku_code`='".$sku['skuCode']."';";
                         }
                         else 
                         {
                             //没有数据的话就直接添加数据
                             $strSqlSku = '';
                             $strSqlSku ="('".$Product->product_id."','"
                             . $Product->id."','"
                             .(isset($sku['skuPrice'])?$sku['skuPrice']:0)."','"
                             .(isset($sku['skuCode'])?$sku['skuCode']:'')."','"
                             .(($sku['ipmSkuStock']>0)?(int)$sku['skuStock']:0)."','"
                             .(isset($sku['ipmSkuStock'])?$sku['ipmSkuStock']:'')."','"
                             .(isset($sku['id'])?$sku['id']:'')."','"
                             .(isset($sku['currencyCode'])?$sku['currencyCode']:'USD')."','"
                             .time()."','"
                             .time()."')";
                             $strSqlSku ="INSERT INTO `aliexpress_product_sku` (`product_id`, `ap_id`, `sku_price`, `sku_code`, `sku_stock`, `ipm_sku_stock`, `merchant_sku_id`,`currency_code`, `create_time`,`update_time`) VALUES ".$strSqlSku.";";
                         }
                         Db::execute($strSqlSku);
                      }
                      else 
                      { 
                          $strSqlSku = '';
                          //第二种情况：SKU Code 为空的，比如说运费什么的。
                          if(Db::query("SELECT `product_id` FROM `aliexpress_product_sku` WHERE `ap_id`={$Product->id} and `sku_code`='';"))
                          {
                              $strSqlSku = "UPDATE `aliexpress_product_sku` SET ".
                                  "`product_id`='" . $Product->product_id."',".
                                  "`sku_price`='" .(isset($sku['skuPrice'])?$sku['skuPrice']:0)."',".
                                  "`sku_stock`='" .(($sku['ipmSkuStock']>0)?(int)$sku['skuStock']:0)."',".
                                  "`ipm_sku_stock`='" .(isset($sku['ipmSkuStock'])?$sku['ipmSkuStock']:'')."',".
                                  "`merchant_sku_id`='" .(isset($sku['id'])?$sku['id']:'')."',".
                                  "`currency_code`='" .(isset($sku['currencyCode'])?$sku['currencyCode']:'USD')."',".
                                  "`update_time`='" .time()."' WHERE `ap_id`={$Product->id} and `sku_code`='';";
                          }
                          else
                          {
                              $strSqlSku ="('".$Product->product_id."','"
                                  . $Product->id."','"
                                  .(isset($sku['skuPrice'])?$sku['skuPrice']:0)."','"
                                  .(isset($sku['skuCode'])?$sku['skuCode']:'')."','"
                                  .(($sku['ipmSkuStock']>0)?(int)$sku['skuStock']:0)."','"
                                  .(isset($sku['ipmSkuStock'])?$sku['ipmSkuStock']:'')."','"
                                  .(isset($sku['id'])?$sku['id']:'')."','"
                                  .(isset($sku['currencyCode'])?$sku['currencyCode']:'USD')."','"
                                  .time()."','"
                                  .time()."')";
                              $strSqlSku ="INSERT INTO `aliexpress_product_sku` (`product_id`, `ap_id`, `sku_price`, `sku_code`, `sku_stock`, `ipm_sku_stock`, `merchant_sku_id`,`currency_code`, `create_time`,`update_time`) VALUES ".$strSqlSku.";";
                          }
                          Db::execute($strSqlSku);
                      }
                      
                     //最后一步、删除该SKU属性下面的所有SKU值，然后重新加入
                     if(isset($sku['aeopSKUProperty']) && count($sku['aeopSKUProperty'])>0 && isset($sku['skuCode']))
                     {
                         $strDelSqlSkuVal ="DELETE FROM `aliexpress_product_sku_val` WHERE `ap_id`=".$Product->id." AND sku_code='".$sku['skuCode']."';";
                         Db::execute($strDelSqlSkuVal);
                         foreach ($sku['aeopSKUProperty'] as $skuVal)
                         {
                             $strSqlSkuVal = '';
                             $strSqlSkuVal ="('" . $Product->id."','"
                                 .$Product->product_id."','"
                                 .(isset($sku['skuCode'])?$sku['skuCode']:'')."','"
                                 .(isset($skuVal['skuPropertyId'])?$skuVal['skuPropertyId']:0)."','"
                                 .(isset($skuVal['propertyValueId'])?$skuVal['propertyValueId']:0)."','"
                                 .(isset($skuVal['propertyValueDefinitionName'])?$skuVal['propertyValueDefinitionName']:'')."','"
                                 .(isset($skuVal['skuImage'])?$skuVal['skuImage']:'')."','"
                                 .time()."','"
                                 .time()."')";
                             $strSqlSkuVal ="INSERT INTO `aliexpress_product_sku_val` (`ap_id`, `product_id`, `sku_code`, `sku_property_id`, `property_value_id`, `property_value_definition_name`, `sku_image`,`create_time`, `update_time`) VALUES ".$strSqlSkuVal.";";
                             Db::execute($strSqlSkuVal);
                         }
                     }
                }
            }
        }
    }
}