<?php
namespace app\index\service;

use app\api\service\Base;
use app\common\model\PurchaseSubclassMap;
use app\common\model\TeamPurchaseMap;
use app\common\model\DeveloperTeam;
use think\Request;
use think\Db;
use think\Exception;
use app\common\exception\JsonErrorException;
use app\common\service\Common as CommonService;
use app\common\model\Category;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/5/26
 * Time: 20:23
 */
class PurchaseSubclassMapService extends Base
{
    protected $purchaseSubclassMapModel;

    public function __construct()
    {
        if (is_null($this->purchaseSubclassMapModel)) {
            $this->purchaseSubclassMapModel = new PurchaseSubclassMap();
        }
    }

    /** 内容列表
     * @param $params
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function mapList($params,$page,$pageSize)
    {
        $where = [];
        $group = false;
        if(isset($params['pid']) && !empty($params['pid'])){
            $categoryArr = [];
            $categoryModel = new Category();
            $categoryList = $categoryModel->field('id')->where(['pid' => $params['pid']])->select();
            foreach($categoryList as $key => $value){
                array_push($categoryArr,$value['id']);
            }
            $where['category_id'] = ['in',$categoryArr];
            $group = true;
        }
        if (isset($params['category_id']) && !empty($params['category_id'])) {
            $where['category_id'] = ['=', $params['category_id']];
        }
        if (isset($params['purchase_id']) && !empty($params['purchase_id'])) {
            $where['purchase_id'] = ['=', $params['purchase_id']];
        }
        $field = 'p.id,category_id,c.name as category_name,u.realname as purchase_name,purchase_id,p.create_time';
        $count = $this->purchaseSubclassMapModel->field($field)->where($where)->count();
        $list = $this->purchaseSubclassMapModel->alias('p')->field($field)->join('category c', 'c.id = p.category_id',
            'left')->join('user u', 'u.id = p.purchase_id',
            'left')->where($where)->order('p.create_time desc')->page($page,
            $pageSize)->select();
        if($group){
            $newList = [];
            foreach($list as $k => $v){
                $teamPurchaseMapModel = new TeamPurchaseMap();
                $teamInfo = $teamPurchaseMapModel->where(['purchase_subclass_id' => $v->id])->find();
                $v['developer_name'] = '';
                if(empty($teamInfo)){
                    $v['is_bind'] = 0;
                }else{
                    $v['is_bind'] = 1;
                    $developerTeamModel = new DeveloperTeam();
                    $developerInfo = $developerTeamModel->field('name')->where(['id' => $teamInfo['team_id']])->find();
                    if(!empty($developerInfo)){
                        $v['developer_name'] = $developerInfo['name'];
                    }
                }
                array_push($newList,$v);
            }
            $list = $newList;
        }
        $result = [
            'data' => $list,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    /** 新增记录
     * @param $data
     * @return bool
     * @throws JsonErrorException
     * @throws \Exception
     */
    public function add($data)
    {
        $detail = json_decode($data, true);
        $groupDetail = [];
        $user = CommonService::getUserInfo();
        foreach ($detail as $k => $v) {
            $temp['category_id'] = $v['category_id'];
            $temp['purchase_id'] = $v['purchase_id'];
            $temp['create_time'] = time();
            $temp['update_time'] = time();
            if (!empty($user)) {
                $temp['creator_id'] = $user['user_id'];
            }
            //检查采购员是否已经绑定过子类了
            $mapInfo = $this->purchaseSubclassMapModel->where([
                'category_id' => $temp['category_id']
            ])->find();
            if (!empty($mapInfo)) {
                throw new JsonErrorException('分类已被其他采购员绑定', 500);
            }
            array_push($groupDetail, $temp);
        }
        //启动事务
        Db::startTrans();
        try {
            $this->purchaseSubclassMapModel->allowField(true)->isUpdate(false)->saveAll($groupDetail);
            Db::commit();
            return true;
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
        $user = CommonService::getUserInfo();
        //启动事务
        Db::startTrans();
        try {
            $data['update_time'] = time();
            if (!empty($user)) {
                $data['updater_id'] = $user['user_id'];
            }
            $this->purchaseSubclassMapModel->allowField(true)->isUpdate(true)->where(['id' => $id])->update($data);
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage(), 500);
        }
    }

    /** 删除
     * @param $id
     * @return array
     */
    public function delete($id)
    {
        //启动事务
        Db::startTrans();
        try {
            $this->purchaseSubclassMapModel->where(['id' => $id])->delete();
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage(), 500);
        }
    }

    /** 读取信息
     * @param $id
     * @return array
     */
    public function info($id)
    {
        $infoList = $this->purchaseSubclassMapModel->field('id,category_id,purchase_id')->where(['id' => $id])->find();
        return $infoList;
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
                    throw new JsonErrorException('请至少选择一条记录', 400);
                }
                $data = json_decode($data, true);
                Db::startTrans();
                try {
                    foreach ($data as $key => $value) {
                        $this->purchaseSubclassMapModel->where(['id' => $value])->delete();
                    }
                    Db::commit();
                    return true;
                } catch (Exception $e) {
                    Db::rollback();
                    throw new JsonErrorException('删除失败', 500);
                }
                break;
        }
    }
}