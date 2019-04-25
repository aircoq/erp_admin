<?php


namespace app\goods\service;


class GoodsExport
{

    public function NOON(){
        $fields = [
            ['key'=>'sku','title'=>'Seller SKU'],
            ['key'=>'Parent_SKU','title'=>'Parent SKU','value'=>''],
            ['key'=>'brand_id','title'=>'Brand Name','value'=>'Unbranded'],
            ['key'=>'name_en','title'=>'Product Title'],
            ['key'=>'product_subtype','title'=>'Product Subtype','value'=>''],
            ['key'=>'sku','title'=>'Model Number'],
            ['key'=>'model_name','title'=>'Model Name','value'=>'Unbranded'],
            ['key'=>'color','title'=>'Colour Name'],
            ['key'=>'color','title'=>'Colour Family'],
            ['key'=>'clothingSize','title'=>'Size'],
            ['key'=>'size_unit','title'=>'Size Unit','value'=>''],
            ['key'=>'length','title'=>'Product Length'],
            ['key'=>'length_unit','title'=>'Length Unit','value'=>'centimeter'],
            ['key'=>'height','title'=>'Product Height'],
            ['key'=>'height_unit','title'=>'Height Unit','value'=>'centimeter'],
            ['key'=>'width','title'=>'Product Width/Depth'],
            ['key'=>'width_unit','title'=>'Width/Depth Unit','value'=>'centimeter'],
            ['key'=>'weight','title'=>'Product Weight'],
            ['key'=>'weight_unit','title'=>'Weight Unit','value'=>'gram'],
            ['key'=>'Capacity','title'=>'Capacity','value'=>''],
            ['key'=>'quantity','title'=>'Number of Pieces','value'=>'10'],
            ['key'=>'base_material','title'=>'Base Material','value'=>''],
            ['key'=>'cost_price','title'=>'cost price'],
            ['key'=>'attr_name_1','title'=>'Attribute Key 1'],
            ['key'=>'attr_value_1','title'=>'Attribute Value 1'],
            ['key'=>'attr_name_2','title'=>'Attribute Key 2'],
            ['key'=>'attr_value_2','title'=>'Attribute Value 2'],
            ['key'=>'attr_name_3','title'=>'Attribute Key 3'],
            ['key'=>'attr_value_3','title'=>'Attribute Value 3'],
            ['key'=>'attr_name_4','title'=>'Attribute Key 4'],
            ['key'=>'attr_value_4','title'=>'Attribute Value 4'],
            ['key'=>'attr_name_5','title'=>'Attribute Key 5'],
            ['key'=>'attr_value_5','title'=>'Attribute Value 5'],
            ['key'=>'amazon_point_1','title'=>'Feature/Bullet 1'],
            ['key'=>'amazon_point_2','title'=>'Feature/Bullet 2'],
            ['key'=>'amazon_point_3','title'=>'Feature/Bullet 3'],
            ['key'=>'amazon_point_4','title'=>'Feature/Bullet 4'],
            ['key'=>'amazon_point_5','title'=>'Feature/Bullet 5'],
            ['key'=>'set_include','title'=>'Set Includes','value'=>''],
            ['key'=>'sku_thumb1','title'=>'Image URL 1'],
            ['key'=>'sku_thumb2','title'=>'Image URL 2'],
            ['key'=>'sku_thumb3','title'=>'Image URL 3'],
            ['key'=>'sku_thumb4','title'=>'Image URL 4'],
            ['key'=>'sku_thumb5','title'=>'Image URL 5'],
            ['key'=>'sku_thumb6','title'=>'Image URL 6'],
            ['key'=>'sku_thumb7','title'=>'Image URL 7'],
            ['key'=>'length','title'=>'Shipping Length (cm)'],
            ['key'=>'height','title'=>'Shipping Height (cm)'],
            ['key'=>'width','title'=>'Shipping Width/Depth (cm)'],
            ['key'=>'ps_product_weight','title'=>'Shipping Weight (KG)'],
            ['key'=>'shipping_destination','title'=>'Shipping Destination','value'=>''],
            ['key'=>'quantity','title'=>'Quantity/Stock','value'=>20],
            ['key'=>'fulfilment_method','title'=>'Fulfilment Method','value'=>'Seller Back to Back - SB2B'],
            ['key'=>'processing_time','title'=>'Processing Time','value'=>'3 days']
        ];
        $key = [];
        foreach ($fields as $v){
            $key[] = $v['key'];
        }
        return ['data'=>$fields,'key'=>$key];
    }


}