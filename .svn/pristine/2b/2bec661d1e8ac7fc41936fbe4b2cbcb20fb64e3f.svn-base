<?php

/**
 * Description of GoodsPublishMapEbay
 * @datetime 2017-5-19  11:07:32
 * @author joy
 */

namespace app\publish\task;
use app\common\exception\JsonErrorException;
use app\index\service\AbsTasker;
use think\Db;
use app\common\exception\TaskException;
use app\common\model\GoodsPublishMap;
use app\common\model\ebay\EbayListing;
use app\common\cache\Cache;
use app\common\model\Goods;
class GoodsPublishMapEbay extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "ebay商品刊登状态映射";
    }
    
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "ebay商品刊登状态映射";
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
            $channel=1;
            $model = new GoodsPublishMap;
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
    private function updatePublishStatus($products,GoodsPublishMap $model)
    {
        try{
            foreach ($products as $product)
            {
                if(is_object($product))
                {
                    $product = $product->toArray();
                }

                if($product['goods'])
                {
                    $platform = json_decode($product['goods']['platform_sale'],true);
                    if($platform)
                    {
                        $product['goods']['platform_sale']=$platform['wish'];
                    }else{
                        $product['goods']['platform_sale']='';
                    }

                    GoodsPublishMap::where('id','=',$product['id'])->update(['platform_sale'=>$product['goods']['platform_sale']]);
                }

                if($product['spu'])
                {
                    $publish_count = EbayListing::where(['spu'=>$product['spu']])->field('account_id,site')->select();

                    $mapStatus = $this->account_publish_status($publish_count);

                    $publish_status = json_encode($mapStatus['publish_status']);

                    $status = $mapStatus['status'];
                    Db::startTrans();
                    try{
                       GoodsPublishMap::where('id','=',$product['id'])->update(['publish_status'=>$publish_status,'status'=> $status]);
                        Db::commit();
                    }catch (\Exception $exp){
                        Db::rollback();
                        throw new TaskException($exp->getMessage());
                    }
                }
            }
        }catch (TaskException $exp){
            throw new TaskException($exp->getFile().$exp->getLine().$exp->getMessage());
        }

    }
	/**
	 * 账号刊登状态
	 * 每个站点每个账号都存在一个记录
	 */
	private function account_publish_status($rows=[])
	{
        try{
            //已授权账号数目
            if($cacheSites = Cache::handler(true)->get('Cache:EbaySites'))
            {
                $sites =  json_decode($cacheSites,true);
            }else{
                $sites = Db::table('ebay_site')->select();
                Cache::handler(true)->set('Cache:EbaySites',json_encode($sites));
            }

            $accounts = Cache::store('Account')->ebayAccount();

            $publish=[];

            //记录每个站点每个账号的刊登情况
            foreach ($sites as $key=>$site)
            {
                foreach ($accounts as $account)
                {
                    if($account['is_invalid']==1)
                    {
                        $publish[$site['siteid']][$account['id']]=0;
                    }
                }
            }

            if(!empty($rows))
            {
                foreach ($rows as $row)
                {
                    $publish[$row['site']][$row['account_id']]=1;
                }
            }

            $status=1;

            foreach ($publish as $key=>$pub)
            {

                if(is_numeric($key) && in_array("0",$pub))
                {
                    $status=0;
                    break;
                }
            }

            return ['publish_status'=>$publish,'status'=>$status];
        }catch (TaskException $exp){
            throw new TaskException($exp->getFile().$exp->getLine().$exp->getMessage());
        }
	}
}
