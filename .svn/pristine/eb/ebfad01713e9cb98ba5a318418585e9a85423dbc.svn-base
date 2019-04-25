<?php
namespace app\publish\task;

use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\publish\queue\AmazonUpcPoolQueuer;

 
/**
 * @node Aamazon自动存储UPC
 * Class AmazonRsyncListing
 * packing app\listing\task
 */
class AmazonAutoPoolUpc extends AbsTasker
{

    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "amazon-自动存储UPC";
    }
    
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "amazon-自动存储UPC";
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
        return [
            'max|最大存储个数' => 'require|select:正常下载:0,1000个:1000,2000个:2000,4000个:4000,5000个:5000,6000个:6000,10000个:10000'
        ];
    }

    /**
     * 任务执行内容
     * @return void
     */
    public  function execute()
    {
        $max = $this->getData('max');
        if (empty($max)) {
            $max = 0;
        }
        (new UniqueQueuer(AmazonUpcPoolQueuer::class))->push($max);
    }
}
