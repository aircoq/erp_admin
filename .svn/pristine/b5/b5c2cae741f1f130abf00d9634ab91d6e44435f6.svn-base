<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/3/6
 * Time: 10:58
 */

namespace app\publish\validate;


use think\Validate;

class EbayCommonValidate extends Validate
{
    const MODULE_TYPE = 'comb,sale,bargaining,choice,exclude,gallery,pickup,location,individual,quantity,receivables,refuse,promotion,style,cate,trans,returngoods';

    protected $rule=[
        ['type|模块类型','require|in:'.self::MODULE_TYPE,],
        ['id','require|number|gt:0','模块id未设置或设置不正确'],
//        ['accountId','string','账号id'],
        ['siteId','number','站点id必须是数字'],
        ['page','number|gt:0','页码必须是大于0的数字'],
        ['pageSize','number|gt:0','每页条目必须是大于0的数字'],
    ];

    protected $saveRule = [
        ['type','require|in:'.self::MODULE_TYPE,'模块类型未设置或设置不正确'],
        ['detail','require|json','详情未设置或格式不对'],

    ];
    protected $saveCommonRule = [
        ['id','number|gt:0','模块id设置不正确'],
        ['site','number','站点id必须是数字'],
        ['model_name','require','模块名称必须设置'],
    ];
    protected $combRule = [
        ['ebay_account','number','账号id设置错误'],
        ['promotion','number','促销模块id设置错误'],
        ['style','number','风格模块id设置错误'],
        ['sale','number','销售说明模块id设置错误'],
        ['trans','number','物流模块id设置错误'],
        ['exclude','number','不运送地区模块id设置错误'],
        ['choice','number','备货模块id设置错误'],
        ['pickup','number','自提模块id设置错误'],
        ['location','number','所在地模块id设置错误'],
        ['gallery','number','橱窗展示模块id设置错误'],
        ['individual','number','私人listing模块id设置错误'],
        ['refuse','number','买家限制模块id设置错误'],
        ['receivables','number','收款模块id设置错误'],
        ['returngoods','number','退货模块id设置错误'],
        ['bargaining','number','议价模块id设置错误'],
        ['quantity','number','数量模块id设置错误'],
    ];
    protected $bargainingRule = [
        ['best_offer','require|in:0,1','是否接受议价设置错误'],
        ['accept_lowest_price','float','自动接受价格设置错误'],
        ['reject_lowest_price','float','自动拒绝价格设置错误'],
    ];
    protected $choiceRule = [
        ['choice_date|备货周期','require|in:0,1,2,3,4,5,10,15,20,30,40'],
    ];
    protected $excludeRule = [
        ['exclude|不运送地区','require|array'],
    ];
    protected $galleryRule = [
        ['picture_gallery|橱窗展示','require|in:0,1,2,3'],
    ];
    protected $pickupRule = [
        ['local_pickup|是否自提','require|in:0,1'],
    ];
    protected $locationRule = [
        ['location|所在地','require'],
        ['country|国家简码','require|length:2'],
    ];
    protected $individualRule = [
        ['individual_listing|是否私人listing','require|in:0,1'],
    ];
    protected $quantityRule = [
        ['quantity|数量','require|integer'],
    ];
    protected $receivablesRule = [
        ['pay_method|付款方式','require|array'],
        ['auto_pay|立即付款','require|in:0,1'],
    ];
    protected $refuseRule = [
        ['registration|主要运送地址在我的运送范围之外','require|in:0,1'],
        ['violations|是否开启弃标案限制','require|in:0,1'],
        ['violations_count|弃标案个数','require|in:2,3,4,5'],
        ['violations_period|弃标案周期','require|in:Days_30,Days_180,Days_360'],
        ['requirements|是否开启购买个数限制','require|in:0,1'],
        ['requirements_max_count|限制个数','require|in:0,1,2,3,4,5,6,7,8,9,10,25,50,75,100'],
        ['minimum_feedback_score|限制个数适用于的买家信用','require|in:0,1,2,3,4,5'],
    ];
    protected $returngoodsRule = [
        ['return_policy|退货政策','require|in:0,1'],
        ['return_type|退货方式','require|in:MoneyBack,MoneyBackOrExchange,MoneyBackOrReplacement'],
        ['return_time|退货周期','require|in:1,2,3'],
        ['return_shipping_option|退货运费承担','require|in:1,2'],
    ];
    protected $transRule = [
        ['trans|物流详情','require|array'],
    ];
    protected $transDetailRule = [
        ['trans_code|物流名称','require'],
        ['cost|首件运费','require|float'],
        ['add_cost|续件运费','require|float'],
        ['extra_cost|额外运费','require|float'],
        ['location|送达地区','array'],
        ['inter|是否国际物流','require|in:0,1'],
    ];
    protected $styleRule = [
        ['ebay_account|账号id','number'],
        ['style_detail|风格详情','require'],
    ];
    protected $promotionRule = [
        ['ebay_account|账号id','require|number'],
        ['start_date|开始时间','require|date'],
        ['end_date|开始时间','require|date'],
        ['promotion|是否开启折扣','require|in:0,1'],
        ['promotion_type|折扣类型','require|in:1,2'],
        ['promotion_discount|折扣比例','require|float'],
        ['promotion_cash|折扣金额','require|float'],
        ['promotion_trans|免运费','require|in:0,1'],
    ];




    protected $scene = [
        'edit' => 'type,id',
        'list' => 'type,siteId,page,pageSize',
    ];

    public function __construct()
    {
        parent::__construct();
        self::extend('json',function ($value) {
            return is_string($value) && !is_null(json_decode($value));
        });
    }

    public function myCheck($data,$scene)
    {
        return $this->check($data,$this->rule,$scene);
    }

    public function saveCheck($data)
    {
        $res0 = $this->check($data,$this->saveRule);
        if (!$res0) {
            return false;
        }
        $detail = json_decode($data['detail'],true);
        $res1 = $this->check($detail,$this->saveCommonRule);

        if (!$res1) {
            return false;
        }
        $ruleName = $data['type'].'Rule';
        if (isset($this->$ruleName)) {
             $res = $this->check($detail, $this->$ruleName);
             if ($res && $data['type']=='trans') {
                 foreach ($detail['trans'] as $tran) {
                     if (!($res = $this->check($tran,$this->transDetailRule))) {
                         break;
                     }
                 }
             }
             return $res;
        }
        return true;
    }
}