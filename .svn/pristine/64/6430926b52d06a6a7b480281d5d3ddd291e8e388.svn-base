<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-4-21
 * Time: 下午1:45
 */

namespace app\publish\queue;


use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\common\model\pandao\PandaoProduct;
use app\common\model\pandao\PandaoProductInfo;
use app\common\model\pandao\PandaoVariant;
use app\common\service\SwooleQueueJob;
use app\common\service\Twitter;
use think\Db;
use think\Exception;

class PandaoProductInsertDb extends SwooleQueueJob
{
    private $channel_id=8;
    private $accountId = null;
    private $accountLastUpdated = null;
    private $cache = null;
    public function getName(): string
    {
        return 'pandao商品写入数据库';
    }

    public function getDesc(): string
    {
        return 'pandao商品写入数据库';
    }

    public function getAuthor(): string
    {
        return 'joy';
    }
    public function init()
    {
        $this->cache = Cache::store('PandaoRsyncListing');
    }

    public function execute()
    {
        set_time_limit(0);
        try{
            $params = $this->params;
            if($params){
                list($account_id,$filename)=explode('|',$params);
                if(empty($account_id)){
                    throw new QueueException("账号非法");
                }else{
                    $this->accountId = $account_id;
                }

                $file = ROOT_PATH.'public/pandao/'.$params.'.csv';

                if(!file_exists($file)){
                    throw new QueueException("文件".$params.'不存在');
                }
                $result = $this->saveData($file);
                if($result === true){
                    $this->lastAction($file);
                }
            }else{
                throw new QueueException("数据元素为空");
            }
        }catch (Exception $exp){
            throw new QueueException("{$exp->getFile()};{$exp->getLine()};{$exp->getMessage()}");
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
        $this->accountLastUpdated ? Cache::store('PandaoAccountCache')->setListingSyncTime($this->accountId, $this->accountLastUpdated) : '';
        @unlink($file); //删除csv文件
    }
    private function saveData($file){
        try{

            $handle = fopen($file, 'r');
            $line=0;$product_id='';
            $variants=[];
            while($row = fgetcsv($handle))
            {
                if($line==0){  //第一个变体数据
                    $product_id = $row[0];
                    $variants[]=$row;
                }else{
                    if($row[0]==$product_id){ //如果下一行记录的product_id和第一条记录的product_id相同,则是同一个商品
                        $variants[]=$row;
                    }else{
                        //将上一个完整的商品数据入库
                        $this->insertAndUpdateData($variants);
                        $product_id = $row[0];
                        $variants=[];
                        $variants[]=$row;
                    }
                }
                ++$line;
            }
            $this->insertAndUpdateData($variants);
            fclose($handle);
            return true;
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }
    }

    private function insertAndUpdateData($variants)
    {
        $return = $this->saveCsvFileData($variants);

        if (empty($return)) {
            return true;
        }

        Db::startTrans();
        try {
            $model = new PandaoProduct();
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
                (new PandaoProductInfo())->allowField(true)->isUpdate(true)->save($info,['id' => $id]);
            } else {
                $id = Twitter::instance()->nextId($this->channel_id, $this->accountId);
                $product['id'] = $id;
                $info['id'] = $id;
                $model->allowField(true)->isUpdate(false)->save($product);
                (new PandaoProductInfo())->allowField(true)->isUpdate(false)->save($info);
            }
            $return['product']['id'] = $id;
            foreach ($return['variants'] as &$variant) {
                if ($variant['vid']) {
                    (new PandaoVariant())->allowField(true)->isUpdate(true)->save($variant);
                } else {
                    // $variant['vid'] = Twitter::instance()->nextId(3, $this->accountId);
                    $variant['add_time'] = time();
                    $variant['pid'] = $id;
                    $variantModel = new PandaoVariant();
                    $variantModel->allowField(true)->isUpdate(false)->save($variant);
                    $variant['vid'] = $variantModel->vid;
                }
            }
            $cacheData = $this->combieProductAndVariantCacheData($return);
            $this->setProductCache($product_id, $cacheData);
            Db::commit();
        } catch (Exception $exp) {
            Db::rollback();
            throw new Exception($exp->getMessage());
        }
    }
    /**
     * 拼装产品和变体缓存数据
     * @param item
     * @return array
     */
    private function combieProductAndVariantCacheData(&$item)
    {
        $arr['id'] = $item['product']['id'];
        $arr['last_updated'] = is_int($item['product']['last_updated'])?$item['product']['last_updated']:strtotime($item['product']['last_updated']);
        foreach ($item['variants'] as $variant) {
            $arr[$variant['variant_id']] = $variant['vid'];
        }

        return $arr;
    }
    /**
     *
     * @param $rows
     * @return bool
     */
    private function saveCsvFileData($rows){
        $product=$variant=[];
        $variants=[];
        $inventorySum = 0;
        $highestPrice = 0;
        $lowestPrice = 0;
        $highestShipping = 0;
        $lowestShipping = 0;
        $id = 0;
        foreach ($rows as $key=>$row){

            $productId = $row[0];
            $variant_id=$row[8];
            $price = $row[13];
            $shipping=$row[15];
            $inventory=$row[16];

            $productCache = $this->getProductCache($productId);

            $lastUpdated = strtotime($row[26]);

            $lastUpdated > $this->accountLastUpdated ? $this->accountLastUpdated = $lastUpdated : '';
            if (!$productCache && $productInfo = PandaoProduct::where('product_id', $productId)->field('id,last_updated')->find()) {
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

            if($key==0){
                $product = $this->formatProduct($row);
                $product['id']=$id;
                $product['account_id'] = $this->accountId;
                $variants['product']=$product;
            }
            $variant= $this->formatVariant($row);

            if ($id && isset($productCache[$variant_id])) {
                $variant['pid'] = $id;
                $variant['vid'] = $productCache[$variant_id];
            } elseif ($id && $variantInfo = PandaoVariant::where('variant_id', $variant_id)->field('vid, pid')->find()) {
                $variant['pid'] = $variantInfo['pid'];
                $variant['vid'] = $variantInfo['vid'];
            }
            $variants['variants'][]=$variant;

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
        }
        $variants['product']['lowest_price']=$lowestPrice;
        $variants['product']['highest_price']=$highestPrice;
        $variants['product']['lowest_shipping']=$lowestShipping;
        $variants['product']['highest_shipping']=$highestShipping;
        return $variants;
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
    private function formatTags($tags){
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
        return $newTags;
    }
    private function formatVariant($item){

        $data['vid']=0;
        $data['pid']=0;
        $data['product_id']=$item[0];
        $data['variant_id']=$item[8];
        $data['sku']=$item[9];
        $data['size']=$item[10];
        $data['color']=$item[11];
        $data['msrp']=$item[12];
        $data['price']=$item[13];
        $data['shipping']=$item[15];
        $data['inventory']=$item[16];
        $data['shipping_time']=$item[17];
        $data['enabled']=$item[18];
        //$data['is_promoted']=$item[19];
        $data['main_image']=$item[22];
        $data['updated_at']=$item[26];
        $data['status']=1;
        return $data;
    }
    private function formatProduct($item){
        $data['product_id']=$item[0];
        $data['name']=$item[1];
        $data['description']=$item[2];
        $data['parent_sku']=$item[5];
        if (is_int($item[6]))  {
            $data['number_saves']=$item[6];
        }
        //$data['number_sold']=$item[7];
        $data['landing_page_url']=$item[7];
        $data['review_status']=1;
        $data['is_promoted']=$item[19]=="True"?1:0;
        $data['upc']=$item[20];
        $data['landing_page_url']=$item[21];
        $data['main_image']=$item[22];
        $data['extra_images']=$item[23];
        $data['tags']=$this->formatTags($item[24]);
        $data['brand']=$item[25];
        $data['last_updated']=$item[26];
        $data['date_uploaded']=$item[27];
        $data['publish_status']=1;
        return $data;
    }

    private function formatData($item):array{
        $data=[];

        $data['product_id']=$item[0];
        $data['name']=$item[1];
        $data['description']=$item[2];
        //$data['']=$item[3];
        //$data['']=$item[4];
        $data['parent_sku']=$item[5];
        if (is_int($item[6]))  {
            $data['number_saves']=$item[6];
        }
        //$data['number_sold']=$item[7];
        $data['landing_page_url']=$item[7];
        $data['variant_id']=$item[8];
        $data['sku']=$item[9];
        $data['size']=$item[10];
        $data['color']=$item[11];
        $data['msrp']=$item[12];
        $data['price']=$item[13];
        //$data['']=$item[14];
        $data['shipping']=$item[15];
        $data['inventory']=$item[16];
        $data['shipping_time']=$item[17];
        $data['review_status']=$item[18];
        $data['enabled']=$item[19];
        $data['upc']=$item[20];
        $data['landing_page_url']=$item[21];
        $data['main_image']=$item[22];
        $data['extra_images']=$item[23];
        $data['tags']=$item[24];
        $data['brand']=$item[25];
        $data['last_updated']=$item[26];
        $data['date_uploaded']=$item[27];
        //$data['']=$item[28];
        //$data['']=$item[29];
        return $data;
    }
}