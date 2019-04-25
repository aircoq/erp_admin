<?php

namespace app\common\model;

use app\common\service\Common;
use app\index\service\AccountApplyService;
use think\Cache;
use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/2/18
 * Time: 11:47
 */
class ExtranetType extends Model
{


    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }


    public static function getName($id)
    {
        return (new ExtranetType())->where('id', $id)->value('name');
    }

    public static function getDNS($id)
    {
        $dns = (new ExtranetType())->where('id', $id)->value('dns');
        return $dns ? explode(',', $dns) : [];
    }

}