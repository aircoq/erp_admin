<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-9-19
 * Time: 下午1:51
 */

namespace app\listing\filter;


use app\common\filter\BaseFilter;

class Account extends BaseFilter
{
    public static function getName(): string
    {
        return "刊登账号过滤器";
    }

    public static function config(): array
    {
        return [
            ''
        ];
    }

    protected function generate()
    {
    }
}