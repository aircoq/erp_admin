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
use app\publish\queue\AmazonHeelSaleQuantityQueuer;
use app\publish\queue\AmazonHeelSalePriceQueuer;
use app\publish\queue\AmazonHeelSaleResultQueuer;
use think\Exception;

class AmazonHeelSaleResult extends AbsTasker
{
    public function getName()
    {
        return "Amazon跟卖结果获取-停止使用";
    }

    public function getDesc()
    {
        return "Amazon跟卖结果获取-停止使用";
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

            $where = [
                'status' => ['in', [0,3]],
                'type' => 1,
            ];

            do{
                $heelSale = (new AmazonHeelSaleLogModel())->field('id,quantity_status, price_status')->where($where)->where('submission_id > 0 and request_number < 100 and ( price_status =0 or quantity_status = 0)')->page($page,$pageSize)->select();

                if(empty($heelSale))
                {
                    break;
                }else{

                    //重新加入获取结果队列
                    $this->queue($heelSale);
                    $page = $page + 1;
                }

            }while(count($heelSale)==$pageSize);
            
        }catch (Exception $exp){
            throw new TaskException($exp->getMessage());
        }
    }


    public function queue($heelSale){

        foreach ($heelSale as $key => $val){

            $val = is_object($val)?$val->toArray():$val;
            if(isset($val['id']) && $val['id']){
                (new UniqueQueuer(AmazonHeelSaleResultQueuer::class))->push($val['id']);


               /* if(empty($val['quantity_status'])) {
                    //上报库存
                    (new UniqueQueuer(AmazonHeelSaleQuantityQueuer::class))->push($val['id']);
                }

                if(empty($val['price_status'])) {
                    //上报价格
                    (new UniqueQueuer(AmazonHeelSalePriceQueuer::class))->push($val['id']);
                }*/

            }
        }
    }
}