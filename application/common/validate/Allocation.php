<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by tanbin.
 * Date: 2017/06/21
 * Time: 17:03
 */
class Allocation extends  Validate
{
    protected $rule = [
        ['out_warehouse_id', 'require|number','出库仓库不能为空！|出库仓库ID为整形！'],
        ['in_warehouse_id', 'require|number','入库仓库不能为空！|入库仓库ID为整形！'],
        ['id', 'require|number','调拨ID不能为空！|调拨ID为整形！'],
        ['type', 'require|number','附件类型不能为空！|附件类型为整形！'],
        ['attachment', 'require','附加不能为空'],
        ['sku_id', 'require','sku_id调拨ID不能为空'],
        ['fn_sku', 'require','FNSKU不能为空'],
        ['remark', 'require','备注原因不能为空'],
        ['shipping_method', 'require','物流渠道不能为空'],
    ];
    protected $scene = [
        'upload_attachment'  => ['type', 'attachment'],
        'save'  => ['out_warehouse_id', 'in_warehouse_id'],
        'verify_fnsku'  => ['in_warehouse_id', 'sku_id', 'fn_sku'],
        'cancel'  => ['remark'],
        'upload_logistics'  => ['shipping_method']
    ];
}

