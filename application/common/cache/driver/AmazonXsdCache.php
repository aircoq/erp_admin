<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/9/5
 * Time: 15:57
 */

namespace app\common\cache\driver;
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use app\common\model\amazon\AmazonCategoryXsd;
use app\common\model\amazon\AmazonCategoryAttributeXsd;
use app\common\model\amazon\AmazonCategoryAttributeValueXsd;
use app\common\model\amazon\AmazonCategoryExcelAttributeValue;
use app\common\model\amazon\AmazonXsdVariantMap;

class AmazonXsdCache extends Cache{
    const ATTR_KEY = "cache:amazon_attr_"; //属性缓存KEY
    const BASE_ATTR = 'base_attr'; //基本可选填的属性
    const COMMON_ATTR = 'common_attr'; //普通属性
    const VARIANT_ATTR = 'variant_attr'; //多属性

    /**
     * @param $site
     * @param $categoryId
     * @return array|false|mixed|\PDOStatement|string|\think\Collection
     */
    public function getBaseAttributeListByCatId($site,$categoryId,$index = 0){
        $key = self::ATTR_KEY.$site."_".$categoryId;
//        if($index == 0){
//            $this->redis->delete($key);
//        }
//        if ($this->redis->exists($key) && ($data = $this->redis->hGet($key,self::BASE_ATTR))) {
//            return json_decode($data,true);
//        }

        $attributeModel = new AmazonCategoryAttributeXsd();
        $baseList = $attributeModel->getBaseAttributeList();
        return $baseList = $this->formateAttributeData($baseList,$site);
//        if($baseList){
//            $baseList = $this->formateAttributeData($baseList,$site);
//            $this->redis->hSet($key,self::BASE_ATTR,json_encode($baseList));
//            return $this->getBaseAttributeListByCatId($site,$categoryId,1);
//        }else{
//            return array();
//        }
    }


    /**
     * 获取XSD模板分类对应的普通属性
     * @param $site
     * @param $categoryId
     */
    public function getCommonAttributeListByCatId($site,$categoryId){
//        $key = self::ATTR_KEY.$site."_".$categoryId;
//        if ($this->redis->exists($key) && ($data = $this->redis->hGet($key,self::COMMON_ATTR)) ) {
//            return json_decode($data,true);
//        }

        $attributeModel = new AmazonCategoryAttributeXsd();
        $commonList = $attributeModel->getCommonAttributeListByCategoryId($categoryId);
        return $baseList = $this->formateAttributeData($commonList,$site);


//        if($commonList){
//            $commonList = $this->formateAttributeData($commonList,$site);
//            $this->redis->hSet($key,self::COMMON_ATTR,json_encode($commonList));
//            return $this->getCommonAttributeListByCatId($site,$categoryId);
//        }else{
//            return array();
//        }

    }


    /**
     * 得到可选的多属性模板
     * @param $site
     * @param $categoryId
     */
    public function getVariantAttributeListByCatId($site,$categoryId){
//        $key = self::ATTR_KEY.$site."_".$categoryId;
//
//        if ($this->redis->exists($key) && ($data = $this->redis->hGet($key,self::VARIANT_ATTR)) ) {
//            return json_decode($data,true);
//        }

        $attributeModel = new AmazonCategoryAttributeXsd();
        $variantList = $attributeModel->getVariantAttributeListByCategoryId($categoryId);

        if($variantList){
            if($variantList[0]['is_hand'] || $variantList[0]['is_match_excel']){
                $isMatch = 1;
            }else{
                $isMatch = 0;
            }
            $variantList = $this->getValueListBySite($site,$categoryId,$variantList[0]['id'],$isMatch);
            return $variantList;
//            $this->redis->hSet($key,self::VARIANT_ATTR,json_encode($variantList));
//           return $this->getVariantAttributeListByCatId($site,$categoryId);
        }else{
            return array();
        }
    }


    public function deleteAttributeCacheByCatId($site,$categoryId){
        if($this->redis->exists(self::ATTR_KEY.$site."_".$categoryId)){
            $this->redis->delete(self::ATTR_KEY.$site."_".$categoryId);
        }

    }

    public function formateAttributeData($data,$site){
        $excelValModel = new AmazonCategoryExcelAttributeValue();
        $xsdValModel = new AmazonCategoryAttributeValueXsd();
        foreach($data as $key => $val){
            $sites = $this->explodeToArray($val['sites']);
            $data[$key]['enable'] = in_array($site,$sites) ? true : false;
            $data[$key]['is_has_children'] = $val['is_has_children'] ? true : false;
            $data[$key]['is_requried'] = $val['is_requried'] ? true : false;
            $data[$key]['is_select'] = $val['is_select'] ? true : false;
            $data[$key]['is_public'] = $val['is_public'] ? true : false;
            $data[$key]['is_hand'] = $val['is_hand'] ? true : false;
            $data[$key]['is_sku'] = $val['is_sku'] ? true : false;
            if($val['is_hand'] || $val['is_match_excel']){
                $isMatch = 1;
            }else{
                $isMatch = 0;
            }
            $data[$key]['values'] = $val['is_select'] ? $this->getValueListByAttrId($xsdValModel,$excelValModel,$site,$val['id'],$isMatch) : "";
        }
        return $data;
    }


    /**通过站点获取属性对应的属性值列表
     * @param $xsdValModel
     * @param $excelValModel
     * @param $site
     * @param $attrId
     * @param $isMatch
     */
    private function getValueListByAttrId($xsdValModel,$excelValModel,$site,$attrId,$isMatch){
        if($isMatch){
            $valueList = $excelValModel->field('name')->where(['site' => $site,'xsd_attr_id' => $attrId])->select();
            return $this->arrayToString($valueList,'name');
        }else{
            $valueList = $xsdValModel->field('value')->where(["attribute_id" => $attrId])->select();
            return $this->arrayToString($valueList,'value');
        }
    }

    private function getVariantValueListBySite($site,$categoryId,$attrId,$isMatch){
        $xsdValModel = new AmazonCategoryAttributeValueXsd();
        $valueList = $xsdValModel->field('id,name')->where(['site' => $site,'id' => $attrId])->select();
    }

    private function getValueListBySite($site,$categoryId,$attrId,$isMatch){

        $excelValModel = new AmazonCategoryExcelAttributeValue();
        $xsdValModel = new AmazonCategoryAttributeValueXsd();
        $valueList = $excelValModel->field('id,name')->where(['site' => $site,'xsd_attr_id' => $attrId])->group('name')->select();

        return $this->getSkuAttriubtes($site,$categoryId,$valueList,'name');
    }


    private function getSkuAttriubtes($site,$categoryId,$data,$key){
        $attributeMdoel = new AmazonCategoryAttributeXsd();
        $variantModel = new AmazonXsdVariantMap();
        $result = array();
        foreach($data as  $val){
            $temp = array(
                'variant' =>    $val[$key],
                "variant_id" => $val["id"],
                'selected' => $variantModel->getVarinatsByCatId($categoryId,$val['id'],'xsd_id,xsd_name'),
            );
            array_push($result,$temp);
        }

        return $result;
    }


    private function arrayToString($array,$key){
        if(is_array($array) && count($array)){
            $data = array();
            foreach($array as $val){
                $data[] = $val[$key];
            }
            $data = array_unique($data);
            $string = "";
            foreach($data as $val){
                $string .= $val.",";
            }
            return trim($string,",");
        }else{
            return "";
        }
    }

    private function explodeToArray($sites){
        if(mb_substr_count($sites,',')){
            $sites = explode(",",$sites);
        }else{
            $sites = array($sites);
        }
        return $sites;
    }
}