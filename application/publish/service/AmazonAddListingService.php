<?php
namespace app\publish\service;

use app\common\cache\Cache;
use app\common\model\amazon\AmazonPublishProductDetail;
use app\common\model\amazon\AmazonPublishProductVariant;
use app\common\model\amazon\AmazonPublishTask;
use app\common\model\Goods;
use app\common\model\GoodsSku;
use app\common\service\ChannelAccountConst;
use app\index\service\MemberShipService;
use think\Db;
use app\common\model\amazon\AmazonPublishProduct;
use app\common\model\amazon\AmazonPublishProductAttach;
use app\common\model\amazon\AmazonPublishProductJson;
use think\Exception;
use Waimao\AmazonMws\AmazonConfig;

class AmazonAddListingService
{

    protected $lang = 'zh';

    private $variantSkuList = array();

    public function getWarehouseList()
    {

    }


    /**
     * 设置刊登语言
     * @param $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }


    /**
     * 获取刊登语言
     * @return string
     */
    public function getLang()
    {
        return $this->lang ?? 'zh';
    }


    private function getSellerSku($item, $uid = 1)
    {
        static $model = null;
        if ($model == null) {
            $model = new \app\goods\service\GoodsSkuMapService();
        }
        $result = $model->addSku(['sku_code' => $item['spu'], 'channel_id' => 2, 'account_id' => $item['account_id']], $uid);
        if (isset($result['result']) && $result['result']) {
            return $result['sku_code'];
        } else {
            return '';
        }
    }


    private function getSpuSearch($skuSet, $spu, $isPublish = 0, $userId = 1)
    {
        $data = array($spu);
        if (isset($skuSet['sku_set']['variant'])) {
            foreach ($skuSet['sku_set']['variant'] as $item) {
                $checked = $item['check']['value'];
                //   if($checked){
                $publishSku = $this->getSellerSku($skuSet, $userId);
                $data[] = $isPublish ? $publishSku : $item['本地SKU']['value'];
                $this->variantSkuList[$item['本地SKU']['value']] = $publishSku;
                //    }
            }
        }
        $data = array_unique($data);
        return implode(',', $data);
    }

    private function getUpcSearch($skuSet)
    {
        $data = array();
        if (isset($skuSet['sku_set']['variant'])) {
            foreach ($skuSet['sku_set']['variant'] as $item) {
                $checked = $item['check']['value'];
                //       if($checked){
                $data[] = $item['Product ID'][1]['value'];
                //      }
            }
        }
        $data = array_unique($data);
        return implode(',', $data);
    }

    public function getTitle($fixAttributes)
    {
        if ($fixAttributes) {
            foreach ($fixAttributes as $fix) {
                if ($fix['name'] == 'Title') {
                    return $fix['value'];
                }
            }
        }
        return '';
    }


    public function getByProductId($productId)
    {
        $productJsonModel = new AmazonPublishProductJson();
        $productInfo = $productJsonModel->field('product_json')->where(['product_id' => $productId])->find();

        if ($productInfo && isset($productInfo->product_json) && $productInfo->product_json) {
            return json_decode($productInfo->product_json, true);
        } else {
            if ($this->lang == 'zh') {
                throw new Exception("该刊登记录不存在");
            } else {
                throw new Exception("The publication record does not exist");
            }
        }

    }


    /**
     * 删除多行数据
     * @param $ids 多行数据的ID数组；
     * @return bool
     * @throws Exception
     */
    public function deleteByProductId($ids)
    {
        try {
            $mainModel = new AmazonPublishProduct();
            $publishList = $mainModel->where(['id' => ['in', $ids]])->select();

            //删除前，先检查记录状态，正在刊登和刊登成功的，禁止删除
            foreach ($publishList as $val) {
                if (in_array($val['publish_status'], [1, 2])) {
                    if ($this->lang == 'zh') {
                        throw new Exception('禁止删除正在上传和上传成功的记录');
                    } else {
                        throw new Exception('Do not delete records that are being uploaded or successfully uploaded');
                    }
                }
            }

            $detailModel = new AmazonPublishProductDetail();
            Db::startTrans();
            $mainModel->where(['id' => ['in', $ids]])->delete();
            $detailModel->where(['product_id' => ['in', $ids]])->delete();
            AmazonPublishTask::update(['status' => 0, 'product_id' => 0], ['product_id' => ['in', $ids]]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    private function getVariantTag()
    {
        return array(
            'check', '本地SKU', 'Product ID', 'Condition', 'Condition Note', 'Standard Price', 'Sale Price', 'Sale Start Date', 'Sale End Date', 'Quantity'
        );
    }

    /**
     * 拿取刊登记录
     * @param $post
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getProductList($post, $page = 1, $pageSize = 10)
    {
        $where = $this->getCondition($post);

        $model = new AmazonPublishProduct();
        $detailModel = new AmazonPublishProductDetail();

        $count = 0;
        $list = [];
        if (empty($where['detail'])) {
            $count = $model->where($where['product'])->count();
            $list = $model->where($where['product'])->order('update_time desc')->field('*')->page($page, $pageSize)->select();
        } else {
            $whereNew = [];
            foreach ($where['product'] as $key => $val) {
                $whereNew['p.' . $key] = $val;
            }
            foreach ($where['detail'] as $key => $val) {
                if (is_numeric($key)) {
                    $whereNew = $val;
                } else {
                    $whereNew['d.' . $key] = $val;
                }
            }

            $count = $model->alias('p')
                ->join(['amazon_publish_product_detail' => 'd'], 'p.id=d.product_id')
                ->where($whereNew)
                ->distinct('p.id')
                ->count();

            $list = $model->alias('p')
                ->join(['amazon_publish_product_detail' => 'd'], 'd.product_id=p.id')
                ->where($whereNew)
                ->distinct('p.id')
                ->order('create_time desc')
                ->field('p.*')
                ->page($page, $pageSize)
                ->select();
        }

        if (empty($list)) {
            return ['count' => $count, 'page' => $page, 'totalpage' => ceil($count / $pageSize), 'data' => []];
        }

        $data = [];
        $productIds = [];
        $accountIds = [];
        foreach ($list as $_val) {
            $productIds[] = $_val['id'];
            $accountIds[] = $_val['account_id'];
        }

        $detailList = $detailModel->where(['product_id' => ['in', $productIds]])->order('id', 'ASC')->select();

        //图片的base_url;
        $baseUrl = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . DS;

        $amazonconfig = new AmazonCategoryXsdConfig();
        $cache = Cache::store('AmazonAccount');
        $statusArr = ['待上传', '上传中', '已上传', '上传失败', '重新编辑', '刊登草稿'];
        foreach ($list as $_val) {
            $account = $cache->getAccount($_val['account_id']);
            $currency = AmazonCategoryXsdConfig::getCurrencyBySite($account['site']);
            $tmp = array(
                'id' => $_val['id'],
                'account_id' => $_val['account_id'],
                'goods_id' => $_val['goods_id'],
                'site' => $_val['site'],
                'site_text' => $amazonconfig->getSiteByNum($_val['site']),
                'spu' => $_val['spu'],
                'publish_spu' => $_val['publish_spu'],
                'title' => '',
                'publish_status' => $_val['publish_status'],
                'status_text' => $statusArr[$_val['publish_status']],
                'current_cost' => 0,
                'pre_cost' => 0,
                'adjusted_price' => 3,
                'sale_price' => '',
                'quantity' => '',
                'main_image' => '',
                'account_name' => $account['code']?? '-',
                'upload_product' => 0,
                'upload_relation' => 0,
                'upload_price' => 0,
                'upload_image' => 0,
                'upload_quantity' => 0,
                'error_message' => [],
                'warning_message' => [],
                'timer' => empty($_val['timer'])? '-' : date('Y-m-d H:i:s', $_val['timer']),
                'create_time' => date('Y-m-d H:i:s', $_val['create_time']),
                'update_time' => date('Y-m-d H:i:s', $_val['update_time']),
                'base_url' => $baseUrl,
                'children' => []
            );
            //刊登成功的，显最后更新时间为刊登成功时间；
            if ($tmp['publish_status'] == AmazonPublishConfig::PUBLISH_STATUS_FINISH) {
                $tmp['status_text'] = $tmp['update_time'];
            }

            $product_status = count($detailList) < 2 ? 0 : 2;
            $relation_status = count($detailList) < 2 ? 0 : 2;
            $quantity_status = count($detailList) < 2 ? 0 : 2;
            $image_status = count($detailList) < 2 ? 0 : 2;
            $price_status = count($detailList) < 2 ? 0 : 2;

            //装父级数据；
            $spuData = [];
            $tmpChild = [];
            foreach ($detailList as $key => $child) {
                if ($child['upload_product'] == 0 || $child['upload_product'] == 2) {
                    $product_status = 0;
                }
                if ($child['upload_relation'] == 0 || $child['upload_relation'] == 2) {
                    $relation_status = 0;
                }
                if ($child['upload_quantity'] == 0 || $child['upload_quantity'] == 2) {
                    $quantity_status = 0;
                }
                if ($child['upload_image'] == 0 || $child['upload_image'] == 2) {
                    $image_status = 0;
                }
                if ($child['upload_price'] == 0 || $child['upload_price'] == 2) {
                    $price_status = 0;
                }

                if ($child['product_id'] == $_val['id']) {
                    if ($child['type'] == 0) {
                        $tmp['title'] = $child['title'];
                        $tmp['sale_price'] = $child['sale_price'];
                        $tmp['quantity'] = $child['quantity'];
                        $tmp['main_image'] = $child['main_image'];
                        $tmp['upload_product'] = $child['upload_product'];
                        $tmp['upload_relation'] = $child['upload_relation'];
                        $tmp['upload_price'] = $child['upload_price'];
                        $tmp['upload_image'] = $child['upload_image'];
                        $tmp['upload_quantity'] = $child['upload_quantity'];
                        $tmp['error_message'] = $this->createMessage($child['error_message']);
                        $tmp['warning_message'] = $this->createMessage($child['warning_message']);

                    } else {
                        //涨降价标识；
                        if ($child['current_cost'] > $child['pre_cost']) {
                            $adjusted_price = 1;
                        } else if ($child['current_cost'] < $child['pre_cost']) {
                            $adjusted_price = 2;
                        } else {
                            $adjusted_price = 3;
                        }

                        $tmpChild = array(
                            'sku' => $child['binding_goods'],
                            'publish_sku' => $child['publish_sku'],
                            'current_cost' => $child['current_cost']. '['. $currency. ']',
                            'pre_cost' => $child['pre_cost']. '['. $currency. ']',
                            'adjusted_price' => $adjusted_price,
                            'sale_price' => $child['standard_price']. '['. $currency. ']',
                            'quantity' => $child['quantity'],
                            'variant' => $this->handleVariantInfo($child['variant_info']),
                            'main_image' => $child['main_image'],
                            'upload_product' => $child['upload_product'],
                            'upload_relation' => $child['upload_relation'],
                            'upload_price' => $child['upload_price'],
                            'upload_image' => $child['upload_image'],
                            'upload_quantity' => $child['upload_quantity'],
                            'error_message' => $this->createMessage($child['error_message']),
                            'warning_message' => $this->createMessage($child['warning_message']),
                        );
                        $tmp['children'][] = $tmpChild;
                    }
                    unset($detailList[$key]);
                }
            }

            $data[] = $tmp;
            //$update = [];
            //if ($_val['product_status'] != 1 && $_val['product_status'] != $product_status) {
            //    $update['product_status'] = $product_status;
            //}
            //if ($_val['relation_status'] != 1 && $_val['relation_status'] != $relation_status) {
            //    $update['relation_status'] = $relation_status;
            //}
            //if ($_val['quantity_status'] != 1 && $_val['quantity_status'] != $quantity_status) {
            //    $update['quantity_status'] = $quantity_status;
            //}
            //if ($_val['image_status'] != 1 && $_val['image_status'] != $image_status) {
            //    $update['image_status'] = $image_status;
            //}
            //if ($_val['price_status'] != 1 && $_val['price_status'] != $price_status) {
            //    $update['price_status'] = $price_status;
            //}

            //if (!empty($update)) {
            //    $model->update($update, ['id' => $_val['id']]);
            //}
        }

        return ['count' => $count, 'page' => $page, 'totalpage' => ceil($count / $pageSize), 'data' => $data];
    }


    /**
     * 处理变体数据，变成name,value格式；
     * @param $info
     * @return array
     */
    public function handleVariantInfo($info) {
        $variant_info = json_decode($info, true);
        $variant = [];
        $attrs = [];
        foreach ($variant_info as $vKey=>$vVal) {
            if (strpos($vKey, '@') === false) {
                $variant[] = ['name' => $vKey, 'value' => $vVal];
            } else {
                $arr = explode('@', $vKey);
                $attrs[$arr[0]] = $vVal;
            }
        }
        foreach ($variant as &$vVal) {
            if (!empty($attrs[$vVal['name']])) {
                $vVal['value'] = $vVal['value']. '('. $attrs[$vVal['name']]. ')';
            }
        }
        return $variant;
    }

    /**
     * 组成正确的信息数组；
     * @param $msg
     * @return array
     */
    public function createMessage($msg) {
        if (empty($msg)) {
            $msg = [];
        }
        if (is_string($msg)) {
            $msg = json_decode($msg, true);
            $msg = empty($msg)? [] : $msg;
        }
        $keys = ['upload_product', 'upload_relation', 'upload_price', 'upload_image', 'upload_quantity'];
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $msg[$key] ?? '';
        }
        return $data;
    }

    public function refresh_status($id)
    {
        $id = (int)$id;
        $model = new AmazonPublishProduct();
        $detailModel = new AmazonPublishProductDetail();

        $product = $model->where(['id' => $id])->find();

        if (empty($product)) {
            if ($this->lang == 'zh') {
                throw new Exception('刊登记录 id 有错，刊登记录不存在');
            } else {
                throw new Exception('The publication record does not exist');
            }
        }

        $detailList = $detailModel->where(['product_id' => ['in', $id]])->select();

        $statusArr = ['待上传', '上传中', '已上传', '上传失败'];

        $tmp['id'] = $id;
        $tmp['publish_status'] = $product['publish_status'];
        $tmp['status_text'] = $statusArr[$product['publish_status']];
        $tmp['upload_product'] = 0;
        $tmp['upload_relation'] = 0;
        $tmp['upload_price'] = 0;
        $tmp['upload_image'] = 0;
        $tmp['upload_quantity'] = 0;
        $tmp['error_message'] = '';

        //装父级数据；
        $count = count($detailList);
        foreach ($detailList as $key => $child) {
            if ($child['type'] == 0) {
                $tmp['upload_product'] = $child['upload_product'];
                $tmp['upload_relation'] = $child['upload_relation'];
                $tmp['upload_price'] = $child['upload_price'];
                $tmp['upload_image'] = $child['upload_image'];
                $tmp['upload_quantity'] = $child['upload_quantity'];
                $tmp['error_message'] = $this->createMessage($child['error_message']);
                $tmp['warning_message'] = $this->createMessage($child['warning_message']);

            } else {
                $child['variant_info'] = json_decode($child['variant_info'], true);
                $tmpChild = array(
                    'id' => $child['id'],
                    'upload_product' => $child['upload_product'],
                    'upload_relation' => $child['upload_relation'],
                    'upload_price' => $child['upload_price'],
                    'upload_image' => $child['upload_image'],
                    'upload_quantity' => $child['upload_quantity'],
                    'error_message' => $this->createMessage($child['error_message']),
                    'warning_message' => $this->createMessage($child['warning_message']),
                );
                $tmp['children'][] = $tmpChild;
            }
        }
        return $tmp;
    }

    public function getCondition($data): array
    {
        $where = [];
        $whered = [];

        if (isset($data['site']) && $data['site']) {
            $where['site'] = $data['site'];
        }
        //创建者
        if (!empty($data['saler_id'])) {
            $memberShipService = new MemberShipService();
            $accountList = $memberShipService->getAccountIDByUserId($data['saler_id'], ChannelAccountConst::channel_amazon);
            $where['account_id'] = ['in', $accountList];
        }
        if (isset($data['account']) && $data['account']) {
            $where['account_id'] = $data['account'];
        }
        //d
        if (isset($data['is_virtual_send']) && in_array($data['is_virtual_send'], ['0', '1'])) {
            $where['is_virtual_send'] = $data['is_virtual_send'];
        }

        if (isset($data['publishStatus']) && $data['publishStatus'] != '') {
            $where['publish_status'] = $data['publishStatus'];
        }

        if (!empty($data['start']) && empty($data['end'])) {
            $where['create_time'] = ['>=', strtotime(trim($data['start'], '"'))];
        } else if (empty($data['start']) && !empty($data['end'])) {
            $where['create_time'] = ['<', strtotime(trim($data['end'], '"')) + 86400];
        } else if (!empty($data['start']) && !empty($data['end'])) {
            $where['create_time'] = ['between', [strtotime(trim($data['start'], '"')), strtotime(trim($data['end'], '"')) + 86400]];
        }

        if (!empty($data['snType']) && !empty($data['snText'])) {
            $tempArr = explode(',', $data['snText']);
            if ($data['snType'] == 'sku') {
                $skuIds = GoodsSku::where(['sku' => ['in', $tempArr]])->column('id');
                if (!empty($skuIds)) {
                    $whered['sku_id'] = ['in', $skuIds];
                } else {
                    $whered['sku_id'] = -1;
                }
            } elseif ($data['snType'] == 'spu') {
                $goodsIds = Goods::where(['spu' => ['in', $tempArr]])->column('id');
                if (!empty($goodsIds)) {
                    $where['goods_id'] = ['in', $goodsIds];
                } else {
                    $where['goods_id'] = -1;
                }
            } elseif ($data['snType'] == 'platform_sku') {
                $whered['publish_sku'] = ['in', $tempArr];
            } elseif ($data['snType'] == 'upc') {
                $whered['product_id_value'] = ['in', $tempArr];
            } elseif ($data['snType'] == 'title') {
                $whered['title'] = ['like', '%' . $data['snText'] . '%'];
            }
        }

        $operateArr = [1 => '>=', '<=', '='];
        if (!empty($data['adjusted_price']) && isset($operateArr[$data['adjusted_price']])) {
            $adjusted_range = 0.01;
            if (isset($data['adjusted_range'])) {
                if ($data['adjusted_range'] === '' || !is_numeric($data['adjusted_range'])) {
                    $adjusted_range = 0.01;
                } else {
                    $adjusted_range = $data['adjusted_range'];
                }
            }
            switch ($data['adjusted_price']) {
                case 1:
                    $whered['current_cost - pre_cost'] = ['>=', $adjusted_range];
                    break;
                case 2:
                    $whered['pre_cost - current_cost'] = ['>=', $adjusted_range];
                    break;
                case 3:
                    $whered['current_cost - pre_cost'] = ['=', 0];
                default:
                    break;
            }
        }
        return ['product' => $where, 'detail' => $whered];
    }

    private function getMainImageBySku($productIds, $sku)
    {
        static $imageList = array();
        if (empty($imageList)) {
            $productJsonModel = new AmazonPublishProductJson();
            $allList = $productJsonModel->where(array('product_id' => array('in', implode(',', $productIds))))->select();
            foreach ($allList as $_val) {
                foreach (json_decode($_val->image_json, true) as $item) {
                    if (isset($item['main']) && $item['main']['real_sku']) {
                        $sku = $item['main']['real_sku'];
                        $imageList[$sku] = $item['main']['image_url'];
                    }
                }
            }
        }
        if (isset($imageList[$sku])) {
            return $imageList[$sku];
        } else {
            return '';
        }

    }


    private function getAccountNameBySku($productIds, $productId)
    {
        static $imageList = array();
        if (empty($imageList)) {
            $productJsonModel = new AmazonPublishProductJson();
            $allList = $productJsonModel->where(array('product_id' => array('in', implode(',', $productIds))))->select();
            foreach ($allList as $_val) {
                $productJson = json_decode($_val->product_json, true);
                foreach ($productJson as $accoutName => $item) {
                    break;
                }
                $imageList[$_val['product_id']] = $accoutName;
            }
        }

        if (isset($imageList[$productId])) {
            return $imageList[$productId];
        } else {
            return '';
        }

    }


    public function getCurrencyBySite($site)
    {
        $list = array(
            'DE' => 'EUR', 'UK' => 'GBP', 'US' => 'USD', 'CA' => 'CAD',
            'FR' => 'EUR', 'JP' => 'JPY', 'IN' => 'EUR',
            'ES' => 'EUR', 'IT' => 'EUR',
        );

        if (isset($list[$site])) {
            return $list[$site];
        } else {
            return 'EUR';
        }
    }
}