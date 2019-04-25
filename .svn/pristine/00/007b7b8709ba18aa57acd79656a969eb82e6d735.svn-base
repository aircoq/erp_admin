<?php
namespace app\goods\controller;

use think\Exception;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\model\Packing as packingModel;
use app\common\model\Goods as GoodsModel;
use app\common\model\Supplier;
use app\common\cache\Cache;

/**
 * Class Wish
 * @title 包装管理
 * @module 商品系统
 * @author ZhaiBin
 * @package app\goods\controller
 */
class Packing extends Base
{
    /**
     * @title 显示包装列表
     * @url /packing
     * @method get
     * @return \think\Response
     */
    public function index()
    {
        $request = Request::instance();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $model = new packingModel();
        $count = $model->count();
        $unitList = $model->field('id,title,cost_price,type,width,height,depth')->page($page, $pageSize)->select();
        $result = [
            'data' => $unitList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return json($result, 200);
    }

    /**
     * @title 创建包装信息
     * @url /packing
     * @method post
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data['title'] = $request->post('title', '');
        $data['cost_price'] = $request->post('cost_price', '');
        $data['width'] = $request->post('width', 0);
        $data['height'] = $request->post('height', 0);
        $data['depth'] = $request->post('depth', 0);
        $data['weight'] = $request->post('weight', 0);
        $data['remark'] = $request->post('remark', '');
        $data['type'] = $request->post('type', 0);
        $data['currency_code'] = $request->post('currency', '');  //货币,
        $data['supplier_id'] = $request->post('supplier', 0);  //供应商
        $data['create_time'] = time();
        $data['update_time'] = time();
        $packingModel = new packingModel();
        $validatePacking = validate('Packing');
        if (!$validatePacking->check($data)) {
            return json(['message' => $validatePacking->getError()], 400);
        }
        $result = $packingModel->allowField(true)->isUpdate(false)->save($data);
        $id = $packingModel->id;
        if ($result) {
            Cache::handler()->del('cache:packing');
            return json(['message' => '新增成功','id'=>$id], 200);
        } else {
            return json(['message' => '新增失败'], 400);
        }
    }

    /**
     * @title 编辑包装
     * @method get
     * @url /packing/:id(\d+)/edit
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $packingModel = new packingModel();
        $result = $packingModel->field('id,title,cost_price,width,height,depth,remark,type,currency_code,supplier_id,weight')->where(['id' => $id])->find();
        $result = empty($result) ? [] : $result;
        //判断供应商是否存在
        if(param($result, 'supplier_id')){
            $supplier = Cache::store('supplier')->getSupplier($result['supplier_id']);
            if(!param($supplier, 'company_name')){
                $result['supplier_id'] = 0;
            }
        }
        return json($result, 200);
    }

    /**
     * @title 更新包装信息
     * @method put
     * @url /packing/:id(\d+)
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
        $data['title'] = isset($params['title']) ? $params['title'] : '';
        $data['cost_price'] = isset($params['cost_price']) ? $params['cost_price'] : 0;
        $data['width'] = isset($params['width']) ? $params['width'] : 0;
        $data['height'] = isset($params['height']) ? $params['height'] : 0;
        $data['depth'] = isset($params['depth']) ? $params['depth'] : 0;
        $data['weight'] = isset($params['weight']) ? $params['weight'] : 0;
        $data['remark'] = isset($params['remark']) ? $params['remark'] : '';
        $data['type'] = isset($params['type']) ? $params['type'] : '';
        $data['currency_code'] = isset($params['currency']) ? $params['currency'] : '';
        $data['supplier_id'] = isset($params['supplier']) ? $params['supplier'] : 0;
        $data['update_time'] = time();
        $packingModel = new packingModel();
        $validatePacking = validate('Packing');
        if($packingModel->isRepeat($data['title'],$id)){
            if (!$validatePacking->check($data,$packingModel->rule())) {
                return json(['message' => $validatePacking->getError()], 400);
            }
        }else{
            return json(['message' => '该标题已存在'], 400);
        }
        if (!$packingModel->isHas($id)) {
            return json(['message' => '该材料不存在'], 400);
        }
        $result = $packingModel->allowField(true)->save($data, ['id' => $id]);
        if ($result) {
            Cache::handler()->del('cache:packing');
            return json(['message' => '更新成功'], 200);
        } else {
            return json(['message' => '更新失败'], 400);
        }
    }

    /**
     * @title 删除包装
     * @method delete
     * @url /packing/:id(\d+)
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $goodsModel = new GoodsModel();
        if ($goodsModel->hasPacking($id)) {
            return json(['message' => '该包装材料已被使用'], 400);
        }
        $packingModel = new packingModel();
        $result = $packingModel->where(['id' => $id])->delete();
        if ($result) {
            Cache::handler()->del('cache:packing');
            return json(['message' => '删除成功'], 200);
        } else {
            return json(['message' => '删除失败'], 400);
        }
    }

    /** 
     * @title 获取供应商信息
     * @method get
     * @url /packing/getSupplier
     * @return \think\response\Json
     */
    public function getSupplier()
    {
        $supplierModel = new Supplier();
        $supplierList = $supplierModel->field('id,company_name')->where(['status' => 1])->select();
        $result = empty($supplierList) ? [] : $supplierList;
        return json($result,200);
    }

    /** 
     * @title 获取币种类型
     * @method get
     * @public
     * @noauth
     * @url /packing/getCurrency
     * @return \think\Response
     */
    public function getCurrency()
    {
        $result = Cache::store('currency')->getCurrency();
        return json($result,200);
    }
    
    /**
     * @title 获取包装字典
     * @method get
     * @url /packing/dictionary
     * @noauth
     * @return \think\Response
     */
    public function dictionary()
    {
        $result = Cache::store('packing')->getPacking();
        return json(array_values($result),200);
    }
}
