<?php

namespace app\report\task;

use app\common\model\GoodsSku;
use app\index\service\AbsTasker;
use app\report\service\StatisticGoods;
use think\Exception;
use app\common\exception\TaskException;
use app\report\service\FirstOrderSkuListService;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/3/19
 * Time: 19:36
 */
class WriteBackSkuSizeTask extends AbsTasker
{
    public function getCreator()
    {
        return 'libaimin';
    }

    public function getDesc()
    {
        return '回写商品系统中的SKU大小';
    }

    public function getName()
    {
        return '回写商品系统中的SKU大小';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        set_time_limit(0);
        try {
            $pageSize = 1000;
            $where = [
                'auto_update_size_time' => 0,
            ];
            $goodsSkuModel = new GoodsSku();
            $count = $goodsSkuModel->where($where)->count();
            $times = ceil($count / $pageSize);
            for ($i = 1; $i <= $times; $i++) {
                $skuIds = $goodsSkuModel->where($where)->order('id')->page($i,$pageSize)->column('id');
                $service = new StatisticGoods();
                $service->updatePackageSkuSize($skuIds);
            }
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }
}