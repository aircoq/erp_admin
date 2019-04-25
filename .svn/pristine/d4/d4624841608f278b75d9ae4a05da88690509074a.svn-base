<?php
/**
 * TOP API: aliexpress.message.redefining.versiontwo.updatemsgrank request
 * 
 * @author auto create
 * @since 1.0, 2018.01.25
 */
class AliexpressMessageRedefiningVersiontwoUpdatemsgrankRequest
{
	/** 
	 * 通道ID(即关系ID)
	 **/
	private $channelId;
	
	/** 
	 * 标签值(0,1,2,3,4,5)依次表示为白，红，橙，绿，蓝，紫
	 **/
	private $rank;
	
	private $apiParas = array();
	
	public function setChannelId($channelId)
	{
		$this->channelId = $channelId;
		$this->apiParas["channel_id"] = $channelId;
	}

	public function getChannelId()
	{
		return $this->channelId;
	}

	public function setRank($rank)
	{
		$this->rank = $rank;
		$this->apiParas["rank"] = $rank;
	}

	public function getRank()
	{
		return $this->rank;
	}

	public function getApiMethodName()
	{
		return "aliexpress.message.redefining.versiontwo.updatemsgrank";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
