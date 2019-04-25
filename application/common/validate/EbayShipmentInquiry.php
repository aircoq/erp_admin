<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by tanbin.
 * User: tb
 * Date: 2017/04/26
 * Time: 9:57
 */
class EbayShipmentInquiry extends  Validate
{
    protected $rule = [
        ['shipment_date','require|date','发货时间不能为空|发货时间格式不对'],
        ['tracking_num','require','跟踪单号不能为空'],
        ['message','require','留言不能为空'],
        ['carrier_name','require','承运人不能为空'],
    ];
}