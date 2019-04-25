<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 2018/9/17
 * Time: 17:23
 */

namespace app\customerservice\task;

use app\common\cache\Cache;
use app\common\exception\TaskException;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\ShopeeReturnQueue;
use app\customerservice\queue\ShopeeCancelQueue;
use app\customerservice\service\ShopeeDisputeService;
use app\index\service\AbsTasker;

class ShopeeReturn extends AbsTasker
{
    public function getName()
    {
        return "Shopee退货退款和取消订单";
    }

    public function getDesc()
    {
        return "Shopee退货退款和取消订单";
    }

    public function getCreator()
    {
        return "denghaibo";
    }

    public function getParamRule()
    {
        return [
            'overTime|结束时间' => 'require|select:现在:0,1天前:1,7天前:7,15天前:15,30天前:30,45天前:45,60天前:60',
            'lastTime|间隔时间' => 'require|select:1小时:1,3小时:3,1天:24,7天:168,15天:360'
        ];
    }

    public function execute()
    {
        (new ShopeeDisputeService())->cachingReturnsn();//最近半月已有退货单号
        $overTime = (int)$this->getData('overTime');
        $lastTime = (int)$this->getData('lastTime');
        $this->push($overTime, $lastTime);
    }

    private function push($overTime, $lastTime)
    {
        try {
            $returnQueue = new UniqueQueuer(ShopeeReturnQueue::class);
            $cancelQueue = new UniqueQueuer(ShopeeCancelQueue::class);
            $accountList = Cache::store('ShopeeAccount')->getTableRecord();
            foreach ($accountList as $k => $v) {
                $params = ['account_id' => $v['id'], 'overTime' => $overTime, 'lastTime' => $lastTime];
                $returnQueue->push($params);
                $cancelQueue->push($params);
            }
        } catch (\Exception $e) {
            throw new TaskException($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }


}