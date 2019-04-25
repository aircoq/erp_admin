<?php
namespace app\common\model;
use traits\model\SoftDelete;
use think\Model;

/**
 * Created by Netbeans.
 * User: empty
 * Date: 2016/12/23
 * Time: 12:03
 */
class OrderOos extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public static function invoke()
    {
        return new self();
    }
    
}