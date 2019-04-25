<?php
namespace app\index\controller;

use app\common\controller\Base;
use app\common\service\UploadService;
use think\Request;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/5/3
 * Time: 11:23
 */
class Upload extends Base
{
    /** 上传
     * @return \think\response\Json
     */
    public function index()
    {
        $request = Request::instance();
        $fileName = $request->post('filename',[]);
        $uploadService = new UploadService();
        $file = $uploadService->uploadFile(json_decode($fileName));
        if($file){
            return json(['message' => '上传成功','url' => $uploadService->_file_name_all],200);
        }else{
            return json(['message' => '上传失败'],500);
        }
    }
}