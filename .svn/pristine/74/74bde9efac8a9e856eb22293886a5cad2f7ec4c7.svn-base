<?php
namespace app\goods\service;

use app\common\model\Goods;
use app\common\cache\Cache;
use app\common\model\GoodsAttribute;
use app\goods\service\GoodsHelp;
use think\Exception;
use think\Db;

/**
 * Created by NetBeans.
 * User: Leslie
 * Date: 2017/01/06
 * Time: 14:50
 * Class: GoodsQcItems
 */

class GoodsQcItems
{       
    /**
     * 获取产品基础质检项
     * @param int $goods_id
     * return array
     */
    public function getBaseGoodsQcItems($goods_id)
    {    
        $goods_info = Goods::where(['id' => $goods_id])->find();
        if (empty($goods_info) || empty($goods_info['category_id'])) {
            throw new Exception('产品不存在或者产品分类不能为0');
        }    
        $goods_attributes = $this->getGoodsAttributes($goods_id);
        $category_group = Cache::store('category')->getQcItems($goods_info['category_id']);
        $check_tool_lists = $this->getCheckTool();
        $groups = [];
        foreach($category_group['group'] as $group) {
            foreach($group['items'] as $k => &$item) {
                if (!isset($goods_attributes[$item['attribute_id']])) {
                    unset($group['items'][$k]);
                    continue;
                }                
                $item['name']    = $goods_attributes[$item['attribute_id']]['name']; // 质检名称
                $item['content'] = ''; // 质检内容
                if (2 != $goods_attributes[$item['attribute_id']]['type']) {
                    foreach($goods_attributes[$item['attribute_id']]['attribute_value'] as $value) {
                        $value['selected'] ? $item['content'] .= ($item['content'] ? ',' : '') . $value['value'] : '';
                    }
                } else {
                    $item['content'] = $goods_attributes[$item['attribute_id']]['type'];
                }
                
                $check_tool = ''; // 检具
                foreach ($item['check_tool'] as $check_tool_id) {
                    $check_tool .= $check_tool ? ', ' : '';
                    $check_tool .= isset($check_tool_lists[$check_tool_id]) ? $check_tool_lists[$check_tool_id] : '';
                }
                $item['check_tool'] = $check_tool;
            }
            $groups[] = $group;
        }
        
        return $groups;
    }
    
    /**
     * 产品分类质检项
     * @param int $goods_id
     * @param int $level
     * return array
     */
    public function getGoodsQcItems($goods_id, $level = 1)
    {
       $selected_items = $this->getSelectedQcItems($goods_id);
       $groups = $this->getBaseGoodsQcItems($goods_id);
       foreach($groups as &$group) {
            foreach($group['items'] as $k => &$item) {
                if (in_array($item['attribute_id'], $selected_items)) {
                    $item['enabled'] = true;
                    continue;
                }
                if ($level == 1) {                   
                    unset($group['items'][$k]);
                } else {
                    $item['enabled'] = false;
                }
            }
        }
        
        return $groups;
    }
    
    /**
     * 获取已选产品质检项
     * @param int $goods_id
     * @return array
     */
    private function getSelectedQcItems($goods_id)
    {
        $result = [];
        $items = GoodsAttribute::where(['goods_id' => $goods_id, 'is_qc' => 1])->field('attribute_id')->select();
        foreach($items as $item) {
            if (!in_array($item['attribute_id'], $result)) {
                $result[] = $item['attribute_id'];
            }
        }
        return $result;
    }
    
    /**
     * 保存质检已选质检项
     * @param int $goods_id
     * @param array $lists
     * @throw Exception
     */
    public function saveGoodsQcItems($goods_id, $lists)
    {
        $goods_info = Goods::where(['id' => $goods_id])->find();
        if (empty($goods_info)) {
            throw new Exception('产品不存在');
        }
        $store_lists = $this->getSelectedQcItems($goods_id);
        $diff = array_diff($store_lists, $lists);
        
        Db::startTrans();
        try {
            $this->checkGoodsQcItems($goods_info['category_id'], $lists);
            
            if ($diff) {
                foreach($diff as $attribute_id) {
                    $goodsAttribute = new GoodsAttribute();
                    $goodsAttribute->where(['attribute_id' => $attribute_id, 'goods_id' => $goods_id])->update(['is_qc' => 0]);
                }
            }
            if ($lists) {
                foreach($lists as $attribute_id) {
                    $goodsAttribute = new GoodsAttribute();
                    $goodsAttribute->where(['attribute_id' => $attribute_id, 'goods_id' => $goods_id])->update(['is_qc' => 1]);
                }
            }
            // 提交事务
            Db::commit();           
        } catch (Exception $ex) {
            Db::rollBack();
            throw new Exception($ex->getMessage());
        }
    }
    
    /**
     * 检测产品质检项
     * @param int $category_id
     * @param int $lists
     * @throws Exception
     */
    private function checkGoodsQcItems($category_id, $lists) 
    {
        $message = '';
        $groups = Cache::store('category')->getQcItems($category_id);
        foreach($groups['group'] as $group) {
            foreach($group['items'] as $item) {
                if ($item['is_qc_required'] && !in_array($item['attribute_id'], $lists)) {
                    $attribute = Cache::store('attribute')->getAttribute($item['attribute_id']);
                    $message .= (isset($attribute['name']) ? $attribute['name'] : ' ') . '是必检项! ';
                }
            }
        }
        
        if ($message) {
            throw new Exception($message);
        }
    }
    
    /**
     * 获取产品属性
     * @param int $goods_id
     * @return array
     */
    public function getGoodsAttributes($goods_id)
    {
        $goods = new GoodsHelp();
        $attributes = $goods->getAttributeInfo($goods_id, 2);
        $goods_attributes = [];
        foreach($attributes as $attribute) {
            $values = [];
            if (2 == $attribute['type']) {
                $goods_attributes[$attribute['attribute_id']] = $attribute;
                continue;
            }
            foreach($attribute['attribute_value'] as $k => $list) {
                if (empty($list['selected'])) {
                    unset($attribute['attribute_value'][$k]);
                    continue;
                }
                $values[$list['id']] = $list;
            }
            $attribute['attribute_value'] = $values;
            $goods_attributes[$attribute['attribute_id']] = $attribute;
        }
        
        return $goods_attributes;
    }
    
    /**
     * 获取检具
     * @return array
     */
    private function getCheckTool()
    {
        $lists = [];
        $result = (new \app\goods\controller\CategoryQc)->getCheckTool();
        foreach($result as $list) {
            $lists[$list['id']] = $list['name'];
        }
        return $lists;
    }
}
