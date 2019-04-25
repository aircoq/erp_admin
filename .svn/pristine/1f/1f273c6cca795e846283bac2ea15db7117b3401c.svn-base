<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/9/28
 * Time: 10:51
 */

namespace app\common\model;

use app\common\model\aliexpress\AliexpressCategory;
use app\common\model\amazon\AmazonCategory;
use app\common\model\ebay\EbayCategory;

use think\Model;
use app\common\cache\Cache;

class GoodsCategoryMap extends model
{
    public static function getsite($channel, $id)
    {
        $result = Cache::store('channel')->getSite($channel, true);
        foreach ($result as $v) {
            if ($v['id'] == $id) {
                return $v;
            }
        }
        return [];

    }

    public static function getChannel($channelId = 0)
    {
        $resilt = Cache::store('channel')->getChannel();
        foreach ($resilt as $v) {
            if ($v['id'] == $channelId) {
                return $v;
            }
        }
        return [];
    }

    public static function getCategoty($channel, $site, $cid)
    {
        switch ($channel) {
            case 'ebay':
                return self::ebaycate($cid, $site);
            case 'amazon':
                return self::amazonCate($cid, $site);
            case 'aliExpress':
                return self::aliexpressCate($cid, $site);
            default:
                return [];
        }
    }


    private static function ebayCate($cid, $site, $result = [])
    {
        $EbayCategory = new EbayCategory();
        $field = 'category_id,category_level,category_name,category_parent_id, leaf_category as is_leaf';
        $data = $EbayCategory->where(['category_id' => $cid, 'site' => $site])->field($field)->find();
        if ($data) {
            $data = $data->toArray();
            array_unshift($result, $data);
            $pid = $data['category_parent_id'];
            if ($pid && $data['category_level'] != 1) {
                return self::ebayCate($pid, $site, $result);
            }
        }
        return $result;
    }

    private static function amazonCate($cid, $site, $result = array())
    {
        $AmazonCategory = new AmazonCategory();
        $field = 'category_id,category_level,category_name,category_parent_id';
        $data = $AmazonCategory->where(['category_id' => $cid, 'site' => $site])->field($field)->find();
        if ($data) {
            $data = $data->toArray();
            array_unshift($result, $data);
            $pid = $data['category_parent_id'];
            if ($pid && $data['category_level'] != 1) {
                return self::amazonCate($pid, $site, $result);
            }
        }
        return $result;
    }

    private static function aliexpressCate($cid, $site, $result = array())
    {
        $AliexpressCategory = new AliexpressCategory();
        $field = 'category_id,category_level,category_name_zh,category_pid as category_parent_id, category_isleaf as is_leaf';
        $data = $AliexpressCategory->where(['category_id' => $cid])->field($field)->find();
        if ($data) {
            $data = $data->toArray();
            $data['category_name'] = $data['category_name_zh'];
            unset($data['category_name_zh']);
            array_unshift($result, $data);
            $pid = $data['category_parent_id'];
            if ($pid && $data['category_level'] != 1) {
                return self::aliexpressCate($pid, $site, $result);
            }
        }
        return $result;
    }


}