<?php
namespace app\customerservice\controller;

use app\customerservice\service\KeywordManageService;
use app\index\service\user as serviceUser;
use think\db\Query;
use think\Request;
use think\Exception;
use app\common\controller\Base;
use think\Validate;
use think\Db;
use app\common\service\Common;
use app\common\cache\Cache;
use app\common\service\Filter;
use app\common\traits\User;
use app\common\service\ChannelAccountConst;
use app\customerservice\filter\KeywordChannelFilter;
use app\common\model\customerservice\MessageKeyword as MessageKeywordModel;

/**
 * @module 客服管理
 * @title 关键词管理页面
 * Date: 2019/02/23
 */
class KeywordsManage extends Base
{

    use User;

    /**
     * @title 关键词列表
     * @author denghaibo
     * @method GET
     * @url /keywords-manage
     */
    public function index()
    {
        try {
            $request = Request::instance();
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 10);
            $params = $request->param();

            $keywordManageService = new KeywordManageService();

            $where = $keywordManageService->index_where($params);


            $count = MessageKeywordModel::where($where)->count();
            $field = 'id,suit_channel_id,keyword,type,status,create_id,create_time';
            $lists = MessageKeywordModel::field($field)->where($where)->page($page, $pageSize)->order('id desc')->select();

            $keywordManageService = new KeywordManageService();
            $type = $keywordManageService->allType();

            foreach ($lists as &$list) {
                $list['channels'] = '';

                $user = new serviceUser();
                $department = $user->getUserDepartment($list['create_id']);

                if (!empty($department)) {
                    $list['department'] = $department[0]['name'];
                } else {
                    $list['department'] = '';
                }

                $list['create_name'] = common::getNameByUserId($list['create_id']);

                if (($list['suit_channel_id'] & 1) == 1) {
                    $list['channels'] = 'ebay';
                }
                if (($list['suit_channel_id'] & 2) == 2) {
                    $list['channels'] = $list['channels'] . ',' . '亚马逊';
                }
                if (($list['suit_channel_id'] & 4) == 4) {
                    $list['channels'] = $list['channels'] . ',' . '速卖通';
                }
                $list['channels'] = trim($list['channels'], ',');

                $list['type_name'] = $type[$list['type'] - 1]['label'];
            }

            $result = [
                'data' => $lists,
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $count,
            ];

            return json($result, 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage() . $e->getFile() . $e->getLine()], 400);
        }
    }

    /**
     * @title 显示一条记录
     * @method GET
     * @url /keywords-manage/view
     */
    public function view()
    {
        try {
            $request = Request::instance();
            $id = $request->get('id', 0);

            if (empty($id)) {
                return json(['message' => 'id为必须'], 400);
            }
            $where['id'] = $id;
            $field = 'id,suit_channel_id,keyword,type,status,create_id,create_time';
            $list = MessageKeywordModel::field($field)->where($where)->find();

            $list['channels'] = '';
            if (($list['suit_channel_id'] & 1) == 1) {
                $list['channels'] = 'Ebay';
            }
            if (($list['suit_channel_id'] & 2) == 2) {
                $list['channels'] = $list['channels'] . ',' . '亚马逊';
            }
            if (($list['suit_channel_id'] & 4) == 4) {
                $list['channels'] = $list['channels'] . ',' . '速卖通';
            }

            $list['channels'] = trim($list['channels'], ',');

            return json($list, 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage() . $e->getFile() . $e->getLine()], 400);
        }
    }

    /**
     * @title 增加一条记录
     * @method POST
     * @url /keywords-manage/add
     */
    public function addKeyword()
    {
        try {
            $return = [
                'ask' => 0,
                'message' => '增加记录错误!'
            ];
            $re = true;
            $sql='';

            $request = Request::instance();
            $type = $request->post('type', 0);
            $keyword = $request->post('keyword', '');
            $keyword = strtolower($keyword);
            $channel_ids = $request->post('channel_ids', []);
            $status = $request->post('status', 0);


            if (empty($type)) {
                return json(['message' => '类型为必须'], 400);
            }
            if (empty($keyword)) {
                return json(['message' => '关键词为必须'], 400);
            }
            if (empty($channel_ids)) {
                return json(['message' => '渠道id为必须'], 400);
            }
            if (($status !== '0') && empty($status)) {
                return json(['message' => '状态为必须'], 400);
            }

            $channel_ids = json_decode($channel_ids, true);

            $messageKeywordModel = new MessageKeywordModel();
            $where['type'] = $type;
            $where['keyword'] = $keyword;

            foreach ($channel_ids as $channel_id) {
                $sql .= $channel_id . ' = suit_channel_id&' . $channel_id . ' or ';
            }

            $sql = substr($sql,0,strlen($sql)-3);
            $where[]  = array('exp',$sql);

            $message_keywords = $messageKeywordModel->where($where)->field('id,suit_channel_id')->find();
//            echo $messageKeywordModel->getLastSql();

            if ($message_keywords)
            {
                $return['ask'] = 1;
                $return['message'] = '关键词已存在!';
                return json($return, 416);
            }else{
                $where_keyword['type'] = $type;
                $where_keyword['keyword'] = $keyword;
                $message_keywords = $messageKeywordModel->where($where_keyword)->field('id,suit_channel_id')->find();
                $message_keywords['suit_channel_id'] = $message_keywords['suit_channel_id'] ?: 0;

                foreach ($channel_ids as $channel_id) {
                    $message_keywords['suit_channel_id'] = $message_keywords['suit_channel_id'] | $channel_id;
                }

                $userInfo = Common::getUserInfo();
                $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

                $data['type'] = $type;
                $data['keyword'] = $keyword;
                $data['suit_channel_id'] = $message_keywords['suit_channel_id'];
                $data['status'] = $status;
                $data['create_id'] = $user_id;
                $data['create_time'] = time();

                if (isset($message_keywords['id'])) {
                    $data['id'] = $message_keywords['id'];
                    $re = $messageKeywordModel->update($data);
                    if ($re === false) {
                        $return['message'] = '更新失败!';
                        return json($return, 500);
                    } else {
                        $return['ask'] = 1;
                        $return['message'] = '更新成功!';
                        return json($return, 200);
                    }
                } else {
                    $re = $messageKeywordModel->save($data);
                    if ($re === false) {
                        $return['message'] = '增加记录失败!';
                        return json($return, 500);
                    } else {
                        $return['ask'] = 1;
                        $return['message'] = '增加记录成功!';
                        return json($return, 200);
                    }
                }
            }


        } catch (Exception $e) {
            return json(['message' => $e->getMessage() . $e->getFile() . $e->getLine()], 400);
        }

    }

    /**
     * @title 删除一条记录
     * @method DELETE
     * @url /keywords-manage/delete
     */
    public function deleteKeyword()
    {
        try {
            $re = true;
            $return = [
                'ask' => 0,
                'message' => '删除记录错误!'
            ];

            $request = Request::instance();
            $id = $request->delete('id', 0);

            if (empty($id)) {
                return json(['message' => 'id为必须'], 400);
            }

            $messageKeywordModel = new MessageKeywordModel();
            $re = $messageKeywordModel->where(['id' => $id])->delete();

            if ($re === false) {
                $return['message'] = '删除记录失败!';
                return json($return, 500);
            } else {
                $return['ask'] = 1;
                $return['message'] = '删除记录成功!';
                return json($return, 200);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage() . $e->getFile() . $e->getLine()], 400);
        }
    }

    /**
     * @title 关键词类型
     * @method GET
     * @url /keywords-manage/type
     * @return array
     */
    public function allType()
    {
        $keywordManageService = new KeywordManageService();
        $type = $keywordManageService->allType();

        return json($type, 200);
    }

    /**
     * @title 渠道
     * @method GET
     * @url /keywords-manage/channel
     * @return array
     */
    public function channels()
    {
        $channel=[
            ['name'=> 'Ebay','id'=>1],
            ['name'=> '亚马逊','id'=>2],
            ['name'=> '速卖通','id'=>4],
        ];

        return json($channel, 200);
    }

    /**
     * @title 根据权限过滤渠道
     * @method GET
     * @url /keywords-manage/permissioned-channel
     * @return array
     * @apiFilter app\customerservice\filter\KeywordChannelFilter
     */
    public function permissionedChannel()
    {
        try {
            $request = Request::instance();
            $channelList = Cache::store('channel')->getChannel();
            $result = [];

            if (!$this->isAdmin()) {
                $channels = [];
                $object = new Filter(KeywordChannelFilter::class,true);
                if ($object->filterIsEffective()) {
                    $channels = $object->getFilterContent();
                }
                foreach ($channelList as $key => $value) {
                    if (in_array($value['id'], $channels)) {
                        array_push($result, $value);
                    }
                }
            } else {
                $channels = [ChannelAccountConst::channel_ebay, ChannelAccountConst::channel_amazon, ChannelAccountConst::channel_aliExpress];
                foreach ($channelList as $key => $value) {
                    if (in_array($value['id'], $channels)) {
                        array_push($result, $value);
                    }
                }
            }

            return json($result, 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage() . $e->getFile() . $e->getLine()], 400);
        }
    }

    /**
     * @title 关键词启用状态
     * @method PUT
     * @url /keywords-manage/keyword-status
     */
    public function keywordStatus()
    {
        try {
            $return = [
                'ask' => 0,
                'message' => '更改状态错误!'
            ];

            $request = Request::instance();
            $id = $request->put('id', 0);
            $status = $request->put('status', 0);

            if (empty($id)) {
                return json(['message' => 'id为必须'], 400);
            }
            if (($status !== '0') && empty($status)) {
                return json(['message' => '状态为必须'], 400);
            }

            $where['id'] = $id;
            $re = MessageKeywordModel::where($where)->setField(['status' => $status]);

            if ($re === false) {
                $return['message'] = '更改失败!';
                return json($return, 500);
            } else {
                $return['ask'] = 1;
                $return['message'] = '更改成功!';
                return json($return, 200);
            }
        } catch (Exception $e) {
            return json(['message' => $e->getMessage() . $e->getFile() . $e->getLine()], 400);
        }
    }
}

