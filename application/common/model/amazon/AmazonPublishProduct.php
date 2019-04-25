<?php
namespace app\common\model\amazon;
use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;
use think\Model;
use think\Loader;
use think\Db;

class AmazonPublishProduct extends ErpModel {
    use ModelFilter;

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

    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
        $this->query('set names utf8mb4');
    }

    public function saveProduct($data){
        $row = $this->getBySpu($data['site'],$data['account_id'],$data['real_spu']);
        if(is_object($row) && $row){
            if($this->where(['id' => $row->id])->update($data)){
                return $row['id'];
            }else{
                return 0;
            }
        }else{
            if($this->insert($data)){
                return $this->getLastInsID();
            }else{
                return 0;
            }
        }
    }


    public function getBySpu($site,$accountId,$spu,$fileds='*'){
        return  $this->field($fileds)->where(array('site' => $site,'account_id' => $accountId,'real_spu' => $spu))->find();
    }
}