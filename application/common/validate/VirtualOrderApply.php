<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/9/29
 * Time: 9:43
 */

namespace app\common\validate;

use \think\Validate;
use app\common\cache\Cache;

class VirtualOrderApply extends Validate
{


    public $seller = [];
    protected $rule = [
        ['channel_id', 'require|checkChannel', '平台id不能为空|不存在该平台'],
        ['number', 'require|unique:VirtualOrderApply', '虚拟订单编号不能为空|虚拟订单编号已存在'],
        ['reason', 'require', '申请原因不能为空'],
        ['seller_id', 'require|checkSellerId', '销售员id不能为空！'],
//        ['estimate_total_cost', 'require', '预估总费用不能为空！'],
        ['creator_id', 'require', '创建人id不能为空']

    ];
    protected function checkChannel($channelId)
    {
        $channelList = Cache::store('channel')->getChannel();
        $aMaps = [];
        foreach ($channelList as $v) {
            $aMaps[$v['id']] = $v;
        }
        if (!isset($aMaps[$channelId])) {
            return false;
        }
        return true;
    }

    protected function checkSellerId($sellerId)
    {
        $this->seller = Cache::store('user')->getOneUser($sellerId);
        if (!$this->seller) {
            return false;
        }
        $this->seller['department_name'] = '';
        if (isset($this->seller['department_id'])) {
            $department = Cache::store('department')->getDepartment($sellerId);
            if ($department) {
                $this->seller['department_name'] = $department['name'];
            }
        }
        return true;
    }
}