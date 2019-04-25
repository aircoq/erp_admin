<?php
/**
 * Created by PhpStorm.
 * User: Tom
 * Date: 2017/6/14
 * Time: 16:48
 */

namespace app\publish\task;

use app\common\cache\Cache;
use app\index\service\AbsTasker;
use app\publish\exception\AliDownProductException;
use app\publish\service\AliexpressTaskHelper;
use service\aliexpress\AliexpressApi;
use think\Exception;

/**
 * 速卖通商品剩余有效期
 * Class AliexpressProductExpiry
 * @package app\publish\task
 */
class AliexpressProductExpiry extends AbsTasker
{
    private $expiry = [
        3=>'3天',
        7=>'7天'
    ];
    public function getName()
    {
        return 'Aliexpress-更新商品有效期状态';
    }

    public function getDesc()
    {
        return 'Aliexpress-更新商品有效期状态';
    }

    public function getCreator()
    {
        return 'Tom';
    }

    public function getParamRule()
    {
        $expiry_day = $this->expiry;
        $rule_str = '';
        foreach($expiry_day as $key=>$value){
            $rule_str .= $value.':'.$key;
        }
        return [
            //'day|剩余多少天'=>'require|select:'.$rule_str,
        ];
    }

    public function execute()
    {
        set_time_limit(0);
        try {
            //获取所有已授权并启用账号
            $accountList = Cache::store('AliexpressAccount')->getAccounts();
            if (!empty($accountList)) {
                foreach ($accountList as $item) {
                    if ($item['is_invalid'] && $item['is_authorization']) {
                        //组装配置信息
                        $config = [
                            'id'                =>  $item['id'],
                            'client_id'         =>  $item['client_id'],
                            'client_secret'     =>  $item['client_secret'],
                            'access_token'      =>  $item['access_token'],
                            'refresh_token'     =>  $item['refresh_token'],
                        ];
                        $this->downProduct($config);
                    }
                }
            }
        } catch (Exception $ex) {
            throw new AliDownProductException($ex->getMessage());
        }
    }

    public function downProduct($config)
    {
        $postProductServer = AliexpressApi::instance($config)->loader('PostProduct');
        $helpServer = new AliexpressTaskHelper();
        $conditions = $this->getCondition();
        if(!empty($conditions)){
            foreach($conditions as $key=>$value){
                $total = 0;
                $page = 1;
                $totalPage = 0;
                $arr_product_id = [];
                do{
                    //获取产品列表
                    $response = $helpServer->getAliProductList($postProductServer,'onSelling',$page,$value);
                    if(isset($response['error_code'])){
                        continue;
                        //throw new AliDownProductException($response['error_message']);
                    }
                    $totalPage = $response['totalPage'];
                    if(!empty($response['data'])){
                        foreach($response['data'] as $product){
                            array_push($arr_product_id,$product['productId']);
                        }
                    }

                    $page++;
                }while($page<=$totalPage);
                if(!empty($arr_product_id)){
                    $reset = $key==0?true:false;
                    $helpServer->setAliProductExpire($arr_product_id,$value,$config['id'],$reset);
                }
            }
        }
    }

    private function getCondition()
    {
        $arr_day = array_keys($this->expiry);
        rsort($arr_day);
        return $arr_day;
    }

}