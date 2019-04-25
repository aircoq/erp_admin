<?php
namespace app\publish\task;
/**
 * rocky
 * 17-4-17
 * ebay获取商户类目属性
*/

use app\index\service\AbsTasker;
use app\common\cache\Cache;
use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayCategory;
use app\common\model\ebay\EbaySite;
use app\common\model\ebay\EbayCategorySpecific;
use service\ebay\EbayApi;
use think\Db;
use app\publish\service\EbayPackApi;
use app\publish\service\EbayDealApiInformation;
use app\publish\service\EbayConstants as Constants;
use think\Exception;

class EbayGetCategorySpecifics extends AbsTasker
{
    private $cacheKey = 'ebay:task:ebay_get_categoryspecifics';

	public function getName()
    {
        return "ebay获取商户类目属性";
    }
    
    public function getDesc()
    {
        return "ebay获取商户类目属性";
    }
    
    public function getCreator()
    {
        return "wlw2533";
    }
    
    public function getParamRule()
    {
        $siteInfo = (new EbaySite())->field('siteid,name')->select();
        $tmp = [];
        foreach ($siteInfo as $site) {
            $tmp[] = $site['name'].':'.$site['siteid'];
        }
        $str = implode(',', $tmp);
        return ['site|站点' => 'request|select:'.'全部站点:-1,'.$str];
    }
    
    
    public function execute()
    {
        try {
            self::GetCategorySpecifics();
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    public function GetCategorySpecifics(){
        try {
            set_time_limit(0);
            $xmlInfo = [];
            $param = $this->getData('site');
            $record = Cache::get($this->cacheKey.'_'.$param);//获取缓存记录
            $siteIds = EbaySite::order('siteid')->column('siteid');
            $siteIndex = 0;
            if ($record === false) {
                if ($param == -1) {//全部站点
                    $xmlInfo['site_id'] = $siteIds[0];
                    $siteIndex = 0;
                } else {//单个站点
                    $xmlInfo['site_id'] = $param;
                }
                $categoryIndex = 0;
            } else {//解析缓存
                $record = explode(',', $record);
                if ($record[0] == -1) {
                    throw new Exception('已全部获取，请停止或清除缓存:'.$this->cacheKey.'_'.$param.'后重新运行');
                }
                $categoryIndex = intval($record[1]);//记录当前分类组索引
                if ($param == -1) { //全部站点
                    $siteIndex = $record[0];
                    $xmlInfo['site_id'] = $siteIds[$siteIndex];
                } else {
                    $xmlInfo['site_id'] = $param;
                }
            }
            $allCategories = EbayCategory::where(['site' => $xmlInfo['site_id']])->distinct(true)->column('category_id');
            $allParentCategories = EbayCategory::where(['site'=>$xmlInfo['site_id']])->distinct(true)->column('category_parent_id');
            $endLeafCategories = array_diff($allCategories, $allParentCategories);
            $categoryGroup = array_chunk($endLeafCategories, 50);
            $xmlInfo['category_id'] = $categoryGroup[$categoryIndex];

            $verb = "GetCategorySpecifics";
            $accountInfo = Cache::store('EbayAccount')->getTableRecord(1);
            $ebayPackObj = new EbayPackApi();
            $ebayResObj = new EbayDealApiInformation();
            $ebayApi = $ebayPackObj->createApi($accountInfo, $verb, $xmlInfo['site_id']);
            $xml = $ebayPackObj->createXml($xmlInfo);
            $res = $ebayApi->createHeaders()->__set('requesBody', $xml)->sendHttpRequest2();
            Cache::handler()->set($this->cacheKey.'_debug', date('Y-m-d H:i:s').'|'.$param.'|'.$xml.'|'.json_encode($res));
            $result = $ebayResObj->dealWithApiResponse($verb, $res, $xmlInfo);
            if ($result === true) {
                $categoryIndex++;
                if ($categoryIndex == count($categoryGroup)) {//获取完毕
                    if ($param == -1) { //全部站点
                        $siteIndex++;
                        $siteIndex = $siteIndex % (Constants::EBAY_SITE_COUNT);//切换站点
                        $siteIndex == 0 && $siteIndex = -1;//循环一遍后不再循环
                        $categoryIndex = 0;
                    } else { //单站点
                        $siteIndex = -1;
                    }
                }
                Cache::handler()->set($this->cacheKey.'_'.$param, $siteIndex . ',' . $categoryIndex);//更新缓存
            }
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }
    
}