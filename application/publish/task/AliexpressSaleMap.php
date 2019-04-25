<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-23
 * Time: 下午1:56
 */
namespace app\publish\task;
use app\common\exception\TaskException;
use app\common\model\aliexpress\AliexpressProduct;
use app\index\service\AbsTasker;
use app\index\service\MemberShipService;
use think\Db;

class AliexpressSaleMap extends AbsTasker
{
    public function getName()
    {
        return "Aliexpress平台销售人员匹配";
    }

    public function getDesc()
    {
        return "Aliexpress平台销售人员匹配";
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
            $page=1;$pageSize=30;
            $where=[
                'salesperson_id'=>['=',0]
            ];
            do{
                $products = (new AliexpressProduct())->field('id,account_id,salesperson_id')->where($where)->select();
                if(empty($products))
                {
                    break;
                }
                $this->update($products);
            }while(count($products)==$pageSize);


        }catch (TaskException $exp){
            throw new TaskException($exp->getMessage());
        }
    }
    private function update(array $products,$channel_id=4)
    {
        if(empty($products))
        {
            return '';
        }

        foreach ($products as $product)
        {
            $product = $product->toArray();
            $sales = (new MemberShipService())->member($channel_id,$product['account_id'],'sales');
            if($sales)
            {
                $product['salesperson_id']=$sales[0]['seller_id'];
                Db::startTrans();
                try{
                    (new AliexpressProduct())->save($product,['id'=>$product['id']]);
                    Db::commit();
                }catch(\Exception $exp){
                    Db::rollback();
                    throw new TaskException($exp->getMessage());
                }

            }
        }
    }
}