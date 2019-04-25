<?php
namespace app\customerservice\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\AfterSaleRuleSet;
use app\common\model\AfterSaleRuleSetItem;
use think\Db;

/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2018/11/10
 * Time: 16:16
 */
class AfterSaleRuleService
{
    protected $afterSaleRuleSetModel;
    protected $afterSaleRuleSetItemModel;

    public function __construct()
    {
        if (is_null($this->afterSaleRuleSetModel)) {
            $this->afterSaleRuleSetModel = new AfterSaleRuleSet();
        }
        if (is_null($this->afterSaleRuleSetItemModel)) {
            $this->afterSaleRuleSetItemModel = new AfterSaleRuleSetItem();
        }
    }

    /**
     * 规则列表
     * @param array $where
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function afterSaleRuleList(array $where)
    {
        $afterSaleRuleSetList = $this->afterSaleRuleSetModel->field('id,sort,title,operator,channel_id,status,create_time,update_time,action_value')->where($where)->order('sort asc,id asc')->select();
        $count = $this->afterSaleRuleSetModel->where($where)->count();
        $new_array = [];
        foreach ($afterSaleRuleSetList as $k => $v) {
            $temp = $v;
            $temp['channel'] = !empty($v['channel_id']) ? Cache::store('channel')->getChannelName($v['channel_id']) : '通用';
            unset($temp['channel_id']);
            array_push($new_array, $temp);
        }
        $result = [
            'data' => $new_array,
            'count' => $count,
        ];
        return $result;
    }

    /**
     * 信息
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function info($id)
    {
        $afterSaleRuleSetList = $this->afterSaleRuleSetModel->field('id,title as name,channel_id,status,action_value,create_time')->where(['id' => $id])->find();
        if (empty($afterSaleRuleSetList)) {
            return ['message' => '不存在该规则', 'code' => 500];
        }
        $afterSaleRuleSetList = $afterSaleRuleSetList->toArray();
        $result = $afterSaleRuleSetList;
        $afterSaleRuleSetItemList = $this->afterSaleRuleSetItemModel->where(['rule_id' => $id])->select();
        $virtualRules = [];
        foreach ($afterSaleRuleSetItemList as $k => $v) {
            $temp['item_id'] = $v['rule_item_id'];
            $item_value = json_decode($v['param_value'], true);
            $temp['choose'] = $item_value;
            array_push($virtualRules, $temp);
        }
        $result['rules'] = $virtualRules;
        return $result;
    }

    /**
     * 添加规则
     * @param $afterSaleRuleSet
     * @param $afterSaleRules
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function save($afterSaleRuleSet, $afterSaleRules)
    {
        $afterSaleRules = json_decode($afterSaleRules, true);
        if (empty($afterSaleRules)) {
            throw new JsonErrorException('请选择一条规则条件');
        }
        //启动事务
        Db::startTrans();
        try {
            $this->afterSaleRuleSetModel->allowField(true)->isUpdate(false)->save($afterSaleRuleSet);
            $afterSaleRule_id = $this->afterSaleRuleSetModel->id;
            $afterSaleRuleSetItem = $this->setItem($afterSaleRules, $afterSaleRule_id);
            $this->afterSaleRuleSetItemModel->allowField(true)->isUpdate(false)->saveAll($afterSaleRuleSetItem);
            Db::commit();
            //删除缓存
            return $this->getOneRule($afterSaleRule_id);
        } catch (\Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage(), 500);
        }
    }

    /**
     * 更新规则
     * @param $id
     * @param $afterSaleRuleSet
     * @param $afterSaleRules
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function update($id, $afterSaleRuleSet, $afterSaleRules)
    {
        $afterSaleRules = json_decode($afterSaleRules, true);
        if (empty($afterSaleRules)) {
            throw new JsonErrorException('请选择一条规则条件');
        }
        //启动事务
        Db::startTrans();
        try {
            $this->afterSaleRuleSetModel->where(["id" => $id])->update($afterSaleRuleSet);
            //删除原来的规则设置条件
            $this->afterSaleRuleSetItemModel->where(['rule_id' => $id])->delete();
            $afterSaleRuleSetItem = $this->setItem($afterSaleRules, $id);
            $this->afterSaleRuleSetItemModel->allowField(true)->isUpdate(false)->saveAll($afterSaleRuleSetItem);
            Db::commit();
            //删除缓存
            return $this->getOneRule($id);
        } catch (\Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage(), 500);
        }
    }

    /**
     * 删除售后单规则
     * @param $id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delete($id)
    {
        if (!$this->afterSaleRuleSetModel->isHas($id)) {
            throw new JsonErrorException('该规则不存在');
        }
        //查看规则是否在启用中
        $info = $this->afterSaleRuleSetModel->where(['id' => $id])->find();
        if ($info['status'] == 0) {
            throw new JsonErrorException('请先停用该规则！', 500);
        }
        //启动事务
        Db::startTrans();
        try {
            //删除规则条件
            $this->afterSaleRuleSetItemModel->where(['rule_id' => $id])->delete();
            //删除规则
            $this->afterSaleRuleSetModel->where(['id' => $id])->delete();
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new JsonErrorException('删除失败', 500);
        }
    }

    /**
     * 修改规则状态
     * @param $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function status($data)
    {
        if (!$this->afterSaleRuleSetModel->isHas($data['id'])) {
            throw new JsonErrorException('该规则不存在');
        }
        $id = $data['id'];
        unset($data['id']);
        Db::startTrans();
        try {
            $this->afterSaleRuleSetModel->where(['id' => $id])->update($data);
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException('操作失败');
        }
    }

    /**
     * 保存排序值
     * @param $sort
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sort($sort)
    {
        $sortData = [];
        foreach ($sort as $k => $v) {
            if (!$this->afterSaleRuleSetModel->isHas($v['id'])) {
                throw new JsonErrorException('该规则不存在');
            }
            $temp = $v;
            $temp['update_time'] = time();
            array_push($sortData, $temp);
        }
        try {
            $this->afterSaleRuleSetModel->isUpdate(true)->saveAll($sortData);
            return true;
        } catch (\Exception $e) {
            throw new JsonErrorException($e->getMessage(), 500);
        }
    }

    /**
     * 获取对应规则
     * @param $rule_id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneRule($rule_id){
        $where['id'] = $rule_id;
        $orderRuleSet = $this->afterSaleRuleSetModel->field('id,sort,title,operator,channel_id,status,create_time,update_time,action_value')->where($where)->find();
        $orderRuleSet['channel'] = !empty($orderRuleSet['channel_id']) ? Cache::store('channel')->getChannelName($orderRuleSet['channel_id']) : '通用';
        unset($orderRuleSet['channel_id']);
        return $orderRuleSet;
    }

    /** 设置规则item
     * @param $afterSaleRules
     * @param $id
     * @return array
     */
    private function setItem($afterSaleRules, $id)
    {
        $afterSaleRuleSetItem = [];
        foreach ($afterSaleRules as $k => $v) {
            $afterSaleRuleSetItem[$k]['rule_id'] = $id;
            $afterSaleRuleSetItem[$k]['create_time'] = time();
            $afterSaleRuleSetItem[$k]['update_time'] = time();
            $afterSaleRuleSetItem[$k]['rule_item_id'] = $v['item_id'];
            $afterSaleRuleSetItem[$k]['param_value'] = json_encode($v['item_value']);
        }
        return $afterSaleRuleSetItem;
    }

    /**
     * 获取包裹申报的可选条件
     * @return mixed
     */
    public function item()
    {
        $result = [];
        $data['label'] = '售后规则可选条件';
        $item = [
            0 => [
                'id' => 1,
                'name' => '平台',
                'statement' => '指定平台/站点/账号',
                'code' => 'afterSaleSource',
                'type' => 2,
                'rule_type' => 0,
                'classified' => 0
            ],
            1 => [
                'id' => 2,
                'name' => '纠纷退款金额',
                'statement' => '指定范围',
                'code' => 'disputeRefundAmount',
                'type' => 3,
                'rule_type' => 0,
                'classified' => 0
            ],
            2 => [
                'id' => 3,
                'name' => '客服员',
                'statement' => '指定人员',
                'code' => 'customerService',
                'type' => 1,
                'rule_type' => 0,
                'classified' => 0
            ]
        ];
        $data['child'] = $item;
        array_push($result,$data);
        return $result;
    }

    /**
     * 获取售后规则条件数组
     * @return array
     */
    public function itemList()
    {
        $item = [
            1 => [
                'id' => 1,
                'name' => '平台',
                'statement' => '指定平台/站点/账号',
                'code' => 'afterSaleSource',
                'type' => 2,
                'rule_type' => 0,
                'classified' => 0
            ],
            2 => [
                'id' => 2,
                'name' => '纠纷退款金额',
                'statement' => '指定范围',
                'code' => 'disputeRefundAmount',
                'type' => 3,
                'rule_type' => 0,
                'classified' => 0
            ],
            3 => [
                'id' => 3,
                'name' => '客服员',
                'statement' => '指定人员',
                'code' => 'customerService',
                'type' => 1,
                'rule_type' => 0,
                'classified' => 0
            ]
        ];
        return $item;
    }
}
