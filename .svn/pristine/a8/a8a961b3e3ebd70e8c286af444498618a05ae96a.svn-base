<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-16
 * Time: 下午4:25
 */

namespace app\common\traits;


trait Role
{
    private $roleServer;
    public function getRole($roleId)
    {
        if(!$this->roleServer){
            $this->roleServer = new \app\index\service\Role();
        }
        return $this->roleServer->getRole($roleId);
    }
}