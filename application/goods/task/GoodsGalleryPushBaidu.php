<?php


namespace app\goods\task;

use app\common\exception\TaskException;
use app\index\service\AbsTasker;
use app\goods\service\GoodsGalleryHash;

class GoodsGalleryPushBaidu extends AbsTasker
{

    public function getCreator()
    {
        return '詹老师';
    }

    public function getDesc()
    {
        return '图片推送到百度库';
    }

    public function getName()
    {
        return '图片推送到百度库';
    }

    public function getParamRule()
    {
        return [
            'type|图片类型' => 'require|select:商品主图:spu,所有主图:all',
        ];
    }

    public function execute()
    {
        try{
            $GoodsGalleryHash = new GoodsGalleryHash();
            $GoodsGalleryHash->pushBaidu();
        }catch (\Exception $ex){
            throw new TaskException($ex->getMessage());
        }
    }
}