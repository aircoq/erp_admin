<?php
namespace app\common\model\customerservice;

use erp\ErpModel;
use think\Model;

/**
 *  邮件账号
 * Class EmailContent
 * @package app\common\model
 */
class EmailAccountsLog extends ErpModel
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public function getTypeTextAttr($value,$data)
    {
        switch ($data['type']){
            case 0:
                return '新增';
            case 1:
                return '修改';
            case 2:
                return '删除';
        }
        return '未知的类型';
    }


    public function operator()
    {
        return $this->hasOne('app\common\model\User','id','operator_id')->field('id,realname');
    }

}