<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/1/4
 * Time: 11:00
 */

namespace app\publish\task;


use app\common\model\GoodsPublishMap;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\publish\queue\GoodsPublishMapStatusUpdateQueue;

class GoodsPublishMapStatusUpdate extends AbsTasker
{
    public function getName()
    {
        return "商品刊登映射表刊登状态更新";
    }

    public function getDesc()
    {
        return "商品刊登映射表刊登状态更新";
    }

    public function getCreator()
    {
        return "wlw2533";
    }

    public function getParamRule()
    {
        return [];
    }
    public function execute()
    {
        set_time_limit(0);
        try {
            $offset = 0;
            $length = 1000;
            $wh['channel'] = ['neq', 1];//ebay平台的不处理
            do {
                $ids = GoodsPublishMap::where($wh)->order('id')->limit($offset, $length)->column('id');
                if (!$ids) {
                    break;
                }
                (new UniqueQueuer(GoodsPublishMapStatusUpdateQueue::class))->push($ids);
                $offset += count($ids);
            } while (1);

        } catch (\Exception $e) {
            return ['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()];
        }
    }


}