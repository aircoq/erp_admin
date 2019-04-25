<?php

namespace app\common\model;

use app\common\service\Common;
use app\index\service\AccountApplyService;
use think\Cache;
use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/2/19
 * Time: 11:47
 */
class ReturnWaitShelvesDetail extends Model
{
    //取消原因 1.销售取消单，未包装  2.销售取消单，已包装  3.物流渠道不支持  4.仓库少货等原因取消,10-包裹拦截 11-客户退货 12-安检不过 13-超时退回 14-原单退件（收费）
    const cancel_reason_unpacked = 1;//
    const cancel_reason_packaging = 2;//
    const cancel_reason_channel_not_supported = 3;//
    const cancel_reason_warehouse_problem = 4;//
    //类型 1-重返上架 2-退回上架
    const TYPE_PICK_RETURN = 1;
    const TYPE_SALE_BACK = 2;
    //退回上架的原因取包裹 退回类型( 0-包裹拦截 1-客户退货 2-安检不过 3-超时退回 4-原单退件（收费）) 转化到这张表需要+10，即下面的值
    const TYPE_SALE_BACK_STEP = 10;

    const typeTexts = [
        1 => '重返待上架',
        2 => '退回待上架',
    ];

    const allCancelReason = [
        '',
        '销售取消单，未包装',
        '销售取消单，已包装',
        '物流渠道不支持',
        '仓库少货等原因取消',
        10 => '包裹拦截',
        '客户退货',
        '安检不过',
        '超时退回',
        '原单退件（收费）',
    ];

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }



    public function isHas($packageNumber)
    {
        $where['package_number'] = $packageNumber;
        return $this->where($where)->find();
    }

}