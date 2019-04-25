<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/5/24
 * Time: 16:17
 */

namespace app\common\model;

use think\Model;

class GoodsPublishMap extends Model
{

    private $filterAccount = [];
    /**
     * listing过滤
     * @param Query $query
     * @param $params
     */
    public function scopeListing(Query $query, $params)
    {
        $this->filterAccount = array_merge($params, $this->filterAccount);
        if(!empty($params))
        {
            $query->where('__TABLE__.account_id','in', $this->filterAccount);
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

    public function goods()
    {
        return $this->belongsTo(Goods::class,'goods_id','id');
    }
    

}