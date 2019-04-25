<?php
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/8/18
 * Time: 17:01
 */

namespace app\index\controller;


use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\index\queue\AmazonAccountHealthSendQueue;
use app\test\controller\JoomOrder;
use think\Controller;
use think\Exception;
use think\Request;
use app\index\service\AmazonAccountHealthService;


/**
 * @module 账号监控
 * @title amazon账号监控
 * @author libaimin
 * @url /amazon-account-health
 * @package app\goods\controller
 */
class AmazonAccountHealth extends Controller
{

    private $serv = null;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        if (!$this->serv) {
            $this->serv = new AmazonAccountHealthService();
        }
    }


    /**
     * @title 查看列表
     * @url /amazon-account-health
     * @apiFilter app\index\filter\AmazonAccountHealthFilter
     * @apiFilter app\index\filter\DepartFilter
     * @method GET
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $params = $request->get();
        try {
            $result = $this->serv->lists($params);
            return json($result);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 帐号筛选列表
     * @url /amazon-account-health/account
     * @method GET
     * @return \think\Response
     */
    public function account()
    {
        $accountList = Cache::store('AmazonAccount')->getAccount();
        $data = [];
        foreach ($accountList as $account) {
            $data[] = [
                'label' => $account['code'],
                'value' => $account['id'],
                'account_name' => $account['account_name'],
            ];
        }

        return json(['account' => $data]);
    }


    /**
     * @title 导出列表
     * @url /amazon-account-health/export
     * @method GET
     * @return \think\Response
     */
    public function export(Request $request)
    {
        $params = $request->get();
        try {
            $result = $this->serv->export($params);
            return json($result);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 查看历史数据；
     * @url /amazon-account-health/:amazon_account_id/history
     * @method GET
     * @return \think\Response
     */
    public function history(Request $request, $amazon_account_id)
    {
        $params = $request->get();
        try {
            $result = $this->serv->gethistory($amazon_account_id, $params);
            return json($result);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 批量设置监控值
     * @url /amazon-account-health
     * @method post
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params = $request->post();
        $result = $this->validate($params, [
            'amazon_account_ids' => 'require',
            'order_rate' => 'require|number',
            'balance_amount' => 'require|number',
        ]);
        if ($result !== true) {
            return json(['message' => $result], 400);
        }
        try {
            $result = $this->serv->setCommonGoal($params);
            return json(['message' => '设置成功']);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 单个设置监控值
     * @url /amazon-account-health
     * @method PUT
     * @return \think\Response
     */
    public function editGoal(Request $request)
    {
        $params = $request->put();
        $result = $this->validate($params, [
            'amazon_account_id' => 'require|number',
            'order_rate' => 'require|number',
            'balance_amount' => 'require|number',
        ]);
        if ($result !== true) {
            return json(['message' => $result], 400);
        }
        try {
            $result = $this->serv->setAccountGoal($params);
            return json(['message' => '设置成功']);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 立即抓取
     * @url /amazon-account-health/repitle
     * @method POST
     * @return \think\Response
     */
    public function repitle(Request $request)
    {
        $params = $request->post();
        $result = $this->validate($params, [
            'amazon_account_ids' => 'require',
        ]);
        if ($result !== true) {
            return json(['message' => $result], 400);
        }
        try {
            $idArr = explode(',', $params['amazon_account_ids']);
            $idArr = array_filter(array_unique($idArr));
            foreach ($idArr as $id) {
                $id = intval($id);
                $account = Cache::store('AmazonAccount')->getAccount($id);
                if (empty($account)) {
                    throw new Exception('amazon帐号ID:'. $id. ' 不存在');
                }
            }
            foreach ($idArr as $id) {
                $id = intval($id);
                (new UniqueQueuer(AmazonAccountHealthSendQueue::class))->push($id);
            }

            return json(['message' => '已加入爬取队列，请稍候查看结果']);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 读取amazon帐号目标率
     * @url /amazon-account-health/:amazon_account_id/goal
     * @method GET
     * @return \think\Response
     */
    public function goal($amazon_account_id)
    {
        try {
            $result = $this->serv->readGoal($amazon_account_id);
            return json($result);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 读取amazon帐余额统计
     * @url /amazon-account-health/balance
     * @method GET
     * @return \think\Response
     */
    public function balance(Request $request)
    {
        try {
            $times = $request->get('times',date('Y-m-d'));
            $result = $this->serv->balances($times);
            return json($result);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 读取amazon帐余额详情
     * @url /amazon-account-health/balance-details
     * @method GET
     * @return \think\Response
     */
    public function balanceDetails(Request $request)
    {
        try {
            $times = $request->get('times',date('Y-m-d'));
            $page = $request->get('page',1);
            $pageSize = $request->get('pageSize',20);
            $result = $this->serv->balanceDetails($times, $page, $pageSize);
            return json($result);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }

}