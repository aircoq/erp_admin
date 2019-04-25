<?php


namespace app\goods\queue;

use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsGalleryPhash;
use app\common\cache\Cache;
use think\Exception;

class GoodsPhashQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "执行phash";
    }

    public function getDesc(): string
    {
        return "执行phash";
    }

    public function getAuthor(): string
    {
        return "詹老师";
    }

    public function execute()
    {
        try {
            $good = new GoodsGalleryPhash();
            $good->runWritePhashAndCreateCache($this->params);
        }catch (Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }
}