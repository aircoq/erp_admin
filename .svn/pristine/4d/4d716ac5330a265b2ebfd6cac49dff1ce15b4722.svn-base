<?php

/**
 * Description of WishHealthData
 * @datetime 2017-7-11  9:56:03
 * @author joy
 */

namespace app\listing\task;
use app\index\service\AbsTasker;
use app\common\exception\TaskException;
use app\common\model\wish\WishHealthData as WishHealthDataModel;
use app\listing\service\HealthDataHelper;
use app\common\model\wish\WishHistoryHealthData;
class WishHealthData extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "wish健康数据";
    }
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "wish健康数据";
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
        return [];
    }
    /**
     * 任务执行内容
     * @return void
     */
    public  function execute()
    {
        set_time_limit(0);
        $model = new WishHealthDataModel;
        $historyModel = new WishHistoryHealthData;
        $service = new HealthDataHelper;
        $page = 1;
        $pageSize=50;
        $where['auth']=['=',1];
        $where['username']=['<>',''];
        $where['password']=['<>',''];
        
        do{
            $products = $model->where($where)->page($page,$pageSize)->select();
            if(empty($products))
            {
                break;
            }else{
                foreach ($products as  $product) 
                {
                    $product = is_object($product)?$product->toArray():$product;

                    $history = $product;
                     
                    $username = $product['username'];
                    
                    $password = $product['password'];

                    $proxy_ip = $product['proxy_ip'];
                    
                    $proxy_port = $product['proxy_port'];
                    
                    $proxy_user = $product['proxy_user'];
                    
                    $proxy_passwd = $product['proxy_passwd'];
                    
                    $proxy_protocol = $product['proxy_protocol'];

                    $tfa_token = $product['tfa_token'];

                    if($username && $password)
                    {
                        $response = $service->getStatisticsData($username,$password,$proxy_ip,$proxy_port,$proxy_user,$proxy_passwd,$proxy_protocol);

                        if($response['code']==0)
                        {
                            $product['valid_money'] = $response['valid_money'];
                            $product['valid_money_time'] = $response['valid_money_time'];
                            $product['unvalid_money'] = $response['unvalid_money'];
                            $product['valid_tracking_rate'] = $response['valid_tracking_rate'];
                            $product['counterfeit_rate'] = $response['counterfeit_rate'];
                            $product['average_rating'] = $response['average_rating'];
                            $product['refund_rate'] = $response['refund_rate'];
                            $product['late_confirmed_fulfillment_rate'] = $response['late_confirmed_fulfillment_rate']; 
                            $product['create_time']= time();
                            $product['tfa_token']= '';
                            $product['msg']= $response['msg'];
                            $product['code']= $response['code'];
                            $count = $model->isUpdate(true)->save($product);
                            if($count)
                            {
                                unset($history['id']);
                                 
                                $update = ((time() - $history['create_time'])/3600)>1?true:false; //如果当前时间与上次记录时间相差不超过1天，则不记录
                                
                                if($update)
                                {
                                    $historyModel->allowField(true)->isUpdate(false)->save($history);
                                }   
                            }
                        }else{
                            if($response['code']==10)
                                $response['auth']=0;
                            $model->isUpdate(true)->save($response,['id'=>$product['id']]);
                        }            
                    }

                }
                $page = $page +1 ;
            }
        }while($products == count($pageSize));
    }
    
}
