<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class Carrier extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }
	 
    /*public function getTypeAttr($value)
    {
        $status = [0=>'无对接物流商',1=>'API对接物流商'];
        return $status[$value];
    }*/
    
//     public function getStatusAttr($value)
//     {
//         $status = [0=>'禁用',1=>'启用'];
//         return $status[$value];
//     }
    
    /**
     * 检查是否存在
     * @return [type] [description]
     */
    public function check(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    public static function getCache($carrierId)
    {
        return static::get($carrierId, true);
    }
}