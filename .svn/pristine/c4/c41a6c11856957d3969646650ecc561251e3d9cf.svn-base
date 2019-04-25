<?php
namespace app\common\model\amazon;

use app\common\model\User;
use erp\ErpModel;
use think\Model;
use think\Loader;
use think\Db;
use app\common\model\amazon\AmazonSellerHeelSale;

class AmazonHeelSaleComplain extends ErpModel
{

    private $filterAccount = [];
    /**
     * listing过滤
     * @param Query $query
     * @param $params
     */
    public function scopeAmazonHeelSaleComplain(Query $query, $params)
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

    /**
     * @return \think\model\relation\HasOne
     * 关联亚马逊账号
     */
    public function account()
    {
        return $this->belongsTo(AmazonAccount::class, 'account_id', 'id');
    }



    /**
     * @return \think\model\relation\HasOne
     * 关联亚马逊账号
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'create_id', 'id');
    }


    public function sellerHeelSale()
    {
        return $this->hasMany(AmazonSellerHeelSale::class, 'heel_sale_complain_id', 'id');
    }
}
