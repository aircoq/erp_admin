<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 18-5-28
 * Time: 下午5:56
 */

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\publish\helper\lazada\LazadaHelper;
use think\Exception;

class LazadaImageUploadQueue //extends SwooleQueueJob
{
    const PRIORITY_HEIGHT = 40;

    public function getName(): string
    {
        return 'lazada图片上传队列';
    }

    public function getDesc(): string
    {
        return 'lazada图片上传队列';
    }

    public function getAuthor(): string
    {
        return 'thomas';
    }

    public function execute()
    {
        set_time_limit(0);
        try{
//            $id = $this->params;  //账号id，变体id
            $params = '31|5596';
            list($accountId, $variantId) = explode("|", $params);
            $res = (new LazadaHelper())->migrateImages($accountId, $variantId);
            if ($res !== true) {
                throw new Exception($res);
            }
        } catch (Exception $exp) {
            throw new QueueException($exp->getMessage());
        }
    }
}