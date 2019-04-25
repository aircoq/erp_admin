<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/5/18
 * Time: 17:53
 */

namespace app\api\controller;


use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\index\queue\AliexpressAccountHealthReceiveQueue;
use app\index\queue\AmazonAccountHealthReceiveQueue;
use app\index\queue\WishAccountHealthReceiveQueue;
use app\index\queue\EbayAccountHealthReceiveQueue;
use app\index\service\EbayAccountHealthService;
use think\Controller;
use think\Request;


/**
 * @module 帐号数据监控
 * @title 帐号数据监控
 * Class EbayNotification
 * @package app\api\controller
 */
class AccountHealt extends Controller
{

    /**
     * @title wish接收帐号健康数据
     * @author 冬；
     * @method POST
     * @url /api/health-receive/wish/:id
     * @noauth
     */
    public function wish(Request $request, $id)
    {
        //拿到数据，队列处理；
        $data = $request->getInput();
        (new UniqueQueuer(WishAccountHealthReceiveQueue::class))->push($id. ':'. $data);
        return json(['status' => 'Sucess']);
    }

    /**
     * @title 速卖通接收帐号健康数据
     * @author 冬；
     * @method POST
     * @url /api/health-receive/aliexpress/:id
     * @noauth
     */
    public function aliexpress(Request $request, $id)
    {
        //拿到数据，队列处理；
        $data = $request->getInput();
        if (is_string($data)) {
            $data = str_replace('account$PresentRecordTable', 'accountUSPresentRecordTable', $data);
        }
        (new AliexpressAccountHealthReceiveQueue($id. ':'. $data))->execute();
        //(new UniqueQueuer(AliexpressAccountHealthReceiveQueue::class))->push($id. ':'. $data);
        return json(['status' => 'Sucess']);
    }

    /**
     * @title ebay接收帐号健康数据
     * @author 冬；
     * @method POST
     * @url /api/health-receive/ebay/
     * @noauth
     */
    public function ebay(Request $request)
    {
        //拿到数据，队列处理；
        $data['HealthData'] = $request->param('HealthData');
        $data['account'] = $request->param('account');
        $data['status'] = $request->param('status');
        $data['message'] = $request->param('message');
        (new UniqueQueuer(EbayAccountHealthReceiveQueue::class))->push($data);
        return json(['status' => 'Sucess']);
    }

    /**
     * @title amazon接收帐号健康数据
     * @author libaimin
     * @method POST
     * @url /api/health-receive/amazon/:id
     * @noauth
     */
    public function amazon(Request $request, $id)
    {
        //拿到数据，队列处理；
        $data['HealthData'] = $request->param('HealthData');
        $data['account'] = $request->param('account');
        $data['status'] = $request->param('status');
        $data['message'] = $request->param('message');
//        $logs = [
//                'time' => date('Y-m-d H:i:s'),
//                'data' => $data,
//            ];
//            Cache::store('JoomOrder')->addSynchronousLogs('101', $logs);
        (new UniqueQueuer(AmazonAccountHealthReceiveQueue::class))->push($data);
        return json(['status' => 'Sucess']);
    }

}