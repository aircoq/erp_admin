<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-4-18
 * Time: 下午5:40
 */

namespace app\system\server;


use app\common\exception\JsonErrorException;
use erp\AbsServer;
use app\common\model\ConfigParams as ConfigParamsModel;
use think\db\Query;
use think\Exception;

class ConfigParams extends AbsServer
{
    public function getConfigs()
    {
        $configs = ConfigParamsModel::all(function(Query $query){
            $query->order('sort');
        });
        $groups = [];
        foreach ($configs as $config){
            $groups[$config->group_id][] = $config;
        }
        $result = [];
        foreach ($groups[0] as $key=>$group){
            $result[$key] = $group;
            $result[$key]['childs'] = isset($groups[$group->id]) ? $groups[$group->id] : [];
        }
        return $result;
    }

    public function addConfig($params)
    {
        try{
            ConfigParamsModel::create($params);
        }catch (Exception $exception){
            echo $exception->getMessage();
        }
    }

    public function addParam($params)
    {
        try{
            $oldCodeModel = ConfigParamsModel::where('code', $params['code'])->where('group_id', $params['group_id'])->find();
            if(!$oldCodeModel){
                ConfigParamsModel::create($params);
            }else{
                throw new JsonErrorException("已存在相同code的参数");
            }

        }catch (Exception $exception){
            echo $exception->getMessage();
        }
    }

    public function addGroup($params)
    {
        try{
            $oldCodeModel = ConfigParamsModel::where('code', $params['code'])->where('group_id', 0)->find();
            if(!$oldCodeModel){
                ConfigParamsModel::create($params);
            }else{
                throw new JsonErrorException("已存在相同code的分组");
            }

        }catch (Exception $exception){
            echo $exception->getMessage();
        }
    }

    public function mdfGroup($group)
    {
        if($oldGroup = ConfigParamsModel::get($group['id'])){
            unset($group['childs']);
            unset($group['create_at']);
            unset($group['update_at']);
            if($oldCodeModel = ConfigParamsModel::where('code', $group['code'])->where('group_id', 0)->find()){
                if($oldCodeModel->id != $group['id']){
                    throw new JsonErrorException("已存在相同code的分组");
                }
            }
            $oldGroup->save($group);
        }else{
            throw new JsonErrorException("不存在分组");
        }
    }

    public function mdfParam($param)
    {
        if($oldParam = ConfigParamsModel::get($param['id'])){
            unset($param['childs']);
            unset($param['create_at']);
            unset($param['update_at']);
            if($oldCodeModel = ConfigParamsModel::where('code', $param['code'])->where('group_id', $param['group_id'])->find()){
                if($oldCodeModel->id != $param['id']){
                    throw new JsonErrorException("已存在相同code的参数");
                }
            }
            $oldParam->save($param);
        }else{
            throw new JsonErrorException("不存在参数");
        }
    }

    public function delParam($id)
    {
        if($oldParam = ConfigParamsModel::get($id)){
            $oldParam->delete();
        }else{
            throw new JsonErrorException("非法的参数ID");
        }
    }

    public function delGroup($id)
    {
        if($oldGroup = ConfigParamsModel::get($id)){
            $oldGroup->delete();
            ConfigParamsModel::where('group_id', $id)->delete();
        }else{
            throw new JsonErrorException("非法的分组ID");
        }
    }

}