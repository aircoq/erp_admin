<?php
namespace app\publish\task;

use app\common\cache\Cache;
use app\common\model\amazon\AmazonPublishProductDetail;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\publish\queue\AmazonUpcDumpQueuer;


/**
 * @node Aamazon提交UPC到生成地去重
 * Class AmazonRsyncListing
 * packing app\listing\task
 */
class AmazonUpcDump extends AbsTasker
{

    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "amazon-提交备份UPC";
    }
    
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "amazon-提交备份UPC";
    }
    
    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return "冬";
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
    public  function execute()
    {
        $this->query();
        $this->redisQuery();
    }

    public function query()
    {
        $limit = 100;
        $where = ['product_id_value' => ['<>', '']];
        //Cache::handler()->del('task:amazon:upc-dump-maxid');
        //如果之前有缓存，则跟着缓存来；
        $maxid = Cache::handler()->get('task:amazon:upc-dump-maxid');
        if (!empty($maxid)) {
            $where['id'] = ['>=', $maxid];
        }

        $model = new AmazonPublishProductDetail();

        $count = $model->where($where)->count();
        $total = ceil($count / $limit);
        $queue = new UniqueQueuer(AmazonUpcDumpQueuer::class);
        for ($start = 0; $start < $total; $start++) {
            $data = $model->where($where)->limit($start * $limit, 1)->order('id', 'asc')->field('id')->select();
            if (empty($data)) {
                continue;
            }
            $id = $data[0]['id'];
            $queue->push([$id, $limit]);
            Cache::handler()->set('task:amazon:upc-dump-maxid', $id);
        }
    }

    public function redisQuery()
    {
        $queue = new UniqueQueuer(AmazonUpcDumpQueuer::class);
        while (true) {
            $param = Cache::handler()->rpop('task:amazon:upc-dump');
            if (empty($param)) {
                break;
            }
            $param = json_decode($param, true);
            if (empty($param)) {
                break;
            }
            if (!is_array($param) || count($param) != 2) {
                break;
            }
            $queue->push($param);
        }
    }
}
