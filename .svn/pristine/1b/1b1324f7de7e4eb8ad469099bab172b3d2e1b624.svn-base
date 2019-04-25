<?php
namespace app\common\validate;

use \think\Validate;

/**
 *
 * Date: 2019/01/15
 * Time: 16:32
 */
class ShippingChannel extends Validate
{

    protected $rule = [
        ['content', 'require', '内容不能为空'],
        ['enabled', 'require', '是否启用不能为空'],
        ['use_site', 'require|number', '是否启用站点配置不能为空|是否启用站点配置参数类型不对'],
        ['channel_id', 'require|number', '平台id是否启用站点配置不能为空|平台id参数类型不对'],
    ];
    
    protected $scene = [
        'add' => ['content', 'enabled', 'use_site', 'channel_id'],
    ];

}