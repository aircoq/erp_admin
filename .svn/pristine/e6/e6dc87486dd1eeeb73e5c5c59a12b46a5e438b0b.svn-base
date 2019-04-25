<?php
namespace app\common\cache\driver;

use think\Db;
use think\Exception;
use app\common\cache\Cache;
use app\common\model\User as UserModel;
use app\common\model\DeveloperSubclassMap;
/** yangweiquan 20170531 */
class PurchaseNo extends Cache
{
    /** 获取当天最后一个采购单号 PO+年月日+三位序列号
     * @param string $key  //类似PO20170101
     * @return mixed|static
     */
    public function getLastPoNumber($key)
    {		
        if($this->redis->exists($key)){
			return $this->redis->get($key);
		}else{
			$po_number = $key.'001';
            $this->setLastPoNumber($key,$po_number);
            return 	$po_number;	
		}       
		
        
    }
	
    /** 设置当天最后一个采购单号
     * @param string $key  //类似20170101
	 * @param string $value  //PO20170101001
     * @return mixed|static
     */
    public function setLastPoNumber($key,$po_number)
    {
        $this->redis->set($key,$po_number);
		$this->redis->expire($key,86400);
		//echo $this->redis->TTL($key);
    }

    /** 从PO号得出序列号1 2 3  999  也就是取得最后三位,并转为数字
     * @param int $id 
     * @return bool
     */ 
    public function getSerialNumberByPoNo($poNumber)
    {
		return intval(substr($poNumber,-3,3));
        
    }
	
    /** 从PO号得出PO号前缀 //PO20170101001
     * @param int $id 
     * @return bool
     */ 
    public function getPrefixionByPoNo($poNumber)
    {
		return substr($poNumber,0,10);
        
    }
	
	
    /** 根据当前日期生成PO号前缀  类似 PO20170101
     * @param int $id 
     * @return bool
     */ 
    public function getCurrentPoNubmerPrefixion()
    {
		return 'PO'.date('Ymd');
    }
	
	/**将数字补足三位*/
	public function getSerialPad($number){
		return  sprintf('%03s', $number);
    }

    	
	
	
   
}