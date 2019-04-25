<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-15
 * Time: 下午2:30
 */

namespace app\publish\task;

use app\common\service\UniqueQueuer;
use app\common\exception\TaskException;
use app\common\model\amazon\AmazonHeelSaleLog as AmazonHeelSaleLogModel;
use app\index\service\AbsTasker;
use app\publish\queue\AliexpressSkuMapQueue;
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use app\publish\queue\AmazonHeelSaleUpdatePriceQueuer;


class AmazonHeelSaleModifyPrice extends AbsTasker
{
    public function getName()
    {
        return "Amazon跟卖自动调价";
    }

    public function getDesc()
    {
        return "Amazon跟卖自动调价";
    }

    public function getCreator()
    {
        return "hao";
    }

    public function getParamRule()
    {
        return [];
    }



    public function execute()
    {
        set_time_limit(0);
        try {
            $page=1;
            $pageSize=100;


            //跟卖成功,并且最低低价大于0
            $where=[
                'type' => ['=',1],
                'price_status' => ['=', 1],
                'quantity_status' => ['=', 1],
                'status' => ['in', [1,4]],
                'lowest_price' => ['>',0],
            ];

            $time = time();

            do{
                $heelSale = (new AmazonHeelSaleLogModel())->field('id,account_id,asin,price,sku,lowest_price,modify_price_type,modify_price')->where($where)->page($page,$pageSize)->select();

                if(empty($heelSale))
                {
                    break;
                }else{
                    foreach ($heelSale as $key => $val){
                        $val = is_object($val)?$val->toArray():$val;

                        //销售价
                        $price = $val['price'];
                        //最低低价
                        $lowest_price = $val['lowest_price'];

                        if($val['modify_price_type']){

                            $params = [
                                'id' => $val['id'],
                                'account_id' => $val['account_id'],
                                'asin' => $val['asin'],
                                'lowest_price' => $lowest_price,
                                'seller_sku' => $val['sku'],
                                'modify_price_type' => $val['modify_price_type'],//百分比调价、金额调价
                                'price' => $price,
                                'modify_price' => $val['modify_price'],
                            ];

                            //加入跟卖自动调价队列
                            (new UniqueQueuer(AmazonHeelSaleUpdatePriceQueuer::class))->push($params);
                        }
                    }

                    $page = $page + 1;
                }

            }while(count($heelSale)==$pageSize);

        }catch (Exception $exp){
            throw new TaskException($exp->getMessage());
        }
    }
}