<?php
namespace app\publish\service;

use app\common\model\Attribute;
use app\common\model\AttributeValue;
use app\common\model\Brand;
use app\common\model\GoodsAttribute;
use app\common\model\GoodsSku;
use app\common\model\lazada\LazadaAccount;
use app\common\model\lazada\LazadaActionLog;
use app\common\model\lazada\LazadaAttribute;
use app\common\model\lazada\LazadaBrand;
use app\common\model\lazada\LazadaCategory;
use app\common\model\lazada\LazadaProduct;
use app\common\model\lazada\LazadaSite;
use app\common\service\Filter;
use app\index\service\Department;
use app\common\model\DepartmentUserMap;
use app\common\model\Goods;
use app\common\model\GoodsSkuMap;
use app\common\service\ChannelAccountConst;
use app\common\service\UniqueQueuer;
use app\publish\filter\LazadaFilter;
use app\publish\helper\lazada\LazadaHelper;
use app\publish\helper\lazada\LazadaUtil;
use app\publish\queue\LazadaPublishQueue;
use app\publish\queue\LazadaSyncItemDetailQueue;
use app\publish\queue\LazadaUpdateListingQueue;
use app\publish\validate\LazadaListingValidate;
use think\Db;
use think\Exception;
use app\common\cache\Cache;
use app\common\traits\User;
use app\common\service\Common;
use think\Url;


class LazadaListingService
{
    use User;
    private $baseUrl;
    public $uid;

    public function __construct($uid)
    {
        $this->uid  = $uid;
        $this->baseUrl = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . '/';
    }

    public function getSellers()
    {
        $departments = (new Department())->getDepsByChannel(ChannelAccountConst::channel_Lazada);
        $ids = array_column($departments,'id');
        $users = (new DepartmentUserMap())->whereIn('department_id',$ids)->field('b.id,b.username,realname')->alias('a')->group('user_id')->join('user b','a.user_id=b.id','RIGHT')->select();
        return $users;
    }


    public function getAccounts()
    {
        $cacheAccount = Cache::store('LazadaAccount');
        $cacheAccounts = $cacheAccount->getAllAccounts();
        $cacheAccounts = $cacheAccount->filter($cacheAccounts, [], 'id,code,site');
        $accounts = [];
        if (!$this->isAdmin()) {
            $accountFilter = new Filter(LazadaFilter::class, true);
            if ($accountFilter->filterIsEffective()) {
                $filterAccounts = $accountFilter->getFilterContent();
            }
        }
        if (isset($filterAccounts)) {
            foreach ($filterAccounts as $k=>$v) {
                if (isset($cacheAccounts[$v])) {
                    $accounts[] = $cacheAccounts[$v];
                }
            }
        } else {
            foreach ($cacheAccounts as $k=>$v) {
                $accounts[] = $v;
            }
        }
        return $accounts;
    }

    /**
     * 组合SQL条件
     * combineWhere
     * @param array $param
     * @return array
     */
    private function getWhere(array $param)
    {
        $where = $join = [];
        $field = 'p.id,p.goods_id,p.category_id,p.account_id,p.name,p.spu,p.name,p.item_id,p.item_sku,p.publish_create_time,p.publish_update_time,
        p.publish_status,p.cron_time,p.create_id,p.cron_time,p.create_time';
        $countField = 'p.id';
        $orderBy = 'p.create_time';
        $sort = 'DESC';
        //搜索开始
        if (isset($param['search_type']) && isset($param['search_content']) && !empty(trim($param['search_content']))) {
            $searchType        = trim($param['search_type']);
            $searchContent     = trim($param['search_content']);
            switch ($searchType) {
                case 'spu':
                    if (strpos($searchContent, ',')) {
                        $where['p.spu'] = ['in', $searchContent];
                    } else {
                        $where['p.spu'] = $searchContent;
                    }
                    break;
                case 'sku':
                    $join['v'] = ['lazada_variant v', "p.id = v.pid"];
                    if (strpos($searchContent, ',')) {
                        $where['v.sku'] = ['in', $searchContent];
                    } else {
                        $where['v.sku'] = $searchContent;
                    }
                    break;
                case 'title':
                    $where['p.name'] = ['like', $searchContent . '%'];
                    break;
            }
        }
        if (!empty($param['account_id'])) {
            $where['p.account_id'] = $param['account_id'];
        }
        if (!empty($param['seller_id'])) {
            $where['p.create_id'] = $param['seller_id'];
        }
        if (!empty($param['status'])) {
            $join['g'] = ['goods g', 'p.goods_id = g.id'];
            $where['p.goods_id'] = ['>', 0];
            $where['g.sales_status'] = $param['status'];
        }
        //刊登成功时间
        if ($param['publish_start_time'] && $param['publish_end_time']) {
            $publishStartTime = strtotime($param['publish_start_time']);
            $publishEndTime   = strtotime($param['publish_end_time'].' 23:59:59');
            $where['p.publish_create_time'] = ['between', [$publishStartTime, $publishEndTime]];
        } else {
            if ($param['publish_start_time']) {
                $where['p.publish_create_time'] = ['>=', strtotime($param['publish_start_time'])];
            }
            if ($param['publish_end_time']) {
                $where['p.publish_create_time'] = ['<=', strtotime($param['publish_end_time'].' 23:59:59')];
            }
        }
        //创建时间
        if ($param['create_start_time'] && $param['create_end_time']) {
            $publishStartTime = strtotime($param['create_start_time']);
            $publishEndTime   = strtotime($param['create_end_time'].' 23:59:59');
            $where['p.create_time'] = ['between', [$publishStartTime, $publishEndTime]];
        } else {
            if ($param['create_start_time']) {
                $where['p.create_time'] = ['>=', strtotime($param['create_start_time'])];
            }
            if ($param['create_end_time']) {
                $where['p.create_time'] = ['<=', strtotime($param['create_end_time'].' 23:59:59')];
            }
        }
        //刊登状态
        if (strlen($param['publish_status'])) {
            if (strpos($param['publish_status'], ',')) {
                $where['p.publish_status'] = ['in', $param['publish_status']];
            } else {
                switch ($param['publish_status']) {
                    case -1:
                        $where['p.publish_status'] = -1;
                        break;
                    case 0:
                        $where['p.publish_status'] = 0;
                        break;
                    case 1:
                        $where['p.publish_status'] = 1;
                        break;
                    case 2:
                        $where['p.publish_status'] = 2;
                        break;
                    case 3:
                        $where['p.publish_status'] = 3;
                        break;
                    case 4:
                        $where['p.publish_status'] = 4;
                        break;
                    case 5:
                        $where['p.publish_status'] = 5;
                        break;
                }
            }
        }

        return ['where' => $where, 'join' => $join, 'field' => $field, 'order_by' => $orderBy, 'sort' => $sort, 'count_field' => $countField];
    }

    /**
     * 获取导出列表信息
     * getList
     * @param array $param
     * @param $page
     * @param $pageSize
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getList(array $param, $page, $pageSize)
    {
        $model = new LazadaProduct();
        $condition = $this->getWhere($param);
        $count = $model->alias('p')->field('p.id')->join($condition['join'])->where($condition['where'])->count($condition['count_field']);

        $lists = $model->alias('p')
            ->field($condition['field'])
            ->join($condition['join'])
            ->with(['variant' => function($query) {$query->with(['goodsSku' =>function($query) {$query->field('id,status');}, 'variantAttribute' =>function($query) {$query->field('vid,attr_name,attr_value');}]);}])
            ->with(['goods' =>function($query) {$query->field('id,thumb');}])
            ->where($condition['where'])
            ->order([$condition['order_by'] => $condition['sort']])
            ->page($page, $pageSize)
            ->select();

        if ($lists) {
            foreach ($lists as $k=>&$v) {
                //产品主图
                $v['real_name'] = '';
                if ($v['create_id']) {
                    $realname = Cache::store('user')->getOneUserRealname($v['create_id']);
                    $v['real_name'] = $realname;
                }
                $account = Cache::store('LazadaAccount')->getAllAccounts($v['account_id']);
                $v['account_name'] = $account['account_name'];
            }
        }
        $result = [
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'data' => $lists,
            'base_url' => $this->baseUrl,
        ];
        return $result;
    }

    public function choiceAccounts($param, $page, $pageSize)
    {
        $where = [];
        $accountModel = new LazadaAccount();
        if (isset($param['code']) && $param['code']) {
            $where['a.code'] = $param['code'];
        }

        if (isset($param['seller_id']) && $param['seller_id']) {
            $where['c.seller_id'] = $param['seller_id'];
        }

        $where['c.channel_id'] = ChannelAccountConst::channel_Lazada;
        $field = 'a.id, a.code, c.seller_id';
        $join['c'] = ['channel_user_account_map c', 'a.id = c.account_id', 'left'];
        $count = $accountModel->alias('a')->join($join)->where($where)->count();
        $list = $accountModel->alias('a')->field($field)->join($join)->where($where)->order('a.id desc')->page($page, $pageSize)->select();
        if ($list) {
            $site = LazadaSite::field('code')->select();
            foreach ($list as $k=>$v) {
                $user = Cache::store('user')->getOneUser($v['seller_id'], 'realname');
                $list[$k]['realname'] = $user ? $user['realname'] : '';
                foreach ($site as $kk=>$vv) {
                    $new[$kk]['account_code'] = $v['code'] . $vv['code'];
                    $new[$kk]['code'] = strtoupper($vv['code']);
                }
                $list[$k]['code_list'] = isset($new) ? $new : [];
            }
        }
        $result = [
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'data' => $list,
        ];
        return $result;
    }


    public function editListing($id)
    {
//        $eay = new \app\publish\helper\ebay\EbayPublish();
//        $goods = $eay->getGoods(100000);
        $model = new LazadaProduct();
        $product = $model->where(['id' => $id])->with(['productAttribute' =>function($query) {$query->where(['vid' => 0]);},
            'variant' => function($query) {$query->with(['variantAttribute', 'variantImages']);}, 'productInfo'])->find();
        if (!$product) {
            return [];
        }

        $goodsId = $product['goods_id'];
        $product = $product->toArray();
        $product['account_name'] = '';
        $product['site'] = '';
        $product['base_url']  = $this->baseUrl;
        $siteId = 0;
        $account = Cache::store('LazadaAccount')->getAllAccounts($product['account_id']);
        if ($account) {
            $product['account_name'] = $account['code'];
            $product['site'] = $account['site'];
            $siteId = LazadaUtil::getSiteIdBySiteCode(strtolower($account['site']));
        }
        //获取品牌
        $brand = LazadaBrand::where(['account_id' => $product['account_id']])->select();
        $product['lazada_brand'] = $brand ? $brand : [];
        //获取产品分类树
        $categories = self::getAllParent($product['category_id'], $siteId);
        $categoryTree = self::categoryTree($categories);
        $categoryName = $categoryTree['category_name'];
        $product['category_name'] = $categoryName;
        //关联的产品信息
//        $goods = $this->getGoods($product['goods_id']);
        $product['goods_info'] = $this->getGoods($goodsId);
        $goodsAttr = $this->getGoodsAttrCodes($goodsId);
        $product['attr_info'] = $goodsAttr ? array_flip(array_values($goodsAttr)) : [];
        $product['goods_sku'] = $this->getSkus($goodsId);

        return $product;
    }


    public function category($site, $categoryId, $categoryName)
    {
        $siteId = LazadaUtil::getSiteIdBySiteCode(strtolower($site));
        $where['site_id'] = $siteId;
        if ($categoryName) {
            $where['category_name'] = ['like', $categoryName . '%'];
        } else {
            $where['parent_id'] = $categoryId;
        }
        $data = LazadaCategory::where($where)->select();
        return $data;
    }

    public function attribute($site, $categoryId)
    {
        $siteId = LazadaUtil::getSiteIdBySiteCode(strtolower($site));
        $where['site_id'] = $siteId;
        $where['category_id'] = $categoryId;
        $data = LazadaAttribute::where($where)->select();
        $newData = [];
        if ($data) {
            //过滤掉重复的值
            foreach ($data as $k=>$v) {
                if (!in_array($v['attribute_name'], LazadaHelper::STATIC_ATTRIBUTE_FILED)) {
                    if ($v['attribute_name'] == 'brand') {
                        $data[$k]['options'] = json_encode([['name' => 'Rondaful']]);
                    }
                    $newData[] = $data[$k];
                }
            }
        }
        return $newData;
    }

    public function test()
    {
        $arr = [
            [
                'account_id' => 31,
                'account_code' => 'xmlkjsa',
                'site' => 'sg',
                'category_id' => 9251,
                'name' => 'Laaw Vestmon retro wave point printed shoulder patchwork dress sexy a wo',
                'cron_time' => '2019-04-24 11:26:02',
                'description' => 'point printed shoulder patchwork1',
                'short_description' => 'point1 printed shoulder patchwork2',
                'product_attribute' => [
                    'brand' => 'Rondaful',
                    'model' => 'L',
                ],
                'variant' => [
                    [
                        'vid' => 5596,
                        'sku_id' => 1000001,
                        'sku' => 'BL9989501',
                        'color_family' => 'black',
                        'original_price' => 51,
                        'price' => 45,
                        'refer_price' => 22,
                        'refer_promotion_price' => 18,
                        'package_weight' => 12,
                        'package_length' => 3,
                        'package_width' => 3,
                        'package_height' => 34,
                        'quantity' => 1,
                        'refer_price' => 1,
                        'refer_promotion_pice' => 1,
                        'tax_class' => 'default',
                    ]
                ],
                'variant_images' => [
                    [
                        'id' => 52045,   //新增时
                        'vid' => 5596, // 新增时 sku_id
                        'path' => '1010/033/985cffb9b579ef16a2989c8870269ba0.jpg',
                    ],
                ],
            ]
        ];

        $arr = json_encode($arr);
        print_r($arr);
        exit;
    }

    /**
     * 刊登
     * @param $params
     * @return int
     * @throws Exception
     */
    public function add($params)
    {
        $listingValidate = new LazadaListingValidate();
        $vars = json_decode($params['vars'], true);
        $error = $listingValidate->checkEdit($vars);
        if ($error) {
            throw new Exception($error, 400);
        }
        try {
            unset($params['vars']);
            $lazadaHelper = new LazadaHelper();
            $productIds = $cronTimes = [];
            Db::startTrans();
            foreach ($vars as $k => $v) {

                $cronTimes[] = empty($var['cron_time']) ? 0 : strtotime($var['cron_time']);
                $params['vars'] = $v;
                $productId = $lazadaHelper->saveProduct($params, $this->uid);
                if (!is_numeric($productId)) {
                    throw new \Exception($productId);
                }
                $productIds[] = $productId;
            }
            Db::commit();
            $i = 0;
            foreach ($productIds as $k => $productId) {
                $i++;
               (new UniqueQueuer(LazadaPublishQueue::class))->push($productId, $cronTimes[$k]);
               LazadaProduct::update(['publish_status'=>1], ['id'=>$productId]);//更新状态
            }
            return $i;
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 更新刊登
     * @param $params
     */
    public function update($params)
    {
        $vars = json_decode($params['vars'], true);
        $listingValidate = new LazadaListingValidate();
        $error = $listingValidate->checkEdit($vars);
        if ($error) {
            throw new Exception($error, 400);
        }
        if (empty($params['id'])) {
            throw new Exception('请求参数id不能为空', 400);
        }

        $product = LazadaProduct::where(['id' => $params['id']])->find();
        $lazadaHelper = new LazadaHelper();
        $params['vars'] = $vars[0];
        try {
            Db::startTrans();
            $productId = $lazadaHelper->saveProduct($params, $this->uid, true);
            if (!is_numeric($productId)) {
                throw new \Exception($productId);
            }
            $cronTime = strtotime($params['vars']['cron_time']);
            //写入更新日志
            $log['create_id'] = $this->uid;
            $log['type'] = LazadaHelper::API_TYPE['update'];
            $log['old_data'] = '';
            $log['new_data'] = '';
            $log['product_id'] = $productId;
            $log['create_time'] = time();
            $log['cron_time'] = $cronTime;
            $logId = (new LazadaActionLog())->insertGetId($log);
            if (empty($logId)) {
                throw new Exception('写入更新日志失败');
            }
            if (in_array($product['status'], [0, 2])) {
                //上架
                (new UniqueQueuer(LazadaPublishQueue::class))->push($productId, $cronTime);
                $publishStatus = 1;
            } else {
                $publishStatus = 3;
                (new UniqueQueuer(LazadaUpdateListingQueue::class))->push($productId, $cronTime);
            }

            LazadaProduct::update(['publish_status' => $publishStatus], ['id' => $productId]);//更新状态
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 手动同步listing
     * @param $id
     */
    public function manualSyncListing($ids)
    {
        $productModel = new LazadaProduct();
        $productList = $productModel->field('account_id, item_id')->where(['id' => ['in', $ids]])->select();
        if (!$productList) {
            throw new Exception('未找到listing 信息!');
        }
        $i = 0;
        foreach ($productList as $k=>$v) {
            $queue = $v['account_id']. '|'. $v['item_id'];
//            (new LazadaSyncItemDetailQueue())->execute($queue);
            (new UniqueQueuer(LazadaSyncItemDetailQueue::class))->push($queue);
            $i++;
        }
        return $i;
    }

    /**
     * 批量刊登
     * @param $id
     */
    public function batchPublish($id)
    {
        $productModel = new LazadaProduct();
        $productList = $productModel->field('id, account_id, item_id, publish_status, status')->where(['id' => ['in', $id]])->select();
        if (!$productList) {
            throw new Exception('未找到listing 信息!');
        }

        $i = 0;
        foreach ($productList as $k=>$v) {
            if (in_array($v['publish_status'], [1,3])) {
                //过滤掉
                continue;
            }
            $productId = $v['id'];
            if (in_array($v['status'], [0, 2])) {
                //上架
                (new UniqueQueuer(LazadaPublishQueue::class))->push($productId);
                $publishStatus = 1;
            } else {
                $publishStatus = 3;
                //更新
                (new UniqueQueuer(LazadaUpdateListingQueue::class))->push($productId);
            }
            //更新状态
            LazadaProduct::update(['publish_status' => $publishStatus], ['id' => $productId]);
            $i++;
        }
        return $i;
    }


    /**
     * 获取一个分类的所有父级
     * @param int $categoryId
     * @param array $arrCategoryIds
     * @return array
     */
    public static function getAllParent($categoryId, $siteId, &$categoryIds = [])
    {
        $category = LazadaCategory::where(['category_id' => $categoryId, 'site_id' => $siteId])->field('category_id, category_name, parent_id')->find();
        if ($category) {
            array_unshift($categoryIds, ['category_id'=>$categoryId, 'category_name'=> $category['category_name']]);
            if ($category['parent_id'] != 0) {
                self::getAllParent($category['parent_id'], $siteId, $categoryIds);
            }
        }

        return $categoryIds;
    }

    /**
     * 将一个分类数组转换成a>>b>>c>>d
     * @param $categorys
     */
    public static function categoryTree($categories)
    {
        $treeName='';
        $childCategory =0;
        foreach ($categories as $category) {
            $treeName = $treeName . '>>' .$category['category_name'];
            $childCategory = $category['category_id'];
        }
        return [
            'category_name' => substr($treeName,2),
            'category_id'   => $childCategory,
        ];
    }



    /**
     * 获取产品信息
     * @param $goodsId
     */
    public function getBaseInfo($goodsId, $field = '*')
    {
        try {
            $goods = (new Goods())->field($field)->where(['id' => $goodsId])->find();
            return empty($goods) ? [] : $goods->toArray();
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * 获取产品信息
     * @param $goodsId
     */
    public function getGoods($goodsId)
    {
        $field = ['id', 'category_id', 'spu', 'name', 'thumb', 'brand_id', 'channel_id', 'sales_status', 'transport_property', 'sales_status'];
        $goods = $this->getBaseInfo($goodsId, $field);
        if ($goods) {
            $goodsHelper = new GoodsHelp();
            $goods['brand'] = $goods['brand_id'] ? $goodsHelper->getBrandById($goods['brand_id']) : '';
            $goods['transport_property'] = (new \app\goods\service\GoodsHelp())->getProTransPropertiesTxt($goods['transport_property']);//物流属性转文本-pan->getProTransPropertiesTxt($goods['transport_property']);//物流属性转文本-pan
            $goods['category'] = $goodsHelper->mapCategory($goods['category_id']);
        }

        return $goods;
    }

    /**
     * 获取产品属性键值信息
     * @param $goodsId
     */
    public function getGoodsAttrCodes($goodsId)
    {
        try {

            $attrs = GoodsAttribute::where(['goods_id'=>$goodsId])->distinct(true)->order('attribute_id')->column('attribute_id');
            $codes = Attribute::where(['id'=>['in', $attrs]])->order('id')->column('name');
            $goodsAttr = $attrs && $attrs ? array_combine($attrs, $codes) : [];
            return $goodsAttr;
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }

    }

    /**
     * 获取产品sku信息
     * @param $goodsId
     */
    public function getSkus($goodsId)
    {
        try {
            $skuInfo = $skuAttrN = [];
            $skus = (new GoodsSku())->field(true)->where(['goods_id' => $goodsId])->order('sku')->select();
            $attrIds = [];//属性id
            $attrValIds = [];//属性值id
            $i = 0;
            foreach ($skus as $sku) {
                $skuAttrVals = json_decode($sku['sku_attributes'], true);
                foreach ($skuAttrVals as $attr => $val) {
                    $attrId = intval(substr($attr,5));//获取属性id
                    $attrCode = Attribute::where(['id'=>$attrId])->value('name');
                    if ($attrId == 11 || $attrId == 15) {//使用别名
                        $wh = [
                            'goods_id' => $goodsId,
                            'attribute_id' => $attrId,
                            'value_id' => $val
                        ];
                        $skuAttrN[$attrCode] = GoodsAttribute::where($wh)->value('alias');
                    } else {
                        $skuAttrN[$attrCode] = AttributeValue::where(['id'=>$val])->value('value');
                    }
                }
                $skuInfo[$i]['id'] = $sku['id'];
                $skuInfo[$i]['sku'] = $sku['sku'];
                $skuInfo[$i]['thumb'] = $sku['thumb'];
                $skuInfo[$i]['status'] = $sku['status'];
                $skuInfo[$i]['cost_price'] = $sku['cost_price'];
                $skuInfo[$i]['retail_price'] = $sku['retail_price'];
                $skuInfo[$i]['sku_attributes'] = $skuAttrN;
                $skuInfo[$i]['map_sku'] = ['goods_id'=>$goodsId,'sku_id'=>$sku['id'],'sku'=>$sku['sku'].'*1'];
                $i++;
            }
            return $skuInfo;
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }

    }

}