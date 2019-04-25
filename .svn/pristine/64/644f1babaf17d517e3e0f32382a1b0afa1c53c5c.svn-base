<?php

namespace app\report\filter;

use app\common\filter\BaseFilter;
use app\common\service\Common;
use app\common\traits\User;
use app\common\model\User as ModelUser;

/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2019/4/8
 * Time: 17:04
 */
class OrderDetailBySellerFilter extends BaseFilter
{
    use User;
    protected $scope = 'Seller';

    public static function getName(): string
    {
        return '通过销售员过滤订单详情统计数据';
    }

    public static function config(): array
    {
        $options = ModelUser::where('status',1)->field('id as value, realname as label')->select();
        return [
            'key' => 'type',
            'type' => static::TYPE_SELECT,
            'options' => $options
        ];
    }

    public function generate()
    {
        $userInfo = Common::getUserInfo();
        //获取自己和下级用户
        $userList = $this->getUnderlingInfo($userInfo['user_id']);
        $userIds = [$userInfo['user_id']];
        if ($userList) {
            $userIds = array_merge($userIds, $userList);
        }
        return $userIds;
    }
}