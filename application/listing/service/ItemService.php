<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/9/22
 * Time: 13:45
 */

namespace app\listing\service;
use app\api\service\Goods;
use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\aliexpress\AliexpressProduct;
use app\common\model\aliexpress\AliexpressProductSku;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingVariation;
use app\common\model\GoodsSkuMap;
use app\common\model\joom\JoomVariant;
use app\common\model\joom\JoomProduct;
use app\common\model\pandao\PandaoProduct;
use app\common\model\pandao\PandaoVariant;
use app\common\model\shopee\ShopeeProduct;
use app\common\model\shopee\ShopeeVariant;
use app\common\model\wish\WishWaitUploadProductVariant;
use app\goods\service\GoodsPublishMapService;
use app\listing\validate\ItemValidate;
use app\common\model\wish\WishWaitUploadProduct;
use app\common\model\amazon\AmazonListing;
use erp\AbsServer;
use think\Exception;
use app\common\model\Goods as GoodsModel;
use think\Db;
class ItemService {
    const CHANNEL=[
        '1'=>'ebay',
        '2'=>'amazon',
        '3'=>'wish',
        '4'=>'aliExpress',
        '5'=>'CD',
        '6'=>'Lazada',
        '7' => 'joom',
        '8' => 'pandao',
        '9' => 'shopee',
    ];
    protected $account_id=0;

    /**
     * 获取平台id
     * @param $platform
     *
     * @return mixed
     */
    protected function getChannelId($platform)
    {
        return array_flip(self::CHANNEL)[$platform];
    }

    /**
     * 查询平台sku与系统sku记录是否存在
     * @param $where
     *
     * @return array|false|\PDOStatement|string|\think\Model
     */
    protected function getGoodsSkuMapRow($where)
    {
        return GoodsSkuMap::where($where)->find();
    }

    /**
     * 更新商品刊登状态
     * @param $channel
     * @param $spu
     * @param $account_id
     * @param int $status
     * @param int $site_id
     */
    protected function updateGoodsPublishStatus($channel,$spu,$account_id,$status=1,$site_id=0)
    {
        GoodsPublishMapService::update($channel,$spu,$account_id,$status,$site_id);
    }

    /**
     *
     * @param $skus
     * @param $sku_id
     * @param $sku_code
     * @param $channel
     * @param $channel_sku
     * @throws Exception
     */
    protected function updateGoodsSKuMap($skus,$sku_id,$sku_code,$channel,$channel_sku)
    {
        try{
            if($this->account_id)
            {
                foreach ($skus as $sku)
                {
                    $sku_code_quantity[$sku['sku_id']]=[
                        'sku_id'=>$sku['sku_id'],
                        'quantity'=>$sku['quantity'],
                        'sku_code'=>$sku['local_sku']
                    ];
                }

                $where=[
                    'sku_id'=>['=',$sku_id],
                    'sku_code'=>['=',$sku_code],
                    'channel_id'=>['=',$channel],
                    'account_id'=>['=',$this->account_id],
                    'channel_sku'=>['=',$channel_sku],
                    //'quantity'=>['=',$v['quantity']],
                ];

                $data=[
                    'sku_id'=>$sku_id,
                    'sku_code'=>$sku_code,
                    'channel_id'=>$channel,
                    'account_id'=>$this->account_id,
                    'channel_sku'=>$channel_sku,
                    'quantity'=>count($skus),
                    'sku_code_quantity'=>json_encode($sku_code_quantity)
                ];

                if($has = $this->getGoodsSkuMapRow($where))
                {
                    GoodsSkuMap::where('id','=',$has['id'])->update($data);
                }else{
                    GoodsSkuMap::insert($data);
                }
            }
        }catch (Exception $exp){
            throw new Exception($exp->getLine().$exp->getLine().$exp->getMessage());
        }
    }

    /**
     * 更新关联
     * @param $post
     * @param $platform
     * @return array
     */
    public function updateRelation($post,$platform)
    {
        $platform = $this->getChannelId($platform);

        $data = json_decode($post,true);

        if(is_array($data))
        {
            foreach ($data as $v)
            {

                $error = (new ItemValidate())->checkData($v);

                if($error)
                {
                    return ['result'=>false,'message'=>$error];
                }

                $error = (new ItemValidate())->checkLocalSku($v['local']);

                if($error)
                {
                    return ['result'=>false,'message'=>$error];
                }
            }
        }

        switch ($platform)
        {
            case 1:
                $response = $this->ebayMap($data,$platform);
                break;
            case 2:
                $response = $this->amazonMap($data,$platform);
                break;
            case 3:
                $response = $this->wishMap($data,$platform);
                break;
            case 4:
                $response = $this->aliexpressMap($data,$platform);
                break;
            case 5:
                break;
            case 6:
                break;
            case 7:
                $response = $this->joomMap($data,$platform);
                break;
            case 8:
                $response = $this->pandaoMap($data,$platform);
                break;
            case 9:
                $response = $this->shopeeMap($data,$platform);
                break;
        }
        return $response;
    }

    /**
     * 速卖通关联
     * @param $data
     * @param $channel
     * @return array
     * @throws Exception
     */
    public function aliexpressMap($data,$channel) {
        try{

            foreach ($data as $v)
            {

                if(isset($v['local']) && is_array($v['local']))
                {

                    $goods_id = $v['local'][0]['goods_id'];
                    $sku_id = $v['local'][0]['sku_id'];
                    $sku_code = $v['local'][0]['local_sku'];
                    $skus = $v['local'];

                    if(empty($this->account_id))
                    {
                        $product = (new AliexpressProductSku())
                            ->field('id,ali_product_id')
                            ->where('id','=',$v['id'])
                            ->with(['product'=>function($query){$query->field('id,account_id');}])->find();

                        if($product)
                        {
                            $this->account_id=$product->product->account_id;

                            $spu = static::getGoodsInfo($goods_id);

                            if($spu)
                            {
                                $this->updateGoodsPublishStatus(4,$spu,$this->account_id);
                            }
                            AliexpressProduct::where('id','=',$product->product->id)->update(['goods_id'=>$goods_id,'goods_spu'=>$spu]);
                        }else{
                            throw new JsonErrorException("已刊登商品SKU不存在");
                        }
                    }

                    $this->updateGoodsSKuMap($skus,$sku_id,$sku_code,$channel,$v['sku']);

                    AliexpressProductSku::where('id','=',$v['id'])->update(['goods_sku_id'=>$sku_id]);

                }else{
                    return ['result'=>false,'message'=>'数据格式错误'];
                }
            }

            return ['result'=>true,'message'=>'更新成功'];
        }catch (JsonErrorException $exp) {
            throw new JsonErrorException("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
        }catch (Exception $exp){
            throw new Exception("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
        }
    }

    /**ebay关联
     * @param $data
     * @param $channel
     * @return array
     * @throws Exception
     */
    public function ebayMap($data,$channel)
    {
        try{

            foreach ($data as $v)
            {

                try{
                    if(isset($v['local']) && is_array($v['local']))
                    {

                        $goods_id = $v['local'][0]['goods_id'];
                        $sku_id = $v['local'][0]['sku_id'];
                        $sku_code = $v['local'][0]['local_sku'];
                        $skus = $v['local'];

                        if(empty($this->account_id))
                        {
                            $product = (new EbayListingVariation())
                                ->field('id,listing_id')
                                ->where('id','=',$v['id'])
                                ->with(['product'=>function($query){$query->field('id,account_id');}])->find();
                            if($product)
                            {
                                $this->account_id=$product->product->account_id;

                                $spu = static::getGoodsInfo($goods_id);

                                if($spu)
                                {
                                    $this->updateGoodsPublishStatus(1,$spu,$this->account_id);
                                }

                                EbayListing::where('id','=',$product->product->id)
                                    ->update(['goods_id'=>$goods_id,'spu'=>$spu]);
                            }
                        }
                        $this->updateGoodsSKuMap($skus,$sku_id,$sku_code,$channel,$v['sku']);
                        EbayListingVariation::where('id','=',$v['id'])->update(['sku_id'=>$sku_id]);
                    }else{
                        return ['result'=>false,'message'=>'数据格式错误'];
                    }
                }catch (JsonErrorException $exp){

                    throw new JsonErrorException($exp->getMessage());
                }

            }

            return ['result'=>true,'message'=>'更新成功'];
        }catch (JsonErrorException $exp) {
            throw new JsonErrorException("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
        }catch (Exception $exp){
            throw new Exception("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
        }
    }
    /**
     * wish关联
     * @param $data
     * @param $channel
     * @return array
     * @throws Exception
     */
    public function pandaoMap($data,$channel)
    {
        try{
            foreach ($data as $v)
            {

                try{
                    if(isset($v['local']) && is_array($v['local']))
                    {

                        $goods_id = $v['local'][0]['goods_id'];
                        $sku_id = $v['local'][0]['sku_id'];
                        $sku_code = $v['local'][0]['local_sku'];
                        $skus = $v['local'];

                        if(empty($this->account_id))
                        {
                            $product = (new PandaoVariant())
                                ->field('vid,pid')
                                ->where('vid','=',$v['id'])
                                ->with(['product'=>function($query){$query->field('id,account_id');}])->find();

                            if($product)
                            {
                                $this->account_id=$product->product->account_id;

                                $spu = static::getGoodsInfo($goods_id);

                                if($spu)
                                {
                                    $this->updateGoodsPublishStatus(8,$spu,$this->account_id);
                                }else{
                                    $spu='';
                                }

                                PandaoProduct::where('id','=',$product->pid)->update(['goods_id'=>$goods_id,'spu'=>$spu]);
                            }
                        }

                        $this->updateGoodsSKuMap($skus,$sku_id,$sku_code,$channel,$v['sku']);

                        PandaoVariant::where('vid','=',$v['id'])->update(['sku_id'=>$sku_id]);

                    }else{
                        return ['result'=>false,'message'=>'数据格式错误'];
                    }
                }catch (JsonErrorException $exp){
                    throw new JsonErrorException($exp->getMessage());
                }

            }
            return ['result'=>true,'message'=>'修改成功'];
        }catch(JsonErrorException $exp) {
            throw new JsonErrorException("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
        }catch(Exception $exp){
            throw new Exception("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
        }
    }

    /**
     * wish关联
     * @param $data
     * @param $channel
     * @return array
     * @throws Exception
     */
    public function wishMap($data,$channel)
    {
        try{
            foreach ($data as $v)
            {

                try{
                    if(isset($v['local']) && is_array($v['local']))
                    {

                        $goods_id = $v['local'][0]['goods_id'];
                        $sku_id = $v['local'][0]['sku_id'];
                        $sku_code = $v['local'][0]['local_sku'];
                        $skus = $v['local'];

                        if(empty($this->account_id))
                        {
                            $product = (new WishWaitUploadProductVariant())
                                ->field('vid,pid')
                                ->where('vid','=',$v['id'])
                                ->with(['product'=>function($query){$query->field('id,accountid');}])->find();
                            if($product)
                            {
                                $this->account_id=$product->product->accountid;

                                $spu = static::getGoodsInfo($goods_id);

                                if($spu)
                                {
                                    $this->updateGoodsPublishStatus(3,$spu,$this->account_id);
                                }

                                WishWaitUploadProduct::where('id','=',$product->product->id)->update(['goods_id'=>$goods_id]);
                            }
                        }

                        $this->updateGoodsSKuMap($skus,$sku_id,$sku_code,$channel,$v['sku']);

                        WishWaitUploadProductVariant::where('vid','=',$v['id'])->update(['sku_id'=>$sku_id]);

                    }else{
                        return ['result'=>false,'message'=>'数据格式错误'];
                    }
                }catch (JsonErrorException $exp){
                    throw new JsonErrorException($exp->getMessage());
                }

            }
            return ['result'=>true,'message'=>'修改成功'];
        }catch(JsonErrorException $exp) {
            throw new JsonErrorException("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
        }catch(Exception $exp){
            throw new Exception("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
        }
    }

    /**
     * amazon关联
     * @param $data
     * @param $channel
     * @return array
     * @throws Exception
     */
    public function amazonMap($data,$channel)
    {
        try{
            foreach ($data as $v)
            {

                try{
                    if(isset($v['local']) && is_array($v['local']))
                    {

                        $goods_id = $v['local'][0]['goods_id'];
                        $sku_id = $v['local'][0]['sku_id'];
                        $sku_code = $v['local'][0]['local_sku'];
                        $skus = $v['local'];
                        $thumb = '';
                        $goodsInfo = Cache::store('Goods')->getSkuInfo($sku_id);
                        if (!empty($goodsInfo)) {
                            $thumb = $goodsInfo['thumb'];
                        }
                        if(empty($this->account_id))
                        {
                            $product = (new AmazonListing())
                                ->field('id,account_id,amazon_listing_id')
                                ->where('id','=',$v['id'])
                                ->find();

                            if($product)
                            {
                                $this->account_id=$product['account_id'];

                                $spu = static::getGoodsInfo($goods_id);

                                if($spu)
                                {
                                    $this->updateGoodsPublishStatus(2,$spu,$this->account_id);
                                }

                                //AmazonListing::where('id','=',$product->id)->update(['goods_id'=>$goods_id]);
                            }
                        }

                        $this->updateGoodsSKuMap($skus,$sku_id,$sku_code,$channel,$v['sku']);

                        foreach ($skus as $sku)
                        {
                            $sku_quantity[$sku['sku_id']] = $sku['local_sku'] . '*' . $sku['quantity'];
                        }
                        $sku_quantity_string = implode(',',$sku_quantity);

                        AmazonListing::where('id','=',$v['id'])->update(['image_url' => $thumb, 'goods_id'=>$goods_id,'spu'=>$spu,'sku_id'=>$sku_id,'sku'=>$sku_code,'sku_quantity'=>$sku_quantity_string]);

                    }else{
                        return ['result'=>false,'message'=>'数据格式错误'];
                    }
                }catch (JsonErrorException $exp){
                    throw new JsonErrorException($exp->getMessage());
                }

            }
            return ['result'=>true,'message'=>'修改成功'];
        }catch(JsonErrorException $exp) {
            throw new JsonErrorException("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
        }catch(Exception $exp){
            throw new Exception("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
        }
    }

    /**
     * joom关联
     * @param $data
     * @param $channel
     * @return array
     * @throws Exception
     */
    public function joomMap($data,$channel)
    {
        try{
            foreach ($data as $v)
            {
                try{
                    if(isset($v['local']) && is_array($v['local']))
                    {

                        $goods_id = $v['local'][0]['goods_id'];
                        $sku_id = $v['local'][0]['sku_id'];
                        $sku_code = $v['local'][0]['local_sku'];
                        $skus = $v['local'];

                        if(empty($this->account_id))
                        {
                            $vmodel = new JoomVariant();
                            $variant = $vmodel->where(['id' => $v['id']])->find();

                            if(!empty($variant)) {

                                $pmodel = new JoomProduct();
                                $product = $pmodel->where(['id' => $variant['joom_product_id']])->find();

                                if($product)
                                {
                                    $this->account_id = $product->account_id;

                                    $spu = static::getGoodsInfo($goods_id);

                                    if($spu)
                                    {
                                        $this->updateGoodsPublishStatus($channel, $spu, $this->account_id);
                                    }

                                    $pmodel->update(['goods_id'=>$goods_id], ['id' => $variant['joom_product_id']]);
                                }
                            }
                        }

                        $this->updateGoodsSKuMap($skus,$sku_id,$sku_code,$channel,$v['sku']);

                        $vmodel->update(['sku_id'=>$sku_id], ['id' => $v['id']]);

                    }else{
                        return ['result'=>false,'message'=>'数据格式错误'];
                    }
                }catch (JsonErrorException $exp){
                    throw new JsonErrorException($exp->getMessage());
                }

            }
            return ['result'=>true,'message'=>'修改成功'];
        }catch(JsonErrorException $exp) {
            throw new JsonErrorException("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
        }catch(Exception $exp){
            throw new Exception("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
        }
    }

    /**
     * 获取商品信息
     * @param $goods_id
     * @return string
     */
    public static function getGoodsInfo($goods_id)
    {
        $spu='';

        if(!$goods_id)
        {
            return $spu;
        }

        $goods = GoodsModel::where('id',$goods_id)->find();
        if($goods)
        {
            $spu = $goods['spu'];
        }
        return $spu;

    }

    /**
     * shopee关联
     * @param $data
     * @param $channel
     * @return array
     * @throws Exception
     */
    public function shopeeMap($data,$channel)
    {
        try{
            foreach ($data as $v) {
                try{
                    if(isset($v['local']) && is_array($v['local']))
                    {
                        //获取本地spu
                        $goods_id = $v['local'][0]['goods_id'];
                        $sku_id = $v['local'][0]['sku_id'];
                        $sku_code = $v['local'][0]['local_sku'];
                        $skus = $v['local'];//获取所有被选中的sku
                        if(!empty($v['id'])){//多属性
                            if(empty($this->account_id)) {
                                $vmodel = new ShopeeVariant();
                                $variant = $vmodel->where('vid',$v['id'])->find();

                                if(!empty($variant)) {//不为空则为多属性

                                    $pmodel = new ShopeeProduct();
                                    $product = $pmodel->where(['id' => $variant['pid']])->find();

                                    if($product)//如果产品表存在该商品
                                    {
                                        $this->account_id = $product->account_id;

                                        $spu = static::getGoodsInfo($goods_id);

                                        if($spu)
                                        {
                                            $this->updateGoodsPublishStatus($channel, $spu, $this->account_id);
                                        }
                                        $pmodel->update(['goods_id'=>$goods_id,'spu'=>$sku_code], ['id' => $variant['pid']]);
                                    }
                                    $vmodel->update(['sku_id'=>$sku_id], ['vid' => $v['id']]);
                                }
                            }
                            $this->updateGoodsSKuMap($skus,$sku_id,$sku_code,$channel,$v['sku']);
                        }else{//单属性
                                $pmodel = new ShopeeProduct();
                                $product = $pmodel->where('id' ,$v['pid'])->find();

                                $this->account_id = $product->account_id;

                                $spu = static::getGoodsInfo($goods_id);

                                if($spu)
                                {
                                    $this->updateGoodsPublishStatus($channel, $spu, $this->account_id);
                                }
                                $pmodel->update(['goods_id'=>$goods_id,'spu'=>$sku_code], ['id' => $v['pid']]);

                        }
                    }else{
                        return ['result'=>false,'message'=>'数据格式错误'];
                    }
                }catch (JsonErrorException $exp){
                    throw new JsonErrorException($exp->getMessage());
                }

            }
            return ['result'=>true,'message'=>'修改成功'];
        }catch(JsonErrorException $exp) {
            throw new JsonErrorException("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
        }catch(Exception $exp){
            throw new Exception("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
        }
    }





}