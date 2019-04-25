<?php

/**
 * Description of AliexpressListingHelper
 * @datetime 2017-5-25  17:32:10
 * @author joy
 */

namespace app\listing\service;
use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressAccountBrand;
use app\common\model\aliexpress\AliexpressActionLog;
use app\common\model\aliexpress\AliexpressCategory;
use app\common\model\aliexpress\AliexpressCategoryAttr;
use app\common\model\aliexpress\AliexpressPublishTemplate;
use app\common\model\GoodsSku;
use app\common\service\UniqueQueuer;
use app\publish\queue\EbayListingExportQueue;
use app\publish\service\AliexpressService;
use app\publish\service\GoodsImage;
use app\listing\queue\AliexpressListingUpdateQueue;
use app\publish\service\AliProductHelper;
use app\publish\service\ExpressHelper;
use app\listing\validate\AliexpressListingValidate;
use app\publish\task\AliexpressProductTemplate;
use think\Exception;
use service\aliexpress\AliexpressApi;
use app\publish\service\AliexpressTaskHelper;
use app\common\model\aliexpress\AliexpressWindow;
use app\common\model\aliexpress\AliexpressProductGroup;
use app\common\model\aliexpress\AliexpressProduct;
use app\common\model\aliexpress\AliexpressProductSku;
use app\common\exception\JsonErrorException;


class AliexpressListingHelper {
    protected $AliexpressProductModel;
    protected $AliexpressWindowModel;
    protected $AliexpressProductGroupModel;

    /**
     * AliexpressListingHelper constructor
     */
    public function __construct()
    {
        if(is_null($this->AliexpressProductModel))
        {
            $this->AliexpressProductModel = new  AliexpressProduct;
        }
        if(is_null($this->AliexpressWindowModel))
        {
            $this->AliexpressWindowModel = new AliexpressWindow;
        }
        
        if(is_null($this->AliexpressProductGroupModel))
        {
            $this->AliexpressProductGroupModel = new AliexpressProductGroup;
        }
        
    }

    public function rsyncListing($uid,$product_id)
    {
        $data=[
            'create_id'=>$uid,
            'type'=>9,
            'old_data'=>'',
            'new_data'=>'同步线上数据',
            'product_id'=>$product_id,
            'create_time'=>time(),
        ];
        $where['product_id']=['=',$product_id];
        return $this->saveAliexpressActionLog($where,$data);
    }

    public function getSameSpuCategoryService($id)
    {
        $product = (new AliexpressProduct())->alias('a')->join('aliexpress_category b','a.category_id=b.category_id')->field('category_name_zh,id,a.category_id')->where('id',$id)->find();

        if(empty($product))
        {
            throw new JsonErrorException("商品不存在");
        }

        $parentCategory = AliexpressCategory::getAllParent($product['category_id']);
        $name="";
        if ($parentCategory)
        {
            foreach ($parentCategory as $item)
            {
                $name=$name.$item['category_name'].">>";
            }
        }

        $product['category_name_zh']=substr($name,0,strlen($name)-2);

        return $product;

    }

    /**
     *
     * @param $id
     * @param $intGoodsId
     * @param $intAccountId
     * @return array
     *
     */
    public  function getSameSpuAttribute($id,$intGoodsId,$intAccountId)
    {
        $helper =  new ExpressHelper();

        $product = (new AliexpressProduct())->with(['productInfo'])->field('group_id,freight_template_id,id,category_id,promise_template_id')->where('id',$id)->find();

        if(empty($product))
        {
            throw new JsonErrorException("商品信息不存在");
        }

        $arrProduct = $product->toArray();

        $category_id = $arrProduct['category_id'];

        //产品普通属性
        $arrAttr = json_decode($arrProduct['product_info']['product_attr'],true);

        $arrAttr = (new AliProductHelper())->bulidAttrData($category_id,$arrAttr);

        if(isset($arrAttr['brand']))
        {
            $brand_id= $arrAttr['brand'];
        }else{
            $brand_id=0;
        }

        $arrAttr = isset($arrAttr['ali_attr'])?$arrAttr['ali_attr']:[];


        // 第一步、获取速卖通该分类下面的所有类目属性和SKU属性(除品牌)

        $objAliexpressCategoryAttr = AliexpressCategoryAttr::where(['category_id'=>$category_id,'id'=>['neq',2]])
            ->field('id,required,spec,names_zh,names_en,sku,units,attribute_show_type_value,customized_pic,customized_name,list_val')
            ->select();

        $arrAliexpressCategoryAttr = $helper->ObjToArray($objAliexpressCategoryAttr);

        $publishAttr = (new AliexpressPublishTemplate())->where(['channel_category_id'=>$category_id,'goods_id'=>$intGoodsId])->find();

        if($publishAttr)
        {
            $publishAttrData = json_decode($publishAttr['data'],true);
        }else{
            $publishAttrData =[];
        }


        $arrSku = [];//SKU属性

        foreach($arrAliexpressCategoryAttr as $arrValue)
        {
            if($arrValue['sku'])
            {
                $arrValue['used_vaules'] = [];
                $arrSku[$arrValue['id']] = $arrValue;
            }
        }

        $goodsInfomation = Cache::store('goods')->getGoodsInfo($intGoodsId);

        if(empty($goodsInfomation))
        {
            throw new JsonErrorException("商品不存在");
        }

        $develop_id = $goodsInfomation['channel_id'];

        //第二步、获取该产品本地的SKU属性
        $where = [
            'goods_id' => $intGoodsId,
            'status' => ['in', [1,4]],
        ];
        $objGoodsListing = $helper->GoodsSkuAttrJsonToArray(GoodsSku::where($where)->select());
        $arrGoodsListing = $helper->ObjToArray($objGoodsListing);


        //拼装SKU生成Listing部分数据
        $arrSkuData =  $helper->getSkuInfo($arrSku,$arrGoodsListing);

        $images = GoodsImage::getPublishImages($intGoodsId,4);

        $spuImage = $images['spuImages'];

        $skuImages = $images['skuImages'];

        $arrSkuData['listing'] = GoodsImage::replaceSkuImage($arrSkuData['listing'],$skuImages,4,'id');

        if($arrSkuData['listing'])
        {
            foreach ($arrSkuData['listing'] as &$sku)
            {
                $sku['ipm_sku_stock']=0;
                $sku['d_imgs'] = GoodsImage::getSkuImagesBySkuId($sku['id'],4,$develop_id);
            }
        }

        //根据分类及账号ID获取相应品牌信息
        $arrBrand = AliexpressAccountBrand::getBrandByAccount($intAccountId,$category_id);

        $diyAttr = $helper->getSelfDefineAttr($publishAttrData);

        $aeopNationalQuoteConfiguration = $helper->getQuoteCountry();

        $brandRequried=AliexpressCategoryAttr::where(['category_id'=>$category_id,'id'=>2])->field('required')->find();

        if($brandRequried)
        {
            $brand_required=$brandRequried['required'];
        }else{
            $brand_required=0;
        }


        //营销图
        $is_market_image = (new ExpressHelper())->marketImageCheck($category_id);

        //开始拼接返回的数据
        $arrReturnData = [
            'brand'=>$arrBrand, //品牌数据
            'brand_required'=>$brand_required,
            'attr_info'=>$arrAttr,    //普通属性
            'listing_info'=>array_values($arrSkuData['listing']),  //listing
            'sku_attr_info'=>array_values($arrSkuData['ali_attr']),    //sku属性array_values($arrSkuData['local_attr'])
            'local_attr'=>array_values($arrSkuData['local_attr']),
            'imgs'=>$spuImage,
            'aeopNationalQuoteConfiguration'=>$aeopNationalQuoteConfiguration,
            'quote_config_status'=>0,
            'brand_id'=>$brand_id,
            'promise_template_id'=>$product['promise_template_id'],
            'freight_template_id'=>$product['freight_template_id'],
            'group_id'=>$product['group_id'],
            'is_market_image' => $is_market_image
        ];
        return $arrReturnData;
    }

    /**
     * 获取已刊登相同spu
     * @param $spu
     * @param $account_id
     */
    public function getSameSpuService($account_id)
    {
        try{
            $where['account_id']=['=',$account_id];
            $where['goods_id']=['>',0];
            $where['goods_spu']=['<>',''];
            return (new AliexpressProduct())->field('id,goods_spu')->where($where)->select();
        }catch (JsonErrorException $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }
    /**
     * 查询操作日志
     * @param array $param
     * @param int $page
     * @param int $pageSize
     * @param string $fields
     * @return array
     */
    public function getLogs($param=[], $page=1, $pageSize=30, $fields='*')
    {
        $where= [
            'product_id'=>['=',$param['product_id']],
        ];

        $const =[
            'subject'=>'刊登标题', //刊登标题
            'delivery_time'=>'备货期',//发货期
            'category_id'=>'分类ID', //分类ID
            'product_price'=>'商品一口价',
            'group_id'=>'产品分组ID',
            'product_unit'=>'商品单位',
            'package_type'=>'打包销售',
            'lot_num'=>'每包件数',
            'package_length'=>'商品包装长度',
            'package_width'=>'商品包装宽度',
            'package_height'=>'商品包装高度',
            'isPackSell'=>'是否自定义计重',
            'base_unit'=>'购买几件以内不增加运费',
            'add_unit'=>'每增加件数',
            'add_weight'=>'增加的重量',
            'ws_valid_num'=>'商品有效天数',
            'bulk_order'=>'批发最小数量',
            'bulk_discount'=>'批发折扣',
            'reduce_strategy'=>'库存扣减策略',
            'freight_template_id'=>'运费模版ID',
            'gross_weight'=>'商品毛重',
            'price'=>'售价',
            'stock'=>'库存',
            'package'=>'包装',
            'template'=>'信息模板',
            'promise_template_id'=>'服务模板ID',
            'imageurls'=>'图片URL',
            'productSkus'=>'SKU属性',
            'productProperties'=>'商品属性',
            'detail'=>'详情描述',
            'mobile_detail'=>'手机详情',
        ];


        $count = (new AliexpressActionLog())->where($where)->count();

        $data = (new AliexpressActionLog())->order('create_time desc')->with(['user'=>function($query){$query->field('id,realname');}])->where($where)->page($page,$pageSize)->select();

        if($data)
        {
            foreach ($data as &$d)
            {
                if(is_array($d['new_data']))
                {
                    $log='';

                    foreach ($d['new_data'] as $name=>$v)
                    {
                        if($name!='sku')
                        {
                            if(isset($const[$name]))
                            {
                                $log=$log.$const[$name].':由['.$d['old_data'][$name].']改为['.$d['new_data'][$name].']'.'<br />';
                            }else{
                                $log=$log.$name.':由['.$d['old_data'][$name].']改为['.$d['new_data'][$name].']'.'<br />';
                            }
                        }else{
                            $log=$log.'修改了SKU信息';
                        }

                    }
                }else{
                    $log=$d['new_data'];
                }
                $d['log']=$log;
            }
        }

        return ['data'=>$data,'count'=>$count,'page'=>$page,'pageSize'=>$pageSize];
    }

    /**
     * 更新速卖通产品信息
     * @param array $data
     * @param array $where
     */
    public function updateProductInfo($data,$where)
    {
        AliexpressProduct::where($where)->update($data);
        //$this->AliexpressProductModel->isUpdate(true)->save($data,$where);
    }

    /**
     * 获取橱窗详情
     * @param int $id 橱窗id
     */
    public  function getWindowDetail($id)
    {
        $detail = $this->AliexpressWindowModel->field('id,window_count,used_count,(window_count-used_count) left_count,account_id,window_products')->with(['account'=>function($query){$query->field('id,code,account_name');}])->where('id','=',$id)->find();
        if($detail && is_object($detail))
        {
            $detail = $detail->toArray();
            $products= json_decode($detail['window_products'],true);

            if($products && isset($products['windowproducts']) && $products['windowproducts']) {
                $products = $products['windowproducts'];
            }
            
            $list=[];
            foreach ($products as $key => $product) 
            {

                $info =$this->AliexpressProductModel
                        ->field('group_id,product_id,subject,imageurls,product_price,goods_spu,bulk_discount')
                        ->where('product_id','=',$product['productId'])
                        ->find();

                if($info && is_object($info))
                {
                    $info = $info->toArray();
                     
                    $group_ids = json_decode($info['group_id'],true);
                    
                    $group_name='&';
                    if(is_array($group_ids))
                    {
                        foreach($group_ids as $group_id)
                        {
                            $groupInfo = $this->AliexpressProductGroupModel->where('group_id','=',$group_id)->find();
                            $group_name.=$group_name.$groupInfo['group_name'];
                        }
                        $group_name = substr($group_name, 1);
                    }else{
                        $groupInfo = $this->AliexpressProductGroupModel->where('group_id','=',$group_ids)->find();
                        $group_name.=$group_name.$groupInfo['group_name'];
                    }
                    $info['group_name'] = $group_name;
                    $info['bulk_price'] = $info['product_price'] * (100-$info['bulk_discount'])/100;
                    $list[] = array_merge($product,$info); 
                } 
            }
            $detail['list']=$list;
        }
        return $detail;
    }
    /**
     * 获取橱窗列表
     * @param type $page
     * @param type $pageSize
     * @param type $param
     * @return type
     */
    public  function getWindowList($page=1,$pageSize=30,$param=[])
    {
        $where=[];
        
        if(isset($param['account_id']) && $param['account_id'])
        {
            $where['account_id'] =['=',$param['account_id']];
        }

        if(isset($param['left_count']) && is_numeric($param['left_count']))
        {
            if($param['left_count']==0)
            {
               $exp='(window_count - used_count) =0 ';
            }else{
               $exp='(window_count - used_count) >0 '; 
            } 
            
            $count = $this->AliexpressWindowModel->where($where)->where($exp)->count();

            $data = $this->AliexpressWindowModel->where($exp)->field('*')->with(['account'=>function($query){$query->with(['channer'=>function($query){$query->with(['user'=>function($query){$query->field('realname,id');}])->where(['channel_id'=>4]);}])->field('id,code,account_name');}])->where($where)->page($page,$pageSize)->select();
          
        }else{

           $count = $this->AliexpressWindowModel->where($where)->count();

           $data = $this->AliexpressWindowModel->field('*')
               ->with(['account'=>function($query){$query->with(['channer'=>function($query){$query->with(['user'=>function($query){$query->field('realname,id');}])->where(['channel_id'=>4]);}])->field('id,code,account_name');}])
               ->where($where)->page($page,$pageSize)->select();

        }
      
        return ['data'=>$data,'page'=>$page,'pageSize'=>$pageSize,'count'=>$count];
    }
    /**
     * 延迟商品有效期
     * @param int $product_id
     * @return boolean
     */
    public function renewExpire ($product_id)
    {
        if(empty($product_id))
        {
             return ['result'=>false,'message'=>'商品id不能为空'];
        }      
        $product = $this->AliexpressProductModel->where(['product_id'=>$product_id])->find();       
        if($product)
        {
            $account = $product->account->toArray(); 
            $product = $product->toArray();
            $api = AliexpressApi::instance($account)->loader('Product');
            $response = $api->renewExpire (['productId'=>$product_id]);
           
            if(isset($response['modifyResponse']['isSuccess']) && $response['modifyResponse']['isSuccess'])
            {
                return ['result'=>true,'message'=>$response];
            }else{
               return ['result'=>false,'message'=>$response];
            }  
        }else{
            return ['result'=>false,'message'=>'商品不存在'];
        }  
    }
    /**
     * 下架商品
     * @param int $product_id
     * @return boolean
     */
    public function offlineAeProduct($product_id)
    {
        if(empty($product_id))
        {
            return false;
        }      
        $product = $this->AliexpressProductModel->where(['product_id'=>$product_id])->find();

        if($product)
        {
            $account = $product->account->toArray();
            $product = $product->toArray();
            $api = AliexpressApi::instance($account)->loader('Product');
            $response = $api->offlineAeProduct(['productIds'=>$product_id]);
            if(isset($response['success']) && $response['success'])
            {
                return true;
            }else{
                return false;
            }  
        }else{
            return false;
        }  
    }
    
    /**
     * 上架商品
     * @param int $product_id
     * @return boolean
     */
    public function onlineAeProduct($product_id)
    {
        if(empty($product_id))
        {
            return false;
        }      
        $product = $this->AliexpressProductModel->where(['product_id'=>$product_id])->find();       
        if($product)
        {
            $account = $product->account->toArray(); 
            $product = $product->toArray();
            $api = AliexpressApi::instance($account)->loader('Product');
            $response = $api->onlineAeProduct(['productIds'=>$product_id]);
            if(isset($response['success']) && $response['success'])
            {
                return true;
            }else{
                return false;
            }  
        }else{
            return false;
        }  
    }
    /**
     * 商品编辑接口
     * @param type $job
     * @return type
     */
    public  function editAeProduct($job)
    {
        //商品信息基本
        $model_info = $this->AliexpressProductModel ->where(['product_id'=>$job,'lock_update'=>1])->find();      

        if($model_info)
        {
	        //账号
	        $accountInfo = $model_info->account->toArray();

	        //商品sku
	        $skus = $model_info->productSku;

	        $api = AliexpressApi::instance($accountInfo);

	        $server  = $api->loader('Product');

	        $code = $accountInfo['code'];

            $product = $model_info->toArray();

	        if($productImage = $model_info->getData('imageurls'))
	        {
		        $product['imageurls']=$productImage['imageurls'];
	        }


             //商品信息
            $product_info = $model_info->productInfo->toArray();

	        //处理描述详情里面的图片

	        if(isset($product_info['detail']) && $product_info['detail'])
	        {
		        $detail=$product_info['detail'];
		        $detail = (new AliexpressTaskHelper())->managerDetail($detail,$api,$code);
	        }else{
		        $detail='';
	        }

	        if($product['relation_template_id']>0 || $product['custom_template_id']>0)
	        {
		        $detail = (new AliexpressTaskHelper())->combineRelationCustomDescription($product,$detail);
	        }else{
		        $detail=$detail;
	        }

	        //处理关联信息模板

	        $mobileDetail =$product_info['mobile_detail']?(new AliexpressTaskHelper())->managerMobileDetail($product_info['mobile_detail'],$api):'';


	        $aeopAeProductSKUs=[];
            if($skus)
            {
                foreach ($skus as $k=>$sku)
                {
                    $sku = $sku->toArray();
                    $aeopAeProductSKUs[$k]['skuPrice']=$sku['sku_price'];
                    $aeopAeProductSKUs[$k]['skuCode']=$sku['sku_code'];
                    $aeopAeProductSKUs[$k]['skuStock']=$sku['sku_stock']?true:false;
                    $aeopAeProductSKUs[$k]['ipmSkuStock']=$sku['ipm_sku_stock'];
                    $aeopAeProductSKUs[$k]['currencyCode']=$sku['currency_code'];
                    $aeopSKUProperty=[];
                    $skuAttrVal = json_decode($sku['sku_attr'],true);
                    if($skuAttrVal)
                    {
                        foreach ($skuAttrVal as $k1=>$val)
                        {
                            $aeopSKUProperty[$k1]['skuPropertyId']=$val['skuPropertyId'];
                            $aeopSKUProperty[$k1]['propertyValueId']=$val['propertyValueId'];

                            if(isset($val['propertyValueDefinitionName']) && $val['propertyValueDefinitionName'])
                            {
                                $aeopSKUProperty[$k1]['propertyValueDefinitionName']=$val['propertyValueDefinitionName'];
                            }       

	                        if(isset($val['skuImage']) && $val['skuImage'])
	                        {
	                        	if(strpos($val['skuImage'],'alicdn.com')==false)
		                        {
			                        $skuImage = (new AliexpressTaskHelper())->uploadOneImage($api,$val['skuImage'],$code);
			                        if($skuImage)
			                        {
				                        $aeopSKUProperty[$k1]['skuImage']=$skuImage;
			                        }
		                        }else{
			                        $aeopSKUProperty[$k1]['skuImage']=$val['skuImage'];
		                        }
	                        }
                        }
                    }

                    $aeopAeProductSKUs[$k]['aeopSKUProperty']=$aeopSKUProperty;
                }
            }     
//            $post=[
//                'productId'=>$product['product_id'],//产品ID
//                'subject'=>$product['subject'], //标题
//                'detail'=>$product_info['detail'], //详情描述
//                'aeopAeProductSKUs'=>json_encode($aeopAeProductSKUs), //sku
//                'aeopAeProductPropertys'=> $product_info['product_attr'],//产品属性，以json格式进行封装后提交
//                'deliveryTime'=>$product['delivery_time'], //备货期。取值范围:1-60;单位:天
//                'promiseTemplateId'=>$product['delivery_time'], //服务模板设置
//                'categoryId'=>$product['category_id'], //分类id
//                'productPrice'=>$product['product_price'], //商品一口价
//                'freightTemplateId'=>$product['freight_template_id'], //运费模版ID
//                'imageURLs'=>AliexpressTaskHelper::uploadImage($api,$product['imageurls']), //产品的主图URL列表
//                'productUnit'=>$product['product_unit'], //商品单位 (存储单位编号)
//                'packageType'=>$product['package_type']?true:false, //打包销售: true 非打包销售:false
//                'lotNum'=>$product['lot_num'], //每包件数。 打包销售情况，lotNum>1,非打包销售情况,lotNum=1
//                'packageLength'=>$product['package_length'],
//                'packageWidth'=>$product['package_width'],
//                'packageHeight'=>$product['package_height'],
//                'grossWeight'=>$product['gross_weight'],
//                'isPackSell'=>$product['is_pack_sell']?true:false, //是否自定义计重.true为自定义计重,false反之
//                'baseUnit'=>$product['base_unit'],
//                'addUnit'=>$product['add_unit'],
//                'addWeight'=>$product['add_weight'],
//                'wsValidNum'=>$product['ws_valid_num'], //商品有效天数。取值范围:1-30,单位:天
//                'bulkOrder'=>$product['bulk_order'], //批发最小数量
//                'bulkDiscount'=>$product['bulk_discount'], //批发折扣,取值范围:1-99
//                'sizechartId'=>$product['sizechart_id']?$product_info['sizechart_id']:'', //尺码表模版ID
//                'reduceStrategy'=>$product['reduce_strategy'], //库存扣减策略，总共有2种：下单减库存(place_order_withhold)和支付减库存(payment_success_deduct)。
//                'groupIds'=> explode(',', $product['group_id']),//产品分组ID
//                'currencyCode'=>$product['currency_code'],//货币单位
//                'mobileDetail'=>$product_info['mobile_detail'],
//                'couponStartDate'=>$product['coupon_start_date']?$product['coupon_start_date']:'', //卡券商品开始有效期
//                'couponEndDate'=>$product['coupon_end_date']?$product['coupon_end_date']:'', //卡券商品结束有效期
//                'Others'=>''
//            ];

	        $post=[
		        'productId'=>$product['product_id'],//产品ID
		        'subject'=>$product['subject'], //标题
		        'detail'=>$detail, //详情描述
		        'aeopAeProductSKUs'=>json_encode($aeopAeProductSKUs), //sku
		        'aeopAeProductPropertys'=> $product_info['product_attr'],//产品属性，以json格式进行封装后提交
		        'categoryId'=>$product['category_id'], //分类id
		        'imageURLs'=>(new AliexpressTaskHelper)->uploadImage($api,$product['imageurls'],$code), //产品的主图URL列表
	        ];
	        //备货期。取值范围:1-60;单位:天
	        if($product['delivery_time'])
	        {
		        $post['deliveryTime'] = $product['delivery_time'];
	        }
	        //服务模板设置
	        if($product['promise_template_id'])
	        {
		        $post['promiseTemplateId'] = $product['promise_template_id'];
	        }

	        ////商品一口价
	        if((float)$product['product_price'])
	        {
		        $post['productPrice'] = number_format($product['product_price'],2);
	        }
	        //运费模版ID
	        if($product['freight_template_id'])
	        {
		        $post['freightTemplateId'] = $product['freight_template_id'];
	        }
	        //商品单位 (存储单位编号)
	        if($product['product_unit'])
	        {
		        $post['productUnit'] = $product['product_unit'];
	        }
	        //打包销售: true 非打包销售:false,
	        if($product['package_type'])
	        {
		        $post['packageType'] = 'true';
		        //每包件数。 打包销售情况，lotNum>1,非打包销售情况,lotNum=1
		        $post['lotNum']=$product['lot_num'];
	        }else{
		        $post['packageType'] = 'false';
		        $post['lotNum']=1;
	        }
	        //包装长宽高
	        if($product['package_length'])
	        {
		        $post['packageLength'] = $product['package_length'];
	        }
	        if($product['package_width'])
	        {
		        $post['packageWidth'] = $product['package_width'];
	        }
	        if($product['package_height'])
	        {
		        $post['packageHeight'] = $product['package_height'];
	        }

	        if($product['gross_weight'])
	        {
		        $post['grossWeight'] = $product['gross_weight'];
	        }

	        if($product['is_pack_sell'])
	        {
		        $post['isPackSell'] = 'true';
		        $post['baseUnit'] = $product['base_unit'];
		        $post['addUnit']=$product['add_unit'];
		        $post['addWeight']=$product['add_weight'];
	        }else{
		        $post['isPackSell'] = 'false';
	        }
	        //商品有效天数。取值范围:1-30,单位:天
	        if($product['ws_valid_num'])
	        {
		        $post['wsValidNum']=$product['ws_valid_num'];
	        }
	        //批发最小数量，批发折扣,取值范围:1-99
	        if($product['bulk_order'] && $product['bulk_discount'])
	        {
		        $post['bulkOrder']=$product['bulk_order'];
		        $post['bulkDiscount']=$product['bulk_discount'];
	        }
	        //尺码表模版ID
	        if($product['sizechart_id'])
	        {
		        $post['sizechartId']=$product['sizechart_id'];
	        }
	        //库存扣减策略，总共有2种：下单减库存(place_order_withhold)和支付减库存(payment_success_deduct)。
	        if($product['reduce_strategy']==1)
	        {
		        $post['reduceStrategy']='place_order_withhold';
	        }elseif($product['reduce_strategy']==2){
		        $post['reduceStrategy']='payment_success_deduct';
	        }
	        //产品分组ID
	        if($product['group_id'])
	        {
		        $post['groupId']=$product['group_id'];
	        }
	        ////货币单位
	        if($product['currency_code'])
	        {
		        $post['currencyCode']=$product['currency_code'];
	        }
	        //
	        if($mobileDetail)
	        {
		        $post['mobileDetail']=$mobileDetail;
	        }
	        ////卡券商品开始有效期
	        if($product['coupon_start_date'])
	        {
		        $post['couponStartDate']=$product['coupon_start_date'];
	        }
	        //卡券商品结束有效期
	        if($product['coupon_end_date'])
	        {
		        $post['couponEndDate']=$product['coupon_end_date'];
	        }

	        $response = $server->editAeProduct($post);
            return $response;
        }
    }
    /**
     * 判断商品是否属于同一个账号
     * @param type $products
     */
    public  function sameAccount(array $products)
    {
        $list=[];
        foreach ($products as $key => $product) 
        {
            $info = $this->AliexpressProductModel->field('id,account_id,product_id')->where(['id'=>$product['id']])->find();
            if($info && is_object($info))
            {
                $info = $info->toArray();
            }
            $list[]=$info;
        }
        if($list && count($list) >1 )
        {
            $total = count($list);
            for($i=1;$i<$total ;++$i)
            {
                if($list[$i]['account_id'] !=  $list[$i-1]['account_id'])
                {
                    return ['result'=>false,'message'=>'商品id['.$list[$i]['product_id'].']的账号其它商品的账号不是同一个账号'];
                }
            }
        }
        return ['result'=>true,'message'=>'没有异常'];
    }
    
    /**
     * 修改产品数据
     */
    public  function editWindowData($data,$scene,$uid)
    {
         
        $products = json_decode($data,true);
        
        $validate = new AliexpressListingValidate;
         
        if($error = $validate->checkEdit($products,$scene))
        {
            return ['result'=>false,'message'=>$error];
        }
        $timestamp = time();
        
        $res = $this->sameAccount($products);
        
        $model = new AliexpressWindow;
        
        if(!$res['result'])
        {
            return $res;
        }else{
            $totalNum = count($products);
            
            foreach ($products as $key => $product) 
            {
               $account_id = $product['account_id']; 
               $dataInfo = $model->get(['account_id'=>$account_id]);
               
               if($dataInfo)
               {
                   $dataInfo = is_object($dataInfo)?$dataInfo->toArray():$dataInfo;
                   
                   if($dataInfo['window_count'] == $dataInfo['used_count'])
                   {
                       return ['result'=>false,'message'=>'橱窗数量已经用完'];
                   }elseif(($totalNum + $dataInfo['used_count'])>$dataInfo['window_count']){
                       $leftNum = $dataInfo['window_count'] - $dataInfo['used_count'];
                       return ['result'=>false,'message'=>'橱窗数量不够，当前剩余数量为['.$leftNum.']'];
                   }
                   
               }else{
                   return ['result'=>false,'message'=>'卖家没有可用橱窗'];
               }
               $now_used_count = $totalNum + $dataInfo['used_count'];
               $productList[]=$product['product_id'];
            } 
             
            if($dataInfo['product_list'])
            {
                $productList = array_merge($productList, json_decode($dataInfo['product_list'],true));
            }
            
            
        }
        
        try{
            $model->isUpdate(true)->save(['product_list'=> json_encode($productList),'status'=>0,'used_count'=>$now_used_count],['account_id'=>$account_id]);
            $message="设置橱窗商品成功，稍后自动执行";
            return ['result'=>true,'message'=>$message];
        } catch (Exception $exp){
            $message = $exp->getMessage();
            return ['result'=>false,'message'=>$message];
        }
    }
    
    /**
     * 修改产品数据
     */
    public  function editSkuData($data,$scene,$uid,$remark='',$cron_time=0)
    {
        try{
            $products = json_decode($data,true);

            $validate = new AliexpressListingValidate;

            if($error = $validate->checkEdit($products,$scene))
            {
                return ['result'=>false,'message'=>$error];
            }
            $timestamp = time();

            $apis = include  APP_PATH.'aliexpress.api.php';

            foreach ($products as $key => &$product)
            {
                $where=[
                    'product_id'=>['=',$product['product_id']],
                    'sku_code'=>['=',$product['sku']],
                ];

                $row = AliexpressProductSku::where($where)->field('product_id,merchant_sku_id')->find();
                if($row)
                {
                    $product_id = $row['product_id'];

                    $new_data=[$scene=>$product[$scene]];

                    $old_data=[$scene=>$product['old_'.$scene]];

                    $map=[
                        'product_id'=>['=',$product_id],
                        'variant_id'=>['=',$row['merchant_sku_id']],
                        'new_data'=>['=',json_encode($new_data)],
                        'create_id'=>['=',$uid],
                        'status'=>['=',0],
                    ];
                    $log=[
                        'product_id'=>$product_id,
                        'variant_id'=>$row['merchant_sku_id'],
                        'type'=>$apis[$scene],
                        'old_data'=>json_encode($old_data),
                        'new_data'=>json_encode($new_data),
                        'create_id'=>$uid,
                        'create_time'=>time(),
                        'remark'=>$remark,
                        'cron_time'=>is_string($cron_time)?strtotime($cron_time):0,
                    ];

                    if($this->saveAliexpressActionLog($map,$log))
                    {
                        AliexpressProduct::where('product_id','=',$product_id)->update(['lock_update'=>1]);
                    }
                }else{
                    throw new JsonErrorException("没有SKU:{$product['sku']}的记录");
                }

            }
            $message="提交成功，系统在处理中，稍后刷新页面";
            return ['result'=>true,'message'=>$message];



//            if($scene=='stock')
//            {
//                $model = new AliexpressSkuStock;
//                $class = AliexpressSkuStock::class;
//            }elseif($scene=='price'){
//                $model = new AliexpressSkuPrice;
//                $class = AliexpressSkuPrice::class;
//            }


//            try{
//                $model ->saveAll($products,true);
//                //time_partition($class, $timestamp, 'create_time');
//                if($scene=='stock')_i
//                {
//                    foreach ($products as &$product)
//                    {
//                        AliexpressProductSku::where(['product_id'=>$product['product_id'],'sku_code'=>$product['sku']])->update(['ipm_sku_stock'=>$product['stock']]);
//
//                    }
//                }elseif($scene=='price'){
//
//
//                    foreach ($products as &$product)
//                    {
//                        AliexpressProductSku::where(['product_id'=>$product['product_id'],'sku_code'=>$product['sku']])->update(['sku_price'=>$product['price']]);
//                    }
//
//                    $product_ids=$this->array_unique_fb($products);
//
//                    foreach ($product_ids as &$product_id)
//                    {
//                        $this->updateProductMaxMinPrice($product_id);
//                    }
//
//                }
//                $message="修改成功";
//                return ['result'=>true,'message'=>$message];
//            } catch (Exception $exp){
//                $message = $exp->getMessage();
//                return ['result'=>false,'message'=>$message];
//            }
        }catch (JsonErrorException $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }
    function array_unique_fb($products)
    { 
        $temp=[];
        foreach($products as $product)
        {
            if(!in_array($product['product_id'], $temp))
            {
                $temp[] = $product['product_id'];
            }
        }
        return $temp;
    }
    /**
     * 更新商品的最大最小售价
     * @param bingint $product_id
     */
    public function updateProductMaxMinPrice($product_id)
    {
        try{
            $max_price =AliexpressProductSku::where('product_id','=',$product_id)->max('sku_price');
            $min_price =AliexpressProductSku::where('product_id','=',$product_id)->min('sku_price');
            $product_price=$max_price>$min_price?$max_price:$min_price;

            $update=[          
                'product_price'=>$product_price,
                'product_min_price'=>$min_price,
                'product_max_price'=>$max_price,
            ];
            AliexpressProduct::where('product_id','=',$product_id)->update($update);
        } catch (JsonErrorException $ex) {
            throw new JsonErrorException($ex->getFile().$ex->getLine().$ex->getMessage());
        }
        
    }

    /**
     * 修改产品数据
     */
    public  function editProductData($data,$scene,$uid=0,$remark='',$cron_time=0)
    {

        try{
            $products = json_decode($data,true);

            $apis = include APP_PATH.'aliexpress.api.php';

            switch($scene)
            {
                case 'title':
                    $scene = 'subject';
                    $api='subject';
                    break;
                case 'promiseTemp':
                    $scene = 'promise_template_id';
                    $api='promiseTemplateId';
                    break;
                case 'freightTemp':
                    $scene = 'freight_template_id';
                    $api='freightTemplateId';
                    break;
                case 'weight':
                    $scene = 'gross_weight';
                    $api='subject';
                    break;
                case 'deliveryTime':
                    $scene = 'delivery_time';
                    $api='deliveryTime';
                    break;
                case 'package_length':
                    $scene = 'package_length';
                    $api='packageLength';
                    break;
                case 'package_width':
                    $scene = 'package_width';
                    $api='packageWidth';
                    break;
                case 'package_height':
                    $scene = 'package_height';
                    $api='packageHeight';
                    break;
                case 'GroupId':
                    $api='GroupId';
                    $scene='group_id';
                    break;
                default:
                    //$api='';
                    break;
            }

            $validate = new AliexpressListingValidate;
            foreach ($products as $product)
            {
                if(isset($product['relation_template_id']) || isset($product['custom_template_id']))
                {
                    if(empty($product['relation_template_id']) && empty($product['custom_template_id']))
                    {
                        return ['result'=>false,'message'=>'关联产品模块和自定义信息模块不能同时为空'];
                    }
                }
            }
            if($scene=='template')
            {
                $res = $this->sameAccount($products);
                if(!$res['result'])
                {
                    return $res;
                }
            }

            foreach ($products as $key => $product)
            {

//                if(isset($product['group_id']))
//                {
//                    $product['group_id'] = json_encode(explode(',', $product['group_id']));
//                }

                if(isset($product['id']))
                {
                    $row = (new AliexpressProduct())->where('id','=',$product['id'])->find();
                }elseif(isset($product['product_id'])){
                    $row = (new AliexpressProduct())->where('id','=',$product['product_id'])->find();
                }else{
                    $row=[];
                }

                if($row)
                {
                    $row = $row->toArray();

                    $product_id = $row['product_id'];

                    if(isset($product['group_id']))
                    {
                        if(is_array(json_decode($row[snake($scene)],true)))
                        {
                            $old_data=[$scene=>implode(',',json_decode($row[snake($scene)],true))];
                        }else{
                            $old_data=[$scene=>$row[snake($scene)]];
                        }
                    }else{
                        $old_data=[$scene=>$row[snake($scene)]];
                    }


                    if(isset($product[snake($scene)]))
                    {
                        $new_data[$scene]=$product[snake($scene)];
                    }else{
                        $new_data[$scene]=$product['value'];
                    }

                    $map=[
                        'product_id'=>['=',$product_id],
                        'new_data'=>['=',json_encode($new_data)],
                        'create_id'=>['=',$uid],
                        'status'=>['=',0],
                    ];
                    $log=[
                        'product_id'=>$product_id,
                        'type'=>isset($apis[$api])?$apis[$api]:4,
                        'old_data'=>json_encode($old_data),
                        'new_data'=>json_encode($new_data),
                        'create_id'=>$uid,
                        'create_time'=>time(),
                        'remark'=>$remark,
                        'cron_time'=>is_string($cron_time)?strtotime($cron_time):0,
                    ];

                    if($this->saveAliexpressActionLog($map,$log))
                    {
                        AliexpressProduct::where('product_id','=',$product_id)->update(['lock_update'=>1]);
                    }
                }
                //$product['lock_update'] =1 ; //更新了资料
                //$product['lock_product'] =1 ; //更新商品资料
            }

            //$this->AliexpressProductModel->allowField(true)->isUpdate(true)->saveAll($products);
            $message="修改成功";
            return ['result'=>true,'message'=>$message];
        }catch (JsonErrorException $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }

    public function getArrayData($product,$row)
    {
        $result=[];
        foreach ($product as $key=>$value)
        {
            $result[$key]=$row[$key];
        }
        return $result;
    }
    public  function editMulitProductData($products=[],$scene,$uid=0,$remark='',$cron_time=0)
    {

        try{
            $apis = include APP_PATH.'aliexpress.api.php';

            foreach ($products as $key => &$product)
            {

                if(isset($product['id']))
                {
                    $row = AliexpressProduct::where('id','=',$product['id'])->find();
                }elseif(isset($product['product_id'])){
                    $row = AliexpressProduct::where('id','=',$product['product_id'])->find();
                }

                if($row)
                {
                    $product_id = $row['product_id'];
                    unset($product['id']);
                    $new_data = $product;
                    $old_data=$this->getArrayData($product,$row);
                    $map=[
                        'product_id'=>['=',$product_id],
                        'new_data'=>['=',json_encode($new_data)],
                        'create_id'=>['=',$uid],
                        'status'=>['=',0],
                    ];

                    $log=[
                        'product_id'=>$product_id,
                        'type'=>$apis[$scene],
                        'old_data'=>json_encode($old_data),
                        'new_data'=>json_encode($new_data),
                        'create_id'=>$uid,
                        'create_time'=>time(),
                        'remark'=>$remark,
                        'cron_time'=>is_string($cron_time)?strtotime($cron_time):0,
                    ];

                    if($this->saveAliexpressActionLog($map,$log))
                    {
                        AliexpressProduct::where('product_id','=',$product_id)->update(['lock_update'=>1]);
                    }
                }
                //$product['lock_update'] =1 ; //更新了资料
                //$product['lock_product'] =1 ; //更新商品资料
            }

            //$this->AliexpressProductModel->allowField(true)->isUpdate(true)->saveAll($products);
            $message="修改成功";
            return ['result'=>true,'message'=>$message];
        }catch (JsonErrorException $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }

    /**
     * 商品上下架
     */
    public function onOffLineProductLog($product_id,$uid,$type,$cron_time=0,$remark='', $ip_address = '')
    {
        $apis = include APP_PATH.'aliexpress.api.php';
        $api=$apis[$type];
        if($type=='online')
        {
            $new_data='上架';
            $old_data='下架';
        }elseif($type=='offline'){
            $new_data='下架';
            $old_data='上架';
        }elseif($type=='renewExpire'){
            $new_data='延长商品有效期';
            $old_data='';
        }
        $where=[
            'create_id'=>['=',$uid,],
            'product_id'=>['=',$product_id],
            'status'=>['=',0],
            'type'=>['=',$api],
        ];

        $log=[
            'create_id'=>$uid,
            'product_id'=>$product_id,
            'new_data'=>$new_data,
            'old_data'=>$old_data,
            'type'=>$api,
            'create_time'=>time(),
            'cron_time'=>$cron_time,
            'remark'=>$remark,
            'ip_address' =>  $ip_address
        ];

        return $this->saveAliexpressActionLog($where,$log);
    }
    /**
     * 保存修改日志
     * @param $where
     * @param $data
     */
    public function saveAliexpressActionLog($where,$data)
    {
        try{
            $model = new AliexpressActionLog();
            if($has = AliexpressActionLog::where($where)->find())
            {
                $id = $has['id'];
                $return = AliexpressActionLog::where('id','=',$has['id'])->update($data);
            }else{

                $return = $model->insertGetId($data);
                $id = $return;
                //return AliexpressActionLog::insert($data);
            }

            if(isset($data['cron_time']) && $data['cron_time']){
                $cron_time = strtotime($data['cron_time']);
                (new UniqueQueuer(AliexpressListingUpdateQueue::class))->push($id,$cron_time);
            }else{
                (new UniqueQueuer(AliexpressListingUpdateQueue::class))->push($id);
            }

            return $return;
        }catch (JsonErrorException $exp){
            throw new JsonErrorException($exp->getFile().$exp->getLine().$exp->getMessage());
        }

    }
    /*
     * 获取速卖通在线listing是否修改了
     */
    public function getAeProductUpdateStatus($product_id,$status=0)
    {     
        return $this->AliexpressProductModel ->field('product_id')->where(['product_id'=>$product_id,'lock_update'=>$status])->find();
    }
    
     /*
     * 修改速卖通在线listing的修改状态
     */
    public function updateAeProductUpdateStatus($product_id,$status=0)
    {     
        return $this->AliexpressProductModel->where(['product_id'=>$product_id])->update(['lock_update'=>$status]);
    }



    public function findAeProductById($product_id)
    {
        if(empty($product_id))
        {
            return ['success'=>false,'message'=>'商品id为空'];
        }

        $product =$this->AliexpressProductModel ->where(['product_id'=>$product_id])->find();

        if($product)
        {
            $account = $product->account->toArray();
            $product = $product->toArray();
            $param['module']='product';
            $param['class']='Product';
            $param['action']='findaeproductbyid';
            //$param['action'] = 'productquery';
            $param['product_id']=$product_id;
            $params=array_merge($account,$param);
            $productDetail = AliexpressService::execute($params);

            if(isset($productDetail['productId']) && $productDetail['productId'])
            {
                $productData = $productInfoData = $productSkuData = [];
                $productId = $productDetail['productId'];
                $productData = [
                    'account_id'=>$account['id'],
                    'product_id'=>$productId,
                    'product_status_type'=>isset($productDetail['productStatusType'])?$productDetail['productStatusType']:'',//平台产品状态
                    'delivery_time'=>isset($productDetail['deliveryTime'])?$productDetail['deliveryTime']:0,//备货期限
                    'category_id'=>isset($productDetail['categoryId'])?$productDetail['categoryId']:0,//分类ID
                    'product_price'=>isset($productDetail['productPrice'])?$productDetail['productPrice']:0,//一口价
                    'product_unit'=>isset($productDetail['productUnit'])?$productDetail['productUnit']:'',//商品单位
                    'package_type'=>(isset($productDetail['packageType'])&&$productDetail['packageType'])?1:0,//是否打包销售
                    'lot_num'=>isset($productDetail['lotNum'])?$productDetail['lotNum']:0,//每包件数
                    'package_length'=>isset($productDetail['packageLength'])?$productDetail['packageLength']:0,//商品包装长度
                    'package_width'=>isset($productDetail['packageWidth'])?$productDetail['packageWidth']:0,//商品包装宽度
                    'package_height'=>isset($productDetail['packageHeight'])?$productDetail['packageHeight']:0,//商品包装高度
                    'gross_weight'=>isset($productDetail['grossWeight'])?$productDetail['grossWeight']:0,//商品毛重
                    'is_pack_sell'=>isset($productDetail['isPackSell'])&&$productDetail['isPackSell']?1:0,//是否自定义计重
                    'base_unit'=>isset($productDetail['baseUnit'])?$productDetail['baseUnit']:0,//几件内不增加邮费
                    'add_unit'=>isset($productDetail['addUnit'])?$productDetail['addUnit']:0,//每次增加的件数
                    'add_weight'=>isset($productDetail['addWeight'])?$productDetail['addWeight']:0,//每次增加的重量
                    'ws_valid_num'=>isset($productDetail['wsValidNum'])?$productDetail['wsValidNum']:0,//商品有效天数
                    'bulk_order'=>isset($productDetail['bulkOrder'])?$productDetail['bulkOrder']:1,//批发最小数量
                    'bulk_discount'=>isset($productDetail['bulkDiscount'])?$productDetail['bulkDiscount']:0,//折扣率
                    'reduce_strategy'=>isset($productDetail['reduceStrategy'])?$productDetail['reduceStrategy']:2,//库存扣减策略
                    'currency_code'=>isset($productDetail['currencyCode'])?$productDetail['currencyCode']:'USD',//货币单位
                    'gmt_create'=>isset($productDetail['gmtCreate'])?strtotime($productDetail['gmtCreate']):0,//产品发布时间
                    'gmt_modified'=>isset($productDetail['gmtModified'])?strtotime($productDetail['gmtModified']):0,//最后更新时间
                    'ws_offline_date'=>isset($productDetail['wsOfflineDate'])?strtotime($productDetail['wsOfflineDate']):0,//下架时间
                    'ws_display'=>isset($productDetail['wsDisplay'])?$productDetail['wsDisplay']:0,//下架原因
                    'promise_template_id'=>isset($productDetail['promiseTemplateId'])?$productDetail['promiseTemplateId']:'',//服务模板
                    'sizechart_id'=>isset($productDetail['sizechartId'])?$productDetail['sizechartId']:0,//尺码模板
                    'freight_template_id'=>isset($productDetail['freightTemplateId'])?$productDetail['freightTemplateId']:'',//运费模板
                    'owner_member_seq'=>isset($productDetail['ownerMemberSeq'])?$productDetail['ownerMemberSeq']:'',//商品所属人loginId
                    'owner_member_id'=>isset($productDetail['ownerMemberId'])?$productDetail['ownerMemberId']:'',//商品所属人Seq
                    'imageurls'=>isset($productDetail['imageURLs'])?$productDetail['imageURLs']:'',//图片地址
                    'group_id'=>(isset($productDetail['groupIds'])&&!empty($productDetail['groupIds']))?json_encode($productDetail['groupIds']['number']):'[]',
                    'coupon_start_date'=>isset($productDetail['couponStartDate'])?strtotime($productDetail['couponStartDate']):0,//卡券商品开始有效期
                    'coupon_end_date'=>isset($productDetail['couponEndDate'])?strtotime($productDetail['couponEndDate']):0,//卡券商品结束有效期
                    'src'=>isset($productDetail['src']) ? $productDetail['src'] : '',//产品类型
                    'is_image_dynamic'=>isset($productDetail['isImageDynamic'])?$productDetail['isImageDynamic']:'',//是否是动态图产品
                    'status'=>2,
                ];



                if(isset($productDetail['subject_list'])) {
                    $subject_list = $productDetail['subject_list'];

                    $productData['subject'] = $subject_list['subject'];
                }

                //到期时间
                if($productData['ws_offline_date']) {

                    $current_time = strtotime(date('Y-m-d'));
                    $time = $productData['ws_offline_date'] - $current_time;

                    $productData['expire_day'] = floor($time/86400);
                }

                if($productData['add_weight'] == 'null') {
                    $productData['add_weight'] = 0;
                }

                $productInfoData = [
                    'product_id'=>isset($productDetail['productId'])?$productDetail['productId']:'',
                    'mobile_detail'=>isset($productDetail['mobileDetail'])?$productDetail['mobileDetail']:'',
                    'product_attr'=>isset($productDetail['aeopAeProductPropertys'])?json_encode($productDetail['aeopAeProductPropertys']['aeopAeProductProperty']):'',
                    'multimedia'=>isset($productDetail['aeopAEMultimedia'])?$productDetail['aeopAEMultimedia']:'[]',
                ];


                if(isset($productDetail['detail_source_list']) && $productDetail['detail_source_list']) {
                    $detail_source_list = $productDetail['detail_source_list'];

                    $productInfoData['detail'] = $detail_source_list['detail'];
                    $productInfoData['mobile_detail'] = $detail_source_list['mobile_detail'];
                }

                if(isset($productDetail['aeopAeProductSKUs']) && !empty($productDetail['aeopAeProductSKUs']))
                {
                    foreach($productDetail['aeopAeProductSKUs']['aeopAeProductSku'] as $sku)
                    {
                        $productSkuData[] = [
                            'product_id'=>$productId,
                            'sku_price'=>isset($sku['skuPrice'])?$sku['skuPrice']:'',
                            'sku_code'=>isset($sku['skuCode'])?$sku['skuCode']:'',
                            'sku_stock'=>isset($sku['skuStock'])?$sku['skuStock']:'',
                            'ipm_sku_stock'=>isset($sku['ipmSkuStock'])?$sku['ipmSkuStock']:'',
                            'merchant_sku_id'=>isset($sku['id'])?$sku['id']:'',
                            'currency_code'=>isset($sku['currencyCode'])?$sku['currencyCode']:'',
                            'sku_attr'=>isset($sku['aeopSKUPropertyList']['aeopSkuProperty'])?json_encode($sku['aeopSKUPropertyList']['aeopSkuProperty']):'[]',
                        ];
                    }
                }


                $helpServer = new AliexpressTaskHelper();

                $helpServer->saveAliProduct($productData,$productInfoData,$productSkuData);
                return ['success'=>true,'message'=>''];
            }else{
                if(isset($productDetail['error_message']))
                {
                    return ['success'=>false,'message'=>$productDetail['error_message']];
                }else{
                    return ['success'=>false,'message'=>'未知错误'];
                }
            }
        }else{
            return ['success'=>false,'message'=>'商品不存在'];
        }
    }
    /**
     * @param int $sku_id
     * @return array
     * 根据sku_id获取销售员id
     */
    public function getSellerIdBySku(int $sku_id)
    {

        $result = [];
        if($sku_id){

            $productKuModel = new AliexpressProductSku;

            $where = [
                'a.goods_sku_id' => $sku_id,
                'a.product_id' => ['>', 0],
            ];

            $result = $productKuModel->alias('a')->field('p.salesperson_id')->join('aliexpress_product p','a.ali_product_id = p.id','LEFT')->where($where)->select();

            if($result){
               $result = array_unique(array_column($result,'salesperson_id'));
            }
        }

        
        return $result;
    }


    /**
     * @param int $goodsId
     * @return array
     * 根据goodsId获取销售员id
     */
    public static function getSellerIdByGoodsId(int $goodsId)
    {

        $result = [];
        if($goodsId){

            $productModel = new AliexpressProduct;

            $where = [
                'a.goods_id' => $goodsId,
                'a.product_id' => ['>', 0],
                'a.salesperson_id' => ['>',0],
            ];


            $result = $productModel->alias('a')->field('a.salesperson_id')->where($where)->select();

            if($result) {
                $result = array_unique(array_column($result,'salesperson_id'));
            }

        }


        return $result;
    }


    /**
     *本地sku搜索平台sku信息
     *
     */
    public function skuCodeSearch($productIds, $skus)
    {

       $productIds = explode(',', $productIds);
       $skus = explode(',', $skus);

       //根据sku查询sku_id
       $skuModel = new GoodsSku();

       $skuIds = $skuModel->field('id')->whereIn('sku',$skus)->select();


       if(empty($skuIds)) {
           return ['message' => '没有查到产品sku信息','status' => false];
       }

       $skuIds = array_column($skuIds,'id');

       $productSkuModel = new AliexpressProductSku();
       $data = $productSkuModel->field('product_id, sku_code as sku, ipm_sku_stock as old_stock')->whereIn('product_id',$productIds)->whereIn('goods_sku_id', $skuIds)->select();


       return ['data' => $data,'message' => '成功', 'status' => true];
    }
}
