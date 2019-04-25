<?php
namespace app\publish\controller;

use app\common\controller\Base;
use app\publish\service\PricingRuleService;
use think\Request;
use app\common\service\Common as CommonService;

/**
 * @module 基础设置
 * @title 定价规则
 * @author phill
 * @url /pricing-rules
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/7/18
 * Time: 10:41
 */
class PricingRule extends Base
{
    protected $pricingRuleService = null;

    protected function init()
    {
        if (is_null($this->pricingRuleService)) {
            $this->pricingRuleService = new PricingRuleService();
        }
    }

    /** 列表
     * @return \think\response\Json
     * @apiRelate app\index\controller\User::index
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\order\controller\Order::channel
     */
    public function index()
    {
        $request = Request::instance();
        $params = $request->param();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 50);
        $where = [];
        if (isset($params['status']) && $params['status'] != '') {
            $where['status'] = ['=', $params['status']];
        }
        if (isset($params['channel_id']) && !empty($params['channel_id'])) {
            $where['channel_id'] = ['=', $params['channel_id']];
        }
        if (isset($params['site_code']) && !empty($params['site_code'])) {
            $where['site'] = ['=', $params['site_code']];
        }
        if (isset($params['title']) && !empty($params['title'])) {
            $where['title'] = ['like', '%' . $params['title'] . '%'];
        }
        if (isset($params['creator_id']) && !empty($params['creator_id'])) {
            $where['operator_id'] = ['=',$params['creator_id']];
        }
        //有效期
        if (!empty($params['date_b'])) {
            $is_date = strtotime($params['date_b']) ? strtotime($params['date_b']) : false;
            if (!$is_date) {
                return json(['message' => '日期格式错误'], 400);
            }
            $params['date_b'] = strtotime($params['date_b']);
        } else {
            $params['date_b'] = 0;
        }
        $where['start_time'] = ['>=', $params['date_b']];
        if (!empty($params['date_e'])) {
            $is_date = strtotime($params['date_e']) ? strtotime($params['date_e']) : false;
            if (!$is_date) {
                return json(['message' => '日期格式错误'], 400);
            }
            $params['date_e'] = strtotime($params['date_e'] . " 23:59:59");
        } else {
            $params['date_e'] = 10413763200;
        }
        $where['end_time'] = ['<=', $params['date_e']];
        $result = $this->pricingRuleService->ruleList($where,$page,$pageSize);
        return json($result);
    }

    /** 获取定价规则信息
     * @param $id
     * @return \think\response\Json
     */
    public function read($id)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $result = $this->pricingRuleService->info($id);
        return json($result, 200);
    }

    /** 新增定价规则
     * @param Request $request
     * @return \think\response\Json
     * @apiRelate app\goods\controller\GoodsSkuMap::query
     * @apiRelate app\order\controller\Rule::resources
     * @apiRelate app\index\controller\Currency::dictionary
     */
    public function save(Request $request)
    {
        $ruleSet['title'] = $request->post('title', '');
        $ruleSet['action_value'] = $request->post('action_value', '');
        $ruleSet['channel_id'] = $request->post('channel_id', 0);
        $ruleSet['status'] = $request->post('status', 0);
        $ruleSet['start_time'] = $request->post('start_time', 0);
        $ruleSet['end_time'] = $request->post('end_time', 0);
        $site = $request->post('sites', 0);
        if(!empty($site)){
            $site = json_decode($site,true);
            $ruleSet['site'] = implode(',',$site);
        }
        $rules = $request->post('rules', 0);
        if (empty($rules)) {
            return json(['message' => '请选择一条规则条件'], 400);
        }
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $ruleSet['operator'] = $user['realname'];
            $ruleSet['operator_id'] = $user['user_id'];
        }
        $validateRuleSet = validate('PriceRuleSet');
        if (!$validateRuleSet->check($ruleSet)) {
            return json(['message' => $validateRuleSet->getError()], 400);
        }
        $ruleSet['create_time'] = time();
        $ruleSet['update_time'] = time();
        $ruleSet['sort'] = 9999;
        $this->pricingRuleService->add($ruleSet, $rules);
        return json(['message' => '新增成功']);
    }

    /** 更新定价规则
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     * @apiRelate app\goods\controller\GoodsSkuMap::query
     * @apiRelate app\index\controller\Currency::dictionary
     * @apiRelate app\order\controller\Rule::resources
     */
    public function update(Request $request, $id)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $ruleSet['title'] = $request->put('title', '');
        $ruleSet['action_value'] = $request->put('action_value', '');
        $ruleSet['channel_id'] = $request->put('channel_id', 0);
        $ruleSet['status'] = $request->put('status', 0);
        $ruleSet['start_time'] = $request->put('start_time', 0);
        $ruleSet['end_time'] = $request->put('end_time', 0);
        $site = $request->put('sites', 0);
        if(!empty($site)){
            $site = json_decode($site,true);
            $ruleSet['site'] = implode(',',$site);
        }
        $rules = $request->put('rules', 0);
        if (empty($rules)) {
            return json(['message' => '请选择一条规则条件'], 400);
        }
        $ruleSet['update_time'] = time();
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $ruleSet['operator'] = $user['realname'];
            $ruleSet['operator_id'] = $user['user_id'];
        }
        $this->pricingRuleService->update($id, $ruleSet, $rules);
        return json(['message' => '更新成功']);
    }

    /** 删除定价规则
     * @param $id
     * @return \think\response\Json
     */
    public function delete($id)
    {
        if (!is_numeric($id) || empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $this->pricingRuleService->delete($id);
        return json(['message' => '删除成功']);
    }

    /**
     * @title 保存排序值
     * @url sort
     * @method post
     * @return \think\response\Json
     */
    public function sort()
    {
        $request = Request::instance();
        $params = $request->param();
        if (!isset($params['sort'])) {
            return json(['message' => '参数错误'], 400);
        }
        $sort = json_decode($params['sort'], true);
        $this->pricingRuleService->sort($sort);
        return json(['message' => '操作成功']);
    }

    /**
     * @title 规则复制
     * @url copy
     * @method post
     * @return \think\response\Json
     */
    public function copy()
    {
        $request = Request::instance();
        $params = $request->param();
        if (!isset($params['id']) || !is_numeric($params['id']) || !isset($params['title']) || empty($params['title'])) {
            return json(['message' => '参数错误'], 400);
        }
        $ruleSetInfoNew = [];
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $ruleSetInfoNew['operator'] = $user['realname'];
            $ruleSetInfoNew['operator_id'] = $user['user_id'];
        }
        $rule_id = $this->pricingRuleService->copy($params, $ruleSetInfoNew);
        return json(['message' => '复制成功','id' => $rule_id]);
    }

    /**
     * @title 更改规则状态
     * @url :id/status/:value
     * @method post
     * @return \think\response\Json
     */
    public function status()
    {
        $request = Request::instance();
        $params = $request->param();
        $data['id'] = $params['id'];
        $data['update_time'] = time();
        $data['status'] = $params['value'];
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['operator'] = $user['realname'];
            $data['operator_id'] = $user['user_id'];
        }
        $this->pricingRuleService->status($data);
        return json(['message' => '操作成功']);
    }

    /**
     * @title 获取可选条件
     * @url items
     * @return \think\response\Json
     */
    public function item()
    {
        $result = $this->pricingRuleService->item();
        return json($result,200);
    }

    /**
     * @title 获取默认设置
     * @url default
     * @return \think\response\Json
     */
    public function defaultRule()
    {
        $result = $this->pricingRuleService->defaultRule();
        return json($result,200);
    }

    /**
     * @title 匹配规则计算销售价
     * @url calculate
     * @method post
     * @return \think\response\Json
     */
    public function calculate()
    {
        $request = Request::instance();
        $params = $request->param();
        if (!isset($params['sku_id']) || !isset($params['account_id']) || !isset($params['channel_id'])
            || !$params['sku_id']  || !$params['channel_id']) {
            return json(['message' => '参数错误'], 400);
        }
        $publishInfo['channel_id'] = $params['channel_id'];
        $detail = json_decode($params['sku_id'],true);
        $publishInfo['channel_account_id'] = $params['account_id'];
        $publishInfo['warehouse_id'] = isset($params['warehouse_id']) ? $params['warehouse_id'] : 0;
        $publishInfo['site_code'] = isset($params['site_code']) ? $params['site_code'] : 0;
        $publishInfo['category_id'] = isset($params['category_id']) ? $params['category_id'] : 0;
        $publishInfo['gross_profit'] = isset($params['gross_profit']) ? $params['gross_profit'] : false;
        $result = $this->pricingRuleService->calculate($publishInfo,$detail);
        return json($result);
    }


    /**
     * @title 获取定价规则资源
     * @url /publish-rules/resources
     * @method post
     * @return \think\response\Json
     */
    public function resources()
    {
        $request = Request::instance();
        $code = $request->post('code', 0);
        if (empty($code)) {
            return json(['message' => '参数错误'], 400);
        }

        $result = $this->pricingRuleService::getResource($code);
        return json($result, 200);
    }
}