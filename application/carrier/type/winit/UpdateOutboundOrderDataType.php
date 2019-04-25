<?php
namespace app\carrier\type\winit;

class UpdateOutboundOrderDataType extends \app\carrier\type\BaseType
{
    /**
     * @var array Properties belonging to objects of this class.
     */
    private static $propertyTypes = [    
        'outboundOrderNum' => [
            'type' => 'integer',
            'repeatable' => false,
            'required' => true
        ],
        'recipientName' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'phoneNum' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'zipCode' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'emailAddress' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'state' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'region' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'city' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'address1' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'address2' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'deliveryWayID' => [
            'type' => 'integer',
            'repeatable' => false,
            'required' => false
        ],
        'insuranceTypeID' => [
            'type' => 'integer',
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


