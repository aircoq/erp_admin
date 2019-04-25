<?php
namespace app\goods\controller;

use think\Controller;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\model\Unit as unitModel;
use app\common\model\Goods as GoodsModel;
use app\common\cache\Cache;

/**
 * Class Unit
 * @title 单位管理
 * @module 商品系统
 * @author ZhaiBin
 * @package app\goods\controller
 */
class Unit extends Base
{
    /**
     * @tile 显示单位列表
     * @method get
     * @url /unit
     * @return \think\Response
     */
    public function index()
    {
        $request = Request::instance();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $model = new unitModel();
        $count = $model->count();
        $unitList = $model->field('id,name,desc')->page($page, $pageSize)->select();
        $result = [
            'data' => $unitList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return json($result, 200);
    }

    /**
     * @title 保存单位
     * @method post
     * @url /unit
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data['name'] = $request->post('name', '');
        $data['desc'] = $request->post('desc', '');
        $data['create_time'] = time();
        $data['update_time'] = time();
        $unitModel = new unitModel();
        $validateUnit = validate('Unit');
        if (!$validateUnit->check($data)) {
            return json(['message' => $validateUnit->getError()], 400);
        }
        $bool = $unitModel->allowField(true)->isUpdate(false)->save($data);
        $id = $unitModel->id;
        if ($bool) {
            Cache::handler()->del('cache:unit');
            return json(['message' => '新增成功','id' => $id], 200);
        } else {
            return json(['message' => '新增失败'], 400);
        }
    }

    /**
     * @title 编辑单位
     * @method get
     * @url /unit/:id(\d+)/edit
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $unitModel = new unitModel();
        $result = $unitModel->field('id,name,desc')->where(['id' => $id])->find();
        $result = empty($result) ? [] : $result;
        return json($result, 200);
    }

    /**
     * @title 更新单位
     * @method put
     * @url /unit/:id(\d+)
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
        $data['desc'] = isset($params['desc']) ? $params['desc'] : '';
        $data['update_time'] = time();
        $unitModel = new unitModel();
        if (!$unitModel->isHas($id)) {
            return json(['message' => '该单位不存在'], 400);
        }
        //判断名称是否重复
        $where = "name = '".$data['name']."' and id != ".$id;
        $unitList = $unitModel->where($where)->select();
        if(!empty($unitList)){
            return json(['message' => '该单位已存在'], 400);
        }
        $result = $unitModel->allowField(true)->save($data, ['id' => $id]);
        if ($result) {
            Cache::handler()->del('cache:unit');
            return json(['message' => '更新成功'], 200);
        } else {
            return json(['message' => '更新失败'], 400);
        }
    }

    /**
     * @title 删除单位
     * @method delete
     * @url /unit/:id(\d+)
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $goodsModel = new GoodsModel();
        if ($goodsModel->hasUnit($id)) {
            return json(['message' => '该单位已被使用'], 400);
        }
        $unitModel = new unitModel();
        $result = $unitModel->where(['id' => $id])->delete();
        if ($result) {
            Cache::handler()->del('cache:unit');
            return json(['message' => '删除成功'], 200);
        } else {
            return json(['message' => '删除失败'], 400);
        }
    }
    
    /**
     * @title 获取单位字段值
     * @method get
     * @url /unit/dictionary
     * @return \think\Response
     */
    public function dictionary()
    {
        $result = Cache::store('unit')->getUnit();
        return json($result);
    }
}
