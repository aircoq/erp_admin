<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-6-1
 * Time: 上午11:37
 */

namespace app\publish\queue;


use app\common\cache\Cache;
use app\common\model\shopee\ShopeeDiscount;
use app\common\model\shopee\ShopeeDiscountDetail;
use app\common\service\SwooleQueueJob;
use app\publish\service\ShopeeApiService;
use think\Exception;

class ShopeeDiscountQueue extends SwooleQueueJob
{
    private $config=[];
    private $account_id=0;
    public function getName(): string
    {
        return 'shopee折扣队列';
    }

    public function getDesc(): string
    {
        return 'shopee折扣队列';
    }

    public function getAuthor(): string
    {
        return 'joy';
    }

    public function execute()
    {

        try{
            $params = $this->params;
            if($params){
                list($account_id,$discount_id)=explode('|',$params);
                $config = Cache::store('ShopeeAccount')->getAccounts($account_id);
                if($config){
                    $this->config = $config;
                    $this->account_id = $account_id;
                    $this->getDiscountDetail($discount_id);
                }
            }
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }
    }
    private function getDiscountDetail($discount_id,$page=1,$total=0){
        $params=[
            'discount_id'=>(int)$discount_id,
            'pagination_offset'=>$page,
            'pagination_entries_per_page'=>100,
        ];

        $response = ShopeeApiService::GetDiscountDetail($this->config,$params);

        if(isset($response['items']) && $response['items']){
            $items = $response['items'];
            $total = $total  + count($items);
            ShopeeDiscountDetail::saveData($discount_id,$items);
        }

        if(isset($response['more']) && $response['more']==true){
            $page = $page + 1;
            self::getDiscountDetail($discount_id,$page,$total);
        }elseif(isset($response['more']) && $response['more']==false){
            ShopeeDiscount::where('discount_id',$discount_id)->setField('total',$total);
        }
    }
}