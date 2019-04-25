<?php
namespace app\publish\controller;

use think\Request;
use think\Exception;
use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\publish\service\AmazonXsdTemplate;
use app\common\service\Common as CommonService;
use app\publish\service\AmazonElement;

/**
 * @module 刊登系统
 * @title Amazon模板设置
 * @url /amazon-template
 * Class AmazonTemplate
 * @author zhangdongdong
 * @package app\publish\controller
 */
class AmazonTemplate extends Base
{

    protected $lang = 'zh';

    private $service = null;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->service = new AmazonXsdTemplate();

        //erp的语言设置，默认是中文，目前可能的值是en:英文；
        $this->lang = $request->header('Lang', 'zh');
    }


    /**
     * @title amazon产品模板列表
     * @access public
     * @method GET
     * @url /amazon-template/product
     * @param array $request
     * @apiRelate app\publish\controller\AmazonTemplate::creator
     * @output think\Response
     */
    public function product(Request $request)
    {
        try {
            $param = $request->param();
            $page = $param['page'] ?? 1;
            $pageSize = $param['pageSize'] ?? 20;

            $param['type'] = 2;
            $lists = $this->service->getList($param, $page, $pageSize);
            return json($lists);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon分类模板列表
     * @access public
     * @method GET
     * @url /amazon-template/category
     * @param array $request
     * @apiRelate app\publish\controller\AmazonTemplate::creator
     * @output think\Response
     */
    public function category(Request $request)
    {
        try {
            $param = $request->param();
            $page = $param['page'] ?? 1;
            $pageSize = $param['pageSize'] ?? 20;

            $param['type'] = 1;
            $lists = $this->service->getList($param, $page, $pageSize);
            return json($lists);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon模板创建人和站点
     * @access public
     * @method GET
     * @url /amazon-template/:type(\d+)/creator
     * @param int $type
     * @output think\Response
     */
    public function creator($type)
    {
        if (!in_array($type, [1, 2])) {
            if ($this->lang == 'zh') {
                return json(['message' => '参数type错误'], 400);
            } else {
                return json(['message' => 'Params Error'], 400);
            }
        }
        $result = $this->service->getCreator($type);
        return json($result);
    }


    /**
     * @title amazon读取模板详情
     * @access public
     * @method GET
     * @url /amazon-template/:id(\d+)
     * @param int $id
     * @output think\Response
     */
    public function read($id)
    {
        $reslut = $this->service->getInfo($id);
        if ($reslut === false) {
            return json(['message' => $this->service->getError()], 400);
        }
        return json($reslut);
    }


    /**
     * @title amazon编辑模板详情
     * @access public
     * @method GET
     * @url /amazon-template/:id(\d+)/edit
     * @param int $id
     * @output think\Response
     */
    public function edit($id)
    {
        $reslut = $this->service->getInfo($id);
        if ($reslut === false) {
            return json(['message' => $this->service->getError()], 400);
        }
        return json($reslut);
    }


    /**
     * @title amazon启用停用模板
     * @access public
     * @method GET
     * @url /amazon-template/status/:id(\d+)/:enable(\d+)
     * @param int $id
     * @param int $enable
     * @output think\Response
     */
    public function status($id, $enable)
    {
        if (!in_array($enable, [0, 1])) {
            if ($this->lang == 'zh') {
                return json(['message' => '参数enable只能0或1'], 400);
            } else {
                return json(['message' => 'Params Error'], 400);
            }
        }
        $reslut = $this->service->status($id, $enable);
        if ($reslut === false) {
            return json(['message' => $this->service->getError()], 400);
        }
        if ($this->lang == 'zh') {
            return json(['message' => ($enable ? '启用' : '禁用') . '成功']);
        } else {
            return json(['message' => ($enable ? 'Enabled' : 'Disabled') . ' success']);
        }
    }


    /**
     * @title amazon更新模板详情
     * @access public
     * @method PUT
     * @url /amazon-template
     * @param array $request
     * @output think\Response
     */
    public function update(Request $request)
    {
        $data = $request->put();
        try {
            $result = $this->validate($data, [
                'id|数据ID' => 'require|number',
                'name|模板名称' => 'require|unique:amazon_xsd_template,name|length:1,50',
                'class_name|分类名称' => 'require|length:1,100',
                'type|模板类型' => 'require|in:1,2',
                'site|站点ID' => 'require|number',
                'default|是否默认' => 'require|in:0,1',
                'status|模板状态' => 'require|in:0,1',
                'detail|详情参数' => 'require',
            ]);
            if ($result !== true) {
                if ($this->lang == 'zh') {
                    return json(['message' => $result], 400);
                } else {
                    return json(['message' => 'Params Error'], 400);
                }
            }
            $user = CommonService::getUserInfo($request);
            $uid = $user['user_id'];
            $result = $this->service->update($data, $uid);
            if ($result === false) {
                if ($this->lang == 'zh') {
                    return json(['message' => $this->service->getError()], 400);
                } else {
                    return json(['message' => 'System Error'], 400);
                }
            }
            if ($this->lang == 'zh') {
                return json(['message' => '更新成功', 'data' => $result]);
            } else {
                return json(['message' => 'Updated success', 'data' => $result]);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon新增模板
     * @access public
     * @method POST
     * @url /amazon-template
     * @param array $request
     * @output think\Response
     */
    public function save(Request $request)
    {
        $data = $request->post();
        try {
            $result = $this->validate($data, [
                'name|模板名称' => 'require|unique:amazon_xsd_template,name|length:1,50',
                'class_name|分类名称' => 'length:0,100',
                'type|模板类型' => 'require|in:1,2',
                'site|站点ID' => 'require|number',
                'default|是否默认' => 'require|in:0,1',
                'status|模板状态' => 'require|in:0,1',
                'detail|详情参数' => 'require',
            ]);
            if ($result !== true) {
                if ($this->lang == 'zh') {
                    return json(['message' => $result], 400);
                } else {
                    return json(['message' => 'Params Error'], 400);
                }
            }
            $user = CommonService::getUserInfo($request);
            $uid = $user['user_id'];
            $result = $this->service->save($data, $uid);
            if ($result === false) {
                if ($this->lang == 'zh') {
                    return json(['message' => $this->service->getError()], 400);
                } else {
                    return json(['message' => 'System Error'], 400);
                }
            }
            if ($this->lang == 'zh') {
                return json(['message' => '保存成功', 'data' => $result]);
            } else {
                return json(['message' => 'Saved success', 'data' => $result]);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon产品元素列表
     * @access public
     * @method GET
     * @url /amazon-template/productbase
     * @param array $request
     * @output think\Response
     */
    public function productbase()
    {
        $list = (new AmazonElement())->getproductBase();
        return json(['list' => $list]);
    }


    /**
     * @title amazon分类列表
     * @access public
     * @method GET
     * @url /amazon-template/categorybase/:site(\d+)
     * @param int $site
     * @output think\Response
     */
    public function categorybase($site)
    {
        $list = (new AmazonElement())->getCategoryBase($site);
        return json(['list' => $list]);
    }


    /**
     * @title amazon分类下所属元素列表
     * @access public
     * @method GET
     * @url /amazon-template/categoryele
     * @param array $request
     * @output think\Response
     */
    public function categoryRelation(Request $request)
    {
        $param = $request->get();
        $result = $this->validate($param, [
            'type_id|分类类别ID' => 'require|number',
            'child_type_id|子分类类别ID' => 'number',
        ]);
        if ($result !== true) {
            if ($this->lang == 'zh') {
                return json(['message' => $result], 400);
            } else {
                return json(['message' => 'Params Error'], 400);
            }
        }

        $result = (new AmazonElement())->getCategoryRelation($param);
        return json($result);
    }


    /**
     * @title amazon批量删除模板
     * @access public
     * @method GET
     * @url /amazon-template/del
     * @param array $request
     * @output think\Response
     */
    public function delete(Request $request)
    {
        $ids = $request->get('ids');
        try {
            $idArr = explode(',', $ids);
            if (empty($ids) || empty($idArr)) {
                if ($this->lang == 'zh') {
                    return json(['message' => 'ids参数为空'], 400);
                } else {
                    return json(['message' => 'Params Error'], 400);
                }
            }
            $reslut = $this->service->delete($idArr);
            if ($reslut === false) {
                if ($this->lang == 'zh') {
                    return json(['message' => $this->service->getError()], 400);
                } else {
                    return json(['message' => 'System error'], 400);
                }
            }
            if ($this->lang == 'zh') {
                return json(['message' => '删除成功']);
            } else {
                return json(['message' => 'Deleted success']);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title amazon更新数据；
     * @access public
     * @method GET
     * @url /amazon-template/update-old-data
     * @param array $request
     * @output think\Response
     */
    public function updateOldData()
    {
        $this->service->updateOldDataAll();
    }

}