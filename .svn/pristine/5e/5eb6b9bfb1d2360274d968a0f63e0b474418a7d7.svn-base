<?php
namespace app\common\model;

use think\Exception;
use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/03/02
 * Time: 9:13
 */
class DeveloperTeam extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 获取
     * @param string $field
     * @return mixed
     */
    public function detail($field = '*')
    {
        return $this->hasMany(TeamChannelUserAccountMap::class, 'team_id', 'id',
            ['developer_team' => 'a', 'team_channel_user_account_map' => 'b'],
            'left')->field($field);
    }

    /** 获取
     * @param string $field
     * @return mixed
     */
    public function subclass($field = '*')
    {
        return $this->hasMany(TeamPurchaseMap::class, 'team_id', 'id',
            ['developer_team' => 'a', 'team_purchase_map' => 'c'],
            'left')->field($field);
    }

    /**
     * 检测记录是否存在
     * @param int $id
     * @param string $name
     * @return bool
     * @throws Exception
     */
    public function isHas($id = 0,$name = '')
    {
        if(empty($id)){
            throw new Exception('分组记录id不能为空');
        }
        $where['id'] = ['eq',$id];
        if(!empty($name)){
            $where['id'] = ['<>',$id];
            $where['name'] = ['eq',$name];
        }
        $result = $this->where($where)->find();
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
        if (is_numeric($value)) {
            return $value;
        } else {
            return strtotime($value);
        }
    }

    /** 更新时间获取器
     * @param $value
     * @return int
     */
    public function getUpdateTimeAttr($value)
    {
        if (is_numeric($value)) {
            return $value;
        } else {
            return strtotime($value);
        }
    }

    /** 检查子类是否已经被引用了
     * @param $data
     * @param $subclass_id
     * @param bool|false $isUpdate
     * @return bool
     */
    public function checkSubclass($data, $subclass_id, $isUpdate = false)
    {
        $where['channel_id'] = ['=', $data['channel_id']];
        $where['company_id'] = ['=', $data['company_id']];
        $where['category_id'] = ['=', $data['category_id']];
        $where['t.id'] = ['=', $subclass_id];
        if ($isUpdate) {
            $where['developer_id'] = ['<>', $data['developer_id']];
        }
        $subList = $this->field('a.id')->alias('a')->join('team_purchase_map t', 'a.id = t.team_id',
            'left')->where($where)->find();
        if (!empty($subList)) {
            return false;
        }
        return true;
    }

    /** 检查成员关系是否已经被引用了
     * @param $data
     * @param $member_id
     * @param bool|false $isUpdate
     * @return bool
     */
    public function checkMemberShip($data, $member_id, $isUpdate = false)
    {
        $where['channel_id'] = ['=', $data['channel_id']];
        $where['company_id'] = ['=', $data['company_id']];
        $where['t.id'] = ['=', $member_id];
        if ($isUpdate) {
            $where['developer_id'] = ['<>', $data['developer_id']];
        }
        $subList = $this->field('a.id')->alias('a')->join('team_channel_user_account_map t', 'a.id = t.team_id',
            'left')->where($where)->find();
        if (!empty($subList)) {
            return false;
        }
        return true;
    }
}