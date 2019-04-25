<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-11
 * Time: 上午11:51
 */

namespace app\listing\queue;


use app\common\exception\QueueException;
use app\common\model\aliexpress\AliexpressProductSku;
use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsSkuMapService;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;


class AliexpressCombineSkuQueue extends SwooleQueueJob
{
    public function getName():string
    {
        return '速卖通捆绑销售(队列)';
    }
    public function getDesc():string
    {
        return '速卖通捆绑销售(队列)';
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
                    $variant = (new AliexpressProductSku())->field('id,ali_product_id,sku_code')->with(['product'=>function($query){$query->field('id,account_id,publisher_id');}])->where('id','=',$vid)->find();
                    if($variant)
                    {
                        $data =[
                            'sku_code'=>$variant['sku_code'],
                            'channel_id'=>4,
                            'account_id'=>$variant['product']['account_id'],
                            'combine_sku'=>$params['combine_sku']
                        ];

                        $response = (new GoodsSkuMapService())->addSkuCodeWithQuantity($data,$variant['product']['publisher_id']);
                        if(isset($response['result']) && $response['result']){
                            Db::startTrans();
                            try{
                                AliexpressProductSku::where('id','=',$vid)->update(['combine_sku'=>$params['combine_sku']]);
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