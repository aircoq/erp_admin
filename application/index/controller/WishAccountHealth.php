<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/5/18
 * Time: 10:01
 */

namespace app\index\controller;


use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\index\queue\WishAccountHealthSendQueue;
use think\Controller;
use think\Exception;
use think\Request;
use app\index\service\WishAccountHealthService;


/**
 * @module 账号监控
 * @title wish账号监控
 * @author 冬
 * @url /wish-account-health
 * Class Wish
 * @package app\goods\controller
 */
class WishAccountHealth extends Controller
{

    private $serv = null;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        if (!$this->serv) {
            $this->serv = new WishAccountHealthService();
        }
    }


    /**
     * @title 查看列表
     * @url /wish-account-health
     * @method GET
     * @apiFilter app\order\filter\WishOrderByAccountFilter
     * @apiRelate app\index\controller\WishAccountHealth::account
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
     * @url /wish-account-health/account
     * @apiFilter app\order\filter\WishOrderByAccountFilter
     * @method GET
     * @return \think\Response
     */
    public function account()
    {
        $result = $this->serv->accounts();
        return json(['account' => $result]);
    }


    /**
     * @title 导出列表
     * @url /wish-account-health/export
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
     * @url /wish-account-health/:wish_account_id/history
     * @method GET
     * @return \think\Response
     */
    public function history(Request $request, $wish_account_id)
    {
        $params = $request->get();
        try {
            $result = $this->serv->gethistory($wish_account_id, $params);
            return json($result);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 查看付款记录；
     * @url /wish-account-health/:wish_account_id/payment
     * @method GET
     * @return \think\Response
     */
    public function payment(Request $request, $wish_account_id)
    {
        $params = $request->get();
        try {
            $result = $this->serv->getpayment($wish_account_id, $params);
            return json($result);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 批量设置监控值
     * @url /wish-account-health
     * @method post
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params = $request->post();
        $result = $this->validate($params, [
            'wish_account_ids' => 'require',
            'imitation_rate' => 'require|number',
            'tracking_rate' => 'require|number',
            'delay_shipment_rate' => 'require|number',
            'thirty_score' => 'require|number',
            'refund_rate' => 'require|number',
            'onway_amount' => 'require|number',
            'unconfirm_amount' => 'require|number',
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
     * @url /wish-account-health
     * @method PUT
     * @return \think\Response
     */
    public function editGoal(Request $request)
    {
        $params = $request->put();
        $result = $this->validate($params, [
            'wish_account_id' => 'require|number',
            'imitation_rate' => 'require|number',
            'tracking_rate' => 'require|number',
            'delay_shipment_rate' => 'require|number',
            'thirty_score' => 'require|number',
            'refund_rate' => 'require|number',
            'onway_amount' => 'require|number',
            'unconfirm_amount' => 'require|number',
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
     * @url /wish-account-health/repitle
     * @method POST
     * @return \think\Response
     */
    public function repitle(Request $request)
    {
        $params = $request->post();
        $result = $this->validate($params, [
            'wish_account_ids' => 'require',
        ]);
        if ($result !== true) {
            return json(['message' => $result], 400);
        }
        try {
            $idArr = explode(',', $params['wish_account_ids']);
            $idArr = array_filter(array_unique($idArr));
            foreach ($idArr as $id) {
                $id = intval($id);
                $account = Cache::store('WishAccount')->getAccount($id);
                if (empty($account)) {
                    throw new Exception('wish帐号ID:'. $id. ' 不存在');
                }
            }
            foreach ($idArr as $id) {
                $id = intval($id);
                (new UniqueQueuer(WishAccountHealthSendQueue::class))->push($id);
            }

            return json(['message' => '已加入爬取队列，请稍候查看结果']);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 读取wish帐号目标率
     * @url /wish-account-health/:wish_account_id/goal
     * @method GET
     * @return \think\Response
     */
    public function goal($wish_account_id)
    {
        try {
            $result = $this->serv->readGoal($wish_account_id);
            return json($result);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }


}