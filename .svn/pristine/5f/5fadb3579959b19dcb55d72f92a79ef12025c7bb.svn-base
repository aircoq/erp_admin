<?php
namespace app\report\task;

use app\index\service\AbsTasker;
use think\Exception;
use app\common\exception\TaskException;
use app\report\service\FirstOrderSkuListService;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/20
 * Time: 19:36
 */
class SkuStatisticReport extends AbsTasker
{
    public function getCreator() {
        return '何程';
    }

    public function getDesc() {
        return '首次出单SKU列表回写';
    }

    public function getName() {
        return '首次出单SKU列表回写';
    }

    public function getParamRule() {
        return [];
    }

    public function execute() {
        try {
            $skuListService = new FirstOrderSkuListService();
            $skuListService->searchDataInsert();
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }
}