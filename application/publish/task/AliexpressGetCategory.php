<?php

/**
 *速卖通分类
 * Description of AliexpressCategory
 * @datetime 2017-5-23  17:35:29
 * @author joy
 */

namespace app\publish\task;
use app\common\cache\Cache;
use app\index\service\AbsTasker;
use app\publish\service\AliexpressTaskHelper;
use app\common\service\UniqueQueuer;
use app\publish\queue\AliexpressCategoryPidQueue;

class AliexpressGetCategory extends AbsTasker{
     /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "速卖通分类";
    }
    
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "速卖通分类";
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
       /* $config = Cache::store('AliexpressAccount')->getAccountById(34);
        if($config)
        {
	        $service = new AliexpressTaskHelper;
	        $service->getAeCategory($config,0);
        }*/
        $accounts = Cache::store('AliexpressAccount')->getAccounts();
        if ($accounts) {

            foreach ($accounts as $account) {
                if (isset($account['is_invalid']) && $account['is_invalid'] && isset($account['is_authorization']) && $account['is_authorization'] ) {

                    (new UniqueQueuer(AliexpressCategoryPidQueue::class))->push($account['id']);
                }
            }

        }
    }
    
}
