<?php
namespace app\carrier\type\winit;

class BaseRequestType extends \app\carrier\type\BaseType
{
    /**
     * @var array Properties belonging to objects of this class.
     */
    public static $propertyTypes = [    
        'action' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'app_key' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'format' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'language' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'platform' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'sign_method' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'timestamp' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'version' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'data' => [
            'type' => 'app\carrier\type\BaseType',
            'repeatable' => false,
            'required' => true
        ],
        'sign' => [
            'type' => 'string',
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
