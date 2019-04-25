<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/9/10
 * Time: 14:23
 */

namespace app\publish\task;

use app\common\model\ebay\EbayAccount;
use app\common\service\ebay\EbayRestful;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\publish\helper\ebay\EbayPublish;
use app\publish\queue\EbayFixPBSE;
use think\Exception;

class EbayCheckViolationPBSE extends AbsTasker
{
    public function getName()
    {
        return "检查eBay已刊登listing是否违反PBSE政策";
    }

    public function getDesc()
    {
        return "检查eBay已刊登listing是否违反PBSE政策";
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
        return ['site|站点' => 'request|select:'.$str];
    }
    
    public function execute()
    {
        set_time_limit(0);
        try {
            $param = $this->getData('site');//站点
            if (!is_numeric($param)) {
                return false;
            }
            $wh = [
                'is_invalid' => 1,//启用
                'oauth_token' => ['neq', ''],//密钥存在
                'ot_invalid_time' => ['gt', time()],//密钥未过期
            ];
            $accountIds = EbayAccount::where($wh)->column('id');
            foreach ($accountIds as $accountId) {
                $this->getViolations($accountId, $param);
            }

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function getViolations($accountId, $site) {
        try {
            $oauthToken = EbayAccount::where(['id' => $accountId])->value('oauth_token');
            $header['X-EBAY-C-MARKETPLACE-ID'] = EbayPublish::MarketPlaceId[$site];
            $header['Authorization'] = 'Bearer '.$oauthToken;
            $url = 'https://api.ebay.com/sell/compliance/v1/listing_violation?compliance_type=PRODUCT_ADOPTION';
            $response = (new EbayRestful('GET', $header))->sendRequest($url);
            $res = json_decode($response, true);
            if (!empty($res['total']) && !empty($res['listingViolations'])) {
                $violations = $res['listingViolations'];
                $itemIds = [];
                foreach ($violations as $violation) {
                    $itemIds[] = $violation['listingId'];
                }
                if (!empty($itemIds)) {
                    (new UniqueQueuer(EbayFixPBSE::class))->push($itemIds);
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


}