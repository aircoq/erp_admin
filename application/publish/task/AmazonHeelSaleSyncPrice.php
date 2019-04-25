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


class AmazonHeelSaleSyncPrice extends AbsTasker
{
    public function getName()
    {
        return "Amazon跟卖同步价格-停止使用";
    }

    public function getDesc()
    {
        return "Amazon跟卖同步价格-停止使用";
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
                'type' => ['=',1],
                'status' => ['in', [1,4]],
            ];

            $data = [];
            do{
                $heelSale = (new AmazonHeelSaleLogModel())->field('id,account_id,listing_id,asin,sku, seller_status, price')->where($where)->page($page,$pageSize)->select();

                if(empty($heelSale))
                {
                    break;
                }else{
                    $object = new AmazonHeelSaleLogModel;

                    $listingModel = new AmazonListing;
                    //同步数据
                    foreach ($heelSale as $key => $val){
                        $val = is_object($val)?$val->toArray():$val;

                        $amamList = $listingModel->field('id, price')->where(['asin1' => $val['asin'], 'account_id' => $val['account_id'], 'seller_type' => 2, 'seller_sku' => $val['sku']])->find();

                        if($amamList && $val['price'] != $amamList['price']) {
                            $listingModel->update(['price' => $val['price']], ['id' => $amamList['id']]);
                        }
                    }

                    $page = $page + 1;
                }

            }while(count($heelSale)==$pageSize);

            return true;
        }catch (Exception $exp){
            throw new TaskException($exp->getMessage());
        }
    }
}