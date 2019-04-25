<?php
namespace app\publish\service;

use app\common\model\amazon\AmazonProductXsd;
use think\Db;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use Waimao\AmazonMws\AmazonConfig;
use app\publish\service\AmazonCategoryXsdConfig;
use app\publish\service\AmazonTypeCheck;

/**
 * Amazon 分类XSD解析
 */

class AmazonCategoryXsdService
{
    private $oriTypes = ['boolean', 'String', 'dateTime', 'date', 'decimal', 'nonNegativeInteger'];
    
    private function saveSimpleType($type, $name, $pid)
    {
        $restriction = '';
        $checkId = 0;
        $typeClass = $this->handleTypeClass($type);
        $typename = $type->getName() ?? $name;
        $attributes = [];
        if (method_exists($type, 'getAttributes')) {
            $attributes = $type->getAttributes();
        }
        if (method_exists($type, 'getRestriction')) {
            $checkId = $this->getCheckId($type->getRestriction(), $restriction);
        }
        $extension = null;
        $extensionName = '';
        if (method_exists($type, 'getExtension')) {
            $extension = $type->getExtension();
        }
        if ($extension) {
            $extensionName = $extension->getBase()->getName();
        }
        $row = [
            'parent_type_id' => $pid,
            'name' => $typename,
            'type' => $typeClass,
            'doc' => $type->getDoc(),
            'restriction' => $restriction,
            'attribute' => count($attributes) ? 1 : 0,
            'check_id' => $checkId,
            'type_class_id' => AmazonConfig::getTypeClassId($typeClass)
        ];
        $typeInfo = Db::name('amazon_type')->where(['name' => $typename, 'parent_type_id' => 0, 'defined' => 0])->field('id')->find();
        if ($typeInfo) {
            $typeId = $typeInfo['id'];
            Db::name('amazon_type')->where('id', $typeId)->update($row);
        } else {
            $typeId = Db::name('amazon_type')->insert($row, false, true);
        }
        $attributes && $this->handleAttribute($attributes, $typeId);
        $extensionName && $this->extension($extensionName, $typeId);
        
        return $typeId;
    }
    
    private function saveComplexType($type, $name, $pid)
    {
        $typename = $type->getName() ?? $name;
        $typeClass = $this->handleTypeClass($type);
        $sequence = '';
        if (method_exists($type, 'getOrder')) {
            $sequence = $type->getOrder();
        }
        $complex = [
            'parent_type_id' => $pid,
            'name' => $typename,
            'type' => $typeClass,
            'doc' => $type->getDoc(),
            'restriction' => '',
            'check_id' => 0,
            'type_class_id' => AmazonConfig::getTypeClassId($typeClass),
            'sequence' => AmazonConfig::getSequence($sequence)
        ];
        $typeInfo = Db::name('amazon_type')->where(['name' => $typename, 'parent_type_id' => $pid, 'defined' => 0])->field('id')->find();
        if ($typeInfo) {
            $typeId = $typeInfo['id'];
            Db::name('amazon_type')->where('id', $typeId)->update($complex);
        } else {
            $typeId = Db::name('amazon_type')->insert($complex, false, true);
        }
        $this->handleComplexType($type, $typeId);
        return $typeId;
    }
    
    private function handleTypeClass($type)
    {
        if ($type instanceof \GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType) {
            $typeName = 'ComplexType';
        } elseif ($type instanceof \GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType) {
            $typeName = 'SimpleType';
        } else if ($type instanceof \GoetasWebservices\XML\XSDReader\Schema\Type\ComplexTypeSimpleContent) {
            $typeName = 'ComplexTypeSimpleContent';
        }else if ($type instanceof \GoetasWebservices\XML\XSDReader\Schema\Type\BaseComplexType) {
            $typeName = 'BaseComplexType';
        } else {
            $typeName = 'Type';
        }

        return $typeName;
    }

    public function saveXsd($file = "", $name)
    {
        ini_set('memory_limit', '2048M');
        $reader = new SchemaReader();
        $schema = $reader->readFile($file);
        $this->saveDefinedType($schema->getTypes());
        $this->saveDefinedElement($schema->getElements(), true);
        $this->saveDefinedType($schema->getTypes(), false);
        $this->saveDefinedElement($schema->getElements(), false);
        return true;
    }
    
    private function saveDefinedType($types, $defined = true)
    {
        foreach ($types as $type) {
            $typeName = $this->handleTypeClass($type);
            $attributes = [];
            if (method_exists($type, 'getAttributes')) {
                $attributes = $type->getAttributes();
            }
            $extension = null;
            $extensionName = '';
            if (method_exists($type, 'getExtension')) {
                $extension = $type->getExtension();
            }
            if ($extension) {
                $extensionName =  $extension->getBase()->getName();
            }
            $sequence = '';
            if (method_exists($type, 'getOrder')) {
                $sequence = $type->getOrder();
            }
            $typeClassId = AmazonConfig::getTypeClassId($typeName);
            $checkId = 0;
            $restriction = '';
            $checkId = $this->getCheckId($type->getRestriction(), $restriction);            
            $row = [
                'name' => $type->getName(),
                'type' => $typeName,
                'restriction' => $restriction,
                'extension' => $extensionName,
                'attribute' => count($attributes) ? 1 : 0,
                'defined' => 1,
                'doc' => $type->getDoc(),
                'sequence' => AmazonConfig::getSequence($sequence),
                'type_class_id' => $typeClassId,
                'check_id' => $checkId
            ];
            $oldRow = Db::name('amazon_type')->where(['name' => $type->getName(), 'defined' => 1])->find();
            if ($oldRow) {               
                $id = $oldRow['id'];
                Db::name('amazon_type')->where(['id' => $id])->update($row);
            } else {
                $id = Db::name('amazon_type')->insert($row, false, true);
            }
            if (!$defined) {
                $attributes && $this->handleAttribute($attributes, $id);
                $extension && $this->extension($extensionName, $id);
                $typeName == 'ComplexType' && $this->handleComplexType($type, $id);
            }
        }
        
        return true;
    }
    
    private function saveDefinedElement($elements, $defined = true)
    {
        foreach ($elements as $element) {
            $type = $element->getType();
            $typeClass = $this->handleTypeClass($type);
            $typename = $type->getName() ?? '';
            $typeId = 0;
            $elementName = $element->getName();
            if ($typename && !in_array($typename, $this->oriTypes)) {
                $refType = Db::name('amazon_type')->where(['name' => $typename, 'defined' => 1])->field('id')->find();
                $refType && $typeId = $refType['id'];
            }
            if (empty($typename) && !$defined) {
                $typeClass == 'ComplexType' && $typeId = $this->saveComplexType($type, $elementName, 0);
                in_array($typeClass, ['SimpleType', 'ComplexTypeSimpleContext']) && $typeId = $this->saveSimpleType($type, $elementName, 0);
            }
            $row = [
                'name' => $elementName,
                'type' => $typeClass,
                'type_name' => $typename,
                'defined' => 1,
                'doc' => $element->getDoc() ?? '',
                'create_time' => time(),
                'type_id' => $typeId,
                'type_class_id' => AmazonConfig::getTypeClassId($typeClass)
            ];
            $oldRow = Db::name('amazon_element')->where(['name' => $element->getName(), 'defined' => 1])->find();
            if ($oldRow) {
                $id = $oldRow['id'];
            } else {
                $id = Db::name('amazon_element')->insert($row, false, true);
            }
            if ($typeId && !$defined) {
                Db::name('amazon_element')->where(['id' => $id])->update(['type_id' => $typeId]);
            }
        }
        
        return true;
    }
    
    private function extension($extension, $id)
    {
        $extType = Db::name('amazon_type')->where(['defined' => 1, 'name' => $extension])->field('id')->find();
        $extType && Db::name('amazon_type')->where(['id' => $id])->update(['extension_id' => $extType['id']]);
    }

    private function handleAttribute($attributes, $id)
    {
        foreach ($attributes as $attribute) {
            $name = $attribute->getName();
            $type = $attribute->getType();
            $typename = $type->getName() ?? '';
            $use = $attribute->getUse();
            $typeId = 0;
            $restriction = '';
            $checkId = 0;
            if ($typename && !in_array($typename, $this->oriTypes)) {
                $refType = Db::name('amazon_type')->where(['name' => $typename, 'defined' => 1])->field('id')->find();
                $typeId = $refType['id'];
            } else {
                $checkId = $this->getCheckId($type->getRestriction(), $restriction);
            }
            $row = [
                'name' => $name,
                'type_id' => $typeId,
                'parent_type_id' => $id,
                'use' => $use == 'required' ? 1 : 0,
                'restriction' => $restriction,
                'create_time' => time(),
                'type_name' => $typename,
                'check_id' => $checkId
            ];
            $attrInfo = Db::name('amazon_type_attribute')->where(['parent_type_id' => $id, 'name' => $name])->find('id');
            if (!$attrInfo) {
                Db::name('amazon_type_attribute')->insert($row);
            } else {
                Db::name('amazon_type_attribute')->where(['id', $id])->update($row);
            }
        }
    }
    
    private function handleComplexType($ctype, $pId)
    {
        $i = 0;
        foreach ($ctype->getElements() as $element) {
            $elementId = 0;
            if ($element instanceof \GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef) {
                $elementInfo = Db::name('amazon_element')->where('name', $element->getName())->find();
                $elementId = $elementInfo['id'];
            }
            $type = $element->getType();
            $typeClass = $this->handleTypeClass($type);
            $typename = $type->getName();
            $restriction = '';
            $checkId = 0;
            $typeId = 0;
            // 引用取用
            if ($typename && !in_array($typename, $this->oriTypes)) {
                $refType = Db::name('amazon_type')->where(['name' => $typename, 'defined' => 1])->field('id')->find();
                $refType && $typeId = $refType['id'];
            }
            if ($typeClass == 'SimpleType' && empty($typename)) {
                $checkId = $this->getCheckId($type->getRestriction(), $restriction);
            }
            if ($typeClass == 'ComplexType' && empty($typename)) {
                $typeId = $this->saveComplexType($type, $element->getName(), $pId);
            }
            if ($typeClass == 'ComplexTypeSimpleContent' && empty($typename)) {
                $typeId = $this->saveSimpleType($type, $element->getName(), $pId);
            }
            $typeClassId = AmazonConfig::getTypeClassId($typeClass);
            $row = [
                'parent_element_id' => 0,
                'element_id' => $elementId,
                'name' => $element->getName(),
                'type' => $typeClass,
                'parent_type_id' => $pId,
                'min_occurs' => $element->getMin(),
                'max_occurs' => $element->getMax(),
                'create_time' => time(),
                'doc' => $element->getDoc(),
                'restriction' => $restriction,
                'type_id' => $typeId,
                'type_class_id' => $typeClassId,
                'check_id' => $checkId,
                'sequence' => ++$i
            ];
            $relationInfo = Db::name('amazon_element_relation')->where(['parent_type_id' => $pId, 'name' => $row['name']])->find('id');
            !$relationInfo ? Db::name('amazon_element_relation')->insert($row) : Db::name('amazon_element_relation')->where(['id' => $relationInfo['id']])->update($row);
        }
    }
    
    private function getCheckId($restriction, &$value)
    {
        if (!$restriction) {
            return 0;
        }
        $arr = $restriction->getChecks();
        if (isset($arr['enumeration'])) {
            $rows = [];
            foreach($arr['enumeration'] as $val) {
                $rows[] = $val['value'];
            }
            $value = json_encode($rows);
            return 1;
        } elseif (isset($arr['pattern'])) {
            $value = $arr['pattern'][0]['value'];
            return 3;
        } elseif (isset($arr['maxLength']) || isset($arr['minLength'])) {
            $row = [];
            isset($arr['maxLength']) && $row['maxLength'] = $arr['maxLength'][0]['value'];
            isset($arr['minLength']) && $row['minLength'] = $arr['minLength'][0]['value'];
            $value = json_encode($row);
            return 2;
        } elseif (isset($arr['totalDigits']) || isset($arr['fractionDigits']) || isset($arr['maxInclusive']) || isset($arr['minInclusive']) || isset($arr['maxExclusive']) || isset($arr['minInclusive'])) {
            $row = [];
            isset($arr['totalDigits']) && $row['totalDigits'] = $arr['totalDigits'][0]['value'];
            isset($arr['fractionDigits']) && $row['fractionDigits'] = $arr['fractionDigits'][0]['value'];
            isset($arr['maxInclusive']) && $row['maxInclusive'] = $arr['maxInclusive'][0]['value'];
            isset($arr['minInclusive']) && $row['minInclusive'] = $arr['minInclusive'][0]['value'];
            isset($arr['maxExclusive']) && $row['maxExclusive'] = $arr['maxExclusive'][0]['value'];
            isset($arr['minInclusive']) && $row['minInclusive'] = $arr['minInclusive'][0]['value'];
            $value = json_encode($row);
            return 4;
        } elseif (isset($arr['whiteSpace'])) {
            $value = json_encode($arr['whiteSpace'][0]['value']);
            return 5;
        } else {
            $value = json_encode($restriction->getChecks());
        }
        
        return 0;
    }

    /**
     * 更新Amazon各站点以下的分类；
     */
    public function updateSiteElement()
    {
        set_time_limit(0);
        $lists= AmazonCategoryXsdConfig::$sites;
        $sites = array_keys($lists);
        foreach($sites as $code) {
            $categories = AmazonCategoryXsdConfig::getCategoriesBySite($code);
            $this->updateElement($code, $categories);
            echo $code, "\r\n";
        }
    }
    
    private function updateElement($code, $categories)
    {
        foreach($categories as $category) {
            $element = Db::name('amazon_element')->where(['name' => $category])->find();
            if (empty($element)) {
                continue;
            }
            $element['site'] |= AmazonCategoryXsdConfig::getBits($code);
            Db::name('amazon_element')->where(['id' => $element['id']])->update(['site' => $element['site']]);
        }
    }


    //---------------------------------------- 以下对XSD的二次解析 ----------------------------------------

    private $model = null;

    private $readTime = 0;


    /**
     * 解析XSD
     * @param $file
     * @param $elementName
     */
    public function readProductXsd()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        //$url = 'https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_1_9/amzn-envelope.xsd';
        $url = 'https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_1_9/Product.xsd';

        $reader = new SchemaReader();
        $schema = $reader->readFile($url);

        //初始化一些需要统一设置的参数；
        $this->startRead();

        //读取产品元素，从产品元素开始解析
        $product = $schema->getElement('Product');

        //开始处理当前的元素和后续元素；
        $this->handleElement($product);

        //读取xsd并处理结束后的操作
        $this->endRead();
    }


    public function handleElement($element, $data_type = 0, $no = 1, $pid = 0, $path = '', $path_id = '', $before = '')
    {
        $this->model = new AmazonProductXsd();
        //元素名称；
        $name = $element->getName();

        //方法是getType，但是返回的结果是这个节点对应的schema,schema里面的方法都要以用；
        $type = $element->getType();
        $type_name = (string)$type->getName();

        $path = trim($path. ','. $name, ',');


        //是否在终端写出记录
        $before .= '------';
        $this->writeInterminal($before, $name, false);

        //元素出现最小个数，如果大于0，则此元素是必填
        $min = 0;
        if (method_exists($element, 'getMin')) {
            $min = $element->getMin();
        }

        //元素出现最大个数
        $max = 0;
        if (method_exists($element, 'getMax')) {
            $max = $element->getMax();
        }

        $order_type = 0;
        $orderTypeArr = ['choice' => 1, 'sequence' => 2];
        if (method_exists($type, 'getOrder')) {
            $order = $type->getOrder();
            $order_type = $orderTypeArr[$order] ?? 0;
        }


        $attributes = [];
        if (method_exists($type, 'getAttributes')) {
            foreach ($type->getAttributes() as $attr) {
                if (!is_object($attr)) {
                    continue;
                }
                $tmp = [
                    'name' => (string)$attr->getName(),
                    'fixed' => (string)$attr->getFixed(),
                    'default' => (string)$attr->getDefault(),
                    'attr_type_name' => '',
                    'restriction' => '',
                ];

                $attrType = $attr->getType();
                if (!empty($attrType)) {
                    //约束条件；
                    $attr_restriction = $this->getRestriction($attrType);
                    //继承来的条件；
                    $attr_extension = $this->getExtension($attrType);

                    $tmp['attr_type_name'] = (string)$attrType->getName();
                    $tmp['restriction'] = $this->combineRestriction($attr_restriction, $attr_extension);;
                }
                $attributes[] = $tmp;
            }
        }

        //约束条件；
        $restriction = $this->getRestriction($type);
        //继承来的条件；
        $extension = $this->getExtension($type);

        //重新组合约束条件；
        $restriction = $this->combineRestriction($restriction, $extension);

        //加州65号提案化学名称类型,这个枚举类型太长了，基本不会用到，所以直接忽略，等用到的时候再处理；
        if ($type_name == 'CaliforniaProposition65ChemicalNamesType') {
            $restriction['enumeration'] = [];
        }

        //包不包含子节点；
        $hasChild = 1;
        if (!method_exists($type, 'getElements')) {
            $hasChild = 0;
        }

        $data = [
            'pid' => $pid,
            'name' => $name,
            'data_type' => $data_type,
            'order_type' => $order_type,
            'has_child' => $hasChild,
            'type_name' => $type_name,
            'attribute' => json_encode($attributes),
            'no' => $no,
            'doc' => substr((string)$element->getDoc(), 0, 200),
            'min' => $min,
            'max' => $max,
            'restriction' => json_encode($restriction),
            'extension' => json_encode($extension),
            'path' => $path,
            'update_time' => $this->readTime,
            'delete_time' => 0
        ];

        $old_id = $this->model->where(['path' => $path])->value('id');

        $id = 0;
        if (empty($old_id)) {
            $data['create_time'] = $this->readTime;
            $id = $this->model->insertGetId($data);
            $path_id = trim($path_id. ','. $id, ',');
            $this->model->update(['path_id' => $path_id], ['id' => $id]);
        } else {
            $id = $old_id;
            $path_id = trim($path_id. ','. $id, ',');
            $data['path_id'] = $path_id;
            $this->model->update($data, ['id' => $id]);
        }

        if (!method_exists($type, 'getElements')) {
            return;
        }

        //用来记录排序；
        $num = 1;
        foreach ($type->getElements() as $val) {
            //data_type用来标记类型，product里面的全是0，productData里面的元素，按第几个来排；
            if ($name == 'ProductData') {
                $data_type = $num;
                $path = '';
                $path_id = '';
            }
            $this->handleElement($val, $data_type, $num, $id, $path, $path_id, $before);
            $num++;
        }
    }


    public function writeInterminal(string $before, string $name = '', bool $write)
    {
        if (!$write) {
            return;
        }
        echo $before. $name. "\r\n";
    }


    public function startRead()
    {
        $this->readTime = time();
        $this->model = new AmazonProductXsd();
    }


    public function endRead()
    {
        //把未被更新的文件标成删除；
        $this->model->update(['delete_time' => time()], ['update_time' => ['<', $this->readTime]]);
    }

    /**
     * 获取约束条件
     * @param $type
     * @return array
     */
    public function getRestriction($type)
    {
        $restriction = [];
        if (is_null($type)) {
            return [];
        }
        if (is_object($type) && method_exists($type, 'getRestriction')) {
            $obj = $type->getRestriction();
            if (!is_null($obj)) {
                $arr = $obj->getChecks();
                foreach ($arr as $key=>$val) {
                    if (isset($val['value'])) {
                        $restriction[$key] = $val['value'] ?? '';
                    } else {
                        $tmp = [];
                        foreach ($val as $v) {
                            $tmp[] = $v['value'] ?? '';
                        }
                        if (count($tmp) == 1) {
                            $restriction[$key] = $tmp[0];
                        } else {
                            $restriction[$key] = $tmp;
                        }

                    }
                }
            }
        }
        return $restriction;
    }


    /**
     * 获取继承的约束条件
     * @param $type
     * @return array
     */
    public function getExtension($type)
    {
        $restriction = [];
        if (empty($type)) {
            return [];
        }
        if (is_object($type) && method_exists($type, 'getExtension')) {
            $obj = $type->getExtension();
            if (!is_null($obj)) {
                $type = $obj->getBase();
                $tmp = $this->getRestriction($type);
                $tmp2 = $this->getExtension($type);
                $restriction = array_merge($tmp2, $tmp, $restriction);
            }
        }
        return $restriction;
    }


    /**
     * 组合约束条件，把继承来的约束和本元素类型约束放在一起；
     * @param $restriction
     * @param $extension
     */
    public function combineRestriction($restriction, $extension)
    {
        //没有继承条件，直接返回；
        if (empty($extension)) {
            return $restriction;
        }
        //因为extension是继承来的，应该被新的覆盖；
        foreach ($restriction as $key=>$val) {
            $extension[$key] = $val;
        }
        return $extension;
    }
}
