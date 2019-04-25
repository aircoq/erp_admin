<?php
namespace app\publish\controller;

use app\publish\service\AmazonShippingGroupNameService;
use think\Request;
use think\Exception;
use app\common\controller\Base;

/**
 * @module 刊登系统
 * @title Amazon刊登运费模板
 * @url /publish/amazon-shipping-group-name
 * Class AmazonPublish
 * @package app\publish\controller
 */
class AmazonShippingGroupName extends Base
{

    protected $lang = 'zh';

    public $service = null;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        //erp的语言设置，默认是中文，目前可能的值是en:英文；
        $this->lang = $request->header('Lang', 'zh');

        $this->service = new AmazonShippingGroupNameService();
        $this->service->setLang($this->lang);
    }



    /**
     * @title amazon运费模板名列表
     * @access public
     * @method get
     * @author 冬
     * @url /publish/amazon-shipping-group-name
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        try {
            $list = $this->service->lists($request);
            return json($list);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 帐号运费模板名
     * @access public
     * @method get
     * @author 冬
     * @url /publish/amazon-shipping-group-name/:account_id/read
     * @return \think\response\Json
     */
    public function read($account_id)
    {
        try {
            $list = $this->service->read($account_id);
            return json(['data' => $list]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 添加模板名
     * @access public
     * @method post
     * @author 冬
     * @url /publish/amazon-shipping-group-name
     * @return \think\response\Json
     */
    public function add(Request $request)
    {
        try {
            $data = $request->post();
            $result = $this->validate($data, [
                'account_id|帐号ID' => 'require|number',
                'group_name|模板名称' => 'require|length:1,80'
            ]);
            if ($result !== true) {
                if ($this->lang == 'zh') {
                    throw new Exception($result);
                } else {
                    throw new Exception('Params Erorr');
                }
            }
            $data = $this->service->edit($data);
            return json(['message' => '添加成功', 'data' => $data]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 修改模板名
     * @access public
     * @method put
     * @author 冬
     * @url /publish/amazon-shipping-group-name
     * @return \think\response\Json
     */
    public function update(Request $request)
    {
        try {
            $data = $request->put();
            $result = $this->validate($data, [
                'id|帐号ID' => 'require|number',
                'account_id|帐号ID' => 'require|number',
                'group_name|模板名称' => 'require|length:1,80'
            ]);
            if ($result !== true) {
                if ($this->lang == 'zh') {
                    throw new Exception($result);
                } else {
                    throw new Exception('Params Erorr');
                }
            }
            $data = $this->service->edit($data);
            return json(['message' => '修改成功', 'data' => $data]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 删除模板名
     * @access public
     * @method delete
     * @author 冬
     * @url /publish/amazon-shipping-group-name
     * @return \think\response\Json
     */
    public function delete(Request $request)
    {
        try {
            $data = $request->delete();
            $result = $this->validate($data, [
                'id|模板ID' => 'require|number'
            ]);
            if ($result !== true) {
                if ($this->lang == 'zh') {
                    throw new Exception($result);
                } else {
                    throw new Exception('Params Erorr');
                }
            }
            $this->service->delete($data['id']);
            return json(['message' => '删除成功']);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }
}