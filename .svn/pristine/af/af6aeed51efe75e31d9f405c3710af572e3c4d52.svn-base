<?php
namespace app\system\validate;
use think\Validate;

/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-20
 * Time: 上午10:54
 */
class Menu extends Validate
{
    protected $rule = [
        ['id','require'],
        ['type','require|in:0,1,2','无效的类型'],
        ['status','require|in:1,2','无效的状态'],
        ['title','require'],
        ['pid','require'],
        ['sort','require'],
        ['paths','require'],
        ['remark','require'],
        ['group','require']
    ];

    protected $scene = [
        'add' => ['name','type','status','title','pid','sort','paths','remark','group'],
        'setting' => ['type','title','pid','sort','paths','remark','group'],
        'change_status' => ['id','status'],
        'change' => []
    ];
}