<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/9/26
 * Time: 11:41
 */

namespace app\publish\task;


use app\index\service\AbsTasker;

class ShopeePublishStatistics extends AbsTasker
{
    public function getName()
    {
        return 'shopee刊登商品统计';
    }

    public function getDesc()
    {
        return 'shopee刊登商品统计';
    }

    public function getCreator()
    {
        return 'wlw2533';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {

    }

}