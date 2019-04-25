<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/9/29
 * Time: 9:35
 */

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;

class VirtualOrderApplyLog extends Model
{

    const STATUS = [
        0 => '已作废',
        1 => '待组长审核',
        2 => '待部长审核',
        3 => '待分配',
        4 => '待执行',
        5 => '执行中',
        6 => '已完结'
    ];

    public function getStatusTxtAttr($value, $data)
    {
        return self::STATUS[$data['status']];
    }
    public function getCreatorAttr($value){
        $user = Cache::store('user')->getOneUser($value);
        if ($user) {
            return $user['realname'];
        }
        return '';
    }
}