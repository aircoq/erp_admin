<?php
namespace app\index\service;
use app\common\model\Category;
use app\common\model\Goods;
use app\common\model\GoodsSku;
use app\common\model\GoodsLang;
use app\common\model\GoodsSkuAlias;
use app\common\model\Attribute;
use app\common\model\AttributeValue;
use app\common\model\Supplier;
use app\common\model\SupplierGoodsOffer;
use app\common\model\Packing;
use app\common\model\CategoryAttribute;
use app\common\model\GoodsAttribute;
use app\goods\service\GoodsHelp;
use app\common\model\amazon\AmazonAccount;
use app\common\model\wish\WishAccount;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\ebay\EbayAccount;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 * 导入数据时用，(项目运行正常时，此类可删除)
 */
class ImportData
{
    private static $category_map = [
        '汽车养护工具' => 131,
        '厨房&餐厅用品' => 45,
        '办公电子'     => 28,
        '休闲时尚（女）'=> 8,
        '休闲时尚（男）'=> 2,
        '家居（女）'=> 10,
        '职业（男）' => 3,
        '情趣' => 121
    ];
    /**
     * 导入供应商
     * @param string $name
     * @return $supplierId : 返回供应商ID
     */
    public static function addSupplier($name = '')
    {
        //供应商
        $supplierId = 0;
        $supplierModel = new Supplier();
        $supplier = $supplierModel->field('id')->where('company_name', $name)->find();
        if (!$supplier) {
            //供应商数组
            if ($name) {
                $supplier = [
                    'company_name' => $name,
                    'create_time'  => time()
                ];
                $supplierModel->data($supplier, true)->isUpdate(false)->save();
                $supplierId = $supplierModel->id;
            }
        } else {
            $supplierId = $supplier['id'];
        }
        return $supplierId;
    }
    
    /**
     * 导入包装材料
     * @param string $name
     * @param string $strPackingVolume
     * @param string $costPrice
     * @return $packingId ：返回包装材料ID
     */
    public static function addPack($name = '', $strPackingVolume = '', $costPrice = '')
    {
        $packingModel = new Packing();
        $packingId = 0;
        $packing = $packingModel->field('id')->where('title', $name)->find();
        if ($packing) {
            $packingId = $packing['id'];
        } else {
            $pHeight = $pwidth = $pdepth = 0;
            if ($strPackingVolume) {
                $packingVolumeArr = explode('*', $strPackingVolume);
                $pHeight = $packingVolumeArr[2];
                $pwidth = $packingVolumeArr[1];
                $pdepth = $packingVolumeArr[0];
            }
            $packingData = [
                'title'        => $name,
                'cost_price'   => $costPrice,
                'width'        => $pwidth,
                'depth'        => $pdepth,
                'height'       => $pHeight,
            ];
            $packingModel->data($packingData, true)->isUpdate(false)->save();
            $packingId = $packingModel->id;
        }
        return $packingId;
    }
    
    /**
     * 导入分类
     * @param string $name
     * @return $categoryId ：返回分类ID
     */
    public static function addCategory($name = '', $type = false)
    {
        $ds = '/';
        $categoryId = 0;
        $categoryModel = new Category();
        if ($name) {
            //导入赛和数据
            if ($type) {
                $name = str_replace(' >> ', '/', $name);
                $cateogryArr = explode($ds, $name);
                $pid = 0;
                foreach ($cateogryArr as $k=>$v) {
                    $categoryName = $v;
                    $category = $categoryModel->field('id')->where('name', $categoryName)->find();
                    if ($category) {
                        $categoryId = $category['id'];
                        $pid = $category['id'];
                    } else {
                        $categoryData['name'] = $categoryName;
                        $categoryData['title'] = $categoryName;
                        $categoryData['pid'] = $pid;
                        $categoryModel->data($categoryData)->isUpdate(false)->save();
                        $categoryId = $categoryModel->id;
                        $pid = $categoryId;
                    }
                }
            } else {
            //导入通途数据
                if (strpos($name, $ds)) {
                    $cateogryArr = explode($ds, $name);
                    $categoryName = str_replace(' ', '', $cateogryArr[1]);
                } else {
                    // $categoryName = str_replace(' ', '', $name);
                    $categoryName = trim($name);
                }
                $category = $categoryModel->field('id')->where('pid > 0 and name = "'. $categoryName .'"')->find();
                if ($category) {
                    $categoryId = $category['id'];
                } else if (isset(self::$category_map[$categoryName])) {
                    $categoryId = self::$category_map[$categoryName];
                } else {
                    $categoryId = 0;
                }
            }
        }
        return $categoryId;
    }
    
    /**
     * 导入商品
     * @param unknown $data
     * @return $goodsId ：返回商品ID
     */
    public static function addGoods($data = [])
    {
        $goodsId = 0;
        $goodsModel = new Goods();
        $goods = $goodsModel->field('id')->where('spu', $data['spu'])->find();
        if (!$goods) {
            $goodsModel->data($data, true)->isUpdate(false)->save();
            $goodsId = $goodsModel->id;
        } else {
            if ($data['category_id'] !== 0)$goodsModel->data($data, true)->where(['id' => $goods['id']])->update(['category_id' => $data['category_id']]);
            $goodsId = $goods['id'];
        }
        return $goodsId;
    }
    
    /**
     * 产品描述
     * @param unknown $data
     * @return $goodsId ：返回商品ID
     */
    public static function addDescription($data = [])
    {
        $goodsLang = new GoodsLang();
        $goods = $goodsLang->field('*')->where(['goods_id' => $data['goods_id'], 'lang_id' => $data['lang_id']])->find();
        if (!$goods) {
            $goodsLang->data($data, true)->isUpdate(false)->save();
        }
    }
    /**
     * 导入属性值(赛和)
     * @param string $name : 属性值
     * @param string $attribute_id ：属性ID
     * @return $attributeValueId ：返回属性ID
     */
    public static function addAttributeValue($name = '', $attribute_id = 0)
    {
        $attributeValueId = 0;
        $attributeValueModel = new AttributeValue();
        $attributeValue = $attributeValueModel->field('id')->where('value', $name)->find();
        if ($attributeValue) {
            $attributeValueId = $attributeValue['id'];
        } else {
            $data['attribute_id'] = $attribute_id;
            $data['code'] = $name;
            $data['value'] = $name;
            $data['create_time'] = time();
            $attributeValueModel->data($data, true)->isUpdate(false)->save();
            $attributeValueId = $attributeValueModel->id;
        }
        return $attributeValueId;
    }
    /**
     * 导入商品对应的sku
     * @param unknown $data : 商品信息
     * @return $skuId ：sku ID
     */
    public static function addGoodsSku($data = [])
    {
        // $skuId = 0;
        // $goodsSkuModel = new GoodsSku();
        // $goodsSkuModel->data($data, true)->isUpdate(false)->save();
        // $skuId = $goodsSkuModel->id;
        // return $skuId;
        $goodsSkuModel = new GoodsSku();
        $res = $goodsSkuModel->where(array("goods_id"=>$data['goods_id'],"sku"=>$data['sku']))->find();
        if($res){#更新
            $goodsSkuModel->where(array("goods_id"=>$data['goods_id'],"sku"=>$data['sku']))->update($data);
            return $res['id'];
        }else{#添加
            $GoodsSkuId = $goodsSkuModel->where(array("goods_id"=>$data['goods_id'],"sku"=>$data['sku']))->insertGetId($data);
            return $GoodsSkuId;
        }
    }
    
    /**
     * 导入商品属性
     * @param unknown $data : 商品属性信息
     */
    public static function addGoodsAttr($data = [])
    {
        $goodsAttrModel = new GoodsAttribute();
        $where['goods_id'] = $data['goods_id'];
        $where['attribute_id'] = $data['attribute_id'];
        $where['value_id'] = $data['value_id'];
        $goodsAttr = $goodsAttrModel->where($where)->find();
        if (!$goodsAttr) {
            $goodsAttrModel->data($data, true)->isUpdate(false)->save();
        }
    }
    
    /**
     * 导入分类属性
     * @param unknown $data
     */
    public static function addCategoryAttr($data = [])
    {
        $where['category_id'] = $data['category_id'];
        $where['attribute_id'] = $data['attribute_id'];
        $categoryAttrModel = new CategoryAttribute();
        $categoryAttr = $categoryAttrModel->where($where)->find();
        if ($categoryAttr) {
            $value = $data['value_range'];
            /*$attrValueArr = $categoryAttr['value_range'];
            $attrValueArr = (array)$attrValueArr;
            $attrValue = strval(trim($attrValueArr[0], '[]'));
            //不存在则追加
            if (!preg_match("/($value)+/is", $attrValue)) {
                $attrValue .= ','. $value;
                $insertData = '['.$attrValue.']';
                $categoryAttrModel->where($where)->setField('value_range', $insertData);
            }*/
            $attrValueArr = json_decode($categoryAttr['value_range'], true);
            if (!in_array($value, $attrValueArr)) {
                array_push($attrValueArr, $value);
                $insertData = json_encode($attrValueArr);
                $categoryAttrModel->where($where)->setField('value_range', $insertData);
            }
        } else {
            $value = $data['value_range'];
            $data['value_range'] = '['.$value.']';
            $categoryAttrModel->data($data, true)->isUpdate(false)->save();
        }
    }
    
    /**
     * 商品sku对应的提供商报价
     * @param unknown $data
     * @return $skuGoodsOfferId ：sku对应的供应商报价ID
     */
    public static function addSupplierOfferGoodsSku($data = [])
    {
        $skuGoodsOfferId = 0;         
        $supplierOfferModel = new SupplierGoodsOffer();
        $supplierOfferModel->data($data, true)->isUpdate(false)->save();
        $skuGoodsOfferId = $supplierOfferModel->id;
        return $skuGoodsOfferId;
    }
    
    /**
     * 获取账号
     * @param string $account
     */
    public function getAccount($account)
    {
        $account = str_replace('-', '', $account);
        $account_name = strtolower($account);
        $account_info = AmazonAccount::where(['account_name' => $account_name])->find();
        if ($account_info) {
            return ['channel_id' => 2, 'account_id' => $account_info['id']];
        }
        
        $account_info = EbayAccount::where(['account_name' => $account_name])->find();
        if ($account_info) {
            return ['channel_id' => 2, 'account_id' => $account_info['id']];
        }
        
        $account_info = AliexpressAccount::where(['account_name' => $account_name])->find();
        if ($account_info) {
            return ['channel_id' => 2, 'account_id' => $account_info['id']];
        }
        
        $account_info = WishAccount::where(['account_name' => $account_name])->find();
        if ($account_info) {
            return ['channel_id' => 2, 'account_id' => $account_info['id']];
        }
        
        return [];
    }
    
    public function getSkuIdBySku($sku)
    {
        $info = GoodsSku::where(['sku' => trim($sku)])->find();
        if ($info) {
            return $info['id'];
        }
        
        return 0;
    }
}