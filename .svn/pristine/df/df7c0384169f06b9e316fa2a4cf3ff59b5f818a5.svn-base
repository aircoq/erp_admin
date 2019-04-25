<?php
namespace app\common\model;

use app\common\service\Common;
use think\Model;
use think\Request;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/8/15
 * Time: 15:32
 */
class UserSimulationOnLog extends Model
{

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 新增日志
     * @param $user
     * @param $remark
     */
    public static function addLog($user,$remark='')
    {
        $userInfo = Common::getUserInfo();
        $temp['user_id'] = $user['id'];
        $temp['user'] = $user['realname'];
        $temp['operator_id'] = $userInfo['user_id'] ?? 0;
        $temp['operator'] = $userInfo['realname'] ?? '';
        $temp['remark'] = $remark;
        $temp['ip'] = Request::instance()->ip();
        $temp['create_time'] = time();
        return (new UserSimulationOnLog())->allowField(true)->isUpdate(false)->save($temp);
    }
}