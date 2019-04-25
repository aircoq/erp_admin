<?php
namespace app\common\model\aliexpress;

use think\Model;

class AliexpressPublishPlan extends Model
{
    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }
    public function getApIdAttr($v)
    {
    	return (string)$v;
    }
    public function product()
    {
    	return $this->hasOne(AliexpressProduct::class,'id','ap_id',[],'LEFT');
    }
}
