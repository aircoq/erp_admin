<?php
/**
 * Created by PhpStorm.
 * User: donghaibo
 * Date: 2019/4/9
 * Time: 9:51
 */

namespace app\finance\service;


use app\common\model\ebay\EbayAccount;
use app\common\service\Encryption;

class EbayMonthlyBill
{
    public function getDownloadAccount()
    {
        $ebayAccountModel = new EbayAccount();
        $accounts = $ebayAccountModel->alias("e")->join("account a","a.account_name=e.account_name")
                    ->field("a.account_name,a.site_code,a.password,a.account_code,e.id")->where('e.is_invalid',1)->select();

        $postAccount = [];
        foreach ($accounts as $account)
        {
            if(empty($account['account_name']) || empty($account['site_code']) || empty($account['password']) || empty($account['account_code']))
            {
               continue;
            }
            $temp = [];
            $temp['account_id'] = $account['id'];
            $temp['account'] = $account['account_name'];
            $temp['password'] = (new Encryption())->decrypt($account['password']);
            $temp['abbreviation'] = $account['account_code'];
            $temp['site'] = $account['site_code'];
            $postAccount[] = $temp;
        }
        return $postAccount;
    }

    public function getHistoryBill()
    {

    }

    private function formateData($params)
    {

    }

}