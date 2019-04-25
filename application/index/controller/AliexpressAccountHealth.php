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
use think\Controller;
use think\Exception;
use think\Request;
use app\index\queue\AliexpressAccountHealthSendQueue;
use app\index\service\AliexpressAccountHealthService;


/**
 * @module 账号监控
 * @title 速卖通账号监控
 * @author 冬
 * @url /aliexpress-account-health
 * Class Aliexpress
 * @package app\goods\controller
 */
class AliexpressAccountHealth extends Controller
{

    private $serv = null;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        if (!$this->serv) {
            $this->serv = new AliexpressAccountHealthService();
        }
    }


    /**
     * @title 查看列表
     * @url /aliexpress-account-health
     * @apiFilter app\order\filter\AliexpressOrderByAccountFilter
     * @apiRelate app\index\controller\AliexpressAccountHealth::account
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
     * @url /aliexpress-account-health/account
     * @apiFilter app\order\filter\AliexpressOrderByAccountFilter
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
     * @url /aliexpress-account-health/export
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
     * @url /aliexpress-account-health/:account_id/history
     * @method GET
     * @return \think\Response
     */
    public function history(Request $request, $account_id)
    {
        $params = $request->get();
        try {
            $result = $this->serv->gethistory($account_id, $params);
            return json($result);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 查看付款记录；
     * @url /aliexpress-account-health/:account_id/:type/payment
     * @method GET
     * @return \think\Response
     */
    public function payment(Request $request, $account_id, $type)
    {
        $params = $request->get();
        try {
            $result = $this->serv->getpayment($account_id, $type, $params);
            return json($result);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 批量设置监控值
     * @url /aliexpress-account-health
     * @method post
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //$temp['today_score'] = bcdiv($temp['today_score'], 100, 2);//今日服务总分
        //$temp['back_transaction_rate'] = bcdiv($temp['back_transaction_rate'], 100, 2);//成交不卖率
        //$temp['not_cargo_dispute_rate'] = bcdiv($temp['not_cargo_dispute_rate'], 100, 2);//未收到货物纠纷提起率；
        //$temp['error_cargo_dispute_rate'] = bcdiv($temp['error_cargo_dispute_rate'], 100, 2);//货不对版纠纷提起率；
        //
        //$temp['dsr_description'] = bcdiv($temp['dsr_description'], 100, 2);//DSR商品描述；
        //$temp['dsr_service'] = bcdiv($temp['dsr_service'], 100, 2);//DSR卖家服务；
        //$temp['dsr_shipping'] = bcdiv($temp['dsr_shipping'], 100, 2);//DSR物流；
        //
        //$temp['forty_eight_deliver'] = bcdiv($temp['forty_eight_deliver'], 100, 2);//48小时发货率；
        //
        //$temp['outlaw_quality'] = bcdiv($temp['outlaw_quality'], 100, 2);//商品信息质量违规；
        //$temp['outlaw_property'] = bcdiv($temp['outlaw_property'], 100, 2);//知识产权禁限违规；
        //$temp['outlaw_trancation'] = bcdiv($temp['outlaw_trancation'], 100, 2);//交易违规及其他；
        //$temp['severity_outlaw_property'] = bcdiv($temp['severity_outlaw_property'], 100, 2);//知识产权严重违规；

        $params = $request->post();
        $result = $this->validate($params, [
            'account_ids' => 'require',
            'today_score' => 'require|number',
            'back_transaction_rate' => 'require|number',
            'not_cargo_dispute_rate' => 'require|number',
            'error_cargo_dispute_rate' => 'require|number',
            'dsr_description' => 'require|number',
            'dsr_service' => 'require|number',
            'dsr_shipping' => 'require|number',
            'forty_eight_deliver' => 'require|number',
            'outlaw_quality' => 'require|number',
            'outlaw_property' => 'require|number',
            'outlaw_trancation' => 'require|number',
            'severity_outlaw_property' => 'require|number',
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
     * @url /aliexpress-account-health
     * @method PUT
     * @return \think\Response
     */
    public function editGoal(Request $request)
    {
        $params = $request->put();
        $result = $this->validate($params, [
            'account_id' => 'require|number',
            'today_score' => 'require|number',
            'back_transaction_rate' => 'require|number',
            'not_cargo_dispute_rate' => 'require|number',
            'error_cargo_dispute_rate' => 'require|number',
            'dsr_description' => 'require|number',
            'dsr_service' => 'require|number',
            'dsr_shipping' => 'require|number',
            'forty_eight_deliver' => 'require|number',
            'outlaw_quality' => 'require|number',
            'outlaw_property' => 'require|number',
            'outlaw_trancation' => 'require|number',
            'severity_outlaw_property' => 'require|number',
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
     * @url /aliexpress-account-health/repitle
     * @method POST
     * @return \think\Response
     */
    public function repitle(Request $request)
    {
        $params = $request->post();
        $result = $this->validate($params, [
            'account_ids' => 'require',
        ]);
        if ($result !== true) {
            return json(['message' => $result], 400);
        }
        try {
            $idArr = json_decode($params['account_ids'], true);
            if (empty($idArr)) {
                throw new Exception('帐号ID为空');
            }
            $idArr = array_filter(array_unique($idArr));
            foreach ($idArr as $id) {
                $id = intval($id);
                $account = Cache::store('AliexpressAccount')->getAccountById($id);
                if (empty($account)) {
                    throw new Exception('aliexpress帐号ID:'. $id. ' 不存在');
                }
            }
            foreach ($idArr as $id) {
                $id = intval($id);
                (new UniqueQueuer(AliexpressAccountHealthSendQueue::class))->push($id);
            }

            return json(['message' => '已加入爬取队列，请稍候查看结果']);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 读取aliexpress帐号目标率
     * @url /aliexpress-account-health/:account_id/goal
     * @method GET
     * @return \think\Response
     */
    public function goal($account_id)
    {
        try {
            $result = $this->serv->readGoal($account_id);
            return json($result);
        } catch(Exception $e){
            return json(['message' => $e->getMessage()], 400);
        }
    }


}