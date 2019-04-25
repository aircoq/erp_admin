<?php
namespace app\common\model\monthly;

use app\common\service\Common;
use think\Model;
use think\Db;

class MonthlyTargetLog extends Model
{

    //类型（0-部门 1-人员 2-金额）
    const department = 0;
    const user = 1;
    const amount = 2;

    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public static function AddLog($type,$department_id,$user_id,$remark)
    {
        $user = Common::getUserInfo();
        $userId = $user['user_id'] ?? 0;
        $data = [
            'create_time' => time(),
            'operator_id' => $userId,
            'operator' => $user['realname'] ?? '',
            'department_id' => $department_id,
            'user_id' => $user_id,
            'type' => $type,
            'remark' => $remark,
        ];
        return (new MonthlyTargetLog())->insert($data);
    }

}
