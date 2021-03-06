<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/8/29
 * Time: 10:50
 */

namespace app\publish\helper\lazada;

/**
 * Class LazadaUtil
 * @package app\publish\helper\shopee
 * @author thomas
 * @date 2019/3/26
 */
class LazadaUtil
{
    /**
     * @param $siteId 站点id
     */
    public static function getSiteLink($siteCode)
    {
        $siteLinkPrefix = 'https://api.lazada.';
        switch ($siteCode) {
            case 'th':
                $siteLink =  $siteLinkPrefix.'co.th/rest';
                break;
            case 'my':
                $siteLink =  $siteLinkPrefix.'com.my/rest';
                break;
            case 'id':
                $siteLink =  $siteLinkPrefix.'co.id/rest';
                break;
            case 'ph':
                $siteLink =  $siteLinkPrefix.'com.ph/rest';
                break;
            case 'sg':
                $siteLink =  $siteLinkPrefix.'sg/rest';
                break;
            case 'vn':
                $siteLink =  $siteLinkPrefix.'vn/rest';
                break;
            default:
                $siteLink = '';
                break;
        }
        return $siteLink;
    }

    /**
     * @param $siteId 站点id
     */
    public static function getSiteIdBySiteCode($siteCode)
    {
        switch ($siteCode) {
            case 'th':
                $siteId =  1;
                break;
            case 'my':
                $siteId =  2;
                break;
            case 'id':
                $siteId =  3;
                break;
            case 'ph':
                $siteId =  4;
                break;
            case 'sg':
                $siteId =  5;
                break;
            case 'vn':
                $siteId =  6;
                break;
            default:
                $siteId = 0;
                break;
        }
        return $siteId;
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


    /**
     *  分类树到二维数组的转换
     * @param $cagetoryTree 分类树
     * @param int $parnetId
     * exp :  获取api
     */
    public static function categoryTreeToArr2($catetoryTree, $parnetId = 0)
    {
        $oneCategory = [];
        static $childrenCategory = [];
        foreach ($catetoryTree as $k=>$v) {

            $oneCategory['category_name'] = $v['name'];
            $oneCategory['category_id'] = $v['category_id'];
            $oneCategory['has_children'] = empty($v['leaf']) ? 0 : 1;
            $oneCategory['parent_id'] = $parnetId;
            array_push($childrenCategory, $oneCategory);
            if (isset($v['children'])) {
                $children = $v['children'];
                $recursionChildren = self::recursionChild($children, $v['category_id']);
                foreach ($recursionChildren as $kk=>$vv) {  //转化二维数组
                    array_push($childrenCategory, $vv);
                }
            }
        }
        $categories = $childrenCategory;
        $childrenCategory = [];
        return $categories;
    }

    private static function recursionChild($children, $parnetId = 0)
    {
        static $temp = [];
        foreach ($children as $k=>$v) {
            $temp[$v['category_id']]['category_name'] = $v['name'];
            $temp[$v['category_id']]['category_id'] = $v['category_id'];
            $temp[$v['category_id']]['has_children'] = empty($v['leaf']) ? 0 : 1;
            $temp[$v['category_id']]['parent_id'] = $parnetId;
            if (isset($v['children'])) {
                foreach ($v['children'] as $kk=>&$vv) {
                    $temp[$vv['category_id']]['category_name'] = $vv['name'];
                    $temp[$vv['category_id']]['category_id'] = $vv['category_id'];
                    $temp[$vv['category_id']]['has_children'] = empty($vv['leaf']) ? 0 : 1;
                    $temp[$vv['category_id']]['parent_id'] = $v['category_id'];
                    if (isset($vv['children'])) {
                        $temp = static::recursionChild($vv['children'], $vv['category_id']);
                    }
                }
            }
        }
        $children = $temp;
        $temp  = [];
        return $children;
    }

    /**
     * 时间格式time转化为iso格式：
     * @param $time
     * @return false|string
     */
    public static function convertTimeToIso8601($time)
    {
        if (is_numeric($time)) {
            $time = date(DATE_ISO8601, $time);
        } else {
            $time = date(DATE_ISO8601, strtotime($time));
        }
        return $time;
    }

    /**
     * @param $data 请求数据
     * @param bool $isPorductTag 是否带Product标签
     * @param bool $isMultiImage 是否组装多图片的url
     * @return string
     */
    public static function buildXml($data, $isProductTag = true, $isMultiImage = false)
    {

        $header = '<?xml version="1.0" encoding="UTF-8" ?><Request>';
        if ($isProductTag) {
            $header .= '<Product>';
            $xmlData = self::dataToXml($data);
            $end = '</Product></Request>';
        } else {
            $xmlData = self::imageDataToXml($data, $isMultiImage);
            $end = '</Request>';
        }

        return $header . $xmlData . $end;
    }

    /**
     * Array to XML.
     *
     * @param array  $data
     * @param string $item
     * @param string $id
     *
     * @return string
     */
    private static function dataToXml($data, $item = 'item', $id = 'id')
    {
        $xml = '';
        foreach ($data as $key => $val) {
            if (is_numeric($key)) {
//                $id && $attr = " {$id}=\"{$key}\"";
                $key = $item;
            }
            $xml .= "<{$key}>";
            if ((is_array($val) || is_object($val))) {
                if ($key == 'Skus') {
                    $xml .= self::dataToXml((array) $val, 'Sku', $id);
                } elseif ($key == 'Images') {
                    $xml .= self::dataToXml((array) $val, 'Image', $id);
                } else {
                    $xml .= self::dataToXml((array) $val, $item, $id);
                }
            } else if ($key == 'description') {
                $xml .= self::cdata($val);
            } else {
                $xml .= is_numeric($val) ? $val : $val;
            }

            $xml .= "</{$key}>";

        }
        return $xml;
    }

    /**
     * Image Array to XML.
     *
     * @param array  $data
     * @param string $item
     * @param string $id
     *
     * @return string
     */
    private static function imageDataToXml($data, $isMultiImage)
    {
        $xml = '';
        //多图片
        if ($isMultiImage) {
            $start = '<Images>';
            $end = '</Images>';
        } else {
            //单张图片
            $start = '<Image>';
            $end   = '</Image>';
        }
        foreach ($data as $key => $val) {
            $xml .= '<Url>';
            $xml .=  $val;
            $xml .= '</Url>';
        }
        return $start . $xml . $end;
    }


    /**
     * Build CDATA.
     *
     * @param string $string
     *
     * @return string
     */
    public static function cdata($string)
    {
        return sprintf('<![CDATA[%s]]>', $string);
    }

}