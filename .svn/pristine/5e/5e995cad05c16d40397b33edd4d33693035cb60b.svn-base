<?php
namespace app\report\controller;

use app\common\controller\Base;
use app\common\service\UniqueQueuer;
use app\report\queue\GoodsReportSaveQueue;
use app\report\service\GoodsAnalysisService;
use app\report\service\StatisticByGoods;
use app\report\service\StatisticGoods;
use think\Request;
use think\Validate;

/**
 * @module 报表系统
 * @title SKU销量动态表
 * @url report/sku-sales-dynamic
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2017/10/16
 * Time: 09:58
 */
class SkuSalesDynamic extends Base
{
    protected $statisticByGoodsService;

    const rule = [
        ['date_b', 'require','开始时间不能为空!'],
        ['date_e', 'require', '结束时间不能为空！'],
    ];


    protected function init()
    {
        if(is_null($this->statisticByGoodsService)){
            $this->statisticByGoodsService = new StatisticByGoods();
        }
    }

    /**
     * @title 列表详情
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $page = $request->get('page',1);
        $pageSize = $request->get('pageSize',10);
        $params = $request->param();
        $params['warehouse_id'] = param($params, 'warehouse_id', '');
        $validate = new Validate();
        if (!$validate->rule(self::rule)->check($params)) {
            return json(['message' => $validate->getError()], 400);
        }
        $diff_days = (strtotime($params['date_e'])-strtotime($params['date_b']))/86400;
        if ($diff_days>7) {
            return json(['message' => '查询间隔时间不能超过7天'], 400);
        }
        $result = $this->statisticByGoodsService->lists($page, $pageSize, $params);
        return json($result);
    }

    /**
     * @title execl字段信息
     * @url export-title
     * @method get
     * @return \think\response\Json
     * @apiFilter app\order\filter\OrderByExportFilter
     */
    public function title()
    {
        $exportTitle = $this->statisticByGoodsService->title();
        foreach ($exportTitle as $key => $value) {
            if ($value['is_show'] == 1) {
                $temp['key'] = $value['title'];
                $temp['title'] = $value['remark'];
                array_push($title, $temp);
            }
        }
        return json($title);
    }


    /**
     * @title 导出execl
     * @url export
     * @method post
     * @apiParam name:sku_ids desc:选中的id---不传表示全部
     * @apiParam name:export_type desc:0-部分  1-全部
     * @return \think\response\Json
     * @apiRelate app\index\controller\DownloadFile::downExportFile
     */
    public function export()
    {
        $request = Request::instance();
        $params = $request->param();
        $sku_ids = $request->post('sku_ids', 0);
        if (isset($request->header()['x-result-fields'])) {
            $field = $request->header()['x-result-fields'];
            $field = explode(',', $field);
        } else {
            $field = [];
        }
        $type = $request->post('export_type', 0);
        $sku_ids = json_decode($sku_ids, true);
        if (empty($sku_ids) && empty($type)) {
            return json(['message' => '请先选择一条记录'], 400);
        }
        if (!empty($type)) {
            $params = $request->param();
            $sku_ids = [];
        }
        $result = $this->statisticByGoodsService->exportOnLine($sku_ids, $field, $params);
        return json($result);
    }

}