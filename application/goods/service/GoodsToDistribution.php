<?php


namespace app\goods\service;

use service\distribution\distributionApi;
use think\Db;
use think\Exception;
use app\common\model\GoodsGallery;
use app\common\model\Goods;
use app\order\service\OrderRuleExecuteService;

class GoodsToDistribution
{

    private function getEnv()
    {
        if (in_array($_SERVER['HTTP_HOST'], ['erp.com', '172.20.1.242', '172.18.8.242'])) {
            return 'test';
        }
        return 'product';
    }

    private function getConfig()
    {
        $env = $this->getEnv();
        if ($env == 'test') {
            return [
                'api_key' => 'AFNLAGKHIHJBAMEA',
                'api_token' => '5l4yudcsnckgmeaypz25rws5kw004fbf'
            ];
        }
        return [
            'api_key' => 'GFGHDFICFPNELNOG',
            'api_token' => 'edxt41s24vfhso0i5vn2kyav2vxkigrx'
        ];
//        return [
//            'api_key' => 'AFNLAGKHIHJBAMEA',
//            'api_token' => '5l4yudcsnckgmeaypz25rws5kw004fbf'
//        ];

    }


    public function getCategory()
    {
        $data = $this->api()->getCategoryTree();
        return $data;
    }

    public function saveSpu($spu, $categoryId = '95', $canUseChannel)
    {
        $result = $this->api()->saveGoodsBase($spu, $categoryId, $canUseChannel);
        if (!$result) {
            throw new Exception("连接失败");
        }
        return json_decode($result, true);
    }

    private function api($name = 'goods', $config = null)
    {
        if (!$config) {
            $config = $this->getConfig();
        }
        return distributionApi::instance($config)->loader($name);
    }

    public function saveLang($spu, $detail)
    {
        $result = $this->api()->saveLang($spu, $detail);
        return $this->netResult($result, __FUNCTION__);
    }

    public function netResult($data, $type = '')
    {
        $result = ['err' => ''];
        if (!$data) {
            $result['err'] = $type . '接口链接失败';
            return $result;
        }
        $data = json_decode($data, true);
        if ($data['errorCode'] > 0) {
            $result['err'] = $data['errorMessage'];
            return $result;
        }
        return $result;
    }

    public function getSpuAttr($spu)
    {
        return $this->api()->getSpuAttr($spu);
    }

    public function saveSku($spu, $aSku)
    {
        $result = $this->api()->saveSku($spu, $aSku);
        return $this->netResult($result, __FUNCTION__);
    }

    public function saveImg($spu, $spu_thumb, $sku_thumb, $del_images = [], $aSku)
    {
        $result = $this->api()->saveImg($spu, $spu_thumb, $sku_thumb, $del_images, $aSku);
        return $this->netResult($result, __FUNCTION__);
    }

    public function getWarehouseId($warehouseId)
    {
        if (!$warehouseId) {
            $warehouseId = 2;
        }
        $map = [
            2 => '2_ZSW',
            6 => '6_JHW'
        ];
        $aWarehouse = $this->api()->getWarehouse();
        if (!$aWarehouse) {
            throw new Exception('分销服务器链接失败');
        }

        if ($aWarehouse['errorCode'] != 0) {
            throw new Exception($aWarehouse['errorMessage']);
        }
        if (!isset($map[$warehouseId])) {
            throw new Exception('该spu仓库不支持推送');
        }
        $third = $map[$warehouseId];
        foreach ($aWarehouse['data'] as $v) {
            if ($v['third_code'] == $third) {
                return $v['value'];
            }
        }
        throw new Exception('找不到对应的仓库id');
    }


    public function pushGoods($goods_id)
    {
        $goodsHelp = new GoodsHelp();
        $goodsInfo = $goodsHelp->getGoodsInfo($goods_id);
        if (!$goodsInfo) {
            throw new Exception('该商品不存在');
        }
        $warehouse_id = $this->getWarehouseId($goodsInfo['warehouse_id']);
        $Goods = new Goods();
        $goodsInfo['category_name'] = $Goods->getCategoryAttr(null, ['category_id' => $goodsInfo['category_id']]);
        $category_name = explode('>', $goodsInfo['category_name']);
        $goodsPlat = $goodsHelp->getPlatformSaleJson($goodsInfo['platform']);
        $tmpChannel = ['ebay' => 1, 'amazon' => 4, 'wish' => 3, 'aliExpress' => 2];
        $tmpChannelKey = array_keys($tmpChannel);
        $canUpChannel = [];
        foreach ($goodsPlat as $k => $v) {
            if (!$v) {
                continue;
            }
            if (in_array($k, $tmpChannelKey)) {
                $canUpChannel[] = $tmpChannel[$k];
            }
        }

        $aSku = GoodsSku::getSkuByGoodsId($goods_id);
        $pushSku = [];
        foreach ($aSku as $skuInfo) {
            $attr = json_decode($skuInfo['sku_attributes'], true);
            $attrKey = array_keys($attr);
//            if (array_intersect($attrKey, [11, 15])) {
//                continue;
//            }
            $pushSku[] = $skuInfo;
        }
        if (!$pushSku) {
            throw new Exception('该商品未含有可推送的sku');
        }
        try {
            $flag = $this->saveSpu($goodsInfo['spu'], $category_name, $canUpChannel);
            if ($flag['errorCode'] != 0) {
                throw new Exception($flag['errorMessage']);
            }
            $err = [];
            $langs = $goodsHelp->getLang($goods_id);
            if ($langs) {
                $detail = [];
                foreach ($langs as $langInfo) {
                    if (!in_array($langInfo['lang_id'], [1, 2])) {
                        continue;
                    }
                    $row = [];
                    $row['lang'] = $langInfo['lang_id'] == 1 ? 'zh' : 'en';
                    $row['title'] = $langInfo['title'];
                    $row['second_title'] = $langInfo['title'];
                    $row['tags'] = $langInfo['tags'];
                    $row['description'] = $langInfo['description'] ? $langInfo['description'] : '描述为空';
                    $detail[] = $row;
                }
                if ($detail) {
                    $tmp = $this->saveLang($goodsInfo['spu'], $detail);
                    if ($tmp['err']) {
                        $err[] = $tmp['err'];
                    }
                }
            }
            $imgList = $this->getGoodsGallery($goods_id);
            $infoSku = [];
            $sku_thumb = [];
            $spu_thumb = $imgList['spu'];
            $sku_ids = [];
            foreach ($pushSku as $skuInfo) {
                $row = [];
                $row['sku'] = $skuInfo['sku'];
                $row['weight'] = $skuInfo['weight'];
                $row['volume_l'] = $skuInfo['length'];
                $row['volume_w'] = $skuInfo['width'];
                $row['volume_h'] = $skuInfo['height'];
                $goods_sku_warehouse = [];
                $goods_sku_warehouse['warehouse_id'] = $warehouse_id;
                $goods_sku_warehouse['cost_price'] = $skuInfo['cost_price'];
                $goods_sku_warehouse['price'] = $skuInfo['cost_price'];
                if ($skuInfo['retail_price']) {
                    $currency = new OrderRuleExecuteService();
                    $skuInfo['retail_price'] = $currency->convertCurrency('USD', 'CNY', $skuInfo['retail_price']);
                }
                $goods_sku_warehouse['retail_price'] = $skuInfo['retail_price'];
                $row['goods_sku_warehouse'][] = $goods_sku_warehouse;
                $attr = json_decode($skuInfo['sku_attributes'], true);
                $jsonAttr = GoodsHelp::getAttrbuteInfoBySkuAttributes($attr, $goods_id);
                $attrDetail = [];
                $userdefine_attrs = [];
                foreach ($jsonAttr as $_k => $_v) {
                    $attrRow = [];
                    $attrRow['attr_name'] = $_v['name'];
                    $attrRow['value'] = $_v['value'];
                    if (in_array($_v['id'], [11, 15])) {
                        $userdefine_attrs[] = $attrRow;
                    } else {
                        $attrDetail[] = $attrRow;
                    }
                }
//                if(!$attrDetail){
//                    continue;
//                }
                $row['attrs'] = $attrDetail;
                $row['userdefine_attrs'] = $userdefine_attrs;
                $infoSku[] = $row;
                if ($imgList['sku']) {
                    if (isset($imgList['sku'][$skuInfo['id']])) {
                        $sku_thumb[] = $imgList['sku'][$skuInfo['id']];
                        $sku_ids[] = $skuInfo['sku'];
                    }
                }
            }
            $infoImg = ['sku_thumb' => [], 'spu_thumb' => [], 'sku_ids' => []];
            $infoImg['spu_thumb'] = $spu_thumb;
            if ($infoSku) {
                $flag = $this->saveSku($goodsInfo['spu'], $infoSku);
                if (!$flag['err']) {
                    //sku保存成功才处理图片
                    $infoImg['sku_thumb'] = $sku_thumb;
                    $infoImg['sku_ids'] = $sku_ids;
                } else {
                    $err[] = $flag['err'];
                }
            }
            if ($infoImg['spu_thumb'] || $infoImg['sku_ids']) {
                $flag = $this->saveImg($goodsInfo['spu'], $infoImg['spu_thumb'], $infoImg['sku_thumb'], [], $infoImg['sku_ids']);
                if ($flag['err']) {
                    $err[] = $flag['err'];
                }
            }
            if ($err) {
                throw new Exception(implode(',', $err));
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    private function getGoodsGallery($goods_id)
    {
        $result = ['sku' => [], 'spu' => []];
        $imgList = GoodsGallery::where('goods_id', $goods_id)->order('is_default asc')->select();
        $skuCount = [];
        foreach ($imgList as $v) {
            $path = GoodsImage::getThumbPath($v['path'], 0, 0);
            if ($v['sku_id']) {
                if (isset($skuCount[$v['sku_id']])) {
                    if ($skuCount[$v['sku_id']] > 5) {
                        continue;
                    }
                }
                $result['sku'][$v['sku_id']][] = $path;
                $skuCount[$v['sku_id']] = isset($skuCount[$v['sku_id']]) ? ($skuCount[$v['sku_id']] + 1) : 1;
            } else {
                $result['spu'][] = $path;
            }
        }
        if (count($result['sku']) == 1) {
            $result['spu'] = array_merge($result['spu'], reset($result['sku']));
        }
        $result['spu'] = array_slice($result['spu'], 0, 5);
        return $result;

    }


}