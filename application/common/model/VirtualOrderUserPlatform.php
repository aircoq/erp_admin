<?php
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/8/7
 * Time: 9:33
 */

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use app\common\model\GoodsSourceUrl;

class VirtualOrderUserPlatform extends Model
{
    const STATUS = [
        0 => '停用',
        1 => '启用',

    ];

    public function isHas($content)
    {
        return $this->where($content)->value('id');
    }

    public function add($where, $data)
    {
        if ($this->isHas($where)) {
            $this->save($data, $where);
        } else {
            $data['create_time'] = time();
            $this->insert($data);
        }
        return true;
    }

    public function getStatusTxtAttr($value, $data)
    {
        return self::STATUS[$data['status']];
    }

    //通过用户ID查询平台名称
    public function getMyChannelName($userId)
    {
        $channelIds = $this->where(['virtual_order_user_id' => $userId])->column('channel_id');
        $res = [];
        foreach ($channelIds as $v) {
            $one = [
                'channelId' => $v,
                'channelName' => Cache::store('Channel')->getChannelName($v),
            ];
            $res[] = $one;
        }
        return $res;
    }


}