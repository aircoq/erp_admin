<?php

/**
 * Description of GoodsPublishMap
 * @datetime 2017-5-19  11:07:32
 * @author joy
 */

namespace app\publish\task;
use app\index\service\AbsTasker;
use think\Db;
use app\common\exception\TaskException;
use app\common\model\GoodsPublishMap;
use app\common\model\aliexpress\AliexpressAccountCategoryPower;
use app\common\model\aliexpress\AliexpressProduct;
use think\Exception;
use app\common\model\Goods;
class GoodsPublishMapAliexpress extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "速卖通商品刊登状态映射";
    }
    
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "速卖通商品刊登状态映射";
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
    
    public function execute()
    {
        set_time_limit(0);

	    try{
            $model = new GoodsPublishMap;
            $channel=4;
            $where['spu']=['NEQ',''];
            $where['channel']=['EQ',$channel]; //wish平台
            $fields="*";
            $spus = $model->field('spu')->where($where)->select();
            $spu_arr = array_column($spus,'spu');
            $map=[
                'status'=>['=',1],
                'spu'=>['NOT IN',$spu_arr],
            ];
            (new Goods())->field("id,spu,status,platform_sale,sales_status,category_id ")->where($map)->chunk(100,function ($rows)use($channel){
                foreach($rows as $row)
                {
                    $row = $row->toArray();
                    $platform_sale=json_decode($row['platform_sale'],true);


                    if(isset($platform_sale['wish']))
                    {
                        $row['platform_sale'] = $platform_sale['wish'];
                    }else{//找不到，禁止上架
                        $row['platform_sale']=2;
                    }

                    if(is_numeric($row['platform_sale']))
                    {
                        $data=[
                            'channel'=>$channel,
                            'goods_id'=>$row['id'],
                            'category_id'=>$row['category_id'],
                            'spu'=>$row['spu'],
                            'platform_sale'=>$row['platform_sale'],
                            'sales_status'=>$row['sales_status'],
                        ];

                        if($has =GoodsPublishMap::where(['goods_id'=>$row['id'],'channel'=>$channel])->find())
                        {
                            GoodsPublishMap::where('id','=',$has['id'])->update($data);
                        }else{
                            (new GoodsPublishMap())->save($data);
                        }
                    }

                }
            });

            $model->field($fields)->with(['goods'=>function($query){$query->field("id,sales_status,platform_sale,status,category_id");}])->where($where)->chunk(100,function($products){
                $this->updatePublishStatus($products);
            });


	    } catch (TaskException $exp) {
		    throw new TaskException($exp->getFile().$exp->getLine().$exp->getMessage());
	    }


    }
    /**
     * 更新刊登状态
     * @param array $products
     */
    private function updatePublishStatus($products)
    {
        try{
            foreach ($products as $product)
            {
                if($product['spu'])
                {

                    //本地分类与平台分类映射关系，看几个账号满足条件，满足条件则可以刊登
                    $mapCategory = AliexpressAccountCategoryPower::where('local_category_id','=',$product['category_id'])->field('account_id')->select();

                    //查询该spu刊登过的
                    $listing = (new AliexpressProduct)->where('goods_spu','=',$product['spu'])->field('account_id')->select();


                    $res = $this->account_publish_status($mapCategory,$listing) ;

                    $status =  $res['status'];

                    $publish_status=json_encode($res['publish_status']);
                    Db::startTrans();
                    try{
                        GoodsPublishMap::where( 'id', '=', $product['id'] )->update( [
                            'publish_status' => $publish_status,
                            'status'         => $status
                        ] );
                        Db::commit();
                    }catch (\Exception $exp){
                        Db::rollback();
                        throw new TaskException($exp->getMessage());
                    }
                }
            }
        }catch (Exception $exp){
            throw new Exception($exp->getFile().$exp->getLine().$exp->getMessage());
        }
    }
    private function publishAccountTOArray($accounts)
    {
        $return=[];
        foreach ($accounts as $account)
        {
            if(!in_array($account['account_id'],$return))
            {
                array_push($return,"{$account['account_id']}");
            }

        }
        return $return;
    }
	/**
	 * 账号刊登状态
	 */
	private function account_publish_status($mapCategory=[],$listing=[])
	{
	    try{

            $publish=$this->publishAccountTOArray($listing);

            $total=0;
            if($mapCategory)
            {
                foreach ($mapCategory as $v)
                {
                    ++$total;
                }
            }

            //判断是否所有的账号都已经刊登了
            if($total==count($listing) && !empty($mapCategory) && !empty($listing))
            {
                $status=1;
            }else{
                $status=0;
            }

            return ['publish_status'=>$publish,'status'=>$status];
        }catch (Exception $exp){
            throw new Exception($exp->getFile().$exp->getLine().$exp->getMessage());
        }

	}
}
