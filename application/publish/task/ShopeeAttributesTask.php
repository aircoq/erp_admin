<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 18-5-28
 * Time: 下午3:42
 */

namespace app\publish\task;


use app\common\model\shopee\ShopeeCategory;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\publish\queue\ShopeeAttributesQueue;
use think\Exception;

class ShopeeAttributesTask extends AbsTasker
{
    private $queueDriver=null;
    public function getName()
    {
        return 'shopee分类属性任务';
    }

    public function getDesc()
    {
        return 'shopee分类属性任务';
    }

    public function getCreator()
    {
        return 'wlw2533';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        set_time_limit(0);
        try{
            $this->queueDriver = (new UniqueQueuer(ShopeeAttributesQueue::class));
            $this->getAllAttributeBySiteAndCategory();
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }
    }
    private function getAllAttributeBySiteAndCategory(){
        $page=1;
        $pageSize=50;
        do{
            $categories = ShopeeCategory::where('has_children',0)->page($page,$pageSize)->select();
            if(empty($categories)){
                break;
            }else{
                $this->pushCategory2Queue($categories);
                $page = $page + 1;
            }
        }while(count($categories) == $pageSize);
    }
    private function pushCategory2Queue($categories){
        foreach ($categories as $category){
            $queue = $category['site_id']."|".$category['category_id'];
            $this->queueDriver->push($queue);
        }
    }
}