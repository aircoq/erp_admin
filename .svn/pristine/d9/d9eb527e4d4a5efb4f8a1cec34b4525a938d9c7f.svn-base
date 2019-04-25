<?php

namespace app\customerservice\controller;

use app\common\exception\JsonErrorException;
use think\Controller;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use app\customerservice\service\EbayFeedbackHelp as EbayFeedbackHelpService;
use app\common\model\ebay\EbayFeedback as EbayFeedbackModel;
use app\index\service\DeveloperService;
use app\common\cache\Cache;
use app\common\model\Order;


/**
 * @module 客服管理
 * @title Ebay评价
 */
class EbayFeedback extends Base
{

    /**
     * @title 评价列表
     * @author tanbin
     * @method GET
     * @apiParam name:status type:int desc:回评状态( 1-已回评  2-未回评  3-回评中  4-回评失败 )
     * @apiParam name:comment_type type:int desc:评价状态( 1-好评 2-中评 3-差评 99-等待买家评价 )
     * @apiFilter app\customerservice\filter\EbayAccountFilter
     * @apiFilter app\customerservice\filter\EbayDepartmentFilter
     * @url /ebay-feedback
     * @apiRelate app\index\controller\MemberShip::member
     */
    public function index()
    {
        try {
            $request = Request::instance();
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 10);

            $params = $request->param();
            $help = new EbayFeedbackHelpService();
            $result = $help->lists($params, $page, $pageSize);

            return json($result, 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 查看评价
     * @author tanbin
     * @method GET
     * @apiParam name:id type:int require:1 desc:ID
     * @url /ebay-feedback/:id
     */
    public function read($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        try {
            $service = new EbayFeedbackHelpService();
            $feedback = $service->feedbackDetail($id);

            $result = $service->getOrderinfo($feedback['order_id'], 1);
            $result['feedback'] = $feedback;

            return json($result, 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 编辑评价
     * @author tanbin
     * @method GET
     * @apiParam name:id type:int require:1 desc:ID
     * @url /ebay-feedback/:id/edit
     * @return \think\Response
     */
    public function edit($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $result = EbayFeedbackModel::field('id,account_id,transaction_id,item_id,handel_status')->where(['id' => $id])->find();
        $result = empty($result) ? [] : $result;

        return json($result, 200);
    }


    /**
     * @title 评价/回评
     * @author tanbin
     * @method POST
     * @apiParam name:transaction_id type:string require:1 desc:交易号
     * @apiParam name:text type:string require:1 desc:评论内容
     * @url /ebay-feedback/comment
     * @return \think\Response
     */
    public function leaveComment()
    {
        $service = new EbayFeedbackHelpService();
        $request = Request::instance();
        $params = $request->param();
        if (empty($params['id']) && empty($params['transaction_id']) && empty($params['order_id'])) {
            return json(['message' => '参数错误'], 400);
        }

        $data = [
            'id' => param($params, 'id'),
            'text' => param($params, 'text'),
            'transaction_id' => param($params, 'transaction_id'),
            'order_id' => param($params, 'order_id')
        ];

        try {
            $result = $service->leaveFeedbackLockRun($data);
            if ($result) {
                return json(['message' => '回评成功'], 200);
            } else {
                return json(['message' => '回评失败'], 400);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * @title 批量评价
     * @author tanbin
     * @method POST
     * @apiParam name:ids type:string require:1 desc:ids（1,2,3）
     * @apiParam name:text type:string require:1 desc:评论内容
     * @url /ebay-feedback/batch/comment
     * @return \think\Response
     */
    function batchLeaveComment()
    {

        $service = new EbayFeedbackHelpService();
        $request = Request::instance();
        $params = $request->param();
        if (empty($params['ids']) || empty($params['text'])) {
            return json(['message' => '参数错误'], 400);
        }

        try {
            $service->batchleaveFeedback($params['ids'], $params['text']);
            return json(['message' => '操作成功,将在后台进行自动回评'], 200);

        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * @title 重新发送评价
     * @author tanbin
     * @method POST
     * @apiParam name:id type:int require:1 desc:id
     * @url /ebay-feedback/repeat
     * @return \think\Response
     */
    public function repeatComment()
    {
        $service = new EbayFeedbackHelpService();
        $request = Request::instance();
        $params = $request->param();
        $id = param($params, 'id');

        try {

            $res = $service->repeatFeedbackLockRun($id);
            if ($res) {
                return json(['message' => '评价成功'], 200);
            } else {
                return json(['message' => '评价失败'], 400);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 500);
        }

    }


    /**
     * @title 追评
     * @author tanbin
     * @method POST
     * @apiParam name:id type:int require:1 desc:id
     * @apiParam name:text type:string require:1 desc:评论内容
     * @url /ebay-feedback/respond
     * @return \think\Response
     */
    public function respondComment()
    {
        $request = Request::instance();
        $params = $request->param();
        $result = $this->validate($params, [
            'id|feedback自增ID' => 'require|min:1',
            'text|追评内容' => 'require|min:1',
        ]);
        if ($result !== true) {
            throw new JsonErrorException($result);
        }

        try {
            $service = new EbayFeedbackHelpService();
            $res = $service->followUpFeedbackLockRun($params);
            if ($res) {
                return json(['message' => '追评成功'], 200);
            } else {
                return json(['message' => '追评失败'], 400);
            }

        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * @title 跟进
     * @author tanbin
     * @method POST
     * @apiParam name:id type:int require:1 desc:id
     * @apiParam name:text type:string require:1 desc:评论内容
     * @url /ebay-feedback/sendMsg
     * @return \think\Response
     */
    public function sendMessage()
    {
        $service = new EbayFeedbackHelpService();
        $request = Request::instance();
        $params = $request->param();

        try {
            $data = [
                'id' => param($params, 'id'),
                'text' => param($params, 'text')
            ];

            $res = $service->sendMessageLockRun($data);
            if ($res !== false) {
                return json($res);
            }
            return json(['message' => '发送失败'], 400);

        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * @title 获取评价模板内容
     * @author tanbin
     * @method GET
     * @apiParam name:transaction_id type:string require:1 desc:交易号
     * @apiParam name:template_id type:int require:1 desc:模板id
     * @apiParam name:is_random type:int  desc:是否随机模板（1-是，0-否）
     * @url /ebay-feedback/tplContent
     * @return \think\Response
     */
    public function getEvaluateTmpContent()
    {
        try {
            $request = Request::instance();
            $params = $request->param();
            $transaction_id = param($params, 'transaction_id');
            $template_id = param($params, 'template_id');
            $is_random = param($params, 'is_random');
            $service = new EbayFeedbackHelpService();
            $content = $service->getEvaluateTmpContent($transaction_id, $template_id, $is_random);
            return json(['content' => $content], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 500);
        }
    }


    /**
     * @title 更改评价状态
     * @author tanbin
     * @method POST
     * @apiParam name:id type:int require:1 desc:id
     * @apiParam name:status type:int require:1 desc:状态码
     * @url /ebay-feedback/status
     * @return \think\Response
     */
    public function changeStatus()
    {
        try {
            $request = Request::instance();
            $params = $request->param();
            $id = isset($params['id']) ? $params['id'] : '';
            if (empty($id) || empty($params['status']) || !in_array($params['status'], [0, 1, 2, 3])) {
                return json(['message' => '参数错误'], 400);
            }

            $field = $request->post('field', 'handel_status');
            $status = $request->post('status', 1);

            $service = new EbayFeedbackHelpService();
            $data = [
                'id' => $id,
                'field' => $field,
                'status' => $status
            ];
            $service->changeStatus($data);
            return json(['message' => '操作成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 500);
        }
    }


    /**
     * @title 回复买家评价
     * @author tanbin
     * @method POST
     * @apiParam name:id type:int require:1 desc:id
     * @apiParam name:status type:int require:1 desc:状态码
     * @url /ebay-feedback/reply
     * @return \think\Response
     */
    public function reply()
    {
        try {
            $request = Request::instance();
            $params = $request->param();
            $result = $this->validate($params, [
                'id|评价ID' => 'require|number',
                'text|回复内容' => 'require|length:1,80',
            ]);
            if ($result !== true) {
                throw new Exception($result);
            }
            $service = new EbayFeedbackHelpService();
            $service->reply($params);
            return json(['message' => '操作成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 500);
        }
    }


    /**
     * @title 统计回评状态-数量
     * @author tanbin
     * @method GET
     * @url /ebay-feedback/status
     * @return \think\Response
     */
    function statusStatistics()
    {
        $request = Request::instance();
        $params = $request->param();
        $where = [];
        //评价状态
        if (!empty(param($params, 'comment_type'))) {
            if ($params['comment_type'] == 99) {
                $where['comment_text_buyer'] = ['EQ', ''];
            } else {
                $where['comment_type'] = ['EQ', $params['comment_type']];
            }
        }
        //跟进状态[get-param 1-需要处理 2-完成处理]
        if (!empty(param($params, 'handel_status')) && in_array($params['handel_status'], [1, 2])) {
            $where['handel_status'] = $params['handel_status'];
        }

        //search
        if (!empty(param($params, 'search_key')) && !empty(param($params, 'search_val'))) {
            $where[$params['search_key']] = ['LIKE', '%' . $params['search_val'] . '%'];
        }

        //买家留评价时间
        $b_time = !empty(param($params, 'date_b')) ? strtotime($params['date_b'] . ' 00:00:00') : '';
        $e_time = !empty(param($params, 'date_e')) ? strtotime($params['date_e'] . ' 23:59:59') : '';

        if ($b_time && $e_time) {
            $where['comment_time_buyer'] = ['BETWEEN', [$b_time, $e_time]];
        } elseif ($b_time) {
            $where['comment_time_buyer'] = ['EGT', $b_time];
        } elseif ($e_time) {
            $where['comment_time_buyer'] = ['ELT', $e_time];
        }


        //账号
        if (!empty(param($params, 'customer_id'))) {
            //通过客服id找到所管理ebay账号id
            $developerService = new DeveloperService();
            $acountids = Cache::store('User')->getCustomerAccount($params['customer_id'], 1);
            if ($acountids) {
                $where['account_id'] = $whereMes['account_id'] = ['in', $acountids];
            } else {
                $where['account_id'] = -1;
            }
        }

        $service = new EbayFeedbackHelpService();
        $result = $service->statusStatistics($where);
        return json(['data' => $result], 200);
    }
}
