<?php
namespace app\internalletter\service;

use app\common\model\InternalLetter;
use app\common\model\InternalLetterText;
use think\Db;
use think\Loader;
use think\Cache;
use app\common\service\Common;
use app\common\cache\Cache as commonCache;
use think\Validate;
use app\common\model\User as UserModel;
use app\common\service\UniqueQueuer;
use app\internalletter\queue\SendDingQueue;
use Exception;
use app\internalletter\service\DingTalkService;

class InternalLetterService
{

    protected $internalLetterModel;
    protected $internalLetterTextModel;

    const batchSendFail = 0;
    const batchSendSuccess = 1;
    const lostTitleOrContentOrReceiver = 2;
    const createIdIsNeeded = 2;
    const sendSuccess = 1;
    const sendFail = 0;

    public function __construct()
    {
        if (is_null($this->internalLetterModel)) {
            $this->internalLetterModel = new InternalLetter();
        }
        if (is_null($this->internalLetterTextModel)) {
            $this->internalLetterTextModel = new InternalLetterText();
        }
    }

    /*
     * 发送站内信
     * @param array receive_ids  收件人id（支持传数组 单个用户可以不用数组，receive_ids[0]为0表示发送所有用户）
     * @param array create_id    发件人id（默认情况下会自动获取，id为空时，不能发送，返回错误2）
     * @param string title       标题
     * @param string content     内容
     * @param int type           站内信类型（详细见文档 站内信.doc）
     * @param int dingtalk       是否同时发送钉钉（0为不发送，1为发送）
     * @param string attachment  附件
     * @throws \think\Exception
     *
     * 同一个微应用相同消息内容同一个用户一天只能接收一次，重复发送会发送成功但用户接收不到。
     */
    public static function sendLetter( $params )
    {
        $internalLetterModel = new InternalLetter();
        $internalLetterTextModel = new InternalLetterText();

//        $receiveIds=[];
        $departmentIds=[];
        $letter = [];
//        $data['department_ids']='';

        if (!empty($params['receive_ids'])) {
            $receiveIds = $params['receive_ids'];
            if(is_array($receiveIds)){
                $letter['receive_ids']=implode(",",$receiveIds);
            }else{
                $letter['receive_ids']=$receiveIds;
            }
        }
        if (!empty($params['department_ids'])) {
            $departmentIds = $params['department_ids'];
            if(is_array($departmentIds)){
                $letter['department_ids']=implode(",",$departmentIds);
            }else{
                $letter['department_ids']=$departmentIds;
            }
        }

        if (!empty($params['type'])) {
            $data['type'] = $params['type'];
        }
        if (!empty($params['title'])) {
            $data['title'] = $params['title'];
        }
        if (!empty($params['content'])) {
            $letter['content'] = $params['content'];
        }
        if (!empty($params['attachment'])) {
            $attachment=self::uploadBase64File($params['attachment']);
            if($attachment == ''){
                $letter['attachment'] = '';
            }else{
                $letter['attachment'] = $attachment;
            }
        }
        if (!empty($params['level'])) {
            $data['level'] = $params['level'];
        }

        if (!empty($params['dingtalk'])) {
            $data['dingtalk'] = $params['dingtalk'];
        }else{
            $data['dingtalk'] = 0;
        }

        if (!empty($params['create_id'])) {
            $data['create_id'] = $params['create_id'];
        }else{
            $userInfo = Common::getUserInfo();
            $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];
            if(empty($user_id)) return self::createIdIsNeeded;
            $data['create_id'] = $user_id;
        }

        $data['create_time'] = time();
        $data['send_time'] = time();
        $data['method'] = 1;

        $letter_text_id=$internalLetterTextModel->insertGetId($letter);
        $data['letter_text_id'] = $letter_text_id;
        $letter_id = $internalLetterModel->insertGetId($data);

        if (isset($params['content']) && $params['content'] != '') {
            $data['content'] = $params['content'];
        }

        if(empty($receiveIds)){
            return false;
        }

        if( is_array($receiveIds) && isset($receiveIds[0]) && $receiveIds[0] === 0){
            $send=self::sendLetterToAll( $letter_text_id, $letter_id, $data);
        }else{
            if(is_array($receiveIds)){
                $send=self::sendLetterToMember($letter_text_id, $receiveIds, $departmentIds, $data);
            }else{
                $send=self::sendLetterToMember($letter_text_id, [$receiveIds], [$departmentIds], $data);
            }
        }

        if($send){
            return self::sendSuccess;
        }else{
            return self::sendFail;
        }
    }

    /*
     * 保存到草稿箱
     * @param $params
     * @throws \think\Exception
     */
    public function saveToDraftbox( $params )
    {

        $letter = [];
        $db='';
        $db2='';

        if (!empty($params['receive_ids'])) {
            $receiveIds = $params['receive_ids'];
            if(is_array($receiveIds)){
                $letter['receive_ids']=implode(",",$receiveIds);
            }else{
                $letter['receive_ids']=$receiveIds;
            }
        }
        if (!empty($params['department_ids'])) {
            $departmentIds = $params['department_ids'];
            if(is_array($departmentIds)){
                $letter['department_ids']=implode(",",$departmentIds);
            }else{
                $letter['department_ids']=$departmentIds;
            }
        }

        if (!empty($params['type'])) {
            $data['type'] = $params['type'];
        }
        if (!empty($params['title'])) {
            $data['title'] = $params['title'];
        }
        if (!empty($params['content'])) {
            $letter['content'] = $params['content'];
        }
        if (!empty($params['dingtalk'])) {
            $data['dingtalk'] = $params['dingtalk'];
        }else{
            $data['dingtalk'] = 0;
        }
        if (!empty($params['attachment'])) {
            $attachment=$this->uploadBase64File($params['attachment']);
            if($attachment == ''){
                $letter['attachment'] = '';
            }else{
                $letter['attachment'] = $attachment;
            }
        }
        if (!empty($params['level'])) {
            $data['level'] = $params['level'];
        }
        $data['draft'] = 1;

        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];
        $data['create_id'] = $user_id;

        $data['create_time']=time();

        if (!empty($params['id'])) {
            $where['id'] = $params['id'];
            $db2=$this->internalLetterModel->where($where)->update($data);

            $letter_text_id = $this->internalLetterModel->where($where)->field('letter_text_id')->find();
            $where['id'] = $letter_text_id['letter_text_id'];
            $db=$this->internalLetterTextModel->where($where)->update($letter);
        }else{
            $letter_text_id=$this->internalLetterTextModel->insertGetId($letter);
            $data['letter_text_id']=$letter_text_id;
            $db2=$this->internalLetterModel->insert($data);
        }
        if($db===false || $db2===false){
            return false;
        }else{
            return true;
        }

    }

    /*
     *  发送站内信（全部成员）
     * @param int $letter_text_id
     * @throws \think\Exception
     */
    public static function sendLetterToAll( $letter_text_id, $letter_id, $data )
    {
        try{
            set_time_limit(0);
            $queue = new UniqueQueuer(SendDingQueue::class);
            $internalLetterModel = new InternalLetter();

            $datas=['letter_text_id'=>$letter_text_id, 'read'=>0, 'delete'=>0, 'send_time'=>time(), 'read_time'=>0, 'delete_time'=>0,
                    'create_id'=>$data['create_id'], 'create_time'=>$data['create_time'], 'title'=>$data['title'],
                    'draft'=>0, 'type'=>$data['type'], 'send_letter_delete'=>0, 'dingtalk'=>$data['dingtalk']];


            $allUserData=(new \app\index\service\User())->getGoupByDepartmentUsers();
            foreach ($allUserData as $userDate) {
                foreach ($userDate as $user) {
                    if(is_array($user)) {
                        foreach ($user as $userId) {
                            $datas['receive_id'] = $userId['user_id'];
                            $datas['dingtalk_userid'] = $userId['dingtalk_userid'];
                            $internalLetterModel->insert($datas);
                        }
                    }
                }
            }

            if($datas['dingtalk']){

                $internalLetterModel->where('id', $letter_id)->update(['status'=>1, 'count'=>1]);

                $params=[
                    'id'=>$letter_id,
                    'send_to_all'=>1
                ];
                $queue->push($params);
            }

        }catch (\Exception $e){
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
        return true;
    }

    /*
     * 发送站内信（指定一个或多个成员）
     * @param int $letter_text_id
     * @param int $receiveIds
     * @throws \think\Exception
     */
    public static function sendLetterToMember( $letter_text_id, $receiveIds, $departmentIds, $data )
    {
        try{
            $queue = new UniqueQueuer(SendDingQueue::class);
            $allUserData=(new \app\index\service\User())->getGoupByDepartmentUsers();

            $internalLetterModel = new InternalLetter();

            $datas=['letter_text_id'=>$letter_text_id, 'read'=>0, 'delete'=>0, 'send_time'=>time(), 'read_time'=>0, 'delete_time'=>0,
                'create_id'=>$data['create_id'], 'create_time'=>$data['create_time'], 'title'=>$data['title'],
                'draft'=>0, 'type'=>$data['type'], 'send_letter_delete'=>0, 'dingtalk'=>$data['dingtalk']];

            if(is_array($receiveIds) && count($receiveIds)) {
                foreach ($receiveIds as $userId) {
                    if(!empty($userId)){

                        foreach ($allUserData as $userDate) {
                            foreach ($userDate as $user) {
                                if (is_array($user)) {
                                    foreach ($user as $userDetails) {

                                        if($userId == $userDetails['user_id']){
                                            $datas['receive_id'] = $userId;
                                            $datas['dingtalk_userid'] = $userDetails['dingtalk_userid'];
                                            $letter_id=$internalLetterModel->insertGetId($datas);

                                            if($datas['dingtalk']){

                                                $internalLetterModel->where('id', $letter_id)->update(['status'=>1, 'count'=>1]);

                                                $params=[
                                                    'id'=>$letter_id,
                                                    'send_to_all'=>0
                                                ];
                                                $queue->push($params);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }catch (\Exception $e){
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
        return true;
    }

    /*
     * 发送钉钉工作消息
     * @param $params
     * @throws \think\Exception
     */
    public function sendMessage( $params )
    {
//        $result = $this->getOrganizeTree();
//        Log::record('result ' . var_export($result, true), 'info');

    }

    /*
     * 更新钉钉userid到user表
     */
    public function updateDingtalkUserid(){

        try {
            $dingTalkService = new DingTalkService();
            $model = new UserModel();
            $user = $model->field('id, mobile')->select();

            Loader::import('taobaosdk.TopSdk', EXTEND_PATH, '.php');

            $departMent = $dingTalkService->getDepartment();

            foreach ($departMent as $key=>$value){
                if($value['id'] == 1) continue;

                $useridList = $dingTalkService->getDepartmentUserDetails($value['id'], 0, 100);
                foreach ($user as $val){
                    if(is_array($useridList)){
                        foreach ($useridList as $userDetails){
                            if(is_array($userDetails)){
                                foreach ($userDetails as $details){
                                    if(is_array($details)) {
                                        if ($details['mobile'] == $val['mobile']) {
                                            $model->update(['dingtalk_userid' => $details['userid'], 'id' => $val['id']]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }


    /* 获取组织树
     * @return array|mixed
     */
    public function getOrganizeTree()
    {
        $result = [];
//        if ($this->redis->exists('cache:organize_tree')) {
//            $result = json_decode($this->redis->get('cache:organize_tree'), true);
//            return $result;
//        } else {
            $organize_list = Db::table('dingtalk_department')->field('name,department_id as id,parent_id as pid,create_time,update_time')->order('department_id ASC')->select();
//        }
        try {
            if ($organize_list) {
                $child = '_child';
                $child_ids = [];
                $temp = [
                    'depr' => '-',
                    'parents' => [],
                    'child_ids' => [],
                    'dir' => [],
                    '_child' => [],
                ];
                $func = function ($tree) use (&$func, &$result, &$temp, &$child, &$icon, &$child_ids) {
                    foreach ($tree as $k => $v) {
                        $v['parents'] = $temp['parents']; //所有父节点
                        $v['depth'] = count($temp['parents']); //深度
                        $v['name_path'] = empty($temp['name']) ? $v['name'] : implode($temp['depr'],
                                $temp['name']) . $temp['depr'] . $v['name']; //英文名路径
                        if (isset($v[$child])) {
                            $_tree = $v[$child];
                            unset($v[$child]);
                            $temp['parents'][] = $v['id'];
                            $temp['name'][] = $v['name'];
                            $result[$k] = $v;
                            if ($v['pid'] == 0) {
                                if (empty($child_ids)) {
                                    $child_ids = [$k];
                                } else {
                                    array_push($child_ids, $k);
                                }
                            }
                            $func($_tree);
                            foreach ($result as $value) {
                                if ($value['pid'] == $k) {
                                    $temp['child_ids'] = array_merge($temp['child_ids'], [$value['id']]);
                                }
                            }
                            $result[$k]['child_ids'] = $temp['child_ids']; //所有子节点
                            $temp['child_ids'] = [];
                            array_pop($temp['parents']);
                            array_pop($temp['name']);
                        } else {
                            $v['child_ids'] = [];
                            $result[$k] = $v;
                            if ($v['pid'] == 0) {
                                if (empty($child_ids)) {
                                    $child_ids = [$k];
                                } else {
                                    array_push($child_ids, $k);
                                }
                            }
                        }
                    }
                };
                $_list = [];
                foreach ($organize_list as $k => $v) {
                    $_list['model'][$v['id']] = $v;
                }
                foreach ($_list as $k => $v) {
//                    Log::record('result ' . var_export(list_to_tree($v, 'id', 'pid', 'son',1), true), 'info');
                    $func(list_to_tree($v, 'id', 'pid', '_child',1));
                }

            }
            //$result = list_to_tree($result);
            $result['child_ids'] = $child_ids;
            //加入redis中
//            $this->redis->set('organize_tree', json_encode($result));
        } catch (Exception $e) {
            var_dump($e->getMessage());
            die;
        }
        return $result;
    }


    /*
     * 接收的站内信列表（收件箱）
     * @param $params
     * @param $page
     * @param $pageSize
     * @return array
     * @throws \think\Exception
     */
    public function pullReceivedLetters( $params, $page, $pageSize)
    {
        $where=[];
        $this->where($where, $params);

        $b_time = !empty(param($params, 'btime'))?$params['btime'].' 00:00:00':'';
        $e_time = !empty(param($params, 'etime'))?$params['etime'].' 23:59:59':'';

        if($b_time){
            if(Validate::dateFormat($b_time,'Y-m-d H:i:s')){
                $b_time = strtotime($b_time);
            }else{
                throw new Exception('起始日期格式错误(格式如:2017-01-01)',400);
            }
        }

        if($e_time){
            if(Validate::dateFormat($e_time,'Y-m-d H:i:s')){
                $e_time = strtotime($e_time);
            }else{
                throw new Exception('截止日期格式错误(格式如:2017-01-01)',400);
            }
        }

        if($b_time && $e_time){
            $where['send_time']  =  ['BETWEEN', [$b_time, $e_time]];
        }elseif ($b_time) {
            $where['send_time']  = ['EGT',$b_time];
        }elseif ($e_time) {
            $where['send_time']  = ['ELT',$e_time];
        }


        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        $where['receive_id']=$user_id;
        $where['delete']=0;
        $where['method']=0;
//        $join['internal_letter_text'] = ['internal_letter_text t', 'l.letter_text_id = t.id', 'left'];
        $field = 'type, read, create_id, title, send_time, letter_text_id';
        $count = $this->internalLetterModel->where($where)->field($field)->count();
        $letterList = $this->internalLetterModel->where($where)->field($field)
            ->order('send_time desc')->page($page, $pageSize)->select();

        unset($where['read']);
        $inbox_count = $this->internalLetterModel->where($where)->field($field)->count();

        foreach ($letterList as $key => $letter) {
            $user_info=commonCache::store('user')->getOneUser($letter['create_id']);
            $letter['create_name']=$user_info['realname']??'';
        }

        $where['read']=0;
        $unread = $this->internalLetterModel->where($where)->field($field)->count();

        $result = [
            'data' => $letterList,
            'count' => $count,
            'inbox_count' => $inbox_count,
            'unread' => $unread,
            'page' => $page,
            'pageSize' => $pageSize
        ];
        return $result;
    }

    /*
     * 新站内信通知
     * @return array
     * @throws \think\Exception
     */
    public function notification()
    {

        if(empty(Cache::get('max_send_time'))){
            $this->get_max_send_time();
        }
        $where['send_time'] = ['>', Cache::get('max_send_time')];

        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        $where['receive_id']=$user_id;
        $where['delete']=0;
        //产品下架不弹窗
        $where['type']=array('neq',20);

        $field = 'type, read, create_id, title, send_time, letter_text_id';
        $letterList = $this->internalLetterModel->where($where)->field($field)->order('send_time desc')->select();


        foreach ($letterList as $key => $letter) {
            $user_info=commonCache::store('user')->getOneUser($letter['create_id']);
            $letter['send_name']=$user_info['realname']??'';

            $content = $this->internalLetterTextModel->where('id', $letter['letter_text_id'])->field('content, attachment ')->find();
            $letter['content'] = $content['content'];
            $letter['attachment'] = $content['attachment'];
        }

        $where['read']=0;
        $where['method']=0;
        unset($where['send_time']);
        unset($where['type']);
        $count = $this->internalLetterModel->where($where)->count();

        if(!empty($letterList)){
            $this->get_max_send_time();
        }

        $result = [
            'data' => $letterList,
            'count' => $count
        ];
        return $result;
    }

    /*
     * 发送的站内信列表（发件箱）
     * @param $params
     * @param $page
     * @param $pageSize
     * @return array
     * @throws \think\Exception
     */
    public function pullSentLetters( $params, $page, $pageSize)
    {
        $where=[];
        $this->where($where, $params);

        $b_time = !empty(param($params, 'btime'))?$params['btime'].' 00:00:00':'';
        $e_time = !empty(param($params, 'etime'))?$params['etime'].' 23:59:59':'';

        if($b_time){
            if(Validate::dateFormat($b_time,'Y-m-d H:i:s')){
                $b_time = strtotime($b_time);
            }else{
                throw new Exception('起始日期格式错误(格式如:2017-01-01)',400);
            }
        }

        if($e_time){
            if(Validate::dateFormat($e_time,'Y-m-d H:i:s')){
                $e_time = strtotime($e_time);
            }else{
                throw new Exception('截止日期格式错误(格式如:2017-01-01)',400);
            }
        }

        if($b_time && $e_time){
            $where['send_time']  =  ['BETWEEN', [$b_time, $e_time]];
        }elseif ($b_time) {
            $where['send_time']  = ['EGT',$b_time];
        }elseif ($e_time) {
            $where['send_time']  = ['ELT',$e_time];
        }


        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        $where['create_id']=$user_id;
        $where['send_letter_delete']=0;
        $where['draft']=0;
        //发信
        $where['method']=1;

        $field = 'type, title, send_time, id, letter_text_id';
        $count = $this->internalLetterModel->where($where)->field($field)->count();
        $letterList = $this->internalLetterModel->where($where)->field($field)->order('send_time desc')->select();
        $idList = $this->internalLetterTextModel->field('id, receive_ids, department_ids' )->select();

        foreach ($letterList as $letter) {
            foreach ($idList as $id) {
                if($letter['letter_text_id'] == $id['id']){
                    $letter['receive_ids'] = $id['receive_ids'];
                    $letter['department_ids'] = $id['department_ids'];
                }
            }
        }

        $username=[];
        $department_name=[];
        foreach ($letterList as $key => $letter) {
            if(!empty($letter['receive_ids'])){
                $ids=explode(',',$letter['receive_ids']);
                $letter['receive_ids']=$ids;
                foreach ($ids as $key => $id) {
                    $user_info = commonCache::store('user')->getOneUser($id);
                    if(!empty($user_info['realname'])){
                        array_push($username, $user_info['realname']);
                    }
                }
                $letter['receive_name'] = $username;
                unset($username);
                $username=[];
            }else{
                $letter['receive_name']=[];
            }
            if(!empty($letter['department_ids'])){
                $ids=explode(',',$letter['department_ids']);
                $letter['department_ids']=$ids;
                foreach ($ids as $key => $id) {
                    //根据部门id获取部门名称
                    $departmentInfo=(new \app\index\service\Department())->getDepartmentNames($id);
                    array_push($department_name, $departmentInfo);
                }
                $letter['department_name'] = $department_name;
                unset($department_name);
                $department_name=[];
            }else{
                $letter['department_name']=[];
            }
        }

        $result = [
            'data' => $letterList,
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize
        ];
        return $result;
    }

    /*
     * 草稿箱
     * @param $params
     * @param $page
     * @param $pageSize
     * @return array
     * @throws \think\Exception
     */
    public function draftLetters( $params, $page, $pageSize)
    {
        $where=[];
        $this->where($where, $params);


        $b_time = !empty(param($params, 'btime'))?$params['btime'].' 00:00:00':'';
        $e_time = !empty(param($params, 'etime'))?$params['etime'].' 23:59:59':'';

        if($b_time){
            if(Validate::dateFormat($b_time,'Y-m-d H:i:s')){
                $b_time = strtotime($b_time);
            }else{
                throw new Exception('起始日期格式错误(格式如:2017-01-01)',400);
            }
        }

        if($e_time){
            if(Validate::dateFormat($e_time,'Y-m-d H:i:s')){
                $e_time = strtotime($e_time);
            }else{
                throw new Exception('截止日期格式错误(格式如:2017-01-01)',400);
            }
        }

        if($b_time && $e_time){
            $where['create_time']  =  ['BETWEEN', [$b_time, $e_time]];
        }elseif ($b_time) {
            $where['create_time']  = ['EGT',$b_time];
        }elseif ($e_time) {
            $where['create_time']  = ['ELT',$e_time];
        }

        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        $where['create_id']=$user_id;
        $where['delete']=0;
        $where['draft']=1;
        $where['method']=0;


        $field = 'type, title, create_time, id, letter_text_id';
        $count = $this->internalLetterModel->where($where)->field($field)->count();
        $letterList = $this->internalLetterModel->where($where)->field($field)->order('create_time desc')->select();
        $idList = $this->internalLetterTextModel->field('id, receive_ids, department_ids' )->select();

        foreach ($letterList as $letter) {
            foreach ($idList as $id) {
                if($letter['letter_text_id'] == $id['id']){
                    $letter['receive_ids'] = $id['receive_ids'];
                    $letter['department_ids'] = $id['department_ids'];
                }
            }
        }

        $username=[];
        $department_name=[];
        foreach ($letterList as $key => $letter) {
            if(!empty($letter['receive_ids'])){
                $ids=explode(',',$letter['receive_ids']);
                $letter['receive_ids']=$ids;

                foreach ($ids as $key => $id) {
                    $user_info = commonCache::store('user')->getOneUser($id);
                    if(!empty($user_info['realname'])){
                        array_push($username, $user_info['realname']);
                    }
                }
                $letter['receive_name'] = $username;
                unset($username);
                $username=[];
            }else{
                $letter['receive_name'] = [];
            }
            if(!empty($letter['department_ids'])){
                $ids=explode(',',$letter['department_ids']);
                $letter['department_ids']=$ids;
                foreach ($ids as $key => $id) {
                    //根据部门id获取部门名称
                    $departmentInfo=(new \app\index\service\Department())->getDepartmentNames($id);
                    array_push($department_name, $departmentInfo);
                }
                $letter['department_name'] = $department_name;
                unset($department_name);
                $department_name=[];
            }else{
                $letter['department_name']=[];
            }
        }

        $result = [
            'data' => $letterList,
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize
        ];
        return $result;
    }

    /*
     * 看收信
     * @param $params
     * @return array
     * @throws \think\Exception
     */
    public function viewReceivedLetter( $params )
    {
        $letter_id=$params['letter_id'];

        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        $where['receive_id']=$user_id;

        $field = 'title, create_id, send_time, type, read, letter_text_id, id';

        $where['letter_text_id']=$letter_id;
        $letter = $this->internalLetterModel->where($where)->field($field)->find();
        $content = $this->internalLetterTextModel->where('id', $letter['letter_text_id'])->field('content, attachment ')->find();

        $letter['content'] = $content['content'];
        $letter['attachment'] = $content['attachment'];

        $user_info=commonCache::store('user')->getOneUser($letter['create_id']);
        $letter['create_name']=$user_info['realname']??'';

        unset($where['letter_text_id']);
        $field = 'title, letter_text_id';
        $where['id'] = ['>', $letter['id']];
        $prev = $this->internalLetterModel->where($where)->field($field)->order('id asc')->find();
        $where['id'] = ['<', $letter['id']];
        $next = $this->internalLetterModel->where($where)->field($field)->order('id desc')->find();


        $result = [
            'data' => $letter,
            'prev' => $prev,
            'next' => $next,
        ];
        return $result;
    }

    /*
     * 看发信
     * @param $params
     * @return array
     * @throws \think\Exception
     */
    public function viewSentLetter( $params )
    {

        $letter_id=$params['letter_id'];

        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        $where['create_id']=$user_id;
        $where['send_letter_delete']=0;
        $where['draft']=0;
        $where['method']=1;


        $field = 'id, title, create_id, type, send_time, letter_text_id';
        $where['id']=$letter_id;
        $letter = $this->internalLetterModel->alias('l')->where($where)->field($field)->find();
        if(empty($letter)){
            return false;
        }
        $content = $this->internalLetterTextModel->where('id',$letter['letter_text_id'])->field('receive_ids, department_ids, content, attachment' )->find();

        $letter['receive_ids'] = $content['receive_ids'];
        $letter['department_ids'] = $content['department_ids'];
        $letter['content'] = $content['content'];
        $letter['attachment'] = $content['attachment'];

        $username=[];
        $ids=explode(',',$letter['receive_ids']);
        $letter['receive_ids']=$ids;

        foreach ($ids as $key => $id) {
            $user_info = commonCache::store('user')->getOneUser($id);
            if(!empty($user_info['realname'])){
                array_push($username, $user_info['realname']);
            }
        }
        $letter['receive_name'] = $username;


        unset($where['l.id']);
        $field = 'title, id';
        $where['id'] = ['>', $letter['id']];
        $prev = $this->internalLetterModel->where($where)->field($field)->order('id asc')->find();
        $where['id'] = ['<', $letter['id']];
        $next = $this->internalLetterModel->where($where)->field($field)->order('id desc')->find();


        $result = [
            'data' => $letter,
            'prev' => $prev,
            'next' => $next,
        ];
        return $result;
    }

    /*
     * 看草稿
     * @param $params
     * @return array
     * @throws \think\Exception
     */
    public function viewDraft( $params )
    {

        $letter_id=$params['letter_id'];

        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        $where['create_id']=$user_id;
        $where['send_letter_delete']=0;
        $where['draft']=1;

        $field = 'id, title, create_id, create_time, type, letter_text_id';
        $where['id']=$letter_id;
        $letter = $this->internalLetterModel->alias('l')->where($where)->field($field)->find();
        if(empty($letter)){
            return false;
        }

        $content = $this->internalLetterTextModel->where('id',$letter['letter_text_id'])->field('receive_ids, department_ids, content, attachment' )->find();

        $letter['receive_ids'] = $content['receive_ids'];
        $letter['department_ids'] = $content['department_ids'];
        $letter['content'] = $content['content'];
        $letter['attachment'] = $content['attachment'];

        $username=[];
        $ids=explode(',',$letter['receive_ids']);
        $letter['receive_ids']=$ids;

        foreach ($ids as $key => $id) {
            $user_info = commonCache::store('user')->getOneUser($id);
            if(!empty($user_info['realname'])){
                array_push($username, $user_info['realname']);
            }
        }
        $letter['receive_name'] = $username;

        unset($where['l.id']);
        $field = 'title, id';
        $where['id'] = ['>', $letter['id']];
        $prev = $this->internalLetterModel->where($where)->field($field)->order('id asc')->find();
        $where['id'] = ['<', $letter['id']];
        $next = $this->internalLetterModel->where($where)->field($field)->order('id desc')->find();


        $result = [
            'data' => $letter,
            'prev' => $prev,
            'next' => $next,
        ];
        return $result;
    }

    /*
     * 草稿编辑
     * @param $params
     * @return array
     * @throws \think\Exception
     */
    public function draftEdit( $params )
    {

        $letter_id=$params['letter_id'];

        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        $where['create_id']=$user_id;
        $where['send_letter_delete']=0;
        $where['draft']=1;

        $field = 'id, title, create_id, create_time, type, letter_text_id';
        $where['id']=$letter_id;
        $letter = $this->internalLetterModel->alias('l')->where($where)->field($field)->find();

        $content = $this->internalLetterTextModel->where('id',$letter['letter_text_id'])->field('receive_ids, department_ids, content, attachment' )->find();

        $letter['receive_ids'] = $content['receive_ids'];
        $letter['department_ids'] = $content['department_ids'];
        $letter['content'] = $content['content'];
        $letter['attachment'] = $content['attachment'];

        $username=[];
        $department_name=[];
        $user=[];
        if(!empty($letter['receive_ids'])){
            $ids=explode(',',$letter['receive_ids']);
            $letter['receive_ids']=$ids;

            foreach ($ids as $key => $id) {
                $user_info = commonCache::store('user')->getOneUser($id);
                $departmentInfo=(new \app\index\service\Department())->getDepartmentNames($id);
                $user[$key] = array('id'=>$id,'realname'=>$user_info['realname']??'','username'=>$user_info['username'],'department'=>$departmentInfo);
            }
        }
        if(!empty($letter['department_ids'])){
            $ids=explode(',',$letter['department_ids']);
            $letter['department_ids']=$ids;
            foreach ($ids as $key => $id) {
                //根据部门id获取部门名称
                $departmentInfo=(new \app\index\service\Department())->getDepartmentNames($id);
                array_push($department_name, $departmentInfo);
            }
            $letter['department_name'] = $department_name;
        }

        $letter['user_data']=$user;

        $result = [
            'data' => $letter
        ];
        return $result;
    }

    /*
     * 草稿箱批量发送
     * @param $params
     * @return array
     * @throws \think\Exception
     */
    public function batchSend( $params )
    {

        $letter_ids=$params['letter_ids'];
        $letter_ids = json_decode($letter_ids);

        foreach ($letter_ids as $key => $letter_id) {
            $where['id'] = $letter_id;
            $field = 'letter_text_id, read, delete, send_time, read_time, delete_time, 
                        create_id,  create_time, id, draft, title, type, send_letter_delete, method, dingtalk';

            $userInfo = Common::getUserInfo();
            $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];
            $data['create_id']=$user_id;
            $data['send_time']=time();
            $data['method']=1;

            $this->internalLetterModel->where($where)->update($data);

            $letter = $this->internalLetterModel->where($where)->field($field)->find();
            $content = $this->internalLetterTextModel->where('id',$letter['letter_text_id'])->field('receive_ids, department_ids, content, attachment' )->find();

            $letter['receive_ids'] = $content['receive_ids'];
            $letter['department_ids'] = $content['department_ids'];
            $letter['content'] = $content['content'];
            $letter['attachment'] = $content['attachment'];

            if($letter['draft'] == 1 && !empty($letter['receive_ids']) && !empty($letter['title']) ){
                $receiveIds=explode(',',$letter['receive_ids']);
                $departmentIds=explode(',',$letter['department_ids']);

                if ($receiveIds[0] === 0) {
                    $send=$this->sendLetterToAll($letter['letter_text_id'], $letter_id, $letter);
                    if(!$send){
                        return $this::batchSendFail;
                    }
                    $this->internalLetterModel->where($where)->setField('draft', '0');
                } else {
                    $send=$this->sendLetterToMember($letter['letter_text_id'], $receiveIds, $departmentIds, $letter);
                    if(!$send){
                        return $this::batchSendFail;
                    }
                    $this->internalLetterModel->where($where)->setField('draft', '0');
                }
            }else{
                return $this::lostTitleOrContentOrReceiver;
            }
        }
        return $this::batchSendSuccess;
    }

    /*
     * 设置已读
     * @throws \think\Exception
     */
    public function setRead( $params )
    {
        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        $where['receive_id']=$user_id;
        $letter_ids=$params['letter_ids'];
        $letter_ids = json_decode($letter_ids);
        $data = array('read'=>'1', 'read_time' => time());
        foreach ($letter_ids as $key => $letter_id) {
            $where['letter_text_id']=$letter_id;
            $update=$this->internalLetterModel->where($where)->update($data);
            if($update === false){
                return false;
            }
        }

        return true;
    }

    /*
     * 批量已读
     * @throws \think\Exception
     */
    public function setAllRead( $params )
    {
        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        $where['receive_id']=$user_id;
        $where['read']=0;
        $letterList = $this->internalLetterModel->where($where)->select();

        unset($where);
        $data = array('read'=>'1', 'read_time' => time());
        foreach ($letterList as $key => $letter_id) {
            $where['letter_text_id']=$letter_id['letter_text_id'];
            $update=$this->internalLetterModel->where($where)->update($data);
            if($update === false){
                return false;
            }
        }

        return true;
    }

    /*
     * 删除收信
     * @param $where
     * @param $params
     * @param $page
     * @param $pageSize
     * @return array
     * @throws \think\Exception
     */
    public function deleteReceivedLetters( $params )
    {
        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        $where['receive_id']=$user_id;
        $letter_ids=$params['letter_ids'];
        $letter_ids = json_decode($letter_ids);
//        $data = array('delete'=>'1', 'delete_time' => time());
        foreach ($letter_ids as $key => $letter_id) {
            $where['letter_text_id']=$letter_id;
//            $this->inboxMessageModel->where($where)->update($data);
            $delete=$this->internalLetterModel->where($where)->delete();
            if($delete === false){
                return false;

            }
        }
        return true;
    }

    /*
     * 删除发信
     * @param $where
     * @param $params
     * @throws \think\Exception
     */
    public function deletSentLetters( $params )
    {
        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        $where['create_id']=$user_id;
        $letter_ids=$params['letter_ids'];
        $letter_ids = json_decode($letter_ids);
        $data = array('send_letter_delete'=>'1', 'send_letter_delete_time' => time());
        foreach ($letter_ids as $key => $letter_id) {
            $where['id']=$letter_id;
            $delete=$this->internalLetterModel->where($where)->update($data);
            if($delete === false){
                return false;
            }
        }
        return true;
    }

    /*
     * 草稿箱批量删除
     * @param $where
     * @param $params
     * @throws \think\Exception
     */
    public function draftDelete( $params )
    {
        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        $where['create_id']=$user_id;
        $where['draft']=1;
        $letter_ids=$params['letter_ids'];
        $letter_ids = json_decode($letter_ids);
        foreach ($letter_ids as $key => $letter_id) {
            $where['id']=$letter_id;
            $letter_text_id=$this->internalLetterModel->where($where)->field('letter_text_id')->find();
            $delete=$this->internalLetterModel->where($where)->delete();
            $condition['id'] = $letter_text_id['letter_text_id'];
            $delete2=$this->internalLetterTextModel->where($condition)->delete();
            if($delete === false || $delete2 === false){
                return false;
            }
        }
        return true;
    }

    /*
     * 获取所有站内信类型
     * @return array
     */
    public function getAllType()
    {
        $type=[
            ['label'=> '系统通知','value'=>1],
            ['label'=> '公告','value'=>2],
            ['label'=> '短信通知','value'=>3],
            ['label'=> '功能更新通知','value'=>4],
            ['label'=> '新功能上线通知','value'=>5],
            ['label'=> '紧急通知','value'=>6],
            ['label'=> '放假通知','value'=>7],
            ['label'=> '暂时缺货通知','value'=>8],
            ['label'=> '停产通知','value'=>9],
            ['label'=> '批发供货通知','value'=>10],
            ['label'=> '价格调整通知','value'=>11],
            ['label'=> '亏损警告通知','value'=>12],
            ['label'=> '侵权产品通知','value'=>13],
            ['label'=> '呆滞提醒通知','value'=>14],
            ['label'=> '取消暂时缺货通知','value'=>15],
            ['label'=> '取消停产通知','value'=>16],
            ['label'=> '产品涨价通知','value'=>17],
            ['label'=> '产品跌价通知','value'=>18],
            ['label'=> '产品设置仿牌','value'=>19],
            ['label'=> '产品下架通知','value'=>20],
            ['label'=> '好评公告','value'=>21],
            ['label'=> '差评公告','value'=>22],
            ['label'=> '创建新订单','value'=>23],
            ['label'=> '借款通知','value'=>24],
            ['label'=> '跟卖投诉完成通知','value'=>25],
            ['label'=> '跟卖提醒通知','value'=>26],
            ['label'=> '报表生成通知','value'=>27],
            ['label'=> '安检退回通知','value'=>28],
            ['label'=> '清库通知','value'=>29],
            ['label'=> '取消清库通知','value'=>30],
            ['label'=> '包裹信息','value'=>31],
            ['label'=> '活动未被延期','value'=>32]
        ];

        return $type;
    }

    /*
     * 获取所有用户信息
     * @return array
     */
    public function getAllUserInfo()
    {
        $allUserData=(new \app\index\service\User())->getGoupByDepartmentUsers();
        return $allUserData;
    }

    /*
    * libaimin 用于测试用的
    * @return $access_token
    */
    public static function getAccessTokenText()
    {
        if(Cache::get('expiration_text') > time()){
            return Cache::get('access_token_text');
        }else{
            $corpId = 'dingxpsex0qhedik9dz5';
            $corpSecret = 'GgsCtRBY-fEiVLM0A6ONyRUnNT9UTjRH3y_udJNV-uxd8ngd_4mEJlZBjWJ8KGqb';
            $url = "https://oapi.dingtalk.com/gettoken?corpid={$corpId}&corpsecret={$corpSecret}";
            $jsoninfo = DingTalkService::get($url, 'json');
            $access_token = $jsoninfo["access_token"];
            $expires_in = $jsoninfo["expires_in"];
            $expiration=$expires_in + time();
            Cache::set('expiration_text',$expiration,$expires_in);
            Cache::set('access_token_text',$access_token,$expires_in);
            return $access_token;
        }
    }

    /*
     * @param $params
     * @param $where
     */
    public function where(&$where, $params)
    {
        //接收者
        if (isset($params['receiver_id']) && $params['receiver_id'] != '') {
//            $where[] = 'find_in_set('.$params['receiver_id'].',receiver_ids)';
            $where[] = ['exp','find_in_set('.$params['receiver_id'].',receive_ids)'];
        }

        //是否已读
        if (isset($params['read']) && $params['read'] != '') {
            $where['read'] = ['eq', $params['read']];
        }

        //发送者
        if (isset($params['send_id']) && $params['send_id'] != '') {
            $where['create_id'] = ['eq', $params['send_id']];
        }
        //全文搜索
        if (isset($params['snText']) && $params['snText'] != '') {
            $snText = trim($params['snText']);
            $snText='%'.$snText.'%';
//            $where['content'] = ['like', $snText];
            $where['title'] = ['like', $snText];
        }
        //通知类型
        if (isset($params['type']) && $params['type'] != '') {
            $where['type'] = ['eq', $params['type']];
        }
    }


    /*
     * @return
     */
    public static function uploadBase64File( $attachment )
    {
        $base64_file = trim($attachment);
        $up_dir = './upload/';//存放在当前目录的upload文件夹下


        $base64_file = json_decode($base64_file, true);
        if(!file_exists($up_dir.date('YmdHis'))){
            mkdir($up_dir.date('YmdHis'),0777);
        }

        if(!empty($base64_file)){
            $type = pathinfo($base64_file[0]['name'], PATHINFO_EXTENSION);
            $type = strtolower($type);
            if(in_array($type,array('pjpeg','jpeg','jpg','gif','bmp','png','xlsx','xls','doc','docx','txt','csv','ppt'))){
                $new_file = $up_dir.date('YmdHis') .'/'. $base64_file[0]['name'];
                if(file_put_contents($new_file, base64_decode(str_ireplace('base64,','', strstr($base64_file[0]['file'],'base64,'))))){
                    return $new_file;
                }else{
                    return 0;

                }
            }else{
                //文件类型错误
                return 0;
            }

        }else{
            //文件错误
            return 0;
        }
    }


    /*
     * @title 下载附件
     */
    public function downAttachment( $full_name )
    {
        echo file_get_contents($full_name);
        exit;
//        return ['message'=>'附件下载成功', 'code' => 200];
    }

    /*
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_max_send_time(): void
    {
        $userInfo = Common::getUserInfo();
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        $where['receive_id'] = $user_id;
        $where['l.delete'] = 0;
        $send_time = $this->internalLetterModel->alias('l')->where($where)->field('l.send_time')
            ->order('send_time desc')->find();

        $max_send_time = empty($send_time) ? 0 : $send_time->send_time;

        Cache::set('max_send_time', $max_send_time, 0);
    }

    /**
     * 保存联系人模板
     * @param $params
     * @return array
     */
    public function saveTemplate($params)
    {
        $return =[
            'ask'=>0,
            'template_id'=>0,
            'message'=>'saveTemplate error',
        ];

        Db::startTrans();
        try {
            $userInfo = Common::getUserInfo();
            $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

            $res = Db::table('internalletter_template')->where('name' , $params['template_name'])->find();

            if ($res)
            {
                Db::table('internalletter_template_detail')->where('internalletter_template_id' , $res['id'])->delete();
                Db::table('internalletter_template')->where('id' , $res['id'])->delete();
            }

            $datas = [
                'name' => $params['template_name'],
                'update_id' => $user_id,
                'create_id' => $user_id,
                'status' => 0,
                'create_time' => time(),
                'update_time' => time(),
            ];

            $template_id = Db::table('internalletter_template')->insertGetId($datas);

            $member_list = json_decode($params['member_list'], true);
            foreach ($member_list as $item)
            {
                $detail_datas = [
                    'internalletter_template_id' => $template_id,
                    'user_id' => $item['user_id'],
                    'username' => $item['username'],
                    'realname' => $item['realname'],
                    'department' => $item['department'],
                    'role' => $item['role'],
                    'create_time' => time(),
                ];
                Db::table('internalletter_template_detail')->insert($detail_datas);
            }

            Db::commit();
            $return['ask'] = 1;
            $return['template_id'] = $template_id;
            $return['message'] = '保存成功！';
        } catch (Exception $ex) {
            Db::rollback();
            $return['ask'] = 0;
            $return['template_id'] = 0;
            $return['message'] = $ex->getMessage();
        }

        return $return;
    }

    /**
     * 删除联系人模板
     * @param $id
     * @return array
     */
    public function deleteTemplate($id)
    {
        $return =[
            'ask'=>0,
            'message'=>'deleteTemplate error',
        ];

        Db::startTrans();
        try {
            $res = Db::table('internalletter_template')->where('id' , $id)->find();
            if ($res)
            {
                Db::table('internalletter_template_detail')->where('internalletter_template_id' , $res['id'])->delete();
                Db::table('internalletter_template')->where('id' , $id)->delete();
            }

            Db::commit();
            $return['ask'] = 1;
            $return['message'] = '删除成功！';
        } catch (Exception $ex) {
            Db::rollback();
            $return['ask'] = 0;
            $return['message'] = $ex->getMessage();
        }

        return $return;
    }

    /**
     * 获取联系人模板
     * @return array
     */
    public function getTemplate()
    {
        $return =[
            'ask'=>0,
            'message'=>'getTemplate error',
            'data' => [],
        ];

        try {
            $res = Db::table('internalletter_template')->select();

            $return['ask'] = 1;
            $return['message'] = '获取成功！';
            $return['data'] = $res;
        } catch (Exception $ex) {
            $return['ask'] = 0;
            $return['message'] = $ex->getMessage();
        }
        return $return;
    }

    /**
     * 获取联系人模板详情
     * @return array
     */
    public function getTemplateDetail($id)
    {
        $return =[
            'ask'=>0,
            'message'=>'getTemplate error',
            'data' => [],
        ];

        try {
            $res = Db::table('internalletter_template_detail')->where('internalletter_template_id', $id)->select();

            $return['ask'] = 1;
            $return['message'] = '获取详情成功！';
            $return['data'] = $res;
        } catch (Exception $ex) {
            $return['ask'] = 0;
            $return['message'] = $ex->getMessage();
        }
        return $return;
    }

    /**
     * 搜索已添加用户
     * @param $user_id
     * @return array
     */
    public function searchUserInTemplate($user_id)
    {
        $return =[
            'ask'=>0,
            'message'=>'getTemplate error',
            'data' => [],
        ];

        try {
            $res = Db::table('internalletter_template_detail')->where('user_id' , $user_id)->select();
            if(empty($res)){
                $return['message'] = '没有对应的已添加用户！';
                return $return;
            }
            foreach ($res as $item)
            {
                $template_name = Db::table('internalletter_template')->where('id' , $item['internalletter_template_id'])->value('name');
                $item['template_name'] = $template_name;
            }
            $return['ask'] = 1;
            $return['message'] = '获取成功！';
            $return['data'] = $res;
        } catch (Exception $ex) {
            $return['ask'] = 0;
            $return['message'] = $ex->getMessage();
        }
        return $return;
    }

}