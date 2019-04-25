<?php
namespace app\irobotbox\task;
/**
 * rocky
 * 17-4-11
 * 获取赛和erp商品类目
*/

use app\index\service\AbsTasker;
use service\irobotbox\IrobotboxApi;
use think\Db;
use app\common\model\Goods;
use app\common\model\WarehouseGoods;
use app\common\model\Category;
use app\index\service\ImportData as ImportService;
use org\EbayXml;
use Box\Spout\Reader\Wrapper\XMLReader;
class syncCategoryInfo extends AbsTasker
{
	public function getName()
    {
        return "获取赛和erp商品信息";
    }
    
    public function getDesc()
    {
        return "获取赛和erp商品信息";
    }
    
    public function getCreator()
    {
        return "曾绍辉";
    }
    
    public function getParamRule()
    {
        return [];
    }
    
    
    public function execute()
    {
        self::syncCategorys();
        sleep(10);
        #self::execute();
    }

    public function syncCategorys(){
        set_time_limit(0); 
        $good = new Goods();
        $cate = new Category();
        $sys = Db::table("sys_task")->where(array("id"=>1))->find();
    	$iroApi = new IrobotboxApi("http://gg7.irobotbox.com/Api/API_ProductInfoManage.asmx?wsdl");

    	$result=$iroApi->createSoapCli()->GetProducts($sys['next_token']);
        $products = $result->GetProductsResult->ProductInfoList->ApiProductInfo;

        foreach($products as $k => $v){
            if($v->ClassID2==$v->ClassID1 && $v->ClassID2==0){
                $parentId = $v->LastClassID;
            }else if($v->ClassID2==$v->ClassID1 && $v->ClassID2!=0){
                $parentId = $v->ClassID2;
            }else if($v->ClassID2 == $v->LastClassID && $v->LastClassID){
                 $parentId = $v->ClassID1;
            }else{
                 $parentId =0;
            }
            
            #var_dump($v->ProductGroupSKU);
            $groupSku = $v->ProductGroupSKU?$v->ProductGroupSKU:$v->ClientSKU;
            if($groupSku && $parentId){
                $tmp['spu']=$groupSku;
                #图片
                $imgs = isset($v->ImageList->ApiProductImage)?$v->ImageList->ApiProductImage:"";
                if(is_array($imgs)){
                    $img = isset($imgs[0])?$imgs[0]->ImageUrl:"";
                }else{
                    $img = isset($img->ImageUrl)?$img->ImageUrl:"";
                }
                
                #类别
                if($v->ClassID2 == $v->LastClassID){
                    $cateRes = $iroApi->createSoapCli()->GetProductClass($parentId);
                }else{
                    $cateRes = $iroApi->createSoapCli()->GetProductClass($parentId);
                }
                
                if(isset($cateRes->GetProductClassResult->ProductClassList->ApiProductClass)){
                    $cateArr =$cateRes->GetProductClassResult->ProductClassList->ApiProductClass;
                }else{
                    $cateArr = array();
                }
                 
                $cateInfo = array();
                if(is_array($cateArr)){
                    foreach($cateArr as $kca => $vca){
                        if(intval($vca->ID) == intval($v->LastClassID)){
                            $cateInfo = $vca;
                        }
                    }
                }else{
                    if($cateArr->ID==$v->LastClassID){
                        $cateInfo = $cateArr;
                    }
                }
                
                if(!empty($cateInfo)){
                    $category=$cate->where(array("name"=>$cateInfo->ClassNameCn))->find();
                    if($category){
                        $categoryId= $category->id;
                    }else{
                        $categoryId=1;
                    }
                }else{
                    $categoryId=1;
                }
                
                $skuAttrValueArr = array();
                
                #$data['spu'] = $v->ProductGroupSKU;
                $data['spu'] = $groupSku;
                $data['category_id']=$categoryId;
                $data['name']=isset($v->PageTitle)?$v->PageTitle:"";#标题
                $data['packing_en_name']=isset($v->ProductName)?$v->ProductName:"";#英文配货名称
                $data['packing_name']=isset($v->ProductNameCN)?$v->ProductNameCN:"";#中文配货名称
                $data['declare_name']=isset($v->DeclarationNameCN)?$v->DeclarationNameCN:"";#中文报关名称
                $data['declare_en_name']=isset($v->DeclarationName)?$v->DeclarationName:"";#英文报关名称
                $data['keywords']=isset($v->SearchKeyword)?$v->SearchKeyword:"";
                $data['description']=isset($v->ProductDescription)?$v->ProductDescription:"";
                $data['thumb']=$img;#图片
                $data['width']=isset($v->Width)?$v->Width:0;#宽
                $data['height']=isset($v->Height)?$v->Width:0;#高
                $data['depth']=isset($v->Length)?$v->Width:0;#深
                $data['weight']=isset($v->GrossWeight)?$v->GrossWeight:0;#重量(毛重)
                if($v->WithBattery=="普货"){
                    $data['type']=0;#商品类型  
                }else{
                    $data['type']=1;
                }
                $data['cost_price']=isset($v->LastSupplierPrice)?$v->LastSupplierPrice:0;#成本价
                $data['retail_price']=isset($v->SalePrice)?$v->SalePrice:0;#销售价格
                $data['update_time']=isset($v->UpdateTime)?strtotime($v->UpdateTime):time();#更新时间
                $data['status']=1;
                $goodsId = ImportService::addGoods($data);

                #添加描述
                $description = array();
                $description['lang_id']=1;
                $description['description']=$data['description'];
                $description['goods_id']=$goodsId;
                ImportService::addDescription($description);

                #属性(颜色)
                $color = $v->ProductColor;
                if($color){
                    $colorId = ImportService::addAttributeValue($color,1);
                    $skuAttrValueArr['attr_1']=$colorId;
                    $goodsAttrArr1 = [
                        'attribute_id' => 1,
                        'goods_id'     => $goodsId,
                        'value_id'     => $colorId,
                        'data'         => $color
                    ];
                    ImportService::addGoodsAttr($goodsAttrArr1);
                    //添加分类属性
                    $categoryAttrArr1 = [
                        'category_id'    => $categoryId,
                        'attribute_id'   => 1,
                        'value_range'    => $colorId,
                        'group_id'       => 1,
                        'sku'            => 1,
                        'gallery'        => 1
                    ];
                    ImportService::addCategoryAttr($categoryAttrArr1);
                }
                    
                #属性(尺码)
                $size = $v->ProductSize;
                if($size){
                    $sizeId = ImportService::addAttributeValue($size,2);
                    $skuAttrValueArr['attr_2']=$sizeId;
                    //添加商品属性
                    $goodsAttrArr2 = [
                        'attribute_id' => 2,
                        'goods_id'     => $goodsId,
                        'value_id'     => $sizeId,
                        'data'         => $size
                    ];
                    ImportService::addGoodsAttr($goodsAttrArr2);
                    //添加分类属性
                    $categoryAttrArr2 = [
                        'category_id'    => $categoryId,
                        'attribute_id'   => 2,
                        'value_range'    => $sizeId,
                        'group_id'       => 1,
                        'sku'            => 1,
                    ];
                    ImportService::addCategoryAttr($categoryAttrArr2);
                }

                #子产品
                $daDetail['goods_id']=$goodsId;
                $daDetail['sku']=$v->ClientSKU;
                $daDetail['thumb']=$img;
                $daDetail['sku_attributes']=$skuAttrValueArr ? json_encode($skuAttrValueArr) : '{}';#属性值
                $daDetail['spu_name']=isset($v->PageTitle)?$v->PageTitle:"";#标题
                $daDetail['cost_price']=$data['cost_price'];#成本价
                $daDetail['retail_price']=$data['retail_price'];#零售价
                $daDetail['market_price']=$data['retail_price'];#市场价
                $daDetail['create_time']=time();
                $daDetail['update_time']=isset($v->UpdateTime)?strtotime($v->UpdateTime):time();#更新时间
                $skuId = ImportService::addGoodsSku($daDetail);

                #获取图片
                $this->getIrbImageUrls($skuId,$v->ClientSKU,$v->SKU,$img);

                #库存(中国本地仓库)
                $inventory=$iroApi->createSoapCli()->GetProductInventory($v->SKU,175);
                $inv = isset($inventory->GetProductInventoryResult->ProductInventoryList->ApiProductInventory)?$inventory->GetProductInventoryResult->ProductInventoryList->ApiProductInventory:0;
                $list = [
                    'warehouse_id'       => 2,
                    'goods_id'           => $goodsId,
                    'sku_id'             => $skuId,
                    'sku'                => $v->ClientSKU,
                    'available_quantity' => isset($inv->GoodNum)?$inv->GoodNum:$inv,
                    'per_time'           => time(),
                    #'per_cost'           => ,
                    'created_time'       => time(),
                    'updated_time'       => isset($inv->UpdateTime)?strtotime($inv->UpdateTime):time(),
                ];
                $warehouseGoods = new WarehouseGoods();
                $warehouseGoods->allowField(true)->save($list);
                #库存(FBA仓库)
                $inventoryFba=$iroApi->createSoapCli()->GetProductInventory($v->SKU,354);

                $invFba = isset($inventoryFba->GetProductInventoryResult->ProductInventoryList->ApiProductInventory)?$inventoryFba->GetProductInventoryResult->ProductInventoryList->ApiProductInventory:0;
                $listFba = [
                    'warehouse_id'       => 67,
                    'goods_id'           => $goodsId,
                    'sku_id'             => $skuId,
                    'sku'                => $v->ClientSKU,
                    'available_quantity' => isset($invFba->GoodNum)?$invFba->GoodNum:$invFba,
                    'per_time'           => time(),
                    #'per_cost'           => $data[$title['FBA仓库金额']]/$data[$title['FBA仓库数量']],
                    'created_time'       => time(),
                    'updated_time'       => isset($invFba->UpdateTime)?strtotime($invFba->UpdateTime):time(),
                 ];
            $warehouseGoods->allowField(true)->save($listFba);
            }
        }
        $nextToken = $result->GetProductsResult->NextToken;
        var_dump($nextToken);
        Db::table("sys_task")->where(array("id"=>1))->update(array("next_token"=>$nextToken));
    }

    #获取图片
    public function getIrbImageUrls($skuId,$clienSku,$sku,$img){
        $imgApi = new IrobotboxApi("http://gg7.irobotbox.com/Api/API_ProductInfoManage.asmx?wsdl");
        $headers = array(
                    "Customer_ID"=>$imgApi->Customer_ID,
                    "Username"=>$imgApi->Username,
                    "Password"=>$imgApi->Password,
            );
        $nSpace = "http://tempuri.org/";
        $cName = "HeaderUserSoapHeader";
        $tempImg=$imgApi->createSoapCli()->createSoapHeader($nSpace,$cName,$headers)->setClientHeaders()->GetProductImages($sku);
        if(isset($tempImg->GetProductImagesResult)){
            $imgUrls = explode("/upload",$tempImg->GetProductImagesResult->any);#获取图片
            foreach($imgUrls as $kimg => $vimg){
                if(strpos($vimg,"product")){#同步数据库
                    $timg = substr($vimg,0,strpos($vimg,"</"));
                    $imgDa['sku_id']=$skuId;
                    $imgDa['sku'] = $clienSku;
                    $imgDa['irb_sku'] = $sku;
                    $imgDa['image_url'] = "/upload".$timg;
                    $rsImg = Db::name("goods_sku_img")->where(array("irb_sku"=>$sku,"image_url"=>$imgDa['image_url']))->find();
                    if($rsImg){#更新
                        Db::name("goods_sku_img")->where(array("irb_sku"=>$sku,"image_url"=>$imgDa['image_url']))->update($imgDa);
                    }else{#添加
                        Db::name("goods_sku_img")->insertGetId($imgDa);
                    }
                }else{
                    unset($imgUrls[$kimg]);
                }
            }
        }else{
            $imgDa['sku_id']=$skuId;
            $imgDa['sku'] = $clienSku;
            $imgDa['irb_sku'] = $sku;
            $imgDa['image_url'] = $img;
            $rsImg = Db::name("goods_sku_img")->where(array("irb_sku"=>$sku,"image_url"=>$imgDa['image_url']))->find();
            if($rsImg){#更新
                Db::name("goods_sku_img")->where(array("irb_sku"=>$sku,"image_url"=>$imgDa['image_url']))->update($imgDa);
            }else{#添加
                Db::name("goods_sku_img")->insertGetId($imgDa);
            }
        }

    }

}