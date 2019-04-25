<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/3/28
 * Time: 17:03
 */

namespace app\publish\queue;


use app\common\service\SwooleQueueJob;
use app\common\model\ebay\EbayListingSetting as ELSMysql;
use app\common\model\mongodb\ebay\EbayListingSetting as ELSMongo;

class EbaySettingMove2Mongo extends SwooleQueueJob
{
    public function getName():string
    {
        return 'ebay setting数据转移到MongoDb';
    }

    public function getDesc():string
    {
        return 'ebay setting数据转移到MongoDb';
    }

    public function getAuthor():string
    {
        return 'wlw2533';
    }

    public function execute()
    {
        $id = $this->params;
        //获取设置信息
        $field = 'id,description';
        $setting = ELSMysql::where('id', $id)->field($field)->find();
        if (!$setting) {
            return;
        }
        $setting = $setting->toArray();
        //mysql里面值为null的字段存储到mongodb会变成字符串的 "NULL"，存储前进行处理
        $setting['description'] = $setting['description'] ?? '';
        $setting['id'] = (int)$setting['id'];
        mongo('ebay_listing_setting')->updateOne(['id'=>$setting['id']],['$set'=>$setting],['upsert'=>true]);
    }
}