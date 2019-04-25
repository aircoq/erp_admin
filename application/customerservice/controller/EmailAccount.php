<?php
// +----------------------------------------------------------------------
// | 客服邮件功能控制器
// +----------------------------------------------------------------------
// | File  : AmazonEmail.php
// +----------------------------------------------------------------------
// | Author: LiuLianSen <3024046831@qq.com>
// +----------------------------------------------------------------------
// | Date  : 2017-07-18
// +----------------------------------------------------------------------
namespace app\customerservice\controller;

use app\common\controller\Base;
use app\common\model\customerservice\EmailAccounts;
use app\common\model\customerservice\EmailAccountsLog;
use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use app\customerservice\service\EmailAccount as EmailAccountServ;
use app\index\service\AccountService;
use think\Db;
use think\Exception;
use think\Request;
use app\common\cache\Cache;

/**
 * @module 客服管理
 * @title 平台帐号绑定邮箱
 * @author LiuLianSen
 * @url /email-account
 */
class EmailAccount extends Base
{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

    }


    /**
     * @title 获取邮箱账号列表
     * @author 冬
     * @url /email-account
     * @method GET
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\order\controller\Order::channel
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function index(Request $request)
    {
        $channel_id = $request->get('channel_id', 0);
        if (!$channel_id) {
            return json(['message' => '平台渠道参数channel_id为空'], 400);
        }
        $server = new EmailAccountServ($channel_id);

        try {
            return json($server->searchEmailAccount($request->param()));
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 查看邮箱账号
     * @author 冬
     * @method GET
     * @apiParam name:id type:int require:1 desc:ID
     * @url /email-account/:id
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\order\controller\Order::channel
     */
    public function read(Request $request, $id)
    {
        $server = new EmailAccountServ();
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        try {
            $result = $server->getInfo($id);
            return json(['data' => $result]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 添加邮箱账号
     * @url /email-account
     * @method post
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\order\controller\Order::channel
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function create(Request $request)
    {
        $channel_id = $request->post('channel_id', 0);
        if (!$channel_id) {
            return json(['message' => '平台渠道参数channel_id为空'], 400);
        }
        $server = new EmailAccountServ($channel_id);
        $data = $request->post();
        try {
            $id = $server->add($data);
            return json(['message' => '创建成功', 'id' => $id]);
        } catch (\Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 添加邮箱账号
     * @url /email-account/:email_account_id
     * @method put
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\order\controller\Order::channel
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function update(Request $request, $email_account_id)
    {
        $channel_id = $request->put('channel_id', 0);
        if (!$channel_id) {
            return json(['message' => '平台渠道参数channel_id为空'], 400);
        }
        $server = new EmailAccountServ($channel_id);
        $data = $request->put();
        try {
            $data['email_account_id'] = $email_account_id;
            $id = $server->update($data);
            return json(['message' => '更新成功', 'id' => $id]);
        } catch (\Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }


    /**
     * @title 删除指定邮箱账号
     * @url /email-account
     * @method Delete
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function delete(Request $request)
    {
        $server = new EmailAccountServ();
        try {
            $ids = $request->delete('ids', '');
            if (empty($ids)) {
                throw new Exception('邮箱账号id未设置', 400);
            }
            $idArr = explode(',', $ids);
            $records = EmailAccounts::where(['id' => ['in', $idArr]])->select();
            if (!$records) {
                throw new Exception('邮箱账号'. $ids. '不存在', 404);
            }

            Db::startTrans();
            try {
                foreach ($records as $record) {
                    $remark = json_encode($record->getData());
                    if (!$record->delete()) {
                        throw new Exception('邮箱账号删除失败', 500);
                    }
                    //记录日志
                    $server->recordLog($record['id'], $remark, 2);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            return json(['message' => '删除成功']);
        } catch (Exception $ex) {
            $error_msg = $ex->getMessage();
            return json(['message' => $error_msg], 400);
        }
    }

    /**
     * @title 获取指定邮箱的log
     * @url /email-account/log/:email_account_id
     * @method get
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function getEmailAccountLog(Request $request)
    {
        try {
            $emailAccountId = $request->param('email_account_id/d', null);
            if (empty($emailAccountId)) {
                throw new Exception('邮箱账号id未设置', 400);
            }
            $model = new EmailAccountsLog();
            $records = $model->with('operator')->where('email_account_id', $emailAccountId)->order('time', 'DESC')->select();
            foreach ($records as &$record) {
                $record->remark = json_decode($record->remark);
                if (!$record->remark) {
                    $record->remark = [];
                }
            }
            return json($records);
        } catch (Exception $ex) {
            $code = $ex->getCode();
            if ($code == 0)
                $code = 500;
            return json(['message' => $ex->getMessage()], $code);
        }
    }

    /**
     * @title 设置邮箱是否启用
     * @url /email-account/:email_account_id/enabled
     * @method put
     * @param Request $request
     * @return \think\response\Json
     */
    public function enableAccount(Request $request)
    {

        try {
            $isEnabled = $request->param('is_enabled', '');
            if ($isEnabled === '') {
                throw new Exception('是否启用标志未设置', 400);
            }
            $userId = Common::getUserInfo()->toArray()['user_id'];
            $server = new EmailAccountServ();
            $server->enableAccount(input('email_account_id', 0), $isEnabled, $userId);
            return json(['message' => '设置成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 不过滤获取平台/站点账号简称
     * @url /email-account/account
     * @method get
     * @return \think\response\Json
     */
    public function account()
    {
        try {
            $request = Request::instance();
            $channel_id = $request->get('channel_id', 0);  //渠道id
            $site_code = $request->get('site_code', 0);  //站点code
            if (empty($channel_id) || !is_numeric($channel_id)) {
                $result['account'] = [];
                $result['site'] = [];
                return json($result, 200);
                //return json(['message' => '参数渠道信息错误'], 400);
            }
            $help = new EmailAccountServ();
            $result = $help->accountInfo($channel_id, $site_code, 'order');
            return json($result, 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

}
