<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-29
 * Time: 下午5:53
 */

namespace app\index\service;


use app\common\cache\Cache;
use app\common\cache\driver\ModelFilter;
use app\common\filter\BaseFilter;
use app\common\filter\WarehouseFilter;
use erp\AbsFilterRule;
use erp\AbsServer;
use app\common\exception\JsonErrorException;
use app\common\model\McaNode;
use erp\FilterConfig;
use Nette\Utils\JsonException;
use think\Model;

class ReflectNode extends AbsServer
{
    private static $filters = [];

    public static function addFilter($filter)
    {
        static::$filters[] = new $filter;
    }

    public function getMcas()
    {
        return McaNode::all();
    }

    public function getPageNodes($nodeid)
    {
        if($mca = McaNode::get($nodeid)){
            return $mca->pagenodes ?: [];
        }else{
            return [];
        }
    }

    public function setPageNodes($nodeid, $nodes)
    {
        if($mca = McaNode::get($nodeid)){
            $mca->pagenodes = $nodes ?: [];
            $mca->save();
            return true;
        }else{
            throw new JsonErrorException("不存在的路由节点");
        }
    }

    public function getFilterNodes($nodeid)
    {
        if($mca = McaNode::get($nodeid)){
            return $mca->filternodes ?: [];
        }else{
            throw new JsonException("不存在的路由节点");
        }
    }

    public function setFilterNodes($nodeid, $nodes)
    {
        if($mca = McaNode::get($nodeid)){
            $mca->filternodes = $nodes ?: [];
            $mca->save();
        }else{
            throw new JsonErrorException("不存在的路由节点");
        }
    }

    /**
     * @param $node Model|int
     * @return array
     */
    public function getConfig($node)
    {
        if(!($node instanceof Model)){
            $node = McaNode::get($node);
        }
        if($node){
            return [
                'name' => $node->title,
                'nodeid' => $node->id,
                'title'=> "{$node->module}>>{$node->class_title}>>{$node->title}",
                'configs'=>$this->getFiltersConfig($node->filternodes ?: []),
                'relates'=>$this->getRelatesConfig($node->relates)
            ];
        }else{
            return [];
        }
    }

    public function getFiltersConfig($filters)
    {
//        $filters = [
////            WarehouseFilter::class,
////            \app\listing\filter\Department::class
//        ];
        $result = [];
        foreach ($filters as $filter){
            $result[] = $filter::checkConfig();
        }
        return $result;
    }

    public function getRelatesConfig($relates)
    {
        $configs = [];
        foreach ($relates as $relate){
            $relate = join('::',$relate);
            if($node = McaNode::get(['name'=>$relate])){
                $configs[] = $this->getConfig($node);
            }
        }
        return $configs;
    }

    private function param2name($key, $param)
    {
        if(!is_int($key)){
            $key = isset($param['key']) ? $param['key'] : $param[0];
        }
        $key = explode('|',$key);
        return $key[0];
    }

    public function param2title($key, $param)
    {
        if(!is_int($key)){
            $key = isset($param['key']) ? $param['key'] : $param[0];
        }
        $key = explode('|',$key);
        return isset($key[1]) ? $key[1] : $key[0];
    }

    private function param2type($key, $param)
    {
        if(preg_match('/type:([\w]+)/',$param, $match)){
            return $match[1];
        }
        return 'input';
    }

    private function param2opts($key, $param)
    {
        $ret = [];
        if(preg_match('/select:([^\|.]+)\|?/', $param, $match)){
            $options = explode(',',$match[1]);
            foreach ($options as $option){
                $option = explode(':',$option);
                $ret[] = [
                    'label' => $option[0],
                    'value' => isset($option[1]) ? $option[1] : $option[0]
                ];
            }
        }
        if(preg_match('/json:([\d\w\":,\{\}\[\]\\\\]+)/i', $param, $match)){
            $ret = json_decode($match[1], true);
        };
        return $ret;
    }

    public function param2subs($key, $param)
    {
        var_dump($param);
        return [];
    }

}