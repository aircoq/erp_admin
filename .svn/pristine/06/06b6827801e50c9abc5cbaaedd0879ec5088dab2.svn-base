<?php
namespace app\goods\controller;

use think\Controller;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\cache\Cache;
use app\common\model\CategoryAttribute as CategoryAttributeModel;
use app\common\model\AttributeGroup;
use app\common\model\GoodsAttribute;
use app\common\model\GoodsSku as GoodsSkuModel;

/**
 * Class CategoryAttribute
 * @title 产品分类属性关联
 * @module 商品系统
 * @author ZhaiBin
 * @url /set-attributes
 * @package app\goods\controller
 */
class CategoryAttribute extends Base
{
    /**
     * 保存分类属性关联
     * @title 保存产品分类属性关联
     * @url /set-attributes
     * @method post
     * @param  \think\Request $request
     * @return \think\Response
     * @apiRelate app\goods\controller\Attribute::dictionary&getAttributeValue
     */
    public function save(Request $request)
    {
        $params = $request->param();
        $category_id = $params['category_id'];
        $data['group'] = json_decode($params['group'], true);
        if(empty($category_id) || empty($data['group'])){
            return json(['message' => '参数错误'],400);
        }
        $categories = Cache::store('category')->getCategoryTree();
        if (!isset($categories[$category_id]) || !empty($categories[$category_id]['child_ids'])) {
            return json(['message' => '分类不存在或者分类存在子分类'], 400);
        }
        $cateAttrModel = new CategoryAttributeModel();        
        //启动事务
        Db::startTrans();
        try {
            $attr_groups = [];
            $attr_lists  = [];
            $sort_int    = [];
            $del_lists   = [];
            $this->formatData($data['group'], $attr_groups, $attr_lists, $sort_int, $category_id); // 格式化数据
            array_multisort($sort_int, SORT_ASC, $attr_groups); // 排序
            unset($sort_int);
            $this->check($category_id, $attr_groups, $attr_lists, $del_lists); // 检测更新数据
            foreach($attr_groups as $group) { // 处理数据
                $attributeGroup = new AttributeGroup();
                if ('add' == $group['action']) {
                    $attributeGroup->allowField(true)->isUpdate(false)->save($group);
                    foreach($group['attributes'] as $attribute) {
                        $cateAttr                 = new CategoryAttributeModel();
                        $attribute['group_id']    = $attributeGroup->id;
                        $attribute['category_id'] = $category_id;
                        $attribute['value_range'] = json_encode($attribute['attribute_value']);
                        unset($attribute['attribute_value']);
                        $isUpdate = isset($attr_lists[$attribute['attribute_id']])&&!empty($attr_lists[$attribute['attribute_id']]['is_exist']) ? true : false;
                        if ($isUpdate) {
                            $cateAttr->allowField(true)->isUpdate(true)->save($attribute, ['category_id' => $category_id, 'attribute_id' => $attribute['attribute_id']]);
                            continue;
                        }
                        $cateAttr->allowField(true)->isUpdate(false)->save($attribute);
                    }
                } elseif ('modify' == $group['action']) {
                    $attributeGroup->allowField(true)->save($group, ['id' => $group['group_id']]);                   
                    foreach($group['attributes'] as $attribute) {
                        $cateAttr                 = new CategoryAttributeModel();
                        $attribute['group_id']    = $group['group_id'];
                        $attribute['category_id'] = $category_id;
                        $attribute['value_range'] = json_encode($attribute['attribute_value']);
                        unset($attribute['attribute_value']);
                        $isUpdate = isset($attr_lists[$attribute['attribute_id']])&&!empty($attr_lists[$attribute['attribute_id']]['is_exist']) ? true : false;
                        if ($isUpdate) {
                            $cateAttr->allowField(true)->isUpdate(true)->save($attribute, ['category_id' => $category_id, 'attribute_id' => $attribute['attribute_id']]);
                            continue;
                        }
                        $cateAttr->allowField(true)->isUpdate(false)->save($attribute);
                    }
                } else {
                    $attributeGroup->where(['id' => $group['group_id']])->delete();
                }
            }
            
            foreach($del_lists as $list) {
                $cateAttrModel->where(['category_id' => $category_id, 'attribute_id' => $list['attribute_id']])->delete();
            }
            Db::commit();
            //删除缓存
            Cache::handler()->del('cache:categoryAttribute');
            return json(['message' =>  '操作成功'], 200);
        } catch (Exception $e) {
            Db::rollback();
            return json(['message' => '操作失败' . $e->getMessage()], 400);
        }
    }

    /**
     * 显示指定的资源
     * @title 查看产品分类属性
     * @method get
     * @url /set-attributes/:id(\d+)
     * @return \think\Response
     * @apiRelate app\goods\controller\Attribute::dictionary&getAttributeValue
     */
    public function read($id)
    {   
        $lists = [];
        $result = Cache::store('category')->getAttribute($id);
        if(isset($result['group'])&&!empty($result['group'])){
            foreach($result['group'] as &$group) {
                $group['isUsed'] = false;
                foreach($group['attributes'] as &$attribute) {
                    $attribute['isUsed'] = false;
                    $attribute_info = Cache::store('attribute')->getAttribute($attribute['attribute_id']);
                    $attribute['name'] = isset($attribute_info['name'])?$attribute_info['name']:'';
                    if (empty($attribute['attribute_value'])) {
                        continue;
                    }
                    $values = [];
                    foreach($attribute['attribute_value'] as $value_id) {
                        if (!isset($attribute_info['value'][$value_id])) {
                            continue;
                        }
                        $attribute['isUsed'] = $attribute['isUsed'] ?: $this->isUsedValue($id, $attribute['attribute_id'], $value_id);
                        $group['isUsed'] = $group['isUsed'] ?: $attribute['isUsed'];
                        $values[] = [
                            'id' => $value_id, 
                            'value' => $attribute_info['value'][$value_id]['value'], 
                            'icon' => $attribute_info['value'][$value_id]['icon']
                        ];
                    }
                    $attribute['attribute_value'] = $values;
                }
                $lists[] = $group;
            }
        }        
        return json($lists,200);
    }


    /**
     * 格式化数据
     * @param array $group
     * @param array $attr_groups
     * @param array $attr_lists
     * @param array $sort_int
     * @param int $category_id
     */
    private function formatData(&$groups, &$attr_groups, &$attr_lists, &$sort_int, $category_id) {
        foreach($groups as $v){
            $temp = [];
            switch($v['action']) {
                case 'add' :
                    $temp['category_id'] = $category_id;
                    $temp['name']        = $v['name'];
                    $temp['create_time'] = time();
                    $temp['sort']        = isset($v['sort']) ? $v['sort'] : 0;
                    $temp['action']      = 'add';
                    $temp['sort_int']    = 0;
                    $sort_int[]          = 0;
                    foreach($v['attributes'] as $attribute) {
                        $list['attribute_id']    = $attribute['attribute_id'];
                        $list['alias']           = $attribute['alias'];
                        $list['required']        = $attribute['required'];
                        $list['sku']             = $attribute['sku'];
                        $list['gallery']         = $attribute['gallery'];
                        $list['attribute_value'] = $attribute['attribute_value'];
                        $temp['attributes'][]    = $list;
                        $attr_lists[$attribute['attribute_id']] = $list;
                        unset($list);
                    }
                    $attr_groups[] = $temp;
                break;
                case 'del' :
                    $temp['group_id'] = $v['group_id'];
                    $temp['sort_int'] = 2;
                    $temp['action']   = 'del';
                    $sort_int[]       = 2;
                    $attr_groups[]    = $temp;
                break;
                case 'modify' :
                    $temp['category_id'] = $category_id;
                    $temp['group_id']    = $v['group_id'];
                    isset($v['name']) ? $temp['name'] = $v['name'] : '';
                    isset($v['sort']) ? $temp['sort'] = $v['sort'] : '';
                    $temp['action']      = 'modify';
                    $temp['sort_int']    = 1;
                    $sort_int[]          = 1;
                    foreach($v['attributes'] as $attribute) {
                        $list['attribute_id']    = $attribute['attribute_id'];
                        $list['alias']           = $attribute['alias'];
                        $list['required']        = $attribute['required'];
                        $list['sku']             = $attribute['sku'];
                        $list['gallery']         = $attribute['gallery'];
                        $list['attribute_value'] = $attribute['attribute_value'];
                        $temp['attributes'][]    = $list;
                        $attr_lists[$attribute['attribute_id']] = $list;
                    }
                    $attr_groups[] = $temp;
                break;          
            }             
        }
    }
    
    /**
     * 检测数据
     * 
     * @param int $category_id
     * @param array $attr_groups
     * @param array $attr_lists
     * @param array $del_lists
     * @throw Exception
     */
    private function check($category_id, &$attr_groups, &$attr_lists, &$del_lists)
    {
        $validateAttrGroup        = validate('AttributeGroup');
        $message = '';
        // 检测添加分组在同一个分类下不能重复出现
        foreach($attr_groups as $group) {
            $group['category_id'] = $category_id;
            if ($group['action'] == 'add') {
                !$validateAttrGroup->check($group) ? $message .= $validateAttrGroup->getError() : '';
                !isset($group['attributes']) ? $message .= ' '. $group['name'] . '的属性不能为空' : '';
            }
        }
        // 检测属性能否被删        
        $search_lists = CategoryAttributeModel::where(['category_id' => $category_id])->select();
        foreach($search_lists as $list) {
            $attribute = Cache::store('attribute')->getAttribute($list['attribute_id']);
            if (isset($attr_lists[$list['attribute_id']])) {
                $attr_lists[$list['attribute_id']]['is_exist'] = true;
                // 检测已作为sku生成条件的属性
                if (0 == $attr_lists[$list['attribute_id']]['sku'] && 1 == $list['sku'] && $this->isParticipateSku($category_id, $list['attribute_id'])) {
                    $message .= '  ' . $attribute['name'] . '参与SKU生成不能，sku为必选 ';
                }
                // 检测属性值能否删除 --start
                if (empty($attr_lists[$list['attribute_id']]['attribute_value'])) {
                    continue;
                }
                $values = json_decode($list['value_range'], true) ?: array_keys($attribute['value']);
                $diff   = array_diff($values, $attr_lists[$list['attribute_id']]['attribute_value']);
                foreach($diff as $value_id) {
                    if ($this->isUsedValue($category_id, $list['attribute_id'], $value_id)) {
                        $message .= ' '. $attribute['name'] . '属性值为' . $attribute['value'][$value_id]['value'] . '不能被删除' . ' ';
                    }
                }
                // ---end               
            } else {
                // 检测属性能否删除
                if ($this->validateCheck($category_id, $list['attribute_id'])) {
                    $del_lists[] = $list;
                    continue;
                }
                $message .= '  '. $attribute['name'] . '不能被删除';
                break;
            }
        }
        
        if ($message) {
            throw new Exception($message);
        }
    }
    
    /**
     * 分类属性是否能删除
     * 
     * @param int $category_id
     * @param int $attribute_id
     * @return boolean
     */
    private function validateCheck($category_id, $attribute_id) {
        $select = GoodsAttribute::alias('ga')->where(['g.category_id' => $category_id, 'ga.attribute_id' => $attribute_id])->join('goods g', 'g.id=ga.goods_id')->count();
        if ($select) {
            return false;
        }
        
        return true;
    }
    
    /**
     *  属性值是否被使用
     * @param int $category_id
     * @param int $attribute_id
     * @param int $value_id
     * @return boolean
     */
    private function isUsedValue($category_id, $attribute_id, $value_id)
    {
       $select = GoodsAttribute::alias('ga')
            ->where(['g.category_id' => $category_id, 'ga.attribute_id' => $attribute_id, 'ga.value_id' => $value_id])
            ->join('goods g', 'g.id=ga.goods_id')
            ->count();
        if ($select) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 检测属性是否参与sku生成
     *
     * @param int $category_id
     * @param int $attribute_id
     * @return boolean
     */
    private function isParticipateSku($category_id, $attribute_id)
    {
        $count = GoodsSkuModel::alias('gs')
            ->where('g.category_id = ' . $category_id . ' AND gs.sku_attributes -> "$.attr_'. $attribute_id . '" != 0')
            ->join('goods g', 'g.id = gs.goods_id')
            ->count();
        return $count;
    }
    
}
