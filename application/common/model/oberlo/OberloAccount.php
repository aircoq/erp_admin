<?php

namespace app\common\model\oberlo;

use think\Model;

class OberloAccount extends Model
{
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /** 检查代码或者用户名是否有存在了
     * @param $id
     * @param $code
     * @param $account_name
     * @return bool
     */
    public function isHas($id,$code,$account_name)
    {
        if(!empty($account_name)){
            $result = $this->where(['name' => $account_name])->where('id','NEQ',$id)->select();
            if(!empty($result)){
                return true;
            }
        }
        $result = $this->where(['code' => $code])->where('id','NEQ',$id)->select();
        if(!empty($result)){
            return true;
        }
        return false;
    }
}