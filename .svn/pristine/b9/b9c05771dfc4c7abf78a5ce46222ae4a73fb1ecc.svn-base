<?php


namespace app\common\model;

use app\index\service\DepartmentUserMapService;
use app\index\service\Department as DepartmentServer;
use think\Model;

class RegisterCompanyLogs extends Model
{
    public function getDepartmentNameAttr($value, $data)
    {
        $departmentUserMapService = new DepartmentUserMapService();
        $department_ids = $departmentUserMapService->getDepartmentByUserId($data['operator_id']);
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