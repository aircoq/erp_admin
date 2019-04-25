<?php
namespace app\goods\controller;

use think\Controller;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\model\QcItem as QcItemModel;
use app\common\cache\Cache;

/**
 * Class QuantityCheck
 * @package app\goods\controller
 */
class QuantityCheck extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $request = Request::instance();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $model = new QcItemModel();        
        $QcItem=$model->getQcItemList($page,$pageSize);
        $QcItemList = $QcItem['list'];//print_r($QcItemList);
        $result = [
            'data' => $QcItemList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $QcItem['count'],
        ];
        return json($result, 200);
        
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data['name'] = $request->post('name', '');
        $data['type'] = $request->post('type', '');
        $data['sort'] = $request->post('sort', '');
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['create_id']='';
        $data['update_id']='';
        $QcItemModel = new QcItemModel();
        $validateQcItem = validate('QcItem');
        if (!$validateQcItem->check($data)) {
            return json($validateQcItem->getError(), 400);
        }
        Db::startTrans();
        try{
            $bool = $QcItemModel->allowField(true)->isUpdate(false)->save($data);
            $id = $QcItemModel->id;
            $data_val['qc_item_id']=$id;
            $data_val['content']=$request->post('content', '');
            $data_val['sort']='';
            $data_val['create_time']=time();
             Db::name('qc_item_value')->insert($data_val);
             Db::commit();
             return json(['message' => '新增成功','id' => $id], 200);
        }  catch (Exception $e){
            Db::rollback();
            return json(['message' => '新增失败'], 500);
        }
        
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $QcItemModel = new QcItemModel();
        $result = $QcItemModel->getQcItemInfo($id);
        $result = empty($result) ? [] : $result;
        return json($result, 200);
    }

    /**
     * 保存更新的资源
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
       if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $params = $request->param();
        $data['name'] = isset($params['name']) ? $params['name'] : '';
        $data['type'] = isset($params['type'])?$params['type']:0;
        $data['sort'] = isset($params['sort'])?$params['sort']:20;
        $data['update_time'] = time();
        $QcItemModel = new QcItemModel();
        //判断名称是否重复
        $where = "name = '".$data['name']."' and id != ".$id;
        $QcItemList = $QcItemModel->where($where)->select();
        if(!empty($QcItemList)){
            return json(['message' => '该质检已存在'], 500);
        }
        Db::startTrans();
        try{
            $result = $QcItemModel->allowField(true)->save($data, ['id' => $id]);
            $data_val['qc_item_id']=$id;
            $data_val['content']=$params['content'];
            $data_val['sort']=20;
            Db::name('qc_item_value')->where(['qc_item_id' => $id])->update($data_val);
            Db::commit();
            return json(['message' => '更新成功','id' => $id], 200);
        }  catch (Exception $e){
            Db::rollback();
            return json(['message' => '更新失败'], 500);
        }
    }

    /**
     * 删除指定资源
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        Db::startTrans();
        try{
            $QcItemModel = new QcItemModel();
            $result = $QcItemModel->where(['id' => $id])->delete();
            Db::name('qc_item_value')->where(['qc_item_id' => $id])->delete();
            Db::commit();
            return json(['message' => '删除成功','id' => $id], 200);
        }catch(Exception $e){
            Db::rollback();
            return json(['message' => '删除失败'], 500);
        }
        
    }
    
}