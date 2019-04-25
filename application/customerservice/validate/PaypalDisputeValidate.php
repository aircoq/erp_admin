<?php

namespace app\customerservice\validate;

use think\Validate;

class PaypalDisputeValidate extends Validate
{
    protected $rule = [
        ['id|列表id', 'require|number|gt:0'],
        ['message|回复内容', 'require|max:2000'],
        ['refund_amount|退款金额', 'number'],
        ['offer_amount|提议退款金额', 'number'],
        ['accept_reason|接受索赔原因', 'number'],
        ['offer_type|提议类别', 'number'],
        ['note|备注', 'max:2000'],
        ['address_id|地址ID', 'number'],
        ['evidence_type|证据类别', 'require|number'],
        ['carrier_name|物流名称', 'max:40'],
        ['tracking_number|物流跟踪号', 'max:40'],
        ['refund_ids|退款交易号', 'max:100'],
    ];


    protected $scene = [
        'send_message' => ['id', 'message'],
        'accept_claim' => ['id', 'accept_reason', 'refund_amount', 'address_id', 'note'],
        'make_offer' => ['id', 'offer_amount', 'address_id', 'offer_type', 'note'],
        'provide_evidence' => ['id', 'address_id', 'evidence_type', 'carrier_name', 'tracking_number', 'refund_ids', 'note'],
        'appeal' => ['id', 'address_id', 'evidence_type', 'carrier_name', 'tracking_number'],
        'acknowledge_return_item' => ['id', 'note'],
    ];
}
