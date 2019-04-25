<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/6
 * Time: 15:38
 */

namespace app\common\validate;

use think\Validate;

class PurchaseParcels extends Validate
{

    protected $regex = [
        'number' => '/^\d+(\.\d+)?$/',
        'phone' => '/^(([04]\d{1,5}-\d{7,8})|(1[735848]\d{9}))$/',
    ];

    protected $rule = [
        'ids' => 'require',
        'warehouse_id' => 'require|integer',
        'warehouse_name' => 'require|chsAlphaNum',
        'tracking_number' => 'require|alphaDash',
        'parcel_num' => 'require|regex:/^[1-9]\d*$/',
        'shipping_methods' => 'require|chsAlphaNum',
        'abnormal_id' => 'require|integer',
        'abnormal_status' => 'require|in:2,3,4,5,6',
        'recipient' => 'require',
        'certificate_files' => 'require',
        'sent_shipping_methods' => 'require|chsAlphaNum',
        'sent_tracking_number' => 'require|alphaDash',
        'sent_date' => 'require|dateFormat:Y-m-d',
        'weight' => 'require|regex:number',
        'sent_fee' => 'require|regex:number',
        'goods_name' => 'require|chsDash',
        'sender_name' => 'require|chsAlpha',
        'sender_phone' => 'require|regex:phone',
        'recipient_name' => 'require|chsAlpha',
        'recipient_phone' => 'require|regex:phone',
        'recipient_address' => 'require',
        'payment_type' => 'require|in:1,2',
        'other_abnormal_type' => 'require|in:3,4,6'
    ];

    protected $message = [
        'ids.require' => '包裹IDS不能为空',
        'warehouse_id.require' => '仓库ID不能为空',
        'warehouse_id.integer' => '仓库ID必须为数字',
        'warehouse_name.require' => '仓库名称不能为空',
        'warehouse_name.chsAlphaNum' => '仓库名称只能字母和汉字',
        'tracking_number.require' => '运单号不能为空',
        'tracking_number.alphaDash' => '运单号只能是字母和数字，下划线_及破折号-',
        'parcel_num.require' => '包裹数不能为空',
        'parcel_num.regex' => '包裹必须为正整数',
        'shipping_methods.require' => '运输方式不能为空',
        'shipping_methods.chsAlphaNum' => '运输方式只能是汉字、字母和数字',
        'abnormal_id.require' => '异常ID不能为空',
        'abnormal_id.integer' => '异常ID必须为数字',
        'abnormal_status.require' => '异常类型不能为空',
        'abnormal_status.in' => '异常类型错误',
        'recipient.require' => '收信人不能为空',
        'certificate_files.require' => '凭证不能为空',
        'sent_shipping_methods.require' => '寄出方运输方式不能为空',
        'sent_shipping_methods.chsAlphaNum' => '寄出方运输方式格式为汉字、字母和数字',
        'sent_tracking_number.require' => '寄出单号不能为空',
        'sent_tracking_number.alphaDash' => '寄出单号格式错误, 单号格式为字母,数字和_-',
        'sent_date.require' => '日期不能为空',
        'sent_date.dateFormat' => '日期格式错误',
        'weight.require' => '重量不能为空',
        'weight.regex' => '重量格式不正确',
        'sent_fee.require' => '运费不能为空',
        'sent_fee.regex' => '运费格式不正确',
        'goods_name.require' => '物品信息不能为空',
        'goods_name.chsDash' => '物品信息格式错误',
        'sender_name.require' => '寄件人姓名不能为空',
        'sender_name.chsAlpha' => '寄件人姓名格式错误',
        'sender_phone.require' => '寄件人手机不能为空',
        'sender_phone.regex' => '寄件人手机格式错误',
        'recipient_name.require' => '收件人姓名不能为空',
        'recipient_name.chsAlpha' => '收件人姓名格式错误',
        'recipient_phone.require' => '收件人手机不能为空',
        'recipient_phone.regex' => '收件人手机格式错误',
        'recipient_address.require' => '收件人地址不能为空',
        'payment_type.require' => '寄付方式不能为空',
        'payment_type.in' => '寄付方式格式错误'
    ];

    protected $scene = [
        // 创建预接收情景
        'create_receive_parcels' => ['tracking_number', 'parcel_num', 'shipping_methods', 'warehouse_id', 'warehouse_name'],
        // 采购跟进情景
        'replay_receive_abnormal' => ['tracking_number', 'abnormal_id', 'recipient',],
        // 标记收包异常(PO缺失)
        'set_receive_abnormal' => ['tracking_number', 'parcel_num', 'shipping_methods', 'recipient', 'warehouse_id', 'warehouse_name'],
        // 选择其他收包异常类型
        'choose_receive_type' => ['ids', 'abnormal_status'],
        // 收包异常上传凭证
        'upload_certificate' => ['abnormal_id','certificate_files'],
        // 采购填入退回信息
        'purchase_input_return' => ['goods_name', 'sender_name', 'sender_phone', 'recipient_name', 'recipient_phone', 'recipient_address', 'payment_type'],
        // 仓库填入退回信息
        'warehouse_input_return' => ['sent_shipping_methods', 'sent_tracking_number', 'sent_date', 'weight', 'sent_fee'],
        // PO缺失未处理包裹选择其他异常
        'change_other_abnormal'=> ['abnormal_id', 'other_abnormal_type'],
        // 异常处理未拆
        'change_to_unopened' => ['ids'],
    ];
}