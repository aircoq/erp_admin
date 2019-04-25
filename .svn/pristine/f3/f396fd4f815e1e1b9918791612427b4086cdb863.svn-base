<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\MsgRuleItem as MsgRuleItemModel;
use app\common\model\MsgRuleSet as MsgRuleSetModel;
use app\common\model\MsgRuleSet;

/**
 * 所有规则信息
 * Created by tanbin.
 * User: 1
 * Date: 2017/04/10
 * Time: 10:45
 */
class Rule extends Cache
{
    /** 获取站内信自动发送规则条件
     * @param int $id
     * @return array|mixed
     */
    public function getMsgRuleItem($id = 0)
    {
        if ($this->redis->exists('cache:MsgRuleItem')) {
            if (!empty($id)) {
                $result = json_decode($this->redis->get('cache:MsgRuleItem'),true);
                return isset($result[$id]) ? $result[$id] : [];
            }
            return json_decode($this->redis->get('cache:MsgRuleItem'),true);
        }
        $msgRuleItemModel = new MsgRuleItemModel();
        $result = $msgRuleItemModel->order('sort asc')->select();
        $new_array = [];
        foreach($result as $k => $v){
            $new_array[$v['id']] = $v;
        }
        $this->redis->set('cache:MsgRuleItem', json_encode($new_array));
        if(!empty($id)){
            return $new_array[$id];
        }
        return $new_array;
    }
    
    /**
     * 获取触发规则统计列表（统计每个触发时间下面的规则条数）
     * @return array|mixed
     */

    public function getTriggerRuleCount()
    {
        if($this->redis->exists('cache:triggerRuleCount')){
            $result = json_decode($this->redis->get('cache:triggerRuleCount'),true);
            return $result;
        }
        //查表
        $result = [];
        $result = MsgRuleSetModel::field("trigger_rule,count('id') as count")->group('trigger_rule')->select();
        if($result){
            $data = [];
            $RuleList = array_merge(MsgRuleSetModel::$TRIGGER_RULE,MsgRuleSetModel::$TRIGGER_RULE_FEEDBACK);
            foreach ($result as $k=>$v){              
                   $result[$k]['trigger_rule_str'] = isset($RuleList[$v['trigger_rule']])?$RuleList[$v['trigger_rule']]:'';                
            }
        }

        $this->redis->set('cache:msgRuleSetCount',json_encode($result));
        return $result; 
    }
    
    
    /**
     * @param int $type
     * @return array|mixed
     */
    public function getMsgRuleSet($type = '')
    {   
        if ($this->persistRedis->exists('cache:MsgRuleSet')) {
        
            $result = json_decode($this->persistRedis->get('cache:MsgRuleSet'),true);
             
            if (!empty($type)) {
                return isset($result[$type]) ? $result[$type] : [];
            }else{
                return $result;
            }
        }
        $msgRuleSetModel = new MsgRuleSet();
        $result = $msgRuleSetModel->field('id,title,trigger_rule,delay_time_send,sort')->with('item')->where(['status' => 0])->order('sort asc')->select();
        $new_array = [];
        foreach($result as $k => $v){
            if(!isset($new_array[$v['trigger_rule']])){
                $new_array[$v['trigger_rule']] = [];
            }
            array_push($new_array[$v['trigger_rule']],$v);
        }
        $this->persistRedis->set('cache:MsgRuleSet', json_encode($new_array));
        if(!empty($type)){
            return isset($new_array[$type]) ? $new_array[$type] : [];
        }
        return $new_array;
    }
    
}


