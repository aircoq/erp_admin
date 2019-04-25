<?php
namespace app\publish\service;

class AmazonSiteMapConfig
{
    public static $siteMapConfig = [
        'JP'=>[
            '商品名' => 'item-name',
            '出品ID' => 'listing-id',
            '出品者SKU' => 'seller-sku',
            '価格' => 'price',
            '数量' => 'quantity',
            '出品日' => 'open-date',
            '商品IDタイプ' => 'product-id-type',
            'コンディション説明' => 'item-note',
            'コンディション' => 'item-condition',
            '国外へ配送可' => 'zshop-category1',
            '迅速な配送' => 'zshop-browse-path',
            '商品ID' => 'product-id',
            '在庫数' => 'pending-quantity',
            'フルフィルメント・チャンネル' => 'fulfillment-channel',
            'merchant-shipping-group' => 'merchant-shipping-group',
        ],
    ];
   }