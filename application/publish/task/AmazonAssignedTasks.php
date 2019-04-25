<?php
namespace app\publish\task;

use app\common\model\amazon\AmazonAccount;
use app\common\model\amazon\AmazonPublishTask;
use app\common\model\ChannelUserAccountMap;
use app\common\service\ChannelAccountConst;
use app\goods\service\GoodsHelp;
use app\index\service\AbsTasker;
use app\index\service\ChannelService;
use app\index\service\DepartmentUserMapService;
use app\publish\service\AmazonPublishTaskService;
use think\Exception;


/**
 * @node Aamazon自动存储UPC
 * Class AmazonRsyncListing
 * packing app\listing\task
 */
class AmazonAssignedTasks extends AbsTasker
{

    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "amazon自动分配任务";
    }
    
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "amazon自动分配任务";
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
        $str = '';
        for ($i = 0; $i <= 23; $i++) {
            $str .= $i. '点:'. $i. ',';
        }
        $str = trim($str, ',');
        $str2 = '';
        for ($i = 1; $i <= 365; $i++) {
            $str2 .= '前第'. $i. '天:'. $i. ',';
        }
        $str2 = trim($str2, ',');
        return [
            'hours|执行时间' => 'require|select:'. $str,
            'day|添加哪天商品' => 'require|select:'. $str2,
        ];
    }

    private $model = null;

    /** @var array 不分配的帐号 */
    private $notAssignAccount = [];

    public function __construct()
    {
        $this->model = new AmazonPublishTask();
    }

    /**
     * 任务执行内容
     * @return void
     */
    public  function execute()
    {
        $hours = (int)$this->getData('hours', 0);
        if (!$this->run($hours)) {
            return false;
        }
        //周日和周六不跑；
        $week = date('w');
        if (in_array($week, [0, 6])) {
            return;
        }

        try {
            //日期；
            $days = [];
            //根据日期算出分配哪天的商品；
            $before = (int)$this->getData('day', 1);
            if ($before > 1) {
                $day = date('Y-m-d', strtotime('-'. $before. ' day'));
                $days[] = $day;
            } else {
                //以下是周二至五的逻辑；
                if ($week >= 2 && $week <= 5) {
                    $day = date('Y-m-d', strtotime('-1 day'));
                    $days[] = $day;
                } else if ($week == 1) {
                    //周一跑周五商品
                    $day = date('Y-m-d', strtotime('-'. ($before + 2). ' day'));
                    $days[] = $day;
                    //周一跑周六商品
                    $day = date('Y-m-d', strtotime('-'. ($before + 1). ' day'));
                    $days[] = $day;
                    //周一跑周日商品
                    $day = date('Y-m-d', strtotime('-'. $before. ' day'));
                    $days[] = $day;
                }
            }

            $serv = new AmazonPublishTaskService();
            $serv->assign($days);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(). '|'. $e->getLine(). '|'. $e->getFile());
        }
    }


    /**
     * 计算时间，是否是要运行的那个时间；
     * @param $hours
     * @return bool
     */
    public function run($hours)
    {
        $time = time();
        $day = date('Y-m-d');
        $start = strtotime($day) + $hours * 3600;
        $end = strtotime($day) + $hours * 3600 + 3600;
        if ($time > $start && $time <= $end) {
            return true;
        }
        return false;
    }

}
