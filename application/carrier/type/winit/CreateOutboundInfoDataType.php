<?php
namespace app\carrier\type\winit;

class CreateOutboundInfoDataType extends \app\carrier\type\BaseType
{
    /**
     * @var array Properties belonging to objects of this class.
     */
    private static $propertyTypes = [    
        'warehouseID' => [
            'type' => 'integer',
            'repeatable' => false,
            'required' => true
        ],
        'eBayOrderID' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'repeatable' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'deliveryWayID' => [
            'type' => 'integer',
            'repeatable' => false,
            'required' => true
        ],
        'insuranceTypeID' => [
            'type' => 'integer',
            'repeatable' => false,
            'required' => true
        ],
        'sellerOrderNo' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'recipientName' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'phoneNum' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'zipCode' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'emailAddress' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'state' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'region' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'city' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'address1' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'address2' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'doorplateNumbers' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'isShareOrder' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'fromBpartnerId' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'platform' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'productList' => [
            'type' => 'app\carrier\type\winit\ProductDataType',
            'repeatable' => true,
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
