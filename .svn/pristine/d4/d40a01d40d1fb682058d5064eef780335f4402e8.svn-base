<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-11
 * Time: 上午10:49
 */

namespace app\listing\queue;


use app\common\exception\QueueException;
use app\common\model\wish\WishWaitUploadProductVariant;
use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsSkuMapService;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;

class WishCombineSkuQueue extends SwooleQueueJob
{
    public function getName():string
    {
        return 'wish捆绑销售(队列)';
    }
    public function getDesc():string
    {
        return 'wish捆绑销售(队列)';
    }
    public function getAuthor():string
    {
        return 'joy';
    }

    public  function execute()
    {
        set_time_limit(0);
        try{
            $params = $this->params;
            if($params)
            {

                if(isset($params['vid']) && isset($params['combine_sku']))
                {
                    $vid = $params['vid'];
                    $variant = (new WishWaitUploadProductVariant())->field('vid,pid,sku')->with(['product'=>function($query){$query->field('id,accountid,uid');}])->where('vid','=',$vid)->find();
                    if($variant)
                    {
                        $data =[
                            'sku_code'=>$variant['sku'],
                            'channel_id'=>3,
                            'account_id'=>$variant['product']['accountid'],
                            'combine_sku'=>$params['combine_sku']
                        ];
                        $response = (new GoodsSkuMapService())->addSkuCodeWithQuantity($data,$variant['product']['uid']);
                        if(isset($response['result']) && $response['result']){
                            Db::startTrans();
                            try{
                                WishWaitUploadProductVariant::where('vid','=',$vid)->update(['combine_sku'=>$params['combine_sku']]);
                                Db::commit();
                            }catch (PDOException $exp){
                                Db::rollback();
                                throw new QueueException($exp->getMessage());
                            }catch (DbException $exp){
                                Db::rollback();
                                throw new QueueException($exp->getMessage());
                            }catch (Exception $exp){
                                Db::rollback();
                                throw new QueueException($exp->getMessage());
                            }
                        }
                    }else{
                        throw new QueueException("数据不存在");
                    }
                }
            }else{
                throw new QueueException("数据为空");
            }
        }catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }
}