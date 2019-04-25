<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/5/14
 * Time: 15:31
 */

namespace app\publish\filter;

use app\common\filter\BaseFilter;
use app\common\model\Role;
use app\common\model\RoleUser;
use app\common\traits\User;
use app\common\model\User as UserModel;
use app\common\service\Common;
class EbayOeListFilter extends BaseFilter
{
    use User;
    protected $scope = 'OeList';
    public static function getName(): string
    {
        return 'OE权限过滤器';
    }

    public static function config(): array
    {
        $ebayRoleIds = Role::where(['name'=>['like', '%ebay%'], 'delete_time'=>null])->column('id');
        $userIds = RoleUser::where(['role_id'=>['in', $ebayRoleIds]])->column('user_id');
        $options = (new UserModel())->field('id as value, realname as label')->where(['id'=>['in', $userIds]])->select();
        return [
            'key' => 'type',
            'type' => static::TYPE_SELECT,
            'options' => $options
        ];
    }

    public function generate()
    {
        $type = $this->getConfig();
        $userInfo = Common::getUserInfo();
        $userIds = $this->getUnderlingInfo($userInfo['user_id']);//获取下属人员信息
        return $userIds;
    }
}