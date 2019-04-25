<?php
namespace app\common\model\aliexpress;

use think\Model;
use think\Db;
use app\common\model\aliexpress\AliexpressOnlineOrder;

class AliexpressOnlineOrderDetail extends Model
{
    protected $autoWriteTimestamp = true;

    /**
     * PLACE_ORDER_SUCCESS:等待买家付款;
     * IN_CANCEL:买家申请取消; 
     * WAIT_SELLER_SEND_GOODS:等待您发货; 
     * SELLER_PART_SEND_GOODS:部分发货; 
     * WAIT_BUYER_ACCEPT_GOODS:等待买家收货; 
     * FUND_PROCESSING:买家确认收货后，等待退放款处理的状态; 
     * FINISH:已结束的订单; 
     * IN_ISSUE:含纠纷的订单; 
     * IN_FROZEN:冻结中的订单; 
     * WAIT_SELLER_EXAMINE_MONEY:等待您确认金额; 
     * RISK_CONTROL:订单处于风控24小时中，从买家在线支付完成后开始，持续24小时。
     */
    
    public function sonOrderStatusAttr($value)
    {
        switch ($value) {
            case 'PLACE_ORDER_SUCCESS':
                $status = 1;
                break;
            case 'IN_CANCEL':
                $status = 2;
                break;
            case 'WAIT_SELLER_SEND_GOODS':
                $status = 3;
                break;
            case 'SELLER_PART_SEND_GOODS':
                $status = 4;
                break;
            case 'WAIT_BUYER_ACCEPT_GOODS':
                $status = 5;
                break;
            case 'FUND_PROCESSING':
                $status = 6;
                break;
            case 'FINISH':
                $status = 7;
                break;
            case 'IN_ISSUE':
                $status = 8;
                break;
            case 'IN_FROZEN':
                $status = 9;
                break;
            case 'WAIT_SELLER_EXAMINE_MONEY':
                $status = 10;
                break;
            case 'RISK_CONTROL':
                $status = 11;
                break;
            default :
                $status = 0;
                break;
        }
        return $status;
    }
    
    /**
     * 添加订单明细
     * @param unknown $data
     */
    public function addOrderDetail($data = [], $isUpate = false) 
    {
        $result = $this->saveAll($data, $isUpate);
        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * 更新订单状态
     * @param number $orderID
     * @param string $orderStatus
     * @param $flag PARENT  CHILD
     */
    public function updateOrderStatus($data = [], $orderID = 0)
    {
        $code = $this->where(['pid' => $orderID])->update($data);
        return $code;
    }
    
    /**
     * 更新订单详情
     * @param unknown $data
     * @param number $orderId
     * @param number $gmtCreate
     */
    public function updateOrderDetail($data = [])
    {
        foreach ($data as $k=>&$v) {
            //$where['gmt_create'] = $v['gmt_create'];*/
            $where['child_id'] = $v['child_id'];
            if(isset($v['sku_code'])){
                $where['sku_code'] = $v['sku_code'];
            }
            $orderDetails = $this->where($where)->field('id')->find();
            if($orderDetails){
                 $v['update_time'] = time();
                 $orderDetails->allowField(true)->save($v);
            }
            unset($v);
            $where = [];
        }
    }
    
    /**
     * 状态修改器
     * @param type $value
     * @return type
     */
    protected function setSonOrderStatusAttr($value)
    {
        return $this->sonOrderStatusAttr($value);
    } 
    
    public function aliorder()
    {
        return $this->belongsTo(AliexpressOnlineOrder::class, 'order_id', 'pid');
    }
}