<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/12
 * Time: 18:09
 */

namespace app\goods\queue;

use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsDiscount;
use think\Exception;

class GoodsDiscountImportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "跌价商品数据导入";
    }

    public function getDesc(): string
    {
        return "跌价商品数据导入";
    }

    public function getAuthor(): string
    {
        return "朱达";
    }

    public function execute()
    {
        try {
            $good = new GoodsDiscount();
            $good->saveAll($this->params);
        }catch (Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }
}