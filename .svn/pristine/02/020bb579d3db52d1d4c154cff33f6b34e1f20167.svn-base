<?php
/**
 * Created by PhpStorm.
 * User: panguofu
 * Date: 2018/10/8
 * Time: 下午5:21
 */

namespace app\common\model\wish;

use think\Model;


class WishExpressTemplate extends Model
{

    /**
     * 初始化数据
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }


    //关联用户
    public function user()
    {
        return $this->hasOne(\app\common\model\User::class,'id','creator_id')->field('id,realname');
    }


}