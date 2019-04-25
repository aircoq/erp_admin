<?php

namespace app\publish\queue;

/**
 * 曾绍辉
 * 17-8-5
 * ebay刊登队列
*/
use app\common\service\CommonQueueJob;
use app\common\exception\TaskException;
use think\Db;

class EbayQueuer extends CommonQueueJob{
	public function getName():string
	{
		return 'ebay刊登队列';
	}

	public function getDesc():string
	{
		return 'ebay刊登队列';
	}

	public function getAuthor():string
	{
		return 'zengsh';
	}
	
	public function production($ids=[]){#生产队列
		try{
			$idsArr = is_array($ids)?$ids:explode(",",$ids);
			foreach($idsArr as $id){
				$this->queuer->push($id);
			}
		}catch(TaskException $e){
			throw new TaskException($e->getMessage());
		}		
	}

	public function consumption(){#消费队列
		return $this->queuer->pop();
	}

}