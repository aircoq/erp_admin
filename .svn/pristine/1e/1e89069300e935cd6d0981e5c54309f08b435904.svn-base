<?php
namespace app\customerservice\service;

use app\common\cache\Cache;
use app\common\model\ebay\EbayOrder;
use app\common\model\MsgTemplate as MsgTemplateModel;
use app\common\model\Order as OrderModel;
use app\common\exception\JsonErrorException;
use app\common\service\Order;
use app\customerservice\service\AmazonEmail as AmazonEmailService;
use app\common\model\OrderAddress;
use app\common\model\amazon\AmazonOrder as AmazonOrderModel;
use app\common\model\MsgTemplateGroup as MsgTemplateGroupModel;
use app\common\service\ChannelAccountConst;
use app\common\model\aliexpress\AliexpressOnlineOrder;

/**
 * Created by tb.
 * User: PHILL
 * Date: 2017/04/01
 * Time: 10:14
 */
/**
 * @module 客服管理
 * @title 回复模板
 */
class MsgTemplateHelp
{

    public function lists($params = [], $page = 1, $pageSize = 10)
    {
        $where = [];
        if (in_array(1,$params['channels'])){
            $where['channel_id'] = 1;
        }elseif (in_array(2,$params['channels'])){
            $where['channel_id'] = 2;
        }elseif (in_array(4,$params['channels'])){
            $where['channel_id'] = 4;
        }else{
            $result = [
                'data' => '',
                'page' => 1,
                'pageSize' => 10,
                'count' => 0
            ];
            return $result;
        }

        $where = $this->getWhere($params);

        $count = MsgTemplateModel::where($where)->count();
        $field = 'id,template_no,template_name,remark,template_type,template_group_id,template_content';
        $templateList = MsgTemplateModel::field($field)->where($where)
            ->page($page, $pageSize)
            ->select();

        $data = [];
        foreach ($templateList as $k => $v) {
            $group = MsgTemplateGroupModel::get($v['template_group_id']);
            $data[] = [
                'id' => $v['id'],
                'template_no' => $v['template_no'],
                'template_name' => $v['template_name'],
                'remark' => $v['remark'],
                'template_type' => MsgTemplateModel::TEM_TYPE[$v['template_type']],
                'group_name' => $group['group_name'],
                'content' => $v['template_content']
            ];
        }
        $result = [
            'data' => $data,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count
        ];

        return $result;
    }

    function getWhere($params = [])
    {
        $where = [];
        // 模板类型
        if (! empty($params['template_type'])) {
            $where['template_type'] = [
                'EQ',
                $params['template_type']
            ];
        }
        // 模板平台
        if (! empty($params['channel_id'])) {
            $where['channel_id'] = [
                'EQ',
                $params['channel_id']
            ];
        }
        // 模板分组
        if (! empty($params['group_id'])) {
            $where['template_group_id'] = [
                'EQ',
                $params['group_id']
            ];
        }
        // 模板编号
        if (! empty($params['search_key']) && ! empty($params['search_val'])) {
            $where[$params['search_key']] = [
                'LIKE',
                '%' . $params['search_val'] . '%'
            ];
        }
        
        // 模板名称
        if (! empty($params['template_name'])) {
            $where['template_name'] = [
                'LIKE',
                '%' . $params['template_name'] . '%'
            ];
        }
        
        return $where;
    }

    /**
     * 获取信息
     * 
     * @param number $id            
     */
    public function info($id = 0)
    {
        $result = MsgTemplateModel::field('*')->where([
            'id' => $id
        ])->find();
        $result = empty($result) ? [] : $result;
        return $result;
    }

    /**
     * 获取消息优先级列表
     * 
     * @return array
     */
    public function getTemplateType()
    {
        $lists = [];
        foreach (MsgTemplateModel::TEM_TYPE as $k => $list) {
            $lists[] = [
                'id' => $k,
                'name' => $list
            ];
        }
        return $lists;
    }

    /**
     * 获取指定平台模板列表
     * 
     * @param number $channelId
     *            平台id
     * @param number $tplType
     *            模板类型（评价、回复）
     * @param number $groupId
     *            分组id
     * @param number $type
     *            列表类型
     * @param string $page_size
     *            查询条数
     * @return unknown[][]
     */
    function getTemplates($channelId = 0, $tplType = 0, $groupId = 0, $type = 1, $pageSize = '')
    {
        $lists = [];
        // 获取所有模板
        if ($type == 1) {
            $lists = Cache::store('MsgTemplate')->getMsgTemplate();
        } elseif ($type == 2) {
            $time = Cache::store('MsgTemplate')->useCountLastTime();
            if ($time + 24 * 3600 < time()) {
                // 默认1天更新一次统计缓存
                Cache::handler()->del('cache:frequentlyUsedMsgTpl'); // 大于1天重新更新
            }
            $lists = Cache::store('MsgTemplate')->getFrequentlyUsedTpl();
        }
        
        $filter = 0;
        
        if ($channelId) {
            $where[] = [
                "channel_id",
                "==",
                $channelId
            ];
            $filter = 1;
        }
        
        if ($tplType) {
            $where[] = [
                "template_type",
                "==",
                $tplType
            ];
            $filter = 1;
        }
        
        // 查询指定分组
        if ($groupId) {
            $where[] = [
                "template_group_id",
                "==",
                $groupId
            ];
            $filter = 1;
        }
        
        if ($filter == 1) {
            $lists = Cache::filter($lists, $where);
        }
        
        // 查询指定条数模板
        if (! empty($pageSize)) {
            $count = count($lists);
            $lists = Cache::page($lists, 1, $pageSize);
        }
        
        $result = [];
        foreach ($lists as $key => $vo) {
            $data = [];
            $data['template_id'] = $vo['id'];
            $data['template_no'] = $vo['template_no'];
            $data['template_name'] = $vo['template_name'];
            $result[] = $data;
        }
        unset($lists);
        unset($data);
        return $result;
    }

    /**
     * 获取所有平台模板列表
     * 
     * @param number $tplType            
     */
    function getAllTemplates($tplType = 0)
    {
        $lists = [];
        // 获取所有模板
        $lists = Cache::store('MsgTemplate')->getMsgTemplate('');
        
        $where[] = [
            "template_type",
            "==",
            $tplType
        ];
        $channel_list = Cache::store('channel')->getChannel();
        
        $result = [];
        $tml_resutl = [];
        foreach ($lists as $key => $vo) {
            if ($tplType && $tplType != $vo['template_type'] && $tplType != 3) {
                continue;
            }
            
            $tml_resutl[$vo['channel_id']][] = [
                'template_id' => $vo['id'],
                'template_no' => $vo['template_no'],
                'template_name' => $vo['template_name']
            ];
        }
        
        foreach ($channel_list as $key => $vo) {
            $result[$vo['id']] = [
                'channel_id' => $vo['id'],
                'channel_name' => $vo['name'],
                'lists' => isset($tml_resutl[$vo['id']]) ? $tml_resutl[$vo['id']] : []
            ];
        }
        
        return $result;
    }

    /**
     * 获取指定平台模板分组列表
     * 
     * @param number $channel_id
     *            平台id
     * @param number $template_type
     *            模板类型（1-回复模板 ，2-评价模板）
     */
    public function getTplGroup($channel_id = 0, $template_type = 0)
    {
        $list = [];
        $list = Cache::store('MsgTemplate')->getMsgTemplateGroup();
        // 查询指定分组
        $filter = 0;
        if ($template_type) {
            $where[] = [
                "template_type",
                "==",
                $template_type
            ];
            $filter = 1;
        }
        
        if ($channel_id) {
            $where[] = [
                "channel_id",
                "==",
                $channel_id
            ];
            $filter = 1;
        }
        
        if ($filter == 1) {
            $list = Cache::filter($list, $where);
        }
        
        $result = [];
        foreach ($list as $vo) {
            $result[] = $vo;
        }
        
        return $result;
    }

    /**
     * 获取模板数据字段列表
     * 
     * @return array
     */
    public function getFieldDatas($channel_id = 0, $tpl_type = 0)
    {
        $list = [];
        $list = Cache::store('MsgTemplate')->getMsgTplField();
        // 查询指定分组
        $filter = 0;
        if ($tpl_type) {
            $where[] = [
                "template_type",
                "==",
                $tpl_type
            ];
            $filter = 1;
        }
        
        if ($channel_id) {
            //速卖通指定条件字段
            if($channel_id == ChannelAccountConst::channel_aliExpress){
                $where[] = ["channel_id", "==", $channel_id];
            }else{
                $where[] = ["channel_id", "in", [$channel_id,0]];
            }
            $filter = 1;
        }
        
        if ($filter == 1) {
            $list = Cache::filter($list, $where);
        }
        $result = [];
        foreach ($list as $vo) {
            $result[] = [
                'field_key' => $vo['field_key'],
                'field_val' => $vo['field_value'],
                'field_db' => $vo['field_db']
            ];
        }
        
        return $result;
    }

    /**
     *
     * @param number $template_id
     *            模板id
     * @param string $field
     *            查找字段
     * @return unknown
     */
    function get($template_id = 0, $field = 'id')
    {
        $result = MsgTemplateModel::field($field)->where([
            'id' => $template_id
        ])->find();
        return $result;
    }

    /**
     *
     * @param number $template_id
     *            模板id
     * @param string $field
     *            查找字段
     * @return unknown
     */
    function check($template_id = 0, $field = 'id')
    {
        $result = MsgTemplateModel::field($field)->where([
            'id' => $template_id
        ])->find();
        if (empty($template_id)) {
            throw new JsonErrorException('模板不存在！');
        }
        return $result;
    }

    /**
     * 替换模板内容中的数据字段field
     * 姓名：${name} to 姓名：AAA
     * 
     * @param int $tplId            
     * @param array $data            
     * @return string $tpl_content
     */
    public function getTplFieldContent($tplId = 0, $data = [])
    {
        $tpl_info = (new MsgTemplateModel())->find($tplId);

        // 清楚缓存 暂时没有页面用于临时清除
        Cache::handler()->del('cache:msgTplField');
        
        $key = $tpl_info['channel_id'] . '-' . $tpl_info['template_type'];
        $fieldLists = Cache::store('MsgTemplate')->getMsgTplField($key);
        $tpl_content = $tpl_info['template_content']; // 获取内容
                                                      
        // 处理$data 与field字段匹配
        $data = $this->handelDataField($fieldLists, $data);

        foreach ($fieldLists as $vo) {
            $field = $vo['field_key'];
            if (isset($data[$field]) && ! empty($data[$field])) {
                $tpl_content = str_replace('${' . $field . '}', $data[$field], $tpl_content);
            } else {
                // 没有的值替换为空
                $tpl_content = str_replace('${' . $field . '}', ' ', $tpl_content);
            }
        }
        return $tpl_content;
    }

    /**
     * 处理数组，对template_field 键值匹配
     * 
     * @param array $fieldLists            
     * @param array $data            
     * @return string[]|unknown[]
     */
    function handelDataField($fieldLists = [], $data = [])
    {
        $result = [];
        if (! empty($fieldLists) && ! empty($data)) {
            foreach ($fieldLists as $vo) {
                $result[$vo['field_key']] = isset($data[$vo['field_db']]) ? $data[$vo['field_db']] : '';
            }
        }
        return $result;
    }

    /**
     * 根据模板编号查找模板信息
     * 
     * @param unknown $template_no
     * @return unknown
     */
    function getTplByTplNo($template_no = '')
    {
        $result = [];
        $model = new MsgTemplateModel();
        $result = $model->where([
            'template_no' => $template_no
        ])->find();
        return $result;
    }

    /**
     * 匹配数据，获取内容
     * 
     * @param array $params            
     * @return string
     */
    function matchTplContent($params = [])
    {
        $template_id = param($params, 'template_id', 0);
        $channel_id = param($params, 'channel_id', 0);

        $data = [];
        if (param($params, 'transform') == 1) {
            $search_type = param($params, 'search_type');
            $search_id = param($params, 'search_id');
            
            switch ($search_type) {
                case 'order':
                case 'channel_order':
                    $data = $this->getOrderData($search_id, $channel_id, $search_type);
                    break;
                case 'msg':
                    $data = $this->getMsgData($search_id, $channel_id);
                    break;
                case 'evaluate':
                    $data = $this->getEvaluateData($search_id, $channel_id);
                    break;
                default:
                    break;
            }
        }

        // 通过模板编号查找模板ID
        if (empty($template_id) && param($params, 'template_no')) {
            // 通过模板编号查找模板id
            $info = $this->getTplByTplNo($params['template_no']);
            $template_id = $info['id'];
        }
        
        // 随机获取模板编号
        if (empty($template_id) && ! param($params, 'template_no') && param($params, 'is_random')) {
            $where['template_type'] = 2;
            $where['channel_id'] = $channel_id;
            $tmp = MsgTemplateModel::field('id')->where($where)
                ->order('rand()')
                ->find();
            $template_id = $tmp['id'];
        }
        // 获取替换字段后的内容
        return $this->getTplFieldContent($template_id, $data);
    }

    /**
     * 获取站内信/邮件匹配内容
     * 
     * @param string $order_id            
     * @param number $channel_id            
     * @return unknown[]
     */
    function getMsgData($search_id = 0, $channel_id = 0)
    {
        if (empty($search_id) || empty($channel_id)) {
            throw new JsonErrorException('参数错误！');
        }
        $data = [];
        switch ($channel_id) {
            case 1:
                $ebayMsgService = new EbayMessageHelp();
                $data = $ebayMsgService->matchFieldData($search_id);
                break;
            case 2:
                $amazonEmailService = new AmazonEmailService();
                $data = $amazonEmailService->matchFieldData($search_id);
                break;
            case 3:
                break;
            case 4:
                $aliexpressService = new AliexpressHelp();
                $data = $aliexpressService->matchFieldData($search_id);
                break;
            default:
                break;
        }
        return $data;
    }

    /**
     * 获取评价匹配内容
     * 
     * @param string $order_id            
     * @param number $channel_id            
     * @return unknown[]
     */
    function getEvaluateData($order_id = '', $channel_id = 0)
    {
        $data = [];
        return $data;
    }

    /**
     * 获取订单匹配内容
     * 
     * @param string $order_id            
     * @param number $channel_id            
     * @return unknown[]
     */
    function getOrderData($order_id = '', $channel_id = 0, $search_type = 'order')
    {
        $where = [];
        if ($search_type == 'order') {
            $where['id'] = [
                'EQ',
                $order_id
            ];
        } elseif ($search_type == 'channel_order') {
            $where['channel_order_number'] = [
                'EQ',
                $order_id
            ];
        }
        $order = OrderModel::where($where)->find();
        if (empty($order) && $search_type == 'order') {
            throw new JsonErrorException('订单不存在');
        }
        // 系统表有数据，就从系统表读取数据
        if ($order) {
            $orderAddressModel = new OrderAddress();
            $order_address = $orderAddressModel->field('*')
                ->where([
                'order_id' => $order['id']
            ])
                ->find();
            $order['consignee'] = $order_address['consignee'];
            $order['country_code'] = $order_address['country_code'];
            $order['province'] = $order_address['province'];
            $order['city'] = $order_address['city'];
            $order['address'] = $order_address['address'];
        }

        // 系统表无数据，从平台读取数据
        if (empty($order) && $search_type == 'channel_order') {
            $order = [
//                 'channel_account_id'=>'',//账号id
//                 'channel_order_number'=>'',//渠道订单号
//                 'buyer_id'=>'',//买家id
//                 'buyer_name'=>'',//买家名称
//                 'pay_time'=>'',//支付时间
//                 'order_amount'=>'',//订单总金额
//                 'consignee'=>'',//收货人姓名
//                 'country_code'=>'',//收货地址--国家
//                 'province'=>'',//收货地址--省
//                 'city'=>'',//收货地址--市
//                 'address'=>'',//收货地址--地址行
            ];
            switch ($channel_id) {
                case ChannelAccountConst::channel_ebay:
                    $ebay_field = 'account_id as channel_account_id,order_id as channel_order_number,buyer_user_id as buyer_id,shipping_address_name as buyer_name,
                        payment_time as pay_time,payment_amount as order_amount,shipping_address_name as consignee,shipping_address_country as country_code,
                        shipping_address_state_or_province as province,shipping_address_city_name as city,shipping_address_street1 as address, shipping_address_street2 as address2';
                    $ebay_where = [
                        'order_id' => $order_id
                    ];
                    $order = EbayOrder::field($ebay_field)->where($ebay_where)->find();
                    $order['address'] .= ' ' . $order['address2'];
                    break;
                case ChannelAccountConst::channel_amazon:
                    $amz_field = 'account_id as channel_account_id,order_number as channel_order_number,email as buyer_id,user_name as buyer_name,payment_time as pay_time,
                         actual_total as order_amount, user_name as consignee,country as country_code,state as province,address1 as address';
                    $amz_where = [
                        'order_number' => $order_id
                    ];
                    $order = AmazonOrderModel::field($amz_field)->where($amz_where)->find();
                    break;
                case ChannelAccountConst::channel_wish:
                    
                    break;
                case ChannelAccountConst::channel_aliExpress:
                    $ali_field = 'account_id as channel_account_id,order_id as channel_order_number,buyer_login_id as buyer_id,buyer_signer_fullname as buyer_name,
                         gmt_pay_time as pay_time,(pay_amount->"$.amount") as order_amount,receipt_address';
                    $ali_row = AliexpressOnlineOrder::field($ali_field)->where('order_id',$order_id)->find();
                    $address = json_decode($ali_row['receipt_address'], true);
                    $order = [
                        'channel_account_id'=>$ali_row['channel_account_id'],//账号id
                        'channel_order_number'=>$ali_row['channel_order_number'],//渠道订单号
                        'buyer_id'=>$ali_row['buyer_id'],//买家id
                        'buyer_name'=>$ali_row['buyer_name'],//买家名称
                        'pay_time'=>$ali_row['pay_time'],//支付时间
                        'order_amount'=>$ali_row['order_amount'],//订单总金额
                        'consignee'=>$address && !empty($address['contactPerson']) ? $address['contactPerson'] : '',//收货人姓名
                        'country_code'=>$address && !empty($address['country']) ? $address['country'] : '',//收货地址--国家
                        'province'=>$address && !empty($address['province']) ? $address['province'] : '',//收货地址--省
                        'city'=>$address && !empty($address['city']) ? $address['city'] : '',//收货地址--市
                        'address'=>$address && !empty($address['detailAddress']) ? $address['detailAddress'] : '',//收货地址--地址行
                    ];
                    if($address && !empty($address['address2'])){
                        $order['address'] .= ' ' . $address['address2'];
                    }
                    break;
                default:
                    break;
            }
            if (empty($order)) {
                throw new JsonErrorException('平台订单不存在');
            }
        }

        // 获取卖家邮件
        $seller_email = '';
        $seller_id = '';
        if (param($order, 'channel_account_id')) {
            switch ($channel_id) {
                case ChannelAccountConst::channel_ebay:
                    $account = Cache::store('EbayAccount')->getTableRecord($order['channel_account_id']);
                    break;
                case ChannelAccountConst::channel_amazon:
                    $account = Cache::store('AmazonAccount')->getAccount($order['channel_account_id']);
                    break;
                case ChannelAccountConst::channel_wish:
                    $account = Cache::store('account')->wishAccount($order['channel_account_id']);
                    break;
                case ChannelAccountConst::channel_aliExpress:
                    $account = Cache::store('AliexpressAccount')->getTableRecord($order['channel_account_id']);
                    break;
                default:
                    break;
            }
            $seller_email = param($account, 'email');
            $seller_id = param($account, 'account_name');
        }

        $data = [];
        $data = [
            'buyer_id' => $order['buyer_id'], // 买家id
            'buyer_name' => param($order, 'buyer'), // 买家名
            'seller' => param($order, 'seller'),
            'seller_id' => $seller_id, // 卖家id
            'seller_email' => $seller_email, // 卖家email
            'order_id' => $order['channel_order_number'],// 平台订单id
            'payment_date' => param($order, 'pay_time') ? date('Y-m-d H:i:s', $order['pay_time']) : '', // 支付时间
            'delivery_date' => param($order, 'shipping_time') ? date('Y-m-d H:i:s', $order['shipping_time']) : '', // 发货时间
            'amount' => $order['order_amount'], // 订单金额
            'carrier' => param($order, 'synchronize_carrier'), // 物流商
            'shipping_number' => param($order, 'synchronize_tracking_number'), // 跟踪号
            'recipient_name' => param($order, 'consignee'), // 收货人姓名
            'recipient_address' => param($order, 'country_code') . ' ' . param($order, 'province') . ' ' . param($order, 'city') . ' ' . param($order, 'address')// 收货人地址
        ];
        return $data;
    }
}