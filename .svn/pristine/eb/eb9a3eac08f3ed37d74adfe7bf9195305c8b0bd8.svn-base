<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/10/10
 * Time: 16:33
 */

namespace app\publish\filter;

use app\common\filter\BaseFilter;
use app\common\service\Common;
use app\common\traits\User;

class JoomListingFilter extends BaseFilter
{
    use User;
    protected $scope = 'VirtualOrderApply';

    public static function getName(): string
    {
        return '虚拟订单权限过滤器';
    }

    public static function config(): array
    {
        $options = [
            ['value'=>0,'label'=>'自己'],
            ['value'=>1,'label'=>'下属'],
        ];
        return [
            'key' => 'type',
            'type' => static::TYPE_SELECT,
            'options' => $options
        ];
    }

    public function generate()
    {
        $type = $this->getConfig();
        $result = [];
        if($type){
            $userInfo = Common::getUserInfo();
            if(in_array(0,$type)){
                $result[] = $userInfo['user_id'];
            }
            IF(in_array(1,$type)){
                $users = $this->getUnderlingInfo($userInfo['user_id']);
                $result = array_merge($result,$users);
            }
        }

        return $result;
    }
}