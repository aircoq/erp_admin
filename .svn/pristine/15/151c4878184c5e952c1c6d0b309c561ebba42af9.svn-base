<?php
namespace app\common\model;

use erp\ErpModel;
use think\Model;
use think\Db;
use think\db\Query;
use app\common\traits\ModelFilter;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/4
 * Time: 17:10
 */
 
class GoodsSkuMap extends ErpModel
{
    use ModelFilter;

    public function scopeGoodsSkuMaps(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.creator_id', 'in', $params);
        }
    }
    /**
     * 初始化数据
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }
    /** 检测产品是否存在
     * @param int $id
     * @return bool
     */
    public function isHas($id = 0)
    {
        $result = $this->where(['id' => $id])->find();
        if(empty($result)){   //不存在
            return false;
        }
        return true;
    }

    /** 判断是否有重复
     * @param $account_id
     * @param $channel_id
     * @param $channel_sku
     * @param $id
     * @return bool
     */
    public function isRepeat($account_id,$channel_id,$channel_sku,$id)
    {
        $where['account_id'] = ['=',$account_id];
        $where['channel_id'] = ['=',$channel_id];
        $where['channel_sku'] = ['=',$channel_sku];
        $where['id'] = ['<>',$id];
        $result = $this->where($where)->find();
        if(empty($result)){
            return false;
        }
        return true;
    }
    
    
    /**
     * 查询是否存在
     * @param type $where
     */
    public  function isExists($where)
    {
        $result = $this->where($where)->find();
        if(empty($result)){
            return false;
        }
        return true;
    }
    /**
     * 关联goods_sku表
     * @return type
     */
    public  function sku()
    {
        return $this->hasOne(GoodsSku::class, 'id', 'sku_id')->field('id,status');
    }
    /**
     * 获取平台sku的状态
     * @param type $sku
     * @param type $account_id
     * @param type $channel_id
     * @return type
     */
    public  function getSkuSellStatus($sku,$account_id,$channel_id=3)
    {
        //$model = new \app\common\model\GoodsSkuMap;
        
        $data = $this->field('sku_id')->with(['sku'])->where(['channel_sku'=>$sku,'channel_id'=>$channel_id,'account_id'=>$account_id])->find();
        if($data)
        {
            $data = $data->toArray();
        }
        return $data;
        
    }
}