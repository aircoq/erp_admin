<?php

/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 16-12-14
 * Time: 下午2:57
 */
namespace app\common\model\system;
use think\Model;

class Menu extends Model
{

    public static $subClasses = [
        'tt'=>[
            'propertys'=>['a','b','c','d'],
            'methods'=>[
                'abc' => ['a','b', 'c'],
                'abd' => ['a','b','d']
            ]
        ]
    ];
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function childs($call = 'select')
    {
        return static::where('pid', $this->id)->$call();
    }
}