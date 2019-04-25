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
use think\Db;
use think\Exception;
use think\Request;
use app\common\cache\Cache;

/**
 * @module 客服管理
 * @title Ebay帐号邮箱
 * @author LiuLianSen
 * @url /ebay-emails/email-account
 */
class EbayEmailAccount extends Base
{

    private $defServ = null;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        if (empty($defServ)) {
            $this->defServ = new EmailAccountServ(ChannelAccountConst::channel_ebay);
        }

    }


    /**
     * @title 获取ebay邮箱账号列表
     * @url /ebay-emails/email-account
     * @method get
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function index(Request $request)
    {
        return json($this->defServ->searchEmailAccount($request->param()));
    }


    /**
     * @title 查看ebay邮箱账号
     * @author tanbin
     * @method GET
     * @apiParam name:id type:int require:1 desc:ID
     * @url /ebay-emails/email-account/:id
     */
    public function read($id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        try {
            $result = $this->defServ->getInfo($id);
            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 获取能够发送邮件的ebay帐号
     * @url /ebay-emails/account
     * @method get
     * @return \think\response\Json
     */
    public function getEnabledEmailAccount()
    {
        $records = Cache::store('EbayAccount')->getAccount();
        foreach ($records as $record) {
            if ($record['is_invalid'] != 1) {
                continue;
            }
            $data[] = [
                'id' => $record['id'],
                'code' => $record['code'],
                'account_name' => $record['account_name'],
            ];
        }
        return json(['data' => $data]);
    }

    /**
     * @title 添加ebay邮箱账号
     * @url /ebay-emails/email-account
     * @method post
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function create(Request $request)
    {
        $data = $request->post();
        try {
            $id = $this->defServ->add($data);
            return json(['message' => '创建成功', 'id' => $id]);
        } catch (\Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 添加ebay邮箱账号
     * @url /ebay-emails/email-account/:email_account_id
     * @method put
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function update(Request $request, $email_account_id)
    {
        $data = $request->put();
        try {
            $data['email_account_id'] = $email_account_id;
            $id = $this->defServ->update($data);
            return json(['message' => '更新成功', 'id' => $id]);
        } catch (\Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }


    /**
     * @title 删除指定ebay邮箱账号
     * @url /ebay-emails/email-account/:email_account_id
     * @method delete
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function delete(Request $request)
    {
        try {
            $emailAccountId = $request->param('email_account_id/d', null);
            if (empty($emailAccountId)) {
                throw new Exception('邮箱账号id未设置', 400);
            }
            $record = EmailAccounts::get($emailAccountId);
            if (!$record) {
                throw new Exception('邮箱账号id不存在', 404);
            }

            Db::startTrans();
            try {
                $remark = json_encode($record->getData());
                if (!$record->delete()) {
                    throw new Exception('邮箱账号删除失败', 500);
                }
                //记录日志
                $this->defServ->recordLog($emailAccountId, $remark, 2);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            return json(['message' => '删除成功']);
        } catch (Exception $ex) {
            $error_msg = $ex->getMessage();
            $code = $ex->getCode();
            if ($code == 0)
                $code = 500;
            return json(['message' => $error_msg], $code);
        }
    }

    /**
     * @title 获取指定ebay邮箱的log
     * @url /ebay-emails/email-account/log/:email_account_id
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
     * @title 设置ebay邮箱账号是否启用
     * @url /ebay-emails/email-account/:email_account_id/enabled
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
            $this->defServ->enableAccount(input('email_account_id', 0), $isEnabled, $userId);
            return json(['message' => '设置成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

}
