<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-10-12
 * Time: 上午10:21
 */

namespace app\common\model\aliexpress;


use think\Model;
use app\common\model\User;

class AliexpressActionLog extends Model
{
    protected function initialize()
    {
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
        return $this->hasOne(AliexpressProduct::class,'product_id','product_id');
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','create_id');
    }
}