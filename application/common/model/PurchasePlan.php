<?php

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use app\common\traits\ModelFilter;
use think\db\Query;
use erp\ErpModel;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class PurchasePlan extends ErpModel
{
    public const PLAN_TYPE_TEXT = [
        1 => '正常采购',
        2 => '供应商多送',
        3 => '样品',
        4 => '备货计划'
    ];

    const NOT_HAVE = 0;
    const SEVENTEEN_PERCENT_VAT_SPECIAL_INVOICE = 1;
    const SEVENTEEN_PERCENT_VAT_PLAIN_INVOICE = 5;
    const THREE_PERCENT_VAT_PLAIN_INVOICE = 2;
    const THREE_PERCENT_PLAIN_INVOICE = 3;
    const THIRTEEN_PERCENT_VAT_PLAIN_INVOICE = 6;
    const THIRTEEN_PERCENT_VAT_SPECIAL_INVOICE = 9;
    const NO_INVOICE = 8;
    const TAX_FREE = 4;
    const OTHER = 7;

    const YES = 1;
    const NO  = 2;

    const INVOICE = [
        self::NOT_HAVE  => '无',
        self::SEVENTEEN_PERCENT_VAT_SPECIAL_INVOICE  => '17%增值税专用发票',
        self::SEVENTEEN_PERCENT_VAT_PLAIN_INVOICE  => '17%的增值税普通发票',
        self::THREE_PERCENT_VAT_PLAIN_INVOICE  => '3%增值税普通发票',
        self::THREE_PERCENT_PLAIN_INVOICE  => '3%普通发票',
        self::THIRTEEN_PERCENT_VAT_PLAIN_INVOICE  => '13%的增值税普通发票',
        self::THIRTEEN_PERCENT_VAT_SPECIAL_INVOICE  => '13%的增值税专用发票',
        self::NO_INVOICE  => '不能开票',
        self::TAX_FREE  => '无税',
        self::OTHER  => '其他',
    ];

    const SUPPLY_CHAIN_FINANCE = [
        self::NOT_HAVE => '无',
        self::YES => '是',
        self::NO => '否',
    ];

    use ModelFilter;

    /**
     * 格式为二维数组给前端
     * @return array
     */
    public static function getInvoiceFormat()
    {
        $formatData = [];
        foreach (self::INVOICE as $k => $v) {
            $formatData[] = [
                'label' => $k,
                'name'  => $v,
            ];
        }
        return $formatData;
    }

    /**
     * 格式为二维数组给前端
     * @return array
     */
    public static function getSupplyChainFinanceFormat()
    {
        $formatData = [];
        foreach (self::SUPPLY_CHAIN_FINANCE as $k => $v) {
            $formatData[] = [
                'label' => $k,
                'name'  => $v,
            ];
        }
        return $formatData;
    }

    /**
     * 发票类型文本
     * @param $label int
     * @return string
     */
    public function getInvoiceText($field)
    {
        $result = self::INVOICE;
        return isset($result[$field]) ? $result[$field] : '';
    }

    /**
     * 获得值
     * @param $label
     * @return string
     */
    public function getSupplyChainFinanceText($field)
    {
        $result = self::SUPPLY_CHAIN_FINANCE;
        return isset($result[$field]) ? $result[$field] : '';
    }

    public function scopePurchase(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.purchase_id', 'in', $params);
        }
    }

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
    public function isHas($id, $company_name)
    {
        if (!empty($company_name)) {
            $result = $this->where(['company_name' => $company_name])->where('id', 'NEQ', $id)->select();
            if (!empty($result)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 关联信息
     */
    public function detail()
    {
        return parent::hasMany('PurchasePlanDetail', 'purchase_plan_id', 'id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'id', 'purchase_plan_id');
    }

    public function getSupplyChainFinanceTextAttr($value, $data)
    {
        return $this->getSupplyChainFinanceText($data['supply_chain_finance']);
    }

    public function getInvoiceTextAttr($value, $data)
    {
        return $this->getInvoiceText($data['invoice']);
    }

    public function getTaxRateTextAttr($value, $data)
    {
        return $data['tax_rate']*100 . '%';
    }
}