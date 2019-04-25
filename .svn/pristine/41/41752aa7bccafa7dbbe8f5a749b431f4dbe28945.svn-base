<?php
namespace app\carrier\type\epx;

class CreateDeliveryOrderRequestType extends \app\carrier\type\BaseType
{
    /**
     * @var array Properties belonging to objects of this class.
     */
    private static $propertyTypes = [    
        'warehouseCode' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'referenceCode' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'carrierCode' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'insureType' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'remoteArea' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'description' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'consignee' => [
            'type' => 'app\carrier\type\epx\ConsigneeType',
            'repeatable' => false,
            'required' => true
        ],        
        'platformCode' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'fbaLabelCode' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'items' => [
            'type' => 'app\carrier\type\epx\ItemType',
            'repeatable' => true,
            'required' => true
        ],
        'insureMoney' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'sellCode' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
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
