<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-27
 * Time: 上午11:06
 */

namespace app\common\model;


use erp\ErpModel;

class AccessRules extends ErpModel
{
    public function getParamsAttr($params)
    {
        return json_decode($params);
    }

    public function setParamsAttr($params)
    {
        return json_encode($params);
    }
    
    public static function getByRoleIds($roleIds)
    {
        return static::where('role_id','in', $roleIds)->field('rule_tag,params')->select();
    }
}