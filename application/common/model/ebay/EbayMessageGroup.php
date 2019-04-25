<?php
namespace app\common\model\ebay;

use app\common\traits\ModelFilter;
use app\customerservice\service\EbayMessageHelp;
use erp\ErpModel;
use think\db\Query;
use think\Model;
use think\Loader;
use think\Db;
use think\Exception;
use app\common\service\ChannelAccountConst;
use app\customerservice\service\MsgRuleHelp;


class EbayMessageGroup extends ErpModel
{

    use ModelFilter;

    private $filterCustomer = [];

    private $filterAccount = [];

    /**
     * 调用EbayAccountFilter过滤
     * @param Query $query
     * @param $params
     */
    public function scopeEbayCustomer(Query $query, $params)
    {
        $this->filterCustomer = array_merge($params, $this->filterCustomer);
        if (!empty($this->filterCustomer)) {
            $query->where('__TABLE__.customer_id', 'in', $this->filterCustomer);
        }
    }

    /**
     * 调用EbayAccountFilter过滤
     * @param Query $query
     * @param $params
     */
    public function scopeEbayAccount(Query $query, $params)
    {
        $this->filterAccount = array_merge($params[0], $this->filterAccount);
        if (!empty($this->filterAccount)) {
            $query->where('__TABLE__.account_id', 'in', $this->filterAccount);
        }
    }

    /**
     * 调用EbayAccountFilter过滤
     * @param Query $query
     * @param $params
     */
    public function scopeDepartment(Query $query, $params)
    {
        $this->filterAccount = array_merge($params, $this->filterAccount);
        if (!empty($this->filterAccount)) {
            $query->where('__TABLE__.account_id', 'in', $this->filterAccount);
        }
    }

    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
        $this->query('set names utf8mb4');
    }


    /** @var array 新增的时候，临时存一下 */
    private $tmp_customer_id = [];

    /**
     * 新增
     * @param array $data [description]
     */
    public function add(array $data)
    {
        $return = [
            'group_id'=>0,
            'new_group'=>false
        ];

        if (!empty($data)) {
            try {
                //检查是否已存在
                $where['account_id'] = $data['account_id'];
                $where['sender_user'] = $data['sender_user'];
                $where['item_id'] = $data['item_id'];
                $info = $this->where($where)->find();
                if (empty($info)) {

                    //新增的时候，写进客服ID，方便查询;
                    if (empty($this->tmp_customer_id[$data['account_id']])) {
                        $this->tmp_customer_id[$data['account_id']] = (new EbayMessageHelp())->getCustomerIdByAccountId($data['account_id']);
                    }
                    $data['customer_id'] = $this->tmp_customer_id[$data['account_id']];
                    $this->insert($data);
                    $id = $this->getLastInsID();
                    $return['group_id'] = $id;
                    $return['new_group'] = true;
                } else {
                    $update = [];
                    //1.新的站内信进来，原分组总数量要加1
                    $update['msg_count'] = $info['msg_count'] + 1;
                    //2.未回复数量要加上新的站内住是否未处理数，可能+0，也可能+1；
                    $update['untreated_count'] = $info['untreated_count'] + $data['untreated_count'];
                    //3.如果回复时间比分组第一次回复早，则把最早数据换掉；
                    if ($info['first_receive_time'] == 0 || $data['first_receive_time'] < $info['first_receive_time']) {
                        $update['first_receive_time'] = $data['first_receive_time'];
                        $update['first_message_id'] = $data['first_message_id'];
                    }
                    if ($data['last_receive_time'] >= $info['last_receive_time'] && empty($info['last_transaction_id'])) {
                        if (!empty($data['last_transaction_id'])) {
                            $update['last_transaction_id'] = $data['last_transaction_id'];
                        }
                        if (!empty($data['local_order_id'])) {
                            $update['local_order_id'] = $data['local_order_id'];
                        }
                    }
                    //4.如果回复时间比分组最后一次回复晚，则把最后一次数据换掉；！！！最重要的是按最后的这条数据的回复状态来确定是否未处理；
                    if ($data['last_receive_time'] > $info['last_receive_time']) {
                        $update['last_receive_time'] = $data['last_receive_time'];
                        $update['last_message_id'] = $data['last_message_id'];
                        $update['status'] = $data['status'];
                    }
                    $update['update_time'] = $data['update_time'];
                    $this->save($update, ['id' => $info['id']]);

                    $id = $info['id'];
                    $return['group_id'] = $id;
                    $return['new_group'] = false;
                }
            } catch (Exception $ex) {
                throw new Exception('ebay站内信分组添加异常 [' . date('Y-m-d H:i:s', time()) . ']' . $ex->getMessage());
            }
        }
        return $return;
    }

    /**
     * 批量新增
     * @param array $data [description]
     */
    public function addAll(array $data)
    {
        foreach ($data as $key => $value) {
            $rs = $this->add($value);
        }

    }


    /**
     * 修改数据
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
     * 保存更新最后交易号。
     * @param unknown $data
     * @return number|\think\false
     */
    public function save_transaction_id($data)
    {

        $where = [];
        $where['account_id'] = ['EQ', $data['account_id']];
        $where['sender_user'] = ['EQ', $data['sender_user']];
        $where['item_id'] = ['EQ', $data['item_id']];

        $info = $this->where($where)->find();
        if (empty($info)) {
            return false;
        }

        $update = [];
        $update['last_transaction_id'] = $data['last_transaction_id'];
        $update['last_order_status'] = $data['last_order_status'];

        $res = $this->update($update, $where);

        $str = $data['account_id'] . '_' . $data['sender_user'] . "_" . $data['item_id'];
        \think\Log::write('更新ebay_message_group的交易号 【' . $str . '】：' . empty($res) ? '成功' : '失败');
        return $res;
    }


    /** 检测数据是否存在
     * @param int $id
     * @return bool
     */
    public function isHas($id = 0)
    {
        $result = $this->where(['id' => $id])->find();

        if (empty($result)) {   //不存在
            return false;
        }
        return true;
    }


}