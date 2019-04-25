<?php
/**
 * Created by PhpStorm.
 * User: panguofu
 * Date: 2018/10/8
 * Time: 下午5:34
 */

namespace app\publish\controller;

use think\Debug;
use think\Request;
use think\Response;
use think\Cache;
use think\Exception;

use app\common\controller\Base;
use app\publish\validate\WishExpressValidate;
use app\publish\service\WishHelper;
use app\common\exception\JsonErrorException;


/**
 * @module 刊登系统
 * @title wish物流价格模版控制器
 * Class WishExpress
 * @url publish/wish-express/
 * packing app\publish\controller
 */
class WishExpress extends Base
{

    private $_validate;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->_validate = new WishExpressValidate();

    }


    /**
     * @title 添加物流模版
     * @url add-template
     * @access public
     * @method post
     * @input Request  mixed $request
     * @return Response Json
     *
     */
    public function addTemplate(Request $request)
    {

        $post = $request->instance()->param();
        $error = $this->_validate->checkData($post, 'add');
        if ($error) {
            throw new JsonErrorException($error);
        }

        try {
            $user_id = (int)$this->user()->user_id;
            //echo $user_id;
            $response = (new WishHelper())->saveExpressTemplate($post, $user_id);
            return json($response);
        } catch (Exception $exp) {
            throw new JsonErrorException($exp->getMessage());
        }

    }


    /**
     * @title 编辑物流模版
     * @url edit-template
     * @access public
     * @method post
     * @input Request  mixed $request
     * @return Response Json
     *
     */
    public function editTemplate(Request $request)
    {
        $post = $request->instance()->param();
        $error = $this->_validate->checkData($post, 'edit');
        if ($error) {
            throw new JsonErrorException($error);
        }


        try {
            $user_id = (int)$this->user()->user_id;
            $where['id'] = ['=', $post['id']];
            $response = (new WishHelper())->saveExpressTemplate($post, $user_id, $where);
            return json($response);
        } catch (Exception $exp) {
            throw new JsonErrorException($exp->getMessage());
        }


    }


    /**
     * @title 批量删除模版
     * @url batch-delete
     * @access public
     * @method delete
     * @input Request  mixed $request
     * @return Response Json
     *
     */
    public function batchDelTemplate(Request $request)
    {

        $user_id = (int)$this->user()->user_id;
        try {
            $post = $request->instance()->param();

            if (!isset($post['id'])) {
                return json(['message' => '模版id必需项']);
            }

            if (!empty($post['id'])) {
                $ids = explode(',', $post['id']);
            } else {
                $ids = [];
            }

            $where['id'] = ['IN', $ids];

            if ($ids) {
                $len = (new WishHelper())->deleteExpressTemplate($where, $user_id);
                if ($len > 0) {
                    $message = '成功删除[' . $len . ']条记录';
                } else {
                    $message = '删除失败';
                }
            } else {
                $message = '请选择你要删除的物流模版';
            }
            if ($len > 0) {
                return json(['message' => $message]);
            } else {
                return json(['message' => $message], 400);
            }
        } catch (JsonErrorException $exp) {
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }


    }


    /**
     * @title wish物流价格模版列表
     * @url lists
     * @access public
     * @method get
     * @input Request  mixed $request
     * @return Response Json
     *
     */
    public function templateList(Request $request)
    {

        try {
            $request = Request::instance();
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 50);
            $helper = new WishHelper();
            //搜索条件
            $param = $request->param();

            $fields = "*";

            $data = $helper->searchExpressTemplate($param, $page, $pageSize, $fields);

            return json($data);

        } catch (Exception $e) {
            throw new JsonErrorException($e->getFile() . $e->getLine() . $e->getMessage());
            return json(['message' => '数据异常', 'data' => []]);
        }


    }


    /**
     * @title 获取模版详情
     * @url detail
     * @access public
     * @method get
     * @input Request  mixed $request
     * @return Response Json
     *
     */
    public function getDetail(Request $request)
    {

        $id = $request->instance()->get('id');
        if (empty($id)) {
            return json(['message' => '信息id不能为空'], 400);
        }

        $helper = new WishHelper();
        $info = $helper->getExpressTemplate($id);

        if (empty($info)) {
            throw new JsonErrorException("信息不存在");
        }

        return json($info);


    }

}