<?php

namespace app\common\model;

use app\common\exception\JsonErrorException;
use app\common\service\Common;
use app\common\cache\Cache;
use app\order\service\Resource;
use think\Model;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/10/30
 * Time: 11:47
 */
class OrderRuleLog extends Model
{
    const add = 0;
    const update = 1;
    const delete = 2;
    const sort = 3;
    const updateStatus = 4;
    const updateValue = 5;

    const attributeName = [
        'title' => '规则名称',
        'channel_id' => '平台',
        'type' => '类型',
        'end_time' => '结束时间',
        'start_time' => '开始时间',
        'status' => '状态',
        'action_type' => '操作动作',
        'action_value' => '操作动作对应值',
    ];


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
        switch ($type) {
            case self::add:
                $remark = '新增记录';
                break;
            case self::update:
                $remark = json_encode($this->getUpdateRemark($rule_id, $data));
                break;
            case self::delete:
                $remark = '删除记录';
                break;
            case self::sort:
                $this->getUpdateSortMessage($data, $oldData, $temp);
                $remark = '调整排序';
                break;
            case self::updateStatus:
                $oldData = $this->getOldLogData($rule_id);
                $remark = json_encode($this->getUpdateType($oldData['status'], $data['status']));
                $oldData['status'] = $data['status'];
                $data = $oldData;
                break;
            case self::updateValue:
                $oldData = $this->getOldLogData($rule_id);
                $remark = json_encode($this->getUpdateType($oldData['action_value'], $data['action_value'],'action_value',2));
                $oldData['action_value'] = $data['action_value'];
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
     * 更新规则状态
     * @param $rule_id
     * @param $data
     * @return array
     */
    public function getUpdateType($oldStatus, $newStatus ,$type = 'status',$actionType = '')
    {
        $message[] = self::attributeName[$type] . ':(' . $this->attributeKeyName($type, $oldStatus,$actionType) . ')->(' . $this->attributeKeyName($type, $newStatus,$actionType) . ')';
        return $message;
    }

    /**
     * 更新规则
     * @param $rule_id
     * @param $newData
     * @return array
     */
    public function getUpdateRemark($rule_id, $newData)
    {
        $oldData = $this->getOldLogData($rule_id);
        $ruleItemList = Cache::store('order')->getRuleItem();
        return $this->arrayAllDiff($ruleItemList,$newData, $oldData);
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
        $newData = $this->arrayChangeKey($newData, 'id');
        $oldData = $this->arrayChangeKey($oldData, 'id');
        foreach ($newData as $key => $vol) {
            $oldSort = isset($oldData[$key]['sort']) ? $oldData[$key]['sort'] : '';
            if ($vol['sort'] != $oldSort) {
                $model = new OrderRuleLog();
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


    /**
     * @param $ruleItemList
     * @param $newData
     * @param $ordData
     * @return array
     */
    public function arrayAllDiff($ruleItemList,$newData, $ordData)
    {

        $message = [];
        $oldRule = isset($ordData['rule']) ? $this->arrayChangeKey($ordData['rule']) : [];
        $newRule = $this->arrayChangeKey($newData['rule']);

        unset($ordData['rule']);
        unset($newData['rule']);
        unset($newData['update_time']);
        unset($newData['operator']);
        unset($newData['operator_id']);
        foreach ($newData as $key => $vol) {
            $old = isset($ordData[$key]) ? $ordData[$key] : '';
            if ($vol != $old) {
                $oldAccountType = $ordData['action_type'] ?? '';
                $newAccountType = $newData['action_type'] ?? '';

                $message[] = self::attributeName[$key] . ':(' . $this->attributeKeyName($key, $old,$oldAccountType) . ')->(' . $this->attributeKeyName($key, $vol,$newAccountType) . ')';
            }
        }
        $this->arrayAddUpdate($ruleItemList,$newRule, $oldRule, $message);
        $this->arrayDiff($ruleItemList,$newRule, $oldRule, $message);
        return $message ? $message : ['无任何修改'];
    }


    /**
     * 查找删除的内容
     * @param $ruleItemList
     * @param $newData
     * @param $ordData
     * @param $message
     * @throws \think\Exception
     */
    public function arrayDiff($ruleItemList,$newData, $ordData, &$message)
    {
        $newKeys = [];
        foreach ($newData as $key => $vol) {
            $newKeys[] = $key;
        }
        $source = new Resource();
        foreach ($ordData as $key => $vol) {
            if (!in_array($key, $newKeys, true)) {
                $message[] = '删除[' . $ruleItemList[$key]['name'] . ':原值：' . $source->getValue($vol) . ']';
            }
        }
    }

    /**
     * 查找修改或者新增的内容
     * @param $ruleItemList
     * @param $newData
     * @param $ordData
     * @param $message
     * @throws \think\Exception
     */
    public function arrayAddUpdate($ruleItemList,$newData, $ordData, &$message)
    {
        $source = new Resource();
        foreach ($newData as $key => $vol) {
            $old = isset($ordData[$key]) ? $ordData[$key] : '';
            if (!$old && isset($vol['item_id'])) {
                $message[] = '新增[' . $ruleItemList[$key]['name'] . ':' . $source->getValue($vol) . ']';
            } else {
                if ($this->isChange($vol, $old)) {
                    $message[] = $ruleItemList[$key]['name'] . ' :(' . $source->getValue($old) . ')->(' . $source->getValue($vol) . ')';
                }
            }
        }
    }

    /**
     * 查看是否有改变数组
     * @param $newData
     * @param $ordData
     * @return bool
     */
    public function isChange($newData, $ordData)
    {
        foreach ($newData as $key => $vol) {
            $old = isset($ordData[$key]) ? $ordData[$key] : '';
            if (is_array($vol)) {
                $temp = $this->isChange($vol, $old);
                if ($temp) {
                    return true;
                }
            } else {
                if ($vol != $old) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 更换二维数组的key值
     * @param $old
     * @param string $keys
     * @return array
     */
    public function arrayChangeKey($old, $keys = 'item_id')
    {
        $new = [];
        foreach ($old as $key => $vol) {
            $new[$vol[$keys]] = $vol;
        }
        return $new;
    }

    /**
     * 更换了属性值
     * @param $type
     * @param $vol
     * @param $actionType
     * @return false|mixed|string
     * @throws \think\Exception
     */
    public function attributeKeyName($type, $vol,$actionType='')
    {
        if (!$vol && $type != 'status') {
            return '';
        }
        $msg = '';
        switch ($type) {
            case 'channel_id':
                if ($vol == 0) {
                    $msg = '通用';
                } else {
                    $msg = Cache::store('Channel')->getChannelName($vol);
                }
                break;
            case 'type':
                $alltyep = ['', '仓库', '运输'];
                $msg = $alltyep[$vol];
                break;
            case 'start_time':
                $msg = date('Y-m-d H:i:s', $vol);
                break;
            case 'end_time':
                $msg = date('Y-m-d H:i:s', $vol);
                break;
            case 'status':
                $all = ['启用', '停用',''=>''];
                $msg = $all[$vol];
                break;
            case 'action_type':
                $all = ['', '分配发货仓库', '匹配邮寄方式', '需人工审核', '自动添加商品备注', '自动配货', '新增商品'];
                $msg = $all[$vol];
                break;
            case 'action_value':
                $msg = $this->getActionValueName($actionType,$vol);
                break;
            default:
                $msg = $vol;
        }
        return $msg;
    }

    public function getActionValueName($actionType,$value){
        $msg = '';
        switch ($actionType){
            case 1: // 分配发货仓库
                $msg = Cache::store('Warehouse')->getWarehouseNameById($value);
                break;
            case 2: // 匹配邮寄方式
                $msg = Cache::store('Shipping')->getFullShippingName($value);
                break;
            case 4: //自动添加商品备注
                $msg = $value;
                break;
            case 6: //新增商品
                $msg = $value;
                break;
        }
        return $msg;
    }

}