<?php

/**
 *速卖通属性
 * Description of AliexpressAttribute
 * @datetime 2017-5-23  17:35:29
 * @author joy
 */

namespace app\publish\task;
use app\common\cache\Cache;
use app\common\exception\TaskException;
use app\common\model\aliexpress\AliexpressCategoryAttr;
use app\common\service\CommonQueuer;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\publish\queue\AliexpressCategoryAttributeQueue;
use app\publish\service\AliexpressTaskHelper;
use service\aliexpress\AliexpressApi;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\aliexpress\AliexpressCategory as Category;
use think\Db;
use think\Exception;

class AliexpressAttibutes extends AbsTasker{
    private $queueDriver;
     /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "速卖通分类属性";
    }
    
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "速卖通分类属性";
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
    public function beforeExec()
    {
        $this->queueDriver = new UniqueQueuer(AliexpressCategoryAttributeQueue::class);
        return true;
    }

    /**
     * 任务执行内容
     * @return void
     */
    
    public function execute()
    {

        try{
            set_time_limit(0);
            $config = Cache::store('AliexpressAccount')->getAccountById(34);
            $page = 1;
            $pageSize=100;
            $AliexpressCategory = new Category;

            do{
                $where=[
                    'category_isleaf'=>1,
                ];
                $categorys = $AliexpressCategory->where($where)->page($page,$pageSize)->order('category_level DESC')->select();

                if(empty($categorys))
                {
                    break;
                }else{
                    //$this->cateAttributes($categorys);
                    $this->pushQueue($categorys);
                    ++$page;
                }
            }while(count($categorys)==$pageSize);
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }
    }
    private function cateAttributes($categorys){
        $attributeModel = new AliexpressCategoryAttr();
        $helper = new AliexpressTaskHelper();
        $config = Cache::store('AliexpressAccount')->getAccountById(34);
        foreach($categorys as $category)
        {
            if(is_object($category))
            {
                $category = $category->toArray();
            }
            $helper->getAeAttribute($config, $attributeModel, $category['category_id']);
        }
    }
    protected function pushQueue($categorys){
        foreach ($categorys as $category){
            $this->queueDriver->push($category['category_id']);
        }
        return true;
    }
    
}
