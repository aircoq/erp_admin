<?php
namespace app\customerservice\task;

use app\common\cache\Cache;
use app\common\model\ChannelUserAccountMap;
use app\common\model\ebay\EbayMessageGroup;
use app\common\service\ChannelAccountConst;
use app\index\service\AbsTasker;
use app\common\service\CommonQueuer;


class EbayMessageGroupAllocatCustomer extends AbsTasker{
    public function getName()
    {
        return "Ebay站内信分配客服";
    }

    public function getDesc()
    {
        return "Ebay站内信分配客服";
    }

    public function getCreator()
    {
        return "冬";
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        $where['customer_id'] = 0;
        $limit = 1000;
        //ebay客服主管用户ID；
        $customer_id = 261;
        $accountCustomer = [];

        $groupModel = new EbayMessageGroup();

        while(true) {
            $lists = $groupModel->where($where)->limit($limit)->field('id,account_id,customer_id')->select();
            if (empty($lists)) {
                break;
            }
            foreach ($lists as $group) {
                //找到对应的客服；
                if (!empty($accountCustomer[$group['account_id']])) {
                    $customer_id = $accountCustomer[$group['account_id']];
                } else {
                    $customer = ChannelUserAccountMap::where([
                        'account_id' => $group['account_id'],
                        'channel_id' => ChannelAccountConst::channel_ebay
                    ])->find();
                    if (!empty($customer) && !empty($customer['customer_id'])) {
                        $accountCustomer[$group['account_id']] = $customer['customer_id'];
                        $customer_id = $customer['customer_id'];
                    }
                }
                $group->update(['customer_id' => $customer_id], ['id' => $group['id']]);
            }

            if (count($lists) < $limit) {
                break;
            }
        }
    }
    
}