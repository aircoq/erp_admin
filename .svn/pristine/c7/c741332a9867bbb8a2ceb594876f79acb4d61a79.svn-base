<?php
namespace app\index\controller;

use app\common\controller\Base;
use app\index\service\ConfigService;
use think\Request;
use app\index\service\DownloadFileService;

/**
 * @module 导出管理
 * @title 账号
 * @author phill
 * @url /downloadFile
 */
class DownloadFile extends Base
{
    /**
     * @title 下载导出的文件
     * @method get
     * @url /downloadFile/downExportFile
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function downExportFile()
    {
        $request = Request::instance();
        $params = $request->param();
        $result = DownloadFileService::downExportFile($params);
        if($result['status'] != 1){
            return json($result, 500);
        }else{
             $filename = $result['saved_path']; //文件名
             $download_file_name = $result['download_file_name'];
             //$download_file_name = date('YmdHis');
            // header("Content-type:application/octet-stream");
             header('Content-type: application/vnd.ms-excel');
             header( "Accept-Ranges:  bytes ");
             header( "Accept-Length: " .filesize($filename));
             header( "Content-Disposition:attachment;filename= {$download_file_name}");
             echo file_get_contents($filename);
             exit;
            // return json($result, 200);
        }
    }

    /**
     * @title 下载打印机
     * @method get
     * @url /printer
     * @return \think\response\Json
     */
    public function downPrint()
    {
        $result['url'] = (new ConfigService())->printerUrl();
        return json($result);
    }

    /**
     * @title 下载发票pdf文件
     * @method get
     * @url /downloadFile/downPdfFile
     * @param Request $request
     */
    public function downPdfFile(Request $request)
    {
        $filePath = $request->get('filePath', '');
        downloadFile($filePath);
    }
}