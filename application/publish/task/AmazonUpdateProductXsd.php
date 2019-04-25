<?php
namespace app\publish\task;

use app\common\cache\Cache;
use app\index\service\AbsTasker;
use app\publish\service\AmazonCategoryXsdService;
use app\publish\service\AmazonXsdTemplate;
use think\Exception;


/**
 * @node Aamazon自动存储UPC
 * Class AmazonRsyncListing
 * packing app\listing\task
 */
class AmazonUpdateProductXsd extends AbsTasker
{

    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "amazon-自动更新产品XSD";
    }
    
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "amazon-自动更新产品XSD";
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
        ];
    }

    /**
     * 任务执行内容
     * @return void
     */
    public  function execute()
    {
        //先更新产品xsd元素；
        $num = 3;
        do {
            try {
                $amazonAttributelService = new AmazonCategoryXsdService();
                $amazonAttributelService->readProductXsd();
                $num = 0;
                $updateDetail = true;
            } catch (Exception $e) {
                $num--;
                $updateDetail = false;
            }
        } while($num);

        //如果上面成功更新了xsd,则更新现有模板；
        if ($updateDetail) {
            $templateService = new AmazonXsdTemplate();
            $templateService->updateOldDataSwoole();
            Cache::handler()->set('task:amazon:update-xsd-template', date('Y-m-d H:i:s'));
        }
    }
}
