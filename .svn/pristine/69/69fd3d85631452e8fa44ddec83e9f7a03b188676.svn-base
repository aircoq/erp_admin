<?php


namespace app\common\model;


use think\Model;
use app\common\cache\Cache;
use app\order\service\OrderService;

class GoodsTort extends Model
{
    /**
     * @var array 临时存放channel变量
     */
    private $tmpChannel = [];

    /**
     * @title 返回渠道配置
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getChannel()
    {
        if ($this->tmpChannel === []) {
            $this->tmpChannel = cache::store('channel')->getChannel();
        }
        return $this->tmpChannel;
    }

    public function getChannelNameAttr($value,$data){
        $channel = $this->getChannel();
        foreach ($channel as $channel_name=>$v){
             if($v['id']==$data['channel_id']){
                 return $channel_name;
             }
        }
        return '';
    }

    public function getShopsAttr($value,$data){
        $result = [];
        $aShopId = explode(',',$data['ban_shop_id']);
        $OrderService = new OrderService();
        foreach ($aShopId as $shopId){
            $row = [];
            $row['shop_id'] = $shopId;
           // $row['shop_code'] = $OrderService->getAccountName($data['channel_id'],$shopId);
            $result[] = $row;
        }
        return $result;
    }

    public function getNoticeChannelAttr($value,$data)
    {
        $result = [];
        $aChannelId = explode(',',$data['notice_channel']);
        foreach ($aChannelId as $channelId){
            $row = [];
            $row['channel_id'] = $channelId;
            $row['channel_name'] = $this->getChannelNameAttr(null,['channel_id'=>$channelId]);
            $result[] = $row;
        }
        return $result;
    }
}