<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 18-5-28
 * Time: 下午3:42
 */

namespace app\publish\task;


use app\common\model\lazada\LazadaCategory;
use app\common\model\lazada\LazadaSite;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\publish\helper\lazada\LazadaHelper;
use app\publish\queue\LazadaAttributesQueue;
use think\Exception;

class LazadaAttributesTask extends AbsTasker
{
    private $queueDriver=null;
    public function getName()
    {
        return 'lazada分类属性任务';
    }

    public function getDesc()
    {
        return 'lazada分类属性任务';
    }

    public function getCreator()
    {
        return 'thomas';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        set_time_limit(0);
        try {
            $this->queueDriver = (new UniqueQueuer(LazadaAttributesQueue::class));

            $this->getAllAttributeBySiteAndCategory();
        } catch (Exception $exp) {
            throw new Exception($exp->getMessage());
        }
    }

    private function getAllAttributeBySiteAndCategory()
    {
        $page=1;
        $pageSize=50;
        do {
            $categories = LazadaCategory::page($page,$pageSize)->select();
            if (empty($categories)) {
                break;
            } else {
                $this->pushCategory2Queue($categories);
                $page = $page + 1;
            }
        } while (count($categories) == $pageSize);
    }

    private function pushCategory2Queue($categories)
    {
        $params = '5|3640';
        list($siteId,$categoryId) = explode("|",$params);
        $country = LazadaSite::where(['id'=>$siteId])->value('code');
        $res = (new LazadaHelper())->syncAttributes($siteId, $country, $categoryId);
        echo $res;
        exit;
        foreach ($categories as $category) {

            $site = LazadaSite::where(['id'=> $category['site_id']])->value('code');
            $res = (new LazadaHelper())->syncAttributes($category['site_id'], $site, $category['category_id']);
            if ($res !== true) {
                echo $res."\n";
            } else {
                echo "sync category attributes completely\n";
            }
//            list($siteId,$categoryId) = explode("|",$params);
//            $queue = $category['site_id']."|".$category['category_id'];
//            $this->queueDriver->push($queue);
        }
    }
}