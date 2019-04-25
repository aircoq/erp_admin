<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/8/29
 * Time: 10:50
 */

namespace app\publish\helper\shopee;

/**
 * Class ShopeeUtil
 * @package app\publish\helper\shopee
 * @author thomas
 * @date 2019/3/26
 */
class ShopeeUtil
{
    /**
     * @param $siteId 站点id
     */
    public static function getSiteLink($siteCode)
    {
        $siteLinkPrefix = 'https://shopee.';
        switch ($siteCode) {
            case 'th':
                $siteLink =  $siteLinkPrefix.'co.th/';
                break;
            case 'my':
                $siteLink =  $siteLinkPrefix.'com.my/';
                break;
            case 'tw':
                $siteLink =  $siteLinkPrefix.'tw/';
                break;
            case 'id':
                $siteLink =  $siteLinkPrefix.'co.id/';
                break;
            case 'ph':
                $siteLink =  $siteLinkPrefix.'ph/';
                break;
            case 'sg':
                $siteLink =  $siteLinkPrefix.'sg/';
                break;
            case 'vn':
                $siteLink =  $siteLinkPrefix.'vn/';
                break;
            default:
                $siteLink = '';
                break;
        }
        return $siteLink;
    }

    /**
     * @param $shopId   店铺id
     * @param $itemId   item_id
     *
     * exp : https://shopee.sg/product/66484404/1400972938/
     * 66484404 ： 店铺id  1400972938 item_id
     */
    public static function getProductLink($siteCode, $shopId, $itemId)
    {
        $siteLink = self::getSiteLink($siteCode);
        $productLink = $siteLink .'product/'. $shopId. '/' . $itemId;
        return $productLink;
    }
}