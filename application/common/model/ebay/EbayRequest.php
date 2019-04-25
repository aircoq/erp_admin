<?php
namespace app\common\model\ebay;

use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;
use think\Db;
use think\Exception;

class EbayRequest extends ErpModel
{
    
    //请求类型
    const  EBAY_REQUEST_CANCEL     = 1;
    const  EBAY_REQUEST_RETURN     = 2;

    //纠纷数据-对应远程模块
    const  EBAY_DATA_CANCEL       = 'Cancel';
    const  EBAY_DATA_CASE         = 'Case';
    const  EBAY_DATA_RETURN       = 'Return';
    const  EBAY_DATA_INQUIRY      = 'Inquiry';
    
    //纠纷数据 - 对应的表
    static  $EBAY_DATA_TABLE = [
        self::EBAY_DATA_CANCEL       =>  'request',
        self::EBAY_DATA_CASE         =>  'case',
        self::EBAY_DATA_RETURN       =>  'request',
        self::EBAY_DATA_INQUIRY      =>  'case'
    ];
    
    //纠纷类型
    const  EBAY_DISPUTE_CANCEL       = 'CANCEL';
    const  EBAY_DISPUTE_NOTRECIVE    = 'NOTRECIVE';
    const  EBAY_DISPUTE_RETURN       = 'RETURN';
    const  EBAY_DISPUTE_NOTPAID      = 'NOTPAID';
    const  EBAY_DISPUTE_ESCALATE     = 'ESCALATE';
    
    
    //纠纷类型描述
    static  $DISPUTE_TYPE = [
        self::EBAY_DISPUTE_CANCEL       =>  '取消交易',
        self::EBAY_DISPUTE_NOTRECIVE    =>  '未收到货',
        self::EBAY_DISPUTE_RETURN       =>  '退货退款',
        self::EBAY_DISPUTE_NOTPAID      =>  '未付款',
        self::EBAY_DISPUTE_ESCALATE     =>  '升级'
    ];
    
    //纠纷类型描述
    static  $EBAY_TYPE = [
        self::EBAY_DISPUTE_CANCEL       =>  self::EBAY_DATA_CANCEL,
        self::EBAY_DISPUTE_NOTRECIVE    =>  self::EBAY_DATA_INQUIRY,
        self::EBAY_DISPUTE_RETURN       =>  self::EBAY_DATA_RETURN,
        self::EBAY_DISPUTE_NOTPAID      =>  self::EBAY_DATA_CANCEL,
        self::EBAY_DISPUTE_ESCALATE     =>  self::EBAY_DATA_CASE
    ];
    
    //状态
    static  $DISPUTE_STATE = [
        'CLOSED'               =>  '关闭',
        'OPEN'                 =>  '未关闭',
        'SELLER_WATTING'       =>  '等待卖家响应',
        'OTHER'                =>  '其它'
    ];
    
    //return 状态 CLOSED
    static  $RETURN_STATE = [
        'CLOSED'                =>  [
            'CLOSED',
            'ESCALATED',
            'REPLACED',
            'REPLACEMENT_CLOSED',
            'REPLACEMENT_DELIVERED',
            'RETURN_REJECTED',
        ],
        'SELLER_WATTING'        =>  [
            'WAITING_FOR_RMA',  //该值表示退货当前正在等待卖方向买方提供退货授权
            'WAITING_FOR_RETURN_LABEL', //此值表示退货当前正在等待退货运输标签可用

            'RETURN_REQUESTED', //该值表示买方已请求退货。
            'RETURN_REQUESTED_TIMEOUT', //该值表示买方已请求退货。

            'RETURN_LABEL_REQUESTED', //此值表示买方已请求退货运输标签。
            'REPLACEMENT_WAITING_FOR_RMA', //该值表示卖方仍需要为替换物品提供退货授权。

            'REPLACEMENT_REQUESTED', //该值表示卖方仍需要为替换物品提供退货授权。
            'PARTIAL_REFUND_FAILED', //该值表示卖方仍需要为替换物品提供退货授权。
            'PARTIAL_REFUND_DECLINED', //该值表示卖方仍需要为替换物品提供退货授权。
            'ITEM_SHIPPED', //该值表示卖方仍需要为替换物品提供退货授权。
            'ITEM_DELIVERED', //该值表示卖方仍需要为替换物品提供退货授权。
        ]
    ];
    
    //return 状态 CLOSED
    static  $CANCEL_STATE = [
        'CLOSED'                =>  'CLOSED'
    ];


    use ModelFilter;

    private $filterAccount = [];

    /**
     * 调用EbayAccountFilter过滤
     * @param Query $query
     * @param $params
     */
    public function scopeEbayAccount(Query $query, $params)
    {
        $this->filterAccount = array_merge($params[0], $this->filterAccount);
        if(!empty($this->filterAccount))
        {
            $query->where('__TABLE__.account_id', 'in', $this->filterAccount);
        }
    }

    /**
     * 调用EbayDepartmentFilter过滤
     * @param Query $query
     * @param $params
     */
    public function scopeDepartment(Query $query, $params)
    {
        $this->filterAccount = array_merge($params, $this->filterAccount);
        if(!empty($this->filterAccount))
        {
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
    
    /**
     * 新增
     * @param array $data [description]
     */
    public function add(array $data)
    {
        if (isset($data['request_id'])) {
            try {
            //检查是否已存在
                $info = $this->where(['account_id' => $data['account_id'], 'request_id'=>$data['request_id'],'request_type'=>$data['request_type']])->find();
                if(empty($info)){
                     $this->insert($data);
                }else{
                    //更新
                    $this->update($data, ['id'=>$info['id']]);
                }
                return true;
            } catch (Exception $ex) {
                throw new Exception($ex->getMessage());
            }
            
        }
        return false;
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
     * 修改
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
     * 检查是否存在
     * @return [type] [description]
     */
    protected function check(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    public static function getDisputeType($type)
    {
        if ($type === self::EBAY_REQUEST_CANCEL) {
            return self::EBAY_DISPUTE_CANCEL;
        }
        if ($type === self::EBAY_REQUEST_RETURN) {
            return self::EBAY_DISPUTE_RETURN;
        }
        if ($type === EbayCase::EBAY_CASE_NOTRECIVE) {
            return self::EBAY_DISPUTE_NOTRECIVE;
        }
        if ($type === EbayCase::EBAY_CASE_NOTASDES) {
            return self::EBAY_DISPUTE_ESCALATE;
        }
        if ($type === EbayCase::EBAY_CASE_NOTRECIVE_EBP) {
            return self::EBAY_DISPUTE_ESCALATE;
        }

        return '-';
    }
 
}
