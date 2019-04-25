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
use app\publish\service\JoomAttrHelp;
use think\Exception;
use think\Request;


/**
 * @module 刊登系统
 * @title Joom商品属性列表
 * @author zhangdongdong
 * @url /joomattr
 * Class Joom
 * @package app\publish\controller
 */
class JoomAttr extends Base
{
    public $help = null;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->help = new JoomAttrHelp();
    }

    /**
     * @title Joom商品颜色列表
     * @url /joomattr/color
     * @access public
     * @method GET
     * @param array $request
     * @output think\Response
     */
    public function index(Request $request)
    {
        try {
            $keyword = $request->get('keyword', '');
            $response = $this->help->getColorList($keyword);

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
     * @title Joom商品尺寸列表
     * @url /joomattr/size
     * @access public
     * @method GET
     * @param array $request
     * @output think\Response
     */
    public function getSize(Request $request)
    {
        try {
            $response = $this->help->getSizeList();

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

}