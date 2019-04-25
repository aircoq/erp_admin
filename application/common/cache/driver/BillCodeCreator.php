<?php
namespace app\common\cache\driver;

use think\Db;
use think\Exception;
use app\common\cache\Cache;


/** yangweiquan 20170603 */
class BillCodeCreator extends Cache
{
    /** 获取当天最后一个采购单号 PO+年月日+三位序列号
     * @param string $key  //类似PO20170101   单号前缀由 单据类型码+日期构成
     * @return mixed|static
     */   	
	private $billType = 'PO';
    public $message = "";

    private function setBillType($billType = 'PO'){
        $this->billType = $billType;
    }

    private function getLastBillCode()
    {
        $key = $this->getCurrentBillNoPrefixion();
        if($this->redis->exists($key)){
			return $this->redis->get($key);
		}else{
			$billNumber = $key.'001';
            $this->setLastBillCode($billNumber);
            return 	$billNumber;
		}
        
    }
	
    /** 设置当天最后一个采购单号
     * @param string $billNumberPrefixion  //类似20170101
	 * @param string $value  //PO20170101001
     * @return mixed|static
     */
    public function setLastBillCode($billNumber)
    {
        $billNumberPrefixion = $this->getPrefixionByBillNo($billNumber);
        $this->redis->set($billNumberPrefixion,$billNumber);
		$this->redis->expire($billNumberPrefixion,86400);//两天后过期
		//echo $this->redis->TTL($key);
    }

    /** 从PO号得出序列号1 2 3  999  也就是取得最后三位,并转为数字
     * @param int $id 
     * @return bool
     */ 
    private function getSerialNumberByBillNo($billNumber)
    {
		return (int)(substr($billNumber,-3,3));
        
    }
	
    /** 从PO号得出PO号前缀 //PO20170101001
     * @param int $id 
     * @return bool
     */ 
    public function getPrefixionByBillNo($billNumber)
    {
		return substr($billNumber,0,10);
        
    }
	
	
    /** 根据当前日期生成PO号前缀  类似 PO20170101
     * @param int $id 
     * @return bool
     */ 
    private function getCurrentBillNoPrefixion()
    {
		return $this->billType.date('Ymd');
    }
	
	/**将数字补足三位，本系统的单号一律为三位顺序号*/
	private function createSerialPad($number){
		return  sprintf('%03s', $number);
    }

    /**获得单号列表，需要几个单号，就传几个，返回的是顺序的从最后一个单号+1开始的单号列表
     假如最后一个单号是 PO20170602003,需要3个单号，则返回
     *  PO20170602004  PO20170602005  PO20170602006
     *
     */
    public function getBillNoList($billCount = 1,$billType = 'PO'){

          $this->setBillType($billType);
          $lastBillNumber =  $this->getLastBillCode();
          //echo $lastBillNumber;
          //$lastBillNumber = "PO20170603005";

          $startNumber = $this->getSerialNumberByBillNo($lastBillNumber);
          $billPrefixion = $this->getCurrentBillNoPrefixion();
           //echo $serialNumber;exit;

          $billNumberList = [];
          for($i = 1;$i <= $billCount;$i++){
              $serialNumber = $this->createSerialPad($startNumber+1);
              $billNumberList[] = $billPrefixion.$serialNumber;
          }
          //print_r($billNumberList);
          return $billNumberList;

    }
	
	
   
}