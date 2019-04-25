<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/1/4
 * Time: 18:02
 */

namespace app\publish\queue;


use app\common\model\GoodsPublishMap;
use app\common\service\SwooleQueueJob;
use think\Exception;

class GoodsPublishMapStatusUpdateQueue extends SwooleQueueJob
{
    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }
    public function getName():string
    {
        return '商品刊登映射表刊登状态更新';
    }
    public function getDesc():string
    {
        return '商品刊登映射表刊登状态更新';
    }
    public function getAuthor():string
    {
        return 'wlw2533';
    }

    public  function execute()
    {
        try {
            $ids = $this->params;
            $publishStatus = GoodsPublishMap::whereIn('id',$ids)->column('publish_status','id');
            foreach ($publishStatus as &$ps) {
                $tmp = json_decode($ps,true);
                if (!$tmp) {
                    continue;
                }
                $tmp = array_values(array_unique($tmp));
                $newPs = [];
                foreach ($tmp as $t) {
                    if (is_array($t)) {
                        $newPs = array_merge($newPs,$t);
                    } else {
                        $newPs[] = (string)$t;
                    }
                }
                $newPs = array_values(array_unique($newPs));
                $ps = json_encode($newPs);
            }
            (new GoodsPublishMap())->saveAll($publishStatus);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}