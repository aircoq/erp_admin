<?php
namespace app\goods\controller;

use app\common\controller\Base;
use think\Request;
use app\goods\service\GoodsDeclare as GoodsDeclareService;

/**
 * @module 商品系统
 * @title 产品申报信息
 * @url goods-declare
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/11/30
 * Time: 13:54
 */
class GoodsDeclare extends Base
{
    protected $goodsDeclareService;

    public function init()
    {
        if (is_null($this->goodsDeclareService)) {
            $this->goodsDeclareService = new GoodsDeclareService();
        }
    }

    /**
     * @title 列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $params = $request->param();
        $where = [];
        if ($sku = param($params, 'sku')) {
            $where['sku'] = ['eq', $sku];
        }
        if (isset($params['snDate'])) {
            $params['date_b'] = isset($params['date_b']) ? $params['date_b'] : 0;
            $params['date_e'] = isset($params['date_e']) ? $params['date_e'] : 0;
            switch ($params['snDate']) {
                case 'create_time':
                    $condition = timeCondition($params['date_b'], $params['date_e']);
                    if (!is_array($condition)) {
                        return json(['message' => '日期格式错误'], 400);
                    }
                    if (!empty($condition)) {
                        $where['create_time'] = $condition;
                    }
                    break;
                default:
                    break;
            }
        }
        $result = $this->goodsDeclareService->lists($page, $pageSize, $where);
        return json($result);
    }

    /**
     * @title 保存
     * @param Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        $sku = $request->post('sku', 0);
        $declare_price = $request->post('declare_price', 0);
        $data['desc'] = $request->post('desc', '');
        $data['title'] = $request->post('title', '');
        $data['thumb'] = $request->post('thumb', '');
        $result = $this->goodsDeclareService->add($sku, $declare_price, $data);
        return json(['message' => '新增成功', 'data' => $result]);
    }

    /**
     * @title 更新
     * @param $id
     * @param Request $request
     * @return \think\response\Json
     */
    public function update($id, Request $request)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $declare_price = $request->put('declare_price', 0);
        $data['desc'] = $request->put('desc', '');
        $data['title'] = $request->put('title', '');
        $data['thumb'] = $request->put('thumb', '');
        if(empty($data['desc']) || empty($data['title']) || empty($data['thumb'])){
            return json(['message' => '参数内容不能为空']);
        }
        $this->goodsDeclareService->update($id, $declare_price, $data);
        return json(['message' => '更新成功']);
    }

    /**
     * @title 查看详情
     * @param $id
     * @return \think\response\Json
     */
    public function read($id)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $info = $this->goodsDeclareService->info($id);
        return json($info);
    }

    /**
     * @title 查看编辑详情
     * @param $id
     * @return \think\response\Json
     */
    public function edit($id)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $info = $this->goodsDeclareService->info($id);
        return json($info);
    }

    /**
     * @title 删除
     * @param $id
     * @return \think\response\Json
     */
    public function delete($id)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $this->goodsDeclareService->del($id);
        return json(['message' => '操作成功']);
    }
}