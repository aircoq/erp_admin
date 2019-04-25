<?php


namespace app\goods\service;

use app\common\cache\Cache;
use app\common\traits\ConfigCommon;
use app\common\model\GoodsTort as ModelGoodsTort;
use app\common\model\GoodsTortListing as ModelGoodsTortListing;
use think\Exception;
use app\publish\queue\ShopeeInfringeEnd;
use app\publish\queue\JoomInfringeEnd;
use app\publish\queue\PandaoInfringeEnd;
use app\publish\queue\AmazonInfringeEnd;
use app\publish\queue\WishInfringeEnd;
use app\publish\queue\AliExpressInfringeEnd;
use app\publish\queue\EbayInfringeEnd;
use app\common\model\Channel;
use app\common\service\CommonQueuer;
use app\common\service\UniqueQueuer;
use app\common\model\Goods as ModelGoods;
use app\common\service\ChannelAccountConst;
use app\goods\service\GoodsSku as ServiceGoodsSku;

class GoodsTort
{
    use ConfigCommon;

    /**
     * @title 获取后台相关配置
     * @param $channelName
     * @return mixed|string
     * @author starzhan <397041849@qq.com>
     */
    private function getCfgByChannelName($channelName)
    {
        $channelName = strtolower($channelName);
        $this->setConfigIdentification('tort_' . $channelName);
        $result = $this->getConfigData();
        return $result['value'];
    }

    public function getUserChannel()
    {
        $result = [];
        $GoodsHelp = new GoodsHelp();
        $GoodsHelp->show_channel;
        $ChannelModel = new Channel();
        $ret = $ChannelModel->where('name', 'in', $GoodsHelp->show_channel)->field('name,id')->select();
        foreach ($ret as $v) {
            $row = [];
            $row['id'] = $v['id'];
            $row['name'] = $v['name'];
            $result[] = $row;
        }
        return $result;

    }

    public function read($goodsId)
    {
        $result = ['channel_data' => [], 'reason' => '', 'notice_channel' => []];
        $channel_data = [];
        $ModelGoodsTort = new ModelGoodsTort();
        $ret = $ModelGoodsTort->where('goods_id', $goodsId)
            ->where('type',0)
            ->order("channel_id asc")
            ->select();
        $tmpRet = [];
        if ($ret) {
            foreach ($ret as $v) {
                $row = [];
                $row['goods_id'] = $v->goods_id;
                $row['channel_id'] = $v->channel_id;
                $row['channel_name'] = $v->channel_name;
                $row['shops'] = $v->shops;
                $tmpRet[$v->channel_id] = $row;
            }
            $first = reset($ret);
            $result['reason'] = $first['reason'];
            $result['notice_channel'] = $first->notice_channel;

        }
        $tmpChannel = $this->getUserChannel();
        foreach ($tmpChannel as $tmpChannel) {
            $channel_id = $tmpChannel['id'];
            if (isset($tmpRet[$channel_id])) {
                $row = $tmpRet[$channel_id];
            } else {
                $row = [];
                $row['goods_id'] = $goodsId;
                $row['channel_id'] = $tmpChannel['id'];
                $row['channel_name'] = $tmpChannel['name'];
                $row['shops'] = [];
            }
            $channel_data[] = $row;
        }

        $result['channel_data'] = $channel_data;
        return $result;
    }

    public function queueData($row, $user_id)
    {

        $queueArr = [
            new CommonQueuer(ShopeeInfringeEnd::class),
            new CommonQueuer(JoomInfringeEnd::class),
            new CommonQueuer(PandaoInfringeEnd::class),
            new CommonQueuer(AmazonInfringeEnd::class),
            new CommonQueuer(WishInfringeEnd::class),
            new CommonQueuer(AliExpressInfringeEnd::class),
            new CommonQueuer(EbayInfringeEnd::class)
        ];
        $ban_shop_id = explode(',', $row['ban_shop_id']);
        $notice_channel = explode(',', $row['notice_channel']);
        $data = [
            'tort_id' => $row['tort_id'],//侵权id
            'goods_id' => $row['goods_id'],//商品id
            'ban_shop_id' => $ban_shop_id,//不用下架的店铺id
            'notice_channel' => $notice_channel,//需要通知的渠道id
            'reason' => $row['reason'],//原因
            'channel_id' => $row['channel_id'],
            'created_id' => $user_id
        ];
        foreach ($queueArr as $queue) {
            $queue->push($data);
        }
    }

    public function checkParam($param, $user_id)
    {
        $goodsId = $param['goods_id'];
        if (!isset($param['channel_data']) || !$param['channel_data']) {
            throw new Exception('channel_data数据不能为空');
        }
        $GoodsHelp = new GoodsHelp();
        $goodsInfo = $GoodsHelp->getGoodsInfo($goodsId);
        if (!$goodsInfo) {
            throw new Exception('该商品不存在');
        }
        $ModelGoodsTort = new ModelGoodsTort();
        $old = [];
        $temp = $ModelGoodsTort->where('goods_id', $goodsId)
            ->where('type',0)->order("channel_id asc")->select();
        foreach ($temp as $channelInfo) {
            $old[$channelInfo['channel_id']] = $channelInfo;
        }
        $channelData = json_decode($param['channel_data'], true);
        if (!isset($param['reason']) || !$param['reason']) {
            throw new Exception('原因不能为空');
        }
        $infoData = [];
        $noPullChannelName = [];
        $notice_channel = isset($param['notice_channel']) ? implode(',', json_decode($param['notice_channel'], true)) : [];
        foreach ($channelData as $row) {
            if (isset($old[$row['channel_id']])) {
                continue;
            }
            $row['channel_name'] = $ModelGoodsTort->getChannelNameAttr(null, ['channel_id' => $row['channel_id']]);
            if (!$this->getCfgByChannelName($row['channel_name'])) {
                $noPullChannelName[] = $row['channel_name'];
                continue;
            }
            $infoRow = [];
            $infoRow['goods_id'] = $goodsId;
            $infoRow['channel_id'] = $row['channel_id'];
            $shopIds = implode(',', $row['ban_shop_id']);
            $infoRow['ban_shop_id'] = $shopIds;
            $infoRow['notice_channel'] = $notice_channel;
            $infoRow['create_id'] = $user_id;
            $infoRow['create_time'] = time();
            $infoRow['reason'] = $param['reason'];
            $infoData[] = $infoRow;
        }
        return [
            'info_data' => $infoData,
            'no_pull_channel_name' => $noPullChannelName,
            'goods_info' => $goodsInfo
        ];
    }

    /**
     * @title 注释..
     * @param $param
     * @author starzhan <397041849@qq.com>
     */
    public function save($param, $user_id)
    {
        $ret = $this->checkParam($param, $user_id);
        $infoData = $ret['info_data'];
        $noPullChannelName = $ret['no_pull_channel_name'];
        $goodsInfo = $ret['goods_info'];
        $goodsHelp = new GoodsHelp();
        if (!$infoData) {
            if ($noPullChannelName) {
                return ['message' => implode('平台、', $noPullChannelName) . "自动下架未开启", 'status' => 1];
            }
            throw new Exception('无操作');
        }
        $channel = [];
        $platform = $goodsInfo['platform'];
        $platformJson = $goodsHelp->getPlatformSaleJson($platform);
        $GoodsLog = new GoodsLog();
        foreach ($infoData as $row) {
            $Model = new ModelGoodsTort();
            $flag = $Model->allowField(true)->isUpdate(false)->save($row);
            $row['channel_name'] = $Model->getChannelNameAttr(null, ['channel_id' => $row['channel_id']]);
            $channel[] = $row['channel_name'];
            if ($flag) {
                $row['tort_id'] = $Model['id'];
                $this->queueData($row, $user_id);
            }
            $platformJson[$row['channel_name']] = 0;
        }
        $GoodsImport = new GoodsImport();
        $newPlatform = $GoodsImport->getPlatForm($platformJson);
        $Model = new ModelGoods();
        $Model->allowField(true)
            ->isUpdate(true)
            ->save(['platform' => $newPlatform], ['id' => $goodsInfo['id']]);
        $GoodsLog->mdfSpu($goodsInfo['spu'], $goodsInfo, ['platform' => $newPlatform]);
        $GoodsLog->save($user_id, $goodsInfo['id'], '【侵权下架】');
        $result = ['message' => implode('平台、', $channel) . "商品自动下架申请已提交", 'status' => 0];
        if ($noPullChannelName) {
            $result = ['message' => implode('平台、', $noPullChannelName) . "自动下架未开启", 'status' => 1];
        }
        return $result;
    }

    public function checkListingData($data)
    {
        $fileds = ['goods_id', 'goods_tort_id', 'listing_id', 'channel_id', 'item_id'];
        foreach ($fileds as $filed) {
            if (!isset($data[$filed]) || !$data[$filed]) {
                throw new Exception($filed . '数据不能为空');
            }
        }
        if (!isset($data['status'])) {
            throw new Exception('status数据不能为空');
        }
        return $data;
    }

    public function listingSave($data)
    {
        $data = $this->checkListingData($data);
        $oldData = ModelGoodsTortListing::where('goods_tort_id', $data['goods_tort_id'])
            ->where('listing_id', $data['listing_id'])
            ->find();
        if ($oldData) {
            $oldData->status = $data['status'];
            $oldData->update_time = time();
            $oldData->save();
        } else {
            $ModelGoodsTortListing = new ModelGoodsTortListing();
            $data['create_time'] = time();
            $ModelGoodsTortListing
                ->allowField(true)
                ->isUpdate(false)
                ->save($data);
        }
    }

    public function saveSku($skuId, $channel_ids, $user_id)
    {
        $skuInfo = ServiceGoodsSku::getBySkuID($skuId);
        $row = [];
        $row['goods_id'] = $skuInfo['goods_id'];
        $row['sku_id'] = $skuId;
        $row['ban_shop_id'] = '';
        $row['create_id'] = $user_id;
        $row['create_time'] = time();
        $row['type'] = 1;
        $old = [];
        $ModelGoodsTort = new ModelGoodsTort();
        $temp = $ModelGoodsTort->where('sku_id', $skuId)->where('type',1)->order("channel_id asc")->select();
        foreach ($temp as $channelInfo) {
            $old[$channelInfo['channel_id']] = $channelInfo;
        }
        foreach ($channel_ids as $channel_id) {
            $Model = new ModelGoodsTort();
            $row['channel_id'] = $channel_id;
            $row['notice_channel'] = $channel_id;
            if(isset($old[$channel_id])){
                $this->pullSkuQueue($old[$channel_id]['id'],$row,$channel_id);
                continue;
            }
            $Model->allowField(true)->isUpdate(false)->save($row);
            $this->pullSkuQueue($Model->id,$row,$channel_id);
        }
    }

    private function pullSkuQueue($id,$data,$channel_id){
        $queue = null;
        switch ($channel_id) {
            case ChannelAccountConst::channel_ebay:
                $queue = new UniqueQueuer(EbayInfringeEnd::class);
                break;
            case ChannelAccountConst::channel_amazon:
                $queue = new UniqueQueuer(AmazonInfringeEnd::class);
                break;
            case ChannelAccountConst::channel_wish:
                $queue = new UniqueQueuer(WishInfringeEnd::class);
                break;
            case ChannelAccountConst::channel_aliExpress:
                $queue = new UniqueQueuer(AliExpressInfringeEnd::class);
                break;
        }
        $data['tort_id'] = $id;
        $data['ban_shop_id'] = [];
        $data['notice_channel'] = [$channel_id];
        if ($queue) {
            $queue->push($data);
        }
    }
}