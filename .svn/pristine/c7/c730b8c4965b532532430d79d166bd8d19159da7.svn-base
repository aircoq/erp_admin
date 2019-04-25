<?php

namespace app\customerservice\controller;

use think\Exception;
use think\Request;
use app\common\controller\Base;
use app\customerservice\service\AmazonFeedbackHelp as AmazonFeedbackHelpService;
use app\common\model\amazon\AmazonFeedback as AmazonFeedbackModel;
use app\index\service\DeveloperService;
use app\common\cache\Cache;


/**
 * @module 客服管理
 * @title Amazon中差评
 * @author yangweiqan f5escenter@163.com
 * @url /amazonFeedBack
 */
class AmazonFeedback extends Base
{


    /**
     * @title 亚马逊评价
     * @author tanbin
     * @method GET
     * @apiFilter app\customerservice\filter\AmazonAccountFilter
     * @apiFilter app\customerservice\filter\AmazonDepartmentFilter
     * @apiParam name:status type:int desc:回评状态( 1-已回评  2-未回评  3-回评中  4-回评失败 )
     * @apiParam name:comment_type type:int desc:评价状态( 1-好评 2-中评 3-差评 99-等待买家评价 )
     * @url /amazon/getFeedbacks
     */
    public function index()
    {
        $request = Request::instance();
        $service = new AmazonFeedbackHelpService();

        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $result = $service->lists($request->param(), $page, $pageSize);
        return json($result, 200);
    }


    /**
     * @title 中差评原因处理(提交中差评原因)
     * @author yangweiquan
     * @method POST
     * @apiParam name:status type:int desc:回评状态( 1-已回评  2-未回评  3-回评中  4-回评失败 )
     * @apiParam name:comment_type type:int desc:评价状态( 1-好评 2-中评 3-差评 99-等待买家评价 )
     * @url /amazon/submitFeedbackReason
     */
    public function submitFeedbackReason()
    {

        $negative_neutral_reason_list = ["未收到货物", "描述不符", "质量问题", "发货错误", "关税问题", "运输途中损坏", "运输时间过长", "客户个人原因", "停产缺货", "延迟发货", "无理由", "其他"];

        $request = Request::instance();
        $params = $request->param();

        if (!isset($params['feedback_id']) || empty($params['feedback_id'])) {
            return json(['message' => '(差评ID)参数错误'], 400);
        }

        if (!isset($params['is_need_re_dispatched']) || !in_array($params['is_need_re_dispatched'], [0, 1])) {
            return json(['message' => '(是否需要重发订单)参数错误'], 400);
        }

        if (!isset($params['negative_neutral_reason']) || !in_array($params['negative_neutral_reason'], $negative_neutral_reason_list)) {
            return json(['message' => '(差评原因)参数错误'], 400);
        }

        if (!isset($params['negative_neutral_remark']) || empty($params['negative_neutral_remark'])) {
            $params['negative_neutral_remark'] = '';
            //return json(['message' => '(备注)参数错误'], 400);
        }

        $service = new    AmazonFeedbackHelpService();

        try {
            $service->submitFeedbackReasonProcessing($params);
            return json(['message' => '提交中差评原因成功'], 200);

        } catch (Exception $e) {
            // var_dump($e->getMessage());
            return json(['message' => '提交中差评原因失败'], 500);
        }
    }


    /**
     * @title 中差评原因处理情况()
     * @author yangweiquan
     * @method POST
     * @url /amazon/submitFeedbackDealingStatus
     */
    public function submitFeedbackDealingStatus()
    {

        $request = Request::instance();
        $params = $request->param();

        if (!isset($params['feedback_id']) || empty($params['feedback_id'])) {
            return json(['message' => '(差评ID)参数错误'], 400);
        }

        $modify_status_str_list = [0 => "未处理", 2 => "等待对方处理", 1 => "处理完成", 3 => "已过期"];

        if (!isset($params['modify_status_id']) || !in_array($params['modify_status_id'], [0, 1, 2, 3])) {
            return json(['message' => '(处理状态)参数错误'], 400);
        }

        if ($params['modify_status_id'] == 1) {//处理完成
            if (!isset($params['is_remove_negative_feedback']) || !in_array($params['is_remove_negative_feedback'], [0, 1])) {
                return json(['message' => '(该中差评是否已经被移除)参数错误'], 400);
            }

        }

        $service = new    AmazonFeedbackHelpService();

        try {
            $service->submitFeedbackDealingStatusProcessing($params);
            return json(['message' => '提交中差评处理状态成功'], 200);

        } catch (Exception $e) {
            //var_dump($e->getMessage());
            return json(['message' => '提交中差评处理状态失败'], 500);
        }


    }

    /**  客服列表 yangweiquan 2017-05-22
     * @title 客服列表
     * @method GET
     * @url /amazon/getCustomerServiceOfficers
     */
    public function customerServiceOfficers()
    {
        //http://172.20.1.241:8080/user/customer/staffs?content=&test

        $request = Request::instance();
        $params = $request->param();

        $data = Cache::store('User')->getCustomerAccount(1, 1);

        if ($data) {

            if (isset($params['s_cs_name']) || !($params['s_cs_name'])) {
                $temp_data = [];
                foreach ($data as $row) {

                }

            }
        }

        $result = [
            'data' => $data,
            'count' => count($data),
        ];
        return json($result, 200);


    }


}
