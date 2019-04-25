<?php
namespace app\common\model\lazada;

use app\common\cache\Cache;
use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;
use think\Model;
use think\Db;

class LazadaPlatformOnlineOrder extends ErpModel
{
    use ModelFilter;

    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function scopeOrder(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.merchant_id', 'in', $params);
        }
    }

    /** 关系
     * @return \think\model\Relation
     */
    public function role()
    {
        //一对一的关系，一个订单对应一个商品
        return $this->belongsTo('WishPlatformOnlineGoods');
    }

    /** 新增订单
     * @param array $data
     * @return bool
     */
    public function add(array $data)
    {
        if (!isset($data['order_id'])) {
            return false;
        }
        if(!Cache::store('partition')->getPartition(__CLASS__,$data['transaction_date'])){
            Cache::store('partition')->setPartition(__CLASS__,$data['transaction_date']);
        }
        //检查订单是否已存在
        if ($this->checkorder(['order_id' => $data['order_id']])) {
            $this->edit($data, ['order_id' => $data['order_id']]);
        }else{
            $this->allowField(true)->isUpdate(false)->save($data);
        }
    }

    /** 获取订单状态
     * @param int $value
     * @return array
     */
    public function getStatus($value = -1)
    {
        $status = [
            0=>'PENDING',
            1=>'SHIPPED',
            2=>'APPROVED',
            3=>'DECLINED',
            4=>'DELAYING',
            5=>'CANCELLED BY CUSTOMER',
            6=>'REFUNDED BY MERCHANT',
            7=>'REFUNDED BY WISH',
            8=>'REFUNDED BY WISH FOR MERCHANT',
            9=>'UNDER REVIEW FOR FRAUD',
            10=>'CANCELLED BY WISH (FLAGGED TRANSACTION)',
            11=>'UNKNOWN'
        ];
        if($value < 0){
            return $status;
        }
        return $status[$value];
    }

    /**
     * 批量新增
     * @param array $data [description]
     */
    public function addAll(array $data)
    {
        foreach ($data as $key => $value) {
            $this->add($value);
        }
    }

    /** 修改订单
     * @param array $data
     * @param array $where
     * @return false|int
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }

    /** 批量修改
     * @param array $data
     * @return false|int
     */
    public function editAll(array $data)
    {
        return $this->save($data);
    }

    /** 检查订单是否存在
     * @param array $data
     * @return bool
     */
    protected function checkorder(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    /** wish平台产品/订单 状态的转换
     * @param $value
     * @return int
     */
    public static function wishTurn($value)
    {
        $state =
            [
                'Enabled' => 1,  #启用中
                'Disabled' => 0,  #禁用中
                'Promoted' => 1,
                'Not Promoted' => 0,
                'pending' => 0,    #待审核
                'approved' => 1,   #已批准
                'rejected' => 2,   #已拒绝

                'APPROVED' => 2,   # 批准
                'CANCELLED BY CUSTOMER' => 5,  #客户取消订单
                'DECLINED' => 3,  # 拒绝
                'DELAYING' => 4,  # 拖延
                'PENDING' => 0,  # 等待
                'REFUNDED BY MERCHANT' => 6, # 商家退货
                'REFUNDED BY WISH' => 7,  # 平台退货
                'REFUNDED BY WISH FOR MERCHANT' => 8, # 平台退还给商家
                'SHIPPED' => 1,  # 运输中
                'UNDER REVIEW FOR FRAUD' => 9, # 列入欺诈
                'CANCELLED BY WISH (FLAGGED TRANSACTION)' => 10
            ];
        if (isset($state[$value])) {
            return $state[$value];
        } else {
            return 11;
        }
    }
}