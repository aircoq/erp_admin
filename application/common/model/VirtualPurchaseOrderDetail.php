<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class VirtualPurchaseOrderDetail extends Model
{
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

    

    /** 获取供应商支付方式
     * @return array
     */
    public static function getPayment()
    {
        $payment = [
            0 => [
                'label' => '',
                'name' => '请选择支付方式'
            ],
            1 => [
                'label' => 1,
                'name' => '现金'
            ],
            2 => [
                'label' => 2,
                'name' => '银行转账'
            ],
            3 => [
                'label' => 3,
                'name' => 'PayPal'
            ],
            4 => [
                'label' => 4,
                'name' => '支付宝'
            ]
        ];
        return $payment;
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