<?php

namespace app\report\controller;

use app\common\cache\Cache;
use app\common\controller\Base;
use app\common\model\aliexpress\AliexpressSettlement;
use app\common\model\wish\WishSettlement;
use app\common\service\ChannelAccountConst;
use app\index\controller\DashBoard;
use \app\report\service\Settlement as settleService;
use think\Db;
use think\Exception;
use think\Request;
use app\common\service\Excel;
use app\common\service\Common;

/**
 * @module 财务结算表
 * @title 财务结算管理
 * @url /settlement
 * @author linpeng <1421698291@qq.com>
 * @package app\warehouse\controller
 */
class Settlement extends Base
{
    /**
     * @title 统计财务结算表
     * @method get
     * @author lin
     * @url /settlement/index_settle
     * @time 2018/11/26 16:57
     * @param Request $request
     * @return array|false|\PDOStatement|string|\think\Collection|\think\response\Json
     * @throws \Exception
     */

    public function indexSettle(Request $request)
    {
        $params = $request->param();
        $service = new settleService();
        $channelId = param($params,'channel_id',0);
        $data = [];
        switch ($channelId){
            case 0:
                return json($data,200);
                break;
            case ChannelAccountConst::channel_aliExpress:
                $data = $service->getAliData($params);
                break;
            case ChannelAccountConst::channel_wish:
                $data = $service->getWishData($params);
                break;
            default:
                return json($data,200);
                break;
        }
        return json($data,200);

    }

    /**
     * @title aliexpress放款帐期详情导出
     * @method post
     * @url /settlement/export
     * @author lin
     * @time 2018/11/27 10:06
     * @param Request $request
     * @return \think\response\Json
     */
    public function export(Request $request)
    {
        try{
            $params = $request->param();
            $service = new settleService();
            $user = Common::getUserInfo($request);
            $count = 0;
            $where = $service->getSettleWhere($params);
            if (isset($params['channel_id']) && is_numeric($params['channel_id'])) {
                switch ($params['channel_id']){
                    case 0:
                        break;
                    case ChannelAccountConst::channel_aliExpress:
                        $settleModel = new AliexpressSettlement();
                        $count = $settleModel->where($where)->where(function ($query){
                                $query->where('transfer_amount','<>','0')->whereOr('refund_amount','<=','0');
                            })->count();
                        if ($count > 500000) {
                            return json(['message'=> '订单量超过五十万，无法导出，请调整搜索条件'],400);
                        }
                        $service->applyExport($params,$user);
                        return json(['message'=> '申请成功', 'join_queue' => 1], 200);
                        break;
                    case ChannelAccountConst::channel_wish:
                        $service->applyExport($params,$user);
                        return json(['message'=> '申请成功', 'join_queue' => 1], 200);
                        break;
                    default:
                        break;
                }
            }

        }catch (Exception $ex){
            return json(['message' => $ex->getMessage()],400);
        }

    }

    /**
     * @title 统计财务结算表详情
     * @method get
     * @author lin
     * @url /settlement/settle_detail
     * @time 2018/11/27 15:41
     * @param Request $request
     * @return \think\response\Json
     */
    public function settleDetail(Request $request)
    {
        try {
            $params = $request->param();
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 10);
            $loanPeriod = $request->get('loan_period',1);
            $channelId = param($params,'channel_id',0);
            $service = new settleService();
            $where = $service->getSettleWhere($params);
            $fields = [
                'account_period_week loan_period',
                'payment_amount',
                'transfer_amount',
                'currency_code',
                'aliexpress_order_id order_id',
                'account_id',
                'payment_time',
                'transfer_time',
                'transfer_amount/payment_amount loan_proportion'
            ];
            switch ($channelId){
                case 0:
                    return json([],200);
                    break;
                case ChannelAccountConst::channel_aliExpress:
                    $settleModel = new AliexpressSettlement();

                    $res = $settleModel->field($fields)
                        ->where($where)->where(function ($query){
                            $query->where('transfer_amount','<>','0')->whereOr('refund_amount','<=','0');
                        })->order('payment_time')->page($page,$pageSize)->select();
                    $count = $settleModel->field($fields)
                        ->where($where)->where(function ($query){
                            $query->where('transfer_amount','<>','0')->whereOr('refund_amount','<=','0');
                        })->order('payment_time')->count();
                    break;
                case ChannelAccountConst::channel_wish:
                    $settleModel = new WishSettlement();
                    $res = $settleModel->field($fields)
                        ->where($where)->order('payment_time')->page($page,$pageSize)->select();
                    $count = $settleModel->field($fields)
                        ->where($where)->order('payment_time')->count();
                    break;
                default:
                    return json([],200);
            }

            if (!$res) {
                return json([],200);
            }
            $service = new settleService();
            $datas = $service->formatDetailData($res,$channelId);
            $data = [
                'page' =>$page,
                'pageSize' => $pageSize,
                'total' => $count,
                'data' => $datas
            ];
            return json($data,200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()],400);

        }

    }

}
