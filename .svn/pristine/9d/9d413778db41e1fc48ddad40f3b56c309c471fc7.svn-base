<?php
namespace app\publish\service;

class AmazonPublishConfig
{
    public static $baseCurrencyCode = [
        'US'=>'USD',
        'MX'=>'MXN',
        'IN'=>'INR',
        'BR'=>'BRL',
        'AU'=>'AUD',
        'CA'=>'CAD',
        'UK'=>'GBP',
        'DE'=>'EUR',
        'ES'=>'EUR',
        'FR'=>'EUR',
        'IT'=>'EUR',
        'JP'=>'JPY',
        'DEFAULT'=>'DEFAULT'
    ];
    public static $fulfillmentCenterID = [
        0=>'DEFAULT',
        1=>'AMAZON_NA',
        2=>'AMAZON_EU',
        3=>'AMAZON_BR',
    ];
    const FULFILLMENT_LATENCY = 2;

    const FEED_TYPE_PRODUCT_DATA = '_POST_PRODUCT_DATA_';
    const FEED_TYPE_PRICING_DATA = '_POST_PRODUCT_PRICING_DATA_';
    const FEED_TYPE_INVENTORY_AVAILABILITY_DATA = '_POST_INVENTORY_AVAILABILITY_DATA_';
    const FEED_TYPE_PRODUCT_IMAGE_DATA = '_POST_PRODUCT_IMAGE_DATA_';
    const FEED_TYPE_PRODUCT_RELATIONSHIP_DATA = '_POST_PRODUCT_RELATIONSHIP_DATA_';

    public static $xml_message_type = [
        self::FEED_TYPE_PRODUCT_DATA=>self::XML_MESSAGE_TYPE_PRODUCT,
        self::FEED_TYPE_PRICING_DATA=>self::XML_MESSAGE_TYPE_PRICING,
        self::FEED_TYPE_INVENTORY_AVAILABILITY_DATA=>self::XML_MESSAGE_TYPE_INVENTORY_AVAILABILITY,
        self::FEED_TYPE_PRODUCT_IMAGE_DATA=>self::XML_MESSAGE_TYPE_IMAGE,
        self::FEED_TYPE_PRODUCT_RELATIONSHIP_DATA=>self::XML_MESSAGE_TYPE_RELATIONSHIP,
    ];

    /** 未刊登 */
    const PUBLISH_STATUS_NONE = 0;
    /** 刊登中 */
    const PUBLISH_STATUS_UNDERWAY = 1;
    /** 刊登成功 */
    const PUBLISH_STATUS_FINISH = 2;
    /** 刊登失败 */
    const PUBLISH_STATUS_ERROR = 3;
    /** 刊登完成编辑 */
    const PUBLISH_STATUS_RE_EDIT = 4;
    /** 刊登草稿 */
    const PUBLISH_STATUS_DRAFT = 5;

    /** 详情未刊登 */
    const DETAIL_PUBLISH_STATUS_NONE = 0;
    /** 详情刊登成功 */
    const DETAIL_PUBLISH_STATUS_FINISH = 1;
    /** 详情刊登失败 */
    const DETAIL_PUBLISH_STATUS_ERROR = 2;

    /** 刊登父产品类别 */
    const PUBLISH_TYPE_PARENT = 0;
    /** 刊登子产品类别 */
    const PUBLISH_TYPE_CHILDREN = 1;

    const XML_ENVELOPE_START = '<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amznenvelope.xsd">';

    const XML_ENVELOPE_END = '
</AmazonEnvelope>';

    const XML_HEADER = '
<Header>
<DocumentVersion>1.01</DocumentVersion>
<MerchantIdentifier>{$merchant_identifier}</MerchantIdentifier>
</Header>';

    //MessageType
    const XML_MESSAGE_TYPE_PRODUCT = '
<MessageType>Product</MessageType>';
    const XML_MESSAGE_TYPE_INVENTORY_AVAILABILITY = '
<MessageType>Inventory</MessageType>';
    const XML_MESSAGE_TYPE_PRICING = '
<MessageType>Price</MessageType>';
    const XML_MESSAGE_TYPE_IMAGE = '
<MessageType>ProductImage</MessageType>';
    const XML_MESSAGE_TYPE_RELATIONSHIP = '
<MessageType>Relationship</MessageType>';

    //title
    const XML_ITEM_NAME = '
<Message>
<MessageID>{$message_id}</MessageID>
<OperationType>PartialUpdate</OperationType>
<Product>
<SKU>{$seller_sku}</SKU>
<DescriptionData>
<Title>{$item_name}</Title>
</DescriptionData>
</Product>
</Message>';

    //description
    const XML_ITEM_DESCRIPTION = '
<Message>
<MessageID>{$message_id}</MessageID>
<OperationType>PartialUpdate</OperationType>
<Product>
<SKU>{$seller_sku}</SKU>
<DescriptionData>
<Description>{$description}</Description>
</DescriptionData>
</Product>
</Message>';

    //delete
    const XML_DELETE_LISTING = '
<Message>
<MessageID>{$message_id}</MessageID>
<OperationType>Delete</OperationType>
<Product>
<SKU>{$seller_sku}</SKU>
</Product>
</Message>';

    //inventory
    //fbm
    const XML_FULFILLMENT_TYPE_MFN = '
<Message>
<MessageID>{$message_id}</MessageID>
<OperationType>Update</OperationType>
<Inventory>
<SKU>{$seller_sku}</SKU>
<FulfillmentCenterID>{$fulfillmentCenterID}</FulfillmentCenterID>
<Quantity>{$quantity}</Quantity>
<SwitchFulfillmentTo>MFN</SwitchFulfillmentTo>
</Inventory>
</Message>';

    //fba
    const XML_FULFILLMENT_TYPE_AFN = '
<Message>
<MessageID>{$message_id}</MessageID>
<OperationType>Update</OperationType>
<Inventory>
<SKU>{$seller_sku}</SKU>
<FulfillmentCenterID>{$fulfillmentCenterID}</FulfillmentCenterID>
<Lookup>FulfillmentNetwork</Lookup>
<SwitchFulfillmentTo>AFN</SwitchFulfillmentTo>
</Inventory>
</Message>';

    //quantity
    const XML_QUANTITY = '
<Message>
<MessageID>{$message_id}</MessageID>
<OperationType>Update</OperationType>
<Inventory>
<SKU>{$seller_sku}</SKU>
<Quantity>{$qunatity}</Quantity>
</Inventory>
</Message>';

    //price
    const XML_PRICE = '
<Message>
<MessageID>{$message_id}</MessageID>
<Price>
<SKU>{$seller_sku}</SKU>
<StandardPrice currency="{$currency}">{$price}</StandardPrice>
</Price>
</Message>';

    //images
    const XML_IMAGE_MAIN = '
<Message>
<MessageID>{$message_id}</MessageID>
<OperationType>Update</OperationType>
<ProductImage>
<SKU>{$seller_sku}</SKU>
<ImageType>Main</ImageType>
<ImageLocation>{$image_location}</ImageLocation>
</ProductImage>
</Message>';
    const XML_IMAGE_SWATCH = '
<Message>
<MessageID>{$message_id}</MessageID>
<OperationType>Update</OperationType>
<ProductImage>
<SKU>{$seller_sku}</SKU>
<ImageType>Swatch</ImageType>
<ImageLocation>{$image_location}</ImageLocation>
</ProductImage>
</Message>';
    const XML_IMAGE_PT = '
<Message>
<MessageID>{$message_id}</MessageID>
<OperationType>Update</OperationType>
<ProductImage>
<SKU>{$seller_sku}</SKU>
<ImageType>PT{$index}</ImageType>
<ImageLocation>{$image_location}</ImageLocation>
</ProductImage>
</Message>';

    //relationship
    const XML_RELATIONSHIP = '
<Message>
<MessageID>{$message_id}</MessageID>
<OperationType>Update</OperationType>
<Relationship>
<ParentSKU>{$seller_spu}</ParentSKU>
<Relation>
<SKU>{$seller_sku}</SKU>
<Type>{$relation_type}</Type>
</Relation>
</Relationship>
</Message>
    ';
}