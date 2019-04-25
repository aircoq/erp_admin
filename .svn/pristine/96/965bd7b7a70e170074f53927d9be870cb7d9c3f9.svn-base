<?php

namespace app\customerservice\service;

use think\Exception;

/**
 * Created by tb
 * User: zhangdongdong
 * Date: 2016/12/6
 * Time: 18:14
 */
class PaypalDisputeConfig
{
    /** @var array 发起纠纷原因 */
    public static $allReason = [
        'OTHER',    //其它
        'MERCHANDISE_OR_SERVICE_NOT_RECEIVED',  //客户未收到商品或服务
        'MERCHANDISE_OR_SERVICE_NOT_AS_DESCRIBED',  //客户报告商品或服务与描述不符
        'UNAUTHORISED', //客户未授权购买商品或服务
        'CREDIT_NOT_PROCESSED', //客户未处理退款或退款
        'DUPLICATE_TRANSACTION',    //该交易是重复的
        'INCORRECT_AMOUNT', //客户收取的金额不正确
        'PAYMENT_BY_OTHER_MEANS',   //客户通过其他方式支付了交易费用
        'CANCELED_RECURRING_BILLING',   //客户因订阅或已取消的定期交易而被收取费用
        'PROBLEM_WITH_REMITTANCE',  //汇款出现问题
    ];


    public static $allReasonText = [
        'OTHER' => '其它',
        'MERCHANDISE_OR_SERVICE_NOT_RECEIVED' => '客户未收到商品或服务',
        'MERCHANDISE_OR_SERVICE_NOT_AS_DESCRIBED' => '客户报告商品或服务与描述不符',
        'UNAUTHORISED' => '客户未授权购买商品或服务',
        'CREDIT_NOT_PROCESSED' => '客户未处理退款或退款',
        'DUPLICATE_TRANSACTION' => '该交易是重复的',
        'INCORRECT_AMOUNT' => '客户收取的金额不正确',
        'PAYMENT_BY_OTHER_MEANS' => '客户通过其他方式支付了交易费用',
        'CANCELED_RECURRING_BILLING' => '客户因订阅或已取消的定期交易而被收取费用',
        'PROBLEM_WITH_REMITTANCE' => '汇款出现问题'
    ];


    /** @var array 发起同意赔偿原因 */
    public static $allAcceptClaimReason = [
        1 => "DID_NOT_SHIP_ITEM",    //商家接受客户的索赔，因为他们无法将商品运回客户
        "TOO_TIME_CONSUMING",    //商家接受客户的索赔，因为商家需要很长时间才能完成订单
        "LOST_IN_MAIL",    //商家正在接受客户的索赔，因为该物品在邮件或运输途中丢失
        "NOT_ABLE_TO_WIN",    //商家接受客户的索赔，因为商家无法找到足够的证据来赢得此争议
        "COMPANY_POLICY",    //商家接受客户声称遵守其内部公司政策
        "REASON_NOT_SET",    //如果上述原因均不适用，则这是商家可以使用的默认值
    ];

    const STATUS_OTHER = 0;
    const STATUS_OPEN = 1;
    const STATUS_WAITING_FOR_BUYER_RESPONSE = 2;
    const STATUS_WAITING_FOR_SELLER_RESPONSE = 3;
    const STATUS_UNDER_REVIEW = 4;
    const STATUS_RESOLVED = 5;

    /** @var array 纠纷状态 */
    public static $allStatus = [
        "OTHER", //如果争议没有其他状态，则为默认状态。
        "OPEN", //争议是开放的。
        "WAITING_FOR_BUYER_RESPONSE", //争议正在等待客户的回应。
        "WAITING_FOR_SELLER_RESPONSE", //争议正在等待商家的回应。
        "UNDER_REVIEW", //该争议正在与PayPal进行审查。
        "RESOLVED" //争议得到解决。
    ];

    /** @var array 纠纷状态 */
    public static $allStatusText = [
        "OTHER" => '其它', //如果争议没有其他状态，则为默认状态。
        "OPEN" => '来自买家的信息', //争议是开放的。
        "WAITING_FOR_BUYER_RESPONSE" => '等待买家回复', //争议正在等待客户的回应。
        "WAITING_FOR_SELLER_RESPONSE" => '需要回复', //争议正在等待商家的回应。
        "UNDER_REVIEW" => 'Paypal审核中', //该争议正在与PayPal进行审查。
        "RESOLVED" => '纠纷已结束' //争议得到解决。
    ];

    /** @var array 纠纷所处阶段  */
    public static $allStage = [
        'INQUIRY',  //客户和商家进行互动以尝试解决争议而无需升级到PayPal。客户发生时：未收到货物或服务。报告收到的商品或服务与描述不符。需要更多详细信息，例如交易副本或收据。
        'CHARGEBACK',   //客户或商家将查询升级为索赔，该索赔授权PayPal调查案件并做出决定。仅在争议渠道发生时发生INTERNAL。
        'PRE_ARBITRATION',  //商人的第一个上诉阶段。如果PayPal的决定不符合商家的利益，商家可以对退款提出申诉。如果商家在上诉期限内没有上诉，PayPal会认为案件已经解决。
        'ARBITRATION'   //商人的第二个上诉阶段。如果第一次上诉被拒绝，商家可以第二次对争议提出上诉。如果商家在上诉期限内没有上诉，案件将在仲裁前阶段返回到已解决的状态。
    ];

    /** @var array 纠纷类别 */
    public static $allChannel = [
        'INTERNAL', //客户联系PayPal以向商家提出争议。
        'EXTERNAL'  //。客户联系其发卡机构或银行以申请退款。
    ];

    /** @var array 提议数组，从1开始，0表示还没有处理结果 */
    public static $allOfferType = [
        1 =>"REFUND",    //商家必须在没有任何物品更换或退货的情况下退还客户。此优惠类型在退款阶段有效，并且在商家愿意退还争议金额而无需客户的任何进一步操作时发生。省略接受声明调用中的refund_amount和return_shipping_address参数。
        "REFUND_WITH_RETURN",    //客户必须将商品退回商家，然后商家将退款。此优惠类型在退款阶段有效，并且在商家愿意退还争议金额并要求客户退回商品时发生。包含return_shipping_address参数但省略接受声明调用中的refund_amount参数。
        "REFUND_WITH_REPLACEMENT",    //商家必须退款，然后将替换物品发送给客户。当商家愿意退还特定金额并发送替换物品时，此优惠类型在查询阶段有效。offer_amount在make offer中包含参数以解决争议调用。
        "REPLACEMENT_WITHOUT_REFUND",    //商家必须向客户发送替换商品，无需额外退款。当商家愿意更换商品而没有任何退款时，此优惠类型在查询阶段有效。省略make offer中的offer_amount参数以解决争议调用
    ];

    /** @var array 处理结果数组，从1开始，0表示还没有处理结果 */
    public static $allOutcomeCode = [
        1 =>'RESOLVED_BUYER_FAVOUR',	//争议得到了解决，对客户有利。
        'RESOLVED_SELLER_FAVOUR',	//争议得到了商人的青睐。
        'RESOLVED_WITH_PAYOUT',	//PayPal为商家或客户提供保护，案件得到解决。
        'CANCELED_BY_BUYER',	//客户取消了争议。
        'ACCEPTED',	//PayPal接受了争议。
        'DENIED',	//PayPal否认了这一争议。
        'NONE',	//针对相同的交易ID创建了争议，之前的争议在没有任何决定的情况下被关闭。
    ];

    /** @var array 证据，从1开始，0表示还没有处理结果 */
    public static $allEvidenceType = [
        1 => 'PROOF_OF_FULFILLMENT',    //'履行证明',
        2 => 'PROOF_OF_REFUND',    //'退款证明',
        3 => 'PROOF_OF_DELIVERY_SIGNATURE',    //'交货签名证明',
        'PROOF_OF_RECEIPT_COPY',    //'收据证明',
        'RETURN_POLICY',    //'退货政策',
        6 => 'BILLING_AGREEMENT',    //'结算协议',
        'PROOF_OF_RESHIPMENT',    //'转运证明',
        'ITEM_DESCRIPTION',    //'项目描述',
        'POLICE_REPORT',    //'警方报告',
        'AFFIDAVIT',    //'宣誓书',
        11 => 'PAID_WITH_OTHER_METHOD',    //'支付另一种方法',
        'COPY_OF_CONTRACT',    //'合同副本',
        'TERMINAL_ATM_RECEIPT',    //'ATM收据',
        'PRICE_DIFFERENCE_REASON',    //'价格差异的原因',
        'SOURCE_CONVERSION_RATE',    //'源转换率',
        16 => 'BANK_STATEMENT',    //'银行对账单',
        'CREDIT_DUE_REASON',    //'信用原因',
        'REQUEST_CREDIT_RECEIPT',    //'请求信用收据',
        'PROOF_OF_RETURN',    //'退货证明',
        'CREATE',    //'创建',
        21 => 'CHANGE_REASON',    //'改变原因',
        22 => 'OTHER',    //'其他',
    ];

    /** @var array 证据，从1开始，0表示还没有处理结果 */
    public static $allEvidenceTypeText = [
        'PROOF_OF_FULFILLMENT' => '履行证明',
        'PROOF_OF_REFUND' => '退款证明',
        'PROOF_OF_DELIVERY_SIGNATURE' => '交货签名证明',
        'PROOF_OF_RECEIPT_COPY' => '收据证明',
        'RETURN_POLICY' => '退货政策',
        'BILLING_AGREEMENT' => '结算协议',
        'PROOF_OF_RESHIPMENT' => '转运证明',
        'ITEM_DESCRIPTION' => '项目描述',
        'POLICE_REPORT' => '警方报告',
        'AFFIDAVIT' => '宣誓书',
        'PAID_WITH_OTHER_METHOD' => '支付另一种方法',
        'COPY_OF_CONTRACT' => '合同副本',
        'TERMINAL_ATM_RECEIPT' => 'ATM收据',
        'PRICE_DIFFERENCE_REASON' => '价格差异的原因',
        'SOURCE_CONVERSION_RATE' => '源转换率',
        'BANK_STATEMENT' => '银行对账单',
        'CREDIT_DUE_REASON' => '信用原因',
        'REQUEST_CREDIT_RECEIPT' => '请求信用收据',
        'PROOF_OF_RETURN' => '退货证明',
        'CREATE' => '创建',
        'CHANGE_REASON' => '改变原因',
        'OTHER' => '其他',
    ];


    public static $allCarrierName = [
        'UPS' => 'United Parcel Service',
        'USPS' => 'United States Postal Service',
        'FEDEX' => 'Federal Express',
        'AIRBORNE_EXPRESS' => 'Airborne Express',
        'DHL' => 'DHL',
        'AIRSURE' => 'AirSure',
        'ROYAL_MAIL' => 'Royal Mail',
        'PARCELFORCE' => 'Parcelforce Worldwide',
        'SWIFTAIR' => 'Swiftair',
        'OTHER' => 'Other',
        'UK_PARCELFORCE' => 'Parcelforce UK',
        'UK_ROYALMAIL_SPECIAL' => 'Royal Mail Special Delivery UK',
        'UK_ROYALMAIL_RECORDED' => 'Royal Mail Recorded UK',
        'UK_ROYALMAIL_INT_SIGNED' => 'Royal Mail International Signed',
        'UK_ROYALMAIL_AIRSURE' => 'Royal Mail AirSure UK',
        'UK_UPS' => 'United Parcel Service UK',
        'UK_FEDEX' => 'Federal Express UK',
        'UK_AIRBORNE_EXPRESS' => 'Airborne Express UK',
        'UK_DHL' => 'DHL UK',
        'UK_OTHER' => 'Other UK',
        'UK_CANNOT_PROV_TRACK' => 'Cannot provide tracking UK',
        'CA_CANADA_POST' => 'Canada Post',
        'CA_PUROLATOR' => 'Purolator Canada',
        'CA_CANPAR' => 'Canpar Courier Canada',
        'CA_LOOMIS' => 'Loomis Express Canada',
        'CA_TNT' => 'TNT Express Canada',
        'CA_OTHER' => 'Other Canada',
        'CA_CANNOT_PROV_TRACK' => 'Cannot provide tracking Canada',
        'DE_DP_DHL_WITHIN_EUROPE' => 'DHL Parcel Europe',
        'DE_DP_DHL_T_AND_T_EXPRESS' => 'DHL T and T Express',
        'DE_DHL_DP_INTL_SHIPMENTS' => 'DHL DP International shipments',
        'DE_GLS' => 'General Logistics Systems Germany',
        'DE_DPD_DELISTACK' => 'DPD Tracking Germany',
        'DE_HERMES' => 'Hermes Germany',
        'DE_UPS' => 'United Parcel Service Germany',
        'DE_FEDEX' => 'Federal Express Germany',
        'DE_TNT' => 'TNT Express Germany',
        'DE_OTHER' => 'Other Germany',
        'FR_CHRONOPOST' => 'Chronopost France',
        'FR_COLIPOSTE' => 'Coliposte France',
        'FR_DHL' => 'DHL France',
        'FR_UPS' => 'United Parcel Service France',
        'FR_FEDEX' => 'Federal Express France',
        'FR_TNT' => 'TNT Express France',
        'FR_GLS' => 'General Logistics Systems France',
        'FR_OTHER' => 'Other France',
        'IT_POSTE_ITALIA' => 'Poste Italia',
        'IT_DHL' => 'DHL Italy',
        'IT_UPS' => 'United Parcel Service Italy',
        'IT_FEDEX' => 'Federal Express Italy',
        'IT_TNT' => 'TNT Express Italy',
        'IT_GLS' => 'General Logistics Systems Italy',
        'IT_OTHER' => 'Other Italy',
        'AU_AUSTRALIA_POST_EP_PLAT' => 'Australia Post EP Plat',
        'AU_AUSTRALIA_POST_EPARCEL' => 'Australia Post Eparcel',
        'AU_AUSTRALIA_POST_EMS' => 'Australia Post EMS',
        'AU_DHL' => 'DHL Australia',
        'AU_STAR_TRACK_EXPRESS' => 'StarTrack Express Australia',
        'AU_UPS' => 'United Parcel Service Australia',
        'AU_FEDEX' => 'Federal Express Australia',
        'AU_TNT' => 'TNT Express Australia',
        'AU_TOLL_IPEC' => 'Toll IPEC Australia',
        'AU_OTHER' => 'Other Australia',
        'FR_SUIVI' => 'Suivi FedEx France',
        'IT_EBOOST_SDA' => 'Poste Italiane SDA',
        'ES_CORREOS_DE_ESPANA' => 'Correos de Espana',
        'ES_DHL' => 'DHL Spain',
        'ES_UPS' => 'United Parcel Service Spain',
        'ES_FEDEX' => 'Federal Express Spain',
        'ES_TNT' => 'TNT Express Spain',
        'ES_OTHER' => 'Other Spain',
        'AT_AUSTRIAN_POST_EMS' => 'EMS Express Mail Service Austria',
        'AT_AUSTRIAN_POST_PPRIME' => 'Austrian Post Prime',
        'BE_CHRONOPOST' => 'Chronopost',
        'BE_TAXIPOST' => 'Taxi Post',
        'CH_SWISS_POST_EXPRES' => 'Swiss Post Express',
        'CH_SWISS_POST_PRIORITY' => 'Swiss Post Priority',
        'CN_CHINA_POST' => 'China Post',
        'HK_HONGKONG_POST' => 'Hong Kong Post',
        'IE_AN_POST_SDS_EMS' => 'Post SDS EMS Express Mail Service Ireland',
        'IE_AN_POST_SDS_PRIORITY' => 'Post SDS Priority Ireland',
        'IE_AN_POST_REGISTERED' => 'Post Registered Ireland',
        'IE_AN_POST_SWIFTPOST' => 'Swift Post Ireland',
        'IN_INDIAPOST' => 'India Post',
        'JP_JAPANPOST' => 'Japan Post',
        'KR_KOREA_POST' => 'Korea Post',
        'NL_TPG' => 'TPG Post Netherlands',
        'SG_SINGPOST' => 'SingPost Singapore',
        'TW_CHUNGHWA_POST' => 'Chunghwa POST Taiwan',
        'CN_CHINA_POST_EMS' => 'China Post EMS Express Mail Service',
        'CN_FEDEX' => 'Federal Express China',
        'CN_TNT' => 'TNT Express China',
        'CN_UPS' => 'United Parcel Service China',
        'CN_OTHER' => 'Other China',
        'NL_TNT' => 'TNT Express Netherlands',
        'NL_DHL' => 'DHL Netherlands',
        'NL_UPS' => 'United Parcel Service Netherlands',
        'NL_FEDEX' => 'Federal Express Netherlands',
        'NL_KIALA' => 'KIALA Netherlands',
        'BE_KIALA' => 'Kiala Point Belgium',
        'PL_POCZTA_POLSKA' => 'Poczta Polska',
        'PL_POCZTEX' => 'Pocztex',
        'PL_GLS' => 'General Logistics Systems Poland',
        'PL_MASTERLINK' => 'Masterlink Poland',
        'PL_TNT' => 'TNT Express Poland',
        'PL_DHL' => 'DHL Poland',
        'PL_UPS' => 'United Parcel Service Poland',
        'PL_FEDEX' => 'Federal Express Poland',
        'JP_SAGAWA_KYUU_BIN' => 'Sagawa Kyuu Bin Japan',
        'JP_NITTSU_PELICAN_BIN' => 'Nittsu Pelican Bin Japan',
        'JP_KURO_NEKO_YAMATO_UNYUU' => 'Kuro Neko Yamato Unyuu Japan',
        'JP_TNT' => 'TNT Express Japan',
        'JP_DHL' => 'DHL Japan',
        'JP_UPS' => 'United Parcel Service Japan',
        'JP_FEDEX' => 'Federal Express Japan',
        'NL_PICKUP' => 'Pickup Netherlands',
        'NL_INTANGIBLE' => 'Intangible Netherlands',
        'NL_ABC_MAIL' => 'ABC Mail Netherlands',
        'HK_FOUR_PX_EXPRESS' => '4PX Express Hong Kong',
        'HK_FLYT_EXPRESS' => 'Flyt Express Hong Kong',
    ];


    /**
     * @var array 操作类型对应的数组；
     */
    public static $allOperatType = [
        1 =>'send_message',
        'accept_claim',
        'make_offer',
        'provide_evidence',
        'appeal',
        'acknowledge_return_item'
    ];


    /**
     * @var array 操作类型对应的数组；
     */
    public static $allOperatTypeText = [
        'send_message' => '发送信息',
        'accept_claim' => '退款',
        'make_offer' => '传送调解方案',
        'provide_evidence' => '提供证据',
        'appeal' => '新增档案',
        'acknowledge_return_item' => '确认收到买家退货'
    ];


    public static function getOperateText($str)
    {
        if (is_numeric($str)) {
            $str = self::$allOperatType[$str];
            if ($str === false) {
                throw new Exception('未知的操作类型');
            }
        }

        return self::$allOperatTypeText[$str];
    }


    public static function getEvidenceTypeText($str)
    {
        if (is_numeric($str)) {
            $str = self::$allEvidenceType[$str];
            if ($str === false) {
                throw new Exception('未知的操作类型');
            }
        }

        return self::$allEvidenceTypeText[$str];
    }


    /**
     * 根据reason拿原表的整型；
     * @param $str
     * @return mixed
     * @throws Exception
     */
    public static function getResonInt($str)
    {
        $key = array_search($str, self::$allReason);
        if ($key === false) {
            throw new Exception('未知dispute原因reason字符串：' . $str);
        }
        return $key;
    }


    /**
     * 根据整型拿reason字符串
     * @param $num
     * @return mixed
     * @throws Exception
     */
    public static function getReasonByInt($num)
    {
        if (!isset(self::$allReason[$num])) {
            throw new Exception('未知dispute原因reason整型：' . $num);
        }
        return self::$allReason[$num];
    }


    /**
     * 拿reason注释
     * @param mixed $str
     * @return string
     * @throws Exception
     */
    public static function getReasonText($str, $stage = '')
    {
        if (is_numeric($str)) {
            $str = self::getReasonByInt($str);
        }
        $reason = self::$allReasonText[$str];
        if ($stage !== '') {
            $reason .= ' - '. self::getStageByInt($stage);
        }

        return $reason;
    }


    /**
     * 根据status拿原表的整型；
     * @param $str
     * @return mixed
     * @throws Exception
     */
    public static function getStatusInt($str)
    {
        $key = array_search($str, self::$allStatus);
        if ($key === false) {
            throw new Exception('未知dispute原因Status字符串：' . $str);
        }
        return $key;
    }


    /**
     * 根据整型拿status字符串
     * @param $num
     * @return mixed
     * @throws Exception
     */
    public static function getStatusByInt($num)
    {
        if (!isset(self::$allStatus[$num])) {
            throw new Exception('未知dispute原因Status整型：' . $num);
        }
        return self::$allStatus[$num];
    }


    /**
     * 拿status中文注释
     * @param $num
     * @return mixed
     * @throws Exception
     */
    public static function getStatusText($str)
    {
        if (is_numeric($str)) {
            $str = self::getStatusByInt($str);
        }
        return self::$allStatusText[$str];
    }


    /**
     * 根据Stage拿原表的整型；
     * @param $str
     * @return mixed
     * @throws Exception
     */
    public static function getStageInt($str)
    {
        $key = array_search($str, self::$allStage);
        if ($key === false) {
            throw new Exception('未知dispute原因Stage字符串：' . $str);
        }
        return $key;
    }


    /**
     * 根据整型拿Stage字符串
     * @param $num
     * @return mixed
     * @throws Exception
     */
    public static function getStageByInt($num)
    {
        if (!isset(self::$allStage[$num])) {
            throw new Exception('未知dispute阶段Stage整型：' . $num);
        }
        return self::$allStage[$num];
    }


    /**
     * 根据Channel拿原表的整型；
     * @param $str
     * @return mixed
     * @throws Exception
     */
    public static function getChannelInt($str)
    {
        $key = array_search($str, self::$allChannel);
        if ($key === false) {
            throw new Exception('未知dispute类别channel_id字符串：' . $str);
        }
        return $key;
    }


    /**
     * 根据整型拿Channel字符串
     * @param $num
     * @return mixed
     * @throws Exception
     */
    public static function getChannelByInt($num)
    {
        if (!isset(self::$allChannel[$num])) {
            throw new Exception('未知dispute类别channel_id整型：' . $num);
        }
        return self::$allChannel[$num];
    }


    /**
     * 根据OutcomeCode拿原表的整型；
     * @param $str
     * @return mixed
     * @throws Exception
     */
    public static function getOutcomeCode($str)
    {
        $key = array_search($str, self::$allOutcomeCode);
        if ($key === false) {
            throw new Exception('未知dispute处理结果comeCode字符串：' . $str);
        }
        return $key;
    }


    /**
     * 根据整型拿OutcomeCode字符串
     * @param $num
     * @return mixed
     * @throws Exception
     */
    public static function getOutcomeCodeByInt($num)
    {
        if (!isset(self::$allOutcomeCode[$num])) {
            throw new Exception('未知dispute处理结果comeCode整型：' . $num);
        }
        return self::$allOutcomeCode[$num];
    }


    /**
     * 信息转换成数组，时间换成现在时间；
     * @param $message
     * @return array|mixed
     */
    public static function convMessage($message)
    {
        if (empty($message)) {
            return [];
        }
        if (!is_array($message)) {
            $message = json_decode($message, true);
        }
        foreach ($message as &$val) {
            $val['time_posted'] = date('Y-m-d H:i:s', strtotime($val['time_posted']));
        }
        unset($val);
        return $message;
    }


    /**
     * 证据文件转成数组；
     * @param $evidences
     * @return array|mixed
     */
    public static function convEvidences($evidences)
    {
        if (empty($evidences)) {
            return [];
        }
        if (!is_array($evidences)) {
            $evidences = json_decode($evidences, true);
        }
        foreach ($evidences as &$val) {
            $val['date'] = date('Y-m-d H:i:s', strtotime($val['date']));
        }
        unset($val);
        return $evidences;
    }


    /**
     * 提议文件转成数组；
     * @param $evidences
     * @return array|mixed
     */
    public static function convOffer($offer)
    {
        if (empty($offer)) {
            return [];
        }
        if (!is_array($offer)) {
            $offer = json_decode($offer, true);
        }
        //foreach ($offer as &$val) {
        //    $val['date'] = date('Y-m-d H:i:s', strtotime($val['date']));
        //}
        //unset($val);
        return $offer;
    }


    /**
     * 提议文件转成数组；
     * @param $evidences
     * @return array|mixed
     */
    public static function convCommunication($communication)
    {
        if (empty($communication)) {
            return [];
        }
        if (!is_array($communication)) {
            $communication = json_decode($communication, true);
        }
        //foreach ($communication as &$val) {
        //    $val['date'] = date('Y-m-d H:i:s', strtotime($val['date']));
        //}
        //unset($val);
        return $communication;
    }
}