<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;


/**
 * 所有规则信息
 * Created by tanbin.
 * User: libaimin
 * Date: 2018/8/13
 * Time: 10:45
 */
class VirtualRule extends Cache
{

    /** 获取所有平台
     * @param int $id
     * @return array|mixed
     */
    public function getChannel($id = 0)
    {
        $channelList = [
            ChannelAccountConst::channel_amazon => ['id' => 2, 'name' => 'amazon', 'title' => '亚马逊平台'],
            ChannelAccountConst::channel_aliExpress => ['id' => 4, 'name' => 'aliExpress', 'title' => '速卖通平台'],
            ChannelAccountConst::channel_ebay => ['id' => 1, 'name' => 'ebay', 'title' => 'eBay平台'],
            ChannelAccountConst::channel_wish => ['id' => 3, 'name' => 'wish', 'title' => 'Wish平台'],
            ChannelAccountConst::channel_Walmart => ['id' => 11, 'name' => 'walmart', 'title' => '沃尔玛平台'],
            ChannelAccountConst::channel_Shopee => ['id' => 9, 'name' => 'shopee', 'title' => 'shopee平台'],
        ];
        if ($id > 0) {
            return $channelList[$id];
        }
        return $channelList;
    }

    public function getChannelCountry($name = '')
    {
        $allCountry = [
            'amazon' => [
                ['code' => 'US', 'name' => '美国'],
                ['code' => 'DE', 'name' => '德国'],
                ['code' => 'UK', 'name' => '英国'],
                ['code' => 'FR', 'name' => '法国'],
                ['code' => 'JP', 'name' => '日本'],
                ['code' => 'IT', 'name' => '意大利'],
                ['code' => 'ES', 'name' => '西班牙'],
                ['code' => 'AU', 'name' => '澳大利亚'],
                ['code' => 'CA', 'name' => '加拿大'],
            ],
            'aliExpress' => [
                ['code' => 'US', 'name' => '美国'],
                ['code' => 'RU', 'name' => '俄罗斯'],
            ],
            'ebay' => [
                ['code' => 'US', 'name' => '美国'],
                ['code' => 'UK', 'name' => '英国'],
            ],
            'wish' => [
                ['code' => 'US', 'name' => '美国'],
            ],
            'walmart' => [
                ['code' => 'US', 'name' => '美国'],
            ],
            'shopee' => [
                ['code' => 'MY', 'name' => '马来西亚'],
                ['code' => 'SG', 'name' => '新加坡'],
                ['code' => 'ID', 'name' => '印度尼西亚'],
                ['code' => 'TW', 'name' => '台湾'],
            ],
        ];
        if ($name) {
            return $allCountry[$name];
        }
        return $allCountry;
    }


}


