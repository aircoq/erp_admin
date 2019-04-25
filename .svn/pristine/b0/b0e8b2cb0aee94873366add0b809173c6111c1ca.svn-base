<?php
namespace app\publish\controller;

use app\common\exception\JsonErrorException;
use think\Request;
use think\Response;
use think\Exception;
use think\Validate;
use app\publish\service\AmazonProductHelp;
use app\common\controller\Base;

/**
 * @module 刊登系统
 * @title Amazon产品模板线上导出
 * @author fuyifa
 * @url /publish/amazon-product-export
 * Class AmazonProductExport
 * @package app\publish\controller
 */
class AmazonProductExport extends Base
{
    const ruleCreate = [
        'name'=>'require',
        'spu' => 'require',
        'goods_id' => 'require|integer',
    ];

    const ruleModify = [
        'name'=>'require',
        'attributes_images'=>'require',
        'spu' => 'require',
        'goods_id' => 'require|integer',
    ];

    private $service;

    public function __construct(Request $request = null)
    {
        $this->service = new AmazonProductHelp();
        parent::__construct($request);
    }

    /**
     * @title 导出产品列表
     * @method get
     * @url /publish/amazon-product-export
     * @param  \think\Request $request
     * @apiParam name:page type:int desc:页码,默认1
     * @apiParam name:pageSize type:int desc:每页数据,默认50
     * @apiParam name:sku_search type:array desc:按SKU搜索[sku_type,sku_value],eg.,[1,"D12345"]
     * @apiParam name:account_id type:int desc:帐号ID
     * @apiParam name:status type:int desc:状态
     * @apiParam name:create_user_id type:int desc:创建人ID
     * @apiParam name:create_time type:array desc:创建时间["2017-11-03&nbsp;21:53:39","2017-11-08&nbsp;21:53:39"]
     * @apiReturn count:结果集总数
     * @apiReturn data:结果集[Array]
     * @apiReturn data['id']:列表产品ID
     * @apiReturn data['spu']:商品SPU
     * @apiReturn data['goods_id']:商品ID
     * @apiReturn data['name']:商品标题
     * @apiReturn data['bullet_point']:商品卖点[{"0":"卖点1","1":"卖点2"}]
     * @apiReturn data['search_terms']:搜索关系字[{"0":"关系词1","1":"关系词2"}]
     * @apiReturn data['attributes_images']:商品属性及图片
     * @apiReturn data['description']:商品描述
     * @apiReturn data['status']:状态[0-未导出；1-已导出；2-已删除]
     * @apiReturn data['create_user_id']:创建者id
     * @apiReturn data['create_time']:创建时间
     * @apiReturn data['update_user_id']:更新者id
     * @apiReturn data['update_time']:更新时间
     * @apiRelate app\index\controller\Department::getUser
     * @apiRelate app\index\controller\AmazonAccount::index
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 50);
            $param = $request->param();
            $count = $this->service->getCount($param);
            if ($count > 0) {
                $lists = $this->service->getList($param, $page,$pageSize);
                $result = [
                    'data' => $lists,
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'count' => $count,
                ];
                return json($result, 200);
            } else {
                $result = [
                    'data' => [],
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'count' => 0,
                ];
                return json($result, 200);
            }
        } catch(Exception $e) {
            return json(['message' => '数据异常'],500);
        }
    }

    /**
     * @title 查看指定产品信息
     * @url :goods_id(\d+)
     * @method get
     * @param  \think\Request $request
     * @apiParam name:goods_id type:int require:true desc:goods_id
     * @apiReturn id:列表产品ID
     * @apiReturn spu:商品SPU
     * @apiReturn goods_id:商品ID
     * @apiReturn name:商品标题
     * @apiReturn bullet_point:商品卖点[{"0":"卖点1","1":"卖点2"}]
     * @apiReturn search_terms:搜索关系字[{"0":"关系词1","1":"关系词2"}]
     * @apiReturn attributes_images:商品属性及图片
     * @apiReturn description:商品描述
     * @apiReturn status:状态[0-未导出；1-已导出；2-已删除]
     * @apiReturn create_user_id:创建者id
     * @apiReturn create_time:创建时间
     * @apiReturn update_user_id:更新者id
     * @apiReturn update_time:更新时间
     * @remark bullet_point, search_terms, images 为json数据格式，其中images原样保存前端过来的值，再将数据表中的值原样返回给前端
     * @return \think\response\Json
     */
    public function view($goods_id)
    {
        if (!is_numeric($goods_id)) {
            return json(['message' => '参数错误'],200);
        }
        try {
            $result = $this->service->getDetail($goods_id);
            if (!empty($result)) {
                return json($result, 200);
            } else {
                return json([], 200);
            }
        } catch(Exception $e) {
            return json(['message' => '获取失败'],500);
        }
    }

    /**
     * @title 修改指定产品的信息
     * @url /publish/amazon-product-export/:goods_id(\d+)
     * @method PUT
     * @param  \think\Request $request
     * @apiParam name:goods_id type:int desc:goods_id
     * @apiParam name:spu type:string desc:商品SPU
     * @apiParam name:goods_id type:int desc:商品ID
     * @apiParam name:name type:string desc:商品标题
     * @apiParam name:bullet_point type:json desc:商品卖点[{"0":"卖点1","1":"卖点2"}]
     * @apiParam name:search_terms type:json desc:搜索关系字[{"0":"关系词1","1":"关系词2"}]
     * @apiParam name:attributes_images type:json desc:商品属性及图片[原样保存前端传过来的值]
     * @apiParam name:description type:string desc:商品描述
     * @remark bullet_point, search_terms, images 为json数据格式，其中images原样保存前端过来的值，再将数据表中的值原样返回给前端
     * @return \think\response\Json
     */
    public function update(Request $request, $goods_id)
    {
        if (!is_numeric($goods_id)) {
            return json(['message' => 'ID参数错误'], 200);
        }
        $validate = new Validate();
        $validate->rule(self::ruleModify);

        $param = $request->param();

        try {
            if(!$validate->check($param)){
                return json_error($validate->getError());
            }

            if ($this->service->update($param,$goods_id)) {
                return json(['message' => '更新成功'], 200);
            } else {
                return json(['message' => '更新失败'], 200);
            }
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 500);
        }
    }

    /**
     * @title 删除指定产品
     * @url /publish/amazon-product-export/:goods_id(\d+)
     * @method delete
     * @param  \think\Request $request
     * @return \think\Response
     * @apiParam name::goods_id type:int desc:goods_id
     * @return \think\response\Json
     */
    public function delete($goods_id)
    {
        if (empty($goods_id)) {
            return json(['messae'=>'请选择产品'],200);
        }
        try {
            $result = $this->service->delete($goods_id);
            if ($result) {
                return json(['message'=>'删除成功']);
            } else {
                return json(['message'=>'删除失败'],200);
            }
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 500);
        }
    }


    /**
     * @title 获取系统中的商品信息
     * @url goods/:goods_id(\d+)
     * @method get
     * @param  \think\Request $request
     * @return \think\Response
     * @apiParam name:goods_id type:int require:true desc:商品ID[商品管理列表的自增ID]
     * @apiReturn spu:商品SPU
     * @apiReturn goods_id:商品ID
     * @apiReturn name:商品标题
     * @apiReturn description:商品描述
     * @apiReturn bullet_point:卖点[json]
     * @apiReturn search_terms:关键词[json]
     * @apiReturn image_host:图片host
     * @apiReturn attributes_images:属性和图片[json]
     * @remark
     * @return \think\response\Json
     */
    public function goods($goods_id)
    {
        if (!is_numeric($goods_id)) {
            return json(['message' => '参数错误'], 200);
        }
        try {
            $goodsinfo = $this->service->getGoodsInfo($goods_id);
            return json($goodsinfo);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 500);
        }
    }

    /**
     * @title 添加系统的产品到导出列表
     * @url /publish/amazon-product-export
     * @method POST
     * @param  \think\Request $request
     * @return \think\Response
     * @apiParam name:spu type:string desc:商品SPU
     * @apiParam name:goods_id type:int desc:商品ID
     * @apiParam name:name type:string desc:商品标题
     * @apiParam name:bullet_point type:json desc:商品卖点[{"0":"卖点1","1":"卖点2"}]
     * @apiParam name:search_terms type:json desc:搜索关系字[{"0":"关系词1","1":"关系词2"}]
     * @apiParam name:attributes_images type:json desc:商品图片[原样保存前端传过来的值]
     * @apiParam name:description type:string desc:商品描述
     * @remark bullet_point, search_terms, attributes_images 为json数据格式
     * @return \think\response\Json
     */
    public function add(Request $request)
    {
        $param = $request->param();

        $validate = new Validate();
        $validate->rule(self::ruleCreate);
        try {
            if (!$validate->check($param)) {
                return json_error($validate->getError());
            }

            $id = $this->service->add($param);
            return json(['message' => '添加到预刊登成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 500);
        }
    }


    /**
     * @title 下载需要导出的产品
     * @url download
     * @method GET
     * @param  \think\Request $request
     * @return \think\Response
     * @apiParam name:account type:array desc:亚马逊帐号[268,"miniuk"]
     * @apiParam name:goods_id type:array desc:商品ID[123,456]
     * @return \think\response\Json
     */
    public function download(Request $request)
    {
        $param = $request->param();
        $result = $this->validate($param, [
            'account'=>'require',
            'goods_id' => 'require',
        ]);
        if ($result !== true) {
            return json_error($result);
        }

        $param["goods_id"] = json_decode($param["goods_id"],true);
        $param["account"] = json_decode($param["account"],true);

        if(!is_array($param["goods_id"]) || empty($param["goods_id"]) || !is_array($param["account"]) || count($param["account"]) < 2)
            return json(['message' => '参数错误'], 200);
        try {
            $this->service->download($param);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 500);
        }
    }

}
