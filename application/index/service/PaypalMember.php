<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/29
 * Time: 15:12
 */

namespace app\index\service;

use think\Exception;
use think\Request;
use app\common\model\UserLog;
use app\common\model\account\PaypalMember as Model;
use app\common\model\paypal\PaypalAccount;
use app\common\service\Common as CommonService;

class PaypalMember
{

    public $model = null;

    public function __construct()
    {
        empty($this->model) && $this->model = new Model();
    }


    /**
     * 获取成员列表
     * @param $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function getMemberList($id)
    {
        $request = Request::instance();
        $params = $request->param();

        $order = 'paypal_member.id';
        $sort = 'desc';
        $sortArr = [
            'create_time'     => 'paypal_member.create_time',
        ];
        if (!empty($params['order_by']) && !empty($sortArr[$params['order_by']])) {
            $order = $sortArr[$params['order_by']];
        }
        if (!empty($params['sort']) && in_array($params['sort'], ['asc', 'desc'])) {
            $sort = $params['sort'];
        }

        $where = $this->getWhere($params);
        $where['paypal_member.paypal_account_id'] = $id;

        $field = 'paypal_member.*,user.username,user.realname';
        $count = $this->model->where($where)
            ->join('user','paypal_member.user_id=user.id')
            ->count();
        $list  = $this->model->where($where)
            ->join('user','paypal_member.user_id=user.id')
            ->field($field)
            ->order($order, $sort)
            ->select();

        $userLog = new UserLog();
        foreach ($list as $key => $item) {
            $list[$key]['department'] = $userLog->getDepartmentNameAttr('', ['operator_id' => $item['user_id']]);
            $list[$key]['create_time'] = date('Y-m-d H:i:s',$item['create_time']);
        }
        $result = [
            'data' => $list,
            'count' => $count,
        ];
        return $result;
    }


    /**
     * 添加paypal账号使用成员
     * @param array $idArr
     * @param $id
     * @return bool
     * @throws Exception
     */
    public function addPaypalMember(array $idArr, $id)
    {
        $paypalInfo = (new PaypalAccount)->field('id,server_id')->find(['id'=>$id]);

        if (!$paypalInfo) {
            throw new Exception("数据不存在");
        }

        $memberInfo = $this->model->field('user_id')->where(['paypal_account_id'=>$id])->select();

        $delArr = [];
        $togetherArr = [];
        if ($memberInfo) {
            foreach ($memberInfo as $item) {
                if (in_array($item->user_id,$idArr)) {
                    array_push($togetherArr,$item->user_id);
                } else {
                    array_push($delArr,$item->user_id);
                }
            }
        }

        $user = CommonService::getUserInfo();
        $newArr = array_diff($idArr,$togetherArr);
        if ($newArr) {
            $time = time();
            $data = [];
            foreach ($newArr as $k=>$v) {
                $data[$k]['user_id'] = $v;
                $data[$k]['paypal_account_id'] = $id;
                $data[$k]['create_id'] = $user['user_id'];
                $data[$k]['create_time'] = $time;
            }
            if (!empty($data)) {
                $result = $this->model->saveAll($data, false);
                if (!$result) {
                    throw new Exception($result);
                }
            }
        }

        if ($delArr) {
            $this->model->where(['user_id'=>['in',implode(',',$delArr)]])->delete();
        }

        (new ManagerServer())->setAuthorizationAll($paypalInfo->server_id,$newArr,$delArr,$user);

        return true;
    }

    /**
     * 生成查询条件
     * @param $params
     * @return array
     */
    public function getWhere($params)
    {
        $where = [];
        if (isset($params['snType']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'username':
                    $where['username'] = ['EQ', $params['snText']];
                    break;
                case 'realname':
                    $where['realname'] = ['EQ', $params['snText']];
                    break;
                default:
                    break;
            }
        }
        return $where;
    }












}