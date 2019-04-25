<?php
namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;


/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class Config extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }
    
    public static function getGoroups()
    {
        $groups = self::where(['group_id' => 0])->column('title', 'id');
        return $groups;
    }
}