<?php


namespace app\goods\service;

use org\Curl;
use think\Exception;
use app\common\model\GoodsGallery;
use app\goods\service\GoodsImage;
use app\goods\service\GoodsSku;
use app\common\model\Goods;
use app\common\service\Order;
use app\index\service\Currency;
use app\common\model\GoodsSku as ServiceGoodsSku;
use app\purchase\service\SafeDeliveryService;

class GoodsToIrobotbox
{

    public function __construct($customerID = 1319, $username = "3444036762@qq.com", $password = "rondaful112")
    {
        $this->Customer_ID = $customerID;
        $this->Username = $username;
        $this->Password = $password;
    }

    private function getAttr($sku_attributes, $goods_id)
    {
        $baseAttr = json_decode($sku_attributes, true);
        $attr = GoodsHelp::getAttrbuteInfoBySkuAttributes($baseAttr, $goods_id);
        $list = [];
        foreach ($attr as $v) {
            if (!in_array($v['id'], [1, 2, 3, 4, 12, 13, 14, 16])) {
                continue;
            }
            $tmp = explode('|', $v['value']);
            if (count($tmp) > 1) {
                $row = $tmp[1];
            } else {
                $row = $v['value'];
            }
            if ($v['id'] == 1) {
                $list['color'] = $row;
            } else {
                $list['size'] = $row;
            }
        }
        return $list;
    }

    private function body($sku_id, $goodsInfo = [])
    {
        $GoodsSku = new GoodsSku();
        $sKuInfo = $GoodsSku->getBySkuID($sku_id, 0, 0);
        $GoodsImport = new GoodsImport();
        if (!$sKuInfo) {
            throw new Exception("该sku不存在");
        }

        $attr = $this->getAttr($sKuInfo['sku_attributes'], $sKuInfo['goods_id']);
        $color = $attr['color'] ?? '';
        $size = $attr['size'] ?? '';
        $Goods = new Goods();
        $GoodsHelp = new GoodsHelp();
        if (!$goodsInfo) {
            $goodsInfo = $GoodsHelp->getGoodsInfo($sKuInfo['goods_id']);
        }
        $goodsInfo['category_txt'] = $Goods->getCategoryAttr(null, ['category_id' => $goodsInfo['category_id']]);
        $aLang = $GoodsImport->getLang($sKuInfo['goods_id']);
        $name_en = '';
        $description = '';
        $description_en = '';
        $tags = '';
        if (isset($aLang[1])) {
            $description = $aLang[1]['description'];
            $description = htmlspecialchars($description);
            $description = preg_replace("/[\f\n\r]+/", PHP_EOL, $description);
            $tags = $aLang[1]['tags'];
        }
        if (isset($aLang[2])) {
            $description_en = $aLang[2]['description'];
            $description_en = preg_replace("/[\f\n\r]+/", PHP_EOL, $description_en);
            $name_en = $aLang[2]['title'];
        }
        $description = $description_en ? $description_en : $description;
        $description = htmlspecialchars($description);
        $length = $sKuInfo['length'] / 10;
        $height = $sKuInfo['height'] / 10;
        $width = $sKuInfo['width'] / 10;
        $currency = new Currency();
        $rate = $currency->getCurrency('USD');
        $rate = $rate['USD'];
        $retail_price = number_format(($sKuInfo['retail_price'] / $rate), 2, ".", '');
        $order = new Order();
        $price = $order->getGoodsCostPriceBySkuId($sku_id, 2);
        $pack = $GoodsHelp->getPackageById($goodsInfo['packing_id']);
        $imgList = $this->createImg($sku_id, $sKuInfo['goods_id']);
        $SupplierName = $GoodsImport->getSupplierName($goodsInfo['supplier_id']);
        $SafeDeliveryService = new SafeDeliveryService();
        $aDay = $SafeDeliveryService->getDeliveryDays($sku_id, $goodsInfo['supplier_id'], 2);
        $day = $aDay ? $aDay : 9;
        $body = " <ApiImportProductInfo>
                    <SKU></SKU>
                    <ClientSKU>{$sKuInfo['sku']}</ClientSKU>
                    <ProductColor>{$color}</ProductColor>
                    <ProductSize>{$size}</ProductSize>
                    <ComeSource>1</ComeSource>
                    <ProductClassNameEN></ProductClassNameEN>
                    <ProductClassNameCN>{$goodsInfo['category_txt']}</ProductClassNameCN>
                    <ProductName>{$name_en}</ProductName>
                    <ProductNameCN>{$sKuInfo['spu_name']}</ProductNameCN>
                    <MateDescription>{$tags}</MateDescription>
                    <ProductDescription>
                            {$description}
                    </ProductDescription>
                    <Length>{$length}</Length>
                    <Width>{$width}</Width>
                    <Height>{$height}</Height>
                    <Pack_Length>{$length}</Pack_Length>
                    <Pack_Width>{$width}</Pack_Width>
                    <Pack_Height>{$height}</Pack_Height>
                    <PackingList>{$pack}</PackingList>
                    <SalePrice>{$retail_price}</SalePrice>
                    <LastSupplierPrice>{$price}</LastSupplierPrice>
                    <NetWeight>{$sKuInfo['weight']}</NetWeight>
                    <GrossWeight>{$sKuInfo['weight']}</GrossWeight>
                    <PackWeight>{$sKuInfo['weight']}</PackWeight>
                    <FeatureList>{$tags}</FeatureList>
                    <ImagesList>
                        {$imgList}
                    </ImagesList>
                    <ProductSuppiler>
                        <ApiImportProductSupplier>
                             <SupplierName>{$SupplierName}</SupplierName>      
                             <SupplierType>1</SupplierType>      
                        </ApiImportProductSupplier>
                    </ProductSuppiler>
                    <ProductSupplierPrice>
                        <ApiImportProductSupplierPrice>
                            <SupplierSKU>{$sKuInfo['sku']}</SupplierSKU>
                            <ProcessPrice>0</ProcessPrice>
                            <OtherPrice>0</OtherPrice>
                            <ProcurementDay>{$day}</ProcurementDay>
                        </ApiImportProductSupplierPrice>
                    </ProductSupplierPrice>
             </ApiImportProductInfo>       
          ";
        return $body;

    }


    private function createImg($sku_id, $goods_id)
    {
        $list = GoodsGallery::where('sku_id', $sku_id)
            ->order('is_default asc')
            ->order('sort asc')
            ->limit(9)
            ->select();
        $result = '';
        $i = 0;

        if (count($list) < 9) {
            $list2 = GoodsGallery::where('goods_id', $goods_id)
                ->where('sku_id', 0)
                ->order('is_default asc')
                ->order('sort asc')
                ->limit(9)
                ->select();
            if (is_array($list)) {
                $list = array_merge($list, $list2);
            } else {
                $list = $list2;
            }
        }
        foreach ($list as $info) {
            if ($i == 0) {
                $isCover = 1;
            } else {
                $isCover = 0;
            }
            $img = GoodsImage::getThumbPath($info['path'], 0, 0);

            $result .= "<ApiImportProductImage>
                            <IsCover>{$isCover}</IsCover>
                            <OriginalImageUrl>{$img}</OriginalImageUrl>
                            <SortBy>{$info['sort']}</SortBy>
                        </ApiImportProductImage> ";
            if ($i >= 8) {
                break;
            }
            $i++;
        }
        return $result;
    }

    public function test(){
        $post = $this->getTest();
        $headers[] = "Content-type: text/xml; charset=utf-8";
        $post = mb_convert_encoding($post,'utf8');
        $response = Curl::curlPost('http://gg7.irobotbox.com/Api/API_ProductInfoManage.asmx?wsdl', $post, $headers);
        return ['request' => $post, 'response' => $response];
    }
    public function upload($goods_id, $showErr = false)
    {
        try {
            $ServiceGoodsSku = new ServiceGoodsSku();
            $aGoodsSku = $ServiceGoodsSku->where('goods_id', $goods_id)->select();
            if (!$aGoodsSku) {
                throw new Exception('该商品的sku为空');
            }
            $body = '';
            $GoodsHelp = new GoodsHelp();

            $goodsInfo = $GoodsHelp->getGoodsInfo($goods_id);
            if (!$goodsInfo) {
                throw new Exception('该商品不存在');
            }
            foreach ($aGoodsSku as $skuInfo) {
                $skuId = $skuInfo['id'];
                $body .= $this->body($skuId);
            }
            $post = '<?xml version="1.0" encoding="utf-8"?>
                        <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                          <soap:Body>
                            <ProcessUpdateProduct xmlns="http://tempuri.org/">
                              <request>
                                  <CustomerID>' . $this->Customer_ID . '</CustomerID>
                                  <UserName>' . $this->Username . '</UserName>
                                  <Password>' . $this->Password . '</Password>
                                  <ImportProductList>' . $body . '</ImportProductList>
                              </request>
                            </ProcessUpdateProduct>
                          </soap:Body>
                        </soap:Envelope>';
            $post = mb_convert_encoding($post,'utf8');
            $headers[] = "Content-type: text/xml; charset=utf-8";
            $response = Curl::curlPost('http://gg7.irobotbox.com/Api/API_ProductInfoManage.asmx?wsdl', $post, $headers);
            if ($showErr == true) {
                return ['request' => $post, 'response' => $response];
            }
            if (!$response) {
                return [];
            }
            $result = $this->getReponseAdd($response);
            if ($result->Status != 'OK') {
                throw new Exception('上传失败！');
            }
            return ['message' => '上传成功'];
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function getTest()
    {


        $dec = 'Features:   Show your plants some love with this elegant, vintage-inspired plant hanger.  This listing is for a macrame plant hanger which will hold a flowerpot   These hangers can be utilized Inside or Outside - Just bring them in during cold winter months   Versatile style that can be hung from a hook in the ceiling or against a wall like a wall hanging.  To hang your plant just insert your pot into the hanger, level the pot and slide the beads down to the rim of the pot   The rope is durable, able to hold heavy flowerpot   Specifications:   Color: as the picture shows   Material: jute rope   Wooden ring: about 5.5 cm/2.17\'\'   Whole length: about 95 cm/37.40\'\'  Notes: 1. Because the product is wholly hand-woven, please allow 1~3cm size variations & style differences.  2. Make sure the your pot size and wall height before you order. The fit width we provide is just for reference. Packing list: 1* Woven Hanging Basket (The flowerpot not included) ';
        $dec = htmlspecialchars($dec);
        $str = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <ProcessUpdateProduct xmlns="http://tempuri.org/">
            <request>
                <CustomerID>1319</CustomerID>
                <UserName>3444036762@qq.com</UserName>
                <Password>rondaful112</Password>
                <ImportProductList>
                    <ApiImportProductInfo>
                        <SKU></SKU>
                        <ClientSKU>EF0020400</ClientSKU>
                        <ProductColor></ProductColor>
                        <ProductSize></ProductSize>
                        <ComeSource>1</ComeSource>
                        <ProductClassNameEN></ProductClassNameEN>
                        <ProductClassNameCN>家居园艺类&gt;花瓶花艺</ProductClassNameCN>
                        <ProductName>MS7249 Woven Basket Tapestry Flower Hanger Basket Plant Hanger Hanging Basket Jute Rope Braided Tapestry</ProductName>
                        <ProductNameCN>MS7249编织吊篮挂毯（OPP袋）</ProductNameCN>
                        <MateDescription></MateDescription>
                        <ProductDescription>'.$dec.'</ProductDescription>
                        <Length>10</Length>
                        <Width>10</Width>
                        <Height>1</Height>
                        <Pack_Length>10</Pack_Length>
                        <Pack_Width>10</Pack_Width>
                        <Pack_Height>1</Pack_Height>
                        <PackingList></PackingList>
                        <SalePrice>0.00</SalePrice>
                        <LastSupplierPrice>17</LastSupplierPrice>
                        <NetWeight>180</NetWeight>
                        <GrossWeight>180</GrossWeight>
                        <PackWeight>180</PackWeight>
                        <FeatureList></FeatureList>
                        <ImagesList>
                            <ApiImportProductImage>
                                <IsCover>1</IsCover>
                                <OriginalImageUrl>http://14.118.130.19/199/329/6f36e084d94a2a1f2a1aada3ee862f23.jpg</OriginalImageUrl>
                                <SortBy>98</SortBy>
                            </ApiImportProductImage>
                            <ApiImportProductImage>
                                <IsCover>0</IsCover>
                                <OriginalImageUrl>http://14.118.130.19/199/329/df7892cf7f929bd108146dc13ac86a65.jpg</OriginalImageUrl>
                                <SortBy>98</SortBy>
                            </ApiImportProductImage>
                            <ApiImportProductImage>
                                <IsCover>0</IsCover>
                                <OriginalImageUrl>http://14.118.130.19/199/329/b7df61387634ff6ed7cb02b9d5ef7514.jpg</OriginalImageUrl>
                                <SortBy>98</SortBy>
                            </ApiImportProductImage>
                            <ApiImportProductImage>
                                <IsCover>0</IsCover>
                                <OriginalImageUrl>http://14.118.130.19/199/329/d366cf04379edd9dd22a32e60c9a2906.jpg</OriginalImageUrl>
                                <SortBy>98</SortBy>
                            </ApiImportProductImage>
                            <ApiImportProductImage>
                                <IsCover>0</IsCover>
                                <OriginalImageUrl>http://14.118.130.19/199/329/f7a553acc1149f73b8b77d2f3a4fe58b.jpg</OriginalImageUrl>
                                <SortBy>98</SortBy>
                            </ApiImportProductImage>
                            <ApiImportProductImage>
                                <IsCover>0</IsCover>
                                <OriginalImageUrl>http://14.118.130.19/199/329/4181d4e7744caa6788c6ff07de07c1d0.jpg</OriginalImageUrl>
                                <SortBy>98</SortBy>
                            </ApiImportProductImage>
                            <ApiImportProductImage>
                                <IsCover>0</IsCover>
                                <OriginalImageUrl>http://14.118.130.19/199/329/629cb019a29a9a52539b6dfcdb8cec46.jpg</OriginalImageUrl>
                                <SortBy>98</SortBy>
                            </ApiImportProductImage>
                            <ApiImportProductImage>
                                <IsCover>0</IsCover>
                                <OriginalImageUrl>http://14.118.130.19/199/329/945526a3faaf224c4a94c95a35886e70.jpg</OriginalImageUrl>
                                <SortBy>98</SortBy>
                            </ApiImportProductImage>
                            <ApiImportProductImage>
                                <IsCover>0</IsCover>
                                <OriginalImageUrl>http://14.118.130.19/199/329/6d84df59c5b4b0f49362189a7086480d.jpg</OriginalImageUrl>
                                <SortBy>98</SortBy>
                            </ApiImportProductImage>
                        </ImagesList>
                        <ProductSuppiler>
                            <ApiImportProductSupplier>
                                <SupplierName>义乌市饰家家居用品有限公司</SupplierName>
                                <SupplierType>1</SupplierType>
                            </ApiImportProductSupplier>
                        </ProductSuppiler>
                        <ProductSupplierPrice>
                            <ApiImportProductSupplierPrice>
                                <SupplierSKU>EF0020400</SupplierSKU>
                                <ProcessPrice>0</ProcessPrice>
                                <OtherPrice>0</OtherPrice>
                                <ProcurementDay>9</ProcurementDay>
                            </ApiImportProductSupplierPrice>
                        </ProductSupplierPrice>
                    </ApiImportProductInfo>
                </ImportProductList>
            </request>
        </ProcessUpdateProduct>
    </soap:Body>
</soap:Envelope>';
        return $str;
    }

    private function getReponseUpdate($response)
    {
        $xmlObj = simplexml_load_string($response);
        $xmlObj->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $result = $xmlObj->xpath("soap:Body");
        if (!isset($result[0]) && !$result[0]) {
            throw new Exception('请求超时');
        }
        $order = $result[0];
        $OrdersResult = $order->ProcessUpdateProductResponse->ProcessUpdateProductResult;
        if (isset($OrdersResult->Status) && $OrdersResult->Status == 'OK') {
            return $OrdersResult->Result->ApiUploadResult;
        }
        throw new Exception('服务器无返回');
    }

    private function getReponseAdd($response)
    {
        $xmlObj = simplexml_load_string($response);
        $xmlObj->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $result = $xmlObj->xpath("soap:Body");
        if (!isset($result[0]) && !$result[0]) {
            throw new Exception('请求超时');
        }
        $order = $result[0];
        $OrdersResult = $order->ProcessUpdateProductResponse->ProcessUpdateProductResult;
        if (isset($OrdersResult->Status) && $OrdersResult->Status == 'OK') {
            return $OrdersResult;
        }
        throw new Exception('服务器无返回');
    }

    public function getChannelSku()
    {
        $post = '<?xml version="1.0" encoding="utf-8"?>
            <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
              <soap:Body>
                <GetPlatformSKUList xmlns="http://tempuri.org/">
                <request>
                      <CustomerID>' . $this->Customer_ID . '</CustomerID>
                      <UserName>' . $this->Username . '</UserName>
                      <Password>' . $this->Password . '</Password>        
                      <OrderSourceType>1</OrderSourceType>
                      <OrderSourceID>0</OrderSourceID>               
                  </request>
                </GetPlatformSKUList>
              </soap:Body>
            </soap:Envelope>';
        $headers[] = "Content-type: text/xml; charset=utf-8";
        $response = Curl::curlPost('http://gg7.irobotbox.com/Api/API_ProductInfoManage.asmx?wsdl', $post, $headers);
        halt($response);
    }

}