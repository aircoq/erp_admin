<?php
namespace app\common\model\wish;

use think\Model;
use think\Loader;
use think\Db;

class WishPlatformOnlineGoods extends Model
{
    
    /**
     * 查询listing列表
     * @param array $wheres
     * @param string $fields
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getList($where, $page = 1, $pageSize = 20,$fields = '*')
    {   
        $goods_data = Db::table('wish_platform_online_goods_variation')->alias('v')
                ->join('wish_platform_online_goods g','v.product_id=g.product_id','LEFT')
                ->join('wish_account a ','g.account_id=a.id','LEFT')->where($where)->field($fields)->page($page,$pageSize)->select();
        return $goods_data;
    }
    
    /**
     * 查询产品总数
     * @param array $wheres
     * @return int
     */
    public function getCount($where)
    {   
        return Db::table('wish_platform_online_goods_variation')->alias('v')
                    ->join('wish_platform_online_goods g','v.product_id=g.product_id','LEFT')
                    ->join('wish_account a ','g.account_id=a.id','LEFT')->where($where)->count();
    }
    
    /**
     * 
     */
    public function getWhere($params)
    {
        $where = array();
       
        if (isset($params['status']) && !empty($params['status'])) 
        {
            $where['v.status']=  array('eq',$params['status']);
        }
        
        if (isset($params['parent_sku']) && !empty($params['parent_sku'])) 
        {   
             $where['g.parent_sku']=  array('eq',$params['parent_sku']);
        }
        
        if (isset($params['sku']) && !empty($params['sku'])) 
        {
            $where['v.sku']=  array('eq',$params['sku']);
        }
        
        if (isset($params['name']) && !empty($params['name'])) 
        {      
            $where['g.name']=  array('like',"%".$params['sku']."%");
        }
        
        if (isset($params['account_name']) && !empty($params['account_name'])) 
        {      
            $where['a.account_name']=  array('eq', $params['account_name']);
        }
        
        return $where;
    }
    /**
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /** 关系
     * @return \think\model\Relation
     */
    public function role()
    {
        //一对多的关系
        return $this->hasMany('WishPlatformOnlineOrder');
    }

    /** 新增产品
     * @param array $data
     * @return false|int
     */
    public function add(array $data)
    {
        if (isset($data['product_id'])) {
            //检查产品是否已存在
            if ($this->checkgoods(['product_id' => $data['product_id']])) {
                return $this->edit($data, ['product_id' => $data['product_id']]);
            }
        }
        return $this->insert($data);
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

    /** 修改产品
     * @param array $data
     * @param array $where
     * @return false|int
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }

    /** 批量修改
     * @param array $data
     * @return false|int
     */
    public function editAll(array $data)
    {
        return $this->save($data);
    }

    /** 检查产品是否存在
     * @param array $data
     * @return bool
     */
    protected function checkgoods(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }
}
