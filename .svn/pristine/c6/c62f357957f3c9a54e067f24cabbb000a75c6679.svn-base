<?php
namespace app\common\traits;

use app\common\cache\Cache;
use think\Exception;
use think\Model;

/**
 * 缓存表基础操作方法
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/12/21
 * Time: 20:15
 */
trait CacheTable
{
    protected $cachePrefix = 'table';
    private $model;
    private $table;
    private $tableLists;  //目录
    private $tableRecordPrefix;    //记录

    /**
     * 获取对象模型
     * @param $model
     * @throws Exception
     */
    private function model($model)
    {
        try {
            $this->model = new $model;
            if ($this->model instanceof Model) {
                $this->table($model);
            } else {
                throw new Exception('the model error');
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 获取表名
     * @param $model
     * @return $this
     */
    private function table($model)
    {
        $modelArr = explode('\\', $model);
        $tableName = end($modelArr);
        $tableName = $this->humpToLine($tableName);
        $this->table = $tableName;
        $this->tableListKey();
        $this->tableDataPrefix();
    }

    /**
     * 表目录key
     * @return $this
     */
    private function tableListKey()
    {
        $this->tableLists = $this->cachePrefix . ':' . $this->table . ':table';
        return $this;
    }

    /**
     * 表记录前缀
     * @return $this
     */
    private function tableDataPrefix()
    {
        $this->tableRecordPrefix = $this->cachePrefix . ':' . $this->table . ':';
        return $this;
    }

    /**
     * 获取缓存对象
     * @param bool|false $handle
     * @return \Redis
     */
    private function cacheObj($handle = false)
    {
        return Cache::handler($handle);
    }

    /** 读取表记录信息
     * @param $id
     * @return array|mixed
     */
    private function readTable($id = 0, $cache = true)
    {
        $newList = [];
        $dataList = $this->model->field(true)->order('id asc')->select();
        foreach ($dataList as $key => $value) {
            $value = $value->toArray();
            if ($cache) {
                $key = $this->tableRecordPrefix . $value['id'];
                foreach ($value as $k => $v) {
                    $this->setData($key, $k, $v);
                }
                $this->setTable($value['id'], $value['id']);
            }
            $newList[intval($value['id'])] = $value;
        }
        if (!empty($id)) {
            return isset($newList[$id]) ? $newList[$id] : [];
        } else {
            return $newList;
        }
    }

    /**
     * 判断key是否存在
     * @param $key
     * @return bool
     */
    private function isExists($key)
    {
        if ($this->cacheObj()->exists($key)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断域是否存在
     * @param $key
     * @param $field
     * @return bool
     */
    private function isFieldExists($key, $field)
    {
        if ($this->cacheObj()->hExists($key, $field)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置值
     * @param $key
     * @param $field
     * @param $value
     */
    public function setData($key, $field, $value)
    {
        if (!$this->isFieldExists($key, $field)) {
            $this->cacheObj()->hSet($key, $field, $value);
        }
    }

    /**
     * 获取信息
     * @param int $id
     * @return array|mixed
     */
    public function getTableRecord($id = 0)
    {
        $recordData = [];
        if (!empty($id)) {
            $key = $this->tableRecordPrefix . $id;
            if ($this->isExists($key)) {
                $info = $this->cacheObj()->hGetAll($key);
            } else {
                $info = $this->readTable($id);
            }
            $recordData = $info;
        } else {
            if ($this->isExists($this->tableLists)) {
                $recordId = $this->cacheObj()->hGetAll($this->tableLists);
                foreach ($recordId as $key => $aid) {
                    $key = $this->tableRecordPrefix . $aid;
                    $recordData[$aid] = $this->cacheObj()->hGetAll($key);
                }
            } else {
                $recordData = $this->readTable($id);
            }
        }
        return $recordData;
    }

    /**
     * 新增一条记录
     * @param int $id
     * @return array|mixed
     */
    public function setTableRecord($id)
    {
        if (!empty($id)) {
            $key = $this->tableRecordPrefix . $id;
            if (!$this->isExists($key)) {
                $dataInfo = $this->model->field(true)->where(['id' => $id])->order('id asc')->find();
                if(!empty($dataInfo)){
                    $dataInfo = $dataInfo->toArray();
                    $key = $this->tableRecordPrefix . $dataInfo['id'];
                    foreach ($dataInfo as $k => $v) {
                        $this->setData($key, $k, $v);
                    }
                    $this->setTable($dataInfo['id'], $dataInfo['id']);
                }
            }
        }
    }

    /**
     * 记录表一共有多少条记录
     * @param $field
     * @param $value
     */
    public function setTable($field, $value)
    {
        if (!$this->isFieldExists($this->tableLists, $field)) {
            $this->cacheObj()->hSet($this->tableLists, $field, $value);
        }
    }

    /**
     * 删除缓存信息
     * @param int $id
     */
    public function delTableRecord($id = 0)
    {
        if (!empty($id)) {
            $key = $this->tableRecordPrefix . $id;
            $this->cacheObj()->del($key);
            $this->cacheObj()->hDel($this->tableLists, $id);
        } else {
            $recordId = $this->cacheObj()->hGetAll($this->tableLists);
            foreach ($recordId as $key => $aid) {
                $key = $this->tableRecordPrefix . $aid;
                $this->cacheObj()->del($key);
            }
            $this->cacheObj()->del($this->tableLists);
        }
    }

    /**
     * 更新缓存信息
     * @param $id
     * @param $field
     * @param $value
     */
    public function updateTableRecord($id, $field, $value)
    {
        if (!empty($id)) {
            $key = $this->tableRecordPrefix . $id;
            if ($this->isFieldExists($key, $field)) {
                $this->cacheObj()->hSet($key, $field, $value);
            }
        }
    }

    /**
     * 驼峰转下划线
     * @param $str
     * @return mixed
     */
    private function humpToLine($str)
    {
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $str);
        $str = ltrim($str, '_');
        return $str;
    }
}