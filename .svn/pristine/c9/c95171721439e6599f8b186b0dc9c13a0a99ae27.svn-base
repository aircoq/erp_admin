<?php
namespace app\common\service;

/**
 * User: zengsh
 * Date: 2017/12/22
 * Time: 17:49
 */

class EbayCommonVariables{
    
    private $variables;

    function __construct(){
        #刊登天数
        $Variables['listingDuration'] =  array(
            1=>'GTC',2=>'Days_1',3=>'Days_3',4=>'Days_5',5=>'Days_7',6=>'Days_10',7=>'Days_30'
        );
        #销售方式
        $Variables['listingType'] = array(
            1=>'FixedPriceItem',2=>'Chinese'
        );
        #橱窗展示(0:None不使用,1: Gallery,2:Featured,3: Plus)
        $Variables['pictureGallery'] = array(
            'None','Gallery','Featured','Plus'
        );
        #hitCount显示类型(0:NoHitCounter,1:BasicStyle, 2:hiddenStyle, 3:RetroStyle, 4: GreedLED, 5:Hidden, 6:HonestyStyle
        $Variables['hitCount'] = array(
            'NoHitCounter','BasicStyle','hiddenStyle','RetroStyle','GreedLED','Hidden','HonestyStyle'
        );
        #接受退货周期:(1 Days_14,2 Days_30,3 Days_60,4 Months_1)
        $Variables['returnTime'] = array(
            1=>'Days_14',2=>'Days_30',3=>'Days_60',4=>'Months_1'
        );
        #ShippingCostPaidByOption 运费承担方:(1: Buyer, 2: Seller)
        $Variables['returnShippingOption'] = array(
            1=>'Buyer',2=>'Seller'
        );
        $this->variables = $Variables;
    }

    public function __set($name,$value){
        $this->$name=$value;
        return $this;
    }

    public function __get($name){
        if(isset($this->$name)){
            return $this->$name;
        }else{
            return null;
        }
    }
}


