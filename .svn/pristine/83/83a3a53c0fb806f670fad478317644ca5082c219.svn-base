<?php
namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\Category;
use app\common\model\ChannelUserAccountMap;
use app\common\model\DeveloperTeam;
use app\common\model\TeamChannelUserAccountMap;
use app\common\model\PurchaseSubclassMap;
use app\common\model\TeamPurchaseMap;
use think\Db;
use think\Exception;
use app\common\model\Goods;
use app\common\cache\Cache;
use think\Request;
use app\order\service\OrderService;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/3/3
 * Time: 11:41
 */
class DeveloperService
{
    use \app\common\traits\User;
    protected $developerTeamModel;
    protected $teamChannelUserAccountMapModel;
    protected $teamPurchaseMapModel;

    /** 构造函数
     * DeveloperService constructor.
     */
    public function __construct()
    {
        if (is_null($this->developerTeamModel)) {
            $this->developerTeamModel = new DeveloperTeam();
        }
        if (is_null($this->teamChannelUserAccountMapModel)) {
            $this->teamChannelUserAccountMapModel = new TeamChannelUserAccountMap();
        }
        if (is_null($this->teamPurchaseMapModel)) {
            $this->teamPurchaseMapModel = new TeamPurchaseMap();
        }
    }

    /** 开发分组列表
     * @param array $where
     * @param $page
     * @param $pageSize
     * @return array
     * @throws Exception
     */
    public function developerList(array $where,$page,$pageSize)
    {
        $field = 'a.id,a.name,a.category_id as category,a.channel_id,a.company_id,a.developer_id as developer,a.update_time,c.realname,d.name as category_name';
        $count = $this->developerTeamModel->alias('a')->field($field)
            ->join('user c', 'a.developer_id = c.id', 'left')
            ->join('category d', 'a.category_id = d.id', 'left')
            ->where($where)
            ->count();
        $list = $this->developerTeamModel->alias('a')->field($field)
            ->join('user c', 'a.developer_id = c.id', 'left')
            ->join('category d', 'a.category_id = d.id', 'left')
            ->where($where)
            ->order('a.create_time desc')->page($page, $pageSize)->select();
        $result = [];
        foreach ($list as $k => $v) {
            $subclass = '';
            $categoryList = $this->teamPurchaseMapModel->alias('t')->join('purchase_subclass_map p',
                't.purchase_subclass_id = p.id', 'left')->where(['team_id' => $v['id']])->select();
            foreach ($categoryList as $cate => $value) {
                $cate = Cache::store('category')->getCategory($value['category_id']);
                if (!empty($cate)) {
                    $subclass .= isset($cate['name']) ? $cate['name'] . ',' : '';
                }
            }
            $subclass = rtrim($subclass, ',');
            $v['subclass'] = $subclass;
            $v['category'] = $v['category_name'];
            $v['developer'] = $v['realname'];
            unset($v['realname']);
            unset($v['category_name']);
            array_push($result, $v);
        }
        $result = [
            'data' => $result,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    /** 读取分组信息
     * @param $id 【分组id】
     * @return array
     */
    public function read($id)
    {
        $shipService = new MemberShipService();
        $purchaseSubclassMapModel = new PurchaseSubclassMap();
        $channelUserAccountMapModel = new ChannelUserAccountMap();
        $detail = [];
        $developerInfo = $this->developerTeamModel->field('id,name,category_id,developer_id,channel_id,company_id')->with('detail,subclass')->where(['id' => $id])->select();
        foreach ($developerInfo as $k => $v) {
            $subclass = [];
            if (isset($v['subclass'])) {
                $subclass_id = [];
                foreach ($v['subclass'] as $key => $value) {
                    array_push($subclass_id, $value->purchase_subclass_id);
                }
                //查询记录信息
                $subclassArr = $purchaseSubclassMapModel->field('id,category_id,purchase_id')->where('id', 'in', $subclass_id)->select();
                foreach($subclassArr as $sub => $class){
                    $class = $class->toArray();
                    $userInfo = $this->getUser($class['purchase_id']);
                    if (!empty($userInfo)) {
                        $class['purchase_name'] = $userInfo['realname'];
                    }
                    $categoryInfo = Category::get($class['category_id']);
                    if(!empty($categoryInfo)){
                        $class['category_name'] = $categoryInfo['name'];
                    }
                    array_push($subclass,$class);
                }
            }
            $newDetail = [];
            if (isset($v['detail'])) {
                $detail_id = [];
                foreach ($v['detail'] as $key => $value) {
                    //查询记录
                    array_push($detail_id, $value->channel_user_account_id);
                }
                $newDetail = $channelUserAccountMapModel->field('id,channel_id,account_id,seller_id,warehouse_type,customer_id')->where('id',
                    'in', $detail_id)->select();
            }
            $v['detail'] = $shipService->merge($newDetail, true);
            $v['subclass'] = $subclass;
            $detail = $v;
        }
        return $detail;
    }

    /** 新增记录
     * @param $data
     * @return \think\response\Json
     * @throws \Exception
     */
    public function add($data)
    {
        $data['create_time'] = time();
        $data['update_time'] = time();
        $category = [];    //分类信息
        $subclass = json_decode($data['subclass'], true);
        $purchaseSubclassMapModel = new PurchaseSubclassMap();
        $channelUserAccountMapModel = new ChannelUserAccountMap();
        //启动事务
        Db::startTrans();
        try {
            $this->developerTeamModel->allowField(true)->isUpdate(false)->save($data);
            $detail = json_decode($data['detail'], true);
            $team_id = $this->developerTeamModel->id;
            $subclassDetail = [];
            foreach ($subclass as $k => $v) {
                $temp = [];
                $temp['team_id'] = $team_id;
                $temp['purchase_subclass_id'] = $v;
                $temp['create_time'] = time();
                $info = $purchaseSubclassMapModel->where(['id' => $v])->find();
                if (empty($info)) {
                    throw new JsonErrorException('子类采购员关系记录不存在', 500);
                }
                //同一个公司，同一个平台,同一个大类
                $isHas = $this->developerTeamModel->checkSubclass($data, $v);
                if (!$isHas) {
                    throw new JsonErrorException('采购员分类记录已被其他组绑定', 500);
                }
                array_push($subclassDetail, $temp);
            }
            $this->teamPurchaseMapModel->allowField(true)->isUpdate(false)->saveAll($subclassDetail);
            $groupDetail = [];
            foreach ($detail as $key => $value) {
                $idArr = explode('_', $value);
                foreach ($idArr as $k => $v) {
                    $temp = [];
                    $info = $channelUserAccountMapModel->where(['id' => $v])->find();
                    if (empty($info)) {
                        throw new JsonErrorException('成员关系记录不存在', 500);
                    }
                    //同一个公司，同一个平台
                    $isHas = $this->developerTeamModel->checkMemberShip($data, $v);
                    if (!$isHas) {
                        $orderService = new OrderService();
                        $channel_name = Cache::store('channel')->getChannelName($info['channel_id']);
                        $account_name = $orderService->getAccountName($info['channel_id'], $info['account_id']);
                        throw new JsonErrorException($channel_name . '渠道' . $account_name . '账号已经被其他记录绑定了！', 500);
                    }
                    $temp['team_id'] = $team_id;
                    $temp['channel_user_account_id'] = $v;
                    $temp['create_time'] = time();
                    array_push($groupDetail, $temp);
                }
            }
            $this->teamChannelUserAccountMapModel->isUpdate(true)->saveAll($groupDetail);
            //推送到产品里
            $this->push($category, $team_id);
            Db::commit();
            return $this->developerList(['a.id' => $team_id],0,100)['data'];
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage(), 500);
        }
    }

    /** 更新
     * @param $data
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function update($data, $id)
    {
        $category = [];    //分类信息
        $data['update_time'] = time();
        $subclass = json_decode($data['subclass'], true);
        $purchaseSubclassMapModel = new PurchaseSubclassMap();
        $channelUserAccountMapModel = new ChannelUserAccountMap();
        //启动事务
        Db::startTrans();
        try {
            //操作原来的分组详情信息
            $this->teamChannelUserAccountMapModel->where(['team_id' => $id])->delete();
            $this->teamPurchaseMapModel->where(['team_id' => $id])->delete();
            //更新
            $this->developerTeamModel->allowField(true)->isUpdate(true)->save($data, ['id' => $id]);
            $detail = json_decode($data['detail'], true);
            //新增子类与采购员的关系
            $subclassDetail = [];
            foreach ($subclass as $k => $v) {
                $temp = [];
                $temp['team_id'] = $id;
                $temp['purchase_subclass_id'] = $v;
                $temp['create_time'] = time();
                $info = $purchaseSubclassMapModel->where(['id' => $v])->find();
                if (empty($info)) {
                    throw new JsonErrorException('子类采购员关系记录不存在', 500);
                }
                //同一个公司，同一个平台,同一个大类
                $isHas = $this->developerTeamModel->checkSubclass($data, $v, true);
                if (!$isHas) {
                    throw new JsonErrorException('采购员分类记录已被其他组绑定', 500);
                }
                array_push($subclassDetail, $temp);
            }
            $this->teamPurchaseMapModel->allowField(true)->isUpdate(false)->saveAll($subclassDetail);
            $groupDetail = [];
            foreach ($detail as $key => $value) {
                $idArr = explode('_', $value);
                foreach ($idArr as $k => $v) {
                    $temp = [];
                    $info = $channelUserAccountMapModel->where(['id' => $v])->find();
                    if (empty($info)) {
                        throw new JsonErrorException('成员关系记录不存在', 500);
                    }
                    //同一个公司，同一个平台
                    $isHas = $this->developerTeamModel->checkMemberShip($data, $v, true);
                    if (!$isHas) {
                        $orderService = new OrderService();
                        $channel_name = Cache::store('channel')->getChannelName($info['channel_id']);
                        $account_name = $orderService->getAccountName($info['channel_id'], $info['account_id']);
                        throw new JsonErrorException($channel_name . '渠道' . $account_name . '账号已经被其他记录绑定了！', 500);
                    }
                    $temp['team_id'] = $id;
                    $temp['channel_user_account_id'] = $v;
                    $temp['create_time'] = time();
                    array_push($groupDetail, $temp);
                }
            }
            $this->teamChannelUserAccountMapModel->isUpdate(true)->saveAll($groupDetail);
            //推送到产品里
            $this->push($category, $id);
            Db::commit();
            return $this->developerList(['a.id' => $id],0,100)['data'];
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException('更新失败', 500);
        }
    }

    /** 更新到产品表中
     * @param $category
     * @param $team_id
     */
    private function push($category, $team_id)
    {
        $goodsModel = new Goods();
        if(!empty($category)){
            $where['category_id'] = ['in', $category];
            $data['developer_group_id'] = $team_id;
            $goodsModel->where($where)->update($data);
        }
    }

    /** 删除
     * @param $id
     * @return array
     */
    public function delete($id)
    {
        if (!$this->developerTeamModel->isHas($id)) {
            throw new JsonErrorException('该分组不存在', 400);
        }
        //启动事务
        Db::startTrans();
        try {
            //删除分组
            $this->developerTeamModel->where(['id' => $id])->delete();
            //分组详情
            $this->teamChannelUserAccountMapModel->where(['team_id' => $id])->delete();
            //删除分组详情
            $this->teamPurchaseMapModel->where(['team_id' => $id])->delete();
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new JsonErrorException('删除失败', 500);
        }
    }

    /** 批量删除
     * @param Request $request
     * @return \think\response\Json
     */
    public function batch(Request $request)
    {
        $params = $request->param();
        $type = $params['type'];
        switch ($type) {
            case 'delete':
                $data = $request->post('data', 0);
                if (empty($data)) {
                    throw new JsonErrorException('请至少选择一条开发者分组记录', 400);
                }
                $data = json_decode($data, true);
                Db::startTrans();
                try {
                    foreach ($data as $k => $v) {
                        if (!$this->developerTeamModel->isHas($v)) {
                            throw new JsonErrorException('该分组不存在', 400);
                            break;
                        }
                        //删除分组
                        $this->developerTeamModel->where(['id' => $v])->delete();
                        //分组详情
                        $this->teamChannelUserAccountMapModel->where(['team_id' => $v])->delete();
                        //删除分组详情
                        $this->teamPurchaseMapModel->where(['team_id' => $v])->delete();
                    }
                    Db::commit();
                    return true;
                } catch (\Exception $e) {
                    Db::rollback();
                    throw new JsonErrorException('删除失败', 500);
                }
                break;
        }
    }

    /**获取开发分组id
     * @param $category_id
     * @return int|mixed
     */
    public function getGroup($category_id)
    {
        $where['category_id'] = ['like', '%' . $category_id . '%'];
        $result = $this->developerTeamModel->where($where)->find();
        if (!empty($result)) {
            return $result['id'];
        }
        return 0;
    }

    /** 获取账号的相关负责人
     * @param $channel_id
     * @param $account_id
     * @param string $type 【'' --所有 customer --客服   seller --销售】
     * @return array
     */
    public function accountHolder($channel_id, $account_id, $type = '')
    {
        $channelUserAccount = new ChannelUserAccountMap();
        $result = [];
        if (!empty($channel_id) && !empty($account_id)) {
            $where['account_id'] = ['=', $account_id];
            $where['channel_id'] = ['=', $channel_id];
            $holderList = $channelUserAccount->field('id,seller_id,customer_id')->where($where)->select();
            $seller = [];
            $customer = [];
            foreach ($holderList as $k => $v) {
                $team_id = 0;
                $mapInfo = $this->teamChannelUserAccountMapModel->field('team_id')->where(['channel_user_account_id' => $v->id])->find();
                if (!empty($mapInfo)) {
                    $team_id = $mapInfo['team_id'];
                }
                $data = $this->userInfo($v['seller_id'], $team_id);
                if (!empty($data)) {
                    array_push($seller, $data);
                }
                $data = $this->userInfo($v['customer_id'], $team_id);
                if (!empty($data)) {
                    $customer = $data;
                }
            }
            $result['customer'] = $customer;
            $result['seller'] = $seller;
        }
        if (!empty($type)) {
            return isset($result[$type]) ? $result[$type] : [];
        }
        return $result;
    }

    /** 人员信息
     * @param $user_id
     * @param int $team_id
     * @return array
     * @throws Exception
     */
    private function userInfo($user_id, $team_id = 0)
    {
        $temp = [];
        $userInfo = Cache::store('user')->getOneUser($user_id);
        if (!empty($userInfo)) {
            $temp['realname'] = $userInfo['realname'];
            $temp['id'] = $user_id;
            $temp['team_id'] = $team_id;
        }
        return $temp;
    }


}