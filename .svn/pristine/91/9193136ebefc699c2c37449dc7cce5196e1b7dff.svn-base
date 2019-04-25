<?php

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/4
 * Time: 17:10
 */
class Goods extends Model
{
    protected $autoWriteTimestamp = true;

    /**
     * 初始化数据
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    // 出售状态
    private const SALES_STATUS = [
        1 => '在售',
        2 => '停售',
        3 => '待发布',
        4 => '卖完下架',
        5 => '缺货',
        6 => '部分在售',
    ];

    public const EVELOPER_DEPARTMENT_ID = [
        0 => 'AliExpress部',
        1 => 'Amazon部',
        2 => 'eBay部',
        3 => 'Wish部',
        4 => '服装事业部',
        5 => 'LED事业部',
        6 => '女装事业部'
    ];

    /** 获取产品销售类型信息
     * @param int $type
     * @return string
     */
    public function getSalesInfo($type = 0)
    {
        $info = [
            0 => '普通',
            1 => '组合',
            2 => '虚拟'
        ];
        return isset($info[$type]) ? $info[$type] : '普通';
    }

    /** 检测产品是否存在
     * @param int $goods_id
     * @return bool
     */
    public function isHas($goods_id = 0)
    {
        $result = $this->where(['id' => $goods_id])->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return true;
    }

    /** 获取产品
     * @return \think\model\Relation
     */
    public function method()
    {
        return $this->hasMany('GoodsSku', 'goods_id', 'id', [],
            'left')->field('id,goods_id,thumb,spu_name,sku, name,market_price,cost_price');
    }

    /**
     * @return array
     */
    public function getOne($goods_id = 0, $fields = "*")
    {
        //$result = $this->where(['$goods_id' => $goods_id])->find();
        $result = Db::table('goods')->where(['id' => $goods_id])->find();

        return $result;


    }

    /*
     * 获取商品属性
     */
    public function attribute()
    {
        return $this->hasMany('GoodsAttribute', 'goods_id', 'id', [], 'left');
    }

    /**
     * 商品相册
     */
    public function gallery()
    {
        return $this->hasMany('GoodsGallery', 'goods_id', 'id', [], 'left');

    }

    /*
     * 商品库存
     */
    public function inventoryHold()
    {
        return $this->hasMany('GoodsInventoryHold', 'goods_id', 'id');
    }

    /*
     * 商品信息，title，description
     * @params int $lang_id
     * @return array
     */
    public function lang($lang_id = 1)
    {
        return $this->hasOne('GoodsLang', 'goods_id', 'id');
    }

    /**
     * 商品来源（渠道）
     */
    public function sourceUrl()
    {
        return $this->hasOne('GoodsSourceUrl', 'goods_id', 'id');
    }


    /** 是否有使用了该单位
     * @param $unit
     * @return bool
     */
    public function hasUnit($unit)
    {
        $result = $this->where(['unit_id' => $unit])->find();
        if (empty($result)) {  //不存在
            return false;
        }
        return true;
    }

    /** 是否有使用了该包装材料
     * @param $packing
     * @return bool
     */
    public function hasPacking($packing)
    {
        $result = $this->where(['packing_id' => $packing])->find();
        if (empty($result)) {  //不存在
            return false;
        }
        return true;
    }

    /**查看是否存在当前数据
     * @param $packing
     * @return bool
     */
    public function checkIroGoods($data)
    {
        $rows = $this->where(array("spu" => $data['spu']))->find();
        if (!empty($rows)) {
            return $rows;
        } else {
            return array();
        }
    }

    public function sku()
    {
        return $this->hasMany(GoodsSku::class, 'goods_id', 'id');
    }

    public function getCategoryAttr($value, $data, $lang_id = 1)
    {
        if (isset($data['category_id'])) {
            $category_id = $data['category_id'];
            static $result = [];
            if (!isset($result[$category_id])) {
                $category_list = Cache::store('category')->getCategoryTree($lang_id);
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
        return '';
    }

    public function getBrandAttr($value, $data)
    {
        $aBrand = Cache::store('brand')->getBrand();
        foreach ($aBrand as $v) {
            if ($v['id'] == $data['brand_id']) {
                return $v['name'];
            }
        }
        return '';
    }

    public function getSupplierAttr($value, $data)
    {
        $user = Cache::store('Supplier')->getSupplier($data['supplier_id']);
        return $user['company_name'] ?? '';
    }

    public function getSalesStatusTxtAttr($value, $data)
    {
        return self::SALES_STATUS[$data['sales_status']] ?? '';
    }

    public function getDeveloperAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['developer_id']);
        return $user['realname'] ?? '';
    }

    public function getDeveloperWithJobNumberAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['developer_id']);
        if(!isset($user['realname'])){
            return '';
        }
        return $user['realname'] ? ($user['realname'] . '[' . $user['job_number'] . ']') : '';
    }

    public function getDevPlatform($value, $data)
    {
        return self::EVELOPER_DEPARTMENT_ID[$data['dev_platform_id']] ?? '';
    }

    public function getPurchaserAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['purchaser_id']);
        return $user['realname'] ?? '';
    }

    public function getPurchaserWithJobNumberAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['purchaser_id']);
        if(!isset($user['realname'])){
            return '';
        }
        return $user['realname'] ? ($user['realname'] . '[' . $user['job_number'] . ']') : '';
    }

    public function clearCache()
    {
        Cache::store('goods')->delGoodsInfo($this->id);
    }

    private $tmpChannel = [];

    public function getChannel()
    {
        if ($this->tmpChannel === []) {
            $this->tmpChannel = cache::store('channel')->getChannel();
        }
        return $this->tmpChannel;
    }

    public function getChannelNameAttr($value, $data)
    {
        $channel = $this->getChannel();
        foreach ($channel as $channel_name => $v) {
            if ($v['id'] == $data['channel_id']) {
                return $channel_name;
            }
        }
        return '';
    }
    public function getWarehouseNameAttr($value, $data)
    {
        $info = Cache::store('warehouse')->getWarehouse($data['warehouse_id']);
        return isset($info['name']) ? $info['name'] : '';
    }


//    public function save($data = [], $where = [], $sequence = null)
//    {
//        $this->clearCache();
//        return parent::save($data, $where, $sequence);
//    }

}