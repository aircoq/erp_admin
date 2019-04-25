<?php
namespace app\common\model;

use app\common\service\Common;
use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/8/9
 * Time: 15:32
 */
class VirtualOrderMissionLog extends Model
{
    const add = 0;
    const update = 1;
    const delete = 2;

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 新增日志
     * @param $mission_id
     * @param $type
     * @param $data
     * @param $msg
     * @param $isOps
     * @param $userInfo
     */
    public static function addLog($mission_id,$type,$data,$msg='',$isOps=false,$userInfo = [])
    {
        if(!$userInfo){
            if($isOps){
                $userInfo = VirtualOrderUser::getUserInfo();
            }else{
                $userInfo = Common::getUserInfo();
            }
        }

        $temp['mission_id'] = $mission_id;
        $temp['type'] = $type;
        $temp['operator_id'] = $userInfo['user_id'] ?? 0;
        $temp['operator'] = $userInfo['realname'] ?? '';
        $remark = '';
        switch($type){
            case self::add:
                $remark = '[新增记录]';
                break;
            case self::update:
                $remark = '修改记录]';
                break;
            case self::delete:
                $remark = '[删除记录]';
                break;

        }
        $temp['remark'] = $remark.$msg;
        $temp['data'] = json_encode($data,JSON_UNESCAPED_UNICODE);
        $temp['create_time'] = time();
        return (new self())->allowField(true)->isUpdate(false)->save($temp);
    }
}