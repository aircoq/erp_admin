<?php
namespace app\carrier\type\epx;

class ConsigneeType extends \app\carrier\type\BaseType
{
    /**
     * @var array Properties belonging to objects of this class.
     */
    private static $propertyTypes = [    
        'fullName' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'countryCode' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'street' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'city' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'state' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'postalCode' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'email' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'phone' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'company' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'doorplate' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'cardId' => [
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


