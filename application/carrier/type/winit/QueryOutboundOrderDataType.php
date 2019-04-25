<?php
namespace app\carrier\type\winit;

class QueryOutboundOrderDataType extends \app\carrier\type\BaseType
{
    /**
     * @var array Properties belonging to objects of this class.
     */
    private static $propertyTypes = [
        'outboundOrderNum' => [
            'type' => 'integer',
            'repeatable' => false,
            'required' => false
        ],
        'startDate' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'endDate' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'sharedOrderType' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'pageSize' => [
            'type' => 'integer',
            'repeatable' => false,
            'required' => false
        ],
        'pageNum' => [
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