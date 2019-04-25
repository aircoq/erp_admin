<?php


namespace app\index\service;


use think\Db;
use think\Exception;
use app\index\validate\RegisterCompany as Validate;
use app\purchase\service\SupplierService;
use app\common\model\RegisterCompany as Model;
use app\index\service\AccountCompanyService;
use app\common\model\AccountCompany;

class RegisterCompany
{

    /**
     * @title 新增法人信息
     * @param $param
     * @param $userInfo
     * @return array
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function saveLegalInfo($param, $userInfo)
    {
        $field = [
            'corporation',
            'corporation_identification',
            'id_date_st',
            'id_date_nd',
            'corporation_id_front',
            'corporation_id_contrary',
            'corporation_address_zip',
            'ic_agent',
            'legal_remark',
            'creator_id',
            'create_time'
        ];
        $code = [
            'submit_audit',
            'save'
        ];
        if (!isset($param['submit_code']) || !$param['submit_code']) {
            throw new Exception('缺少submit_code');
        }
        if (!in_array($param['submit_code'], $code)) {
            throw new Exception('submit_code值不正确');
        }
        if (isset($param['corporation_id_front'])) {
            if (empty($param['corporation_id_front'])) {
                throw new Exception('身份证背面照是必填项');
            }

            $param['corporation_id_front'] = $this->uploadFiles($param['corporation_id_front']);
        }
        if (isset($param['corporation_id_contrary'])) {
            if (empty($param['corporation_id_contrary'])) {
                throw new Exception('身份证背面照是必填项');
            }
            $param['corporation_id_contrary'] = $this->uploadFiles($param['corporation_id_contrary']);
        }

        $validate = new Validate();
        $param['creator_id'] = $userInfo['user_id'];
        $param['create_time'] = time();

        $flag = $validate->scene('add_legalInfo')->check($param);
        if ($flag === false) {
            throw new Exception($validate->getError());
        }
        $data = $this->fill($field, $param);
        Db::startTrans();
        try {
            $RegisterCompanyLogs = new RegisterCompanyLogs();
            if ($param['submit_code'] == 'submit_audit') {
                $data['status'] = Model::STATUS_IC_AGENT_AUDITING;
                $RegisterCompanyLogs->submitLegalInfo($param['legal_remark']);
            } else {
                $data['status'] = Model::STATUS_RECEIVE_LEGAL;
                $RegisterCompanyLogs->saveLegalInfo($param['legal_remark']);
            }
            $Model = new Model();
            $data['charter_url'] = '';
            $Model->allowField(true)->isUpdate(false)->save($data);
            $RegisterCompanyLogs->save($Model->id, $userInfo['user_id'], $userInfo['realname']);
            Db::commit();
            return ['message' => '添加成功', 'data' => ['id' => $Model->id]];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }

    }

    /**
     * @title 更新数据
     * @param $old
     * @param $param
     * @param $userInfo
     * @param $scene
     * @param callable|null $callback
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function update($old, $param)
    {
        $Model = new Model();
        $Model->allowField(true)->isUpdate(true)->save($param, ['id' => $old['id']]);
    }

    /**
     * @title 更新法人信息
     * @param $id
     * @param $param
     * @param $userInfo
     * @return array
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function updateLegalInfo($id, $param, $userInfo)
    {
        $field = [
            'corporation',
            'corporation_identification',
            'id_date_st',
            'id_date_nd',
            'corporation_id_front',
            'corporation_id_contrary',
            'corporation_address_zip',
            'ic_agent',
            'legal_remark'
        ];
        $code = [
            'submit_audit',
            'save',
            'cancel',
            'audit_success',
            'audit_fail'
        ];
        $Model = new Model();
        $old = $Model->where('id', $id)->find();
        if (!$old) {
            throw new Exception('该记录不存在，无法保存');
        }
        if (!isset($param['submit_code']) || !$param['submit_code']) {
            throw new Exception('缺少submit_code');
        }
        if (!in_array($param['submit_code'], $code)) {
            throw new Exception('submit_code值不正确');
        }
        if (isset($param['corporation_id_front']) && $param['corporation_id_front']) {
            $param['corporation_id_front'] = $this->uploadFiles($param['corporation_id_front']);
        } else {
            unset($param['corporation_id_front']);
        }
        if (isset($param['corporation_id_contrary']) && $param['corporation_id_contrary']) {
            $param['corporation_id_contrary'] = $this->uploadFiles($param['corporation_id_contrary']);
        } else {
            unset($param['corporation_id_contrary']);
        }
        $data = $this->fill($field, $param);
        $data['updater_id'] = $userInfo['user_id'];
        $data['update_time'] = time();
        $RegisterCompanyLogs = new RegisterCompanyLogs();
        $scene = 'update_legal';
        $companyData = $Model->data($data, true);
        $remark = isset($param['legal_remark']) ? $param['legal_remark'] : $old['legal_remark'];
        switch ($param['submit_code']) {
            case 'submit_audit':
                if (!in_array($old['status'], [Model::STATUS_RECEIVE_LEGAL, Model::STATUS_IC_AGENT_AUDIT_FAIL, Model::STATUS_WAIT_LICENCE_FAIL])) {
                    throw new Exception('当前状态为' . $old->status_txt . ",不允许提交工商代理审核");
                }
                $data['status'] = Model::STATUS_IC_AGENT_AUDITING;
                $RegisterCompanyLogs->submitLegalInfo($remark)->mdf('法人资料', $old, $companyData);
                break;
            case 'save':
                $RegisterCompanyLogs->saveLegalInfo($remark);
                break;
            case 'cancel':
                if (!in_array($old['status'], [Model::STATUS_IC_AGENT_AUDIT_FAIL, Model::STATUS_WAIT_LICENCE_FAIL])) {
                    throw new Exception('当前状态为' . $old->status_txt . ",不允许作废");
                }
                $data['status'] = Model::STATUS_INVALID;
                $RegisterCompanyLogs->invalid($param['reason']);
                break;
            case 'audit_success':
                if (!in_array($old['status'], [Model::STATUS_IC_AGENT_AUDITING])) {
                    throw new Exception('当前状态为' . $old->status_txt . ",不允许审核");
                }
                $data['status'] = Model::STATUS_WAIT_LICENCE;
                $RegisterCompanyLogs->agree_ic_agent();
                break;
            case 'audit_fail':
                if (!in_array($old['status'], [Model::STATUS_IC_AGENT_AUDITING])) {
                    throw new Exception('当前状态为' . $old->status_txt . ",不允许审核");
                }
                $data['status'] = Model::STATUS_IC_AGENT_AUDIT_FAIL;
                if (empty($param['reason'])) {
                    throw new Exception('不通过原因不能为空');
                }
                $RegisterCompanyLogs->disagree_ic_agent($param['reason']);
                break;
        }
        if ($scene) {
            $validate = new Validate();
            $flag = $validate->scene($scene)->check($data);
            if ($flag === false) {
                throw new Exception($validate->getError());
            }
        }
        Db::startTrans();
        try {
            $Model->allowField(true)->isUpdate(true)->save($data, ['id' => $id]);
            $RegisterCompanyLogs->save($id, $userInfo['user_id'], $userInfo['realname']);
            Db::commit();
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
        return ['message' => '保存成功'];
    }

    public function saveCompanyInfo($id, $param, $userInfo)
    {
        $field = [
            'type',
            'source',
            'company',
            'company_registration_number',
            'business_term_st',
            'business_term_nd',
            'company_time',
            'company_address_zip'
        ];
        $code = [
            'audit_success',
            'audit_fail'
        ];
        $Model = new Model();
        $old = $Model->where('id', $id)->find();
        if (!$old) {
            throw new Exception('该记录不存在，无法保存');
        }
        if ($old['status'] != Model::STATUS_WAIT_LICENCE) {
            throw new Exception('当前状态为' . $old['status_txt'] . ",不能保存");
        }
        if (!isset($param['submit_code']) || !$param['submit_code']) {
            throw new Exception('缺少submit_code');
        }
        if (!in_array($param['submit_code'], $code)) {
            throw new Exception('submit_code值不正确');
        }

        $data = $this->fill($field, $param);
        $data['updater_id'] = $userInfo['user_id'];
        $data['update_time'] = time();
        $scene = '';
        $RegisterCompanyLogs = new RegisterCompanyLogs();
        if ($param['submit_code'] == 'audit_success') {
            $scene = 'add_company';
            $data['status'] = Model::STATUS_RECEIVE_LICENCE;
            $count = AccountCompany::where('company',$data['company'])->count();
            if($count){
                throw new Exception('该公司【'.$data['company'].'】名字已存在');
            }
            $count = Model::where('company',$data['company'])->where('id','<>',$id)->count();
            if($count){
                throw new Exception('该公司【'.$data['company'].'】名字已存在');
            }
            $RegisterCompanyLogs->saveCompanyInfo();
        } else {
            if (empty($param['reason'])) {
                throw new Exception('不通过原因不能为空');
            }
            $data['status'] = Model::STATUS_WAIT_LICENCE_FAIL;
            $RegisterCompanyLogs->disagree_licence($param['reason']);
        }
        if ($scene) {
            $validate = new Validate();
            $flag = $validate->scene($scene)->check($data);
            if ($flag === false) {
                throw new Exception($validate->getError());
            }
        }
        Db::startTrans();
        try {
            $Model->allowField(true)->isUpdate(true)->save($data, ['id' => $id]);
            $RegisterCompanyLogs->save($id, $userInfo['user_id'], $userInfo['realname']);
            Db::commit();
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }

        return ['message' => '保存成功'];
    }

    /**
     * @title 查询详情
     * @param $id
     * @param string $field
     * @return array
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function read($id, $field = '*')
    {
        $Model = new Model();
        $info = $Model->field($field)->where('id', $id)->find();
        if (!$info) {
            throw new Exception('该记录不存在');
        }
        return $this->row($info);
    }

    public function getLegalInfo($id)
    {
        $field = 'id,corporation,corporation_identification,id_date_st,id_date_nd,corporation_id_front,
        corporation_id_contrary,corporation_address_zip,ic_agent,legal_remark,has_seal';
        $info = Db::name('register_company')->field($field)->where('id', $id)->find();
        if (!$info) {
            throw new Exception('该记录不存在');
        }
        $info['url_prefix'] = (new AccountCompanyService)->getUrlPrefix();
        return $info;
    }

    private function row($row)
    {
        $result = [];
        $v = $row->toArray();
        isset($v['id']) && $result['id'] = $row['id'];
        isset($v['corporation']) && $result['corporation'] = $row['corporation'];
        isset($v['id_date_st']) && $result['id_date_st'] = $row['id_date_st'];
        isset($v['id_date_st']) && $result['id_date_st_txt'] = $row['id_date_st_txt'];
        isset($v['id_date_nd']) && $result['id_date_nd'] = $row['id_date_nd'];
        isset($v['id_date_st']) && $result['id_date_nd_txt'] = $row['id_date_nd_txt'];
        isset($v['corporation_id_front']) && $result['corporation_id_front'] = $row['corporation_id_front'];
        isset($v['corporation_id_contrary']) && $result['corporation_id_contrary'] = $row['corporation_id_contrary'];
        isset($v['corporation_address_zip']) && $result['corporation_address_zip'] = $row['corporation_address_zip'];
        isset($v['ic_agent']) && $result['ic_agent'] = $row['ic_agent'];
        isset($v['legal_remark']) && $result['legal_remark'] = $row['legal_remark'];
        isset($v['type']) && $result['type'] = $row['type'];
        isset($v['source']) && $result['source'] = $row['source'];
        isset($v['company']) && $result['company'] = $row['company'];
        isset($v['company_registration_number']) && $result['company_registration_number'] = $row['company_registration_number'];
        isset($v['business_term_st']) && $result['business_term_st'] = $row['business_term_st'];
        isset($v['business_term_nd']) && $result['business_term_nd'] = $row['business_term_nd'];
        isset($v['company_time']) && $result['company_time'] = $row['company_time'];
        isset($v['company_address_zip']) && $result['company_address_zip'] = $row['company_address_zip'];
        isset($v['charter_url']) && $result['charter_url'] = $row['charter_url'];
        isset($v['corporation_identification']) && $result['corporation_identification'] = $row['corporation_identification'];
        isset($v['status']) && $result['status'] = $row['status'];
        isset($v['status']) && $result['status_txt'] = $row['status_txt'];
        isset($v['creator_id']) && $result['creator_id'] = $row['creator_id'];
        isset($v['creator_id']) && $result['creator_txt'] = $row['creator_txt'];
        isset($v['create_time']) && $result['create_time'] = $row['create_time'];
        isset($v['create_time']) && $result['create_time_txt'] = $row['create_time_txt'];
        isset($v['corporate_settlement']) && $result['corporate_settlement'] = $row['corporate_settlement'];
        isset($v['agency_settlement']) && $result['agency_settlement'] = $row['agency_settlement'];
        isset($v['has_seal']) && $result['has_seal'] = $row['has_seal'];
        isset($v['settlement_remark']) && $result['settlement_remark'] = $row['settlement_remark'];
        isset($v['creator_id']) && $result['department_name'] = $row['department_name'];
        return $result;
    }

    /**
     * @title 状态列表
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getStatus()
    {
        $status = Model::STATUS;
        $result = [];
        foreach ($status as $id => $info) {
            $row = [];
            $row['value'] = $id;
            $row['label'] = $info['name'];
            $result[] = $row;
        }
        return $result;
    }


    public function fill($field, $data)
    {
        $result = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $field)) {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    /**
     * @title 上传文件
     * @param $base64
     * @return mixed
     * @author starzhan <397041849@qq.com>
     */
    public function uploadFile($base64)
    {
        $SupplierService = new SupplierService();
        $fileResult = $SupplierService->base64DecImg($base64, 'upload/baseaccount/' . date('Y-m-d'), time() . rand(0, 100));
        return $fileResult['filePath'];
    }

    /**
     * @title 上传文件S
     * @param $base64
     * @return array
     * @throws Exception
     */
    public function uploadFiles($base64)
    {
        $fileResult = [];
        $suplierService = new SupplierService();
        $accountApplyService = new AccountApplyService();

        $charterArr = json_decode($base64, true);
        if (!is_array($charterArr)) {
            throw new Exception('参数错误');
        }

        foreach ($charterArr as $key => $item) {
            if (!isset($item['path']) || !isset($item['is_add'])) {
                throw new Exception('参数错误');
            }

            if (strpos($item['path'], 'data:image') !== false) {
                $imagesArr = $suplierService->base64DecImg($item['path'], 'upload/baseaccount/' . date('Y-m-d'), time() . rand(0, 100));
                $imagesArr['fileName'] = $item['name'];
                $fileResult[] = $imagesArr;

            } else if (strpos($item['path'], 'data:application/pdf') !== false) {
                $imagesArr = $accountApplyService->base64DecPdf($item['path'], 'upload/baseaccount/' . date('Y-m-d'), time() . rand(0, 100));
                $imagesArr['fileName'] = $item['name'];
                $fileResult[] = $imagesArr;

            } else {
                $imagesArr['fileName'] = $item['name'];
                $imagesArr['filePath'] = $item['path'];
                $fileResult[] = $imagesArr;
            }
        }
        return json_encode($fileResult);
    }


    /**
     * @title 获取公司信息
     * @param $id
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getCompanyInfo($id)
    {
        $field = 'id,type,source,company,company_registration_number,
        business_term_st,business_term_nd,company_time,company_address_zip';
        return $this->read($id, $field);
    }

    public function getCharter($id)
    {
        $field = 'id,charter_url';
        $info = Db::name('register_company')->field($field)->where('id', $id)->find();
        if (!$info) {
            throw new Exception('该记录不存在');
        }
        $info['url_prefix'] = (new AccountCompanyService)->getUrlPrefix();
        return $info;
    }

    public function getSettlement($id)
    {
        $field = 'id,corporate_settlement,agency_settlement,has_seal,settlement_remark';
        return $this->read($id, $field);
    }

    /**
     * @title 上传执照
     * @param $id
     * @param $param
     * @param $userInfo
     * @return array
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function saveCharter($id, $param, $userInfo)
    {
        $field = [
            'charter_url',
        ];
        $Model = new Model();
        $old = $Model->where('id', $id)->find();
        if (!$old) {
            throw new Exception('该记录不存在，无法保存');
        }
        if ($old['status'] != Model::STATUS_RECEIVE_LICENCE) {
            throw new Exception('当前状态为' . $old['status_txt'] . ",不能保存");
        }

        $data = $this->fill($field, $param);
        $data['updater_id'] = $userInfo['user_id'];
        $data['update_time'] = time();

        if (isset($param['charter_url']) && $param['charter_url']) {
            $data['charter_url'] = $this->uploadFiles($param['charter_url']);
        }

        $validate = new Validate();
        $flag = $validate->scene('saveCharter')->check($data);
        if ($flag === false) {
            throw new Exception($validate->getError());
        }
        $RegisterCompanyLogs = new RegisterCompanyLogs();
        $data['status'] = Model::STATUS_WAIT_SETTLE;
        
        $RegisterCompanyLogs->pullCompanyData();
        Db::startTrans();
        try {
            $Model->allowField(true)->isUpdate(true)->save($data, ['id' => $id]);
            $RegisterCompanyLogs->save($id, $userInfo['user_id'], $userInfo['realname']);
            $this->pullCompanyData($id);
            Db::commit();
            return ['message' => '保存成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    /**
     * @title 推送到公司资料库
     * @param $id
     * @author starzhan <397041849@qq.com>
     * @throws Exception
     */
    public function pullCompanyData($id)
    {
        $Model = new Model();
        $info = $Model->where('id', $id)->find();
        if (!$info) {
            throw new Exception('该记录不存在');
        }
        $row = $info->getData();
        $result = [];
        $result['company'] = $row['company'];
        $count = AccountCompany::where('company',$result['company'])->count();
        if($count){
            throw new Exception('同步失败，该公司【'.$result['company'].'】名字已存在');
        }
        $result['company_registration_number'] = $row['company_registration_number'];
        $result['corporation'] = $row['corporation'];
        $result['corporation_identification'] = $row['corporation_identification'];
        $result['id_date_st'] = $row['id_date_st']?date('Y-m-d',$row['id_date_st']):'';
        $result['id_date_nd'] = $row['id_date_nd']?date('Y-m-d',$row['id_date_nd']):'';
        $result['corporation_address_zip'] = $row['corporation_address_zip'];
        $result['type'] = $row['type'];
        $result['source'] = $row['source'];
        $result['business_term_st'] = $row['business_term_st']?date('Y-m-d',$row['business_term_st']):'';
        $result['business_term_nd'] = $row['business_term_nd']?date('Y-m-d',$row['business_term_nd']):'';
        $result['company_time'] = $row['company_time'];
        $result['company_address_zip'] = $row['company_address_zip'];
        $result['corporate_settlement'] = $row['corporate_settlement'];
        $result['agency_settlement'] = $row['agency_settlement'];
        $result['has_seal'] = $row['has_seal'];
        $result['settlement_remark'] = $row['settlement_remark'];
        $result['creator_id'] = $row['creator_id'];
        $result['create_time'] = $row['create_time'];
        $result['update_time'] = $row['update_time'];
        $images = [
            'charter_url' => [],
            'corporation_id_front' => json_decode($row['corporation_id_front']),
            'corporation_id_contrary' => json_decode($row['corporation_id_contrary']),
            'open_licence' => [],
        ];
        $result['images'] = json_encode($images);
        $result['vat_data'] = '[]';
        $result['vat_attachment'] = '[]';
        $AccountCompany = new AccountCompany();
        $AccountCompany->isUpdate(false)->allowField(true)->save($result);
    }

    public function saveSettlement($id, $param, $userInfo)
    {
        $field = [
            'corporate_settlement',
            'agency_settlement',
            'has_seal',
            'settlement_remark'
        ];
        $code = [
            'audit_submit',
            'audit_success',
            'save'
        ];
        $Model = new Model();
        $old = $Model->where('id', $id)->find();
        if (!$old) {
            throw new Exception('该记录不存在，无法保存');
        }
        if (!isset($param['submit_code']) || !$param['submit_code']) {
            throw new Exception('缺少submit_code');
        }
        if (!in_array($param['submit_code'], $code)) {
            throw new Exception('submit_code值不正确');
        }

        $data = $this->fill($field, $param);
        $data['updater_id'] = $userInfo['user_id'];
        $data['update_time'] = time();
        $RegisterCompanyLogs = new RegisterCompanyLogs();
        $remark = empty($data['settlement_remark']) ? $old['settlement_remark'] : $data['settlement_remark'];
        switch ($param['submit_code']) {
            case 'audit_submit':
                if ($old['status'] != Model::STATUS_WAIT_SETTLE) {
                    throw new Exception('当前状态为' . $old['status_txt'] . ",不能待领公章");
                }
                $validate = new Validate();
                $flag = $validate->scene('wait_settle')->check($data);
                if ($flag === false) {
                    throw new Exception($validate->getError());
                }
                if ($data['has_seal'] == 1) {
                    $data['status'] = Model::STATUS_RECEIVE_SEAL;
                    $RegisterCompanyLogs->waitSettle($remark);
                } else {
                    $data['status'] = Model::STATUS_FINISH;
                    $RegisterCompanyLogs->finish($remark);
                }
                break;
            case 'audit_success':
                if ($old['status'] != Model::STATUS_RECEIVE_SEAL) {
                    throw new Exception('当前状态为' . $old['status_txt'] . ",不能保存");
                }
                $data['status'] = Model::STATUS_FINISH;
                $RegisterCompanyLogs->finish($remark);
                break;
            case 'save':
//                if ($old['status'] != Model::STATUS_WAIT_SETTLE) {
//                    throw new Exception('当前状态为' . $old['status_txt'] . ",不能保存");
//                }
                $RegisterCompanyLogs->saveSettlement($remark);
                break;
        }
        Db::startTrans();
        try {
            $Model->allowField(true)->isUpdate(true)->save($data, ['id' => $id]);
            $RegisterCompanyLogs->save($id, $userInfo['user_id'], $userInfo['realname']);
            Db::commit();
            return ['message' => '保存成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    public function logs($id)
    {
        $result = (new RegisterCompanyLogs())->getLog($id);
        foreach ($result as &$v) {
            $v['department_name'] = $v->department_name;
        }
        return $result;
    }

    private function getWhere($param)
    {
        $Model = new Model();
        if (isset($param['status']) && $param['status'] !== '') {
            $Model = $Model->where('status', intval($param['status']));
        }
        if (isset($param['corporation']) && $param['corporation']) {
            $Model = $Model->where('corporation', 'like', "%{$param['corporation']}%");
        }
        if (isset($param['corporation_identification']) && $param['corporation_identification']) {
            $Model = $Model->where('corporation_identification', 'like', "%" . $param['corporation_identification'] . "%");
        }
        if (isset($param['create_time_st']) && $param['create_time_st']) {
            $str_time = strtotime($param['create_time_st']);
            $Model = $Model->where('create_time', '>=', $str_time);
        }
        if (isset($param['create_time_nd']) && $param['create_time_nd']) {
            $nd_time = strtotime($param['create_time_nd'] . " 23:59:59");
            $Model = $Model->where('create_time', '<=', $nd_time);
        }
        return $Model;
    }

    private function getOrder()
    {
        return 'id desc';
    }

    public function index($page, $pageSize, $param)
    {
        $result = ['data' => []];
        $result['page'] = $page;
        $result['pageSize'] = $pageSize;
        $result['count'] = $this->getWhere($param)->count();
        if ($result['count'] == 0) {
            return $result;
        }
        $o = $this->getWhere($param);
        $sort = $this->getOrder();
        $ret = $o->page($page, $pageSize)
            ->field("id,corporation,corporation_identification,corporation_identification,status,creator_id,create_time,ic_agent")
            ->order($sort)->select();
        if ($ret) {
            $result['data'] = $this->lists($ret);
        }
        return $result;
    }

    public function lists($ret)
    {
        $result = [];
        foreach ($ret as $v) {
            $row = $this->row($v);
            $result[] = $row;
        }
        return $result;
    }
}