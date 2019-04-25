<?php
namespace app\index\controller;

use app\common\controller\Base;
use app\common\service\Common;
use app\common\service\UniqueQueuer;
use app\common\model\ServerLog;
use app\index\queue\AccountUserMapBatchQueue;
use app\index\service\AccountUserMapService;
use think\Request;

/**
 * @module 账号基础信息-成员管理
 * @title 账号基础信息-成员管理
 * @url /account-users
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/23
 * Time: 14:40
 */
class AccountUser extends Base
{
    protected $accountUserService;

    protected function init()
    {
        if (is_null($this->accountUserService)) {
            $this->accountUserService = new AccountUserMapService();
        }
    }

    /**
     * @title 显示资源列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $account_id = $request->get('account_id',0);
        $content = $request->get('content','');
        if(empty($account_id)){
            return json(['message' => '参数值错误'],400);
        }
        if(!empty($content)){
            $where['u.realname'] = ['like','%'.$content.'%'];
        }
        $where['account_id'] = ['eq',$account_id];
        $accountList = $this->accountUserService->map($where);
        return json($accountList);
    }

    /**
     * @title 保存新建的资源
     * @param  \think\Request $request
     * @return \think\Response
     * @apiRelate app\index\controller\User::staffs
     */
    public function save(Request $request)
    {
        $account_id = $request->post('account_id',0);
        $userList = $request->post('users','');
        if(empty($account_id) || empty($userList)){
            return json(['message' => '参数值不能为空'],400);
        }
        $userList = json_decode($userList,true);
        $this->accountUserService->add($account_id,$userList);
        return json(['message' => '保存成功']);
    }


    /**
     * @title 批量添加、删除账号成员
     * @method post
     * @url batch
     * @param Request $request
     * @return \think\response\Json
     */
    public function batch(Request $request){
        $channel_id = $request->post('channel_id');
        $user_id = $request->post('user_id');
        $type = $request->post('type'); //
        if(empty($channel_id) || empty($user_id) || empty($type)){
            return json(['message' => '参数值不能为空'],400);
        }
        if($channel_id > 4){
            return json(['message' => '目前只支持大平台'],400);
        }
        $service = new UniqueQueuer(\app\index\queue\AccountUserMapBatchQueue::class);
        $ids = json_decode($user_id,true);
        $user =  Common::getUserInfo();
        $user['realname'] = '[批量操作]'. $user['realname'];
        foreach ($ids as $userId){
            $temp = [
                'channel_id' => $channel_id,
                'user_id' => $userId,
                'is_add' => $type == 1 ? true : false,
                'user' => $user,
            ];
            $service->push($temp);
        }
        return json(['message' => '已添加到队列处理请耐心等待.']);
    }
}