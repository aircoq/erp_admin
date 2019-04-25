<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\listing\task;
use app\common\model\wish\WishWaitUploadProductInfo;
use app\common\service\CommonQueuer;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\common\model\wish\WishWaitUploadProduct;
use app\listing\queue\WishExpressQueue;
use service\wish\WishApi;
use think\Db;
use app\common\exception\TaskException;
/**
 * @node wish express物流
 * Class WishInventory 
 * packing app\listing\task
 */
class WishExpress extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "获取wish在线listing运费数据";
    }
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "获取wish在线listing运费数据";
    }
    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return "joy";
    }
    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [
            'type|执行类型' => 'require|select:全部:ALL,express为空|default:ok',
        ];
    }
    /**
     * 任务执行内容
     * @return void
     */
    public  function execute()
    {
        
        set_time_limit(0);
        
        $page =1;
        $pageSize=50;
        $fields="id,product_id";
        
        //$where['product_id']=array('<>','');
        //$where['all_country_shipping']=array('<>','');
        $where['shipping_status']=['=',0];
        $model = new WishWaitUploadProductInfo();

        do {
            $type = $this->getData('type');
            $type = $type=='' ? 'NULL' : $type;

            if($type=='NULL'){
                $products = $model->with(['product'=>function($query){$query->field('id,accountid');}])->field($fields)->where($where)->page($page, $pageSize)->select();
            }else if($type=='ALL'){
                $products = $model->with(['product'=>function($query){$query->field('id,accountid');}])->field($fields)->where($where)->page($page, $pageSize)->select();
            }

            if(empty($products)){
                 break;
            } else{
                self::pushQueue($products);
                $page++;
            }           
        } while (count($products)==$pageSize);
    }

    private static function pushQueue(array  $rows){
        if(empty($rows)){
            return false;
        }else{
            foreach ($rows as $row){
                if($row['product']){
                    $product_id  = $row['product_id'];
                    $account_id = $row['product']['accountid'];
                    $queue=['account_id'=>$account_id,'product_id'=>$product_id];
                    (new CommonQueuer(WishExpressQueue::class))->push($queue);
                }
            }
        }
    }
    
    private static function getAllShipping(array $products)
    {
        if(is_array($products) && $products)
        {
            foreach ($products as $key => $product) 
            {
               $product = $product->toArray();
               $api = WishApi::instance($product)->loader('Product');
               $response = $api->getAllShipping($product);
               if($response['state']==true)
               {
                   $shipping_prices = $response['data']['ProductCountryAllShipping']['shipping_prices'];
                   $data['all_country_shipping']= json_encode($shipping_prices);
                   self::updateCountryAllShipping($data, ['id'=>$product['pid']]);
               }
            }
        }
    }
    /**
     * 更新产品所有的国家物流设置
     * @param array $data
     * @param array $where
     */
    private static function updateCountryAllShipping($data,$where)
    {
       $model = new WishWaitUploadProductInfo();
       Db::startTrans();
       try {
           $model->isUpdate(true)->allowField(true)->save($data, $where);
           Db::commit();
       } catch (\Exception $exp) {
           Db::rollback();
           throw new TaskException($exp->getMessage());
       }      
    }
   
}
