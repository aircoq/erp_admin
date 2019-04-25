<?php
namespace app\common\model\paytm;

use think\Model;
use think\Loader;
use think\Db;

class maytmAbroadOrderDetail extends Model
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

    //TODO: 订单要和wishgoods对应吗
    /**
     * 关系
     * @return [type] [description]
     */
    // public function role()
    // {
    //     //一对一的关系，一个订单对应一个商品
    //     return $this->belongsTo('WishPlatformOnlineGoods');
    // }

    /**
     * 新增订单
     * @param array $data [description]
     */
    public function add(array $data)
    {
        if (!isset($data['order_id'])) {
            return false;
        }
        $current = explode('/', $data['transaction_date']);
        $this->parition($current);
        $data['transaction_date'] = strtotime($data['transaction_date']);
        //检查订单是否已存在
        if ($this->checkorder(['order_id' => $data['order_id']])) {
            $this->edit($data, ['order_id' => $data['order_id']]);
        }

        $this->allowField(true)->isUpdate(false)->save($data);
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
     * 分区
     * @return [type] [description]
     */
    // public function parition($current)
    // {
    //     if (!isset($current[1])) return false;

    //     if ($current[1] < 10) {
    //         $current[1] = '0' . $current[1];
    //     }
    //     $start = strtotime($current[0] . '-' . $current[1] . '-01 00:00:00');

    //     $end = intval($current[1]) + 1;
    //     if ($end > 12) {
    //         $current[0] = intval($current[0]) + 1;
    //         $end = '01';

    //     } else if ($end < 10) {
    //         $end = '0' . $end;
    //     }

    //     $less = strtotime($current[0] . '-' . $end . '-01 00:00:00');
    //     try {
    //         //当前时间，判断分区是否存在
    //         $result = Db::query('ALTER TABLE `wish_platform_online_order` CHECK partition p' . $start);
    //         if ($result[0]['Msg_type'] != 'status' && $result[0]['Msg_text'] != 'OK') {
    //             //证明分区不存在，需要创建
    //             $bool = Db::query('ALTER TABLE `wish_platform_online_order` ADD PARTITION (PARTITION p' . $start . ' VALUES LESS THAN (' . $less . '))');
    //             if ($bool) {
    //                 return true;
    //             }
    //         }
    //     } catch (Execption $e) {
    //         return false;
    //     }
    // }

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
}