<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/1/10
 * Time: 17:23
 */

namespace app\publish\service;

use app\api\service\Goods;
use app\common\cache\driver\JoomShop;
use app\common\model\GoodsSku;
use app\common\model\joom\JoomProduct;
use app\common\model\joom\JoomProductInfo;
use app\common\model\joom\JoomVariant;
use app\index\service\MemberShipService;
use think\Db;
use app\common\cache\Cache;
use app\common\model\User;
use app\common\model\joom\JoomProduct as JoomProductModel;
use app\common\model\joom\JoomVariant as JoomVariantModel;
use app\common\model\joom\JoomAccount as JoomAccountModel;
use app\common\model\joom\JoomShop as JoomShopModel;
use app\common\model\joom\JoomActionLog as JoomActionLogModel;
use app\common\model\User as UserModel;
use app\goods\service\GoodsHelp;
use app\common\service\Common as CommonService;
use app\common\model\GoodsSku as GoodsSkuModel;
use app\common\model\Department as DepartmentModel;
use app\common\model\DepartmentUserMap;
use app\index\service\Department as DepartmentService;
use app\common\service\ChannelAccountConst;
use app\common\model\AccountUserMap;
use joom\JoomListingApi;//joomlistin平台api
use app\index\service\DepartmentUserMapService;

/**
 * @module 刊登系统
 * @title JoomListingHelper辅助类
 * @author kevin
 * @Class JoomListingHelper
 * @package app\publish\service
 */
class JoomListingHelper
{

    public $error = '初始值';

    public $user_id = 0;

    public function __construct()
    {

    }

    public function getlishList($param, $page, $pageSize)
    {
        //当只有一个售卖专员，且，售卖专员ID==当前登录ID时，证明是受权限限制制了，只查出他本人的产品就好了；
        $users = $this->userList();
        $user_ids = array_column($users, 'id');

        if(!empty($users)){//获取下属的基本账号
            $account_ids = (new AccountUserMap())->whereIn('user_id',$user_ids)->column('account_id');
            $where['account'] = array('in', $account_ids);
            if( (!empty($param['create_id'])) && (!in_array($param['create_id'],$user_ids)) ){ //如果查询的不是当前权限下的成员
                return [];
            }
        }

        $where = $this->getCondition($param);
        if($where === false) {
            return false;
        }

        if(!empty($param['order']) && !empty($param['sort']) && in_array($param['order'], ['number_sold', 'number_saves', 'date_uploaded', 'update_time']) && in_array($param['sort'], ['desc', 'asc'])) {
            $order = $param['order'];
            $sort = $param['sort'];
        } else {
            $order = 'id';
            $sort = 'asc';
        }
        $count = $this->_getCount($where);
        $lists = $this->_getLists($where, $page, $pageSize, $order, $sort);
        //列表为空，直接返回；
        if(empty($lists)) {
            return [
                'count' => $count,
                'page' => $page,
                'order' => $order,
                'sort' => $sort,
                'pageSize' => $pageSize,
                'data' => [],
            ];
        }

        //组个数据；
        $new_list = [];
        foreach($lists as $data) {
            $data = $data->toArray();
            $new_list[] = $data;
        }
        //帐号店铺
        $accountList = JoomAccountModel::where(['id' => ['in', array_column($new_list, 'account_id')]])->column('account_name', 'id');
        $shopList = JoomShopModel::where(['id' => ['in', array_column($new_list, 'shop_id')]])->column('code,shop_name', 'id');
        //销售专员
        $sellorList = $this->getSellerByAccountId(array_column($new_list, 'shop_id'));
        //审核状态
        $review_lists = ['待审核', '已批准', '被拒绝'];
        $updateArr = ['待同步', '更新成功', '更新失败'];
        //审核被拒绝，更新失败原因
        $ids = array_column($new_list,'id');
        $errMsg = JoomProductInfo::whereIn('id',$ids)->column('review_note,message','id');
        //goods_id转本地spu
        $goodsHelp = new GoodsHelp();
        $localSpuList = $goodsHelp->goodsId2Spu(array_column($new_list, 'goods_id'));

        foreach($new_list as &$val) {
            //帐号店铺
            $val['account_name'] = $accountList[$val['account_id']]?? '';
            $val['shop_name'] = $shopList[$val['shop_id']]['shop_name']?? '';
            $val['shop_code'] = $shopList[$val['shop_id']]['code']?? '';
            $val['sellor'] = '-';
            foreach($sellorList as $seller) {
                if($val['shop_id'] == $seller['id']) {
                    $val['sellor'] = $seller['realname'];
                    break;
                }
            }
            //时间转化
            $val['date_uploaded'] = $val['date_uploaded'] == 0? '' : date('Y-m-d H:i', $val['date_uploaded']);
            $val['create_time'] = $val['create_time'] == 0? '' : date('Y-m-d H:i', $val['create_time']);
            $val['update_time'] = $val['update_time'] == 0? '' : date('Y-m-d H:i', $val['update_time']);

            //sku,spu
            $val['local_parent_sku'] = empty($val['goods_id'])? '' : ($localSpuList[$val['goods_id']]?? '');

            //审核状态
            $val['review_status_text'] = $review_lists[$val['review_status']]?? '';
            $val['lock_update'] = $updateArr[$val['lock_update']]?? '';
            $val['review_note'] = $errMsg[$val['id']]['review_note']??'';
            $val['message'] = $errMsg[$val['id']]['message']??'';

            //退单量，退单率
            $val['number_refunds'] = is_null($val['number_refunds']) ? '-' : $val['number_refunds'];
            $val['refund_rate'] = is_null($val['refund_rate']) ? '-' : (100*$val['refund_rate'].'%');

        }

        return [
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'order' => $order,
            'sort' => $sort,
            'data' => $new_list,
        ];
    }

    private function _getCount($where)
    {
        $allwhere = [];
        foreach($where['product'] as $key => $val) {
            $allwhere['p.'. $key] = $val;
        }
        foreach($where['variant'] as $key => $val) {
            $allwhere['v.'. $key] = $val;
        }
        foreach($where['goods'] as $key => $val) {
            $allwhere['g.'. $key] = $val;
        }
        if(!empty($where['variant']) && !empty($where['goods'])) {
            $count = JoomProductModel::alias('p')
                ->join(['joom_variant' => 'v'], 'p.id=v.joom_product_id')
                ->join(['goods' => 'g'], 'g.id=p.goods_id')
                ->group('p.id')
                ->where($allwhere)
                ->count('p.id');
        } else if(empty($where['variant']) && !empty($where['goods'])) {
            $count = JoomProductModel::alias('p')
                ->join(['goods' => 'g'], 'g.id=p.goods_id')
                ->group('p.id')
                ->where($allwhere)
                ->count('p.id');
        } else if(!empty($where['variant']) && empty($where['goods'])) {
            $count = JoomProductModel::alias('p')
                ->join(['joom_variant' => 'v'], 'p.id=v.joom_product_id')
                ->group('p.id')
                ->where($allwhere)
                ->count('p.id');
        } else {
            $count = JoomProductModel::field('id') ->where($where['product']) ->count();
        }

        return $count;
    }

    private function _getLists($where, $page, $pageSize, $order, $sort)
    {
        $allwhere = [];
        foreach($where['product'] as $key => $val) {
            $allwhere['p.'. $key] = $val;
        }
        foreach($where['variant'] as $key => $val) {
            $allwhere['v.'. $key] = $val;
        }
        foreach($where['goods'] as $key => $val) {
            $allwhere['g.'. $key] = $val;
        }
        if(!empty($where['variant']) && !empty($where['goods'])) {
            $lists = JoomProductModel::alias('p')
                ->join(['joom_variant' => 'v'], 'p.id=v.joom_product_id')
                ->join(['goods' => 'g'], 'g.id=p.goods_id')
                ->field('p.id,p.main_image,p.product_id,p.goods_id,p.parent_sku,p.name,p.account_id,p.shop_id,p.create_id,p.review_status,p.number_saves,p.number_sold,p.lock_update,p.date_uploaded,p.update_time,p.create_time,p.number_refunds,p.refund_rate')
                ->group('p.id')
                ->where($allwhere)
                ->page($page, $pageSize)
                ->order([$order => $sort])
                ->select();
        } else if(empty($where['variant']) && !empty($where['goods'])) {
            $lists = JoomProductModel::alias('p')
                ->join(['goods' => 'g'], 'g.id=p.goods_id')
                ->field('p.id,p.main_image,p.product_id,p.goods_id,p.parent_sku,p.name,p.account_id,p.shop_id,p.create_id,p.review_status,p.number_saves,p.number_sold,p.lock_update,p.date_uploaded,p.update_time,p.create_time,p.number_refunds,p.refund_rate')
                ->group('p.id')
                ->where($allwhere)
                ->page($page, $pageSize)
                ->order([$order => $sort])
                ->select();
        } else if(!empty($where['variant']) && empty($where['goods'])) {
            $lists = JoomProductModel::alias('p')
                ->join(['joom_variant' => 'v'], 'p.id=v.joom_product_id')
                ->field('p.id,p.main_image,p.product_id,p.goods_id,p.parent_sku,p.name,p.account_id,p.shop_id,p.create_id,p.review_status,p.number_saves,p.number_sold,p.lock_update,p.date_uploaded,p.update_time,p.create_time,p.number_refunds,p.refund_rate')
                ->group('p.id')
                ->where($allwhere)
                ->page($page, $pageSize)
                ->order([$order => $sort])
                ->select();
        } else {
            $lists = JoomProductModel::field('id,main_image,product_id,goods_id,parent_sku,name,account_id,shop_id,create_id,review_status,number_saves,number_sold,lock_update,date_uploaded,update_time,create_time,number_refunds,refund_rate')
                ->where($where['product'])
                ->page($page, $pageSize)
                ->order([$order => $sort])
                ->select();
        }

        return $lists;
    }

    /**
     * @title 拿取权限下的销售专员
     * @apiFilter app\publish\filter\JoomListingFilter
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userList()
    {
        /*****************************可用************************/
        $departmentModel = new DepartmentModel();
        $departmentService = new DepartmentService();
        $departmentUserMap = new DepartmentUserMap();
        $userModel = new User();

        $user_info = CommonService::getUserInfo();
//        $user_info = ["user_id" => 5792,"realname" =>  "朱翼","username" =>  "swoole"];

        $channel_id = ChannelAccountConst::channel_Joom;

        $par_departments = $departmentService->getParentDepartmentsByChannelId($channel_id);
        $is_header = $departmentUserMap->where('user_id', $user_info['user_id'])->whereIn('department_id',$par_departments)->find();//判断用户在不在部门上级中

        $is_code_farming = DB('role_user')->where('user_id', $user_info['user_id'])->whereIn('role_id',[1,211])->find();//如果是IT或超管

        $departments = $departmentModel->where('channel_id', $channel_id)->column('id');//找当前频道所有部门

        if ( $is_header || (!empty($is_code_farming)) ) {

            $data = $departmentUserMap->alias('a')->whereIn('department_id',$departments)->field('b.id,b.realname')->alias('a')
                ->join('user b','a.user_id=b.id','LEFT')
                ->distinct('a.seller')->select();

        }else {

            $dui= $departmentUserMap->where('user_id',$user_info['user_id'])->whereIn('department_id',$departments)->select();//查找use和部门的交集
            $data = [];
            foreach ($dui as $k){
                if($k->job_id == 18){
                    $res = $departmentUserMap->alias('d')->field('user.id,user.realname')
                        ->where('department_id',$k['department_id'])->join('user','d.user_id = user.id','LEFT')->select();
                    $data = $res;
                }else{
                    $res = $userModel->where('id',$user_info['user_id'])->field('id,realname')->find();
                    $data[] = $res;
                }

            }
        }
        return $data;
        /*****************************缺少设置************************/
//        $user_info = CommonService::getUserInfo();
//        $ids = $this->getUnderlingInfo($user_info['id']);
    }

    /**
     * 获取下属人员信息
     * @param $user_id
     * @return array
     * @throws \think\Exception
     */
    public function getUnderlingInfo($user_id)
    {
        $underling = [];
        try {
            $departments = Cache::store('department')->tree();
            $departmentUserMapService = new DepartmentUserMapService();
            $departments_ids = $departmentUserMapService->getDepartmentByUserId($user_id);
            foreach ($departments_ids as $key => $department_id) {
                if (isset($departments[$department_id])) {
                    if (in_array($user_id, $departments[$department_id]['leader_id'])) {
                        $child = $departments[$department_id]['child_ids'];
                        if (!empty($child)) {
                            $departmentIds = $child;
                            foreach($child as $c => $children){
                                if(isset($departments[$department_id])){
                                    $child_child = $departments[$children]['child_ids'];
                                    if(!empty($child_child)){
                                        $departmentIds = array_merge($departmentIds, $child_child);
                                    }
                                }
                            }
                            array_push($departmentIds, $department_id);
                        } else {
                            $departmentIds = [$department_id];
                        }
                        $userMapModel = new DepartmentUserMap();
                        $userList = $userMapModel->field('user_id')->where('department_id', 'in', $departmentIds)->select();
                        if (!empty($userList)) {
                            foreach ($userList as $k => $user) {
                                array_push($underling, $user['user_id']);
                            }
                        }
                    }
                }
            }
            array_push($underling, $user_id);
            $underling = array_unique($underling);
        } catch (Exception $e) {
        }
        return $underling;
    }



    /**
     * 获取条件
     * @param $param
     * @return array|bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCondition($param)
    {
        $where = [
            'product' => [],
            'variant' => [],
            'goods' => [],
        ];
        $where2 = []; //临时用来装需要加变体查的条件；
        if(isset($param['enabled']) && $param['enabled'] !== '') {
            $where['product']['enabled'] = $param['enabled'];
        }
        if(!empty($param['account_id'])) {
            $where['product']['account_id'] = $param['account_id'];
        }
        //店铺
        if(!empty($param['shop_id'])) {
            $where['product']['shop_id'] = $param['shop_id'];
        }
        //单个销售员查询
        if(!empty($param['create_id'])) {//获取销售人员对应的店铺id
            $memberService = new MemberShipService();
            $shopIds = $memberService->getAccountIDByUserId($param['create_id'], 7);
            $where['product']['shop_id'] = ['in', $shopIds];
        }
        //审核状态
        if(isset($param['review_status']) && $param['review_status'] !== '') {
            $where['product']['review_status'] = $param['review_status'];
        }
        //修改状态
        if(isset($param['status']) && $param['status'] !== '') {
            $where['variant']['status'] = $param['status'];
        }
        //变体修改后上传状态
        if(isset($param['status']) && $param['status'] !== '') {
            $where['variant']['status'] = $param['status'];
        }
        //修改状态
        if(isset($param['lock_update']) && $param['lock_update'] !== '') {
            $where['product']['lock_update'] = $param['lock_update'];
        }
        //listing上架时，variant出现有下架的情况
        if(isset($param['variant_enabled']) && $param['variant_enabled'] !== '') {
            $where['variant']['enabled'] = $param['variant_enabled'];
        }
        //本地状态
        if(isset($param['sell_status']) && $param['sell_status'] !== '') {
            $where['goods']['sales_status'] = $param['sell_status'];
        }
        //上传时间,因为只传过来日期，转成时间戳后是按零时算的，所以最大时间需要加上当天的全部时间；
        if(!empty($param['min_time']) && empty($param['max_time'])) {
            $where['product']['date_uploaded'] = ['>', strtotime($param['min_time'])];
        } else if(empty($param['min_time']) && !empty($param['max_time'])) {
            $where['product']['date_uploaded'] = ['<', (strtotime($param['max_time']) + 86400)];
        } else if(!empty($param['min_time']) && !empty($param['max_time'])) {
            $where['product']['date_uploaded'] = ['between', [strtotime($param['min_time']), (strtotime($param['max_time']) + 86400)]];
        }

        //售价运费等分段查询；
        if(!empty($param['min_text']) && empty($param['max_text']) && !empty($param['sectionType'])  && in_array($param['sectionType'], ['price', 'shipping'])) {
            $where['variant'][$param['sectionType']] = ['>', $param['min_text']];
        }
        if(empty($param['max_text']) && !empty($param['max_text']) && !empty($param['sectionType'])  && in_array($param['sectionType'], ['price', 'shipping'])) {
            $where['variant'][$param['sectionType']] = ['<', $param['max_text']];
        }
        if(!empty($param['max_text']) && !empty($param['max_text']) && !empty($param['sectionType'])  && in_array($param['sectionType'], ['price', 'shipping'])) {
            $where['variant'][$param['sectionType']] = ['between', [$param['min_text'], $param['max_text']]];
        }

        //可售数查询；
        if(!empty($param['sectionType']) && $param['sectionType'] == 'inventory') {
            if(!empty($param['min_text']) && empty($param['max_text'])) {
                $where['product']['inventory'] = ['>', $param['min_text']];
            } else if(empty($param['min_text']) && !empty($param['max_text'])) {
                $where['product']['inventory'] = ['<', $param['max_text']];
            } else if(!empty($param['min_text']) && !empty($param['max_text'])) {
                $where['product']['inventory'] = ['between', [$param['min_text'], $param['max_text']]];
            }
        }

        //搜索框分类，有的要去变体查，有的要去产品列表查
        if(!empty($param['snType']) && !empty($param['snText'])) {
            $txt = json_decode($param['snText'],true);
            if (is_null($txt)) {
                $txt = [$param['snText']];
            }
            switch($param['snType']) {
                //本地spu;
                case 'local_spu':
                    if (count($txt) == 1) {
                        $goodsIds = \app\common\model\Goods::distinct(true)->where('spu','like',$txt[0].'%')
                            ->column('id');
                    } else if (count($txt)>1){
                        $goodsIds = \app\common\model\Goods::distinct(true)->whereIn('spu',$txt)->column('id');
                    }
                    $where['product']['goods_id'] = empty($goodsIds)?['exp','is null']:['in', $goodsIds];
                    break;
                case 'platform_spu':
                    if (count($txt) == 1) {
                        $where['product']['parent_sku'] = ['like',$txt[0].'%'];
                    } else if (count($txt)>1){
                        $where['product']['parent_sku'] = ['in',$txt];
                    }
                    break;
                case 'local_sku':
                    if (count($txt) == 1) {
                        $goodsIds = GoodsSku::distinct(true)->where('sku','like',$txt[0].'%')
                            ->column('goods_id');
                    } else if (count($txt)>1){
                        $goodsIds = GoodsSku::distinct(true)->whereIn('sku',$txt)->column('goods_id');
                    }

                    $where['product']['goods_id'] = empty($goodsIds)?['exp','is null']:['in', $goodsIds];

                    break;
                case 'platform_sku':
                    if (count($txt) == 1) {
                        $where['variant']['sku'] = ['like',$txt[0].'%'];
                    } else if (count($txt)>1){
                        $where['variant']['sku'] = ['in',$txt];
                    }
//                    $where['variant']['sku'] = $param['snText'];
                    break;
                case 'product_id':
                    if (count($txt) == 1) {
                        $where['product']['product_id'] = ['like',$txt[0].'%'];
                    } else if (count($txt)>1){
                        $where['product']['product_id'] = ['in',$txt];
                    }
//                    $where['product']['product_id'] = $param['snText'];
                    break;
                case 'name':
                    if (count($txt) == 1) {
                        $where['product']['name'] = ['like',$txt[0].'%'];
                    } else if (count($txt)>1){
                        $where['product']['name'] = ['in',$txt];
                    }
//                    $where['product']['name'] = $param['snText'];
                    break;
                default:
                    $this->error = '未知条件snType：'. $param['snType'];
                    return false;
                    break;
            }
        }
        return $where;
    }

    /**
     * @title 拿取变体
     * @param $data
     */
    public function getVariantList($data)
    {
        $where = [];
        if(!empty($data['joom_product_id'])) {
            $where['joom_product_id'] = $data['joom_product_id'];
        }
        if(!empty($data['product_id'])) {
            $where['product_id'] = $data['product_id'];
        }
        if(!empty($data['variant_id'])) {
            $where['variant_id'] = $data['variant_id'];
        }
        if(empty($where)) {
            $this->error = '空数为空，请传参数joom_product_id|product_id|varant_id';
            return false;
        }
        $lists = JoomVariantModel::where($where)
            ->field('id,joom_product_id,variant_id,product_id,main_image,sku,sku_id,color,size,price,shipping,inventory,shipping_time,enabled,sell_status')
            ->select();
        if(empty($lists)) {
            return [];
        }

        //本地状态
        $sell_lists = [
            1 => '在售',
            2 => '停售',
            3 => '待发布',
            4 => '卖完下架',
            5 => '缺货'
        ];
        $sku_ids = [0];
        foreach($lists as $li) {
            $sku_ids[] = $li['sku_id'];
        }
        $goodsSkuModel = new GoodsSkuModel();
        $local_skus = $goodsSkuModel->alias('v')
            ->join(['goods' => 'g'], 'v.goods_id=g.id')
            ->field('v.id,v.sku,g.sales_status')
            ->where(['v.id' => ['in', $sku_ids]])
            ->column('v.id,v.sku,g.sales_status', 'v.id');
        //var_dump($local_skus);
        $new_list = [];
        foreach($lists as $val) {
            $data = $val->toArray();
            $data['enabled'] = $data['enabled'] == 0 ? '下架' : '上架';
            $data['base_url'] = Cache::store('configParams')->getConfig('innerPicUrl')['value'].DS;
            if(isset($local_skus[$val['sku_id']])) {
                $data['sku_id'] = $local_skus[$val['sku_id']]['sku'];
                $data['sell_status'] = $sell_lists[$local_skus[$val['sku_id']]['sales_status']] ?? '-';
            } else {
                $data['sku_id'] = '';
                $data['sell_status'] = '-';
            }

            $new_list[] = $data;
        }
        return $new_list;
    }

    /**
     * @title 同步
     * @param $request
     * @return array|bool
     */
    public function sync($request)
    {
        //检查参数；
        $product_ids = $request->post('product_ids', '');
        if(empty($product_ids)) {
            $this->error = '参数product_ids为空';
            return false;
        }
        $idArr = array_filter(explode(',', $product_ids));
        $joomProductModel = new JoomProductModel();
        $lists = $joomProductModel->where(['product_id' => ['in', $idArr]])->field('id, Product_id')->column('shop_id', 'product_id');
        //先检验传过来的product_id都存在不；
        $errorid = [];
        foreach($idArr as $id) {
            if(!isset($lists[$id])) {
                $errorid[] = $id;
            }
        }
        if(!empty($errorid)) {
            $this->error = '产品ID：'. implode(',', $errorid). '错误，不存在';
            return false;
        }

        $shopModel = new JoomShopModel();
        $shopLists = $shopModel->where(['id' => ['in', array_values($lists)]])->column('id,client_id,client_secret,access_token,refresh_token', 'id');

        //下面开始开始；
        foreach($lists as $product_id => $shop_id) {
            $api = new JoomListingApi($shopLists[$shop_id]);
            $result = $api->getProduct($product_id);
            if($result['code'] == 0) {//请求成功同步到数据库
                $this->_syncProduct($result['data'], $shop_id);
            }
            unset($api);
        }

        return ['message' => '更新成功'];
    }

    private function _syncProduct($data, $shop_id)
    {
        //$cacheList = Cache::store('JoomListing');
        //审核状态
//        $review_arr = ['apending' => 0, 'approved' => 1, 'rejected' => 2];

        $joomProductModel = new JoomProductModel();

        $product = $data['Product'];
        $productCache = JoomProduct::field('id')->where('product_id',$product['id'])->find();

        //组合标签
        $tag = '';
        if(isset($product['tags'])) {
            foreach($product['tags'] as $t) {
                $tag .= $t['Tag']['name']. ',';
            }
            $tag = trim($tag, ',');
        }

        //组合错误原因
        $variantMessage = [];
        $warning_id = '';
        $review_note = '';
        if(isset($product['diagnosis'])) {
            foreach($product['diagnosis'] as $t) {
                $dec = $t['description']?? '';
                if(isset($t['variantId'])) {
                    $warning_id .= $t['variantId']. '|';
                    $variantMessage[$t['variantId']] = $dec;
                } else {
                    $review_note .= $dec;
                }
            }
            $warning_id = trim($warning_id, '|');
            $review_note = trim($review_note, '|');
        }
        //审核状态
        $review_arr = ['pending' => 0, 'approved' => 1, 'rejected' => 2];

        //产品表；
        $lists['product'] = $tmpp = [
            'id' => $productCache['id']?? 0,
            'tags' => $tag,
            'name' => $product['name'] ?? '',
            'main_image' => $product['main_image'] ?? '',
            'parent_sku' => $product['parent_sku'] ?? '',
            'is_promoted' => strtolower($product['is_promoted']) == 'true' ? 1 : 0,
            'number_saves' => $product['number_saves'] ?? '',
            'product_id' => $product['id'],
            'enabled' => strtolower($product['enabled']) == 'true' ? 1 : 0,
            'review_status' => $review_arr[$product['review_status']]?? 0,
            'number_sold' => $product['number_sold'] ?? '',
            'number_refunds' => $product['number_refunds'] ?? '0',//退款订单数量
            'refund_rate' => empty($product['refund_rate']) ? '0' : number_format($product['refund_rate'],4),
            'date_uploaded' => strtotime($product['date_uploaded']),
            'dangerous_kind' => $product['dangerous_kind'],
        ];

        //下列几个属性可能没有，有才出现；防止更新错错；
        if(isset($product['brand'])) {
            $lists['product']['brand'] = $product['brand'];
        }
        if(isset($product['upc'])) {
            $lists['product']['upc'] = $product['upc'];
        }

        //info表
        $lists['info'] = [
            'extra_images' => $product['extra_images'] ?? '',
            'original_images' => $product['original_image_url'] ?? '',
            'product_id' => $product['id'],
            'warning_id' => $warning_id,
            'review_note' => $review_note,
        ];
        if ($lists['product']['enabled'] && $lists['product']['review_status']==1) {//在线审核通过再更新描述
            $lists['info']['description'] = $product['description'] ?? '';
        }

        //下列变量将变体表数据到产品表
        $variant_enabled = $tmpp['enabled'];
        $inventory = 0;
        $lowest_price = 0;
        $highest_price = 0;
        $lowest_shipping = 0;
        $highest_shipping = 0;

        //variant表
        $lists['variant'] = [];
        if(isset($product['variants'])) {
            $oldVariantIds = JoomVariant::where('joom_product_id',$productCache['id'])->column('id','variant_id');
            foreach($product['variants'] as $vkey=>$v) {
                //拿变体缓存；
//                $oldVariantId = getVariantCache($shop_id, $v['Variant']['id']);
                $lists['variant'][$vkey] = $tmpv = [
                    'sku' => $v['Variant']['sku'],
                    'color' => $v['Variant']['color'] ?? '',
                    'size' => $v['Variant']['size'] ?? '',
                    'price' => $v['Variant']['price'] ?? '',
                    'declared_value' => $v['Variant']['declaredValue'] ?? '',
                    'shipping' => $v['Variant']['shipping'] ?? '',
                    'shipping_time' => $v['Variant']['shipping_time'] ?? '',
                    'inventory' => $v['Variant']['inventory'] ?? '',
                    'msrp' => $v['Variant']['msrp'] ?? '',
                    'shipping_weight' => $v['Variant']['shipping_weight']??0,
                    'shipping_length' => $v['Variant']['shipping_length']??0,
                    'shipping_width' => $v['Variant']['shipping_width']??0,
                    'shipping_height' => $v['Variant']['shipping_height']??0,
                    'message' => $variantMessage[$v['Variant']['id']] ?? '',
                    'main_image' => $v['Variant']['main_image'] ?? '',
                    'original_image_url' => $v['Variant']['original_image_url'] ?? '',
                    'variant_id' => $v['Variant']['id'],
                    'product_id' => $v['Variant']['product_id'],
                    'enabled' => strtolower($v['Variant']['enabled']) == 'true' ? 1 : 0,
                    //能拉下来的，肯定都是刊登成功的；
                    'status' => 1,
                ];
                if (isset($oldVariantIds[$v['Variant']['id']])) {
                    $lists['variant'][$vkey]['id'] = $oldVariantIds[$v['Variant']['id']];
                }

                //下列变量将变体表数据到产品表
                if($tmpv['enabled'] == 0) { //只要有一个变体是下架，这个值就设为0
                    $variant_enabled = 0;
                }

                //先附初值，然后记算后续值大小；
                if($vkey == 0) {
                    $inventory = $tmpv['inventory'];
                    $highest_price = $lowest_price = $tmpv['price'];
                    $highest_shipping = $lowest_shipping = $tmpv['shipping'];
                } else {
                    $inventory = min($inventory, $tmpv['inventory']);
                    $lowest_price = min($lowest_price, $tmpv['price']);
                    $highest_price = max($highest_price, $tmpv['price']);
                    $lowest_shipping = min($lowest_shipping, $tmpv['shipping']);
                    $highest_shipping = max($highest_shipping, $tmpv['shipping']);
                }
            }
        }

        //以下变体内的数据记入产品，便用列表查询；
        $lists['product']['variant_enabled'] = $variant_enabled;
        $lists['product']['inventory'] = $inventory;
        $lists['product']['lowest_price'] = $lowest_price;
        $lists['product']['highest_price'] = $highest_price;
        $lists['product']['lowest_shipping'] = $lowest_shipping;
        $lists['product']['highest_shipping'] = $highest_shipping;

        $result = $joomProductModel->syncProduct($lists);

        return $result;
    }

    /**
     * 产品上下架操作
     * @param $request
     * @param string $type enable:上架；disable：下架；
     * @return array|bool
     */
    public function operation($request, $type = 'enable')
    {
        if(!in_array($type, ['enable', 'disable'])) {
            $this->error = '操作类型不存在';
            return false;
        }
        //检查参数；
        $product_ids = $request->post('product_ids', '');
        if(empty($product_ids)) {
            $this->error = '参数product_ids为空';
            return false;
        }
        $idArr = array_filter(explode(',', $product_ids));
        $joomProductModel = new JoomProductModel();
        $lists = $joomProductModel->where(['product_id' => ['in', $idArr]])->field('id, Product_id')->column('shop_id', 'product_id');
        //先检验传过来的product_id都存在不；
        $errorid = [];
        foreach($idArr as $id) {
            if(!isset($lists[$id])) {
                $errorid[] = $id;
            }
        }
        if(!empty($errorid)) {
            $this->error = '产品ID：'. implode(',', $errorid). '错误，不存在';
            return false;
        }

        $shopModel = new JoomShopModel();
        $shopLists = $shopModel->where(['id' => ['in', array_values($lists)]])->column('id,client_id,client_secret,access_token,refresh_token', 'id');

        //操作用户信息
        $user = CommonService::getUserInfo($request);
        $uid = $user['user_id'];
        //记录操作日志；
        $joomService = new JoomService();

        //下面开始开始；
        $message = '';
        $updateProductId = [];
        foreach($lists as $product_id => $shop_id) {
            if(isset($shopLists[$shop_id])) {
                $where=[
                    'create_id' => ['=', $uid],
                    'new_data' => ['=', ($type == 'enable'? '上架成功' : '下架成功')],
                    'product_id' =>[ '=', $product_id],
                    'status' => ['=', 0],
                ];
                $log=[
                    'create_id' => $uid,
                    'type'=>JoomService::TYPE[$type. 'Product'],
                    'new_data' => ($type == 'enable'? '上架成功' : '下架成功'),
                    'old_data' => '',
                    'product_id' => $product_id,
                    'create_time' => time(),
                ];
                $joomService->joomActionLog($log, $where);

            } else {
                $message .= '产品ID：'. $product_id. "帐号店铺信息不存在;\r\n";
            }
        }

        //更新本地成功后的数据；
        //if(!empty($updateProductId)) {
        //    $joomProductModel->where(['product_id' => ['in', $updateProductId]])->update(['enabled' => ($type == 'enable'? 1 : 0)]);
        //}
        return ['message' => !empty($message)? $message : ($type == 'enable'? '上架成功' : '下架成功')];
    }

    /**
     * @title 变体上下架；
     * @param $request
     * @param string $type
     * @return array
     */
    public function variantOperation($request, $type = 'enable')
    {
        if(!in_array($type, ['enable', 'disable'])) {
            $this->error = '操作类型不存在';
            return false;
        }
        //检查参数；
        $skus = $request->post('skus', '');
        $product_id = $request->post('product_id', '');
        if(empty($skus) && empty($product_id)) {
            $this->error = '参数skus|product_id为空';
            return false;
        }
        $where = [];
        if(!empty($skus)) {
            $skuArr = array_filter(explode(',', $skus));
            $where['sku'] = ['in', $skuArr];
        }
        if(!empty($product_id)) {
            $where['product_id'] = $product_id;
        }
        $joomVariantModel = new JoomVariantModel();
        $lists = $joomVariantModel->where($where)->column('joom_product_id,product_id,variant_id', 'sku');

        //先检验传过来的product_id都存在不；
        $errorid = [];
        foreach($skuArr as $sku) {
            if(!isset($lists[$sku])) {
                $errorid[] = $sku;
            }
        }
        if(!empty($errorid)) {
            $this->error = '变体sku：'. implode(',', $errorid). '错误，不存在';
            return false;
        }

        //找出全部product_id;
        $product_ids = [];
        foreach($lists as $li) {
            $product_ids[] = $li['joom_product_id'];
        }

        //找出所有的product_id对应的店铺授权信息；
        $shopModel = new JoomShopModel();
        $shopLists = $shopModel->alias('s')
            ->join(['joom_product' => 'p'], 's.id=p.shop_id')
            ->where(['p.id' => ['in', $product_ids]])
            ->field('p.id pid,s.id,s.client_id,s.client_secret,s.access_token,s.refresh_token')
            ->select();
        $shop_lists = [];
        foreach($shopLists as $shop) {
            $shop_lists[$shop['pid']] = $shop->toArray();
        }

        //操作用户信息
        $user = CommonService::getUserInfo($request);
        $uid = $user['user_id'];
        //记录操作日志；
        $joomService = new JoomService();

        //下面开始开始；
        $message = '';
        $updateSku = [];
        foreach($lists as $sku => $variant) {
            if(isset($shop_lists[$variant['joom_product_id']])) {
                $updateSku[] = $sku;
                $where=[
                    'create_id' => ['=', $uid],
                    'new_data' => ['=', ($type == 'enable'? '上架成功' : '下架成功')],
                    'variant_id'=> $variant['variant_id'],
                    'status' => ['=', 0],
                ];
                $log=[
                    'create_id' => $uid,
                    'type' => JoomService::TYPE[$type. 'Variant'],
                    'new_data' => ($type == 'enable'? '上架成功' : '下架成功'),
                    'old_data' => '',
                    'product_id' => $variant['product_id'],
                    'variant_id'=> $variant['variant_id'],
                    'create_time' => time(),
                ];
                $joomService->joomActionLog($log, $where);
            } else {
                $message .= 'sku：'. $sku. "帐号店铺信息不存在;\r\n";
            }
        }
        //更新本地成功后的数据；
        //if(!empty($updateSku)) {
        //    $joomVariantModel->where(['sku' => ['in', $updateSku]])->update(['enabled' => ($type == 'enable'? 1 : 0)]);
        //}
        return ['message' => !empty($message)? $message : ($type == 'enable'? '上架成功' : '下架成功')];
    }

    /**
     * 查询操作日志
     * @param array $param
     * @param int $page
     * @param int $pageSize
     * @param string $fields
     * @return array
     */
    public function getLogs($param=[], $page=1, $pageSize=30, $fields='*')
    {
        $where= [
            'product_id'=>['=',$param['product_id']],
        ];

        $const =[
            'name'=>'刊登标题',
            'description'=>'详情描述',
            'tags'=>'tags',
            'brand'=>'品牌',
            'landing_page_url'=>'展示页面',
            'upc'=>'upc',
            'main_image'=>'主图',
            'extra_images'=>'辅图',
            'max_quantity'=>'最大库存',
            'inventory'=>'库存',
            'price'=>'售价',
            'shipping'=>'运费',
            'enabled'=>'是否有效',
            'size'=>'size',
            'color'=>'颜色',
            'msrp'=>'msrp',
            'shipping_time'=>'发货日期',
            'warehouse_name'=>'仓库',
            'combine_sku'=>'捆绑sku'
        ];


        $count = (new JoomActionLogModel())->where($where)->count();

        $data = (new JoomActionLogModel())->order('create_time Desc')->where($where)->page($page,$pageSize)->select();
        $uidArr = [0];
        foreach($data as $val) {
            $uidArr[] = $val['create_id'];
        }
        $userList = UserModel::where(['id' => ['in', array_unique($uidArr)]])->column('realname', 'id');

        if($data)
        {
            foreach ($data as &$d)
            {
                $d['create_name'] = $userList[$d['create_id']];
                if(is_array($d['new_data']))
                {
                    $log='';

                    foreach ($d['new_data'] as $name=>$v)
                    {
                        if(is_numeric($name))
                        {
                            $log = 'Wish Express';
                        }else{
                            $old_log = is_string($d['old_data'][$name])? $d['old_data'][$name] :  json_encode($d['old_data'][$name]);
                            $new_log = is_string($d['new_data'][$name])? $d['new_data'][$name] :  json_encode($d['new_data'][$name]);
                            $log=$log. $const[$name]. ':由['. $old_log. ']改为['. $new_log.']'. '<br />';
                        }
                    }
                }else{
                    $log = $d['new_data'];
                }
                $d['log'] = $log;
            }
        }

        return ['data' => $data, 'count' => $count,'page' => $page, 'pageSize' => $pageSize];
    }

    /**
     * 根据joom_shop_id帐号获取销售员和仓库类型
     * @param $account_id_arr
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getSellerByAccountId($account_id_arr) {
        $where['a.is_invalid'] = ['eq', 1];
        $where['a.joom_enabled'] = ['eq', 1];
        $where['b.channel_id'] = ['eq', 7];
        if(!empty($account_id_arr)) {
            $where['b.account_id'] = ['in', $account_id_arr];
        }
        $seller = (new JoomShopModel())->alias('a')
            ->group('a.id')
            ->field('a.id,u.realname,u.id uid,b.warehouse_type,b.seller_id')
            ->join('channel_user_account_map b', 'a.id=b.account_id', 'LEFT')
            ->join('user u', 'b.seller_id=u.id', 'LEFT')
            ->where($where)
            ->order('a.id ASC')
            ->select();
        return $seller;
    }

    public function getRecordList($param, $page, $pageSize)
    {
        $where = $this->getCondition($param);
        //创建时间，需要转化一下；
        if (isset($where['product']['date_uploaded'])) {
            $where['product']['create_time'] = $where['product']['date_uploaded'];
            unset($where['product']['date_uploaded']);
        }

        if($where === false) {
            return false;
        }

        $where['product']['application'] = ['=', 'rondaful'];

        $count = $this->_getCount($where);
        $lists = $this->_getLists($where, $page, $pageSize, 'create_time', 'desc');//列表找account_id

        //列表为空，直接返回；
        if(empty($lists)) {
            return [
                'count' => $count,
                'page' => $page,
                'pageSize' => $pageSize,
                'data' => [],
                'base_url' => '',
            ];
        }

        //组合数据；
        $joom_product_ids = [];
        $new_list = [];
        foreach($lists as $data) {
            $data = $data->toArray();
            $joom_product_ids[] = $data['id'];
            $new_list[] = $data;
        }
        //帐号销售专员店铺
        $sellorList = $this->getSellerByAccountId(array_column($new_list, 'shop_id'));//shop_id
        $accountist = JoomAccountModel::where(['id' => ['in', array_column($new_list, 'account_id')]])->column('account_name', 'id');
        $shopList = JoomShopModel::where(['id' => ['in', array_column($new_list, 'shop_id')]])->column('code,shop_name', 'id');
//        dump(json_decode(json_encode($sellorList),true) );die();
        //审核状态
        $review_lists = ['待审核', '已批准', '被拒绝'];
        //goods_id转本地spu
        $goodsHelp = new GoodsHelp();
        $localSpuList = $goodsHelp->goodsId2Spu(array_column($new_list, 'goods_id'));
        //变体数据；
        $variantList = JoomVariantModel::where(['joom_product_id' => ['in', $joom_product_ids]])
            ->field('id,joom_product_id,product_id,main_image,variant_id,sku,sku_id,color,size,price,shipping,inventory,shipping_time,status,sku,message')
            ->select();
        //找出变体SKU
        $sku_ids = [0];
        foreach($variantList as $li) {
            $sku_ids[] = $li['sku_id'];
        }
        $local_skus = GoodsSkuModel::where(['id' => ['in', $sku_ids]])->column('sku', 'id');
        //变体同步状态；
        $statusArr = ['上传中', '上传成功', '上传失败'];

        //找出对应的变体的状态，匹配出SKU；
        $tmpProduct = [];
        foreach($variantList as $variant) {
            $tmp = $variant->toArray();
            $tmp['status_text'] = $statusArr[$tmp['status']];
            $tmp['sku_id'] = $local_skus[$tmp['sku_id']]?? '';
            $tmpProduct[$variant['joom_product_id']][] = $tmp;
        }

        foreach($new_list as &$val) {
            //帐号店铺
            $val['account_name'] = $accountist[$val['account_id']] ?? '';
            $val['sellor'] = '-';
            foreach($sellorList as $seller) {
                if($val['shop_id'] == $seller['id']) {
                    $val['sellor'] = $seller['realname'];
                    break;
                }
            }

            $val['shop_name'] = $shopList[$val['shop_id']]['shop_name']?? '';
            $val['shop_code'] = $shopList[$val['shop_id']]['code']?? '';

            //时间转化
            $val['date_uploaded'] = $val['date_uploaded'] == 0? '' : date('Y-m-d H:i', $val['date_uploaded']);
            $val['create_time'] = $val['create_time'] == 0? '' : date('Y-m-d H:i', $val['create_time']);
            $val['update_time'] = $val['update_time'] == 0? '' : date('Y-m-d H:i', $val['update_time']);

            //sku,spu
            $val['local_parent_sku'] = empty($val['goods_id'])? '' : ($localSpuList[$val['goods_id']]?? '');

            //审核状态
            $val['review_status_text'] = $review_lists[$val['review_status']]?? '';

            //产品状态匹配变体状态；
            $val['status'] = 0;
            //变体
            $variant = $tmpProduct[$val['id']]?? [];
            $message = '';
            foreach($variant as $v) {
                if($v['status'] > $val['status']) {
                    $val['status'] = $v['status'];
                }
                if (empty($message) && $v['message']) {
                    $message = $v['message'];
                }
            }
            $val['status_text'] = $statusArr[$val['status']];
            $val['variant'] = $variant;
            $val['message'] = $message;
        }
        unset($val);

        return [
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'data' => $new_list,
            'base_url' => Cache::store('configParams')->getConfig('innerPicUrl')['value'].DS
        ];
    }

    public function delRecordList($ids) {
        $idArr = array_filter(explode(',', $ids));
        $productModel = new JoomProductModel();
        $variantModel = new JoomVariantModel();
        $productList = $productModel->where(['id' => ['in', $idArr]])->column('product_id,enabled', 'id');
        if(empty($productList)) {
            return ['message' => '产品不存在'];
        }
        $variantList = $variantModel->where(['joom_product_id' => ['in', $idArr]])->field('id,variant_id,joom_product_id,status')->select();
        //查询可否删除，product_id为空variant_id为空，status==2满足这3条件才能删除;
        $delArr = [];
        foreach($productList as $id => $product) {
            if(!empty($product['product_id']) || $product['enabled'] == 1 && $product['status'] != 2) {
                $this->error = '删除错误ID：'. $id. ' 的product_id不为空，或平台状态已上架';
                return false;
            }
            foreach($variantList as $variant) {
                if($variant['joom_product_id'] == $id) {
                    if(!empty($vairant['variant_id']) && $variant['status'] != 2) {
                        $this->error = '删除错误ID：'. $id. ' 的variant_id不为空，或平台状态已上架，或上传状态无出错';
                        return false;
                    }
                }
            }
            $delArr[] = $id;
        }
        foreach($delArr as $id) {
            $result = $productModel->where(['id' => $id])->delete();
            $variantModel->where(['joom_product_id' => $id])->delete();
        }
        return ['message' => '成功删除'. count($delArr). '条数据'];
    }

    /**
     * 返回本例错误；
     * @return mixed
     */
    public function getError() {
        return $this->error;
    }
}