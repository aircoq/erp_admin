<?php
namespace app\carrier\type\winit;

class ProductDataType extends \app\carrier\type\BaseType
{
    /**
     * @var array Properties belonging to objects of this class.
     */
    private static $propertyTypes = [    
        'eBayBuyerID' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'eBayItemID' => [
            'type' => 'integer',
            'repeatable' => false,
            'required' => false
        ],
        'eBaySellerID' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'eBayTransactionID' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => false
        ],
        'productCode' => [
            'type' => 'string',
            'repeatable' => false,
            'required' => true
        ],
        'productNum' => [
            'type' => 'integer',
            'repeatable' => false,
            'required' => true
        ],
        'specification' => [
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