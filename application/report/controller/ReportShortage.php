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
use  app\report\service\ReportShortageService as reportShortageService;
use think\Request;


/**
 * Class ReportShortage
 * @package app\report\controller
 * @module 报表系统
 * @title 缺货记录
 * @url /report/shortage
 */
class ReportShortage extends Base
{
    /**
     * @title 获取缺货记录列表
     * @url /report/shortage
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        try {
            $params = $request->param();
            $service = new ReportShortageService();
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

            $service = new ReportShortageService();
            $re=$service->applyExport($params);
            return json($re);
        } catch (\Exception $ex) {

            $msg  = $ex->getMessage().$ex->getFile().$ex->getLine();

            return json(['message'=>$msg]);
        }
    }




}