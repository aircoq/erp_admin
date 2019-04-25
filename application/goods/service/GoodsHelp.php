<?php

namespace app\goods\service;

use app\common\model\Goods;
use app\common\cache\Cache;
use app\common\model\GoodsCategoryMap;
use app\common\model\GoodsSku;
use app\common\model\GoodsSourceUrl;
use app\common\model\Channel;
use app\common\model\GoodsAttribute;
use app\common\model\GoodsLang;
use app\common\model\Category;
use app\common\model\GoodsGallery;
use app\common\model\Lang;
use app\common\model\GoodsTortDescription;
use app\common\model\TortEmailAttachment;
use app\common\service\Common;
use app\goods\queue\GoodsToDistributionQueue;
use app\index\service\User;
use think\Exception;
use think\Db;
use app\common\model\GoodsDevelopLog;
use app\common\model\GoodsSkuAlias;
use app\purchase\service\SupplierService;
use app\purchase\service\SupplierOfferService;
use app\purchase\service\PurchaseProposal;
use app\common\exception\JsonErrorException;
use app\common\model\GoodsImgRequirement;
use app\common\validate\GoodsCategotyMap as ValidateGoodCategoryMap;
use app\warehouse\type\ProductType;
use app\warehouse\type\SkuType;
use app\warehouse\service\GuanYiWarehouse;
use app\index\service\DownloadFileService;
use app\common\validate\GoodsSku as ValidateGoodsSku;
use app\common\validate\GoodsTortDescription as ValidateGoodsTortDescription;
use app\common\model\SupplierGoodsOffer;
use app\common\validate\GoodsImgRequirement as ValidateGoodsImgRequirement;
use app\goods\service\GoodsSkuAlias as GoodsSkuAliasServer;
use app\common\service\UniqueQueuer;
use app\goods\queue\SyncGoodsImgQueue;
use app\goods\service\GoodsSku as ServiceGoodsSku;
use app\warehouse\service\WarehouseCargoGoods;
use app\publish\queue\GoodsPublishMapQueue;
use app\publish\queue\AliexpressLocalSellStatus;
use app\publish\queue\WishLocalSellStatus;
use app\publish\queue\PandaoLocalSellStatus;
use app\common\service\CommonQueuer;
use app\goods\queue\GoodsPushIrobotbox;
use app\common\validate\GoodsLang as ValidateGoodsLang;
use app\goods\service\GoodsGalleryPhash;
use app\goods\service\GoodsGalleryDhash;
use app\goods\service\GoodsNotice;
use app\report\service\MonthlyTargetAmountService;
use app\common\model\report\ReportGoodsByDeveloper;
use think\db\Query;
use PDO;
use app\warehouse\service\WarehouseGoods;
use app\order\service\OrderService;
use app\index\service\ChannelDistribution;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/6
 * Time: 18:14
 */
class GoodsHelp
{
    // 产品sku属性规则
    private $sku_rules = [
        'color' => ['default' => 'ZZ', 'length' => 2],
        'size' => ['default' => 0, 'length' => 1],
        'style' => ['default' => 'Z', 'length' => 1],
        'specification' => ['default' => 'Z', 'length' => 1]
    ];

    // sku状态
    public $sku_status = [
        1 => '在售',
        2 => '停售',
        3 => '待发布',
        4 => '卖完下架',
        5 => '缺货',
    ];

    // 平台销售状态
    public $platform_sale_status = [
        1 => '可选上架',
        0 => '禁止上架'
    ];
    //显示 页面上的 渠道（平台上下架情况）
    public $show_channel = [
        'ebay',
        'amazon',
        'wish',
        'aliExpress',
        'pandao',
        'joom',
        'shopee'
    ];

    /**
     * 各平台对应的位运算
     * @var array
     */
//    public $platform_map = [
//        'ebay' => 16,
//        'amazon' => 8,
//        'wish' => 4,
//        'aliExpress' => 2,
//        'joom' => 1,
//        'umka' => 4096,
//        'jumia' => 2048,
//        'vova' => 1024,
//        'lazada' => 512,
//        'paytm' => 256,
//        'shopee' => 128,
//        'walmart' => 64,
//        'pandao' => 32
//    ];

//    public function platform_map()
//    {
//        $aChannel = Cache::store('channel')->getChannel();
//        $result = [];
//        foreach ($this->show_channel as $k) {
//            if (!isset($aChannel[$k])) {
//                continue;
//            }
//            $channelInfo = $aChannel[$k];
//            $result[$k] = pow(2, $channelInfo['id'] - 1);
//        }
//        return $result;
//    }

    /**
     * @title 根据channelId计算出匹配的值
     * @param $channel_id
     * @return number
     * @author starzhan <397041849@qq.com>
     */
    public function getPlatformValueByChannelId($channel_id)
    {
        return pow(2, $channel_id - 1);
    }

    /**
     * @var array 临时存放channel变量
     */
    private $tmpChannel = [];

    /**
     * @title 返回渠道配置
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getChannel()
    {
        if ($this->tmpChannel === []) {
            $this->tmpChannel = cache::store('channel')->getChannel();
        }
        return $this->tmpChannel;
    }

    /**
     * @title 根据channel_name计算出匹配的值.
     * @param $channelName
     * @return number
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function getPlatformValueByChannelName($channelName)
    {
        $aChannel = $this->getChannel();
        if (!isset($aChannel[$channelName])) {
            return 0;
        }
        $channelInfo = $aChannel[$channelName];
        return pow(2, $channelInfo['id'] - 1);
    }


    // 出售状态
    public $sales_status = [
        1 => '在售',
        2 => '停售',
        3 => '待发布',
        4 => '卖完下架',
        5 => '缺货',
        6 => '部分在售'
    ];

    // 物流属性
    private $transport_properties = [
        // 六级
        'general' => ['name' => '普货', 'field' => 'general', 'value' => 0x1, 'exclusion' => 0xffffe],
        // 一级
        'purebattery' => ['name' => '纯电池', 'field' => 'purebattery', 'value' => 0x2, 'exclusion' => 0xfffc, 'electricity' => 1],
        'highbattery' => ['name' => '超大电池', 'field' => 'highbattery', 'value' => 0x4, 'exclusion' => 0xfffb, 'electricity' => 1],
        'bigsize' => ['name' => '超尺寸', 'field' => 'bigsize', 'value' => 0x8, 'exclusion' => 0xfff7],
        'maxvolume' => ['name' => '抛货', 'field' => 'maxvolume', 'value' => 0x10, 'exclusion' => 0xffef],
        // 二级
        'liquid' => ['name' => '液体', 'field' => 'liquid', 'value' => 0x20, 'exclusion' => 0xffcf],
        'powder' => ['name' => '粉状', 'field' => 'powder', 'value' => 0x40, 'exclusion' => 0xffbf],
        'pastesolid' => ['name' => '膏状', 'field' => 'pastesolid', 'value' => 0x80, 'exclusion' => 0xff7f],
        // 三级
        'builtinbattery' => ['name' => '电池内置', 'field' => 'builtinbattery', 'value' => 0x100, 'exclusion' => 0xff, 'electricity' => 1],
        'swordfire' => ['name' => '刀枪火', 'field' => 'swordfire', 'value' => 0x200, 'exclusion' => 0xff],
        // 四级
        'buttonbattery' => ['name' => '带纽扣电池', 'field' => 'buttonbattery', 'value' => 0x400, 'exclusion' => 0xff, 'electricity' => 1],
        'bluetooth' => ['name' => '带蓝牙标示产品', 'field' => 'bluetooth', 'value' => 0x800, 'exclusion' => 0xff],
        // 五级
        'withmagnetic' => ['name' => '带磁性', 'field' => 'withmagnetic', 'value' => 0x1000, 'exclusion' => 0xff],
        'capacitance' => ['name' => '带电容', 'field' => 'capacitance', 'value' => 0x2000, 'exclusion' => 0xff, 'electricity' => 1],
        'fragile' => ['name' => '易碎品', 'field' => 'fragile', 'value' => 0x4000, 'exclusion' => 0xff],
        'seed' => ['name' => '种子', 'field' => 'seed', 'value' => 0x8000, 'exclusion' => 0xff],
        'sexy' => ['name' => '情趣用品', 'field' => 'sexy', 'value' => 0x10000, 'exclusion' => 0xff],
        // 六级
        'led' => ['name' => 'LED灯', 'field' => 'led', 'value' => 0x20000, 'exclusion' => 0x1],
        'strongmagnetic' => ['name' => '强磁性', 'field' => 'strongmagnetic', 'value' => 0x40000, 'exclusion' => 0x1001],
        'heterosexual_packaging' => ['name' => '异形包装', 'field' => 'heterosexual_packaging', 'value' => 0x80000, 'exclusion' => 0x1],
    ];

    public function getPurchaserId($goods_id)
    {
        $aGoods = Cache::store('goods')->getGoodsInfo($goods_id);
        $aUser = [];
        if (!empty($aGoods['purchaser_id'])) {
            $aUser = Cache::store('user')->getOneUser($aGoods['purchaser_id']);
        }
        return isset($aUser['realname']) ? $aUser['realname'] : '';
    }

    private static function getGoodsWhere($condition, $count = false)
    {
        $where = [];
        $whereOR = [];
        if (isset($condition['category_id']) && !empty($condition['category_id'])) {
            $category_list = Cache::store('category')->getCategoryTree();
            if ($category_list[$condition['category_id']]) {
                $child_ids = $category_list[$condition['category_id']]['child_ids'];
                if (count($child_ids) > 1) {
                    $where['a.category_id'] = ['in', $child_ids];
                } else {
                    $where['a.category_id'] = ['=', $condition['category_id']];
                }
            }
        }
        $isleft = false;
        if (isset($condition['snType']) && isset($condition['snText']) && !empty($condition['snText'])) {
            $snType = trim($condition['snType']);
            $snText = trim($condition['snText']);
            $aSnText = explode(',', $snText);
            switch ($snType) {
                case 'sku':
                    if (count($aSnText) == 1) {
                        $sku_id = self::getSkuIdByAlias($snText);
                        $sku_id ? $where['b.id'] = ['=', $sku_id] : $where['b.sku'] = ['like', $snText . '%'];
                    } else if (count($aSnText) > 1) {
                        $GoodsSku = new ServiceGoodsSku();
                        $skuIds = $GoodsSku->getASkuIdByASkuOrAlias($aSnText);
                        $skuIds = array_values($skuIds);
                        $where['b.id'] = ['in', $skuIds];
                    }

                    break;
                case 'title':
//                    $where['match(a.name)'] = ['exp', " against('{$snText}' in boolean mode)"];
                    $where['a.name'] = ['like', '%' . $snText . '%'];
                    $isleft = true;
                    break;
                case 'strict_sku'://主商品名称 sku字段
                    $sku_id = self::getSkuIdByAlias($snText);
                    $sku_id ? $where['b.id'] = ['=', $sku_id] : $where['b.sku'] = ['EQ', $snText];
                    break;
                case 'sku_id'://按sku_id搜索
                    $where['b.id'] = ['EQ', $snText];
                    break;
                case 'spu': //按spu
                    $goodsInfo = Goods::whereIn('spu', $aSnText)->field('id')->select();
                    $aGoodsIds = [];
                    foreach ($goodsInfo as $v) {
                        $aGoodsIds[] = $v->id;
                    }
                    if ($aGoodsIds) {
                        $where['b.goods_id'] = ['in', $aGoodsIds];
                    } else {
                        $where['b.goods_id'] = ['EQ', -1];
                    }
                    break;
                default:
                    break;
            }
        }

        if (isset($condition['goods_id']) && !empty($condition['goods_id'])) {
            $where['b.goods_id'] = ['EQ', $condition['goods_id']];
        }
        $string = '';
        if (isset($condition['is_multi_warehouse_or_warehouse_id']) && !empty($condition['is_multi_warehouse_or_warehouse_id'])) {

            $string = 'a.warehouse_id = ' . $condition['is_multi_warehouse_or_warehouse_id'] . ' or a.is_multi_warehouse = 1';
            $isleft = true;
        }
        if (isset($condition['is_multi_warehouse']) && !empty($condition['is_multi_warehouse'])) {
            $where['a.is_multi_warehouse'] = ['EQ', $condition['is_multi_warehouse']];
            $isleft = true;
        }
        if (isset($condition['category_id']) && !empty($condition['category_id'])) {
            $oData = Db::table('goods')->alias('a')
                ->field('a.category_id,a.weight,b.id,b.goods_id,b.thumb,b.spu_name,b.sku, b.name,b.market_price,b.cost_price,b.sku_attributes,b.status')
                ->join('goods_sku b', 'a.id = b.goods_id', 'right');
        } else {
            $oData = Db::table('goods_sku')->alias('b')->field('b.weight,b.img_is_exists,b.id,b.goods_id,b.thumb,b.spu_name,b.sku, b.name,b.market_price,b.cost_price,b.sku_attributes,b.status');
            if (!$count) {
                $oData = $oData->join('goods a', 'b.goods_id=a.id', 'left');
            } else {
                if ($isleft) {
                    $oData = $oData->join('goods a', 'b.goods_id=a.id', 'left');
                }
            }
        }
        if ($string) {
            $oData->where($string);
        }
        if (param($condition, 'supplier_id')) {
            $where['c.supplier_id'] = $condition['supplier_id'];
            $where['c.is_default'] = 1;
            $oData = $oData->join('supplier_goods_offer c', 'c.sku_id=b.id', 'left');
        }
        return $oData->where($where);
    }


    /**
     * 获取产品信息--以sku为维度
     * @param array $condition
     * @return array
     */
    public static function getGoods($condition)
    {
        $official_rate = Cache::store('currency')->getCurrency()['USD'];
        $page = isset($condition['page']) ? $condition['page'] : 1;
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $count = self::getGoodsWhere($condition, true)->count();
        $goodsList = self::getGoodsWhere($condition)->page($page, $pageSize)->select();
        $new_array = [];
        $sku_id_list = [];
        $keys = [];
        foreach ($goodsList as $k => $v) {
            $statusMap = (new self())->sales_status;
            $temp['status_txt'] = $statusMap[$v['status']] ?? '';
            $temp['status'] = $v['status'];
            $temp['official_rate'] = $official_rate['official_rate'];
            $temp['id'] = $v['id'];
            $temp['sku_id'] = $v['id'];
            $sku_id_list[] = $v['id'];
            $temp['goods_id'] = $v['goods_id'];
            $temp['spu_name'] = $v['spu_name'] . ' ' . $v['name'];
            $temp['thumb'] = $v['thumb'] ? GoodsImage::getThumbPath($v['thumb']) : '';
            $temp['path'] = $v['thumb'];
            $temp['sku'] = $v['sku'];
            $temp['market_price'] = $v['market_price'];
            $temp['cost_price'] = $v['cost_price'];
            $temp['weight'] = $v['weight'];
            $temp['sku_alias'] = GoodsSkuAliasServer::getAliasBySkuId($v['id']);
            //转义
            $attributes = json_decode($v['sku_attributes'], true);
            $tmpAttr = self::getAttrbuteInfoBySkuAttributes($attributes, $v['goods_id']);
            foreach ($tmpAttr as $attrvalue) {
                if ($attrvalue) {
                    $temp[$attrvalue['name']] = $attrvalue['value'];
                    $keys[$attrvalue['name']] = 1;
                }
            }
            array_push($new_array, $temp);
        }
        if ($keys) {
            $keys = array_keys($keys);
        }
        /*是否显示报价*/
        if (isset($condition['is_display_price']) && !empty($condition['is_display_price'])) {
            $supplierOfferService = new SupplierOfferService();
            $getSupplierOfferResult = $supplierOfferService->skuHaveSupplierOffer($sku_id_list);
            if ($new_array) {
                foreach ($new_array as &$row) {
                    if ($getSupplierOfferResult['status'] == 1) {
                        $row['is_have_price'] = isset($getSupplierOfferResult['list'][$row['sku_id']]) ? 1 : 0;
                    } else {
                        $row['is_have_price'] = 0;
                    }
                }
            }
        }
        $result = [
            'data' => $new_array,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'keys' => $keys
        ];
        return $result;
    }

    /**
     * 获取status值
     * @param $value
     * @return mixed|string
     * @autor starzhan <397041849@qq.com>
     */
    public function getStatusAttr($value)
    {
        if ($value) {
            return isset($this->sku_status[$value]) ? $this->sku_status[$value] : '';
        }
        return '';

    }

    /**
     * 根据$attributes返回属性记录
     * @param $attributes array 例: ['attr_1'=>197,'attr_2'=>198]
     * @return array 例 [[name=>'',value=>''],[name=>'',value=>''] ]
     * @autor starzhan <397041849@qq.com>
     */
    public static function getAttrbuteInfoBySkuAttributes($attributes, $goodsId): array
    {
        $aResult = [];
        if (!$attributes || !$goodsId) {
            return $aResult;
        }
        foreach ($attributes as $k => $v) {
            $row = [];
            $aK = explode('_', $k);
            $aCache = Cache::store('attribute')->getAttribute($aK[1]);
            if ($aCache) {
                $row['name'] = isset($aCache['name']) ? $aCache['name'] : '';
                $aValues = isset($aCache['value']) ? $aCache['value'] : [];
                $row['value'] = isset($aValues[$v]) ? $aValues[$v]['value'] : '';
                $row['code'] = isset($aCache['code']) ? $aCache['code'] : '';
                $row['value_id'] = $v;
                $row['id'] = $aK[1];
                if (in_array($aK[1], GoodsImport::$diy_attr)) {
                    $aGoodsAttribute = GoodsAttribute::where(['goods_id' => $goodsId, 'value_id' => $v])->find();
                    if ($aGoodsAttribute) {
                        $row['value'] = $aGoodsAttribute->alias;
                    }
                }
            }
            $aResult[] = $row;
        }
        return $aResult;
    }

    /**
     * 根据sku获取所有属性
     * @param $sku
     * @param $alias 是否需要检索别名别名
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public static function getAttrbuteInfoSku($sku, $alias = false)
    {
        if (!$sku) {
            return [];
        }
        if ($alias) {
            $oAlias = GoodsSkuAlias::where('alias', $sku)->find();
            if ($oAlias) {
                $aSkuInfo = Cache::store('goods')->getSkuInfo($oAlias->sku_id);
                if ($aSkuInfo) {
                    $attributes = json_decode($aSkuInfo['sku_attributes'], true);
                    return self::getAttrbuteInfoBySkuAttributes($attributes, $aSkuInfo['goods_id']);
                }
            }
        }
        $aSkuInfo = GoodsSku::where('sku', $sku)->find();
        if (!$aSkuInfo) {
            return [];
        }
        $attributes = json_decode($aSkuInfo->sku_attributes, true);
        return self::getAttrbuteInfoBySkuAttributes($attributes, $aSkuInfo->goods_id);
    }

    /** 获取产品信息--以spu为维度
     * @param array $condition
     * @return array
     */
    public static function goodsToSpu($condition)
    {
        $goodsModel = new Goods();
        $where['l.lang_id'] = ['=', 2];
        $join = [];
        $page = isset($condition['page']) ? $condition['page'] : 1;
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $where = [];
        if (isset($condition['category_id']) && !empty($condition['category_id'])) {
            $category_list = Cache::store('category')->getCategoryTree();
            if ($category_list[$condition['category_id']]) {
                $child_ids = $category_list[$condition['category_id']]['child_ids'];
                if (count($child_ids) > 1) {
                    $where['a.category_id'] = ['in', $child_ids];
                } else {
                    $where['a.category_id'] = ['=', $condition['category_id']];
                }
            }
        }
        $join[] = ['goods_lang l', 'l.goods_id = a.id', 'left'];
        if (isset($condition['snType']) && isset($condition['snText']) && !empty($condition['snText'])) {
            $snType = trim($condition['snType']);
            $snText = json_decode(trim($condition['snText']));
            switch ($snType) {
                case 'sku':
                    if (is_array($snText)) {
                        $GoodsSku = new ServiceGoodsSku();
                        $skuIds = $GoodsSku->getASkuIdByASkuOrAlias($snText);
                        $skuIds = array_values($skuIds);
                        $where['b.id'] = ['in', $skuIds];
                    } else {
                        $sku_id = self::getSkuIdByAlias(trim($condition['snText']));
                        $sku_id ? $where['b.id'] = ['=', $sku_id] : $where['b.sku'] = ['like', trim($condition['snText']) . '%'];
                    }

                    $join[] = ['goods_sku b', 'b.goods_id = a.id', 'left'];
                    break;
                case 'title':
                    if (is_array($snText)) {
                        $where['a.spu'] = ['in', $snText];
                    } else {
                        $where['a.spu'] = ['like', trim($condition['snText']) . '%'];
                    }

                    break;
                case 'en_name':   //英文名称
                    $where['a.name'] = ['like', '%' . trim($condition['snText']) . '%'];
                    break;
                case 'cn_name':
                    $where['a.name'] = ['like', '%' . trim($condition['snText']) . '%'];
                    break;
                default:
                    break;
            }
        }
        $field = 'a.id,a.thumb,a.spu,a.name,l.title';
        if (isset($condition['field'])) {
            $field .= ',' . $condition['field'];
        }
        $count = $goodsModel->alias('a')->field($field)->join($join)->where($where)->group('a.id')->count();
        $goodsList = $goodsModel->alias('a')->field($field)->join($join)->where($where)->page($page, $pageSize)->group('a.id')->select();
        foreach ($goodsList as &$list) {
            $list['thumb'] = $list['thumb'] ? GoodsImage::getThumbPath($list['thumb']) : '';
        }
        $result = [
            'data' => $goodsList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    /**
     * 产生SPU
     * @param int $category_id 产品分类Id
     * @return string
     */
    public function generateSpu($category_id)
    {
        $category_lists = Cache::store('category')->getCategoryTree();
        if (!isset($category_lists[$category_id])) {
            return '';
        }
        $spu = $category_lists[$category_id]['code'];
        if (!($pid = $category_lists[$category_id]['pid']) || !isset($category_lists[$pid])) {
            return '';
        }
        $spu = $category_lists[$pid]['code'] . $spu;
        $category = Category::where(['id' => $category_id])->field('sequence')->find();
        if (!$category) {
            return '';
        }
        $sequence = $category['sequence'];
        $spu .= substr('0000', 0, 4 - count(str_split(++$sequence))) . $sequence;
        return $spu;
    }

    /**
     *
     * @param $category_id
     * @author starzhan <397041849@qq.com>
     */
    public function createSpu($category_id)
    {
        do {
            $spu = $this->generateSpu($category_id);
            if (!$spu) {
                break;
            }
        } while ($this->isSameSpu($spu, $category_id));
        return $spu;
    }

    /**
     * sku生成
     * @param string $spu 产品SPU
     * @param array $attributes 物品属性
     * @return string
     */
    public function generateSku($spu, $attributes, $number = 0)
    {
        $sku = $spu;
//        if ($attributes) {
//            foreach ($this->sku_rules as $code => $rule) {
//                $str = $rule['default'];
//                foreach ($attributes as $attribute) {
//                    if ($code == $attribute['code']) {
//                        isset($attribute['value_code']) ? $str = $attribute['value_code'] : '';
//                        break;
//                    }
//                }
//                $sku .= $str;
//            }
//        }

        $sku .= substr('000', 0, 2 - strlen(strval(++$number))) . $number;
        return $sku;
    }

    /**
     * 预开发产品往产品表新增数据
     * 之前与商品添加的方法混在一起，现在区分开来因为逻辑不同
     * 最主要要实事求是，不要搞拿来主义
     * @param $params
     * @param $user_id
     * @author starzhan <397041849@qq.com>
     */
    public function pre2Goods($params, $user_id)
    {
        $params['status'] = 0;//开发商品是未启用的状态
        $params['sales_status'] = 3; // 待发布
        $params['type'] = 0;//普通商品
        $params['from'] = 1;//来源 ，商品开发流程
        $params['process_id'] = 1968;//默认为已生成sku状态
        if (empty($params['developer_id'])) {
            throw new Exception('开发员不能为空！');
        }
        //$params['channel_id'] = $this->getChannelIdByDevloperId($params['channel_id']);
        if (empty($params['category_id'])) {
            throw new Exception('分类不能为空！');
        }
        $params['spu'] = $this->createSpu($params['category_id']);
        $goodsValidate = Validate('goods');
        $flag = $goodsValidate->scene('dev')->check($params);
        if ($flag === false) {
            throw new Exception($goodsValidate->getError());
        }
        try {
            $goods = new Goods();
            $goods->allowField(true)->isUpdate(false)->save($params);
            $source_urls[] = empty($params['source_url']) ? [] : $params['source_url'];
            if (!empty($source_urls)) {
                $this->saveSourceUrls($goods->id, $source_urls, $user_id);
            }
            $img_requirement_data = [];
            isset($params['is_photo']) && $img_requirement_data['is_photo'] = intval($params['is_photo']);
            isset($params['photo_remark']) && $params['photo_remark'] && $img_requirement_data['photo_remark'] = $params['photo_remark'];
            isset($params['undisposed_img_url']) && $params['undisposed_img_url'] && $img_requirement_data['undisposed_img_url'] = $params['undisposed_img_url'];
            isset($params['img_requirement']) && $params['img_requirement'] && $img_requirement_data['ps_requirement'] = $params['img_requirement'];
            if ($img_requirement_data) {
                $now = time();
                $img_requirement_data['goods_id'] = $goods->id;
                $img_requirement_data['create_time'] = $now;
                $img_requirement_data['update_time'] = $now;
                $imgModel = new GoodsImgRequirement();
                $imgModel->allowField(true)->save($img_requirement_data);
            }
            //添加描述
            if (!empty($params['description'])) {
                $langData = [
                    'goods_id' => $goods->id,
                    'lang_id' => 1,
                    'description' => $params['description'],
                    'title' => $params['name'],
                    'tags' => $params['tags']
                ];
                $goodsLang = new GoodsLang();
                $goodsLang->allowField(true)->save($langData);
            }
            if (!empty($params['skus'])) {
                $this->preAddSku($goods->id, $params);
            }
            Db::commit();
            return $goods->id;
        } catch (Exception $ex) {
            Db::rollBack();
            throw new Exception('添加失败 ' . $ex->getMessage());
        }
    }

    /**
     * 商品添加
     * @param $params
     * @param $user_id
     * @return mixed
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function add($params, $user_id)
    {

        $params['alias'] = $params['name'];
        $params['type'] = 0;
        $params['status'] = 1;
        $params['sales_status'] = 3; // 待发布      
        $params['spu'] = $this->generateSpu($params['category_id']);
        if (isset($params['platform_sale'])) {
            $lists = json_decode($params['platform_sale'], true);
            $platform_sales = [];
            foreach ($lists as $list) {
                $platform_sales[$list['name']] = $list['value_id'];
            }
            $params['platform_sale'] = json_encode($platform_sales);
        }
        if (isset($params['source_url'])) {
            $params['source_url'] = json_decode($params['source_url'], true);
        }
        if (isset($params['properties'])) {
            $properties = json_decode($params['properties'], true);
            $params['transport_property'] = $this->formatTransportProperty($properties);
            $this->checkTransportProperty($params['transport_property']);
        }
        // 产品参数验证
        $goodsValidate = Validate('goods');
        if (!$goodsValidate->check($params)) {
            throw new JsonErrorException($goodsValidate->getError());
        }
        // 开启事务
        Db::startTrans();
        try {
            $goods = new Goods();
            $goods->allowField(true)->isUpdate(false)->save($params);
            $source_urls[] = empty($params['source_url']) ? [] : $params['source_url'];
            if (!empty($source_urls)) {
                $this->saveSourceUrls($goods->id, $source_urls, $user_id);
            }
            $img_requirement_data = [];
            isset($params['is_photo']) && $img_requirement_data['is_photo'] = intval($params['is_photo']);
            isset($params['photo_remark']) && $params['photo_remark'] && $img_requirement_data['photo_remark'] = $params['photo_remark'];
            isset($params['undisposed_img_url']) && $params['undisposed_img_url'] && $img_requirement_data['undisposed_img_url'] = $params['undisposed_img_url'];
            isset($params['img_requirement']) && $params['img_requirement'] && $img_requirement_data['ps_requirement'] = $params['img_requirement'];
            if ($img_requirement_data) {
                $now = time();
                $img_requirement_data['goods_id'] = $goods->id;
                $img_requirement_data['create_time'] = $now;
                $img_requirement_data['update_time'] = $now;
                $imgModel = new GoodsImgRequirement();
                $imgModel->allowField(true)->save($img_requirement_data);
            }
            // 提交事务
            Db::commit();
            return $goods->id;
        } catch (Exception $ex) {
            Db::rollBack();
            throw new Exception('添加失败 ' . $ex->getMessage());
        }
    }

    /**
     * 产品添加
     * @param array $params
     * @param int $user_id
     * @throws Exception
     * @return int
     */
    public function add2($params, $user_id)
    {
        // 产品标签
        if (isset($params['tags'])) {
            $tags = json_decode($params['tags'], true);
            $params['tags'] = '';
            foreach ($tags as $tag) {
                $params['tags'] .= ($params['tags'] ? ',' : '') . $tag['id'];
            }
        }
        if (!isset($params['description'])) {
            $params['description'] = '';
        }
        $params['type'] = 0;
        $params['status'] = 1;
        $params['sales_status'] = 3; // 待发布
        // 资源链接
        if (isset($params['source_url'])) {
            $source_urls = json_decode($params['source_url'], true);
        }
        // 平台销售状态
        if (isset($params['platform_sale'])) {
            $lists = json_decode($params['platform_sale'], true);
            $platform_sales = [];
            foreach ($lists as $list) {
                $platform_sales[$list['name']] = $list['value_id'];
            }
            $params['platform_sale'] = json_encode($platform_sales);
        } else {
            $params['platform_sale'] = json_encode([]);
        }
        // 产品物流属性
        if (isset($params['properties'])) {
            $properties = json_decode($params['properties'], true);
            $params['transport_property'] = $this->formatTransportProperty($properties);
            $this->checkTransportProperty($params['transport_property']);
        }
        // 产品验证
        $goodsValidate = Validate('goods');
        if (!$goodsValidate->check($params)) {
            throw new Exception($goodsValidate->getError());
        }
        $params['spu'] = $this->generateSpu($params['category_id']);
        // 开启事务
        Db::startTrans();
        try {
            $goods = new Goods();
            $goods->allowField(true)->isUpdate(false)->save($params);
            if (isset($source_urls)) {
                $this->saveSourceUrls($goods->id, $source_urls, $user_id);
            }
            if ($params['spu']) {
                $sequence = intval(substr($params['spu'], -4));
                Category::where(['id' => $params['category_id']])->update(['sequence' => $sequence]);
            }
            $langData = [
                'goods_id' => $goods->id,
                'lang_id' => 1,
                'description' => '',
                'title' => $params['name'],
                'tags' => ''
            ];
            $goodsLang = new GoodsLang();
            $goodsLang->allowField(true)->save($langData);
            // 提交事务
            Db::commit();
            return $goods->id;
        } catch (Exception $ex) {
            Db::rollBack();
            throw new Exception('添加失败 ' . $ex->getMessage());
        }
    }

    /**
     * 临时存放
     * @var array
     */
    private $phashGoodsId = [];

    /**
     * 产品搜索条件
     * @param array $params
     * @return array
     */
    public function getWhere($params)
    {
        $where = ' g.status =1 ';
        $join = [];
        if (isset($params['status']) && !empty($params['status'])) {
            $where .= ' and g.sales_status = ' . $params['status'];
        }
        if (isset($params['phash'])) {
            $aGoodsId = explode(',', $params['phash']);
            $aGoodsId = array_filter($aGoodsId);
            if ($aGoodsId) {
                $this->phashGoodsId = $aGoodsId;
                $where .= " and g.id in (" . implode(',', $aGoodsId) . ") ";
            } else {
                $where .= " and false ";
            }
        }
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'name':
                    // $where .= " and g.name like '%{$params['snText']}%' ";
                    $params['snText'] = mb_convert_encoding($params['snText'], 'UTF-8');
                    $aSnText = explode(' ', $params['snText']);
                    if (count($aSnText) > 1) {
                        foreach ($aSnText as $_k => &$snText) {
                            $snText = '+' . $snText;
                        }
                    }
                    $mbLen = mb_strlen($params['snText'], 'UTF-8');
                    if ($mbLen == 1) {
                        $where .= " and g.name like '%" . $params['snText'] . "%'";
                    } else {
                        $params['snText'] = implode(' ', $aSnText);
                        $where .= " and match(g.name) against ('{$params['snText']}' in boolean mode ) ";
                    }
                    break;
                case 'declareName':
                    $where .= " and g.declare_name like '%" . $params['snText'] . "%'";
                    break;
                case 'declareEnName':
                    $where .= " and g.declare_en_name like '%" . $params['snText'] . "%'";
                    break;
                case 'packingName':
                    $where .= " and g.packing_name like '%" . $params['snText'] . "%'";
                    break;
                case 'packingEnName':
                    $where .= " and g.packing_en_name like '%" . $params['snText'] . "%'";
                    break;
                case 'sku':
                    $arrValue = json_decode($params['snText'], true);
                    if ($arrValue) {
                        if (count($arrValue) == 1) {
                            $arrValue = reset($arrValue);
                            $join[] = [
                                'goods_sku gs', 'gs.goods_id=g.id'
                            ];
                            $sku_id = self::getSkuIdByAlias($arrValue);
                            $where .= $sku_id ? " and gs.id =" . $sku_id : " and gs.sku like '" . $arrValue . "%'";
                        } else {
                            $join[] = [
                                'goods_sku gs', 'gs.goods_id=g.id'
                            ];
                            $GoodsSku = new ServiceGoodsSku();
                            $skuIds = $GoodsSku->getASkuIdByASkuOrAlias($arrValue);
                            $skuIds = array_values($skuIds);
                            if ($skuIds) {
                                $where .= ' and gs.id in (' . implode(',', $skuIds) . ') ';
                            } else {
                                $where .= ' and false ';
                            }
                        }
                    }

                    break;
                case 'spu':
                    $arrValue = json_decode($params['snText'], true);
                    if ($arrValue) {
                        if (count($arrValue) == 1) {
                            $arrValue = reset($arrValue);
                            $where .= " and g.spu like '" . $arrValue . "%'";
                        } else {
                            foreach ($arrValue as $k => $v) {
                                $v = "'" . $v . "'";
                                $arrValue[$k] = $v;
                            }
                            $where .= " and g.spu in (" . implode(',', $arrValue) . ") ";
                        }
                    }
                    break;
                case 'alias':
                    $where .= " and g.alias like '" . $params['snText'] . "%'";
                    break;
                default:
                    break;
            }
        }

        if (isset($params['dateType']) && $params['dateType'] == 'sellTime') {
            if (isset($params['date_start']) && !empty($params['date_start'])) {
                $start = strtotime($params['date_start']);
                $start ? $where .= ' and g.publish_time >= ' . $start : '';
            }

            if (isset($params['date_end']) && !empty($params['date_end'])) {
                $end = strtotime($params['date_end'] . ' 23:59:59');
                $end ? $where .= ' and g.publish_time < ' . $end : '';
            }
        }

        if (isset($params['dateType']) && $params['dateType'] == 'stopTime') {
            if (isset($params['date_start']) && !empty($params['date_start'])) {
                $start = strtotime($params['date_start']);
                $start ? $where .= ' and g.stop_selling_time > ' . $start : '';
            }

            if (isset($params['date_end']) && !empty($params['date_end'])) {
                $end = strtotime($params['date_end'] . ' 23:59:59');
                $end ? $where .= ' and g.stop_selling_time < ' . $end : '';
            }
        }
        if (isset($params['category_id']) && $params['category_id']) {
            $params['category_id'] = intval($params['category_id']);
            $aCategorys = Cache::store('category')->getCategoryTree();
            $aCategory = isset($aCategorys[$params['category_id']]) ? $aCategorys[$params['category_id']] : [];
            if ($aCategory) {
                $searchIds = [$params['category_id']];
                if ($aCategory['child_ids']) {
                    $searchIds = array_merge($searchIds, $aCategory['child_ids']);
                }
                $where .= ' and g.category_id in (' . implode(',', $searchIds) . ') ';
            }
        }
        if (isset($params['without_img']) && $params['without_img']) {
            $where .= " and g.thumb = '' ";
        }

        if (isset($params['supplier_id']) && $params['supplier_id']) {
            $params['supplier_id'] = trim($params['supplier_id']);
            $where .= " and g.supplier_id = '{$params['supplier_id']}' ";
        }

        if (isset($params['developer_id']) && $params['developer_id']) {
            $params['developer_id'] = trim($params['developer_id']);
            $where .= " and g.developer_id = '{$params['developer_id']}' ";
        }
        if (isset($params['purchaser_id']) && $params['purchaser_id']) {
            $params['purchaser_id'] = trim($params['purchaser_id']);
            $where .= " and g.purchaser_id = '{$params['purchaser_id']}' ";
        }

        if (isset($params['warehouse_id']) && $params['warehouse_id']) {
            $params['warehouse_id'] = trim($params['warehouse_id']);
            $where .= " and g.warehouse_id = '{$params['warehouse_id']}' ";
        }

        $wheres['where'] = $where;
        $wheres['join'] = $join;
        return $wheres;
    }

    /**
     * 查询产品总数
     * @param array $wheres
     * @return int
     */
    public function getCount($wheres)
    {
        if (!empty($wheres['join'])) {
            $count = Goods::alias('g')->join($wheres['join'])->where($wheres['where'])->count('distinct(g.id)');
        } else {
            $count = Goods::alias('g')->where($wheres['where'])->count();
        }
        return $count;
    }

    /**
     * 查询产品列表
     * @param array $wheres
     * @param string $fields
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getList($wheres, $fields = '*', $page = 1, $pageSize = 20, $domain = '')
    {
        $goodsModel = new Goods();
        $order = ['g.id' => 'desc'];
        if ($this->phashGoodsId) {
            //如果是查图片，要按匹配度排序
            $order = "find_in_set(g.id,'" . implode(',', $this->phashGoodsId) . "')";
        }
        if (isset($wheres['json']) || empty($wheres['join'])) {
            $goods_data = $goodsModel->alias('g')->order($order)->field($fields)->where($wheres['where'])->page($page, $pageSize)->select();
        } else {
            $goods_data = $goodsModel->alias('g')->order($order)->join($wheres['join'])->field($fields)->where($wheres['where'])->page($page, $pageSize)->select();
        }
        $new_array = [];
        foreach ($goods_data as $k => $v) {
            $new_array[$k] = $v;
            if (isset($v['thumb'])) {
                $new_array[$k]['thumb'] = empty($v['thumb']) ? '' : GoodsImage::getThumbPath($v['thumb'], 0, 0);
            }
            //isset($new_array[$k]['category_id']) ? $new_array[$k]['category'] = $this->mapCategory($v['category_id']) : '';
            $new_array[$k]['category'] = $v->category;
            isset($new_array[$k]['sales_status']) ? $new_array[$k]['status'] = $v['sales_status'] : '';
            isset($new_array[$k]['transport_property']) ? $new_array[$k]['transport_property'] = $this->getProTransPropertiesTxt($new_array[$k]['transport_property']) : '';
            isset($new_array[$k]['type']) ? $new_array[$k]['sales'] = $goodsModel->getSalesInfo($v['type']) : '';
            isset($new_array[$k]['publish_time']) ? ($new_array[$k]['publish_time'] = $v['publish_time'] ? date('Y-m-d', $new_array[$k]['publish_time']) : '') : '';
            isset($new_array[$k]['stop_selling_time']) ? ($new_array[$k]['stop_selling_time'] = $v['stop_selling_time'] ? date('Y-m-d', $new_array[$k]['stop_selling_time']) : '') : '';
            $new_array[$k]['purchaser'] = $v->purchaser;
            $new_array[$k]['developer'] = $v->developer;
            $new_array[$k]['channel_name'] = $v->channel_name;
            unset($new_array[$k]['sales_status']);
            unset($new_array[$k]['type']);
        }
        return $new_array;
    }

    /**
     * 获取产品sku列表
     * @param int $goods_id
     * @return array
     */
    public function getGoodsSkus($goods_id)
    {
        $sku_data = GoodsSku::field("length,width,height,id,goods_id,sku,retail_price,market_price,cost_price,weight,sku_attributes,status,thumb")
            ->where(['goods_id' => $goods_id])
            ->order('sku asc')
            ->select();
        $new_sku = [];
        $goodsInfo = Cache::store('goods')->getGoodsInfo($goods_id);
        $PurchaseProposal = new PurchaseProposal();
        foreach ($sku_data as $key => $value) {
            $new_sku[$key] = $value;
//            if ($value['weight'] == 0) {
//                $new_sku[$key]['weight'] = $goodsInfo['weight'];
//            }
            $new_sku[$key]['status'] = isset($this->sku_status[$value['status']]) ? $this->sku_status[$value['status']] : '';
            $new_sku[$key]['thumb'] = empty($value['thumb']) ? '' : GoodsImage::getThumbPath($value['thumb']);
            $attr = json_decode($value['sku_attributes'], true);
            $attrs = self::getAttrbuteInfoBySkuAttributes($attr, $goods_id);
            $new_sku[$key]['attributes'] = '';
            $tmp = [];
            foreach ($attrs as $val) {
                $tmp[] = $val['value'];
            }
            $new_sku[$key]['goods_id'] = $goods_id;
            $new_sku[$key]['attributes'] = implode(' ；', $tmp);
            $new_sku[$key]['length'] = $new_sku[$key]['length'] / 10;
            $new_sku[$key]['width'] = $new_sku[$key]['width'] / 10;
            $new_sku[$key]['height'] = $new_sku[$key]['height'] / 10;
            $new_sku[$key]['alias'] = GoodsSkuAliasServer::getAliasBySkuId($value['id']);
            $new_sku[$key]['daily_mean_sales'] = $PurchaseProposal->getDailySale($value['id'], $goodsInfo['warehouse_id']);
            $new_sku[$key]['warehouse_id'] = $goodsInfo['warehouse_id'];
        }
        return $new_sku;
    }

    /**
     * 匹配多级分类名称
     * @param int $category_id
     * @return string
     */
    public function mapCategory($category_id)
    {
        static $result = [];
        if (!isset($result[$category_id])) {
            $category_list = Cache::store('category')->getCategoryTree();
            $name_path = "";
            $loop_category_id = $category_id;
            while ($loop_category_id) {
                if (!isset($category_list[$loop_category_id])) {
                    break;
                }
                $name_path = $name_path ? $category_list[$loop_category_id]['title'] . '>' . $name_path : $category_list[$loop_category_id]['title'];
                $parent = $category_list[$loop_category_id]['parents'];
                $loop_category_id = empty($parent) ? 0 : $parent[0];
            }
            $result[$category_id] = $name_path;
        }

        return $result[$category_id];
    }

    /**
     * 获取产品基础信息
     * @param int $goods_id 产品id
     * @return array
     */
    public function getBaseInfo($goods_id)
    {
        $fields = 'id,category_id,name,spu,declare_name,declare_en_name,retail_price,cost_price,weight,net_weight,width,height,depth,volume_weight,packing_id,unit_id,thumb,'
            . 'alias,hs_code,process_id,is_packing,brand_id,tort_id,tags,warehouse_id,is_multi_warehouse,pre_sale,platform_sale,transport_property,developer_id,same_weight,create_time,same_weight,description,platform';
        $result = Goods::where(['id' => $goods_id])->field($fields)->find();
        $arr = [];
        if ($result) {
            $result['category'] = $this->mapCategory($result['category_id']);
            $result['package'] = $result['packing_id'] ? $this->getPackageById($result['packing_id']) : '';
            $result['unit'] = $result['unit_id'] ? $this->getUnitById($result['unit_id']) : '';
            $result['tort'] = $result['tort_id'] ? $this->getTortById($result['tort_id']) : '';
            $result['brand'] = $result['brand_id'] ? $this->getBrandById($result['brand_id']) : '';
            $result['tags'] = $this->getEnglishTags($goods_id);
            $result['warehouse'] = $result['warehouse_id'] ? $this->getWarehouseById($result['warehouse_id']) : '';
//            $result['platform_sale'] = $this->resolvePlatformSale($result['platform_sale']);
            $result['platform_sale'] = $this->getPlatformSale($result['platform']);
            $result['retail_price'] = round($result['retail_price'], 2);
            $result['cost_price'] = round($result['cost_price'], 2);
            $result['source_url'] = $this->getSourceUrls($goods_id);
            $result['properties'] = $this->getProTransProperties($result['transport_property']);
            $result['developer'] = $this->getUserNameById($result->getData('developer_id'));
            $result['create_time'] = $result['create_time'] ? date('Y-m-d H:i:s', $result['create_time']) : '';
            $result['width'] = $result->width / 10;
            $result['height'] = $result['height'] / 10;
            $result['depth'] = $result['depth'] / 10;
            $arr = $result->toArray();
            $arr['developer_id'] = $result->getData('developer_id');
        }
        return $arr;
    }

    public function getEnglishTags($goods_id)
    {
        $result = [];
        $aLang = GoodsLang::where('goods_id', $goods_id)
            ->where('lang_id', 2)
            ->find();
        if ($aLang) {
            $result = $aLang['tags'] ? explode('\n', $aLang['tags']) : [];
        }
        return $result;
    }

    /**
     * 更新产品基础信息
     * @param int $goods_id
     * @param array $data
     * @param int $user_id
     * @throws Exception
     * @return int
     */
    public function updateBaseInfo($goods_id, $data, $user_id)
    {
        $fields = '
                category_id,
                spu,
                name,
                packing_en_name, 
                packing_name,
                declare_name,
                declare_en_name, 
                thumb,sort, alias,
                unit_id, 
                weight,
                net_weight,
                width, 
                height,
                depth,
                volume_weight,
                packing_id,
                cost_price, 
                retail_price, 
                hs_code, 
                is_packing,
                tags,
                brand_id,
                tort_id,
                warehouse_id,
                is_multi_warehouse,
                platform,
                transport_property,
                developer_id,
                same_weight,
                pre_sale';
        $goods_info = Goods::where(['id' => $goods_id])->field($fields)->find()->getData();
        if (!$goods_info) {
            throw new Exception('产品不存在', 101);
        }
        if (isset($data['platform_sale'])) {
            $lists = json_decode($data['platform_sale'], true);
            $data['platform'] = $this->getPlatform($lists);
        }
        if (isset($data['source_url'])) {
            $data['source_url'] = json_decode($data['source_url'], true);
            $original_url = $this->getSourceUrls($goods_id);
            if (!array_diff($data['source_url'], $original_url) && !array_diff($original_url, $data['source_url'])) {
                unset($data['source_url']);
            }
        }
        if (isset($data['properties'])) {
            $properties = json_decode($data['properties'], true);
            $data['transport_property'] = $this->formatTransportProperty($properties);
            $this->checkTransportProperty($data['transport_property']);
        }
        isset($data['width']) && $data['width'] = $data['width'] * 10;
        isset($data['depth']) && $data['depth'] = $data['depth'] * 10;
        isset($data['height']) && $data['height'] = $data['height'] * 10;
        $diff = array_intersect(array_keys($goods_info), array_keys($data));
        $update = [];
        foreach ($diff as $key) {
            if ($goods_info[$key] != $data[$key]) {
                $update[$key] = $data[$key];
            }
        }
        if (empty($update) && !isset($data['source_url'])) {
            throw new Exception('没有更新的字段', 102);
        }

        // 开启事务
        Db::startTrans();
        try {
            $GoodsLog = new GoodsLog();
            $new = $old = [];
            foreach ($update as $k => $v) {
                $new[$k] = $v;
                $old[$k] = $goods_info[$k];
            }
            $update['update_time'] = time();
            $Goods = new Goods();
            unset($update['spu']);
            $f = $Goods->allowField(true)->isUpdate(true)->save($update, ['id' => $goods_id]);
            if ($f) {
                if (isset($update['name'])) {
                    $GoodsSku = new GoodsSku();
                    $GoodsSkuService = new ServiceGoodsSku();
                    $aGoodsSku = $GoodsSku->where('goods_id', $goods_id)->select();
                    foreach ($aGoodsSku as $aSkuInfo) {
                        $GoodsSkuService->afterUpdate($aSkuInfo, ['spu_name' => $update['name']]);
                        $aSkuInfo->spu_name = $update['name'];
                        $f = $aSkuInfo->save();
                        Cache::store('goods')->delSkuInfo($aSkuInfo->id);
                    }
                }
            }
            $GoodsLog->mdfSpu($goods_info['spu'], $old, $new)->save($user_id, $goods_id);
            if (isset($data['source_url'])) {
                $this->saveSourceUrls($goods_id, $data['source_url'], $user_id);
            }
            $this->putPushList($goods_id);
            // 提交事务
            Db::commit();
            Cache::handler()->hdel('cache:Goods', $goods_id);
        } catch (Exception $ex) {
            Db::rollBack();
            throw  $ex;
        }
        return 0;
    }

    public function getPlatform($lists)
    {
        $result = 0;
        foreach ($lists as $list) {
            if ($list['value_id'] == 1) {
                $value = $this->getPlatformValueByChannelName($list['name']);
                if ($value > 0) {
                    $result += $value;
                }
            }
        }
        return $result;
    }

    /**
     * @title 根据平台id，来获取这个商品在这个平台上是否能上架
     * @param $goods_id
     * @param $channel_id
     * @author starzhan <397041849@qq.com>
     */
    public function getPlatformForChannel($goods_id, $channel_id)
    {
        $goodsInfo = $this->getGoodsInfo($goods_id);
        if (!$goodsInfo) {
            throw new Exception("商品[{$goods_id}]不存在的");
        }
        $value = $this->getPlatformValueByChannelId($channel_id);
        $platform = $goodsInfo['platform'];
        if (($platform & $value) == $value) {
            return 1;
        }
        return 0;
    }

    /**
     * 获取仓库名称
     * @param int $id
     * @return string
     */
    public function getWarehouseById($id)
    {
        $info = Cache::store('warehouse')->getWarehouse($id);
        return isset($info['name']) ? $info['name'] : '';
    }

    /**
     * 解析平台销售状态
     * @param string $platform_sale
     * @return array
     */
    public function resolvePlatformSale($platform_sale)
    {
        $platform_sales = json_decode($platform_sale, true);
        $lists = Channel::where(['status' => 0])->field('id,name,title')->select();
        foreach ($lists as &$list) {
            $list['value_id'] = isset($platform_sales[$list['name']]) ? $platform_sales[$list['name']] : 1;
            $list['value'] = isset($this->platform_sale_status[$list['value_id']]) ? $this->platform_sale_status[$list['value_id']] : '';
        }
        return $lists;
    }

    public function getPlatformSale($platform)
    {
        $data = [];
        $tmpList = Channel::where(['status' => 0])->field('id,name,title')->select();
        foreach ($tmpList as $v) {
            $channelName = $v['name'];
            $value = $this->getPlatformValueByChannelName($channelName);
            if (($platform & $value) == $value) {
                $data[$channelName] = 1;
            } else {
                $data[$channelName] = 0;
            }
        }
        //$tmpList = Channel::where(['status' => 0])->field('id,name,title')->select();
        $lists = [];
        foreach ($tmpList as $l) {
            $lists[$l['name']] = $l;
        }
        $result = [];
        foreach ($data as $k => $v) {
            if (!isset($lists[$k])) {
                continue;
            }
            $list = $lists[$k];
            $row = [];
            $row['id'] = $list['id'];
            $row['name'] = $list['name'];
            $row['title'] = $list['title'];
            $row['value_id'] = $v;
            $row['value'] = $this->platform_sale_status[$v];
            $result[] = $row;
        }
        return $result;
    }

    public function getPlatformSaleJson($platform)
    {
        $data = [];
        foreach ($this->show_channel as $channelName) {
            $value = $this->getPlatformValueByChannelName($channelName);
            if (($platform & $value) == $value) {
                $data[$channelName] = 1;
            } else {
                $data[$channelName] = 0;
            }
        }
        return $data;
    }

    /**
     * 获取平台状态列表
     * @return array
     */
    public function getPlatformSaleStatus()
    {
        $lists = [];
        foreach ($this->platform_sale_status as $k => $list) {
            $lists[] = [
                'id' => $k,
                'name' => $list
            ];
        }

        return $lists;
    }

    /**
     * 获取产品参考地址
     * @param int $goods_id
     * @return array
     */
    private function getSourceUrls($goods_id)
    {
        $results = [];
        $lists = GoodsSourceUrl::where(['goods_id' => $goods_id])->select();
        foreach ($lists as $list) {
            $results[] = $list['source_url'];
        }
        return $results;
    }

    /**
     * 保存产品参考地址
     * @param int $goods_id
     * @param array $urls
     * @param int $user_id
     */
    public function saveSourceUrls($goods_id, $urls, $user_id)
    {
        $goodsSourceUrl = new GoodsSourceUrl();
        $goodsSourceUrl->where(['goods_id' => $goods_id])->delete();
        $results = [];
        foreach ($urls as $source_url) {
            $results[] = [
                'goods_id' => $goods_id,
                'source_url' => $source_url,
                'create_time' => time(),
                'create_id' => $user_id
            ];
        }
        $results ? $goodsSourceUrl->allowField(true)->saveAll($results) : '';
    }

    /**
     * 获取产品规格参数
     * @param int $goods_id 产品ID
     * @param int $filter_sku 若为0为产品属性，1为规格参数，2为全部属性
     * @param int $edit 是否为编辑属性
     * @return array
     */
    public function getAttributeInfo($goods_id, $filter_sku = 2, $edit = 1)
    {
        $result = [];
        do {
            $goods = Goods::where(['id' => $goods_id])->field('category_id')->find();
            if (empty($goods) || !$goods->category_id) {
                break;
            }
            // 获取产品属性
            $goodsAttributes = GoodsAttribute::where(['goods_id' => $goods_id])->select();
            $goods_attributes = [];
            foreach ($goodsAttributes as $attribute) {
                $goods_attributes[$attribute['attribute_id']]['attribute_value'][] = $attribute->toArray();
            }
            unset($goodsAttributes);
            $result = $this->matchCateAttribute($goods->category_id, $filter_sku, $edit, $goods_attributes);
        } while (false);
        return $result;
    }

    /**
     * 获取分类属性及值
     * type 0 单选 1 多选 2 输入文本
     * @param int $category_id 分类id
     * @param int $filter_sku 过滤sku, 当值为2 全部属性, 1 选择参与sku规则制定属性, 0 不选择参与sku规则制定属性
     * @return array
     */
    public function getCategoryAttribute($category_id, $filter_sku = 2)
    {
        $cate_attributes = Cache::store('category')->getAttribute($category_id);
        $result = [];
        if (empty($cate_attributes)) {
            return $result;
        }
        foreach ($cate_attributes['group'] as $group) {
            foreach ($group['attributes'] as &$attribute) {
                // 属性详情解析
                $attribute_info = Cache::store('attribute')->getAttribute($attribute['attribute_id']);
                if (2 != $filter_sku && $filter_sku != $attribute['sku']) {
                    continue;
                }
                $attribute['is_alias'] = $attribute_info['is_alias'];
                $attribute['suffix'] = $attribute_info['suffix'];
                $attribute['warning'] = $attribute_info['warning'];
                $attribute['match'] = $attribute_info['match'];
                $attribute['type'] = $attribute_info['type'];
                $attribute['name'] = $attribute_info['name'];
                $attribute['code'] = $attribute_info['code'];
                $attribute['group_id'] = $group['group_id'];
                $attribute['group_sort'] = $group['sort'];
                $attribute['group_name'] = $group['name'];
                if (2 == $attribute['type']) {
                    $attribute['attribute_value'] = '';
                } elseif (empty($attribute['attribute_value'])) {
                    foreach ($attribute_info['value'] as $v) {
                        $v['selected'] = false;
                        $attribute['attribute_value'][$v['id']] = $v;
                    }
                } else {
                    $values = [];
                    foreach ($attribute['attribute_value'] as $attribute_value_id) {
                        if (isset($attribute_info['value'][$attribute_value_id])) {
                            $values[$attribute_value_id] = $attribute_info['value'][$attribute_value_id];
                            $values[$attribute_value_id]['selected'] = false;
                        }
                    }
                    $attribute['attribute_value'] = $values;
                }
                $result[] = $attribute;
            }
        }
        return $result;
    }

    /**
     * @title 推送到push-listing队列
     * @param $goods_id
     * @param $spu
     * @param $status
     * @param $platform
     * @param $sales_status
     * @param $category_id
     * @author starzhan <397041849@qq.com>
     */
    public function putPushList($goods_id)
    {
        $queue = new CommonQueuer(GoodsPublishMapQueue::class);
        $goodsInfo = Goods::where('id', $goods_id)->find();
        $row = [];
        $row['id'] = $goods_id;
        $row['spu'] = $goodsInfo['spu'];
        $row['status'] = $goodsInfo['status'];
        $platform_sale = $this->getPlatformSaleJson($goodsInfo['platform']);
        $platform_sale = array_map(function ($val) {
            if (!$val) {
                return 0;
            } else {
                return $val;
            }

        }, $platform_sale);
        $platform_sale['joom'] = 1;
        $row['platform_sale'] = json_encode($platform_sale);
        $row['sales_status'] = $goodsInfo['sales_status'];
        $row['category_id'] = $goodsInfo['category_id'];
        $queue->push($row);
    }

    /**
     * 匹配分类属性
     * @param int $category_id
     * @param int $filter_sku
     * @param int $edit
     * @param array $goods_attributes
     * @return array
     */
    public function matchCateAttribute($category_id, $filter_sku, $edit, $goods_attributes)
    {
        // 获取分类属性
        $category_attributes = $this->getCategoryAttribute($category_id, $filter_sku);
        // 分类属性是否为已选
        $result = [];
        foreach ($category_attributes as $k => $attribute) {
            $alias = '';
            if ($attribute['type'] == 2) {
                $value = $this->getAttributeValueByIdAndValueId($goods_attributes, $attribute['attribute_id'], 0, $alias);
                //  $value === 0 说明不存在此属性
                if ($value === 0 && $edit == 0) {
                    unset($category_attributes[$k]);
                } elseif ($value === 0 && $edit == 1) {
                    $attribute['attribute_value'] = '';
                    $attribute['enabled'] = false;
                } else {
                    $attribute['attribute_value'] = $value;
                    $attribute['enabled'] = true;
                }
                $result[] = $attribute;
                continue;
            }

            $attribute['enabled'] = true;
            foreach ($attribute['attribute_value'] as $ke => &$attribute_value) {
                if ($this->getAttributeValueByIdAndValueId($goods_attributes, $attribute['attribute_id'], $attribute_value['id'], $alias) !== 0) {
                    $attribute_value['selected'] = true;
                    $alias ? $attribute_value['value'] = $alias : '';
                } else {
                    if ($edit == 1) {  // && $attribute['is_alias'] == 0
                        $attribute_value['selected'] = false;
                    } else {
                        unset($attribute['attribute_value'][$ke]);
                    }
                }
            }

            if ($edit == 1 && !isset($goods_attributes[$attribute['attribute_id']])) {
                $attribute['enabled'] = false;
            } else if ($edit == 0 && !isset($goods_attributes[$attribute['attribute_id']])) {
                continue;
            }
            $attribute['attribute_value'] = array_values($attribute['attribute_value']);
            $result[] = $attribute;
        }
        unset($category_attributes);
        if (0 == $filter_sku) {
            $groups = [];
            foreach ($result as $list) {
                if (in_array($list['group_id'], $groups)) {
                    $groups[$list['group_id']]['attributes'][] = $list;
                } else {
                    $groups[$list['group_id']]['group_name'] = $list['group_name'];
                    $groups[$list['group_id']]['group_sort'] = $list['group_sort'];
                    $groups[$list['group_id']]['group_id'] = $list['group_id'];
                    $groups[$list['group_id']]['attributes'][] = $list;
                }
            }
            $result = array_values($groups);
        }

        return $result;
    }

    /**
     * 更新产品规格参数
     * @param int $goods_id
     * @param array $attributes
     * @param int $filter_sku
     * @return int
     * @throws Exception
     */
    public function modifyAttribute($goods_id, $attributes, $filter_sku = 2)
    {
        $goods_info = Goods::where(['id' => $goods_id])->field('category_id')->find();
        if (empty($goods_info) || empty($goods_info['category_id'])) {
            throw new Exception('产品不存在或产品分类不存在', 101);
        }

        //启动事务
        Db::startTrans();
        try {
            $results = [];
            $this->checkGoodsAttributes($filter_sku, $goods_id, $attributes, $results, $goods_info['category_id']);
            foreach ($attributes as $attribute) {
                $goodsAttrModel = new GoodsAttribute();
                if ($attribute['type'] == 2) {
                    if ($goodsAttrModel->check(['attribute_id' => $attribute['attribute_id'], 'goods_id' => $goods_id])) {
                        $goodsAttrModel->allowField(true)->isUpdate(true)->where(['attribute_id' => $attribute['attribute_id'], 'goods_id' => $goods_id])->update(['data' => $attribute['attribute_value']]);
                    } else {
                        $goodsAttrModel->allowField(true)->save(['attribute_id' => $attribute['attribute_id'], 'goods_id' => $goods_id, 'value_id' => 0, 'data' => $attribute['attribute_value']]);
                    }
                } else {
                    $goodsAttrModel->where(['attribute_id' => $attribute['attribute_id'], 'goods_id' => $goods_id])->delete();
                    $infos = [];
                    $alias = $attribute['is_alias'];
                    foreach ($attribute['attribute_value'] as $value) {
                        $is_qc = isset($results[$attribute['attribute_id']]) ? $results[$attribute['attribute_id']]['is_qc'] : 0;
                        $infos[] = ['attribute_id' => $attribute['attribute_id'], 'goods_id' => $goods_id, 'value_id' => $value['id'], 'data' => '', 'is_qc' => $is_qc, 'alias' => $alias ? $value['value'] : ''];
                    }
                    $infos ? $goodsAttrModel->allowField(true)->saveAll($infos) : '';
                }
                unset($results[$attribute['attribute_id']]);
            }
            // 产品属性更新时删除操作
            if (0 == $filter_sku && !empty($results)) {
                foreach ($results as $info) {
                    $goodsAttrModel = new GoodsAttribute();
                    $goodsAttrModel->where(['attribute_id' => $info['attribute_id'], 'goods_id' => $goods_id])->delete();
                }
            }
            Db::commit();
            return ['message' => '修改成功'];
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception('修改失败' . $e->getMessage(), 103);
        }

        return 0;
    }


    /**
     * 获取属性值根据值
     * @param array $goods_attributes 产品属性数组
     * @param int $attribute_id 属性Id
     * @param int $attribute_value_id 属性值Id
     * @return int|string
     */
    public function getAttributeValueByIdAndValueId(&$goods_attributes, $attribute_id, $attribute_value_id, &$alias)
    {
        if (!isset($goods_attributes[$attribute_id])) {
            return 0;
        }
        foreach ($goods_attributes[$attribute_id]['attribute_value'] as $value) {
            if ($attribute_value_id == $value['value_id']) {
                $alias = $value['alias'];
                return $attribute_value_id ?: $value['data'];
            }
        }
        return 0;
    }

    /**
     * 格式化属性数组
     * @param array $attributes
     * @return array
     */
    public function formatAttribute($attributes)
    {
        $lists = [];
        $attributes = json_decode($attributes, true);
        foreach ($attributes as $attribute) {
            $list['attribute_id'] = $attribute['attribute_id'];
            $list['type'] = $attribute['type'];
            isset($attribute['required']) ? $list['required'] = $attribute['required'] : '';
            isset($attribute['sku']) ? $list['sku'] = $attribute['sku'] : '';
            isset($attribute['gallery']) ? $list['gallery'] = $attribute['gallery'] : '';
            if (2 == $list['type']) {
                $list['attribute_value'] = $attribute['attribute_value'];
            } else {
                $list['attribute_value'] = [];
                foreach ($attribute['attribute_value'] as $value) {
                    $list['attribute_value'][] = $value;
                }
            }
            $lists[] = $list;
            $list = null;
        }
        return $lists;
    }

    /**
     * 检测产品属性能否删除
     * @param int $filter_sku
     * @param int $goods_id
     * @param array $attributes
     * @throws Exception
     */
    public function checkGoodsAttributes($filter_sku, $goods_id, &$attributes, &$results, $category_id)
    {
        $message = '';
        // 分类属性
        $categoryAttributes = $this->getCategoryAttribute($category_id, $filter_sku);
        $base_attributes = [];
        foreach ($categoryAttributes as $val) {
            $base_attributes[$val['attribute_id']] = $val;
        }
        $base_info = $base_attributes;
        // 检查属性及属性值存在
        foreach ($attributes as &$attribute) {
            $attribute_id = $attribute['attribute_id'];
            if (!isset($base_attributes[$attribute_id])) {
                $message .= ' 属性Id为' . $attribute_id . '不存在分类中 ';
                break;
            }
            if ($attribute['type'] != $base_attributes[$attribute_id]['type']) {
                $message .= ' 属性' . $base_attributes[$attribute_id]['name'] . '类型不对 ';
                break;
            }
            if (empty($attribute['attribute_value']) && $base_attributes[$attribute_id]['required']) {
                $message .= ' 属性' . $base_attributes[$attribute_id]['name'] . '值不能为空 ';
            }
            // 属性为单选且不参与sku计算 进行单选
            if ($attribute['type'] == 0 && $base_attributes[$attribute_id]['sku'] == 0 && count($attribute['attribute_value']) > 1) {
                $message .= ' ' . $base_attributes[$attribute_id]['name'] . '为单选项不能多选';
            }

            // 是否允许自定义别名
            $attribute['is_alias'] = $base_attributes[$attribute_id]['is_alias'];
            if ($attribute['type'] != 2) {
                foreach ($attribute['attribute_value'] as $value) {
                    !isset($base_attributes[$attribute_id]['attribute_value'][$value['id']]) ? ($message .= $base_attributes[$attribute_id]['name'] . '属性值Id' . $value['id'] . '不存在 ') : '';
                }
            }

            if ($message) {
                break;
            }
            unset($base_attributes[$attribute['attribute_id']]);
            unset($attribute);
        }

        // 检查分类中属性设置为必选项出现
        if (!$message && !empty($base_attributes)) {
            foreach ($base_attributes as $attribute) {
                $attribute['required'] == 1 ? $message .= ' 属性为' . $attribute['name'] . '是必选项 ' : '';
                unset($attribute);
            }
        }

        if ($message) {
            throw new Exception($message, 102);
        }
        // 获取产品属性值
        $lists = GoodsAttribute::where(['goods_id' => $goods_id])->select();
        foreach ($lists as $list) {
            $attribute_id = $list['attribute_id'];
            if (!in_array($attribute_id, array_keys($base_info))) {
                continue;
            }
            if (isset($results[$attribute_id])) {
                $results[$attribute_id]['attribute_value'][$list['value_id']] = $list['alias'];
            } else {
                $results[$attribute_id] = [
                    'attribute_id' => $attribute_id,
                    'is_qc' => $list['is_qc']
                ];
                $list['value_id'] != 0 ? ($results[$attribute_id]['attribute_value'][$list['value_id']] = $list['alias']) : '';
            }
        }
        // 检测规格参数能否删除
        if (1 == $filter_sku) {
            foreach ($attributes as &$attribute) {
                $attribute_id = $attribute['attribute_id'];
                $attribute_info = Cache::store('attribute')->getAttribute($attribute_id);
                $attribute['is_alias'] = $attribute_info['is_alias'];
                if (!isset($results[$attribute_id])) {
                    continue;
                }
                // 过滤产品已存在的属性
                foreach ($attribute['attribute_value'] as $val) {
                    if (isset($results[$attribute_id]['attribute_value'][$val['id']])) {
                        unset($results[$attribute_id]['attribute_value'][$val['id']]);
                    }
                }
                // 检测规格属性值能否删除
                foreach ($results[$attribute_id]['attribute_value'] as $value_id => $value) {
                    $where = ' `goods_id` = ' . $goods_id . '  AND sku_attributes->"$.attr_' . $attribute_id . '" = ' . $value_id;
                    $count = GoodsSku::where($where)->count();
                    if ($count) {
                        $name = $attribute_info['is_alias'] ? $value : $attribute_info['value'][$value_id]['value'];
                        $message .= $attribute_info['name'] . '的值' . $name . '正在使用中';
                    }
                }
            }
        }

        if ($message) {
            throw new Exception($message);
        }
    }

    /**
     * 分配属性值Id
     * @param array 属性值
     * @param array 已用属性值
     * @param array 属性所有的值
     * @return boolean
     */
    private function allocationValueId(&$attribute_value, $used_value, $value_ids)
    {
        $availableValueIds = array_diff($value_ids, $used_value);
        foreach ($attribute_value as $key => &$val) {
            if ($val['id']) {
                continue;
            }
            if (!empty($availableValueIds)) {
                $val['id'] = array_shift($availableValueIds);
            } else { // 属性值不够移除
                unset($attribute_value[$key]);
            }
        }

        return true;
    }

    /**
     * 获取产品关联供应商信息
     * @param int $goods_id
     * @return array
     */
    public function getSupplierInfo($goods_id)
    {
        $lists = [];
        $supplierService = new SupplierService();
        $suppliers = $supplierService->supplierInfo($goods_id);
        $lists['supplier'] = [];
        if ($suppliers) {
            $lists['supplier'] = $suppliers;
        }
        $goodsInfo = Goods::where(['id' => $goods_id])->field('purchaser_id,supplier_id')->find();
        $lists['purchaser_id'] = 0;
        $lists['purchaser'] = '';
        $lists['supplier_id'] = 0;
        if ($goodsInfo) {
            $lists['purchaser_id'] = $goodsInfo['purchaser_id'];
            $lists['purchaser'] = $goodsInfo['purchaser_id'] ? $this->getUserNameById($goodsInfo['purchaser_id']) : '';
            $lists['supplier_id'] = $goodsInfo['supplier_id'];
        }
        $skus = GoodsSku::where(['goods_id' => $goods_id])->field('id as sku_id,thumb,sku')->select();
        if ($skus) {
            $skus = collection($skus)->toArray();
        }
        if ($skus && $lists['supplier']) {
            foreach ($lists['supplier'] as &$supplier) {
                $temp_skus = $skus;
                foreach ($temp_skus as &$sku) {
                    $params = [
                        "supplier_id" => $supplier['supplier_id'],
                        "sku_id" => $sku['sku_id'],
                    ];
                    $sku['thumb'] = $sku['thumb'] ? GoodsImage::getThumbPath($sku['thumb']) : '';
                    $getSupplierSkusResult = SupplierOfferService::getSupplierSkus($params);
                    $sku['price'] = 0.00;
                    $sku['audited_price'] = 0.00;
                    $sku['section'] = [];
                    $sku['cycle'] = [];
                    $sku['link'] = "";
                    if ($getSupplierSkusResult['status'] == 1) {
                        if ($getSupplierSkusResult['list']) {
                            $getSupplierSkusResult['list'] = collection($getSupplierSkusResult['list'])->toArray();
                        }
                        $sku['price'] = isset($getSupplierSkusResult['list'][0]['price']) ? $getSupplierSkusResult['list'][0]['price'] : 0.00;
                        $sku['audited_price'] = isset($getSupplierSkusResult['list'][0]['audited_price']) ? $getSupplierSkusResult['list'][0]['audited_price'] : 0.00;
                        $sku['currency_code'] = isset($getSupplierSkusResult['list'][0]['currency_code']) ? $getSupplierSkusResult['list'][0]['currency_code'] : 'CNY';
                        $sku['section'] = isset($getSupplierSkusResult['list'][0]['section']) ? $getSupplierSkusResult['list'][0]['section'] : [];
                        $sku['cycle'] = isset($getSupplierSkusResult['list'][0]['cycle']) ? $getSupplierSkusResult['list'][0]['cycle'] : [];
                        // $sku['link'] = isset($getSupplierSkusResult['list'][0]['link']) ? $getSupplierSkusResult['list'][0]['link'] : '';
                    }
                }

                $supplier['is_default'] = 0;
                if ($supplier['supplier_id'] == $goodsInfo->supplier_id) {
                    $supplier['is_default'] = 1;
                }
                $supplier['skus'] = $temp_skus;
            }
        }
        return $lists;
    }

    /**
     * 根据goods_id返回供应商ID
     *  author yangweiquan 20170624 16:53
     */
    public static function getGoodSupplierList($params)
    {
        $result = ['status' => 0, 'message' => ''];
        if (empty($params['goods_id'])) {
            $result['message'] = '产品ID不能为空';
            return $result;
        }
        $getSuppliersResult = SupplierOfferService::getSuppliersByGoodsId(["goods_id" => $params['goods_id']]);
        $suppliers = [];
        if ($getSuppliersResult['status'] == 1) {
            $suppliers = $getSuppliersResult['list'];
        }

        if ($suppliers) {
            foreach ($suppliers as &$supplier) {
                $supplierInfo = Cache::store('supplier')->getSupplier($supplier['supplier_id']);
                $supplier['company_name'] = !empty($supplierInfo['company_name']) ? $supplierInfo['company_name'] : '';
            }
        }

        $result['status'] = 1;
        $result['message'] = 'OK';
        $result['list'] = $suppliers;
        return $result;
    }

    /**
     * 获取产品描述信息
     * @praam int $goods_id 产品Id
     * @param int $lang_id 语言Id
     * @return array
     */
    public function getProductDescription($goods_id, $lang_id = 0)
    {
        $where = 'goods_id =' . $goods_id;
        if (0 == $lang_id) {

        } elseif (1 == $lang_id) {
            $where .= ' AND lang_id = ' . $lang_id;
        } else {
            $where .= ' AND lang_id in (1, ' . $lang_id . ')';
        }
        $lists = GoodsLang::where($where)->select();

        foreach ($lists as &$list) {
            $list['lang_name'] = $this->getLangName($list['lang_id']);
            $list['tags'] = empty($list['tags']) ? [] : explode('\n', $list['tags']);
            $amazon_point_1 = $list->amazon_point_1;
            $amazon_point_2 = $list->amazon_point_2;
            $amazon_point_3 = $list->amazon_point_3;
            $amazon_point_4 = $list->amazon_point_4;
            $amazon_point_5 = $list->amazon_point_5;
            $selling_point_description = [];
            $amazon_point_1 && $selling_point_description[] = $amazon_point_1;
            $amazon_point_2 && $selling_point_description[] = $amazon_point_2;
            $amazon_point_3 && $selling_point_description[] = $amazon_point_3;
            $amazon_point_4 && $selling_point_description[] = $amazon_point_4;
            $amazon_point_5 && $selling_point_description[] = $amazon_point_5;
            $list['selling_point_description'] = $selling_point_description;
        }
        if (!$lists) {
            $lists[] = [
                'lang_id' => 1,
                'lang_name' => '中文',
                'description' => '',
                'title' => '',
                'tags' => [],
                'goods_id' => $goods_id,
                'selling_point_description' => []
            ];
        }
        return $lists;
    }

    /**
     * 获取语言名字
     * @param int $lang_id
     * @return array
     */
    private function getLangName($lang_id)
    {
        $lists = Cache::store('lang')->getLang();
        $name = '';
        foreach ($lists as $list) {
            if ($list['id'] == $lang_id) {
                $name = $list['name'];
                break;
            }
        }

        return $name;
    }

    /**
     * 更新产品描述
     * @param int $goods_id
     * @param array $data
     * @throws Exception
     * @return array
     */
    public function modifyProductDescription($goods_id, $data, $user_id)
    {
        // 开始事务
        Db::startTrans();
        $GoodsLog = new GoodsLog();
        try {
            foreach ($data as $list) {
                $goodsLang = new GoodsLang();
                $aLang = $goodsLang->where(['goods_id' => $goods_id, 'lang_id' => $list['lang_id']])->find();
                if ($aLang) {
                    if (isset($list['description'])) {
                        if ($aLang->description != $list['description']) {
                            $GoodsLog->mdfLang($list['lang_id'], ['description' => $aLang->description], ['description' => $list['description']]);
                        }
                    }
                    if (isset($list['selling_point'])) {
                        $json = json_decode($list['selling_point'], true);
                        $upStr = [];
                        foreach ($json as $jK => $jV) {
                            $jV = addslashes($jV);
                            $upStr[] = "'$.{$jK}','{$jV}'";
                        }
                        if ($upStr) {
                            $list['selling_point'] = ['exp', 'JSON_SET(selling_point,' . implode(',', $upStr) . ')'];
                        }
                    }
                    $goodsLang->allowField(true)->where(['goods_id' => $goods_id, 'lang_id' => $list['lang_id']])->update($list);
                } else {
                    $list['goods_id'] = $goods_id;
                    if (!isset($list['selling_point'])) {
                        $list['selling_point'] = '{}';
                    }
                    $ValidateGoodsLang = new ValidateGoodsLang();
                    $flag = $ValidateGoodsLang->check($list);
                    if ($flag === false) {
                        throw new Exception($ValidateGoodsLang->getError());
                    }
                    $goodsLang->allowField(true)->save($list);
                    $GoodsLog->addLang($list['lang_id']);
                }
            }
            $GoodsLog->save($user_id, $goods_id);
            Db::commit();
            return ['message' => '更新成功'];
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 格式化产品描述
     * @param array $descriptions
     * @return array
     */
    public function formatDescription($descriptions)
    {
        $results = [];
        $descriptions = json_decode($descriptions, true);
        foreach ($descriptions as $description) {
            $list['lang_id'] = $description['lang_id'];
            $list['description'] = $description['description'];
            $list['tags'] = implode('\n', $description['tags']);
            $list['title'] = trim($description['title']);
            $selling_point = [];
            if (isset($description['selling_point_description'])) {
                for ($i = 1; $i <= 5; $i++) {
                    $selling_point_key = 'amazon_point_' . $i;
                    if (isset($description['selling_point_description'][$i - 1])) {
                        $selling_point[$selling_point_key] = $description['selling_point_description'][$i - 1];
                    } else {
                        $selling_point[$selling_point_key] = '';
                    }
                }
            }
            $list['selling_point'] = json_encode($selling_point, JSON_FORCE_OBJECT);
            $results[] = $list;
        }
        return $results;
    }

    /**
     * 修改商品渠道属性
     * @param $id
     * @param $platform
     * @author starzhan <397041849@qq.com>
     */
    public function saveGoodsCategoryMap($id, $platform, $user_id = 0)
    {
        if (!$id || !$platform) {
            throw new Exception('id或platform不能为空');
        }
        $GoodsCategoryMap = new GoodsCategoryMap();
        $validate = new ValidateGoodCategoryMap();
        $aDatas = [];
        foreach ($platform as $v) {

            $aData = [];
            $aData['goods_id'] = $id;
            $aData['channel_id'] = $v['channel_id'];
            $aData['channel_category_id'] = $v['channel_category_id'];
            $aData['site_id'] = $v['site_id'] ?? 0;
            $aData['create_time'] = time();
            $aData['update_time'] = time();
            $aData['operator_id'] = $user_id;
            $error = $validate->check($aData);
            if ($error === false) {
                throw new Exception($validate->getError());
            }
            $aDatas[] = $aData;
        }
        Db::startTrans();
        try {
            $GoodsCategoryMap->where('goods_id', $id)->delete();
            if ($aDatas) {
                $GoodsCategoryMap->allowField(true)->insertAll($aDatas);
            } else {
                throw  new Exception('添加失败,platform为空');
            }
            Db::commit();
            return '';
        } catch (Exception $ex) {
            Db::rollback();
            throw  new Exception($ex->getMessage());
        }
    }

    /**
     * 获取商品渠道属性
     * @param $goods_id
     * @return array
     * @autor starzhan <397041849@qq.com>
     */
    public function getGoodsCategoryMap($goods_id)
    {
        if (!$goods_id) {
            return [];
        }
        $GoodsCategoryMap = new GoodsCategoryMap();
        $aGoodsCategoryMap = $GoodsCategoryMap->where('goods_id', $goods_id)->select();
        $result = [];
        if ($aGoodsCategoryMap) {
            foreach ($aGoodsCategoryMap as $v) {
                $row = [];
                // $row['id'] = $v->id;
                $row['goods_id'] = $v->goods_id;
                $row['channel_id'] = $v->channel_id;
                $row['site_id'] = $v->site_id;
                $row['channel_category_id'] = $v->channel_category_id;
                $row['label'] = '';
                $row['path'] = '[]';
                $Channel = GoodsCategoryMap::getChannel($v->channel_id);
                $sile = [];
                if ($Channel && $v->site_id) {
                    $sile = GoodsCategoryMap::getsite($Channel['name'], $v->site_id);
                }
                $label = [];
                $path = [];
                $channel_category = GoodsCategoryMap::getCategoty($Channel['name'], $v->site_id, $row['channel_category_id']);
                $label[] = $Channel['name'];
                $path[] = ['label' => $Channel['name'], 'is_site' => 1, 'id' => $v->channel_id];
                if ($sile) {
                    $label[] = $sile['name'];
                    $path[] = ['id' => $v->site_id, 'code' => $sile['code'], 'label' => $sile['name']];
                }
                foreach ($channel_category as $cate) {
                    $label[] = $cate['category_name'];
                    $path[] = ['id' => $cate['category_id'], 'label' => $cate['category_name']];
                }
                $row['label'] = implode('>>', $label);
                $row['path'] = json_encode($path, JSON_UNESCAPED_UNICODE);
                $result[] = $row;
            }
        }
        return $result;

    }

    /**
     * 获取产品包装名称
     * @param int $id
     * @return string
     */
    public function getPackageById($id)
    {
        $lists = Cache::store('packing')->getPacking();
        foreach ($lists as $list) {
            if ($list['id'] == $id) {
                return $list['name'];
            }
        }
        return '';
    }

    /**
     * 获取产品包装名称
     * @param int $id
     * @return string
     */
    public function getUnitById($id)
    {
        $lists = Cache::store('unit')->getUnit();
        foreach ($lists as $list) {
            if ($list['id'] == $id) {
                return $list['name'];
            }
        }
        return '';
    }

    /**
     * 获取标签
     * @param int $tag
     * @return array
     */
    private function getTags($tag)
    {
        $result = [];
        $tags = explode(',', $tag);
        $lists = Cache::store('tag')->getTag();
        foreach ($tags as $tag_id) {
            foreach ($lists as $list) {
                if ($list['id'] == $tag_id) {
                    $result[] = $list;
                }
            }
        }
        return $result;
    }

    /**
     * 获取产品品牌
     * @param int $id
     * @return string
     */
    private function getBrandById($id)
    {
        $lists = Cache::store('brand')->getBrand();
        foreach ($lists as $list) {
            if ($list['id'] == $id) {
                return $list['name'];
            }
        }
        return '';
    }

    /**
     * 获取产品品牌风险
     * @param int $id
     * @return string
     */
    public function getTortById($id)
    {
        $lists = Cache::store('brand')->getTort();
        foreach ($lists as $list) {
            if ($list['id'] == $id) {
                return $list['name'];
            }
        }
        return '';
    }

    /**
     * 获取出售信息
     * @return array
     */
    public function getSalesStatus()
    {
        $lists = [];
        foreach ($this->sales_status as $k => $list) {
            $lists[] = [
                'id' => $k,
                'name' => $list
            ];
        }

        return $lists;
    }

    /**
     * 获取产品物流属性
     * @param int $property
     * @return array
     */
    public function getProTransProperties($property)
    {
        $results = $this->getTransportProperies();
        foreach ($results as &$list) {
            $list['enabled'] = $list['value'] & $property ? true : false;
        }

        return $results;
    }

    /**
     * 获取产品物流属性文本
     * @param int $property
     * @return string
     */
    public function getProTransPropertiesTxt($property)
    {
        $ret = [];
        $results = $this->getTransportProperies();
        foreach ($results as $list) {
            $list['enabled'] = $list['value'] & $property ? true : false;
            if ($list['enabled']) {
                $ret[] = $list['name'];
            }
        }
        return implode('、', $ret);
    }

    /**
     * 获取产品物流属性文本根据skuId
     * @params int skuId
     * @return string
     */
    public function getTPropertiesTextBySkuId($skuId)
    {
        $skuInfo = Cache::store('goods')->getSkuInfo($skuId);
        if (empty($skuInfo)) {
            return '';
        }
        $goodsInfo = Cache::store('goods')->getGoodsInfo($skuInfo['goods_id']);
        return $goodsInfo ? $this->getProTransPropertiesTxt($goodsInfo['transport_property']) : '';
    }

    /**
     * @title 根据商品id得出物流属性
     * @param $goods_id
     * @return string
     * @author starzhan <397041849@qq.com>
     */
    public function getPropertiesTextByGoodsId($goods_id)
    {
        $goodsInfo = Cache::store('goods')->getGoodsInfo($goods_id);
        return $goodsInfo ? $this->getProTransPropertiesTxt($goodsInfo['transport_property']) : '';
    }

    /**
     * 格式化物流属性
     * @param array $properties
     * @return int
     */
    public function formatTransportProperty($properties)
    {
        $transport_property = 0;
        foreach ($properties as $property) {
            if (isset($property['enabled']) && !$property['enabled']) {
                continue;
            }
            $transport_property += isset($this->transport_properties[$property['field']]) ? $this->transport_properties[$property['field']]['value'] : 0;
        }
        return $transport_property;
    }

    /**
     * 检查产品物流属性
     * @param int $transport_property
     */
    public function checkTransportProperty($transport_property)
    {
        foreach ($this->transport_properties as $property) {
            if (($transport_property & $property['value']) && ($property['exclusion'] & $transport_property)) {
                throw new Exception('存在与' . $property['name'] . '相排斥的物流属性');
            }
            if ($transport_property == 1) {
                break;
            }
        }
    }

    /**
     * 获取物流属性列表
     * @return array
     */
    public function getTransportProperies()
    {
        $results = [];
        foreach ($this->transport_properties as $property) {
            $property['enabled'] = false;
            $results[] = $property;
        }

        return $results;
    }

    /**
     * 获取用户名 根据id
     * @param int $id
     * @return string
     */
    public function getUserNameById($id)
    {
        if (!$id) {
            return '';
        }
        static $users = [];
        if (!isset($users[$id])) {
            $userInfo = Cache::store('user')->getOneUser($id);
            if (!$userInfo) {
                $users[$id] = '';
            }
            $users[$id] = $userInfo['realname'] ?? '';
        }
        return $users[$id];
    }


    /**
     * 获取产品日志列表
     * @param number $goods_id 产品|预产品 id
     * @param number $type 0-产品开发日志  1-预产品开发日志
     * @return unknown
     */
    public function getLog($goods_id = 0, $type = 2)
    {
        $where = ['goods_id' => $goods_id, 'type' => $type];
        if ($type == 1) {
            $where = ['pre_goods_id' => $goods_id, 'type' => 1];
        }
        $lists = GoodsDevelopLog::where($where)->select();
        $goodsdev = new Goodsdev();
        $goodsLog = new GoodsLog();
        foreach ($lists as &$list) {
            $list['operator'] = $this->getUserNameById($list['operator_id']);
            $list['process'] = $goodsdev->getProcessBtnNameById($list['process_id']);
            $list['remark'] = $goodsLog->getRemark($list['remark']);
            unset($list['operator_id'], $list['process_id'], $list['id']);
        }
        return $lists;
    }

    /**
     * 添加日志
     * @param int $goods_id
     * @param string $remark
     */
    public function addLog($goods_id, $remark)
    {
        $log['remark'] = $remark;
        $log['goods_id'] = $goods_id;
        $log['create_time'] = time();
        $log['process_id'] = 0;
        $log['operator_id'] = 1;
        $goodsDevLog = new GoodsDevelopLog();
        $goodsDevLog->allowField(true)->save($log);
    }


    /**
     * @title 获取产品sku信息列表
     * @param $goods_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSkuInfo($goods_id)
    {
        $result = [];
        $headers = [];
        do {
            $goods_info = Goods::where(['id' => $goods_id])->field('category_id,weight')->find();
            if (empty($goods_info)) {
                break;
            }
            $sku_lists = (new GoodsSku)->alias('a')
                ->where(['a.goods_id' => $goods_id])
                ->field('a.id,a.sku,a.thumb,a.goods_id,a.name,a.cost_price,a.retail_price,a.sku_attributes,a.weight,
                a.width,a.height,a.length,a.auto_update_time')
                ->order('sku asc')
                ->select();

            if (empty($sku_lists)) {
                break;
            }

            $time = time();
            $goodsDiscountService = new GoodsDiscount();

            foreach ($sku_lists as &$sku) {
                $sku = $sku->toArray();
                $sku['weight'] == 0 ? $sku['weight'] = $goods_info['weight'] : '';
                $sku['width'] = $sku['width'] / 10;
                $sku['length'] = $sku['length'] / 10;
                $sku['height'] = $sku['height'] / 10;
                $sku['alias_sku'] = GoodsSkuAliasServer::getAliasBySkuId($sku['id'], 2);
                $sku['main_image'] = $sku['thumb'] ? GoodsImage::getThumbPath($sku['thumb']) : '';
                $sku_attributes = json_decode($sku['sku_attributes'], true);
                $temps = self::getAttrbuteInfoBySkuAttributes($sku_attributes, $goods_id);
                foreach ($temps as $temp) {
                    $row = [];
                    $row['attribute_value'] = $temp['value'];
                    $row['attribute_id'] = (int)$temp['id'];
                    $sku['attributes'][] = $row;
                    $headers[$temp['id']] = [
                        'attribute_id' => (int)$temp['id'],
                        'name' => $temp['name']
                    ];
                }

                $result = $goodsDiscountService->readConcise($sku['id']);
                if (!$result || $result['status'] != 1 || ($result['discount_num'] == 0 || $result['discount_num'] == $result['sell_num']|| $result['valid_time'] > $time || $result['over_time'] < $time)) {
                    $sku['discount_value'] = 0;
                } else {
                    $sku['discount_value'] = $result['discount_value'];

                    if ($result['discount_type'] == 2) {
                        $sku['discount_value'] = sprintf("%.3f",($result['discount_value'] * $result['inventory_price']) / 100);
                    }
                }
            }
            $result['lists'] = $sku_lists;
            $result['headers'] = array_values($headers);
        } while (false);

        return $result;
    }

    /**
     * 获取商品相册
     */
    public function getGoodsGallery($goods_id = 0)
    {
        $gallerys = GoodsImage::getAllImages(['goods_id' => $goods_id]);

        if ($gallerys) {
            return $gallerys;
        } else {
            return null;
        }

    }

    /**
     * 获取产品编辑sku
     * @param int $goods_id
     * @return array
     */
    public function getSkuLists($goods_id)
    {
        $lists = [];
        $headers = [];
        $goods_attributes = $this->getAttributeInfo($goods_id, 1);
        $sku_lists = GoodsSku::where(['goods_id' => $goods_id])->field('id,sku,thumb,name,sku_attributes,name,cost_price,retail_price,weight,status,length,width,height')->select();
        $goods_info = Goods::where(['id' => $goods_id])->field('weight')->find();
        foreach ($goods_attributes as $attribute) {
            foreach ($attribute['attribute_value'] as $k => &$list) {
                if (empty($list['selected'])) {
                    unset($attribute['attribute_value'][$k]);
                    continue;
                }
                $row['attribute_value'] = $list['value'];
                $row['attribute_id'] = $list['attribute_id'];
                $list['attributes'][] = $row;
            }
            if (empty($attribute['attribute_value'])) {
                continue;
            }
            $headers[] = [
                'attribute_id' => $attribute['attribute_id'],
                'name' => $attribute['name']
            ];

            $lists[] = $attribute['attribute_value'];
        }
        $new_lists = $this->getBaseSkuLists($lists, $sku_lists, $headers, $goods_info['weight']);
        return ['lists' => $new_lists, 'headers' => $headers];
    }

    /**
     * 获取基础sku 列表
     * @param array $lists
     * @param array $sku_lists
     * @param array $headers
     * @param int $weight
     * @return array
     */
    public function getBaseSkuLists(&$lists, &$sku_lists, &$headers, $weight = 0)
    {
        $huilv = Cache::store('currency')->getCurrency()['USD'];
        // 获取属性组合sku数据
        $new_lists = !empty($lists) ? $this->getAttrSet($lists) : [];
        foreach ($new_lists as &$list) {
            foreach ($list as $v) {
                $new_list[$v['attribute_id']] = $v;
            }
            $list = $new_list;
        }
        // 匹配sku
        foreach ($new_lists as &$list) {
            foreach ($sku_lists as $k => $sku_info) {
                $sku_info['weight'] == 0 ? $sku_info['weight'] = $weight : '';
                $flag = true;
                $sku_attributes = json_decode($sku_info['sku_attributes'], true);
                foreach ($list as $attribute_id => $value) {
                    if (isset($sku_attributes['attr_' . $attribute_id]) && $sku_attributes['attr_' . $attribute_id] == $value['id']) {
                        continue;
                    } else {
                        $flag = false;
                        break;
                    }
                }
                if ($flag) {
                    $list['thumb'] = $sku_info['thumb'] ? GoodsImage::getThumbPath($sku_info['thumb']) : '';
                    $list['sku'] = $sku_info['sku'];
                    $list['alias_sku'] = $this->getSkuAlias($sku_info['id']);
                    $list['id'] = $sku_info['id'];
                    $list['name'] = $sku_info['name'];
                    $list['status'] = $sku_info['status'];
                    $list['cost_price'] = $sku_info['cost_price'];
                    $list['retail_price'] = $sku_info['retail_price'];
                    $list['official_rate'] = $huilv['official_rate'];
                    $list['weight'] = $sku_info['weight'];
                    $list['height'] = $sku_info['height'] / 10;
                    $list['width'] = $sku_info['width'] / 10;
                    $list['length'] = $sku_info['length'] / 10;
                    $list['enabled'] = true;
                    unset($sku_lists[$k]);
                    break;
                }
            }
            if (!isset($list['sku'])) {
                $list['thumb'] = '';
                $list['sku'] = '';
                $list['alias_sku'] = [];
                $list['id'] = 0;
                $list['name'] = '';
                $list['status'] = 0;
                $list['cost_price'] = 0.00;
                $list['retail_price'] = 0.00;
                $list['weight'] = $weight;
                $list['enabled'] = false;
                $list['height'] = 0;
                $list['width'] = 0;
                $list['length'] = 0;
            }
            unset($list);
        }
        if (!empty($sku_lists)) {
            foreach ($sku_lists as $list) {
                $list['enabled'] = true;
                $list['alias_sku'] = $this->getSkuAlias($list['id']);
                foreach ($headers as $header) {
                    $list[$header['attribute_id']] = [
                        'value' => ''
                    ];
                }
                $list['height'] = $list['height'] / 10;
                $list['width'] = $list['width'] / 10;
                $list['length'] = $list['length'] / 10;
                unset($list['sku_attributes']);
                array_push($new_lists, $list);
            }
        }
        return $new_lists;
    }


    /**
     * 保存产品sku信息
     * @param int $goods_id
     * @param array $lists
     * @param boolean $is_generate_sku
     * @throws Exception
     */
    public function saveSkuInfo($goods_id, $lists, $is_generate_sku = true)
    {
        $goods_info = Goods::where(['id' => $goods_id])->field('spu, name, weight')->find();
        if (empty($goods_info)) {
            throw new Exception('产品没找到');
        }
        $attributes = $this->getAttributeInfo($goods_id, 1);
        $goods_attributes = [];
        foreach ($attributes as $attribute) {
            $values = [];
            foreach ($attribute['attribute_value'] as $k => $list) {
                if (empty($list['selected'])) {
                    unset($attribute['attribute_value'][$k]);
                    continue;
                }
                $values[$list['id']] = $list;
            }
            $attribute['attribute_value'] = $values;
            $goods_attributes[$attribute['attribute_id']] = $attribute;
        }
        $add_lists = [];
        $del_lists = [];
        $modify_lists = [];

        // 开始事务
        Db::startTrans();
        try {
            $this->formatSkuInfo($lists, $add_lists, $modify_lists, $del_lists, $goods_attributes, $goods_info['spu'], $goods_id, $is_generate_sku);
            if ($add_lists) {
                foreach ($add_lists as $list) {
                    $goodsSku = new GoodsSku();
                    if (isset($list['id'])) {
                        unset($list['id']);
                    }
                    isset($list['weight']) && $list['weight'] == $goods_info['weight'] ? $list['weight'] = 0 : '';
                    $list['status'] = 0;
                    $list['spu_name'] = $goods_info['name'];
                    $list['goods_id'] = $goods_id;
                    $list['create_time'] = time();
                    $list['update_time'] = time();
                    $alias_sku = $list['alias_sku'];
                    isset($list['width']) && $list['width'] = $list['width'] * 10;
                    isset($list['height']) && $list['height'] = $list['height'] * 10;
                    isset($list['length']) && $list['length'] = $list['length'] * 10;
                    unset($list['alias_sku']);
                    $goodsSku->allowField(true)->isUpdate(false)->save($list);
                    !empty($alias_sku) ? $this->saveSkuAlias($goodsSku->id, $list['sku'], $alias_sku) : '';
                }
            }

            if ($modify_lists) {
                foreach ($modify_lists as $list) {
                    $goodsSku = new GoodsSku();
                    $list['update_time'] = time();
                    if (isset($list['alias_sku'])) {
                        !empty($list['alias_sku']['add']) ? $this->saveSkuAlias($list['id'], $list['sku'], $list['alias_sku']['add']) : '';
                        !empty($list['alias_sku']['del']) ? $this->deleteSkuAlias($list['id'], $list['alias_sku']['del']) : '';
                        unset($list['alias_sku']);
                    }
                    if (isset($list['sku'])) {
                        unset($list['sku']);
                    }
                    isset($list['width']) && $list['width'] = $list['width'] * 10;
                    isset($list['height']) && $list['height'] = $list['height'] * 10;
                    isset($list['length']) && $list['length'] = $list['length'] * 10;
                    $goodsSku->allowField(true)->isUpdate(true)->save($list);
                    Cache::store('goods')->delSkuInfo($list['id']);
                }
            }

            if ($del_lists) {
                foreach ($del_lists as $list) {
                    $goodsSku = new GoodsSku();
                    $goodsSku->where(['id' => $list['id'], 'goods_id' => $goods_id])->delete();
                    Cache::store('goods')->delSkuInfo($list['id']);
                    $this->deleteSkuAlias($list['id']);
                }
            }
            // 事务提交
            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 获取预开发产品的属性
     * @param $attributes
     * @return array
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    private function getPreSkuAttr($attributes)
    {
        $resultAttributes = [];
        foreach ($attributes as $aAttributes) {
            $attributesValues = Cache::store('attribute')->getAttribute($aAttributes['attribute_id']);
            //判断是否是自定义属性
            if (in_array($aAttributes['attribute_id'], GoodsImport::$diy_attr)) {
                $resultAttributes[] = [
                    'attribute_id' => $aAttributes['attribute_id'],
                    'value' => $aAttributes['attribute_value'],
                    'value_id' => 0,
                    'code' => $attributesValues['code'],
                    'value_code' => $aAttributes['attribute_value']
                ];
            } else {
                $value_id = 0;
                $code = '';
                foreach ($attributesValues['value'] as $k => $v) {
                    if ($v['value'] == $aAttributes['attribute_value']) {
                        $value_id = $k;
                        $code = $v['code'];
                        break;
                    }
                }
                if (!$value_id) {
                    throw new Exception('未找到属性值' . $aAttributes['attribute_value']);
                }
                $resultAttributes[] = [
                    'attribute_id' => $aAttributes['attribute_id'],
                    'value_id' => $value_id,
                    'code' => $attributesValues['code'],
                    'value_code' => $code
                ];
            }
        }
        return $resultAttributes;
    }

    /**
     *
     * @param $goodsInfo
     * @author starzhan <397041849@qq.com>
     */
    private function preAddSku($goods_id, $goodsInfo)
    {

        $skus = json_decode($goodsInfo['skus'], true);
        if (!$skus) {
            throw new Exception('SKU信息不能为空');
        }
        try {
            $sku_array = [];
            foreach ($skus as $sku) {
                $sku['goods_id'] = $goods_id;
                $sku['name'] = $goodsInfo['name'];
                $sku['attributesFinally'] = [];
                $rule = [];
                $sku['attributes'] = array_filter($sku['attributes'], function ($val) {
                    if ($val['attribute_value'] == '-1') {
                        return false;
                    }
                    return true;
                });
                if ($sku['attributes']) {
                    $sku['attributesFinally'] = $this->getPreSkuAttr($sku['attributes']);
                    $attributes = [];
                    foreach ($sku['attributesFinally'] as $attr) {
                        $attr['category_id'] = $goodsInfo['category_id'];
                        $attr['goods_id'] = $goods_id;
                        if ($attr['value_id'] == 0) {
                            $value_id = GoodsImport::addSelfAttribute($attr);
                            $attributes['attr_' . $attr['attribute_id']] = $value_id;
                        } else {
                            GoodsImport::addAttribute($attr);
                            $attributes['attr_' . $attr['attribute_id']] = $attr['value_id'];
                        }
                        $rule[] = [
                            'code' => $attr['code'],
                            'attribute_id' => $attr['attribute_id'],
                            'value_code' => $attr['value_code'],
                            'value_id' => $attr['value_id'] == 0 ? $value_id : $attr['value_id']
                        ];
                    }

                    $sku['sku_attributes'] = json_encode($attributes);
                }
                $sku['sku'] = $this->createSku($goodsInfo['spu'], [], $goods_id, 0, $sku_array);
                $sku_array[] = $sku['sku'];
                $goodsSku = new GoodsSku();
                $validateGoodsSku = new ValidateGoodsSku();
                $flag = $validateGoodsSku->scene('preDev')->check($sku);
                if ($flag === false) {
                    throw new Exception($validateGoodsSku->getError());
                }
                $goodsSku->allowField(true)->isUpdate(false)->save($sku);
                if ($goodsInfo['supplier_id'] && !empty($sku['cost_price'])) {
                    $goodsOfferModel = new SupplierGoodsOffer();
                    $offerData = [];
                    $offerData['goods_id'] = $goods_id;
                    $offerData['sku_id'] = $goodsSku->id;
                    $offerData['supplier_id'] = $goodsInfo['supplier_id'];
                    $offerData['price'] = $sku['cost_price'];
                    $offerData['audited_price'] = $sku['cost_price'];
                    $offerData['update_time'] = time();
                    $offerData['status'] = 1;
                    isset($goodsInfo['purchase_link']) && $goodsInfo['purchase_link'] && $offerData['link'] = $goodsInfo['purchase_link'];
                    isset($goodsInfo['developer_id']) && $goodsInfo['developer_id'] && $offerData['creator_id'] = $goodsInfo['developer_id'];
                    $goodsOfferModel->allowField(true)->isUpdate(false)->save($offerData);
                }
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * 保存产品平台销售状态信息
     * @param int $goods_id
     * @param array $platformSale
     * @throws Exception
     */
    public function savePlatformSale($goods_id, $platformSale)
    {
        $goods_info = Goods::where(['id' => $goods_id])->field('platform_sale')->find();
        if (empty($goods_info)) {
            throw new Exception('产品没找到');
        }
        // 开始事务
        Db::startTrans();
        try {
            $aPlatformSale = json_decode($goods_info->platform_sale, true);
            $aPlatformSale = array_merge($aPlatformSale, $platformSale);
            $Goods = new Goods();
            $Goods->allowField(true)->isUpdate(true)->save(['platform_sale' => json_encode($aPlatformSale)], ['id' => $goods_id]);
            // 事务提交
            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 获取sku别名列表
     * @param int $sku_id
     * @return array
     */
    private function getSkuAlias($sku_id)
    {
        $results = [];
        $lists = GoodsSkuAlias::where(['sku_id' => $sku_id])->select();
        foreach ($lists as $list) {
            $results[] = $list['alias'];
        }

        return $results;
    }

    /***
     * 保存sku别名
     * @param int $sku_id
     * @param string $sku
     * @param array $lists
     */
    private function saveSkuAlias($sku_id, $sku, $lists)
    {
        $goodsSkuAlias = new GoodsSkuAlias();
        $results = [];
        foreach ($lists as $alias) {
            $results[] = [
                'sku_id' => $sku_id,
                'sku_code' => $sku,
                'alias' => $alias,
                'create_time' => time()
            ];
            Cache::handler()->hdel('cache:Sku', $sku_id);
        }
        $goodsSkuAlias->allowField(true)->saveAll($results);
    }

    /**
     * 删除sku别名
     * @param int $sku_id
     * @param string $sku
     */
    private function deleteSkuAlias($sku_id, $sku = null)
    {
        if (null == $sku) {
            GoodsSkuAlias::where(['sku_id' => $sku_id])->delete();
        } else if (is_string($sku)) {
            GoodsSkuAlias::where(['sku_id' => $sku_id, 'alias' => $sku])->delete();
        } else {
            foreach ($sku as $alias) {
                GoodsSkuAlias::where(['sku_id' => $sku_id, 'alias' => $alias])->delete();
            }
        }
        Cache::handler()->hdel('cache:Sku', $sku_id);
    }

    /**
     * sku信息格式化与检测
     * @param array $lists
     * @param array $add_lists
     * @param array $modify_lists
     * @param array $del_lists
     * @param array $goods_attributes
     * @param string $spu
     * @param int $goods_id
     * @param boolean $is_generate_sku
     * @throws Exception
     */
    public function formatSkuInfo(&$lists, &$add_lists, &$modify_lists, &$del_lists, &$goods_attributes, $spu, $goods_id, $is_generate_sku)
    {
        $message = '';
        $attribute_array = [];
        $sku_array = [];
        foreach ($lists as $list) {
            switch ($list['action']) {
                case 'add':
                    $attributes = !empty($list['attributes']) ? $list['attributes'] : [];
                    $sku_attributes = [];
                    $rule = [];
                    foreach ($attributes as $attribute) { // 组织属性规格用于生产sku
                        $sku_attributes['attr_' . $attribute['attribute_id']] = $attribute['value_id'];
                        if (!isset($goods_attributes[$attribute['attribute_id']])
                            || !isset($goods_attributes[$attribute['attribute_id']]['attribute_value'][$attribute['value_id']])) {
                            $message .= '属性或者属性值不存在产品规格中';
                        }
                        $rule[] = [
                            'code' => $goods_attributes[$attribute['attribute_id']]['code'],
                            'attribute_id' => $attribute['attribute_id'],
                            'value_code' => $goods_attributes[$attribute['attribute_id']]['attribute_value'][$attribute['value_id']]['code'],
                            'value_id' => $attribute['value_id']
                        ];
                    }
                    if ($message) {
                        break;
                    }
                    $list['sku_attributes'] = json_encode($sku_attributes);
                    if (in_array($list['sku_attributes'], $attribute_array) || $this->isSameAttribute($goods_id, $sku_attributes)) {
                        $message .= '存在相同的属性sku';
                        break;
                    }
                    $attribute_array[] = $list['sku_attributes'];
                    $list['sku'] = $is_generate_sku ? $this->createSku($spu, $rule, $goods_id, 0, $sku_array) : '';
                    $sku_array[] = $list['sku'];
                    $list['alias_sku'] = isset($list['alias_sku']) ? $list['alias_sku'] : [];
                    // 验证sku_alias 别名问题
                    foreach ($list['alias_sku'] as $alias) {
                        if (in_array($alias, $sku_array) || $this->isSameSku($goods_id, $alias)) {
                            $message .= '  ' . $alias . '存在相同的sku';
                            continue;
                        }
                        $sku_array[] = $alias;
                    }
                    $add_lists[] = $list;
                    break;
                case 'del':
                    if (!$this->isDeleteSku($list['id'])) {
                        $message .= (isset($list['sku']) ? $list['sku'] : '') . '不可以被删除';
                    }
                    $del_lists[] = $list;
                    break;
                case 'modify':
                    if (isset($list['alias_sku'])) {
                        $sku_alias = $list['alias_sku'];
                        $search_lists = $this->getSkuAlias($list['id']);
                        $del_alias = array_diff($search_lists, $sku_alias);
                        $add_alias = array_diff($sku_alias, $search_lists);
                        foreach ($add_alias as $alias) {
                            if (in_array($alias, $sku_array) || $this->isSameSku($goods_id, $alias)) {
                                $message .= '  ' . $alias . '存在相同的sku';
                                continue;
                            }
                            $sku_array[] = $alias;
                        }
                    }
                    $list['alias_sku']['add'] = $add_alias;
                    $list['alias_sku']['del'] = $del_alias;
                    $modify_lists[] = $list;
                    break;
            }
        }

        if ($message) {
            throw new Exception($message);
        }
    }

    /**
     * sku能否删除
     * @param int $sku_id
     * @return boolean
     */
    public function isDeleteSku($sku_id)
    {
        $sku_info = GoodsSku::where(['id' => $sku_id])->field('status')->find();
        if (!$sku_info || $sku_info['status'] == 0) {
            return true;
        }

        return false;
    }

    /**
     * 获取sku
     * @param string $spu
     * @param array $rule
     * @param int $goods_id
     * @param int $num
     * @param array $list_array
     * @return string
     */
    public function createSku($spu, $rule, $goods_id, $num = 0, $list_array = array(), $singleSku = false)
    {
        if ($singleSku) {
            return $spu . "00";
        }
        do {
            $sku = $this->generateSku($spu, $rule, $num++);
        } while ($this->isSameSku($goods_id, $sku) || in_array($sku, $list_array));

        return $sku;
    }

    /**
     * 检测是否使用过相同属性
     * @param int $goods_id
     * @param array $sku_attributes
     * @return boolean
     */
    public function isSameAttribute($goods_id, $sku_attributes)
    {
        $where = ' goods_id =' . $goods_id;
        foreach ($sku_attributes as $attribute => $value_id) {
            $where .= ' AND sku_attributes->"$.' . $attribute . '" = ' . $value_id;
        }
        $count = GoodsSku::where($where)->count();
        if ($count) {
            return true;
        }

        return false;
    }

    /**
     * 检测是否使用过相同SKU
     * @param int $goods_id
     * @param string $sku
     * @return boolean
     */
    public function isSameSku($goods_id, $sku)
    {
        $count = GoodsSku::where(['sku' => $sku])->count();
        $alias_count = GoodsSkuAlias::where(['alias' => $sku])->count();
        if ($count || $alias_count) {
            return true;
        }

        return false;
    }

    /**
     * 检测是否使用过相同SKU
     * @param int $goods_id
     * @param string $sku
     * @return boolean
     */
    public function isSameSpu($spu, $category_id)
    {
        $count = Goods::where('spu', $spu)->whereOr('alias', $spu)->count();
        if ($count) {
            $sequence = intval(substr($spu, -4));
            Category::where('id', $category_id)->update(['sequence' => $sequence]);
            return true;
        }
        return false;
    }

    /**
     * 获取属性值名称
     * @param array $goods_attributes
     * @param int $attribute_id
     * @param int $attribute_value_id
     * @return string
     */
    public function getAttributeValue(&$goods_attributes, $attribute_id, $attribute_value_id, &$attribute_name)
    {
        $attribute = '';
        $value = '';
        foreach ($goods_attributes as $list) {
            if ($list['attribute_id'] == $attribute_id) {
                $attribute = $list;
                break;
            }
        }
        if (!$attribute) {
            return '';
        }
        $attribute_name = $attribute['name'];
        foreach ($attribute['attribute_value'] as $value_list) {
            if ($value_list['id'] == $attribute_value_id) {
                $value = $value_list['value'];
                break;
            }
        }
        return $value;
    }

    /**
     * 数组交叉组合
     * @staticvar array $_total_arr
     * @staticvar int $_total_arr_index
     * @staticvar int $_total_count
     * @staticvar array $_temp_arr
     * @param array $arrs
     * @param int $_current_index
     * @return array
     */
    private function getAttrSet(array $arrs, $_current_index = -1)
    {
        static $_total_arr;
        static $_total_arr_index;
        static $_total_count;
        static $_temp_arr;
        if ($_current_index < 0) {
            $_total_arr = array();
            $_total_arr_index = 0;
            $_temp_arr = array();
            $_total_count = count($arrs) - 1;
            $this->getAttrSet($arrs, 0);
        } else {
            foreach ($arrs[$_current_index] as $v) {
                //如果当前的循环的数组少于输入数组长度
                if ($_current_index < $_total_count) {
                    //将当前数组循环出的值放入临时数组
                    $_temp_arr[$_current_index] = $v;
                    //继续循环下一个数组
                    $this->getAttrSet($arrs, $_current_index + 1);
                } else if ($_current_index == $_total_count) { //如果当前的循环的数组等于输入数组长度(这个数组就是最后的数组)
                    //将当前数组循环出的值放入临时数组
                    $_temp_arr[$_current_index] = $v;
                    //将临时数组加入总数组
                    $_total_arr[$_total_arr_index] = $_temp_arr;
                    //总数组下标计数+1
                    $_total_arr_index++;
                }
            }
        }

        return $_total_arr;
    }

    /**
     * 获取sku关联的仓库
     * @param int sku_id
     * @return array
     */
    public function getSkuWarehouses($sku_id)
    {
        $skuInfo = Cache::store('goods')->getSkuInfo($sku_id);
        if (empty($skuInfo)) {
            return [0];
        }
        $goodsInfo = Cache::store('goods')->getGoodsInfo($skuInfo['goods_id']);
        if (empty($goodsInfo)) {
            return [0];
        }
        if (!$goodsInfo['is_multi_warehouse']) {//是否为多仓库，不是则取得默认仓库
            return [$goodsInfo['warehouse_id']];
        }
        $WarehouseGoods = new WarehouseGoods();
        return $WarehouseGoods->getWarehouseBySkuId($sku_id);
//        $warehouses = Cache::store('warehouse')->getWarehouse();
//        foreach ($warehouses as $k => $warehouse) {
//            if ($warehouse['status'] == 0) {
//                unset($warehouses[$k]);
//            }
//        }
//        return array_keys($warehouses);
    }

    /**
     * 操作产品状态
     * @param int $goods_id
     * @param int $status
     * @return array
     */
    public function changeStatus($goods_id, $status, $user_id)
    {
        $goodsModel = new Goods();
        $goodsSkuModel = new GoodsSku();
        //查看产品是否存在
        if (!$goodsModel->isHas($goods_id)) {
            throw new Exception('产品不存在');
        }
        //查询原来的信息
        $goodsInfo = $goodsModel->where(['id' => $goods_id])->find();
        $data['update_time'] = time();
        $GoodsLog = new GoodsLog();
        $GoodsSkuServer = new ServiceGoodsSku();
        Db::startTrans();
        try {
            switch ($status) {
                case 1:  // 出售
                    if ($goodsInfo['sales_status'] != $status) {
                        $GoodsLog->mdfSpu($goodsInfo['spu'], ['sales_status' => $goodsInfo['sales_status']], ['sales_status' => $status]);
                        $this->pushSpuStatusQueue($goods_id, $status);
                    }
                    $data['sales_status'] = $status;
                    $data['publish_time'] = time();
                    $goodsModel->where(['id' => $goods_id])->update($data);
                    //sku表
                    $aSku = $goodsSkuModel->where(['goods_id' => $goods_id])->select();
                    foreach ($aSku as $skuInfo) {
                        if ($skuInfo->status != 1) {
                            // $GoodsLog->mdfSku($skuInfo->sku,['status'=>$skuInfo->status],['status'=>1]);
                            $old = $skuInfo->toArray();
                            $skuInfo->status = 1;
                            $skuInfo->save();
                            $GoodsSkuServer->afterUpdate($old, ['status' => 1]);
                            // $this->pushSkuStatusQueue($skuInfo->id, 1);
                        }
                    }
                    break;
                case 2:  // 停售
                    if ($goodsInfo['sales_status'] != $status) {
                        $GoodsLog->mdfSpu($goodsInfo['spu'], ['sales_status' => $goodsInfo['sales_status']], ['sales_status' => $status]);
                        $this->pushSpuStatusQueue($goods_id, $status);
                    }
                    $data['sales_status'] = $status;
                    $data['stop_selling_time'] = time();
                    $goodsModel->where(['id' => $goods_id])->update($data);
                    //sku表
                    $aSku = $goodsSkuModel->where(['goods_id' => $goods_id])->select();
                    foreach ($aSku as $skuInfo) {
                        if ($skuInfo->status != 2) {
                            //  $GoodsLog->mdfSku($skuInfo->sku,['status'=>$skuInfo->status],['status'=>2]);
                            $old = $skuInfo->toArray();
                            $skuInfo->status = 2;
                            $skuInfo->save();
//                            $this->pushSkuStatusQueue($skuInfo->id, 2);
                            $GoodsSkuServer->afterUpdate($old, ['status' => 2]);
                        }
                    }
                    break;
                case 4: // 卖完下架
                    if ($goodsInfo['sales_status'] != $status) {
                        $GoodsLog->mdfSpu($goodsInfo['spu'], ['sales_status' => $goodsInfo['sales_status']], ['sales_status' => $status]);
                        $this->pushSpuStatusQueue($goods_id, $status);
                    }
                    $data['sales_status'] = $status;
                    $goodsModel->where(['id' => $goods_id])->update($data);
                    //sku表
                    $aSku = $goodsSkuModel->where(['goods_id' => $goods_id])->select();
                    foreach ($aSku as $skuInfo) {
                        if ($skuInfo->status != 4) {
                            $old = $skuInfo->toArray();
                            //   $GoodsLog->mdfSku($skuInfo->sku,['status'=>$skuInfo->status],['status'=>4]);
                            $skuInfo->status = 4;
                            $skuInfo->save();
                            $GoodsSkuServer->afterUpdate($old, ['status' => 4]);
//                            $this->pushSkuStatusQueue($skuInfo->id, 4);
                        }
                    }

                    break;
                case 5: // 缺货
                    if ($goodsInfo['sales_status'] != $status) {
                        $GoodsLog->mdfSpu($goodsInfo['spu'], ['sales_status' => $goodsInfo['sales_status']], ['sales_status' => $status]);
                        $this->pushSpuStatusQueue($goods_id, $status);
                    }
                    $data['sales_status'] = $status;
                    $goodsModel->where(['id' => $goods_id])->update($data);
                    //sku表
                    $aSku = $goodsSkuModel->where(['goods_id' => $goods_id])->select();
                    foreach ($aSku as $skuInfo) {
                        if ($skuInfo->status != 5) {
                            $old = $skuInfo->toArray();
                            //   $GoodsLog->mdfSku($skuInfo->sku,['status'=>$skuInfo->status],['status'=>5]);
                            $skuInfo->status = 5;
                            $skuInfo->save();
//                            $this->pushSkuStatusQueue($skuInfo->id, 5);
                            $GoodsSkuServer->afterUpdate($old, ['status' => 5]);
                        }
                    }
                    break;
            }
            $GoodsLog->save($user_id, $goods_id);
            GoodsNotice::sendDown();
            Db::commit();

            Cache::handler()->hdel('cache:Goods', $goods_id);
            $skuList = $goodsSkuModel->where(['goods_id' => $goods_id])->field('id')->select();
            foreach ($skuList as $sku) {
                cache::handler()->hdel('cache:Sku', $sku['id']);
            }
            $stop_time = isset($data['stop_selling_time']) ? $data['stop_selling_time'] : $goodsInfo['stop_selling_time'];
            $publish_time = isset($data['publish_time']) ? $data['publish_time'] : $goodsInfo['publish_time'];
            $result = [
                'message' => '操作成功',
                'stop_selling_time' => $stop_time ? date('Y-m-d H:i:s', $stop_time) : '',
                'publish_time' => $publish_time ? date('Y-m-d H:i:s', $publish_time) : ''
            ];
            return $result;
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception('操作失败');
        }
    }

    /**
     * 操作产品sku
     * @param int $sku_id sku Id
     * @param int $status 状态
     */
    public function changeSkuStatus($sku_id, $status, $user_id)
    {
        $goodsSkuModel = new GoodsSku();
        $data['update_time'] = time();
        $aSku = $goodsSkuModel->where('id', $sku_id)->find();
        if (!$aSku) {
            throw new Exception('该sku不存在');
        }
        $old = $aSku->toArray();
        $Goods = new Goods();
        $aGoods = $Goods->where('id', $aSku->goods_id)->find();
        if (!$aGoods) {
            throw new Exception('该sku对应的商品不存在');
        }
        $GoodsLog = new GoodsLog();
        $GoodsSkuServer = new ServiceGoodsSku();
        Db::startTrans();
        try {
            switch ($status) {
                case 1:  // 出售
                    if ($status != $aSku->status) {
                        $GoodsLog->mdfSku($aSku['sku'], ['status' => $aSku->status], ['status' => $status]);
                        $aSku->status = $status;
                        $aSku->save();
//                        $this->pushSkuStatusQueue($sku_id, $status);
                        $GoodsSkuServer->afterUpdate($old, ['status' => $status]);
                        $sales_status = 6;
                        $count = $aGoods->sku()->where('status', '<>', 1)->count();
                        if (!$count) {
                            $sales_status = 1;
                        }
                        if ($aGoods->sales_status != $sales_status) {
                            if ($aGoods['publish_time'] == 0) {
                                $aGoods->publish_time = time();
                            }
                            $GoodsLog->mdfSpu($aGoods->spu, $aGoods, ['sales_status' => $sales_status]);
                            $aGoods->sales_status = $sales_status;
                            $aGoods->save();
                            $this->pushSpuStatusQueue($aGoods->id, $sales_status);
                            $this->putPushList($aGoods->id);
                        }
                    }
                    break;
                case 2:  // 停售
                    if ($status != $aSku->status) {
                        $GoodsLog->mdfSku($aSku['sku'], ['status' => $aSku->status], ['status' => $status]);
                        $aSku->status = $status;
                        $aSku->save();
//                        $this->pushSkuStatusQueue($sku_id, $status);
                        $GoodsSkuServer->afterUpdate($old, ['status' => $status]);
                        $count = $aGoods->sku()->where('status', '<>', $status)->count();
                        if (!$count) {
                            if ($aGoods->sales_status != 2) {
                                $GoodsLog->mdfSpu($aGoods->spu, ['sales_status' => $aGoods->sales_status], ['sales_status' => 2]);
                                $aGoods->sales_status = 2;
                                $aGoods->platform = 0;
                                $aGoods->save();
                                $this->pushSpuStatusQueue($aGoods->id, 2);
                                $this->putPushList($aGoods->id);
                            }
                        } else {
                            if ($aGoods->sales_status != 6) {
                                $GoodsLog->mdfSpu($aGoods->spu, ['sales_status' => $aGoods->sales_status], ['sales_status' => 6]);
                                $aGoods->sales_status = 6;
                                $aGoods->save();
                                $this->pushSpuStatusQueue($aGoods->id, 2);
                                $this->putPushList($aGoods->id);
                            }
                        }
                    }
                    break;
                case 4: // 卖完下架
                    if ($status != $aSku->status) {
                        $GoodsLog->mdfSku($aSku['sku'], ['status' => $aSku->status], ['status' => $status]);
                        $aSku->status = $status;
                        $aSku->save();
//                        $this->pushSkuStatusQueue($sku_id, $status);
                        $GoodsSkuServer->afterUpdate($old, ['status' => $status]);
                    }
                    break;
                case 5: // 缺货
                    if ($status != $aSku->status) {
                        $GoodsLog->mdfSku($aSku['sku'], ['status' => $aSku->status], ['status' => $status]);
                        $aSku->status = $status;
                        $aSku->save();
//                        $this->pushSkuStatusQueue($sku_id, $status);
                        $GoodsSkuServer->afterUpdate($old, ['status' => $status]);
                    }
                    break;
            }
            $GoodsLog->save($user_id, $aGoods['id']);
            GoodsNotice::sendDown();
            Db::commit();
            cache::handler()->hdel('cache:Sku', $sku_id);
            Cache::handler()->hdel('cache:Goods', $aSku->goods_id);
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception('操作失败');
        }
    }

    public function pushSpuStatusQueue($goods_id, $status)
    {
        $data = [
            'id' => $goods_id,
            'status' => $status,
            'type' => 1
        ];
        $queu1 = new CommonQueuer(AliexpressLocalSellStatus::class);
        $queu2 = new CommonQueuer(WishLocalSellStatus::class);
        $queu3 = new CommonQueuer(PandaoLocalSellStatus::class);
        // $data = json_encode($data);
        $queu1->push($data);
        $queu2->push($data);
        $queu3->push($data);
        return true;
    }

    public function pushSkuStatusQueue($sku_id, $status)
    {
        $data = [
            'id' => $sku_id,
            'status' => $status,
            'type' => 2
        ];
        $queu1 = new CommonQueuer(AliexpressLocalSellStatus::class);
        $queu2 = new CommonQueuer(WishLocalSellStatus::class);
        $queu3 = new CommonQueuer(PandaoLocalSellStatus::class);
        // $data = json_encode($data);
        $queu1->push($data);
        $queu2->push($data);
        $queu3->push($data);
        return true;
    }


    /**
     * 检查sku的缩略图存在与否
     * @param string $img_url sku Id
     */
    private static function checkGoodsUrl($img_url)
    {
        $img_content = file_get_contents($img_url);
        return $img_content;
    }

    /** 根据一定条件查询 goods表
     * @param array $condition
     * @return array
     */
    public static function getGoodsByCondition($condition)
    {
        $goodsModel = new Goods();
        if (!$condition) {
            return [];
        }
        return $goodsModel->where($condition)->select();
    }

    /**
     * 获取sku 重量 尺寸信息
     * @param int $id sku id
     * @return array
     */
    public function getSkuCheckInfo($id)
    {
        $skuInfo = Cache::store('goods')->getSkuInfo($id);
        if (!$skuInfo) {
            throw new Exception('资源不存在');
        }
        $goodsInfo = Cache::store('goods')->getGoodsInfo($skuInfo['goods_id']);
        $result['weight'] = $skuInfo['weight'] ? $skuInfo['weight'] : $goodsInfo['weight'];
        $result['length'] = $skuInfo['length'] / 10;
        $result['height'] = $skuInfo['height'] / 10;
        $result['width'] = $skuInfo['width'] / 10;
        return $result;
    }

    /**
     * 获取产品比对资料
     * @param int sku_id
     * @return array
     */
    public function getComparisonInfo($id)
    {
        $result = $this->getSkuCheckInfo($id);
        $result['pictures'] = [];
        $pictures = GoodsGallery::where(['sku_id' => $id])->field('path')->order('sort asc')->select();
        foreach ($pictures as $picture) {
            $result['pictures'][] = GoodsImage::getThumbPath($picture['path']);
        }
        return $result;
    }

    /**
     * 更新sku 重量 尺寸信息
     * @param int $id sku id
     * @return array
     */
    public function updateSkuCheckInfo($id, $data)
    {
        $skuInfo = Cache::store('goods')->getSkuInfo($id);
        if (!$skuInfo) {
            throw new Exception('资源不存在');
        }
        if (!isset($data['weight']) || !$data['weight']) {
            throw new Exception('重量不能为空');
        }
        if ($skuInfo['check']) {
            throw new Exception('已审核过');
        }
        $updateInfo['weight'] = $data['weight'];
        $updateInfo['depth'] = $data['length'] * 10;
        $updateInfo['height'] = $data['height'] * 10;
        $updateInfo['width'] = $data['width'] * 10;
        $goodsInfo = Cache::store('goods')->getGoodsInfo($skuInfo['goods_id']);
        Db::startTrans();
        try {
            $Goods = new Goods();
            $Goods->allowField(true)->isUpdate(false)->save($updateInfo, ['id' => $goodsInfo['id']]);
            Cache::store('goods')->delGoodsInfo($goodsInfo['id']);
            if ($goodsInfo['same_weight']) {
                $lists = GoodsSku::where(['goods_id' => $goodsInfo['id']])->field('id')->select();
                foreach ($lists as $list) {
                    GoodsSku::where(['id' => $list['id']])->update(['check' => 1]);
                    Cache::store('goods')->delSkuInfo($list['id']);
                }
            } else {
                GoodsSku::where(['id' => $id])->update(['check' => 1, 'weight' => $updateInfo['weight']]);
                Cache::store('goods')->delSkuInfo($id);
            }
            Db::commit();
        } catch (Exception $ex) {
            Db::rollback();
            throw new Exception('更新失败');
        }

        return true;
    }

    /**
     * @title 获取SKU附属参数  日销量
     * @date 2017/07/20
     * @time 20:30
     * @author yangweiquan
     */
    public static function getSkuIncidentalParameter($params)
    {
        $result = ['status' => 0, 'message' => ''];
        $sku_id_arr = [];
        if (empty($params['sku_ids'])) {
            $result['message'] = "SKU ID表不能为空。";
            return $result;
        }

        $sku_id_arr = array_unique(explode(',', $params['sku_ids']));
        if (empty($sku_id_arr)) {
            $result['message'] = "SKU ID列表不能为空。";
            return $result;
        }
        if (empty($params['warehouse_id'])) {
            $params['warehouse_id'] = 0;
            //$result['message'] = "仓库ID不能为空。";
            //return $result;
        }
        foreach ($sku_id_arr as $temp_sku_id) {
            $skuInfo = Cache::store('goods')->getSkuInfo($temp_sku_id);
            if (!$skuInfo) {
                $result['message'] = "ID为{$temp_sku_id}的SKU不存在。";
                return $result;
            }
        }
        $purchaseProposal = new PurchaseProposal();
        $sku_list_with_parameter = [];
        foreach ($sku_id_arr as $sku_id) {
            $paramsList = [];
            $paramsList[$temp_sku_id]['daily_sale_number'] = 0;
            if ($params['warehouse_id']) {
                $paramsList[$temp_sku_id]['daily_sale_number'] = $purchaseProposal->getDailySale($sku_id, $params['warehouse_id']);
            }
            $sku_list_with_parameter[] = $paramsList;
        }
        $result['status'] = 1;
        $result['message'] = 'OK';
        $result['list'] = $sku_list_with_parameter;
        return $result;
    }

    public static function getSkuIdByAlias($alias)
    {
        if (is_numeric($alias)) {
            if (strlen($alias) >= 6) {
                return $alias;
            }
        }
        $skuAlias = GoodsSkuAlias::where(['alias' => $alias])->field('sku_id')->find();
        return $skuAlias ? $skuAlias->sku_id : 0;
    }

    /**
     * 根据SKU中文名获取对应的状态数值
     * @param $name
     * @autor starzhan <397041849@qq.com>
     */
    public function getSkuValueByName($name)
    {
        $aMap = array_flip($this->sku_status);
        return isset($aMap[$name]) ? $aMap[$name] : 0;
    }

    /**
     *
     * @param $sku_id
     * @autor starzhan <397041849@qq.com>
     */
    public function getSkuStatusBySkuId($sku_id)
    {
        if (!$sku_id) {
            return '';
        }
        $aSkuInfo = Cache::store('goods')->getSkuInfo($sku_id);
        if ($aSkuInfo) {
            return isset($this->sku_status[$aSkuInfo['status']]) ? $this->sku_status[$aSkuInfo['status']] : '';
        }
        return '';
    }

    /**
     * 根据goods_id 构建出 推送管易的商品数据
     * @param $goods_id
     * @author starzhan <397041849@qq.com>
     */
    public function createGuanyiData($goods_id)
    {
        $goods = Cache::store('goods')->getGoodsInfo($goods_id);
        if (!$goods) {
            throw new Exception('该商品不存在[' . $goods_id . ']');
        }
        $goods['category'] = $this->mapCategory($goods['category_id']);
        $GoodsSku = new GoodsSku();
        $aGoogsSku = $GoodsSku->field('sku,weight,retail_price,cost_price,sku_attributes')->where('goods_id', $goods_id)->select();
        foreach ($aGoogsSku as $v) {
            $row = [];
            $row['sku'] = $v->sku;
            $row['weight'] = $v->weight;
            $row['retail_price'] = $v->retail_price;
            $row['cost_price'] = $v->cost_price;
            $row['sku_attributes'] = $v->sku_attributes;
            $goods['sku'][] = $row;
        }
        return $goods;
    }

    /**
     * 更新到管易
     * @param $goodsId
     * @param $data
     * @autor starzhan <397041849@qq.com>
     */
    public function updateGuanyi($data)
    {
        try {
            $category = str_replace(">", " > ", $data['category']);
            $service = new GuanYiWarehouse();
            $code = $service->getCode();
            $goods = new ProductType([
                'owcode' => $code,
                'owname' => '利朗达',
                'cacode' => str_replace('&', ' ', $category),
                'itemna' => str_replace('&', ' ', $data['name']),
                'itemno' => $data['id'],
                'shname' => str_replace('&', ' ', $data['name']),
                'clientno' => $code
            ]);
            $skuLists = $data['sku'];
            $attribute_1 = Cache::store('attribute')->getAttributeWms(1);
            $attribute_2 = Cache::store('attribute')->getAttributeWms(100); //合集尺寸
            foreach ($skuLists as $skuList) {
                $skuData = [
                    'itemno' => $data['id'],
                    'stcode' => $skuList['sku'],
                    'stname' => ' ',
                    'brcode' => $skuList['sku'],
                    'length' => $data['depth'],
                    'wide' => $data['width'],
                    'high' => $data['height'],
                    'weight' => $skuList['weight'] == 0 ? $data['weight'] / 1000 : $skuList['weight'] / 1000,
                    'gweight' => $skuList['weight'] == 0 ? $data['weight'] / 1000 : $skuList['weight'] / 1000,
                    'FirstPrice' => floatval($skuList['retail_price']),
                    'CostPrice' => floatval($skuList['cost_price'])
                ];
                $sku_attributes = json_decode($skuList['sku_attributes'], true);
                $attr_name_arr = [];
                if (isset($sku_attributes['attr_1']) && isset($attribute_1['value'][$sku_attributes['attr_1']])) {
                    $skuData['color'] = $attribute_1['value'][$sku_attributes['attr_1']]['value'];
                    $skuData['colorName'] = $attribute_1['value'][$sku_attributes['attr_1']]['value'];
                    $skuData['colorCode'] = $attribute_1['value'][$sku_attributes['attr_1']]['code'];
                    $attr_name_arr[] = explode('|', $skuData['color'])[0];
                }
                if (isset($sku_attributes['attr_2']) && isset($attribute_2['value'][$sku_attributes['attr_2']])) {
                    $skuData['size'] = $attribute_2['value'][$sku_attributes['attr_2']]['value'];
                    $skuData['sizeName'] = $attribute_2['value'][$sku_attributes['attr_2']]['value'];
                    $attr_name_arr[] = explode('|', $skuData['size'])[0];;
                }
                if (!empty($attr_name_arr)) {
                    $skuData['stname'] = implode('、', $attr_name_arr);
                }
                $sku = new SkuType($skuData);
                $goods->detail[] = $sku;
            }
            $result = $service->createSKUs([$goods]);
            if ($result['Result'] == 'false') {
                $result = json_decode($result['CancelReason'], true);
                throw new Exception($result['ErrMsg']);
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public static function createExportData($spu, $sku)
    {
        return [
            'spu' => $spu,
            'name' => '',
            'titleLenMoreThan100' => '否',
            'description' => '',
            'errorDescription' => '正确',
            'tag' => '',
            'sku' => $sku,
            'color' => '',
            'size' => '',
            'quantity' => '9999',
            'price' => '',
            'MSRP' => '',
            'Shipping' => '',
            'ShippingTime' => '15-45',
            'weight' => '',
            'length' => '',
            'width' => '',
            'height' => '',
            'hs_code' => '',
            'thumb' => '',
            'variantThumb' => '',
            'thumb0' => '',
            'thumb1' => '',
            'thumb2' => '',
            'thumb3' => '',
            'thumb4' => '',
            'thumb5' => '',
            'thumb6' => '',
            'thumb7' => '',
            'thumb8' => '',
            'thumb9' => '',
            'thumb10' => ''
        ];
    }

    private static function getExportData($ids)
    {
        try {
            $Goods = new Goods();
            $fields = "id,spu,name,thumb,hs_code";
            $oGoods = $Goods->where('id', 'in', $ids)->field($fields)->select();
            if ($oGoods) {
                $aGoods = [];
                foreach ($oGoods as $v) {
                    $aGoods[$v->id] = $v->toArray();
                }
                $GoodsSku = new GoodsSku();
                $oSku = $GoodsSku->where('goods_id', 'in', $ids)->field('id,goods_id,sku,thumb,length,width,height,weight,market_price')->select();
                $GoodsLang = new GoodsLang();
                $oLang = $GoodsLang->where('goods_id', 'in', $ids)->where('lang_id', 2)->field('goods_id,description')->select();
                foreach ($oLang as $v) {
                    $aGoods[$v->goods_id]['description'] = $v->description;
                }
                $GoodsGallery = new GoodsGallery();
                $oImg = $GoodsGallery->where('goods_id', 'in', $ids)->field("goods_id,path,sku_id")->select();
                $aSkuImg = [];
                $aGoodsImg = [];
                if ($oImg) {
                    foreach ($oImg as $v) {
                        if ($v->sku_id) {
                            if (isset($aSkuImg[$v->sku_id]) && count($aSkuImg[$v->sku_id]) >= 10) {
                                continue;
                            }
                            $aSkuImg[$v->sku_id][] = GoodsImage::getThumbPath($v->path);

                        } else {
                            if (isset($aGoodsImg[$v->goods_id]) && count($aGoodsImg[$v->goods_id]) >= 10) {
                                continue;
                            }
                            $aGoodsImg[$v->goods_id][] = GoodsImage::getThumbPath($v->path);
                        }
                    }
                }
                $aSku = [];
                if ($oSku) {
                    foreach ($oSku as $k => $v) {
                        $aGood = $aGoods[$v->goods_id];
                        $row = self::createExportData($aGood['spu'], $v->sku);
                        $v = $v->toArray();
                        $row['name'] = $aGood['name'];
                        $row['description'] = isset($aGood['description']) ? $aGood['description'] : '';
                        $row['thumb'] = $aGood['thumb'];
                        $row['hs_code'] = $aGood['hs_code'];
                        $row['variantThumb'] = $v['thumb'];
                        $row['length'] = $v['length'];
                        $row['width'] = $v['width'];
                        $row['height'] = $v['height'];
                        $row['weight'] = $v['weight'];
                        $row['MSRP'] = $v['market_price'];
                        if ($k == 0) {
                            $aRowGoodsImg = isset($aGoodsImg[$v['goods_id']]) ? $aGoodsImg[$v['goods_id']] : [];
                            $aRowGoodsSkuImg = isset($aSkuImg[$v['id']]) ? $aSkuImg[$v['id']] : [];
                            $aTmpImg = array_merge($aRowGoodsImg, $aRowGoodsSkuImg);
                            $aTmpImg = array_slice($aTmpImg, 0, 10);
                        } else {
                            $aRowGoodsSkuImg = isset($aSkuImg[$v['goods_id']]) ? $aSkuImg[$v['goods_id']] : [];
                            $aTmpImg = array_slice($aRowGoodsSkuImg, 0, 10);
                        }
                        foreach ($aTmpImg as $i => $img) {
                            $row['thumb' . $i] = $img;
                        }
                        $aSku[] = $row;

                    }
                }
                return $aSku;
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }

    }

    /**
     * 供应商导出功能
     * @param array $lists
     */
    public static function export($ids)
    {

        try {
            $header = [
                ['title' => 'Parent Unique ID', 'key' => 'spu', 'width' => 10],
                ['title' => '*Product Name', 'key' => 'name', 'width' => 35],
                ['title' => 'Description', 'key' => 'description', 'width' => 15],
                ['title' => '*Tags', 'key' => 'tag', 'width' => 25],
                ['title' => '*Unique ID', 'key' => 'sku', 'width' => 20],
                ['title' => 'Color', 'key' => 'color', 'width' => 20],
                ['title' => 'Size', 'key' => 'size', 'width' => 25],
                ['title' => '*Quantity', 'key' => 'quantity', 'width' => 20],
                ['title' => '*Price', 'key' => 'price', 'width' => 40],
                ['title' => 'MSRP', 'key' => 'MSRP', 'width' => 20],
                ['title' => '*Shipping', 'key' => 'Shipping', 'width' => 20],
                ['title' => 'Shipping Time(enter without " ", just the estimated days )', 'key' => 'ShippingTime', 'width' => 20],
                ['title' => 'Shipping Weight', 'key' => 'weight', 'width' => 20],
                ['title' => 'Shipping Length', 'key' => 'length', 'width' => 20],
                ['title' => 'Shipping Width', 'key' => 'width', 'width' => 20],
                ['title' => 'Shipping Height', 'key' => 'height', 'width' => 20],
                ['title' => 'HS Code', 'key' => 'hs_code', 'width' => 20],
                ['title' => '*Product Main Image URL', 'key' => 'thumb', 'width' => 20],
                ['title' => 'Variant Main Image URL', 'key' => 'variantThumb', 'width' => 20],
                ['title' => 'Extra Image URL', 'key' => 'thumb0', 'width' => 20],
                ['title' => 'Extra Image URL 1', 'key' => 'thumb1', 'width' => 20],
                ['title' => 'Extra Image URL 2', 'key' => 'thumb2', 'width' => 20],
                ['title' => 'Extra Image URL 3', 'key' => 'thumb3', 'width' => 20],
                ['title' => 'Extra Image URL 4', 'key' => 'thumb4', 'width' => 20],
                ['title' => 'Extra Image URL 5', 'key' => 'thumb5', 'width' => 20],
                ['title' => 'Extra Image URL 6', 'key' => 'thumb6', 'width' => 20],
                ['title' => 'Extra Image URL 7', 'key' => 'thumb7', 'width' => 20],
                ['title' => 'Extra Image URL 8', 'key' => 'thumb8', 'width' => 20],
                ['title' => 'Extra Image URL 9', 'key' => 'thumb9', 'width' => 20],
                ['title' => 'Extra Image URL 10', 'key' => 'thumb10', 'width' => 20],
            ];
            $lists = self::getExportData($ids);
            $file = [
                'name' => '导出joom商品',
                'path' => 'goods'
            ];
            $ExcelExport = new DownloadFileService();
            return $ExcelExport->exportCsv($lists, $header, $file);

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 获取商品修图要求
     * @param $goods_id
     * @author starzhan <397041849@qq.com>
     */
    public function getImgRequirement($goods_id)
    {
        $GoodsImgRequirement = new GoodsImgRequirement();
        $row = $GoodsImgRequirement->where('goods_id', $goods_id)->find();
        if ($row) {
            return $row->toArray();
        }
        throw new Exception('没有对应的修图要求');
    }

    public function saveImgRequirement($goods_id, $params)
    {
        $GoodsImgRequirement = new GoodsImgRequirement();
        $row = $GoodsImgRequirement->where('goods_id', $goods_id)->find();
        if ($row) {
            $row->ps_requirement = $params['img_requirement'];
            $row->save();
        } else {
            $data = [
                'goods_id' => $goods_id,
                'is_photo' => 0,
                'ps_requirement' => $params['img_requirement'],
                'create_time' => time(),
                'photo_remark' => '',
                'undisposed_img_url' => '',
            ];
            $ValidateGoodsImgRequirement = new ValidateGoodsImgRequirement();
            $flag = $ValidateGoodsImgRequirement->check($data);
            if ($flag === false) {
                throw new Exception($ValidateGoodsImgRequirement->getError());
            }
            $GoodsImgRequirement->allowField(true)->isUpdate(false)->save($data);
        }
        return ['message' => '修改成功'];
    }

    /**
     * @title sku2id
     * @param $sku
     * @return int|mixed
     * @author starzhan <397041849@qq.com>
     */
    public static function sku2id($sku)
    {
        $ServiceGoodsSku = new ServiceGoodsSku();
        return $ServiceGoodsSku->getSkuIdBySku($sku);
    }

    /**
     * @title 获取sku列表
     * @param array $sku_ids
     * @return array
     */
    public static function getSkuListByIds($sku_ids)
    {
        $result = [];
        foreach ($sku_ids as $sku_id) {
            $sku_info = Cache::store('goods')->getSkuInfo($sku_id);
            $temp['sku_id'] = $sku_id;
            $temp['sku'] = $sku_info['sku'];
            $temp['thumb'] = $sku_info['thumb'] ? GoodsImage::getThumbPath($sku_info['thumb']) : '';
            $temp['spu_name'] = $sku_info['spu_name'];
            $result[] = $temp;
        }
        return $result;
    }

    public function getGoodsAndSkuAttrBySpu($spu, $lang = 'en')
    {
        $result = [];
        $goodModel = new Goods();
        //先找出lang_ids
        $langs = Lang::field('id,name')->limit(30)->select();

        //先找出语言选项；
        $lang_id = 2;
        foreach ($langs as $val) {
            if (strcasecmp($val['name'], $lang) === 0) {
                $lang_id = $val['id'];
                break;
            }
        }

        $goodsInfo = $goodModel->alias('g')
            ->join(['goods_lang' => 'l'], 'g.id=l.goods_id')
            ->where(['g.spu' => $spu, 'l.lang_id' => $lang_id])
            ->field('g.id,g.name,g.category_id,g.brand_id,l.title,l.description,selling_point')
            ->find();

        //如果当前语言没有描述，且不是英文站点的，则找英文站点的出来；
        if (empty($goodsInfo) && $lang_id != 2) {
            $goodsInfo = $goodModel->alias('g')
                ->join(['goods_lang' => 'l'], 'g.id=l.goods_id')
                ->where(['g.spu' => $spu, 'l.lang_id' => 2])
                ->field('g.id,g.name,g.category_id,g.brand_id,l.title,l.description,selling_point')
                ->find();
        }
        //以上站点都没有，就找中文站点的
        if (empty($goodsInfo)) {
            $goodsInfo = $goodModel->alias('g')
                ->join(['goods_lang' => 'l'], 'g.id=l.goods_id', 'left')
                ->where(['g.spu' => $spu, 'l.lang_id' => 1])
                ->field('g.id,g.name,g.category_id,g.brand_id,l.title,l.description,selling_point')
                ->find();
        }

        if (!$goodsInfo) {
            $goodsInfo = $goodModel->alias('g')
                ->join(['goods_lang' => 'l'], 'g.id=l.goods_id', 'left')
                ->where(['g.spu' => $spu])
                ->field('g.id,g.name,g.category_id,g.brand_id,l.title,l.description,selling_point')
                ->find();
        }

        if (!$goodsInfo) {
            throw new Exception('SPU：' . $spu . '不存在');
        }

        //带出amazon五点；
        $point = ['', '', '', '', ''];
        $selling_poing = json_decode($goodsInfo->selling_point, true);
        if (is_array($selling_poing)) {
            foreach ($point as $key => $val) {
                $point[$key] = $selling_poing['amazon_point_' . ($key + 1)] ?? '';
            }
        }

        $result['goods_id'] = $goodsInfo->id;
        $result['goods_name'] = (string)$goodsInfo->name;
        $result['goods_title'] = (string)$goodsInfo->title;
        $result['category_name'] = $this->mapCategory($goodsInfo->category_id);
        $result['brand'] = $this->getBrandById($goodsInfo->brand_id);
        $result['description'] = (string)$goodsInfo->description;
        $result['selling_point'] = $point;
        $result['sku_list'] = [];
        $aSku = GoodsSku::where(['goods_id' => $goodsInfo->id, 'status' => ['<>', 2]])->select();
        foreach ($aSku as $v) {
            $attr = json_decode($v['sku_attributes'], true);
            $aAttr = self::getAttrbuteInfoBySkuAttributes($attr, $goodsInfo->id);
            $row = [];
            $row['sku_id'] = $v['id'];
            $row['sku'] = $v['sku'];
            $row['attr'] = $aAttr;
            $result['sku_list'][] = $row;
        }
        return $result;
    }

    /**
     * @title 获取sku打印标签
     * @param $sku_id
     * @author starzhan <397041849@qq.com>
     */
    public function getSkuLabel($ids, $is_band_area = 0, $user_id, $warehouse_id = 2)
    {
        $result = [];
        $WarehouseCargoGoods = new WarehouseCargoGoods();
        foreach ($ids as $sku_id) {
            $skuInfo = Cache::store('goods')->getSkuInfo($sku_id);
            if (!$skuInfo) {
                throw new Exception('该sku不存在');
            }
            $goods_id = $skuInfo['goods_id'];
            $skuInfo = Cache::store('goods')->getSkuInfo($sku_id);
            $aGoods = Cache::store('goods')->getGoodsInfo($goods_id);
            if (!$aGoods) {
                throw new Exception('该sku对应的商品不存在');
            }
            $row = [];
            $row['spu'] = $aGoods['spu'];
            $row['sku'] = $skuInfo['sku'];
            $row['sku_alias'] = implode('、', GoodsSkuAliasServer::getAliasBySkuId($sku_id));
            $row['category'] = $this->mapCategory($aGoods['category_id']);
            $row['brand'] = $this->getBrandById($aGoods['brand_id']);
            $row['properties'] = $this->getProTransPropertiesTxt($aGoods['transport_property']);
            $row['name_cn'] = $aGoods['name'];
            $row['goods_size'] = $skuInfo['length'] . "*" . $skuInfo['width'] . "*" . $skuInfo['height'] . "(mm)";
            $row['sku_weight'] = $skuInfo['weight'];
            $row['sku_id'] = $sku_id;
            $row['job_number'] = $user_id;
            $row['date'] = date('yWN');
            $attr = self::getAttrbuteInfoBySkuAttributes(json_decode($skuInfo['sku_attributes'], true), $skuInfo['goods_id']);
            $row['color'] = '';
            foreach ($attr as $attrInfo) {
                if ($attrInfo['name'] == 'color') {
                    $row['color'] = $attrInfo['value'];
                }
            }
            if ($is_band_area) {
                $row['warehouse_cargo_code'] = $WarehouseCargoGoods->getSkuCargoCode($warehouse_id, $sku_id);
            }
            $result['print_data'][] = $row;
        }
        $result['default_tmp_id'] = 39;
        return $result;
    }

    public function batchCatchPhoto($ids)
    {
        try {
            foreach ($ids as $goods_id) {
                try {
                    $goods = Cache::store('goods')->getGoodsInfo($goods_id);
                    if (empty($goods)) {
                        throw new Exception('产品不存在！');
                    }
                    $data = [
                        'goods_id' => $goods_id,
                        'spu' => $goods['spu']
                    ];
                    $queue = new UniqueQueuer(SyncGoodsImgQueue::class);
                    $queue->push($data);
                } catch (Exception $ex) {
                    throw new Exception($ex->getMessage());
                }
            }
            return ['message' => '抓取成功'];
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function pushIrobotbox($ids)
    {
        try {
            foreach ($ids as $goods_id) {
                try {

                    $queue = new UniqueQueuer(GoodsPushIrobotbox::class);
                    $queue->push($goods_id);
                } catch (Exception $ex) {
                    throw new Exception($ex->getMessage());
                }
            }
            return ['message' => '加入队列成功！'];
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function goodsId2Spu($goods_id)
    {
        $aGoodsId = [];
        if (!is_array($goods_id)) {
            $aGoodsId[] = $goods_id;
        } else {
            $aGoodsId = $goods_id;
        }
        $result = [];
        foreach ($aGoodsId as $gid) {
            $cache = Cache::store('goods')->getGoodsInfo($gid);
            if ($cache) {
                $result[$gid] = $cache['spu'];
            }
        }
        return $result;
    }

    public function spu2goodsId($spu)
    {
        $aSpu = [];
        if (!is_array($spu)) {
            $aSpu[] = $spu;
        } else {
            $aSpu = $spu;
        }
        $result = [];
        foreach ($aSpu as $s) {
            $aGoods = Goods::where('spu', $s)->find();
            if ($aGoods) {
                $result[$s] = $aGoods['id'];
            }
        }
        return $result;
    }

    public function getPropertyByaSkuId($aSkuId)
    {
        $property = 0;
        foreach ($aSkuId as $skuId) {
            $aSkuInfo = Cache::store('goods')->getSkuInfo($skuId);
            if (!$aSkuInfo) {
                continue;
            }
            $aGoodsInfo = Cache::store('goods')->getGoodsInfo($aSkuInfo['goods_id']);
            if (!$aGoodsInfo) {
                continue;
            }
            $property = $property ? ($property | $aGoodsInfo['transport_property']) : $aGoodsInfo['transport_property'];
        }
        return $property;
    }

    /**
     * @title 根据分类拿goods_id
     * @param $categoryId
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getGoodsIdByCategoryId($categoryId)
    {
        $categoryId = intval($categoryId);
        $aCategory = Cache::store('category')->getCategoryTree();
        $aCategory = isset($aCategory[$categoryId]) ? $aCategory[$categoryId] : [];
        if ($aCategory) {
            $searchIds = [$categoryId];
            if ($aCategory['child_ids']) {
                $searchIds = array_merge($searchIds, $aCategory['child_ids']);
            }
            $Goods = new Goods();
            $result = [];
            $ret = $Goods->where('category_id', 'in', $searchIds)->field('id')->select();
            foreach ($ret as $v) {
                $result[] = $v->id;
            }
            return $result;
        }
        return [];
    }

    /**
     * @return array
     */
    public function getPlatformSaleAttr($value)
    {
        return isset($this->platform_sale_status[$value]) ? $this->platform_sale_status[$value] : '';
    }

    /**
     * @title 根据id获取详情
     * @param $id
     * @return mixed
     * @author starzhan <397041849@qq.com>
     */
    public function getGoodsInfo($id)
    {
        return Cache::store('goods')->getGoodsInfo($id);
    }

    /**
     * @title 保存抓图路径
     * @param $goods_id
     * @param $path
     * @author starzhan <397041849@qq.com>
     */
    public function saveThumbPath($goods_id, $path)
    {
        Goods::where('id', $goods_id)->update(['thumb_path' => $path]);
        Cache::store('goods')->delGoodsInfo($goods_id);

    }

    /**
     * @title 设置采购员
     * @param $aGoodsId
     * @param $purchaserId
     * @return array
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function setPurchaserId($aGoodsId, $purchaserId)
    {
        $aGoods = Goods::where('id', 'in', $aGoodsId)->select();
        if (!$aGoods) {
            throw new Exception('商品不存在');
        }
        $aE = [];
        foreach ($aGoods as $aGoodInfo) {
            Db::startTrans();
            try {
                $goodsLog = new GoodsLog();
                $oldPurchaserId = $aGoodInfo->purchaser_id;
                $aGoodInfo->purchaser_id = $purchaserId;
                $aGoodInfo->save();
                $aGoodInfo->clearCache();
                $goodsLog->mdfSpu($aGoodInfo->spu,
                    ['purchaser_id' => $oldPurchaserId],
                    ['purchaser_id' => $purchaserId]);
                $goodsLog->save(Common::getUserInfo()['user_id'], $aGoodInfo->id);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $aE[] = $e->getMessage();
            }
        }
        return ['message' => '设置成功！'];
    }

    /**
     * @title 根据供应商id 返回商品id
     * @param $supplier_id
     * @author starzhan <397041849@qq.com>
     */
    public function getGoodsIdBySupplierId($supplier_id)
    {
        $result = [];
        $aGoods = Goods::where('supplier_id', $supplier_id)->select();
        foreach ($aGoods as $goodsInfo) {
            $result[] = $goodsInfo['id'];
        }
        return $result;
    }

    public function getGoodsIdBySpuOrAlias($spu)
    {
        $aSpu = [];
        if (!is_array($spu)) {
            $aSpu[] = $spu;
        } else {
            $aSpu = $spu;
        }
        $result = [];
        foreach ($aSpu as $s) {
            $aGoods = Goods::where('spu', $s)->find();
            if (!$aGoods) {
                $aGoods = Goods::where('alias', $s)->find();
            }
            if ($aGoods) {
                $result[$s] = $aGoods['id'];
            }
        }
        return $result;
    }


    /**
     * 下单时根据订单获取good_ids再获取物流商属性
     * @param int $goods_id
     * @return array [2,4,256,1024,8192]; //带电 [1,8,16,32,64,128,512,2048,4096,16384,32768,65536,131072];//不带电
     */
    public static function getPropertyByOrder($aGoodsId)
    {
        $ret = Goods::where('id', $aGoodsId)->field('transport_property')->find();
        $self = new self();
        $results = $self->getTransportProperies();
        $transport_property = [];
        foreach ($results as &$list) {
            if ($list['value'] & $ret['transport_property']) {
                $transport_property[] = $list['value'];
            }
        }
        return $transport_property;
    }

    public function getLang($goods_id)
    {
        $GoodsLang = new GoodsLang();
        return $GoodsLang->where('goods_id', $goods_id)->select();
    }

    /**
     * @title 根据采购员id获取所有商品id
     * @param $purchaser_id
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getGoodsIdsByPurchaserId($purchaser_id)
    {
        $aId = [];
        $aGoods = Goods::where('purchaser_id', $purchaser_id)
            ->field('id')
            ->where('status', 1)
            ->select();
        foreach ($aGoods as $v) {
            $aId[] = $v->id;
        }
        return $aId;
    }

    /**
     * @title 根据开发员获取商品信息
     * @param $developer_id
     * @author starzhan <397041849@qq.com>
     */
    public function getSupplierIdByDeveloperId($developer_id)
    {
        $field = 'supplier_id';
        $where['developer_id'] = ['in', $developer_id];
        $model = new Goods();
        $result = [];
        //$aGoods = $model->where('developer_id', $developer_id)->field($field)->select();
        $aGoods = $model->where($where)->field($field)->select();
        foreach ($aGoods as $goodsInfo) {
            $supplier_id = $goodsInfo->supplier_id;
            if ($supplier_id) {
                $result[] = $supplier_id;
            }
        }
        return $result;
    }

    public function pullCountDevelop($date = null)
    {
        if (!$date) {
            $date = date('Y-m-d');
        }
        $strTime = strtotime($date);
        $Model = new ReportGoodsByDeveloper();
        $field = 'quantity,developer_id';
        $reset = $Model->field($field)->Where('dateline', $strTime)->select();
        $year = date('Y', $strTime);
        $month = date('m', $strTime);
        $MonthlyTargetAmountService = new MonthlyTargetAmountService();
        $err = [];
        foreach ($reset as $v) {
            if (!$v['developer_id']) {
                continue;
            }
            $ret = $MonthlyTargetAmountService
                ->addDevelopment($v['developer_id'], $v['quantity'], $year, $month);
            if ($ret !== true) {
                $err[] = "({$v['developer_id']})" . $ret['message'];
            }
        }
        if ($err) {
            throw new Exception(implode("\n", $err));
        }
    }

    public function countDevelop($date = null)
    {
        if (!$date) {
            $date = date('Y-m-d');
        }
        $strTime = strtotime($date);
        $endTime = $strTime + 86400;
        $Model = new Goods();
        $reset = $Model->alias('g')
            ->field("count(DISTINCT g.id) as num,g.developer_id,g.channel_id,u.department_id")
            ->join('department_user_map u', 'u.user_id = g.developer_id ', 'left')
            ->where('g.publish_time', '>=', $strTime)
            ->where('g.publish_time', '<', $endTime)
            ->group('g.developer_id')
            ->select();
        $err = [];
        ReportGoodsByDeveloper::where('dateline', $strTime)->delete();
        foreach ($reset as $v) {
            if (!$v['developer_id']) {
                continue;
            }
            $row = [];
            $row['dateline'] = $strTime;
            $row['quantity'] = $v['num'];
            $row['channel_id'] = $v['channel_id'];
            $row['developer_id'] = $v['developer_id'];
            $row['department_id'] = $v['department_id'] ?? 0;
            $Model = new ReportGoodsByDeveloper();
            $Model->isUpdate(false)->allowField(true)->save($row);
        }
        if ($err) {
            throw new Exception(implode("\n", $err));
        }
    }

    /**
     * @title 根据供应商修改
     * @param $supplier_id
     * @param $purchaser_id
     * @author starzhan <397041849@qq.com>
     */
    public function updatePurchaserIdBySupplierId($supplier_id, $purchaser_id, $user_id)
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $sql = "select id,purchaser_id,spu from goods where  supplier_id = {$supplier_id}";
        $Q = new Query();
        $a = $Q->query($sql, [], true, true);
        $time = time();
        $errSpu = [];
        while ($row = $a->fetch(PDO::FETCH_ASSOC)) {
            if ($row['purchaser_id'] != $purchaser_id) {
                Db::startTrans();
                try {
                    $model = new Goods();
                    $data = [];
                    $data['purchaser_id'] = $purchaser_id;
                    $data['update_time'] = $time;
                    $model->save($data, ['id' => $row['id']]);
                    Cache::store('goods')->delGoodsInfo($row['id']);
                    $GoodsLog = new GoodsLog();
                    $GoodsLog->mdfSpu($row['spu'], $row, ['purchaser_id' => $purchaser_id]);
                    $GoodsLog->save($user_id, $row['id'], '同步供应商采购员');
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    $errSpu[] = $row['spu'];
                }
            }
        }
        if ($errSpu) {
            throw new Exception(implode(',', $errSpu));
        }
    }

    /**
     * @title 替换采购员
     * @param $old_purchaser_id
     * @param $new_purchaser_id
     * @param $user_id
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function updatePurchaserIdByPurchaserId($old_purchaser_id, $new_purchaser_id, $user_id)
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $sql = "select id,purchaser_id,spu from goods where  purchaser_id = {$old_purchaser_id}";
        $Q = new Query();
        $a = $Q->query($sql, [], true, true);
        $time = time();
        $errSpu = [];
        while ($row = $a->fetch(PDO::FETCH_ASSOC)) {
            if ($row['purchaser_id'] != $new_purchaser_id) {
                Db::startTrans();
                try {
                    $model = new Goods();
                    $data = [];
                    $data['purchaser_id'] = $new_purchaser_id;
                    $data['update_time'] = $time;
                    $model->save($data, ['id' => $row['id']]);
                    $GoodsLog = new GoodsLog();
                    $GoodsLog->mdfSpu($row['spu'], $row, ['purchaser_id' => $new_purchaser_id]);
                    $GoodsLog->save($user_id, $row['id'], '同步供应商采购员');
                    Db::commit();
                    Cache::store('goods')->delGoodsInfo($row['id']);
                } catch (Exception $e) {
                    Db::rollback();
                    $errSpu[] = $row['spu'];
                }
            }
        }
        if ($errSpu) {
            throw new Exception(implode(',', $errSpu));
        }
    }

    /**
     * @title 获取供应商的spu数
     * @param $supplierId
     * @return int|string
     * @author starzhan <397041849@qq.com>
     */
    public function countBySupplierId($supplierId)
    {
        return Goods::where('supplier_id', $supplierId)
            ->where('status', 1)
            ->count();
    }

    public function getGoodsTortDescription($id, $page, $page_size)
    {
        $result = ['list' => []];
        $result['page'] = $page;
        $result['page_size'] = $page_size;
        $ModelGoodsTortDescription = new GoodsTortDescription();
        $result['count'] = $ModelGoodsTortDescription->where('goods_id', $id)->count();
        if ($result['count'] == 0) {
            return $result;
        }
        $ModelGoodsTortDescription = new GoodsTortDescription();
        $ret = $ModelGoodsTortDescription->where('goods_id', $id)
            ->field('id,channel_id,account_id,site_code,remark,tort_type,tort_time,create_id')
            ->page($page, $page_size)
            ->select();
        $result['list'] = $this->fillGoodsTortDescriptionData($ret);
        return $result;
    }

    private function fillGoodsTortDescriptionData($ret)
    {
        $result = [];
        foreach ($ret as $v) {
            $row = [];
            $row['id'] = $v->id;
            $row['channel_name'] = $v->channel_name;
            $row['site_code'] = $v->site_code;
            $row['account_name'] = $v->account_name;
            $row['remark'] = $v->remark;
            $row['tort_type'] = $v->tort_type;
            $row['tort_time'] = $v->tort_time;
            $userName = Cache::store('user')->getOneUserRealname($v->create_id);
            $row['creator'] = $userName;
            $result[] = $row;
        }
        return $result;
    }

    public function getGoodsTortDescriptionById($id)
    {
        $ModelGoodsTortDescription = new GoodsTortDescription();
        $row = $ModelGoodsTortDescription
            ->field('id,goods_id,channel_id,account_id,site_code,remark,tort_time,tort_type,email_content')
            ->where('id', $id)
            ->find();
        return $row;
    }

    public function saveGoodsTortDescription($goods_id, $param, $user_id)
    {
        $validate = new ValidateGoodsTortDescription();
        try {
            if (isset($param['id']) && $param['id']) {
                $param['goods_id'] = $goods_id;
                $flag = $validate->scene('edit')->check($param);
                if ($flag === false) {
                    throw new Exception($validate->getError());
                }
                $Model = new GoodsTortDescription();
                $id = $param['id'];
                unset($param['id']);
                $param['tort_type'] = TortImport::TYPE[$param['tort_type']];
                //存在邮件图片则需要验证
                if (isset($param['email_img'])) {
                    $imgPathList = json_decode($param['email_img'], true);
                    $validate = new ValidateGoodsTortDescription();
                    if (!$validate->scene('email')->check(['email_img' => $imgPathList])) {
                        throw new Exception($validate->getError());
                    }
                    unset($param['email_img']);
                }
                Db::startTrans();
                try {
                    $Model->allowField(true)->isUpdate(true)->save($param, ['id' => $id]);
                    //处理邮件有图片的情况
                    if (isset($imgPathList)) {
                        foreach ($imgPathList as &$v) {
                            $v['tort_id'] = $id;
                            $v['creator_id'] = $user_id;
                        }
                        $tortEmailAttachment = new TortEmailAttachment();
                        $tortEmailAttachment->saveAll($imgPathList);
                    }
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    throw new JsonErrorException($e->getMessage());
                }
            } else {

                $param['goods_id'] = $goods_id;
                $param['create_time'] = time();
                $param['create_id'] = $user_id;
                $flag = $validate->scene('insert')->check($param);
                if ($flag === false) {
                    throw new Exception($validate->getError());
                }
                $Model = new GoodsTortDescription();
                $param['tort_type'] = TortImport::TYPE[$param['tort_type']];

                //存在邮件图片则需要验证
                if (isset($param['email_img'])) {
                    $imgPathList = json_decode($param['email_img'], true);
                    $validate = new ValidateGoodsTortDescription();
                    if (!$validate->scene('email')->check(['email_img' => $imgPathList])) {
                        throw new Exception($validate->getError());
                    }
                    unset($param['email_img']);
                }
                Db::startTrans();
                try {
                    $Model->allowField(true)->isUpdate(false)->save($param);
                    //处理邮件有图片的情况
                    if (isset($imgPathList)) {
                        foreach ($imgPathList as &$v) {
                            $v['tort_id'] = $Model->id;
                            $v['creator_id'] = $user_id;
                        }
                        $tortEmailAttachment = new TortEmailAttachment();
                        $tortEmailAttachment->saveAll($imgPathList);
                    }
                    $goodsInfo = $this->getGoodsInfo($goods_id);
                    $OrderService = new OrderService();
                    $accountName = $OrderService->getAccountName($param['channel_id'], $param['account_id']);
                    GoodsNotice::sendTortDescription($goods_id, $goodsInfo, $param['channel_id'], $param['site_code'], $accountName, $param['remark']);
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    throw new JsonErrorException($e->getMessage());
                }
            }
            return ['message' => '保存成功'];
        } catch (Exception $ex) {
            throw $ex;
        }
    }


    /**
     * @title 移除侵权详情
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function removeGoodsTortDescription($id)
    {
        $model = new GoodsTortDescription();
        $model->where('id', $id)->delete();
        return ['message' => '删除成功'];

    }

    public function getGoodsTortDescriptionByGoodsId($aGoodsId)
    {
        $model = new GoodsTortDescription();
        $result = [];
        $ret = $model->alias('g')->join('user u', 'g.create_id = u.id', 'left')->where('g.goods_id', 'in', $aGoodsId)->field('g.id,g.goods_id,g.channel_id,g.account_id,g.site_code,g.remark,g.tort_time,g.tort_type,u.realname')->select();
        foreach ($ret as $v) {
            $result[$v['goods_id']][] = $v;
        }
        return $result;
    }

    /**
     * 拿那一天的所人商品
     * @param $day
     * @return array
     * 获取产品总数时，需增加以下过滤条件：
     * 1、产品分类：仓库耗材:id-193、LED工厂灯:id-197、男装:id-1、女装:id-7分类不参与分配（按照分类的第一大类过滤即可）
     * 2、侵权产品：存在侵权记录的产品不参与分配
     * 3、禁止上架：在亚马逊平台状态为禁止上架的不参与分配
     * 4、商品状态：停售状态的不参与分配
     */
    public function getAssignGoods($day, $channel_id = 2)
    {
        $ChannelDistribution = new ChannelDistribution();
        $channelSetting = $ChannelDistribution->read($channel_id);
        //1.找出不分配的分类；
        $categoryModel = new Category();
        $categoryMainIds = $channelSetting['ban_category'];
        $where = [];
        $canStatus = $channelSetting['product_status'];
        $where2 = '';
        if ($canStatus) {
            $where['sales_status'] = ['in', $canStatus];
            if (in_array(7, $canStatus)) {
                $where['id'] = ['not in', ' (select goods_id from goods_tort_description)'];
            }
            if (in_array(8, $canStatus)) {
                $where2 = "platform&{$channel_id}=$channel_id";
            }
        }
        if ($categoryMainIds) {
            $categoryIds = $categoryModel
                ->where(['pid' => ['in', $categoryMainIds]])
                ->whereOr('id', 'in', $categoryMainIds)
                ->column('id');
            $where['category_id'] = ['NOT IN', $categoryIds];
        }
        $where['publish_time'] = ['BETWEEN', [strtotime($day), strtotime($day) + 86399]];
        $Goods = new Goods();
        $goods = $Goods->where($where)->where($where2)->column('spu', 'id');
        return $goods;
    }

    /**
     * 筛选侵权表条件
     * @param $params
     * @return array
     */
    public function tortWhere($params)
    {
        $where = [];

        //批量SPU查询
        if (!empty($params['spu'])) {
            $spuArr = $arrValue = json_decode($params['spu'], true);
            if ($spuArr) {
                $where['spu'] = ['in', implode(',', $spuArr)];
            }
        }
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'goods_channel':
                    $where['goods.channel_id'] = ['eq', $params['snText']];
                    break;
                case 'tort_channel':
                    $where['goods_tort_description.channel_id'] = ['eq', $params['snText']];
                    break;
                default:
                    break;
            }
        }

        //新增平台下站点条件筛选
        if (isset($params['snType']) && isset($params['snText'])
            && !empty($params['snText']) && isset($params['siteCode'])
            && !empty($params['siteCode'])
        ) {
            $where['goods_tort_description.site_code'] = ['eq', $params['siteCode']];
        }

        //侵权类型筛选
        if (isset($params['tortType']) && isset($params['tortText']) && !empty($params['tortText'])) {
            switch ($params['tortType']) {
                case 'select':
                    $where['tort_type'] = ['eq', TortImport::TYPE[$params['tortText']]];
                    break;
                case 'text':
                    $where['tort_type'] = ['like', '%' . $params['tortText'] . '%'];
                    break;
                default:
                    break;
            }
        }

        //侵权时间 和 添加时间的 筛选
        if ($params['time_type'] == 1) {
            //侵权时间
            if (isset($params['start_time']) || isset($params['end_time'])) {

                if (!empty($params['start_time']) && !empty($params['end_time'])) {

                    $where['goods_tort_description.tort_time'] =
                        ['between', [strtotime($params['start_time']), strtotime($params['end_time']) + 86399]];

                } elseif (!empty($params['start_time']) && empty($params['end_time'])) {

                    $where['goods_tort_description.tort_time'] = ['egt', strtotime($params['start_time'])];

                } elseif (empty($params['start_time']) && !empty($params['end_time'])) {

                    $where['goods_tort_description.tort_time'] = ['elt', strtotime($params['end_time'])];

                }
            }
        } elseif ($params['time_type'] == 2) {
            //添加时间
            if (isset($params['start_time']) || isset($params['end_time'])) {

                if (!empty($params['start_time']) && !empty($params['end_time'])) {

                    $where['goods_tort_description.create_time'] =
                        ['between', [strtotime($params['start_time']), strtotime($params['end_time']) + 86399]];

                } elseif (!empty($params['start_time']) && empty($params['end_time'])) {

                    $where['goods_tort_description.create_time'] = ['egt', strtotime($params['start_time'])];

                } elseif (empty($params['start_time']) && !empty($params['end_time'])) {

                    $where['goods_tort_description.create_time'] = ['elt', strtotime($params['end_time'])];

                }
            }
        }

        //商品分类的筛选
        if (isset($params['category_id']) && !empty($params['category_id'])) {
            $where['category_id'] = $params['category_id'];
        }

        return $where;
    }

    /**
     * 获取侵权列表总条数
     * @param $where
     * @return mixed
     */
    public function getTortCount($where)
    {
        $tortModel = new GoodsTortDescription();
        $result = $tortModel
            ->join('goods', 'goods_tort_description.goods_id=goods.id', 'left')
            ->where($where)
            ->count();
        return $result;
    }

    /**
     * 获取侵权列表
     * @param $where
     * @param $fields
     * @param null $page
     * @param null $pageSize
     * @return false|\PDOStatement|string|\think\Collection
     * @throws Exception
     */
    public function getTortList($where, $fields, $page = null, $pageSize = null)
    {
        $tortModel = new GoodsTortDescription();
        $page = $page ?? 1;
        $pageSize = $pageSize ?? 10;

        $result = $tortModel
            ->join('goods', 'goods_tort_description.goods_id=goods.id', 'left')
            ->where($where)
            ->field($fields)
            ->page($page, $pageSize)
            ->order('create_time desc')
            ->select();
        $user = new User();
        $channels = (new \app\common\cache\driver\Channel())->getChannel();
        $OrderService = new OrderService();
        $temp = [];
        foreach ($channels as $k => $v) {
            $temp[$v['id']] = $v['title'];
        }

        foreach ($result as $key => $value) {
            $userInfo = $user->getUser($value['create_id']);
            $accountName = $OrderService->getAccountName($value['channel_id'], $value['account_id']);
            $result[$key]['tort_channel'] = $value['channel_id'] ? $temp[$value['channel_id']] : '';
            $result[$key]['goods_channel'] = $value['goods_channel_id'] ? $temp[$value['goods_channel_id']] : '';
            $result[$key]['tort_time'] = date('Y-m-d H:i:s', $value['tort_time']);
            $result[$key]['create'] = $userInfo['realname'] ?? '';
            $result[$key]['tort_account'] = $accountName ?? '';
            $goods = Goods::get($value['goods_id']);
            $result[$key]['category'] = is_null($goods) ? '' : $goods->category;
            if (isset($value['thumb'])) {
                $result[$key]['thumb'] = empty($value['thumb']) ? '' : GoodsImage::getThumbPath($value['thumb'], 0, 0);
            }
        }

        return $result;
    }

    public function countNotStoppedBySupplierId($supplier_id)
    {
        return Goods::where('supplier_id', $supplier_id)->where('sales_status', '<>', 2)->count();
    }
}