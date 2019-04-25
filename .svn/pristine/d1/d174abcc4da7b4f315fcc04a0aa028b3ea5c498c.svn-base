<?php

namespace app\goods\controller;

use app\common\service\Common;
use app\goods\service\GoodsSkuMapExportService;
use app\goods\service\GoodsSkuMapService;
use think\Controller;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\cache\Cache;
use app\common\model\GoodsSku as GoodsSkuModel;
use app\common\model\User;
use app\goods\service\GoodsSku as GoodsSkuService;
use app\goods\service\GoodsSkuMapLog as GoodsSkuMapLogService;

/**
 * @title 商品sku映射
 * @author phill
 * @url /sku-map
 * Class GoodsSkuMap
 * @package app\goods\controller
 */
class GoodsSkuMap extends Base
{
    protected $goodsSkuMapService = null;

    protected function init()
    {
        if (is_null($this->goodsSkuMapService)) {
            $this->goodsSkuMapService = new GoodsSkuMapService();
        }
    }

    /**
     * @title 显示资源列表
     * @return \think\Response
     * @apiRelate app\goods\controller\Category::index
     * @apiRelate app\goods\controller\GoodsSkuMap::save
     * @apiFilter app\goods\filter\GoodsSkuMapsFilter
     */
    public function index()
    {
        $request = Request::instance();
        $params = $request->param();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $result = $this->goodsSkuMapService->mapList($params, $page, $pageSize);
        return json($result, 200);
    }

    /**
     * @title 保存新建的资源
     * @param  \think\Request $request
     * @return \think\Response
     * @apiRelate app\order\controller\Order::getGoods
     */
    public function save(Request $request)
    {
        $params = $request->param();
        $data = $params;
        $validateGoodsSkuMap = validate('GoodsSkuMap');
        if (!$validateGoodsSkuMap->check($data)) {
            return json($validateGoodsSkuMap->getError(), 400);
        }
        if (!isset($data['channel_id']) || !isset($data['account_id']) || !isset($data['channel_sku']) || !isset($data['sku'])) {
            return json(['message' => '渠道账号等信息为必填'], 400);
        }
        $lock_key = 'goodsSkuMap:lock:' . $data['channel_id'] . ':' . $data['account_id'] . ':' . $data['channel_sku'];
        try {
            $lock = Cache::handler()->set($lock_key, 1, ['nx', 'ex' => 180]);
            if (!$lock) {
                return json(['message' => '操作频繁，请稍后再试~'], 400);
            }
            $map_id = $this->goodsSkuMapService->add($data);
            Cache::handler()->delete($lock_key);
            return json(['message' => '新增成功', 'id' => $map_id]);
        } catch (Exception $ex) {
            Cache::handler()->delete($lock_key);
            return json(['message' => $ex->getMessage()], 400);
        }

    }

    /**
     * @title 显示指定的资源
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $result = $this->goodsSkuMapService->info($id);
        return json($result);
    }

    /**
     * @title 显示指定的资源
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $result = $this->goodsSkuMapService->info($id);
        return json($result);
    }

    /**
     * @title 保存更新的资源
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     * @apiRelate app\order\controller\Order::getGoods
     */
    public function update(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $data = $request->param();
        if (!isset($data['channel_id']) || !isset($data['account_id']) || !isset($data['channel_sku']) || !isset($data['sku'])) {
            return json(['message' => '渠道账号等信息为必填'], 400);
        }
        $this->goodsSkuMapService->update($data, $id);
        return json(['message' => '更新成功']);
    }

    /**
     * @title 删除指定资源
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $this->goodsSkuMapService->batch([$id]);
        return json(['message' => '删除成功']);
    }

    /**
     * @title 批量删除
     * @url batch
     * @method post
     * @return \think\response\Json
     */
    public function batch()
    {
        $request = Request::instance();
        $data = $request->post('data', []);
        $this->goodsSkuMapService->batch($data);
        return json(['message' => '删除成功']);
    }

    /**
     * @title 获取平台信息
     * @url channel
     * @return \think\response\Json
     * @throws Exception
     */
    public function channel()
    {
        $channelData = Cache::store('channel')->getChannel();
        $result = [];
        foreach ($channelData as $k => $v) {
            $temp['label'] = $v['name'];
            $temp['value'] = $v['id'];
            array_push($result, $temp);
        }
        return json($result, 200);
    }

    /**
     * @title 获取账号信息
     * @url account
     * @return \think\response\Json
     * @throws Exception
     */
    public function account()
    {
        $request = Request::instance();
        $channel_id = $request->get('channel_id', 0);
        $content = $request->get('content', 0);
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        if (empty($channel_id) || !is_numeric($channel_id)) {
            return json(['message' => '参数错误'], 400);
        }
        $accountData = Cache::store('account')->getAccountByChannel($channel_id);
        $result = [];
        if (!empty($accountData)) {
            if (!empty($content)) {
                $where[] = ['code', 'like', $content];
                $accountList = Cache::filter($accountData, $where,
                    'id,account_name,code');
            } else {
                $accountList = $accountData;
            }
            $count = count($accountList);
            $accountData = [];
            foreach ($accountList as $key => $value) {
                $temp['id'] = intval($value['id']);
                $temp['account_name'] = $value['code'];
                array_push($accountData, $temp);
            }
            $result = [
                'data' => $accountData,
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $count,
            ];
        }
        return json($result, 200);
    }

    /**
     * @title 获取本地sku信息
     * @url skuInfo
     * @return \think\response\Json
     * @throws Exception
     */
    public function skuInfo()
    {
        $request = Request::instance();
        $content = $request->get('content', 0);
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $goodsSkuModel = new GoodsSkuModel();
        $where['sku'] = ['like', $content . '%'];
        $count = $goodsSkuModel->field('id,sku')->where($where)->count();
        $goodsSkuList = $goodsSkuModel->field('id,sku')->where($where)->page($page, $pageSize)->select();
        $result = [
            'data' => $goodsSkuList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return json($result, 200);
    }

    /**
     * @title 获取员工信息
     * @url employee
     * @return \think\response\Json
     * @throws Exception
     */
    public function employee()
    {
        $request = Request::instance();
        $content = $request->get('content', 0);
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $userModel = new User();
        $where['realname'] = ['like', '%' . $content . '%'];
        $count = $userModel->field('id,realname')->where($where)->count();
        $userList = $userModel->field('id,realname')->where($where)->page($page, $pageSize)->select();
        $result = [
            'data' => $userList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return json($result, 200);
    }

    /**
     * @title 搜索sku
     * @url query
     * @param Request $request
     * @return \think\response\Json
     */
    public function query(Request $request)
    {
        $keyword = $request->get('keyword', '');
        $goodsSkuService = new GoodsSkuService();
        $result = $goodsSkuService->querySkus($keyword);
        return json($result, 200);
    }

    /**
     * @title 查看是否已关联
     * @url map
     * @apiParam name:channel_sku desc:渠道sku
     * @apiParam name:sku_id desc:sku id
     * @apiParam name:channel_id desc:渠道id
     * @apiParam name:account_id desc:账号id
     * @return \think\response\Json
     */
    public function map()
    {
        $request = Request::instance();
        $channel_sku = $request->get('channel_sku', 0);
        $sku_id = $request->get('sku_id', 0);
        $channel_id = $request->get('channel_id', 0);
        $account_id = $request->get('account_id', 0);
        if (empty($channel_sku) || empty($sku_id) || empty($channel_id) || empty($account_id)) {
            return json(['message' => '参数错误']);
        }
        $result = $this->goodsSkuMapService->isMap($channel_sku, $sku_id, $channel_id, $account_id);
        return json($result);
    }


    /**
     * @title 导入商品映射信息
     * @method post
     * @author tanbin
     * @url /sku-map/import
     * @apiParam name:file type:string require:1 desc:excel文件流
     * @return \think\Response
     */
    function excelImport(Request $request)
    {
        $filePath = '';
        $file = $request->post('file', '');
        if (!$file) {
            return json(['message' => '参数错误'], 400);
        }
        try {
            $result = $this->goodsSkuMapService->saveExcelImportData($file);
            if ($result['status'] == 1) {
                return json($result, 200);
            } else {
                return json(['message' => '导入失败'], 400);
            }
        } catch (Exception $ex) {
            return json(['message' => '导入失败，' . $ex->getMessage()], 400);
        }


    }

    /**
     * @title 产品映射管理导出
     * @url export
     * @method post
     * @return \think\response\Json
     */
    public function export()
    {
        $request = Request::instance();
        $params = $request->param();
        $ids = $request->post('ids', 0);
//        if (isset($request->header()['x-result-fields'])) {
//            $field = $request->header()['x-result-fields'];
//            $field = explode(',', $field);
//        } else {
//            $field = [];
//        }
        $type = $request->post('export_type', 0);
        $ids = json_decode($ids, true);
        /*if (empty($type) && empty($ids)) {
            return json(['message' => '请选择一条记录'], 400);
        }
        if (!empty($type)) {
            $ids = [];
        }*/
        $goodsSkuMapExportService = new GoodsSkuMapExportService();
        $result = $goodsSkuMapExportService->exportApply($params, $ids);
        return json($result);
    }

    /**
     * @title 批量设置虚拟仓发货
     * @method put
     * @url batch/virtual
     * @author starzhan <397041849@qq.com>
     */
    public function batchSetVirtual()
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            if (!isset($param['ids']) || !$param['ids']) {
                throw new Exception('id不能为空');
            }
            if(!isset($param['is_virtual_send'])){
                throw new Exception('是否虚拟仓发货不能为空');
            }
            $ids = json_decode($param['ids']);
            $goodsSkuMapExportService = new GoodsSkuMapExportService();
            $result = $goodsSkuMapExportService->batchSetVirtual($ids,$param['is_virtual_send'],$userInfo);
            return json($result, 200);
        } catch (Exception $ex) {
            $arr = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage()
            ];
            return json($arr, 400);
        }
    }

    /**
     * @title 产品映射 管理日志
     * @url /sku-map/:id/logs
     * @method GET
     * @return json
     */
    public function logs($id)
    {
        $res = (new GoodsSkuMapLogService())->getLogsByMapId($id);
        return json($res, 200);
    }
}


