<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/11/19
 * Time: 10:25
 */

namespace app\publish\task;


use app\common\model\ChannelUserAccountMap;
use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayListing;
use app\index\service\AbsTasker;

class EbayBackWriteCreateId extends AbsTasker
{
    public function getName()
    {
        return "ebay listing回写创建者id";
    }

    public function getDesc()
    {
        return "ebay listing回写创建者id";
    }

    public function getCreator()
    {
        return "wlw2533";
    }

    public function getParamRule()
    {
        return [];
    }


    public function execute()
    {
        set_time_limit(0);
        //账号销售员映射关系
        $whAccountSellerMap['channel_id'] = 1;
        $whAccountSellerMap['warehouse_type'] = 1;
        $accountSellers = ChannelUserAccountMap::where($whAccountSellerMap)->column('seller_id','account_id');
        //以账号为维度查询
        $whAccount['is_invalid'] = 1;
        $whAccount['account_status'] = 1;
        $accountIds = EbayAccount::where($whAccount)->column('id');
        $wh['draft'] = 0;
        $wh['item_id'] = ['neq',0];
        $wh['application'] = 1;
        $wh['realname'] = ['in',[0,1]];
        foreach ($accountIds as $accountId) {
            if (!isset($accountSellers[$accountId])) {
                continue;
            }
            $wh['account_id'] = $accountId;
            EbayListing::update(['realname'=>$accountSellers[$accountId]],$wh);
        }
    }

}