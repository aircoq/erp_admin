<?php
// +----------------------------------------------------------------------
// |导出文件列表
// +----------------------------------------------------------------------
// | File  : ExportFileList.php
// +----------------------------------------------------------------------
// | Author: LiuLianSen <3024046831@qq.com>
// +----------------------------------------------------------------------
// | Date  : 2017-08-07
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
namespace app\report\controller;


use app\common\controller\Base;
use app\common\service\Common;
use  app\report\service\ReportUnshippedService as reportUnshippedService;
use think\Request;


/**
 * Class ReportShipped
 * @package app\report\controller
 * @module 报表系统
 * @title 未发货记录
 * @url /report/unshipped
 */
class ReportUnshipped extends Base
{
    /**
     * @title 获取未发货记录列表
     * @url /report/unshipped
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        try {
            $params = $request->param();
            $service = new ReportUnshippedService();
            $data = $service->logShipped($params);
            return json($data);
        }catch (\Exception $ex){
            $msg = $ex->getMessage().$ex->getLine();

            return json(['message'=>$msg]);
        }
    }
    /**
     * @title 导出
     * @url export
     * @method post
     */
    public function export(Request $request)
    {
        $params = $request->param();

        try{
            $service = new ReportUnshippedService();
            $re=$service->applyExport($params);
            return json($re);
        } catch (\Exception $ex) {
            $code = $ex->getCode();
            $msg  = $ex->getMessage();
            if(!$code){
                $code = 400;
                $msg  = '程序内错误';
            }
            return json(['message'=>$msg], $code);
        }
    }
    
}