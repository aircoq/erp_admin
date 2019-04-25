<?php


namespace app\common\model;

use app\index\service\Department as DepartmentServer;
use app\index\service\DepartmentUserMapService;
use think\Model;

class GoodsSkuMapLog extends Model
{
    protected $table = 'goods_sku_map_log';

    protected $autoWriteTimestamp = false;

    protected $createTime = null;

    protected $updateTime = null;

    protected $hidden = ['id', 'map_id', 'operator_id'];

    protected $append = ['department_name'];

    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function getDepartmentNameAttr($value, $data)
    {
        $userId = $data['operator_id'];
        $departmentUserMapService = new DepartmentUserMapService();
        $department_ids = $departmentUserMapService->getDepartmentByUserId($userId);
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