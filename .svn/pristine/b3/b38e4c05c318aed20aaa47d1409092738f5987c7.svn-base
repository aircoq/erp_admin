<?php
namespace app\common\model\ebay;

use think\Model;

class EbayAccount extends Model
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
    
	/**
     * 获取token，如果只有一个token，则直接返回
     * @param type $value
     * @return type
     */
    public  function getTokenAttr($value)
    {
        $token_arr = json_decode($value,true);
        if(is_array($token_arr) && count($token_arr) >1)
        {
            return $value;
        }else{
            return $token_arr[0];
        }
    }

    /**
     * 新增
     * @param array $data [description]
     */
    public function add(array $data)
    {
        if (isset($data['id'])) {
            //检查产品是否已存在
            if ($this->check(['id' => $data['id']])) {
                return $this->edit($data, ['id' => $data['id']]);
            }
        }
        return $this->insert($data);
    }

    /**
     * 批量新增
     * @param array $data [description]
     */
    public function addAll(array $data)
    {
        foreach ($data as $key => $value) {
            $this->add($value);
        }
    }

    /**
     * 修改
     * @param  array $data [description]
     * @return [type]       [description]
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }

    /**
     * 批量修改
     * @param  array $data [description]
     * @return [type]       [description]
     */
    public function editAll(array $data)
    {
        return $this->save($data);
    }

    /**
     * 检查是否存在
     * @return [type] [description]
     */
    protected function check(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }
    
    
    public function is_min_paypal_id($id)
    {   
        $result = $this->where('min_paypal_id','EQ',$id)->select();        
        if(!empty($result)){
            return true;
        }       
        return false;
    }
    
    public function is_max_paypal_id($id)
    {
        $result = $this->where('max_paypal_id','EQ',$id)->select();
        if(!empty($result)){
            return true;
        }
        return false;
    }

    #listing更新时间获取一个账号
    public function getAccountOne(){
        $rows=$this->where(array("is_invalid"=>1))->order("start")->find();
        $this->edit(array('start'=>$rows->start+1),array('id'=>$rows['id']));
        return $rows;
    }

    #listing更新时间获取一个账号
    public function getAccountUpcus(){
        $rows=$this->order("upac_date")->find();
        $this->edit(array('upac_date'=>time()),array('id'=>$rows['id']));
        return $rows;
    }
}
