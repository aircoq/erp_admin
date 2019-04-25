<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-14
 * Time: 下午2:36
 */

namespace app\system\model;


use app\common\traits\Role;
use erp\ErpModel;

class ReleaseItem extends ErpModel
{
    use Role;
    protected $table = "version_release_item";

    protected $pk = "id";

    public function getAtRolesAttr($atRoles)
    {
        $atRoles = json_decode($atRoles, true);
        $result = [];
        foreach ($atRoles as $atRole){
            if($role = $this->getRole($atRole)){
                $result[] = [
                    'id'=>$atRole,
                    'name' => $role['name']
                ];
            }

        }
        return $result;
    }

    public function setAtRolesAttr($atRole)
    {
        return json_encode($atRole);
    }

    public function getDevAuthorsAttr($devAuthors)
    {
        return json_decode($devAuthors, true);
    }

    public function setDevAuthorsAttr($devAuthors)
    {
        return json_encode($devAuthors);
    }
}