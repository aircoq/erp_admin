<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2019/1/18
 * Time: 11:31
 */

namespace app\publish\task;


use app\common\cache\Cache;
use app\common\model\ebay\EbayListing;
use app\index\service\AbsTasker;
use app\common\model\ebay\EbayListingSetting as ELSMysql;
use app\common\model\mongodb\ebay\EbayListingSetting as ELSMongo;
use app\publish\helper\ebay\EbayPublish;
use think\Config;
use think\Exception;
use MongoDB\Client;

class EbayListingSettingMoveMysqlToMongodb extends AbsTasker
{
    public function getName()
    {
        return "ebay setting表从mysql移到mongodb";
    }

    public function getDesc()
    {
        return "ebay setting表从mysql移到mongodb";
    }

    public function getCreator()
    {
        return "wlw2533";
    }

    public function getParamRule()
    {
        return [];
    }


    public function execute()
    {
        try {
            //先查询主表，避免转移一些垃圾信息
            $lastId = Cache::handler()->get('mysql_mongodb_setting_id');
            if ($lastId >= 6200000) {
                return;
            }
            $lastId = $lastId ?: 0;
            $wh = [
                'draft' => 0,//范本不移
                'item_id' => ['<>',0],
                'listing_status' => ['in',EbayPublish::OL_PUBLISH_STATUS],
                'id' => ['between', [$lastId+1,6200000]],
            ];
            $ids = EbayListing::where($wh)->order('id')->limit(200)->column('id');
            if (!$ids) {
                return;
            }

            //获取设置信息
            $field = 'id,description';
            $settings = ELSMysql::whereIn('id', $ids)->field($field)->select();
            if (!$settings) {
                return;
            }
            $settings = collection($settings)->toArray();
            //mysql里面值为null的字段存储到mongodb会变成字符串的 "NULL"，存储前进行处理
            foreach ($settings as &$setting) {
                $setting['id'] = (int)$setting['id'];
                $setting['description'] = $setting['description'] ?? '';
                $lastId = $setting['id'];
            }
//            ELSMongo::insertAll($settings);
            $collection = mongo('ebay_listing_setting');
            foreach ($settings as $k => $st) {
                $collection->updateOne(['id'=>$st['id']],['$set'=>$st],['upsert'=>true]);
            }
            Cache::handler()->set('mysql_mongodb_setting_id',$lastId);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

}
