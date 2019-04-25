<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-2-7
 * Time: 下午2:45
 */

namespace app\index\controller;


use app\common\controller\Base;
use app\common\model\aliexpress\AliexpressAccount as AliexpressAccountModel;
use app\common\model\amazon\AmazonAccount as AmazonAccountModel;
use app\common\model\ebay\EbayAccount as EbayAccountModel;
use app\common\model\wish\WishAccount as WishAccountModel;
use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use app\index\service\ChannelUserAccountMap as ChannelUserAccountMapServer;
use app\index\service\User as UserServer;
use think\db\Query;
use think\Request;

/**
 * @module 账号管理
 * @title 平台账号
 */
class ChannelAccount extends Base
{
    /**
     * @title 搜索账号
     * @url /channel-account/search
     * @apiParam name:channel type:int desc:平台ID
     * @apiParam name:keyword type:string desc:搜索关键字
     * @param Request $request
     * @return \think\response\Json
     */
    public function search(Request $request)
    {
        $channel = $request->get('channel');
        $keyword = $request->get('keyword');
        $server = new \app\index\service\ChannelAccount();
        $account = $server->searchByChannels($keyword, $channel);
        return json($account, 200);
    }


    /**
     * @disabled
     */
    public function user_map_get(Request $request)
    {
        $gets = $request->get();
        if(!isset($gets['channel_id']) || empty($gets['channel_id'])){
            return json_error('必需指定平台');
        }
        if(!isset($gets['account_id']) || empty($gets['account_id'])){
            return json_error('必需指定账号');
        }
        $account_id = $gets['account_id'];
        $channel_id = $gets['channel_id'];
        $server = new ChannelUserAccountMapServer([]);
        $data = $server->getUserMaps($channel_id, $account_id);
        return json($data);
    }

    /**
     * @disabled
     */
    public function user_map_set(Request $request)
    {
        $puts = $request->put();
        if(!isset($puts['channel_id']) || empty($puts['channel_id'])){
            return json_error("必需指定平台");
        }
        if(!isset($puts['account_id']) || empty($puts['account_id'])){
            return json_error("必需指定账号");
        }
        if(!isset($puts['maps']) || empty($puts['maps'])){
            return json_error("必需设置项");
        }
        $user = Common::getUserInfo($request);
        $maps = json_decode($puts['maps']);
        if(!$maps){
            return json_error('非法设置数据');
        }
        $channel_id = $puts['channel_id'];
        $account_id = $puts['account_id'];
        $server = new ChannelUserAccountMapServer($user);
        $server->setUserMaps($channel_id, $account_id, $maps);
        return json(['message'=>'设置成功']);

    }
    /**
     * @disabled
     */
    public function user_list(Request $request)
    {
        $channel = $request->param('channel_id');
        if(empty($channel)){
            return json_error('必需指定平台');
        }
        $userServer = new UserServer();
        $result = $userServer->getUsers($channel);
        return json($result);
    }
}