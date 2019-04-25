<?php
namespace app\index\controller;

use app\common\controller\Base;
use app\common\service\ChannelAccountConst;
use app\index\service\MemberShipService;
use think\Request;

/**
 * @module 用户系统
 * @title 平台账号绑定
 * @author phill
 * @url /member-ship
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/5/8
 * Time: 17:19
 */
class MemberShip extends Base
{
    /**
     * @var MemberShipService
     */
    protected $memberShipService;

    protected function init()
    {
        if (is_null($this->memberShipService)) {
            $this->memberShipService = new MemberShipService();
        }
    }

    /**
     * @title 成员列表
     * @apiFilter app\common\filter\ChannelsFilter
     * @return \think\response\Json
     */
    public function index()
    {
        $request = Request::instance();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $params = $request->param();
        $where = [];
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            $snText = $params['snText'];
            $snText = json_decode($snText, true);
            if($snText){
                switch ($params['snType']) {
                    case 'sales':
                        $where['seller_id'] = ['in', $snText];
                        break;
                    case 'customer':
                        $where['customer_id'] = ['in', $snText];
                        break;
                    default:
                        break;
                }
            }

        }
        if ($channel_id = param($params,'channel_id')) {
            $where['channel_id'] = ['=', $channel_id];
            if ($shop_id = param($params,'shop_id')) {
                $where['account_id'] = ['=', $shop_id];
            }
        }

        $account_id = param($params,'account_id');
        $accountType = param($params,'account_type');
        if ($account_id && $accountType && $channel_id) {

            $account_id = json_decode($account_id,true);
            if( $account_id){
                if($accountType == 2){ //文本
                    $account_id = $this->memberShipService->getAccountIdByCode($channel_id,$account_id);
                }else{
                    if($channel_id == ChannelAccountConst::channel_Joom){
                        $account_id = (new \app\common\model\joom\JoomShop())->where('joom_account_id' ,'in',$account_id)->column('id');
                    }
                }
                $where['account_id'] = ['in', $account_id];
            }
        }

        $shop_id = param($params,'shop_id');
        if ($shop_id) {
            $where['account_id'] = $shop_id;
        }

        $result = $this->memberShipService->memberList($where,$page,$pageSize);
        return json($result, 200);
    }

    /**
     * @title 查看成员账号绑定信息
     * @param $id
     * @url /member-ship/:id(\w+)
     * @return \think\response\Json
     */
    public function read($id)
    {
        $result = $this->memberShipService->info($id);
        return json($result, 200);
    }

    /**
     * @title 获取编辑成员信息
     * @param $id
     * @url /member-ship/:id(\w+)/edit
     * @return \think\response\Json
     */
    public function edit($id)
    {
        $result = $this->memberShipService->info($id);
        return json($result, 200);
    }

    /**
     * @title 新增成员
     * @param Request $request
     * @return \think\response\Json
     * @apiRelate app\index\controller\User::staffs
     */
    public function save(Request $request)
    {
        $params = $request->param();
        if(!isset($params['detail'])){
            return json(['message' => '参数错误'], 400);
        }
        $detail = json_decode($params['detail'], true);
        if (empty($detail)) {
            return json(['message' => '请至少填写一条记录'], 400);
        }

        // 根据平台，过滤参数
        foreach ($detail as $key=>$val){
            $info = $this->filterParams($val['info'], $val['channel_id']);
            if(empty($info['data'])){
                unset($detail[$key]);
            } else {
                $detail[$key]['info'] = $info['data'];
            }
        }

        $result = $this->memberShipService->add($detail);
        return json(['message' => '新增成功','data' => $result], 200);
    }

    /**
     * @title 更新成员
     * @param Request $request
     * @param $id
     * @url /member-ship/:id(\w+)
     * @method put
     * @return \think\response\Json
     * @apiRelate app\index\controller\User::staffs
     */
    public function update(Request $request, $id)
    {
        $params = $request->param();
        if (!isset($params['info'])) {
            return json(['message' => '销售员必须指定'], 400);
        }
        $infoList = json_decode($params['info'], true);
        if (empty($infoList)) {
            return json(['message' => '销售员必须指定'], 400);
        }
        unset($params['info']);

        // 根据平台，过滤参数
        $realData = $this->filterParams($infoList, $params['channel_id']);
        if(!empty($realData['msg'])){
            return json(['message' => $realData['msg']], 400);
        }

        $result = $this->memberShipService->update($params,$realData['data'], $id);
        return json(['message' => '更新成功','data' => $result], 200);
    }

    /**
     * @title 删除
     * @param $id
     * @url /member-ship/:id(\w+)
     * @return \think\response\Json
     */
    public function delete($id)
    {
        $this->memberShipService->delete($id);
        return json(['message' => '删除成功'], 200);
    }

    /**
     * @title 批量删除
     * @url batch/:type(\w+)
     * @method post
     * @return \think\response\Json
     */
    public function batch()
    {
        $request = Request::instance();

        // 批量修改时， 需要根据平台过滤参数 add by 2019-04-16
        if ($request->param('type') == 'update') {
            $postData = $request->post();
            $data = json_decode($postData['data'], true);
            foreach ($data as $key => $val) {
                $data[$key]['info'] = $this->filterParams($val['info'], $val['channel_id'])['data'];
            }
            $postData['data'] = json_encode($data);
            $request->post($postData);
        }

        $this->memberShipService->batch($request);
        return json(['message' => '操作成功'], 200);
    }

    /**
     * @title 查找成员关系
     * @url memberInfo
     * @return \think\response\Json
     * @apiRelate app\index\controller\User::staffs
     */
    public function memberInfo()
    {
        $request = Request::instance();
        $channel_id = $request->get('channel_id', 0);
        $account_id = $request->get('account_id', 0);
        $result = $this->memberShipService->infoByChannel($channel_id, $account_id);
        return json($result, 200);
    }

    /**
     * @title 获取渠道 销售员-客服信息
     * @url :type(\w+)/member
     * @apiParam name:type desc:sales-销售员customer-客服
     * @apiParam name:channel_id desc:渠道id
     * @apiParam name:account_id desc:账号id
     * @return \think\response\Json
     */
    public function member()
    {
        $request = Request::instance();
        $channel_id = $request->get('channel_id', 0);
        $account_id = $request->get('account_id', 0);
        $params = $request->param();
        $type = $params['type'];
        $result = $this->memberShipService->member($channel_id, $account_id, $type);
        return json($result);
    }

    /**
     * @title 刊登获取 销售员-客服信息
     * @url :channel_id(\d+)/:type(\w+)/publish
     * @apiParam name:type desc:sales-销售员customer-客服
     * @apiParam name:channel_id desc:渠道id
     * @apiParam name:category_id desc:分类id
     * @apiParam name:warehouse_type desc:仓库类型
     * @apiParam name:spu desc:spu
     * @apiParam name:snType desc:搜索内容code简称account_id账号seller_id销售员
     * @apiParam name:snText desc:搜索内容值
     * @return \think\response\Json
     */
    public function publish()
    {
        $request = Request::instance();
        $spu = $request->get('spu', 0);
        $warehouse_type = $request->get('warehouse_type', 0);
        $snType = $request->get('snType', 0);
        $category_id = $request->get('category_id', 0);
        $snText = $request->get('snText', 0);
        $where = [];
        if(!empty($snType) && !empty($snText)){
            $where[$snType] = ['=',$snText];
        }
        $params = $request->param();
        $type = $params['type'];
        $channel_id = $params['channel_id'];
        $result = $this->memberShipService->memberByPublish($warehouse_type, $channel_id, $type, $spu,$where,$category_id);
        return json($result);
    }

    /**
     * @title 全部导出
     * @url download
     * @method post
     * @return \think\response\Json
     */
    public function download(Request $request)
    {
        $params=$request->param();
        try{
            $result=$this->memberShipService->applyExport($params);
             return json($result);
        }catch (\Exception $ex){
            $msg  = $ex->getMessage();
            return json(['message'=>$msg],500);
        }
    }

    /**
     * @title 日志
     * @url log
     * @method get
     */
    public function log()
    {
        $request = Request::instance();
        $channel_id = $request->get('channel_id', 0);
        $account_id = $request->get('account_id', 0);
        if (empty($channel_id) || empty($account_id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $dataInfo = $this->memberShipService->getLog($channel_id,$account_id);
        return json(['message' => '拉取成功', 'data' => $dataInfo], 200);
    }

    /**
     * @title 平台账号成员列表
     * @method get
     * @url channel-user-account
     * @return \think\response\Json
     */
    public function channelUserAccount()
    {
        $request = Request::instance();
        $channel_id = $request->get('channel_id',0);
        $account_id = $request->get('account_id',0);
        if (empty($channel_id) || empty($account_id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $where['channel_id'] = ['eq',$channel_id];
        $where['account_id'] = ['eq',$account_id];
        $accountList = $this->memberShipService->getChannelUserAccount($where);
        return json($accountList);
    }

    /**
     * @title 添加平台账号成员
     * @method post
     * @url add-account
     * @return \think\response\Json
     */
    public function addAccount()
    {
        $request = Request::instance();
        $channel_id = $request->post('channel_id',0);
        $account_id = $request->post('account_id',0);
        $userList = $request->post('users','');
        if(empty($channel_id) || empty($account_id) || empty($userList)){
            return json(['message' => '参数值不能为空'],400);
        }
        $userList = json_decode($userList,true);
        $this->memberShipService->addChannelUserAccount($channel_id,$account_id,$userList);
        return json(['message' => '保存成功']);
    }

    /**
     * @title 过滤保存数据
     * @return array
     */
    private function filterParams($infoList, $channel_id)
    {
        // joom平台， 修改销售员与仓库类型只能是全部 add by 2019-04-16
        if (ChannelAccountConst::channel_Joom == $channel_id) {

            $arr = [
                'data' => [], // 过滤后的参数
                'msg' => '',  // 错误信息
            ];
            foreach ($infoList as $key => $val) {
                if (isset($val['warehouse_type']) && $val['warehouse_type'] == 0) {
                    $arr['data'][] = $val;
                    break;
                }
            }
            empty($arr['data']) && $arr['msg'] = 'joom平台，销售员与仓库类型只能是全部';

            return $arr;

        } else {

            $warehouse_type = $temp = [];
            $saveData = [
                'data' => [], // 过滤后的参数
                'msg' => '',  // 错误信息
            ];

            // 其他平台，可以选全部，选全部的话只有一条。如果不选全部，只能有一个海外或者一个本地，或者各一个
            foreach ($infoList as $key => $val) {

                // warehouse_type = 0 全部
                if (isset($val['warehouse_type']) && $val['warehouse_type'] == 0) {
                    $temp = $val;
                    break;
                }

                // warehouse_type = [1:本地, 2:海外]
                if (!in_array($val['warehouse_type'], $warehouse_type)) {
                    array_push($saveData['data'], $val);
                    array_push($warehouse_type, $val['warehouse_type']);
                }
            }

            if (!empty($temp)) {
                return ['data' => [$temp], 'msg' => ''];
            }

            if (empty($saveData['data'])) {
                $saveData['msg'] = '除了joom平台，可以选全部，选全部的话只有一条。如果不选全部，只能有一个海外或者一个本地，或者各一个';
            }
            return $saveData;
        }
    }
}