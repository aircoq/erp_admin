<?php
namespace app\report\controller;

use app\common\controller\Base;
use app\report\service\AmazonAccountMonitorService;
use think\Request;
/**
 * @module 报表系统
 * @title 亚马逊账号监控
 * @url report/amazon-monitor
 * Created by PhpStorm.
 * User: zhaixueli
 * Date: 2019/1/6
 * Time: 18:00
 */
class AmazonAccountMonitor extends Base
{
    protected $amazonAccountMonitorService;

    protected function init()
    {
        if(is_null($this->amazonAccountMonitorService)){
            $this->amazonAccountMonitorService = new AmazonAccountMonitorService();
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
        $result = $this->amazonAccountMonitorService->lists($page, $pageSize, $params);
        return json($result);
    }

    /**
     * @title 导出
     * @url export
     * @param Request $request
     * @method post
     * @return \think\response\Json
     */
    public function export(Request $request)
    {
        $params = $request->param();

        try{
            $this->amazonAccountMonitorService->applyExport($params);
            return json(['message'=> '成功加入导出队列']);
        } catch (\Exception $ex) {
            $code = $ex->getCode();
            $msg  = $ex->getMessage();
            if (!$code) {
                $code = 400;
                $msg  = '程序内部错误';
            }
            return json(['message'=>$msg], $code);
        }
    }


}