<?php
namespace app\common\model;
use think\db\Query;
use think\Model;

/**
 * Created by Netbeans.
 * User: empty
 * Date: 2016/12/23
 * Time: 12:03
 */
class RoleUser extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * @param $userId
     * @return array
     */
    public static function getRoles($userId)
    {
        return self::hasWhere('roles', ['status' => 1])->where('user_id', $userId)->select();
    }

    public function roles()
    {
        return $this->hasMany(Role::class, 'id','role_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }

    public static function getUserIds($roleId)
    {
        $roleUsers = static::where('role_id',$roleId)->select();
        return array_map(function($roleUser){return $roleUser->user_id;},$roleUsers);
    }
}