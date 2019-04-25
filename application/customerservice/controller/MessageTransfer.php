<?php

namespace app\customerservice\controller;

use app\customerservice\service\MessageTransferService;
use think\Exception;
use think\Request;
use app\common\controller\Base;

/**
 * @module 客服管理
 * @title 站内信转让
 * @url /message-transfer
 */
class MessageTransfer extends Base
{

    protected $service;

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * @title 站内信待处理列表
     * @author 冬
     * @method GET
     * @url /message-transfer
     * @apiRelate app\index\controller\MemberShip::member
     */
    public function index(Request $request)
    {
        try {
            $params = $request->get();
            $result = $this->validate($params, [
                'channel_id|渠道ID' => 'number',
                'customer_id|客服ID' => 'number',
//                'account_id|帐号ID' => 'number',
                'msg_type|站内信类型' => 'number',
            ]);
            if ($result !== true) {
                throw new Exception($result);
            }
            $service = new MessageTransferService();
            $result = $service->lists($params);

            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 帐号未处理信息条数；
     * @author 冬
     * @method GET
     * @url /message-transfer/account-total
     * @return \think\Response
     */
    public function accountMessageTotal(Request $request)
    {
        try {
            $params = $request->get();
            $result = $this->validate($params, [
                'channel_id|渠道ID' => 'number',
                'customer_id|客服ID' => 'number',
                //'account_id|帐号ID' => 'number'
            ]);
            if ($result !== true) {
                throw new Exception($result);
            }
            $service = new MessageTransferService();
            $result = $service->accountMessageTotal($params);

            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage(). '|'. $e->getFile(). '|'. $e->getLine()], 400);
        }
    }


    /**
     * @title 转发站内信
     * @author 冬
     * @method post
     * @url /message-transfer/transfer
     * @return \think\Response
     */
    public function transfer(Request $request)
    {
        try {
            $params = $request->post();
            $result = $this->validate($params, [
                'channel_id|渠道ID' => 'require|number',
                'from_customer_id|转出客服ID' => 'require|number',
                'account_id|帐号ID' => 'require|number',
                'to_customer_id|接收客服ID' => 'require|number',
                'account_total|帐号现有数量' => 'require|number',
                'total|转发的数量' => 'require|number',
                'msg_type|站内信类型' => 'number',
                'remark|备注' => 'min:0',
            ]);
            if ($result !== true) {
                throw new Exception($result);
            }
            $service = new MessageTransferService();
            $result = $service->transfer($params);
            if ($result) {
                return json(['message' => '转发成功']);
            } else {
                return json(['message' => '转发失败'], 400);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 转派记录
     * @author 冬
     * @method get
     * @url /message-transfer/record
     * @apiRelate app\customerservice\controller\MessageTransfer::creator
     * @return \think\Response
     */
    public function record(Request $request)
    {
        try {
            $params = $request->get();
            $result = $this->validate($params, [
                'channel_id|渠道ID' => 'number',
                'from_customer_id|转出客服ID' => 'number',
                'to_customer_id|接收客服ID' => 'number',
                //'account_id|帐号ID' => 'number',
                'time_start|开始时间' => 'date',
                'time_end|结束时间' => 'date',
            ]);
            if ($result !== true) {
                throw new Exception($result);
            }
            $service = new MessageTransferService();
            $result = $service->record($params);
            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 转派操作人
     * @author 冬
     * @method get
     * @url /message-transfer/creator
     * @return \think\Response
     */
    public function creator(Request $request)
    {
        try {
            $params = $request->get();
            $result = $this->validate($params, [
                'channel_id|渠道ID' => 'number'
            ]);
            if ($result !== true) {
                throw new Exception($result);
            }
            $service = new MessageTransferService();
            $result = $service->creator($params);
            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }
}
