<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-23
 * Time: 下午2:47
 */

namespace app\common\model;


use erp\ErpModel;

class RoleAccess extends ErpModel
{
    public function getPagesAttr($pages)
    {
        if(is_string($pages)){
            $pages = json_decode($pages);
        }
        return $pages;
    }

    public function setPagesAttr($pages)
    {
        if(!is_string($pages)){
            $pages = json_encode($pages);
        }
        return $pages;
    }

    public function getFiltersAttr($filters)
    {
        if(is_string($filters)){
            $filters = json_decode($filters);
        }
        return $filters;
    }

    public function setFiltersAttr($filters)
    {
        if(!is_string($filters)){
            $filters = json_encode($filters);
        }
        return $filters;
    }

    public function getPages($roleid)
    {
        $pages = static::where('role_id', $roleid)->field('pages')->select();
        $result = [];
        foreach ($pages as $page){
            $result[] = $page->pages;
        }
        return $result;
    }

    public function getFilters($roleid)
    {
        $filters = static::where('role_id', $roleid)->field('filters')->select();
        $result = [];
        foreach ($filters as $filter){
            $result[] = $filter->filters;
        }
        return $result;
    }

    public function mca()
    {
        return $this->hasOne(McaNode::class, 'id', 'node_id');
    }

    public static function getNodeIds($roleId)
    {
        $nodes = static::where(['role_id'=>$roleId])->field('node_id')->select();
        return array_map(function($node){return $node->node_id; }, $nodes);
    }
}