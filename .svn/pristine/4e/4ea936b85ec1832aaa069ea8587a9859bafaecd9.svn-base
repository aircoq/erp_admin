<?php
namespace app\common\model\amazon;

use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;
use think\Model;
use think\Loader;
use think\Db;

class AmazonListing extends ErpModel
{
    use ModelFilter;
    protected $type = [
        'open_date'  =>  'timestamp:m/d/Y',
        'create_time' => 'timestamp',
        'modify_time' => 'timestamp',
    ];

    private $filterAccount = [];
    /**
     * listing过滤
     * @param Query $query
     * @param $params
     */
    public function scopeListing(Query $query, $params)
    {
        $this->filterAccount = array_merge($params, $this->filterAccount);
        if(!empty($params))
        {
            $query->where('__TABLE__.account_id','in', $this->filterAccount);
        }
    }

    /**
     * listing过滤
     * @param Query $query
     * @param $params
     */
    public function scopeDepartment(Query $query, $params)
    {
        $this->filterAccount = array_merge($params, $this->filterAccount);
        if(!empty($params))
        {
            $query->where('__TABLE__.account_id','in', $this->filterAccount);
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
    }

    /**
     * 部门过滤
     * @param Query $query
     * @param $params
     */
//    public function scopeDepartment(Query $query, $params)
//    {
//
//        if(!empty($params))
//        {
//            $accounts=[];
//            //用户列表
//            foreach ($params as $param)
//            {
//                $users = (new Department())->getDepartmentUser($param);
//
//                if($users)
//                {
//                    foreach ($users as $user)
//                    {
//                        if($user)
//                        {
//                            $where['seller_id']=['IN',$user];
//                            $account_list =MemberShipService::getChannelAccountsByUers($this->channel_id,$where);
//                            if($account_list)
//                            {
//                                $accounts = array_merge($accounts,$account_list);
//                            }
//                        }
//                    }
//                }
//            }
//
//            if(!empty($accounts))
//            {
//                $query->where('__TABLE__.shop_id','in',$accounts);
//            }
//        }
//    }
    
    /**
     * 新增
     * @param array $data [description]
     */
    public function add(array $data)
    {
        if (isset($data['id'])) {
            //检查是否已存在
            if ($this->check(['id' => $data['id']])) {
                return $this->edit($data, ['id' => $data['id']]);
            }
        }
        return $this->insertGetId($data);
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
     * 修改
     * @param  array $data [description]
     * @return [type]       [description]
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->isUpdate(true)->save($data, $where);
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

    public function getList($fields = '', array $join, array $where, $group = '', $page = 0, $pageSize = 50){
        if($pageSize > 0)
            $lists = $this->alias('e')->field($fields)->join($join)->where($where)->group($group)->page($page,$pageSize)->select();
        else
            $lists = $this->alias('e')->field($fields)->join($join)->where($where)->group($group)->select();
        return $lists;
    }

    public function amazonAccount()
    {
        return $this->belongsTo('amazon_account','account_id','id')->field('id,account_name');
    }

    public function skuSaledQty()
    {
        return $this->hasMany('amazon_order_detail','online_sku','seller_sku')->field(true);
    }

    /**
     * 是否有某商品ID
     * hasGoods
     * @param int $goods_id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getDetail($goods_id){
        if($goods_id > 0){
            $result = $this->where('goods_id',$goods_id)->find();
            if($result)
                $result->baseUrl = $this->_baseUrl;
            return $result;
        }
        return false;
    }

    public function addListing($data)
    {
        if (isset($data['description'])){
            unset($data['description']);
        }
        return $this->insertGetId($data);
    }

    public function editListing($data, $listing_id)
    {
        if (isset($data['description'])){
            unset($data['description']);
        }

        $this->allowField(true)->update($data, ['id'=>$listing_id]);
        return true;
    }
}
