<?php


namespace app\goods\service;

use app\internalletter\service\InternalLetterService;
use app\publish\helper\ebay\EbayPublish;
use app\publish\service\WishHelper;
use app\publish\service\JoomService;
use app\publish\helper\shopee\ShopeeHelper;
use app\publish\service\PandaoService;
use app\listing\service\AliexpressListingHelper;
use app\publish\service\AmazonPublishHelper;
use app\index\service\ChannelService;
/**
 * @title 商品公告消息处理类
 * @module 请输入模块
 * @url 输入url
 * @author starzhan <397041849@qq.com>
 */
class GoodsNotice
{
    private static $NoticeData = [];

    public static function downSku($sku){
        self::$NoticeData[] = $sku;
    }

    /**
     * @title 发送下架通知
     * @author starzhan <397041849@qq.com>
     */
    public static function sendDown(){
        if(self::$NoticeData){
            $title = "商品下架通知";
            $content = '以下sku已下架:<br>';
            foreach (self::$NoticeData as $sku){
                $content.=$sku.'<br>';
            }
            $InternalLetterService = new InternalLetterService();
            $params = [
                'receive_ids'=>'[0]',
                'title'=>$title,
                'content'=>$content,
                'type'=>20
            ];
            $InternalLetterService->sendLetter($params);
        }
    }
    public static function sendTortDescription($goods_id, $goodsInfo, $channel_id, $site='', $code, $remark)
    {
        $userIds = [];
        switch ($channel_id) {
            case 1:
                $userIds = EbayPublish::getSalesmenByGoodsId($goods_id);
                break;
            case 2:
                $ser = new AmazonPublishHelper();
                $userIds = $ser->getSellerIdByGoodsId($goods_id);
                break;
            case 3:
                $userIds = WishHelper::getSalesmenByGoodsId($goods_id);
                break;
            case 4:
                $userIds = AliexpressListingHelper::getSellerIdByGoodsId($goods_id);
                break;
            case 7:
                $userIds = JoomService::getSalesmenByGoodsId($goods_id);
                break;
            case 9:
                $userIds = ShopeeHelper::getSalesmenByGoodsId($goods_id);
                break;
            case 8:
                $userIds = PandaoService::getSalesmenByGoodsId($goods_id);
                break;
        }
        array_push($userIds, $goodsInfo['developer_id']);
        $userIds = array_filter($userIds);
        if (!$userIds) {
            return;
        }
        if($userIds){
            $title = "商品侵权信息通知";
            $ChannelService = new ChannelService();
            $channelInfo = $ChannelService->getInfoById($channel_id);
            $channelName = $channelInfo['title']??'';
            $content = "SPU：{$goodsInfo['spu']} 在{$channelName}侵权：<br>";
            if($site){
                $content.="站点: {$site} ";
            }
            if($code){
                $content.="帐号简称: {$code} ";
            }
            if($remark){
                $content.="侵权描述: {$remark} ";
            }
            $InternalLetterService = new InternalLetterService();
            $params = [
                'receive_ids'=>$userIds,
                'title'=>$title,
                'content'=>$content,
                'type'=>13,
                'dingtalk'=>1
            ];
            $flag =$InternalLetterService->sendLetter($params);
        }
    }
}