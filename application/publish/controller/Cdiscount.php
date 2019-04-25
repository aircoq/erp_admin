<?php
/**
 * Created by PhpStorm.
 * User: panguofu
 * Date: 2018/10/24
 * Time: 下午6:02
 */

/*
<
Product
BrandName = "Deeluxe"   //品牌名称
SellerProductFamily = "SOJ50874" //SPU
SellerProductColorName = "Bleu Délavé"  //产品颜色，产品是Variant必填，产品是Standard非必填
Size = "38/34"   //产品大小，产品是Variant必填，产品是Standard非必填
Description = "Marque Deeluxe, Modèle Tanner Snow Bleu, Jeans Coupe Droite Homme, Couleur Bleu Délavé, 100% Coton , Taille 28" //描述，可选项，支持txt,html类型
LongLabel = "Nudie Average Joe organic vacation worn Jeans" //长标签
Model = "SOUMISSION CREATION PRODUITS_MK"
ProductKind = "Variant" //取值：Variant、Standard
CategoryCode = "0R050A01" //类别代码
SellerProductId = "120905783" //产品ID
ShortLabel = "Jeans Deeluxe Tanner Snow Bleu" //短标签
EncodedMarketingDescription = "RGVzY3JpcHRpb24gcXVpIGNvbnRpZW50IGR1IDxzdHJvbmc+SFRNTDwvc3Ryb25nPg==" //描述，可选项，最大5000characters
>
<Product . EanList >
          <ProductEan Ean = "3606918243767" /> //EAN码是国际物品编码协会制定的一种商品用条码，通用于全世界。
</Product . EanList >
<Product . ModelProperties >
          <x:String x:Key = "Genre" > Homme - Garçon</x:String >
		  <x:String x:Key = "Type de public" > Adulte</x:String >
</Product . ModelProperties >//属性
<Product . Pictures >
          <ProductImage Uri = "http://cdn.sojeans.com/products/406x538/2710-jeans-deeluxe-tanner-1.jpg" />
		  <ProductImage Uri = "http://cdn.sojeans.com/products/406x538/2710-jeans-deeluxe-tanner-2.jpg" />
		  <ProductImage Uri = "http://cdn.sojeans.com/products/406x538/2710-jeans-deeluxe-tanner-3.jpg" />
		  <ProductImage Uri = "http://cdn.sojeans.com/products/406x538/2710-jeans-deeluxe-tanner-4.jpg" />
</Product . Pictures >//不能超过4张
</Product >

*/


/*

<Offer
SellerProductId="32427220"  //产品ID
ProductEan="0080605625006" //EAN码
ProductCondition="6"//产品状况
Price="19.95" //含税价
EcoTax="0.10" //ECO税
DeaTax="3.14" //DEA税
VatRate="19.6" //增值税税率
Stock="10" //库存数量
StrikedPrice="39.95"//不知道什么价
Comment="Offre avec tous les modes de livraisons possibles" //评论
PreparationTime="1">
        <Offer.ShippingInformationList>
          <ShippingInformationList Capacity="3">
			 <ShippingInformation AdditionalShippingCharges="0.95" DeliveryMode="Standard" ShippingCharges="1.0" />
//AdditionalShippingCharges：运费附加费
DeliveryMode：配送方式
ShippingCharges：运费
			 <ShippingInformation AdditionalShippingCharges="1.95" DeliveryMode="Tracked" ShippingCharges="2.0" />
			 <ShippingInformation AdditionalShippingCharges="2.95" DeliveryMode="Registered" ShippingCharges="3.0" />
          </ShippingInformationList>
        </Offer.ShippingInformationList>

<Offer.PriceAndDiscountList> //折扣
          <DiscountComponentList Capacity="1"> //Capacity:list SIZE
            <DiscountComponent
DiscountUnit="1"
DiscountValue="30"
SalesReferencePrice="100"//销售参考价格
Type="3"//类型，枚举
EndDate="0001-01-01T00:01" //结束时间
StartDate="0001-01-01T00:00" //开始时间
/>
          </DiscountComponentList>
        </Offer.PriceAndDiscountList>

      </Offer>

*/

namespace app\publish\controller;

use app\common\exception\JsonErrorException;
use app\publish\service\ExpressHelper;
//use app\publish\service\GoodsImage;
use think\Debug;
use think\Request;
use think\Response;
//use think\Cache;
use think\Exception;
use cd\CdProductApi;
use cd\CdOfferApi;

use app\common\model\cd\CdAccount;
use app\common\cache\Cache;

use app\common\controller\Base;


class Cdiscount extends Base
{

    public function submitProductPackage()
    {

        $uid = 2;
        $cache = Cache::store('CdAccount');
        $account = $cache->getAccountById($uid);
        if (!isset($account)) {
            $this->error = '账号不存在'; //diaryyanzi@outlook.com
            return false;
        }

        try {
            $api = new CdProductApi($account);
            $url = 'https://dev.cdiscount.com/marketplace/wp-content/uploads/APIMPCdiscount_Sample_Products.zip';
            $rlt = $api->submitProductPackage($url);
            print_r($rlt);
        } catch (\Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 400);
        }

    }


    public function submitOfferPackage()
    {
        $uid = 2;
        $cache = Cache::store('CdAccount');
        $account = $cache->getAccountById($uid);
        if (!isset($account)) {
            $this->error = '账号不存在'; //diaryyanzi@outlook.com
            return false;
        }

        try {
            $api = new CdOfferApi($account);
            $url = 'https://dev.cdiscount.com/marketplace/wp-content/uploads/APIMPCdiscount_Sample_Offers_Full.zip';
            $rlt = $api->submitOfferPackage($url);
            print_r($rlt);
        } catch (\Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 400);
        }
    }


}