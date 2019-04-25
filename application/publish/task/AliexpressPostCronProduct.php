<?php

/**
 * 刊登速卖通刊登商品(定时刊登)
 * Description of AliexpressPostProduct
 * @datetime 2017-5-22  9:50:22
 * @author joy
 */

namespace app\publish\task;
use app\index\service\AbsTasker;
use app\common\exception\TaskException;
use think\Db;
use app\publish\service\AliexpressTaskHelper;

class AliexpressPostCronProduct extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "刊登速卖通刊登商品(定时刊登)";
    }
    
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "刊登速卖通刊登商品(定时刊登)";
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
        $where['status']=['=',0];
        $where['plan_time']=['<>',0];
        $pageSize=20;
        $page=1;
        $helper = new AliexpressTaskHelper;
        do{
            $jobs = Db::table('aliexpress_publish_plan')->where($where)->page($page,$pageSize)->select();
             
            if(empty($jobs))
            {
                break;
            }
            $helper->ManagerData($jobs);
            $page++;
            
        }while (count($jobs)==$pageSize);
    }
   
    
}

