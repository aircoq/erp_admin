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
use app\common\model\amazon\AmazonListing;
use app\publish\service\AmazonCategoryXsdConfig;


class AmazonUpLowerSync extends AbsTasker
{
    public function getName()
    {
        return "Amazon定时上下架同步-停止使用";
    }

    public function getDesc()
    {
        return "Amazon定时上下架同步-停止使用";
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
            $where=[
                'type' => ['=',4],
                'is_sync' => ['=',0],
                'status' => ['=', 1],
            ];

            $time = time();

            do{
                $heelSale = (new AmazonHeelSaleLogModel())->field('id,account_id,listing_id,asin,sku, seller_status')->where($where)->page($page,$pageSize)->select();

                if(empty($heelSale))
                {
                    break;
                }else{
                    $object = new AmazonHeelSaleLogModel;

                    //同步数据
                    foreach ($heelSale as $key => $val){
                        $val = is_object($val)?$val->toArray():$val;

                        if(isset($val['id']) && $val['id']){

                            $seller_status = $val['seller_status'] == 1 ? 1 : 4;

                            $object->update(['is_sync' => 1], ['id' => $val['id']]);

                            $data = ['status' => $seller_status, 'is_sync' => 1];

                            //上架
                            if($val['seller_status'] == 1) {
                                $data['upper_request_time'] = $time;
                            }

                            //下架
                            if($val['seller_status'] == 2) {
                                $data['last_request_time'] = $time;
                            }
                            $object->update($data, ['account_id' => $val['account_id'],'type' => 1, 'asin' => $val['asin'], 'sku' => $val['sku']]);

                            $model = new AmazonListing();

                            $model->update(['seller_status' => $val['seller_status']], ['id' => $val['listing_id']]);


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