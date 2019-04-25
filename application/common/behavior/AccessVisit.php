<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-28
 * Time: 下午2:43
 */

namespace app\common\behavior;


use erp\ErpRbac;
use app\index\service\User;
use think\exception\HttpResponseException;
use think\Request;

class AccessVisit
{
    public function run(&$call)
    {
        $request = Request::instance();
        if(ErpRbac::withoutVisits($request)){
            return;
        }
        $tokeTypes = $request->header('tokeTypes', '');
        if ($tokeTypes == 'Virtual') {
            return;
        }
        $userId = User::getCurrent();
        $rbac = ErpRbac::getRbac($userId);
        if(!$rbac->visit()){
            throw new HttpResponseException(json_error('没有访问权限'));
        }
    }
}