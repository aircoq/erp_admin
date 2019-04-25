<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-5-29
 * Time: 下午12:28
 */

namespace app\publish\task;


use app\common\model\shopee\ShopeeAccount;
use app\common\model\shopee\ShopeeDiscount;
use app\common\service\CommonQueuer;
use app\index\service\AbsTasker;
use app\publish\queue\ShopeeDiscountQueue;
use app\publish\service\ShopeeApiService;
use think\Exception;

class ShopeeGetDiscountsList extends AbsTasker
{
    public function getName()
    {
        return 'shopee折扣任务';
    }

    public function getDesc()
    {
        return 'shopee折扣任务';
    }

    public function getCreator()
    {
        return 'joy';
    }

    public function getParamRule()
    {
       return [];

    }

    public function execute()
    {
        set_time_limit(0);
        try{
            $where['platform_status']=['=',1];
            $accounts = ShopeeAccount::where($where)->select();

            if($accounts){
                foreach ($accounts as $account){
                    $this->getDiscountList($account->toArray());
                }
            }
        }catch (Exception $exp){
            throw new Exception("{$exp->getMessage()}");
        }
    }
    private function getDiscountListByStatus($account){
        try{
            $status = ShopeeDiscount::STATUS;
            foreach ($status as  $statu){
                $this->getDiscountList($account,$statu);
            }
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }

    }
    public function getDiscountList($account,$status='ALL',$page=1){
        try{
            $params=[
                'discount_status'=>$status,
                'pagination_offset'=>$page,
                'pagination_entries_per_page'=>100,
            ];
            $response = ShopeeApiService::getDiscountList($account,$params);
            if(isset($response['discount']) && $response['discount']){
                $discounts = $response['discount'];
                $this->pushDiscountToQueue($account['id'],$discounts);
            }
            if(isset($response['more']) && $response['more']){
                $page = $page + 1;
                self::getDiscountList($account,$status,$page);
            }
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }

    }
    private function pushDiscountToQueue($account_id,$discounts){
        try{
            $queueDriver = (new CommonQueuer(ShopeeDiscountQueue::class));
            foreach ($discounts as $discount){
                $queue = $account_id.'|'.$discount['discount_id'];
                $discount['account_id'] = $account_id;
                ShopeeDiscount::saveData($discount);
                $queueDriver->push($queue);
            }
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }
    }

}