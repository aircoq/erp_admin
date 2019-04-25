<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-31
 * Time: 下午5:22
 */

namespace app\common\model;


use app\common\exception\JsonErrorException;
use erp\ErpModel;
use Nette\Utils\JsonException;
use think\db\Query;

class McaNode extends ErpModel
{
    protected function base(Query $query)
    {
    }
    public function getPagenodesAttr($value)
    {
        if(is_string($value)){
            $value = json_decode($value);
        }
        return $value;
    }

    public function setPagenodesAttr($value)
    {
        if(is_array($value) || is_object($value)){
            $value = json_encode($value);
        }
        return $value;
    }

    public function getRelatesAttr($value)
    {
        if(is_string($value)){
            $value = json_decode($value, true);
        }
        if(!$value){
            $value = [];
        }
        return $value;
    }

    public function setRelatesAttr($value)
    {
        if(is_array($value)){
            $value = json_encode($value);
        }
        return $value;
    }

    public function getFilternodesAttr($value)
    {
        if(is_string($value)){
            $value = json_decode($value);
        }
        return $value;
    }

    public function setFilternodesAttr($value)
    {
        if(is_array($value) || is_object($value)){
            $value = json_encode($value);
        }
        return $value;
    }

    public static function node_id($method, $route)
    {
        $where = [
            'method' => strtolower($method),
            'route' => $route,
        ];
        $one = static::where($where)->find();
        if($one){
            return $one->id;
        }
    }

    public static function node_id_del($method, $route)
    {
        $where = [
            'method' => strtolower($method),
            'route' => $route,
        ];
        static::where($where)->cache('node_'.$method.'|'.$route,10)->delete();

    }

    public static function relates($nodeid)
    {
        $mca = static::get($nodeid, true);
        if($mca){
            return $mca->relates;
        }else{
            return [];
        }
    }

    public static function apis($nodeid)
    {
        $mca = static::get($nodeid, true);
        if($mca){
            try{
                $apis = array_filtermap(function($relate){
                    return static::relate2route($relate);
                }, $mca->relates);
                $apis[] = $api = static::route2api($mca);
                return $apis;
            }catch (JsonErrorException $jsonErrorException){
                throw new JsonErrorException("$nodeid -> {$jsonErrorException->getMessage()}");
            }
        }else{
            return [];
        }
    }

    public static function relate2route($relate)
    {
        if(is_string($relate) && preg_match('/|/', $relate)){
            return $relate;
        }
        if(is_string($relate[0]) && preg_match("/app\\\\([\w]+)\\\\controller\\\\([\w]+)/i", $relate[0], $match)){
            $mca = "{$match[1]}/{$match[2]}/{$relate[1]}";
            if($find = static::where('mca',$mca)->find()){
                return static::route2api($find);
            }else{
                return false;
            }
        }
        $relate = json_encode($relate);
        throw new JsonErrorException("无效的关系:$relate");
    }

    public static function route2api($mca)
    {
        $route = preg_replace('/\([\\\\\d\w\+]+\)/','', $mca->route);
        $route = preg_replace("/^\\//","", $route);
        $method= $mca->method;
        return "$method|$route";
    }

}