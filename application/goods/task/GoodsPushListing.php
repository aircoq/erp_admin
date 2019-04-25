<?php

namespace app\goods\task;

use app\goods\service\GoodsHelp;
use app\index\service\AbsTasker;
use app\common\model\Goods;
use app\common\service\CommonQueuer;
use app\publish\queue\GoodsPublishMapQueue;
use think\Exception;
use app\common\exception\TaskException;



class GoodsPushListing extends AbsTasker
{

    const LEN = 500;
    private $queue = null;

    public function __construct()
    {
        $this->aopTimezone = date_default_timezone_get(); //请求返回的数据要求的时区（本地服务器时区）
        $this->apiTimeZone = 'Etc/GMT+8'; //太平洋时区（西八区）
        $this->retime = 43200; //一天 重复抓取的时间，单位为秒
        $this->queue = new CommonQueuer(GoodsPublishMapQueue::class);
    }

    public function getCreator()
    {
        return '詹熏欣';
    }

    public function getDesc()
    {
        return '商品修改推送';
    }

    public function getName()
    {
        return '商品修改推送';
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
            $type = $type=='' ? 'NEW' : $type;
            if($type=='NEW'){
                $this->pushNew();
            }else if($type=='ALL'){
                $this->pushAll();
            }

        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }

    }
    public function pushNew(){
        $allowStatus = [1,4];
        $aGoods = Goods::where('status', 1)
            ->order('id desc')
            ->field('id,spu,status,platform,sales_status,category_id')
            ->limit(self::LEN)
            ->select();
        $GoodsHelp = new GoodsHelp();
        foreach ($aGoods as $goodsInfo) {
            if(!in_array($goodsInfo['sales_status'],$allowStatus)){
                continue;
            }
            $row = [];
            $row['id'] = $goodsInfo['id'];
            $row['spu'] = $goodsInfo['spu'];
            $row['status'] = $goodsInfo['status'];
            $platform_sale = $GoodsHelp->getPlatformSaleJson($goodsInfo['platform']);
            $platform_sale = array_map(function($val){
                if(!$val){
                    return 2;
                }else{
                    return $val;
                }

            },$platform_sale);
            $platform_sale['joom'] = 1;
            $row['platform_sale'] = json_encode($platform_sale); //乔哥那边 1 是可以，禁止是2
            $row['sales_status'] = $goodsInfo['sales_status'];
            $row['category_id'] = $goodsInfo['category_id'];
            $this->queue->push($row);
        }
    }

    private function pushAll()
    {
        $page_size = 1000;
        $page = 1;
        $allowStatus = [1,4];
        do{
            $GoodsHelp = new GoodsHelp();
            $aGoods = Goods::where('status', 1)
                ->order('id desc')
                ->field('id,spu,status,platform,sales_status,category_id')
                ->page($page,$page_size)
                ->select();
            foreach ($aGoods as $goodsInfo) {
                if(!in_array($goodsInfo['sales_status'],$allowStatus)){
                    continue;
                }
                $row = [];
                $row['id'] = $goodsInfo['id'];
                $row['spu'] = $goodsInfo['spu'];
                $row['status'] = $goodsInfo['status'];
                $platform_sale = $GoodsHelp->getPlatformSaleJson($goodsInfo['platform']);
                $platform_sale = array_map(function($val){
                    if(!$val){
                        return 2;
                    }else{
                        return $val;
                    }
                },$platform_sale);
                $platform_sale['joom'] = 1;
                $row['platform_sale'] = json_encode($platform_sale);
                $row['sales_status'] = $goodsInfo['sales_status'];
                $row['category_id'] = $goodsInfo['category_id'];
                $this->queue->push($row);
            }
            $page++;
        }while($page<100);
    }
}