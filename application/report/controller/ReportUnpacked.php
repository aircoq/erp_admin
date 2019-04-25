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
use app\report\service\ReportUnpackedService ;
use think\Request;


/**
 * Class ReportUnpacked
 * @package app\report\controller
 * @module 报表系统
 * @title 未拆包记录
 * @url /report/unpacked
 */
class ReportUnpacked extends Base
{
    /**
     * @title 获取未拆包记录列表
     * @url /report/unpacked
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        try {
            $params = $request->param();
            $service = new ReportUnpackedService();
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
            $service = new ReportUnpackedService();
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