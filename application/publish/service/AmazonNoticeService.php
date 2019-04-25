<?php
namespace app\publish\service;


use app\common\service\UniqueQueuer;
use app\common\model\amazon\AmazonNotice as AmazonNoticeModel;
use app\publish\queue\AmazonSetNoticeQueuer;

class AmazonNoticeService
{

    /**
     * @var array
     * 1.AnyOfferChanged:对于 您销售的商品，只要条件（新的或已使用的）有任何前20个商品的商家信息更改，就会发送AnyOfferChanged通知。前20名优惠由降价确定，即价格加运费。如果多个卖家收取相同的到岸价格，结果将以随机顺序返回。您只会收到 有活动优惠的商品的AnyOfferChanged通知。您无法订阅您没有有效优惠的商品的通知
     *
     * 2.FeedProcessingFinished:每当你使用提交的任何饲料通知发送饲料API部分 达到的饲料加工状态DONE 或取消
     *
     * 3.FeePromotion:使用亚马逊MWS的卖家可以享受限时费用促销。要接收可用费用促销的通知，卖家必须订阅 FeePromotion通知。当卖家最初注册订阅并且 isEnabled设置为true时，卖家收到所有当前有效的促销活动。每个促销都作为单个消息发送。促销变为活动状态时会发送后续促销通知。
     *
     * 4.FulfillmentOrderStatus:该 FulfillmentOrderStatus每当有一个状态的改变通知发送多渠道配送 履行订单
     *
     * 5.ReportProcessingFinished:该 ReportProcessingFinished 每当您已使用要求的任何报告通知发送报告API部分 达到的报表处理状态 DONE， 取消，或 DONE_NO_DATA
     */
    //通知类型配置
    public static  $notice_type_conf = [
        'AnyOfferChanged',//商家信息更改,
        'FeedProcessingFinished',//状态DONE 或取消
        'FeePromotion',//要接收可用费用促销的通知
        'FulfillmentOrderStatus',
        'ReportProcessingFinished',
    ];


    /**
     * @title 通知信息查询
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *
     */
    public function noticeInfo($id)
    {
        $notice_type_conf = self::$notice_type_conf;

        $model = new AmazonNoticeModel;

        $data = $model->where(['account_id' => $id])->find();

        if($data){
            $data = $data->toArray();

            $notice_type = $data['notice_type'];

            if($data['notice_type']){
                $data['notice_type'] = json_decode($data['notice_type'],true);
            }

        }else{//无数据
            $data = ['id' => 0, 'account_id' => $id];
        }

        if(!isset($notice_type) || empty($notice_type)){
            $notice_type = [];
            foreach ($notice_type_conf as $key => $val){
                $notice_type[] = ['name' => $val, 'checked' => ''];
            }

            $data['notice_type'] = $notice_type;
        }

        return $data;
    }


    /**
     * @param $params
     * @param int $uid
     */
    public function noticeEdit($params, $uid = 0)
    {
        $id = $params['id'] ? $params['id'] : 0;
        unset($params['id']);


        $model = new AmazonNoticeModel;

        $time = time();
        if($id){
            //更新
            $params['updated_time'] = $time;


            if($model->isUpdate(true)->save($params, ['id' => $id])){

                //写入通知配置队列
                (new UniqueQueuer(AmazonSetNoticeQueuer::class))->push($id);
                return ['message' => '更新成功', 'status' => true];
            }else{
                return ['message' => '更新失败', 'status' => false];
            }
        }

        //添加
        $params['created_time'] = time();
        $params['create_id'] = $uid;

        $model->save($params);
        $id = $model->id;
        if($id){

            //写入通知配置队列
            (new UniqueQueuer(AmazonSetNoticeQueuer::class))->push($id);
            return ['message' => '新增成功', 'status' => true];
        }else{
            return ['message' => '新增失败', 'status' => false];
        }
    }
}