<?php

namespace app\common\model\aliexpress;

use app\common\service\OrderStatusConst;
use app\common\traits\ModelFilter;
use app\customerservice\service\AliexpressHelp;
use app\customerservice\service\KeywordMatching;
use erp\ErpModel;
use org\bovigo\vfs\Issue104TestCase;
use think\db\Query;
use think\Model;
use think\Db;
use think\Exception;
use app\common\cache\Cache;

class AliexpressMsgRelation extends ErpModel
{
    use ModelFilter;
    protected $autoWriteTimestamp = true;
    
    //标签
    const MSG_RANK = [
        0=>'白',
        1=>'红',
        2=>'橙',
        3=>'绿',
        4=>'蓝',
        5=>'紫'
    ];
    //消息类型。message_center站内信；order_msg订单留言
    const TYPE_MESSAGE_CENTER = 1;
    const TYPE_ORDER_MSG = 2;
    
    const MSG_TYPE = [
        self::TYPE_MESSAGE_CENTER =>  'message_center',
        self::TYPE_ORDER_MSG      =>  'order_msg'
    ];
    //消息处理状态
    const DEAL_STATUS_FAIL      = 0;
    const DEAL_STATUS_SUCCESS   = 1;
    const MSG_DEAL_STATUS = [
        self::DEAL_STATUS_FAIL=>'待处理',
        self::DEAL_STATUS_SUCCESS=>'已处理'
    ];
    //消息是否已读
    const NO_READ      = 0;
    const IS_READ      = 1;
    const MSG_READ_STATUS = [
        self::NO_READ=>'未读',
        self::IS_READ=>'已读'
    ];

    //系统处理优先级
    const MSG_HANDLE_LEVEL = [
        1 => '红旗',
        2 => '紫旗',
        3 => '蓝旗', 
        4 => '墨绿',
        5 => '绿旗',
        6 => '橙旗',
        7 => '黄旗'
    ];

    private $filterAccount = [];

    /**
     * 调用AmazonAccount过滤
     * @param Query $query
     * @param $params
     */
    public function scopeAliexpressAccount(Query $query, $params)
    {
        $this->filterAccount = array_merge($params, $this->filterAccount);
        if(!empty($params))
        {
            $query->where('__TABLE__.aliexpress_account_id', 'in', $this->filterAccount);
        }
    }


    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }
    
    /**
     * 根据Aliexpress站内信标签获取站内信数量
     * @param int $rank
     * @param array $params 前台提交过来的查询条件
     * @return int
     */
    public static function getCountByRank($rank=-1,$params=null)
    {
        $map = [];
        if($rank>=0){
            $map = ['rank'=>$rank];
        }
        //标签
        if(isset($params['rank'])&&$params['rank']>=0){
            $map['rank'] = $params['rank'];
        }
        //处理状态
        if(isset($params['status'])&&$params['status']!==''){
            if($params['status']==2){
                $map['deal_status'] = 0;
                $map['msg_time'] = ['lt',  (time()-172800)];
            }else{
                $map['deal_status'] = $params['status'];
            }            
        }
        //标签
        if(isset($params['rank'])&&$params['rank']>=0){
            $map['rank'] = $params['rank'];
        }
        //消息类别
        if (isset($params['msg_type']) && $params['msg_type']) {
            switch ($params['msg_type']) {
                case 1:
                    $map['has_product'] = 1;
                    break;
                case 2:
                    $map['has_order'] = 1;
                    break;
                case 3:
                    $map['has_order'] = 1;
                    break;
                case 4:
                    $map['r.has_other'] = 1;
                    break;
                default:
                    break;
            }
        }
        //客服账号
        if(isset($params['customer_id'])&&$params['customer_id']){
            $map['owner_id'] = $params['customer_id'];
        }
        //处理优先级
        if(isset($params['level'])&&$params['level']){
            $map['level'] = $params['level'];
        }
        //是否已读        
        if(isset($params['read'])&&$params['read']!==''){
            $map['read_status'] = $params['read'];
        }
        //店铺账号
        if(isset($params['account_id'])&&$params['account_id']!==''){
            $map['aliexpress_account_id'] = $params['account_id'];
        }
        //关键字
        if(isset($params['filter_type']) && isset($params['filter_text']) && !empty($params['filter_text'])){
            switch ($params['filter_type'])
            {
                case 'order_id'://系统订单号
                    $order_id_arr = explode('-', $params['filter_text']);
                    $filter_id = isset($order_id_arr[1]) ? $order_id_arr[1] : $order_id_arr[0];
                    $ids = AliexpressMsgDetail::where(['type_id'=>['like',$filter_id.'%']])->column('aliexpress_msg_relation_id');
                    $map['id']=['in',$ids];
//                     $map['has_other'] = 1;
                    break;
                case 'channel_order_id'://平台订单号
                    $ids = AliexpressMsgDetail::where(['type_id'=>['like',$params['filter_text'].'%']])->column('aliexpress_msg_relation_id');
                    $map['id']=['in',$ids];
//                     $map['has_other'] = 1;
                    break;
                case 'buyer_id':
                    $map['other_login_id|other_name'] = ['like','%'.$params['filter_text'].'%'];
                    break;
                default :
                    break;
            }
        }
        return self::where($map)->count();
    }
    
    /**
     * 根据系统处理优先级获取站内信数量
     * @param int $level
     * @return int
     */
    public static function getCountByLevel($level=0)
    {
        $map = [];
        if($level>0){
            $map = ['level'=>$level];
        }
        return self::where($map)->count();
    }
    
    public function add($data,$accountId){
        $aliexpress_msg_relation_id = 0;
        $index = true;
        if(!empty($data['relation'])){
            Db::startTrans();
            try {
                /*
                 * 主表has_order、has_product、has_other字段值为0时，不更新
                 * wangwei 2018-9-20 19:04:51
                 */
                if(isset($data['relation']['has_order']) && $data['relation']['has_order']== 0){
                    unset($data['relation']['has_order']);
                }
                if(isset($data['relation']['has_product']) && $data['relation']['has_product'] == 0){
                    unset($data['relation']['has_product']);
                }
                if(isset($data['relation']['has_other']) && $data['relation']['has_other'] == 0){
                    unset($data['relation']['has_other']);
                }
                
                $model = $this->where(['channel_id'=>$data['relation']['channel_id']])->find();

                if(empty($model)){
                    if($accountId && !(isset($data['relation']['aliexpress_account_id']) && $data['relation']['aliexpress_account_id'])){
                        $data['relation']['aliexpress_account_id'] = $accountId;
                    }
                    $this->allowField(true)->save($data['relation']);
                    $aliexpress_msg_relation_id = $this->getData('id');
                    $this->detail()->saveAll($data['detail']);
                    /**
                     * 触发第一封站内信事件
                     */
                    $aliexpressHelp = new AliexpressHelp();
                    foreach ($data['detail'] as $detail_data)
                    {
                        $summary = json_decode($detail_data['summary'],true);
                        if (isset($data['relation']['other_login_id']) && $summary['sender_login_id']==$data['relation']['other_login_id']  && isset($detail_data['msg_id']) && isset($detail_data['channel_id']))
                        {
                            if ($index){
                                $index = false;
                                $aliexpressHelp->trigger_first_msg_event($data['relation']['other_login_id'], $detail_data['msg_id'],$detail_data['channel_id'], $accountId);
                            }
                            /**
                             * 关键词匹配
                             */
                            $where['msg_id'] = $detail_data['msg_id'];
                            $where['channel_id'] = $detail_data['channel_id'];
                            $re = $this->detail()->field('id')->where($where)->find();
                            $param = [
                                'channel_id'=>4,
                                'message_id'=>$re['id'],
                                'account_id'=>$accountId,
                                'message_type'=>0,
                                'buyer_id'=>$detail_data['sender_login_id'],
                                'receive_time'=>$detail_data['gmt_create'],
                            ];
                            $keywordMatching = new KeywordMatching();
                            $keywordMatching->keyword_matching($detail_data['content'],$param);
                        }
                    }
                }else {
                    $aliexpress_msg_relation_id = $model['id'];
                    //不更新消息客服负责人
                    unset($data['relation']['owner_id']);
                    $this->isUpdate(true)->save($data['relation'], ['id' => $model['id']]);
                    isset($data['detail']) && !empty($data['detail']) && $this->save_all($data['detail'], $model['id'], $accountId, $data['relation']['other_login_id'] ?? '');
                }
                Db::commit();
                return $aliexpress_msg_relation_id;
            } catch (Exception $ex) {
                Db::rollback();
                Cache::handler()->hSet('hash:aliexpress:message', $data['relation']['channel_id'] . ' ' . date('Y-m-d H:i:s', time()), '添加异常'. $ex->getMessage());
                throw new Exception($ex->getMessage());
            }
        }
    }

    /**
     * @param $datas
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function save_all($datas, $aliexpress_msg_relation_id, $accountId ,$other_login_id)
    {
        $aliexpressMsgDetail = new AliexpressMsgDetail();
        foreach ($datas as $data)
        {
            if(empty($data['msg_id']) || empty($data['channel_id'])){
                continue;
            }
            $has_con = [
                'msg_id'=>$data['msg_id'],
                'channel_id'=>$data['channel_id']
            ];
            $detailModel = $aliexpressMsgDetail->field('id')->where($has_con)->find();
            if($detailModel){
                $data['id'] = $detailModel['id'];
            }else {
                $data['aliexpress_msg_relation_id'] = $aliexpress_msg_relation_id;
            }
            (new AliexpressMsgDetail())->isUpdate(isset($data['id']) && $data['id'])->save($data);
            $id = $aliexpressMsgDetail->getLastInsID();
            $summary = json_decode($data['summary'],true);

            if (!$detailModel && $summary['sender_login_id']==$other_login_id)
            {
                /**
                 * 关键词匹配
                 */
                $param = [
                    'channel_id'=>4,
                    'message_id'=>$id,
                    'account_id'=>$accountId,
                    'message_type'=>0,
                    'buyer_id'=>$data['sender_login_id'],
                    'receive_time'=>$data['gmt_create'],
                ];
                $keywordMatching = new KeywordMatching();
                $keywordMatching->keyword_matching($data['content'],$param);
            }
        }
        return true;
    }

    public static function getType($typeStr)
    {
        $type = self::MSG_TYPE;
        $type = array_flip($type);
        return $type[$typeStr];
    }
    
    public function detail(){
        return $this->hasMany(AliexpressMsgDetail::class)->order('gmt_create desc');
    }
    
//    public function waitDistribution()
//    {
//        return $this->hasOne('app\common\model\Order','channel_order_number','channel_id')
//            ->where('status',OrderStatusConst::ForDistribution);
//    }
    
    
}

