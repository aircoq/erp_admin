<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-7-2
 * Time: 下午2:54
 */

namespace app\index\service;


use erp\AbsServer;

class Node extends AbsServer
{
    /**
     * @doc 获取全局API(无需认证权限)
     * @return array
     */
    public static function getIgnoreVists()
    {
        if(file_exists(APP_PATH.'ignore_auths.php')){
            return include APP_PATH."ignore_auths.php";
        }else{
            return [];
        }
    }

    public static function getIgnoreVistsApi()
    {
        $vists = static::getIgnoreVists();
        return array_map(function($vist){
            return $vist[2];
        },$vists);
    }
}