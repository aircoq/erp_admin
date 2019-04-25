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
use app\publish\service\AmazonCategoryHelper;

use GoetasWebservices\XML\XSDReader\SchemaReader;


class AmazonAttriubteService
{
    private $nodeList = array();
    public function saveExcelAttributeImport(array $requestData){
        $content = $requestData["content"];
        if(!isset($requestData["content"])){
            throw new JsonErrorException('请导入EXCEL模板数据！');
        }

        if(empty($requestData["content"])){
            throw new JsonErrorException('导入数据为空！');
        }

        $importService = new ImportExport();

        $path = $importService->uploadFile($content,'excel_vail_value');

        if(!$path){
            throw new JsonErrorException('文件上传失败！');
        }

        $data = $importService::excelImportByIndex($path,0);
        if (!(is_array($data) && count($data) > 1)){
            throw new JsonErrorException('导入数据为空！');
        }

        $attributeParis = $this->getExcelAttributeValuePairs($data);
        $time = time();
        $attributeMapModel = new AmazonCategoryAttributeMap();
        $attributeXsdModel = new AmazonCategoryAttributeXsd();
        $categoryXsdModel = new AmazonCategoryXsd();
        $categoryIdList = $categoryXsdModel->field('id')->where(["f_id" => $requestData['cat_id']])->select();

        foreach($attributeParis as $name => $valueList){
            $this->handleOneRecord($attributeMapModel,$attributeXsdModel,$name,$valueList,$requestData);
            if(is_array($categoryIdList) && count($categoryIdList)){
                foreach($categoryIdList as $val){
                    $requestData['cat_id'] = $val['id'];
                    $this->handleOneRecord($attributeMapModel,$attributeXsdModel,$name,$valueList,$requestData);
                }
            }
        }

        $attributeMapModel->deleteByCategoryId($requestData['cat_id'],$requestData['site'],$time);

        //去除缓存
        $cache = new AmazonXsdCache();
        $cache->deleteAttributeCacheByCatId($requestData['site'],$requestData['category_id']);

    }


    public function handleOneRecord(AmazonCategoryAttributeMap $mapModel,$attrModel,$name,$valueList,array $requestData){
        $attrList = $attrModel->getListByAttributeName($requestData['cat_id'],$name,'id');
        $this->matchOneAttribute($mapModel,$attrModel,$name,$valueList,$requestData,$attrList);
    }


    public function matchOneAttribute(AmazonCategoryAttributeMap $mapModel,$attrModel,$name,$valueList,array $requestData,$attrList){
        if(is_array($attrList) && count($attrList)){
            $time = time();
            foreach($attrList as $info){
                $map = ['cat_id' => $requestData['cat_id'],'site' => $requestData['site'],'xsd_attr_id' => $info['id'],'excel_attr_name' => $name,'last_update_time' => time(),'creator' => 1];

                $rowId = $mapModel->saveAttributeMap($map);
                if(!$rowId) {
                    throw new JsonErrorException("保存属性:" . $name . "失败", 400);
                }
                //更新EXCEL匹配上了
                $attrModel->where(array('id' => $info['id']))->update(array('is_match_excel' => 1));
                $valueModel = new AmazonCategoryExcelAttributeValue();

                foreach($valueList as $val){
                    $valueData = ['excel_attr_id' => $rowId,'xsd_attr_id' => $map['xsd_attr_id'],'name' => $val,'last_update_time' => time(),'site' => $requestData['site'] ];
                    $valueModel->saveExcelAttributeValue($valueData);
                }

            }

            $valueModel->deleteByUpdateTime($rowId,$time);

        }
    }

    public function getExcelAttributeValuePairs($data){
        $attributeParis = array();
        $fields = array();
        foreach($data as $key => $val){
            if($key == 0){
                $fields = $val;
                continue;
            }
            foreach($val as $code => $attrValue){
                $attrName = $fields[$code];
                $attrName = str_replace(' ','',ucwords(str_replace("_",' ',$attrName)));
                if($attrValue){
                    $attributeParis[$attrName][] = $attrValue;
                }
            }
        }
        return $attributeParis;
    }


    private function ss($nodeList,$node){

        $data = array();
        $len = count($nodeList);
        $methond = "level".$len;
        print_r($this->$methond(
            $nodeList,$data
        ));
    }

    private function level2($nodelList,$data){
        if(!isset($data[$nodelList[0]])){
            $data[$nodelList[0]] = array();
        }
        if(!isset($data[$nodelList[0]][$nodelList[1]])){
            $data[$nodelList[0]][$nodelList[1]] = array();
        }
        return $data;
    }

    private function level3($nodelList,$data){
        if(!isset($data[$nodelList[0]])){
            $data[$nodelList[0]] = array();
        }

        if(!isset($data[$nodelList[0]][$nodelList[1]])){
            $data[$nodelList[0]][$nodelList[1]] = array();
        }

        if(!isset($data[$nodelList[0]][$nodelList[1]][$nodelList[2]] )){
            $data[$nodelList[0]][$nodelList[1]][$nodelList[2]] = array();
        }

        return $data;
    }

    private function level4($nodelList,$data){
        if(!isset($data[$nodelList[0]])){
            $data[$nodelList[0]] = array();
        }

        if(!isset($data[$nodelList[0]][$nodelList[1]])){
            $data[$nodelList[0]][$nodelList[1]] = array();
        }

        if(!isset($data[$nodelList[0]][$nodelList[1]][$nodelList[2]] )){
            $data[$nodelList[0]][$nodelList[1]][$nodelList[2]] = array();
        }

        if(!isset($data[$nodelList[0]][$nodelList[1]][$nodelList[2]][$nodelList[3]] )){
            $data[$nodelList[0]][$nodelList[1]][$nodelList[2]][$nodelList[3]] = array();
        }

        return $data;
    }

    private function level5($nodelList,$data){
        if(!isset($data[$nodelList[0]])){
            $data[$nodelList[0]] = array();
        }

        if(!isset($data[$nodelList[0]][$nodelList[1]])){
            $data[$nodelList[0]][$nodelList[1]] = array();
        }

        if(!isset($data[$nodelList[0]][$nodelList[1]][$nodelList[2]] )){
            $data[$nodelList[0]][$nodelList[1]][$nodelList[2]] = array();
        }

        if(!isset($data[$nodelList[0]][$nodelList[1]][$nodelList[2]][$nodelList[3]] )){
            $data[$nodelList[0]][$nodelList[1]][$nodelList[2]][$nodelList[3]] = array();
        }

        if(!isset($data[$nodelList[0]][$nodelList[1]][$nodelList[2]][$nodelList[3]][$nodelList[4]] )){
            $data[$nodelList[0]][$nodelList[1]][$nodelList[2]][$nodelList[3]][$nodelList[4]] = array();
        }

        return $data;
    }

    private function level6($nodelList,$data){
        if(!isset($data[$nodelList[0]])){
            $data[$nodelList[0]] = array();
        }

        if(!isset($data[$nodelList[0]][$nodelList[1]])){
            $data[$nodelList[0]][$nodelList[1]] = array();
        }

        if(!isset($data[$nodelList[0]][$nodelList[1]][$nodelList[2]] )){
            $data[$nodelList[0]][$nodelList[1]][$nodelList[2]] = array();
        }

        if(!isset($data[$nodelList[0]][$nodelList[1]][$nodelList[2]][$nodelList[3]] )){
            $data[$nodelList[0]][$nodelList[1]][$nodelList[2]][$nodelList[3]] = array();
        }

        if(!isset($data[$nodelList[0]][$nodelList[1]][$nodelList[2]][$nodelList[3]][$nodelList[4]] )){
            $data[$nodelList[0]][$nodelList[1]][$nodelList[2]][$nodelList[3]][$nodelList[4]] = array();
        }

        if(!isset($data[$nodelList[0]][$nodelList[1]][$nodelList[2]][$nodelList[3]][$nodelList[4]][$nodelList[5]] )){
            $data[$nodelList[0]][$nodelList[1]][$nodelList[2]][$nodelList[3]][$nodelList[4]][$nodelList[5]] = array();
        }
        return $data;
    }


    public function arrayToXml($arr){
        $xml = "";
        foreach ($arr as $key=>$val){
            if(is_array($val)){
                $xml.="<".$key.">".$this->arrayToXml($val)."</".$key.">";
            }else{
                $xml.="<".$key.">".$val."</".$key.">";
            }
        }
        return $xml;
    }



    public function saveXsd($file = "",$name){
        ini_set('memory_limit', '1024M');
        $obj = new AmazonXsdService();
        $obj->plainXsd($file,$name);

        exit;



        $elements = $auto->getType()->getElements();
        foreach($elements as $k => $element) {
            $childs = $element->getType();
         //   if ( $childs instanceof \GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType){
                foreach($childs->getElements() as $child){
                    echo $child->getName();
                    echo "<br>";
                }
        //    }


//            foreach($childs->getElements() as $element) {
//                print_r($element->getName(). '<br>');
//            }
            exit;
        }
        exit;




        $reader = new SchemaReader();
        $schema = $reader->readFile($file);
        foreach ($schema->getSchemas() as $key => $innerSchema){

            if(is_object($innerSchema) && method_exists($innerSchema,'getElements')){
                foreach($innerSchema->getElements() as $element){
        //                    echo $element->getName();
                //                    echo "<br>";
                }
            }
        }


        foreach ($schema->getElements() as $element){
           // echo $element->getName();
            // echo "<br>";
            $auto = $schema->findElement('AutoAccessory');
            var_dump($auto);exit;
            foreach($auto->getType()->getElements() as $child){
                echo $child->getName();
                echo "<br>";
            }

        }

        foreach ($schema->getAttributes() as $attr){
            print_r($attr);
        }

        exit;


//        echo levenshtein('color','collar');
//        exit;
//
//        $arr = array();
//        $model = new AmazonCategoryAttributeXsd();
//        $list = $model->where(['category_id' => 1])->select();
//        $data = array();
//        foreach($list as $val){
//            $node =  $val['node_tree'];
//            if(mb_substr_count($node,'-')){
//                $nodeList = explode('-',$node);
//                $len = count($nodeList);
//                $method = 'level'.$len;
//                $data = $this->$method($nodeList,$data);
//            }
//        }


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
            $nodeTreeList = array();
            $attrNodeTreeList = array();
            foreach ($nodeList['element'] as $index => $list) {
//                if($index != 6 && $index != 0) continue;
                //解析得到类目及该类目下所有子类目
                if (isset($list['@attributes'])) {
                    $categoryName = $list['@attributes']['name'];
                    if ($index == 0) {
                        $categoryData = array('f_id' => 0, 'category_name' => $categoryName,'node_tree' => $categoryName);
                        $rowId = $categoryXsdModel->saveCategory($categoryData);
                        $parentId = $rowId;

                    } else {
                        $fCategoryName = $nodeList['element'][0]['@attributes']['name'];
                        $categoryData  =  array('f_id' => $parentId, 'category_name' => $categoryName,'node_tree' => $fCategoryName.'-ProductType-'.$categoryName);
                        $rowId  =  $categoryXsdModel->saveCategory($categoryData);
                    }
                    $nodeTreeList[$rowId] = $categoryData['node_tree'];
                }

                //保存类目对应的属性和属性值
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
                                        'node_tree' => $nodeTreeList[$rowId].'-'.$xsdAttrInfo['name']
                                    );

                                    //是否有子属性
                                    if (isset($xsdAttr['complexType']['sequence']['element'])) {
                                        $xsdAttrData['is_has_children'] = 1;
                                    }

                                    //是否有可选值
                                    if (isset($xsdAttr['simpleType']['restriction']['enumeration'])) {
                                        $xsdAttrData['is_select'] = 1;
                                    }

                                    $attrId = $categoryAttrXsdModel->saveCategoryAttribute($xsdAttrData);
                                    $attrNodeTreeList[$attrId] = $xsdAttrData["node_tree"];
                                }
                            }

                            //保存属性对应的属性值
                            if($index ==0 && isset($xsdAttr['complexType']['choice']['element'])){
                                $valueList = $xsdAttr['complexType']['choice']['element'];
                                if(is_array($valueList) && count($valueList) == 1){
                                    $valueList = array($valueList);
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

                            //保存次级属性及属性值
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
                                            'node_tree' => $attrNodeTreeList[$attrId].'-'.$childInfo['name'],
                                        );

                                        //是否有子属性
                                        if (isset($child['complexType']['sequence']['element'])) {
                                            $xsdChildAttrData['is_has_children'] = 1;
                                        }

                                        //是否有可选值
                                        if (isset($child['simpleType']['restriction']['enumeration'])) {
                                            $xsdChildAttrData['is_select'] = 1;
                                        }
                                        $childAttrId = $categoryAttrXsdModel->saveCategoryAttribute($xsdChildAttrData);
                                    }


                                    if(isset($child['simpleType']['restriction']['enumeration'])){
                                        $childTmpValueList = $child['simpleType']['restriction']['enumeration'];
                                        if($childTmpValueList){
                                            if(count($childTmpValueList) == 1){
                                                $childTmpValueList = array($childTmpValueList);
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


    public function getXsdCategoryConfig($param = array(),$page =1,$pageSize = 50,$fields="*")
    {
        $where=[];
        $post = $param;
        $sql = "select * from amazon_category_xsd ";
        $condition = "WHERE sites is not null";

        if(isset($post['site']) && $post['site'] )
        {
            $site = $post['site'];
            $condition .= " AND  FIND_IN_SET('$site',sites)";
        }

        if(isset($post['second_category_id']) && $post['second_category_id']){
            $condition .= " AND id = {$post['second_category_id']}";
        }elseif(isset($post['first_category_id']) && $post['first_category_id']){
            $condition .= " AND id = {$post['first_category_id']}";
        }

        if(isset($post['create']) && $post['create'] )
        {
            $creator = $post['create'];
            $condition .= " AND creator = $creator";
        }

        $sql .= $condition;

        $rows =  Db::query($sql);
        if($rows){
            $datas = array();
            foreach($rows as $val){
                if(mb_substr_count($val['sites'],',')){
                    if(empty($post['site'])){
                        $sites = explode(',',$val['sites']);
                    }else{
                        $sites = array($post['site']);
                    }
                }else{
                    $sites = isset($post['site']) && $post['site'] ? array($post['site']) : array($val['sites']);
                }

                foreach($sites as $site){
                    $val['sites'] = $site;
                    $val['category_name'] = str_replace('-ProductType','',$val['node_tree']);
                    $datas[] = $val;
                }
            }
            $totals=count($datas);
            $countpage = ceil($totals/$pageSize); #计算总页面数
            $pagedata = array();
            $start = ($page-1) * $pageSize;
            $pagedata=array_slice($datas,$start,$pageSize);
            return ['data'=>$pagedata,'count'=>$totals,'page'=>$page,'pageSize'=>$pageSize];
        }else{
            return ['data'=>array(),'count'=>0,'page'=>$page,'pageSize'=>$pageSize];
        }

    }



}
