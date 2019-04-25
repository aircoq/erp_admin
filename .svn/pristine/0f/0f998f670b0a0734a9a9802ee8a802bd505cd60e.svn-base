<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class SupplierGoodsOffer extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 检测产品是否存在
     * @param int $id
     * @return bool
     */
    public function isHas($id = 0)
    {
        $result = $this->where(['id' => $id])->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return true;
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

    /** 创建时间获取器
     * @param $value
     * @return int
     */
    public function getCreateTimeAttr($value)
    {
        if(is_numeric($value)){
            return $value;
        }else{
            return strtotime($value);
        }
    }

    /** 更新时间获取器
     * @param $value
     * @return int
     */
    public function getUpdateTimeAttr($value)
    {
        if(is_numeric($value)){
            return $value;
        }else{
            return strtotime($value);
        }
    }

    /** 获取状态信息
     * @param $status
     * @return string
     */
    public static function getStatus($status)
    {
        $result = [
            0 => '未审核',
            1 => '审核通过',
            2 => '审核不通过'
        ];
        if (isset($result[$status])) {
            return $result[$status];
        }
        return '';
    }

    /** 获取
     * @return mixed
     */
    public function section()
    {
        return $this->hasMany(SupplierGoodsOfferSection::class, 'supplier_goods_offer_id', 'id',['supplier_goods_offer' => 'a', 'supplier_goods_offer_section' => 'b'],
            'left')->field('supplier_goods_offer_id,id AS section_id,min_quantity,max_quantity,price')->order('price asc');
    }
}