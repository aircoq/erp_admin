<?php
namespace app\common\model\customerservice;

use app\common\model\amazon\AmazonOrder;
use app\common\model\Order;
use erp\ErpModel;
use think\Model;

/**
 *  邮件信息
 * Class EmailList
 * @package app\common\model
 */
class EmailList extends ErpModel
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->query('set names utf8mb4');
    }

    public function content()
    {
        return $this->hasOne('EmailContent','email_id','id')->bind(['body'=>'content']);
    }

    public function box()
    {
        return $this->hasOne('EmailBoxes','id','box_id',[],'INNER')->bind([
            'box_code'=> 'code',
            'box_name'=> 'ch_name',
        ]);
    }


    public function flag()
    {
        return $this->hasOne('EmailFlags','id','flag_id')->bind([
            'flag_code'=> 'code',
            'flag_name'=> 'ch_name',
        ]);
    }

    public function amazonOrder()
    {
        return $this->hasOne(AmazonOrder::class, 'order_number','order_no',[],'LEFT');
    }

    public function systemOrder()
    {
        return $this->hasOne(Order::class,'channel_order_number','order_no',[],'LEFT');
    }

    public function amazonAccount()
    {
        return $this->hasOne('app\common\model\amazon\AmazonAccount','id','account_id',[],'LEFT');
    }


    /**
     * 获取附件列表数组
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getAttachmentsArrayAttr($value,$data)
    {
        $arr = json_decode($this->attachments);
        $result = [];
        foreach ($arr as $attch) {
            $result[] = [
                'name' => $attch->name,
                'path' => $attch->path,
            ];
        }
        return $result;
    }
    
    function check($id){
        $result = $this->field('id')->where(['id'=>$id])->find();
        return $result;
    }

}