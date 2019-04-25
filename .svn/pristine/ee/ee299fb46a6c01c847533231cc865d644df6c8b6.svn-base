<?php


namespace app\goods\queue;


use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use app\goods\service\GoodsImport;

class GoodsCvsExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "商品导出csv队列";
    }

    public function getDesc(): string
    {
        return "商品导出csv队列";
    }

    public function getAuthor(): string
    {
        return "詹老师";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }

    public function execute()
    {
        try {
            $serv = new GoodsImport();
            $field = empty($this->params['field']) ? [] : json_decode($this->params['field'], true);
            $header = $serv->getExportSkuField($field);
            $serv->getExportSkuData($this->params,$header);
        }catch (\Exception $ex){
            Cache::handler()->hset(
                'GoodsImport:goods_export_csv',
                'error_'.time(),
                $ex->getMessage());
        }
    }
}