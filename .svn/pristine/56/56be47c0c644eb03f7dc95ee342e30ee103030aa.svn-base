<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-2-27
 * Time: 上午10:32
 */

namespace app\common\service;


class ChannelConst
{
    const Amazon = 'Amazon';
    const eBay = 'eBay';
    const AliExpress = 'AliExpress';
    const wish = 'wish';
    const Lazada = 'Lazada';
    const Cdiscount = 'Cdiscount';
    const Joom = 'joom';
    const CD = 'cd';
    const Pandao = 'pandao';
    const Shopee = 'shopee';
    const Paytm = 'paytm';
    const Walmart = 'walmart';
    const Vova = 'vova';
    const Jumia = 'jumia';
    const Umka = 'umka';
    /**
     * @param $id
     * @return string
     */
    public static function channelId2Name($id)
    {
        switch ($id){
            case ChannelAccountConst::channel_aliExpress:
                return self::AliExpress;
            case ChannelAccountConst::channel_CD:
                return self::Cdiscount;
            case ChannelAccountConst::channel_ebay:
                return self::eBay;
            case ChannelAccountConst::channel_Lazada:
                return self::Lazada;
            case ChannelAccountConst::channel_wish:
                return self::wish;
            case ChannelAccountConst::channel_Joom:
                return self::Joom;
            case ChannelAccountConst::channel_Pandao:
                return self::Pandao;
            case ChannelAccountConst::channel_Shopee:
                return self::Shopee;
            case ChannelAccountConst::channel_Paytm:
                return self::Paytm;
            case ChannelAccountConst::channel_Vova:
                return self::Vova;
        }
    }
}