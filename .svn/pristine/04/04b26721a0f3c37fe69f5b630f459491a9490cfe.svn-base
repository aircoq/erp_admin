<?php

namespace app\index\controller;

use app\common\controller\Base;
use app\index\service\AccountCompanyService;
use think\Exception;
use think\Request;

/**
 * @module 基础设置
 * @title 平台公司资料
 * @author libaimin
 * @url account-company
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/11/21
 * Time: 14:46
 */
class AccountCompany extends Base
{

    /**
     * @var AccountCompanyService
     */
    protected $accountCompanyServer;

    protected function init()
    {
        if (is_null($this->accountCompanyServer)) {
            $this->accountCompanyServer = new AccountCompanyService();
        }
    }

    /**
     * @title 平台公司资料列表
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     */
    public function index(Request $request)
    {
        $result = $this->accountCompanyServer->lists($request);
        return json($result, 200);
    }

    /**
     * @title 显示指定的资源
     * @param $id
     * @return \think\response\Json
     * @throws Exception
     */
    public function read($id)
    {
        $result = $this->accountCompanyServer->read($id);
        return json($result, 200);
    }


    /**
     * @title 获取平台公司资料信息
     * @param $id
     * @return \think\response\Json
     * @throws Exception
     */
    public function edit($id)
    {
        $result = $this->accountCompanyServer->read($id);
        return json($result, 200);
    }

    /**
     * @title 保存平台公司资料信息
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     */
    public function save(Request $request)
    {
        $data['company'] = trim($request->post('company', ''));
        $data['company_type'] = trim($request->post('company_type', ''));
        $data['company_registration_number'] = trim($request->post('company_registration_number', ''));
        $data['corporation'] = $request->post('corporation', '');
        $data['company_time'] = $request->post('company_time', 0);
        $data['company_address_zip'] = $request->post('company_address_zip', '');
        $data['corporation_address_zip'] = $request->post('corporation_address_zip', '');
        $data['charter_url'] = $request->post('charter_url', '');
        $data['corporation_id_front'] = $request->post('corporation_id_front', '');
        $data['corporation_id_contrary'] = $request->post('corporation_id_contrary', '');
        $data['status'] = $request->post('status', 0);
        $data['channel'] = $request->post('channel', 0);
        $data['corporation_id'] = $request->post('corporation_id', '');
        $data['phone'] = $request->post('phone', '');
        $data['type'] = $request->post('type', 0);
        $data['source'] = $request->post('source', 0);
        $data['open_bank_account'] = $request->post('open_bank_account', '');
        $data['open_date'] = $request->post('open_date', '');
        $data['open_bank'] = $request->post('open_bank', '');
        $data['open_licence'] = $request->post('open_licence', '');
        $data['id_date_nd'] = $request->post('id_date_nd', '');
        $data['id_date_st'] = $request->post('id_date_st', '');
        $data['business_term_st'] = $request->post('business_term_st', '');
        $data['business_term_nd'] = $request->post('business_term_nd', '');
        $data['corporation_identification'] = $request->post('corporation_identification', '');

        $validateAccountCompanyServer = validate('AccountCompany');
        if (!$validateAccountCompanyServer->check($data)) {
            return json(['message' => $validateAccountCompanyServer->getError()], 400);
        }
        $id = $this->accountCompanyServer->save($data);
        return json(['message' => '新增成功', 'data' => $id], 200);
    }

    /**
     * @title 更新平台公司资料信息[公司资料]
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     * @throws Exception
     */
    public function update(Request $request, $id)
    {
        $data['company'] = trim($request->put('company', ''));
        $data['company_type'] = trim($request->put('company_type', ''));
        $data['company_registration_number'] = trim($request->put('company_registration_number', ''));
        $data['corporation'] = $request->put('corporation', '');
        $data['company_time'] = $request->put('company_time', 0);
        $data['company_address_zip'] = $request->put('company_address_zip', '');
        $data['corporation_address_zip'] = $request->put('corporation_address_zip', '');
        $data['charter_url'] = $request->put('charter_url', '');
        $data['corporation_id_front'] = $request->put('corporation_id_front', '');
        $data['corporation_id_contrary'] = $request->put('corporation_id_contrary', '');
        $data['status'] = $request->put('status', 0);
        $data['channel'] = $request->put('channel', 0);
        $data['corporation_id'] = $request->put('corporation_id', '');
        $data['phone'] = $request->put('phone', '');
        $data['type'] = $request->put('type', 0);
        $data['source'] = $request->put('source', 0);
        $data['open_bank_account'] = $request->put('open_bank_account', '');
        $data['open_date'] = $request->put('open_date', '');
        $data['open_bank'] = $request->put('open_bank', '');
        $data['open_licence'] = $request->put('open_licence', '');
        $data['id_date_nd'] = $request->put('id_date_nd', '');
        $data['id_date_st'] = $request->put('id_date_st', '');
        $data['business_term_st'] = $request->put('business_term_st', '');
        $data['business_term_nd'] = $request->put('business_term_nd', '');
        $data['corporation_identification'] = $request->put('corporation_identification', '');
        $data['images'] = $request->put('images', '');
        unset($data['id']);
        $dataInfo = $this->accountCompanyServer->update($id, $data);
        return json(['message' => '修改成功', 'data' => $dataInfo], 200);
    }


    /**
     * @title 更新平台公司资料信息[账号信息]
     * @url :id/account
     * @method PUT
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     * @throws Exception
     */
    public function updateAccount(Request $request, $id)
    {
        $data['collection_account'] = $request->put('collection_account', '');
        $data['credit_card'] = $request->put('credit_card', '');
        unset($data['id']);
        $dataInfo = $this->accountCompanyServer->update($id, $data);
        return json(['message' => '修改成功', 'data' => $dataInfo], 200);
    }


    /**
     * @title 更新平台公司资料信息[VAT]
     * @url :id/vat
     * @method PUT
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     */
    public function updateVat(Request $request, $id)
    {
        $param = $request->param();
        try {
            if (empty($param['vat_data']) && empty($param['vat_attachment'])) {
                throw new Exception('税率和附件不能同时为空');
            }
            unset($param['id']);
            $dataInfo = $this->accountCompanyServer->updateVat($id, $param);
            return json(['message' => '修改成功', 'data' => $dataInfo], 200);
        } catch (Exception $ex) {
            $err = [];
            $err['file'] = $ex->getFile();
            $err['line'] = $ex->getLine();
            $err['message'] = $ex->getMessage();
            return json($err, 400);
        }
    }

    /**
     * @title 删除
     * @url :id
     * @method delete
     */
    public function delete($id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $dataInfo = $this->accountCompanyServer->delete($id);
        return json(['message' => '删除成功', 'data' => $dataInfo], 200);
    }

    /**
     * @title 日志
     * @url :id/log
     * @method get
     */
    public function log($id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $dataInfo = $this->accountCompanyServer->getLog($id);
        return json(['message' => '删除成功', 'data' => $dataInfo], 200);
    }

    /**
     * @title 拉取公司名称列表
     * @url company
     * @method get
     */
    public function company()
    {
        $request = Request::instance();
        $params = $request->param();
        $dataInfo = $this->accountCompanyServer->getCompany($params);
        return json(['message' => '拉取成功', 'data' => $dataInfo], 200);
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
        $dataInfo = $this->accountCompanyServer->update($id, $data);
        return json(['message' => '修改成功', 'data' => $dataInfo], 200);
    }

    /**
     * @title 公司类型
     * @url type
     * @method get
     */
    public function type()
    {
        $dataInfo = $this->accountCompanyServer->getType();
        return json(['message' => '拉取成功', 'data' => $dataInfo], 200);
    }

    /**
     * @title 资料来源
     * @url source
     * @method get
     */
    public function source()
    {
        $dataInfo = $this->accountCompanyServer->getSource();
        return json(['message' => '拉取成功', 'data' => $dataInfo], 200);
    }

    /**
     * @title 获取可用公司
     * @noauth
     * @method get
     * @url can-use
     * @author starzhan <397041849@qq.com>
     */
    public function getCanUse()
    {
        $param = $this->request->param();
        try {
            $AccountCompanyService = new AccountCompanyService();
            $page_size = $param['pageSize'] ?? 50;
            $page = $param['page'] ?? 1;
            $result = $AccountCompanyService->getCanUse($page, $page_size, $param);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage()
            ];
            return json($err, 200);
        }
    }

}