<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User:zhaixueli
 * Date: 2018/12/21
 * Time: 18:48
 */
class ShippingAddress  extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
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
    /** 修改
     * @param array $data
     * @param array $where
     * @return false|int
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }


}