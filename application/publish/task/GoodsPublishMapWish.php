<?php

/**
 * Description of GoodsPublishMap
 * @datetime 2017-5-19  11:07:32
 * @author joy
 */

namespace app\publish\task;
use app\common\model\Goods;
use app\index\service\AbsTasker;
use think\Db;
use app\common\exception\TaskException;
use app\common\model\GoodsPublishMap;
use app\common\model\wish\WishWaitUploadProduct;
use app\common\cache\Cache;
use think\Exception;

class GoodsPublishMapWish extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "wish商品刊登状态映射";
    }
    
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "wish商品刊登状态映射";
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
        $message='';
        try{
            //$sql="SELECT id,spu,status,json_extract(platform_sale,'$.wish') as platform_sale,sales_status,category_id   FROM goods WHERE status=1  AND spu NOT IN(SELECT spu FROM goods_publish_map WHERE channel=$channel group by spu)";
            //$model = new GoodsPublishMap;
//            $channel=3;
//            $where['spu']=['NEQ',''];
//            $where['channel']=['EQ',$channel]; //wish平台
//
//            $spus =GoodsPublishMap::field('spu')->where($where)->select();
//            $spu_arr = array_column($spus,'spu');
            $fields="id,spu,status,platform_sale,sales_status,category_id";
            $map=[
                'status'=>['=',1],
                //'spu'=>['NOT IN',$spu_arr],
            ];
            $page=1;
            $pageSize=20;
            do{
                $rows = Goods::field($fields)->where($map)->page($page,$pageSize)->select();
                if(empty($rows))
                {
                    break;
                }
                $this->insertOrUpdate($rows);
            }while(count($rows)==$pageSize);




//            (new Goods())->field("id,spu,status,platform_sale,sales_status,category_id ")->where($map)->chunk(100,function ($rows)use($channel){
//                foreach($rows as $row)
//                {
//                    $row = $row->toArray();
//                    $platform_sale=json_decode($row['platform_sale'],true);
//
//
//                    if(isset($platform_sale['wish']))
//                    {
//                        $row['platform_sale'] = $platform_sale['wish'];
//                    }else{//找不到，禁止上架
//                        $row['platform_sale']=2;
//                    }
//                    dump($row['spu']);
//                    if(is_numeric($row['platform_sale']))
//                    {
//                        $data=[
//                            'channel'=>$channel,
//                            'goods_id'=>$row['id'],
//                            'category_id'=>$row['category_id'],
//                            'spu'=>$row['spu'],
//                            'platform_sale'=>$row['platform_sale'],
//                            'sales_status'=>$row['sales_status'],
//                        ];
//
//                        if($has =GoodsPublishMap::where(['goods_id'=>$row['id'],'channel'=>$channel])->find())
//                        {
//                            GoodsPublishMap::where('id','=',$has['id'])->update($data);
//                        }else{
//                            (new GoodsPublishMap())->save($data);
//                        }
//                    }
//
//                }
//            });

//            $model->field($fields)->with(['goods'=>function($query){$query->field("id,sales_status,platform_sale,status,category_id");}])->where($where)->chunk(100,function($products){
//                $this->updatePublishStatus($products);
//            });
        }catch (\Throwable $exp){
            $message = "file:{$exp->getFile()};line:{$exp->getLine()}:message:{$exp->getMessage()}";
            throw new TaskException($message);
        }finally{
            Cache::handler()->hSet('goods:publish:map:wish',date('Y-m-d H:i:s',time()),$message);
        }
    }
    private function insertOrUpdate($rows,$channel=3)
    {
        foreach($rows as $row)
        {
            $row = is_object($row)?$row->toArray():$row;

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
    }
    /**
     * 更新刊登状态
     * @param array $products
     */
    private function updatePublishStatus($products)
    {
        $message="";
        try{

            foreach ($products as $product)
            {
                if ( is_object( $product ) )
                {
                    $product = $product->toArray();
                }

                if ( $product['goods'] )
                {
                    $platform = json_decode( $product['goods']['platform_sale'], true );
                    if ( $platform ) {
                        $product['goods']['platform_sale'] = $platform['wish'];
                    } else {
                        $product['goods']['platform_sale'] = '';
                    }

                    GoodsPublishMap::where('id','=',$product['id'])->update(['platform_sale'=>$product['goods']['platform_sale']]);

                }

                if ( $product['spu'] )
                {

                    //已刊登过的
                    $publish_count = WishWaitUploadProduct::where( [ 'parent_sku' => $product['spu'] ] )->field( 'accountid' )->select();

                    //授权有效帐号
                    $count = $this->account_publish_status();

                    $status = count($publish_count)==$count?1:0;

                    $publish_status=json_encode($this->publishAccountTOArray($publish_count));
                    Db::startTrans();
                    try{
                        GoodsPublishMap::where( 'id', '=', $product['id'] )->update( [ 'publish_status' => $publish_status,'status'=>$status] );
                        Db::commit();
                    }catch (\Exception $exp){
                        Db::rollback();
                        throw new TaskException($exp->getMessage());
                    }

                }
            }
        }catch (\Throwable $exp){
            $message = "file:{$exp->getFile()};line:{$exp->getLine()}:message:{$exp->getMessage()}";
            throw new TaskException($message);
        }finally{
            Cache::handler()->hSet('goods:publish:map:wish',date('Y-m-d H:i:s',time()),$message);
        }
    }

    private function publishAccountTOArray($accounts)
    {
        $return=[];
        foreach ($accounts as $account)
        {
            if(!in_array($account['accountid'],$return))
            {
                array_push($return,"{$account['accountid']}");
            }

        }
        return $return;
    }

	/**
	 * 账号刊登状态
	 */
	private function account_publish_status()
	{
	    try{
            $accounts = Cache::store('WishAccount')->getAccount();
            $count=0;
            foreach ($accounts as $account_id=>$account)
            {
                if($account['is_invalid']==1 && $account['is_authorization']==1)
                {
                    ++$count;
                }
            }
            return $count;
        }catch (\Throwable $exp){
	        throw new TaskException($exp->getMessage());
        }
	}
}
