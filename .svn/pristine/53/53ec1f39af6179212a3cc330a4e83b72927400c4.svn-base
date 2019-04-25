<?php

namespace app\customerservice\controller;


use app\common\exception\JsonErrorException;
use app\common\model\ChannelUserAccountMap;
use app\common\service\ChannelAccountConst;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\EbaySendMessageQueue;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use app\common\model\ebay\EbayMessage as EbayMessageModel;
use app\customerservice\service\EbayMessageHelp as EbayMessageHelpService;
use app\common\model\ebay\EbayMessageGroup as EbayMessageGroupModel;

/**
 * @module 客服管理
 * @title ebay站内信
 */
class EbayMessage extends Base
{

    protected $service;

    public function __construct()
    {
        parent::__construct();
        empty($this->service) and $this->service = new EbayMessageHelpService();
    }

    /**
     * @title ebay收件箱列表
     * @author tanbin
     * @method GET
     * @apiParam name:customer_id type:int desc:客服账号id
     * @apiParam name:state type:int desc:处理状态(见备注)
     * @apiParam name:time_sort type:int desc:排序
     * @apiParam name:search_key type:string desc:搜索字段
     * @apiParam name:search_val type:string desc:搜索值
     * @remark 处理状态：【  已处理- 1，未处理- 2，48小时未处理 - 11 】  <br />
     * @remark 排序： 【 新到旧：0， 旧到新：1 】   <br />
     * @remark 搜索字段： 【 发送人：sender, 刊登号：item_id ，交易号： 交易号：transaction_id  】   <br />
     * @url /ebay-message
     * @apiFilter app\customerservice\filter\EbayAccountFilter
     * @apiFilter app\customerservice\filter\EbayDepartmentFilter
     * @apiFilter app\customerservice\filter\EbayCustomerFilter
     * @apiRelate app\customerservice\controller\EbayMessage::getMessageLevel&getMessageLevelCount&getGroupDatas&message_list&getAccountList&update&remark
     * @apiRelate app\index\controller\MemberShip::member
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\customerservice\controller\MsgTemplate::getTemplates
     */
    public function index()
    {
        try {
            $request = Request::instance();
            $params = $request->param();
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 10);
            $lists = $this->service->group_list($params, $page, $pageSize);

            $result = [
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $lists['count'],
                'data' => $lists['list'],
            ];

            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 发件箱列表
     * @author tanbin
     * @method GET
     * @apiParam name:type type:int desc:类型[1-ebay来信  2-发件箱]
     * @url /ebay-message/getMessageList/outbox
     * @apiRelate app\index\controller\MemberShip::member
     * @apiRelate app\order\controller\Order::account
     */
    public function getMessageListOutbox()
    {
        $request = Request::instance();
        $params = $request->param();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $result = $this->service->getMessageList($params, $page, $pageSize);
        return json($result, 200);
    }

    /**
     * @title ebay来信
     * @author tanbin
     * @method GET
     * @apiParam name:type type:int desc:类型[1-ebay来信  2-发件箱]
     * @url /ebay-message/getMessageList
     * @apiRelate app\index\controller\MemberShip::member
     * @apiRelate app\order\controller\Order::account
     */
    public function getMessageListInbox()
    {
        $request = Request::instance();
        $params = $request->param();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $result = $this->service->getMessageList($params, $page, $pageSize);
        return json($result, 200);
    }


    /**
     * @title 查看站内信
     * @author tanbin
     * @method GET
     * @apiParam name:id type:int require:1 desc:ID
     * @url /ebay-message/:id
     * @return \think\Response
     */
    public function read($id)
    {
        if (empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        try {
            $result = $this->service->info($id);
            return json($result, 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 加载更多站内信
     * @author tanbin
     * @method GET
     * @apiParam name:group_id type:int require:1 desc:ID
     * @apiParam name:page type:int require:1 desc:分页
     * @apiParam name:pageSize type:int require:1 desc:每页几条
     * @url /ebay-message/list
     * @return \think\Response
     */
    public function message_list()
    {
        $request = Request::instance();
        $params = $request->param();
        if (!param($params, 'group_id')) {
            return json(['message' => '参数错误'], 400);
        }
        $result = $this->service->more_lists($params);

        return json($result, 200);
    }


    /**
     * @title 删除站内信
     * @author tanbin
     * @method DELETE
     * @apiParam name:id type:int require:1 desc:ID
     * @url /ebay-message/:id
     * @return \think\Response
     */
    public function delete($id)
    {
        $messageModel = new EbayMessageModel();
        $message = $messageModel->find($id, 'id,message_type,is_invalid');
        if ($message['message_type'] != 3) {
            return json(['message' => '该信息不是收件箱中内容，不能删除！'], 400);
        }
        if ($message['is_invalid'] == 1) {
            return json(['message' => '该信息已经删除，请不要重复操作！'], 400);
        }
        //修改状态
        $res = EbayMessageModel::update(['is_invalid' => 1], ['id' => $id]);

        if ($res) {
            return json(['message' => '删除成功'], 200);
        } else {
            return json(['message' => '删除失败'], 500);
        }
    }


    /**
     * @title 获取订单列表
     * @author tanbin
     * @method GET
     * @apiParam name:id type:int require:1 desc:ID
     * @apiParam name:message_id type:string require:1 desc:消息标识号
     * @apiParam name:msg_type type:int require:1 desc:1-收件箱 3-发件箱
     * @remark msg_type的值： 1-收件箱 3-发件箱
     * @url /ebay-message/getOrderList
     * @return \think\Response
     */
    public function getOrderList(Request $request, $id)
    {
        $params = $request->param();
        if (!$id || empty($params['message_id'])) {
            return json(['message' => '参数错误'], 400);
        }

        $res = $this->service->getOrderList($params);

        $result = [
            'data' => $res
        ];

        return json($result);

    }


    /**
     * @title 获取客服对应的账号
     * @author tanbin
     * @method GET
     * @apiFilter app\customerservice\filter\EbayAccountFilter
     * @apiFilter app\customerservice\filter\EbayDepartmentFilter
     * @apiFilter app\customerservice\filter\EbayCustomerFilter
     * @apiReturn show_all:是否显示全部  ； 显示全部是用下拉框显示所有账号。（1-是 0-否）
     * @apiReturn data:账号信息
     * @url /ebay-message/account
     * @return \think\Response
     */
    public function getAccountList(Request $request)
    {
        try {
            $request = Request::instance();
            $params = $request->param();
            $datas = $this->service->CountNoReplayMsg($params);
            $result = [
                'data' => $datas,
                'show_all' => 0,
            ];

            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 发送消息
     * @author tanbin
     * @method POST
     * @apiParam name:order_id type:string desc:系统订单id
     * @apiParam name:account_id type:int desc:卖家账号id
     * @apiParam name:item_id type:string desc:刊登号
     * @apiParam name:buyer_id type:string desc:买家id
     * @apiParam name:text type:string require:1 desc:消息内容
     * @remark 传参： order_id+text 或  account_id+item_id+buyer_id+text
     * @url /ebay-message/send
     * @return \think\Response
     */
    public function send(Request $request)
    {
        $params = $request->param();

        if (!param($params, 'order_id')) {
            if (empty(param($params, 'account_id')) || empty(param($params, 'item_id')) || empty(param($params, 'text')) || empty(param($params, 'buyer_id'))) {
                return json(['message' => '参数错误'], 400);
            }
        }

        try {
            $res = $this->service->sendEbayMsgLockRun($params);
            if ($res['status'] == 1) {
                return json(['message' => "发送成功!"], 200);
            } else {
                return json(['message' => $res['message']], 400);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }

    }

    /**
     * @title 回复消息
     * @author 冬
     * @method POST
     * @apiParam name:message_id type:string require:1 desc:消息标识号
     * @apiParam name:body_text type:string require:1 desc:回复内容
     * @url /ebay-message/replay
     * @return \think\Response
     */
    public function replay(Request $request)
    {
        $params = $request->post();
        try {
            $result = $this->validate($params, [
                'message_id' => 'require|number',
                'body_text|回复内容' => 'require|min:1',
            ]);
            if ($result !== true) {
                return json(['message' => $result], 400);
            }
            $to_queue = empty($params['to_queue'])? 0 : 1;

            $res = $this->service->replaySaveDataLockRun($params['message_id'], $params['body_text'], $to_queue);
            if ($res) {
                if ($to_queue) {
                    return json(['message' => "加入发件队列成功!"], 200);
                } else {
                    return json(['message' => "发送站内信成功!"], 200);
                }
            }
            return json(['message' => "发送失败!"], 400);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 重新发送
     * @author tanbin
     * @method POST
     * @apiParam name:id type:int require:1 desc:ID
     * @apiParam name:message_id type:string require:1 desc:消息标识号
     * @url /ebay-message/resend
     * @return \think\Response
     */
    public function resend(Request $request, $id)
    {
        $params = $request->param();
        $id = $params['id'];
        if (empty($id)) {
            return json(['message' => '参数错误'], 200);
        }

        try {
            //(new UniqueQueuer(EbaySendMessageQueue::class))->push($id);
            $res = $this->service->resendSaveData($id);
            if ($res['status'] === 1) {
                return json(['message' => '发送成功', 'status' => 1]);
            } else {
                return json(['message' => $res['message']], 400);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 批量重新发送
     * @author tanbin
     * @method POST
     * @apiParam name:message_id type:string require:1 desc:消息标识号
     * @url /ebay-message/resend/batch
     * @return \think\Response
     */
    public function resendBatch(Request $request)
    {
        $params = $request->param();
        if (empty($params['message_id'])) {
            return json(['message' => '参数错误'], 200);
        }
        $arr = json_decode($params['message_id'], true);
        $arr = array_merge(array_unique(array_filter($arr)));
        if (empty($arr)) {
            return json(['message' => '参数错误'], 200);
        }

        try {
            $model = new EbayMessageModel();
            $model->update(['send_status' => 2], ['id' => ['in', $arr]]);
            $queue = new UniqueQueuer(EbaySendMessageQueue::class);
            foreach ($arr as $id) {
                $queue->push($id);
            }
            return json([
                'message' => '加入发件队列成功',
                'status' => 1
            ]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 修改状态
     * @author tanbin
     * @method POST
     * @apiParam name:id type:int require:1 desc:ID
     * @apiParam name:state type:int require:1 desc:状态码（忽略-2）
     * @url /ebay-message/status
     * @return \think\Response
     */
    public function changeStatus()
    {
        $request = Request::instance();
        $id = $request->post('id', '');
        $state = $request->post('state', '');
        if (!$id || empty($state)) {
            return json(['message' => '参数有误!'], 200);
        }

        $res = $this->service->ignoreMsg($id, $state);
        if ($res) {
            return json(['message' => '操作成功'], 200);
        }
        return json(['message' => '操作失败'], 500);
    }


    /**
     * @title ebay客服账号列表
     * @author tanbin
     * @method GET
     * @url /ebay-message/getEbayCustomer
     * @return \think\Response
     */
    public function getEbayCustomer()
    {
        $result = $this->service->getEbayCustomer();
        return json($result, 200);
    }


    /**
     * @title 消息优先级消息统计
     * @author tanbin
     * @method GET
     * @url /ebay-message/getLevelCount
     * @return \think\Response
     */
    public function getMessageLevelCount()
    {
        $result = $this->service->getMessageLevelCount();
        return json($result, 200);
    }


    /**
     * @title 优先级消息列表
     * @author tanbin
     * @method GET
     * @url /ebay-message/level
     * @return \think\Response
     */
    public function getMessageLevel()
    {
        $result = $this->service->getMessageLevel();
        return json($result, 200);
    }

    /**
     * @title 修改站内信优先级
     * @author tanbin
     * @method POST
     * @apiParam name:sender type:string require:1 desc:发送人id
     * @apiParam name:item_id type:string require:1 desc:商品号
     * @apiParam name:level_id type:int require:1 desc:等级id
     * @url /ebay-message/updateMessageLevel
     * @return \think\Response
     */
    public function updateMessageLevel()
    {
        $request = Request::instance();

        $groupId = $request->post('group_id', 0);
        $levelId = $request->post('level_id', 0);

        if (empty($groupId)) {
            return json(['message' => '站内信分组参数group_id不能为空'], 400);
        }
        try {
            $ebayMsgGroupModel = new EbayMessageGroupModel();
            $group = $ebayMsgGroupModel->where(['id' => $groupId])->find();
            if (empty($group)) {
                return json(['message' => '站内信分组参数错误，分组不存在'], 400);
            }
            $data_group['prior_level'] = $levelId;
            $data_group['prior_time'] = time();
            $group->save($data_group);

            return json(['message' => '操作成功'], 200);
        } catch (Exception $e) {
            return json(['message' => '操作失败'], 500);
        }
    }


    /**
     * @title 获取来往信息列表
     * @author tanbin
     * @method GET
     * @apiParam name:group_id type:int require:1 desc:分组id
     * @url /ebay-message/getGroupDatas
     * @return \think\Response
     */
    function getGroupDatas()
    {
        $request = Request::instance();
        $group_id = $request->get('group_id', 0);
        if (empty($group_id)) {
            return json(['message' => '参数有误!'], 400);
        }
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 50);
        $where['group_id'] = $group_id;

        $datas = $this->service->lists($where, '', $page, $pageSize);

        $result = [
            'data' => $datas
        ];
        return json($result, 200);
    }


    /**
     * @title 更换站内信客服id
     * @author 冬
     * @method post
     * @url /ebay-message/change-customer
     * @return \think\Response
     */
    public function changeCustomer(Request $request)
    {
        $data = $request->post();
        $result = $this->validate($data, [
            'group_id' => 'require|number',
            'customer_id|客服ID' => 'require|number',
        ]);
        if ($result !== true) {
            throw new JsonErrorException($result);
        }
        try {
            $customer = ChannelUserAccountMap::where([
                'channel_id' => ChannelAccountConst::channel_ebay,
                'customer_id' => $data['customer_id']
            ])->find();
            if (empty($customer)) {
                throw new Exception('参数错误，customer_id,客服ID不正确');
            }
            $groupModel = new EbayMessageGroupModel();
            $group = $groupModel->where(['id' => $data['group_id']])->find();
            if (empty($group)) {
                throw new Exception('参数错误, group_id,分组ID不存确');
            }
            $group->save(['customer_id' => $data['customer_id']]);
            return json(['message' => '更新成功']);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }

    }

    /**
     * @title 更新指定id的站内信标签
     * @url /ebay-message/:id
     * @method put
     * @param Request $request
     * @return \think\response\Json
     */
    public function update(Request $request, $id)
    {
        try {
            if (!$id) {
                return json('无效的邮件id', 400);
            }
            $this->service->updateReceivedMail($id,$request->param());
            return json(['message'=>'更新成功']);
        }catch (\Exception $ex) {
            $msg = $ex->getMessage();
            return json(['message'=>$msg], 400);
        }
    }

    /**
     * @title 站内信添加删除备注
     * @url /ebay-message/remark
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function remark(Request $request)
    {
        try {
            $data = $request->post();
            $result = $this->validate($data, [
                'id|站内信ID' => 'require|number',
                'remark|备注' => 'length:0,80'
            ]);
            if ($result !== true) {
                return json(['message' => $result], 400);
            }
            $result = $this->service->messageRemark($data);
            return json($result);
        }catch (\Exception $ex) {
            $msg = $ex->getMessage();
            return json(['message'=>$msg], 400);
        }
    }

    /**
     * @title 测试队列接收运行
     * @url /ebay-message/queue
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function queue(Request $request)
    {
        try {
            $data = $request->post();
            $result = $this->validate($data, [
                'name|队列完整名' => 'require',
                'params|参数' => 'require',
                'postman|postman运行' => 'in:0,1',
                'timer' => 'number',
            ]);
            if ($result !== true) {
                return json(['message' => $result], 400);
            }
            $start = microtime(true);
            $postman = !empty($data['postman']);
            $this->service->pushQueue($data['name'], $data['params'], $postman, $data['timer'] ?? '');

            $time = microtime(true) - $start;
            return json(['message' => '执行完成,时间：'. $time]);
        }catch (\Exception $ex) {
            return json(['message'=>$ex->getMessage()], 400);
        }
    }

    /**
     * @title 测试servers
     * @url /ebay-message/server
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function server(Request $request)
    {
        try {
            $data = $request->post();
            $result = $this->validate($data, [
                'name|服务完整名' => 'require',
                'method|方法' => 'require',
                'result|结果' => 'in:1,2',
                'p1|参数1' => 'length:0,500',
                'p2|参数2' => 'length:0,500',
                'p3|参数3' => 'length:0,500',
                'p4|参数4' => 'length:0,500',
                'p5|参数5' => 'length:0,500',
                'p6|参数6' => 'length:0,500',
            ]);
            if ($result !== true) {
                return json(['message' => $result], 400);
            }
            $start = microtime(true);
            $result = $this->service->testMethod($data);

            if ($data['result'] == 1) {
                $time = microtime(true) - $start;
                return json(['message' => '执行完成,时间：'. $time]);
            } else {
                return json($result);
            }
        }catch (\Exception $ex) {
            return json(['message'=>$ex->getMessage()], 400);
        }
    }
}