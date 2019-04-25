<?php
namespace app\system\controller;

use think\Request;
use app\common\controller\Base;
use app\system\server\Menu as MenuServer;



/**
 * @module 内部系统
 * @title 菜单
 * Class Menu
 * @package app\system\controller
 */
class Menu extends Base
{

    
    /**
     * @noauth
     */
    public function index(MenuServer $server)
    {
        $group = input('get.group',0);
        $menuData = $server->get_list(['group'=>$group]);
        return json($menuData);
    }

    /**
     * @noauth
     * @title 前端菜单数据
     * @url /menu/pages
     */
    public function pages(MenuServer $server,Request $request)
    {
        $param=$request->get();
        $pages = $server->get_pages($param);
        return json($pages);
    }

    /**
     * @title 编辑菜单
     * @url /system/menu/:id
     * @method put
     */
    public function setting(MenuServer $server, $id)
    {
        $puts = Request::instance()->put();
        $server->modify($id, $puts);
        return json(['message'=>'修改成功']);
    }

    /**
     * @title 改变状态
     * @url /system/menu/change-status
     * @method put
     */
    public function change_status(MenuServer $server)
    {
        $puts = Request::instance()->put();
        $server->changeStatus($puts['id'], $puts['status']);
        return json(['message'=>'状态设置成功'],200);
    }

    /**
     * @title 添加菜单
     * @url /system/menu/add
     * @method post
     */
    public function add(Request $request, MenuServer $server)
    {
        $post = $request->post();
        if($id = $server->create($post)){
            return json(['message'=>'新增成功','data'=>$id]);
        }else{
            return json(['message'=>'新增失败'],500);
        }
    }

    /**
     * @title 改变
     * @url /system/menu/change
     * @method put
     */
    public function change(Request $request, MenuServer $sever)
    {
        $put = $request->put();
        $deletes = json_decode($put['deletes']);
        $sorts = json_decode($put['sorts']);
        $sever->deletes($deletes);
        $sever->sorts($sorts);
        return json(['message'=>'更新成功','data'=>$sorts]);
    }

    /**
     * @url /system/menu
     */
    public function delete(Request $request, MenuServer $sever)
    {
        $id = $request->delete('id');
        $sever->deletes([$id]);
        return json(['message'=>'删除成功'],200);
    }
}
