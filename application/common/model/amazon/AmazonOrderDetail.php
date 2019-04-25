<?php
namespace app\common\model\amazon;

use think\Model;
use app\common\cache\Cache;

class AmazonOrderDetail extends Model
{
    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /**
     * 关系
     * @return [type] [description]
     */
    public function role()
    {
        //一对一的关系，一个订单对应一个商品
        return $this->belongsTo('WishPlatformOnlineGoods');
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
    
    
    /**
     * 修改订单
     * @param  array $data [description]
     * @return [type]       [description]
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }

    /**
     * 批量修改
     * @param  array $data [description]
     * @return [type]       [description]
     */
    public function editAll(array $data)
    {
        return $this->save($data);
    }

    /**
     * 检查订单是否存在
     * @return [type] [description]
     */
    protected function checkorder(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }
    
    /**
     * @desc 保存通过api拉取的亚马逊订单明细数据
     * @author wangwei
     * @date 2019-3-11 17:59:20
     * @param array $items 订单明细，二维数组
     * @param int $account_id 平台账号id
     * @param string $order_number  平台订单号
     */
    public function saveAmazonOrderItemsByApi($items, $account_id, $order_number){
        /**
         * 1、简单校验
         */
        if(!isNumericArray($items)){
            throw new \Exception('items not Numeric Array');
        }
        if(empty($account_id)){
            throw new \Exception('account_id not empty');
        }
        if(empty($order_number)){
            throw new \Exception('order_number not empty');
        }
        $hasOrder = AmazonOrder::where(['order_number' => $order_number,'account_id'=>$account_id])->field('sales_channel,amazon_order_id,last_update_time,id')->find();
        if(empty($hasOrder)){
            throw new \Exception("order_number:{$order_number} does not exist");
        }
        
        /**
         * 2、添加\更新amazon_order_detail表
         */
        //实际运费
        $total_shipping_fee = 0;
        foreach ($items as $item){
            $itemPrice = $item['ItemPrice'] ? $item['ItemPrice']['Amount'] : 0;
            $price = $item['QuantityOrdered'] ? round($itemPrice / $item['QuantityOrdered'], 4) : 0;
            $tmp_fee = $item['ShippingPrice'] ? floatval($item['ShippingPrice']['Amount']) : 0;
            $total_shipping_fee += $tmp_fee;
            $aod_row = array(
                'record_number'=>$item['OrderItemId'], // 订单商品识别号
                'order_number'=>$order_number,
                'item_price'=>$price,
                'currency_code'=>$item['ItemPrice'] ? $item['ItemPrice']['CurrencyCode'] : '',
                'online_sku'=>$item['SellerSKU'],
                'sku'=>$item['SellerSKU'],
                'qty'=>$item['QuantityOrdered'],
                'shipping_fee'=>$tmp_fee, //amazon的邮费没有？
                'item_id'=>$item['ASIN'],
                'item_title'=>param($item, 'Title', ''),
                'item_url'=>'https://www.'.$hasOrder['sales_channel'] . '/gp/product/'.$item['ASIN'],
                'promotion_discount'=>$item['PromotionDiscount'] ? $item['PromotionDiscount']['Amount'] : 0,
                'shipping_tax'=>$item['ShippingTax'] ? $item['ShippingTax']['Amount'] : 0,
                'shipping_discount'=>$item['ShippingDiscount'] ? $item['ShippingDiscount']['Amount'] : 0,
                'shipping_price'=>$item['ShippingPrice'] ? $item['ShippingPrice']['Amount'] : 0,
                'condition_note'=>param($item, 'ConditionNote', ''),
                'condition_subtype_id'=>param($item, 'ConditionSubtypeId', ''),
            );
            //添加\更新表数据
            $aodModel = new AmazonOrderDetail();
            //查询是否存在
            $hasOrderItem = $aodModel->where(['amazon_order_id' => $hasOrder['amazon_order_id'], 'record_number' => $aod_row['record_number']])->field('id')->find();
            if ($hasOrderItem) {
                $aodModel->where('id',$hasOrderItem['id'])->update($aod_row);
            } else {
                $aod_row['amazon_order_id'] = $hasOrder['amazon_order_id'];
                $aod_row['created_time'] = time();
                $aodModel->data($aod_row)->save();
            }
        }
        
        /**
         * 3、更新amazon_order表
         */
        $ao_up = [
            'has_detail'=>1,
            'actual_shipping'=>$total_shipping_fee,
        ];
        (new AmazonOrder())->where('id',$hasOrder['id'])->update($ao_up);
        
        /**
         * 4、更新订单缓存
         */
        $orderCache = [
            'last_update_time' => $hasOrder['last_update_time'],
            'id'  => $hasOrder['id'],
            'has_detail'=>1
        ];
        Cache::store('AmazonOrder')->orderUpdateTime($account_id, $order_number, $orderCache);
        
    }
    
}