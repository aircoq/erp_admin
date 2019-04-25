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
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use app\common\model\amazon\AmazonListing;
use app\publish\service\AmazonCategoryXsdConfig;
use app\publish\service\AmazonHeelSaleLogService;
use app\common\model\amazon\AmazonActionLog as AmazonActionLogModel;


class AmazonHeelSaleSyncFail extends AbsTasker
{
    public function getName()
    {
        return "Amazon跟卖状态同步失败-停止使用";
    }

    public function getDesc()
    {
        return "Amazon跟卖状态同步失败-停止使用";
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
                'price_status' => ['=', 1],
                'quantity_status' => ['=', 1],
                'is_sync' => ['=',0],
                'status' => ['in', [1,4]],
                'rule_id' => ['>', 0]
            ];

            $time = time();

            $logService = new AmazonHeelSaleLogService;

            $object = new AmazonHeelSaleLogModel;

            $actionLogModel = new  AmazonActionLogModel;

            $model = new AmazonListing();

            do{
                $heelSale = $object->field('id,account_id,asin,price,quantity,sku,rule_id,create_id')->where($where)->page($page,$pageSize)->order('created_time desc')->select();

                if(empty($heelSale))
                {
                    break;
                }else{

                    //同步数据
                    foreach ($heelSale as $key => $val){
                        $val = is_object($val)?$val->toArray():$val;

                        if(isset($val['id']) && $val['id']){



                            $info = $model->field('id')->where(['seller_sku' => $val['sku'],'account_id' => $val['account_id'],'seller_type' => 2, 'asin1' => $val['asin']])->find();
                            if($info){
                                $id = $info['id'];
                            }else{

                                //跟卖成功,添加listing数据
                                $account = Cache::store('AmazonAccount')->getAccount($val['account_id']);
                                $currency = AmazonCategoryXsdConfig::getCurrencyBySite($account['site']);

                                $data = [
                                    'seller_sku' => $val['sku'],
                                    'account_id' => $val['account_id'],
                                    'seller_type' => 2,
                                    'currency' => $currency,
                                    'price' => $val['price'],
                                    'quantity' => $val['quantity'],
                                    'asin1' => $val['asin'],
                                    'site' => isset($account['site']) ? (string)$account['site'] : '',
                                    'modify_time' => $time,
                                    'rule_id' => $val['rule_id'],
                                ];

                                $id = $model->insertGetId($data);
                            }

                            if($id){
                                //写入action_log
                                $data = [
                                    'account_id' => $val['account_id'],
                                    'seller_sku' => $val['sku'],
                                    'type' => 3,
                                    'old_value' => '{"quantity":0}',
                                    'new_value' => '{"quantity":'.$val['quantity'].'}',
                                    'create_id' => $val['create_id'],
                                    'status' => 1,
                                    'create_time' => time(),
                                ];
                                $actionLogModel->insertGetId($data);

                                //listing成功,则判断是否有定时上下架,并且写入到消息队列中
                                if($val['rule_id']){
                                    $logService->upLowerOpen($id, $val['rule_id']);
                                }

                            }
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