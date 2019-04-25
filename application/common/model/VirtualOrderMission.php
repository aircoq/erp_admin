<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/9/29
 * Time: 9:31
 */

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use think\db\Query;
use erp\ErpModel;




class VirtualOrderMission extends ErpModel
{

    const STATUS = [
        0 => '待审核',
        1 => '待分配负责人',
        2 => '待分配买家',
        3 => '待执行',
        4 => '已完成',
        5 => '已取消',
        7 => '待留评',
        -1 => '全部',
    ];

    const STATUS_US = [
        0 => '待审核',
        1 => '待分配负责人',
        2 => '待分配买家',
        3 => 'Pending',
        4 => 'Completed',
        5 => 'Cancelled',
        7 => 'Pending review',
        -1 => 'All',
    ];

    const status_assigned = 1;
    const status_distributed = 2;
    const status_executed = 3;
    const status_done = 4;
    const status_expired = 5;


    public function scopeVirtualOrderApply(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('a.seller_id', 'in', $params);
        }
    }


    public function getOperatorNameAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['operator_id']);
        if ($user) {
            return $user['realname'];
        }
        return '';
    }

    public function getTaskIdNameAttr($value, $data)
    {
        if($data['type'] == 3){
            return (new VirtualOrderUser())->where('id',$data['task_id'])->value('realname');
        }else{
            return (new LocalBuyerAccount())->where('id',$data['task_id'])->value('username');
        }
        return '';

    }

    public function getTaskerName($type,$taskId)
    {
        $data = [
            'type' => $type,
            'task_id' => $taskId,
        ];
        return $this->getTaskIdNameAttr([],$data);
    }

    public function getFunctionaryerName($ids)
    {
        $data['functionary_id'] = $ids;
        return $this->getFunctionaryIdNameAttr([],$data);
    }

    public function getFunctionaryIdNameAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['functionary_id']);
        if ($user) {
            return $user['realname'];
        }
        return '';

    }

    public function getChannelAttr($value, $data)
    {
        return Cache::store('channel')->getChannelName($data['channel_id']);
    }

    public function getStatusTxtAttr($value, $data)
    {
        return self::STATUS[$data['status']];
    }

    public function add($data){
        return $this->insert($data);
    }

}