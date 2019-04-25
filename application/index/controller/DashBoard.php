<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-5-9
 * Time: 上午10:47
 * 2017-11-24  phill 重新整理
 */

namespace app\index\controller;


use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\index\service\Dashboard as Server;
use think\Request;

/**
 * @module 通用系统
 * @title 首页
 * @url /dashboard
 * Class DashBoard
 * @package app\index\controller
 */
class DashBoard extends Base
{

    /**
     * @title 最近15天平台订单总数
     * @url nearby15
     * @param Server $server
     * @apiFilter app\common\filter\ChannelsFilter
     * @apiFilter app\common\filter\ChannelAccountsFilter
     * @return \think\response\Json
     */
    public function nearby15(Server $server)
    {
        $request = Request::instance();
        $channel_id = $request->get('channel_id',0);
        return json($server->nearby15($channel_id));
    }

    /**
     * @title 最近2天平台订单总数
     * @url nearby2
     * @param Server $server
     * @apiFilter app\common\filter\ChannelsFilter
     * @apiFilter app\common\filter\ChannelAccountsFilter
     * @return \think\response\Json
     */
    public function nearby2(Server $server)
    {
        $request = Request::instance();
        $channel_id = $request->get('channel_id',0);
        return json($server->nearby2($channel_id));
    }

    /**
     * @title 最近15天平台订单总数[钉钉]
     * @url dingtalk-nearby15
     * @param Server $server
     * @apiFilter app\common\filter\ChannelsFilter
     * @apiFilter app\common\filter\ChannelAccountsFilter
     * @return \think\response\Json
     */
    public function nearby16(Server $server)
    {
        $request = Request::instance();
        $channel_id = $request->get('channel_id',0);
        return json($server->nearby16($channel_id));
    }

    /**
     * @title 最近15天平台FBA订单总数
     * @url fba-nearby15
     * @param Server $server
     * @apiFilter app\common\filter\ChannelsFilter
     * @apiFilter app\common\filter\ChannelAccountsFilter
     * @return \think\response\Json
     */
    public function fbaNearby15(Server $server)
    {
        $request = Request::instance();
        $channel_id = $request->get('channel_id',0);
        return json($server->fbaNearby15($channel_id));
    }

    /**
     * @title 查询账号业绩
     * @url account-performance
     * @apiParam name:channel type:int required desc:平台
     * @apiParam name:account type:int required desc:账号
     * @apiParam name:time type:int required desc:时间
     * @param Request $request
     * @param Server $server
     * @return \think\response\Json
     */
    public function accountPerformance(Request $request, Server $server)
    {
        $params = $request->param();
        if(!$time = param($params, 'time')){
            throw new JsonErrorException("缺少时间");
        }
        $channel = param($params, 'channel', 0);
        $account = param($params, 'account', 0);
        $result = $server->performance($channel, $account, $time);
        return json($result);
    }

    /**
     * @title 订单管理
     * @url orders
     * @param Server $server
     * @return \think\response\Json
     */
    public function orderInfo(Server $server)
    {
        return json($server->orderInfo());
    }

    /**
     * @title 订单管理
     * @url listings
     * @param Server $server
     * @return \think\response\Json
     */
    public function listingCount(Server $server)
    {
        $listingCounts = $server->listingCount();
        return json($listingCounts);
    }

    /**
     * @title 仓库信息
     * @url warehouses
     * @param Server $server
     * @return \think\response\Json
     */
    public function warehouseInfo(Server $server)
    {
        $warehouseInfo = $server->warehouseInfo();
        return json($warehouseInfo);
    }

    /**
     * @title 账号销售量统计
     * @url account-info
     * @param Server $server
     * @apiFilter app\common\filter\ChannelsFilter
     * @apiFilter app\common\filter\ChannelAccountsFilter
     * @return \think\response\Json
     */
    public function accountInfo(Server $server)
    {
        $request = Request::instance();
        $dateline = $request->get('dateline',date('Y-m-d'));
        $channel_id = $request->get('channel_id',0);
        $page = $request->get('page',1);
        $pageSize = $request->get('pageSize',20);
        if($channel_id <= 0){
            return json(['message' => '账号id必填'] ,400);
        }
        $data = $server->getStaticOrderInfo($channel_id,$dateline,$page,$pageSize);
        return json(['message' => '获取成功', 'data' => $data]);
    }
}