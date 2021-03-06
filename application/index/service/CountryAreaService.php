<?php
/**
 * Created by PhpStorm.
 * User: Dana
 * Date: 2019/3/28
 * Time: 14:53
 */

namespace app\index\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\Area;
use app\common\model\Country;
use app\index\validate\Area as AreaValidate;
use think\Db;
use think\Debug;
use think\Exception;
use think\Log;
use think\Validate;

class CountryAreaService
{
    CONST OVERSEA = 45055;
    protected $areaModel;
    protected $areaValidate;
    protected $countryModel;

    public function __construct()
    {
        if (is_null($this->areaModel)) {
            $this->areaModel = new Area();
            $this->countryModel = new Country();
        }
        $this->areaValidate = new AreaValidate();
    }

    public function getWhere($param)
    {
        $countryModel = new Country();
        $countryCodeList = Cache::store('Area')->getAllCountryCodeList();
        $countryModel->whereIn('country_code', $countryCodeList);
        if (isset($param['country_code']) && !empty($param['country_code'])) {
            $countryModel->where('country_code', $param['country_code']);
        }
        return $countryModel;
    }

    /**
     * @param $param
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($param, $page = 1, $pageSize = 10)
    {
        try {
            //搜索国家
            $countryCodeList = Cache::store('Area')->getAllCountryCodeList();
            $countryList = $this->getWhere($param)->page($page, $pageSize)->select();
            $count = count($countryList) === 1 ? 1 : count($countryCodeList);

            $result = [
                'data' => $countryList,
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $count
            ];

            return $result;
        } catch (Exception $e) {
            return new JsonErrorException('显示有城市的国家失败');
        }
    }

    /**
     * 判断单个国家是否有城市
     * @param $country_code
     * @return array
     */
    public function hasArea($country_code)
    {
        $countryInfo = [];
        $count = 0;
        //dump($country_code);
        $data = $this->countryModel->where('country_code', $country_code)->find();

        if ($data->area) {
            unset($data['area']);
            $countryInfo['country_code'] = $data->country_code;
            $countryInfo['country_en_name'] = $data->country_en_name;
            $countryInfo['country_cn_name'] = $data->country_cn_name;
            $count = 1;
        }

        return [$countryInfo, $count];
    }

    /**
     * @title 获取国家城市列表
     * @param $country_code
     */
    public function cityList($param, $page = 1, $pageSize = 10)
    {
        $count = 0;
        $cityLists = [];
        $flag = $this->areaValidate->scene('cityList')->check($param);
        if ($flag == false) {
            throw new JsonErrorException($this->areaValidate->getError());
        }
        list($data, $code) = $this->countryModel->hasCountryCode($param['country_code']);
        if ($code) {
            throw new JsonErrorException($data);
        }

        $country_code = $param['country_code'];
        $cityLists = $this->areaModel->where(['country_code' => $country_code])->where('id', 'gt', self::OVERSEA)->order('id')->page($page, $pageSize)->select();
        $count = $this->areaModel->where(['country_code' => $country_code])->count();
        if (!$cityLists) {
            $cityLists = [];
        }
        $result = [
            'data' => $cityLists,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count
        ];
        return $result;
    }

    /**
     * 获取AreaInfo
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function readArea($id)
    {
        list($areaInfo, $code) = $this->areaModel->hasId($id);
        if ($code) {
            return $areaInfo;
        }
        return $areaInfo;
    }

    /**
     * @param $param
     * @return array
     * @throws Exception
     */
    public function save($param)
    {
        if (empty($param)) {
            throw new JsonErrorException('参数不能为空');
        }
        $detail = $this->checkParam($param);
        try {
            Db::startTrans();
            $data = $this->_checkFormatParam($detail);
            $this->areaModel->allowField(true)->saveAll($data);
            Cache::store('Area')->addCountryCodeList($param['country_code']);
            Db::commit();

        } catch (Exception $ex) {
            //异常回滚
            Db::rollback();
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * 验证二维数据
     * @param $data
     * @return array
     */
    private function validate($data)
    {
        //实例化验证类
        $validate = new AreaValidate();
        //对数据进行批量验证
        $error = [];
        if (!$validate->check($data, '', 'save')) {
            $error['message'] = is_array($validate->getError()) ? implode(' ', $validate->getError()) : $validate->getError();
            //$error['data'] = $data;
            return $error;
        }
    }

    /**
     * 验证数据
     * @param $param
     * @return array
     */
    public function checkFormatParam($param)
    {
        $error = [];
        $data = [];
        foreach ($param as $key => $val) {
            $result = $this->validate($val);
            if ($result) {
                array_push($error, $result);
            } else {
                array_push($data, $val);
            }
        }
        return [$data, $error];
    }


    /**
     * 检测参数
     * @param $param
     */
    public function _checkFormatParam($param)
    {
        $datas = [];
        foreach ($param as $key => $val) {
            // 验证输入字段是否有正常格式
            $result = $this->validate($val);
            if ($result) {
                throw new JsonErrorException($result);
            }
            // 验证 字段是否出现重复城市
            $data = $this->_isExistCity($val);
            array_push($datas, $data);
        }
        return $datas;
    }

    /**
     * 检测传输数据
     * @param $param
     * @return mixed
     */
    public function checkParam($param)
    {
        $country_code = param($param, 'country_code', '');
        unset($param['country_code']);
        if (!isset($param['detail']) && empty($param['detail'])) {
            throw new JsonErrorException('参数detial 必填');
        }
        $detail = $param['detail'];
        // 判断country_code 是否存在
        list($data, $code) = $this->countryModel->hasCountryCode($country_code);
        if ($code) {
            throw new JsonErrorException($data);
        }
        $detail = json_decode($detail, true);

        foreach ($detail as $key => $item) {
            $detail[$key]['country_code'] = $country_code;
        }
        return $detail;
    }

    /**
     * 判断该国家内的城市是否存在
     * @param $country_code
     * @param $english_name
     */
    public function isExistCity($val)
    {
        $data = [];
        $data['english_name'] = $val['english_name'];
        $where['english_name'] = $val['english_name'];
        $where['country_code'] = $val['country_code'];
        $data['country_code'] = $val['country_code'];
        $data['name'] = $val['name'];
        $data['id'] = intval($val['id']);
        $result = (new Area())->field('id,english_name,country_code')->where($where)->find();
        if ($result && $result->id !== $data['id']) {
            throw new JsonErrorException('该城市英文名已存在');
        }
    }

    /**
     * 存在则替换
     * @param $val
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function _isExistCity($val)
    {
        $data = [];
        $data['english_name'] = $val['english_name'];
        $where['english_name'] = $val['english_name'];
        $where['country_code'] = $val['country_code'];
        $data['country_code'] = $val['country_code'];
        $data['name'] = $val['name'];
        $result = (new Area())->field('id,english_name,country_code')->where($where)->find();

        if ($result) {
            // 存在则替换
            $data['english_name'] = $val['english_name'];
            $data['country_code'] = $val['country_code'];
            $data['id'] = $result->id;
        }
        return $data;
    }

    /**
     * 更新 城市名
     * @param $param
     * @param $id
     * @return array
     * @throws Exception
     */
    public function update($param, $id)
    {
        $data = $this->_checkParam($param, $id);
        $validate = new AreaValidate();
        $flag = $validate->scene('update')->check($data);
        if ($flag == false) {
            throw new JsonErrorException($validate->getError());
        }

        list($res, $code) = $this->countryModel->hasCountryCode(param($param, 'country_code', ''));
        if ($code) {
            throw new JsonErrorException($res, 500);
        }

        try {
            $this->isExistCity($data);
            $this->areaModel->allowField(true)->update($data, ['id' => $id]);
            return ['code' => 200, 'message' => '更新成功'];
        } catch (Exception $e) {
            throw new JsonErrorException('更新失败');
        }
    }

    /**
     * 检测参数及id
     * @param $param
     * @param $id
     * @return array
     */
    public function _checkParam($param, $id)
    {
        $data = [];
        list($res, $code) = $this->areaModel->hasId($id);
        if ($code) {
            throw new JsonErrorException($res, 500);
        }
        if (isset($param['english_name'])) {
            $data['english_name'] = trim($param['english_name']);
            if (empty($data['english_name'])) {
                unset($data['english_name']);
            }
        }
        if (isset($param['name'])) {
            $data['name'] = trim($param['name']);
        }
        if (isset($param['country_code'])) {
            $data['country_code'] = trim($param['country_code']);
        }
        $data['id'] = $id;
        return $data;
    }

    public function delete($id)
    {
        Db::startTrans();
        try {
            list($data, $code) = $this->areaModel->hasId($id);
            if ($code) {
                throw new JsonErrorException($data, 500);
            }
            $this->areaModel::where('id', $id)->delete();
            Db::commit();
            Cache::store('Area')->delCountryCodeList();
        } catch (Exception $exp) {
            Db::rollback();
            throw new JsonErrorException('删除失败', 500);
        }
    }

    /**
     * 获取城市ID
     * @param $country_code
     * @param $name
     * @return array|int
     */
    public function getCityIdByName($country_code, $name)
    {
        $id = $this->areaModel->where(['country_code' => $country_code, 'english_name' => $name])->column('id');
        return empty($id) ? 0 : $id;
    }

}