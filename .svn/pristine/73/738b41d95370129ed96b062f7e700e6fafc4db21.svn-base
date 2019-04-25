<?php

namespace app\customerservice\controller;

use app\customerservice\service\PaypalDisputeService;
use app\customerservice\validate\PaypalDisputeValidate;
use think\Exception;
use think\Request;
use app\common\controller\Base;


/**
 * @module 客服管理
 * @title Paypal纠纷
 * @url /paypal-dispute
 */
class PaypalDispute extends Base
{

    private $server = null;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        is_null($this->server) && $this->server = new PaypalDisputeService();
    }


    /**
     * @title paypal纠纷列表
     * @author 冬
     * @method GET
     * @url /paypal-dispute
     * @apiFilter app\customerservice\filter\PaypalAccountFilter
     * @apiRelate app\customerservice\controller\PaypalDispute::update&statistics&accounts&getAddress&saveAddress
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\system\controller\Country::index
     * @apiRelate app\order\controller\VirtualOrder::country
     * @apiRelate app\purchase\controller\Supplier::info
     */
    public function index(Request $request)
    {
        $params = $request->get();
        try {
            $list = $this->server->getLists($params);
            return json($list);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title paypal纠纷统计
     * @author 冬
     * @method get
     * @url /paypal-dispute/statistics
     */
    public function statistics()
    {
        try {
            $list = $this->server->statistics();
            return json($list);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title paypal更新纠纷
     * @author 冬
     * @method put
     * @url /paypal-dispute/:id
     */
    public function update($id)
    {
        try {
            $this->server->update($id);
            return json(['message' => '更新完成']);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title paypal帐号筛选
     * @author 冬
     * @method get
     * @url /paypal-dispute/accounts
     */
    public function accounts()
    {
        try {
            $accounts = $this->server->accounts();
            return json(['data' => $accounts]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 查看paypal纠纷详情
     * @author 冬
     * @method get
     * @url /paypal-dispute/:id/read
     */
    public function read($id)
    {
        try {
            $data = $this->server->detail($id);
            return json(['data' => $data]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 处理paypal纠纷详情
     * @author 冬
     * @method get
     * @url /paypal-dispute/:id
     */
    public function detail($id)
    {
        try {
            $data = $this->server->detail($id);
            return json(['data' => $data]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title paypal处理纠纷
     * @author 冬
     * @method post
     * @url /paypal-dispute/:type
     */
    public function operate(Request $request, $type)
    {
        if (empty($type) || !in_array($type, ['send_message', 'accept_claim', 'make_offer', 'provide_evidence', 'appeal', 'acknowledge_return_item'])) {
            return json(['message' => '未声明处理纠纷的类型'], 400);
        }

        //检查post数据
        $data = $request->post();
        $validate = new PaypalDisputeValidate();
        if (!$validate->scene($type)->check($data)) {
            return json(['message' => $validate->getError()], 400);
        }

        try {
            $result = $this->server->saveOperateData($data, $type);
            $msg = $this->server->operatInQueue ? '加入队列' : '操作';
            if ($result) {
                return json(['message' => $msg. '成功']);
            } else {
                return json(['message' => $msg. '失败'], 400);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title paypal纠纷添加新地址
     * @author 冬
     * @method POST
     * @url /paypal-dispute/address
     */
    public function saveAddress(Request $request)
    {
        $data = $request->post();
        $result = $this->validate($data, [
            'id|地址ID' => 'number',
            'account_id|paypal帐号ID' => 'require|egt:1',
            'country_code|国家' => 'require|length:2',
            'province|省' => 'require',
            'city|市' => 'require',
            'area|区' => 'require',
            'address_line_1|地址1' => 'require',
            'address_line_2|地址2' => 'require',
            'postal_code|邮编' => 'require|number'
        ]);

        if ($result !== true) {
            return json(['message' => $result], 400);
        }

        try {
            $this->server->saveAddress($data);
            return json(['message' => '地址保存成功']);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title paypal纠纷拿取地址
     * @author 冬
     * @method get
     * @url /paypal-dispute/:aid/address
     */
    public function getAddress($aid)
    {
        try {
            $data = $this->server->address($aid);
            return json(['data' => $data]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title paypal纠纷拿给客户付款订单
     * @author 冬
     * @method get
     * @url /paypal-dispute/:id/refund_order
     */
    public function refundOrder($id, Request $request)
    {
        try {
            $data = $request->get();
            $data = $this->server->refundOrder($id, $data);
            return json(['data' => $data]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title paypal纠纷物流选取；
     * @author 冬
     * @method get
     * @url /paypal-dispute/carriers
     */
    public function carriers()
    {
        try {
            $data = $this->server->carriers();
            return json(['data' => $data]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title paypal纠纷同意赔偿原因；
     * @author 冬
     * @method get
     * @url /paypal-dispute/accept_reason
     */
    public function acceptReason()
    {
        try {
            $data = $this->server->acceptReason();
            return json(['data' => $data]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }
}
