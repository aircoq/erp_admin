<?php
namespace app\common\model\customerservice;

use erp\ErpModel;
use think\Model;

/**
 *  邮件账号
 * Class EmailContent
 * @package app\common\model
 */
class EmailAccounts extends ErpModel
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }


    public function log()
    {
        return $this->hasMany('EmailAccountsLog','email_account_id')->order('time', 'DESC');
    }

    public function amazonAccount()
    {
        return $this->hasOne('app\common\model\amazon\AmazonAccount','id','account_id');
    }

}