<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/08/03
 * Time: 14:10
 */
class ChannelBuyer extends Model
{
    /**
     * 初始化数据
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
        $this->query('set names utf8mb4');
    }

    /**
     * 检测渠道买家id是否存在
     * @param $channel_id
     * @param $buyer_id
     * @param int $id
     * @return array|bool|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isHas($channel_id,$buyer_id,$id = 0)
    {
        if(!empty($id)){
            $where['id'] = ['<>',$id];
        }
        $where['channel_id'] = ['=',$channel_id];
        $where['buyer_id'] = ['=',$buyer_id];
        $result = $this->field(true)->where($where)->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return $result;
    }
}