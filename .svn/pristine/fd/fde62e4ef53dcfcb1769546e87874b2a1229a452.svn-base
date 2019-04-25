<?php
namespace app\publish\service;

use app\common\model\amazon\AmazonElement as AmazonElementModel;
use app\common\model\amazon\AmazonElementRelation as AmazonElementRelationModel;
use app\common\model\amazon\AmazonProductXsd;
use app\common\model\amazon\AmazonType;
use think\Exception;

class AmazonElement
{
    private $model;
    private $relationElement;
    private $xsdModel;

    public function __construct()
    {
        $this->model = new AmazonElementModel();
        $this->relationElement = new AmazonElementRelationModel();
        $this->xsdModel = new AmazonProductXsd();
    }

    public function getProductBase()
    {
        $where = [
            'delete_time' => 0,
            'data_type' => 0,
            'id' => ['>', 1],
            'has_child' => 0,
        ];
        $field = 'id,pid,name,order_type,type_name,min,max,doc,restriction,path,create_time';
        $products = $this->xsdModel->where($where)->field($field)->select();

        foreach ($products as $val) {
            $val = $val->toArray();
            if ($val['name'] == 'Product') {
                continue;
            }
            $restriction = json_decode($val['restriction'], true);
            $data[] = [
                'id' => $val['id'],
                'pid' => $val['pid'],
                'name' => $val['name'],
                'max_occurs' => $val['max'],
                'min_occurs' => 0,
                'node_tree' => $val['path'],
                'parent_type_id' => $val['pid'],
                'restriction' => $restriction,
                'sequence' => $val['order_type'],
                'type' => $val['type_name'],
                'type_id' => $val['id'],
                'variation' => '0',
                'create_time' => $val['create_time'],
            ];
        }

        return $data;
        //$productElement = $this->model->where(['name' => 'Product'])->find();
        //$elements = $this->getRelationElements($productElement['type_id'], 'Product');
        //return $elements;
    }

    public function getTreeData($lists, $pid = 1)
    {
        $data = [];
        foreach ($lists as $key => $val) {
            if ($val['pid'] == $pid) {
                $data[] = $val;
                unset($lists[$key]);
            }
        }
        foreach ($data as $key => $val) {
            $data[$key]['childs'] = $this->getTreeData($lists, $val['id']);
        }
        return $data;
    }

    /**
     * 根据站点拿到该站点下面的分类
     * @param int $site
     * @return array
     */
    public function getCategoryBase($site)
    {
        //找出product_data的id;
        $product_data_id = $this->xsdModel->where(['name' => 'ProductData'])->value('id');
        //找出大分类的ID；
        $categorys = $this->xsdModel->where(['pid' => $product_data_id, 'delete_time' => 0])
            ->field('id,name,id type_id')
            ->column('id,name,id type_id', 'id');

        //找出type的ID；
        $product_type_ids = $this->xsdModel
            ->where(['name' => 'ProductType', 'delete_time' => 0, 'pid' => ['in', array_keys($categorys)]])
            ->field('id,pid')
            ->column('id', 'pid');

        //找出小分类；
        $subCategorys = $this->xsdModel->where(['has_child' => 1, 'delete_time' => 0, 'pid' => ['in', array_values($product_type_ids)]])
            ->field('id,name,pid,id type_id')
            ->column('id,name,pid,id type_id', 'id');

        //组合上述数据；
        $data = [];
        foreach ($categorys as $val) {
            $val['childs'] = [];
            if (!empty($product_type_ids[$val['id']])) {
                foreach ($subCategorys as $k => $v) {
                    if ($v['pid'] == $product_type_ids[$val['id']]) {
                        unset($v['pid']);
                        $v['childs'] = [];
                        $val['childs'][] = $v;
                        unset($subCategorys[$k]);
                    }
                }
            }
            $data[] = $val;
        }

        return $data;

        //$categoryElement = $this->model->where('site & '. $site. ' = '. $site)->field('id,name,type_id,site,variation')->select();
        //if(empty($categoryElement)) {
        //    return [];
        //}
        //
        //$lists = [];
        //$typeIdArr = [];
        //foreach($categoryElement as $val) {
        //    $lists[] = $val->toArray();
        //    $typeIdArr[] = $val['type_id'];
        //}
        //
        ////productType下面的子元素就是子分类；
        ////找出这些type_id下级name为productType的元素
        //$productTypeList = $this->relationElement->where(['parent_type_id' => ['in', $typeIdArr], 'name' => 'ProductType'])
        //    ->field('type_id,parent_type_id')
        //    ->select();
        //
        ////没有producttype元素则证明没有子分类了
        //if(empty($productTypeList)) {
        //    return $lists;
        //}
        //
        //$productTypeIdArr = [];
        //foreach($lists as &$data) {
        //    $data['productType_id'] = 0;
        //    foreach($productTypeList as $val) {
        //        if ($data['type_id'] == $val['parent_type_id']) {
        //            $data['productType_id'] = $val['type_id'];
        //            $productTypeIdArr[] = $val['type_id'];
        //        }
        //    }
        //}
        //unset($data);
        //
        ////找出productType的的下级
        //$subList = $this->relationElement->where(['parent_type_id' => ['in', $productTypeIdArr]])
        //    ->field('id,type_id,name,parent_type_id,variation')
        //    ->select();
        //
        //foreach($lists as &$data) {
        //    $childs = [];
        //    foreach($subList as $sub) {
        //        if ($data['productType_id'] == $sub['parent_type_id']) {
        //            $childs[] = $sub->toArray();
        //        }
        //    }
        //    $data['childs'] = $childs;
        //}
        //
        //return $lists;
    }

    /**
     * 根据站点分类id分类下面所属的元素；
     * @param int $site
     * @return array
     */
    public function getCategoryRelation($param)
    {
        //找出变体；
        $variants = [];
        $field = 'id,pid,name,order_type,has_child,type_name,min,max,doc,restriction,path';

        //先验证当前分类元素存不存在；
        $node = $this->xsdModel->where(['id' => $param['type_id']])->field('id')->find();
        if (empty($node)) {
            throw new Exception('未知type_id');
        }

        if (empty($param['child_type_id'])) {
            $elements = $this->getXsdRelationElements($node['id'], []);
            foreach ($elements as $key => $ele) {
                if ($ele['name'] == 'VariationTheme') {
                    $variants = $ele['restriction']['enumeration'] ?? [];
                    unset($elements[$key]);
                }
            }
        } else {
            //大分类除了小分类外的元素；
            $elements = $this->getXsdRelationElements($node['id'], ['ProductType']);

            //子分类的元素，变体在子分类里面
            $childnode = $this->xsdModel->where(['id' => $param['child_type_id']])->field('id')->find();
            if (empty($childnode)) {
                throw new Exception('未知child_type_id');
            }

            $productType = $this->xsdModel->where(['pid' => $param['type_id'], 'name' => 'ProductType'])->field('id')->find();
            //ProductType为空时，就是没有子分类，
            if (empty($productType)) {
                throw new Exception('未知child_type_id，大分类type_id下面不存在子分类');
            }

            //确保Nodetree的起点和大分类相同，以刊登时能匹上去；
            $childElements = $this->getXsdRelationElements($param['child_type_id'], []);

            //组合起大小节点；
            $elements = array_merge($elements, $childElements);
            foreach ($elements as $key => $ele) {
                if ($ele['name'] == 'VariationTheme') {
                    $variants = $ele['restriction']['enumeration'] ?? [];
                    unset($elements[$key]);
                }
            }
        }

        $news = [];
        foreach ($elements as $val) {
            $news[] = [
                'id' => $val['id'],
                'pid' => $val['pid'],
                'name' => $val['name'],
                'min_occurs' => $val['min'],
                'max_occurs' => $val['max'],
                'node_tree' => $val['path'],
                'parent_type_id' => $val['pid'],
                'restriction' => $val['restriction'],
                'sequence' => $val['order_type'],
                'type' => $val['type_name'],
                'type_id' => $val['id'],
                'variation' => '0',
                'create_time' => $val['create_time'],
            ];
        }

        return ['list' => $news, 'variants' => $variants];

        //if(empty($param['child_type_id'])) {
        //    $node = $this->model->where(['type_id' => $param['type_id']])->find();
        //    if(empty($node)) {
        //        throw new Exception('未知type_id');
        //    }
        //
        //    $elements = $this->getRelationElements($param['type_id'], $node['name']);
        //    foreach($elements as $key => $ele) {
        //        if($ele['name'] == 'VariationTheme') {
        //            $variants = $ele['restriction'] ?? [];
        //            unset($elements[$key]);
        //        }
        //    }
        //} else {
        //    //大分类除了小分类外的元素；
        //    $node = $this->model->where(['type_id' => $param['type_id']])->find();
        //    $elements = $this->getRelationElements($param['type_id'], $node['name'], ['ProductType']);
        //    if(empty($node)) {
        //        throw new Exception('未知type_id');
        //    }
        //
        //    //子分类的元素，变体在子分类里面
        //    $childnode = $this->model->where(['type_id' => $param['child_type_id']])->find();
        //    if(empty($childnode)) {
        //        $childnode = $this->relationElement->where(['type_id' => $param['child_type_id']])->find();
        //        if(empty($childnode)) {
        //            throw new Exception('未知child_type_id');
        //        }
        //    }
        //    $productType = $this->relationElement->where(['parent_type_id' => $param['type_id'], 'name' => 'ProductType'])->find();
        //    //ProductType为空时，就是没有子分类，
        //    if(empty($productType)) {
        //        throw new Exception('未知child_type_id，大分类type_id下面不存在子分类');
        //    }
        //    if(!$this->relationElement->where(['parent_type_id' => $productType['type_id'], 'type_id' => $param['child_type_id']])->count()) {
        //        throw new Exception('分类错误child_type_id不属于type_id下属分类');
        //    }
        //
        //    //确保Nodetree的起点和大分类相同，以刊登时能匹上去；
        //    $childTreeName = $node['name']. ',ProductType,'. $childnode['name'];
        //    $childElements = $this->getRelationElements($param['child_type_id'], $childTreeName);
        //    foreach($childElements as $key => $ele) {
        //        if($ele['name'] == 'VariationTheme') {
        //            $variants = $ele['restriction'] ?? [];
        //            unset($childElements[$key]);
        //        }
        //    }
        //    $elements = array_merge($elements, $childElements);
        //}
        //
        //$filterArr = (new AmazonXsdTemplate())->getFilterField();
        //
        ////销毁固定显示的字段和
        //foreach($elements as $key=>$value) {
        //    if(in_array($value['name'], $filterArr)) {
        //        unset($elements[$key]);
        //    }
        //}
        //$elements = array_merge($elements);
        //return ['list' => $elements, 'variants' => $variants];
    }


    /**
     * 拿取amazon_product_xsd里面的数据；
     * @param $id
     * @param $name
     * @param $filter
     * @return array
     */
    public function getXsdRelationElements($id, $filter, $min_occurs = null, $max_occurs = null)
    {
        $rows = [];
        $elements = $this->xsdModel->where(['pid' => $id])
            ->field('id,pid,name,order_type,has_child,type_name,min,max,doc,restriction,path,create_time')
            ->select();
        foreach ($elements as $element) {
            if ($element['name'] == 'ProductData') {
                continue;
            }
            //需要过滤的子级；
            if (in_array($element['name'], $filter)) {
                continue;
            }
            switch ($element['has_child']) {
                case 0:
                    $row = $element->toArray();
                    $row['min'] = (!is_null($max_occurs) && $min_occurs == 0) ? 0 : $row['min'];
                    $row['max'] = (!is_null($max_occurs) && $max_occurs == 0) ? 0 : $row['max'];

                    if ($row['restriction']) {
                        $row['restriction'] = json_decode($row['restriction'], true);
                    } else {
                        $row['restriction'] = [];
                    }
                    array_push($rows, $row);
                    break;
                case 1:
                    $row = $element->toArray();
                    $childRows = $this->getXsdRelationElements($row['id'], $filter, $row['min'], $row['max']);
                    $rows = array_merge($rows, $childRows);
                    break;
                default:
                    break;
            }
        }

        return $rows;
    }


    /**
     * 递归查找下级；
     * 因为是递归查打，occurs参数防止出现上层非必填，但下层必填，从而导致这条树所有参数都必填的情况
     * @param $id
     * @param $name
     * @param null $min_occurs
     * @param null $max_occurs
     * @return array
     */
    public function getRelationElements($id, $name, $filter = [], $min_occurs = null, $max_occurs = null)
    {
        $rows = [];
        $elements = $this->relationElement->where(['parent_type_id' => $id])->select();
        foreach ($elements as $element) {
            //var_dump($element->toArray());
            //continue;
            if ($element['name'] == 'ProductData') {
                continue;
            }
            //需要过滤的子级；
            if (in_array($element['name'], $filter)) {
                continue;
            }
            switch ($element['type_class_id']) {
                case '1':
                    $row = $element->toArray();
                    $row['max_occurs'] = (!is_null($max_occurs) && $max_occurs == 0) ? 0 : $row['max_occurs'];
                    $row['min_occurs'] = (!is_null($max_occurs) && $min_occurs == 0) ? 0 : $row['min_occurs'];

                    $row['node_tree'] = $name . ',' . $element['name'];
                    if ($row['restriction']) {
                        $row['restriction'] = json_decode($row['restriction']);
                    } else {
                        $row['restriction'] = [];
                    }
                    array_push($rows, $row);
                    break;
                case '2':
                    $row = $element->toArray();
                    $childRows = $this->getRelationElements($row['type_id'], $name . ',' . $row['name'], $filter, $row['min_occurs'], $row['max_occurs']);
                    $rows = array_merge($rows, $childRows);
                    break;
                case '3':
                    $row = $element->toArray();
                    $row['max_occurs'] = (!is_null($max_occurs) && $max_occurs == 0) ? 0 : $row['max_occurs'];
                    $row['min_occurs'] = (!is_null($max_occurs) && $min_occurs == 0) ? 0 : $row['min_occurs'];
                    $row['node_tree'] = $name . ',' . $element['name'];
                    if ($row['restriction']) {
                        $row['restriction'] = json_decode($row['restriction']);
                    } else {
                        $row['restriction'] = [];
                    }
                    array_push($rows, $row);
                    break;
            }
        }

        return $rows;
    }


    /**
     * 组成产品的json,此JSON会直接存在类文件里；
     * @return string
     */
    public function getProductSequnceJson()
    {
        return json_encode($this->getProductSequnce(), JSON_UNESCAPED_UNICODE);
    }


    /**
     * 组成产品的sequence数组；
     * @return string
     */
    public function getProductSequnce()
    {
        $productElement = $this->xsdModel->where(['name' => 'Product'])->find();
        $elements = $this->getSequence($productElement['id']);
        return ['Product' => $elements];
    }


    /**
     * 组成产品的json,此JSON会直接存在类文件里；
     * @return string
     */
    public function makePriceSequnceJson()
    {
        $productElement = $this->model->where(['name' => 'Price'])->find();
        $elements = $this->getRelationElementArray($productElement['type_id']);
        return json_encode(['Price' => $elements], JSON_UNESCAPED_UNICODE);
    }


    public function getRelationElementArray($id, $show_sequnce = false)
    {
        $rows = [];
        $elements = $this->relationElement->where(['parent_type_id' => $id])->order('sequence', 'ASC')->select();
        foreach ($elements as $element) {
            if ($element['name'] == 'ProductData') {
                $rows['ProductData'] = [];
                continue;
            }
            $row = $element->toArray();

            $key = $show_sequnce ? $row['name'] . '_' . $row['sequence'] : $row['name'];
            switch ($element['type_class_id']) {
                case '1':
                    $rows[$key] = '';
                    break;
                case '2':
                    $rows[$key] = $this->getRelationElementArray($row['type_id']);
                    break;
                case '3':
                    $rows[$key] = '';
                    break;
            }
        }

        return $rows;
    }


    /**
     * Amazon刊登生成分类模板路径数组，important！！！
     * 组成分类模板的json,此JSON会直接存在redis里,此类偶尔会调用；
     * @param $pid
     * @param $cid
     */
    public function getCategorySequence($pid, $cid = 0)
    {
        $element = $this->xsdModel->where(['id' => $pid])->find();
        if (empty($element)) {
            throw new Exception('生成分类模板路径数组错误，请联系开发人员核查');
        }
        $data[$element['name']] = $this->getSequence($pid, $cid);

        return $data;
    }


    /**
     * 获取产品，或者分类的排序xml元素组成的数组；
     * @param $pid
     * @param int $cid
     * @return array
     */
    public function getSequence($pid, $cid = 0)
    {
        $sequence = [];
        $eles = $this->xsdModel->where(['pid' => $pid])->field('id,name,pid,has_child,no')->order('no', 'asc')->select();
        foreach ($eles as $val) {
            if ($val['has_child'] == 1) {
                if ($val['name'] == 'ProductType') {
                    $sequence['ProductType'] = $this->getCategorySequence($cid);
                } elseif ($val['name'] == 'ProductData') {
                    $sequence['ProductData'] = [];
                } else {
                    $sequence[$val['name']] = $this->getSequence($val['id'], $cid);
                }
            } else {
                $sequence[$val['name']] = '';
            }
        }

        return $sequence;
    }
}
