<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/1/9
 * Time: 11:25
 */

namespace app\listing\service;

use app\common\cache\Cache;
use app\common\model\joom\JoomProduct as JoomProductModel;
use app\common\model\joom\JoomProduct;
use app\common\model\joom\JoomVariant;
use joom\JoomListingApi;
use think\Exception;

/**
 * @title 同步joomlisting
 * Class JoomSyncListingHelper
 * @package app\listing\service
 */
class JoomSyncListingHelper
{
    /**
     * @param $config
     * @return bool
     * @throws Exception
     */
    public function downListing($config)
    {
        set_time_limit(0);
        $execute_start = time(); //执行开始时间

        $start = 0;
        $limit = 200;

        $api = new JoomListingApi($config);

        $bol = true;
        do {
            //拉取listing;
            $res = $api->getListing($start, $limit);
            if($res['code'] != 0) {
                throw new Exception($res['message']);
            } else {
                //记录日志信息
                \think\Log::write('joom_sync_listing_helper_api_result:'. json_encode($res). 'start--'.$start. 'limit--'.$limit, 'info');
            }

            $res['data'] = empty($res['data']) ? [] : $res['data'];

            //同步数据；
            $result = $this->syncListing($res['data'], $config);
            if(!empty($res['paging']['next']) && count($res['data']) == $limit) {
                $start = $start + $limit;
            } else {
                $bol = false;
            }

        } while($bol);

        Cache::store('JoomListing')->setListingSyncTime($config['id'], $execute_start);

        return true;
    }

    /**
     * @title 同步listing数据
     * @param $data 拉下来的listing数据；
     * @param $config 帐号
     * @return bool
     * @param $data
     * @param $config
     * @return bool
     * @throws Exception
     */
    public function syncListing($data, $config) {
//        $cacheList = Cache::store('JoomListing');

        //审核状态
        $review_arr = ['pending' => 0, 'approved' => 1, 'rejected' => 2];

        $joomProductModel = new JoomProductModel();

        $lists = [];
        $productIds = [];
        try {
            foreach ($data as $dt) {
                if (isset($dt['Product']['id'])) {
                    $productIds[] = $dt['Product']['id'];
                }
            }
            $ids = JoomProduct::whereIn('product_id',$productIds)->column('id','product_id');
            $variantIds = JoomVariant::whereIn('joom_product_id',array_values($ids))->column('id','variant_id');

            foreach($data as $key => $val) {
                if(empty($val['Product'])) {
                    continue;
                }
                $product = $val['Product'];
//            $productCache = $cacheList->getProductCache($config['id'], $product['id']);

                //组合标签
                $tag = '';
                if(isset($product['tags'])) {
                    foreach($product['tags'] as $t) {
                        $tag .= $t['Tag']['name']. ',';
                    }
                    $tag = trim($tag, ',');
                }

                //组合错误原因
                $variantMessage = [];
                $warning_id = '';
                $review_note = '';
                if(isset($product['diagnosis'])) {
                    foreach($product['diagnosis'] as $t) {
                        $dec = $t['description']?? '';
                        if(isset($t['variantId'])) {
                            $warning_id .= $t['variantId']. '|';
                            $variantMessage[$t['variantId']] = $dec;
                        } else {
                            $review_note .= $dec;
                        }
                    }
                    $warning_id = trim($warning_id, '|');
                    $review_note = trim($review_note, '|');
                }

                //产品表；
                $lists[$key]['product'] = $tmpp = [
//                'id' => $ids[$product['id']]?? 0,
                    'shop_id' => $config['id'],
                    'account_id' => $config['account_id'],
                    'tags' => $tag,
                    'name' => $product['name'] ?? '',
                    'main_image' => $product['main_image'] ?? '',
                    'parent_sku' => $product['parent_sku'] ?? '',
                    'is_promoted' => strtolower($product['is_promoted']) == 'true' ? 1 : 0,
                    'number_saves' => $product['number_saves'] ?? '',
                    'product_id' => $product['id'],
                    'enabled' => strtolower($product['enabled']) == 'true' ? 1 : 0,
                    'review_status' => $review_arr[$product['review_status']]?? 0,
                    'number_sold' => $product['number_sold'] ?? '',
                    'number_refunds' => $product['number_refunds'] ?? '',//退款订单数量
                    'refund_rate' => $product['refund_rate'] ?? '',
                    'date_uploaded' => strtotime($product['date_uploaded']),
                ];
                if (isset($ids[$product['id']])) {
                    $lists[$key]['product']['id'] = $ids[$product['id']];
                } else {
                    // 线上有的产品，而本地表中没有此产品，则添加此产品
                    $lists[$key]['product']['id'] = 0;
                }

                //下列几个属性可能没有，有才出现；防止更新错错；
                if(isset($product['brand'])) {
                    $lists[$key]['product']['brand'] = $product['brand'];
                }
                if(isset($product['upc'])) {
                    $lists[$key]['product']['upc'] = $product['upc'];
                }

                //info表
                $lists[$key]['info'] = [
//                'description' => $product['description'] ?? '',
                    'landing_page_url' => $product['landing_page_url'] ?? '',
                    'extra_images' => $product['extra_images'] ?? '',
                    'original_images' => $product['original_image_url'] ?? '',
                    'product_id' => $product['id'],
                    'warning_id' => $warning_id,
                    'review_note' => $review_note,
                ];
                //如果产品已下架，再更新时，会出现乱码报错的情况，所以只更新在线的描述
                if ($lists[$key]['product']['enabled'] && $lists[$key]['product']['review_status']==1) {
                    $lists[$key]['info']['description'] = $product['description'] ?? '';
                } else {
                    //再次判断description是否为空
                    $lists[$key]['info']['description']  = $lists[$key]['info']['description'] ?? '';
                }
                //下列变量将变体表数据到产品表
                $variant_enabled = $tmpp['enabled'];
                $inventory = 0;
                $lowest_price = 0;
                $highest_price = 0;
                $lowest_shipping = 0;
                $highest_shipping = 0;

                //variant表
                $lists[$key]['variant'] = [];
                if(isset($product['variants'])) {

                    foreach($product['variants'] as $vkey=>$v) {

                        //拿变体缓存；
//                    $variantCache = $cacheList->getVariantCache($config['id'], $v['Variant']['id']);

                        $lists[$key]['variant'][$vkey] = $tmpv = [
//                        'id' => $variantIds[$v['Variant']['id']]?? 0,
                            'sku' => $v['Variant']['sku'],
                            'color' => $v['Variant']['color'] ?? '',
                            'size' => $v['Variant']['size'] ?? '',
                            'price' => $v['Variant']['price'] ?? '',
                            'declared_value' => $v['Variant']['declaredValue'] ?? '',
                            'shipping' => $v['Variant']['shipping'] ?? '',
                            'shipping_time' => $v['Variant']['shipping_time'] ?? '',
                            'inventory' => $v['Variant']['inventory'] ?? '',
                            'msrp' => $v['Variant']['msrp'] ?? '',
                            'message' => $variantMessage[$v['Variant']['id']] ?? '',
                            'main_image' => $v['Variant']['main_image'] ?? '',
                            'original_image_url' => $v['Variant']['original_image_url'] ?? '',
                            'variant_id' => $v['Variant']['id'],
                            'product_id' => $v['Variant']['product_id'],
                            'enabled' => strtolower($v['Variant']['enabled']) == 'true' ? 1 : 0,
                            //能拉下来的，肯定都是刊登成功的；
                            'status' => 1,
                        ];
                        if (isset($variantIds[$v['Variant']['id']])) {
                            $lists[$key]['variant'][$vkey]['id'] = $variantIds[$v['Variant']['id']];
                        } else {
                            $lists[$key]['variant'][$vkey]['id'] = 0;
                        }

                        //下列变量将变体表数据到产品表
                        if($tmpv['enabled'] == 0) { //只要有一个变体是下架，这个值就设为0
                            $variant_enabled = 0;
                        }

                        //先附初值，然后记算后续值大小；
                        if($vkey == 0) {
                            $inventory = $tmpv['inventory'];
                            $highest_price = $lowest_price = $tmpv['price'];
                            $highest_shipping = $lowest_shipping = $tmpv['shipping'];
                        } else {
                            $inventory = min($inventory, $tmpv['inventory']);
                            $lowest_price = min($lowest_price, $tmpv['price']);
                            $highest_price = max($highest_price, $tmpv['price']);
                            $lowest_shipping = min($lowest_shipping, $tmpv['shipping']);
                            $highest_shipping = max($highest_shipping, $tmpv['shipping']);
                        }
                    }
                }

                //以下变体内的数据记入产品，便用列表查询；
                $lists[$key]['product']['variant_enabled'] = $variant_enabled;
                $lists[$key]['product']['inventory'] = $inventory;
                $lists[$key]['product']['lowest_price'] = $lowest_price;
                $lists[$key]['product']['highest_price'] = $highest_price;
                $lists[$key]['product']['lowest_shipping'] = $lowest_shipping;
                $lists[$key]['product']['highest_shipping'] = $highest_shipping;
            }
        } catch (\Exception $e) {
            //记录日志信息
            \think\Log::write('joom_sync_listing_helper_option_result:'. json_encode($data).'msg--'. $e->getMessage(). 'line--'.$e->getMessage(), 'info');
        }


        $result = $joomProductModel->syncAll($lists);
        return $result;
    }
}