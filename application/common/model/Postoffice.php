<?php


namespace app\common\model;

use think\Model;
use app\common\cache\Cache;

class Postoffice extends Model
{
    public function getCreateTxtAttr($value,$data)
    {
        return Cache::store('user')->getOneUserRealname($data['creator_id']);
    }

    public function getCreateTimeDateAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$data['create_time']);
    }

}