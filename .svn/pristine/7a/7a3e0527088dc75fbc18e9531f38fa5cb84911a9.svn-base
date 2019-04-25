<?php
namespace app\common\model\ebay;

use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;
use think\Model;
use think\Db;
use think\Exception;

class EbayCase extends ErpModel
{
    
    //纠纷类型 01-EBP未收到货品 02-未收到货品普通纠纷 11-货物与描述不符EBP纠纷 12-货物与描述不符的普通纠纷 21-买家未付款 31-卖家取消交易',
    //请求类型
    const  EBAY_CASE_NOTRECIVE_EBP     = '01';
    const  EBAY_CASE_NOTRECIVE         = '02';
    const  EBAY_CASE_NOTASDES          = '11';
    
    //没有收到货物 状态
    static  $NOTRECIVE_STATE = [
        'OTHER'                 =>  [
            'ON_HOLD',
            'OTHER',
            'WAITING_BUYER_RESPONSE',
        ],
        'CLOSED'               =>  [
            'CLOSED',
            'CS_CLOSED',
            'CLOSED_WITH_ESCALATION',
            'WAITING_CS',
        ],
        'SELLER_WATTING'       =>  [
            'OPEN',
            'REFUND_AGREED_BUT_FAILED',
            'WAITING_DELIVERY',
            'WAITING_SELLER_RESPONSE'
        ]
    ];
    
    // case 状态
    static  $CASE_STATE = [
        'OPEN'                 =>  'OPEN',
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
        $base = $data['data'];
        $history = $data['history'];
        $caseHistoryModel = new EbayCaseResponseHistory();
        if (isset($base['case_id'])) {
            Db::startTrans();
            try {
                //检查是否已存在
                $info = $this->where(['case_id'=>$base['case_id']])->find();
                if(empty($info)){
                    //插入历史消息
                    if($history){
                        $res = $caseHistoryModel->addAll($history);
                    }
                    //note_recive_item 只进行修改状态
                    if($base['case_type']!='01'){
                         $res = $this->insert($base);
                    }
                    
                }else{
                   
                     if($base['case_type']=='01'){
                        //升级not_recive_item 只修改状态
                        $update_data['case_type'] = '01';
                        $this->update($update_data, ['id'=>$info['id']]);
                    }else{
                        $this->update($base, ['id'=>$info['id']]);
                    }
                }
                Db::commit();
                return true;
            } catch (Exception $ex) {
                Db::rollback();
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
    

 
}
