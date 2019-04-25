<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-7-5
 * Time: 上午9:47
 */

namespace app\goods\service;

use app\common\cache\Cache;
use app\common\model\AttributeValue;
use app\common\model\Brand;
use app\common\model\Category;

use app\common\model\Channel;
use app\common\model\DepartmentUserMap;
use app\common\model\GoodsAttribute;
use app\common\model\GoodsGallery;
use app\common\model\GoodsLang;
use app\common\model\GoodsSku;
use app\common\model\Goods;
use app\common\model\LogExportDownloadFiles;
use app\common\service\Excel;
use app\index\service\DownloadFileService;
use app\publish\service\GoodsImage;
use app\common\model\RoleUser;
use app\warehouse\service\WarehouseGoods;
class GoodsDownloadService
{
    private static $channel_id;
    private static $imageUrl = null;
    private static $service = null;
    private static $ShopeeHeaders = "ps_category_list_id,ps_product_name,产品中文名称,产品中文描述,物流属性,ps_product_description,ps_price,ps_stock,ps_product_weight,ps_days_to_ship,ps_sku_ref_no_parent,ps_mass_upload_variation_help,ps_variation 1 ps_variation_sku,ps_variation 1 ps_variation_name,ps_variation 1 ps_variation_price,ps_variation 1 ps_variation_stock,ps_variation 2 ps_variation_sku,ps_variation 2 ps_variation_name,ps_variation 2 ps_variation_price,ps_variation 2 ps_variation_stock,ps_variation 3 ps_variation_sku,ps_variation 3 ps_variation_name,ps_variation 3 ps_variation_price,ps_variation 3 ps_variation_stock,ps_variation 4 ps_variation_sku,ps_variation 4 ps_variation_name,ps_variation 4 ps_variation_price,ps_variation 4 ps_variation_stock,ps_variation 5 ps_variation_sku,ps_variation 5 ps_variation_name,ps_variation 5 ps_variation_price,ps_variation 5 ps_variation_stock,ps_variation 6 ps_variation_sku,ps_variation 6 ps_variation_name,ps_variation 6 ps_variation_price,ps_variation 6 ps_variation_stock,ps_variation 7 ps_variation_sku,ps_variation 7 ps_variation_name,ps_variation 7 ps_variation_price,ps_variation 7 ps_variation_stock,ps_variation 8 ps_variation_sku,ps_variation 8 ps_variation_name,ps_variation 8 ps_variation_price,ps_variation 8 ps_variation_stock,ps_variation 9 ps_variation_sku,ps_variation 9 ps_variation_name,ps_variation 9 ps_variation_price,ps_variation 9 ps_variation_stock,ps_variation 10 ps_variation_sku,ps_variation 10 ps_variation_name,ps_variation 10 ps_variation_price,ps_variation 10 ps_variation_stock,ps_variation 11 ps_variation_sku,ps_variation 11 ps_variation_name,ps_variation 11 ps_variation_price,ps_variation 11 ps_variation_stock,ps_variation 12 ps_variation_sku,ps_variation 12 ps_variation_name,ps_variation 12 ps_variation_price,ps_variation 12 ps_variation_stock,ps_variation 13 ps_variation_sku,ps_variation 13 ps_variation_name,ps_variation 13 ps_variation_price,ps_variation 13 ps_variation_stock,ps_variation 14 ps_variation_sku,ps_variation 14 ps_variation_name,ps_variation 14 ps_variation_price,ps_variation 14 ps_variation_stock,ps_variation 15 ps_variation_sku,ps_variation 15 ps_variation_name,ps_variation 15 ps_variation_price,ps_variation 15 ps_variation_stock,ps_variation 16 ps_variation_sku,ps_variation 16 ps_variation_name,ps_variation 16 ps_variation_price,ps_variation 16 ps_variation_stock,ps_variation 17 ps_variation_sku,ps_variation 17 ps_variation_name,ps_variation 17 ps_variation_price,ps_variation 17 ps_variation_stock,ps_variation 18 ps_variation_sku,ps_variation 18 ps_variation_name,ps_variation 18 ps_variation_price,ps_variation 18 ps_variation_stock,ps_variation 19 ps_variation_sku,ps_variation 19 ps_variation_name,ps_variation 19 ps_variation_price,ps_variation 19 ps_variation_stock,ps_variation 20 ps_variation_sku,ps_variation 20 ps_variation_name,ps_variation 20 ps_variation_price,ps_variation 20 ps_variation_stock,ps_img_1,ps_img_2,ps_img_3,ps_img_4,ps_img_5,ps_img_6,ps_img_7,ps_img_8,ps_img_9";
    private static $CDHeaders = "Référence vendeur,EAN (Facultatif pour Mode et Maison),Marque,Nature du produit	,Code catégorie,	Libellé court panier,	Libellé long produit,	Description produit,	Image 1 (jpeg)	,Sku famille,	Taille (borné)	,Couleur marketing	,Description marketing,	Image 2 (jpeg)	,Image 3 (jpeg)	,Image 4 (jpeg)	,Navigation / classification / rayon	,ISBN	,MFPN,	Longueur (cm),	Largeur (cm),	Hauteur (cm),	Poids (kg)	,Avertissement(s),	Commentaire,Couleur(s),	Couleur  principale	,Genre	,Licence,	Sports	,Type de public";
    private static $walmartHeaders = <<<EOD
sku,productName,productIdType,productId,shortDescription,keyFeatures1,keyFeatures2,keyFeatures3,keyFeatures4,重量,keyFeatures5,ProductTaxCode,brand,manufacturer,manufacturerPartNumber,multipackQuantity,countPerPack,count,pieceCount,modelNumber,ProductIdUpdate,SkuUpdate,mainImageUrl,productSecondaryImageURL1,productSecondaryImageURL2,productSecondaryImageURL3,productSecondaryImageURL4,productSecondaryImageURL5,productSecondaryImageURL6,productSecondaryImageURL7,productSecondaryImageURL8,productSecondaryImageURL9,msrp,price,MinimumAdvertisedPrice,StartDate,EndDate,MustShipAlone,ShippingOverrideIsShippingAllowed,ShippingOverrideShipMethod,ShippingOverrideShipRegion,ShippingOverrideshipPrice,color,colorCategory,pattern,material,gender,ageGroup,clothingSizeGroup,clothingSize,isSet,ShippingWeightMeasure,ShippingWeightUnit,shipsInOriginalPackaging,variantGroupId,variantAttributeNames,isPrimaryVariant,swatchVariantAttribute,swatchImageUrl,isProp65WarningRequired,prop65WarningText,smallPartsWarnings,requiresTextileActLabeling,countryOfOriginTextiles,batteryTechnologyType,hasWarranty,warrantyURL,warrantyText,clothingTopStyle,dressShirtSize,sleeveStyle,sleeveLengthStyle,shirtNeckStyle,collarType,jacketStyle,suitBreastingStyle,sweaterStyle,scarfStyle,upperBodyStrapConfiguration,hatSize,hatStyle,braStyle,braSize,chestSizeMeasure,chestSizeUnit,pantRise,waistStyle,waistSizeMeasure,waistSizeUnit,pantySize,skirtLengthMeasure,skirtLengthUnit,legOpeningCut,pantLegCut,jeanStyle,jeanWash,jeanFinish,pantSize,pantFit,pantStyle,beltStyle,beltBuckleStyle,pantyStyle,shortsStyle,skirtAndDressCut,skirtLengthStyle,hosieryStyle,tightsSheerness,underwearStyle,sockSize,sockStyle,sockRise,materialName,materialPercentage,fabricCareInstructions,accentColor,clothingWeight,clothingStyle,clothingFit,clothingCut,clothingLengthStyle,fastenerType,swimsuitStyle,dressStyle,gotsCertification,theme,character,globalBrandLicense,sportsLeague,sportsTeam,occasion,activity,sport,season,weather,isMaternity,academicInstitution,athlete,autographedBy,braBandSizeMeasure,braBandSizeUnit,braCupSize,neckSizeMeasure,neckSizeUnit,sleeveLengthMeasure,sleeveLengthUnit,inseamMeasure,inseamUnit,isMadeFromRecycledMaterial,recycledMaterial,percentageOfRecycledMaterial,features,keywords,产品重量,产品成本,中文标题,产品状态
EOD;
    private static $LazadaHeaders = "sup,SKU别名,SKU,物流属性,出售状态,中文报关名,英文报关名,产品中文名称,产品英文名称,package,英文描述,Features,lazada平台描述,默认仓库名称,品牌,关键词,开发员,开发时间,分类,采购价格,产品重量,颜色,color,尺寸,包装尺寸长,包装尺寸宽,包装尺寸高,sku主图,sku图片1,sku图片2,sku图片3,sku图片4,sku图片5,sku图片6,sku图片7,sku图片8,sku图片9";


    private static function getWalmartData($products)
    {
        $rows = [];
        $goodsHelper = new GoodsHelp();
        if ($products) {
            foreach ($products as $product) {
                $goods_id = $product['id'];
                $category = Category::where('id', $product['category_id'])->find();
                $variants = $product['sku'];
                $lang = GoodsLang::where('goods_id', $product['id'])->where('lang_id', 2)->find();
                if ($lang) {
                    $row['productName'] = $lang['title'];
                    $row['shortDescription'] = $lang['description'];
                } else {
                    $row['productName'] = $row['shortDescription'] = '';
                }
                $row['中文标题'] = '';
                $langZh = GoodsLang::where('goods_id', $product['id'])->where('lang_id', 1)->find();
                if ($langZh) {
                    $row['中文标题'] = $langZh['title'];
                }

                $brand = Brand::where('id', $product['brand_id'])->find();
                $row['Brand'] = $brand ? $brand['name'] : '';
                $row['mainImageUrl'] = strpos($product['thumb'],'.jpg')!==false ? self::$imageUrl . $product['thumb'] : '';
                $row['weightUnit'] = 'lb';
                $row['productIdType'] = 'UPC';
                $row['productId'] = '';
                $row['ProductTaxCode'] = 2038711;
                $images = GoodsImage::getPublishImages($goods_id, self::$channel_id);
                $images = $images['spuImages'] ? $images['spuImages'] : [];
                foreach ($variants as $variant) {
                    $row['sku'] = $variant['sku'];
                    $row['price'] = $variant['retail_price'];
                    $row['产品重量'] = $variant['weight'];
                    $row['weight'] = $variant['weight'] * 0.0022046;
                    $row['重量'] = $variant['weight'] * 0.0022046;
                    $gallerys = GoodsGallery::where('sku_id', $variant['id'])->limit(9)->select();
                    $k = 1;
                    if ($gallerys) {
                        foreach ($gallerys as $gallery) {
                            $row['productSecondaryImageURL' . $k] = self::$imageUrl . $gallery['path'];
                            ++$k;
                        }
                    }
                    if ($k < 9) {
                        foreach ($images as $image) {
                            $row['productSecondaryImageURL' . $k] = self::$imageUrl . $image['path'];
                            ++$k;
                            if ($k > 9) {
                                break;
                            }
                        }
                    }
                    $color = $clothingSize = '';
                    $sku_attributes = json_decode($variant['sku_attributes'], true);
                    $temps = GoodsHelp::getAttrbuteInfoBySkuAttributes($sku_attributes, $goods_id);
                    foreach ($temps as $temp) {
                        if ($temp['id'] == 1) {
                            $color = $temp['value'];
                        } elseif (strpos($temp['name'], '尺码') !== false) {
                            $clothingSize = $temp['value'];
                        }
                    }
                    $row['color'] = $color;
                    $row['clothingSize'] = $color;
                    $row['产品成本'] = $variant['cost_price'];
                    $row['产品状态'] = $goodsHelper->sku_status[$variant['status']];
                    $rows[$variant['id']] = $row;
                }
            }
        }
        return $rows;
    }

    private static function getShopeeData($products)
    {
        $rows = [];
        if ($products) {
            $WarehouseGoods = new WarehouseGoods();
            foreach ($products as $product) {
                $goods_id = $product['id'];
                $category = Category::where('id', $product['category_id'])->find();
                if ($category) {
                    $row['ps_category_list_id'] = $category['title'];
                } else {
                    $row['ps_category_list_id'] = '';
                }
                $row['产品中文名称'] = $product['name'];

                $lang = GoodsLang::where('goods_id', $product['id'])->where('lang_id', 2)->find();
                if ($lang) {
                    $row['ps_product_name'] = $lang['title'];
                    $row['ps_product_description'] = $lang['description'];
                } else {
                    $row['ps_product_name'] = '';
                    $row['ps_product_description'] = '';
                }
                $row['产品中文描述'] = '';
                $langZh = GoodsLang::where('goods_id', $product['id'])->where('lang_id', 1)->find();
                if ($langZh) {
                    $row['产品中文描述'] = $langZh['description'];
                }

                $row['物流属性'] = self::$service->getProTransPropertiesTxt($product['transport_property']);
               // $cost_price = GoodsSku::where('goods_id', $product['id'])->min('cost_price');
                $weight = GoodsSku::where('goods_id', $product['id'])->max('weight');
                $row['ps_price'] = 0;
                $row['ps_stock'] = 0;
                $row['ps_product_weight'] = $weight ? number_format($weight / 1000, 2) : 0;
                $row['ps_days_to_ship'] = 2;

                $row['ps_sku_ref_no_parent'] = $product['spu'];
                $row['ps_mass_upload_variation_help'] = '';

                $skus = $product['sku'];
                $len = 1;
                $ps_stock = 0;
                $aPrice = [];
                foreach ($skus as $sku) {
                    $row['ps_variation ' . $len . ' ps_variation_sku'] = $sku['sku'];

                    $attr = json_decode($sku['sku_attributes'], true);
                    $attrs = GoodsHelp::getAttrbuteInfoBySkuAttributes($attr, $goods_id);
                    $tmp = [];
                    foreach ($attrs as $val) {
                        $tmp[] = $val['value'];
                    }
                    $price = $WarehouseGoods->getPrice($product['warehouse_id'],$sku['id']);
                    $price = $price?$price:$sku['cost_price'];
                    $row['ps_variation ' . $len . ' ps_variation_name'] = implode('_', $tmp);
                    $row['ps_variation ' . $len . ' ps_variation_price'] = $price;
                    $sku_stock = $WarehouseGoods->available_quantity($product['warehouse_id'],$sku['id']);
                    $row['ps_variation ' . $len . ' ps_variation_stock'] = $sku_stock;
                    $ps_stock = $ps_stock+$sku_stock;
                    $len = $len + 1;
                    $aPrice[] = $price;
                }
                $row['ps_price'] = $aPrice?max($aPrice):0;
                $row['ps_stock'] = $ps_stock;
                if ($len < 20) {
                    for ($len; $len < 21; ++$len) {
                        $row['ps_variation ' . $len . ' ps_variation_sku'] = '';
                        $row['ps_variation ' . $len . ' ps_variation_name'] = '';
                        $row['ps_variation ' . $len . ' ps_variation_price'] = '';
                        $row['ps_variation ' . $len . ' ps_variation_stock'] = '';
                    }
                }
                $k = 0;
                //$images = GoodsGallery::where('goods_id',$product['id'])->where('sku_id',0)->limit(9)->select();
                $gallerys = GoodsImage::getPublishImages($goods_id, self::$channel_id);
                if (empty($gallerys['spuImages'])) {//对应平台的找不到，找其它平台的
                    $channelIds = Channel::column('id');
                    foreach ($channelIds as $channelId) {
                        $gallerys = GoodsImage::getPublishImages($goods_id, $channelId);
                        if (!empty($gallerys['spuImages'])) break;
                    }
                }
                $images = $gallerys['spuImages'] ? $gallerys['spuImages'] : [];

                if ($images) {
                    foreach ($images as $image) {
                        $row['ps_img_' . ($k+1)] = self::$imageUrl . $image['path'];
                        if (++$k == 9) {
                            break;
                        }
                    }
                }
                if($k<9 && $gallerys['skuImages']){//不够9张，使用子产品图片
//                    $left = $k>1 ? (9-$k) : 9;
//                    $images = GoodsGallery::where('goods_id',$product['id'])->where('sku_id',1)->limit($left)->select();
//                    if($gallerys['skuImages']){
                    foreach ($gallerys['skuImages'] as $skuImage){
                        $row['ps_img_'.($k+1)]=self::$imageUrl.$skuImage['path'];
                        if (++$k == 9) {
                            break;
                        }
                    }
//                    }
//                    for ($k=$left;$k>0;--$k){
//                        $row['ps_img_'.($k+1)]="";
//                    }
                }
                for (; $k < 9; $k++) {
                    $row['ps_img_' . ($k+1)] = "";
                }
                $rows[$product['id']] = $row;
            }
        }
        return $rows;
    }

    private static function getDiscountData($products)
    {
        $rows = [];
        if ($products) {
            foreach ($products as $product) {
                $goods_id = $product['id'];
                $category = Category::where('id', $product['category_id'])->find();

                $skus = $product['sku'];
                $row['Sku famille'] = $product['spu'];
                $row['Nature du produit'] = count($skus) > 1 ? 'Variant' : 'Standard';
                $row['EAN (Facultatif pour Mode et Maison)'] = '';
                $brand = Brand::where('id', $product['brand_id'])->find();
                $row['Marque'] = $brand ? $brand['name'] : '';
                $row['Code catégorie'] = '';
                $row['Navigation / classification / rayon'] = '';
                $row['ISBN'] = $row['MFPN'] = '';
                $row['Avertissement(s)'] = $row['Commentaire'] = $row['Couleur(s)'] = $row['Couleur  principale'] = '';
                $row['Genre'] = $row['Licence'] = $row['Sports'] = '';
                $row['Type de public'] = '';
                $lang = GoodsLang::where('goods_id', $product['id'])->where('lang_id', 2)->find();
                if ($lang) {
                    $row['Libellé court panier'] = $lang['title'];
                    $row['Libellé long produit'] = $lang['title'];
                    $row['Description produit'] = $lang['description'];
                    $row['Description marketing'] = $lang['description'];
                } else {
                    $row['Libellé long produit'] = $row['Libellé court panier'] = '';
                    $row['Description marketing'] = $row['Description produit'] = '';
                }

                foreach ($skus as $sku) {
                    $row['Référence vendeur'] = $sku['sku'];

                    $attr = json_decode($sku['sku_attributes'], true);
                    $attrs = GoodsHelp::getAttrbuteInfoBySkuAttributes($attr, $goods_id);
                    $tmp = [];
                    $row['Couleur marketing'] = '';
                    foreach ($attrs as $val) {
                        if (strpos($val['name'], 'Color') !== false) {
                            $row['Couleur marketing'] = $val['value'];
                        } else {
                            $tmp[] = $val['value'];
                        }
                    }
                    $row['Longueur (cm)'] = $sku['length'] / 10;
                    $row['Largeur (cm)'] = $sku['width'] / 10;
                    $row['Hauteur (cm)'] = $sku['height'] / 10;
                    $row['Poids (kg)'] = $sku['weight'] / 1000;
                    $row['Taille (borné)'] = implode('_', $tmp);
                    $k = 2;

                    $gallerys = GoodsImage::getPublishImages($goods_id, self::$channel_id);
                    $images = $gallerys['spuImages'] ? $gallerys['spuImages'] : [];
                    $row['Image 1 (jpeg)'] = self::$imageUrl . $sku['thumb'];
                    if ($images) {
                        foreach ($images as $image) {
                            $row["Image $k (jpeg)"] = self::$imageUrl . $image['path'];
                            ++$k;
                            if ($k > 5) {
                                break;
                            }
                        }
                    }
                    for ($k; $k <= 5; $k++) {
                        $row["Image $k (jpeg)"] = "";
                    }
                    $rows[] = $row;
                }
            }
        }
        return $rows;
    }

    public static function download($ids, $channel_id = 9)
    {

        $products = Goods::whereIn('id', $ids)->whereIn('sales_status', [1, 4, 6])->with(['sku' => function ($query) {
            $query->whereIn('status', [1, 4, 6]);
        }])->select();
        static::$channel_id = $channel_id;
        $service = new GoodsHelp();
        self::$service = $service;
        $imageUrl = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;
        self::$imageUrl = $imageUrl;

        switch ($channel_id) {
            case 5:
                $rows = self::getDiscountData($products);
                break;
            case 9:
                $rows = self::getShopeeData($products);
                break;
            case 11:
                $rows = self::getWalmartData($products);
                break;
            case 6:
                $rows = self::getLazadaData($products);
                break;
            default:
                break;
        }

        $header = self::createHeaders($channel_id);
        if ($channel_id == 11) {
            $rows = self::createWalmartData($rows, $header);
        }

        $file = [
            'name' => '导出刊登商品' . now(),
            'path' => 'goods'
        ];
        $result = Excel::exportExcel2007($header, $rows, $file);
        return $result;
        //return self::exportCsv($rows, $header, $file);
    }

    private static function createWalmartData($rows, $headers)
    {
        foreach ($headers as $header) {
            $key = $header['key'];
            foreach ($rows as &$row) {
                if (!isset($row[$key])) {
                    $row[$key] = '';
                }
            }
        }
        return $rows;
    }

    private static function createWalmartHeaders()
    {
        $titles = self::$walmartHeaders;
        $array = explode(',', $titles);
        $headers = [];
        foreach ($array as $item) {
            $encode = mb_detect_encoding($item, array("ASCII", 'UTF-8', 'GB2312', "GBK", 'BIG5'));
            if ($encode == 'GBK' || $encode == 'GB2312') {
                $item = mb_convert_encoding($item, 'utf-8', $encode);
            }
            $header['width'] = 20;
            $header['need_merge'] = 0;
            $header['title'] = $header['key'] = trim($item);
            array_push($headers, $header);
        }
        return $headers;
    }

    public static function exportCsv($lists, $header, $file = [])
    {
        $result = ['status' => 0, 'message' => 'error'];
        try {
            $aHeader = [];
            foreach ($header as $v) {
                $v['title'] = mb_convert_encoding($v['title'], 'gb2312', 'utf-8');
                $aHeader[] = $v['title'];
            }
            $fileName = $file['name'] . date('YmdHis');
            $downFileName = $fileName . '.csv';
            $file = ROOT_PATH . 'public' . DS . 'download' . DS . $file['path'];
            $filePath = $file . DS . $downFileName;
            //无文件夹，创建文件夹
            if (!is_dir($file) && !mkdir($file, 0777, true)) {
                $result['message'] = '创建文件夹失败。';
                @unlink($filePath);
                return $result;
            }
            $fp = fopen($filePath, 'a');
            fputcsv($fp, $aHeader);
            foreach ($lists as $i => $row) {
                $rowContent = [];
                foreach ($header as $h) {
                    $field = $h['key'];
                    $value = isset($row[$field]) ? $row[$field] : '';
                    $value = mb_convert_encoding($value, 'gb2312', 'utf-8');
                    $rowContent[] = $value;
                }
                fputcsv($fp, $rowContent);
            }
            fclose($fp);
            try {
                $logExportDownloadFiles = new LogExportDownloadFiles();
                $data = [];
                $data['file_extionsion'] = 'csv';
                $data['saved_path'] = $filePath;
                $data['download_file_name'] = $downFileName;
                $data['type'] = 'supplier_export';
                $data['created_time'] = time();
                $data['updated_time'] = time();
                $logExportDownloadFiles->allowField(true)->isUpdate(false)->save($data);
                $udata = [];
                $udata['id'] = $logExportDownloadFiles->id;
                $udata['file_code'] = date('YmdHis') . $logExportDownloadFiles->id;
                $logExportDownloadFiles->allowField(true)->isUpdate(true)->save($udata);
            } catch (\Exception $e) {
                $result['message'] = '创建导出文件日志失败。' . $e->getMessage();
                @unlink($filePath);
                return $result;
            }
            $result['type'] = '.xls';
            $result['status'] = 1;
            $result['message'] = 'OK';
            $result['file_code'] = $udata['file_code'];
            $result['file_name'] = $fileName;
            return $result;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private static function createHeaders($channel_id)
    {
        switch ($channel_id) {
            case 5:
                $titles = self::$CDHeaders;
                break;
            case 9:
                $titles = self::$ShopeeHeaders;
                break;
            case 11:
                $titles = self::$walmartHeaders;
                break;
            case 6:
                $titles = self::$LazadaHeaders;
                break;
            default:
                break;
        }
        $array = explode(',', $titles);

        $headers = [];
        foreach ($array as $item) {
            $encode = mb_detect_encoding($item, array("ASCII", 'UTF-8', 'GB2312', "GBK", 'BIG5'));
            if ($encode == 'GBK' || $encode == 'GB2312') {
                $item = mb_convert_encoding($item, 'utf-8', $encode);
            }
            $header['width'] = 20;
            $header['need_merge'] = 0;
            $header['title'] = $header['key'] = trim($item);
            array_push($headers, $header);
        }
        return $headers;
    }

    private static function attribute($sku)
    {
        $sku_attributes = json_decode($sku['sku_attributes'], true);
        $sku['name'] = '';
        foreach ($sku_attributes as $attribute_id => $attribute_value_id) {
            list($attr, $attr_id) = explode('_', $attribute_id);//$attr_id //属性名

            $attrKeyVal = (new AttributeValue())->field('a.value,a.code vcode,b.code')->alias('a')->join('attribute b', 'a.attribute_id=b.id', 'LEFT')
                ->where(['a.id' => $attribute_value_id, 'a.attribute_id' => $attr_id])->find();

            if ($attrKeyVal) {
                //如果类型是type获取style,则取goods_attribute表里的alias
                if ($attrKeyVal['code'] == 'type' || $attrKeyVal['code'] == 'style') {
                    $where = [
                        'goods_id' => ['=', $sku['goods_id']],
                        'attribute_id' => ['=', $attr_id],
                        'value_id' => ['=', $attribute_value_id]
                    ];

                    $goodsAttribute = GoodsAttribute::where($where)->find();

                    if ($goodsAttribute) {
                        if (strlen($sku['name'])) {
                            $sku['name'] = $sku['name'] . '_' . $goodsAttribute['alias'];
                        } else {
                            $sku['name'] = $sku['name'] . $goodsAttribute['alias'];
                        }
                    }
                } else {

                    if (count($sku_attributes) > 2) {
                        if (strlen($sku['name']) > 0) {
                            $sku['name'] = $sku['name'] . '_' . $attrKeyVal['value'];
                        } else {
                            $sku['name'] = $attrKeyVal['value'];
                        }
                    } else {
                        $sku['name'] = $attrKeyVal['value'];
                    }
                }
            }
        }

        return $sku['name'];
    }

    /**
     * 获取用户名 根据id
     * @param int $id
     * @return string
     */

    private static function getLazadaData($products)
    {
        $rows = [];
        if ($products) {
            $userCache = Cache::store('user');
            foreach ($products as $product) {
                $goods_id = $product['id'];
                $variants = $product['sku'];
                $row['sup'] = $product['spu'];
                $row['物流属性'] = self::$service->getProTransPropertiesTxt($product['transport_property']);
                $row['出售状态'] = self::$service->sales_status[$product['sales_status']];
                $row['中文报关名'] = $product['declare_name'];
                $row['英文报关名'] = $product['declare_en_name'];
                $row['产品中文名称'] = $product['name'];
                $platform_sale = json_decode($product['platform_sale'], true);
                $row['lazada平台描述'] = (isset($platform_sale['lazada']) && $platform_sale['lazada'] == 1) ? '可选上架' : '禁止上架';
                $row['英文描述'] = '';
                $row['产品英文名称'] = '';
                $langEn = GoodsLang::where('goods_id', $product['id'])->where('lang_id', 2)->find();;
                if ($langEn) {
                    $dec= str_replace(array("\r\n","\r","\n"),"<br>",$langEn['description']);
                    $row['英文描述']=str_replace(PHP_EOL,"<br>",$dec);
                    $row['产品英文名称'] = $langEn['title'];
                }
                $start=['Features','Product features','Parameters','Specification','Product description','Description'];
                $end=['Package:','Package List:','Packing List:','Packaging:','Package Listing:','Package includes:','Package include:','Product List:','Product packaging:','Product Description:'];
                foreach ($start as $v){
                    if(stristr($langEn['description'],$v)!==false){
                        foreach ($end as $value){
                            if(stristr($langEn['description'],$value)!==false){
                                $features=substr($langEn['description'], strpos($langEn['description'], $v),(strlen($langEn['description']) - stripos($langEn['description'], $value))*(-1));
                                $features= str_replace(array("\r\n","\r","\n"),"<br/>",$features);
                                $features=str_replace(PHP_EOL,"<br/>",$features);
                                break;
                            }
                        }
                        if(isset($features) && !$features){
                            $features=$features? stristr($langEn['description'], $v):'';
                            $features= str_replace(array("\r\n","\r","\n"),"<br/>",$features);
                            $features=str_replace(PHP_EOL,"<br/>",$features);
                        }
                        break;
                    }
                    unset($v);
                    unset($value);
                }
                foreach ($end as $value){
                    if(stristr($langEn['description'],$value)){
                        $package=stristr($langEn['description'], $value);
                        $package= str_replace(array("\r\n","\r","\n"),"<br/>",$package);
                        $package=str_replace(PHP_EOL,"<br/>",$package);
                        break;
                    }
                    unset($value);
                }

                $row['Features']=$features??$langEn['description'];
                $row['package'] =$package??'';
                unset($features);
                unset($package);
                $row['默认仓库名称'] = self::$service->getWarehouseById($product['warehouse_id']);
                $data['goods_id'] = $goods_id;
                $brand = Brand::where('id', $product['brand_id'])->find();
                $row['品牌'] = $brand ? $brand['name'] : '';
                $row['关键词'] = $product['keywords'];
                $developerCacheInfo = $userCache->getOneUser($product['developer_id']);
                $row['开发员'] = $developerCacheInfo['realname']??'';
                $row['开发时间'] = date('Y-m-d', $product['create_time']);
                $row['分类'] = self::$service->mapCategory($product['category_id']);;
                $weight = 0;
                foreach ($variants as $variant) {
                    $row['SKU'] = $variant['sku'];
                    $row['SKU别名'] = $variant['alias_sku'];
                    $row['采购价格'] = $variant['cost_price'];
                    $row['产品重量'] = $variant['weight'];
                    $attr = json_decode($variant['sku_attributes'], true);
                    $attrs = GoodsHelp::getAttrbuteInfoBySkuAttributes($attr, $goods_id);
                    $weight += $variant['weight'];
                    $tmp = [];
                    foreach ($attrs as $val) {
                        $tmp[] = $val['value'];
                    }
                    $color=isset($tmp[0])?explode('|', $tmp[0]):'';
                    $row['颜色'] =$color[0]??'' ;
                    $row['color'] = $color[1]??'';
                    $row['尺寸'] = $tmp[1]??'';
                    $row['包装尺寸长'] = ($variant['length'] / 10);
                    $row['包装尺寸宽'] = ($variant['width'] / 10);
                    $row['包装尺寸高'] = ($variant['height'] / 10) ;
                    $row['sku主图'] = $variant['thumb'];
                    $gallerys = GoodsGallery::where('sku_id', $variant['id'])->limit(9)->select();
                    $k = 1;
                    if ($gallerys) {
                        foreach ($gallerys as $gallery) {
                            $pic = self::$imageUrl . $gallery['path'];
                            $row['sku图片' . $k] = empty($pic) ? '' : $pic;
                            ++$k;
                        }
                        for ($k; $k <= 9; $k++) {
                            $row['sku图片' . $k] = "";
                        }
                    } else {
                        $row['sku图片1'] = '';
                        $row['sku图片2'] = '';
                        $row['sku图片3'] = '';
                        $row['sku图片4'] = '';
                        $row['sku图片5'] = '';
                        $row['sku图片6'] = '';
                        $row['sku图片7'] = '';
                        $row['sku图片8'] = '';
                        $row['sku图片9'] = '';
                    }
                    $rows[$variant['id']] = $row;
                    unset($variant);
                    unset($product);
                }

                unset($row);

            }
        }
        return $rows;
    }


}