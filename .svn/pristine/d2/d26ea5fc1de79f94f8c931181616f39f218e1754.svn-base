<?php
namespace app\report\task;

use app\index\service\AbsTasker;
use app\report\service\MonthlyTargetAmountService;
use think\Exception;
use app\common\exception\TaskException;
use app\report\service\FirstOrderSkuListService;
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/9/20
 * Time: 19:36
 */
class WriteBackMonthlyTargetRanking extends AbsTasker
{
    public function getCreator() {
        return 'libaimin';
    }

    public function getDesc() {
        return '员工销售额排名回写';
    }

    public function getName() {
        return '员工销售额排名回写';
    }

    public function getParamRule() {
        return [];
    }

    public function execute() {
        try {
            $skuListService = new MonthlyTargetAmountService();
            $skuListService->progressTarget();
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }
}