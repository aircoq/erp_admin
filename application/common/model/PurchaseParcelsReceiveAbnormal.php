<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/15
 * Time: 17:30
 */

namespace app\common\model;

use think\Model;

/**
 * Class PurchaseParcelsReceiveAbnormal
 * @author huangweijie
 * @date 2018/11/15
 * @email wythe.huangw@gmail.com
 * @package app\common\model
 */
class PurchaseParcelsReceiveAbnormal extends Model
{
    /**
     * 处理状态
     * @var array
     */
    const PROCESSING_STATUS = [
        0 => '未处理',
        1 => '已处理',
    ];

    /**
     * 审批状态
     * @var array
     */
    const AUDIT_STATUS = [
        'toBeConfirmedByWarehouse' => 1,
        'confirmedByWarehouse' => 2,
        'toBeConfirmedByPurchase' => 3,
        'confirmedByPurchase' => 4,
        'approvedByPurchaseLeader' => 5,
        'returnByPurchaseLeader' => 6,
        'approvedByPurchaseManager' => 7,
        'returnByPurchaseManager' => 8,
        'pendingUploadImg' => 9,
        'alreadyUploadedImg' => 10,
        'desertedProcess' => 11,
    ];

    /**
     * 包裹丢失流程-退回修改
     * @var array
     */
    const RETURN_MODIFY = [
        self::AUDIT_STATUS['returnByPurchaseLeader'],
        self::AUDIT_STATUS['returnByPurchaseManager'],
    ];

    /**
     * 包裹丢失流程-审批通过
     * @var array
     */
    const AUDIT_PASS_BY_LOST = [
        self::AUDIT_STATUS['approvedByPurchaseLeader'],
        self::AUDIT_STATUS['approvedByPurchaseManager'],
    ];

    /**
     * 包裹丢失流程-组长已经审核
     * @var array
     */
    const AUDITED_BY_LEADER = [
        self::AUDIT_STATUS['approvedByPurchaseLeader'],
        self::AUDIT_STATUS['returnByPurchaseLeader'],
    ];

    /**
     * 包裹丢失流程-经理已经审核
     * @var array
     */
    const AUDITED_BY_MANAGER = [
        self::AUDIT_STATUS['approvedByPurchaseManager'],
        self::AUDIT_STATUS['returnByPurchaseManager'],
    ];

    const ABNORMAL_SOURCE = [
        'purchase_list' => 1,
        'purchase_order' => 2,
        'system_auto' => 3,
        'not_purchase_order' => 4,
        'unpack_abnormal' => 5,
    ];

    /**
     * 获取处理
     *
     * @param int $value
     * @return string
     */
    public function getProcessingStatus(int $value): string
    {
        return self::PROCESSING_STATUS[$value];
    }

    /**
     * 获取异常类型
     *
     * @param int $value
     * @param int $lost
     * @return string
     */
    public function getAbnormalTypeText(int $value, int $lost = 0): string
    {
        $abnormal = ['无', 'PO缺失', '包裹丢失', '退回物流商', '退回供应商', '仓库转移', '其他'];
        $statusText = $abnormal[$value] ?? '';
        if ($value === 2) {
            if ($lost === 2) {
                $statusText = $statusText . '(有差异)';
            } elseif ($lost === 3) {
                $statusText = $statusText . '(无差异)';
            }
        }
        return $statusText;
    }

    /**
     * 获取异常来源
     *
     * @param int $value
     * @return string
     */
    public function getSourceText(int $value):string
    {
        $source = [1 => '包裹查询', '采购单管理', '系统自动', 'PO缺失列表', '拆包异常'];
        return $source[$value] ?? '';
    }

    /**
     * 异常来源
     * @return array
     */
    public function getSource(): array
    {
        $source = [
            ['label' => '包裹查询', 'value' => 1],
            ['label' => '采购单管理', 'value' => 2],
            ['label' => '系统自动', 'value' => 3],
            ['label' => 'PO缺失列表', 'value' => 4],
            ['label' => '拆包退货异常', 'value' => 5]
        ];
        return $source;
    }


}