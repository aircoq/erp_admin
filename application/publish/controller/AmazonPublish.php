<?php
namespace app\publish\controller;

use app\common\exception\JsonErrorException;
use app\common\model\amazon\AmazonPublishProduct;
use app\common\model\amazon\AmazonPublishProductDetail;
use app\common\model\amazon\AmazonSellerHeelSale;
use app\common\model\amazon\AmazonUpcParam;
use app\common\service\CommonQueuer;
use app\common\service\UniqueQueuer;
use app\publish\queue\AmazonPublishProductResultQueuer;
use app\publish\queue\AmazonPublishResultQueuer;
use app\publish\service\AliProductHelper;
use app\publish\service\AmazonPublishService;
use app\common\service\Common as CommonService;
use app\publish\service\AmazonTranslateService;
use app\publish\service\AmazonUpcService;
use think\Request;
use think\Exception;
use app\common\controller\Base;
use app\publish\service\AmazonXsdTemplate;
use app\publish\service\AmazonCategoryXsdConfig;
use app\publish\service\AmazonHeelSaleLogService;

/**
 * @module 刊登系统
 * @title Amazon刊登
 * @url /publish/amazon
 * Class AmazonPublish
 * @package app\publish\controller
 */
class AmazonPublish extends Base
{

    protected $lang = 'zh';

    public $service = null;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        //erp的语言设置，默认是中文，目前可能的值是en:英文；
        $this->lang = $request->header('Lang', 'zh');

        $this->service = new AmazonPublishService();
        $this->service->setLang($this->lang);
    }

    /**
     * @title amazon未刊登列表
     * @access public
     * @method get
     * @url unpublished
     * @apiRelate app\goods\controller\Category::index
     * @apiRelate app\publish\controller\AmazonPublishDraft::index&edit&save&update&delete
     * @apiRelate app\publish\controller\AmazonPublish::getUpc&goodsTortInfo&batchCopy&translate
     * @apiRelate app\publish\controller\PricingRule::calculate
     * @apiRelate app\publish\controller\AmazonShippingGroupName::index&read&add&update&delete
     * @return \think\response\Json
     */
    public function unpublished()
    {
        try {
            $param = $this->request->instance()->param();
            $page = $this->request->param('page', 1);
            $pageSize = $this->request->param('pageSize', 30);
            $fields = "*";
            $response = $this->service->getWaitPublishGoods($param, $page, $pageSize, $fields);
            return json($response);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 未刊登侵权信息
     * @param Request $request
     * @return \think\response\Json
     * @url /publish/amazon/goods-tort-info/:goods_id(\d+)
     * @method GET
     */
    public function goodsTortInfo(Request $request)
    {
        $goods_id = $request->param('goods_id');
        if (empty($goods_id)) {
            return json(['message' => 'goods_id为空'], 400);
        }
        $service = new AliProductHelper();
        $result = $service->goodsTortInfo($goods_id);
        return json($result);
    }

    /**
     * @title amazon开始刊登时获取模板
     * @access public
     * @method get
     * @url /publish/amazon/template
     * @param Request $request
     * @return \think\response\Json
     */
    public function template(Request $request)
    {
        $data = $request->param();
        try {
            $result = $this->validate($data, [
                'category_template_id|分类模板ID' => 'number',
                'product_template_id|产品模板ID' => 'number',
                'site|站点' => 'require|number',
            ]);
            if ($result !== true) {
                if ($this->lang == 'zh') {
                    return json(['message' => $result], 400);
                } else {
                    return json(['message' => 'Params Error.'], 400);
                }
            }

            $templateHelp = new AmazonXsdTemplate();
            $templateHelp->setLang($this->lang);

            $returnData = [];
            if (!empty($data['category_template_id'])) {
                $returnData['category'] = $templateHelp->getAttr($data['category_template_id'], $data['site']);
            }
            if (isset($data['product_template_id'])) {
                $returnData['product'] = $templateHelp->getProductAttr($data['product_template_id'], $data['site']);
            }
            return json($returnData);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title amazon刊登获取分类/产品模板列表
     * @access public
     * @method get
     * @url /publish/amazon/templatelist
     * @param Request $request
     * @return \think\response\Json
     */
    public function getTemplateList(Request $request)
    {
        try {
            $type = $request->get('type', '1');
            $site = $request->get('site', '');
            if (empty($type) || !in_array($type, ['1', '2'])) {
                if ($this->lang == 'zh') {
                    return json(['message' => '模板类型Type 是必传参数'], 400);
                } else {
                    return json(['message' => 'Params Error.'], 400);
                }
            }
            if (empty($site)) {
                if ($this->lang == 'zh') {
                    return json(['message' => '站点site 是必传参数'], 400);
                } else {
                    return json(['message' => 'Params Error.'], 400);
                }
            }
            $keyword = $request->get('keyword', '');
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 2000);
            $templateHelp = new AmazonXsdTemplate();
            $result = $templateHelp->getSimpleList($type, $site, $keyword, $page, $pageSize);
            if ($result === false) {
                return json(['message' => $templateHelp->getError()], 400);
            }
            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title amazon刊登站点列表；
     * @access public
     * @method get
     * @url /publish/amazon/site
     * @param Request $request
     * @return \think\response\Json
     */
    public function getAmazonSite()
    {
        $site = AmazonCategoryXsdConfig::getSiteList();
        return json(['site' => $site]);
    }


    /**
     * @title amazon刊登用站点取帐号列表；
     * @access public
     * @method GET
     * @url /publish/amazon/account
     * @apiRelate app\publish\controller\AmazonPublish::getAmazonSite
     * @apiFilter app\publish\filter\AmazonFilter
     * @apiFilter app\publish\filter\AmazonDepartmentFilter
     * @param Request $request
     * @return \think\response\Json
     */
    public function account()
    {
        try {
            $site = request()->get('site', '');
            $warehouse_type = request()->get('warehouse_type', '');

            $list = $this->service->getAccount($site, $warehouse_type);
            return json($list);
        } catch (Exception $e) {
            return json('MSG:' . $e->getMessage() . '; LINE:' . $e->getLine() . '; FILE:' . $e->getFile(), 400);
        }
    }

    /**
     * @title amazon刊登详情获取刊登字段；
     * @access public
     * @method GET
     * @url /publish/amazon/field
     * @param Request $request
     * @return \think\response\Json
     */
    public function field(Request $request)
    {
        try {
            $params = $request->get();
            $result = $this->validate($params, [
                'spu' => 'require|length:1,30',
                'account_id' => 'integer',
                'site' => 'integer',
            ]);
            if ($result === true) {
                //帐号和站点不可以同时为空；
                if (empty($params['account_id']) && empty($params['site'])) {
                    if ($this->lang == 'zh') {
                        return json(['message' => '站点site,和帐号account_id 不可以同时为空'], 400);
                    } else {
                        return json(['message' => 'Params error'], 400);
                    }
                }
            }
            if ($result !== true) {
                if ($this->lang == 'zh') {
                    return json(['message' => $result], 400);
                } else {
                    return json(['message' => 'Params error'], 400);
                }
            }

            $data = $this->service->getField($params);
            return json($data);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title amazon刊登详情保存；
     * @access public
     * @method POST
     * @url /publish/amazon/detail
     * @apiRelate app\publish\controller\AmazonPublishDraft::index
     * @apiRelate app\publish\controller\AmazonPublishDraft::edit
     * @apiRelate app\publish\controller\AmazonPublishDraft::save
     * @apiRelate app\publish\controller\AmazonPublishDraft::update
     * @apiRelate app\publish\controller\AmazonPublishDraft::delete
     * @apiRelate app\publish\controller\AmazonPublish::getUpc
     * @apiRelate app\publish\controller\AmazonPublish::batchCopy
     * @apiRelate app\publish\controller\AmazonPublish::translate
     * @apiRelate app\publish\controller\PricingRule::calculate
     * @param Request $request
     * @return \think\response\Json
     */
    public function detail(Request $request)
    {
        try {
            $list = $request->post('data');

            $user = CommonService::getUserInfo($request);
            $uid = $user['user_id'];
            $result = $this->service->savePublishData($list, $uid);
            if ($result) {
                if ($this->lang == 'zh') {
                    return json(['message' => '保存成功' . ($this->service->getSaveReplace() ? '，已更换特殊字符' : '')]);
                } else {
                    return json(['message' => 'Save success' . ($this->service->getSaveReplace() ? '，replace the character' : '')]);
                }
            } else {
                if ($this->lang == 'zh') {
                    return json(['message' => '保存失败'], 400);
                } else {
                    return json(['message' => 'System Error'], 400);
                }
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title amazon刊登详情保存；
     * @access public
     * @method GET
     * @url /publish/amazon/edit
     * @param Request $request
     * @return \think\response\Json
     */
    public function edit(Request $request)
    {
        try {
            $id = $request->get('id', 0);
            $copy = $request->get('copy', 0);
            $result = $this->service->getPublishData($id, $copy);
            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine()], 400);
        }
    }

    /**
     * @title amazon刊登记录更改为失败；
     * @access public
     * @method GET
     * @url /publish/amazon/:id/defeat
     * @param Request $request
     * @return \think\response\Json
     */
    public function defeat($id)
    {
        try {
            $id = intval($id);
            if ($this->service->defeat($id)) {
                if ($this->lang == 'zh') {
                    return json(['message' => '更新成功。']);
                } else {
                    return json(['message' => 'Updated successful.']);
                }
            }
            if ($this->lang == 'zh') {
                return json(['message' => '更新失败。'], 400);
            } else {
                return json(['message' => 'System Error.'], 400);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon刊登修复；
     * @access public
     * @method GET
     * @url /publish/amazon-task/:type/:id/:status
     * @param Request $request
     * @return \think\response\Json
     */
    public function task($type, $id, $status = '', Request $request)
    {
        if (empty($type) || empty($id)) {
            return;
        }

        switch ($type) {
            case 'product':
                AmazonPublishProduct::update(['publish_status' => $status], ['id' => $id]);
                echo '刊登记录ID：' . $id . ' 状态更新为:' . $status;
                break;
            case 'detail':
                $field = '';
                $detail_type = $request->get('detail_type', 0);
                if (!empty($detail_type)) {
                    switch ($detail_type) {
                        //上传了产品
                        case 1:
                            $field = 'upload_product';
                            break;

                        //上传了对应关系
                        case 2:
                            $field = 'upload_relation';
                            break;

                        //上传了数量
                        case 3:
                            $field = 'upload_quantity';
                            break;

                        //上传了图片
                        case 4:
                            $field = 'upload_image';
                            break;

                        //上传了价格
                        case 5:
                            $field = 'upload_price';
                            break;
                    }
                }
                if ($field) {
                    AmazonPublishProductDetail::update([$field => $status], ['id' => $id]);
                    echo '刊登详情ID：' . $id . '状态字段：' . $field . '更新为' . $status;
                }
                break;
            case 'result':
                (new UniqueQueuer(AmazonPublishProductResultQueuer::class))->push($id);
                echo '刊登submissionId：' . $id . ' 已重新推送进队列；';
                break;
        }
    }


    /**
     * @title amazon刊登翻译；
     * @access public
     * @method POST
     * @url /publish/amazon/translate
     * @param Request $request
     * @return \think\response\Json
     */
    public function translate(Request $request)
    {
        try {
            $data = $request->post('data');
            $server = new AmazonTranslateService();
            $result = $server->translate($data);
            return json(['data' => $result]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $msg = json_decode($e->getMessage(), true);
            if (is_array($msg) && !empty($msg['error']['message'])) {
                if ($msg['error']['message'] == 'Daily Limit Exceeded') {
                    if ($this->lang == 'zh') {
                        return json(['message' => '今日翻译限额的字符已用完，请等待太平洋时间零点后继续使用'], 400);
                    } else {
                        return json(['message' => 'The characters of the translation quota have been used up today. Please wait for zero Pacific time to continue using'], 400);
                    }
                } else {
                    return json(['message' => $msg['error']['message']], 400);
                }

            }
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon获取UPC;
     * @access public
     * @method get
     * @url /publish/amazon/:num/upc
     * @param Request $request
     * @return \think\response\Json
     */
    public function getUpc(Request $request, $num = 1)
    {
        try {
            if ($num < 1) {
                if ($this->lang == 'zh') {
                    return json(['message' => '正在获取不正确的UPC数量.'], 400);
                } else {
                    return json(['message' => 'Params Error.'], 400);
                }
            }
            $server = new AmazonUpcService();
            $result = $server->getUpc($num);
            return json(['data' => $result]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon编辑刊登完成后的内容;
     * @access public
     * @method get
     * @url /publish/amazon/:id/:type/reedit
     * @return \think\response\Json
     */
    public function reEdit($id, $type)
    {
        try {
            $server = new AmazonPublishService();
            $server->reEdit($id, $type);
            return json(['data' => '刊登记录 ' . $id . ' 执行成功，请重新编辑']);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon编辑刊登异常导出;
     * @access public
     * @method get
     * @url /publish/amazon/error-export
     * @return \think\response\Json
     */
    public function errorExport(Request $request)
    {
        try {
            $ids = $request->get('ids');
            if (empty($ids)) {
                if ($this->lang == 'zh') {
                    return json(['message' => '导出参数ids为空'], 400);
                } else {
                    return json(['message' => 'Params Error.'], 400);
                }
            }
            $server = new AmazonPublishService();
            $result = $server->errorExport($ids);
            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazonn添加UPC参数;
     * @access public
     * @method post
     * @url /publish/amazon/add-upc-params
     * @return \think\response\Json
     */
    public function addUpcParam(Request $request)
    {
        try {
            $data = $request->post();
            $result = $this->validate($data, [
                'code' => 'require|length:1',
                'header' => 'require|length:5',
            ]);
            if ($result !== true) {
                throw new Exception($result);
            }
            $upcpmodel = new AmazonUpcParam();
            $upcpmodel->addParmas($data);
            if ($this->lang == 'zh') {
                return json(['message' => '添加成功.']);
            } else {
                return json(['message' => 'Success.']);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazonn批量复制;
     * @access public
     * @method post
     * @url /publish/amazon/batch-copy
     * @return \think\response\Json
     */
    public function batchCopy(Request $request)
    {
        try {
            $data = $request->post();
            $result = $this->validate($data, [
                'account_ids|帐号ID' => 'require|min:1',
                'ids|刊登记录ID' => 'require|min:1'
            ]);
            if ($result !== true) {
                throw new Exception($result);
            }
            $service = new AmazonPublishService();
            if ($service->batchCopy($data)) {
                if ($this->lang == 'zh') {
                    return json(['message' => '复制成功.']);
                } else {
                    return json(['message' => 'Success.']);
                }
            } else {
                if ($this->lang == 'zh') {
                    return json(['message' => '复制失败'], 400);
                } else {
                    return json(['message' => 'System Error'], 400);
                }
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon批量跟卖;
     * @access public
     * @method post
     * @url /publish/amazon/batch-heel-sale
     * @return \think\response\Json
     */
    public function batchHeelSale(Request $request)
    {
        try {

            $data = $request->post('data');

            $lang = $this->lang;
            if (empty($data)) {
                $message = $lang == 'zh' ? '你提交的数据为空' : 'The data you submitted is empty.';
                return json(['message' => $message], 400);
            }

            //创建id
            $user = CommonService::getUserInfo($request);
            $uid = $user['user_id'];

            $arrReturn['data'] = [];
            $data = \GuzzleHttp\json_decode($data, true);

            $service = new AmazonHeelSaleLogService();

            //定时上架id
            $rule_id = $request->post('rule_id', '0');

            $rules = $rule_id ? $service->upLowerRuleInfo($rule_id) : [];

            if ($rule_id && empty($rules)) {
                $message = $lang == 'zh' ? '请选择有效的定时规则' : 'Please select a valid timing rule';
                return json(['message' => $message], 400);
            }

            foreach ($data as $val) {
                $val['create_id'] = $uid;

                //asin,price,quantity 不能为空
                if($lang == 'zh') {
                    $result = $this->validate($val, [
                        'sku|平台sku' => 'require',
                        'account_id|账号简称' => 'require',
                        'asin|ASIN码' => 'require',
                        'price|销售价格' => 'require',
                        'quantity|库存' => 'require',
                    ]);
                }else{
                    $result = $this->validate($val, [
                        'sku|Platform SKU' => 'require',
                        'account_id|Account abbreviation' => 'require',
                        'asin|ASIN code' => 'require',
                        'price|Selling price' => 'require',
                        'quantity|Stock' => 'require',
                    ]);
                }


                if ($result !== true) {
                    throw  new Exception($result);
                }

                $result = $service->bathHeelSale($val, $rule_id, $rules, $lang);

                if ($result) {
                    array_push($arrReturn['data'], $result);
                }
            }

            return json($arrReturn, 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon跟卖列表
     * @access public
     * @method get
     * @url /publish/amazon/heel-sale-list
     * @return \think\response\Json
     */
    public function heelSaleList(Request $request)
    {
        try {
            $post = $request->get();
            $page = $this->request->param('page', 1);
            $pageSize = $this->request->param('pageSize', 30);

            $response = (new AmazonHeelSaleLogService)->heelSaleList($post, $page, $pageSize);
            return json($response);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage(), 'data' => []], 400);
        }
    }


    /**
     * @title amazon定时上下架添加规则
     * @access public
     * @method post
     * @url /publish/amazon/add-up-lower-rule
     * @return \think\response\Json
     */
    public function addUpLowerRule(Request $request)
    {
        try {
            $data = $request->post();
            $lang = $this->lang;
            if (empty($data)) {
                $message = $lang == 'zh' ? '你提交的数据为空' : 'The data you submitted is empty.';
                return json(['message' => $message], 400);
            }

            //创建id
            $user = CommonService::getUserInfo($request);
            $uid = $user['user_id'];

            $valiDate = [
                'rule_name|规则名称' => 'require',
                'start_time|开始时间' => 'require',
                'end_time|截止时间' => 'require',
            ];

            if($lang == 'en'){
                $valiDate = [
                    'rule_name|Rule name' => 'require',
                    'start_time|start time' => 'require',
                    'end_time|Deadline' => 'require',
                ];
            }

            //验证字段
            $result = $this->validate($data, $valiDate);
            if ($result !== true) {
                throw  new Exception($result);
            }

            if ((strtotime($data['end_time']) - strtotime($data['start_time'])) > 3*30 * 24 * 3600) {
                $message = $lang == 'zh' ? '有效时间不能超过3个月' : 'The effective time should not exceed 3 months.';
                return json(['message' => $message], 400);
            }

            $data['create_id'] = $uid;
            $service = new AmazonHeelSaleLogService();
            $result = $service->addUpLowerRule($data, $lang);

            if (empty($result['status'])) {
                return json(['message' => $result['message']]);
            } else {
                return json(['message' => $result['message']], 400);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon定时上下架规则列表
     * @access public
     * @method get
     * @url /publish/amazon/up-lower-rule-list
     * @return \think\response\Json
     */
    public function upLowerRuleList(Request $request)
    {
        try {
            $post = $request->get();
            $page = $this->request->param('page', 1);
            $pageSize = $this->request->param('pageSize', 30);

            $response = (new AmazonHeelSaleLogService)->upLowerRuleList($post, $page, $pageSize);
            return json($response);

        } catch (Exception $e) {
            return json(['message' => $e->getMessage(), 'data' => []], 400);
        }
    }


    /**
     * @title 定时上架规则状态修改
     * @access public
     * @method get
     * @url /publish/amazon/up-lower-rule-status
     * @return \think\response\Json
     */
    public function upLowerRuleStatus(Request $request)
    {
        try {
            $post = $request->get();

            $lang = $this->lang;
            if (!$post['id']) {
                $status = $lang == 'zh' ? '参数id为空' : 'The parameter ID is empty';
                throw new Exception($status);
            }

            //状态必须为0:开启;1:关闭
            if (isset($post['status']) && !in_array($post['status'], [0, 1])) {
                $status = $lang == 'zh' ? '参数status错误' : 'Parameter status error';
                throw new Exception($status);
            }

            $response = (new AmazonHeelSaleLogService)->upLowerRuleStatus($post, $lang);
            return json($response);

        } catch (Exception $e) {
            return json(['message' => $e->getMessage(), 'data' => []], 400);
        }
    }


    /**
     * @title 定时上架规则删除
     * @access public
     * @method post
     * @url /publish/amazon/up-lower-rule-delete
     * @return \think\response\Json
     */
    public function upLowerRuleDelete(Request $request)
    {
        try {
            $ids = $request->post('ids');

            $lang = $this->lang;
            if (!$ids) {
                $status = $lang == 'zh' ? '参数ids为空' : 'The parameter IDS is empty';
                throw new Exception($status);
            }

            $response = (new AmazonHeelSaleLogService)->upLowerRuleDelete($ids, $lang);
            if(empty($response['status'])) {
                return json(['message' => $response['message']]);
            }

            return json(['message' => $response['message']], 400);

        } catch (Exception $e) {
            return json(['message' => $e->getMessage(), 'data' => []], 400);
        }
    }


    /**
     * @title 定时上架规则详情
     * @access public
     * @method get
     * @url /publish/amazon/up-lower-rule-detail
     * @return \think\response\Json
     */
    public function upLowerRuleDetail(Request $request)
    {
        try {
            $id = $request->get('id');

            $lang = $this->lang;
            if (!$id) {
                $status = $lang == 'zh' ? '参数id为空' : 'The parameter ID is empty';
                throw new Exception($status);
            }

            $response = (new AmazonHeelSaleLogService)->upLowerRuleDetail($id);
            if ($response) {
                return json($response);
            }

            return json(['message' => $lang == 'zh' ? 'No data' : '']);

        } catch (Exception $e) {
            return json(['message' => $e->getMessage(), 'data' => []], 400);
        }
    }


    /**
     * @title amazon定时上架规则编辑
     * @access public
     * @method post
     * @url /publish/amazon/up-lower-rule-edit
     * @return \think\response\Json
     */
    public function upLowerRuleEdit(Request $request)
    {
        try {

            $data = $request->post();

            $lang = $this->lang;
            if (empty($data)) {
                return json(['message' => $lang == 'zh' ? '你提交的数据为空' : 'The data you submitted is empty.'], 400);
            }

            $id = $request->post('id');

            if (!$id) {
                $status = $lang == 'zh' ? '参数id为空' : 'The parameter ID is empty';
                throw new Exception($status);
            }

            //创建id
            $user = CommonService::getUserInfo($request);
            $uid = $user['user_id'];

            //验证字段
            $valiDate = [
                'rule_name|规则名称' => 'require',
                'start_time|开始时间' => 'require',
                'end_time|截止时间' => 'require',
            ];

            if($lang == 'en'){
                $valiDate = [
                    'rule_name|Rule name' => 'require',
                    'start_time|start time' => 'require',
                    'end_time|Deadline' => 'require',
                ];
            }

            $result = $this->validate($data, $valiDate);

            if ($result !== true) {
                throw  new Exception($result);
            }

            if ((strtotime($data['end_time']) - strtotime($data['start_time'])) > 3*30 * 24 * 3600) {
                $message = $lang == 'zh' ? '有效时间不能超过3个月' : 'The effective time should not exceed 3 months.';
                return json(['message' => $message], 400);
            }


            $data['create_id'] = $uid;

            $service = new AmazonHeelSaleLogService();
            $result = $service->addUpLowerRule($data, $lang);

            if (empty($result['status'])) {

                return json(['message' => $result['message']]);
            } else {

                return json(['message' => $result['message']], 400);
            }

        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 定时上下架开启
     * @access public
     * @method post
     * @url /publish/amazon/up-lower-open
     * @return \think\response\Json
     */
    public function upLowerOpen(Request $request)
    {
        try {
            $ids = $request->post('id');

            $lang = $this->lang;
            if (!$ids) {

                $status = $lang == 'zh' ? '参数ids为空' : 'The parameter IDS is empty';
                return json(['message' => '参数ids为空'], 400);
            }

            $rule_id = $request->post('rule_id');

            if (!$rule_id) {

                $status = $lang == 'zh' ? '规则id为空' : 'Rule ID is empty';
                return json(['message' => $status], 400);
            }

            //下架
            $response = (new AmazonHeelSaleLogService)->upLowerOpen($ids, $rule_id, $lang);

            if ($response['status'] == true) {

                $status = $lang == 'zh' ? '开启成功，稍后执行...' : 'Open successfully and execute later.';
                return json(['message' => $status]);
            }

            return json(['message' => $response['message']], 400);

        } catch (Exception $e) {
            return json(['message' => $e->getMessage(), 'data' => []], 400);
        }
    }


    /**
     * @title 关闭定时上下架
     * @access public
     * @method post
     * @url /publish/amazon/up-lower-close
     * @return \think\response\Json
     */
    public function upLowerClose(Request $request)
    {
        try {
            $ids = $request->post('id');

            $lang = $this->lang;
            if (!$ids) {

                $status = $lang == 'zh' ? '参数ids为空' : 'The parameter IDS is empty';
                throw new Exception($status);
            }

            $response = (new AmazonHeelSaleLogService)->upLowerClose($ids, $lang);

            if ($response['status'] == true) {
                return json(['message' => $response['message']]);
            }

            return json(['message' => $response['message']], 400);

        } catch (Exception $e) {
            return json(['message' => $e->getMessage(), 'data' => []], 400);
        }
    }


    /**
     * @title 亚马逊跟卖投诉管理列表
     * @access public
     * @method get
     * @url /publish/amazon/heel-sale-complain
     * @apiFilter app\publish\filter\AmazonHeelSaleComplainFilter
     * @return \think\response\Json
     */
    public function heelSaleComplain(Request $request)
    {
        try {

            $post = $request->get();
            $page = $this->request->param('page', 1);
            $pageSize = $this->request->param('pageSize', 30);

            $response = (new AmazonHeelSaleLogService)->heelSaleComplain($post, $page, $pageSize);
            return json($response);


        } catch (Exception $e) {
            return json(['message' => $e->getMessage(), 'data' => []], 400);
        }
    }


    /**
     * @title 处理跟卖投诉状态
     * @access public
     * @method post
     * @url /publish/amazon/complain-status
     * @return \think\response\Json
     */
    public function complainStatus(Request $request)
    {
        try {

            //1.获取调价值

            $posts = $request->post();

            $lang = $this->lang;
            if (!$posts || !$posts['id']) {

                $status = $lang == 'zh' ? '参数id为空' : 'The parameter ID is empty';
                throw new Exception($status);
            }

            $status = $request->post('status', 0);

            if (!in_array($status, [0, 1])) {

                $status = $lang == 'zh' ? '类型type错误' : 'Type type error';
                throw new Exception($status);
            }

            $user = CommonService::getUserInfo($request);
            $uid = $user['user_id'];

            $response = (new AmazonHeelSaleLogService)->complainStatus($posts, $uid);
            return json($response);

        } catch (Exception $e) {
            return json(['message' => $e->getMessage(), 'data' => []], 400);
        }
    }


    /**
     * @title 删除跟卖投诉
     * @access public
     * @method post
     * @url /publish/amazon/complain-delete
     * @return \think\response\Json
     */
    public function complainDelete(Request $request)
    {
        try {

            $ids = $request->post('ids');

            $lang = $this->lang;
            if (!$ids) {
                $status = $lang == 'zh' ? '参数ids为空' : 'The parameter IDS is empty';
                throw new Exception($status);
            }

            /*  $user = CommonService::getUserInfo($request);
              $uid = $user['user_id'];*/

            $response = (new AmazonHeelSaleLogService)->complainDelete($ids, $lang);

            if(empty($response['status'])) {
                return json(['message' => $response['message']]);
            }

            return json(['message' => $response['message']], 400);

        } catch (Exception $e) {
            return json(['message' => $e->getMessage(), 'data' => []], 400);
        }
    }


    /**
     * @title 抓取asin跟卖
     * @access public
     * @method post
     * @url /publish/amazon/heel-sale-get
     * @return \think\response\Json
     */
    public function heelSaleGet(Request $request)
    {
        try {

            $id = $request->post('id');

            $lang = $this->lang;
            if (!$id) {

                $status = $lang == 'zh' ? '参数id为空' : 'The parameter ID is empty';
                throw new Exception($status);
            }

            $response = (new AmazonHeelSaleLogService)->heelSaleGet($id);
            return json($response);

        } catch (Exception $e) {
            return json(['message' => $e->getMessage(), 'data' => []], 400);
        }
    }


    /**
     * @title ASIN审核
     * @access public
     * @method post
     * @url /publish/amazon/review-asin
     * @return \think\response\Json
     */
    public function reviewAsin(Request $request)
    {
        try {
            $posts = $request->post('data');

            $lang = $this->lang;
            if (!$posts) {

                $status = $lang == 'zh' ? '参数不能为空' : 'Parameters cannot be null';
                throw new Exception($status);
            }

            $posts = json_decode($posts, true);

            $response = (new AmazonHeelSaleLogService)->reviewAsin($posts);
            return json($response);

        } catch (Exception $e) {

            return json(['message' => $e->getMessage(), 'data' => []], 400);
        }
    }


    /**
     * @title 亚马逊批量跟卖修改信息查询
     * @access public
     * @method post
     * @url /publish/amazon/heel-sale-info
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     *
     */
    public function heelSaleInfo(Request $request)
    {
        try {
            $ids = $request->post('ids');

            $lang = $this->lang;
            if(!$ids) {

                $status = $lang == 'zh' ? '参数不能为空' : 'Parameters cannot be null';
                throw new Exception($status);
            }

            $data = (new AmazonHeelSaleLogService())->heelSaleInfo($ids);
            return json($data);

        } catch (Exception $e) {

            return json(['message' => $e->getMessage(), 'data' => []], 400);
        }
    }


    /**
     * @title 亚马逊批量跟卖修改信息提交
     * @access public
     * @method post
     * @url /publish/amazon/heel-sale-batch-edit
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     *
     */
    public function heelSaleBatchEdit(Request $request)
    {

        try {
            //1.获取跟卖数据
            $posts = $request->post('data');

            $lang = $this->lang;
            if(!$posts) {

                $status = $lang == 'zh' ? '参数不能为空' : 'Parameters cannot be null';
                throw new Exception($status);
            }

            $posts = \GuzzleHttp\json_decode($posts,true);

            //2.根据id查询规则是否改变.如果规则改变,则删除之前此条跟卖记录对应的规则数据.同时添加该条跟卖规则;
            $result = (new AmazonHeelSaleLogService())->heelSaleBatchEdit($posts);

            if($lang == 'zh') {
                return json(['message' => '更新成功'.$result.'条数据...']);
            }

            if($lang == 'en') {
                return json(['message' => 'Update success'.$result.'Bar data...']);
            }

        } catch (Exception $e) {

            return json(['message' => $e->getMessage(), 'data' => [], 400]);
        }
    }


    /**
     * @title 亚马逊跟卖批量删除
     * @access public
     * @method post
     * @url /publish/amazon/heel-sale-bath-del
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     *
     */
    public function heelSaleBatchDel(Request $request)
    {

        try {
            $ids = $request->post('ids');

            $lang = $this->lang;
            if(!$ids) {

                $status = $lang == 'zh' ? '参数不能为空' : 'Parameters cannot be null';
                throw new Exception($status);
            }

            $data = (new AmazonHeelSaleLogService())->heelSaleBatchDel($ids);

            if($lang == 'zh') {
                return json(['message' => '批量删除成功']);
            }

            if($lang == 'en') {
               return json(['message' => 'Successful batch deletion']);
            }

        } catch (Exception $e) {

            return json(['message' => $e->getMessage(), 'data' => []], 400);
        }
    }
}