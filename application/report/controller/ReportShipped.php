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
use  app\report\service\ReportShippedService as reportShippedService;
use think\Request;


/**
 * Class ReportShipped
 * @package app\report\controller
 * @module 报表系统
 * @title 已发货记录
 * @url /report/shipped
 */
class ReportShipped extends Base
{
    /**
     * @title 获取已发货记录列表
     * @url /report/shipped
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        try {
            $params = $request->param();
            $service = new ReportShippedService();
            $data = $service->logShipped($params);
            return json($data);
        }catch (\Exception $ex){
            $msg = $ex->getMessage();
            $code = $ex->getCode();
            if(!$code){
                $code =  500;
                $msg = '程序内部错误';
            }
            return json(['message'=>$msg],$code);
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
            $service = new ReportShippedService();
            $re=$service->applyExport($params);
            return json($re);
        } catch (\Exception $ex) {
            $msg  = $ex->getMessage();

            return json(['message'=>$msg]);
        }
    }




}