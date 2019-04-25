<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/1/8
 * Time: 10:40
 */

namespace app\publish\controller;

use app\common\model\AccountUserMap;
use app\common\model\joom\JoomShopCategory;
use think\Request;
use app\common\controller\Base;
use app\publish\service\JoomCategoryService;
use app\common\service\Common as CommonService;
use app\publish\service\JoomListingHelper;

/**
 * @module 刊登系统
 * @title Joom分类管理
 * @author zhangdongdong
 * @url /joom-category
 * Class JoomCategory
 * @package app\publish\controller
 */
class JoomCategory extends Base
{

    private $categoryService = null;

    public function __construct()
    {
        parent::__construct();
        $this->categoryService = new JoomCategoryService();
    }

    /**
     * @title 帐号店铺分类列表
     * @method GET
     * @url /joom-category
     * @return \think\Response
     */
    public function index()
    {
        $data = request()->get();
        $result = $this->validate($data, [
            'joom_account_id|帐号ID' => 'number|gt:0',
            'joom_shop_id|店铺ID' => 'number|gt:0',
            'category_id|分类ID' => 'number|gt:0',
        ]);
        if($result !== true) {
            return json(['message' => $result], 400);
        }

        $result = $this->categoryService->lists($data);
        return json($result, 200);
    }


    /**
     * @title 返回帐号店铺分类ID数组
     * @method POST
     * @url /joom-category/getcategory
     * @return \think\Response
     */
    public function getcategory()
    {
        $data = request()->post();
        $result = $this->validate($data, [
            'joom_account_id|帐号ID' => 'number|gt:0',
            'joom_shop_id|店铺ID' => 'number|gt:0',
        ]);
        if($result !== true) {
            return json(['message' => $result], 400);
        }

        $result = $this->categoryService->getcategoryID($data);
        return json(['data' => $result], 200);
    }

    /**
     * @title 拿取Joom帐号
     * @method GET
     * @url /joom-category/accounts
     * @return \think\response
     */
    public function accounts()
    {
        $accountlist = $this->categoryService->accounts(0);
        return json(['data' => $accountlist], 200);
    }

    /**
     * @title 拿取Joom帐号对应的店铺
     * @method GET
     * @url /joom-category/shops
     * @return \think\response
     */
    public function shops()
    {
        $joom_account_id = request()->get('account_id',0);
        $accountlist = $this->categoryService->shops($joom_account_id);
        return json(['data' => $accountlist], 200);
    }

    /**
     * @title 拿取商品分类
     * @method GET
     * @url /joom-category/category
     * @return \think\response
     */
    public function category(Request $request)
    {
        $lang = $request->header('Lang','zh');
        $lists = $this->categoryService->categoryLists($lang);
        return json(['data' => $lists], 200);
    }

    /**
     * @title 设置账号店铺分类；
     * @method POST
     * @url /joom-category
     * @return \think\response
     */
    public function set()
    {
        $data = request()->post();
        $result = $this->validate($data, [
            'joom_account_id|帐号ID' => 'require|number|gt:0',
            'joom_shop_id|店铺ID' => 'require|number|gt:0',
            'category_id|分类ID' => 'require|length:1,1000',
        ]);
        if($result !== true) {
            return json(['message' => $result], 400);
        }

        $data['update'] = $data['update']?? 0;
        //获取操作人信息
        $user = CommonService::getUserInfo(request());
        if (!empty($user)) {
            $data['creator_id'] = $user['user_id'];
        }
        $data['create_time'] = time();
        $result = $this->categoryService->setCategory($data);

        if($result === false) {
            return json(['message' => $this->categoryService->getError()], 400);
        }
        return json(['message' => ($data['update'] == 0 ? '新增成功' : '编辑成功')], 200);
    }

    /**
     * @title 设置账号店铺分类；
     * @method POST
     * @url /joom-category/del
     * @return \think\response
     */
    public function del()
    {
        $ids = request()->post('ids', '');
        if(empty($ids)) {
            return json(['message' => '参数为空，请传ids'], 400);
        }
        $id_arr = array_filter(explode(',', $ids));
        foreach($id_arr as &$val) {
            $val = trim($val);
        }
        try {
            $result = JoomShopCategory::where(['id' => ['in', $id_arr]])->delete();
            return json(['message' => '成功删除'. $result. '条记录'], 200);
        } catch(Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 根据产品ID返回能刊登的店铺；
     * @method GET
     * @url /joom-category/checkshops
     * @return \think\response
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkshops() {
        $data = request()->get();
        $result = $this->validate($data, [
            'goods_id|产品ID' => 'require|number',
            'warehouse_type|仓库类型' => 'number'
        ]);
        if($result !== true) {
            return json(['message' => $result], 400);
        }
        $result = $this->categoryService->checkShops($data);

        if($result === false) {
            return json(['message' => $this->categoryService->getError()], 400);
        }
        return json(['data' => $result], 200);

    }
}