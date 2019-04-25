<?php
namespace app\carrier\type\winit;

class QueryOutboundOrderListDataType extends \app\carrier\type\BaseType
{
    /**
     * @var array Properties belonging to objects of this class.
     */
    private static $propertyTypes = [    
        'warehouseId' => [
            'type'       => 'integer',
            'repeatable' => false,
            'required'   => false
        ],
        'outboundOrderNum' => [
            'type'       => 'string',
            'repeatable' => false,
            'required'   => false
        ],
        'sellerOrderNo' => [
            'type'       => 'string',
            'repeatable' => false,
            'required'   => false
        ],
        'trackingNo' => [
            'type'       => 'string',
            'repeatable' => false,
            'required'   => false
        ],
        'receiverName' => [
            'type'       => 'string',
            'repeatable' => false,
            'required'   => false
        ],
        'bookingOperator' => [
            'type'       => 'string',
            'repeatable' => false,
            'required'   => false
        ],
        'productValue' => [
            'type'       => 'string',
            'repeatable' => false,
            'required'   => false
        ],
        'productValue' => [
            'type'       => 'string',
            'repeatable' => false,
            'required'   => false
        ],
        'productName' => [
            'type'       => 'string',
            'repeatable' => false,
            'required'   => false
        ],
        'productSku' => [
            'type'       => 'string',
            'repeatable' => false,
            'required'   => false
        ],
        'shareOrderType' => [
            'type'       => 'integer',
            'repeatable' => false,
            'required'   => false
        ],
        'dateOrderedStartDate' => [
            'type'       => 'string',
            'repeatable' => false,
            'required'   => true
        ],
        'dateOrderedEndDate' => [
            'type'       => 'string',
            'repeatable' => false,
            'required'   => true
        ],
        'status' => [
            'type'       => 'string',
            'repeatable' => false,
            'required'   => false
        ],
        'pageSize' => [
            'type' => 'integer',
            'repeatable' => false,
            'required' => true
        ],
        'pageNum' => [
            'type' => 'integer',
            'repeatable' => false,
            'required' => true
        ]
    ];

    /**
     * @param array $values Optional properties and values to assign to the object.
     */
    public function __construct(array $values = [])
    {
        list($parentValues, $childValues) = self::getParentValues(self::$propertyTypes, $values);
        
        parent::__construct($parentValues);

        if (!array_key_exists(__CLASS__, self::$properties)) {
            self::$properties[__CLASS__] = array_merge(self::$properties[get_parent_class()], self::$propertyTypes);
        }

        $this->setValues(__CLASS__, $childValues);
    }
}
