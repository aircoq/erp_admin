<?php
namespace app\common\model;

use app\common\service\Common;
use think\Model;


class StockRuleLog extends Model
{
    const add = 0;
    const update = 1;
    const delete = 2;
    const sort = 3;

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 新增日志
     * @param $rule_id
     * @param $type
     * @param $data
     */
    public function addLog($rule_id,$type,$data)
    {
        $userInfo = Common::getUserInfo();
        $temp['rule_id'] = $rule_id;
        $temp['type'] = $type;
        $temp['operator_id'] = $userInfo['user_id'] ?? 0;
        $temp['operator'] = $userInfo['realname'] ?? '';
        $remark = '';
        switch($type){
            case self::add:
                $remark = '新增记录';
                break;
            case self::update:
                $remark = '修改记录';
                break;
            case self::delete:
                $remark = '删除记录';
                break;
            case self::sort:
                $remark = '排序调整';
                break;
        }
        $temp['remark'] = $remark;
        $temp['data'] = json_encode($data,JSON_UNESCAPED_UNICODE);
        $temp['create_time'] = time();
        $this->allowField(true)->isUpdate(false)->save($temp);
    }
}