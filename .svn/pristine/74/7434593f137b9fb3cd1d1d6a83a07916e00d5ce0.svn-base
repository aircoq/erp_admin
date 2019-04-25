<?php
namespace app\common\model;

use think\Model;

/** 采购订单
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class PurchaseOrderLog extends Model
{
    //protected $autoWriteTimestamp = true;
    /**
     * 初始化
     */
    protected function initialize()
    {
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
     * @param $company_name
     * @return bool
     */
    public function isHas($id,$company_name)
    {
        if(!empty($company_name)){
            $result = $this->where(['company_name' => $company_name])->where('id','NEQ',$id)->select();
            if(!empty($result)){
                return true;
            }
        }
        return false;
    }

}