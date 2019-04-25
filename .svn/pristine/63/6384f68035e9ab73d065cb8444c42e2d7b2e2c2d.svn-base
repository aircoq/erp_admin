<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\common\model\wish;

use app\common\traits\ModelFilter;
use app\index\service\AccountUserMapService;
use app\index\service\Department;
use app\index\service\MemberShipService;
use erp\ErpModel;
use think\Model;
use think\Db;
use think\db\Query;
use think\Model\Merge;
/**
 * Description of WishWaitUploadProduct
 *
 * @author RondaFul
 */
class WishWaitUploadProduct extends ErpModel
{
    use ModelFilter;
   /**
     * 初始化
     */
    /*const  APPROVED=1; 
    const REJECTED=2; 
    const PENDING=3;
    const PRODUCT_STATUS = [
        self::APPROVED=>'已批准',
        self::REJECTED=>'被拒绝',
        self::PENDING=>'待审核',
    ];*/
	protected $pk='id';

	protected $rule = [
        'type' => 'mod', // 分表方式
        'num'  => 50     // 分表数量
    ];

    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /**
     * listing过滤
     * @param Query $query
     * @param $params
     */
    public function scopeListing(Query $query, $params)
    {

        if(!empty($params))
        {
            $query->where('__TABLE__.accountid','in',$params);
        }
    }

    /**
     * 部门过滤
     * @param Query $query
     * @param $params
     */
    public function scopeDepartment(Query $query, $params)
    {

        if(!empty($params))
        {
            $accounts=[];
            //用户列表
            foreach ($params as $param)
            {
                $users = (new Department())->getDepartmentUser($param);

                if($users)
                {
                    foreach ($users as $user)
                    {
                        if($user)
                        {
                            $where['seller_id']=['IN',$user];
                            $account_list =MemberShipService::getChannelAccountsByUsers(3,$where);
                            if($account_list)
                            {
                                $accounts = array_merge($accounts,$account_list);
                            }
                        }
                    }
                }
            }

            if(!empty($accounts))
            {
                $query->where('__TABLE__.accountid','in',$accounts);
            }
        }
    }
    
    public function getIdAttr($value)
    {
        return (string)$value;
    }

    public function setIsPromotedAttr($v)
    {
        if($v)
        {
            return 1;
        }else{
            return 0;
        }
    }
    
    public function getReviewStatusAttr($value)
    {
        if($value=='1') //approved
        {
           $value= '已批准';
        }elseif($value=='2'){ //rejected
           $value= '被拒绝';
        }elseif($value=='3'){ //pending
            $value= '待审核';
        }
        return $value;
    }

    /**
     * 新增产品信息
     * @param type $data
     * @return boolean
     */
    public function add($data)
    {
        if (!isset($data['product_id'])) {
            return false;
        }
        
        //检查订单是否已存在
        if ($this->checkProduct(['product_id' => $data['product_id']])) {
            $this->edit($data, ['product_id' => $data['product_id']]);
        }else{
            $data['addtime'] = time();
            time_partition(__CLASS__,$data['addtime']);
            $this->allowField(true)->isUpdate(false)->save($data);
        }
    }
    
    /** 修改产品信息
     * @param array $data
     * @param array $where
     * @return false|int
     */
    public function edit(array $data, array $where)
    {
        return $this->isUpdate(true)->allowField(true)->save($data, $where);
    }
    
    public function  checkProduct($where)
    {
        $result = $this->get($where);
        if (!empty($result)) {
            return true;
        }
        return false;
    }


    /** 新增
     * @param array $data
     * @return false|int
     */
    public function addData(array $data)
    {
        if (isset($data['goods_id']) && isset($data['accountid'])) 
        {
            //检查产品是否已存在
            if($data['goods_id']>0)
            {
                $where['goods_id'] = ['eq',$data['goods_id'] ];
            }elseif(isset($data['parent_sku']) && $data['parent_sku']) {
                $where['parent_sku'] =  ['eq',$data['parent_sku'] ];
            }
            $where['accountid']=['eq',$data['accountid']];
            
            if ($this->check($where)) 
            {
                $accountInfo = (new WishAccount())->where('id','=',$data['accountid'])->find();
                
                $response =['state'=>false,'message'=>'账号['.$accountInfo['account_name'].']已经刊登过,请不要重复刊登'];
   
            }else{//不存在，则插入

                //->partition(['accountid'=>$data['accountid']],'accountid',$this->rule)
                $res = $this->allowField(true)->isUpdate(false)->save($data);
                if($res)
                {
                    $response =['state'=>true,'message'=>''];
                }else{
                    $response =['state'=>false,'message'=>$this->getError()];
                }            
            }
        }
        return $response;
    }
    
    
    /** 更新数据
     * @param array $data
     * @return false|int
     */
    public function updateData(array $data)
    {
        if (isset($data['id']) && isset($data['accountid'])) 
        {
            //检查产品是否已存在
            if ($this->check(['id' => $data['id'],'accountid'=>$data['accountid']])) 
            {
                //update set isUpdate true
                $where=[
                    'id'=>$data['id'],
                    'accountid'=>$data['accountid']
                ];
                $data['update_time'] = time();

                $res = $this->allowField(true)->isUpdate(true)->save($data,$where);           
                if($res)
                {
                    $response =['state'=>true,'message'=>'更新成功'];
                }else{
                    $response =['state'=>false,'message'=>$this->getError()];
                }     
            }
        }      
        return $response;
    }
    
    /** 检查是否存在
     * @param array $data
     * @return bool
     */
    public   function check(array $data)
    {
        $result = $this->get($data);
     
        if (!empty($result)) 
        {
            return true;
        }
        return false;
    }
    
    /**
     *  获取待刊登商品列表
     *  一个待上传商品product对应多个variant
     */
     //====================关联模型
    //关联变体
    public function variants()
    {
        return $this->hasMany('WishWaitUploadProductVariant','pid','id');
    }

    public function info()
    {
        return $this->hasOne('WishWaitUploadProductInfo','id','id');
    }
    
    public function skus()
    {
        return $this->hasMany(WishWaitUploadProductVariant::class,'pid','id');
    }
    
    public function user()
    {
        return $this->hasOne(\app\common\model\User::class,'id','uid','u','LEFT');
    }
    /**
     * 
     * @return type
     */
   //关联账户
   public function account()
   {
       return $this->hasOne(WishAccount::class,'id','accountid',['wish_account' => 'a', 'wish_wait_upload_product' => 'p'],'LEFT');
   }
   //关联商品
   public  function goods()
   {
       return $this->hasOne(\app\common\model\Goods::class,'id','goods_id','g','LEFT');
   }
   
   public function getAll($where=array(),$page=1,$pageSize=30,$fields="*")
    {
        return $this->alias('a')
                    ->join('wish_wait_upload_product_variant b','a.id = b.pid')
                    ->join('wish_account c','a.accountid = c.id')
                    ->where($where)
                    ->field($fields)
                    ->page($page,$pageSize)->order('vid desc')->select();
    }
    
    public  function getSkus($where,$fields="*")
    {
        return $this->alias('a')
                    ->join('wish_wait_upload_product_variant b','a.id = b.pid')
                    ->join('wish_account c','a.accountid = c.id')
                    ->where($where)
                    ->field($fields)
                    ->select();
    }
    
    /**
     * 获取product and variant information
     * @param array $where
     * @param string $fields
     * @return array
     */
    public static function productVariant(array $where,$fields="*")
    {
        return (new self())->alias('a')
                    ->join('wish_wait_upload_product_variant b','a.id = b.pid')
                    ->where($where)
                    ->field($fields)
                    ->select();
    }
    
    /**
     * 
     * @param array $post post过来的查询条件
     * @param int $cron 是否定时刊登,1为定时刊登
     * @return array
     */
    public  function  getWhere($post=array(),$cron=0)
    {
        $where = array();
        
        if( isset($post['nType']) && $post['nType']=='parent_sku' && $post['nContent'])
        {
            $where['parent_sku'] = array('eq',$post['nContent']);
        }
        
        if(isset($post['nType']) && $post['nType']=='sku'  && $post['nContent'])
        {
            $where['sku'] = array('like',$post['nContent'].'%');
        }
        
        if($cron==1)
        {
            $where['cron_time'] = array('neq',0);
        }
        
        
        if(isset($post['status']))
        {
            $where['status'] = array('=',$post['status']);
        }
       
        
        if(isset($post['nType']) && $post['nType']=='zh_name' && $post['nContent'])
        {
            $where['zh_name'] = array('like','%'.$post['nContent'].'%');
        }
        
        if(isset($post['nType']) && $post['nType']=='name' && $post['nContent'])
        {
            $where['name'] = array('like','%'.$post['nContent'].'%');
        }
        
        if(isset($post['account']) && $post['account'])
        {
            $where['accountid'] = array('eq',$post['account']);
        }
        
        if(isset($post['ntime']) && $post['ntime']=='add_time')
        {
            if(isset($post['start_time']) && $post['start_time'])
            {
                $where['add_time']=array('>=',strtotime($post['start_time']));
                
            }elseif(isset ($post['end_time']) && $post['end_time']){
                
                $where['add_time']=array('<=',strtotime($post['end_time']));
                
            }elseif(isset ($post['start_time']) && isset ($post['end_time']) && $post['end_time'] && $post['start_time']){
                
                $where['add_time']=array('>=',strtotime($post['start_time']));
                $where['add_time']=array('<=',strtotime($post['end_time']));
                
            }        
        }
        
        if(isset($post['ntime']) && $post['ntime']=='run_time')
        {
            if(isset($post['start_time']) && $post['start_time'])
            {
                $where['run_time']=array('>=',strtotime($post['start_time']));
                
            }elseif(isset ($post['end_time']) && $post['end_time']){
                
                $where['run_time']=array('<=',strtotime($post['end_time']));
                
            }elseif(isset ($post['start_time']) && isset ($post['end_time']) && $post['end_time'] && $post['start_time']){
                
                $where['run_time']=array('>=',strtotime($post['start_time']));
                $where['run_time']=array('<=',strtotime($post['end_time']));
                
            }    
        }      

        return $where;
    }

    public  function  getCount($where)
    {
        return $this->alias('a')
                ->join('wish_wait_upload_product_variant b','a.id = b.pid')
                ->join('wish_account c','a.accountid = c.id')
                ->where($where)->count();
    }
	/**
	 * @doc hasWhere的加强版
	 * @param $hasWheres array hasWhere复数（数组）
	 * @return mixed|null|static
	 */
	public static function hasWhereHeighten($hasWheres)
	{
		$model = null;
		if(!empty($hasWheres)){
			foreach ($hasWheres as $key => $hasWhere){
				if($model){
					$model = call_user_func([$model,"hasWhere"], $key, $hasWhere);
				}else{
					$model = forward_static_call([static::class, "hasWhere"], $key, $hasWhere);
				}
			}
		}else{
			$model = new static();
		}
		return $model;
	}

	public  function saveListing($return)
    {
        Db::startTrans();
        try{
            if(isset($return['product']))
            {
                $product = $return['product'];

                $product_id = $product['product_id'];

                $id = $product['id'];

                $info=[
                    'id'=>$id,
                    'product_id'=>$product['product_id'],
                    'description'=>$product['description'],
                    'landing_page_url'=>$product['landing_page_url'],
                    'extra_images'=>$product['extra_images'],
                ];

                if($this->where('id',$product['id'])->find())
                {
                    $isUpdate=true;
                    $this->allowField(true)->isUpdate(true)->save($product,['id'=>$product['id']]);
                }else{
                    $isUpdate=false;
                    $this->allowField(true)->isUpdate(false)->save($product);
                }

                if($isUpdate)
                {
                    $this->isUpdate(true)->info()->save($info,['id'=>$product['id']]);
                }else{
                    $this->isUpdate(false)->info()->save($info);
                }

                if(isset($return['variant']) && $return['variant'])
                {
                    if($isUpdate)
                    {
                        foreach ($return['variant'] as $variant)
                        {
                            $this->isUpdate(true)->allowField(true)->variants()->save($variant,['vid'=>$variant['vid']]);
                        }
                    }else{
                        $this->isUpdate(false)->allowField(true)->variants()->saveAll($return['variant']);
                    }

                }
            }
            Db::commit();
        }catch (\Exception $exp){
            Db::rollback();
            throw new QueueException("File:{$exp->getMessage()};Line:{$exp->getMessage()};Message:{$exp->getMessage()}");
        }
    }
    
}
