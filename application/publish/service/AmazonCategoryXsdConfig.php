<?php
namespace app\publish\service;

Class AmazonCategoryXsdConfig
{
    public static $sites = ['US' => 1, 'UK' => 2, 'DE' => 3, 'CA' => 4, 'FR' => 5, 'IT' => 6, 'JP' => 7, 'ES' => 8, 'AU' => 9, 'MX' => 10, 'IN' => 11];

    public static $currencyArr = ['US' => 'USD', 'UK' => 'GBP', 'CA' => 'CAD', 'JP' => 'JPY', 'DE' => 'EUR', 'FR' => 'EUR', 'IT' => 'EUR', 'ES' => 'EUR', 'MX' => 'MXN', 'AU' => 'AUD', 'IN' => 'INR'];


    private static $usCategories = [
        'AutoAccessory',
        'Baby',
        'Beauty',
        'CameraPhoto',
        //'Clothing',
        'ClothingAccessories',
        'CE',
        'Computers',
        'FoodAndBeverages',
        'Gourmet',
        'Health',
        'HomeImprovement',
        'FoodServiceAndJanSan',
        'LabSupplies',
        'PowerTransmission',
        'RawMaterials',
        'Jewelry',
        'Lighting',
        'MusicalInstruments',
        'PetSupplies',
        'Clothing',     //1_9 + 1.6
        'Sports',
        'SportsMemorabilia',
        'TiresAndWheels',
        'Tools',
        'Toys',
        'Wireless',
        'LargeAppliances'
    ];

    private static $ukCategories = [
        'AutoAccessory',
        'ClothingAccessories',
        'CE',
        'Beauty',
        'CameraPhoto',
        'FoodAndBeverages',
        'Gourmet',
        'Health',
        'HomeImprovement',
        'Jewelry',
        'Lighting',
        'MusicalInstruments',
        'PetSupplies',
        'Clothing',     //1_6
        'Sports',
        'TiresAndWheels',
        'Tools',
        'ToysBaby',
        'Wireless'
    ];

    private static $deCategories = [
        'AutoAccessory',
        'Baby',
        'Beauty',
        'CameraPhoto',
        'Clothing',     //1_9 + 1.6
        'ClothingAccessories',
        'CE',
        'Computers',
        'FoodAndBeverages',
        'Gourmet',
        'Health',
        'HomeImprovement',
        'FoodServiceAndJanSan',
        'LabSupplies',
        'PowerTransmission',
        'RawMaterials',
        'Jewelry',
        'Lighting',
        'MusicalInstruments',
        'PetSupplies',
        'Sports',
        'SportsMemorabilia',
        'TiresAndWheels',
        'Tools',
        'Toys',
        'Wireless',
        'LargeAppliances',
    ];

    private static $caCategories = [
        'AutoAccessory',
        'Baby',
        'Beauty',
        'CameraPhoto',
        'Clothing',     //1_9 + 1_6
        'ClothingAccessories',
        'CE',
        'Computers',
        'FoodAndBeverages',
        'Gourmet',
        'Health',
        'HomeImprovement',
        'FoodServiceAndJanSan',
        'LabSupplies',
        'PowerTransmission',
        'RawMaterials',
        'Jewelry',
        'Lighting',
        'MusicalInstruments',
        'PetSupplies',
        'Sports',
        'SportsMemorabilia',
        'TiresAndWheels',
        'Tools',
        'Toys',
        'Wireless',
        'LargeAppliances',
    ];

    private static $frCategories = [
        'AutoAccessory',
        'Baby',
        'Beauty',
        'CameraPhoto',
        'Clothing',     //1_9 + 1_6
        'ClothingAccessories',
        'CE',
        'Computers',
        'FoodAndBeverages',
        'Gourmet',
        'Health',
        'HomeImprovement',
        'FoodServiceAndJanSan',
        'LabSupplies',
        'PowerTransmission',
        'RawMaterials',
        'Jewelry',
        'Lighting',
        'MusicalInstruments',
        'PetSupplies',
        'Sports',
        'SportsMemorabilia',
        'TiresAndWheels',
        'Tools',
        'Toys',
        'Wireless',
        'LargeAppliances',
    ];

    private static $itCategories = [
        'AutoAccessory',
        'Baby',
        'Beauty',
        'CameraPhoto',
        'Clothing',     //1_9 + 1_6
        'ClothingAccessories',
        'CE',
        'Computers',
        'FoodAndBeverages',
        'Gourmet',
        'Health',
        'HomeImprovement',
        'FoodServiceAndJanSan',
        'LabSupplies',
        'PowerTransmission',
        'RawMaterials',
        'Jewelry',
        'Lighting',
        'MusicalInstruments',
        'PetSupplies',
        'Sports',
        'SportsMemorabilia',
        'TiresAndWheels',
        'Tools',
        'Toys',
        'Wireless',
        'LargeAppliances',
    ];

    private static $jpCategories = [
        'AutoAccessory',
        'Beauty',
        'Clothing',     //不是1_6和1_9
        'CE',
        'FoodAndBeverages',
        'Health',
        'Jewelry',
        'MusicalInstruments',
        'Sports',
        'HomeImprovement',
        'ToysBaby',
        'Lighting',
        'PetSupplies',
        'TiresAndWheels',
        'Tools',
        'Wireless',
        'CameraPhoto',
        'Gourmet',
    ];

    private static $esCategories = [
        'AutoAccessory',
        'Baby',
        'Beauty',
        'CameraPhoto',
        'Clothing',     //1_6 + 1_9
        'ClothingAccessories',
        'CE',
        'Computers',
        'FoodAndBeverages',
        'Gourmet',
        'Health',
        'HomeImprovement',
        'FoodServiceAndJanSan',
        'LabSupplies',
        'PowerTransmission',
        'RawMaterials',
        'Jewelry',
        'Lighting',
        'MusicalInstruments',
        'PetSupplies',
        'Sports',
        'SportsMemorabilia',
        'TiresAndWheels',
        'Tools',
        'Toys',
        'Wireless',
        'LargeAppliances',
    ];

    private static $auCategories = [
        'AutoAccessory',
        'ClothingAccessories',
        'CE',
        'Beauty',
        'CameraPhoto',
        'FoodAndBeverages',
        'Gourmet',
        'Health',
        'HomeImprovement',
        'Jewelry',
        'Lighting',
        'MusicalInstruments',
        'PetSupplies',
        'Clothing',
        'Sports',
        'TiresAndWheels',
        'TiresAndWheels',
        'ToysBaby',
        'Wireless'
    ];

    private static $mxCategories = [
    ];

    private static $inCategories = [
    ];

    private static $basic = [
        "ClothingAccessories",
        "Clothing",
        "Miscellaneous",
        "CameraPhoto",
        "Home",
        "Sports",
        "SportsMemorabilia",
        "EntertainmentCollectibles",
        "HomeImprovement",
        "Tools",
        "FoodAndBeverages",
        "Gourmet",
        "Jewelry",
        "Health",
        "CE",
        "Computers",
        "SoftwareVideoGames",
        "Wireless",
        "Beauty",
        "Office",
        "MusicalInstruments",
        "AutoAccessory",
        "PetSupplies",
        "ToysBaby",
        "Baby",
        "TiresAndWheels",
        "Music",
        "Video",
        "Lighting",
        "LargeAppliances",
        "FBA",
        "Toys",
        "GiftCard",
        "LabSupplies",
        "EducationSupplies",
        "RawMaterials",
        "PowerTransmission",
        "Industrial",
        "Shoes",
        "Motorcycles",
        "MaterialHandling",
        "MechanicalFasteners",
        "FoodServiceAndJanSan",
        "WineAndAlcohol",
        "EUCompliance",
        "Books",
        "AdditionalProductInformation",
        "Arts",
        "Luggage",
        "Outdoors",
        "Coins",
        "Furniture",
        "LuxuryBeauty",
        "Collectibles",
        "ProfessionalHealthCare",
        "ThreeDPrinting",
    ];

    public static function getCategoriesBySite($code)
    {
        $var = strtolower($code) . 'Categories';
        if (!isset(self::${$var})) {
            return [];
        }
        $categorys = array_unique(array_merge(self::$basic, self::${$var}));
        return $categorys;
    }
    
    public static function getBits($code)
    {
        $code = strtoupper($code);
        $bit = isset(self::$sites[$code]) ? self::$sites[$code] : 0;
        if (!$bit) {
            return 0;
        }
        
        return self::moveBit($bit);
    }
    
    public static function moveBit($bit)
    {
        return 1<<($bit-1);
    }

    /**
     * 根据站点ID拿站点标识；
     * @param $num
     * @return mixed
     */
    public static function getSiteByNum($num) {
        $list = self::getSiteList();
        foreach($list as $val) {
            if($val['value'] == $num) {
                return $val['label'];
            }
        }
        return '';
    }

    /**
     * 根据站点或站占ID拿语言；
     * @param $num
     * @return mixed
     */
    public static function getLangCodeBySite($str) {
        $lang = 'en';
        if (is_numeric($str)) {
            $site_name = self::getSiteByNum($str);
        } else {
            $site_name = $str;
        }
        if (empty($site_name)) {
            return $lang;
        }
        switch ($site_name) {
            case 'US':
            case 'UK':
            case 'CA':
            case 'AU':
            case 'IN':
                $lang = 'en';
                break;
            case 'MX':
                $lang = 'es';
                break;
            default:
                $lang = strtolower($site_name);
        }
        return $lang;
    }

    /**
     * 拿亚码逊站点列表；
     * @return array
     */
    public static function getSiteList() {
        $data = [];
        foreach(self::$sites as $key=>$val) {
            $data[] = ['label' => $key, 'value' => self::moveBit($val)];
        }
        return $data;
    }

    /**
     * 拿亚码逊站点货币；
     * @return array
     */
    public static function getCurrencyBySite($site) : string {
        if (is_numeric($site)) {
            $site = self::getSiteByNum($site);
        }
        $site = strtoupper($site);
        $currency = self::$currencyArr[$site] ?? 'EUR';
        return $currency;
    }
}
