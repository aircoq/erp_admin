<?php
namespace app\report\task;

use app\common\cache\Cache;
use app\common\model\report\ReportStatisticByMessage;
use app\index\service\AbsTasker;
use app\report\service\StatisticMessage;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/08/24
 * Time: 15:24
 */
class MessageStatisticReport extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '客服业绩统计信息写入数据库';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '';
    }

    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return 'libaimin';
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
     * 执行方法
     */
    public function execute()
    {
        $server = new StatisticMessage();
        try {
            $server->writeInMessage();
        }catch (Exception $e){
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }


}