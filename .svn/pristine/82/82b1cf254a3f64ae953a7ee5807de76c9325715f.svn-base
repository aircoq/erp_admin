<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-19
 * Time: 下午2:55
 */

namespace app\publish\task;

use app\common\model\wish\WishWaitUploadProductVariant;
use app\common\service\UniqueQueuer;
use app\common\exception\TaskException;
use app\index\service\AbsTasker;
use app\publish\queue\WishSkuMapQueue;

class WishSkuMap extends AbsTasker
{
    public function getName()
    {
        return "Wish平台sku与系统sku关系绑定";
    }

    public function getDesc()
    {
        return "Wish平台sku与系统sku关系绑定";
    }

    public function getCreator()
    {
        return "joy";
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
            $pageSize=30;
            $where=[
                'sku_id'=>['=',0]
            ];
            do{
                $skus = (new WishWaitUploadProductVariant())->field('vid,pid,sku')->where($where)->page($page,$pageSize)->select();
                if(empty($skus))
                {
                    break;
                }else{
                    $this->update($skus);
                    $page = $page + 1;
                }

            }while(count($skus)==$pageSize);

        }catch (TaskException $exp){
            throw new TaskException($exp->getMessage());
        }
    }
    private function update(array $skus)
    {
        foreach ($skus as $sku)
        {
            $sku = $sku->toArray();
            (new UniqueQueuer(WishSkuMapQueue::class))->push($sku);
        }
    }
}