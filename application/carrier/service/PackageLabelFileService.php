<?php
/**
 * Created by PhpStorm.
 * User: TOM
 * Date: 2017/8/23
 * Time: 17:27
 */

namespace app\carrier\service;


use erp\AbsServer;
use org\Curl;
use think\Config;
use think\Exception;

class PackageLabelFileService extends AbsServer
{
    /**
     * 上传包裹物流面单
     * @param $packageNumber //包裹号(这里用作文件名)
     * @param $fileContent   //面单内容
     * @param $ext           //文件类型
     * @return string
     * @throws Exception
     */
    public function uploadLabelFile($packageNumber,$fileContent,$ext)
    {
        if (empty($packageNumber)) {
            throw new Exception('包裹号不能为空');
        }
        if (!in_array($ext, ['jpg', 'gif', 'png', 'jpeg','pdf'])) {
            throw new Exception('文件格式不支持');
        }
        if (empty($fileContent)) {
            throw new Exception('文件内容不能为空');
        }
        $path = 'label/'.date('Y').'/'.date('m').'/'.date('d');
        $info = [
            'path'      => $path,
            'name'      => $packageNumber,
            'content'   => $fileContent,
            'file_ext'  => $ext
        ];
        $url = Config::get('picture_upload_url') . '/upload.php';
        $strJson = Curl::curlPost($url, $info);
        $request = json_decode($strJson, true);
        if ($request && $request['status'] ==1) {
            return $path . '/' .$packageNumber . '.'. $ext;
        }
        throw new Exception($request ? $request['error_message'] : '文件上传失败');
    }

    /**
     * @title 上传包裹物流面单
     * @param $packageNumber
     * @param $fileContent
     * @param string $ext
     * @return string
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function uploadHtmlFile($packageNumber,$fileContent,$ext="html"){
        if (empty($packageNumber)) {
            throw new Exception('包裹号不能为空');
        }
        if (!in_array($ext, ["html"])) {
            throw new Exception('文件格式不支持');
        }
        if (empty($fileContent)) {
            throw new Exception('文件内容不能为空');
        }
        $path = 'html/'.date('Y').'/'.date('m').'/'.date('d');
        $info = [
            'path'      => $path,
            'name'      => $packageNumber,
            'content'   => $fileContent,
            'file_ext'  => $ext
        ];
        $url = Config::get('picture_upload_url') . '/upload.php';
        $strJson = Curl::curlPost($url, $info);
        $request = json_decode($strJson, true);
        if ($request && $request['status'] ==1) {
            return $path . '/' .$packageNumber . '.'. $ext;
        }
        throw new Exception($request ? $request['error_message'] : '文件上传失败');
    }
}