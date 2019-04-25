<?php

namespace app\common\model\aliexpress;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\Goods;
use app\common\model\GoodsSku;
use app\common\service\UniqueQueuer;
use app\common\traits\ModelFilter;
use app\goods\service\GoodsPublishMapService;
use app\index\service\Department;
use app\index\service\MemberShipService;
use app\publish\queue\AliexpressQueueJob;
use app\publish\task\AliexpressPublish;
use erp\ErpModel;
use think\Db;
use think\db\Query;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\Model;
use app\publish\queue\AliexpressPublishFailQueue;
use app\common\model\aliexpress\AliexpressPublishTask as AliexpressPublishTaskModel;

/**
 * Created by ZendStudio.
 * User: Hot-Zr
 * Date: 2017年3月29日
 * Time: 16:36:45
 */
class AliexpressProduct extends ErpModel
{

    private $filterAccount = [];

    use ModelFilter;
    const PRODUCT_UNIT = [
        '100000015' => '件/个(piece/pieces)',
        '100000000' => '袋(bag/bags)',
        '100000001' => '桶(barrel/barrels)',
        '100000002' => '蒲式耳(bushel/bushels)',
        '100078580' => '箱(carton)',
        '100078581' => '厘米(centimeter)',
        '100000003' => '立方米(cubicmeter)',
        '100000004' => '打(dozen)',
        '100078584' => '英尺(feet)',
        '100000005' => '加仑(gallon)',
        '100000006' => '克(gram)',
        '100078587' => '英寸(inch)',
        '100000007' => '千克(kilogram)',
        '100078589' => '千升(kiloliter)',
        '100000008' => '千米(kilometer)',
        '100078559' => '升(liter/liters)',
        '100000009' => '英吨(longton)',
        '100000010' => '米(meter)',
        '100000011' => '公吨(metricton)',
        '100078560' => '毫克(milligram)',
        '100078596' => '毫升(milliliter)',
        '100078597' => '毫米(millimeter)',
        '100000012' => '盎司(ounce)',
        '100000014' => '包(pack/packs)',
        '100000013' => '双(pair)',
        '100000016' => '磅(pound)',
        '100078603' => '夸脱(quart)',
        '100000017' => '套(set/sets)',
        '100000018' => '美吨(shortton)',
        '100078606' => '平方英尺(squarefeet)',
        '100078607' => '平方英寸(squareinch)',
        '100000019' => '平方米(squaremeter)',
        '100078609' => '平方码(squareyard)',
        '100000020' => '吨(ton)',
        '100078558' => '码(yard/yards)'
    ];
    const ONSELLING = 1;//产品计数单位

    //产品状态
    const OFFLINE = 2;    //上架
    const AUDITING = 3;    //下架
    const EDITINGREQUIRED = 4;    //审核中
    const PRODUCT_STATUS = [
        self::ONSELLING => 'onSelling',
        self::OFFLINE => 'offline',
        self::AUDITING => 'auditing',
        self::EDITINGREQUIRED => 'editingRequired',
    ];    //审核不通过
    const PRODUCT_STATUS_DISPLAY = [
        self::ONSELLING => '上架',
        self::OFFLINE => '下架',
        self::AUDITING => '审核中',
        self::EDITINGREQUIRED => '审核不通过',
    ];
    const PLACE_OEDER_WITHHOLD = 1;
    //库存扣减策略
    const PAYMENT_SUCCESS_DEDUCT = 2;//下单减库存
    const REDUCE_STRATEGY = [
        self::PLACE_OEDER_WITHHOLD => 'place_order_withhold',
        self::PAYMENT_SUCCESS_DEDUCT => 'payment_success_deduct'
    ];//付款减库存
    const EXPIRE_OFFLINE = 1;

    //下架原因
    const USER_OFFLINE = 2;//过期下架
    const VIOLATE_OFFLINE = 3;//用户下架
    const PUNISH_OFFLINE = 4;//违规下架
    const DEGRADE_OFFLINE = 5;//交易违规下架
    const INDUSTRY_OFFLINE = 6;//降级下架
    const WS_DISPLAY = [
        self::EXPIRE_OFFLINE => 'expire_offline',
        self::USER_OFFLINE => 'user_offline',
        self::VIOLATE_OFFLINE => 'violate_offline',
        self::PUNISH_OFFLINE => 'punish_offline',
        self::DEGRADE_OFFLINE => 'degrade_offline',
        self::INDUSTRY_OFFLINE => 'industry_offline',
    ];//未续约下架
    const TYPE_TDX = 1;

    //产品类型
    const TYPE_ISV = 2;//淘宝代销产品
    const PRODUCT_TYPE = [
        self::TYPE_TDX => 'tdx',
        self::TYPE_ISV => 'isv'
    ];//API上传产品
    const PUBLISH_DEFULT = 0;

    //产品刊登状态
    const PUBLISH_DRAFT = 0;//保存草稿
    const PUBLISH_TIMING = 1;   //信息已保存，未刊登
    const PUBLISH_COMPLETED = 2;    //定时刊登
    const PUBLISH_PENDING = 3;//已刊登
    const PUBLISH_FAIL = 4;  //刊登中
    const PROHIBITED_TYPE = [
        'FORBIDEN_TYPE' => '禁用',
        'RESTRICT_TYPE' => '限定',
        'BRAND_TYPE' => '品牌',
        'TORT_TYPE' => '侵权',
    ];     //刊登失败
    protected $autoWriteTimestamp = true;

    public static function getReduceStrategy($strReduceStrategy)
    {
        if (isset(self::REDUCE_STRATEGY[$strReduceStrategy])) {
            return self::REDUCE_STRATEGY[$strReduceStrategy];
        } else {
            return '';
        }
    }

    public static function setReduceStrategy($strReduceStrategy)
    {
        $arrReduceStrategy = array_flip(self::REDUCE_STRATEGY);
        return isset($arrReduceStrategy[$strReduceStrategy]) ? $arrReduceStrategy[$strReduceStrategy] : 0;
    }

    /**
     * listing过滤
     * @param Query $query
     * @param $params
     */
    public function scopeListing(Query $query, $params)
    {
        $this->filterAccount = array_merge($params, $this->filterAccount);
        if(!empty($params))
        {
            $query->where('__TABLE__.account_id','in', $this->filterAccount);
        }
    }


    /**
     * 部门过滤
     * @param Query $query
     * @param $params
     */
    public function scopeDepartment(Query $query, $params)
    {
        if (!empty($params)) {
            $accounts = [];
            //用户列表
            foreach ($params as $param) {
                $users = (new Department())->getDepartmentUser($param);

                if ($users) {
                    foreach ($users as $user) {
                        if ($user) {
                            $where['seller_id'] = ['IN', $user];
                            $account_list = MemberShipService::getChannelAccountsByUsers(3, $where);
                            if ($account_list) {
                                $accounts = array_merge($accounts, $account_list);
                            }
                        }
                    }
                }
            }
            if (!empty($accounts)) {
                $query->where('__TABLE__.account_id', 'in', $accounts);
            }
        }
    }

    /**
     * 新增产品
     * @param $productData
     * @param $infoData
     * @param $skuData
     * @return array
     */
    public function addProduct($productData, $infoData, $skuData)
    {
        try {

            Db::startTrans();
            try {
                $this->allowField(true)->save($productData);

                $ali_product_id = $this->id;
                $this->productInfo()->save($infoData);

                if($skuData){

                    foreach ($skuData as $key => $val) {
                        $skuData[$key]['ali_product_id'] = $ali_product_id;
                    }

                    $productSkuModel = new AliexpressProductSku();
                    $productSkuModel->saveAll($skuData);
                }
                Db::commit();
            } catch (PDOException $exp) {
                Db::rollback();
                throw new JsonErrorException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
            } catch (DbException $exp) {
                Db::rollback();
                throw new JsonErrorException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
            } catch (\Exception $exp) {
                Db::rollback();
                throw new JsonErrorException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
            }

            if (isset($productData['is_plan_publish']) && $productData['is_plan_publish'] == 1) {

                $data['send_return'] = '';
                $data['create_time'] = $data['update_time'] = time();
                if (isset($productData['plan_publish_time']) && !empty($productData['plan_publish_time'])) {
                    $data['plan_time'] = $productData['plan_publish_time'];
                } else {
                    $data['plan_time'] = 0;
                }
                Db::startTrans();
                try {
                    $this->plan()->save($data);
                    $this->where(['id' => $this->id])->setField('status', self::PUBLISH_PENDING);
                    $this->where(['id' => $this->id])->setField('product_status_type', self::PUBLISH_FAIL);
                    Db::commit();
                } catch (PDOException $exp) {
                    Db::rollback();
                    throw new JsonErrorException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
                } catch (DbException $exp) {
                    Db::rollback();
                    throw new JsonErrorException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
                } catch (\Exception $exp) {
                    Db::rollback();
                    throw new JsonErrorException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
                }

                $queue = (string)$productData['id'];
                if ($data['plan_time'] <= time()) {
                    (new UniqueQueuer(AliexpressQueueJob::class))->push($queue);
                } else {
                    (new UniqueQueuer(AliexpressQueueJob::class))->push($queue, $data['plan_time']);
                }
            }

            if (isset($productData['goods_spu']) && !empty($productData['goods_spu'])) {
                GoodsPublishMapService::update(4, $productData['goods_spu'], $productData['account_id']);
            }

            $this->sycEveryDayPublish($ali_product_id, $productData);
            return ['status' => true, 'id' => $this->id];
        } catch (Exception $ex) {
            return ['status' => false, 'error_message' => $ex->getMessage()];
        }
    }



    /**
     *同步每日刊登任务状态,以及回写listing_id
     *
     */
    public function sycEveryDayPublish($ali_product_id, $productData = [])
    {

        $productInfo = (new AliexpressProduct())->field('status')->where('id','=',$ali_product_id)->find();

        $publishTaskModel = new AliexpressPublishTaskModel();
        if(isset($productData['task_id']) && $productData['task_id']) {

            $publishTaskModel->update(['ali_product_id' => $ali_product_id, 'status' => $productInfo['status']], ['id' => $productData['task_id']]);
        }else{

            $updateDate = [
                'status' => $productInfo['status']
            ];

            //刊登成功,则会写实际刊登时间
            if($productInfo['status'] == 2) {
                $updateDate['actual_publish_time'] = time();
            }

            $publishTaskModel->update($updateDate, ['ali_product_id' => $ali_product_id]);
        }
    }


    /**
     * 编辑修改产品
     * @param AliexpressProduct $model
     * @param $productData
     * @param $infoData
     * @param $skuData
     * @return array
     */
    public function updateProduct(AliexpressProduct $model, $productData, $infoData, $skuData)
    {

        try {

            $arrSku = [];
            foreach ($skuData as $sku) {
                $arrSku[$sku['sku_code']] = $sku;
            }

            $queue = (string)$model->id;

            if (empty($queue)) {
                throw new Exception("id不能为空");
            }

            $skuModel = new AliexpressProductSku();
            $productSku = $skuModel->field('id,sku_code')->where(['ali_product_id' => $queue])->select();

            $del_sku = [];
            foreach ($productSku as $sku) {
                if (isset($arrSku[$sku['sku_code']])) {
                    $arr_sku = $arrSku[$sku['sku_code']];
                    $arr_sku['lock_sku'] = 1;

                    $skuModel->update($arr_sku, ['id' => $sku['id']]);

                    unset($arrSku[$sku['sku_code']]);
                } else {
                    $del_sku[] = $sku['id'];
                }
            }


            if($del_sku) {
                $skuModel->whereIn('id', $del_sku)->delete();
            }


            if($arrSku) {

                foreach ($arrSku as $key => $val) {

                    $arrSku[$key]['product_id'] = $productData['product_id'];
                    $arrSku[$key]['ali_product_id'] = $queue;
                }
            }

            Db::startTrans();
            try {
                $model->isUpdate(true)->allowField(true)->save($productData);
                $infoModel = new AliexpressProductInfo();
                $infoModel->update($infoData, ['ali_product_id' => $queue]);
                $skuModel->saveAll(array_values($arrSku));
                Db::commit();
            } catch (PDOException $exp) {
                Db::rollback();
                throw new Exception($exp->getMessage());
            } catch (DbException $exp) {
                Db::rollback();
                throw new Exception($exp->getMessage());
            } catch (\Exception $exp) {
                Db::rollback();
                throw new Exception($exp->getMessage());
            }


            if (isset($productData['is_plan_publish']) && $productData['is_plan_publish'] == 1 && $model['status'] != self::PUBLISH_COMPLETED) {

                $status = $model['status'];

                $plan_status = $status == 4 ? -1 : 0;

                $planModel = new AliexpressPublishPlan();
                $planModel = $planModel->where(['ap_id' => $queue])->find();

                if (empty($planModel)) {
                    $data['status'] = $plan_status;
                    $data['send_return'] = '';
                    $data['create_time'] = $data['update_time'] = time();
                    if (isset($productData['plan_publish_time']) && !empty($productData['plan_publish_time'])) {
                        $data['plan_time'] = $productData['plan_publish_time'];
                    } else {
                        $data['plan_time'] = 0;
                    }

                    Db::startTrans();
                    try {
                        $model->plan()->save($data);
                        Db::commit();
                    } catch (PDOException $exp) {
                        Db::rollback();
                        throw new Exception($exp->getMessage());
                    } catch (DbException $exp) {
                        Db::rollback();
                        throw new Exception($exp->getMessage());
                    } catch (\Exception $exp) {
                        Db::rollback();
                        throw new Exception($exp->getMessage());
                    }

                    //如果未刊登成功
                    if (!isset($productData['product_id']) || empty($productData['product_id'])) {

                        //修复草稿箱提交两次
                        $model->update(['status'=>3],['id'=>$queue]);

                        if ($data['plan_time'] <= time()) {

                            if($status == 4) {
                                (new UniqueQueuer(AliexpressPublishFailQueue::class))->push($queue);
                            }else {
                                (new UniqueQueuer(AliexpressQueueJob::class))->push($queue);
                            }

                        } else {

                            if ($status == 4) {
                                (new UniqueQueuer(AliexpressPublishFailQueue::class))->push($queue, $data['plan_time']);
                            }else {
                                (new UniqueQueuer(AliexpressQueueJob::class))->push($queue, $data['plan_time']);
                            }
                        }
                    }

                    $productData['status'] = self::PUBLISH_PENDING;
                } else {

                    if ($planModel['status'] != 1) {

                        $model->update(['status'=>3],['id'=>$queue]);
                        $planModel->update(['status' => $plan_status, 'send_return' => ''], ['ap_id' => $queue]);

                        if ($planModel['plan_time'] <= time()) {

                            if($status == 4) {
                                (new UniqueQueuer(AliexpressPublishFailQueue::class))->push($queue);
                            }else {
                                (new UniqueQueuer(AliexpressQueueJob::class))->push($queue);
                            }

                        } else {

                            if ($status == 4) {
                                (new UniqueQueuer(AliexpressPublishFailQueue::class))->push($queue, $planModel['plan_time']);
                            }else {
                                (new UniqueQueuer(AliexpressQueueJob::class))->push($queue, $planModel['plan_time']);
                            }
                        }

                    } elseif ($planModel['status'] == 1) {

                        $productData['lock_update'] = 1;
                        $productData['lock_product'] = 1;

                    }

                    //修复线上刊登记录表成功,未刊登成功
                    if(isset($productData['product_id']) && empty($productData['product_id']) && $planModel['status'] == 1) {

                        $planModel->update(['status' => 0, 'send_return' => ''], ['ap_id' => $queue]);

                        if ($planModel['plan_time'] <= time()) {
                            (new UniqueQueuer(AliexpressQueueJob::class))->push($queue);
                        } else {
                            (new UniqueQueuer(AliexpressQueueJob::class))->push($queue, $planModel['plan_time']);
                        }
                    }
                }
            }

            //修复刊登成功,商品平台id为0,需重新提交刊登队列
            if (isset($productData['is_plan_publish']) && $productData['is_plan_publish'] == 1 && $model['status'] == self::PUBLISH_COMPLETED) {

                if (!isset($productData['product_id']) || empty($productData['product_id'])) {

                    $planModel = new AliexpressPublishPlan();
                    $planInfo = $planModel->where(['ap_id' => $queue])->find();

                    if($planInfo['status'] == 1){

                        $model->update(['status'=>3],['id'=>$queue]);
                        $planModel->update(['status' => 0, 'send_return' => ''], ['ap_id' => $queue]);

                        if ($planModel['plan_time'] <= time()) {
                            (new UniqueQueuer(AliexpressQueueJob::class))->push($queue);
                        } else {
                            (new UniqueQueuer(AliexpressQueueJob::class))->push($queue, $planModel['plan_time']);
                        }
                    }
                }
            }

            $this->sycEveryDayPublish($model->id, $productData);
            return ['status' => true, 'id' => $model->id];
        } catch (Exception $ex) {
            throw new JsonErrorException($ex->getFile() . $ex->getLine() . $ex->getMessage());
            //return ['status'=>false,'error_message'=>$ex->getMessage()];
        }
    }


    //关联关系start
    //sku数据
    public function productGroup()
    {
        return $this->hasMany(AliexpressProductGroup::class, 'group_id', 'group_id');
    }

    public function productInfo()
    {
        return $this->hasOne(AliexpressProductInfo::class, 'ali_product_id', 'id');
    }

    //product_info

    public function productSku()
    {
        //return $this->hasMany(AliexpressProductSku::class,'product_id','product_id');
        return $this->hasMany(AliexpressProductSku::class, 'ali_product_id', 'id');
        //->field('sku_price,sku_code,sku_stock,ipm_sku_stock,product_id');
    }

    //账号信息

    public function plan()
    {
        return $this->hasOne(AliexpressPublishPlan::class, 'ap_id', 'id');
    }

    //商品信息


    //刊登计划

    public function account()
    {
        return $this->hasOne(AliexpressAccount::class, 'id', 'account_id')->field('id,code,refresh_token,access_token,client_secret,client_id');
    }

    //本地产品sku表

    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id', 'id');
    }

    //服务模板

    public function sku()
    {
        return $this->belongsToMany(GoodsSku::class, 'aliexpress_product_sku', 'goods_sku_id', 'ali_product_id');
    }

    //运费模板

    public function promise()
    {
        return $this->hasOne(AliexpressPromiseTemplate::class, 'template_id', 'promise_template_id');
    }

    //自定义模板

    public function freight()
    {
        return $this->hasOne(AliexpressFreightTemplate::class, 'template_id', 'freight_template_id');
    }

    //关联模板

    public function ctemp()
    {
        return $this->hasOne(AliexpressProductTemplate::class, 'id', 'custom_template_id')->field('id,name');
    }

    //关联关系end

    public function rtemp()
    {
        return $this->hasOne(AliexpressProductTemplate::class, 'id', 'relation_template_id')->field('id,name');
    }

    public function getIdAttr($v)
    {
        return (string)$v;
    }

    public function getGroupIdAttr($v)
    {
        $arr = json_decode($v, true);
        if (count($arr) > 1) {
            return $v;
        } else {
            return empty($arr) ? '' : $arr[0];
        }
    }

    public function getImageurlsAttr($v)
    {
        $images = explode(';', $v);
        if ($images) return $images[0];
        else return '';
    }

    /**
     * 初始化数据
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    protected function getProductStatusTypeAttr($value)
    {
        //$product_status = self::PRODUCT_STATUS;
        $product_status = [
            self::ONSELLING => '上架',
            self::OFFLINE => '下架',
            self::AUDITING => '审核中',
            self::EDITINGREQUIRED => '审核不通过',
        ];
        return isset($product_status[$value]) ? $product_status[$value] : '';
    }

    protected function setProductStatusTypeAttr($value)
    {
        return self::getStatus($value);
    }

    public static function getStatus($strStatus)
    {
        $arrProductStatus = array_flip(self::PRODUCT_STATUS);
        return isset($arrProductStatus[$strStatus]) ? $arrProductStatus[$strStatus] : 0;
    }

    //获取器start

    protected function setReduceStrategyAttr($value)
    {
        return self::setReduceStrategy($value);
    }
    //获取器end


    //修改器start

    protected function setWsDisplayAttr($value)
    {
        return self::getWsDisplay($value);
    }

//    protected function getReduceStrategyAttr($value)
//    {
//        return self::getReduceStrategy($value);
//    }

    public static function getWsDisplay($strWsDisplay)
    {
        $arrWsDisplay = array_flip(self::WS_DISPLAY);
        return isset($arrWsDisplay[$strWsDisplay]) ? $arrWsDisplay[$strWsDisplay] : 0;
    }

    protected function setSrcAttr($value)
    {
        return self::getProductType($value);
    }

    public static function getProductType($strType)
    {
        $arrType = array_flip(self::PRODUCT_TYPE);
        return isset($arrType[$strType]) ? $arrType[$strType] : 0;
    }

    //修改器end
}