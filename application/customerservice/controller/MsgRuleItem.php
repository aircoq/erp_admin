<?php
namespace app\customerservice\controller;

use app\common\controller\Base;
use app\common\cache\Cache;


/**
 * @module 客服管理
 * @title 站内信/评价自动发送规则匹配项
 */
class MsgRuleItem extends Base
{

    /**
     * @title 自动发送规则匹配项列表
     * @author tanbin
     * @method GET
     * @url /msg-rule-items
     */
    public function index()
    {        
        $ruleItemList = Cache::store('rule')->getMsgRuleItem();       
        $where[] = ["is_hidden", "==", 0];
        $result = Cache::filter($ruleItemList, $where, 'id,name,statement,code,type,rule_type,classified');

        return json($result, 200);
    }
}