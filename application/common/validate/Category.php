<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:57
 */
class Category extends  Validate
{
    /*protected $rule = [
        ['title','unique:Category,title^pid','分类名不能为空！|同级分类下分类名已存在！'],
        ['code', 'unique:Category,code^pid|max:2', '产品分类编码不能为空！|同级分类编码已经存在！|产品分类编码最大长度为2！']
    ];*/

    protected $rule = [
        ['title', 'require|unique:Category,title^pid'],
        ['code', 'require|unique:Category,code^pid|max:2']
    ];

    protected $message = [
        'title.require' => '分类名不能为空!',
        'title.unique'  => '同级分类下分类名已存在！',
        'code.require'  => '产品分类编码不能为空！',
        'code.unique'   => '同级分类编码已经存在！',
        'code.max'      => '产品分类编码最大长度为2！',
    ];

    protected $scene = [
        'insert' => ['title' => 'require|unique:Category,title^pid', 'code' => 'require|unique:Category,code^pid|max:2'],
        'update' => ['title' => 'unique:Category,title^pid', 'code' => 'unique:Category,code^pid|max:2']
    ];
}