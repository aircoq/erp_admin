<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-13
 * Time: 上午10:35
 */

namespace app\common\model\joom;


use think\Model;

class JoomActionLog extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }
    public function getStatusAttr($v)
    {
        if($v==0)
        {
            return '待提交';
        }elseif($v==1){
            return '提交成功';
        }elseif($v==-1){
            return '提交失败';
        }else{
            return '已提交';
        }
    }

    public function getCreateTimeAttr($v)
    {
        if($v==0)
        {
            return '';
        }else{
            return date('Y-m-d H:i:s',$v);
        }
    }

    public function getCronTimeAttr($v)
    {
        if($v==0)
        {
            return '即时';
        }else{
            return date('Y-m-d H:i:s',$v);
        }
    }

    public function getOldDataAttr($v)
    {
        if(is_json($v))
        {
            return json_decode($v,true);
        }else{
            return $v;
        }
    }

    public function getNewDataAttr($v)
    {
        if(is_json($v))
        {
            return json_decode($v,true);
        }else{
            return $v;
        }
    }

    public function product()
    {
        return $this->hasOne(JoomProduct::class,'product_id','product_id');
    }
}