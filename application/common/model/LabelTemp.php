<?php
/**
 * Created by PhpStorm.
 * User: TOM
 * Date: 2017/7/17
 * Time: 17:46
 */

namespace app\common\model;


use think\Model;

class LabelTemp extends Model
{
    protected $autoWriteTimestamp = true;
    //标签模板类型
    const TEMP_TYPE = [
        1=>'产品标签',
        2=>'箱唛面单',
        3=>'引导',
        4=>'库位',
        5=>'周转箱',
        6=>'多品拣货单',
        7=>'卡板',
        8=>'商品条码'
    ];
    const TYPE_FIELDS = [
        1=>[
            ['field' => 'spu', 'name' => '产品SPU', 'type' => 1],
            ['field' => 'sku', 'name' => '产品SKU', 'type' => 1],
            ['field' => 'sku_alias', 'name' => 'SKU别名', 'type' => 1],
            ['field' => 'category', 'name' => '分类', 'type' => 1],
            ['field' => 'brand', 'name'=>'品牌', 'type' => 1],
            ['field' => 'logistics', 'name' => '物流属性', 'type' => 1],
            ['field' => 'name_cn', 'name' => '中文名称', 'type' => 1],
            ['field' => 'goods_size', 'name' => '产品尺寸', 'type' => 1],
            ['field' => 'sku_weight', 'name' => '产品重量', 'type' => 1],
            ['field' => 'sku_id', 'name' => '产品skuID', 'type' => 1],
            ['field' => 'color', 'name' => '产品颜色', 'type' => 1],
            ['field' => 'warehouse_cargo_code', 'name' => '产品货位号', 'type' => 1],
            ['field' => 'warehouse', 'name' => '所在仓库', 'type' => 1],
            ['field' => 'tracking_number', 'name' => '运单号', 'type' => 1],
            ['field' => 'purchase_order_code', 'name' => '采购单号', 'type' => 1],
            //['field' => 'num', 'name' => '入库批次数量', 'type' => 1],
            ['field' => 'batch', 'name' => '批次', 'type' => 1],
            ['field' => 'handler', 'name' => '操作人', 'type' => 1],
            ['field' => 'date', 'name' => '操作时间', 'type' => 1],
            ['field' => '_sn', 'name' => '序号', 'type' => 1],
            ['field' => 'job_number', 'name' => '工号', 'type' => 1],
        ],
        2=>[
            ['field' => 'spu', 'name' => '产品SPU', 'type' => 1],
            ['field' => 'sku', 'name' => '产品SKU', 'type' => 1],
            ['field' => 'sku_alias', 'name' => 'SKU别名', 'type' => 1],
            ['field' => 'category', 'name' => '分类', 'type' => 1],
            ['field' => 'brand', 'name'=>'品牌', 'type' => 1],
            ['field' => 'logistics', 'name' => '物流属性', 'type' => 1],
            ['field' => 'name_cn', 'name' => '中文名称', 'type' => 1],
            ['field' => 'goods_size', 'name' => '产品尺寸', 'type' => 1],
            ['field' => 'sku_weight', 'name' => '产品重量', 'type' => 1],
            ['field' => 'sku_id', 'name' => '产品skuID', 'type' => 1],
            ['field' => 'color', 'name' => '产品颜色', 'type' => 1],
            ['field' => 'warehouse_cargo_code', 'name' => '产品货位号', 'type' => 1],
            ['field' => 'warehouse', 'name' => '所在仓库', 'type' => 1],
            ['field' => 'tracking_number', 'name' => '运单号', 'type' => 1],
            ['field' => 'purchase_order_code', 'name' => '采购单号', 'type' => 1],
            ['field' => 'num', 'name' => '入库批次数量', 'type' => 1],
            ['field' => 'batch', 'name' => '批次', 'type' => 1],
            ['field' => 'handler', 'name' => '操作人', 'type' => 1],
            ['field' => 'date', 'name' => '操作时间', 'type' => 1],

            ['field' => 'supplier', 'name' => '供应商', 'type' => 1],
            ['field' => 'total', 'name' => '装箱数量', 'type' => 1],
            ['field' => 'gross_weight', 'name' => '装箱毛重', 'type' => 1],
            //['field' => 'net_weight', 'name' => '装箱净重', 'type' => 1],
            //['field' => 'box_size', 'name' => '外箱尺寸', 'type' => 1],
        ],
        3=>[
            ['field' => 'warehouse_area_type', 'name' => '区域', 'type' => 1],
            ['field' => 'purchase_order_code', 'name' => '采购单', 'type' => 1],
            ['field' => 'batch_id', 'name' => '批次', 'type' => 1],
            ['field' => 'sku', 'name' => 'SKU', 'type' => 1],
            ['field' => 'quantity', 'name'=>'数量', 'type' => 1],
            ['field' => 'handler', 'name' => '操作人', 'type' => 1],
            ['field' => 'handler_id', 'name' => '操作人ID', 'type' => 1],
            ['field' => 'shipping_mark', 'name' => '箱唛', 'type' => 1],
            ['field' => 'warehouse_cargo_code', 'name' => '产品货位号', 'type' => 1],
            ['field' => 'purchase_parcels_records_id', 'name' => '明细ID', 'type' => 1],
            ['field' => 'job_number', 'name' => '工号', 'type' => 1],
            ['field' => 'picking_number', 'name' => '拣货单号', 'type' => 1],
            ['field' => 'shipping_name', 'name' => '物流渠道', 'type' => 1],
            ['field' => 'width', 'name' => '宽', 'type' => 1],
            ['field' => 'height', 'name' => '高', 'type' => 1],
        ],
        4=>[
            ['field' => 'warehouse_area_name', 'name' => '分区', 'type' => 1],
            ['field' => 'warehouse_cargo_code', 'name' => '库位', 'type' => 1],
            ['field' => 'sku', 'name' => 'SKU', 'type' => 1]
        ],
        5=>[
            ['field' => 'turnover_box_code', 'name' => '周转箱号', 'type' => 1],
            ['field' => 'turnover_box_type', 'name' => '类型(单品、多品)', 'type' => 1],
        ],
        6=>[
            ['field' => 'picking_number', 'name' => '拣货单号', 'type' => 1],
            ['field' => 'basket', 'name' => '周转篮', 'type' => 1],
            ['field' => 'package_number', 'name' => '包裹号', 'type' => 1],
            ['field' => 'height', 'name' => '长', 'type' => 1],
            ['field' => 'width', 'name' => '宽', 'type' => 1],
            ['field' => 'star', 'name' => '标记', 'type' => 1],
        ],
        7=>[
            ['field' => 'parcel_box_code', 'name' => '卡板号', 'type' => 1],
            ['field' => 'parcel_qty', 'name' => '包裹数量', 'type' => 1],
            ['field' => 'create_date', 'name' => '创建时间', 'type' => 1],
        ],
        8=>[
            ['field' => 'fn_sku', 'name' => 'FNSKU', 'type' => 1],
            ['field' => 'product_name', 'name' => '商品名称', 'type' => 1],
        ],
    ];

    /**
     * 获取器
     */
    public function getSizeAttr($value)
    {
        $value = json_decode($value,true);
        return $value;
    }
    public function getTempDataAttr($value)
    {
        $value = json_decode($value,true);
        return $value;
    }

    /**
     * 获取标签类型显示名称
     * @param int $type
     * @return mixed|string
     */
    public static function getTempTypeName(int $type)
    {
        $tempTypes = self::TEMP_TYPE;
        return isset($tempTypes[$type])?$tempTypes[$type]:'';
    }
    
    public function getSubTempAttr($value)
    {
        $value = json_decode($value,true);
        return $value;
    }
}
