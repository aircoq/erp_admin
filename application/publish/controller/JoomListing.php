<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 18-1-7
 * Time: 上午9:05
 */

namespace app\publish\controller;

use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\publish\service\JoomListingHelper;
use think\Exception;
use think\Request;
use app\publish\queue\JoomQueueJob;
use app\common\service\UniqueQueuer;


/**
 * @module 刊登系统
 * @title JoomListing在售下架列表
 * @author zhangdongdong
 * @url /joomlisting
 * Class Joom
 * @package app\publish\controller
 */
class JoomListing extends Base
{
    public $help = null;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->help = new JoomListingHelper();
    }

    /**
     * @title JoomListing在售下架列表
     * @url /joomlisting
     * @access public
     * @method GET
     * @apiFilter app\publish\filter\JoomFilter
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {

        try {

            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 50);
            $param = $request->param();
            $response = $this->help->getlishList($param, $page, $pageSize);

            if($response === false)
            {
                return json(['message' => $this->help->getError()], 400);
            }else{
                return json($response);
            }
        } catch (Exception $exp) {
            throw new JsonErrorException($exp->getFile() . $exp->getLine() . $exp->getMessage());
        }
    }

    /**
     * @title 获取JoomListing列表里variant的数据；
     * @url /joomlisting/variant
     * @access public
     * @method GET
     * @param Request $request
     * @return \think\response\Json
     */
    public function variant(Request $request) {
        $data = $request->get();
        try {
            $response = $this->help->getVariantList($data);

            if($response === false)
            {
                return json(['message' => $this->help->getError()], 400);
            }else{
                return json($response);
            }
        } catch (Exception $exp) {
            throw new JsonErrorException($exp->getFile() . $exp->getLine() . $exp->getMessage());
        }
    }

    /**
     * @title 获取Joomlisting销售员列表
     * @url /joomlisting/users
     * @access public
     * @method GET
     * @apiFilter app\publish\filter\JoomListingFilter
     * @output think\Response
     */
    public function users() {
        $list =  $this->help->userList();
        $data = [];
        foreach ($list as $k => $v){
            $data[] = ['value' =>$v ['id'],'label' => $v['realname']];
        }
        return json(['data' => $data]);
    }

    /**
     * @title Joomlisting批量同步更新
     * @url /joomlisting/sync
     * @access public
     * @method POST
     * @output think\Response
     */
    public function sync(Request $request)
    {
        $result = $this->help->sync($request);

        if($result === false)
        {
            return json(['message' => $this->help->getError()], 400);
        }else{
            return json($result);
        }
    }

    /**
     * @title 产品上架和批量上架接口
     * @url /joomlisting/enable
     * @access public
     * @method POST
     * @output think\Response
     */
    public function enable(Request $request)
    {
        $result = $this->help->operation($request, 'enable');

        if($result === false)
        {
            return json(['message' => $this->help->getError()], 400);
        }else{
            return json($result);
        }
    }

    /**
     * @title 产品下架和批量下架接口
     * @url /joomlisting/disable
     * @access public
     * @method POST
     * @output think\Response
     */
    public function disable(Request $request)
    {
        $result = $this->help->operation($request, 'disable');

        if($result === false)
        {
            return json(['message' => $this->help->getError()], 400);
        }else{
            return json($result);
        }
    }

    /**
     * @title 变体上架和批量上架接口
     * @url /joomlisting/variantEnable
     * @access public
     * @method POST
     * @output think\Response
     */
    public function variantEnable(Request $request)
    {
        $result = $this->help->variantOperation($request, 'enable');

        if($result === false)
        {
            return json(['message' => $this->help->getError()], 400);
        }else{
            return json($result);
        }
    }

    /**
     * @title 变体下架和批量下架接口
     * @url /joomlisting/variantDisable
     * @access public
     * @method POST
     * @output think\Response
     */
    public function variantDisable(Request $request)
    {
        $result = $this->help->variantOperation($request, 'disable');

        if($result === false)
        {
            return json(['message' => $this->help->getError()], 400);
        }else{
            return json($result);
        }
    }

    /**
     * @title 获取Joomlisting操作日志
     * @url /joomlisting/logs
     * @access public
     * @method GET
     * @output think\Response
     */
    public function logs(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 50);

            //搜索条件
            $param = $request->param();

            if(!isset($param['product_id']))
            {
                return json(['message'=>'产品ID必需'],500);
            }

            $fields = "*";

            $data = $this->help->getLogs($param, $page, $pageSize, $fields);

            return  json($data);

        }catch(Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 获取Joom刊登记录
     * @url /joomlisting/record
     * @access public
     * @method GET
     * @output think\Response
     */
    public function record(Request $request) {

        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 50);
            $param = $request->param();
            $response = $this->help->getRecordList($param, $page, $pageSize);

            if($response === false)
            {
                return json(['message' => $this->help->getError()], 400);
            }else{
                return json($response);
            }
        } catch (Exception $exp) {
            throw new JsonErrorException($exp->getFile() . $exp->getLine() . $exp->getMessage());
        }
    }

    /**
     * @title 删除Joom刊登出错的数据
     * @url /joomlisting/delrecord
     * @access public
     * @method GET
     * @output think\Response
     */
    public function delrecord(Request $request) {

        try {
            $ids = $request->get('ids', '');
            if(empty($ids)) {
                return json(['message' => '请至少选择一条数据进行删除', 400]);
            }

            $response = $this->help->delRecordList($ids);

            if($response === false)
            {
                return json(['message' => $this->help->getError()], 400);
            }else{
                return json($response);
            }
        } catch (Exception $exp) {
            throw new JsonErrorException($exp->getFile() . $exp->getLine() . $exp->getMessage());
        }
    }


    /**
     * @title Joom记录里批量刊登数据
     * @url /joomlisting/publish
     * @access public
     * @method GET
     * @output think\Response
     */
    public function publish(Request $request) {
        $ids = $request->get('ids', '');
        if(empty($ids)) {
            return json(['message' => '请至少选择一条数据进行刊登', 400]);
        }
        $ids = array_filter(explode(',', $ids));
        $uniqueQueuer = new UniqueQueuer(JoomQueueJob::class);
        foreach($ids as $id) {
            $uniqueQueuer->push($id);
        }
        return json(['message' => '提交刊登成功']);
    }
}