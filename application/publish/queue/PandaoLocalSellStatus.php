<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-6-9
 * Time: 下午5:50
 */

namespace app\publish\queue;


use app\common\service\SwooleQueueJob;
use app\publish\service\CommonService;
use think\Exception;

class PandaoLocalSellStatus extends SwooleQueueJob
{
    private $channel_id=8;
    public function getName():string
    {
        return '更新pandao在线listing本地销售状态';
    }
    public function getDesc():string
    {
        return '更新pandao在线listing本地销售状态';
    }
    public function getAuthor():string
    {
        return 'joy';
    }

    public  function execute()
    {
        $params = $this->params;
        if($params)
        {
            CommonService::updateListingSellStatus($this->channel_id,$params);
        }else{
            throw new Exception("数据为空");
        }
    }
}