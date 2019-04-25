<?php


namespace app\index\service;

use app\common\model\ExportTemplate as ModelExportTemplate;
use app\common\model\ExportTemplateDetail as ModelExportTemplateDetail;
use app\common\validate\ExportTemplateDetail as ValidateExportTemplateDetail;
use app\common\validate\ExportTemplate as ValidateExportTemplate;
use app\goods\service\GoodsImport;
use think\Exception;
use think\Db;

class ExportTemplate
{
    /**
     * @title 获取我的模板
     * @param $mid
     * @param $type
     * @return false|\PDOStatement|string|\think\Collection
     * @author starzhan <397041849@qq.com>
     * type 32  mymall海外仓导出  linpeng 2019年3月26日
     */
    public function getTemplateByCreateId($mid, $type)
    {
        return ModelExportTemplate::where('create_id', $mid)
            ->where('status', ModelExportTemplate::STATUS_AVAILABLE)
            ->where('type', $type)
            ->field('id,name')
            ->order('id desc')
            ->select();
    }

    /**
     * @title 根据模板id获取详情
     * @param $id
     * @return false|\PDOStatement|string|\think\Collection
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function getDetail($id)
    {
        $templateInfo = ModelExportTemplate::where('id', $id)->find();
        if (!$templateInfo) {
            throw new Exception('模板不存在');
        }
        $result = ModelExportTemplateDetail::where('export_template_id', $id)
            ->order('sort asc')
            ->field('id,show_field,field,sort')
            ->select();
        if($templateInfo['type']==1){
            $filed = [];
            $map = [];
            foreach ($result as $v){
                $filed[] = $v['field'];
                $map[$v['field']]  = $v;
            }
            if($filed){
                $GoodsImport = new GoodsImport();
                $tmp1 = $GoodsImport->getBaseField($filed);
                $result = [];
                foreach ($tmp1 as $v){
                    $row = $map[$v['key']];
                    $result[] = $row;
                }
                return $result;
            }

        }
        return $result;
    }

    private function checkParam($param,$user_id)
    {
        if (!isset($param['name']) || !$param['name']) {
            throw  new Exception('标题不能为空！');
        }
        if (!isset($param['type']) || !$param['type']) {
            throw  new Exception('模板场景不能为空！');
        }
        if (!isset($param['list']) || !$param['list']) {
            throw  new Exception('列表不能为空！');
        }
        $list = json_decode($param['list'], true);
        if (!is_array($list)) {
            throw  new Exception('列表不能为空！');
        }
        $id = $param['id'] ?? 0;
        if ($id) {
            $oldName = $this->getExportById($id);
            if ($oldName) {
                $nameData = $this->getName($param['name'],$user_id);
                if($nameData){
                    if($nameData['id']!=$id){
                        throw new Exception('该模板名称已存在');
                    }
                }
                if ($oldName['name'] != $param['name']) {
                    $id = 0;
                }
            }
        }
        $old = [];
        $aOldId = [];
        if ($id) {
            $old = $this->getOld($id);
            foreach ($old as $v) {
                $aOldId[] = $v['id'];
            }
        }
        $aOldField = array_keys($old);
        $postData = [];
        $addData = [];
        foreach ($list as $v) {
            if (!in_array($v['field'], $aOldField)) {
                $addData[] = $v;
            } else {
                $postData[$v['field']] = $v;
            }
        }
        $mdfField = array_intersect($aOldField, array_keys($postData));
        $delField = array_diff($aOldField, array_keys($postData));
        $mdfId = $delId = [];
        foreach ($mdfField as $field) {
            $mdfId[] = $old[$field]['id'];
        }
        foreach ($delField as $field) {
            $delId[] = $old[$field]['id'];
        }
        $postDataNew = [];
        foreach ($postData as $field => $v) {
            $tmpId = $old[$field]['id'];
            $postDataNew[$tmpId] = $v;
        }
        if (empty($addData) && empty($delId) && empty($mdfId) && $id) {
            throw new Exception('没什么好操作的');
        }
        return ['add_data' => $addData, 'del_id' => $delId, 'mdf_id' => $mdfId, 'post_data' => $postDataNew, 'id' => $id, 'type' => $param['type']];
    }

    private function getExportById($id = 0)
    {
        return ModelExportTemplate::where('id', $id)->find();

    }

    private function getName($name, $user_id)
    {
        return ModelExportTemplate::where('name', $name)->where('create_id', $user_id)->find();
    }

    private function getOld($id)
    {
        $result = [];
        $aSet = ModelExportTemplateDetail::where('export_template_id', $id)->select();
        foreach ($aSet as $v) {
            $row = [];
            $row['id'] = $v['id'];
            $row['show_field'] = $v['show_field'];
            $row['field'] = $v['field'];
            $row['sort'] = $v['sort'];
            $result[$row['field']] = $row;
        }
        return $result;
    }

    /**
     * @title 保存
     * @param $param
     * @param $user_id
     * @return array
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function TemplateSave($param, $user_id)
    {
        $checkData = $this->checkParam($param,$user_id);
        Db::startTrans();
        try {
            if (!$checkData['id']) {
                $data = [
                    'create_id' => $user_id,
                    'name' => trim($param['name']),
                    'type' => $checkData['type'],
                    'create_time' => time()
                ];
                $ValidateExportTemplate = new ValidateExportTemplate();
                $flag = $ValidateExportTemplate->check($data);
                if ($flag === false) {
                    throw new Exception($ValidateExportTemplate->getError());
                }
                $ModelExportTemplate = new ModelExportTemplate();
                $ModelExportTemplate
                    ->allowField(true)
                    ->isUpdate(false)
                    ->save($data);
                $checkData['id'] = $ModelExportTemplate->id;
            }
            if ($checkData['add_data']) {
                foreach ($checkData['add_data'] as $v) {
                    unset($v['id']);
                    $v['export_template_id'] = $checkData['id'];
                    $ValidateExportTemplateDetail = new ValidateExportTemplateDetail();
                    $flag = $ValidateExportTemplateDetail->check($v);
                    if ($flag === false) {
                        throw new Exception($ValidateExportTemplateDetail->getError());
                    }
                    $ModelExportTemplateDetail = new ModelExportTemplateDetail();
                    $ModelExportTemplateDetail
                        ->allowField(true)
                        ->isUpdate(false)
                        ->save($v);
                }
            }
            if ($checkData['mdf_id']) {
                $postData = $checkData['post_data'];
                foreach ($checkData['mdf_id'] as $id) {
                    $mdfData = $postData[$id];
                    ModelExportTemplateDetail::where('id', $id)->update($mdfData);
                }
            }
            if ($checkData['del_id']) {
                foreach ($checkData['del_id'] as $id) {
                    ModelExportTemplateDetail::where('id', $id)->delete();
                }
            }
            Db::commit();
            return ['message' => '保存成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    public function delete($id)
    {
        ModelExportTemplate::where('id', $id)->delete();
        return ['message' => '删除成功！'];
    }

}