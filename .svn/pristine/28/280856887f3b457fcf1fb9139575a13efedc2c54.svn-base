<?php
namespace app\customerservice\service;

use app\common\cache\Cache;
use think\Exception;
use app\order\service\OrderRuleExecuteService;

/**
 * Created by tb.
 * User: PHILL
 * Date: 2017/05/08
 * Time: 10:14
 */
class MsgRuleCheckHelp
{

    /**
     * 检测规则
     * 
     * @param
     *            $order
     * @param
     *            $detail
     * @param
     *            $log
     * @param
     *            $rule_type
     * @return array
     * @throws \app\common\cache\Exception
     */
    public static function checkRule($order, $detail, $log, $rule_type = 0)
    {
        if (empty($rule_type)) {
            $ruleSetList = Cache::store('Rule')->getMsgRuleSet(); // 查询启用的规则 操作动作
        } else {
            $ruleSetList[] = Cache::store('Rule')->getMsgRuleSet($rule_type); // 查询启用的规则
        }
        
        foreach ($ruleSetList as $key => $value) {
            $result = self::ruleProcess($order, $detail, $value, $log, true);
            if ($result['state']) {
                $order = $result['order'];
                $detail = $result['detail'];
                $log = $result['log'];
            }
        }
        return [
            'state' => true,
            'order' => $order,
            'detail' => $detail,
            'log' => $log
        ];
    }

    /**
     * 规则的处理
     * 
     * @param
     *            $order
     * @param
     *            $detail
     * @param
     *            $ruleSetList
     * @param
     *            $log
     * @param
     *            $ban
     * @return array
     * @throws \app\common\cache\Exception
     */
    public static function ruleProcess($order, $detail, $ruleSetList, $log, $ban = false)
    {
        $orderRuleExecuteService = new OrderRuleExecuteService();
        
        $is_ok = false;
        $ruleItemList = Cache::store('rule')->getMsgRuleItem(); // 所有的items
        
        foreach ($ruleSetList as $k => $v) {
            // ------如果有时间，则判断时间 ----------------
            if (! empty($v['delay_time_send'])) {
                $delay_time_send = json_decode($v['delay_time_send']); // 延迟发送时间
                                                                       
                // 满足条件的模板
                $time = time();
                $time_ok = 0; // 是否满足条件[只要有一个就算满足]
                $templateList = []; // 满足条件的模板
                foreach ($delay_time_send as $ck_v) {
                    // 是否有匹配时间参数 check_time
                    if (! isset($order['order_time'])) {
                        $new_log = [
                            'message' => "订单[" . $order['id'] . "]没有定义匹配时间参数：order_time",
                            'operator' => '系统'
                        ];
                        array_push($log, $new_log);
                        continue;
                    }
                    if ($ck_v->day > 0) {
                        $order_check_time = $order['order_time'] + $ck_v->day * 3600 * 24;
                    }
                    if ($ck_v->hour > 0) {
                        $order_check_time = $order['order_time'] + $ck_v->hour * 3600;
                    }
                    if ($time >= $order_check_time) {
                        // 保存满足条件将可发送的模板
                        $templateList[] = [
                            'day' => $ck_v->day,
                            'hour' => $ck_v->hour,
                            'template_id' => $ck_v->template_id
                        ];
                        $time_ok = 1;
                    }
                }
                if ($time_ok == 0) {
                    continue;
                }
            } else {
                continue;
            }
            // ------如果有时间，则判断时间 ----------------
            
            // ------匹配规则----------------
            $action_type = $v['trigger_rule'];
            
            foreach ($v['item'] as $kk => $vv) {
                $ruleItem = isset($ruleItemList[$vv['rule_item_id']]) ? $ruleItemList[$vv['rule_item_id']] : [];
                
                $item_value = json_decode($vv['param_value'], true);
                $item_value = is_array($item_value) ? $item_value : [];
                $is_ok = $orderRuleExecuteService->check($ruleItem['code'], $item_value, $order, $detail, $is_ok);
                if (! $is_ok) {
                    self::log($v['title'], $vv['rule_item_id'] . '@@@' . $ruleItem['code'], $order, $detail);
                    break;
                }
            }
            if ($is_ok) {
                // 记录匹配成功的规则
                if (isset($order['rule_id'])) {
                    array_push($order['rule_id'], $v['id']);
                } else {
                    $order['rule_id'] = [
                        $v['id']
                    ];
                }
                // 匹配成功,把数据赋值上去,包括，条件成立
                $order['templates'] = $templateList; // 匹配成功的模板集合
                $new_log = [
                    'message' => '已匹配到模板',
                    'operator' => '系统'
                ];
                array_push($log, $new_log);
                break;
            }
        }
        if (! $is_ok) {
            $new_log = [
                'message' => '没有匹配到模板',
                'operator' => '系统'
            ];
            array_push($log, $new_log);
        }
        
        return [
            'state' => true,
            'order' => $order,
            'detail' => $detail,
            'log' => $log
        ];
    }

    /**
     * 记录日志
     * 
     * @param
     *            $rule
     * @param
     *            $item_id
     * @param
     *            $order
     * @param
     *            $detail
     */
    public static function log($rule, $item_id, $order, $detail)
    {
        $dir = 'msgRule';
        if (! is_dir(LOG_PATH . $dir) && ! mkdir(LOG_PATH . $dir, 0666, true)) {
            throw new Exception('目录创建不成功');
        }
        $logFile = LOG_PATH . "msgRule/msglog-" . date('Y-m-d') . ".log";
        file_put_contents($logFile, "-------规则为" . $rule . "的itemID为" . $item_id . "匹配不成功，订单数据" . "-------\r\n" . json_encode($order) . "\r\n-----详情数据为-----\r\n".json_encode($detail)."\r\n",FILE_APPEND);
    }
    
}