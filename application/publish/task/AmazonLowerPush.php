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
use app\publish\queue\AmazonTimerLowerQueuer;


class AmazonLowerPush extends AbsTasker
{
    public function getName()
    {
        return "Amazon定时下架推送-停止使用";
    }

    public function getDesc()
    {
        return "Amazon定时下架推送-停止使用";
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

            $start_time = strtotime(date('Y-m-d'));
            $end_time = strtotime(date('Y-m-d H:00'));
            $where=[
                'is_up_open' => ['=',0],
                'up_open_time' => ['between',[$start_time, $end_time]],
                'seller_status' => 2,
            ];

            do{
                $upOpenLogModel = new AmazonUpOpenLogModel();

                $amazonListingModel = new AmazonListing;

                $upOpenLog = $upOpenLogModel->field('id, listing_id, seller_sku, seller_status, up_open_time')->where($where)->page($page++,$pageSize)->select();

                if(empty($upOpenLog))
                {
                    break;
                }else{

                    foreach ($upOpenLog as $key => $val){
                        $val = is_object($val)?$val->toArray():$val;

                        $data = [
                            'up_open_id' => $val['id'],
                            'id' => $val['listing_id'],
                            'seller_sku' => $val['seller_sku'],
                            'up_open_time' => $val['up_open_time'],
                            'seller_status' => 2
                        ];


                        //加入定时下架消息队列
                        (new UniqueQueuer(AmazonTimerLowerQueuer::class))->push($data);
                    }

                }

            }while(count($upOpenLog)==$pageSize);

        }catch (Exception $exp){
            throw new TaskException($exp->getMessage());
        }
    }
}