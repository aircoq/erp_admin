<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/8/14
 * Time: 16:03
 */

namespace app\index\task;


use app\common\service\ebay\EbayRestful;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\common\model\ebay\EbayAccount;
use think\Exception;

class EbayRefreshToken extends AbsTasker
{
    public function getName()
    {
        return "Ebay刷新Token";
    }

    public function getDesc()
    {
        return "Ebay使用账号refresh token 刷新OAuth Token";
    }

    public function getCreator()
    {
        return "wlw2533";
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        try {
            $map['is_invalid'] = 1;
            $map['account_status'] = 1;
            $map['ort_invalid_time'] = ['gt', time()];
            $accountIds = (new EbayAccount())->where($map)->column('id');
            foreach ($accountIds as $accountId) {
                (new UniqueQueuer(\app\index\queue\EbayRefreshToken::class))->push($accountId);
            }
        } catch (\Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

}