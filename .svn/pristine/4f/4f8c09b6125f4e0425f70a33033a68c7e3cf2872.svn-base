<?php
namespace app\common\model;

use app\common\service\Common;
use app\common\cache\Cache;
use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/8/9
 * Time: 15:32
 */
class VirtualRuleLog extends Model
{
    const add = 0;
    const update = 1;
    const delete = 2;
    const sort = 3;
    const updateStatus = 4;

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
     * @param array $oldData
     * @return false|int
     */
    public function addLog($rule_id, $type, $data, $oldData = [])
    {
        $userInfo = Common::getUserInfo();
        $temp['rule_id'] = $rule_id;
        $temp['type'] = $type;
        $temp['operator_id'] = $userInfo['user_id'] ?? 0;
        $temp['operator'] = $userInfo['realname'] ?? '';
        $remark = '';
        $orderRuleLogMode = new OrderRuleLog();
        switch ($type) {
            case self::add:
                $remark = '新增记录';
                break;
            case self::update:
                $oldData = $this->getOldLogData($rule_id);
                $ruleItemList = Cache::store('order')->getVirtualRuleItem();
                $remark = json_encode($orderRuleLogMode->arrayAllDiff($ruleItemList,$data, $oldData));
                break;
            case self::delete:
                $remark = '删除记录';
                break;
            case self::sort:
                $orderRuleLogMode->getUpdateSortMessage($data, $oldData, $temp);
                $remark = '调整排序';
                break;
            case self::updateStatus:
                $oldData = $this->getOldLogData($rule_id);
                $remark = json_encode($orderRuleLogMode->getUpdateStatus($oldData['status'], $data['status']));
                $oldData['status'] = $data['status'];
                $data = $oldData;
                break;
        }
        $temp['remark'] = $remark;
        $temp['data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
        $temp['create_time'] = time();
        return $this->allowField(true)->isUpdate(false)->save($temp);
    }

    public function getOldLogData($ruleId)
    {
        $oldData = $this->where('rule_id', $ruleId)->order('id desc')->value('data');
        return json_decode($oldData, true);
    }

    /**
     * 更新排序
     * @param $newData
     * @param $oldData
     * @param $temp
     * @return array
     */
    public function getUpdateSortMessage($newData, $oldData, $temp)
    {
        $orderRuleLogMode = new OrderRuleLog();
        $newData = $orderRuleLogMode->arrayChangeKey($newData, 'id');
        $oldData = $orderRuleLogMode->arrayChangeKey($oldData, 'id');
        foreach ($newData as $key => $vol) {

            $oldSort = isset($oldData[$key]['sort']) ? $oldData[$key]['sort'] : '';
            if ($vol['sort'] != $oldSort) {
                $model = new VirtualRuleLog();
                $temp['remark'] = '优先级调整:(' . ($oldSort+1) . ')-->(' . ($vol['sort']+1) . ')';
                $temp['rule_id'] = $vol['id'];
                $data = $model->where('rule_id', $vol['id'])->order('id desc')->value('data');
                $temp['data'] = $data ? $data : '';
                $temp['create_time'] = time();
                $model->allowField(true)->isUpdate(false)->save($temp);
            }
        }
        return true;
    }
}