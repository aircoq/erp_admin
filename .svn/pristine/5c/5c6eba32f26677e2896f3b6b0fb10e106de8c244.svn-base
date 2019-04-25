<?php
namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\Account;
use app\common\model\EmailServer as EmailServiceMode;
use app\common\cache\Cache;
use think\Request;
use think\Db;

/**
 * Created by PhpStorm.
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/10/22
 * Time: 14:46
 */
class EmailService
{
    protected $emailServer;

    public function __construct()
    {
        if (is_null($this->emailServer)) {
            $this->emailServer = new EmailServiceMode();
        }
    }

    /** 账号列表
     * @param Request $request
     * @return array
     * @throws \think\Exception
     */
    public function lists(Request $request)
    {
        $where = [];
        $params = $request->param();
//        if (isset($params['channel_id']) && $params['channel_id'] !== '') {
//            $where['channel_id'] = $params['channel_id'];
//        }
//
//        if (isset($params['channel_site']) && $params['channel_site'] !== '') {
//            $where['channel_site'] = $params['channel_site'];
//        }


        $order = 'id';
        $sort = 'desc';

        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $field = '*';
        $count = $this->emailServer->field($field)->where($where)->count();
        $accountList = $this->emailServer->field($field)
            ->where($where)
            ->order($order, $sort)
            ->page($page, $pageSize)
            ->select();
        $result = [
            'data' => $accountList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }


    /** 保存账号信息
     * @param $data
     * @return array
     */
    public function save($data)
    {
        $time = time();
        unset($data['name']);
        $data['create_time'] = $time;
        $data['update_time'] = $time;
//        if($this->emailServer->isHas()){
//            throw new JsonErrorException('账号已经存在',500);
//        }

        $this->emailServer->allowField(true)->isUpdate(false)->save($data);
        //获取最新的数据返回
        $new_id = $this->emailServer->id;
        return $this->read($new_id);
    }

    /** 账号信息
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function read($id)
    {
        $accountInfo = $this->emailServer->field(true)->where(['id' => $id])->find();
        if(empty($accountInfo)){
            throw new JsonErrorException('账号不存在',500);
        }
        return $accountInfo;
    }

    /** 更新
     * @param $id
     * @param $data
     * @return \think\response\Json
     */
    public function update($id, $data)
    {

//        $oldData = $this->emailServer->isHas();
//        if($oldData && $oldData['id'] != $id){
//            throw new JsonErrorException('账号已经存在',500);
//        }
        $data['update_time'] = time();
        $this->emailServer->allowField(true)->save($data, ['id' => $id]);
        return $this->emailServer;
    }

    /**
     * 删除信息
     * @param $id
     * @return int
     */
    public function delete($id,$account_id)
    {
        $where['id'] = ['<>',$account_id];
        $where['email_server_id'] = $id;
        $isHas = (new Account())->where($where)->value('id');
        if($isHas){
            throw new JsonErrorException('该服务器绑定了其他账号无法删除，请先更换其他账号的服务器再删除',500);
        }
        return $this->emailServer->where('id',$id)->delete();
    }
}