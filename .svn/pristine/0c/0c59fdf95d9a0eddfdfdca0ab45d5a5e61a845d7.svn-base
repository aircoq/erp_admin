<?php
namespace app\goods\controller;

use think\Controller;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\model\CategoryAttribute as CategoryAttributeModel;
use app\common\cache\Cache;
use app\common\model\AttributeGroup;
use app\common\model\GoodsAttribute;

/**
 * Class CategoryQc
 * @title 产品分类质检关联
 * @module 商品系统
 * @url /set-qc
 * @package app\goods\controller
 */
class CategoryQc extends Base
{
    /**
     * 保存新建的资源
     * @title 保存分类质检关联
     * @url /set-qc
     * @method post
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params        = $request->param();
        $category_id   = $params['category_id'];
        $data['group'] = json_decode($params['group'],true);
        if(empty($category_id) || empty($data['group'])){
            return json(['message' => '参数错误'],400);
        }
        $categories = Cache::store('category')->getCategoryTree();
        if (!isset($categories[$category_id]) || !empty($categories[$category_id]['child_ids'])) {
            return json(['message' => '分类不存在或者分类存在子分类'], 500);
        }
        $items = [];
        foreach($data['group'] as $group) {
            foreach($group['items'] as $item) {
                $item['qc_group_id']          = $group['qc_group_id'];
                $items[$item['attribute_id']] = $item;
            }          
        }
        // 启动事务
        Db::startTrans();
        try {
            $modify_lists = [];
            $del_lists    = [];
            // 检测质检项能否删除
            $this->check($items, $category_id, $modify_lists, $del_lists);
            if ($modify_lists) {
                foreach($modify_lists as $list) {
                    $list['is_qc']      = 1;
                    $list['check_tool'] = json_encode($list['check_tool']);
                    $cateAttribute      = new CategoryAttributeModel();
                    $cateAttribute->allowField(true)->where(['category_id' => $category_id, 'attribute_id' => $list['attribute_id']])->update($list);
                }
            }
            if ($del_lists) {
                foreach($del_lists as $list) {
                    $list['is_qc']          = 0;
                    $list['qc_group_id']    = 0;
                    $list['is_qc_required'] = 0;
                    $list['check_tool']     = json_encode([]);
                    $cateAttribute          = new CategoryAttributeModel();
                    $cateAttribute->allowField(true)->where(['category_id' => $category_id, 'attribute_id' => $list['attribute_id']])->update($list);
                }
            }
            Db::commit();
            //删除缓存
            Cache::handler()->del('cache:categoryQc');
            return json(['message' => '保存成功'], 200);
        } catch (Exception $ex) {
            Db::rollBack();
            return json(['message' => '更新失败 ' . $ex->getMessage()], 400);
        }
    }
    
    /**
     * 检测质检项
     * @param array $items
     * @param int $category_id
     * @param array $modify_lists
     * @param array $del_lists
     * @throws Exception
     */
    private function check(&$items, $category_id, &$modify_lists, &$del_lists)
    {
        $search_lists = CategoryAttributeModel::where(['category_id' => $category_id, 'is_qc' => 1])->field('is_qc,attribute_id,qc_group_id,check_tool')->select();
        $message      = '';
        foreach($search_lists as $list) {
            $attribute = Cache::store('attribute')->getAttribute($list['attribute_id']);
            // 过滤需要删除的质检项
            if (isset($items[$list['attribute_id']])) {               
                continue;
            } else {
                if ($this->isUsedQcItem($list['attribute_id'], $category_id)) {
                    $message .= ' ' . (isset($attribute['name']) ? $attribute['name'] : $list['attribute_id']) . '已在产品中使用不能删除 ';
                }
                $del_lists[] = ['attribute_id' => $list['attribute_id']];
            }
        }
        $modify_lists = $items;
        if (!empty($message)) {
            throw new Exception($message);
        }
    }
    
    /**
     * 质检项是否在产品中已使用
     * @param int $attribute_id
     * @param int $category_id
     * @return boolean
     */
    private function isUsedQcItem($attribute_id, $category_id)
    {
        $item = GoodsAttribute::alias('ga')->join('goods g', 'g.id=ga.goods_id')->where(['g.category_id' => $category_id, 'ga.attribute_id' => $attribute_id, 'is_qc' => 1])->find();
        if (empty($item)) {
            return false;
        }
        return true;
    }

    /**
     * 显示指定的资源
     * @title 查看产品分类质检
     * @url /set-qc/:id(\d+)
     * @method get
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {   
        $lists = [];
        $result = Cache::store('category')->getQcItems($id);
        if(isset($result['group'])&&!empty($result['group'])){
            foreach($result['group'] as &$group) {
                foreach($group['items'] as &$attribute) {
                    $attribute_info = Cache::store('attribute')->getAttribute($attribute['attribute_id']);                
                    $attribute['name'] = isset($attribute_info['name'])?$attribute_info['name']:'';
                }
                $lists[] = $group;
            }
        }        
        return json($lists,200);
    }
    
    /**
     * @title 获取检具字段值
     * @method get
     * @url /goods/check_tool
     * @return \think\Response
     */
    public function checkTool()
    {
        $result = $this->getCheckTool();
        
        return json($result, 200);
    }
    
    /**
     * @title 获取质检组信息
     * @method get
     * @url /set-qc/group
     * @return \think\Response
     */
    public function getGroups()
    {
        $groups = AttributeGroup::where(['type' => 1])->field('id,name')->select();
        return json($groups, 200);
    }
    
    /**
     * 获取工具基础函数
     * @disabled
     * @return \think\Response
     */
    public function getCheckTool()
    {
        $result = [
            ['id' => 1, 'name' => '米尺'],
            ['id' => 2, 'name' => '卷尺'],
            ['id' => 3, 'name' => '秤']
        ];
        
        return $result;
    }
}