<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by phpstrom.
 * User: laiyongfeng
 * Date: 2018/05/21
 * Time: 10:23
 */
class WarehouseGoodsForecast extends  Validate
{
    protected $rule = [
        ['warehouse_type','require|number','仓库类型不能为空！|仓库类型为数字！'],
        ['sku_info','require','sku信息不能为空'],
        ['type','require','仓库系统类型不能为空|仓库类型为数字！'],
        ['local_sku','require','绑定sku信息不能为空'],
        ['third_sku','require','第三方sku不能为空'],
        ['id','require|number','ID不能为空！|ID为数字！'],
    ];

    protected $scene = [
        'add'  => ['warehouse_type', 'sku_info'],
        'category'  => ['warehouse_type'],
        'relate'  => ['third_sku', 'local_sku'],
    ];
}