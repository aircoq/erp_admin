<?php
namespace app\common\model\aliexpress;

use app\common\cache\Cache;
use erp\ErpModel;
use think\Model;

class AliexpressAccount extends ErpModel
{

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


    // 开启时间字段自动写入
    protected $autoWriteTimestamp = true;

    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }
    public  function channer()
    {
        return $this->hasOne(\app\common\model\ChannelUserAccountMap::class, 'account_id', 'id');
    }

    /**
     * 新增
     * @param array $data [description]
     */
    public function add(array $data)
    {
        if (isset($data['id'])) {
            //检查产品是否已存在
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
     * 检查是否存在
     * @return [type] [description]
     */
    public function check(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }
    
    /** 检查代码或者用户名是否有存在了
     * @param $account_id
     * @param $code
     * @param $account_name
     * @return bool
     */
    public function isHas($id,$code)
    {
        $result = $this->where(['code' => $code])->where('id','NEQ',$id)->find();
        if($result){
            return true;
        }
        return false;
    }
    
    public static function getAliConfig($id)
    {
        $accountInfo = Cache::store('AliexpressAccount')->getTableRecord($id);
        if($accountInfo){
            return [
                        'id'            =>  $accountInfo['id'],
                        'client_id'     =>  $accountInfo['client_id'],
                        'client_secret' =>  $accountInfo['client_secret'],
                        'accessToken'   =>  $accountInfo['access_token'],
                        'refreshtoken'  =>  $accountInfo['refresh_token'],
                   ];
        }else{
            return [];
        }
    }

    public function user()
    {

        return $this->hasOne(\app\common\model\ChannelUserAccountMap::class,  'account_id', 'id');
    }
}
