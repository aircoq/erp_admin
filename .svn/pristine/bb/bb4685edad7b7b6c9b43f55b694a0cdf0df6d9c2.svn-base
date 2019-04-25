<?php


namespace app\goods\task;

use app\common\exception\TaskException;
use app\index\service\AbsTasker;
use app\goods\service\GoodsBrandsLink;

class BrandLinkCategorySync extends AbsTasker
{
    public function getName()
    {
        return "同步品连分类";
    }

    public function getDesc()
    {
        return "同步品连分类";
    }

    public function getCreator()
    {
        return '詹老师';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        try {
            $goodsBrandsLink = new GoodsBrandsLink();
            $goodsBrandsLink->brandLinksCategorySync();
        } catch (\Exception $e) {
            throw new TaskException($e->getMessage());
        }
    }
}