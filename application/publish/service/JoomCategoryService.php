<?php

/**
 * Description of AliexpressCategoryService
 * @datetime 2017-6-6  11:14:59
 * @author zhangdongdong
 */

namespace app\publish\service;
use app\common\model\ChannelUserAccountMap;
use app\common\model\joom\JoomShop;
use app\index\service\JoomShopService;
use app\index\service\MemberShipService;
use think\Db;
use think\Exception;
use app\common\cache\Cache;
use app\common\model\joom\JoomAccount as JoomAccountModel;
use app\common\model\joom\JoomShop as JoomShopModel;
use app\common\model\joom\JoomShopCategory as JoomShopCategoryModel;
use app\common\model\Goods as GoodsModel;
use app\goods\service\CategoryHelp;
//部门权限
use app\common\service\Common as CommonService;
use app\index\service\Department;
use app\common\service\ChannelAccountConst;
use app\common\model\AccountUserMap;

use app\publish\validate\AliCategoryAuthValidate;

class JoomCategoryService {

    protected $cgModel = null;

    private $error = '';

    public function __construct()
    {
        $this->cgModel = new JoomShopCategoryModel();
    }

    /**
     * 获取列表
     * @param $param
     * @return array
     */
    public function lists($param) {
        $page = $param['page']?? 1;
        $pageSize = $param['pageSize']?? 10;

        $where = [];
        $where2 = [];
        if(!empty($param['joom_account_id'])) {
            $where['joom_account_id'] = $param['joom_account_id'];
        }
        if(!empty($param['joom_shop_id'])) {
            $where['joom_shop_id'] = $param['joom_shop_id'];
        }
        if(!empty($param['category_id'])) {
            $category_ids[] = $param['category_id'];
            $catelists = Cache::store('category')->getCategory();
            foreach($catelists as $val) {
                if(isset($val['pid']) && $val['pid'] == $param['category_id']) {
                    $category_ids[] = $val['id'];
                }
            }
            unset($category_list);
            $where['category_id'] = ['in', $category_ids];
        }

        $count = $this->cgModel->where($where)->count();

        $lists = $this->cgModel->where($where)->order('create_time', 'desc')->page($page, $pageSize)->select();
        if(empty($lists)) {
            return [
                'data' => [],
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $count,
            ];
        }

        $account_ids = [];
        $shop_ids = [];
        $new_lists = [];
        foreach($lists as $val) {
            $account_ids[] = $val['joom_account_id'];
            $shop_ids[] = $val['joom_shop_id'];
            $new_lists[] = $val->toArray();
        }

        //帐号名,店铺名；
        $account_arr = JoomAccountModel::where(['id' => ['in', $account_ids]])->column('account_name,code', 'id');
        $shop_arr = JoomShopModel::where(['id' => ['in', $shop_ids]])->column('shop_name', 'id');

        //获取分类名；
        $help = new CategoryHelp();
        $category_list = $help->getCategoryLists();

        $new_array = [];
        foreach($new_lists as $val) {
            $val['account_name'] = $account_arr[$val['joom_account_id']]['account_name']?? '';
            $val['account_code'] = $account_arr[$val['joom_account_id']]['code']?? '';
            $val['shop_name'] = $shop_arr[$val['joom_shop_id']]?? '';
            $val['category_name'] = $this->getCategoryName($category_list, $val['category_id']);
            $val['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
            $new_array[] = $val;
        }

        $result = [
            'data' => $new_array,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    /**
     * 通过categoryid来取得category名字；
     * @param $category_list
     * @param $id
     * @return string
     */
    private function getCategoryName($category_list, $id) {
        if($id == 0) {
            return '';
        }
        $bol = true;
        $name = '';
        foreach($category_list as $val) {
            $name = '';
            $title = $val['title'];
            if(isset($val) && $val['id'] == $id) {
                $name = $title;
                break;
            }
            foreach($val['childs'] as $v) {
                if(isset($v['id']) && $v['id'] == $id) {
                    $name = $title. '>'. $v['title'];
                    break;
                }
            }
            if(!empty($name)) {
                break;
            }

        }

        return $name;
    }

    /**
     * 获取joom 平台账号,根据用户权限进行过滤
     * @param int $warehouse_type
     * @param int $userId 用户id
     * @return array
     */

    public function accounts($warehouse_type = 0)
    {
        //获取当前用户下的成员
        $users = ( new JoomListingHelper )->userList();
        $user_ids = array_column($users,'id');

        if(!empty($warehouse_type)) {
            $where['a.warehouse_type'] = ['eq', $warehouse_type];
        }

        $where['b.is_invalid'] = ['eq', 1];
        $where['b.joom_enabled'] = ['eq', 1];
        $where['a.channel_id'] = ['eq', 7];

        $data = [];

        $accounts = (new ChannelUserAccountMap())->alias('a')
            ->where($where)
            ->whereIn('a.seller_id',$user_ids)
            ->field('c.id value,c.code label,c.account_name')
            ->join('joom_shop b', 'b.id=a.account_id', 'LEFT')
            ->join('joom_account c', 'b.joom_account_id=c.id', 'LEFT')
            ->distinct('c.id')
            ->select();

        foreach ($accounts as $account){
            $data[] = $account;
        }
        return $data;


        /*************************通过user、user_account_map、joom_account查找*******************************************/
//        //获取当前用户下的成员
//        $users = ( new JoomListingHelper ) ->userList();
////        dump(json_decode(json_encode($users),true));die();
//
//        if(!empty($warehouse_type)) {
//            $where['b.warehouse_type'] = ['eq', $warehouse_type];
//        }
//        $where['a.is_invalid'] = ['eq', 1];
//        $where['a.platform_status'] = ['eq', 1];
//        $where['b.channel_id'] = ['eq', 7];
//
//        Db::startTrans();
//        try{
//            $joom_accounts = [];
//            foreach ($users as $k1 => $user){//base_account_ids
//                if(!empty($user['id'])){
//
//                    $account_ids = DB::table('user')->alias('a')
//                        ->where('b.channel_id',7)
//                        ->where('a.id',$user['id'])
//                        ->join('account_user_map c', 'c.user_id=a.id', 'LEFT')
//                        ->join('account b', 'c.account_id=b.id', 'LEFT')
//                        ->join('channel_user_account_map d', 'a.id=d.seller_id', 'LEFT')
//                        ->field('b.id,d.warehouse_type')
//                        ->distinct('b.id')
//                        ->select();
//
//                    if(!empty($account_ids)){//joom_account_ids
//                        foreach ($account_ids as $k2 => $account_id){
//                            $joom_account = Db::table('joom_account')
//                                ->where('base_account_id',$account_id['id'])
//                                ->field('id value,account_name,code label')
//                                ->select();
//                            dump($joom_account);
//                            if(!empty($joom_account)){
//                                foreach ($joom_account as $v){
//                                    $joom_accounts[] = $v;
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//            Db::commit();
//        }catch (\Exception $exception) {
//            //错误信息
//            Db::rollback();
//            print_r($exception->getMessage());
//        }
//        return  empty($joom_accounts) ? [] : array_values(array_unique($joom_accounts, SORT_REGULAR));
    }

    /**
     * joom分钏拿取帐号对应的店铺信息；
     */
    public function shops($joom_account_id)
    {
        $where = empty($joom_account_id) ? [] : ['joom_account_id' => $joom_account_id];
        $where['is_invalid'] = 1;
        $where['is_authorization'] = 1;
        $data = JoomShop::field('code label,shop_name,id value')->where($where)->select();
        return $data;
    }

    /**
     * @title 当前可用的分类列表；
     */
    public function categoryLists($lang)
    {
        $help = new CategoryHelp();

        $lang_id = $lang == 'zh' ? 1 : 2;
        $list = $help->getCategoryLists($lang_id);
        return $list;
    }

    /**
     * @title 设置分类
     * @param $data
     * @return array
     */
    public function setCategory($data)
    {
        $where['joom_account_id'] = $data['joom_account_id'];
        $where['joom_shop_id'] = $data['joom_shop_id'];
        //先查看这个账号店铺存不存在
        $count = JoomShopModel::where(['joom_account_id' => $data['joom_account_id'], 'id' => $data['joom_shop_id']])->count();
        if($count < 1) {
            $this->error = '帐号店铺信息不存在';
            return false;
        }

        //先找出所有的数据；
        $oldList = $this->cgModel->where(['joom_account_id' => $data['joom_account_id'], 'joom_shop_id' => $data['joom_shop_id']])->column('category_id', 'id');

        $categoryArr = explode(',', $data['category_id']);
        //排序
        if(count($categoryArr) > 1) {
            $categoryArr = array_unique($categoryArr);
            sort($categoryArr);
        }

        //编辑时再找出要删除的数据和要新增的数据，新增时不进行操作；
        $delArr = [];
        if($data['update'] != 0) {
            foreach($oldList as $key=>$val) {
                if(!in_array($val, $categoryArr)) {
                    $delArr[] = $key;
                }
            }
        }

        //需要新增的数据数组；
        $addArr = [];
        foreach($categoryArr as $val) {
            if(!in_array($val, $oldList)) {
                $addArr[] = [
                    'joom_account_id' => $data['joom_account_id'],
                    'joom_shop_id' => $data['joom_shop_id'],
                    'category_id' => $val,
                    'create_time' => $data['create_time'],
                    'creator_id' => $data['creator_id'],
                ];
            }
        }

        try {
            if(!empty($delArr)) {
                $this->cgModel->where(['id' => ['in', $delArr]])->delete();
            }

            if(!empty($addArr)) {
                $this->cgModel->allowField(true)->isUpdate(false)->saveAll($addArr);
            }

            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function getError() {
        return $this->error;
    }

    public function getcategoryID($data)
    {
        $list = $this->cgModel->where(['joom_account_id' => $data['joom_account_id'], 'joom_shop_id' => $data['joom_shop_id']])->field('category_id')->select();
        if(empty($list)) {
            return [];
        }
        $list = collection($list)->toArray();
        return array_column($list, 'category_id');
    }

    /**
     * @title 验证产品信息：当前权限下的 同类型的店铺
     * @param $data
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkShops($data) {
        //获取组员 [id,realename]
        $users = ( new JoomListingHelper )->userList();

        //获取当前商品类别
        $goods = GoodsModel::where(['id' => $data['goods_id']])->find();
        if(empty($goods) || $goods['category_id'] == 0) {// 验证商品category_id
            return [];
        }

        $category_id = $goods['category_id'];
        $joom_shop_ids = JoomShopCategoryModel::where('category_id',$category_id)->column('joom_shop_id');
        if(!count($joom_shop_ids)) {
            return [];
        }

        if(!empty($warehouse_type)) {
            $where['a.warehouse_type'] = ['eq', $warehouse_type];
        }
        $where['b.is_invalid'] = ['eq', 1];
        $where['b.joom_enabled'] = ['eq', 1];
        $where['a.channel_id'] = ['eq', 7];

        Db::startTrans();
        try{
            $data = [];
            foreach ($users as $k=>$user){
                $accounts = (new ChannelUserAccountMap())->alias('a')
                    ->where($where)
                    ->whereIn('a.seller_id',$user['id'])
                    ->field('c.id,c.account_name name,c.code,a.warehouse_type,a.seller_id uid,b.id shop_id,b.shop_name,b.code shop_code,c.id joom_account_id')
                    ->join('joom_shop b', 'b.id=a.account_id', 'LEFT')
                    ->join('joom_account c', 'b.joom_account_id=c.id', 'LEFT')
                    ->select();
                if (!empty($accounts)){
                    foreach ($accounts as $k=>$account){
                        $account['realname'] = $user['realname'];
                        $data[$account['id']]['id'] = $account['id'];
                        $data[$account['id']]['name'] = $account['name'];
                        $data[$account['id']]['code'] = $account['code'];
                        $data[$account['id']]['warehouse_type'] = $account['warehouse_type'];
                        $data[$account['id']]['uid'] = $account['uid'];
                        $data[$account['id']]['realname'] = $account['realname'];
                        $data[$account['id']]['shop'][]= ['id'=>$account['shop_id'],'shop_name'=>$account['shop_name'],'code'=>$account['shop_code'],'joom_account_id'=>$account['joom_account_id']];
                    }
                }
            }
            Db::commit();
        }catch (\Exception $exception) {
            //错误信息
            Db::rollback();
            print_r($exception->getMessage());
        }
        return array_values(array_unique($data, SORT_REGULAR));


        /*************************通过user、user_account_map、joom_account查找*******************************************/
        //获取组员 id,realename
//        $users = ( new JoomListingHelper )->userList();
//        $good = GoodsModel::where(['id' => $data['goods_id']])->find();//获取当前商品的信息
//        if(empty($good) || $good['category_id'] == 0) {// 验证商品category_id
//            return [];
//        }
//        $where = [];
//        $where['category_id'] = $good['category_id'];
//        $joom_shop_ids = JoomShopCategoryModel::where($where)->column('joom_shop_id');
//        if(!count($joom_shop_ids)) {
//            return [];
//        }
//        Db::startTrans();
//        try{
//            $joom_accounts = [];
//            $data = [];
//            foreach ($users as $k1 => $user){//base_account_ids
//                if(!empty($user['id'])){
//
//                    $account_ids = DB::table('user')->alias('a')
//                        ->where('b.channel_id',7)
//                        ->where('a.id',$user['id'])
//                        ->join('account_user_map c', 'c.user_id=a.id', 'LEFT')
//                        ->join('account b', 'c.account_id=b.id', 'LEFT')
//                        ->join('channel_user_account_map d', 'a.id=d.seller_id', 'LEFT')
//                        ->field('b.id,d.warehouse_type')
//                        ->select();
//
//                    if(!empty($account_ids)){//joom_account_ids
//                        foreach ($account_ids as $k2 => $account_id){
//                            $joom_accounts = Db::table('joom_account')
//                                ->where('base_account_id',$account_id['id'])
//                                ->field('id,account_name,code')
//                                ->select();
//                        }
//
//                        if(!empty($joom_accounts)){
//                            foreach ($joom_accounts as $k3 => $joom_account){
//                                $shops = DB::table('joom_shop')->alias('a')
//                                    ->where('a.joom_account_id',$joom_account['id'])
//                                    ->where('b.category_id',$good['category_id'])
//                                    ->join('joom_shop_category b', 'a.id=b.joom_shop_id ', 'LEFT')
//                                    ->field('a.id,a.code,a.shop_name,a.joom_account_id')
//                                    ->select();
//
//                                $data[$k1][$k2][$k3]['id'] = $joom_account['id'];
//                                $data[$k1][$k2][$k3]['name'] = $joom_account['account_name'];
//                                $data[$k1][$k2][$k3]['code'] = $joom_account['code'];
//                                $data[$k1][$k2][$k3]['warehouse_type'] = $account_ids[$k2]['warehouse_type'];
//                                $data[$k1][$k2][$k3]['uid'] = $user['id'];
//                                $data[$k1][$k2][$k3]['realname'] = $user['realname'];
//                                $data[$k1][$k2][$k3]['shop'] = $shops;
//                            }
//                        }
//                    }
//                }
//
//            }
//            Db::commit();
//        }catch (\Exception $exception) {
//            //错误信息
//            Db::rollback();
//            print_r($exception->getMessage());
//        }
//
//        $re = [];
//        foreach ($data as $val){
//            foreach ($val as $va){
//                foreach ($va as $v){
//                    $re[] = $v;
//                }
//            }
//        }
//        return $re;
    }
}
