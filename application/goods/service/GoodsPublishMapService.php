<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/18
 * Time: 10:50
 */

namespace app\goods\service;
use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\exception\QueueException;
use app\common\model\aliexpress\AliexpressProduct;
use app\common\model\aliexpress\AliexpressProductSku;
use app\common\model\amazon\AmazonPublishProduct;
use app\common\model\amazon\AmazonPublishProductDetail;
use app\common\model\amazon\AmazonPublishProductVariant;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingVariation;
use app\common\model\Goods;
use app\common\model\GoodsPublishMap;
use app\common\model\joom\JoomProduct;
use app\common\model\joom\JoomVariant;
use app\common\model\pandao\PandaoProduct;
use app\common\model\pandao\PandaoVariant;
use app\common\model\wish\WishWaitUploadProduct;
use app\common\model\wish\WishWaitUploadProductVariant;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;

class GoodsPublishMapService
{
	public const CHANNEL=[
		 '1'=>'ebay',
	     '2'=>'amazon',
		 '3'=>'wish',
		 '4'=>'aliExpress',
		 '5'=>'CD',
		 '6'=>'Lazada',
         '7'=>'joom',
         '8'=>'pandao',
         '9'=>'shopee',
		];

    /**
     * 保存商品信息
     * @param $data
     */
	public function saveGoodsData($goods)
    {
        try{

            foreach (self::CHANNEL as $channel_id=>$channel)
            {
                $platform_sale_arr=json_decode($goods['platform_sale'],true);
                if(isset($platform_sale_arr[$channel]))
                {
                    $platform_sale = $platform_sale_arr[$channel];
                }else{//找不到，禁止上架
                    $platform_sale=2;
                }

                $where=[
                    'goods_id'=>['=',$goods['id']],
                    'channel'=>['=',$channel_id],
                ];
                $data=[
                    'channel'=>$channel_id,
                    'goods_id'=>$goods['id'],
                    'category_id'=>$goods['category_id'],
                    'spu'=>$goods['spu'],
                    'platform_sale'=>$platform_sale,
                    'sales_status'=>$goods['sales_status'],
                ];
                Db::startTrans();
                try{
                    if($has =GoodsPublishMap::where($where)->field('id')->find())
                    {
                        GoodsPublishMap::where('id','=',$has['id'])->update($data);
                    }else{
                        (new GoodsPublishMap())->save($data);
                    }
                    Db::commit();

                }catch (PDOException $exp){
                    Db::rollback();
                    throw new QueueException($exp->getMessage());
                }catch (DbException $exp){
                    Db::rollback();
                    throw new QueueException($exp->getMessage());
                }catch (Exception $exp){
                    Db::rollback();
                    throw new QueueException($exp->getMessage());
                }
            }
        }catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }
	/**
	 * @param array $goods 商品数据
	 *
	 * @return bool
	 */
	public static function addSpu(array $goods):bool
	{
		if(empty($goods))
		{
			return false;
		}
		if(!isset($goods['spu']) || !isset($goods['id']) || !isset($goods['category_id']) || !isset($goods['sales_status']) || !isset($goods['platform_sale']))
		{
			return false;
		}
		if(is_json($goods['platform_sale']))
		{
			$platform_sale = json_decode($goods['platform_sale'],true);
		}else{
			$platform_sale = $goods['platform_sale'];
		}

		foreach ($platform_sale as $platform=>$sale)
		{
			$channel = array_flip(self::CHANNEL)[$platform];
			$where=[
				'goods_id'=>$goods['id'],
				'spu'=>$goods['spu'],
				'channel'=>$channel,
			];
			$data=[
				'channel'=>$channel,
				'goods_id'=>$goods['id'],
				'category_id'=>$goods['category_id'],
				'spu'=>$goods['spu'],
				'platform_sale'=>$sale,
				'sales_status'=>$goods['sales_status']
			];
			//Db::startTrans();
			try{
				if(GoodsPublishMap::where($where)->find())
				{
					GoodsPublishMap::where($where)->update($data);
				}else{
					GoodsPublishMap::insert($data);
				}
				//Db::commit();
			}catch(JsonErrorException $exp){
				//Db::rollback();
				throw new JsonErrorException($exp->getFile().$exp->getLine().$exp->getMessage());
			}
		}

		return true;
	}
	/**
	 * @param $channel 平台id
	 * @param $spu  spu
	 * @param $account_id 账号id
	 * @param int $site_id 站点id
	 */
	public static function update($channel,$spu,$account_id,$status=1,$site_id=0)
	{
		try{
			if(empty($channel) && empty($spu))
			{
				return ;
			}

			$where['channel']=['=',$channel];
//			$where['spu']=['=',$spu];
            $goodsId = Goods::where('spu',$spu)->value('id');
            $where['goods_id'] = $goodsId;
			$res = GoodsPublishMap::where($where)->find();

            if($res)
            {
                $publish_status = json_decode( $res['publish_status'], true );
            }else{
                return '';
            }

            if(empty($publish_status))
            {
                $publish_status=[];
            }

            if($status==1)//加入
            {
                if($channel==1) //ebay
                {
                    if(!isset($publish_status[$site_id]))
                    {
                        $publish_status[$site_id]=[];
                    }
                    $publish_status[$site_id] = array_values(array_unique($publish_status[$site_id]));//去重
                    if (!in_array($account_id,$publish_status[$site_id])) {//里面不存在才加入
                        array_push($publish_status[$site_id],(string)$account_id);//需要强制转为字符串，json_search不支持搜索整型
                    }
                } else {
                    $publish_status = array_values(array_unique($publish_status));
                    if (!in_array($account_id, $publish_status)) {
                        array_push($publish_status,(string)$account_id);
                    }
                    $new_publish_status = [];
                    foreach ($publish_status as $val) {
                        if (is_array($val)) {
                            $new_publish_status = array_merge($new_publish_status, $val);
                        } else {
                            $new_publish_status[] = $val;
                        }
                    }
                    $publish_status = array_values(array_unique($new_publish_status));
                }
            }else{//删除
                if($channel==1) //ebay
                {
                    foreach ($publish_status as $siteid => &$accounts) {
                        if ($siteid == $site_id)
                        {
                            $key  = array_search($account_id,$accounts);
                            unset($accounts[$key]);
                        }
                    }
                } else {
                    $new_publish_status = [];
                    foreach ($publish_status as $val) {
                        if (is_array($val)) {
                            $new_publish_status = array_merge($new_publish_status, $val);
                        } else {
                            $new_publish_status[] = $val;
                        }
                    }
                    $new_publish_status = array_values(array_unique($new_publish_status));
                    $key  = array_search($account_id,$new_publish_status);
                    unset($new_publish_status[$key]);
                    $publish_status = $new_publish_status;
                }
            }

//            Cache::store('goodsPublishMap')->setPublishCache($channel,$spu,$publish_status);
            if ($channel !== 1) {
                foreach ($publish_status as &$accountId) {
                    $accountId = (string)$accountId;//有部分是按整型保存的，全部转化为字符串
                }
            }
            $publish_status = array_values(array_unique($publish_status));//再次去重
            $update['publish_status']=json_encode($publish_status);
            Db::startTrans();
			try{
				GoodsPublishMap::where('id','=',$res['id'])->update($update);
				if($status==1){
				    GoodsPublishMap::where('id',$res['id'])->setInc('publish_count',1);
                }
				Db::commit();
			}catch (PDOException $exp){
			    Db::rollback();
				throw new JsonErrorException($exp->getFile().$exp->getLine().$exp->getMessage());
			}

		}catch (Exception $exp){
			throw new JsonErrorException($exp->getFile().$exp->getLine().$exp->getMessage());
		}
	}

	public static function ebay($skus,$goods_id){
        $model = new EbayListingVariation();
        $result=[];
        foreach ($skus as $sku){
            $where=[
                'listing_status'=>['IN',[3,5,6]],
                'draft'=>['=',0],
                'a.sku_id'=>['=',$sku['id']]
            ];
            $count = $model->alias('a')->join('ebay_listing b','a.listing_id=b.id','LEFT')->where($where)->count();
            $result[$sku['sku']]=$count;
        }
        $map=[
            'listing_status'=>['IN',[3,5,6]],
            'draft'=>['=',0],
            'goods_id'=>['=',$goods_id]
        ];
        $spuCount = EbayListing::where($map)->field('id')->count();

        return ['statistics'=>$result,'publish_count'=>$spuCount];
    }
    public static function amazon($skus,$goods_id){
        $model = new AmazonPublishProductDetail();
        $result=[];
        foreach ($skus as $sku){
            $where=[
                'quantity'=>['<>',0],
                'publish_status'=>['=',2],
                'a.sku'=>['=',$sku['sku']]
            ];
            $count = $model->alias('a')->join('amazon_publish_product b','a.product_id=b.id','LEFT')->where($where)->count();
            $result[$sku['sku']]=$count;
        }
        $map=[
            'publish_status'=>['=',2],
            'goods_id'=>['=',$goods_id]
        ];
        $spuCount = AmazonPublishProduct::where($map)->field('id')->count();

        return ['statistics'=>$result,'publish_count'=>$spuCount];
    }
    public static function wish($skus,$goods_id){
	    $model = new WishWaitUploadProductVariant();
	    $result=[];
        foreach ($skus as $sku){
            $where=[
                'review_status'=>['IN',[0,1]],
                'enabled'=>['=',1],
                'publish_status'=>['=',1],
                'sku_id'=>['=',$sku['id']]
            ];
            $count = $model->alias('a')->join('wish_wait_upload_product b','a.pid=b.id','LEFT')->where($where)->count();
            $result[$sku['sku']]=$count;
        }
        $map=[
            'publish_status'=>['=',1],
            'goods_id'=>['=',$goods_id]
        ];
        $spuCount = WishWaitUploadProduct::where($map)->field('id')->count();

        return ['statistics'=>$result,'publish_count'=>$spuCount];
    }
    public static function aliexpress($skus,$goods_id){
        $model = new AliexpressProductSku();
        $result=[];
        foreach ($skus as $sku){
            $where=[
                'status'=>['=',1],
                'product_status_type'=>['IN',[1,3]],
                'goods_sku_id'=>['=',$sku['id']]
            ];
            $count = $model->alias('a')->join('aliexpress_product b','a.ali_product_id=b.id','LEFT')->where($where)->count();
            $result[$sku['sku']]=$count;
        }
        $map=[
            'status'=>['=',1],
            'product_status_type'=>['IN',[1,3]],
            'goods_id'=>['=',$goods_id]
        ];
        $spuCount = AliexpressProduct::where($map)->field('id')->count();

        return ['statistics'=>$result,'publish_count'=>$spuCount];
    }
    public static function joom($skus,$goods_id){
        $model = new JoomVariant();
        $result=[];
        foreach ($skus as $sku){
            $where=[
                'a.enabled'=>['=',1],
                'review_status'=>['IN',[1,0]],
                'sku_id'=>['=',$sku['id']]
            ];
            $count = $model->alias('a')->join('joom_product b','a.joom_product_id=b.id','LEFT')->where($where)->count();
            $result[$sku['sku']]=$count;
        }
        $map=[
            'enabled'=>['=',1],
            'goods_id'=>['=',$goods_id]
        ];
        $spuCount = JoomProduct::where($map)->field('id')->count();

        return ['statistics'=>$result,'publish_count'=>$spuCount];
    }
    public static function pandao($skus,$goods_id){
        $model = new PandaoVariant();
        $result=[];
        foreach ($skus as $sku){
            $where=[
                'review_status'=>['IN',[0,1]],
                'enabled'=>['=',1],
                'publish_status'=>['=',1],
                'sku_id'=>['=',$sku['id']]
            ];
            $count = $model->alias('a')->join('pandao_product b','a.pid=b.id','LEFT')->where($where)->count();
            $result[$sku['sku']]=$count;
        }
        $map=[
            'publish_status'=>['=',1],
            'goods_id'=>['=',$goods_id]
        ];
        $spuCount = PandaoProduct::where($map)->field('id')->count();

        return ['statistics'=>$result,'publish_count'=>$spuCount];
    }
    public static function shopee($skus){

    }
}