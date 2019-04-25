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
use  app\report\service\ExportFileList as ExportFileListServ;
use think\Request;


/**
 * Class ProfitStatement
 * @package app\report\controller
 * @module 报表系统
 * @title 文件导出申请列表
 * @url /report/export-files
 */
class ExportFileList extends Base
{
    /**
     * @title 获取用户的导出文件申请列表
     * @url /report/export-files
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        try {
            $params = $request->param();
            $userId = Common::getUserInfo($request)->user_id;
            $serv = new ExportFileListServ();
            $data = $serv->getExportList(array_merge($params, ['user_id' => $userId]));
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
     * @title 删除报表
     * @method delete
     * @url deletes/:id
     * @author libaimin
     */
    public function deletes($id)
    {
        try {
            if (!$id) {
                throw new Exception("请输入编号！");
            }
            $serv = new ExportFileListServ();
            $serv->deletes($id);
            return json(['message' => '操作成功']);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }


}