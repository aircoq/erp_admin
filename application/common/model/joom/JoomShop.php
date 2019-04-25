<?php
namespace app\common\model\joom;

use think\Model;
use think\Db;

class JoomShop extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }


    /** 检查是否存在
     * @param array $data
     * @return bool
     */
    public function check(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
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
            $result = $this->where(['account_name' => $account_name])->where('id','NEQ',$id)->select();
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
