<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 18-8-20
 * Time: 上午11:48
 */

namespace app\publish\queue;


use app\common\model\lazada\LazadaSite;
use app\common\service\SwooleQueueJob;
use app\publish\helper\lazada\LazadaHelper;
use think\Exception;

class LazadaAttributesQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return 'lazada分类属性队列';
    }

    public function getDesc(): string
    {
        return 'lazada分类属性队列';
    }

    public function getAuthor(): string
    {
        return 'thomas';
    }

    public function execute()
    {
        set_time_limit(0);
        try {
            $params = $this->params;
            list($siteId,$categoryId) = explode("|",$params);
            $country = LazadaSite::where(['id' => $siteId])->value('code');
            $res = (new LazadaHelper())->syncAttributes($siteId, $country, $categoryId);
            if ($res !== true) {
                echo $res."\n";
            } else {
                echo "sync category attributes completely\n";
            }
        } catch (Exception $exp) {
            throw new Exception("{$exp->getMessage()}");
        }
    }
}