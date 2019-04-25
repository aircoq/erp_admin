<?php
namespace app\publish\task;
/**
 * rocky-wlw2533
 * 17-4-11
 * ebay商户分类抓取任务
*/

use app\common\cache\Cache;
use app\index\service\AbsTasker;
use app\common\model\ebay\EbayCategory;
use app\common\model\ebay\EbaySite;
use app\publish\service\EbayDealApiInformation;
use think\Exception;
use app\publish\service\EbayPackApi;
use app\publish\service\EbayConstants as Constants;

class EbayGetCategorys extends AbsTasker
{
    private $cacheKey = 'ebay:task:ebay_get_categories';
    public function getName()
    {
        return "获取ebay商品类目";
    }
    
    public function getDesc()
    {
        return "获取ebay商品类目";
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
            self::GetCategories();
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * 一次性获取站点所有分类数据量过大，逻辑改为每次获取站点下的一个分类及其下的所有子类，使用缓存记录下次要获取的信息
     * 缓存键名：ebay:task:ebay_get_categories，键值：siteIndex,nextCategoryIndex
     * @throws Exception
     */
    public function GetCategories()
    {
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
                $xmlInfo['level_limit'] = 1;
                $categoryIndex = -1;
            } else {//解析缓存
                $record = explode(',', $record);
                if ($record[0] == -1) {
                    throw new Exception('已全部获取，请停止或清除缓存:'.$this->cacheKey.'_'.$param.'后重新运行');
                }
                $categoryIndex = $record[1];//记录当前分类索引
                if ($param == -1) { //全部站点
                    $siteIndex = $record[0];
                    $xmlInfo['site_id'] = $siteIds[$siteIndex];
                } else {
                    $xmlInfo['site_id'] = $param;
                }
            }
            if ($categoryIndex != -1) {
                $categories = EbayCategory::where(['site' => $xmlInfo['site_id'], 'category_level' => 1])
                    ->order('category_id')->column('category_id');
                $xmlInfo['parent_id'] = $categories[$categoryIndex];
            } else {//刚切换站点，先同步所有顶层分类
                $xmlInfo['level_limit'] = 1;
            }
            $verb = "GetCategories";
            $accountInfo = Cache::store('EbayAccount')->getTableRecord(1);
            $ebayPackObj = new EbayPackApi();
            $ebayResObj = new EbayDealApiInformation();
            $siteId = $xmlInfo['site_id'] == 100 ? 0 : $xmlInfo['site_id'];//ebayMotors站点特殊处理
            $ebayApi = $ebayPackObj->createApi($accountInfo, $verb, $siteId);
            $xml = $ebayPackObj->createXml($xmlInfo);
            $res = $ebayApi->createHeaders()->__set('requesBody', $xml)->sendHttpRequest2();
            Cache::handler()->set($this->cacheKey.'_debug', date('Y-m-d H:i:s').'|'.$param.'|'.$xml.'|'.json_encode($res));
            $result = $ebayResObj->dealWithApiResponse($verb, $res, $xmlInfo);

            if ($result === true) {//只有成功更新了才更新缓存
                //获取顶层总分类数
                $categoryCnt = EbayCategory::where(['site' => $xmlInfo['site_id'], 'category_level' => 1])->count();
                $categoryIndex++;
                if ($categoryIndex == $categoryCnt) {//分类获取完毕
                    if ($param == -1) { //全部站点
                        $siteIndex++;
                        $siteIndex = $siteIndex % (Constants::EBAY_SITE_COUNT);//切换站点
                        $siteIndex == 0 && $siteIndex = -1;//循环一遍后不再循环
                        $categoryIndex = -1;
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
    
//    public function createXmlGetCategories(string $token, int $siteId) : string
//    {
//        try {
//            $xml = '<?xml version="1.0" encoding="utf-8">';
//            $xml .= '<GetCategoriesRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
//            $xml .= '<RequesterCredentials>';
//            $xml .= '<eBayAuthToken>'.$token.'</eBayAuthToken>';
//            $xml .= '</RequesterCredentials>';
//            $xml .= '<ErrorLanguage>zh_CN</ErrorLanguage>';
//            $xml .= '<WarningLevel>High</WarningLevel>';
//            $xml .= '<CategorySiteID>'.$siteId.'</CategorySiteID>';
////            $xml .= '<CategoryParent>2984</CategoryParent>';
//            $xml .= '<DetailLevel>ReturnAll</DetailLevel>';
////            $xml .= '<LevelLimit>3</LevelLimit>';
//            $xml .= '</GetCategoriesRequest>';
//            return $xml;
//        } catch(Exception $e) {
//            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
//        }
//    }

}
