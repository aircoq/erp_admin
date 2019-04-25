<?php
/**
 * Created by NetBeans.
 * User: joy
 * Date: 2017-3-15
 * Time: 上午10:19
 */

namespace app\publish\task;

use app\common\model\wish\WishWaitUploadProductVariant;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\publish\queue\WishQueueJob;
use app\common\exception\TaskException;
use app\common\model\wish\WishWaitUploadProduct;
use think\Exception;

/**
 * @node Wish刊登任务
 * Class WishPublish
 * packing app\publish\task
 */
class WishPublish extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "wish未刊登自动加入队列";
    }
    
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "wish未刊登自动加入队列";
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
            $page=1;
            $pageSize=10;
            $model=new WishWaitUploadProductVariant();
            do{
                $where['status']=['=',0];

                $products = $model->field('vid,pid,status')->where($where)->with(['product'=>function($query){
                    $query->field('id,cron_time');
                }])->page($page,$pageSize)->group('pid')->select();
                if(empty($products))
                {
                    break;
                }
                $this->pushQueue($products);
                $page=$page+1;
            }while(count($products)==$pageSize);
        }catch (Exception $exp){
            throw new TaskException($exp->getMessage());
        }
    }

    /**
     * 未刊登成功的自动入队列
     * @param $products
     * @return string
     */
    private function pushQueue($rows)
    {
        try{
            if(empty($rows))
            {
                return '';
            }

            foreach ($rows as $row)
            {
                $row = is_array($row)?:$row->toArray();
                if(isset($row['product']))
                {
                    $product= $row['product'];
                    $queue = $product['id'];
                    $cron_time = $product['cron_time'];
                    if($cron_time<=time())
                    {
                        (new UniqueQueuer(WishQueueJob::class))->push($queue);
                    }else{
                        (new UniqueQueuer(WishQueueJob::class))->push($queue,$cron_time);
                    }
                }
            }
        }catch (Exception $exp){
            throw new TaskException($exp->getMessage());
        }
    }
}
