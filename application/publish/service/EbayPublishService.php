<?php

/**
 * Description of EbayPublishService
 * @datetime 2017-6-13  9:39:58
 * @author joy
 */

namespace app\publish\service;
use app\common\exception\JsonErrorException;
use app\common\model\ebay\EbayDraft;
use app\common\model\GoodsDiscount;
use app\common\model\GoodsLang;
use app\common\model\GoodsSku;
use app\common\model\GoodsTortDescription;
use app\common\service\Common;
use think\Db;
use app\goods\service\GoodsImage;
use app\common\model\GoodsPublishMap;
use app\common\model\Goods;
use app\common\model\Category;
use think\helper\Str;

class EbayPublishService {
    protected  $goodsPublishMapModel;
    protected  $ebayListingModel;
    protected  $ebayAccountModel;
    public function __construct() {
//        $this->goodsPublishMapModel = new \app\common\model\GoodsPublishMap();
//        $this->ebayListingModel = new \app\common\model\ebay\EbayListing;
//        $this->ebayAccountModel = new \app\common\model\ebay\EbayAccount;
    }
     /**
     * 获取待刊登商品列表
     */
    
    public function getWaitPublishGoods($param,$page,$pageSize,$fields="*")
    {

        $where=[];
        $goodsMod = new Goods();
        $category = new Category();
        $where['channel']=['eq',1];

        //$where['m.platform_sale']=['eq',1];
//        $where['m.platform_sale']=['IN',array(0,1)];
        $where['g.sales_status'] = ['IN', array(1, 4, 6)];

        $post = $param;
        if(isset($post['category_id']) && $post['category_id']){
            $categoryId = $post['category_id'];
            $cateIds = $category->getAllChilds($categoryId);
            $where['g.category_id'] = ['in',$cateIds];
        }
        if (!empty($post['start_date']) || !empty($post['end_date'])) {
            $startDate = empty($post['start_date']) ? 0 : strtotime($post['start_date']);
            $endDate = empty($post['end_date']) ? time() : strtotime($post['end_date'].' 23:59:59');
            $where['g.publish_time'] = ["between",[(string)$startDate,(string)$endDate]];
        }

        $goodsIds = [];

        if( isset($post['snType']) && $param['snType']=='spu' && $post['snText'])
        {
            $spuArr = json_decode($post['snText'],true);
            if (is_null($spuArr) && is_string($post['snText'])) {
                $goodsIds = Goods::where('spu','like',$post['snText'].'%')->column('id');
            } elseif (is_array($spuArr) && count($spuArr) == 1) {
                $goodsIds = Goods::where('spu','like',$spuArr[0].'%')->column('id');
            } elseif (is_array($spuArr) && count($spuArr) > 1) {
                $goodsIds = Goods::whereIn('spu',$spuArr)->column('id');
            }
            if (empty($goodsIds)) {
                $where['m.goods_id'] = ['exp','is null'];
            } else {
                $where['m.goods_id'] = ['in',$goodsIds];
            }

        }

        if( isset($post['snType']) && $post['snType']=='name' && $post['snText'])
        {
            $name = json_decode($post['snText'],true);
            if (is_null($name) && is_string($post['snText'])) {
                $goodsIds = Goods::where('name','like',$post['snText'].'%')->column('id');
            } elseif (is_string($name)) {
                $goodsIds = Goods::where('name','like',$name.'%')->column('id');
            } elseif (is_array($name) && count($name) == 1) {
                $goodsIds = Goods::where('name','like',$name[0].'%')->column('id');
            } elseif (is_array($name) && count($name) > 1) {
                $goodsIds = Goods::whereIn('name',$name)->column('id');
            }
            if (empty($goodsIds)) {
                $where['m.goods_id'] = ['exp','is null'];
            } else {
                $where['m.goods_id'] = ['in',$goodsIds];
            }
        }

        if (isset($post['snType']) && $post['snType']=='sku' && $post['snText']) {
            $skus = json_decode($post['snText'],true);
            if (is_null($skus) && is_string($post['snText'])) {
                $goodsIds = GoodsSku::distinct(true)->where('sku','like',$post['snText'].'%')->column('goods_id');
            } elseif (is_string($skus)) {
                $goodsIds = GoodsSku::distinct(true)->where('sku','like',$skus.'%')->column('goods_id');
            } elseif (is_array($skus) && count($skus) == 1) {
                $goodsIds = GoodsSku::distinct(true)->where('sku','like',$skus[0].'%')->column('goods_id');
            } elseif (is_array($skus) && count($skus) > 1) {
                $goodsIds = GoodsSku::distinct(true)->whereIn('sku',$skus)->column('goods_id');
            }
            if (empty($goodsIds)) {
                $where['m.goods_id'] = ['exp','is null'];
            } else {
                $where['m.goods_id'] = ['in',$goodsIds];
            }
        }

        if (isset($post['draft_flag']) && in_array($post['draft_flag'], ['0','1']))
        {
            if ($goodsIds) {//上面设置过
                $tmpGoodsIds = EbayDraft::distinct(true)->where('site_id',$param['site'])
                    ->whereIn('goods_id', $goodsIds)->column('goods_id');
                if ($post['draft_flag']) {//有范本
                    $where['m.goods_id'] = $tmpGoodsIds ? ['in', $tmpGoodsIds] : ['exp', 'is null'];//重写搜索条件
                } else {//没有范本
                    $goodsIds = array_diff($goodsIds, $tmpGoodsIds);//滤除掉有范本的
                    $where['m.goods_id'] = ['in', $goodsIds];//重写搜索条件
                }
            } else {//上面没有设置过
                $tmpGoodsIds = EbayDraft::distinct(true)->where('site_id',$param['site'])->column('goods_id');
                if ($tmpGoodsIds) {
                    $where['m.goods_id'] = [$post['draft_flag'] ? 'in' : 'not in', $tmpGoodsIds];
                }
            }
        }


//        if(is_numeric($post['site']) && is_numeric($post['account']))
//        {
//            #$map="JSON_SEARCH(publish_status,'one',{$post['account']},NULL,'$.\"{$post['site']}\"') IS NULL ";
//            $map="JSON_SEARCH(publish_status,'one',{$post['account']}) IS NULL ";
//            $map=[];
//        }elseif(is_numeric($post['site'])){
//            $map = [];
//            return ['data'=>[],'count'=>0,'page'=>$page,'pageSize'=>$pageSize];
//        }else{
//            $map=[];
//        }
        //添加武汉深圳销售员隔离功能
//        $userInfo = Common::getUserInfo();
//        $userId = $userInfo['user_id']??0;







        $model = new GoodsPublishMap;
        $count = $model->alias('m')
        ->join('goods g','m.goods_id=g.id','LEFT')
        ->where($where)->count();

        
        $data = $model->order('m.id desc')
        ->field('m.spu,m.goods_id,g.platform_sale,g.thumb,g.name,publish_time,g.category_id,g.platform')
        ->alias('m')->join('goods g','m.goods_id=g.id','LEFT')
        ->where($where)->page($page,$pageSize)->select();

        if ($data) {
            $data = collection($data)->toArray();
            $goodsIds = array_column($data,'goods_id');
            $enTitles = GoodsLang::whereIn('goods_id',$goodsIds)->where('lang_id',2)->column('title','goods_id');
            $tortGoodsIds = GoodsTortDescription::distinct(true)->whereIn('goods_id',$goodsIds)->column('goods_id');
            $draftGoodsIds = EbayDraft::distinct(true)->whereIn('goods_id',$goodsIds)
                ->where('site_id',$param['site'])->column('goods_id');
            $discountGoodsIds = GoodsDiscount::whereIn('goods_id',$goodsIds)->column('goods_id');
        }

        
        foreach ($data as $k=> &$d)
        {
            $d['id'] = $d['goods_id'];
            $d['thumb'] = GoodsImage::getThumbPath($d['thumb'],60,60);
            $d['category_name'] = $goodsMod->getCategoryAttr([],['category_id'=>$d['category_id']]);
            $d['en_title'] = $enTitles[$d['id']]??'';
            $d['platform_sale_status'] = (new \app\goods\service\GoodsHelp())->getPlatformForChannel($d['id'],1) ? '可选上架' : '禁止上架';
            $d['tort_flag'] = in_array($d['goods_id'],$tortGoodsIds) ? 1 : 0;
            $d['draft_flag'] = in_array($d['goods_id'], $draftGoodsIds) ? 1 : 0;
            $d['discount_flag'] = in_array($d['goods_id'],$discountGoodsIds) ? 1 : 0;
        }
        return ['data'=>$data,'count'=>$count,'page'=>$page,'pageSize'=>$pageSize];
    }
    
    /**
     * 统计未刊登商品数量
     * @param type $where
     */
    public function getWaitPublishGoodsCount($where)
    {
        return $this->goodsPublishMapModel->alias('p')->join('goods g ','p.goods_id=g.id','LEFT')->join('category c','g.category_id=c.id','LEFT')
                ->where($where)->count();
    }
    /**
     * eBay待刊登列表查询条件
     * @param type $post
     * @return string
     */
    public function getWaitPublishGoodsWhere($post)
    {
        $where=[];
        
        $where['p.status']=['eq',1];   
        $where['p.channel']=['eq',1];  
        $where['p.platform_sale']=['eq',1];
        $where['p.publish_status']=['eq',0];
        
        if( isset($post['snType']) && $post['snType']=='spu' && $post['snText'])
        {
            $where['p.'.$post['snType']] = array('eq',$post['snText']);
        }
               
        if( isset($post['snType']) && $post['snType']=='id' && $post['snText'])
        {
            $where['g.id'] = array('eq',$post['snText']);
        }
        
        if( isset($post['snType']) && $post['snType']=='name' && $post['snText'])
        {
            $where['g.'.$post['snType']] = array('like','%'.$post['snText'].'%');
        }
        
        if( isset($post['snType']) && $post['snType']=='alias' && $post['snText'])
        {
            $where['g.'.$post['snType']] = array('like','%'.$post['snText'].'%');
        }
        
        if( isset($post['snType']) && $post['snType']=='keywords' && $post['snText'])
        {
            $where['g.'.$post['snType']] = array('like','%'.$post['snText'].'%');
        }
        
        //分类名
        if( isset($post['snType']) && $post['snType']=='cname' && $post['snText'])
        {
            $where['c.name'] = array('like','%'.$post['snText'].'%');
        }
        
        //站点
        if( isset($post['site']) && is_string($post['site']) && $post['site'])
        {
            $where['site_publish_status$.'.$post['site']] = ['eq',0];
        }
        
        return $where;
    }
}
