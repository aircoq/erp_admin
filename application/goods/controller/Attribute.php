<?php
namespace app\goods\controller;

use think\Exception;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\cache\Cache;
use app\common\model\Attribute as AttributeModel;
use app\common\model\AttributeValue;
use app\common\model\GoodsAttribute;
use app\common\model\CategoryAttribute as CategoryAttributeModel;

/**
 * Class Attribute
 * @package app\goods\controller
 * @module 商品系统
 * @title 属性管理
 * @url /attributes
 */
class Attribute extends Base
{
    /**
     * 显示资源列表
     * @title 查看属性列表
     * @method get
     * @url /attributes
     * @return \think\Response
     */
    public function index(Request $request)
    {
        if (isset($request->header()['X-Result-Fields'])) {
            $field = $request->header()['X-Result-Fields'];
        }
        $page = $request->get(REQ_PAGE, 1);
        $pageSize = $request->get('pageSize', 10);
        $attributes_list = Cache::store('attribute')->getAttribute();
        $count = count($attributes_list);
        $new_array = Cache::page($attributes_list, $page, $pageSize);
        $result = [
            'data' => $new_array,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return json($result, 200);
    }

    /**
     * 保存新建的资源
     * @title 保存属性
     * @method post
     * @url /attributes
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params = $request->param();
        $data['name'] = $params['name'];
        $data['type'] = isset($params['type']) ? $params['type'] : 0;
        isset($params['code']) ? $data['code'] = $params['code'] : '';
        $attributeModel = new AttributeModel();
        $validateAttribute = validate('Attribute');
        if(!$validateAttribute->check($data)){
            return json(['message' => $validateAttribute->getError()],500);
        }
        //启动事务
        Db::startTrans();
        try {
            $data['create_time'] = time();
            $attributeModel->allowField(true)->isUpdate(false)->save($data);
            $aid = $attributeModel->id;
            $data['attribute_value'] = isset($params['attribute_value']) ? json_decode($params['attribute_value'], true) : [];
            if(!empty($data['attribute_value'])){
                $attributeValue = new AttributeValue();
                $attributeValue->saveData($data['attribute_value'],$aid);
            }
            Db::commit();
            //删除缓存
            Cache::handler()->del('cache:attribute');
            return json(['message' =>  '新增成功', 'id' => $aid], 200);
        } catch (Exception $e) {
            Db::rollback();
            return json(['message' => '新增失败'], 500);
        }
    }

    /**
     * 显示指定的资源
     * @title 查看属性详情
     * @method get
     * @url /attributes/:id(\d+)
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        $attribute_list[$id] = Cache::store('attribute')->getAttribute($id);
        $attribute = Cache::filter($attribute_list,[],'id,name,type,code,value');
        $temp = [];
        if(!empty($attribute[$id]['value'])){
            $attribute_value = Cache::filter($attribute[$id]['value'],[],'id,attribute_id,value,code,icon,sort');
            if(is_array($attribute_value)){
                foreach($attribute_value as $k => $v){
                    array_push($temp,$v);
                }
            }
        }
        $result = $attribute[$id];
        $result['value'] = $temp;
        return json($result,200);
    }

    /**
     * 显示编辑资源表单页.
     * @title 编辑属性
     * @method get
     * @url /attributes/:id(\d+)/edit
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        $attribute_list[$id] = Cache::store('attribute')->getAttribute($id);
        $attribute = Cache::filter($attribute_list,[],'id,name,type,code,value');
        $temp = [];
        if(!empty($attribute[$id]['value'])){
            $attribute_value = Cache::filter($attribute[$id]['value'],[],'id,attribute_id,value,code,sort,icon');
            if(is_array($attribute_value)){
                foreach($attribute_value as $k => $v){
                    array_push($temp,$v);
                }
            }
        }
        $result = $attribute[$id];
        $result['value'] = $temp;
        return json($result,200);
    }

    /**
     * 保存更新的资源
     * @title 更新属性
     * @method put
     * @url /attributes/:id(\d+)
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request,$id)
    {
        $params = $request->param();
        if(!isset($params['name']) || !isset($params['type'])){
            return json(['message' => '参数错误'],500);
        }
        $data['id']   = $id;
        $data['name'] = $params['name'];
        $data['type'] = isset($params['type']) ? $params['type'] : 0;
        $attributeModel = new AttributeModel();
        $attrValidate   = validate('attribute');
        if(!$attrValidate->check($data)){
            return json(['message' => '该属性名已被使用'],500);
        }
        $attrValueModel = new AttributeValue();
        //启动事务
        Db::startTrans();
        try {
            if(!$attributeModel->hasData($id)){
                return json(['message' => '该属性不存在'], 500);
            }
            $data['attribute_value'] = isset($params['attribute_value']) ? json_decode($params['attribute_value'], true) : [];
            $add_lists    = [];
            $modify_lists = [];
            $del_lists    = [];
            $this->check($data, $add_lists, $modify_lists, $del_lists);
            $attributeModel->allowField(true)->save($data,['id' => $id]);           
            if ($del_lists) {
                $attrValueModel->deleteData($del_lists, $id);
            }
            if ($add_lists) {
                $attrValueModel->saveData($add_lists, $id);
            }
            if ($modify_lists) {
                $attrValueModel->updateData($modify_lists);
            }
            Db::commit();
            //删除缓存
            Cache::handler()->del('cache:attribute');
            return json(['message' => '更新成功'], 200);
        } catch ( Exception $e) {
            Db::rollback();
            $message = $e->getMessage();
            return json(['message' => '更新失败 '. $message], 500);
        }
    }
    
    /**
     * 检查属性能否更新
     *
     * @param array $data
     * @param array $add_lists
     * @param array $modify_lists,
     * @param array $del_lists
     * @throws Exception
     */
    private function check(&$data, &$add_lists, &$modify_lists, &$del_lists)
    {   
        $message = '';
        $attribute_value = Cache::store('attribute')->getAttribute($data['id']);
        if (!empty($attribute_value)){
            $attribute_value = Cache::filter($attribute_value['value'],[],'');
        }
        
        foreach($data['attribute_value'] as $list) {
            if (isset($list['id']) && isset($attribute_value[$list['id']])) {
                unset($attribute_value[$list['id']]);
                $modify_lists[] = $list;
            } else {
                $list['attribute_id'] = $data['id'];
                $add_lists[] = $list;
            }
        }
        
        if ($attribute_value) {
            $del_lists = $attribute_value;
            foreach($del_lists as $list) {
                if ($this->isUsedGoods($data['id'], $list['id'])) {
                    $message .= isset($list['value']) ? $list['value'] : $list['id'];
                    $message .= '属性值已在产品中使用不能删除！';
                    break;
                }
                
                if ($this->isUsedCategory($data['id'], $list['id'])) {
                    $message .= isset($list['value']) ? $list['value'] : $list['id'];
                    $message .= '属性值已在产品中使用不能删除！';
                    break;
                }
            }
        }
        
        if ($message) {
            throw new Exception($message);
        }
        
    }
    
    /**
     * 检测具体属性值在产品中是否使用
     * @param int $attribute_id
     * @param int $attribute_value_id
     * @return boolean 
     */
    private function isUsedGoods($attribute_id, $attribute_value_id)
    {
        if (GoodsAttribute::where(['attribute_id' => $attribute_id, 'value_id' => $attribute_value_id])->count()) {
            return true;
        }
        return false;
    }
    
    /**
     * 检测具体分类是否使用此属性值
     * @param int $attribute_id
     * @param int $attribute_value_id
     * @return boolean 
     */
    private function isUsedCategory($attribute_id, $attribute_value_id) 
    {
        static $lists = [];
        if (!$lists) {
            $lists = CategoryAttributeModel::where(['attribute_id' => $attribute_id])->field('value_range')->select();
        }
        foreach($lists as $list) {
            $values = json_decode($list['value_range'], true);
            if (empty($values)) {
                continue;
            }
            if (in_array($attribute_value_id, $values)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 删除指定资源
     * @title 删除属性
     * @method delete
     * @url /attributes/:id(\d+)
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $attributeModel = new AttributeModel();
        if($this->hasBind($id)){
            return json(['message' => '该属性已经绑定分类了'],500);
        }
        //启动事务
        Db::startTrans();
        try {
            $attributeModel->where(['id' => $id])->delete();
            $attributeValue = new AttributeValue();
            $attributeValue->where(['attribute_id' => $id])->delete();
            Db::commit();
            //删除缓存
            Cache::handler()->del('cache:attribute');
            return json(['message' => '删除成功'], 200);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['message' => '删除失败'], 500);
        }
    }
    
    /** 
     * 查看属性是否已经被绑定了
     * @param int $attribute_id
     * @return bool
     */
    private function hasBind($attribute_id = 0)
    {
        $data = CategoryAttributeModel::where(['attribute_id' => $attribute_id])->count();
        if(empty($data)){   //没有绑定
            return false;
        }
        return true;
    }
    
    /**
     * @title 属性字典
     * @url /attribute/dictionary
     * @method get
     * @return \think\Response
     */
    public function dictionary()
    {
        $request  = Request::instance();
        $pageSize = $request->param('pageSize', 20);
        $page     = $request->param('page', 1);
        $name     = $request->param('name');
        $attribute_lists = Cache::store('attribute')->getAttribute();
        if ($name) {
           $new_array = Cache::filter($attribute_lists, [['name', 'like', $name]], 'id,name');
           $count = count($new_array);
           $lists = Cache::page($new_array, $page, $pageSize);
        } else {
          $new_array = Cache::filter($attribute_lists, [], 'id,name');
          $count = count($attribute_lists);
          $lists = Cache::page($new_array, $page, $pageSize);
        }
        $result = [
            'data' => $lists,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return json($result, 200);
    }
    
    /**
     * @title 属性质检字典
     * @url /attribute/qc_dictionary/:id(\d+)
     * @method get
     * @return \think\Response
     */
    public function qc_dictionary($id)
    {
        $request  = Request::instance();
        $pageSize = $request->param('pageSize', 20);
        $page     = $request->param('page', 1);
        $name     = $request->param('name');
        $group = Cache::store('category')->getAttribute($id);
        $attributes = Cache::store('attribute')->getAttribute();
        $attribute_lists = [];
        foreach($group['group'] as $group) {
            foreach($group['attributes'] as $attribute) {
                $attribute['name'] = isset($attributes[$attribute['attribute_id']]) ? $attributes[$attribute['attribute_id']]['name'] : '';
                $attribute_lists[] = $attribute;
            }
        }       
        if ($name) {
           $new_array = Cache::filter($attribute_lists, [['name', 'like', $name]], 'id,name');
           $count = count($new_array);
           $lists = Cache::page($new_array, $page, $pageSize);
        } else {
          $new_array = Cache::filter($attribute_lists, [], 'id,name');
          $count = count($attribute_lists);
          $lists = Cache::page($new_array, $page, $pageSize);
        }
        $result = [
            'data' => $lists,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return json($result, 200);
    }
    
    /**
     * @title 属性code
     * @public
     * @method get
     * @url /attribute/code
     * @return \think\Response
     */
    public function attributeCode()
    {
        $result = [
            ['code' => 'color',         'name' => '颜色'],
            ['code' => 'size',          'name' => '尺码'],
            ['code' => 'style',         'name' => '样式'],
            ['code' => 'specification', 'name' => '规格']
        ];
        return json($result, 200);
    }
    
    /**
     * @title 获取属性值根据属性Id
     * @method get
     * @url /attribute/getAttributeValue/:id(\d+)
     * @param  int $id
     * @return \think\Response
     */
    public function getAttributeValue($id)
    {
        $attribute_list[$id] = Cache::store('attribute')->getAttribute($id);
        $attribute = Cache::filter($attribute_list,[],'id,value');
        $temp = [];
        if(!empty($attribute[$id]['value'])){
            $attribute_value = Cache::filter($attribute[$id]['value'],[],'id,value');
            if(is_array($attribute_value)){
                foreach($attribute_value as $k => $v){
                    array_push($temp,$v);
                }
            }
        }

        return json($temp,200);
    }
    
    /**
     * @title 修改属性排序
     * @method put
     * @url /attribute/sorts
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function sorts(Request $request)
    {
        $sorts = json_decode($request->param('sorts'), true);
        if (empty($sorts)) {
            return json(['message' => '请求参数不能为空'], 400);
        }
        Db::startTrans();
        try {
            foreach($sorts as $sort) {
                $attribute = new AttributeModel();
                $attribute->save(['sort' => $sort['sort']], ['id' => $sort['id']]);
            }
            Db::commit();
            Cache::handler()->del('cache:attribute');
            return json(['message' => '操作成功'], 200);
        } catch (Exception $ex) {
            Db::rollback();
            return json(['message' => '操作失败'], 400);
        }
    }      
}
