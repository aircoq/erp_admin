<?php
namespace app\index\controller;

use app\index\service\ReflectNode;
use think\Request;
use think\Exception;
use app\common\controller\Base;
use app\common\cache\Cache;
use app\common\model\Node as NodeModel;

/**
 * @module 内部系统
 * @title 服务端节点
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/17
 * Time: 14:57
 * @url /node
 */
class Node extends Base
{

    public function index(ReflectNode $reflectNode)
    {
        $nodes = $reflectNode->getMcas();
        return json($nodes);
    }

    /**
     * @title 获取节点页面信息
     * @url pageNode
     */
    public function getPageNode(Request $request, ReflectNode $reflectNode)
    {
        $nodeid = $request->get('nodeid');
        $pageNodes = $reflectNode->getPageNodes($nodeid);
        return json($pageNodes);
    }

    /**
     * @title 忽略权限的节点列表
     * @url ignore-vists
     */
    public function getIgnoreVists()
    {
        $ignoreVists = \app\index\service\Node::getIgnoreVists();
        return json($ignoreVists);
    }

    /**
     * @title 设置节点页面信息
     * @url pageNode
     * @method put
     */
    public function setPageNode(Request $request, ReflectNode $reflectNode)
    {
        $nodeid = $request->put('nodeid');
        $pageNodes = $request->put('page_nodes');
        $reflectNode->setPageNodes($nodeid, $pageNodes);
        return json(['message'=>'保存成功']);
    }

    /**
     * @title 获取节点过虑器列表
     * @url filterNode
     */
    public function getFilterNode(Request $request, ReflectNode $reflectNode)
    {
        $nodeid = $request->get('nodeid');
        $pageNodes = $reflectNode->getFilterNodes($nodeid);
        return json($pageNodes);
    }

    /**
     * @title 设置节点过虑器列表
     * @url filterNode
     * @method put
     */
    public function setFilterNode(Request $request, ReflectNode $reflectNode)
    {
        $nodeid = $request->put('nodeid');
        $pageNodes = $request->put('page_nodes');
        $reflectNode->setFilterNodes($nodeid, $pageNodes);
        return json(['message'=>'保存成功']);
    }

    /**
     * @title 获取节点信息
     * @url config/:nodeid
     */
    public function nodeConfig(ReflectNode $reflectNode, $nodeid)
    {
        $config = $reflectNode->getConfig($nodeid);
        return json($config);
    }

    /**
     *  @title 停用，启用
     *  @url changeStatus
     */
    public function changeStatus(Request $request)
    {
        $id = $request->get('id', 0);
        $status = $request->get('status', 0);
        if (empty($id) || !isset($status)) {
            return json(['message' => '请求参数错误'], 400);
        }
        try {
            $mca = NodeModel::get($id);
            $mca->setField('status', $status);
            return json(['message' => '操作成功'], 200);
        } catch (Exception $e) {
            return json(['message' => '操作失败'], 500);
        }
    }
    
    /**
     * @title 排序
     * @url sort
     * @method POST
     */
    public function sort(Request $request)
    {
        $sorts = $request->post('sorts', []);
        if (empty($sorts)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $sorts = json_decode($sorts, true);
        if (!is_array($sorts)) {
            return json(['message' => '请求参数错误'], 400);
        }
        foreach ($sorts as $k => $v) {
            NodeModel::where(['id' => $k])->setField('sort', $v);
        }
        Cache::store('node')->delete();
        return json(['message' => '操作成功'], 200);
    }
}