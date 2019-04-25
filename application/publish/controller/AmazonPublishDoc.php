<?php
namespace app\publish\controller;

use app\common\service\Common;
use app\publish\service\AmazonPublishDocService;
use app\publish\service\CommonService;
use think\Request;
use think\Exception;
use app\common\controller\Base;

/**
 * @module 刊登系统
 * @title Amazon刊登范本
 * @url /publish/amazon/doc
 * Class AmazonPublishDraft
 * @package app\publish\controller
 */
class AmazonPublishDoc extends Base
{

    protected $lang = 'zh';
    public $service = null;
    public function __construct(Request $request) {
        parent::__construct($request);

        //erp的语言设置，默认是中文，目前可能的值是en:英文；
        $this->lang = $request->header('Lang', 'zh');

        if(empty($this->service)) {
            $this->service = new AmazonPublishDocService();
        }
    }


    /**
     * @title amazon范本列表；
     * @access public
     * @method GET
     * @url /publish/amazon/doc
     * @apiRelate app\publish\controller\AmazonPublishDoc::creator
     * @apiRelate app\publish\controller\AmazonPublishDoc::undoc
     //* @apiRelate app\publish\controller\AmazonPublishDraft::save
     //* @apiRelate app\publish\controller\AmazonPublishDraft::update
     //* @apiRelate app\publish\controller\AmazonPublishDraft::delete
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        try {
            $params = $request->get();
            $lists = $this->service->lists($params);
            return json($lists);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }

    }

    /**
     * @title amazon未写范本列表；
     * @access public
     * @method GET
     * @url /publish/amazon/undoc
     * @param Request $request
     * @return \think\response\Json
     */
    public function undoc(Request $request)
    {
        try {
            $params = $request->get();
            $lists = $this->service->unlists($params);
            return json($lists);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title amazon范本创建人；
     * @access public
     * @method GET
     * @url /publish/amazon/doc-creator
     * @param Request $request
     * @return \think\response\Json
     */
    public function creator()
    {
        try {
            $lists = $this->service->creator();
            return json(['data' => $lists]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon范本删除；
     * @access public
     * @method GET
     * @url /publish/amazon/doc-del
     * @param Request $request
     * @return \think\response\Json
     */
    public function delete(Request $request)
    {
        try {
            $ids = $request->get('ids');
            $this->service->del($ids);
            return json(['message' => '删除成功']);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon范本新增编辑基础；
     * @access public
     * @method GET
     * @url /publish/amazon/doc-base-field
     * @param Request $request
     * @return \think\response\Json
     */
    public function baseField(Request $request)
    {
        try {
            $goods_id = $request->get('goods_id', 0);
            if (empty($goods_id)) {
                throw new Exception('请传递商品参数goods_id');
            }
            $result = $this->service->getBaseField($goods_id);
            return json(['data' => $result]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon范本新增；
     * @access public
     * @method GET
     * @url /publish/amazon/doc-site-field
     * @param Request $request
     * @return \think\response\Json
     */
    public function getField(Request $request)
    {
        try {
            $params = $request->get();
            $result = $this->service->getDocField($params);
            return json(['data' => $result]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon范本编辑和复制；
     * @access public
     * @method GET
     * @url /publish/amazon/doc-edit-field
     * @param Request $request
     * @return \think\response\Json
     */
    public function editField(Request $request)
    {
        try {
            $params = $request->get();
            $result = $this->validate($params, [
                'id|范本ID' => 'require|number',
                'copy|复制参数' => 'in:0,1',
            ]);
            if ($result !== true) {
                throw new Exception($result);
            }
            $params['coopy'] = empty($params['coopy']) ? 0 : $params['coopy'];
            $result = $this->service->editDocField($params['id'], $params['coopy']);
            return json(['data' => $result]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon范本保存；
     * @access public
     * @method POST
     * @url /publish/amazon/doc-save
     * @param Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        try {
            $data = $request->post('data');
            $user = Common::getUserInfo($request);
            $uid = $user['user_id'];
            $result = $this->service->save($data, $uid);
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
        }
    }

}