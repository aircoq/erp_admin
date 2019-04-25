<?php

namespace app\index\controller;

use app\common\controller\Base;
use app\common\service\Common;
use app\index\service\SoftwareService;
use app\index\service\SoftwareVersion;
use think\Exception;
use think\Request;
use app\common\service\Common as CommonService;
use app\common\model\AccountApply as AccountApplyModel;

/**
 * @module 基础设置
 * @title 软件管理
 * @author libaimin
 * @url software
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/12/8
 * Time: 17:45
 */
class Software extends Base
{
    protected $softwareService;

    protected function init()
    {
        if (is_null($this->softwareService)) {
            $this->softwareService = new SoftwareService();
        }
    }

    /**
     * @title 显示资源列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $params = $request->param();
        $accountList = $this->softwareService->lists($params, $page, $pageSize);
        return json($accountList);
    }

    /**
     * @title 保存新建的资源
     * @param  \think\Request $request
     * @return \think\Response
     * @apiRelate app\index\controller\User::staffs
     */
    public function save(Request $request)
    {
        $userInfo = Common::getUserInfo();
        $data['version'] = $request->post('version', '');
        $data['software_type'] = $request->post('software_type', 0);
        $data['status'] = $request->post('status', 0);

        $data['remark'] = $request->post('remark', '');
        $data['md5'] = $request->post('md5', '');
        $validateAccount = validate('Software');
        if (!$validateAccount->check($data)) {
            return json(['message' => $validateAccount->getError()], 400);
        }
        $data['create_time'] = time();
        $data['creator_id'] = $userInfo['user_id'];

        $params = $request->post('doc', '');
        if ($params) {
            $params = json_decode($params, true);
            $params = $params[0];
            $fileInfo = explode('.',$params['name']);
            $fileNoExtName = $fileInfo[0];
            $ext = end($fileInfo);
            $filename = 'install/' . $fileNoExtName."(".date('ymdhis').rand(0,99).").".$ext;
            $start = strpos($params['file'], ',');
            $content = substr($params['file'], $start + 1);
            file_put_contents($filename, base64_decode(str_replace(" ", "+", $content)));
            $data['upgrade_address'] = $filename;
        } else {
            json(['message' => '请选择文件'], 400);
        }
        $result = $this->softwareService->save($data);
        return json(['message' => '新增成功', 'data' => $result]);
    }


    /**
     * @title 更改账号状态
     * @url batch/:type
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function batch(Request $request)
    {
        $ids = $request->post('ids', '');


        if (empty($ids)) {
            return json(['message' => '参数值不能为空']);
        }
        $params = $request->param();
        $type = $params['type'];
        $data['type_msg'] = $request->post('type_msg', '');
        $ids = json_decode($ids, true);
        $this->softwareService->status($ids, $data, $type);
        return json(['message' => '更改成功']);
    }

    /**
     * @title 修改状态
     * @url :id/status
     * @method post
     */
    public function changeStatus(Request $request, $id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $data['status'] = $request->post('status', 0);
        $dataInfo = $this->softwareService->update($id, $data);
        return json(['message' => '修改成功', 'data' => $dataInfo], 200);
    }


    /**
     * @title 获取状态
     * @url type
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function type()
    {
        $result = $this->softwareService->typeInfo();
        return json($result);
    }

    /**
     * @title 删除软件
     * @param $id
     * @return \think\response\Json
     */
    public function delete($id)
    {
        $this->softwareService->delete($id);
        return json(['message' => '删除成功'], 200);
    }

    /**
     * @title 发布软件版本
     * @method post
     * @url :id/version
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function sendVersion($id)
    {
        $param = $this->request->param();
        try {
            $softwareVersion = new SoftwareVersion();
            $result = $softwareVersion->sendVersion($id, $param);
            return json($result, 200);
        } catch (Exception $ex) {
            return json([
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage()
            ], 400);
        }
    }

    /**
     * @title 历史版本
     * @method get
     * @url :id/version
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function getVersion($id)
    {
        $softwareVersion = new SoftwareVersion();
        $result = $softwareVersion->getVersion($id);
        return json($result,200);
    }


}