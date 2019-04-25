<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\ebay\EbayCase as EbayCaseModel;
use app\common\model\ebay\EbayRequest as EbayRequestModel;
use app\common\model\ebay\EbayFeedback as EbayFeedbackModel;

/**
 * ebay售后
 * Created by tanbin.
 * User: PHILL
 * Date: 2017/06/10
 * Time: 11:44
 */
class EbayAfterSales extends Cache
{

    /**
     * 通过用户id查找是否发起过纠纷,是否差评
     * @param string $buyer_id
     */
    public function afterSalesByBuyer($buyer_id = '')
    {
        //Cache::handler()->del('hash:EbayOrderUpdateTime'); //删除
        
        $key = 'hash:afterSalesByBuyer';
        if ($buyer_id) {            
            $result = json_decode(self::$redis->hget($key, $buyer_id), true);
            if($result){
                return $result;
            }
            
            //是否发起纠纷
            $data['dispute'] = 0;
            $data['negative_comment'] = 0;
            $res = EbayCaseModel::field('id')->where(['buyer_account'=>$buyer_id])->find();
            if(empty($res)){
               $res =  EbayRequestModel::field('id')->where(['buyer_account'=>$buyer_id])->find();
            }
            if($res){
                $data['dispute'] = 1;  //该用户发起过纠纷 
            }
            
            //是否发起差评
            $res = EbayFeedbackModel::field('id')->where(['comment_text_buyer'=>$buyer_id,'comment_type'=>3])->find();
            if($res){
                $data['negative_comment'] = 1;  //该用户发起过差评
            }
            
            self::$redis->hset($key, $buyer_id, json_encode($data));
            return $data;
        }
  
        return [];
    }
    
    

}
