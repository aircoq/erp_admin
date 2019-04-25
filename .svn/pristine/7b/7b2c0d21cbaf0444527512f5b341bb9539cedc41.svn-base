<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-4-11
 * Time: 上午10:41
 */

namespace app\common\cache;


use app\index\controller\Test;

class RBAC extends CacheModel
{
    protected static $fields = [
        'pages',
        'filters',
        'visits',
        'relates'
    ];

    public static function hasPermission($api)
    {
        if(preg_match('/|/',$api)){
            list($method, $url) = explode('|',$api);
        }
        if(preg_match('::',$api)){
            list($method, $url) = explode('|',$api);
        }
    }

    public function getVisitsAttr($val)
    {
        if(is_string($val)){
            $val = json_decode($val);
        }
        return $val;
    }

    public function setVisitsAttr($val)
    {
        if(!is_string($val)){
            $val = json_encode($val);
        }
        return $val;
    }

    public function getRelatesAttr($val)
    {
        if(is_string($val)){
            $val = json_decode($val, true);
        }
        if(!$val){
            $val = [];
        }
        return $val;
    }

    public function setRelatesAttr($val)
    {
        if(!is_string($val)){
            $val = json_encode($val);
        }
        return $val;
    }

    public function getFiltersAttr($val)
    {
        if(is_string($val)){
            $val = json_decode($val);
        }
        return $val;
    }

    public function setFiltersAttr($val)
    {
        if(!is_string($val)){
            $val = json_encode($val);
        }
        return $val;
    }

    public function getPagesAttr($val)
    {
        if(is_string($val)){
            $val = json_decode($val);
        }
        return $val;
    }

    public function setPagesAttr($val)
    {
        if(!is_string($val)){
            $val = json_encode($val);
        }
        return $val;
    }
}