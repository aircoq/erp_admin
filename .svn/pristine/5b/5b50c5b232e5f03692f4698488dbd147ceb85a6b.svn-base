<?php
namespace app\common\model\yandex;

use think\Model;
use think\Db;

class YandexAccount extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }
    public function getIdAttr($value)
    {
        return $value;
    }
    /** 新增
     * @param array $data
     * @return false|int
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

    /** 修改
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

    /** 检查是否存在
     * @param array $data
     * @return bool
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
     * @param $id
     * @param $code
     * @param $account_name
     * @return bool
     */
    public function isHas($id,$code,$account_name)
    {
        if(!empty($account_name)){
            $result = $this->where(['account_name' => $account_name])->where('id','NEQ',$id)->select();
            if(!empty($result)){
                return true;
            }
        }
        $result = $this->where(['code' => $code])->where('id','NEQ',$id)->select();
        if(!empty($result)){
            return true;
        }
        return false;
    }

    /** 创建时间获取器
     * @param $value
     * @return int
     */
    public function getCreateTimeAttr($value)
    {
        if(is_numeric($value)){
            return $value;
        }else{
            return strtotime($value);
        }
    }

    /** 更新时间获取器
     * @param $value
     * @return int
     */
    public function getUpdateTimeAttr($value)
    {
        if(is_numeric($value)){
            return $value;
        }else{
            return strtotime($value);
        }
    }
}
