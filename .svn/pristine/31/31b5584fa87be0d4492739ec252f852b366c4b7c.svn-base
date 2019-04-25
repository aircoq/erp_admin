<?php
namespace app\common\model;

use think\Model;
use think\Db;
/**
 * Created by PhpStorm.
 * User: zhaixueli
 * Date: 2018/11/29
 * Time: 19:00
 */
class ServerNetworkIp extends Model
{
    /**
     * 初始化数据
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
            //检查服务器是否已存在
            if ($this->check(['id' => $data['id']])) {
                return $this->edit($data, ['id' => $data['id']]);
            }
        }
        return $this->insert($data);
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
}