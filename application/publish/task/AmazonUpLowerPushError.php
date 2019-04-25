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
use app\common\model\amazon\AmazonUpOpenLog as AmazonUpOpenLogModel;
use app\index\service\AbsTasker;
use app\publish\queue\AliexpressSkuMapQueue;
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use app\common\model\amazon\AmazonListing;
use app\publish\service\AmazonCategoryXsdConfig;
use app\publish\queue\AmazonTimerUpperQueuer;
use app\publish\queue\AmazonTimerLowerQueuer;


class AmazonUpLowerPushError extends AbsTasker
{
    public function getName()
    {
        return "Amazon定时上下架推送错误信息-停止使用";
    }

    public function getDesc()
    {
        return "Amazon定时上下架推送错误信息-停止使用";
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

            $start_time = strtotime(date('Y-m-d H:00'));

            $end_time = $start_time+3600;
            $where=[
                'u.is_up_open' => ['in', [0,1]],
                'u.up_open_time' => ['between', [$start_time, $end_time]],
                'a.type' => 4,
                'a.status' => 1,
            ];

            //1.定时查询,检查是否有修改定时跟卖状态.如果正确就过滤.不正确.就再次推送队列.
            do{
                $upOpenLogModel = new AmazonUpOpenLogModel();

                $upOpenLog = $upOpenLogModel->alias('u')->field('u.id, u.listing_id, u.seller_sku, u.seller_status, u.up_open_time,a.seller_status as status')->join('amazon_heel_sale_log a','u.listing_id = a.listing_id','left')->where($where)->page($page++,$pageSize)->select();


                if(empty($upOpenLog))
                {
                    break;
                }else{


                    foreach ($upOpenLog as $key => $val){
                        $val = is_object($val)?$val->toArray():$val;



                        if($val['seller_status'] != $val['status']) {

                            $data = [
                                'up_open_id' => $val['id'],
                                'id' => $val['listing_id'],
                                'seller_sku' => $val['seller_sku'],
                                'up_open_time' => $val['up_open_time'],
                                'seller_status' => $val['seller_status']
                            ];


                          if($val['seller_status'] == 2) {
                              //加入定时下架消息队列
                              (new UniqueQueuer(AmazonTimerLowerQueuer::class))->push($data);
                          }

                          if($val['seller_status'] == 1) {
                              //加入定时上架消息队列
                              (new UniqueQueuer(AmazonTimerUpperQueuer::class))->push($data);
                          }
                        }
                    }

                }

            }while(count($upOpenLog)==$pageSize);

        }catch (Exception $exp){
            throw new TaskException($exp->getMessage());
        }
    }
}