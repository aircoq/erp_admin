<?php
namespace app\internalletter\controller;

use app\common\controller\Base;
use app\internalletter\service\DingTalkService;
use app\internalletter\service\InternalLetterService;
use think\Request;

/**
* @module 站内信
* @title 系统公告站内信
* @url /internal-letters
* Created by PhpStorm.
*/

class InternalLetter extends Base
{
    /**
     * @title 发送站内信
     * @method post
     * @url /internal-letters
     * @param Request $request
     */
    public function sendLetter(Request $request)
    {
        $params = $request->param();
        $title = $request->post('title', 0);
        if (empty($title)) {
            return json(['message' => '标题为必须'], 400);
        }
        $content = $request->post('content', 0);
        if (empty($content)) {
            return json(['message' => '内容为必须'], 400);
        }
        $type = $request->post('type', 1);
        if (empty($type)) {
            return json(['message' => '类型为必须'], 400);
        }
        $receive_ids = $request->post('receive_ids');
        $params['receive_ids'] = json_decode($receive_ids, true);
        if (empty($params['receive_ids'])) {
            return json(['message' => '收件人为必须'], 400);
        }

        $response=(new InternalLetterService())->sendLetter( $params );
        if ($response == 1) {
            return json(['message' => '发送成功']);
        }
        if($response == 2){
            return json(['message' => '发送者为必须'], 400);
        }
        return json(['message' => '发送失败'], 400);
    }

    /**
     * @title 发钉钉工作消息
     * @url message
     * @method get
     * @param Request $request
     */
    public function message(Request $request)
    {
        $params = $request->param();

        (new InternalLetterService())->sendMessage( $params );
    }

    /**
     * @title 保存到草稿箱
     * @url draftbox
     * @method post
     * @param Request $request
     */
    public function saveToDraftbox(Request $request)
    {
        $params = $request->param();

        $receive_ids = $request->post('receive_ids');
        $params['receive_ids'] = json_decode($receive_ids, true);

        $response=(new InternalLetterService())->saveToDraftbox( $params );
        if ($response) {
            return json(['message' => '保存成功']);
        }
        return json(['message' => '保存失败'], 400);
    }

    /**
     * @title 收件箱
     * @url received-letters
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function receivedLetters(Request $request)
    {
        $params = $request->param();
        $page = $request->get('page', 0);
        $pageSize = $request->get('pageSize', 10);

        $result = (new InternalLetterService())->pullReceivedLetters( $params, $page, $pageSize);
        return json($result);
    }

    /**
     * @title 发件箱
     * @url sent-letter
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function sentLetters(Request $request)
    {
        $params = $request->param();
        $page = $request->get('page', 0);
        $pageSize = $request->get('pageSize', 10);

        $result = (new InternalLetterService())->pullSentLetters( $params, $page, $pageSize);
        return json($result);
    }

    /**
     * @title 草稿箱
     * @url draft
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function draft(Request $request)
    {
        $params = $request->param();
        $page = $request->get('page', 0);
        $pageSize = $request->get('pageSize', 10);

        $result = (new InternalLetterService())->draftLetters( $params, $page, $pageSize);
        return json($result);
    }

    /**
     * @title 草稿编辑
     * @url draft-edit
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function draftEdit(Request $request)
    {
        $params = $request->param();

        $letter_id = $request->get('letter_id', 0);
        if (empty($letter_id)) {
            return json(['message' => 'letter_id 必须'], 400);
        }

        $result = (new InternalLetterService())->draftEdit( $params );
        return json($result);
    }

    /**
     * @title 草稿箱批量发送
     * @url batch-send
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function batchSend(Request $request)
    {
        $params = $request->param();

        $letter_ids = $request->post('letter_ids/a', 0);
        if (!is_array($letter_ids) OR !count($letter_ids)) {
            return json(['message' => '站内信id为必须'], 400);
        }

        $result = (new InternalLetterService())->batchSend( $params );
        if ($result == 1) {
            return json(['message'=>'发送成功'], 200);
        }
        if ($result == 2){
            return json(['message' => '“收件人”，“标题” 不能为空'], 400);
        }
        return json(['message' => '发送失败'], 400);
    }

    /**
     * @title 草稿箱批量删除
     * @url draft-delete
     * @method delete
     * @param Request $request
     * @return \think\response\Json
     */
    public function draftDelete(Request $request)
    {
        $params = $request->param();

        $letter_ids = $request->delete('letter_ids/a', 0);
        if (!is_array($letter_ids) && !count($letter_ids)) {
            return json(['message' => '站内信id为必须'], 400);
        }

        $result = (new InternalLetterService())->draftDelete( $params );
        if ($result) {
            return json(['message' => '删除成功']);
        }
        return json(['message' => '删除失败'], 400);
    }

    /**
     * @title 看收信
     * @url view-letter
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function viewReceivedLetter(Request $request)
    {
        $params = $request->param();

        $letter_id = $request->get('letter_id', 0);
        if (empty($letter_id)) {
            return json(['message' => 'letter_id 必须'], 400);
        }

        $result = (new InternalLetterService())->viewReceivedLetter( $params );
        return json($result);
    }

    /**
     * @title 看发信
     * @url view-sent-letter
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function viewSentLetter(Request $request)
    {
        $params = $request->param();

        $letter_id = $request->get('letter_id', 0);
        if (empty($letter_id)) {
            return json(['message' => 'letter_id 必须'], 400);
        }

        $result = (new InternalLetterService())->viewSentLetter( $params );
        if ($result) {
            return json($result);
        }
        return json(['message' => '查看发信失败'], 400);
    }

    /**
     * @title 设置已读
     * @url read
     * @method put
     * @param Request $request
     */
    public function setRead(Request $request)
    {
        $params = $request->param();

        $letter_ids = $request->put('letter_ids/a', 0);
        if (!is_array($letter_ids) OR !count($letter_ids)) {
            return json(['message' => '站内信id为必须'], 400);
        }

        $response=(new InternalLetterService())->setRead( $params );
        if ($response) {
            return json(['message' => '已标记为已读']);
        }
        return json(['message' => '已读失败'], 400);
    }

    /**
     * @title 全部已读
     * @url all-read
     * @method put
     * @param Request $request
     */
    public function setAllRead(Request $request)
    {
        $params = $request->param();
        $response=(new InternalLetterService())->setAllRead( $params );

        if ($response) {
            return json(['message' => '已全部标记为已读']);
        }
        return json(['message' => '全部已读失败'], 400);
    }


    /**
     * @title 看草稿
     * @url view-draft
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function viewDraft(Request $request)
    {
        $params = $request->param();

        $letter_id = $request->get('letter_id', 0);
        if (empty($letter_id)) {
            return json(['message' => 'letter_id 必须'], 400);
        }

        $result = (new InternalLetterService())->viewDraft( $params );

        if ($result) {
            return json($result);
        }
        return json(['message' => '查看草稿失败'], 400);
    }

    /**
     * @title 删除收信
     * @url delete-received-letters
     * @method delete
     * @param Request $request
     */
    public function deleteReceivedLetters(Request $request)
    {
        $params = $request->param();

        $letter_ids = $request->delete('letter_ids/a', 0);
        if (!is_array($letter_ids) OR !count($letter_ids)) {
            return json(['message' => '站内信id为必须'], 400);
        }

        $response=(new InternalLetterService())->deleteReceivedLetters( $params );

        if ($response) {
            return json(['message' => '删除成功']);
        }
        return json(['message' => '删除失败'], 400);
    }

    /**
     * @title 删除发信
     * @url delet-sent-letters
     * @method post
     * @param Request $request
     */
    public function deletSentLetters(Request $request)
    {
        $params = $request->param();

        $letter_ids = $request->post('letter_ids/a', 0);
        if (!is_array($letter_ids) OR !count($letter_ids)) {
            return json(['message' => '站内信id为必须'], 400);
        }

        $response=(new InternalLetterService())->deletSentLetters( $params );

        if ($response) {
            return json(['message' => '删除成功']);
        }
        return json(['message' => '删除失败'], 400);
    }

    /**
     * @title 获取所有站内信类型
     * @url type
     * @method get
     */
    public function type()
    {
        $result = (new InternalLetterService())->getAllType();
        return json($result);
    }

    /**
     * @title 获取所有用户信息
     * @url user-info
     * @method get
     */
    public function userInfo()
    {
        $result = (new InternalLetterService())->getAllUserInfo();
        return json($result);
    }

    /**
     * @title 下载附件
     * @method get
     * @url attachment
     */
    public function attachment( Request $request ){
        $params = $request->param();

        $full_name = $request->get('full_name', 0);
        if (empty($full_name)) {
            return json(['message' => '文件名必须'], 400);
        }
        $result = (new InternalLetterService())->downAttachment( $params['full_name'] );
        return json($result);
    }

    //@noauth
    /**
     * @title 新站内信通知
     * @method get
     * @url notification
     */
    public function notification( Request $request ){

        $result = (new InternalLetterService())->notification();
        return json($result);
    }

    /**
     * @title 发送钉钉群消息
     * @method post
     * @url chat
     */
    public function send_chat_message(Request $request)
    {
        $params = $request->param();

        $chat_id = $request->post('chat_id', 0);
        $content = $request->post('content', '');

        if (empty($chat_id)) {
            return json(['message' => '群id为必须'], 400);
        }
        if (empty($content)) {
            return json(['message' => '内容为必须'], 400);
        }
        $result = (new DingTalkService())->send_chat_message_post($params);
        return json($result);
    }

    /**
     * @title 保存联系人模板
     * @method post
     * @url templates
     * @param Request $request
     * @return \think\response\Json
     */
    public function saveTemplate(Request $request)
    {
        $params = $request->param();

        $template_name = $request->post('template_name', '');
        $member_list = $request->post('member_list', '');

        if (empty($template_name)) {
            return json(['message' => '模板名称为必须'], 400);
        }
        if (empty($member_list)) {
            return json(['message' => '成员列表为必须'], 400);
        }
        $result = (new InternalLetterService())->saveTemplate($params);
        return json($result);
    }

    /**
     * @title 删除联系人模板
     * @method delete
     * @url templates
     * @param Request $request
     * @return \think\response\Json
     */
    public function deleteTemplate(Request $request)
    {
        $params = $request->param();

        $template_id = $request->delete('template_id', '');

        if (empty($template_id)) {
            return json(['message' => '模板id为必须'], 400);
        }
        $result = (new InternalLetterService())->deleteTemplate($template_id);
        return json($result);
    }

    /**
     * @title 获取联系人模板
     * @method get
     * @url templates
     * @param Request $request
     * @return \think\response\Json
     */
    public function getTemplate()
    {
        $result = (new InternalLetterService())->getTemplate();
        return json($result);
    }

    /**
     * @title 获取联系人模板详情
     * @method get
     * @url templates—detail
     * @param Request $request
     * @return \think\response\Json
     */
    public function getTemplateDetail(Request $request)
    {
        $template_id = $request->get('template_id', '');
        if (empty($template_id)) {
            return json(['message' => '模板id为必须'], 400);
        }

        $result = (new InternalLetterService())->getTemplateDetail($template_id);
        return json($result);
    }

    /**
     * @title 搜索已添加用户
     * @method get
     * @url user-templates
     * @param Request $request
     * @return \think\response\Json
     */
    public function searchUserInTemplate(Request $request)
    {
        $user_id = $request->get('user_id', '');

        if (empty($user_id)) {
            return json(['message' => '用户id为必须'], 400);
        }
        $result = (new InternalLetterService())->searchUserInTemplate($user_id);
        return json($result);
    }
}