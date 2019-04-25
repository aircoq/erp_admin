<?php
/**
 * Created by PhpStorm.
 * User: TOM
 * Date: 2017/9/1
 * Time: 17:07
 */

namespace app\api\help;


use app\common\model\Supplier;
use app\common\model\SupplierActionLog;
use app\common\validate\Supplier as ValidateSupplier;
use app\common\model\User;
use app\goods\service\CategoryHelp;
use app\purchase\service\SupplierService;
use think\Db;
use think\Exception;
use app\common\model\Area;
use think\Loader;
use think\Validate;
use app\common\cache\Cache;

class SupplierHelp
{
    private $response;
    //供应商类别
    private static $type_map = [
        0 => 0,
        1 => 2,
        2 => 1,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6
    ];
    //付款方式
    private static $pay_type_map = [
        0 => 4,
        1 => 2,
        2 => 5,
        3 => 1
    ];
    //交易方式(修改)
    private static $transaction_type_map = [
        0 => 1,
        1 => 2,
    ];
    //发票类型
    private static $invoice_map = [
        0 => 2,
        1 => 6,
        2 => 5,
        3 => 9,
        4 => 1,
        5 => 7,
        6 => 8
    ];
    //结算方式(修改)
    private static $balance_type_map = [
        0 => 0,
        1 => 5,
        2 => 6,
        3 => 7,
        4 => 8,
        5 => 9,
        //6 => 10,
        7 => 11,
        8 => 12,
        9 => 3,
        10 => 4,
        11 => 13,
        12 => 14,
        13 => 15,
        14 => 18,
        15 => 16,
        //16 => 17,
        17 => 19,
        18 => 20,
        19 => 21,
        20 => 22,
        21 => 23,
        22 => 24,
        23 => 25,
        24 => 26,
    ];

    //是否能退货
    private static $whether = [
        0 => 1,
        1 => 2,
    ];
    //退货天数
    private static $return_goods_data = [
        0 => 1,
        1 => 2,
        2 => 3,
        3 => 4,
    ];

    //是否贴标、套牌
    private static $label_deck = [
        0 => 1,
        1 => 2,
        2 => 3,
        3 => 4,
    ];

    //默认付款方式：ERP1-对私，2-对公
    private static $default_payment_method = [
        0 => 1,
        1 => 2,
    ];



    /**
     * @desc 新增供应商
     * @param $params
     */
    public function addSupplier(array $params)
    {
        $model = new Supplier();
        $validate = Loader::validate('Supplier');
        $supplierService = new SupplierService();
        foreach ($params as $product) {
            Db::startTrans();
            try {
                //数据转换
                $supplierData = self::dataConversion($product);
                cache::handler()->set('OaAdd:supplier',json_encode($supplierData));
                if ($validate->check($supplierData,[],'api')){
                    $supplierId = $model->allowField(true)->insertGetId($supplierData);
                    $supplierService->addLog($supplierId,$supplierData['creator_id'],1,'OA新增供应商');
                    if (is_null($supplierId)) {
                        $this->response[] = [
                            'success' => false,
                            'error_msg' => $model->getError()
                        ];
                    } else {
                        $this->response[] = [
                            'success' => true,
                        ];
                    }

                }else{
                    throw new Exception($validate->getError());
                }
                Db::commit();
            } catch (Exception $exception) {
                Db::rollback();
                $this->response[] = [
                    'success' => false,
                    'error_msg' => [$exception->getMessage() . $exception->getFile() . $exception->getLine()]
                ];
            }

        }
        return $this->response;
    }

    public function updateSupplier(array $params)
    {
        Db::startTrans();
        try {
            $data = self::checkAndBuildUpdateData($params);
            $company_name = $params['company_name'];
            $oldSupplier = Supplier::where('company_name',$company_name)->find();
            $Supplier = new Supplier();
            if(!$oldSupplier){
                throw new Exception('该供应商不存在!');
            }
            if(isset($data['company_name_new'])){
                if ($data['company_name_new'] == $oldSupplier['company_name'] || $Supplier->isHas($oldSupplier['id'],$data['company_name_new'])) {
                    throw new Exception('公司名称存在');
                }
                $data['company_name'] = $data['company_name_new'];
            }
            if (isset($data['code']) && ($data['code'] == $oldSupplier['code'] || $Supplier->isHas($oldSupplier['id'],'',$data['code']))) {
                throw new Exception('信用代码存在');
            }
            if (isset($data['balance_type']) && $data['balance_type'] != $oldSupplier->balance_type){
                if (in_array($data['balance_type'],[12,13]) && $oldSupplier->auto_payment_request !=2){
                    $data['auto_payment_request'] = 2;
                }
                if (in_array($data['balance_type'],[5,6,7,8,9,11,14,15,16,18,19,20,21,22,23,24,25,26]) && $oldSupplier->auto_payment_request !=1){
                    $data['auto_payment_request'] = 1;
                }
            }
            $Supplier->isUpdate(true)->allowField(true)->save($data,['id'=>$oldSupplier->id]);
            $this->editContent($data,$oldSupplier);
            $this->response[] = [
                'success' => true,
            ];
            Db::commit();
            Cache::store('Supplier')->updateSupplier($oldSupplier->id);
        } catch (Exception $exception) {
            Db::rollback();
            $this->response[] = [
                'success' => false,
                'error_msg' => [$exception->getMessage() . $exception->getFile() . $exception->getLine()]
            ];
        }
        return $this->response;
    }

    private static function checkAndBuildUpdateData($params)
    {
        if (!isset($params['company_name'])) {
            throw new Exception('供应商名称不能为空');
        }
        $data=$params;
//        $ValidateSupplier = new ValidateSupplier();
        if (!empty($params['operator_id'])){
            $userInfo = User::where(['job_number' => $params['operator_id']])->field('id')->find();
        }
        if (empty($userInfo) && !empty($params['creator'])){
            $userInfo = User::where(['realname' => $params['creator']])->field('id')->find();
        }
        $data['operator_id'] = !empty($userInfo) ? $userInfo['id'] : 0;
        //供应链专员
        if (!empty($data['supply_chain_specialist_id'])){
            $supplyChainSpecialistId = [];
            $supplyChainSpecialist = User::where(['job_number' => $data['supply_chain_specialist_id']])->field('id')->find();
            $supplierService = new SupplierService();
            $supplyChainSpecialists = $supplierService->supplyChainDepartmentId();
            foreach ($supplyChainSpecialists as $supplyChainSpecialistValue){
                $supplyChainSpecialistId[] = $supplyChainSpecialistValue['id'];
            }
            if (!in_array($supplyChainSpecialist['id'],$supplyChainSpecialistId)){
                throw new Exception('该人员不属于供应链部门');
            }
            $data['supply_chain_specialist_id'] = !empty($supplyChainSpecialist) ? $supplyChainSpecialist['id'] : 0;
        }
        //交易方式
        if(isset($params['transaction_type']) && is_numeric($params['transaction_type'])){
            $data['transaction_type']=self::$transaction_type_map[$params['transaction_type']];
        }

        if(isset($params['business_license']) && $params['business_license']){
            $data['business_license']=$params['business_license'];
            $data['company_name_new']=$params['business_license'];
        }
        //结算方式
        if(isset($params['balance_type']) && is_numeric($params['balance_type'])){
            $data['balance_type']=self::$balance_type_map[$params['balance_type']];
        }
        //阿里账号
        if (isset($params['ali_account'])){
            $data['ali_account']=$params['ali_account'];
        }
        //发票类型
        if(isset($params['invoice']) && is_numeric($params['invoice'])){
            $data['invoice']=self::$invoice_map[$params['invoice']];
        }
        //省份
        if(isset($params['province']) && $params['province']){
            $data['province_name']=$params['province'];
            $data['province_id']=self::getAreaId($params['province'], 1);
        }
        //城市
        if(isset($params['city']) && $params['city']){
            $data['city_name']=$params['city'];
            $data['city_id']=self::getAreaId($params['city'], 2);
        }
        //县
        if(isset($params['areaOrCounty']) && $params['areaOrCounty']){
            $data['area_name']=$params['areaOrCounty'];
            $data['area_id'] = self::getAreaId($params['areaOrCounty'], 3);
        }
        //商品分類
        if(isset($params['introduce']) && $params['introduce']){
            $data['categorys'] = self::getCategorys($params['introduce']);
        }

        if (isset($params['open_account']) && $params['open_account']){
            $data['public_accounts'] = $params['open_account'];
        }
        if (isset($params['open_account_bank']) && $params['open_account_bank']){
            $data['public_accounts_bank'] = $params['open_account_bank'];
        }
        if (isset($params['open_city']) && $params['open_city']){
            $data['public_bank_city'] = self::getAreaId($params['open_city'],2);
        }
        if (isset($params['open_phone']) && $params['open_phone']){
            $data['public_bank_retained_contact_way'] = $params['open_phone'];
        }
        if (isset($params['open_account_name']) && $params['open_account_name']){
            $data['public_accounts_name'] = $params['open_account_name'];
        }

        if (isset($params['private_account']) && $params['private_account']){
            $data['private_accounts'] = $params['private_account'];
        }
        if (isset($params['private_account_name']) && $params['private_account_name']){
            $data['private_accounts_name'] = $params['private_account_name'];
        }
        if (isset($params['private_account_bank']) && $params['private_account_bank']){
            $data['private_accounts_bank'] = $params['private_account_bank'];
        }
        if (isset($params['private_city']) && $params['private_city']){
            $data['opening_bank_city'] = self::getAreaId($params['private_city'],2);
        }
        if (isset($params['private_idcard']) && $params['private_idcard']){
            $data['opening_id_card'] = $params['private_idcard'];
        }
        if (isset($params['private_phone']) && $params['private_phone']){
            $data['bank_retained_contact_way'] = $params['private_phone'];
        }
        if (isset($params['payment_effective_time']) && $params['payment_effective_time']){
            $data['payment_effective_time'] = strtotime($params['payment_effective_time']);
        }
        if (isset($params['return_goods'])){
            $data['return_goods'] =  self::$whether[$params['return_goods']];
        }
        if (isset($params['return_goods_data'])){
            $data['return_goods_data'] =  self::$return_goods_data[$params['return_goods']];
        }
        if (isset($params['label_deck'])){
            $data['label_deck'] =  self::$label_deck[$params['label_deck']];
            if ($data['label_deck'] == 3){
                $data['label_deck_update_time'] = time();
            }
        }
        if (isset($params['case_packing'])){
            $data['case_packing'] =  self::$whether[$params['case_packing']];
        }
        if (isset($params['supply_chain_finance'])){
            $data['supply_chain_finance'] =  self::$whether[$params['supply_chain_finance']];
        }
        if (isset($params['delivery_day'])){
            $data['delivery_day'] =  $params['delivery_day'] ?? 0;
        }
        if (isset($params['public_bank_address'])){
            $data['public_bank_address'] =  $params['public_bank_address'] ?? '';
        }
        if (isset($params['public_swift_address'])){
            $data['public_swift_address'] =  $params['public_swift_address'] ?? '';
        }
        if (isset($params['public_cnaps'])){
            $data['public_cnaps'] =  $params['public_cnaps'] ?? '';
        }
        if (isset($params['private_bank_address'])){
            $data['private_bank_address'] =  $params['private_bank_address'] ?? '';
        }
        if (isset($params['private_swift_address'])){
            $data['private_swift_address'] =  $params['private_swift_address'] ?? '';
        }
        if (isset($params['private_cnaps'])){
            $data['private_cnaps'] =  $params['private_cnaps'] ?? '';
        }
        if (isset($params['default_payment_method'])){
            $data['default_payment_method'] =  static::$default_payment_method[$params['default_payment_method']] ?? 1;
        }

        //支付方式
        if(isset($params['pay_type']) && is_numeric($params['pay_type'])){
            $data['pay_type']=self::$pay_type_map[$params['pay_type']];
            /**
            if ($data['pay_type'] == 2){
                if (empty($data['private_accounts'])){
                    throw new Exception('对私账号不能为空');
                }
                if (empty($data['private_accounts_name'])){
                    throw new Exception('对私户名不能为空');
                }
                if (empty($data['private_accounts_bank'])){
                    throw new Exception('对私账号开户行不能为空');
                }
                if (empty($data['private_swift_address'])){
                    throw new Exception('对私SWIFT ADDRESS不能为空');
                }
                if (empty($data['private_cnaps'])){
                    throw new Exception('对私CNAPS不能为空');
                }
                if (empty($data['default_payment_method'])){
                    throw new Exception('默认付款方式不能为空');
                }
            }
            */
        }

        $data['update_time'] = time();
//        $flag = $ValidateSupplier->scene('apiUpdate')->check($data);
//        if ($flag === false) {
//            throw new Exception($ValidateSupplier->getError());
//        }
        return $data;
    }

    private static function dataConversion($data)
    {
        if (!empty($data['operator_id'])){
            $userInfo = User::where(['job_number' => $data['operator_id']])->field('id')->find();
        }
        if (empty($userInfo) && !empty($data['creator'])){
            $userInfo = User::where(['realname' => $data['creator']])->field('id')->find();
        }
        if (!empty($data['supply_chain_specialist_id'])){
            $supplyChainSpecialistId = [];
            $supplyChainSpecialist = User::where(['job_number' => $data['supply_chain_specialist_id']])->field('id')->find();
            $supplierService = new SupplierService();
            $supplyChainSpecialists = $supplierService->supplyChainDepartmentId();
            foreach ($supplyChainSpecialists as $supplyChainSpecialistValue){
                $supplyChainSpecialistId[] = $supplyChainSpecialistValue['id'];
            }
            if (!in_array($supplyChainSpecialist['id'],$supplyChainSpecialistId)){
                throw new Exception('该人员不属于供应链部门');
            }
        }
        $supplierData = [
            'company_name' => $data['business_license'],//公司名
            'code' => $data['business_code'],//统一社会信用代码
            'type' => static::$type_map[$data['type']],//供应商类别
            'invoice' => static::$invoice_map[$data['invoice']],//发票类型
            'legal' => $data['legal'],//法人代表
            'business_license' => $data['business_license'],//营业执照名称
            'online_shop_name' => $data['online_shop_name'] ?? '',//网上店铺全称
            'categorys' => self::getCategorys($data['properties']),//类目
            'balance_type' => static::$balance_type_map[$data['balance_type']],//结算方式
            'transaction_type' => static::$transaction_type_map[$data['transaction_type']],//交易类型
            'pay_type' => static::$pay_type_map[$data['pay_type']],//支付方式
            'bank' => $data['bank'],//开户行    选
            'bank_account' => $data['bank_account'],//账号    选
            'account_name' => $data['account_name'],//开户名    选
            'contacts2' => $data['contacts2'],//联系人2        选
            'contacts2_job' => $data['contacts2_job'],//联系人2的职务   选
            'mobile2' => $data['tel2'],//联系人2联系电话      选
            'contacts_job' => $data['contacts_job'],//联系人1职务
            'contacts' => $data['contacts'],//联系人1
            'mobile' => $data['tel1'],//联系电话
            'province_id' => self::getAreaId($data['province'], 1),//省id
            'city_id' => self::getAreaId($data['city'], 2),//城市id
            'area_id' => self::getAreaId($data['areaOrCounty'], 3),//地区id
            'province_name' => $data['province'],//省名称
            'city_name' => $data['city'],//城市名称
            'area_name' => $data['areaOrCounty'],//地区名称
            'address' => $data['address'],//地址
            'qq' => $data['qq'],//qq号码
            'qq2' => $data['qq2'],//qq号码2
            //'status' => SupplierService::SUPPLIER_VERIFIED_STATUS,//状态 0-待审  1-已审 2-禁用
            'status' => 1,//状态 0-待审  1-已审 2-禁用
            'remark' => $data['remark'],//备注    选
            'link' => $data['website'],//供应商链接
            'new_reason' =>$data['new_reason'],// 新增理由  "新品新增","替换"
            'creator_id' => !empty($userInfo) ? $userInfo['id'] : 0,//创建者id

            'public_accounts' => '',//对公账号(仅限于线下交易)   非必填
            'public_accounts_bank' => '',//对公账号开户行(仅限于线下交易)   非必填
            'public_accounts_name' => '',//对公账号名(仅限于线下交易)   非必填
            'public_bank_city' => '',//开户行所在市(对公)   非必填
            'public_bank_retained_contact_way' => '',//银行留存联系方式(对公)   非必填
            'public_bank_address'=> '',//开户行支行具体地址（对公）
            'public_swift_address'=> '',//SWIFT ADDRESS(对公)
            'public_cnaps'=> '',//CNAPS（对公）

            'private_accounts' => '',//对私账号(仅限于线下交易)
            'private_accounts_name' => '',//对私户名(仅限于线下交易)
            'private_accounts_bank' => '',//对私账号开户行(仅限于线下交易)
            'opening_bank_city'=> '',
            'opening_id_card'=> '',
            'bank_retained_contact_way'=> '',
            'private_bank_address'=> '',//开户行支行具体地址（对私）
            'private_swift_address'=> '',//SWIFT ADDRESS(对私)
            'private_cnaps'=> '',//CNAPS（对私）

            'payment_effective_time'=> strtotime($data['payment_effective_time']) ?? '',//账期生效日期
            'payment_communicator'=> '',//谈账期人
            'return_goods'=> static::$whether[$data['return_goods']] ?? 0,//是否能退货
            'return_goods_data'=> static::$return_goods_data[$data['return_goods_data']] ?? 0,//退货天数
            'label_deck'=> static::$label_deck[$data['label_deck']] ?? 0,//是否贴标、套牌
            'case_packing'=> static::$whether[$data['case_packing']] ?? 0,//外箱包装是否符合标准
            'supply_chain_finance'=> static::$whether[$data['supply_chain_finance']] ?? 0,//供应链金融
            'supply_chain_specialist_id'=> !empty($supplyChainSpecialist) ? $supplyChainSpecialist['id'] : 0,//供应链专员id
            'delivery_day'=> $data['delivery_day'] ?? 0,//交货时间
            'default_payment_method'=> static::$default_payment_method[$data['default_payment_method']] ?? 1,//默认付款方式
            'ali_account'=> $data['ali_account'] ?? '',

        ];
        $supplierData['create_time'] = strtotime($data['create_time']);
        $supplierData['update_time'] = strtotime($data['update_time']);
        if ($supplierData['pay_type'] == 2){
            $supplierData['public_accounts'] = $data['open_account'] ?? '';//对公账号(仅限于线下交易)   非必填
            $supplierData['public_accounts_bank'] = $data['open_account_bank'] ?? '';//对公账号开户行(仅限于线下交易)   非必填
            $supplierData['public_accounts_name'] = $data['open_account_name'] ?? '';//对公账号名(仅限于线下交易)   非必填
            $supplierData['public_bank_city'] = $data['open_city'] ? self::getAreaId($data['open_city'],2): '';//开户行所在市(对公)   非必填
            $supplierData['public_bank_retained_contact_way'] = $data['open_phone'] ?? '';//银行留存联系方式(对公)   非必填
            $supplierData['public_bank_address'] = $data['public_bank_address'] ?? '';//开户行支行具体地址（对公）
            $supplierData['public_swift_address'] = $data['public_swift_address'] ?? '';//SWIFT ADDRESS(对公)
            $supplierData['public_cnaps'] = $data['public_cnaps'] ?? '';//CNAPS（对公）

            $supplierData['private_accounts'] = $data['private_account'] ?? '';//对私账号(仅限于线下交易)
            $supplierData['private_accounts_name'] = $data['private_account_name'] ?? '';//对私户名(仅限于线下交易)
            $supplierData['private_accounts_bank'] = $data['private_account_bank'] ?? '';//对私账号开户行(仅限于线下交易)
            $supplierData['opening_bank_city'] = $data['private_city'] ? self::getAreaId($data['private_city'],2): '';
            $supplierData['opening_id_card'] = $data['private_idcard'] ?? '';
            $supplierData['bank_retained_contact_way'] = $data['private_phone'] ?? '';
            $supplierData['private_bank_address'] = $data['private_bank_address'] ?? '';//开户行支行具体地址（对私）
            $supplierData['private_swift_address'] = $data['private_swift_address'] ?? '';//SWIFT ADDRESS(对私)
            $supplierData['private_cnaps'] = $data['private_cnaps'] ?? '';//CNAPS（对私）
            if (empty($supplierData['private_accounts'])){
                throw new Exception('对私账号不能为空');
            }
            if (empty($supplierData['private_accounts_name'])){
                throw new Exception('对私户名不能为空');
            }
            if (empty($supplierData['private_accounts_bank'])){
                throw new Exception('对私账号开户行不能为空');
            }
            if (empty($supplierData['private_swift_address'])){
                throw new Exception('对私SWIFT ADDRESS不能为空');
            }
            if (empty($supplierData['private_cnaps'])){
                throw new Exception('对私CNAPS不能为空');
            }
            if (empty($supplierData['default_payment_method'])){
                throw new Exception('默认付款方式不能为空');
            }
        }
        if ($supplierData['balance_type'] && in_array($supplierData['balance_type'],[11,14,15,16,18,19,20,21,22,23,24,25,26])){
            if (!$supplierData['ali_account']){
                throw new Exception('阿里账号不能为空');
            }
        }
        return $supplierData;
    }
    /**
     * @desc 获取省、市、区ID
     * @param string $name 区域名称
     * @param int $level 对应area表level
     * @author Jimmy <554511322@qq.com>
     * @date 2018-02-07 16:13:11
     */
    private static function getAreaId($name, $level)
    {
        if(!$name){
            return 0;
        }
        $map['name'] = $name;
        $map['level'] = $level;
        $res = Area::get($map);
        if (!$res['id']) {
            throw new Exception("{$name}无对应数据!");
        }
        return $res['id'];
    }

    private static function getCategorys($categorys)
    {
        $categoryIds = [];
        $categoryHelp = new CategoryHelp();
        foreach ($categorys as $category){
            $categoryData = explode('/',$category);
            $categoryId = $categoryHelp->getIdByAName($categoryData);
            $categoryIds[] = end($categoryId);
        }
        $categoryIds = implode(',',$categoryIds);
        return $categoryIds;
    }

    private function editContent($data, $supplierInfo): string
    {
        $content = 'OA修改';
        $supplierService = new SupplierService();
        $fields = SupplierActionLog::FIELDS;
        foreach ($fields as $k => $v) {
            if (!isset($data[$k]) || $k == 'introduce'){
                continue;
            }
            $data[$k] = ltrim($data[$k],' ');
            $supplierInfo[$k] = ltrim($supplierInfo[$k],' ');
            if (isset($data[$k]) && $data[$k] != $supplierInfo[$k]) {
                $fields[$k] = rtrim($fields[$k],',');
                switch ($k){
                    case 'level':
                        $supplierInfo[$k] = $supplierService->getLevelText($supplierInfo[$k]);
                        $data[$k] = $supplierService->getLevelText($data[$k]);
                        break;
                    case 'type':
                        $supplierInfo[$k] = $supplierService->getTypeText($supplierInfo[$k]);
                        $data[$k] = $supplierService->getTypeText($data[$k]);
                        break;
                    case 'invoice':
                        $supplierInfo[$k] = $supplierService->getInvoiceText($supplierInfo[$k]);
                        $data[$k] = $supplierService->getInvoiceText($data[$k]);
                        break;
                    case 'purchaser_id':
                    case 'payment_communicator':
                    case 'supply_chain_specialist_id':
                        $cacheUser = Cache::store('User');
                        $supplierInfo[$k] = $cacheUser->getOneUser($supplierInfo[$k])['realname'] ?? '';
                        $data[$k] = $cacheUser->getOneUser($data[$k])['realname'] ?? '';
                        break;
                    case 'return_goods':
                    case 'case_packing':
                    case 'supply_chain_finance':
                    case 'auto_payment_request':
                        $supplierInfo[$k] = $supplierService->getWhetherText($supplierInfo[$k]);
                        $data[$k] = $supplierService->getWhetherText($data[$k]);
                        break;
                    case 'categorys':
                        $supplierInfo[$k] = $supplierService->getCategoryName($supplierInfo[$k]);
                        $data[$k] = $supplierService->getCategoryName($data[$k]);
                        break;
                    case 'transaction_type':
                        $supplierInfo[$k] = $supplierService->getTransactionText($supplierInfo[$k]);
                        $data[$k] = $supplierService->getTransactionText($data[$k]);
                        break;
                    case 'balance_type':
                        $supplierInfo[$k] = Cache::store('Supplier')->getBalanceTypeText($supplierInfo[$k]);
                        $data[$k] = Cache::store('Supplier')->getBalanceTypeText($data[$k]);
                        break;
                    case 'pay_type':
                        $supplierInfo[$k] = $supplierService->getPayTypeText($supplierInfo[$k]);
                        $data[$k] = $supplierService->getPayTypeText($data[$k]);
                        break;
                    case 'province_id':
                    case 'city_id':
                    case 'area_id':
                        $supplierInfo[$k] = (new Area())->where('id', $supplierInfo[$k])->value('name') ?? '';
                        $data[$k] = (new Area())->where('id', $data[$k])->value('name') ?? '';
                        break;
                    case 'payment_effective_time':
                        if ($supplierInfo[$k]){
                            $supplierInfo[$k] = date("Y-m-d",$supplierInfo[$k]);
                        }
                        $data[$k] = date("Y-m-d",$data[$k]);
                        break;
                    default:
                        break;
                }
                $content .= $fields[$k].':'.$supplierInfo[$k].'修改成'.$data[$k].',';
            }
        }
        if (isset($data['change_payment_information']) && $data['change_payment_information']) {
            $content .= '付款资料,';
        }
        if (isset($data['change_business_file']) && $data['change_business_file']) {
            $content .= '营业执照图片,';
        }
        if (isset($data['change_supplier_plant']) && $data['change_supplier_plant']) {
            $content .= '供应商厂房,';
        }
        if (isset($data['change_business_image']) && $data['change_business_image']) {
            $content .= '营业图片,';
        }

        //return rtrim($content,',');
        $content = rtrim($content,',');
        $supplierService->addLog($supplierInfo->id,$data['operator_id'],6,$content);
        return $content;
    }

}