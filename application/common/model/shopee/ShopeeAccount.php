<?php

namespace app\common\model\shopee;

use erp\ErpModel;

class ShopeeAccount extends ErpModel
{

    const STATUS_TXT = [
        0 => '启用',
        1 => '不启用'
    ];

    public function getStatusTxtAttr($value, $data)
    {
        return isset(self::STATUS_TXT[$data['status']]) ? self::STATUS_TXT[$data['status']] : '';
    }


    public function getDownloadOrderTxtAttr($value, $data)
    {
        if ($data['download_order'] == 0) {
            return '未启用';
        } else {
            return '下单间隔时间' . $data['download_order'] . "秒";
        }
    }

    public function getSyncDeliveryTxtAttr($value, $data)
    {
        if ($data['sync_delivery'] == 0) {
            return '未启用';
        } else {
            return '同步间隔时间' . $data['sync_delivery'] . "秒";
        }
    }

    public function getPlatformStatusTxtAttr($value, $data)
    {
        if ($data['platform_status'] == 1) {
            return '启用';
        } else {
            return '未启用';
        }
    }

    public function getSiteIdAttr($value, $data)
    {
        if ($data['site']) {
            $model = new ShopeeSite();
            $o = $model->where('code', $data['site'])->find();
            if ($o) {
                return $o->id;
            }
        }
        return 0;
    }

    public function getIsTHTxtAttr($value, $data)
    {
        if($data['is_TH']==1){
            return '是';
        }else{
            return '否';
        }
    }
    public function getIsThirdTxtAttr($value,$data)
    {
        if ($data['third_party_delivery'] == 1) {
            return '泰国仓发货';
        }else {
            return '非第三方仓库发货';
        }
    }
}