<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/5/31
 * Time: 14:14
 */

namespace app\publish\task;


use app\index\service\AbsTasker;
use app\common\cache\Cache;
use app\common\model\ebay\EbaySite;
use app\publish\service\EbayPackApi;
use app\publish\service\EbayDealApiInformation;
use think\Exception;
use app\publish\service\EbayConstants as Constants;


class EbayGetShippingServices extends AbsTasker
{
    private $cacheKey = 'ebay:task:ebay_get_shippingservice';
    public function getName()
    {
        return "ebay获取可用物流方式";
    }

    public function getDesc()
    {
        return "ebay获取可用物流方式";
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
            self::GetShippingServices();
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * 获取站点支持的物流方式
     * 使用缓存记录当前站点，键值：siteIndex
     * @throws Exception
     */
    public function GetShippingServices()
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
            } else {//解析缓存
                if ($record == -1) {
                    throw new Exception('已全部获取，请停止或清除缓存:'.$this->cacheKey.'_'.$param.'后重新运行');
                }
                if ($param == -1) { //全部站点
                    $siteIndex = intval($record);
                    $xmlInfo['site_id'] = $siteIds[$siteIndex];
                } else {
                    $xmlInfo['site_id'] = $param;
                }
            }

            $verb = "GeteBayDetails";
            $accountInfo = Cache::store('EbayAccount')->getTableRecord(1);
            $ebayPackObj = new EbayPackApi();
            $ebayResObj = new EbayDealApiInformation();

            $xmlInfo['detail_name'] = 'ShippingServiceDetails';
            $ebayApi = $ebayPackObj->createApi($accountInfo, $verb, $xmlInfo['site_id']);
            $xml = $ebayPackObj->createXml($xmlInfo);
            $res = $ebayApi->createHeaders()->__set('requesBody', $xml)->sendHttpRequest2();
            //调试信息
            Cache::handler()->set($this->cacheKey.'_debug', date('Y-m-d H:i:s').'|'.$param.'|'.$xml.'|'.json_encode($res));
            //调试信息结束
            $result = $ebayResObj->dealWithApiResponse($verb, $res, $xmlInfo);
            if ($result === true) {
                if ($param == -1) {
                    $siteIndex++;
                    $siteIndex = $siteIndex % (Constants::EBAY_SITE_COUNT);
                    $siteIndex == 0 && $siteIndex = -1;
                } else {
                    $siteIndex = -1;
                }
                Cache::handler()->set($this->cacheKey.'_'.$param, $siteIndex);
            }
        } catch(Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }
}