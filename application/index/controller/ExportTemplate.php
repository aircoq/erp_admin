<?php


namespace app\index\controller;


use app\common\controller\Base;
use app\common\service\Common;
use app\index\service\ExportTemplate as ServiceExportTemplate;
use think\Exception;

/**
 * @title 导出模板处理
 * @module 导出管理
 * @url export-template
 * @author starzhan <397041849@qq.com>
 */
class ExportTemplate extends Base
{

    /**
     * @title 获取我的模板
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function index()
    {
        try{
            $ExportTemplate = new ServiceExportTemplate();
            $userInfo = Common::getUserInfo($this->request);
            $param = $this->request->param();
            if(!isset($param['type'])||!$param['type']){
                throw new Exception('类型不能为空');
             }
            return json($ExportTemplate->getTemplateByCreateId($userInfo['user_id'], $param['type']), 200);
        }catch (Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }

    }

    /**
     * @title 获取导出模板详情
     * @author starzhan <397041849@qq.com>
     * @apiFilter app\common\filter\GoodsExportFilter
     */
    public function read($id)
    {
        $param = $this->request->param();
        $param['type'] = isset($param['type'])?$param['type']:0;
        $ExportTemplate = new ServiceExportTemplate();
        return json($ExportTemplate->getDetail($id), 200);
    }

    /**
     * @title 保存模板
     * @method post
     * @author starzhan <397041849@qq.com>
     */
    public function save(){
        try {
            $param = $this->request->param();
            $ExportTemplate = new ServiceExportTemplate();
            $userInfo = Common::getUserInfo($this->request);
            return json($ExportTemplate->TemplateSave($param,$userInfo['user_id']), 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 删除导出模板
     * @author starzhan <397041849@qq.com>
     */
    public function delete($id){
        try {
            $param = $this->request->param();
            $ExportTemplate = new ServiceExportTemplate();
            return json($ExportTemplate->delete($id), 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

}