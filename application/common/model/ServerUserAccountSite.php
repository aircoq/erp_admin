<?php

namespace app\common\model;

use think\Db;
use think\Exception;
use think\Model;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/8/22
 * Time: 17:46
 */
class ServerUserAccountSite extends Model
{

    /**
     * 服务器渠道账号人员关系表
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * @param $where
     * @return array|bool|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isHas($where)
    {
        $result = $this->where($where)->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return $result;
    }

    /**
     *
     * @param $data
     * @return bool|false|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add($data)
    {
        $data['site'] = strtolower($data['site']);
        $where = [
            'account_id' => $data['account_id'],
            'relation_module' => $data['relation_module'],
            'site' => $data['site'],
            'user_id' => $data['user_id']
        ];
        $time = time();
        $saveData['update_time'] = $time;
        $saveData['cookie'] = $data['cookie'];
        $saveData['session_id'] = $data['session_id'];
        $saveData['is_account'] = $data['is_account'];
        $status = (new ServerUserAccountSite())->save($saveData, $where);
        if ($status === 0) {
            $data['update_time'] = $time;
            $data['create_time'] = $time;
            $status = (new ServerUserAccountSite())
                ->allowField(true)
                ->isUpdate(false)
                ->save($data);
        }
        return $status;
    }

    public function getCookie($data)
    {
        $where = [
            'account_id' => $data['id'],
            'relation_module' => 9,
            'site' => $data['site'],
        ];
        $cookie = $this->where($where)->order('id asc')->value('cookie');
        if ($cookie) {
            return json_decode($cookie, true);
        } else {
            $where['relation_module'] = 0;
            $cookie = $this->where($where)->order('id asc')->value('cookie');
            if ($cookie) {
                return json_decode($cookie, true);
            }
        }
        return [];
    }


}