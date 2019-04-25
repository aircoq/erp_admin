<?php
namespace app\common\model\customerservice;

use erp\ErpModel;
use think\Model;

/**
 *  发送邮件信息
 * Class EmailList
 * @package app\common\model
 */
class EmailSentList extends ErpModel
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public function content()
    {
        return $this->hasOne('EmailSentContent','sent_id','id')->bind(['body'=>'content']);
    }

    /**
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusTextAttr($value,$data)
    {
        switch ($this->status){
            case 0:
                return '待发送';
            case 1:
                return '已发送';
            case 2:
                return '发送失败';
        }
        return '未知状态';

    }

}