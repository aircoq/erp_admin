<?php
namespace app\index\controller;

use app\common\controller\Base;
use think\Request;
use app\common\exception\JsonErrorException;
use app\index\service\DownloadFileService;

/**
 * @module 账号管理
 * @title 下载文件
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/21
 * Time: 14:38
 * @url /downfile
 */
class Download extends Base
{

    /**
     * @title 下载模板文件
     * @author PHILL
     * @method get
     * @url /downfile
     * @apiParam name:code type:strings require:1 desc:文件编码
     * @apiParam name:filePath type:strings require:1 desc:文件路径
     * @apiParam name:fileName type:string require:1 desc:下载后文件名
     * @remark 传参格式： 第一种方式：[ code ] 或者  第二种方式[ filePath 和  fileName ]<br />
     * @remark 第一种方式：文件编码（code） ： <br />
     * @remark code - 导入采购计划sku模板  [ plan_sku ] <br />
     * @remark code - 导入物流运费设置模板   [ carriage ] <br />
     * @remark code - 导入供应商报价模板       [ supplier_offer ] <br />
     * @remark code - 导入商品映射sku模板       [ good_sku_map ] <br />
     * @remark code - 导入安全期模板       [ save_delivery ] <br />
     */
    public function index(Request $request)
    {        
        $params    = $request->param();
        if(param($params, 'code')){
           $service = new DownloadFileService();
           $params = $service->formatData($params['code']);
        }
        if(empty(param($params, 'filePath')) || empty(param($params, 'fileName'))){
            throw new JsonErrorException('参数错误！');
        }
        $file = ROOT_PATH."/public/".$params['filePath'];
        //检查文件是否存在
        if(!is_file($file)){
            throw new JsonErrorException('文件不存在！');
        }
        
        header("Content-type:application/octet-stream");
        header( "Accept-Ranges:  bytes ");
        header( "Accept-Length: " .filesize(rtrim($file)));
        header( "Content-Disposition:attachment;filename= {$params['fileName']}");
        echo file_get_contents($file);
        return json('', 200);
    }
    
    
        
}