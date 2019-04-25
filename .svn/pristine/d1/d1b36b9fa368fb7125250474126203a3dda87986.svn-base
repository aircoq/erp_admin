<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class FinancePurchase extends Model
{
    protected $autoWriteTimestamp = true;
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

    /** 获取供应商等级信息
     * @return array
     */
    public static function getLevel()
    {
        $level = [
            0 => [
                'label' => '',
                'name' => '请选择供应商等级'
            ],
            1 => [
                'label' => 1,
                'name' => '一等供应商'
            ],
            2 => [
                'label' => 2,
                'name' => '二等供应商'
            ],
            3 => [
                'label' => 3,
                'name' => '三等供应商'
            ],
        ];
        return $level;
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

    public function purchaseOrder()
    {
        return parent::belongsTo('PurchaseOrder', 'purchase_order_id');
    }

}