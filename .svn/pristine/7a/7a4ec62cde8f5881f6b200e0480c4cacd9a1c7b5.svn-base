<?php
namespace app\publish\controller;

use think\Request;
use think\Exception;
use app\publish\service\AmazonListingService;
use app\common\controller\Base;

/**
 * @module 刊登系统
 * @title AmazonListing管理
 * @author fuyifa
 * @url /publish/amazon-listing
 * Class AmazonListing
 * @package app\publish\controller
 */
class AmazonListing extends Base
{

    private $lang = 'zh';

    const ruleCreate = [
        'name' => 'require',
        'spu' => 'require',
        'goods_id' => 'require|integer',
    ];

    const ruleModfiy = [
        'name' => 'require',
        'attributes_images' => 'require',
        'spu' => 'require',
        'goods_id' => 'require|integer',
    ];

    const ruleDownload = [
        'account' => 'require',
        'goods_id' => 'require',
    ];

    private $service;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->service = new AmazonListingService();
        $this->lang = $request->header('Lang', 'zh');
        $this->service->setLang($this->lang);
    }

    /**
     * @title listing 列表
     * @method get
     * @param  \think\Request $request
     * @apiFilter app\publish\filter\AmazonFilter
     * @apiFilter app\publish\filter\AmazonDepartmentFilter
     * @apiRelate app\goods\controller\Category::index
     * @apiRelate app\index\controller\DeveloperTeam::category
     * apiReturnLess [联表获取:帐号名-by account_id[ok],已售量-join order table,浏览量,本地状态-join goods table,修改状态-join goods table]
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 50);
            $param = $request->param();

            $result = $this->service->getList($param, $page, $pageSize);
            return json($result, 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title listing导出
     * @method get
     * @url /publish/amazon-listing/export
     * @param  \think\Request $request
     * @apiFilter app\publish\filter\AmazonFilter
     * @apiFilter app\publish\filter\AmazonDepartmentFilter
     * @apiRelate app\goods\controller\Category::index
     * @apiRelate app\index\controller\DeveloperTeam::category
     * apiReturnLess [联表获取:帐号名-by account_id[ok],已售量-join order table,浏览量,本地状态-join goods table,修改状态-join goods table]
     * @return \think\response\Json
     */
    public function export(Request $request)
    {
        try {
            $param = $request->param();
            $result = $this->service->export($param);
            return json($result, 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 查看指定产品信息
     * @url detail/:listing_id(\d+)
     * @method get
     * @param  \think\Request $request
     * @return \think\response\Json
     */
    public function detail($listing_id)
    {
        if (!is_numeric($listing_id)) {
            if ($this->lang == 'zh') {
                return json(['message' => '参数错误'], 400);
            } else {
                return json(['message' => 'Params Error'], 400);
            }
        }
        try {
            $result = $this->service->getDetail($listing_id);
            return json($result, 200);
        } catch (Exception $e) {
            if ($this->lang == 'zh') {
                return json(['message' => '获取失败'], 400);
            } else {
                return json(['message' => 'System Error'], 400);
            }
        }
    }


    /**
     * @title 查看指定产品信息
     * @url /publish/amazon-listing/relation
     * @method get
     * @param  \think\Request $request
     * @return \think\response\Json
     */
    public function relation(Request $request)
    {
        $id = $request->get('id', 0);
        try {
            $this->service->userRelation($id);
            if ($this->lang == 'zh') {
                return json(['message' => '更新成功']);
            } else {
                return json(['message' => 'Updated success']);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 查找asin
     * @url /publish/amazon-listing/asins
     * @method post
     * @param  \think\Request $request
     * @return \think\response\Json
     */
    public function asins(Request $request)
    {
        $asins = $request->post('content', '[]');
        $asins = json_decode($asins, true);
        try {
            if (empty($asins)) {
                if ($this->lang == 'zh') {
                    return json(['message' => '参数错误'], 400);
                } else {
                    return json(['message' => 'System Error'], 400);
                }
            }
            $result = $this->service->asinExist($asins);
            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 批量删除listing
     * @url /publish/amazon-listing/batch
     * @method delete
     * @param  \think\Request $request
     * @return \think\response\Json
     */
    public function batchDel(Request $request)
    {
        try {
            $ids = $request->delete('ids');
            $this->service->batchDel($ids);
            if ($this->lang == 'zh') {
                return json(['message' => '删除listing成功']);
            } else {
                return json(['message' => 'Deleted successfully']);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

}
