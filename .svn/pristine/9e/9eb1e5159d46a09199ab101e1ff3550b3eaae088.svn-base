<?php


namespace app\goods\queue;

use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsGalleryDhash;
use app\common\cache\Cache;
use think\Exception;

class GoodsDhashQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "执行Dhash";
    }

    public function getDesc(): string
    {
        return "执行Dhash";
    }

    public function getAuthor(): string
    {
        return "詹老师";
    }

    public function execute()
    {
        try {
            $good = new GoodsGalleryDhash();
            $good->runWritePhashAndCreateCache($this->params);
        }catch (Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }
}