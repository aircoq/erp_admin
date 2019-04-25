<?php

/**
 * Description of AliexpressApiException
 * @datetime 2017-6-12  13:53:39
 * @author joy
 */

namespace app\common\model\aliexpress;
use think\Model;
class AliexpressApiException extends Model{
    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }
    
    public  function add($data)
    {
        $where['code'] = ['eq',$data['code']];
        if($this->check($where))
        {
            return $this->save($data, $where);
        }else{
            return $this->save($data);
        }
    }
    public  function edit($data,$where)
    {
        return $this->where($where)->update($data);
    }
    public  function check($where)
    {
        return $this->get($where);
    }       
}
