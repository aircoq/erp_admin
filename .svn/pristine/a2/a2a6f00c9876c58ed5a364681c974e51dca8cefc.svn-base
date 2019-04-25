<?php
namespace app\api\controller;

use think\Exception;
use think\Request;
use app\common\controller\Base;
use app\api\service\GuanYi;

/**
 * @title xxxx
 * Class Guanyiwarehouse
 * @package app\api\controller
 */
class  Guanyiwarehouse extends Base
{
    private $fromty = [
        '01', // 采购入库
        '02', // 盘盈入库
        '03', // 调拨入库
        '04', // 销售退货入库
        '09', // 其它入库
        '11', // 销售出库(暂时不用)
        '12', // 盘亏出库
        '13', // 调拨出库
        '14', // 采购退货出库
        '19'  // 其它出库
    ];

    /**
     * @title 库存异动接口
     * @author zengsh
     * @package app\api\controller
     * @method POST
     * @url api/Guanyiwarehouse/inventoryChanged
     * @noauth
     */
    public function inventoryChanged(Request $request)
    {
        $guanYi = new GuanYi();
        $param = $request->getInput();
        $parent_log_id = $guanYi->AddWmsLog($param, 'inventoryChanged', 'purchase_order', md5($param));
        $tempArr = array();
        parse_str($param, $tempArr); 
        $lists = json_decode($tempArr['message'], true);     
        if (empty($lists)) {
            $message = '传输数据为空';
            $guanYi->updateWmsLog($parent_log_id, 2, $message);
            exit; // 终止程序
        }
        try {
            $flag = 0;
            foreach($lists as $list) {
                $result = true;
                $list['parent_log_id'] = $parent_log_id;
                if ($list['fromty'] == '01') { // 采购入库
                    $result = $guanYi->inventoryExecute($list);
                } else if (in_array($list['fromty'], ['11', '12', '13', '14', '19'])) { // 出库
                    $result = $guanYi->stockOut($list);
                } else if (in_array($list['fromty'], ['02', '03', '04', '09'])){ // 入库
                    $result = $guanYi->stockIn($list);
                }
                if (!is_bool($result)) {
                    $flag++;
                }
            }
            if ($flag) {
                throw new Exception('执行失败次数为 '. $flag);
            }
            $guanYi->updateWmsLog($parent_log_id, 1);
            return json(['result' => true, 'message' => '操作成功']);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $guanYi->updateWmsLog($parent_log_id, 2, $message);
            return json(['result' => true, 'message' => '处理失败']);
        }
    }


    /**
     * @title 发货回传接口
     * @author zengsh
     * @package app\api\controller
     * @method POST
     * @url api/Guanyiwarehouse/deliveryReturn
     * @noauth
     */
    public function deliveryReturn(Request $request)
    {
        $guanYi = new GuanYi();
        $param = $request->getInput();
        $parent_log_id = $guanYi->AddWmsLog($param, 'deliveryReturn', 'package', md5($param));
        $tempArr = array();
        parse_str($param, $tempArr);
        $lists = json_decode($tempArr['message'], true);
        if (empty($lists)) {
            $message = '传输数据为空';
            $guanYi->updateWmsLog($parent_log_id, 2, $message);
            exit; // 终止程序
        }
        try {
            $flag = 0;
            foreach ($lists as $list) {
                $list['parent_log_id'] = $parent_log_id;
                $result = $guanYi->deliveryExecute($list);
                if (!is_bool($result)) {
                    $flag++;
                }
            }
            if ($flag) {
                throw new Exception('执行不成功条数为 ' . $flag);
            }
            $guanYi->updateWmsLog($parent_log_id, 1);
            return json(['result' => true, 'message' => '操作成功']);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $guanYi->updateWmsLog($parent_log_id, 2, $message);
            return json(['result' => true, 'message' => '执行失败:'.$e->getMessage()]);
        }
    }
    
    /**
     * @title 拒单接口
     * @author Rondaful
     * @package app\api\controller
     * @method POST
     * @url api/Guanyiwarehouse/rejectPackage
     */
    public function rejectPackage(Request $request)
    {
        $guanYi = new GuanYi();
        $param = $request->getInput();
        $parent_log_id = $guanYi->AddWmsLog($param, 'rejectPackage', 'package', md5($param));
        $tempArr = array();
        parse_str($param, $tempArr);
        $lists = json_decode($tempArr['message'], true);        
        if (empty($lists)) {
            $message = '传输数据为空';
            $guanYi->updateWmsLog($parent_log_id, 2, $message);
            exit; // 终止程序
        }
        try {
            $flag = 0;
            foreach ($lists as $list) {
                $list['parent_log_id'] = $parent_log_id;
                $result = $guanYi->rejectExecute($list);
                if (!is_bool($result)) {
                    $flag++;
                }
            }
            if ($flag) {
                throw new Exception('执行不成功条数为 ' . $flag);
            }
            $guanYi->updateWmsLog($parent_log_id, 1);
            return json(['result' => true, 'message' => '操作成功']);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $guanYi->updateWmsLog($parent_log_id, 2, $message);
            return json(['result' => true, 'message' => '执行失败']);
        }
    }

}