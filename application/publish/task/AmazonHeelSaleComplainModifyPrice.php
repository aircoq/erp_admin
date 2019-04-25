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
use app\common\model\amazon\AmazonHeelSaleComplain as AmazonHeelSaleComplainModel;
use app\index\service\AbsTasker;
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use app\publish\queue\AmazonHeelSaleComplainPriceQueuer;


class AmazonHeelSaleComplainModifyPrice extends AbsTasker
{
    public function getName()
    {
        return "Amazon反跟卖自动调价";
    }

    public function getDesc()
    {
        return "Amazon反跟卖自动调价";
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
                'is_delete' => ['=',0],
                'lowest_price' => ['>', 0],
                'modify_price_type' => ['>', 0],
            ];

            $time = time();

            do{
                $heelSale = (new AmazonHeelSaleComplainModel())->field('id,account_id,sku,price')->where($where)->page($page,$pageSize)->select();

                if(empty($heelSale))
                {
                    break;
                }else{
                    foreach ($heelSale as $key => $val){
                        $val = is_object($val)?$val->toArray():$val;

                        //加入跟卖自动调价队列
                        (new UniqueQueuer(AmazonHeelSaleComplainPriceQueuer::class))->push($val['id']);
                    }

                    $page = $page + 1;
                }

            }while(count($heelSale)==$pageSize);

        }catch (Exception $exp){
            throw new TaskException($exp->getMessage());
        }
    }
}