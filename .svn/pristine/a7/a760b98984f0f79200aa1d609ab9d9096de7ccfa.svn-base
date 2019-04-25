<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-9-19
 * Time: 下午2:03
 */

namespace app\listing\filter;


use app\common\cache\Cache;
use app\common\filter\BaseFilter;

class Department extends BaseFilter
{

    protected $scope = "Department";

    public static function getName(): string
    {
        return "刊登部门过滤器";
    }

    public static function config(): array
    {
        /**
         * @var $cache \app\common\cache\driver\Department
         */
        $cache = Cache::store('department');
        $departs = $cache->getDepartmentOptions();
        $departs = array_combine(array_values($departs), array_keys($departs));
        return [
            'type'=>static::TYPE_MULTIPLE_SELECT,
            'name'=>'部门类型',
            'options' => array_merge(['本部门' => 0],$departs)
        ];
    }

    protected function generate()
    {

    }
}