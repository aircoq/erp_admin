<?php


namespace app\common\model;

use think\Model;
use app\common\model\Software;
use app\common\cache\Cache;
use app\common\traits\ConfigCommon;
use app\index\service\DepartmentUserMapService;
use app\index\service\Department as DepartmentServer;

class SoftwareVersion extends Model
{
    use ConfigCommon;
    public function software()
    {
        return $this->belongsTo(Software::class, 'software_id', 'id');
    }

    public function getCreatorTxtAttr($value, $data)
    {
        return Cache::store('user')->getOneUserRealname($data['creator_id']);
    }

    public function getCreateTimeTxtAttr($value, $data)
    {
        return date('Y-m-d', $data['create_time']);
    }

    public function getUpgradeAddressAttr($value, $data)
    {
        $ip = $this->getApiIpCfg();
        return $ip."/".$data['upgrade_address'];
    }
    private function getApiIpCfg()
    {
        $this->setConfigIdentification('api_ip');
        return $this->getConfigData();
    }

    public function getStatusTxtAttr($value, $data)
    {
        $map = [
            '0' => '启用',
            '1' => '停用'
        ];
        return $map[$data['status']] ?? '';
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