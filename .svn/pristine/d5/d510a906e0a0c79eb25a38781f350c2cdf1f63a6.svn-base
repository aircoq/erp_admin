<?php

namespace app\index\service;

use app\common\cache\Cache;
use app\common\model\Channel;
use app\common\model\ChannelProportion;
use app\common\model\ChannelConfig;
use app\common\service\Common;
use think\Db;
use think\Exception;
use app\index\validate\ChannelProportion as validateChannelProportion;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/11
 * Time: 19:43
 */
class ChannelService
{
    protected $channelModel;

    public function __construct()
    {
        if (is_null($this->channelModel)) {
            $this->channelModel = new Channel();
        }
    }

    /**
     * 封装where条件
     * @param array $params
     * @return array
     */
    public function where($params = [])
    {
        $where = [];
        //平台状态
        if (isset($params['status']) && $params['status'] != '') {
            $where['status'] = ['eq', $params['status']];
        }
        //平台名称
        if (isset($params['channelName']) && $params['channelName'] != '') {
            $where['title'] = ['like', '%' . $params['channelName'] . '%'];
        }
        return $where;
    }

    /**
     * 查询平台列表数据
     * @param array $params
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function channelList($params = [], $page = 1, $pageSize = 10)
    {
        $where = $this->where($params);
        $field = 'id,name,title,status,is_site,config,create_time,update_time';
        $count = $this->channelModel->field($field)->where($where)->count();
        $channelList = $this->channelModel->field($field)->where($where)->page($page, $pageSize)->order('id desc')->select();
        foreach ($channelList as $k => $v) {
            $v['config'] = json_decode($v['config'], true);
            $v['delivery_deadline'] = $v['config']['delivery_deadline'] ?? 0;
            $v['examination_cycle'] = $v['config']['examination_cycle'] ?? 0;
            $v['create_time'] = ($v['create_time'] > 0) ? date('Y-m-d H:i:s', $v['create_time']) : '';
            $v['update_time'] = ($v['update_time'] > 0) ? date('Y-m-d H:i:s', $v['update_time']) : '';
        }
        $result = [
            'data' => $channelList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }


    /**
     * 查询平台详情
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        $field = 'id,title,name,is_site,config,status,duplication,create_time,update_time';
        $channelInfo = $this->channelModel->field($field)->where(['id' => $id])->find();
        if ($channelInfo) {
            $channelInfo['config'] = json_decode($channelInfo['config'], true);
            $channelInfo['delivery_deadline'] = $channelInfo['config']['delivery_deadline'] ?? 0;
            $channelInfo['list_num'] = $channelInfo['config']['list_num'] ?? 0;
            $channelInfo['examination_cycle'] = $channelInfo['config']['examination_cycle'] ?? 0;
            $channelInfo['duplication'] = $channelInfo['duplication'] ?? 0;
            $channelInfo['create_time'] = ($channelInfo['create_time'] > 0) ? date('Y-m-d H:i:s', $channelInfo['create_time']) : '';
            $channelInfo['update_time'] = ($channelInfo['update_time'] > 0) ? date('Y-m-d H:i:s', $channelInfo['update_time']) : '';
        } else {
            $channelInfo = [];
        }
        return $channelInfo;
    }

    /**
     * 添加平台
     * @param $params
     * @return int|string
     */
    public function save($params)
    {
        $save_data = [];
        $save_data['title'] = $params['title'];
        $save_data['name'] = $params['name'];
        $save_data['is_site'] = $params['is_site'];
        //平台过期时间
        $config = [];
//        $config['delivery_deadline'] = $params['delivery_deadline'];
        $save_data['config'] = json_encode($config);
        $save_data['status'] = $params['status'] ?? 0;
        $save_data['create_time'] = time();
        $id = $this->channelModel->insertGetId($save_data);
        $data = $this->read($id);
        return $data;
    }

    /**
     * 更新平台信息
     * @param $params
     * @param $id
     * @return bool
     */
    public function update($params, $id)
    {

        $data = [];
        if (isset($params['title']) && !empty($params['title'])) {
            $data['title'] = $params['title'];
        }
        if (isset($params['name']) && !empty($params['name'])) {
            $data['name'] = $params['name'];
        }

        if (isset($params['is_site']) && $params['is_site'] != '') {
            $data['is_site'] = $params['is_site'];
        }


        if (isset($params['delivery_deadline']) && !empty($params['delivery_deadline'])) {
            $config = [];
            $config['delivery_deadline'] = $params['delivery_deadline'];
            $data['config'] = json_encode($config);
        }
        $item = [];
        if ($id == 2) {
            if (isset($params['delivery_deadline']) && !empty($params['delivery_deadline'])) {

                $item['delivery_deadline'] = $params['delivery_deadline'];
            }
            if (isset($params['examination_cycle']) && !empty($params['examination_cycle'])) {
                $item['examination_cycle'] = $params['examination_cycle'];
            }
            if (isset($params['list_num']) && !empty($params['list_num'])) {
                $item['list_num'] = $params['list_num'];
            }
            $item = json_encode($item);

            $data['config'] = $item;
        }

        if (isset($params['status']) && $params['status'] != '') {
            $data['status'] = $params['status'];
        }
        $data['update_time'] = time();
        $result = $this->channelModel->where(['id' => $id])->update($data);
        $dataInfo = [];
        if ($result) {
            $dataInfo['status'] = 1;
            $dataInfo['data'] = $this->read($id);
        } else {
            $dataInfo['status'] = 0;
            $dataInfo['data'] = [];
        }
        return $dataInfo;
    }

    /**
     * 修改平台状态
     * @param $param
     * @return bool
     */
    public function changeStatus($param)
    {
        $data = [];
        $data['status'] = $param['status'];
        $result = $this->channelModel->where(['id' => $param['id']])->update($data);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @title 获取平台占比信息
     * @param $id
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getProportion($id)
    {
        $ChannelProportion = new ChannelProportion();
        $ret = $ChannelProportion->field("id,channel_id,department_id,product_proportion,profit_in,profit_out,product_count")->where('channel_id', $id)->select();
        $result = [];
        foreach ($ret as $v) {
            $row = [];
            $row['id'] = $v['id'];
            $row['channel_id'] = $v['channel_id'];
            $row['department_id'] = $v['department_id'];
            $row['department_name'] = $v->department_name;
            $row['product_proportion'] = $v->product_proportion;
            $row['profit_in'] = $v->profit_in;
            $row['profit_out'] = $v->profit_out;
            $row['product_count'] = $v->product_count;
            $result[] = $row;
        }
        return $result;
    }

    /**
     * @title 根据渠道id获取销售部门
     * @param $channelId
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getDepartmentByChannelId($channelId)
    {
        $department = new Department();
        $ret = $department->getDepartmentByChannelId($channelId);
        $result = [];
        foreach ($ret as $v) {
            $row = [];
            $row['id'] = $v['id'];
            $row['name'] = $v['name'];
            $result[] = $row;
        }
        return $result;
    }

    /**
     * @title 保存平台占比信息
     * @param $id
     * @param $lists
     * @return array
     * @throws Exception
     * @author starzhan <397041849@qq.com>
     */
    public function saveProportion($id, $lists)
    {
        $userInfo = Common::getUserInfo();
        $model = new ChannelProportion();
        $old = $model->where('channel_id', $id)->select();
        $oldResult = [];
        foreach ($old as $v) {
            $row = [];
            $row['channel_id'] = $v['channel_id'];
            $row['department_id'] = $v['department_id'];
            $row['product_proportion'] = $v['product_proportion'];
            $row['profit_in'] = $v['profit_in'];
            $row['profit_out'] = $v['profit_out'];
            $row['product_count'] = $v['product_count'];
            $oldResult[$v['id']] = $row;
        }
        $add = [];
        $mdf = [];
        $sum_product_proportion = 0;
        $sum_profit_in = 0;
        $sum_profit_out = 0;
        foreach ($lists as $list) {
            $row = [];
            $row['channel_id'] = $id;
            $row['department_id'] = $list['department_id'];
            $row['product_proportion'] = $list['product_proportion'];
            $row['profit_in'] = $list['profit_in'];
            $row['profit_out'] = $list['profit_out'];
            $row['product_count'] = $list['product_count'];
            $sum_product_proportion += $list['product_proportion'];
            $sum_profit_in += $list['profit_in'];
            $sum_profit_out += $list['profit_out'];
            if (empty($list['id'])) {
                $add[] = $row;
            } else {
                $mdf[$list['id']] = $row;
            }
        }
        if ($sum_product_proportion != 100) {
            throw new Exception('各部门产品数占比总和须为100%');
        }
//        if($sum_profit_in!=100){
//            throw new Exception('各部门预计利润率本部总和须为100%');
//        }
//        if($sum_profit_out!=100){
//            throw new Exception('各部门预计利润率外部总和须为100%');
//        }
        $mdfId = array_keys($mdf);
        $oldId = array_keys($oldResult);
        $delId = array_diff($oldId, $mdfId);
        $validateChannelProportion = new validateChannelProportion;
        $time = time();
        $updateAll = [];
        foreach ($mdf as $updateId => $update) {
            $update['id'] = $updateId;
            $update['update_time'] = $time;
            $update['update_id'] = $userInfo['user_id'];
            $flag = $validateChannelProportion->scene('update')->check($update);
            if ($flag === false) {
                throw new Exception($validateChannelProportion->getError());
            }
            $updateAll[] = $update;
        }
        $insertAll = [];
        foreach ($add as $info) {
            $info['create_id'] = $userInfo['user_id'];
            $info['create_time'] = $time;
            $flag = $validateChannelProportion->scene('insert')->check($info);
            if ($flag === false) {
                throw new Exception($validateChannelProportion->getError());
            }
            $insertAll[] = $info;
        }
        Db::startTrans();
        try {
            if ($updateAll) {
                $model = new ChannelProportion();
                $model->isUpdate(true)->saveAll($updateAll);
            }
            if ($insertAll) {
                $model = new ChannelProportion();
                $model->isUpdate(false)->saveAll($insertAll);
            }
            if ($delId) {
                $model = new ChannelProportion();
                $model->where('id', 'in', $delId)->delete();
            }
            Db::commit();
            return ['message' => '保存成功'];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    public function getProportionByChannelIdAndDepartmentId($channelId, $departmentId)
    {
        $model = new ChannelProportion();
        return $model->where('channel_id', $channelId)
            ->where('department_id', $departmentId)
            ->field('id,channel_id,department_id,product_proportion,profit_in,profit_out,product_count')
            ->find();
    }

    public function getProportionByChannelId($channelId)
    {
        $model = new ChannelProportion();
        $ret = $model->where('channel_id', $channelId)
            ->field('id,channel_id,department_id,product_proportion,profit_in,profit_out,product_count')
            ->select();
        $result = [];
        foreach ($ret as $v) {
            $v['department_name'] = $v->department_name;
            $result[] = $v->toArray();
        }
        return $result;
    }

    /**
     * @desc 获取配置信息
     * @param int $channel_id
     * @param int
     * @array array
     */
    public function getChannelConfigDetail($channel_id)
    {
        $field = 'duplication';
        $warehouse = (new Channel())->where('id', $channel_id)->field($field)->find();
        $result['channel'] = $warehouse;
        $list = (new ChannelConfig())->where('channel_id', $channel_id)->select();
        foreach ($list as $key => &$v) {
            if (is_json($v['value'])) {
                $v['value'] = json_decode($v['value'], true);
            }
            if ($v['status'] == 1) {
                if (in_array($v['type'], [3, 4]) && trim($v['setting']) != '') {
                    // 配置参数转换成数组
                    if (is_json($v['setting'])) {
                        $v['setting'] = json_decode($v['setting'], true);
                    } else {
                        $v['setting'] = string_to_list($v['setting']);
                    }
                }
            }
        }
        $result['config'] = $list;
        return $result;
    }


    /**
     * @desc 删除站点配置
     * @param int $config_id
     */
    public function delete($config_id)
    {
        (new ChannelConfig())->where('id', $config_id)->delete();
    }

    /**
     * @desc 更新站点配置
     * @param int $config_id
     * @param array $params
     * @throws Exception
     */
    public function updateConfig($config_id, $params)
    {
        $data = (new ChannelConfig())->where('id', $config_id)->find();
        if (empty($data)) {
            throw new Exception('记录不存在！');
        }
        $data->allowField(true)->save($params);
    }

    /**
     * @desc 获取站点配置
     * @param int $config_id
     * @return array
     */
    public function getConfigDetail($config_id)
    {
        $data = (new ChannelConfig())->where('id', $config_id)->find();
        return $data;
    }

    /** 检查子类的数据值
     * @param $data
     * @param $setting
     * @param $choose_value
     * @return mixed
     * @throws Exception
     */
    private function checkChild($data, $setting, $choose_value)
    {
        try {
            if (isset($data['child']) && !empty($data['child'])) {
                foreach ($data['child'] as $k => $v) {
                    $choose_value['child'] = [];
                    $this->checkChild($v, $setting[$k]['child'], $choose_value['child']);
                }
            }
            if (request_verify($data['value'], $setting['data_type'])) {
                $temp['value'] = $data['value'];
                $temp['key'] = $data['key'];
                array_push($choose_value, $temp);
            } else {
                throw new Exception($data['key'] . '参数值必须为' . $setting['data_type'] . '格式', 400);
            }
            return $choose_value;
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * @desc 平台站点配置
     * @param int $channel_id
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function setting($channel_id, $params)
    {
        $where['channel_id'] = $channel_id;
        unset($params['channel_id']);
        $channel = [];
        $configModel = new ChannelConfig();
        try {
            foreach ($params as $key => $val) {
                $where['name'] = $key;
                $info = $configModel->field('data_type,setting')->where($where)->find();
                if (!empty($info)) {
                    if (is_true_json($val)) {
                        $choose_value = [];
                        $temp = json_decode($val, true);
                        $setting = json_decode($info['setting'], true);
                        $settingData = [];
                        if (!empty($setting) && is_array($setting)) {
                            foreach ($setting as $k => $v) {
                                $settingData[$v['value']] = $v;
                            }
                        }
                        if (isset($temp['value'])) {
                            $choose_value['value'] = $temp['value'];
                            $choose_value['key'] = $key;
                            if (isset($temp['child']) && !empty($temp['child']) && !empty($settingData)) {
                                $choose_value['child'] = [];
                                foreach ($temp['child'] as $k => $v) {
                                    $choose_value['child'] = $this->checkChild($v, $settingData[$temp['value']]['child'][$k], $choose_value['child']);
                                }
                                $configModel->where($where)->setField('value', json_encode($choose_value));
                            } else {
                                $configModel->where($where)->setField('value', json_encode($choose_value));
                            }
                        } else if (is_array($temp)) {
                            $choose_value = [];
                            foreach ($temp as $k => $v) {
                                $sale_value['key'] = $v;
                                $sale_value['value'] = true;
                                array_push($choose_value, $sale_value);
                            }
                            $configModel->where($where)->setField('value', json_encode($choose_value));
                        } else {
                            if (request_verify($temp, $info['data_type'])) {
                                $configModel->where($where)->setField('value', $temp);
                            } else {
                                throw new Exception($key . '参数值必须为' . $info['data_type'] . '格式', 400);
                            }
                        }
                    } else {
                        if (request_verify($val, $info['data_type'])) {
                            $configModel->where($where)->setField('value', $val);
                        } else {
                            throw new Exception($key . '参数值必须为' . $info['data_type'] . '格式', 400);
                        }
                    }
                } else {
                    $channel[$key] = $val;
                }
            }
            if (!empty($channel)) {
                (new Channel())->allowField(true)->save($channel, ['id' => $channel_id]);
            }
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }

    /**
     * @desc 获取参考参数（排出已经引用的）
     * @param int $id
     * @return array
     */
    public function getSystemParams($id)
    {
        $lists = (new ConfigService())->getParamsByGroup('channel');
        $name_arr = (new ChannelConfig())->where('channel_id', $id)->column('name');
        $result = [];
        foreach ($lists as $item) {
            if (!in_array($item['name'], $name_arr)) {
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * @desc 新增站点配置
     * @param int $warehouse_id
     * @param array $params
     * @return boolean
     */
    public function addConfig($warehouse_id, $params)
    {
        $params['channel_id'] = $warehouse_id;
        $params['add_type'] = 2;
        (new ChannelConfig())->allowField(true)->save($params);
    }

    /**
     * @desc 引用站点配置
     * @param int $channel_id
     * @param array $config_ids
     * @return boolean
     */
    public function useConfig($channel_id, $config_ids)
    {
        $hanNmaes = (new ChannelConfig())->where('channel_id', $channel_id)->column('name');
        foreach ($config_ids as $config_id) {
            $config = (new ConfigService())->getDetailById($config_id);
            if (in_array($config['name'], $hanNmaes)) {
                continue;
            }
            unset($config['id']);
            $config['channel_id'] = $channel_id;
            $config['add_type'] = 1;
            (new ChannelConfig())->allowField(true)->save($config);
        }
    }

    public function getInfoById($id)
    {
        $allChannel = Cache::store('channel')->getChannel();
        foreach ($allChannel as $channel_code => $info) {
            if ($info['id'] == $id) {
                return $info;
            }
        }
        return [];
    }

}