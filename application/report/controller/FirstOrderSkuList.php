<?php
namespace app\report\controller;
use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\report\service\FirstOrderSkuListService;
use app\report\service\FirstOrderSkuListExportService;
use think\Request;

/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2018/9/19
 * Time: 17:26
 */

/**
 * @title 首次生成SKU列表
 * @url /first-order
 * Class FirstOrderSkuList
 * @package app\report\controller
 */
class FirstOrderSkuList extends Base
{
    protected $skuListService;
    protected $skuListExportService;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        if (is_null($this->skuListService)) {
            $this->skuListService = new FirstOrderSkuListService();
        }
        if (is_null($this->skuListExportService)) {
            $this->skuListExportService = new FirstOrderSkuListExportService();
        }
    }

    /**
     * @title 首次出单列表
     * @url /first-order
     * @method get
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        try{
            $request = Request::instance();
            $params = $request->param();
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);
            $firstOrderList = $this->skuListService->index($params, $page, $pageSize);
            return json($firstOrderList, 200);
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title execl字段信息
     * @url export-title
     * @method get
     * @return \think\response\Json
     */
    public function title()
    {
        $exportTitle = $this->skuListExportService->title();
        $title = [];
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
     * @title 导出
     * @url export
     * @method post
     * @return \think\response\Json
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \think\Exception
     */
    public function export()
    {
        $request = Request::instance();
        $params = $request->param();
        $ids = $request->post('ids', 0);
        if (isset($request->header()['x-result-fields'])) {
            $field = $request->header()['x-result-fields'];
            $field = explode(',', $field);
        } else {
            $field = [];
        }
        $type = $request->post('export_type', 0);
        $ids = json_decode($ids, true);
        if (empty($ids) && empty($type)) {
            return json(['message' => '请先选择一条记录'], 400);
        }
        if (!empty($type)) {
            $params = $request->param();
            $ids = [];
        }
        $result = $this->skuListExportService->exportOnline($ids, $field, $params);
        return json($result);
    }
}