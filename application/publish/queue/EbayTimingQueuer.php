<?php

namespace app\publish\queue;

/**
 * 曾绍辉
 * 17-8-24
 * ebay定时刊登队列
*/
use app\common\service\CommonQueueJob;
use app\common\exception\TaskException;
use think\Db;
use app\common\cache\Cache;

class EbayTimingQueuer {
	protected $redis;
	protected $key;

	function __construct($key='collection'){
		$this->key = $key;
		#$this->redis = new \Redis();
		#$this->redis->connect('localhost', 6379); 
		$this->redis = Cache::handler(true);
	}

	/*
	*title 用于压入有序集合
	*@param $listId  定时执行的listingID
	*@param $timing  定时执行的时间
	*/
	public function production($param=[]){#生产队列
		try{
			$this->redis->zAdd($this->key,$param['timing'],$param['listId']);
		}catch(TaskException $e){
			throw new TaskException($e->getMessage());
		}		
	}

	/*
	*title 获取消费数据
	*@param $start 开始时间
	*@param $end 截止时间
	*/
	public function consumption($param=[]){#消费队列
		try{
			return $this->redis->zRangeByScore($this->key,$param['start'],$param['end']);
		}catch(TaskException $e){
			throw new TaskException($e->getMessage());
		}
	}

	/*
	*title 获取消费数据并将集合中数据移除(根据集合分数索引)
	*@param $start 区间起始值
	*@param $end 区间结束值
	*/
	public function consumptionRem($param=[]){
		try{
			$res = $this->redis->zRangeByScore($this->key,$param['start'],$param['end']);
			$this->redis->zRemRangeByScore($this->key,$param['start'],$param['end']);
			return $res;
		}catch(TaskException $e){
			throw new TaskException($e->getMessage());
		}
	}

	/*
	*title 移除集合中指定数据
	*@param $listId 定时执行的listingID
	*/
	public function removeRem($param=[]){
		try{
			$res = $this->redis->zDelete($this->key,$param['listId']);
		}catch(TaskException $e){
			throw new TaskException($e->getMessage());
		}
	}
}

