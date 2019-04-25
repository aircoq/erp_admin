<?php
/**
 * Created by PhpStorm.
 * User: hjt
 * Date: 2019/1/22
 * Time: 18:10
 */

namespace app\common\validate;


use think\Validate;

class SupplierDiscussRecord extends Validate
{
    protected $rule = [
        ['supplier_id','require','供应商ID不能为空'],
        ['discuss_time','require','洽谈时间不能为空'],
        ['discuss_way','require','洽谈方式不能为空'],
        ['make_project','require','谈成项目不能为空'],
        ['expense_reimbursement_voucher','require','报销凭证不能为空'],
        ['scene_picture','require','现场图片不能为空'],
        ['content','require','洽谈详情不能为空'],
    ];
    protected $scene = [
        'add'   =>  ['supplier_id','discuss_time','discuss_way','make_project','expense_reimbursement_voucher','scene_picture','content'],
    ];
}