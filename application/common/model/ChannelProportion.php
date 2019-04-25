<?php


namespace app\common\model;


use app\common\cache\Cache;
use think\Model;

class ChannelProportion extends Model
{
    public function getDepartmentNameAttr($value, $data)
    {
        $aDepartmentInfo = Cache::store('department')->getDepartment($data['department_id']);
        return $aDepartmentInfo['name'] ?? '';
    }
}