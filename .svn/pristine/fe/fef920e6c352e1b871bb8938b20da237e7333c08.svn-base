<?php
namespace app\report\task;

use app\index\service\AbsTasker;
use app\report\service\StatisticGoods;
use think\Exception;
use app\common\exception\TaskException;
use app\report\service\FirstOrderSkuListService;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/20
 * Time: 19:36
 */
class PackageSkuAverageWeight extends AbsTasker
{
    public function getCreator() {
        return 'libaimin';
    }

    public function getDesc() {
        return '统计下载包裹sku平均重量';
    }

    public function getName() {
        return '统计下载包裹sku平均重量';
    }

    public function getParamRule() {
        return [];
    }

    public function execute() {
        try {
            $api = new StatisticGoods();
            $api->getPackageSkuAverageWeight();
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }
}