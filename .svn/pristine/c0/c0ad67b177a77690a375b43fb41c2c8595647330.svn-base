<?php
namespace app\common\model\amazon;

use think\Model;
use think\Loader;
use think\Db;
use app\common\cache\Cache;

class AmazonProductExport extends Model
{
    protected $type = [
        'bullet_point'=>'json',
        'search_terms'=>'json',
        'attributes_images'=>'json',
        'extend_info'=>'json'
    ];
    protected $readonly = ['spu','goods_id'];
    private $_baseUrl;

    public function __construct($data = [])
    {
        $this->_baseUrl = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . '/';
        parent::__construct($data);
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
        $this->type = [];
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
        $this->type = [];
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
        $this->type = [];
        return $this->allowField(true)->save($data, $where);
    }

    /**
     * 批量修改
     * @param  array $data [description]
     * @return [type]       [description]
     */
    public function editAll(array $data)
    {
        $this->type = [];
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

    public function getList($fields = '', array $join, array $where, $group = '', $order = 'create_time desc', $page = 0, $pageSize = 50){
        if($pageSize > 0)
            $lists = $this->alias('e')->field($fields)->join($join)->where($where)->group($group)->order($order)->page($page,$pageSize)->select();
        else
            $lists = $this->alias('e')->field($fields)->join($join)->where($where)->group($group)->order($order)->select();
        foreach ($lists as &$list){
            $list['baseUrl'] = $this->_baseUrl;
        }
        return $lists;
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
}
