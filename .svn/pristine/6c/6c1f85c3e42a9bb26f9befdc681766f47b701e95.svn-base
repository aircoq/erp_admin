<?php


namespace app\goods\task;

use app\index\service\AbsTasker;
use app\common\service\CommonQueuer;
use think\Exception;
use app\common\exception\TaskException;
use app\goods\queue\GoodsToDistributionQueue;
use app\common\model\Goods;

class GoodsToDistribution extends AbsTasker
{
    const LEN = 500;
    private $queue = null;

    public function __construct()
    {
        $this->aopTimezone = date_default_timezone_get(); //请求返回的数据要求的时区（本地服务器时区）
        $this->apiTimeZone = 'Etc/GMT+8'; //太平洋时区（西八区）
        $this->retime = 43200; //一天 重复抓取的时间，单位为秒
        $this->queue = new CommonQueuer(GoodsToDistributionQueue::class);
    }

    public function getCreator()
    {
        return '詹老师';
    }

    public function getDesc()
    {
        return '商品推送分销';
    }

    public function getName()
    {
        return '商品推送分销';
    }

    public function getParamRule()
    {
        return [
            'type|商品类型' => 'require|select:全部商品:ALL,最新商品:NEW',
        ];
    }

    public function execute()
    {
        set_time_limit(0);
        try {
            $type = $this->getData('type');
            $type = $type == '' ? 'ALL' : $type;
            if ($type == 'NEW') {
                $this->pushNew();
            } else if ($type == 'ALL') {
                $this->pushAll();
            }

        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }

    }

    public function pushNew()
    {
        $allowStatus = [1, 4];
        $aGoods = Goods::where('status', 1)
            ->order('id desc')
            ->field('id')
            ->limit(self::LEN)
            ->select();

        foreach ($aGoods as $goodsInfo) {
//            if (!in_array($goodsInfo['sales_status'], $allowStatus)) {
//                continue;
//            }
            $this->queue->push($goodsInfo['id']);
        }
    }

    public function pushAll()
    {
        $page_size = 1000;
        $page = 1;
        $allowStatus = [1, 4];
        do {
            $aGoods = Goods::where('status', 1)
                ->order('id desc')
                ->field('id,sales_status')
                ->page($page, $page_size)
                ->select();
            foreach ($aGoods as $goodsInfo) {
                if($goodsInfo['sales_status']==2){
                    continue;
                }
                $this->queue->push($goodsInfo['id']);
            }
            $page++;
        } while ($page < 100);
    }
}
