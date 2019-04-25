<?php
namespace app\index\controller;

use app\common\controller\Base;
use app\index\service\EmailService;
use think\Request;

/**
 * @module 基础设置
 * @title 邮箱服务器
 * @author libaimin
 * @url email-server
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/10/22
 * Time: 14:46
 */
class EmailServer extends Base
{
    protected $emailServer;
    protected function init()
    {
        if(is_null($this->emailServer)){
            $this->emailServer = new EmailService();
        }
    }

    /**
     * @title 平台自动登录列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $result = $this->emailServer->lists($request);
        return json($result, 200);
    }

    /**
     * @title 获取平台自动登录信息
     * @param $id
     * @return \think\response\Json
     */
    public function edit($id)
    {
        $result = $this->emailServer->read($id);
        return json($result, 200);
    }

    /**
     * @title 保存平台自动登录信息
     * @param Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        $data['imap_url'] = trim($request->post('imap_url',''));
        $data['imap_ssl_port'] = trim($request->post('imap_ssl_port',0));
        $data['smtp_url'] = trim($request->post('smtp_url',''));
        $data['smtp_ssl_port'] = trim($request->post('smtp_ssl_port',0));
        $data['has_code'] = trim($request->post('has_code',0));
        $id = $this->emailServer->save($data);
        return json(['message' => '新增成功','data' => $id], 200);
    }

    /**
     * @title 更新平台自动登录信息
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     */
    public function update(Request $request,$id)
    {
        $data['imap_url'] = trim($request->put('imap_url',''));
        $data['imap_ssl_port'] = trim($request->put('imap_ssl_port',0));
        $data['smtp_url'] = trim($request->put('smtp_url',''));
        $data['smtp_ssl_port'] = trim($request->put('smtp_ssl_port',0));
        $data['has_code'] = trim($request->put('has_code',0));
        $data['id'] = $id;
        unset($data['id']);
        $datas = $this->emailServer->update($id,$data);
        return json(['message' => '修改成功','data'=>$datas], 200);
    }

    /**
     * @title 删除
     * @url /email-server/:id/:account_id
     * @method delete
     */
    public function delete($id,$account_id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }

        $datas = $this->emailServer->delete($id,$account_id);
        return json(['message' => '删除成功','data'=>$datas], 200);
    }


}