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
use app\common\model\aliexpress\AliexpressProduct;
use app\report\service\StatisticShelf;
use app\report\service\StatisticPicking;
use app\index\service\AbsTasker;
use app\publish\queue\AliexpressSkuMapQueue;
use think\Exception;

class AliexpressProductOnSelling extends AbsTasker
{
    public function getName()
    {
        return "Aliexpress平台当天销售人员统计";
    }

    public function getDesc()
    {
        return "Aliexpress平台当天销售人员统计";
    }

    public function getCreator()
    {
        return "haolongfei";
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


            //开始时间
            $starTime = strtotime(date('Y-m-d',strtotime("-1 day")));
            $endTime = strtotime(date('Y-m-d 23:59:59', strtotime("-1 day")));

            $where['status'] = ['=', 2];
            $where['product_status_type'] = ['in',[1,2]];

            do{
                $productList = (new AliexpressProduct())->field('count(*) as num,account_id,goods_id,salesperson_id,product_status_type')->whereBetween('publish_time', [$starTime, $endTime])->where($where)->group('salesperson_id,account_id,product_status_type')->page($page,$pageSize)->select();

                if(empty($productList))
                {
                    break;
                }else{
                    foreach ($productList as $key => $val){

                        $val = json_decode($val, true);

                        //上架
                        if($val['product_status_type'] == '上架'){
                            StatisticShelf::addReportShelf(4,$val['account_id'],$val['salesperson_id'],$val['goods_id'],$val['num'],0,$starTime);
                        }

                        //下架
                        if($val['product_status_type'] == '下架'){
                            StatisticPicking::addReportPicking(4,$val['account_id'],$val['salesperson_id'],$val['goods_id'],$val['num'],0,$starTime);
                        }
                    }

                    $page = $page + 1;
                }

            }while(count($productList)==$pageSize);

            return true;
        }catch (Exception $exp){
            throw new TaskException($exp->getMessage());
        }
    }
}