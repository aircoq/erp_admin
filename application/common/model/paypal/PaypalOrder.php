<?php

namespace app\common\model\paypal;

use app\common\exception\QueueException;
use think\Model;
use think\Db;
use app\common\cache\Cache;
use think\Exception;


class PaypalOrder extends Model
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

    protected $type = [
        'item' => 'array',
    ];

    /**
     * 新增订单
     * @param array $data [description]
     */
    public function add222($data)
    {
        if (empty($data)) {
            return false;
        }
        $masterTable = "paypal_order";
        $date = array(2013, 2014, 2015, 2016, 2017, 2018);
        if (!empty($data)) {
            foreach ($data as $order) {
                try {
                    // time_partition(__CLASS__,$order['order']['created_time'],'created_time',$date);
                } catch (\Exception $e) {
                }
                if (!$this->checkorder(['txn_id' => $order['txn_id']])) {
                    // 启动事务
//                     Db::startTrans();
                    $rs = Db::name($masterTable)->insert($order);
//                     if ($rs) {         
//                         try {
//                             // 提交事务 
//                             Db::commit();                   
//                         } catch (\Exception $e) {
//                             //回滚事务 
//                             Db::rollback(); 
//                         }
//                     }
                }
            }
        }
        return true;
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
        return true;
    }


    /**
     * 新增
     * @param array $data [description]
     */
    public function add(array $data)
    {
        $masterTable = "paypal_order";
        if (isset($data['txn_id'])) {
            $id = $data['id'];
            try {
                //检查是否已存在
                if (!$id) {
                    $id = $this->insert($data, false, true);

                } else {
                    $this->update($data, ['id' => $id]);
                }

                //保存或者更新paypal定单都重新更新order;
                //$info = [
                //    'id' => $id,
                //    'txn_id' => $data['txn_id'],
                //    'account_id' => $data['account_id'],
                //    'receiver_email' => $data['receiver_email'],
                //    'payer_email' => $data['payer_email'],
                //    'payment_status' => $data['payment_status']
                //];
                //Cache::store('PaypalOrder')->paypalOrderByTxnid($data['account_id'], $data['txn_id'], $info);

                return true;

            } catch (Exception $ex) {
                throw new Exception('保存数据出错:'. json_encode($data). '|'. $ex->getMessage());
            }
        }
        return false;
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
}