<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-30
 * Time: 下午3:08
 */

namespace app\common\filter;


use app\common\filter\BaseFilter;
use app\common\model\Account;
use app\common\model\Department;
use app\common\model\User;
use app\common\model\WishTest;
use erp\FilterConfig;
use erp\FilterParam;
use think\Model;

class WarehouseFilter extends BaseFilter
{
    protected $scope = 'Department';

    public static function getName(): string
    {
        return "仓库权限过滤器";
    }

    public static function config(): array
    {
        $depart = Department::get();
        $options = $depart->field('id as value, name as label')->select();
        return [
            'key' => 'type',
            'type' => static::TYPE_SELECT,
            'options' => $options
        ];
    }

    protected function generate()
    {
        $type = $this->getConfig();//1234
        return [19,20,22];
    }

}