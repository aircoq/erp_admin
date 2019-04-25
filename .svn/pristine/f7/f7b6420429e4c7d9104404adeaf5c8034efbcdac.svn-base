<?php

namespace app\common\model\aliexpress;

use app\common\model\Order;
use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;
use think\Model;
use think\Db;
use app\common\cache\Cache;

class AliexpressIssue extends ErpModel
{
    use ModelFilter;
    private $filterAccount = [];
    protected $autoWriteTimestamp = true;
    
    protected $dateFormat = false;


    const PROCESSING    = 1;
    const CANCELED_ISSUE          = 2;
    const FINISH                   = 3;

    const ISSUE_STATUS = [
        self::PROCESSING    =>  'processing',//纠纷状态 处理中
        self::CANCELED_ISSUE  =>  'canceled_issue',//纠纷取消
        self::FINISH =>  'finish',//纠纷完结,退款处理完成
    ];

    const ISSUE_LABEL = [
        1 => ['name' => '全部', 'condition' => [], 'value' => 1],
        2 => ['name' => '纠纷处理中', 'condition' => ['i.issue_status' => 'processing'], 'value' => 2],
        3 => ['name' => '等待卖家确认', 'condition' => ['i.wait_seller_accept' => 1,'i.after_sale_warranty' =>0,'i.issue_status' => 'processing'], 'value' => 3],
        4 => ['name' => '售后宝纠纷', 'condition' => ['i.after_sale_warranty' => 1,'i.issue_status' => 'processing'], 'value' => 4],
        5 => ['name' => '纠纷已取消', 'condition' => ['i.issue_status' => 'canceled_issue'], 'value' => 5],
        6 => ['name' => '纠纷已结束', 'condition' => ['i.issue_status' => 'finish'], 'value' => 6],
    ];

    /**
     * @param array $data
     * @return boolean 是否存在
     */
    public function saveData($data)
    {
        $check = $this->where(['issue_id' => $data['issue_id']])->find();
        if (!$check) {
            $this->allowField(true)->isUpdate(false)->save($data);
        } else {
            $this->allowField(true)->isUpdate(true)->save($data, ['issue_id' => $data['issue_id']]);
        }
        return !!$check;
    }
    
    public static function checkNewest($order_id,$issue_modified_time)
    {
        $model = self::get(['order_id'=>$order_id,'issue_modified_time'=>$issue_modified_time]);
        if(empty($model)){
            return false;
        }
        return true;
    }
    
    public function solution()
    {
        return $this->hasMany('AliexpressIssueSolution', 'issue_id', 'issue_id');
    }

    public function process()
    {
        return $this->hasMany('AliexpressIssueProcess', 'issue_id', 'issue_id');
    }
    
    public function haveOrder()
    {
        return $this->hasOne('AliexpressOnlineOrder', 'order_id','order_id','','LEFT JOIN')->field('id,gmt_create,gmt_pay_time,buyer_login_id')->setEagerlyType(0);
    }

    public function sysorder()
    {
        return $this->hasOne(Order::class,'channel_order_number','order_id')->field('id,order_number');
    }

    /**
     * 纠纷创建时间修改器(Aliexpress返回时间与北京时间相差15小时)
     * @param $value
     * @return mixed
     */
    public function setIssueCreateTimeAttr($value)
    {
        if(!$value){
            return $value;
        }else{
            return ($value+54000);
        }
    }

    public function setIssueModifiedTimeAttr($value)
    {
        if(!$value){
            return $value;
        }else{
            return ($value+54000);
        }
    }

    public function setExpireTimeAttr($value)
    {
        if(!$value){
            return $value;
        }else{
            return ($value+54000);
        }
    }

    /**
     * 调用AliexpressAccount过滤
     * @param Query $query
     * @param $params
     */
    public function scopeAliexpressAccount(Query $query, $params)
    {
        $this->filterAccount = array_merge($params, $this->filterAccount);
        if(!empty($params))
        {
            $query->where('__TABLE__.aliexpress_account_id', 'in', $this->filterAccount);
        }
    }
}
