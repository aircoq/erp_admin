<?php
namespace app\common\model\ebay;

use app\common\service\UniqueQueuer;
use app\customerservice\queue\EbayMessageUntreatedTotalQueue;
use app\customerservice\service\EbayMessageHelp;
use app\customerservice\service\KeywordMatching;
use erp\ErpModel;
use think\Config;
use think\Model;
use app\common\cache\Cache;
use think\Db;
use think\Exception;
use app\common\exception\JsonErrorException;

class EbayMessage extends ErpModel
{

    /**
     * 初始化
     *
     * @return [type] [description]
     */
    protected function initialize()
    {
        // 需要调用 mdoel 的 initialize 方法
        parent::initialize();
        $this->query('set names utf8mb4');
    }

    /**
     * 批量新增
     *
     * @param array $data
     *            [description]
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
     *
     * @param array $data
     *            [description]
     */
    public function add(array $data)
    {
        $msgBodyModel = new EbayMessageBody();
        $msgGroupModel = new EbayMessageGroup();
        $keywordMatching = new KeywordMatching();
        $this->query('set names utf8mb4');
        $message_id = isset($data['data']['message_id']) ? $data['data']['message_id'] : 0;
        if (empty($message_id)) {
            return false;
        }

        /** @var  $lock \app\common\cache\driver\Lock */
        $lock = Cache::store('Lock');
        $lockParam = ['message_id' => $data['data']['message_id']];
        //$lock->unlockParams($lockParam);
        if ($lock->lockParams($lockParam)) {
            $id = $data['data']['id'];

            try {
                if (empty($id)) {
                    $message = $this->where(['message_id' => $data['data']['message_id']])
                        ->field('id,group_id,replied,status')
                        ->find();
                    if (!empty($message)) {
                        $id = $data['data']['id'] = $message['id'];
                        $data['data']['group_id'] = $message['group_id'];
                        //被忽略的标记为已回复；
                        if ($message['status'] == 2) {
                            $data['data']['replied'] = 1;
                            $data['data']['status'] = 2;
                        }
                    }
                }
                if (!$id) {
                    $group_data = [
                        'group_id'=>0,
                        'new_group'=>false
                    ];
                    if ($data['data']['message_type'] == 1 && isset($data['groupData']) && !empty($data['groupData'])) {
                        $group_data = $msgGroupModel->add($data['groupData']); // 新信息才进行统计
                    }

                    Db::startTrans();
                    $data['data']['group_id'] = $group_data['group_id'];
                    $id = $this->insertGetId($data['data']);

                    //html保存到mongodb;
                    $html = '';
                    if ($data['detaliData']) {
                        $data['detaliData']['id'] = $id;
                        //html不再保存到mysql,提出来保存到mongodb;
                        $html = $data['detaliData']['message_html'];
                        unset($data['detaliData']['message_html']);
                        $msgBodyModel->insert($data['detaliData']);
                    }

                    $this->saveHtmlToMongo($id, $html);
                    Db::commit();

                    /*
                     * 触发买家第一封站内信事件
                     */
                    if ($data['data']['message_type'] == 1 && isset($data['groupData']) && !empty($data['groupData']) && $group_data['new_group']) {
                        (new EbayMessageHelp())->triggerMyMessageEvent($data['groupData']);
                    }

                    if (isset($data['data']['message_text'])){
                        /**
                         * 关键词匹配
                         */
                        $param = [
                            'channel_id'=>1,
                            'message_id'=>$id,
                            'account_id'=>$data['data']['account_id'],
                            'message_type'=>0,
                            'buyer_id'=>$data['data']['sender'],
                            'receive_time'=>$data['data']['send_time'],
                        ];
                        $keywordMatching->keyword_matching($data['data']['message_text'],$param);
                    }

                } else {
                    //需要更新的分组数据；
                    $updateGroup = [];

                    //已有的数据，应调整分组；
                    if ($data['data']['message_type'] == 1) {
                        if (!empty($data['data']['group_id'])) {

                            //之前的分组数据查出来；
                            $group = $msgGroupModel->where(['id' => $data['data']['group_id']])->find();

                            //买家发送的数据肯定是有分组的；
                            if (!empty($group)) {
                                //1. 对比最后分组的message_id，如果一样，则以当前这个站内信的数据为准；
                                if ($data['data']['message_id'] == $group['last_message_id']) {
                                    //老分组未处理时，新数据已回复，则应该把分组装态调整为已回复；
                                    if ($group['status'] != $data['data']['replied']) {
                                        $updateGroup['status'] = $data['data']['replied'];
                                        if ($data['data']['replied'] == 0) {
                                            $updateGroup['untreated_count'] = $group['untreated_count'] + 1;
                                        } else {
                                            if ($group['untreated_count'] > 0) {
                                                $updateGroup['untreated_count'] = $group['untreated_count'] - 1;
                                            }
                                        }
                                    }
                                    if (empty($group['last_transaction_id']) && !empty($data['data']['transaction_id'])) {
                                        $updateGroup['last_transaction_id'] = $data['data']['transaction_id'];
                                    }
                                    if (empty($group['local_order_id']) && !empty($data['data']['local_order_id'])) {
                                        $updateGroup['local_order_id'] = $data['data']['local_order_id'];
                                    }
                                } else if ($data['data']['send_time'] > $group['last_receive_time']) {
                                    $updateGroup['status'] = $data['data']['replied'];
                                    $updateGroup['last_message_id'] = $data['data']['message_id'];
                                    $updateGroup['last_receive_time'] = $data['data']['send_time'];
                                    if (!empty($data['data']['transaction_id'])) {
                                        $updateGroup['last_transaction_id'] = $data['data']['transaction_id'];
                                    }
                                    if (!empty($data['data']['local_order_id'])) {
                                        $updateGroup['local_order_id'] = $data['data']['local_order_id'];
                                    }
                                } else if ($group['first_receive_time'] == 0 && $data['data']['send_time'] < $group['first_receive_time']) {
                                    $updateGroup['first_message_id'] = $data['data']['message_id'];
                                    $updateGroup['first_receive_time'] = $data['data']['send_time'];
                                }
                            }
                        }
                    }

                    Db::startTrans();
                    //类别为买家发送，且有分组有数据需要更新时先更新分组数据；
                    if ($data['data']['message_type'] == 1 && !empty($updateGroup)) {
                        $updateGroup['update_time'] = $data['data']['update_time'];
                        if (!empty($data['data']['update_id'])) {
                            $updateGroup['update_id'] = $data['data']['update_id'];
                        }
                        $msgGroupModel->update($updateGroup, ['id' => $data['data']['group_id']]);
                    }

                    $this->update($data['data'], ['id' => $id]);

                    //html保存到mongodb;
                    //html不再保存到mysql,提出来保存到mongodb;
                    $html = $data['detaliData']['message_html'] ?? '';
                    unset($data['detaliData']['message_html']);
                    $msgBodyModel->update($data['detaliData'], ['id' => $id]);
                    //保存到mongodb;
                    $this->saveHtmlUpdateToMongo($id, $html);

                    Db::commit();
                }

                $lock->unlockParams($lockParam);
                return true;
            } catch (Exception $ex) {
                Db::rollback();
                $lock->unlockParams($lockParam);
                throw new Exception('ebay站内信添加异常 ' . $ex->getMessage());
            }
        }
        return false;
    }


    /**
     * 保存html到mongodb;
     */
    public function saveHtmlToMongo($id, $html)
    {
        if (empty($id) || !is_numeric($id)) {
                throw new Exception('保存mongodb时，传进未知ID参数:['. json_encode($id, true). ']');
        }
        $id = (int)$id;
        $db = Db::connect(Config::get('mongodb'));
        try {
            $db->table('ebay_message_detail')->insert(['message_id' => $id, 'html' => $html]);
            //$db->close();
            return true;
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            //加入了重复的内容；
            if (strpos($msg, 'duplicate key error collection') !== false) {
                $db->table('ebay_message_detail')->where(['message_id' => $id])->update(['html' => $html]);
                //$db->close();
                return true;
            }
            throw new Exception($e->getMessage());
        }
    }


    /**
     * 保存html到mongodb;
     */
    public function saveHtmlUpdateToMongo($id, $html)
    {
        if (empty($id) || !is_numeric($id)) {
                throw new Exception('保存mongodb时，传进未知ID参数:['. json_encode($id, true). ']');
        }
        $id = (int)$id;
        $db = Db::connect(Config::get('mongodb'));
        try {
            $num = $db->table('ebay_message_detail')->where(['message_id' => $id])->field('message-id')->count('message_id');
            if ($num) {
                $db->table('ebay_message_detail')->where(['message_id' => $id])->update(['html' => $html]);
            } else {
                $db->table('ebay_message_detail')->insert(['message_id' => $id, 'html' => $html]);
            }
            //$db->close();
            return true;
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            //加入了重复的内容；
            if (strpos($msg, 'duplicate key error collection') !== false) {
                return true;
            }
            throw new Exception($e->getMessage());
        }
    }


    /**
     * 批量新增发件箱信息
     *
     * @param array $data
     *            [description]
     */
    public function addAllOutbox(array $data)
    {
        foreach ($data as $key => $value) {
            $rs = $this->addOutbox($value);
        }
        return true;
    }


    /** @var array 新增的时候，临时存一下 */
    private $tmp_customer_id = [];

    /**
     * 新增发件箱信息
     *
     * @param array $data
     *            [description]
     */
    public function addOutbox(array $data)
    {
        $msgBodyModel = new EbayMessageBody();
        $msgGroupModel = new EbayMessageGroup();

        $message_id = isset($data['data']['message_id']) ? $data['data']['message_id'] : 0;
        if (empty($message_id)) {
            return false;
        }
        $lock = Cache::store('Lock');
        //$lock->unlockParams($lockParam);
        $lockParam = ['message_id' => $data['data']['message_id']];
        if ($lock->lockParams($lockParam)) {
            try {
                $id = $data['data']['id'];
                //进来后再查询一下ID防止进来后又重建；
                if (empty($id)) {
                    $message = $this->where(['message_id' => $data['data']['message_id']])->field('id,group_id')->find();
                    if (!empty($message)) {
                        $id = $data['data']['id'] = $message['id'];
                        $data['data']['group_id'] = $message['group_id'];
                    }
                }
                if (!$id) {
                    // 查找group_id
                    $where = [];
                    $where['sender_user'] = $data['data']['send_to_name'];
                    $where['receive_user'] = $data['data']['sender'];
                    $where['item_id'] = $data['data']['item_id'];

                    $group = $msgGroupModel->field('id,last_receive_time,status')
                        ->where($where)
                        ->find();

                    $data['data']['group_id'] = 0;
                    $time = time();
                    //当存在分组数据时
                    if (empty($group)) {
                        $account_id = $data['data']['account_id'];
                        $group['account_id'] = $account_id;
                        $group['item_id'] = $data['data']['item_id'];
                        $group['sender_user'] = $data['data']['send_to_name'];
                        $group['receive_user'] = $data['data']['sender'];
                        $group['first_receive_time'] = 0;

                        if (!empty($data['data']['local_order_id'])) {
                            $group['local_order_id'] = $data['data']['local_order_id'];
                        }
                        if (!empty($data['data']['transaction_id'])) {
                            $group['last_transaction_id'] = $data['data']['transaction_id'];
                        }

                        $group['created_time'] = $time;
                        $group['update_time'] = $time;
                        $group['create_id'] = 0;
                        $group['update_id'] = 0;
                        //新增的时候，写进客服ID，方便查询;
                        if (empty($this->tmp_customer_id[$account_id])) {
                            $this->tmp_customer_id[$account_id] = (new EbayMessageHelp())->getCustomerIdByAccountId($account_id);
                        }
                        $group['customer_id'] = $this->tmp_customer_id[$account_id];

                        //把状态标记为已处理；
                        $group['status'] = 1;

                        //分组不存在，创建新的分组；
                        $group_id = $msgGroupModel->insertGetId($group);
                        if (empty($group_id)) {
                            throw new Exception('发送站内信时，新增分组失败');
                        }
                        $data['data']['group_id'] = $group_id;
                    } else {
                        $data['data']['group_id'] = $group['id'];
                        //如果分组数据是未处理，但是接收时间比发送邮件的时间要早，则，标记为已处理
                        if ($group['status'] == 0 && $group['last_receive_time'] < $data['data']['send_time']) {
                            $update['status'] = 1;
                            $update['update_time'] = time();

                            if (!empty($data['data']['local_order_id'])) {
                                $update['local_order_id'] = $data['data']['local_order_id'];
                            }
                            if (!empty($data['data']['transaction_id'])) {
                                $update['last_transaction_id'] = $data['data']['transaction_id'];
                            }
                            $msgGroupModel->update($update, ['id' => $group['id']]);
                        }
                    }

                    try {
                        Db::startTrans();
                        $id = $this->insertGetId($data['data']);
                        //html保存到mongodb;
                        $html = '';
                        if ($data['detaliData']) {
                            $data['detaliData']['id'] = $id;
                            //html不再保存到mysql,提出来保存到mongodb;
                            $html = $data['detaliData']['message_html'];
                            unset($data['detaliData']['message_html']);
                            $msgBodyModel->insert($data['detaliData']);
                        }
                        $this->saveHtmlToMongo($id, $html);
                        Db::commit();
                    } catch (Exception $e) {
                        Db::rollback();
                        throw new Exception($e->getMessage());
                    }
                } else {

                    if (!empty($data['data']['group_id'])) {
                        $group = $msgGroupModel->field('id,last_receive_time,status')->where(['id' => $data['data']['group_id']])->find();
                        //当存在分组数据时
                        if (!empty($group)) {
                            //如果分组数据是未处理，但是接收时间比发送邮件的时间要早，则，标记为已处理
                            if ($group['status'] == 0 && $group['last_receive_time'] < $data['data']['send_time']) {
                                $update['status'] = 1;
                                $update['update_time'] = time();

                                if (!empty($data['data']['local_order_id'])) {
                                    $update['local_order_id'] = $data['data']['local_order_id'];
                                }
                                if (!empty($data['data']['transaction_id'])) {
                                    $update['last_transaction_id'] = $data['data']['transaction_id'];
                                }
                                $msgGroupModel->update($update, ['id' => $group['id']]);
                            }
                        }
                    }

                    try {
                        Db::startTrans();
                        $this->update($data['data'], ['id' => $id]);
                        //html保存到mongodb;
                        //html不再保存到mysql,提出来保存到mongodb;
                        $html = $data['detaliData']['message_html'] ?? '';
                        unset($data['detaliData']['message_html']);
                        $msgBodyModel->update($data['detaliData'], ['id' => $id]);
                        //保存到mongodb;
                        $this->saveHtmlUpdateToMongo($id, $html);
                        Db::commit();
                    } catch (Exception $e) {
                        Db::rollback();
                        throw new Exception($e->getMessage());
                    }
                }
                $lock->unlockParams($lockParam);
                return true;
            } catch (exception $e) {
                $lock->unlockParams($lockParam);
                throw new Exception($e->getMessage());
            }
        }
        return false;
    }

    public function detail()
    {
        return $this->hasOne('EbayMessageBody', 'message_id', 'message_id');
    }

    /**
     * 修改数据
     *
     * @param array $data
     *            [description]
     * @return [type] [description]
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }

    /**
     * 批量修改
     *
     * @param array $data
     *            [description]
     * @return [type] [description]
     */
    public function editAll(array $data)
    {
        return $this->save($data);
    }

    /**
     * 查找数据
     *
     * @param array $param
     * @param string $field
     * @throws JsonErrorException
     * @return unknown
     */
    public function find($param = [], $field = 'id')
    {
        if (is_array($param)) {
            $result = $this->field($field)
                ->where($param)
                ->find();
        } else {
            $result = $this->field($field)
                ->where([
                    'id' => $param
                ])
                ->find();
        }
        if (empty($result)) { // 不存在
            throw new JsonErrorException('该数据不存在');
        }
        return $result;
    }

    /**
     * 检测数据是否存在
     *
     * @param number $id
     *            自增长id
     */
    public function isHas($id = 0)
    {
        $result = $this->field('id')
            ->where([
                'id' => $id
            ])
            ->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return true;
    }


}