<?php
namespace app\common\model;

use erp\ErpModel;
use think\Model;
use app\common\traits\ModelFilter;
use think\db\Query;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class ChannelUserAccountMap extends ErpModel
{
    use ModelFilter;

    /**
     * 通过平台过滤
     * @param Query $query
     * @param $params
     */
    public function scopeChannels(Query $query, $params)
    {
        $query->where('__TABLE__.channel_id', 'in', $params);
    }

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }
    public  function user()
    {
        return $this->hasOne(User::class, 'id', 'seller_id');
    }

    /** 检查账号是否重复
     * @param $channel_id 【渠道id】
     * @param $account_id 【账号id】
     * @param int $group_id 【分组id】
     * @return bool
     */
    public function checkRepeat($channel_id, $account_id, $group_id = 0)
    {
        $ok = true;
        if (empty($id)) {
            $result = $this->where(['channel_id' => $channel_id, 'account_id' => $account_id])->select();
            if (!empty($result)) {
                $ok = false;
            }
        } else {
            $where['channel_id'] = ['=', $channel_id];
            $where['account_id'] = ['=', $account_id];
            $where['team_id'] = ['<>', $group_id];
            $result = $this->where($where)->select();
            if (!empty($result)) {
                $ok = false;
            }
        }
        return $ok;
    }

    /** 检测记录是否存在
     * @param int $id
     * @return bool
     */
    public function isHas($id = 0)
    {
        $result = $this->where(['id' => $id])->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return true;
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