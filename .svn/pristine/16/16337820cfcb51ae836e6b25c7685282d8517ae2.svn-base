<?php
namespace app\publish\service;

use app\common\exception\JsonErrorException;
use think\Db;
use think\Exception;
use app\common\model\amazon\AmazonCategoryAttributeMap;
use app\common\model\amazon\AmazonCategoryXsd;
use app\common\model\amazon\AmazonCategoryAttributeXsd;
use app\common\model\amazon\AmazonCategoryAttributeValueXsd;
use app\common\model\amazon\AmazonCategoryExcelAttributeValue;
use app\common\cache\driver\AmazonXsdCache;
use app\common\service\ImportExport;
use app\common\service\ImportXsd;

class AmazonXsdService
{
    private $_categoryXsdModel = null;
    private $_categoryAttrXsdModel = null;
    private $_attrValXsdModel = null;
    public function __construct()
    {
        $this->_categoryXsdModel = new AmazonCategoryXsd();
        $this->_categoryAttrXsdModel = new AmazonCategoryAttributeXsd();
        $this->_attrValXsdModel = new AmazonCategoryAttributeValueXsd();
    }

    public function mainPlain($element,$fElementName = '',$level = 1,$parentId = 0,$parentNode=''){
        if(method_exists($element,'getName') && ($categoryName = $element->getName())){
          if($level == 1){
              $categoryData = array('f_id' => 0, 'category_name' => $categoryName,'node_tree' => $categoryName);
              $parentId = $this->_categoryXsdModel->saveCategory($categoryData);
              $parentNode = $categoryData['node_tree'];
          }
        }

        if(method_exists($element,'getType')){
            $class = get_class($element->getType());
            switch ($class){
                    case 'GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType':
                            $this->handleComplexType($element->getType(),$categoryName,$level,$parentId,$parentNode);
                    case 'GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType':
                            $this->handleSimpleType($element->getType(),$categoryName,$level,$parentId,$parentNode);
                default :
                         break;
            }
        }
    }

    private function saveCategory($parentId,$categoryName,$nodeTree){
        $categoryData = array('f_id' => $parentId, 'category_name' => $categoryName,'node_tree' => $nodeTree);
        return $this->_categoryXsdModel->saveCategory($categoryData);
    }

    private function saveCategoryAttribute($categoryId,$name,$fid,$nodeTree,$type='',$min=0,$max=1){
        $xsdAttrData = array(
            'category_id' => $categoryId, 'name' => $name,
            'type' => $type,
            'min_occurs' => $min,
            'max_occurs' => $max,
            'f_id'       => $fid,
            'node_tree' => $nodeTree,
        );
        return $this->_categoryAttrXsdModel->saveCategoryAttribute($xsdAttrData);
    }



    private function handleComplexType($element,$fName,$level,$parentId,$parentNode){
        foreach($element->getElements() as $chlidComElement){
            $name = $chlidComElement->getName();
            $tmpId = 0;
            $tmpNode = '';
            if( $name && $fName != 'ProductType'){
                $tmpNode = $parentNode.'-'.$name;
                if(method_exists($chlidComElement,'getType')){
                    $typeName = $chlidComElement->getType()->getName();
                }else{
                    $typeName = '';
                }
                $min = method_exists($chlidComElement,'getMin') ? $chlidComElement->getMin() :0;
                $max = method_exists($chlidComElement,'getMax') ? $chlidComElement->getMax() :1;
                if($level == 1){
                    $tmpId = $this->saveCategoryAttribute($parentId,$name,0,$tmpNode,$typeName,$min,$max);
                }else{
                    $parentInfo = $this->_categoryAttrXsdModel->field('id,category_id')->where(['id' => $parentId,'node_tree' => $parentNode])->find();
                    $this->_categoryAttrXsdModel->where(['id' => $parentInfo['id']])->update(array('is_has_children' => 1));
                    $tmpId = $this->saveCategoryAttribute($parentInfo ? $parentInfo['category_id'] : $parentId,$name,$parentInfo ? $parentInfo['id'] : 0,$tmpNode,$typeName,$min,$max);
                }
            }else{
                $parentInfo = $this->_categoryAttrXsdModel->field('category_id')->where(['id' => $parentId])->find();
                $categoryData = array('f_id' => $parentInfo['category_id'], 'category_name' => $name,'node_tree' => $parentNode.'-'.$name);
                $tmpId = $this->_categoryXsdModel->saveCategory($categoryData);
                $tmpNode = $categoryData['node_tree'];
            }

            $class = get_class($chlidComElement);


            if($class == 'GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef'){
             //   echo $name;exit;

            }else if($class == 'GoetasWebservices\XML\XSDReader\Schema\Element\Element'){

            }else{
                echo 22;exit;
            }

            if(method_exists($chlidComElement,'getType')){
                $type = $chlidComElement->getType();
//                if($type->getName() == ''){
//                    echo 33;exit;
//                }
                $this->mainPlain($chlidComElement,$name,$level+1,$tmpId ? $tmpId :$parentId,$tmpNode ? $tmpNode :$parentNode);
            }else{

            }

        }
    }


    private function handleSimpleType($element,$fName,$level,$parentId,$parentNode){
        if(method_exists($element,'getUse')){
            $use = $element->getUse();
        }

        $name = $element->getName();
        $tmpId = 0;
        $tmpNode = '';
        if($level == 1 && $name){
            $tmpNode = $parentNode.'-'.$name;
        }

    //    if($fName=='Parentage'){
            if($element->getRestriction() && count($element->getRestriction()->getChecks())){
                $isSelect = 0;
                foreach($element->getRestriction()->getChecks() as $label => $checks){
                    if($label == 'enumeration'){
                        $isSelect = 1;
                    foreach($checks as  $check){
                            $xsdValueData = array(
                                'attribute_id' => $parentId,
                                'value'     => $check['value'],
                            );
                            $this->_attrValXsdModel->saveCategoryAttributeValue($xsdValueData);
                        }
                    }else{

                    }
                 }
                if($isSelect){
                    $this->_categoryAttrXsdModel->where(['id' => $parentId])->update(array('is_select' => 1));
                }
            }

        if(method_exists($element,'getType')){

        }

    }


    public function plainProudctXsd($file,$name){
        $reader = new \GoetasWebservices\XML\XSDReader\SchemaReader;
       // new \GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
        $schema = $reader->readFile($file);
        $productElements = $schema->findElement($name);
     //   foreach($schema->getElements() as $element){
            $this->mainPlain($productElements);
     //   }
    }


    public function plainXsd($file,$name){
         $this->plainProudctXsd($file,$name);
    }







    public function saveXsd($file = ""){
        $importService = new ImportXsd();
        $path = $file;
        $nodeList = $importService->getXsdNodes($path);
        if($nodeList) {
            $fid = 0;
            if (isset($nodeList['element']['@attributes'])) {
                $nodeList['element'][0] = $nodeList['element'];
            }

            $time = time();
            $categoryXsdModel = new AmazonCategoryXsd();
            $categoryAttrXsdModel = new AmazonCategoryAttributeXsd();
            $categoryAttrXsdValModel = new AmazonCategoryAttributeValueXsd();

            $parentId = 0;
            foreach ($nodeList['element'] as $index => $list) {
//                if($index != 6 && $index != 0) continue;

                if (isset($list['@attributes'])) {
                    $categoryName = $list['@attributes']['name'];
                    if ($index == 0) {
                        $categoryData = array('f_id' => 0, 'category_name' => $categoryName);
                        $rowId = $categoryXsdModel->saveCategory($categoryData);
                        $parentId = $rowId;
                    } else{
                        $categoryData  =  array('f_id' => $parentId, 'category_name' => $categoryName);
                        $rowId  =  $categoryXsdModel->saveCategory($categoryData);
                    }
                }

                if (isset($list['complexType']['sequence']['element'])) {
                    $xsdAttributes = $list['complexType']['sequence']['element'];

                    if ($xsdAttributes) {
                        foreach ($xsdAttributes as $xsdAttr) {
                            if (isset($xsdAttr['@attributes'])) {
                                $xsdAttrInfo = $xsdAttr['@attributes'];
                                if(!empty($xsdAttrInfo['name'])){

                                    $xsdAttrData = array(
                                        'category_id' => $rowId, 'name' => $xsdAttrInfo['name'],
                                        'type' => isset($xsdAttrInfo['type']) ? $xsdAttrInfo['type'] : '',
                                        'min_occurs' => isset($xsdAttrInfo['minOccurs']) ? $xsdAttrInfo['minOccurs'] : 0,
                                        'max_occurs' => isset($xsdAttrInfo['maxOccurs']) ? $xsdAttrInfo['maxOccurs'] : 0,
                                        'f_id'       => 0,
                                    );

                                    if (isset($xsdAttr['complexType']['sequence']['element'])) {
                                        $xsdAttrData['is_has_children'] = 1;
                                    }

                                    if (isset($xsdAttr['simpleType']['restriction']['enumeration'])) {
                                        $xsdAttrData['is_select'] = 1;
                                    }
                                    $attrId = $categoryAttrXsdModel->saveCategoryAttribute($xsdAttrData);
                                }
                            }

                            if($index ==0 && isset($xsdAttr['complexType']['choice']['element'])){
                                $valueList = $xsdAttr['complexType']['choice']['element'];
                                if(is_array($valueList) && count($valueList) == 1){
                                    continue;
                                }

                                foreach($valueList as $child){
                                    if(!isset($child['@attributes']['ref'])){
                                        if(isset($child['@attributes']['type'])){
                                            $value = $child['@attributes']['type'];
                                        }else if(isset($child['@attributes']['name'])){
                                            $value = $child['@attributes']['name'];
                                        }
                                    }else{
                                        $value = $child['@attributes']['ref'];
                                    }
                                    $this->saveXsdValueList($value,$attrId);
                                }
                            }

                            if(isset($xsdAttr['simpleType']['restriction']['enumeration'])){
                                $valueList = $xsdAttr['simpleType']['restriction']['enumeration'];
                                if($valueList){
                                    foreach($valueList as $child){
                                        $value = $child['@attributes']['value'];
                                        $this->saveXsdValueList($value,$attrId);
                                    }
                                }
                            }

                            if(isset($xsdAttr['complexType']['sequence']['element'])){
                                $tmpAttrList = $xsdAttr['complexType']['sequence']['element'];

                                foreach($tmpAttrList as $child){
                                    if(isset($child['@attributes'])){
                                        $childInfo = $child['@attributes'];
                                        $xsdChildAttrData = array(
                                            'category_id' => $rowId, 'name' => isset($childInfo['name']) ? $childInfo['name'] : $childInfo['ref'],
                                            'type' => isset($childInfo['type']) ? $childInfo['type'] : '',
                                            'min_occurs' => isset($childInfo['minOccurs']) ? $childInfo['minOccurs'] : 0,
                                            'max_occurs' => isset($childInfo['maxOccurs']) ? $childInfo['maxOccurs'] : 0,
                                            'f_id'       => $attrId,
                                        );
                                        if (isset($child['complexType']['sequence']['element'])) {
                                            $xsdChildAttrData['is_has_children'] = 1;
                                        }
                                        if (isset($child['simpleType']['restriction']['enumeration'])) {
                                            $xsdChildAttrData['is_select'] = 1;
                                        }
                                        $childAttrId = $categoryAttrXsdModel->saveCategoryAttribute($xsdChildAttrData);
                                    }

                                    if(isset($child['simpleType']['restriction']['enumeration'])){
                                        $childTmpValueList = $child['simpleType']['restriction']['enumeration'];
                                        if($childTmpValueList){
                                            if(count($childTmpValueList) == 1){
                                                continue;
                                            }

                                            foreach($childTmpValueList as $childValueInfo){
                                                $childValue = $childValueInfo['@attributes']['value'];
                                                $this->saveXsdValueList($childValue,$childAttrId);
                                            }

                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function saveXsdValueList($value,$attrId){
        $categoryAttrXsdValModel = new AmazonCategoryAttributeValueXsd();
        if(mb_substr_count($value,',')){
            $tmpValueList = explode(",",$value);
            foreach($tmpValueList as $tmpValue){
                $xsdValueData = array(
                    'attribute_id' => $attrId,
                    'value'     => $tmpValue
                );
                $categoryAttrXsdValModel->saveCategoryAttributeValue($xsdValueData);
            }
        }else{
            $xsdValueData = array(
                'attribute_id' => $attrId,
                'value'     => $value
            );
            $categoryAttrXsdValModel->saveCategoryAttributeValue($xsdValueData);
        }
    }


}
