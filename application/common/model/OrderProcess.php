<?php
namespace app\common\model;

use app\common\service\OrderStatusConst;
use think\Model;
use think\Db;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class OrderProcess extends Model
{
    /**
     * 订单
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /** 状态位查询
     * @param $type
     * @return array
     */
    public function getCondition($type = "")
    {
        switch ($type) {
            case 'audit':  //待审核
                $condition = " type = 0 and code_prefix = 0";
                break;
            case "procurement":  //采购状态
                $condition = " type = 0 and code_prefix = 17";
                break;
            case "delivery":  //发货状态
                $condition = " type = 0 and code_prefix = 7";
                break;
            case "refund":  //退款状态
                $condition = " type = 0 and code_prefix = 15";
                break;
            default:
                $condition = " type = 0";
                break;
        }
        $result = $this->where($condition)->order('sort desc,id asc')->select();
        $new_list = [];
        foreach ($result as $k => $v) {
            $temp['remark'] = $v['remark'];
            $temp['code'] = $v['code_prefix'] . '_' . $v['code_suffix'];
            array_push($new_list, $temp);
        }
        return $new_list;
    }

    /** 获取状态名称
     * @param $status
     * @return mixed|string
     */
    public function getStatusName($status)
    {
        $status = decbin($status);
        $length = strlen($status);
        $code_prefix = bindec(substr($status, 0, $length - 17));
        $code_suffix = bindec(substr($status, $length - 16, $length - 1));
        //查库
        $result = $this->where(['code_prefix' => $code_prefix, 'code_suffix' => $code_suffix, 'type' => 0])->find();
        if (!empty($result)) {
            return $result['remark'];
        }else{
            switch($status){
                case OrderStatusConst::IsPaying:
                    return '标记为已付款';
                    break;
                case OrderStatusConst::Audited:
                    return '标记为已审';
                    break;
                default:
                    return '';
                    break;
            }
        }
    }

    /** 获取状态名称（通过字符串）
     * @param $status
     * @return mixed|string
     */
    public function getStatusNameByStr($status)
    {
        $code_prefix = substr($status, 0, strpos($status,'_'));
        $code_suffix = substr($status, (strpos($status,'_')+1));
        //查库
        $result = $this->where(['code_prefix' => $code_prefix, 'code_suffix' => $code_suffix, 'type' => 0])->find();
        if (!empty($result)) {
            return $result['remark'];
        }else{
            switch($status){
                case OrderStatusConst::IsPaying:
                    return '标记为已付款';
                    break;
                case OrderStatusConst::Audited:
                    return '标记为已审';
                    break;
                default:
                    return '';
                    break;
            }
        }
    }
}