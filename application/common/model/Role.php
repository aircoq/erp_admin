<?php
namespace app\common\model;
use erp\ErpModel;
use traits\model\SoftDelete;
use think\Model;

/**
 * Created by Netbeans.
 * User: empty
 * Date: 2016/12/23
 * Time: 12:03
 */
class Role extends ErpModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    protected static $associatedDeleteCallbacks = [
        \app\index\service\User::class
    ];

    public function isHas($id, $name)
    {
        return $this->where('id', '<>', $id)->where('name', $name)->find();
    }

    public static function getNodes($roleId)
    {
        return static::hasWhere('nodes',function(){
            var_dump(func_get_args());
        })->where('id',$roleId)->select();
    }

    public function nodes()
    {
        return $this->hasManyThrough(Node::class,'node_id','id');
    }

    public function access()
    {
        return $this->hasMany(RoleAccess::class, 'id', 'role_id');
    }

}