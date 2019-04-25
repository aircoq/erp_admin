<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-14
 * Time: 下午2:10
 */

namespace app\system\controller;


use app\common\controller\Base;
use app\system\server\Release as ReleaseServer;
use think\Request;

/**
 * @module 系统管理
 * @title 版本管理
 * @url /release
 */
class Release extends Base
{
    public function index(Request $request, ReleaseServer $server)
    {
        $params = $request->param();
        $releases = $server->getReleases($params);
        return json($releases);
    }

    public function save(Request $request, ReleaseServer $server)
    {
        $post = $request->param();
        $server->create($post);
        return json(['message'=>"新增成功"]);
    }

    public function delete(Request $request, ReleaseServer $server)
    {
        $server->remove($request->param('id'));
        return json(['message'=>'移除成功']);
    }

    /**
     * @title 标识已读
     * @url /release/:id(\d+)/read
     * @method post
     */
    public function read(Request $request, ReleaseServer $server)
    {
        $id = $request->param('id');
        $server->read($id);
        return json(['message'=>'标记成功']);
    }

    /**
     * @title 获取标识已读
     * @url /release/reads
     * @method get
     */
    public function getReads(ReleaseServer $server)
    {
        $userId = Request::instance()->param('id');
        return json($server->getReads($userId));
    }
}