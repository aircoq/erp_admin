<?php

/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/25
 * Time: 14:00
 */

namespace app\listing\queue;

use app\common\exception\QueueException;
use app\common\model\wish\WishWaitUploadProductInfo;
use app\common\service\CommonQueuer;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use think\Db;
use app\common\model\wish\WishWaitUploadProduct;
use app\common\model\wish\WishWaitUploadProductVariant;
use app\common\service\Twitter;
use think\Exception;

class WishListingInsertDb extends SwooleQueueJob
{
    private $accountId = null;    
    private $accountLastUpdated = null;
    private $cache = null;
    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }
    public function getName(): string {
        return 'wish在线listing写入数据库';
    }

    public function getDesc(): string {
        return 'wish在线listing写入数据库';
    }

    public function getAuthor(): string {
        return 'joy';
    }
    
    public function init()
    {
        // opcache_reset();
        $this->cache = Cache::store('WishRsyncListing');
    }
    
    public function execute()
    {
        set_time_limit(0);
        $job = $this->params;
        if (!$job) {
            return true;
        }
        try {
            list($accountid, $job_id) = explode("_", $job);
            $this->accountId = $accountid;
            if (!$this->accountId) {
                throw new Exception($job . '没有获取到相应的账号');
            }
            $this->accountLastUpdated = 0;
            $file = ROOT_PATH . 'public' . DS . 'wish_product' . DS . $job . '.csv';
            if (!file_exists($file)) {
                throw new Exception($file . ' 文件不存在');
            }
            $newfile = $this->convertFile($file);
            $this->saveDataUseWhile($newfile);
            @unlink($newfile);
            $this->lastAction($file);
            return true;
        } catch (Exception $exp) {
            if (isset($newfile) && file_exists($newfile)) {
                @unlink($newfile);
            }
            throw new Exception("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * 最后的收尾工作，更新since,删除csv文件
     * @param $file csv文件
     * @param $since 更新时间
     * @param $account 帐号信息
     */
    private function lastAction($file)
    {
        $this->accountLastUpdated ? Cache::store('WishAccount')->setWishLastRsyncListingSinceTime($this->accountId, date('Y-m-d H:i:s', $this->accountLastUpdated)) : '';
        @unlink($file); //删除csv文件
    }

    /**
     * 获取listing上次拉取缓存的数据
     * @param $product_id
     * @return array|mixed
     */
    private function getProductCache($product_id)
    {
        $cache = $this->cache->getProductCache($this->accountId, $product_id);
        return $cache ? $cache : [];
    }

    /**
     * 设置上次拉取product的时间
     * @param $product_id
     * @param $cache
     *  id
     *  product_id
     *  last_update
     *  variant_id
     *
     */
    private function setProductCache($product_id, $cache)
    {
        return $this->cache->setProductCache($this->accountId, $product_id, $cache);
    }

    /**
     * 拼装产品和变体缓存数据
     * @param item
     * @return array
     */
    private function combieProductAndVariantCacheData(&$item)
    {
        $arr['id'] = $item['product']['id'];
        $arr['last_updated'] = $item['product']['last_updated'];
        foreach ($item['variant'] as $variant) {
            $arr[$variant['variant_id']] = $variant['vid'];
        }

        return $arr;
    }

    /**
     * 逐行读取
     * @param string $file
     * @return boolean
     * @throws Exception
     */
    private function saveDataUseWhile($file)
    {
        $handle = fopen($file, 'a+');
        $data = [];
        $product_id = '';
        try {
            $row = fgetcsv($handle); // 获取header
            if (count($row) < 30 || $row[0] != 'Product ID') {
                throw new Exception($file . ' 此文件格式不对');
            }
            while ($row = fgetcsv($handle)) {
                if (empty($row[0])) {
                    $data['variant'][] = $row;
                } else {
                    $product_id ? $this->insertAndUpdateData($data) : '';
                    $product_id = $row[0];
                    $data = [
                        'product' => $row,
                        'variant' => [$row]
                    ];
                    $row[0] = '';
                    $data['variant'][] = $row;
                }
            }
            if ($data) {
                $this->insertAndUpdateData($data);
            }
            fclose($handle);
            return true;
        } catch (Exception $exp) {
            fclose($handle);
            throw new Exception($exp->getMessage());
        }
    }

    private function insertAndUpdateData($data)
    {
        $return = $this->getProductVariantData($data);
        if (!$return) {
            return true;
        }
        Db::startTrans();
        try {
            $model = new WishWaitUploadProduct();
            $product = $return['product'];
            $product_id = $product['product_id'];
            $id = $product['id'];
            $info = [
                'id' => $id,
                'product_id' => $product['product_id'],
                'description' => $product['description'],
                'landing_page_url' => $product['landing_page_url'],
                'extra_images' => $product['extra_images'],
            ];
            if ($id) {
                $model->allowField(true)->isUpdate(true)->save($product);
                WishWaitUploadProductInfo::where(['id' => $id])->update($info);
            } else {
                $id = Twitter::instance()->nextId(3, $this->accountId);
                $product['id'] = $id;
                $info['id'] = $id;
                $product['addtime'] = time();
                $model->allowField(true)->isUpdate(false)->save($product);
                (new WishWaitUploadProductInfo())->allowField(true)->isUpdate(false)->save($info);
            }
            $return['product']['id'] = $id;
            foreach ($return['variant'] as &$variant) {
                if ($variant['vid']) {
                    (new WishWaitUploadProductVariant())->allowField(true)->isUpdate(true)->save($variant);
                } else {
                    // $variant['vid'] = Twitter::instance()->nextId(3, $this->accountId);
                    $variant['add_time'] = time();
                    $variant['pid'] = $id;
                    $variantModel = new WishWaitUploadProductVariant();
                    $variantModel->allowField(true)->isUpdate(false)->save($variant);
                    $variant['vid'] = $variantModel->vid;
                }
            }

//            $cacheData = $this->combieProductAndVariantCacheData($return);
            Db::commit();
//            $this->setProductCache($product_id, $cacheData);
            $queue=[
                'account_id'=>$this->accountId,
                'product_id'=>$product_id
            ];
            (new CommonQueuer(WishExpressQueue::class))->push($queue);
        } catch (Exception $exp) {
            Db::rollback();
            throw new Exception('line:'.$exp->getLine().'exp'.$exp->getMessage().'--product:'.$product. '--info:'.$info.'--varient:'.$variant);
        }
    }

    /**
     * 获取商品和变体数据
     * @param $data
     * @param $account
     * @return array
     * @throws QueueException
     */
    private function getProductVariantData($data)
    {
        $id = 0;
        $return = [];
        if (!$data['product']) {
            return [];
        }
        $inventorySum = 0;
        $highestPrice = 0;
        $lowestPrice = 0;
        $highestShipping = 0;
        $lowestShipping = 0;
        if (count($data['product']) < 30) {
            Cache::handler()->hSet('wish:list:product:data', $this->accountId .'_' . $data['product'][0] . '_'. date('Y-m-d H:i:s'), json_encode($data));
            return [];
        }
        $productId = $data['product'][0];
//        $productCache = $this->getProductCache($productId);
        $productCache = '';
        $lastUpdated = strtotime($data['product'][26]);
        $lastUpdated > $this->accountLastUpdated ? $this->accountLastUpdated = $lastUpdated : '';
        if (!$productCache && $productInfo = WishWaitUploadProduct::where('product_id', $productId)->field('id,last_updated')->find()) {
            if ($productInfo->last_updated == $lastUpdated) {
                return [];
            }
            $id = $productInfo['id'];
        }
        if ($productCache && $productCache['last_updated'] == $lastUpdated) {
            return [];
        } elseif ($productCache) {
            $id = $productCache['id'];
        }

        foreach ($data['variant'] as $row) {
            if (count($row) < 30) {
                Cache::handler()->hSet('wish:list:product:data', $this->accountId .'_' . $productId . '_'. date('Y-m-d H:i:s'), json_encode($data));
                return [];
            }
            $name = $row[1]; //标题
            $description = $row[2]; //描述
            $number_saves = (int) $row[3]; //收藏
            $number_sold = (int) $row[4]; //售出
            $parent_sku = $row[5];
            $upc = $row[6]; //upc
            $landing_page_url = $row[7]; //详情页面
            $variant_id = $row[8]; //变体id
            $sku = $row[9]; //sku
            $size = $row[10];
            $color = $row[11];
            $msrp = $this->convertStringToFloat($row[12]);
            $cost = $this->convertStringToFloat($row[13]);
            $price = $this->convertStringToFloat($row[14]);
            $shipping = $this->convertStringToFloat($row[15]);
            $inventory = (int) $row[16];
            $shipping_time = $row[17];
            $enabled = $row[18];
            $is_promoted = $row[19]; //是否促销
            $review_status = $row[20]; //审核状态
            if(!is_numeric($review_status))
            {
                $review_status = deal_review_status($review_status);
            }
            $main_image = $row[22]; //主图
            $extra_images = $row[23]; //附图
            $tags = $row[24]; //tags
            $brand = $row[25]; //品牌
            $date_uploaded = $row[27]; //刊登时间
            $warning_id = $row[28];
            $wish_express_countries = $row[29];
            if ($tags) {
                $tagsArr = explode("|", $tags);
                $attr = [];
                foreach ($tagsArr as $arr) {
                    $tag = explode(",", $arr);
                    if ($tag && count($tag) == 2) {
                        list($tagid, $tagname) = $tag;
                        $attr[] = str_replace("name:", "", $tagname);
                    }
                }
                $newTags = implode(",", $attr);
            } else {
                $newTags = '';
            }
            unset($tags);
            
            if ($row[0]) {
                $return['product'] = [
                    'id' => $id,
                    'product_id' => $productId,
                    'name' => $name,
                    'main_image' => $main_image ? $main_image : "",
                    'accountid' => $this->accountId,
                    'extra_images' => $extra_images,
                    'description' => $description,
                    'number_saves' => $number_saves,
                    'number_sold' => $number_sold,
                    'parent_sku' => $parent_sku,
                    'upc' => $upc,
                    'landing_page_url' => $landing_page_url,
                    'is_promoted' => strtolower($is_promoted) == 'true' ? 1 : 0,
                    'review_status' => $review_status,
                    'brand' => $brand,
                    'last_updated' => $lastUpdated,
                    'tags' => $newTags,
                    'date_uploaded' => strtotime($date_uploaded),
                    'warning_id' => $warning_id,
                    'wish_express_countries' => $wish_express_countries,
                    'publish_status'=>1,
                ];
            } else {
                //Wish产品变体信息
                $variant = [
                    'vid' => 0,
                    'pid' => 0,
                    'variant_id' => $variant_id,
                    'product_id' => $productId,
                    'sku' => $sku,
                    'main_image' => $main_image ? $main_image : "",
                    'size' => $size,
                    'color' => $color,
                    'msrp' => $msrp,
                    'price' => $price,
                    'shipping' => $shipping,
                    'shipping_time' => $shipping_time,
                    'enabled' => $enabled,
                    'inventory' => $inventory,
                    'cost' => $cost,
                    'status' => 1
                ];
                // 统计信息
                if (!$highestPrice) {
                    $highestPrice = $price;
                } else {
                    $price > $highestPrice ? $highestPrice = $price : '';
                }
                if (!$lowestPrice) {
                    $lowestPrice = $price;
                } else {
                    $price < $lowestPrice ? $lowestPrice = $price : '';
                }
                if (!$highestShipping) {
                    $highestShipping = $shipping;
                } else {
                    $shipping > $highestShipping ? $highestShipping = $shipping : '';
                }
                if (!$lowestShipping) {
                    $lowestShipping = $shipping;
                } else {
                    $shipping < $lowestShipping ? $lowestShipping = $shipping : '';
                }
                
                $inventorySum += $inventory;
                
                if ($id && isset($productCache[$variant_id])) {
                    $variant['pid'] = $id;
                    $variant['vid'] = $productCache[$variant_id];
                } elseif ($id && $variantInfo = WishWaitUploadProductVariant::where('variant_id', $variant_id)->field('vid, pid')->find()) {
                    $variant['pid'] = $variantInfo['pid'];
                    $variant['vid'] = $variantInfo['vid'];
                }
                $return['variant'][] = $variant;
            }
        }
        
        $return['product']['inventory'] = $inventorySum;
        $return['product']['highest_price'] = $highestPrice;
        $return['product']['lowest_price'] = $lowestPrice;
        $return['product']['highest_shipping'] = $highestShipping;
        $return['product']['lowest_shipping'] = $lowestShipping;
        
        return $return;
    }
    
    /**
     * 过滤掉多余的\
     * @param string $file
     * @return string
     */
    private function convertFile($file)
    {
        $fileInfo = pathInfo($file);
        $newfile = $fileInfo['dirname'] .'/'. substr($fileInfo['basename'], 0, -(strlen($fileInfo['extension']) + 1)) . '_new.'. $fileInfo['extension'];
        $handle = fopen($file, 'r');
        $backup = fopen($newfile, 'w');
        while($line = fgets($handle)) {
            $line = preg_replace('/\\\(?="")/', '', $line);
            fputs($backup, $line);
        }
        fclose($handle);
        fclose($backup);
        return $newfile;
    }
    
    /**
     * 转化金额
     * @param string $string
     * @return float
     */
    private function convertStringToFloat($string)
    {
        $str = str_replace('$', '', $string);
        $str = str_replace(',', '', $str);
        return floatval($str);
    }
}
