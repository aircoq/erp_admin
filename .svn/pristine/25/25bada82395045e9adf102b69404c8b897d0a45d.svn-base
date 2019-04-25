<?php

/**
 * Description of WishSize
 * @datetime 2017-4-24  14:52:55
 * @author joy
 */

namespace app\common\model\wish;
use think\Model;
class WishShippingCharge extends Model{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function isHas($where)
    {
        return $this->where($where)->find();
    }

    public function add($data)
    {
        $saveData['updated_time'] = time();
        $where['weight'] = $data['weight'];
        $old = $this->isHas($where);
        if($old){
            $saveData['general_surface_fee'] = $data['general_surface_fee'];
            $saveData['general_registration_fee'] = $data['general_registration_fee'];
            $saveData['special_surface_fee'] = $data['special_surface_fee'];
            $saveData['special_registration_fee'] = $data['special_registration_fee'];
            $this->save($saveData,['id'=>$old['id']]);
        }else{
            $data['create_time'] =  $data['updated_time'] = $saveData['updated_time'];
            $this->insert($data);
        }
    }
    
}
