<?php
/**
 * Created by PhpStorm.
 * User: ZxH
 * Date: 2019/3/22
 * Time: 10:48
 */

namespace app\goods\filter;

use app\common\filter\BaseFilter;
use app\common\traits\User;
use app\common\service\Common;

class GoodsSkuMapsFilter extends BaseFilter
{
    use User;
    protected $scope = 'GoodsSkuMaps';

    public static function getName(): string
    {
        return '商品映射过滤器';
    }

    public static function config(): array
    {
        $options = [
            ['value' => 0, 'label' => '自己'],
            ['value' => 1, 'label' => '下属'],
        ];
        return [
            'key' => 'type',
            'type' => static::TYPE_MULTIPLE_SELECT,
            'options' => $options
        ];
    }

    public function generate()
    {
        $type = $this->getConfig();
        $result = [];
        if ($type) {
            $userInfo = Common::getUserInfo();
            if (in_array(0, $type)) {
                $result[] = $userInfo['user_id'];
            }
            IF (in_array(1, $type)) {
                $users = $this->getUnderlingInfo($userInfo['user_id']);
                $result = array_merge($result, $users);
            }
        }

        return $result;
    }
}