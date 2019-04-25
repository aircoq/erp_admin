<?php
namespace app\common\interfaces;
/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/6/8
 * Time: 10:33
 */
interface MemberPublish
{
    /** 过滤spu已选择过的账号信息
     * @param $account_id
     * @param $spu
     * @return mixed
     */
    public function filterSeller($account_id,$spu);
}