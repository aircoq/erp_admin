<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/10/22
 * Time: 14:46
 */
class ChannelNode extends Validate
{
    protected $rule = [
        ['website_url', 'require|unique:ChannelNode,website_url','网站地址不能为空！|网站地址已存在！'],
        ['type','require','类型为必填'],
    ];

}