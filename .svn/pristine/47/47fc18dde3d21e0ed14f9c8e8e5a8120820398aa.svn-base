<?php
namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\Config;
use app\common\traits\ConfigCommon;
use think\Exception;
use app\common\cache\Cache;
use think\Db;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/5/15
 * Time: 14:03
 */
class ConfigService
{
    use ConfigCommon;

    protected $configModel;

    public function __construct()
    {
        if (is_null($this->configModel)) {
            $this->configModel = new Config();
        }
    }

    /** 配置列表
     * @param $where
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function configList($where, $page, $pageSize)
    {
        $count = $this->configModel->where($where)->count();
        $groups = $this->configModel->getGoroups();
        $field = 'module, lang_id';
        $list = $this->configModel->field($field, true)->where($where)->order('sort asc')->order('id DESC')->page($page,
            $pageSize)->select();
        foreach ($list as $k => $v) {
            if (isset($groups[$v['group_id']])) {
                $v['group'] = $groups[$v['group_id']];
            } else {
                $v['group'] = $v['title'];
            }
        }
        $result = [
            'data' => $list,
            'groups' => $groups,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    /**
     * 保存
     * @param $params
     * @return mixed
     */
    public function save($params)
    {
        try {
            $this->configModel->allowField(true)->isUpdate(false)->save($params);
            return $this->configModel->id;
        } catch (Exception $e) {
            throw new JsonErrorException('新增失败', 500);
        }
    }

    /** 更新
     * @param $params
     * @param $id
     */
    public function update($params, $id)
    {
        try {
            $this->configModel->allowField(true)->save($params, ['id' => $id]);
            Cache::store('configParams')->delete($id);
        } catch (Exception $e) {
            throw new JsonErrorException('更新失败', 500);
        }
    }

    /** 拼接配置信息
     * @param $result
     * @return array
     */
    public function config($result)
    {
        $where = [
            'group_id' => ['in', [0, $result['id']]],
        ];
        $list = $this->configModel->where($where)->order('sort')->select();
        $groups = [];
        foreach ($list as $key => &$v) {
            if(is_json($v['value'])){
                $v['value'] = json_decode($v['value'],true);
            }
            //分组名
            if ($v['group_id'] == 0) {
                $groups[strtolower($v['name'])] = $v['title'];
                unset($list[$key]);
            } else {
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
        }
        $list = $list ? array_values($list) : [];
        $result = [
            'list' => $list,
            'groups' => $groups,
            'group_id' => $result['id'],
        ];
        return $result;
    }

    /** 删除
     * @param $id
     */
    public function delete($id)
    {
        try {
            $this->configModel->where(['id' => $id])->delete();
            Cache::store('configParams')->delete($id);
        } catch (Exception $e) {
            throw new JsonErrorException('操作失败', 500);
        }
    }

    /** 参数配置
     * @param $params
     * @return bool
     */
    public function params($params)
    {
        $configModel = new Config();
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
                        if(isset($temp['value'])){
                            $choose_value['value'] = $temp['value'];
                            $choose_value['key'] = $key;
                            if (isset($temp['child']) && !empty($temp['child']) && !empty($settingData)) {
                                $choose_value['child'] = [];
                                foreach ($temp['child'] as $k => $v) {
                                    $choose_value['child'] = $this->checkChild($v, $settingData[$temp['value']]['child'][$k], $choose_value['child']);
                                }
                                $configModel->where($where)->setField('value', json_encode($choose_value));
                            }else{
                                $configModel->where($where)->setField('value', json_encode($choose_value));
                            }
                        }else if(is_array($temp)){
                            $choose_value = [];
                            foreach($temp as $k => $v){
                                $sale_value['key'] = $v;
                                $sale_value['value'] = true;
                                array_push($choose_value,$sale_value);
                            }
                            $configModel->where($where)->setField('value', json_encode($choose_value));
                        }else{
                            if (request_verify($temp, $info['data_type'])) {
                                $configModel->where($where)->setField('value', $temp);
                            } else {
                                throw new JsonErrorException($key . '参数值必须为' . $info['data_type'] . '格式', 400);
                            }
                        }
                    } else {
                        if (request_verify($val, $info['data_type'])) {
                            $configModel->where($where)->setField('value', $val);
                        } else {
                            throw new JsonErrorException($key . '参数值必须为' . $info['data_type'] . '格式', 400);
                        }
                    }
                }
            }
            Cache::store('configParams')->delete();
            return true;
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
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
                array_push($choose_value,$temp);
            } else {
                throw new JsonErrorException($data['key'] . '参数值必须为' . $setting['data_type'] . '格式', 400);
            }
            return $choose_value;
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /** 排序
     * @param $sorts
     */
    public function sort($sorts)
    {
        Db::startTrans();
        try {
            foreach ($sorts as $k => $v) {
                $this->configModel->where(['id' => $k])->setField('sort', $v);
            }
            Db::commit();
        } catch (Exception $e) {
            throw new JsonErrorException('操作失败', 500);
        }
    }

    /** 状态更改
     * @param $id
     * @param $status
     * @return bool
     */
    public function status($id, $status)
    {
        try {
            $this->configModel->where('id', $id)->setField('status', $status);
            Cache::store('configParams')->delete($id);
            return true;
        } catch (Exception $e) {
            throw new JsonErrorException('操作失败', 500);
        }
    }

    /** 数据类型
     * @return array
     */
    public function dataType()
    {
        $type = [
            0 => [
                'data_type' => 'int',
                'remark' => '整型'
            ],
            1 => [
                'data_type' => 'string',
                'remark' => '字符串'
            ],
            2 => [
                'data_type' => 'bool',
                'remark' => '布尔值'
            ],
            3 => [
                'data_type' => 'float',
                'remark' => '浮点型'
            ],
        ];
        return $type;
    }

    /**
     * 转换数据
     * @param $config
     * @return mixed
     */
    public function conversion($config)
    {
        if($config['type'] == 3){   //数组格式
            if(!empty($config['value'])){
                $configData = json_decode($config['value'],true);
                if($config['choose_type'] == 0){  //单选
                    $configValue = 0;
                    if(!empty($configData)){
                        $configValue = $configData['value'];
                    }
                    $config['value'] = $configValue;
                }
            }
        }
        return $config;
    }

    /**
     * 获取打印机路径
     * @return int|string
     */
    public function printerUrl()
    {
        $this->configIdentification = 'printer_url';
        return $this->getConfigData();
    }

    /**
     * @desc 获取指定分组参数
     * @param string $group_name
     * @return array
     */
    public function getParamsByGroup($group_name = 'warehouse')
    {
        $group_id = $this->configModel->where('name', $group_name)->value('id');
        $result = [];
        if($group_id){
            $result = (new Config())->where('group_id', $group_id)->field('id, title, name')->select();
        }
        return $result;
    }

    /**
     * @desc 获取指定参数
     * @param string $group_name
     * @return int|string
     */
    public function getDetailById($id)
    {
        $data = $this->configModel->where('id', $id)->find();
        if(empty($data)){
            throw new Exception('参数设置不存在！');
        }
        return $data->toArray();
    }
}