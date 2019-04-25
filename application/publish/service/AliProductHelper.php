<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/5/19
 * Time: 15:46
 */

namespace app\publish\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\aliexpress\AliexpressAccountBrand;
use app\common\model\aliexpress\AliexpressApiException;
use app\common\model\aliexpress\AliexpressCategory;
use app\common\model\aliexpress\AliexpressCategoryAttr;
use app\common\model\aliexpress\AliexpressCategoryAttrVal;
use app\common\model\aliexpress\AliexpressFreightTemplate;
use app\common\model\aliexpress\AliexpressProduct;
use app\common\model\aliexpress\AliexpressProductGroup;
use app\common\model\aliexpress\AliexpressProductSku;
use app\common\model\aliexpress\AliexpressProductTemplate;
use app\common\model\aliexpress\AliexpressPromiseTemplate;
use app\common\model\aliexpress\AliexpressPublishPlan;
use app\common\model\aliexpress\AliexpressPublishTemplate;
use app\common\model\AttributeValue;
use app\common\model\Brand;
use app\common\model\Goods;
use app\common\model\GoodsGallery;
use app\common\model\GoodsLang;
use app\common\model\GoodsPublishMap;
use app\common\model\GoodsSku;
use app\common\model\GoodsSkuMap;
use app\customerservice\service\AliexpressHelp;
use app\listing\service\AliexpressListingHelper;
use app\publish\controller\Wish;
use app\publish\exception\AliPublishException;
use erp\AbsServer;
use think\Db;
use think\Exception;
use app\goods\service\GoodsImage;
use app\publish\service\GoodsImage as PublishGoodsImageService;
use app\common\traits\User;
use app\common\model\aliexpress\AliexpressGroupRegion as AliexpressGroupRegionModel;
use app\common\service\Common;
use app\common\model\GoodsAttribute;
use app\goods\service\GoodsHelp;
use app\common\model\Channel;
use app\common\model\ebay\EbayAccount;
use app\common\model\wish\WishAccount;
use app\common\model\amazon\AmazonAccount;
use app\common\service\UniqueQueuer;
use app\goods\queue\GoodsTortListingQueue;
use app\publish\queue\AliexpressSkuOfflineQueue;
use app\common\model\GoodsDiscount as GoodsDiscountModel;

class AliProductHelper extends AbsServer
{

    use User;

    protected static $publish_error_code = [
        '010025' => '检查账号申请该品牌没有,或者申请有没有到期',
        '030002' => '请检查产品分组是否填写,如果填写了,可以重新同步,再选择产品分组',
        '010019' => '普通属性中必填的属性未填写,填写之后重新刊登',
        '010008' => '是否是有效的卖家,以及分类是否可以刊登',
        '150019' => '当前选择的品牌没有发布产品的权限,在卖家后台-账号及认证——我的申请——我的品牌申请中查看有没有申请,或者申请到期没,通过后,重新获取最新品牌,再次刊登',
        '07010008' => '账号选择的类目没有权限发布,请前往店铺后台申请,之后再刊登商品',
        '07150019' => '账号选择的类目没有设置品牌,请前往店铺后台申请,之后再erp获取最新品牌刊登',
        '07010016' => '请检查自定义属性名是否重复,属性名不能重复',
    ];

    /**
     * 获取刊登产品列表
     * @param $params
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getAliProductList($params, $page = 1, $pageSize = 10, $status = 2)
    {
        $productModel = new AliexpressProduct();

        $arrWhere = $this->getFilterCondition($params, $status);

        if(isset($arrWhere['data'])) {
           return $arrWhere;
        }

        $field = 'p.goods_id,p.id,p.account_id,p.product_id,product_status_type,subject,goods_spu,group_id,product_min_price,product_max_price,plan_publish_time,
        promise_template_id,imageurls,p.update_time,freight_template_id,p.currency_code,salesperson_id,p.category_id,p.gmt_create,p.gross_weight,
        p.package_length,p.package_width,p.package_height,p.lot_num,p.package_type,p.product_unit,p.delivery_time,p.bulk_discount,p.lock_update,p.status,
        p.relation_template_id,p.relation_template_postion,p.custom_template_id,p.custom_template_postion,p.create_time';

        $order_str = '';
        $join = [];
        if (isset($params['order_type']) && !empty($params['order_type'])) {
            $sort = $params['order_sort'] ? $params['order_sort'] : 'desc';
            switch ($params['order_type']) {
                case 'update':
                    $order_str = ' update_time ' . $sort;
                    break;
                case 'price':
                    $order_str = ' product_min_price ' . $sort;
                    break;
                case 'wholesale':
                    $field = $field . ' ,(product_min_price*(100-if(bulk_discount>0,bulk_discount,100))/100) as wholesale';
                    $order_str = ' wholesale ' . $sort;
                    break;
                case 'stock':
                    $field = $field . ' ,sum(ipm_sku_stock) as total_stock';
                    $join['s'] = ['aliexpress_product_sku s', 'p.id=s.ali_product_id', 'left'];
                    $order_str = ' total_stock ' . $sort;
                    break;
            }
        } else {
            $order_str = ' update_time DESC';
        }


        //侵权平台 0全部,1.eBay平台,2.亚马逊平台,3.Wish平台,4.速卖通平台,5.CD平台,6.Lazada平台,7.Joom平台,8.MyMall平台,9.Shopee
        if(isset($params['tort_platform']) && is_numeric($params['tort_platform'])) {
            if(!$params['tort_platform']) {
                $where['t.channel_id'] = $params['tort_platform'];
            }
            $field.=',t.channel_id';
            $join['t'] = ['goods_tort_description t','p.goods_id = t.goods_id', 'right'];
        }

        //spu 本地状态
        if (isset($arrWhere['g.sales_status'])) {
            $join['g'] = ['goods g', 'p.goods_id=g.id', 'left'];
        }

        //sku 本地状态
        if (isset($arrWhere['gs.status'])) {
            $join['s'] = ['aliexpress_product_sku s', 'p.id=s.ali_product_id', 'left'];
            $join['gs'] = ['goods_sku gs', 's.goods_sku_id=gs.id', 'left'];
        }

        $where = [];
        //cost_price_type 0:全部1:涨价;2:降价3:无
        if (isset($params['cost_price_type']) && $params['cost_price_type']) {


            //涨价>0,降价<0
            $cost_price_compare = $params['cost_price_type'] == 1 ? ['>', 0] : ['<', 0];

            $where['(current_cost - pre_cost)'] = $cost_price_compare;
            $arrWhere['(s.current_cost - s.pre_cost)'] = $cost_price_compare;

            $cost_price = $params['cost_price'];

            //涨价:当前价-原始价等于价格浮动
            if ($params['cost_price_type'] == 1) {

                //涨幅价存在
                if ($params['cost_price']) {
                    $where['(current_cost - pre_cost)'] = ['>=', $cost_price];
                    $arrWhere['(s.current_cost - s.pre_cost)'] = ['>=', $cost_price];
                }
            }

            //降价:原始价-当前价等于价格浮动
            if ($params['cost_price_type'] == 2) {

                //跌幅价存在
                if ($params['cost_price']) {
                    $where['(pre_cost - current_cost)'] = ['>=', $cost_price];
                    $arrWhere['(s.pre_cost - s.current_cost)'] = ['>=', $cost_price];
                }
            }

            $join['s'] = ['aliexpress_product_sku s', 'p.id=s.ali_product_id', 'left'];
        }


        //查询sku库存是否为0
        $skuWhere = [];
        if (isset($params['is_stock']) && is_numeric($params['is_stock'])) {

            //预警,正常
            if (in_array($params['is_stock'], [0, 1])) {

                $skuWhere = [
                    'ipm_sku_stock' => $params['is_stock'] > 0 ? ['>', 0] : ['=', 0],
                    'product_id' => ['>', 0],
                ];

                $productSkuList = (new AliexpressProductSku)->field('distinct(ali_product_id) ali_product_id')->page($page, $pageSize)->where($skuWhere)->select();
                if ($productSkuList) {
                    $aliProductIds = array_column($productSkuList, 'ali_product_id');

                    $arrWhere['p.id'] = ['in', $aliProductIds];
                }
            }
        }

        if (empty($join)) {

            if ($skuWhere) {
                $count = (new AliexpressProductSku)->where($skuWhere)->count('distinct ali_product_id');

                $list = $productModel->field($field)->alias('p')->with(['productSku' => function ($query) use ($where) {
                    $query->where($where);
                }, 'sku','freight'])->order($order_str)->where($arrWhere)->group('p.id')->select();
            } else {
                $count = $productModel->alias('p')->where($arrWhere)->count('p.id');

                $list = $productModel->field($field)->alias('p')->with(['productSku' => function ($query) use ($where) {
                    $query->where($where);
                }, 'sku','freight'])->order($order_str)->page($page, $pageSize)
                    ->where($arrWhere)->group('p.id')->select();
            }
        } else {


            if ($skuWhere) {
                $count = (new AliexpressProductSku)->where($skuWhere)->count('distinct ali_product_id');

                $list = $productModel->field($field)->join($join)->alias('p')->with(['productSku' => function ($query) use ($where) {
                    $query->where($where);
                }, 'sku','freight'])->order($order_str)->where($arrWhere)->group('p.id')->select();

            } else {
                $count = $productModel->alias('p')->join($join)->where($arrWhere)->count('distinct(p.id)');

                $list = $productModel->field($field)->join($join)->alias('p')->with(['productSku' => function ($query) use ($where) {
                    $query->where($where);
                }, 'sku','freight'])->order($order_str)
                    ->page($page, $pageSize)->where($arrWhere)->group('p.id')->select();

            }
        }


        $arrAccount = Cache::store('AliexpressAccount')->getAllAccounts();
        $arr_attr_name = [];
        $arr_attr_val_name = [];

        if (!empty($list)) {

            $arrSkuStatus = [
                0 => '未上架',
                1 => '上架',
                2 => '下架',
                3 => '待发布',
                4 => '卖完下架',
                5 => '缺货'
            ];

            foreach ($list as &$product) {
                $product = $product->toArray();

                $product['product_unit_name'] = '';
                $product_unit = AliexpressProduct::PRODUCT_UNIT;
                if(isset($product_unit[$product['product_unit']])){
                    $product['product_unit_name'] = $product_unit[$product['product_unit']];
                }

                $product['freight_template_name'] = isset($product['freight']['template_name'])?$product['freight']['template_name']:'';

                $arrProductSku = collection($product['product_sku'])->toArray();
                $product['stock'] = array_sum(array_column($arrProductSku, 'ipm_sku_stock'));

                $product['group_name'] = AliexpressProductGroup::getNameByGroupId($product['account_id'], json_decode($product['group_id'], true));
                $arrImgs = explode(';', $product['imageurls']);
                $product['main_img'] = !empty($arrImgs) && is_array($arrImgs) ? $arrImgs[0] : '';
                $product['account_code'] = $arrAccount[$product['account_id']]['code'];
                $product['product_min_price'] = round($product['product_min_price'], 2);
                $product['product_max_price'] = round($product['product_max_price'], 2);
                $product['is_wholesale'] = $product['bulk_discount'] > 0 ? 1 : 0;
                $bulk_discount = (100 - $product['bulk_discount']) / 100;
                $user = Cache::store('user')->getOneUser($product['salesperson_id']);
                $product['seller'] = empty($user) ? '' : $user['realname'];
                $product['price'] = $product['product_max_price'] == $product['product_min_price'] ? $product['product_min_price'] : $product['product_min_price'] . '-' . $product['product_max_price'];
                $product['wholesale_min_price'] = round($product['product_min_price'] * $bulk_discount, 2);
                $product['wholesale_max_price'] = round($product['product_max_price'] * $bulk_discount, 2);
                $product['wholesale_price'] = $product['wholesale_min_price'] == $product['wholesale_max_price'] ? $product['wholesale_max_price'] : $product['wholesale_min_price'] . '-' . $product['wholesale_max_price'];
                //获取sku信息
                $used_attr = $listing_attr_val = [];
                $goods_skus = [];
                if (!empty($product['sku'])) {
                    foreach ($product['sku'] as $item) {
                        $goods_skus[$item['id']] = $item;
                    }
                }
                unset($product['sku']);

                $is_stock = 1;
                foreach ($arrProductSku as $k => &$sku) {

                    //检查sku的可售量 是否有为0
                    if (empty($sku['ipm_sku_stock'])) {
                        $is_stock = 0;
                    }

                    $goods_sku = isset($goods_skus[$sku['goods_sku_id']]) ? $goods_skus[$sku['goods_sku_id']] : [];
                    $arrProductSku[$k]['thumb'] = !empty($goods_sku) ? $goods_sku['thumb'] : '';
                    $arrProductSku[$k]['local_sku'] = !empty($goods_sku) ? $goods_sku['sku'] : '';
                    $arrProductSku[$k]['local_status'] = !empty($goods_sku) ? $arrSkuStatus[$goods_sku['status']] : '';

                    $arrProductSku[$k]['cost_pirce_desc'] = '0.00';
                    $arrProductSku[$k]['cost_price_type'] = '0';
                    //原成本价不等于调整成本价
                    if ($sku['current_cost'] != $sku['pre_cost']) {

                        if ($sku['pre_cost'] < $sku['current_cost']) {
                            $arrProductSku[$k]['cost_pirce_desc'] = '涨价:' . ($sku['current_cost'] - $sku['pre_cost']);
                            $arrProductSku[$k]['cost_price_type'] = '1';
                        } else {
                            $arrProductSku[$k]['cost_pirce_desc'] = '降价:' . ($sku['pre_cost'] - $sku['current_cost']);
                            $arrProductSku[$k]['cost_price_type'] = '2';
                        }
                    }


                    $sku['wholesale_price'] = round($sku['sku_price'] * $bulk_discount, 2);
                    //sku属性ID及属性值ID转化成名字
                    $arr_sku_attr = json_decode($sku['sku_attr'], true);
                    if (!empty($arr_sku_attr)) {
                        foreach ($arr_sku_attr as &$attr) {

                            $attr['skuPropertyId'] = isset($attr['skuPropertyId']) ? $attr['skuPropertyId'] : '';

                            if (isset($arr_attr_name[$product['category_id'] . '_' . $attr['skuPropertyId']])) {
                                $attr['attr_name'] = $arr_attr_name[$product['category_id'] . '_' . $attr['skuPropertyId']];
                            } else {
                                $attr['attr_name'] = AliexpressCategoryAttr::getNameById($product['category_id'], $attr['skuPropertyId']);
                                $arr_attr_name[$product['category_id'] . '_' . $attr['skuPropertyId']] = $attr['attr_name'];
                            }
                            if ($attr['attr_name']) {
                                $used_attr[$attr['skuPropertyId']] = $attr['attr_name'];
                            }

                            if (isset($arr_attr_val_name[$attr['propertyValueId']])) {
                                $attr['attr_val_name'] = $arr_attr_val_name[$attr['propertyValueId']];
                            } else {
                                $attr['attr_val_name'] = AliexpressCategoryAttrVal::getNameById($attr['propertyValueId']);
                                $arr_attr_val_name[$attr['propertyValueId']] = $attr['attr_val_name'];
                            }
                            if (isset($used_attr[$attr['skuPropertyId']])) {
                                $listing_attr_val[$attr['skuPropertyId']][] = $attr['attr_val_name'] ? $attr['attr_val_name'] : '';
                            }
                        }
                        $sku['sku_attr'] = $arr_sku_attr;
                    }
                }


                unset($goods_skus);
                //检查sku的可售量
                $product['is_stock'] = $is_stock;
                $product['used_attr'] = array_values($used_attr);
                $product['listing_attr_val'] = array_values($listing_attr_val);
                $product['id'] = (string)$product['id'];
                $product['product_sku'] = $arrProductSku;
                $product['tort_platform']  = isset($product['channel_id']) ? 1 : 0;
            }
        }


        $base_url = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;
        return ['base_url' => $base_url, 'data' => $list, 'count' => $count, 'page' => $page, 'totalpage' => ceil($count / $pageSize)];
    }

    /**
     * 设置产品列表搜索条件
     * @param $params
     * @return array
     */
    protected function getFilterCondition($params, $status)
    {
        $where = [];
        //平台账号
        if (isset($params['account_id']) && !empty($params['account_id'])) {
            $where['p.account_id'] = $params['account_id'];
        }
        //到期时间
        if (isset($params['expire_day']) && $params['expire_day'] > 0) {
            $where['p.expire_day'] = [['elt', $params['expire_day']], ['gt', 0]];
        }
        //产品平台状态
        if (isset($params['status']) && !empty($params['status'])) {
            $where['p.product_status_type'] = intval($params['status']);
        }

        //产品平台状态
        if (isset($params['salesperson_id']) && !empty($params['salesperson_id'])) {
            $where['p.salesperson_id'] = intval($params['salesperson_id']);
        }


        if ($status !== '') {
            if ($status == 3) {
                $where['p.status'] = ['in', [3, 1]];
            } else {
                $where['p.status'] = intval($status);
            }

        }

        //sku 本地sku状态
        if ($params['snType'] == 'sku' && $params['snText'] && isset($params['local_status']) && $params['local_status'] !== '') {
            $where['gs.status'] = intval($params['local_status']);
        }

        //应用，刊登来源
        if (isset($params['application']) && is_numeric($params['application'])) {
            if ($params['application'] == 1) {
                $where['p.application'] = array('=', "rondaful");
            } else {
                $where['p.application'] = array('<>', "rondaful");
            }

        }

        if (isset($params['ntime']) && $params['ntime'] == 'last_updated') {
            if (!empty($params['start']) && !empty($params['end'])) {
                $where['p.update_time'] = ['BETWEEN TIME', [strtotime($params['start']), strtotime($params['end'] . ' 23:59:59')]];
            } elseif (!empty($params['start'])) {
                $where['p.update_time'] = ['EGT', strtotime($params['start'])];
            } elseif (!empty($params['end'])) {
                $where['p.update_time'] = ['ELT', strtotime($params['end'] . ' 23:59:59')];
            }
        } elseif (isset($params['ntime']) && $params['ntime'] == 'date_uploaded') {
            if (!empty($params['start']) && !empty($params['end'])) {
                $where['p.create_time'] = ['BETWEEN TIME', [strtotime($params['start']), strtotime($params['end'] . ' 23:59:59')]];
            } elseif (!empty($params['start'])) {
                $where['p.create_time'] = ['EGT', strtotime($params['start'])];
            } elseif (!empty($params['end'])) {
                $where['p.create_time'] = ['ELT', strtotime($params['end'] . ' 23:59:59')];
            }
        }

        //spu 本地状态
        if (in_array($params['snType'],['spu','product_id','title']) && isset($params['local_status']) && !empty($params['local_status'])) {
            $where['g.sales_status'] = $params['local_status'];
        }
        //关键词
        if (isset($params['snType']) && !empty($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'product_id':
                    $where['p.product_id'] = ['IN', $params['snText']];
                    break;
                case 'spu':
                    $where['p.goods_spu'] = ['IN', $params['snText']];
                    break;
                case 'sku':
                    $sku_code = $params['snText'];

                    $sku_code = ['sku' => ['IN', $params['snText']]];

                    $goodsSkuModel = new GoodsSku();
                    $goodsIds = $goodsSkuModel->field('goods_id')->where($sku_code)->select();

                    $base_url = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;
                    if(!$goodsIds) {
                       return  ['base_url' => $base_url, 'data' => [], 'count' => 0, 'page' => 1, 'totalpage' => 0];
                    }

                    $goodsIds = array_column($goodsIds, 'goods_id');
                    $where['p.goods_id'] = ['in', $goodsIds];
                    break;
                case 'title':
                    $where['p.subject'] = ['like', '%' . $params['snText'] . '%'];
                    break;
            }
        }


        //virtual_send '是否虚拟仓发货，"":全部; 1:是;0:否
        if (isset($params['virtual_send']) && is_numeric($params['virtual_send'])) {

            if ($params['virtual_send']) {
                $where['p.virtual_send'] = ['=', 1];
            } else {
                $where['p.virtual_send'] = ['=', 0];
            }
        }

        return $where;
    }

    /**
     * 获取未刊登列表
     * @param $params
     * @param int $page
     * @param int $pageSize
     * @return array
     */
  /*  public function getUnpublishList($params, $page = 1, $pageSize = 10, $uid, $fields = "*")
    {
        $where = [];

        $where['m.channel'] = ['eq', 4];
        $where['m.platform_sale'] = ['=', 1];
        $where['g.sales_status'] = ['IN', array(1, 4, 6)];
        $fields = "distinct(m.spu),g.category_id,m.goods_id,g.thumb,g.name,g.publish_time,g.packing_en_name,t.id as task_id,t.ali_product_id";
        $post = $params;
        $join = [];
        if (isset($post['snType']) && $post['snType'] == 'spu' && $post['snText']) {
            $where['m.' . $post['snType']] = array('IN', $post['snText']);
        }

        if (isset($post['snType']) && $post['snType'] == 'title' && $post['snText']) {
            $where['name'] = array('like', '%' . $post['snText'] . '%');
        }

        if (isset($post['snType']) && $post['snType'] == 'id' && $post['snText']) {
            $where['m.goods_id'] = array('eq', $post['snText']);
        }

        if (isset($post['snType']) && $post['snType'] == 'sku' && $post['snText']) {
            $where['gs.sku'] = array('IN', $post['snText']);
            $join[] = ['goods_sku gs', 'gs.goods_id=m.goods_id', 'RIGHT'];
        }

        if (isset($post['category_id']) && $post['category_id']) {
            $category_id = (int)$post['category_id'];
            $categories = CommonService::getSelfAndChilds($category_id);
            $where['g.category_id'] = array('IN', $categories);
        }

        if (isset($post['developer_id']) && $post['developer_id']) {
            $where['g.developer_id'] = array('=', $post['developer_id']);
        }

        //品牌id
        if (isset($post['brand_id']) && $post['brand_id']) {
            $where['g.brand_id'] = ['=', $post['brand_id']];
        }

        $authCategoryArray = [];
        if (isset($post['account_id']) && is_numeric($post['account_id'])) {
            //$where['m.publish_status$."'.$post['account_id'].'"'] = ['=',0];
            //查看对应账号是否已经刊登,未刊登才显示
            $map = " JSON_SEARCH(m.publish_status,'one', " . $post['account_id'] . ") IS NULL ";
            $authCategorys = AliexpressCategoryService::getAuthCategory($post['account_id']);

            if (empty($authCategorys)) {
                return ['message' => '帐号还没有绑定平台分类与本地分类关系，请先绑定'];
            }
            $authCategoryArray = AliexpressCategoryService::getAuthCategoryArray($authCategorys);

         } else {
            $map = [];
        }


        $where['t.status'] = ['=',0];
        $where['t.sales_id'] = ['=',$uid];

        $model = new GoodsPublishMap();
        if (!empty($authCategoryArray)) {
            $count = $model->alias('m')->join('goods g', 'm.goods_id=g.id', 'LEFT')
                ->join('aliexpress_publish_task t','m.goods_id = t.goods_id','LEFT')
                ->where($where)->where($map)->whereIn('g.category_id', $authCategoryArray)
                ->join($join)->count();

            $data = $model->alias('m')
                ->join('goods g', 'm.goods_id=g.id', 'LEFT')
                ->join('aliexpress_publish_task t','m.goods_id = t.goods_id','LEFT')
                ->join($join)
                ->where($where)->where($map)->whereIn('g.category_id', $authCategoryArray)
                ->field($fields)->order('g.create_time desc')->page($page, $pageSize)->select();

        } else {
            $count = $model->alias('m')
                ->join('goods g', 'm.goods_id=g.id', 'LEFT')
                ->join('aliexpress_publish_task t','m.goods_id = t.goods_id','LEFT')
                ->join($join)
                ->where($where)->where($map)
                ->count();

            $data = $model->alias('m')
                ->join('goods g', 'm.goods_id=g.id', 'LEFT')->join($join)
                ->join('aliexpress_publish_task t','m.goods_id = t.goods_id','LEFT')
                ->where($where)->where($map)
                ->field($fields)->order('g.create_time desc')->page($page, $pageSize)->select();
        }

        $goodsModel = new Goods();
        $goodsHelp = new GoodsHelp();

        foreach ($data as $k => &$d) {
            $d['id'] = $d['goods_id'];

            $category = $goodsModel->getCategoryAttr("", $d);
            if ($category) {
                $d['category'] = $category;
            } else {

                $d['category'] = '';
            }

            $lang = GoodsLang::where(['goods_id' => $d['goods_id'], 'lang_id' => 2])->field('title')->find();

            if ($lang) {
                $d['packing_en_name'] = $lang['title'];
            }
            $d['thumb'] = empty($d['thumb']) ? '' : GoodsImage::getThumbPath($d['thumb'], 60, 60);

            //是否平台侵权
            $goods_tort = $goodsHelp->getGoodsTortDescriptionByGoodsId($d['goods_id']);
            $d['is_goods_tort'] = $goods_tort ? 1 : 0;
        }
        return ['data' => $data, 'count' => $count, 'page' => $page, 'pageSize' => $pageSize];
    }*/

    /**
     * 获取未刊登列表
     * @param $params
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getUnpublishList($params, $page = 1, $pageSize = 10, $uid = 0, $fields = "*")
    {
        $where = [];

        $where['m.channel'] = ['=', 4];
        $where['m.platform_sale'] = ['=', 1];
        $where['g.sales_status'] = ['IN', array(1, 4, 6)];
        $fields = "distinct(m.spu),g.category_id,m.goods_id,g.thumb,g.name,g.publish_time,g.packing_en_name";
        $post = $params;
        $join = [];
        if (isset($post['snType']) && $post['snType'] == 'spu' && $post['snText']) {
            $where['m.' . $post['snType']] = array('IN', $post['snText']);
        }

        if (isset($post['snType']) && $post['snType'] == 'title' && $post['snText']) {
            $where['name'] = array('like', '%' . $post['snText'] . '%');
        }

        if (isset($post['snType']) && $post['snType'] == 'id' && $post['snText']) {
            $where['m.goods_id'] = array('eq', $post['snText']);
        }

        if (isset($post['snType']) && $post['snType'] == 'sku' && $post['snText']) {
            $where['gs.sku'] = array('IN', $post['snText']);
            $join[] = ['goods_sku gs', 'gs.goods_id=m.goods_id', 'RIGHT'];
        }

        if (isset($post['category_id']) && $post['category_id']) {
            $category_id = (int)$post['category_id'];
            $categories = CommonService::getSelfAndChilds($category_id);
            $where['g.category_id'] = array('IN', $categories);
        }

        if (isset($post['developer_id']) && $post['developer_id']) {
            $where['g.developer_id'] = array('=', $post['developer_id']);
        }

        //品牌id
        if (isset($post['brand_id']) && $post['brand_id']) {
            $where['g.brand_id'] = ['=', $post['brand_id']];
        }


        $authCategoryArray = [];
        if (isset($post['account_id']) && is_numeric($post['account_id'])) {
            //$where['m.publish_status$."'.$post['account_id'].'"'] = ['=',0];
            //查看对应账号是否已经刊登,未刊登才显示
            $map = " JSON_SEARCH(m.publish_status,'one', " . $post['account_id'] . ") IS NULL ";
            $authCategorys = AliexpressCategoryService::getAuthCategory($post['account_id']);

            if (empty($authCategorys)) {
                return ['message' => '帐号还没有绑定平台分类与本地分类关系，请先绑定'];
            }
            $authCategoryArray = AliexpressCategoryService::getAuthCategoryArray($authCategorys);

        } else {
            $map = [];
        }

        $model = new GoodsPublishMap();
        if (!empty($authCategoryArray)) {
            $count = $model->alias('m')->join('goods g', 'm.goods_id=g.id', 'LEFT')
                ->where($where)->where($map)->whereIn('g.category_id', $authCategoryArray)
                ->join($join)->count();

            $data = $model->alias('m')
                ->join('goods g', 'm.goods_id=g.id', 'LEFT')
                ->join($join)
                ->where($where)->where($map)->whereIn('g.category_id', $authCategoryArray)
                ->field($fields)->order('g.create_time desc')->page($page, $pageSize)->select();

        } else {
            $count = $model->alias('m')
                ->join('goods g', 'm.goods_id=g.id', 'LEFT')
                ->join($join)
                ->where($where)->where($map)
                ->count();

            $data = $model->alias('m')
                ->join('goods g', 'm.goods_id=g.id', 'LEFT')->join($join)
                ->where($where)->where($map)
                ->field($fields)->order('g.create_time desc')->page($page, $pageSize)->select();
        }

        $goodsModel = new Goods();
        $goodsHelp = new GoodsHelp();

        foreach ($data as $k => &$d) {
            $d['id'] = $d['goods_id'];

            $category = $goodsModel->getCategoryAttr("", $d);
            if ($category) {
                $d['category'] = $category;
            } else {

                $d['category'] = '';
            }

            $lang = GoodsLang::where(['goods_id' => $d['goods_id'], 'lang_id' => 2])->field('title')->find();

            if ($lang) {
                $d['packing_en_name'] = $lang['title'];
            }
            $d['thumb'] = empty($d['thumb']) ? '' : GoodsImage::getThumbPath($d['thumb'], 60, 60);

            //是否平台侵权
            $goods_tort = $goodsHelp->getGoodsTortDescriptionByGoodsId($d['goods_id']);
            $d['is_goods_tort'] = $goods_tort ? 1 : 0;

            //是否跌价
            $goodsDiscountModel = new GoodsDiscountModel();
            $is_goods_discount = $goodsDiscountModel->where('goods_id','=', $d['goods_id'])->field('id')->find();
            $d['is_goods_discount'] = $is_goods_discount ? 1 : 0;
        }
        return ['data' => $data, 'count' => $count, 'page' => $page, 'pageSize' => $pageSize];
    }

    /**
     * 获取商品详细信息
     * @param $productId
     * @param $categoryId
     * @return array
     */
    public function getAliProductDetail($productId, $categoryId = 0)
    {
        $aliexpressCategoryAttrModel = new AliexpressCategoryAttr();
        $productModel = new AliexpressProduct();
        $field = 'configuration_type,aeop_national_quote_configuration,quote_config_status,id,account_id,product_id,subject,delivery_time,category_id,product_price,product_unit,package_type,lot_num,package_length,
        package_width,package_height,gross_weight,is_pack_sell,base_unit,add_unit,add_weight,ws_valid_num,bulk_order,bulk_discount,reduce_strategy,
        currency_code,promise_template_id,sizechart_id,freight_template_id,imageurls,goods_id,goods_spu,group_id,is_balance,warehouse_id,plan_publish_time,
        salesperson_id,lock_update,relation_template_id,relation_template_postion,custom_template_id,custom_template_postion,virtual_send';
        $objProduct = $productModel->field($field)->with(['productSku', 'productInfo'])->where(['id' => $productId])->find();

        if (empty($objProduct)) {
            throw new AliPublishException('没有找到相关产品');
        }

        $arrProduct = $objProduct->toArray();
        $arrProduct['imageurls'] = $objProduct->getData('imageurls');

        $aeopNationalQuoteConfiguration = $objProduct->getData('aeop_national_quote_configuration');

        $arrProduct['category_id'] = $categoryId ? $categoryId : $arrProduct['category_id'];

        //是否有营销图
        $arrProduct['is_market_image'] = (new ExpressHelper())->marketImageCheck($arrProduct['category_id']);
        $arrProduct['goods_title'] = '';
        $arrProduct['id'] = (string)$arrProduct['id'];

        $arrProduct['transport_property'] = '';
        $arrProduct['is_goods_tort'] = 0;

        if (!empty($arrProduct['goods_id'])) {
            $goods = Cache::store('goods')->getGoodsInfo($arrProduct['goods_id']);
            if ($goods) {
                $arrProduct['goods_title'] = $goods['name'];
                $goodsServer = $this->invokeServer(\app\goods\service\GoodsHelp::class);
                $arrProduct['category_name'] = $goodsServer->mapCategory($goods['category_id']);
            }

            //物流属性
            $arrProduct['transport_property'] = (new GoodsHelp())->getPropertiesTextByGoodsId($arrProduct['goods_id']);
        }


        $arrProduct['detail'] = $arrProduct['product_info']['detail'];

        $preg = '/<img[\s\S]*?src\s*=\s*[\"|\'](.*?)[\"|\'][\s\S]*?>/';
        preg_match_all($preg, $arrProduct['detail'], $match);
        $arrDetail['detail_images'] = [];
        if (count($match) > 1) {
            $arrDetail['detail_images'] = $match[1];
        }


        //营销图
        $market_images = $arrProduct['product_info']['market_images'];
        $arrProduct['market_images'] = $market_images ? json_decode($market_images, true) : [];
        $arrProduct['mobileDetail'] = json_decode($arrProduct['product_info']['mobile_detail'], true);
        //产品普通属性
        $arrAttr = json_decode($arrProduct['product_info']['product_attr'], true);
        $arrAttr = $this->bulidAttrData($arrProduct['category_id'], $arrAttr);
        //根据分类及账号ID获取相应品牌信息
        $arrBrand = AliexpressAccountBrand::getBrandByAccount($arrProduct['account_id'], $arrProduct['category_id']);

        //获取分类所有sku属性
        $arrSkuAttr = $aliexpressCategoryAttrModel->getCategoryAttr($arrProduct['category_id'], 1);
        if (!empty($arrSkuAttr)) {
            $arrSkuAttr = collection($arrSkuAttr)->toArray();
            $arrSkuAttr = array_combine(array_column($arrSkuAttr, 'id'), $arrSkuAttr);
        } else {
            $arrSkuAttr = [];
        }

        //sku listing信息
        $arrSku = $arrProduct['product_sku'];

        //获取属性对应关系数据
        $arrMapping = json_decode($arrProduct['product_info']['attr_ext_info'], true);
        $arrProduct['attr_ext_info'] = $arrMapping;
        if (!empty($arrMapping)) {
            $arrKeys = array_column($arrMapping, 'ali_id');
            $arrVals = array_column($arrMapping, 'attribute_id');
            $arrMapping = array_combine($arrKeys, $arrVals);
        }

        if (!empty($arrSku)) {
            foreach ($arrSku as &$sku) {

                $sku['sku_attr'] = json_decode($sku['sku_attr'], true);
                $sku['sku_attr_relation'] = json_decode($sku['sku_attr_relation'], true);
                $sku['sku_attributes'] = $sku['sku_attr'] ? $sku['sku_attr'] : [];
                $images = [];
                if (isset($sku['goods_sku_id']) && $sku['goods_sku_id']) {
                    $images = GoodsGallery::where(['sku_id' => $sku['goods_sku_id'], 'is_default' => 1])->select();
                }
                $sku['d_imgs'] = $images;
                unset($sku['sku_attr']);
            }
        }


        $arrSku = $this->bulidSkuAttrData($arrSkuAttr, $arrSku, $arrProduct['goods_id']);

        $skuPropertyId = $arrSku['skuPropertyId'];

        //默认区域分组
        $arrProduct['region_group_id'] = isset($arrProduct['product_info']['region_group_id']) && $arrProduct['product_info']['region_group_id'] ? $arrProduct['product_info']['region_group_id'] : '';
        //默认区域模板
        $arrProduct['region_template_id'] = isset($arrProduct['product_info']['region_template_id']) && $arrProduct['product_info']['region_template_id'] ? $arrProduct['product_info']['region_template_id'] : '';

        unset($arrProduct['product_info'], $arrProduct['product_sku']);
        $arrDetail = $arrProduct;
        $account = Cache::store('AliexpressAccount')->getAccountById($arrDetail['account_id']);
        $arrDetail['account_code'] = $account['code'];

        $arrDetail['relation_template_id'] = !empty($arrDetail['relation_template_id']) ? $arrDetail['relation_template_id'] : '';
        $arrDetail['relation_template_postion'] = !empty($arrDetail['relation_template_postion']) ? $arrDetail['relation_template_postion'] : '';
        $arrDetail['custom_template_id'] = !empty($arrDetail['custom_template_id']) ? $arrDetail['custom_template_id'] : '';
        $arrDetail['custom_template_postion'] = !empty($arrDetail['custom_template_postion']) ? $arrDetail['custom_template_postion'] : '';
        $arrDetail['salesperson_id'] = !empty($arrDetail['salesperson_id']) ? $arrDetail['salesperson_id'] : '';
        $arrDetail['is_wholesale'] = $arrDetail['bulk_discount'] ? 1 : 0;
        $arrDetail['warehouse_id'] = $arrDetail['warehouse_id'] ? $arrDetail['warehouse_id'] : '';
        $arrDetail['sales'] = $arrDetail['salesperson_id'];
        $arrDetail['sizechart_id'] = $arrDetail['sizechart_id'] ? $arrDetail['sizechart_id'] : '';
        $arrDetail['title'] = $arrDetail['subject'];
        $arrDetail['deliveryTime'] = $arrDetail['delivery_time'];
        $arrDetail['promiseTemplateId'] = $arrDetail['promise_template_id'];
        $arrDetail['freightTemplateId'] = $arrDetail['freight_template_id'] ? $arrDetail['freight_template_id'] : '';
        $arrDetail['productUnit'] = $arrDetail['product_unit'];
        $arrDetail['lotNum'] = $arrDetail['lot_num'];
        $arrDetail['packageLength'] = $arrDetail['package_length'];
        $arrDetail['packageWidth'] = $arrDetail['package_width'];
        $arrDetail['packageHeight'] = $arrDetail['package_height'];
        $arrDetail['grossWeight'] = $arrDetail['gross_weight'];
        $arrDetail['isPackSell'] = $arrDetail['is_pack_sell'];
        $arrDetail['baseUnit'] = $arrDetail['base_unit'];
        $arrDetail['addUnit'] = $arrDetail['add_unit'];
        $arrDetail['addWeight'] = $arrDetail['add_weight'];
        $arrDetail['wsValidNum'] = $arrDetail['ws_valid_num'];
        $arrDetail['bulkOrder'] = $arrDetail['bulk_order'];
        $arrDetail['bulkDiscount'] = $arrDetail['bulk_discount'];
        $arrDetail['reduceStrategy'] = $arrDetail['reduce_strategy'] ? AliexpressProduct::REDUCE_STRATEGY[$arrDetail['reduce_strategy']] : '';
        $groupIds = json_decode($arrDetail['group_id'], true);
        $arrDetail['groupId'] = !empty($groupIds) ? (is_array($groupIds) ? implode(',', $groupIds) : $groupIds) : '';
        $arrDetail['currencyCode'] = $arrDetail['currency_code'];
        unset($arrDetail['subject'], $arrDetail['delivery_time'], $arrDetail['promise_template_id'], $arrDetail['freight_template_id'], $arrDetail['product_unit'],
            $arrDetail['lot_num'], $arrDetail['package_length'], $arrDetail['package_width'], $arrDetail['package_height'], $arrDetail['gross_weight'], $arrDetail['is_pack_sell'],
            $arrDetail['base_unit'], $arrDetail['add_unit'], $arrDetail['ws_valid_num'], $arrDetail['bulk_order'], $arrDetail['bulk_discount'], $arrDetail['reduce_strategy'],
            $arrDetail['group_id'], $arrDetail['currency_code']
        );
        //设置平台属性被用到的属性值
        if (!empty($arrSkuAttr)) {
            $used_attr_values = $arrSku['used_ali_attr_values'];
            foreach ($arrSkuAttr as &$sku_attr) {
                $sku_attr['used_vaules'] = isset($used_attr_values[$sku_attr['id']]) ? array_values($used_attr_values[$sku_attr['id']]) : [];
            }
        }
        $arrDetail['imageURLs'] = explode(';', $arrDetail['imageurls']);


        if ($arrDetail['category_id']) {
            $allCategory = AliexpressCategory::getAllParent($arrDetail['category_id']);

            $arrDetail['category_relation'][] = (new ExpressHelper())->categoryTree($allCategory);

            if ($mapCategory = (new ExpressHelper())->getBindAndPublishCategory($arrDetail['category_id'])) {
                foreach ($mapCategory as $map) {
                    $categoryAttr = AliexpressCategory::getAllParent($map['channel_category_id']);

                    $arrDetail['category_relation'][] = (new ExpressHelper())->categoryTree($categoryAttr);
                }
            }

        } else {
            $arrDetail['category_relation'][] = [];
        }


        $brand = [];
        if ($arrAttr['brand']) {
            $select_brand = (new AliexpressCategoryAttrVal())->where('id', $arrAttr['brand'])->find();
            if ($select_brand) {
                $brand[] = $select_brand->toArray();
            }
        }
        if (!empty($brand)) {
            $arrDetail['brand'] = array_merge($arrBrand, $brand);
        } else {
            $arrDetail['brand'] = $arrBrand;
        }

        if (empty($aeopNationalQuoteConfiguration)) {
            $arrDetail['aeopNationalQuoteConfiguration'] = (new ExpressHelper())->getQuoteCountry();
        } else {

            $aeopNationalQuoteConfiguration = is_array($aeopNationalQuoteConfiguration) ? $aeopNationalQuoteConfiguration : json_decode($aeopNationalQuoteConfiguration, true);

            if (!is_array($aeopNationalQuoteConfiguration)) {
                $aeopNationalQuoteConfiguration = json_decode($aeopNationalQuoteConfiguration, true);
            }

            $arrDetail['aeopNationalQuoteConfiguration'] = is_string($aeopNationalQuoteConfiguration) ? [] : $aeopNationalQuoteConfiguration;
        }

        unset($arrDetail['aeop_national_quote_configuration']);
        $arrDetail['base_url'] = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;
        $arrDetail['attr_info'] = isset($arrAttr['ali_attr']) ? $arrAttr['ali_attr'] : [];
        $arrDetail['listing_info'] = isset($arrSku['arrSku']) ? $arrSku['arrSku'] : [];
        $arrDetail['brand_id'] = isset($arrAttr['brand']) ? $arrAttr['brand'] : '';

        $arrSkuData = $this->getSkuAttrInfo($objProduct['goods_id'], $objProduct['account_id']);

        //$arrDetail['sku_attr_info'] =isset($arrSkuData['ali_attr'])?array_values($arrSkuData['ali_attr']):$arrSkuAttr;
        $arrDetail['sku_attr_info'] = $arrSkuAttr;
        if (isset($arrSkuData['local_attr'])) {
            $local_attr = array_values($arrSkuData['local_attr']);
        } elseif (isset($arrSku['loaclAttr'])) {
            $local_attr = $arrSku['loaclAttr'];
        } else {
            $local_attr = [];
        }

        if ($local_attr) {
            foreach ($local_attr as $key => $val) {

                if ($val['attribute_name'] == 'type' || $val['attribute_code'] == 'type') {
                    $local_attr[$key]['ali_attr_id'] = $skuPropertyId;
                }
            }
        }
        $arrDetail['local_attr'] = $local_attr;


        $arrDetail = $this->getGroupTemplates($arrDetail);
        return $arrDetail;
    }


    /**
     *根据用户id,查询分组,区域模板
     *
     */
    public function getGroupTemplates($arrDetail)
    {

        //根据登录id 获取分组, 区域模板
        $userInfo = Common::getUserInfo();
        $userIds = $this->getUnderlingInfo($userInfo['user_id']);//获取下属人员信息

        $groupRegion = [];
        $regionTemplate = [];

        if ($userIds) {

            $groupRegionModel = new AliexpressGroupRegionModel;
            $userIds = implode(',', $userIds);

            //根据用户id获取分组
            $groupRegion = $groupRegionModel->field('id as region_group_id,parent_id,name')->where('parent_id', '=', 0)->whereIn('create_id', $userIds)->select();

            if ($groupRegion) {

                foreach ($groupRegion as $key => $val) {

                    if (isset($arrDetail['region_group_id']) && $val['region_group_id'] == $arrDetail['region_group_id']) {
                        $groupRegion[$key]['checked'] = 1;
                    }
                }
            }


            $where = [];
            if (isset($arrDetail['region_group_id']) && $arrDetail['region_group_id']) {
                $where = ['parent_id' => ['=', $arrDetail['region_group_id']]];
            } else {
                $where = ['parent_id' => ['>', 0]];
            }

            $regionTemplate = $groupRegionModel->field('id as region_template_id,parent_id,name,type,aeopNationalQuoteConfiguration')->where($where)->whereIn('create_id', $userIds)->select();

            //根据用户id获取区域模板
            if ($regionTemplate) {

                foreach ($regionTemplate as $key => $val) {
                    if (isset($val['region_template_id']) && $val['region_template_id'] == $arrDetail['region_template_id']) {
                        $regionTemplate[$key]['checked'] = 1;
                    }
                }
            }

        }


        $arrDetail['group_region'] = $groupRegion;
        $arrDetail['region_template'] = $regionTemplate;

        return $arrDetail;
    }

    /**
     * @param $intGoodsId
     * @param $intAccountId
     * @param bool $intAliCategoryId
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *
     */
    private function getSkuAttrInfo($intGoodsId, $intAccountId, $intAliCategoryId = false)
    {

        // 第一步、获取速卖通该分类下面的所有类目属性和SKU属性(除品牌)
        $objAliexpressCategoryAttr = AliexpressCategoryAttr::where(['category_id' => $intAliCategoryId, 'id' => ['neq', 2]])
            ->field('id,parent_attr_id,required,spec,names_zh,names_en,sku,units,attribute_show_type_value,customized_pic,customized_name,list_val')
            ->select();
        $helper = new ExpressHelper;
        $arrAliexpressCategoryAttr = $helper->ObjToArray($objAliexpressCategoryAttr);

        $publishAttr = AliexpressPublishTemplate::where(['channel_category_id' => $intAliCategoryId, 'goods_id' => $intGoodsId])->find();

        if ($publishAttr) {
            $publishAttrData = json_decode($publishAttr['data'], true);
        } else {
            $publishAttrData = [];
        }

        $arrAttr = [];//普通属性
        $arrSku = [];//SKU属性

        foreach ($arrAliexpressCategoryAttr as $arrValue) {
            if ($arrValue['sku']) {
                $arrValue['used_vaules'] = [];
                $arrSku[$arrValue['id']] = $arrValue;
            } else {
                $arrAttr[] = $helper->getChoosedAttribute($arrValue, $publishAttrData);
            }
        }

        $goodsInfomation = Goods::where('id', $intGoodsId)->find();

        if (empty($goodsInfomation)) {
            return [];
        }

        $develop_id = $goodsInfomation['channel_id'];

        //第二步、获取该产品本地的SKU属性
        $skus = GoodsSku::where('goods_id', $intGoodsId)->whereIn('status', [1, 4])->order('sku ASC')->select();
        $objGoodsListing = $helper->GoodsSkuAttrJsonToArray($skus);
        $arrGoodsListing = $helper->ObjToArray($objGoodsListing);

        //拼装SKU生成Listing部分数据
        $arrSkuData = $helper->getSkuInfo($arrSku, $arrGoodsListing, $intAliCategoryId);

        return $arrSkuData;
    }

    public function getLocalAttrInfoByGoodsId($arrSkuAttr, $goodsId)
    {
        if (empty($arrSkuAttr)) {
            return [];
        }
        $expressHelpServer = $this->invokeServer(ExpressHelper::class);
        $objGoodsListing = $expressHelpServer->GoodsSkuAttrJsonToArray(GoodsSku::where('goods_id', $goodsId)->select());
        $arrGoodsListing = json_decode(json_encode($objGoodsListing), true);
        $arrSkuData = $expressHelpServer->getSkuInfo($arrSkuAttr, $arrGoodsListing);
        return $arrSkuData;
    }

    /**
     * 组装普通属性信息
     * @param $categoryId
     * @param array $attrData
     * @return array
     */
    public function bulidAttrData($categoryId, $attrData)
    {
        $result = [
            'ali_attr' => [
                'attr' => [],
                'diy_attr' => []
            ],
            'brand' => ''
        ];
        if (empty($attrData)) {
            return $result;
        }
        $aliexpressCategoryAttrModel = new AliexpressCategoryAttr();
        //获取所有普通属性
        $arrAllAttr = $aliexpressCategoryAttrModel->getCategoryAttr($categoryId, 0, ['id' => ['neq', 2]]);
        if (empty($arrAllAttr)) {
            return $result;
        }
        $arrAttr = [];
        $arrDiyAttr = [];
        $brand = '';    //选中的品牌

        foreach ($attrData as $attr) {
            //获取属性信息
            if (isset($attr['attrName'])) {
                $arrDiyAttr[] = $attr;
                continue;
            }
            if ($attr['attrNameId'] == 2) {
                $brand = $attr['attrValueId'];
            }
            if (isset($attr['attrValue'])) {
                $arrAttr[$attr['attrNameId']]['attrValue'] = $attr['attrValue'];
                if (isset($attr['attrValueId'])) {
                    $arrAttr[$attr['attrNameId']]['attrValueId'] = $attr['attrValueId'];
                }
            } else {
                $arrAttr[$attr['attrNameId']][] = isset($attr['attrValueId']) ? $attr['attrValueId'] : '';
            }
        }
        foreach ($arrAllAttr as &$item) {
            if (isset($arrAttr[$item['id']])) {
                switch ($item['attribute_show_type_value']) {
                    case 'list_box':
                        $item['default_id'] = implode(',', $arrAttr[$item['id']]) ? intval(implode(',', $arrAttr[$item['id']])) : '';
                        if (isset($arrAttr[$item['id']]['attrValue'])) {
                            $item['default_value'] = $arrAttr[$item['id']]['attrValue'];
                        }
                        if (isset($arrAttr[$item['id']]['attrValueId'])) {
                            $item['default_id'] = intval($arrAttr[$item['id']]['attrValueId']);
                        }
                        break;
                    case 'input':
                        $item['default_value'] = isset($arrAttr[$item['id']]['attrValue']) ? $arrAttr[$item['id']]['attrValue'] : '';
                        break;
                    case 'input_int':
                        $item['default_value'] = isset($arrAttr[$item['id']]['attrValue']) ? $arrAttr[$item['id']]['attrValue'] : '';
                        break;
                    case 'input_string':
                        $item['default_value'] = isset($arrAttr[$item['id']]['attrValue']) ? $arrAttr[$item['id']]['attrValue'] : '';
                        break;
                    case 'check_box':
                        if (!empty($arrAttr[$item['id']])) {
                            $attr_value = '';
                            if (isset($arrAttr[$item['id']]['attrValue'])) {
                                $attr_value = $arrAttr[$item['id']]['attrValue'];
                                unset($arrAttr[$item['id']]['attrValue']);
                            }
                            $item['default_value'] = $attr_value;
                        }
                        $item['default_id'] = array_values($arrAttr[$item['id']]);
                        break;
                    case 'intervel':
                        break;
                    default:
                        break;
                }
            }
        }
        $result['ali_attr'] = ['attr' => $arrAllAttr, 'diy_attr' => $arrDiyAttr];
        $result['brand'] = $brand;
        return $result;
    }

    /**
     * 组装sku属性信息
     * @param array $arrSkuAttr 分类sku属性
     * @param array $arrSku listing信息
     * @return array
     */
    public function bulidSkuAttrData(array &$arrSkuAttr, array $arrSkus, $goodsId = '')
    {
        $arr_used_loacl_attr = [];  //用到的本地属性
        $used_ali_attr_values = [];//用到的平台sku属性值
        $arr_local_attr = Cache::store('Attribute')->getAttribute();    //获取所有本地产品属性

        $arr_no_sku_code = [];  //未对应到的sku_code
        //将对应本地同一sku的合并为一条记录
        $arrSku = [];
        if (!empty($goodsId)) {
            foreach ($arrSkus as $sku) {
                $arrSku[$sku['id']][] = $sku;
            }
        } else {
            //去除发货地属性200007763后，其他属性一致则合并
            foreach ($arrSkus as $k => $sku) {
                $arr_attr_v = [];
                $fag = false;
                foreach ($sku['sku_attributes'] as $v_attr) {
                    if ($v_attr['skuPropertyId'] != '200007763') {
                        $arr_attr_v[$v_attr['skuPropertyId']] = $v_attr['propertyValueId'];
                    } else {
                        $fag = true;
                    }
                }
                if (!$fag) {
                    $arrSku[$k][] = $sku;
                } else {
                    asort($arr_attr_v);
                    $arrSku[md5(json_encode($arr_attr_v))][] = $sku;
                }
            }
        }


        foreach ($arrSku as $k => $sku) {

            $extend = [];
            foreach ($sku as $key => $i_sku) {

                foreach ($i_sku['sku_attributes'] as $i_attr) {

                    $used_ali_attr_values[$i_attr['skuPropertyId']][$i_attr['propertyValueId']] = $i_attr['propertyValueId'];
                    if (isset($arrSkuAttr[$i_attr['skuPropertyId']])) {
                        $this_attr = $arrSkuAttr[$i_attr['skuPropertyId']];
                        $this_attr_values = $this_attr['list_val'];
                        $this_attr_values = array_combine(array_column($this_attr_values, 'id'), $this_attr_values);
                        if ($i_attr['skuPropertyId'] == '200007763') {
                            $extend[] = [
                                'ali_attr_id' => $i_attr['skuPropertyId'],
                                'ali_attr_val_id' => $i_attr['propertyValueId'],
                                'propertyValueId' => $i_attr['propertyValueId'],
                                'retail_price' => $i_sku['sku_price'],
                                'sku_price' => $i_sku['sku_price'],
                                'sku_stock' => $i_sku['sku_stock'],
                                'ipm_sku_stock' => $i_sku['ipm_sku_stock'],
                                'currency_code' => $i_sku['currency_code'],
                                'name_zh' => $this_attr_values[$i_attr['propertyValueId']]['name_zh'],
                            ];
                        }
                    }

                }

            }
            $arrSku[$k] = $sku[0];
            $arrSku[$k]['extend'] = $extend;
        }
        $cache = Cache::store('Goods');


        $skuPropertyId = '';
        foreach ($arrSku as &$sku) {
            $skuInfo = [];
            if (isset($sku['goods_sku_id']) && $sku['goods_sku_id']) {
                $skuInfo = $cache->getSkuInfo($sku['goods_sku_id']);
            }

            if ($skuInfo) {
                $sku['cost_price'] = $skuInfo['cost_price'];
                $sku['weight'] = $skuInfo['weight'];

            } else {
                $sku['cost_price'] = $sku['weight'] = 0;
            }

            $sku['sku'] = isset($sku['sku_code']) ? $sku['sku_code'] : '';
            $sku['combine_sku'] = isset($sku['combine_sku']) ? $sku['combine_sku'] : '';
            $sku['retail_price'] = isset($sku['sku_price']) ? $sku['sku_price'] : 0;
            $sku['sku_attributes'] = array_combine(array_column($sku['sku_attributes'], 'skuPropertyId'), $sku['sku_attributes']);

            foreach ($sku['sku_attributes'] as &$attr) {

                $attr['value'] = '';
                if ($skuInfo) {
                    $skuInfo = GoodsSku::where('id', $sku['goods_sku_id'])->find();
                    $arrTemp = join(',', json_decode($skuInfo->sku_attributes, true));

                    $sku_attributes = AttributeValue::where('p1.id', 'IN', $arrTemp)
                        ->field('p1.id,p1.attribute_id,p1.code,p1.value,p2.name as attribute_name,p2.code as attribute_code')
                        ->alias('p1')
                        ->join('attribute p2', 'p2.id=p1.attribute_id', 'LEFT')
                        ->find();

                    $code = strtolower($sku_attributes['attribute_code']);
                    //如果类型是type获取style,则取goods_attribute表里的aliag
                    if ($code == 'type' || $code == 'style') {
                        $where = [
                            'goods_id' => ['=', $skuInfo['goods_id']],
                            'attribute_id' => ['=', $sku_attributes['attribute_id']],
                            'value_id' => ['=', $sku_attributes['id']]
                        ];
                        $goodsAttribute = GoodsAttribute::where($where)->field('alias')->find();
                        if ($goodsAttribute) {
                            $attr['value'] = $goodsAttribute['alias'];
                        }

                        $skuPropertyId = $attr['skuPropertyId'];
                    }
                }

                $used_ali_attr_values[$attr['skuPropertyId']][$attr['propertyValueId']] = $attr['propertyValueId'];
                $attr['ali_attr_id'] = $attr['skuPropertyId'];
                $attr['ali_attr_val_id'] = $attr['propertyValueId'];
                $attr['attribute_id'] = 0;
                $attr['id'] = '';
                $attr['attribute_name'] = 0;
                $attr['code'] = '';
                $attr['custom_name'] = isset($attr['propertyValueDefinitionName']) ? $attr['propertyValueDefinitionName'] : '';
                $attr['custom_pic'] = isset($attr['skuImage']) ? $attr['skuImage'] : '';
                if (!empty($arrSkuAttr)) {
                    $attr['customized_name'] = isset($arrSkuAttr[$attr['skuPropertyId']]['customized_name']) ? $arrSkuAttr[$attr['skuPropertyId']]['customized_name'] : '';
                    $attr['customized_pic'] = isset($arrSkuAttr[$attr['skuPropertyId']]['customized_pic']) ? $arrSkuAttr[$attr['skuPropertyId']]['customized_pic'] : '';
                    if (isset($arrSkuAttr[$attr['skuPropertyId']])) {
                        $arrSkuAttr[$attr['skuPropertyId']]['is_checked'] = 1;
                    }
                }
            }

            if (!empty($sku['sku_attr_relation'])) {
                foreach ($sku['sku_attr_relation'] as $attr_relation) {
                    if (isset($attr_relation['attribute_id']) && $attr_relation['attribute_id']) {
                        //在平台属性中设置对应的本地属性
                        if (!empty($arrSkuAttr) && isset($arrSkuAttr[$attr_relation['skuPropertyId']])) {
                            $arrSkuAttr[$attr_relation['skuPropertyId']]['attribute_id'] = $attr_relation['attribute_id'];
                            $arrSkuAttr[$attr_relation['skuPropertyId']]['attribute_name'] = $arr_local_attr[$attr_relation['attribute_id']]['name'];
                        }
                        $sku['sku_attributes'][$attr_relation['skuPropertyId']]['attribute_code'] = $arr_local_attr[$attr_relation['attribute_id']]['code'];
                        $sku['sku_attributes'][$attr_relation['skuPropertyId']]['attribute_name'] = $arr_local_attr[$attr_relation['attribute_id']]['name'];
                        if (isset($attr_relation['attribute_value_id']) && $attr_relation['attribute_value_id']) {
                            $sku['sku_attributes'][$attr_relation['skuPropertyId']]['id'] = $attr_relation['attribute_value_id'];
                            $sku['sku_attributes'][$attr_relation['skuPropertyId']]['code'] = isset($arr_local_attr[$attr_relation['attribute_id']]['value'][$attr_relation['attribute_value_id']]['code']) ? $arr_local_attr[$attr_relation['attribute_id']]['value'][$attr_relation['attribute_value_id']]['code'] : '';
                            $sku['sku_attributes'][$attr_relation['skuPropertyId']]['value'] = isset($arr_local_attr[$attr_relation['attribute_id']]['value'][$attr_relation['attribute_value_id']]['value']) ? $arr_local_attr[$attr_relation['attribute_id']]['value'][$attr_relation['attribute_value_id']]['value'] : '';
                        }

                        $sku['sku_attributes'][$attr_relation['skuPropertyId']]['attribute_id'] = $attr_relation['attribute_id'];
                        //放入被使用的本地属性


                        $arr_used_loacl_attr[$attr_relation['attribute_id']] = [
                            'ali_attr_id' => isset($attr_relation['ali_id']) ? $attr_relation['ali_id'] : '',
                            'attribute_id' => $attr_relation['attribute_id'],
                            'attribute_name' => $arr_local_attr[$attr_relation['attribute_id']]['name'],
                            'attribute_code' => $arr_local_attr[$attr_relation['attribute_id']]['code'],
                        ];
                    }

                }
            } else {
                $arr_no_sku_code[] = $sku['sku_code'];
            }
            unset($sku['sku_attr_relation']);
        }

        if (!empty($arr_no_sku_code)) {
            $goodsSkuMapModel = new GoodsSkuMap();
            $arrSkuMap = $goodsSkuMapModel->where(['channel_sku' => ['in', $arr_no_sku_code], 'channel_id' => 4])->field('sku_id')->select();
            if (!empty($arrSkuMap)) {
                $arrSkuMap = collection($arrSkuMap)->toArray();
                $arrSkuInfo = GoodsSku::where(['id' => ['in', array_column($arrSkuMap, 'sku_id')]])->select();
                if (!empty($arrSkuInfo)) {
                    foreach ($arrSkuInfo as $item) {
                        $arrTemp = join(',', json_decode($item->sku_attributes, true));
                        $sku_attributes = AttributeValue::where('p1.id', 'IN', $arrTemp)
                            ->field('p1.id,p1.attribute_id,p1.code,p1.value,p2.name as attribute_name,p2.code as attribute_code')
                            ->alias('p1')
                            ->join('attribute p2', 'p2.id=p1.attribute_id', 'LEFT')
                            ->select();
                        if (!empty($sku_attributes)) {
                            foreach ($sku_attributes as $attributes) {
                                $arr_used_loacl_attr[$attributes['attribute_id']] = [
                                    'ali_attr_id' => '',
                                    'attribute_id' => $attributes['attribute_id'],
                                    'attribute_name' => $attributes['attribute_name'],
                                    'attribute_code' => $attributes['attribute_code'],
                                ];
                            }
                        }
                    }
                }
            }
        }
        $arrSkuAttr = array_values($arrSkuAttr);
        if (!empty($arrSkuAttr)) {
            foreach ($arrSkuAttr as &$item) {
                $item['attribute_id'] = isset($item['attribute_id']) ? $item['attribute_id'] : '';
                $item['attribute_name'] = isset($item['attribute_name']) ? $item['attribute_name'] : '';
                $item['list_val'] = array_values($item['list_val']);
            }
        }
        foreach ($arrSku as $k => $sku_item) {
            $arrSku[$k]['sku_attributes'] = array_values($sku_item['sku_attributes']);
        }
        return ['arrSku' => array_values($arrSku), 'loaclAttr' => array_values($arr_used_loacl_attr), 'used_ali_attr_values' => $used_ali_attr_values, 'skuPropertyId' => $skuPropertyId];
    }

    /**
     * 获取产品平台状态
     * @return array
     */
    public function getProductStatus()
    {
        $arrProductStatus = AliexpressProduct::PRODUCT_STATUS_DISPLAY;
        array_walk($arrProductStatus, function (&$value, $key) {
            $value = ['id' => $key, 'name' => $value];
        });
        array_unshift($arrProductStatus, ['id' => '', 'name' => '全部']);
        return array_values($arrProductStatus);
    }

    /**
     * 批量修改标题、服务模板、运费模板、毛重
     * @param $type
     * @param $productData
     * @return array
     */
    public function batchEditProduct($type, $productData)
    {
        $return = [
            'success' => 0,
            'fail' => 0,
            'error_message' => []
        ];
        $field = '';
        switch ($type) {
            case 'title':
                $field = 'subject';
                break;
            case 'promiseTemp':
                $field = 'promise_template_id';
                break;
            case 'freightTemp':
                $field = 'freight_template_id';
                break;
            case 'weight':
                $field = 'gross_weight';
                break;
            default:
                break;
        }
        foreach ($productData as $product) {
            $productModel = new AliexpressProduct();
            $objProduct = $productModel->where(['id' => $product['product_id'], 'status' => AliexpressProduct::PUBLISH_COMPLETED])->find();
            if (empty($objProduct)) {
                $return['fail']++;
                array_push($return['error_message'], 'ID为' . $product['product_id'] . '的商品不存在');
                continue;
            }
            $objProduct->$field = $product['value'];
            $objProduct->lock_update = 1;
            $objProduct->lock_product = 1;
            if ($objProduct->save()) {
                $return['success']++;
            } else {
                $return['fail']++;
                array_push($return['error_message'], '平台产品ID为' . $objProduct['product_id'] . '的商品修改失败:' . $objProduct->getError());
            }
        }
        $return['error_message'] = implode(';', $return['error_message']);
        return ['message' => '成功：' . $return['success'] . ',失败：' . $return['fail'] . '。' . $return['error_message']];
    }

    /**
     * 批量修改商品长、宽、高
     * @param $params
     * @return array
     */
    public function batchEditSize($params, $uid = 0)
    {

        $productIds = explode(',', $params['product_ids']);

        $remark = isset($params['remark']) ? $params['remark'] : '';

        $cron_time = isset($params['cron_time']) ? $params['cron_time'] : 0;

        $product = [];

        if ($productIds) {
            foreach ($productIds as $k => $productId) {
                $product[$k]['id'] = $productId;
                $product[$k]['package_length'] = $params['length'];
                $product[$k]['package_width'] = $params['width'];
                $product[$k]['package_height'] = $params['height'];
            }
        }
        $data = [];
        foreach ($product as $p) {
            $id = $p['id'];
            foreach ($p as $name => $v) {

                if ($name != 'id') {
                    $row = (new AliexpressProduct())->field($name)->where('id', '=', $id)->find();
                    if ($row[$name] != $v) {

                        $data = [
                            0 => [
                                'id' => $id,
                                $name => $v
                            ]
                        ];
                        if ($data) {
                            $data = json_encode($data);

                            $result = (new AliexpressListingHelper())->editProductData($data, $name, $uid, $remark, $cron_time);
                        }
                    }
                }
            }
        }

        $result = '修改成功';

        //$result = (new AliexpressListingHelper())->editMulitProductData($product,'package',$uid,$remark,$cron_time);

        return $result;
        exit();

        $return = [
            'success' => 0,
            'fail' => 0,
            'error_message' => []
        ];
        $productIds = explode(',', $params['product_ids']);
        foreach ($productIds as $productId) {
            $productModel = new AliexpressProduct();
            $objProduct = $productModel->where(['id' => $productId, 'status' => AliexpressProduct::PUBLISH_COMPLETED])->find();
            if (empty($objProduct)) {
                $return['fail']++;
                array_push($return['error_message'], 'ID为' . $productId . '的商品不存在');
                continue;
            }
            $objProduct->package_length = $params['length'];
            $objProduct->package_width = $params['width'];
            $objProduct->package_height = $params['height'];
            $objProduct->lock_update = 1;
            $objProduct->lock_product = 1;
            if ($objProduct->save()) {
                $return['success']++;
            } else {
                $return['fail']++;
                array_push($return['error_message'], '平台产品ID为' . $objProduct['product_id'] . '的商品修改失败:' . $objProduct->getError());
            }
        }
        $return['error_message'] = implode(';', $return['error_message']);
        return ['message' => '成功：' . $return['success'] . ',失败：' . $return['fail'] . '。' . $return['error_message']];
    }

    /**
     * 批量修改商品计数单位
     * @param $params
     * @return array
     */
    public function batchEditProductUnit($params, $uid = 0)
    {

        $productIds = explode(',', $params['product_ids']);

        $remark = isset($params['remark']) ? $params['remark'] : '';

        $cron_time = isset($params['cron_time']) ? $params['cron_time'] : 0;

        $product = [];

        if ($productIds) {
            foreach ($productIds as $k => $productId) {
                $product[$k]['id'] = $productId;
                $product[$k]['product_unit'] = $params['product_unit'];
                $product[$k]['package_type'] = $params['package_type'];
                if ($params['package_type'] == 1) {
                    $product[$k]['lot_num'] = $params['num'];
                }
            }
        }

        $result = (new AliexpressListingHelper())->editMulitProductData($product, 'productUnit', $uid, $remark, $cron_time);

        return $result;
        exit();

        $return = [
            'success' => 0,
            'fail' => 0,
            'error_message' => []
        ];
        $productIds = explode(',', $params['product_ids']);
        foreach ($productIds as $productId) {
            $productModel = new AliexpressProduct();
            $objProduct = $productModel->where(['id' => $productId, 'status' => AliexpressProduct::PUBLISH_COMPLETED])->find();
            if (empty($objProduct)) {
                $return['fail']++;
                array_push($return['error_message'], 'ID为' . $productId . '的商品不存在');
                continue;
            }
            $objProduct->product_unit = $params['product_unit'];
            $objProduct->package_type = $params['package_type'];
            if ($params['package_type'] == 1) {
                $objProduct->lot_num = $params['num'];
            }
            $objProduct->lock_update = 1;
            $objProduct->lock_product = 1;
            if ($objProduct->save()) {
                $return['success']++;
            } else {
                $return['fail']++;
                array_push($return['error_message'], '平台产品ID为' . $objProduct['product_id'] . '的商品修改失败:' . $objProduct->getError());
            }
        }
        $return['error_message'] = implode(';', $return['error_message']);
        return ['message' => '成功：' . $return['success'] . ',失败：' . $return['fail'] . '。' . $return['error_message']];
    }

    /**
     * 批量修改商品SKU价格
     * @param $productData
     * @return array
     */
    public function batchEditSkuPrice($productData)
    {
        $return = [
            'success' => 0,
            'fail' => 0,
            'error_message' => []
        ];
        foreach ($productData as $product) {
            $productSkuModel = new AliexpressProductSku();
            $objSku = $productSkuModel->where(['id' => $product['sku_id']])->find();
            if (empty($objSku)) {
                $return['fail']++;
                array_push($return['error_message'], 'SKU为' . $product['sku'] . '的数据不存在');
                continue;
            }
            $objSku->sku_price = $product['value'];
            $objSku->lock_sku = 1;
            Db::startTrans();
            try {
                $objSku->save();
                AliexpressProduct::update(['lock_update' => 1], ['id' => $objSku->ali_product_id]);
                $return['success']++;
                Db::commit();
            } catch (Exception $ex) {
                Db::rollback();
                $return['fail']++;
                array_push($return['error_message'], '平台产品ID为' . $objSku['sku_code'] . '的商品修改失败:' . $ex->getMessage());
            }
        }
        $return['error_message'] = implode(';', $return['error_message']);
        return ['message' => '成功：' . $return['success'] . ',失败：' . $return['fail'] . '。' . $return['error_message']];
    }

    /**
     * 获取已刊登商品简易列表
     * @param $params
     * @return array
     */
    public function getProductList($params)
    {
        $page = (isset($params['page']) && !empty($params['page'])) ? $params['page'] : 1;
        $pageSize = (isset($params['pageSize']) && !empty($params['pageSize'])) ? $params['pageSize'] : 10;
        $productModel = new AliexpressProduct();
        $where = 'status=' . AliexpressProduct::PUBLISH_COMPLETED . ' and product_status_type=' . AliexpressProduct::ONSELLING;
        if ($title = param($params, 'title')) {
            $where .= ' and subject like "%' . $title . '%"';
        }
        if ($expire_day = param($params, 'expire_day')) {
            $where .= ' and (expire_day>0 and expire_day<=' . $expire_day . ')';
        }
        if ($group_id = param($params, 'group_id')) {
            $where .= " and JSON_CONTAINS(group_id, '" . $group_id . "')";
        }
        if ($account_id = param($params, 'account_id')) {
            $where .= ' and account_id=' . $account_id;
        }
        $field = 'id,product_id,subject,imageurls';
        $count = $productModel->field($field)->where($where)->count();
        $productList = $productModel->field($field)->where($where)->page($page, $pageSize)->select();
        $list = [];
        if (!empty($productList)) {
            foreach ($productList as $product) {
                $arr_img = empty($product['imageurls']) ? [] : explode(';', $product['imageurls']);
                $list[] = [
                    'id' => $product['id'],
                    'product_id' => $product['product_id'],
                    'title' => $product['subject'],
                    'img' => empty($arr_img) ? '' : $arr_img[0]
                ];
            }
        }
        return ['data' => $list, 'count' => $count, 'page' => $page, 'totalpage' => ceil($count / $pageSize)];;
    }

    /**
     * 获取复制信息
     * @param $productId
     * @param int $categoryId
     * @return array
     */
    public function getCopyData($productId, $categoryId = 0)
    {
        $aliexpressCategoryAttrModel = new AliexpressCategoryAttr();
        $productModel = new AliexpressProduct();
        $field = 'id,subject,delivery_time,category_id,product_price,product_unit,package_type,lot_num,package_length,
        package_width,package_height,gross_weight,is_pack_sell,base_unit,add_unit,add_weight,ws_valid_num,bulk_order,bulk_discount,reduce_strategy,
        currency_code,imageurls,goods_id,goods_spu,is_balance,warehouse_id,plan_publish_time,
        salesperson_id,lock_update,relation_template_id,relation_template_postion,custom_template_id,custom_template_postion';
        $objProduct = $productModel->field($field)->with(['productSku', 'productInfo'])->find($productId);
        if (empty($objProduct)) {
            throw new AliPublishException('没有找到相关产品');
        }
        $arrProduct = $objProduct->toArray();
        $arrProduct['category_id'] = $categoryId ? $categoryId : $arrProduct['category_id'];
        $arrProduct['goods_title'] = '';

        //物流属性
        $arrProduct['transport_property'] = '';
        if (!empty($arrProduct['goods_id'])) {
            $goods = Cache::store('goods')->getGoodsInfo($arrProduct['goods_id']);
            $arrProduct['goods_title'] = $goods['name'];
            $goodsServer = $this->invokeServer(\app\goods\service\GoodsHelp::class);
            $arrProduct['category_name'] = $goodsServer->mapCategory($goods['category_id']);

            //物流属性
            $arrProduct['transport_property'] = (new GoodsHelp())->getPropertiesTextByGoodsId($arrProduct['goods_id']);
        }
        //产品详细
        $arrProduct['detail'] = $arrProduct['product_info']['detail'];
        $arrProduct['mobileDetail'] = json_decode($arrProduct['product_info']['mobile_detail']);
        //产品普通属性
        $arrAttr = json_decode($arrProduct['product_info']['product_attr'], true);
        $arrAttr = $this->bulidAttrData($arrProduct['category_id'], $arrAttr);

        //获取分类所有sku属性
        $arrSkuAttr = $aliexpressCategoryAttrModel->getCategoryAttr($arrProduct['category_id'], 1);
        if (!empty($arrSkuAttr)) {
            $arrSkuAttr = collection($arrSkuAttr)->toArray();
            $arrSkuAttr = array_combine(array_column($arrSkuAttr, 'id'), $arrSkuAttr);
        } else {
            $arrSkuAttr = [];
        }

        //sku listing信息
        $arrSku = $arrProduct['product_sku'];

        //获取属性对应关系数据
        $arrMapping = json_decode($arrProduct['product_info']['attr_ext_info'], true);
        $arrProduct['attr_ext_info'] = $arrMapping;

        if (!empty($arrSku)) {
            foreach ($arrSku as &$sku) {
                $sku['sku_attr'] = json_decode($sku['sku_attr'], true);
                $sku['sku_attr_relation'] = json_decode($sku['sku_attr_relation'], true);
                $sku['sku_attributes'] = $sku['sku_attr'] ? $sku['sku_attr'] : [];
                unset($sku['sku_attr']);
            }
        }
        $arrSku = $this->bulidSkuAttrData($arrSkuAttr, $arrSku, $arrProduct['goods_id']);
        unset($arrProduct['product_info'], $arrProduct['product_sku']);
        $arrDetail = $arrProduct;
        $arrDetail['account_code'] = '';
        $arrDetail['is_wholesale'] = $arrDetail['bulk_discount'] ? 1 : 0;
        $arrDetail['warehouse_id'] = $arrDetail['warehouse_id'] ? $arrDetail['warehouse_id'] : '';
        $arrDetail['sales'] = $arrDetail['salesperson_id'] ? $arrDetail['salesperson_id'] : '';
        $arrDetail['sizechart_id'] = '';
        $arrDetail['title'] = $arrDetail['subject'];
        $arrDetail['deliveryTime'] = $arrDetail['delivery_time'];
        $arrDetail['promiseTemplateId'] = '';
        $arrDetail['freightTemplateId'] = '';
        $arrDetail['productUnit'] = $arrDetail['product_unit'];
        $arrDetail['lotNum'] = $arrDetail['lot_num'];
        $arrDetail['packageLength'] = $arrDetail['package_length'];
        $arrDetail['packageWidth'] = $arrDetail['package_width'];
        $arrDetail['packageHeight'] = $arrDetail['package_height'];
        $arrDetail['grossWeight'] = $arrDetail['gross_weight'];
        $arrDetail['isPackSell'] = $arrDetail['is_pack_sell'];
        $arrDetail['baseUnit'] = $arrDetail['base_unit'];
        $arrDetail['addUnit'] = $arrDetail['add_unit'];
        $arrDetail['addWeight'] = $arrDetail['add_weight'];
        $arrDetail['wsValidNum'] = $arrDetail['ws_valid_num'];
        $arrDetail['bulkOrder'] = $arrDetail['bulk_order'];
        $arrDetail['bulkDiscount'] = $arrDetail['bulk_discount'];
        $arrDetail['reduceStrategy'] = $arrDetail['reduce_strategy'] ? AliexpressProduct::REDUCE_STRATEGY[$arrDetail['reduce_strategy']] : '';
        $arrDetail['groupId'] = '';
        $arrDetail['currencyCode'] = $arrDetail['currency_code'];
        unset($arrDetail['subject'], $arrDetail['delivery_time'], $arrDetail['promise_template_id'], $arrDetail['freight_template_id'], $arrDetail['product_unit'],
            $arrDetail['lot_num'], $arrDetail['package_length'], $arrDetail['package_width'], $arrDetail['package_height'], $arrDetail['gross_weight'], $arrDetail['is_pack_sell'],
            $arrDetail['base_unit'], $arrDetail['add_unit'], $arrDetail['ws_valid_num'], $arrDetail['bulk_order'], $arrDetail['bulk_discount'], $arrDetail['reduce_strategy'],
            $arrDetail['group_id'], $arrDetail['currency_code'], $arrDetail['id']
        );
        //设置平台属性被用到的属性值
        if (!empty($arrSkuAttr)) {
            $used_attr_values = $arrSku['used_ali_attr_values'];
            foreach ($arrSkuAttr as &$sku_attr) {
                $sku_attr['used_vaules'] = isset($used_attr_values[$sku_attr['id']]) ? array_values($used_attr_values[$sku_attr['id']]) : [];
            }
        }

        $allCategory = AliexpressCategory::getAllParent($arrDetail['category_id']);

        $arrDetail['category_relation'][] = (new ExpressHelper())->categoryTree($allCategory);

        if ($mapCategory = (new ExpressHelper())->getBindAndPublishCategory($arrDetail['category_id'])) {
            foreach ($mapCategory as $map) {
                $categoryAttr = AliexpressCategory::getAllParent($map['channel_category_id']);
                $arrDetail['category_relation'][] = (new ExpressHelper())->categoryTree($categoryAttr);
            }
        }
        $skuImages = [];
        $goods_brand = '';
        $goods_brand_id = 0;
        if (isset($objProduct['goods_id']) && $objProduct['goods_id']) {
            $goods_id = $objProduct['goods_id'];
            $productImages = PublishGoodsImageService::getPublishImages($goods_id, 4);

            $arrDetail['imageURLs'] = $productImages['spuImages'];
            $skuImages = $productImages['skuImages'];
            $goods = Cache::store('Goods')->getGoodsInfo($goods_id);
            if (isset($goods['brand_id']) && $goods['brand_id']) {
                $goodsBrand = Brand::where('id', $goods['brand_id'])->find();
                $goods_brand = $goodsBrand['name'];
                $goods_brand_id = $goods['brand_id'];
            }
        } else {
            $arrDetail['imageURLs'] = [];
        }

        $skus = [];

        if (isset($arrSku['arrSku']) && $arrSku['arrSku']) {
            $skus = $arrSku['arrSku'];

            $skus = PublishGoodsImageService::replaceSkuImage($skus, $skuImages, 4, 'goods_sku_id');

        }

        //$arrDetail['category_relation'] = AliexpressCategory::getAllParent($arrDetail['category_id']);
        $arrDetail['brand'] = [];
        $arrDetail['goods_brand_id'] = $goods_brand_id;
        $arrDetail['goods_brand'] = $goods_brand;
        $arrDetail['attr_info'] = isset($arrAttr['ali_attr']) ? $arrAttr['ali_attr'] : [];
        $arrDetail['listing_info'] = $skus;
        $arrDetail['sku_attr_info'] = $arrSkuAttr;
        $arrDetail['local_attr'] = isset($arrSku['loaclAttr']) ? $arrSku['loaclAttr'] : [];
        $arrDetail['base_url'] = Cache::store('configParams')->getConfig('outerPicUrl')['value'] . DS;
        $arraDetail['is_plan_publish'] = 0;

        return $arrDetail;
    }

    /**
     * 根据账号和分类获取品牌信息
     * @param $accountId
     * @param $categoryId
     * @return array
     */
    public function getBrands($accountId, $categoryId)
    {
        $brands = AliexpressAccountBrand::getBrandByAccount($accountId, $categoryId);
        return $brands;
    }

    /**
     * 获取未刊登产品数量
     * @return int|string
     */
    public function getUnpublishCount()
    {
        $model = new GoodsPublishMap();
        $count = $model->where(['channel' => 4, 'publish_status' => 0])->count();
        return $count;
    }

    /**
     * 获取产品数量
     * @param $where
     * @return int|string
     */
    public function getProductCount($where)
    {
        $model = new AliexpressProduct();
        $count = $model->where($where)->count();
        return $count;
    }

    /**
     * 获取停售待下架数量
     * @return int|string
     */
    public function getUnsaleCount()
    {
        $model = new AliexpressProduct();
        $join = [
            ['aliexpress_product_sku aps', 'p.id=aps.ali_product_id', 'left'],
            ['goods_sku gs', 'aps.goods_sku_id=gs.id', 'left']
        ];
        $where = [
            'p.status' => 2,
            'gs.status' => 2
        ];
        $count = $model->alias('p')->join($join)->where($where)->count('distinct(p.id)');
        return $count;
    }

    /**
     * 获取产品信息模板
     * @param $accountId
     * @param $type
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getProductTemp($accountId, $type)
    {
        $model = new AliexpressProductTemplate();
        $tempList = $model->field('id,name')->where(['account_id' => $accountId, 'type' => $type])->select();
        return $tempList;
    }


    /**
     * 批量修改成本价
     * @param $productData
     * @return array
     */
    public function changeCostPrice($params)
    {
        $productSkuModel = new AliexpressProductSku();
        $productSkuList = $productSkuModel->where(['product_id' => ['in', $params]])->select();

        foreach ($productSkuList as $value) {

            //启动事务
            Db::startTrans();
            try {
                $value = json_decode($value, true);

                //初始成本价和调整之后的成本价不同,则将初始成本价修改为调整价
                if ($value['current_cost'] != $value['pre_cost']) {
                    $productSkuModel->where(["id" => $value['id']])->update(['pre_cost' => $value['current_cost']]);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
            }
        }

        return ['message' => 'listing成本价更改成功'];
    }


    /**
     * 获取多个商品详细信息
     * @param $productId
     * @param $categoryId
     * @return array
     */
    public function getAliProductDetails($productIds, $accountIds)
    {
        $productModel = new AliexpressProduct();
        $field = 'configuration_type,aeop_national_quote_configuration,quote_config_status,id,account_id,product_id,subject,delivery_time,category_id,product_price,product_unit,package_type,lot_num,package_length,
        package_width,package_height,gross_weight,is_pack_sell,base_unit,add_unit,add_weight,ws_valid_num,bulk_order,bulk_discount,reduce_strategy,
        currency_code,promise_template_id,sizechart_id,freight_template_id,imageurls,goods_id,goods_spu,group_id,is_balance,warehouse_id,plan_publish_time,
        salesperson_id,lock_update,relation_template_id,relation_template_postion,custom_template_id,custom_template_postion';
        $objProducts = $productModel->field($field)->with(['productSku', 'productInfo'])->select($productIds);

        if (empty($objProducts)) {
            throw new AliPublishException('没有找到相关产品');
        }

        $productList = [];
        foreach ($objProducts as $key => $arrDetail) {

            $imageUrls = $arrDetail->getData('imageurls');
            $arrDetail = $arrDetail->toArray();

            //没有关联本地spu,则自动过滤
            if (!$arrDetail['goods_id']) {
                continue;
            }

            $arrDetail['product_id'] = 0;

            if (isset($arrDetail['reduce_strategy'])) {
                $strategry = [
                    1 => 'place_order_withhold',
                    2 => 'payment_success_deduct'
                ];
                $arrDetail['reduce_strategy'] = $strategry[$arrDetail['reduce_strategy']];
            }

            $arrDetail['goods_title'] = '';

            $arrDetail['detail'] = $arrDetail['product_info']['detail'];
            unset($arrDetail['product_info']['detail']);

            $preg = '/<img[\s\S]*?src\s*=\s*[\"|\'](.*?)[\"|\'][\s\S]*?>/';
            preg_match_all($preg, $arrDetail['detail'], $match);
            $arrDetail['detail_images'] = count($match) > 1 ? $match[1] : [];

            //手机详情
            $arrDetail['mobile_detail'] = $arrDetail['product_info']['mobile_detail'] ? json_decode($arrDetail['product_info']['mobile_detail'], true) : [];
            unset($arrDetail['product_info']['mobile_detail']);

            //产品普通属性
            $arrDetail['attr'] = json_decode($arrDetail['product_info']['product_attr'], true);
            unset($arrDetail['product_info']['product_attr']);
            //根据商品id 获取商品信息
            if ($arrDetail['goods_id']) {
                $goods = Cache::store('goods')->getGoodsInfo($arrDetail['goods_id']);
                if ($goods) {
                    $arrDetail['goods_title'] = $goods['name'];
                    $goodsServer = $this->invokeServer(\app\goods\service\GoodsHelp::class);
                    $arrDetail['category_name'] = $goodsServer->mapCategory($goods['category_id']);
                }
            }

            //获取分类所有sku属性
            $sku = $arrDetail['product_sku'];

            if ($sku) {
                foreach ($sku as $skuKey => $skuVal) {
                    $sku[$skuKey]['sku_attr'] = json_decode($skuVal['sku_attr'], true);
                }
            }

            $arrDetail['sku'] = $sku;
            unset($arrDetail['product_sku']);
            unset($arrDetail['id']);

            $arrDetail['imageurls'] = $imageUrls;

            $productList[$key] = $arrDetail;
        }


        //根据账号简称查询信息
        $aliexpressAccount = new AliexpressAccount();
        $accountIds = explode(',', $accountIds);
        $accountList = $aliexpressAccount->wherein('id', $accountIds)->field('id, code, account_name')->with(['user' => function ($query) {
            $query->where(['channel_id' => 4]);
        }])->select();

        $accountList = json_decode(json_encode($accountList), true);

        //将账号信息与产品信息匹配
        $prductData = [];
        foreach ($accountList as $k => $v) {
            foreach ($productList as $key => $val) {
                $val['account_id'] = $v['id'];
                $val['account_code'] = $v['code'];
                $val['account_name'] = $v['account_name'];
                $val['salesperson_id'] = $v['user']['seller_id'];
                $val['is_plan_publish'] = 0;
                $val['sizechart_id'] = 0;

                $imageurls = $val['imageurls'];

                $val = FieldAdjustHelper::adjust($val, 'publish', 'HTU');

                $val['imageurls'] = $imageurls;
                array_push($prductData, $val);
            }
        }

        return $prductData;
    }


    /**
     *根据品牌名称查询
     *
     */
    public function brandList($request)
    {
        try {

            //搜素条件
            $brandName = trim($request->get('snType'));
            $snText = trim($request->get('snText'));

            //当前页,默认为第一页
            $page = $request->get('page', 1);

            //每页显示多少条,默认为10条
            $pageSize = $request->get('pageSize', 10);

            $where = [];
            if ($brandName && $snText) {

                //名牌名称
                if ($brandName === 'brand_name') {
                    $where['name'] = ['like', "%$snText%"];
                }
            }

            $brandModel = new Brand;
            //总的条数
            $counts = $brandModel->where($where)->count();

            $brands = $brandModel->field('id as brand_id,name as brand_name')->where($where)->page($page, $pageSize)->select();

            return ['page' => $page, 'data' => $brands, 'pageSize' => $pageSize, 'count' => $counts];

        } catch (\Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }


    public function goodsTortInfo($goods_id)
    {
        //1.eBay平台,2.亚马逊平台,3.Wish平台,4.速卖通平台,5.CD平台,6.Lazada平台,7.Joom平台,8.MyMall平台,9.Shopee
        $channel_config = [1, 2, 3, 4, 5, 6, 7, 8, 9];

        $channelModel = new Channel();
        $channelList = $channelModel->field('id as channel, name')->whereIn('id', $channel_config)->select();

        $channelList = array_column($channelList, 'name', 'channel');

        $goods_tort = (new GoodsHelp())->getGoodsTortDescriptionByGoodsId($goods_id);

        if ($goods_tort) {
            $goods_tort = $goods_tort[$goods_id];
            $ebayAccount = new EbayAccount();
            $amazonAccount = new AmazonAccount();
            $wishAccount = new WishAccount();
            $aliAccount = new AliexpressAccount();

            foreach ($goods_tort as $key => $val) {
                $goods_tort[$key]['channel'] = isset($channelList[$val['channel_id']]) ? $channelList[$val['channel_id']] : '';

                $account_id = $val['account_id'];
                $where = ['id' => $account_id];

                $account_name = '';
                switch ($val['channel_id']) {
                    case 1://eBay平台
                        $account_name = $ebayAccount->field('account_name')->where($where)->value('account_name');
                        break;
                    case 2://亚马逊平台
                        $account_name = $amazonAccount->field('account_name')->where($where)->value('account_name');
                        break;

                    case 3://Wish平台
                        $account_name = $wishAccount->field('account_name')->where($where)->value('account_name');
                        break;

                    case 4://速卖通平台
                        $account_name = $aliAccount->field('account_name')->where($where)->value('account_name');
                        break;

                    case 5://CD平台

                        break;
                    case 6://Lazada平台

                        break;
                    case 7://Joom平台

                        break;

                    case 8://MyMall平台

                        break;

                    case 9://Shopee

                        break;
                }

                $goods_tort[$key]['account_name'] = (string)$account_name;
            }
        }

        return $goods_tort;
    }


    /**
     * 刊登异常列表
     * @param $params
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function failPublishList($params, $page = 1, $pageSize = 10, $status)
    {
        $productModel = new AliexpressProduct();
        $arrWhere = $this->getFilterCondition($params, $status);

        $field = 'p.goods_id,p.id,account_id,p.product_id,product_status_type,subject,goods_spu,group_id,product_min_price,product_max_price,plan_publish_time,
        promise_template_id,imageurls,p.update_time,freight_template_id,p.currency_code,salesperson_id,p.category_id,p.gmt_create,p.gross_weight,
        p.package_length,p.package_width,p.package_height,p.lot_num,p.package_type,p.product_unit,p.delivery_time,p.bulk_discount,p.lock_update,p.status,
        p.relation_template_id,p.relation_template_postion,p.custom_template_id,p.custom_template_postion,p.create_time';


        if(isset($arrWhere['data'])) {
            return $arrWhere;
        }

        if (!empty($params['start']) && !empty($params['end'])) {
            $arrWhere['p.update_time'] = ['BETWEEN TIME', [strtotime($params['start']), strtotime($params['end'] . ' 23:59:59')]];
        } elseif (!empty($params['start'])) {
            $arrWhere['p.update_time'] = ['EGT', strtotime($params['start'])];
        } elseif (!empty($params['end'])) {
            $arrWhere['p.update_time'] = ['ELT', strtotime($params['end'] . ' 23:59:59')];
        }
        
        $order_str = ' update_time DESC';


        $arrWhere['p.product_id'] = ['=', 0];

        $count = $productModel->alias('p')->where($arrWhere)->count('p.id');

        $list = $productModel->field($field)->alias('p')->with(['productSku', 'sku','plan','promise','freight'])->order($order_str)
            ->page($page, $pageSize)->where($arrWhere)->select();

        $arrAccount = Cache::store('AliexpressAccount')->getAllAccounts();

        $arr_attr_name = [];
        $arr_attr_val_name = [];


        if (!empty($list)) {

            $arrSkuStatus = [
                0 => '未上架',
                1 => '上架',
                2 => '下架',
                3 => '待发布',
                4 => '卖完下架',
                5 => '缺货'
            ];

            foreach ($list as &$product) {
                $product = $product->toArray();


                $product['product_unit_name'] = '';
                $product_unit = AliexpressProduct::PRODUCT_UNIT;
                if(isset($product_unit[$product['product_unit']])){
                    $product['product_unit_name'] = $product_unit[$product['product_unit']];
                }

                $product['freight_template_name'] = isset($product['freight']['template_name'])?$product['freight']['template_name']:'';
                $product['promise_template_name'] = isset($product['promise']['template_name'])?$product['promise']['template_name']:'';

                $arrProductSku = collection($product['product_sku'])->toArray();
                $product['stock'] = array_sum(array_column($arrProductSku, 'ipm_sku_stock'));


                $arrImgs = explode(';', $product['imageurls']);
                $product['main_img'] = !empty($arrImgs) && is_array($arrImgs) ? $arrImgs[0] : '';
                $product['account_code'] = $arrAccount[$product['account_id']]['code'];
                $product['product_min_price'] = round($product['product_min_price'], 2);
                $product['product_max_price'] = round($product['product_max_price'], 2);
                $product['is_wholesale'] = $product['bulk_discount'] > 0 ? 1 : 0;
                $bulk_discount = (100 - $product['bulk_discount']) / 100;
                $user = Cache::store('user')->getOneUser($product['salesperson_id']);
                $product['seller'] = empty($user) ? '' : $user['realname'];
                $product['price'] = $product['product_max_price'] == $product['product_min_price'] ? $product['product_min_price'] : $product['product_min_price'] . '-' . $product['product_max_price'];
                $product['wholesale_min_price'] = round($product['product_min_price'] * $bulk_discount, 2);
                $product['wholesale_max_price'] = round($product['product_max_price'] * $bulk_discount, 2);
                $product['wholesale_price'] = $product['wholesale_min_price'] == $product['wholesale_max_price'] ? $product['wholesale_max_price'] : $product['wholesale_min_price'] . '-' . $product['wholesale_max_price'];
                //获取sku信息
                $used_attr = $listing_attr_val = [];
                $goods_skus = [];
                if (!empty($product['sku'])) {
                    foreach ($product['sku'] as $item) {
                        $goods_skus[$item['id']] = $item;
                    }
                }
                unset($product['sku']);

                $is_stock = 1;
                foreach ($arrProductSku as $k => &$sku) {

                    //检查sku的可售量 是否有为0
                    if (empty($sku['ipm_sku_stock'])) {
                        $is_stock = 0;
                    }

                    $goods_sku = isset($goods_skus[$sku['goods_sku_id']]) ? $goods_skus[$sku['goods_sku_id']] : [];
                    $arrProductSku[$k]['thumb'] = !empty($goods_sku) ? $goods_sku['thumb'] : '';
                    $arrProductSku[$k]['local_sku'] = !empty($goods_sku) ? $goods_sku['sku'] : '';
                    $arrProductSku[$k]['local_status'] = !empty($goods_sku) ? $arrSkuStatus[$goods_sku['status']] : '';


                    $sku['wholesale_price'] = round($sku['sku_price'] * $bulk_discount, 2);
                    //sku属性ID及属性值ID转化成名字
                    $arr_sku_attr = json_decode($sku['sku_attr'], true);
                    if (!empty($arr_sku_attr)) {
                        foreach ($arr_sku_attr as &$attr) {

                            $attr['skuPropertyId'] = isset($attr['skuPropertyId']) ? $attr['skuPropertyId'] : '';

                            if (isset($arr_attr_name[$product['category_id'] . '_' . $attr['skuPropertyId']])) {
                                $attr['attr_name'] = $arr_attr_name[$product['category_id'] . '_' . $attr['skuPropertyId']];
                            } else {
                                $attr['attr_name'] = AliexpressCategoryAttr::getNameById($product['category_id'], $attr['skuPropertyId']);
                                $arr_attr_name[$product['category_id'] . '_' . $attr['skuPropertyId']] = $attr['attr_name'];
                            }
                            if ($attr['attr_name']) {
                                $used_attr[$attr['skuPropertyId']] = $attr['attr_name'];
                            }

                            if (isset($arr_attr_val_name[$attr['propertyValueId']])) {
                                $attr['attr_val_name'] = $arr_attr_val_name[$attr['propertyValueId']];
                            } else {
                                $attr['attr_val_name'] = AliexpressCategoryAttrVal::getNameById($attr['propertyValueId']);
                                $arr_attr_val_name[$attr['propertyValueId']] = $attr['attr_val_name'];
                            }
                            if (isset($used_attr[$attr['skuPropertyId']])) {
                                $listing_attr_val[$attr['skuPropertyId']][] = $attr['attr_val_name'] ? $attr['attr_val_name'] : '';
                            }
                        }
                        $sku['sku_attr'] = $arr_sku_attr;
                    }
                }


                unset($goods_skus);

                $product['used_attr'] = array_values($used_attr);
                $product['listing_attr_val'] = array_values($listing_attr_val);
                $product['id'] = (string)$product['id'];
                $product['product_sku'] = $arrProductSku;

                //如果是刊登失败，获取错误信息
                if ($product['status'] == AliexpressProduct::PUBLISH_FAIL) {
                    $error = json_decode($product['plan']['send_return'], true);

                    if(!isset($error['error_message'])) {
                        continue;
                    }

                    $product['error'] = $error;
                    $apiException = [];
                    if (isset($error['error_code']) && $error['error_code']) {
                        $error_code = $error['error_code'];

                        if($error_code == -9999) {

                            $error_code = explode('|', $error['error_message']);
                            $error_code = $error_code[0];

                            $error_code = str_replace('API_EXCEPTION:', '', $error_code);
                        }

                        $apiException = AliexpressApiException::where('code', $error_code)->find();
                    }else {

                        $error_message = explode(':', $error['error_message']);

                        if(isset($error_message[1]) && $error_message[1]) {
                            $error_message = $error_message[1] == 'code' ? $error_message[2] : $error_message[1];
                            $error_message = explode('|', $error_message)[0];

                            $apiException = AliexpressApiException::where('code', $error_message)->find();
                        }

                    }


                    if($apiException) {
                        $product['error_description'] = $apiException['description'];
                        $product['solution'] = $apiException['solution'];
                        continue;
                    }

                    if(is_string($error['error_message']) && strpos($error['error_message'],'file_get_contents') !== false || strpos($error['error_message'],"couldn't open file") !== false) {
                        $product['error_description'] = '图片同步问题,先检查图片是否上传,有图片再重新刊登';
                        $product['solution'] = '请参考错误描述解决';
                        continue;
                    }

                    $error_message = explode(':', $error['error_message']);
                    if(isset($error_message[2]) && isset(self::$publish_error_code[$error_message[2]])) {
                        $product['error_description'] = self::$publish_error_code[$error_message[2]];
                        $product['solution'] = '请参考错误描述解决';
                        continue;
                    }

                    $product['error_description'] = '请联系IT部速卖通平台技术开发人员';
                    $product['solution'] = '请联系IT部速卖通平台技术开发人员';

                }
            }
        }

        $base_url = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;
        return ['base_url' => $base_url, 'data' => $list, 'count' => $count, 'page' => $page, 'totalpage' => ceil($count / $pageSize)];
    }


    /**
     * 刊登队列列表
     * @param $params
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function waitPublishList($params, $page = 1, $pageSize = 10, $status)
    {
        $productModel = new AliexpressProduct();
        $arrWhere = $this->getFilterCondition($params, $status);
        $field = 'p.goods_id,p.id,account_id,p.product_id,product_status_type,subject,goods_spu,group_id,product_min_price,product_max_price,plan_publish_time,
        promise_template_id,imageurls,p.update_time,freight_template_id,p.currency_code,salesperson_id,p.category_id,p.gmt_create,p.gross_weight,
        p.package_length,p.package_width,p.package_height,p.lot_num,p.package_type,p.product_unit,p.delivery_time,p.bulk_discount,p.lock_update,p.status,
        p.relation_template_id,p.relation_template_postion,p.custom_template_id,p.custom_template_postion,p.create_time';

        if(isset($arrWhere['data'])) {
            return $arrWhere;
        }

        if (!empty($params['start']) && !empty($params['end'])) {
            $arrWhere['p.update_time'] = ['BETWEEN TIME', [strtotime($params['start']), strtotime($params['end'] . ' 23:59:59')]];
        } elseif (!empty($params['start'])) {
            $arrWhere['p.update_time'] = ['EGT', strtotime($params['start'])];
        } elseif (!empty($params['end'])) {
            $arrWhere['p.update_time'] = ['ELT', strtotime($params['end'] . ' 23:59:59')];
        }

        $arrWhere['p.product_id'] = ['=', 0];

        $order_str = ' update_time DESC';
        $count = $productModel->alias('p')->where($arrWhere)->count('distinct(p.id)');

        $list = $productModel->field($field)->alias('p')->with(['productSku', 'sku','promise','freight'])->order($order_str)
            ->page($page, $pageSize)->where($arrWhere)->select();


        $arrAccount = Cache::store('AliexpressAccount')->getAllAccounts();
        $arr_attr_name = [];
        $arr_attr_val_name = [];

        if (!empty($list)) {

            $arrSkuStatus = [
                0 => '未上架',
                1 => '上架',
                2 => '下架',
                3 => '待发布',
                4 => '卖完下架',
                5 => '缺货'
            ];

            foreach ($list as &$product) {
                $product = $product->toArray();

                $product['product_unit_name'] = '';
                $product_unit = AliexpressProduct::PRODUCT_UNIT;
                if(isset($product_unit[$product['product_unit']])){
                    $product['product_unit_name'] = $product_unit[$product['product_unit']];
                }

                $product['freight_template_name'] = isset($product['freight']['template_name'])?$product['freight']['template_name']:'';
                $product['promise_template_name'] = isset($product['promise']['template_name'])?$product['promise']['template_name']:'';

                $arrProductSku = collection($product['product_sku'])->toArray();
                $product['stock'] = array_sum(array_column($arrProductSku, 'ipm_sku_stock'));

                $arrImgs = explode(';', $product['imageurls']);
                $product['main_img'] = !empty($arrImgs) && is_array($arrImgs) ? $arrImgs[0] : '';
                $product['account_code'] = $arrAccount[$product['account_id']]['code'];
                $product['product_min_price'] = round($product['product_min_price'], 2);
                $product['product_max_price'] = round($product['product_max_price'], 2);
                $product['is_wholesale'] = $product['bulk_discount'] > 0 ? 1 : 0;
                $bulk_discount = (100 - $product['bulk_discount']) / 100;
                $user = Cache::store('user')->getOneUser($product['salesperson_id']);
                $product['seller'] = empty($user) ? '' : $user['realname'];
                $product['price'] = $product['product_max_price'] == $product['product_min_price'] ? $product['product_min_price'] : $product['product_min_price'] . '-' . $product['product_max_price'];
                $product['wholesale_min_price'] = round($product['product_min_price'] * $bulk_discount, 2);
                $product['wholesale_max_price'] = round($product['product_max_price'] * $bulk_discount, 2);
                $product['wholesale_price'] = $product['wholesale_min_price'] == $product['wholesale_max_price'] ? $product['wholesale_max_price'] : $product['wholesale_min_price'] . '-' . $product['wholesale_max_price'];
                //获取sku信息
                $used_attr = $listing_attr_val = [];
                $goods_skus = [];
                if (!empty($product['sku'])) {
                    foreach ($product['sku'] as $item) {
                        $goods_skus[$item['id']] = $item;
                    }
                }
                unset($product['sku']);

                foreach ($arrProductSku as $k => &$sku) {

                    $goods_sku = isset($goods_skus[$sku['goods_sku_id']]) ? $goods_skus[$sku['goods_sku_id']] : [];
                    $arrProductSku[$k]['thumb'] = !empty($goods_sku) ? $goods_sku['thumb'] : '';
                    $arrProductSku[$k]['local_sku'] = !empty($goods_sku) ? $goods_sku['sku'] : '';
                    $arrProductSku[$k]['local_status'] = !empty($goods_sku) ? $arrSkuStatus[$goods_sku['status']] : '';


                    $sku['wholesale_price'] = round($sku['sku_price'] * $bulk_discount, 2);
                    //sku属性ID及属性值ID转化成名字
                    $arr_sku_attr = json_decode($sku['sku_attr'], true);
                    if (!empty($arr_sku_attr)) {
                        foreach ($arr_sku_attr as &$attr) {

                            $attr['skuPropertyId'] = isset($attr['skuPropertyId']) ? $attr['skuPropertyId'] : '';

                            if (isset($arr_attr_name[$product['category_id'] . '_' . $attr['skuPropertyId']])) {
                                $attr['attr_name'] = $arr_attr_name[$product['category_id'] . '_' . $attr['skuPropertyId']];
                            } else {
                                $attr['attr_name'] = AliexpressCategoryAttr::getNameById($product['category_id'], $attr['skuPropertyId']);
                                $arr_attr_name[$product['category_id'] . '_' . $attr['skuPropertyId']] = $attr['attr_name'];
                            }
                            if ($attr['attr_name']) {
                                $used_attr[$attr['skuPropertyId']] = $attr['attr_name'];
                            }

                            if (isset($arr_attr_val_name[$attr['propertyValueId']])) {
                                $attr['attr_val_name'] = $arr_attr_val_name[$attr['propertyValueId']];
                            } else {
                                $attr['attr_val_name'] = AliexpressCategoryAttrVal::getNameById($attr['propertyValueId']);
                                $arr_attr_val_name[$attr['propertyValueId']] = $attr['attr_val_name'];
                            }
                            if (isset($used_attr[$attr['skuPropertyId']])) {
                                $listing_attr_val[$attr['skuPropertyId']][] = $attr['attr_val_name'] ? $attr['attr_val_name'] : '';
                            }
                        }
                        $sku['sku_attr'] = $arr_sku_attr;
                    }
                }

                unset($goods_skus);
                $product['used_attr'] = array_values($used_attr);
                $product['listing_attr_val'] = array_values($listing_attr_val);
                $product['id'] = (string)$product['id'];
            }
        }


        $base_url = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;
        return ['base_url' => $base_url, 'data' => $list, 'count' => $count, 'page' => $page, 'totalpage' => ceil($count / $pageSize)];
    }


    /**
     * 草稿箱列表
     * @param $params
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function draftsList($params, $page = 1, $pageSize = 10, $status)
    {
        $productModel = new AliexpressProduct();
        $arrWhere = $this->getFilterCondition($params, $status);

        if(isset($arrWhere['data'])) {
            return $arrWhere;
        }

        $field = 'p.goods_id,p.id,account_id,p.product_id,product_status_type,subject,goods_spu,group_id,product_min_price,product_max_price,plan_publish_time,
        promise_template_id,imageurls,p.update_time,freight_template_id,p.currency_code,salesperson_id,p.category_id,p.gmt_create,p.gross_weight,
        p.package_length,p.package_width,p.package_height,p.lot_num,p.package_type,p.product_unit,p.delivery_time,p.bulk_discount,p.lock_update,p.status,
        p.relation_template_id,p.relation_template_postion,p.custom_template_id,p.custom_template_postion,p.create_time';

        if (!empty($params['start']) && !empty($params['end'])) {
            $arrWhere['p.create_time'] = ['BETWEEN TIME', [strtotime($params['start']), strtotime($params['end'] . ' 23:59:59')]];
        } elseif (!empty($params['start'])) {
            $arrWhere['p.create_time'] = ['EGT', strtotime($params['start'])];
        } elseif (!empty($params['end'])) {
            $arrWhere['p.create_time'] = ['ELT', strtotime($params['end'] . ' 23:59:59')];
        }

        $arrWhere['p.product_id'] = ['=', 0];

        $order_str = ' update_time DESC';
        $count = $productModel->alias('p')->where($arrWhere)->count('distinct(p.id)');
        $list = $productModel->field($field)->alias('p')->with(['productSku', 'sku','promise','freight'])->order($order_str)
            ->page($page, $pageSize)->where($arrWhere)->select();


        $arrAccount = Cache::store('AliexpressAccount')->getAllAccounts();
        $arr_attr_name = [];
        $arr_attr_val_name = [];

        if (!empty($list)) {
            foreach ($list as &$product) {
                $product = $product->toArray();

                $product['product_unit_name'] = '';
                $product_unit = AliexpressProduct::PRODUCT_UNIT;
                if(isset($product_unit[$product['product_unit']])){
                    $product['product_unit_name'] = $product_unit[$product['product_unit']];
                }

                $product['freight_template_name'] = isset($product['freight']['template_name'])?$product['freight']['template_name']:'';
                $product['promise_template_name'] = isset($product['promise']['template_name'])?$product['promise']['template_name']:'';

                $arrProductSku = collection($product['product_sku'])->toArray();
                $product['stock'] = array_sum(array_column($arrProductSku, 'ipm_sku_stock'));
                
                $arrImgs = explode(';', $product['imageurls']);
                $product['main_img'] = !empty($arrImgs) && is_array($arrImgs) ? $arrImgs[0] : '';
                $product['account_code'] = isset($arrAccount[$product['account_id']]['code']) ? $arrAccount[$product['account_id']]['code'] : '';
                $product['product_min_price'] = round($product['product_min_price'], 2);
                $product['product_max_price'] = round($product['product_max_price'], 2);
                $product['is_wholesale'] = $product['bulk_discount'] > 0 ? 1 : 0;
                $bulk_discount = (100 - $product['bulk_discount']) / 100;
                $user = Cache::store('user')->getOneUser($product['salesperson_id']);
                $product['seller'] = empty($user) ? '' : $user['realname'];
                $product['price'] = $product['product_max_price'] == $product['product_min_price'] ? $product['product_min_price'] : $product['product_min_price'] . '-' . $product['product_max_price'];
                $product['wholesale_min_price'] = round($product['product_min_price'] * $bulk_discount, 2);
                $product['wholesale_max_price'] = round($product['product_max_price'] * $bulk_discount, 2);
                $product['wholesale_price'] = $product['wholesale_min_price'] == $product['wholesale_max_price'] ? $product['wholesale_max_price'] : $product['wholesale_min_price'] . '-' . $product['wholesale_max_price'];
                //获取sku信息
                $used_attr = $listing_attr_val = [];
                $goods_skus = [];
                if (!empty($product['sku'])) {
                    foreach ($product['sku'] as $item) {
                        $goods_skus[$item['id']] = $item;
                    }
                }
                unset($product['sku']);
                $arrSkuStatus = [
                    0 => '未上架',
                    1 => '上架',
                    2 => '下架',
                    3 => '待发布',
                    4 => '卖完下架',
                    5 => '缺货'
                ];

                foreach ($arrProductSku as $k => &$sku) {

                    $goods_sku = isset($goods_skus[$sku['goods_sku_id']]) ? $goods_skus[$sku['goods_sku_id']] : [];
                    $arrProductSku[$k]['thumb'] = !empty($goods_sku) ? $goods_sku['thumb'] : '';
                    $arrProductSku[$k]['local_sku'] = !empty($goods_sku) ? $goods_sku['sku'] : '';
                    $arrProductSku[$k]['local_status'] = !empty($goods_sku) ? $arrSkuStatus[$goods_sku['status']] : '';

                    $sku['wholesale_price'] = round($sku['sku_price'] * $bulk_discount, 2);
                    //sku属性ID及属性值ID转化成名字
                    $arr_sku_attr = json_decode($sku['sku_attr'], true);
                    if (!empty($arr_sku_attr)) {
                        foreach ($arr_sku_attr as &$attr) {

                            $attr['skuPropertyId'] = isset($attr['skuPropertyId']) ? $attr['skuPropertyId'] : '';

                            if (isset($arr_attr_name[$product['category_id'] . '_' . $attr['skuPropertyId']])) {
                                $attr['attr_name'] = $arr_attr_name[$product['category_id'] . '_' . $attr['skuPropertyId']];
                            } else {
                                $attr['attr_name'] = AliexpressCategoryAttr::getNameById($product['category_id'], $attr['skuPropertyId']);
                                $arr_attr_name[$product['category_id'] . '_' . $attr['skuPropertyId']] = $attr['attr_name'];
                            }
                            if ($attr['attr_name']) {
                                $used_attr[$attr['skuPropertyId']] = $attr['attr_name'];
                            }

                            if (isset($arr_attr_val_name[$attr['propertyValueId']])) {
                                $attr['attr_val_name'] = $arr_attr_val_name[$attr['propertyValueId']];
                            } else {
                                $attr['attr_val_name'] = AliexpressCategoryAttrVal::getNameById($attr['propertyValueId']);
                                $arr_attr_val_name[$attr['propertyValueId']] = $attr['attr_val_name'];
                            }
                            if (isset($used_attr[$attr['skuPropertyId']])) {
                                $listing_attr_val[$attr['skuPropertyId']][] = $attr['attr_val_name'] ? $attr['attr_val_name'] : '';
                            }
                        }
                        $sku['sku_attr'] = $arr_sku_attr;
                    }
                }

                unset($goods_skus);

                $product['used_attr'] = array_values($used_attr);
                $product['listing_attr_val'] = array_values($listing_attr_val);
                $product['id'] = (string)$product['id'];
            }
        }

        $base_url = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;
        return ['base_url' => $base_url, 'data' => $list, 'count' => $count, 'page' => $page, 'totalpage' => ceil($count / $pageSize)];
    }



    /**
     *sku下架
     *
     */
    public function skuOffline($job)
    {
        //1.根据sku_id,查询product_id,
        $prodcutSkuModel = new AliexpressProductSku();

        $where = [
            'goods_sku_id' => ['=', $job['sku_id']],
            'product_id' => ['>', 0],
            'ipm_sku_stock' => ['>',0],
        ];

        $productSkuList = $prodcutSkuModel->field('id, product_id, ipm_sku_stock, ali_product_id, sku_code')->where($where)->select();

        if(empty($productSkuList)) {
           return;
        }

        $productIds = array_column($productSkuList, 'product_id');

        $productSkuGroupList = $prodcutSkuModel->field('count(*) as num, product_id')->wherein('product_id',  $productIds)->group('product_id')->select();

        $productSkuGroupList = json_decode(\GuzzleHttp\json_encode($productSkuGroupList), true);

        $listingHeperService = new AliexpressListingHelper();


        foreach ($productSkuList as $key => $val) {

            $data = [
                'goods_id'=> $job['goods_id'],//商品id
                'goods_tort_id'=> $job['tort_id'],//侵权下架id
                'listing_id'=>$val['ali_product_id'],//listing_id
                'channel_id'=> 4,//平台id
                'item_id'=> $val['product_id'],//平台listing唯一码
                'status'=>'0',//状态 0 待下架   1 下架成功 2 下架失败
                'type' => 1,
                'sku_id' => $job['sku_id'],
            ];

            //初始化回写
            (new UniqueQueuer(GoodsTortListingQueue::class))->push($data);

            //写入缓存
            Cache::handler()->set('AliExpressSkuOffLine:'.$val['product_id'], \GuzzleHttp\json_encode($data));

            //sku下架队列
            $data = [['product_id' => $val['product_id'], 'sku' =>$val['sku_code'],'stock' => 0, 'old_stock' => $val['ipm_sku_stock']]];

            //sku库存改为0
            $listingHeperService->editSkuData(\GuzzleHttp\json_encode($data),'stock',0,$remark='批量停售sku',$cron_time=0);


            if($val['product_id'] == $productSkuGroupList[$key]['product_id'] && $productSkuGroupList[$key]['num'] == 1) {

                //spu针对单个sku,直接下架操作
                $listingHeperService->onOffLineProductLog($val['product_id'],1,'offline',0,' 批量停售sku');
            }

        }

        return;
    }


    /**
     *获取单个产品,回写sku库存下架回写
     *
     */
    public function skuOfflineWriteBack($status, $productId)
    {

        $key = 'AliExpressSkuOffLine:'.$productId;
        if(Cache::handler()->exists($key)){

            $data = \GuzzleHttp\json_decode(Cache::handler()->get($key), true);

            //回写
            $data = [
                'goods_id'=> $data['goods_id'],//商品id
                'goods_tort_id'=> $data['tort_id'],//侵权下架id
                'listing_id'=>$data['listing_id'],//listing_id
                'channel_id'=> 4,//平台id
                'item_id'=> $data['product_id'],//平台listing唯一码
                'status'=>$status,//状态 0 待下架   1 下架成功 2 下架失败
                'type' => 1,
                'sku_id' => $data['sku_id'],
            ];

            //删除缓存数据
            Cache::handler()->delete($key);

            //初始化回写
            (new UniqueQueuer(GoodsTortListingQueue::class))->push($data);
        }

        return;
    }

}