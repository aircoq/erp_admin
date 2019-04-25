<?php


namespace app\goods\queue;

use app\common\service\SwooleQueueJob;
use app\goods\service\GoodsHelp;
use app\common\model\Goods;

class GoodsUpdatePlatform extends SwooleQueueJob
{
    public function getName(): string
    {
        return "商品更新Platform";
    }

    public function getDesc(): string
    {
        return "商品更新Platform";
    }

    public function getAuthor(): string
    {
        return "詹老师";
    }

    public function execute()
    {
        $page = 1;
        $oldPlatformMap = [
            'ebay' => 16,
            'amazon' => 8,
            'wish' => 4,
            'aliExpress' => 2,
            'joom' => 1,
            'umka' => 4096,
            'jumia' => 2048,
            'vova' => 1024,
            'lazada' => 512,
            'paytm' => 256,
            'shopee' => 128,
            'walmart' => 64,
            'pandao' => 32

        ];
        $GoodsHelp = new GoodsHelp();
        $newPlatform_map =$GoodsHelp->platform_map();
        $Goods = new Goods();
        $count = $Goods->where('platform_new', 0)->count();
        $pageSize = 100;
        $totalPage = ceil($count / $pageSize);
        do{
            $aGoods = $Goods->field('*')->where('id','>',$this->params)->where('platform_new', 0)->page($page, $pageSize)->select();
            foreach ($aGoods as $goodsInfo) {
                $result = 0;
                $platform = $goodsInfo['platform'];
                $data = [];
                foreach ($oldPlatformMap as $key => $value) {
                    if (($platform | $value) == $platform) {
                        $data[$key] = 1;
                    } else {
                        $data[$key] = 0;
                    }
                }
                foreach ($data as $name=>$value_id){
                    if ($value_id == 1) {
                        if (!isset($newPlatform_map[$name])) {
                            continue;
                        }
                        $value = $newPlatform_map[$name];
                        $result += $value;
                    }
                }
                $goodsInfo->platform_new = $result;
                $goodsInfo->save();
            }
            $page++;
        }while($page<$totalPage);
    }
}