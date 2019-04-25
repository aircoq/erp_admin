<?php

namespace app\customerservice\controller;
use app\common\controller\Base;
use app\common\service\ChannelAccountConst;
use app\common\service\Common as CommonService;
use app\customerservice\service\AfterSaleRuleService;
use think\Request;

/**
 * @module 客服管理
 * @title 售后单规则
 * @author hecheng
 * @url /after-sale-rules
 * @package app\customerservice\controller
 */
class AfterSaleRule extends Base
{
    protected $afterSaleRuleService;

    protected function init()
    {
        if (is_null($this->afterSaleRuleService)) {
            $this->afterSaleRuleService = new AfterSaleRuleService();
        }
    }

    /**
     * @title 售后单规则列表
     * @url /after-sale-rules
     * @method GET
     * @return \think\response\Json
     */
    public function index()
    {
        $request = Request::instance();
        $params = $request->param();
        $where = [];
        //平台
        if (isset($params['channel_id']) && $params['channel_id'] != '') {
            $where['channel_id'] = ['=', $params['channel_id']];
        } else {
            $where['channel_id'] = ['=', 0];
        }
        //状态
        if (isset($params['status']) && $params['status'] != '') {
            $where['status'] = ['=', $params['status']];
        }
        //规则名称
        if (isset($params['title']) && !empty($params['title'])) {
            $where['title'] = ['like', '%' . $params['title'] . '%'];
        }
        //创建时间
        if (isset($params['snDate'])) {
            $params['date_b'] = isset($params['date_b']) ? trim($params['date_b']) : 0;
            $params['date_e'] = isset($params['date_e']) ? trim($params['date_e']) : 0;
            switch (trim($params['snDate'])) {
                case 'create_time':
                    $condition = timeCondition($params['date_b'], $params['date_e']);
                    if (!is_array($condition)) {
                        return json(['message' => '日期格式错误'], 400);
                    }
                    if (!empty($condition)) {
                        $where['create_time'] = $condition;
                    }
                    break;
                default:
                    break;
            }
        } else {
            $params['date_b'] = isset($params['date_b']) ? trim($params['date_b']) : 0;
            $params['date_e'] = isset($params['date_e']) ? trim($params['date_e']) : 0;
            $condition = timeCondition($params['date_b'], $params['date_e']);
            if (!is_array($condition)) {
                return json(['message' => '日期格式错误'], 400);
            }
            if (!empty($condition)) {
                $where['create_time'] = $condition;
            }
        }
        $result = $this->afterSaleRuleService->afterSaleRuleList($where);
        return json($result, 200);
    }

    /**
     * @title 规则详情
     * @method GET
     * @url /after-sale-rules/:id
     * @param $id
     * @return \think\response\Json
     */
    public function read($id)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $result = $this->afterSaleRuleService->info($id);
        return json($result, 200);
    }

    /**
     * @title 新增订单
     * @method POST
     * @url /after-sale-rules
     * @param Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        $afterSaleRuleSet['title'] = trim($request->post('name', ''));
        $afterSaleRuleSet['channel_id'] = $request->post('channel_id', 0);
        $afterSaleRuleSet['action_value'] = $request->post('action_value', '');
        $afterSaleRuleSet['status'] = $request->post('status', 0);
        $afterSaleRules = $request->post('rules', 0);
        if (empty($afterSaleRules)) {
            return json(['message' => '请选择一条规则条件'], 400);
        }

        $afterSaleRuleSet['create_time'] = time();
        $afterSaleRuleSet['update_time'] = time();
        $afterSaleRuleSet['sort'] = 9999;
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $afterSaleRuleSet['create_name'] = $afterSaleRuleSet['operator'] = $user['realname'];
            $afterSaleRuleSet['create_id'] = $afterSaleRuleSet['operator_id'] = $user['user_id'];
        }
        $data = $this->afterSaleRuleService->save($afterSaleRuleSet, $afterSaleRules);
        return json(['message' => '新增成功', 'data' => $data]);
    }

    /**
     * @title 更新规则
     * @method PUT
     * @url /after-sale-rules/:id(\d+)
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     */
    public function update(Request $request, $id)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $afterSaleRuleSet['title'] = trim($request->put('name', ''));
        $afterSaleRuleSet['channel_id'] = $request->put('channel_id', 0);
        $afterSaleRuleSet['status'] = $request->put('status', 0);
        $afterSaleRuleSet['action_value'] = $request->put('action_value', '');
        $afterSaleRules = $request->put('rules', 0);
        if (empty($afterSaleRules)) {
            return json(['message' => '请选择一条规则条件'], 400);
        }

        $afterSaleRuleSet['update_time'] = time();
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $afterSaleRuleSet['operator'] = $user['realname'];
            $afterSaleRuleSet['operator_id'] = $user['user_id'];
        }
        $data = $this->afterSaleRuleService->update($id, $afterSaleRuleSet, $afterSaleRules);
        return json(['message' => '更改成功', 'data' => $data]);
    }

    /**
     * @title 删除规则
     * @method delete
     * @url /after-sale-rules/:id(\d+)
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $this->afterSaleRuleService->delete($id);
        return json(['message' => '删除成功']);
    }

    /**
     * @title 修改规则状态
     * @method post
     * @url /after-sale-rules/status
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        $request = Request::instance();
        $params = $request->param();
        if (!isset($params['status']) || !isset($params['id']) || !is_numeric($params['id'])) {
            return json(['message' => '参数错误'], 400);
        }
        $data['id'] = $params['id'];
        $data['update_time'] = time();
        $data['status'] = $params['status'];
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['operator'] = $user['realname'];
            $data['operator_id'] = $user['user_id'];
        }
        $this->afterSaleRuleService->status($data);
        return json(['message' => '操作成功']);
    }

    /**
     * @title 保存排序值
     * @url /after-sale-rules/sort
     * @method post
     * @return \think\response\Json
     * @throws Exception
     */
    public function changeSort()
    {
        $request = Request::instance();
        $params = $request->param();
        if (!isset($params['sort'])) {
            return json(['message' => '参数错误'], 400);
        }
        $sort = json_decode($params['sort'], true);
        $this->afterSaleRuleService->sort($sort);
        return json(['message' => '操作成功']);
    }

    /**
     * @title 获取售后单规则
     * @method GET
     * @url /after-sale-rules/rule-item
     * @return \think\response\Json
     */
    public function ruleItem()
    {
        $result = $this->afterSaleRuleService->item();
        return json($result, 200);
    }

    /**
     * @title 平台列表
     * @method get
     * @url /after-sale-rules/channel
     * @return \think\response\Json
     */
    public function channelList()
    {
        $channel = [
            0 => [
                'id' => 0,
                'name' => '通用'
            ],
            1 => [
                'id' => ChannelAccountConst::channel_ebay,
                'name' => 'Ebay'
            ],
            2 => [
                'id' => ChannelAccountConst::channel_aliExpress,
                'name' => '速卖通'
            ],
            3 => [
                'id' => ChannelAccountConst::channel_Shopee,
                'name' => 'Shopee'
            ]
        ];
        return json($channel, 200);
    }
}