<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-4-18
 * Time: 下午5:39
 */

namespace app\system\controller;


use app\common\controller\Base;
use app\system\server\ConfigParams as Server;
use think\Request;

/**
 * @module 系统内部
 * @url /system-config
 * @title 新的系统配置
 */
class ConfigParams extends Base
{
    public function index(Server $server)
    {
        $configs = $server->getConfigs();
        return json($configs);
    }

    public function save(Request $request, Server $server)
    {
        $params = $request->param();
        $server->addConfig($params);
        return json(['message'=>'添加成功']);
    }

    /**
     * @title 添加分组
     * @url group
     * @method post
     *
     * @param Request $request
     * @param Server $server
     * @return \think\response\Json
     */
    public function add_group(Request $request, Server $server)
    {
        $params = $request->param();
        $server->addGroup($params);
        return json(['message'=>'添加分组成功']);
    }

    /**
     * @title 添加配置
     * @url param
     * @method post
     * @param Request $request
     * @param Server $server
     * @return \think\response\Json
     */
    public function add_param(Request $request, Server $server)
    {
        $params = $request->param();
        $server->addParam($params);
        return json(['message'=>'添加参数成功']);
    }

    /**
     * @title 修改分组
     * @url group
     * @method put
     *
     * @param Request $request
     * @param Server $server
     * @return \think\response\Json
     */
    public function mdf_group(Request $request, Server $server)
    {
        $params = $request->param();
        $server->mdfGroup($params);
        return json(['message'=>'编辑分组成功']);
    }

    /**
     * @title 修改配置
     * @url param
     * @method put
     *
     * @param Request $request
     * @param Server $server
     * @return \think\response\Json
     */
    public function mdf_param(Request $request, Server $server)
    {
        $params = $request->param();
        $server->mdfParam($params);
        return json(['message'=>'编辑参数成功']);
    }

    /**
     * @title 删除分组
     * @url group/:id
     * @method delete
     *
     * @param $id
     * @param Server $server
     * @return \think\response\Json
     */
    public function del_group($id, Server $server)
    {
        $server->delGroup($id);
        return json(['message'=>'编辑分组成功']);
    }

    /**
     * @title 删除配置
     * @url param:/id
     * @method delete
     *
     * @param $id
     * @param Server $server
     * @return \think\response\Json
     */
    public function del_param($id, Server $server)
    {
        $server->delParam($id);
        return json(['message'=>'编辑参数成功']);
    }
}