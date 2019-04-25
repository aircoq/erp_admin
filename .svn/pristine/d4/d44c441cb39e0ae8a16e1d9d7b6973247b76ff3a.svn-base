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
use app\publish\queue\AmazonUpperResultQueuer;
use app\publish\queue\AmazonLowerResultQueuer;
use think\Exception;

class AmazonUpLowerResult extends AbsTasker
{
    public function getName()
    {
        return "Amazon上下架结果获取-停止使用";
    }

    public function getDesc()
    {
        return "Amazon上下架结果获取-停止使用";
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


            $where = [
                'status' => ['in', [0,3]],
                'type' => 4,
            ];
            do{
                $heelSale = (new AmazonHeelSaleLogModel())->field('id,type,seller_status')->where($where)->where('submission_id > 0 and request_number < 100')->page($page,$pageSize)->select();

                if(empty($heelSale))
                {
                    break;
                }else{

                    //重新加入获取结果队列
                    $this->queue($heelSale);
                    $page = $page + 1;
                }

            }while(count($heelSale)==$pageSize);
            
        }catch (Exception $exp){
            throw new TaskException($exp->getMessage());
        }
    }


    public function queue($heelSale){

        foreach ($heelSale as $key => $val){

            $val = is_object($val)?$val->toArray():$val;
            if(isset($val['id']) && $val['id']){

                //上架上传结果
                if($val['seller_status'] == 1){
                    (new UniqueQueuer(AmazonUpperResultQueuer::class))->push($val['id']);
                }

                //下架上传结果
                if($val['seller_status'] == 2){
                    (new UniqueQueuer(AmazonLowerResultQueuer::class))->push($val['id']);
                }
            }
        }
    }
}