<?php

/**
 * Description of Collect
 * @datetime 2017-4-25  14:32:49
 * @author joy
 */

namespace app\publish\controller;
use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\publish\service\WishHelper;
use think\Exception;
use think\Request;
use app\publish\service\CollectService;
use app\common\service\Common;
/**
 * @module 刊登系统
 * @title 数据采集
 * @author joy
 */
class Collect extends Base
{

    /**
     * @title 速卖通部门所有员工
     * @url /aliexpress-users
     * @method get
     * @author joy
     * @access public
     * @return json
     */
    public function aliexpressUsers()
    {
        try{
            $response = (new WishHelper())->getWishUsers(4);
            return json($response);
        }catch (JsonErrorException $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }

    /**
     * @title 刊登数据采集列表
     * @url /publish-collect-index
     * @method GET
     * @return
     */
    public function index(Request $request)
    {
        $params = $request->param();
        $uid = Common::getUserInfo()['user_id'];
        $params['create_id']=$uid;
        $page = $request->param('page',1);
        $pageSize = $request->param('pageSize',30);
        $response = (new CollectService())->lists($params,$page,$pageSize);
        return json($response);
    }

    /**
     * @title 添加采集
     * @url /publish-collect-add
     * @method post
     * @param Request $request
     * @return \think\response\Json
     * @throws JsonException
     */
    public function add(Request $request)
    {
        try{
            $url = $request->param('url');
            if(empty($url))
            {
                return json_error('采集地址为空，请重新输入');
            }
            $uid = Common::getUserInfo()['user_id'];
            $response = (new CollectService())->common($url,$uid);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }

    /**
     * @title 认领
     * @url /publish-collect-claim
     * @method POST
     * @param Request $request
     * @return \think\response\Json
     * @throws JsonException
     */
    public function claim(Request $request)
    {
        try{
            $params = $request->param();
            $uid = Common::getUserInfo()['user_id'];
            $response = (new CollectService())->claim($params,$uid);
            if($response['result'])
            {
                return json($response);
            }else{
                return json($response,400);
            }
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
    /**
     * @title 绑定本地商品
     * @url /publish-collect-bind-goods
     * @method POST
     * @param Request $request
     * @return \think\response\Json
     * @throws JsonException
     */
    public function bind(Request $request)
    {
        try{
            $id = $request->param('id');
            $goods_id = $request->param('goods_id');
            if(empty($id) || empty($goods_id))
            {
                return json_error('采集商品id和绑定本地商品id都不能为空');
            }
            $response = (new CollectService())->bind($id,$goods_id);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }
    /**
     * @title 刊登采集删除
     * @url /publish-collect-delete
     * @method POST
     * @param Request $request
     * @return \think\response\Json
     * @throws JsonException
     */
    public function delete(Request $request)
    {
        try{
            $id = $request->param('id');
            if(empty($id))
            {
                return json_error('采集商品id不能为空');
            }
            $response = (new CollectService())->delete($id);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }
}
