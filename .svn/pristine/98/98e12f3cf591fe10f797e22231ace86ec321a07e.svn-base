<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-2-24
 * Time: 上午10:02
 */

namespace app\index\service;

use app\common\model\ChannelUserAccountMap as Model;
use think\Request;


class ChannelUserAccountMap
{
    private $user;
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * @param $channel_id
     * @param $account_id
     * @return array
     */
    public function getUserMaps($channel_id, $account_id)
    {
        $map = new Model();
        $map->where('channel_id', $channel_id);
        $map->where('account_id', $account_id);
        $data = $map->select();
        return $data;
    }

    /**
     * @param $channel_id
     * @param $account_id
     * @param $maps
     * @return bool
     */
    public function setUserMaps($channel_id, $account_id, $maps)
    {
        $model = new Model();
        $model->where('channel_id', $channel_id);
        $model->where('account_id', $account_id);
        $model->delete();
        $inserts = [];
        foreach ($maps as $map){
            $insert = [
                'user_id' => $map->user_id,
                'account_id' => $account_id,
                'channel_id' => $channel_id,
                'warehouse_type' => $map->warehouse_type,
                'leader' => 0,
                'create_time' => $map->create_time,
                'creator_id' => $this->user['user_id']
            ];
            $inserts[] = $insert;
        }
        if(!empty($inserts)){
            $model->insertAll($inserts);
        }
        return true;
    }
    
    /**
     * 根据渠道id和账号id查询客服id
     * wangwei 2018-9-19 21:44:57
     * @param int $channelId
     * @param int $accountId
     * @return int
     */
    public static function getCustomerId($channelId, $accountId)
    {
        $map = [
            'channel_id'=>$channelId,
            'account_id'=>$accountId,
        ];
        $re = (new Model())->where($map)->order('update_time desc,id desc')
        ->field('customer_id')
        ->find();
        return param($re, 'customer_id',0);
    }
    
    /**
     * @desc 获取当前销售负责的账号id 
     * @author wangwei
     * @date 2018-12-11 16:06:08
     * @param int $channelId
     * @param int $sellerId
     */
    public static function getAccountBySellerId($channelId, $sellerId)
    {
        $return = [];
        if(!$channelId || !$sellerId){
            return $return;
        }
        $where = [
            'channel_id'=>$channelId,
            'seller_id'=>$sellerId
        ];
        if($res = (new Model())->where($where)->field('account_id')->select()){
            $return = array_unique(array_column($res, 'account_id'));
        }
        return $return;
    }
    
    /**
     * 根据渠道id和账号id查询销售id
     * wangwei 2018-9-19 21:44:57
     * @param int $channelId
     * @param int $accountId
     * @return int
     */
    public static function getSellerId($channelId, $accountId)
    {
        $map = [
            'channel_id'=>$channelId,
            'account_id'=>$accountId,
        ];
        $re = (new Model())->where($map)->order('update_time desc,id desc')
        ->field('seller_id')
        ->find();
        return param($re, 'seller_id',0);
    }

}