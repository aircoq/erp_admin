<?php


namespace app\common\model;

use app\common\cache\Cache;
use think\Model;
use app\common\traits\ConfigCommon;
use app\index\service\DepartmentUserMapService;
use app\index\service\Department as DepartmentServer;

class RegisterCompany extends Model
{
    use ConfigCommon;
    const STATUS_RECEIVE_LEGAL = 2;//接受法人资料
    const STATUS_INVALID = 1;//作废
    const STATUS_IC_AGENT_AUDITING = 3;//工商代理审核中
    const STATUS_IC_AGENT_AUDIT_FAIL = 4;//工商代理审核未通过
    const STATUS_WAIT_LICENCE = 5;//待批执照
    const STATUS_WAIT_LICENCE_FAIL = 6;//待批执照未通过
    const STATUS_RECEIVE_LICENCE = 7;//待领执照
    const STATUS_WAIT_SETTLE = 8;//待结账
    const STATUS_RECEIVE_SEAL = 9;//待领公章
    const STATUS_FINISH = 10;//待领公章

    const STATUS = [
        self::STATUS_INVALID => ['name' => '已作废'],
        self::STATUS_RECEIVE_LEGAL => ['name' => '接收法人资料'],
        self::STATUS_IC_AGENT_AUDITING => ['name' => '工商代理审核中'],
        self::STATUS_IC_AGENT_AUDIT_FAIL => ['name' => '工商代理审核未通过'],
        self::STATUS_WAIT_LICENCE => ['name' => '待批执照'],
        self::STATUS_WAIT_LICENCE_FAIL => ['name' => '待批执照未通过'],
        self::STATUS_RECEIVE_LICENCE => ['name' => '待领执照'],
        self::STATUS_WAIT_SETTLE => ['name' => '待结账'],
        self::STATUS_RECEIVE_SEAL => ['name' => '待领公章'],
        self::STATUS_FINISH => ['name' => '注册完成']
    ];

    public function getIdDateStTxtAttr($value, $data)
    {
        return date('Y-m-d', $data['id_date_st']);
    }

    public function getIdDateNdTxtAttr($value, $data)
    {
        return date('Y-m-d', $data['id_date_nd']);
    }

    public function setIdDateStAttr($value)
    {
        return strtotime($value);
    }

    public function setIdDateNdAttr($value)
    {
        return strtotime($value);
    }

    public function setBusinessTermStAttr($value)
    {
        return strtotime($value);
    }

    public function setBusinessTermNdAttr($value)
    {
        return strtotime($value);
    }

    public function setCompanyTimeAttr($value)
    {
        return strtotime($value);
    }


    public function getStatusTxtAttr($value, $data)
    {
        $status = $data['status'];
        return isset(self::STATUS[$status]) ? self::STATUS[$status]['name'] : '';
    }

    public function getCreatorTxtAttr($value, $data)
    {
        return Cache::store('user')->getOneUserRealname($data['creator_id']);
    }

    public function getCreateTimeTxtAttr($value, $data)
    {
        if ($data['create_time']) {
            return date('Y-m-d H:i:s', $data['create_time']);
        }
        return '';
    }

    private function getApiIpCfg()
    {
        $this->setConfigIdentification('api_ip');
        return $this->getConfigData();
    }

    public function getCorporationIdFrontAttr($value, $data)
    {
        $ip = $this->getApiIpCfg();
        return $ip . "/" . $data['corporation_id_front'];
    }

    public function getCorporationIdContraryAttr($value, $data)
    {
        $ip = $this->getApiIpCfg();
        return $ip . "/" . $data['corporation_id_contrary'];
    }

    public function getCharterUrlAttr($value, $data)
    {
        $ip = $this->getApiIpCfg();
        return $ip . "/" . $data['charter_url'];
    }

    public function getDepartmentNameAttr($value, $data)
    {
        $departmentUserMapService = new DepartmentUserMapService();
        $department_ids = $departmentUserMapService->getDepartmentByUserId($data['creator_id']);
        $departmentInfo = '';
        $departmentServer = new DepartmentServer();
        foreach ($department_ids as $d => $department) {
            if (!empty($department)) {
                $departmentInfo .= $departmentServer->getDepartmentNames($department) . '   ,   ';
            }
        }
        $departmentInfo = rtrim($departmentInfo, '   ,   ');
        return $departmentInfo;
    }

}
