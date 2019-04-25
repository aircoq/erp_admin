<?php

/**
 * Description of WishActionLog
 * @datetime 2017-4-20  15:22:27
 * @author joy
 */

namespace app\common\model\wish;
use app\common\model\User;
use think\Model;
class WishActionLog extends Model{
   /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }
    
    /**
     * 获取是否已经存在
     * @param type $where
     * @return array
     */
    public  function existsOne($where)
    {
        return $this->get($where)->toArray();
    }
    
    /**
     * 插入一条数据
     * @param type $data
     * @return integer|false
     */
    public function insertOne($data)
    {
        if($insertId=$this->save($data))
        {
            return $insertId;
        }else{
            return $this->getError();
        }
        
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

    public function user()
    {
        return $this->hasOne(User::class,'id','create_id');
    }

    public function product()
    {
        return $this->hasOne(WishWaitUploadProduct::class,'product_id','product_id');
    }
}
