<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/4
 * Time: 10:43
 */
class Server extends Validate
{
    protected $rule = [
        ['name', 'require|unique:Server,name','服务器名称不能为空！|服务器名称已存在！'],
        ['ip', 'require|unique:Server,ip','服务器IP地址不能为空！|服务器IP地址已存在！'],
        ['mac', 'require|unique:Server,mac', '服务器MAC地址不能为空!|服务器MAC地址已存在！'],
    ];

    protected $scene = [
        'edit' => ['id','name','ip','mac']
    ];
}