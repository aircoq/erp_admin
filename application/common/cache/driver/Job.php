<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\Job as JobModel;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/10/18
 * Time: 9:43
 */
class Job extends Cache
{
    const cachePrefix = 'table';
    protected $job = self::cachePrefix.':job:table';
    protected $jobPrefix = self::cachePrefix.':job:';

    /**
     * 获取职务职位信息
     * @param int $id
     * @return array|mixed
     */
    public function getJob($id = 0)
    {
        $jobData = [];
        if (!empty($id)) {
            $key = $this->jobPrefix . $id;
            if ($this->isExists($key)) {
                $jobInfo = $this->redis->hGetAll($key);
            } else {
                $jobInfo = $this->readJob($id);
            }
            $jobData = $jobInfo;
        } else {
            if ($this->isExists($this->job)) {
                $jobId = $this->redis->hGetAll($this->job);
                foreach ($jobId as $key => $jid) {
                    $key = $this->jobPrefix . $jid;
                    $jobData[$jid] = $this->redis->hGetAll($key);
                }
            } else {
                $jobData = $this->readJob($id);
            }
        }
        return $jobData;
    }

    /** 获取职务信息
     * @param $id
     * @return array|mixed
     */
    private function readJob($id = 0)
    {
        $jobModel = new JobModel();
        $where = [];
        if (!empty($id)) {
            $where['id'] = ['eq', $id];
        }
        $new_list = [];
        $jobList = $jobModel->field(true)->where($where)->order('id asc')->select();
        foreach ($jobList as $key => $value) {
            $value = $value->toArray();
            $key = $this->jobPrefix . $value['id'];
            foreach ($value as $k => $v) {
                $this->setData($key, $k, $v);
            }
            $this->setTable($value['id'], $value['id']);
            $new_list[intval($value['id'])] = $value;
        }
        if (!empty($id)) {
            return isset($new_list[$id]) ? $new_list[$id] : [];
        } else {
            return $new_list;
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
            $this->redis->hSet($key, $field, $value);
        }
    }

    /**
     * 记录表一共有多少条记录
     * @param $field
     * @param $value
     */
    public function setTable($field, $value)
    {
        if (!$this->isFieldExists($this->job, $field)) {
            $this->redis->hSet($this->job, $field, $value);
        }
    }

    /**
     * 判断key是否存在
     * @param $key
     * @return bool
     */
    private function isExists($key)
    {
        if ($this->redis->exists($key)) {
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
        if ($this->redis->hExists($key, $field)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除缓存
     * @param $key
     */
    public function delete($key)
    {
        if (!empty($key)) {
            $this->redis->del($this->jobPrefix . $key);
            $this->redis->hDel($this->job, $key);
        } else {
            if ($this->redis->exists($this->job)) {
                $tableData = $this->redis->hGetAll($this->job);
                foreach ($tableData as $key => $value) {
                    $this->redis->del($this->jobPrefix . $key);
                }
                $this->redis->del($this->job);
            }
        }
    }
}