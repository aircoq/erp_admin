<?php
/**
 * Created by PhpStorm.
 * User: denghaibo
 * Date: 2018/12/24
 * Time: 20:57
 */

namespace app\internalletter\service;

use think\Cache;
use Exception;
use think\Loader;
use think\Config;

class DingTalkService
{

    /**
     * @return mixed
     */
    public static function getAccessToken()
    {
        if(Cache::get('expiration') > time() && !empty(Cache::get('access_token'))){
            return Cache::get('access_token');
        }else{
            $corpId = Config::get('dingding.corpId');
            $corpSecret = Config::get('dingding.corpSecret');
            $url = "https://oapi.dingtalk.com/gettoken?corpid={$corpId}&corpsecret={$corpSecret}";
            $jsoninfo = self::get($url, 'json');
            $access_token = $jsoninfo["access_token"];
            $expires_in = $jsoninfo["expires_in"];
            $expiration=$expires_in + time();
            Cache::set('expiration',$expiration,$expires_in);
            Cache::set('access_token',$access_token,$expires_in);
            return $access_token;
        }
    }

    /**
     * 群消息（暂时不能用）
     * @return mixed|\ResultSet|\SimpleXMLElement
     */
    public static function send_chat_message( $datas )
    {
        $return =[
            'ask'=>0,
            'message'=>'send_chat_message error',
            'data'=>[],
        ];
        try{
            if(!$chat_id = param($datas, 'chat_id')){
                throw new Exception('群id不能为空!');
            }
            if(!$content = param($datas, 'content')){
                throw new Exception('群消息内容不能为空!');
            }

            Loader::import('taobaosdk.TopSdk', EXTEND_PATH, '.php');
            $access_token = self::getAccessToken();

            $c = new \DingTalkClient;
            $req = new \OapiChatSendRequest();
            $req->setChatid($chat_id);
            // $req->setChatid('chat435ccb74c6ae780b37231215c97b7c01');

            $text = new \Text;
            $text->content=$content;
            $req->setMsgtype("text");
            $req->setText(json_encode($text));
            $resp = $c->execute($req, $access_token);

            $returnStr = json_encode($resp);
            $returnArray = json_decode($returnStr, true);

            if(strtolower($returnArray['errmsg']) == 'ok')
            {
                $return['message'] = 'success';
                $return['ask'] = 1;
            }else {
                $return['ask'] = 0;
                $return['message'] = 'fail';
            }

        } catch (\Exception $e) {
            $return['ask'] = 0;
            $return['message'] = $e->getMessage() . $e->getFile() . $e->getLine();
        }
        return $return;

    }

    /**
     * 群消息
     * @param $datas
     * @return mixed
     * @throws Exception
     */
    public static function send_chat_message_post( $datas )
    {
        $return =[
            'ask'=>0,
            'message'=>'send_chat_message error',
        ];

        try{
            if(!$chat_id = param($datas, 'chat_id')){
                throw new Exception('群id不能为空!');
            }
            if(!$content = param($datas, 'content')){
                throw new Exception('群消息内容不能为空!');
            }

            $access_token = self::getAccessToken();

            $url = "https://oapi.dingtalk.com/chat/send?access_token={$access_token}";
            $fields = [
                'chatid' => $chat_id,
                'msgtype' => 'text',
                'text' => [
                    'content' => $content,
                ],
            ];

            $resp = self::post($url, $fields,'json');

            $returnStr = json_encode($resp);
            $returnArray = json_decode($returnStr, true);

            if(strtolower($returnArray['errmsg']) == 'ok')
            {
                $return['message'] = 'success';
                $return['ask'] = 1;
            }else {
                $return['ask'] = 0;
                $return['message'] = 'fail';
            }

        } catch (Exception $e) {
            $return['ask'] = 0;
            $return['message'] = $e->getMessage() . $e->getFile() . $e->getLine();
        }

        return $return;
    }

    /**
     * 发送钉钉
     * @param $access_token
     * @param $dingtalk_userid
     * @param $content
     * @param $title
     * @param $send_to_all
     * @return mixed|\ResultSet|\SimpleXMLElement
     * @throws Exception
     */
    public static function send_dingtalk_message($access_token, $dingtalk_userid, $content, $title, $send_to_all)
    {
        $return =[
            'ask'=>0,
            'message'=>'send_dingtalk_message error',
            'task_id'=>'',
        ];
        try{
            Loader::import('taobaosdk.TopSdk', EXTEND_PATH, '.php');

            $c = new \DingTalkClient;
            $req = new \OapiMessageCorpconversationAsyncsendV2Request();
            $req->setAgentId(Config::get('dingding.agent_id'));
            if($send_to_all){
                $req->setToAllUser("true");
            }else{
                $req->setUseridList("$dingtalk_userid");
                //        $req->setDeptIdList("40491397");
                $req->setToAllUser("false");
            }
            $msg = new \Msg;
            $msg->msgtype = "markdown";
//            $text = new \Text;
//            $text->content = $content;
//            $msg->text = $text;

            $markdown = new \Markdown;
            $markdown->text=$content;
            $markdown->title=$title;
            $msg->markdown = $markdown;

//            $oa = new \OA;
//            $body = new \Body;
//            $body->content=$content;
//            $body->title=$title;
//            $oa->body = $body;
//            $head = new \Head;
//            $head->bgcolor="#bbbbbb";
//            $head->text="ERP消息";
//            $oa->head = $head;
//            $msg->oa = $oa;

//            $action_card = new \ActionCard;
//            $action_card->single_url="single_url";
//            $action_card->single_title=$title;
//            $action_card->markdown=$content;
//            $action_card->title="ERP消息";
//            $msg->action_card = $action_card;

            $req->setMsg(json_encode($msg));
            $resp = $c->execute($req, $access_token);

            $returnStr = json_encode($resp);
            $returnArray = json_decode($returnStr, true);

            if(strtolower($returnArray['errcode']) == 0)
            {
                $return['task_id'] = $returnArray['task_id'];
                $return['message'] = 'success';
                $return['ask'] = 1;
            }
        } catch (\Exception $e) {
            $return['ask'] = 0;
            $return['message'] = $e->getMessage() . $e->getFile() . $e->getLine();
        }

        return $return;
    }

    /**
     * 检查钉钉发送状态
     * @param $task_id
     * @return bool|string
     */
    public function check_ding_status($task_id)
    {
        $return =[
            //ask为 0表示其他原因失败，1表示发送成功，2表示发送失败
            'ask'=>0,
            'message'=>'ding_status error',
            'data'=>[],
        ];

        if(empty($task_id)){
            return false;
        }

        Loader::import('taobaosdk.TopSdk', EXTEND_PATH, '.php');
        $access_token = self::getAccessToken();

        $c = new \DingTalkClient;
        $req = new \OapiMessageCorpconversationGetsendresultRequest();
        $req->setAgentId(Config::get('dingding.agent_id'));
        $req->setTaskId($task_id);
        $resp = $c->execute($req, $access_token);
        $jsonStr = json_encode($resp);
        $returnArray = json_decode($jsonStr, true);

        if(isset($returnArray['send_result'])){
            if (!empty($returnArray['send_result']['failed_user_id_list'])){
                $failed_user_id_list = implode(',',$returnArray['send_result']['failed_user_id_list']);
                $return['ask'] = 2;
                $return['data'] = $failed_user_id_list;
                return $return;
            }
            if (!empty($returnArray['send_result']['invalid_user_id_list']) || !empty($returnArray['send_result']['invalid_dept_id_list'])){
                return $return;
            }

            $return['ask'] = 1;
            $return['data'] = [];
            $return['message'] = 'success';
            return $return;
        }else{
            return $return;
        }
    }

    /*
     * 获取部门用户userid列表
     * @return $UseridList['userIds']
     */
    private function getUseridList($deptId)
    {
        $access_token = $this->getAccessToken();
        $url = "https://oapi.dingtalk.com/user/getDeptMember?access_token={$access_token}&deptId={$deptId}";
        $UseridList = self::get($url, 'json');
        return $UseridList['userIds'];
    }

    /*
     * 获取部门用户userid和姓名列表
     * @return $UseridList['userIds']
     */
    private function getUseridAndNameList($deptId)
    {
        $access_token = $this->getAccessToken();
        $url = "https://oapi.dingtalk.com/user/simplelist?access_token={$access_token}&department_id={$deptId}";
        $UseridList = self::get($url, 'json');
        return $UseridList;
    }

    /*
     * 获取部门用户（详情）
     * @return $UseridList
     */
    public function getDepartmentUserDetails($deptId, $offset, $size)
    {
        $access_token = $this->getAccessToken();
        $url = "https://oapi.dingtalk.com/user/listbypage?access_token={$access_token}&department_id={$deptId}&offset={$offset}&size={$size}";
        $departmentUserDetails = self::get($url, 'json');
        return $departmentUserDetails;
    }

    /*
     * 获取部门列表
     * @return $departMent['department']
     */
    public function getDepartment()
    {
        $access_token = $this->getAccessToken();
        $url = "https://oapi.dingtalk.com/department/list?access_token={$access_token}";
        $departMent = self::get($url, 'json');
        return $departMent['department'];
    }

    /*
     * 获取用户详情
     * @return $userDetails
     */
    private function getUserDetail($userid)
    {
        $access_token = $this->getAccessToken();
        $url = "https://oapi.dingtalk.com/user/get?access_token={$access_token}&userid={$userid}";
        $userDetails = self::get($url, 'json');
        return $userDetails;
    }

    /**
     * 模拟GET请求
     *
     * @param string $url
     * @param string $data_type
     *
     * @return mixed
     *
     * Examples:
     * ```
     * HttpCurl::get('http://api.example.com/?a=123&b=456', 'json');
     * ```
     */
    static public function get($url, $data_type='text')
    {
        $cl = curl_init();
        if(stripos($url, 'https://') !== FALSE) {
            curl_setopt($cl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($cl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($cl, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($cl, CURLOPT_URL, $url);
        curl_setopt($cl, CURLOPT_RETURNTRANSFER, 1 );
        $content = curl_exec($cl);
        $status = curl_getinfo($cl);
        curl_close($cl);
        if (isset($status['http_code']) && $status['http_code'] == 200) {
            if ($data_type == 'json') {
                $content = json_decode($content, true);
            }
            return $content;
        } else {
            return FALSE;
        }
    }


    /**
     * 模拟POST请求
     *
     * @param string $url
     * @param array $fields
     * @param string $data_type
     *
     * @return mixed
     *
     * Examples:
     * ```
     * HttpCurl::post('http://api.example.com/?a=123', array('abc'=>'123', 'efg'=>'567'), 'json');
     * HttpCurl::post('http://api.example.com/', '这是post原始内容', 'json');
     * 文件post上传
     * HttpCurl::post('http://api.example.com/', array('abc'=>'123', 'file1'=>'@/data/1.jpg'), 'json');
     * ```
     */
    static public function post($url, $fields, $data_type='text')
    {
        $cl = curl_init();
        $header = array("Content-Type: application/json; charset=utf-8", "Content-Length:".strlen(json_encode($fields)));
        curl_setopt($cl,CURLOPT_HTTPHEADER,$header);
        if(stripos($url, 'https://') !== FALSE) {
            curl_setopt($cl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($cl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($cl, CURLOPT_SSLVERSION, 1);
        }
        if (class_exists('\CURLFile')) {
            if (isset($fields['media'])) {
                $fields = array('media' => new \CURLFile(realpath(ltrim($fields['media'], '@'))));
            }
        } else {
            if (defined('CURLOPT_SAFE_UPLOAD')) {
                curl_setopt($cl, CURLOPT_SAFE_UPLOAD, false);
            }
        }
        curl_setopt($cl, CURLOPT_URL, $url);
        curl_setopt($cl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($cl, CURLOPT_POST, true);
        curl_setopt($cl, CURLOPT_POSTFIELDS, json_encode($fields));
        $content = curl_exec($cl);
        $status = curl_getinfo($cl);
        curl_close($cl);
        if (isset($status['http_code']) && $status['http_code'] == 200) {
            if ($data_type == 'json') {
                $content = json_decode($content);
            }
            return $content;
        } else {
            return FALSE;
        }
    }

}