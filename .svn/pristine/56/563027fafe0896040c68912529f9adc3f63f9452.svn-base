<?php
namespace app\common\model\aliexpress;
use app\common\traits\ModelFilter;
use app\index\service\Department;
use app\index\service\MemberShipService;
use erp\ErpModel;
use think\db\Query;
use think\Model;
class AliexpressAccountCategoryPower extends ErpModel
{
    use ModelFilter;
    /**
     * listing过滤
     * @param Query $query
     * @param $params
     */
    public function scopeListing(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.account_id', 'in', $params);
        }
    }

    /**
     * 部门过滤
     * @param Query $query
     * @param $params
     */
    public function scopeDepartment(Query $query, $params)
    {
        if (!empty($params)) {
            $accounts = [];
            //用户列表
            foreach ($params as $param) {
                $users = (new Department())->getDepartmentUser($param);

                if ($users) {
                    foreach ($users as $user) {
                        if ($user) {
                            $where['seller_id'] = ['IN', $user];
                            $account_list = MemberShipService::getChannelAccountsByUsers(3, $where);
                            if ($account_list) {
                                $accounts = array_merge($accounts, $account_list);
                            }
                        }
                    }
                }
            }
            if (!empty($accounts)) {
                $query->where('__TABLE__.account_id', 'in', $accounts);
            }
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
    //关联
    public function  account()
    {
        return $this->hasOne(AliexpressAccount::class, 'id', 'account_id')
                ->field('id,code,account_name')
                ->setEagerlyType(1);
    }
    public  function alicategory()
    {
        return $this->hasOne(AliexpressCategory::class, 'category_id', 'category_id')->field('category_id,category_name_zh')->setEagerlyType(1);
    }
    public  function localcategory()
    {
        return $this->hasOne(\app\common\model\Category::class,'id','local_category_id')->field('id,name local_name');
    }
    //关联end
    /**
     * 新增授权分类
     * @param array $data
     * @return string|int
     */
    public  function add($data)
    {
        $where = ['account_id'=>$data['account_id'],'category_id'=>$data['category_id'],'local_category_id'=>$data['local_category_id']];
        if($this->check($where))
        {
            $data['updatetime'] = time();
            $message = $this->isUpdate(true)->allowField(true)->update($data,$where);
        }else{
            $data['addtime'] = time();
            $message =  $this->isUpdate(false)->allowField(true)->insert($data);
        }
        return $message;
    }
    
    /**
     * 新增授权分类
     * @param array $data
     * @return string|int
     */
    public  function edit($data)
    {
        if(isset($data['id']))
        {
            $where = ['id'=>$data['id']];
        }else{
            $where = ['account_id'=>$data['account_id'],'category_id'=>$data['category_id'],'local_category_id'=>$data['local_category_id']];
        } 
        
        if($this->check($where))
        {
            $data['updatetime'] = time();
            $message = $this->isUpdate(true)->allowField(true)->update($data,$where);
        }else{
            $data['addtime'] = time();
            $message =  $this->isUpdate(false)->allowField(true)->insert($data);
        }
        return $message;
    }
    /**
     * 批量新增授权分类
     * @param type $data
     */
    public  function addAll($data)
    {
        return $this->saveAll($data);
    }
    /**
     * 查询是否已经存在
     * @param array $where
     * @return boolean
     */
    public function check($where)
    {
        if($this->where($where)->find())
        {
            return true;
        }else{
            return false;
        }
    }
    
   
}