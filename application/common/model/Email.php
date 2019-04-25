<?php


namespace app\common\model;

use app\common\cache\Cache;
use app\common\service\Encryption;
use think\Model;
use app\index\service\AccountCompanyService;
use app\common\model\Phone;
use app\index\service\DepartmentUserMapService;
use app\index\service\Department as DepartmentServer;

class Email extends Model
{
    public function getIsRegAttr($value, $data)
    {
        if ($data['account_count'] > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    public function getIsRegTxtAttr($value, $data)
    {
        if ($data['account_count'] > 0) {
            return '是';
        } else {
            return '否';
        }
    }

    public function getStatusTxtAttr($value, $data)
    {
        if ($data['status'] > 0) {
            return '启用';
        } else {
            return '停用';
        }
    }

    public function getIsReceiveTxtAttr($value, $data)
    {
        if ($data['is_receive'] > 0) {
            return '启用';
        } else {
            return '停用';
        }
    }

    public function getIsSendTxtAttr($value, $data)
    {
        if ($data['is_send'] > 0) {
            return '启用';
        } else {
            return '停用';
        }
    }

    public function getRegTxtAttr($value, $data)
    {
        if (!$data['reg_id']) {
            return '';
        }
        return Cache::store('user')->getOneUserRealname($data['reg_id']);
    }

    public function getRegTimeDateAttr($value, $data)
    {
        if ($data['reg_time']) {
            return date('Y-m-d', $data['reg_time']);
        }
        return '--';

    }

    public function getChannelIdsAttr($value, $data)
    {
        if (!$data['channel']) {
            return [];
        }
        $AccountCompanyService = new AccountCompanyService();
        return $AccountCompanyService->placeToChannel($data['channel']);
    }

    public function setChannelAttr($value)
    {

        $AccountCompanyService = new AccountCompanyService();
        $data = $AccountCompanyService->channelToplace($value);
        return $data;
    }

    public function setPasswordAttr($value)
    {
        $Encryption = new Encryption();
        return $Encryption->encrypt($value);
    }

    public function phone()
    {
        return $this->belongsTo(Phone::class, 'phone_id', 'id');
    }

    public function getRegDepartmentNameAttr($value, $data)
    {
        $departmentUserMapService = new DepartmentUserMapService();
        $department_ids = $departmentUserMapService->getDepartmentByUserId($data['reg_id']);
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

    public function getErrorTimeTxtAttr($value, $data)
    {
        if($data['error_msg']!=''){
            return date('Y-m-d H:i:s',$data['error_time']);
        }
        return '--';

    }
}