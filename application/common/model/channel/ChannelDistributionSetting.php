<?php

namespace app\common\model\channel;

use think\Model;

class ChannelDistributionSetting extends Model
{
    const STATUS_ON_SALE = 1; //在售
    const STATUS_STOP_SALE = 2; //停售
    const STATUS_WAIT_PUBLISH = 3; //待发布
    const STATUS_SALE_DOWN = 4; //卖完下架
    const STATUS_NO_GOODS = 5; //缺货
    const STATUS_PART_SALE = 6; //部分在售
    const STATUS_NO_TORT = 7; //无侵权
    const STATUS_ON_PUBLISH = 8; //可选上架
    const STATUS_TXT = [
        self::STATUS_ON_SALE => '在售',
        self::STATUS_STOP_SALE => '停售',
        self::STATUS_WAIT_PUBLISH => '待发布',
        self::STATUS_SALE_DOWN => '卖完下架',
        self::STATUS_NO_GOODS => '缺货',
        self::STATUS_PART_SALE => '部分在售',
        self::STATUS_NO_TORT => '无侵权',
        self::STATUS_ON_PUBLISH => '可选上架',
    ];

    public function setItemAttr($value)
    {
        return implode(',', $value);
    }

    public function setProductStatusAttr($value)
    {
        return implode(',', $value);
    }

    public function setBanCategoryAttr($value)
    {
        return implode(',', $value);
    }

    public function setBanSiteAttr($value)
    {
        return implode(',', $value);
    }

    public function setBanAccountIdAttr($value)
    {
        return json_encode($value);
    }

    public function setCategoryAccountAttr($value)
    {
        return json_encode($value);
    }

    public function setCategoryDepartmentAttr($value)
    {
        return json_encode($value);
    }

    public function setPublishValueAttr($value)
    {
        return implode(',', $value);
    }

    public function setAllowPositionAttr($value)
    {
        return implode(',', $value);
    }

    public function getItemAttr($value)
    {
        if (empty($value)) {
            return [];
        }
        $result = explode(',', $value);
        return array_map('intval',$result);
    }

    public function getProductStatusAttr($value)
    {
        if (empty($value)) {
            return [];
        }
        $result = explode(',', $value);
        return array_map('intval',$result);
    }

    public function getBanCategoryAttr($value)
    {
        if (empty($value)) {
            return [];
        }
        $result = explode(',', $value);
        return array_map('intval',$result);
    }

    public function getBanSiteAttr($value)
    {
        if (empty($value)) {
            return [];
        }
        return explode(',', $value);
    }

    public function getAllowPositionAttr($value)
    {
        if (empty($value)) {
            return [];
        }
        $result = explode(',', $value);
        return array_map('intval',$result);
    }

    public function getBanAccountIdAttr($value)
    {
        $result = json_decode($value, true);
        return array_map('intval',$result);
    }

    public function getCategoryDepartmentAttr($value)
    {
        $result = json_decode($value, true);
        return $result;
    }

    public function getCategoryAccountAttr($value)
    {
        $result = json_decode($value, true);
        return $result;
    }

    public function getPublishValueMinAttr($value, $data)
    {
        if (empty($data['publish_value'])) {
            return 0;
        }
        return floatval(explode(',', $data['publish_value'])[0]);
    }

    public function getPublishValueEstAttr($value, $data)
    {
        if (empty($data['publish_value'])) {
            return 0;
        }

        return floatval(explode(',', $data['publish_value'])[1]);
    }

}