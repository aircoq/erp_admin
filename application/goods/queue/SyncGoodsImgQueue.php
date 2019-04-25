<?php

/**
 * Created by PhpStorm.
 * User: TOM
 * Date: 2017/8/30
 * Time: 11:16
 */

namespace app\goods\queue;

use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsImageDownloadNewService;
use think\Exception;

class SyncGoodsImgQueue extends SwooleQueueJob
{

    public function getName(): string
    {
        return '下载远程产品图片队列';
    }

    public function getDesc(): string
    {
        return '下载远程产品图片队列';
    }

    public function getAuthor(): string
    {
        return 'Tom';
    }
    
    public static function swooleTaskMaxNumber():int
    {
        return 3;
    }
    protected $maxFailPushCount = 5;

    public function execute()
    {
        $params = $this->params;
        try {
            $goodsImgDownloadService = new GoodsImageDownloadNewService();
            if (is_array($params)) {
                $path = isset($params['path']) ? $params['path'] : '';
                $goodsImgDownloadService->syncImg($params['goods_id'], $path);
            } else {//兼容之前只存goodsid
                $goodsImgDownloadService->syncImg($params);
            }
        } catch (\Exception $exception) {
            throw new QueueException($exception->getMessage());
        }
    }
}