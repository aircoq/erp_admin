<?php

namespace app\goods\controller;

use app\common\cache\Cache;
use app\common\controller\Base;
use app\common\model\GoodsPreCategoryMap;
use app\goods\service\GoodsHelp;
use think\Request;
use think\Exception;
use app\common\service\Common;
use app\goods\service\GoodsPreDevService;
use app\common\exception\JsonErrorException;
use think\Db;

/**
 * @module 商品系统
 * @title 产品预开发
 */
class GoodsPreDev extends Base
{
    /**
     * 显示资源列表
     * @title 预开发产品列表
     * @author  ZhaiBin
     * @url /goods-pre-dev
     * @method get
     * @param  Request $request
     * @apiParam name:page type:int desc:页码
     * @apiParam name:pageSize type:int desc:每页数量
     * @apiParam name:process_id type:int desc:进程id（状态id）
     * @apiParam name:create_id type:int desc:申请人id
     * @apiParam name:search_key type:string desc:搜索值（流程编号-code ， 产品名称-title）
     * @apiParam name:search_val type:string desc:搜索内容
     * @apiParam name:create_time_start type:string desc:申请开始时间
     * @apiParam name:create_time_end type:string desc:申请结束时间
     * @apiReturn id:自增长ID
     * @apiReturn code:流程号
     * @apiReturn title:产品标题
     * @apiReturn create_id:申请人id
     * @apiReturn creator:创建人
     * @apiReturn category_id:分类id
     * @apiReturn category:分类描述
     * @apiReturn create_time:创建时间
     * @apiReturn update_time:修改时间
     * @apiReturn operator_id:待操作人id
     * @apiReturn process_id:流程id
     * @apiReturn process:流程描述
     * @apiReturn is_edit:开发者可编辑（未提交审核）[ 1-是 ]
     * @apiReturn is_create_sku:开发者可编辑可添加sku信息（流程审核完毕） [ 1-是 ]
     * @apiReturn is_complete:是否完结 [ 1-是 ]
     * @remark search_key : 搜索值（流程编号-code ， 产品名称-title）<br />
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $params = $request->param();
        array_walk($params, 'trim');
        $page = $request->param('page', 1);
        $pageSize = $request->param('pageSize', 10);
        $service = new GoodsPreDevService();
        $field = 'id, code, title, create_id, category_id, create_time, update_time, operator_id, process_id';
        $where = $service->getWhere($params);
        $count = $service->getCount($where);
        $lists = $service->getList($where, $page, $pageSize, $field, $order = ['id' => 'desc']);
        // $actions  = $service->getProcessBtnById($request->param('process_id', 0), 1);
        return json([
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'data' => $lists,
            // 'actions'  => $actions
        ], 200);
    }


    /**
     * 查看预产品开发
     * @title 查看预产品开发
     * @author tanbin
     * @url /goods-pre-dev/:id
     * @method get
     * @match ['id' => '\d+']
     * @param int $id
     * @remark <h2>参数返回，查看“编辑预产品开发”</h2>
     * @return \think\Response
     */
    public function read($id)
    {
        if (empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        try {
            $service = new GoodsPreDevService();
            $result = $service->getInfo($id);
            return json($result, 200);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
            return json(['message' => $message, 'file' => $ex->getFile(), 'line' => $ex->getLine()], 400);
        }
    }

    /**
     * 保存新建的资源
     * @title 新增预产品开发
     * @author  ZhaiBin
     * @url /goods-pre-dev
     * @method post
     * @remark <h2>参数请参考“更新预产品开发”</h2>
     * @apiRelate app\goods\controller\Unit::dictionary
     * @apiRelate app\goods\controller\Brand::tortDictionary
     * @apiRelate app\goods\controller\Category::index
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params = $request->param();
        $userInfo = Common::getUserInfo($request);
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        try {
            $service = new GoodsPreDevService();
            $result = $service->save($params, $user_id);
            if (param($result, 'status')) {
                if (param($result, 'data')) {
                    //封装返回值
                    $data = $result['data'];
                    $data['creator'] = $userInfo['realname'];
                }
                return json(['message' => '保存成功', 'data' => $data], 200);
            } else {
                return json(['message' => '添加失败'], 400);
            }

        } catch (Exception $ex) {
            $message = $ex->getMessage();
            return json([
                'message' => $message,
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ], 400);
        }
    }

    /**
     * 编辑预产品开发
     * @title 编辑预产品开发
     * @author tanbin
     * @url /goods-pre-dev/:id/edit
     * @method get
     * @match ['id' => '\d+']
     * @param int $id
     * @apiReturn id:预产品ID
     * @apiReturn code:预产品开发流程号
     * @apiReturn category_id:分类id
     * @apiReturn dev_platform:平台code
     * @apiReturn title:产品标题
     * @apiReturn brand_id:品牌id
     * @apiReturn tort_id:侵权风险id
     * @apiReturn lowest_sale_price:最低限价
     * @apiReturn competitor_price:竞争对手售价
     * @apiReturn gross_profit:本平台毛利率
     * @apiReturn transport_property:物流属性值
     * @apiReturn length:长度
     * @apiReturn height:高度
     * @apiReturn width:宽度
     * @apiReturn weight:毛重
     * @apiReturn net_weight:净重
     * @apiReturn is_packing:是否含包装（1含， 0 不含)
     * @apiReturn warehouse_id:仓库id
     * @apiReturn is_multi_warehouse:是否多仓（1 是， 0 不是）
     * @apiReturn unit_id:单位id
     * @apiReturn source_url:来源地址
     * @apiReturn purchase_url:采购地址 ["www.aaa.com","www.bbb.com"]
     * @apiReturn tags:产品标签["1121","CPU"]
     * @apiReturn description:产品描述
     * @apiReturn developer_note:开发备注
     * @apiReturn process_id:流程id
     * @apiReturn create_id:流程创建人id
     * @apiReturn create_time:申请时间
     * @apiReturn update_time:修改时间
     * @apiReturn is_create_sku:是否允许编辑完善sku信息（０－不允许，１－允许）
     * @apiReturn platform_sale:销售平台状态@
     * @platform_sale id:平台id name:平台code title:平台名称 value_id:平台状态值 value:平台状态
     * @apiReturn images:图片@
     * @images id:id path:图片地址
     * @apiReturn properties:物流属性列表@
     * @properties name:属性名称 field:属性字段值 value:属性字段值 enabled:是否选中
     * @return \think\Response
     */
    public function edit($id)
    {
        try {
            $service = new GoodsPreDevService();
            $result = $service->getInfo($id);
            return json($result, 200);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
            return json(['message' => $message], 400);
        }
    }

    /**
     * 保存更新的资源
     * @title 更新预产品开发
     * @author tanbin
     * @method put
     * @url /goods-pre-dev/:id
     * @match ['id' => '\d+']
     * @param  \think\Request $request
     * @param  int $id
     * @apiParam name:category_id type:int require:1 desc:分类id
     * @apiParam name:dev_platform type:string require:1 desc:平台code（ebay、wish..）
     * @apiParam name:brand_id type:int require:1 desc:品牌id
     * @apiParam name:is_impower type:int require:1 desc:是否授权 （1-是 0-否）
     * @apiParam name:title type:string require:1 desc:产品名称
     * @apiParam name:tort_id type:int desc:侵权风险id
     * @apiParam name:purchase_price type:float require:1 desc:采购价
     * @apiParam name:advice_price type:float require:1 desc:建议售价
     * @apiParam name:lowest_sale_price type:float require:1 desc:最低限价
     * @apiParam name:competitor_price type:float desc:竞争对手售价
     * @apiParam name:gross_profit type:float require:1 desc:本平台毛利率
     * @apiParam name:weight type:int require:1 desc:产品毛重
     * @apiParam name:net_weight type:int require:1 desc:产品净重
     * @apiParam name:height type:int require:1 desc:高度
     * @apiParam name:length type:int require:1 desc:长度
     * @apiParam name:width type:int require:1 desc:宽度
     * @apiParam name:volume_weight type:int desc:体积重
     * @apiParam name:is_packing type:int require:1 desc:是否含包装（1含， 0 不含)
     * @apiParam name:packing_id type:int desc:前置包装材料id
     * @apiParam name:packing_back_id type:int desc:后置包装材料id
     * @apiParam name:unit_id type:int require:1 desc:单位id
     * @apiParam name:warehouse_id type:int require:1 desc:仓库id
     * @apiParam name:is_multi_warehouse type:int desc:是否多仓（1 是， 0 不是）
     * @apiParam name:is_sampling type:int desc:是否取样（1 是， 0 不是）
     * @apiParam name:source_url type:string desc:平台连接
     * @apiParam name:purchase_url type:json desc:采购链接["www.aaa.com","www.bbb.com"]
     * @apiParam name:thumb type:string desc:图片流
     * @apiParam name:tags type:json desc:标签["出口贸易","国外进口"]
     * @apiParam name:description type:string require:1 desc:产品描述
     * @apiParam name:developer_note type:string desc:开发备注
     * @apiParam name:properties type:json require:1 desc:多维数组-产品物流属性
     * @apiParam name:properties.field type:string require:1 desc:物流属性字段 [ field ]
     * @apiParam name:properties.value type:int require:1 desc:物流属性字段值[ value ]
     * @apiParam name:platform_sale type:json require:1 desc:多维数组-销售平台状态
     * @apiParam name:platform_sale.id type:int require:1 desc:平台id
     * @apiParam name:platform_sale.name type:string require:1 desc:平台code
     * @apiParam name:platform_sale.title type:string require:1 desc:平台名称
     * @apiParam name:platform_sale.value_id type:int require:1 desc:平台销售状态id
     * @apiParam name:platform_sale.value type:string require:1 desc:平台销售状态值
     * @apiParam name:audit type:int desc:是否提交审核（保存：0，提交审核：1）
     *
     * @apiParam name:create_sku type:int desc:是否添加附加sku信息（否：0 ， 是：1）
     * @apiParam name:attributes type:json desc:多维数组-属性
     * @apiParam name:attributes.type type:int require:1 desc:类型
     * @apiParam name:attributes.attribute_id type:int require:1 desc:属性id
     * @apiParam name:attributes.attribute_value type:json require:1 desc:多维数组-子属性值
     * @apiParam name:attributes.attribute_value.id type:int require:1 desc:子属性值id
     * @apiParam name:attributes.attribute_value.value type:int require:1 desc:子属性值
     * @apiParam name:skus type:json desc:多维数组-sku列表
     * @apiParam name:skus.action type:string require:1 desc:操作类型 add-添加
     * @apiParam name:skus.sku type:string desc:sku
     * @apiParam name:skus.alias_sku type:json desc:关联sku
     * @apiParam name:skus.name type:string require:1 desc:sku名称
     * @apiParam name:skus.retail_price type:num require:1 desc:销售价
     * @apiParam name:skus.cost_price type:num require:1 desc:采购价
     * @apiParam name:skus.weight type:num desc:毛重
     * #@apiParam name:skus.net_weight type:num desc:净重
     * #@apiParam name:skus.length type:num desc:长
     * #@apiParam name:skus.width type:num desc:宽
     * #@apiParam name:skus.height type:num desc:高
     * @apiParam name:skus.attributes type:json desc:多维数组-属性值
     * @apiParam name:skus.attributes.attribute_id type:int require:1 desc:属性值id
     * @apiParam name:skus.attributes.attribute_value type:int require:1 desc:属性值
     * @apiParam name:supplier_id type:int desc:供应商id
     * @apiParam name:is_photo type:int desc:是否拍照（1-是  0-否）
     * @apiParam name:photo_remark type:string desc:拍照要求
     * @apiParam name:undisposed_img_url type:string desc:未处理图片路径
     * @apiParam name:img_requirement type:json desc:多维数组-修图要求
     * @remark attributess属性格式： [{"type":0,"attribute_id":14,"attribute_value":[{"id":212,"value":"白色|White"}]}] <br />
     * @remark skus属性格式： [{"action":"add","sku":"","id":0,"alias_sku":[],"name":"baiseXXXX","retail_price":"2","cost_price":"2","weight":"2","attributes":[{"attribute_id":14,"value_id":212}]}] <br />
     * @apiReturn message:更新成功
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $params = $request->param();
        if (empty($id) || empty($params)) {
            return json(['message' => '缺少Id或者参数不能为空'], 400);
        }
        $userInfo = Common::getUserInfo($request);
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];
        try {
            $params['id'] = $id;
            $service = new GoodsPreDevService();
            $service->save($params, $user_id);
            return json(['message' => '更新成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * 获取开发产品日志信息
     * @title 查看预产品开发日志
     * @author  ZhaiBin
     * @url /goods-pre-dev/log/:id(\d+)
     * @method get
     * @param int $id
     * @apiReturn goods_id:预产品id
     * @apiReturn type:类型（1-预产品开发日志）
     * @apiReturn remark:操作备注
     * @apiReturn create_time:操作时间
     * @apiReturn operator:操作人
     * @apiReturn process:操作进程
     * @return \think\Response
     */
    public function getLog($id)
    {
        if (empty($id)) {
            return json(['message' => '产品ID不能为空'], 400);
        }
        try {
            $service = new GoodsPreDevService();
            $result = $service->getLog($id);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => '获取失败'], 400);
        }
    }


    /**
     * 获取审核按钮
     * @title 获取预产品开发审核按钮
     * @author tanbin
     * @url /goods-pre-dev/audit
     * @method get
     * @apiParam name:id type:int require:1 desc:自增长id
     * @apiReturn btn_name:按钮值
     * @apiReturn url:进程url
     * @apiReturn remark:备注
     * @apiReturn code:流程操作code
     * @apiReturn execute:execute
     * @return \think\Response
     */
    function getAuditBtn(Request $request)
    {
        $id = $request->get('id', 0);
        if (empty($id)) {
            throw  new JsonErrorException('参数错误');
        }
        $service = new GoodsPreDevService();
        $info = $service->find($id, 'id,process_id');
        if (empty($info)) {
            throw  new JsonErrorException('数据不存在');
        }
        $result = $service->getProcessBtnById($info['process_id']);
        return json($result, 200);
    }

    /**
     * 审核
     * @title 审核预产品开发流程
     * @author tanbin
     * @url /goods-pre-dev/audit
     * @method post
     * @apiParam name:id type:int desc:自增长id
     * @apiParam name:code type:string desc:审核结果code
     * @apiParam name:remark type:string desc:备注
     * @apiReturn message:审核成功
     * @return \think\Response
     */
    function audit(Request $request)
    {
        $id = $request->post('id', 0);
        if (empty($id)) {
            throw  new JsonErrorException('参数错误');
        }
        $params = $request->param();
        $userInfo = Common::getUserInfo($request);
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];
        $service = new GoodsPreDevService();
        try {
            $result = $service->handle($id, $params, $user_id);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage()
            ];
            return json($err, 400);
        }
    }


    /**
     * 获取预产品产品开发流程
     * @title 获取预产品产品开发流程
     * @author tanbin
     * @url /goods-pre-dev/process
     * @method get
     * @apiReturn process_id:进程id
     * @apiReturn title:进程描述值
     * @return \think\Response
     */
    function getProcess()
    {
        $service = new GoodsPreDevService();
        $result = $service->getProcessList();
        $tmp = [];
        $sort = [];
        foreach ($result as $k => $v) {
            if ($v['status'] == 1) {
                $tmp[$k] = $v;
                $sort[$k] = $v['sort'];
            }
        }
        array_multisort($sort, SORT_ASC, $tmp);

        return json($tmp, 200);
    }


    /**
     * 获取申请人数据
     * @title 获取预产品开发申请人
     * @author tanbin
     * @url /goods-pre-dev/proposer
     * @method get
     * @apiReturn id:申请人id
     * @apiReturn user_name:申请人用户名
     * @apiReturn true_name:申请人真实姓名
     * @return \think\Response
     */
    function getProposer()
    {
        $result[] = [
            'id' => 1,
            'user_name' => 'admin',
            'true_name' => '超级管理员',
        ];
        $result[] = [
            'id' => 9999,
            'user_name' => 'test',
            'true_name' => '测试',
        ];

        return json($result, 200);
    }

    /**
     * @title 获取初始渠道列表
     * @method get
     * @url /goods-pre-dev/channel
     * @author starzhan <397041849@qq.com>
     */
    public function getInitChannel()
    {
        $channelList = Cache::store('channel')->getChannel();
        $result = [];
        foreach ($channelList as $v) {
            $row = [];
            $row['id'] = $v['id'];
            $row['title'] = $v['title'];
            $result[] = $row;
        }
        return json($result, 200);
    }

    private function saveGoodsPreCategoryMap($id, $platform, $user_id = 0)
    {
        if (!$id || !$platform) {
            throw new Exception('id或platform不能为空');
        }
        $GoodsCategoryMap = new GoodsPreCategoryMap();
        $aDatas = [];
        foreach ($platform as $v) {
            $aData = [];
            $aData['pre_goods_id'] = $id;
            $aData['channel_id'] = $v['channel_id'];
            $aData['channel_category_id'] = $v['channel_category_id'];
            $aData['site_id'] = $v['site_id'] ?? 0;
            $aData['create_time'] = time();
            $aData['update_time'] = time();
            $aData['operator_id'] = $user_id;
            $aDatas[] = $aData;
        }
        Db::startTrans();
        try {
            $GoodsCategoryMap->where('pre_goods_id', $id)->delete();
            if ($aDatas) {
                $GoodsCategoryMap->allowField(true)->insertAll($aDatas);
            } else {
                throw  new Exception('添加失败,platform为空');
            }
            Db::commit();
            return '';
        } catch (Exception $ex) {
            Db::rollback();
            throw  new Exception($ex->getMessage());
        }
    }

}
