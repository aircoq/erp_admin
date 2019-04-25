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
class GoodsGallery extends Model
{

    const IS_DEFAULT = [
        1=>'主图',
        2=>'细节图',
        4=>'关联图片',
        8=>'营销图'
    ];
    /**
     * 初始化数据
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public static function getPicByGoodsAttr($goodsId,$attrId,$attrValId)
    {
        $where['goods_id']      = $goodsId;
        $where['attribute_id']  = $attrId;
        $where['value_id']      = $attrValId;
        $info = self::where($where)->field('path')->find();
        return $info['path'];
    }

    public function getChannelAttr($value,$data){
        $result = Cache::store('channel')->getChannel();
        $new_list = [];
        foreach($result as $k => $v){
            $new_list[$v['id']] = $v['name'];
        }
        return isset($new_list[$data['channel_id']])?$new_list[$data['channel_id']]:'';


    }
    public function getIsDefaultTxtAttr($value,$data){
        return self::IS_DEFAULT[$data['is_default']];
    }

    public function getSkuAttr($value,$data){
        if($data['sku_id']>0){
            return Cache::store('goods')->getSkuInfo($data['sku_id'])['sku'];
        }
        return '';

    }
}