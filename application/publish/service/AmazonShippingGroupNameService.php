<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2019/2/26
 * Time: 17:07
 */

namespace app\publish\service;


use app\common\model\amazon\AmazonAccount;
use app\common\model\amazon\AmazonShippingGroupName as GroupNameModel;
use app\common\service\Common;
use app\common\traits\User;
use app\common\model\User as UserModel;
use think\Exception;
use think\Request;

class AmazonShippingGroupNameService
{
    use User;

    private $model = null;

    protected $lang = 'zh';

    public function __construct()
    {
        $this->model = new GroupNameModel();
    }


    /**
     * 设置刊登语言
     * @param $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }


    /**
     * 获取刊登语言
     * @return string
     */
    public function getLang()
    {
        return $this->lang ?? 'zh';
    }


    public function getCondition(Request $request)
    {
        $data = $request->get();
        $where = ['status' => 1];
        if (!empty($data['account_id'])) {
            $where['account_id'] = $data['account_id'];
        }
        if (!empty($data['create_id'])) {
            $where['create_id'] = $data['create_id'];
        }

        return $where;
    }


    /**
     * @param Request $request
     * @return array
     */
    public function lists(Request $request) : array
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 50);

        $where = $this->getCondition($request);
        $count = $this->model->where($where)->count();

        $result = [
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'data' => []
        ];
        if (empty($count)) {
            return $result;
        }

        $list = $this->model->where($where)->select();

        $aids = [];
        $uids = [];
        foreach ($list as $val) {
            $uids[] = $val['create_id'];
            $uids[] = $val['update_id'];
            $aids[] = $val['account_id'];
        }

        $users = UserModel::where(['id' => ['in', $uids]])->column('realname', 'id');
        $accounts = AmazonAccount::where(['id' => ['in', $aids]])->column('code', 'id');

        $new = [];
        foreach ($list as $val) {
            $tmp = $val->toArray();
            $tmp['code'] = $accounts[$val['account_id']] ?? '-';
            $tmp['create_name'] = $users[$val['create_id']] ?? '-';
            $tmp['update_name'] = $users[$val['update_id']] ?? '-';
            $new[] = $tmp;
        }
        $result['data'] = $new;
        return $result;
    }


    /**
     * @param $account_id
     * @return array
     * @throws Exception
     */
    public function read($account_id) : array
    {
        if (!$account_id) {
            if ($this->lang == 'zh') {
                throw new Exception('帐号参数为空');
            } else {
                throw new Exception('Params Error');
            }
        }
        $list = $this->model->where(['account_id' => $account_id, 'status' => 1])->field('group_name label,id value')->select();
        return $list;
    }


    /**
     * @param $account_id
     * @return bool
     * @throws Exception
     */
    public function edit($params) : array
    {
        $user = Common::getUserInfo();
        $time = time();
        $data['account_id'] = $params['account_id'];
        $data['group_name'] = $params['group_name'];
        $data['update_id'] = $user['user_id'];
        $data['update_time'] = $time;
        if (!empty($params['id'])) {
            $old = $this->model->where(['id' => $params['id'], 'status' => 1])->find();
            if (!empty($old) && $old['account_id'] != $params['account_id']) {
                if ($this->lang == 'zh') {
                    throw new Exception('新的帐号和旧数据的帐号ID对不上,禁止更新别的帐号的模板名');
                } else {
                    throw new Exception('Params Error');
                }
            }
            $this->model->update($data, ['id' => $params['id']]);
            $result = ['value' => (int)$params['id'], 'lable' => $data['group_name']];
        } else {
            $data['create_id'] = $user['user_id'];
            $data['create_time'] = $time;
            $id = $this->model->insertGetId($data);
            $result = ['value' => $id, 'lable' => $data['group_name']];
        }
        return $result;
    }


    public function delete($id) : bool
    {
        $old = $this->model->where(['id' => $id])->find();
        if (empty($old['status'])) {
            if ($this->lang == 'zh') {
                throw new Exception('删除的数据不存在');
            } else {
                throw new Exception('Params Error');
            }
        }
        $user = Common::getUserInfo();
        $time = time();
        $data['update_id'] = $user['user_id'];
        $data['update_time'] = $time;
        $data['status'] = 0;
        $this->model->update($data, ['id' => $id]);
        return true;
    }

}