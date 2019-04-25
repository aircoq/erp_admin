<?php
namespace app\common\model;

use think\Model;
use app\common\model\User as UserModel;

class PurchaseParcelsBoxLog extends Model
{
    /**
     * 获取器转换时间格式
     * 
     * @param string $value 字段值创建时间戳
     * 
     * @return string
     */
    public function getCreateTimeAttr($value)
    {
        if (empty($value)) {
            return '';
        }
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 获取器转换用户名字
     * 
     * @param string $value 字段值创建人ID
     * 
     * @return string
     */
    public function getCreateIdAttr($value)
    {
        if (empty($value)) {
            return '';
        }
        return UserModel::get($value)->realname;
    }
}