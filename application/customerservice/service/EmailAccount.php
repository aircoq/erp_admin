<?php
// +----------------------------------------------------------------------
// | 邮件service
// +----------------------------------------------------------------------
// | File  : Email.php
// +----------------------------------------------------------------------
// | Author: LiuLianSen <3024046831@qq.com>
// +----------------------------------------------------------------------
// | Date  : 2017-07-19
// +----------------------------------------------------------------------

namespace app\customerservice\service;

use app\common\cache\Cache;
use app\common\model\customerservice\EmailAccounts;
use app\common\model\customerservice\EmailAccountsLog;
use app\common\model\customerservice\EmailList;
use app\common\model\customerservice\EmailSentList;
use app\common\service\Common;
use app\customerservice\validate\AmazonEmailValidate;
use erp\AbsServer;
use think\Db;
use think\db\Query;
use think\Exception;
use app\common\exception\JsonErrorException;
use think\Validate;

class EmailAccount
{
    public $channel_id = 0;

    public function __construct($channel_id = 0) {
        $this->channel_id = $channel_id;
    }

    /**
     * @param $params
     * @return \think\response\Json
     * @throws \Exception
     */
    public function add($params)
    {
        $this->checkData($params);

        $model = new EmailAccounts();
        $count = $model->where(['account_id' => $params['account_id'], 'channel_id' => $this->channel_id])->count();
        if ($count) {
            throw new Exception('帐号已经添设置过邮箱！');
        }
        $account = $this->getAccount($params['account_id']);
        //自动添加渠道和名称，简称；
        $data['channel_id'] = $this->channel_id;
        $data['account_id'] = $account['id'];
        $data['account_name'] = $account['account_name'];
        $data['account_code'] = $account['account_code'];

        $data['email_account'] = $params['email_account'];
        if (!empty($params['email_password'])) {
            $data['email_password'] = $params['email_password'];
        }

        $data['imap_url'] = $params['imap_url'];
        $data['imap_ssl_port'] = $params['imap_ssl_port'];
        $data['smtp_url'] = $params['smtp_url'];
        $data['smtp_ssl_port'] = $params['smtp_ssl_port'];
        $data['smtp_url'] = $params['smtp_url'];
        $data['smtp_ssl_port'] = $params['smtp_ssl_port'];

        $data['is_enabled'] = $params['is_enabled'];
        $data['allowed_receive'] = $params['allowed_receive'];
        $data['allowed_send'] = $params['allowed_send'];
        $data['create_time'] = $data['update_time'] = time();

        $id = $model->allowField(true)->insertGetId($data);
        if ($id) {
            $this->recordLog($id, '添加成功');
            return $id;
        }
        throw new Exception('添加失败');
    }

    public function checkData($data) {
        $validate = new Validate();
        $result = $validate->check($data, [
            'account_id|平台帐号ID' => 'require|number',
            'email_account|邮箱帐号' => 'require|length:1,50',
            //'email_password|邮箱密码' => 'require',
            'imap_url|imap服务器地址' => 'require',
            'imap_ssl_port|imap服务器端口' => 'require|number',
            'smtp_url|smtp服务器地址' => 'require',
            'smtp_ssl_port|smtp服务器端口' => 'require',
            'is_enabled|是否启用' => 'require|number|in:0,1',
            'allowed_receive|是否开启收件' => 'require|number|in:0,1',
            'allowed_send|是否开启发件' => 'require|number|in:0,1',
        ]);
        if ($result !== true) {
            throw new Exception($validate->getError());
        }
    }

    /**
     * 拿取渠道帐号
     * @param $account_id
     * @return mixed
     * @throws Exception
     */
    private function getAccount($account_id) {
        $chanelAccountArr = [1 => 'EbayAccount', 2 => 'AmazonAccount'];
        $account = Cache::store($chanelAccountArr[$this->channel_id])->getTableRecord($account_id);
        if (empty($account)) {
            throw new Exception('渠道帐号不存在');
        }
        $data['id'] = $account['id'];
        $data['account_name'] = $account['account_name'] ?? $account['name'] ?? $account['shop_name'] ?? '';
        $data['account_code'] = $account['account_code'] ?? $account['code'] ?? '';
        return $data;
    }

    /**
     * 记录邮箱操作信息
     * @param $id
     * @param $message
     */
    public function recordLog($id, $message, $type = 0) {
        $message = is_array($message)? $message : [$message];
        $user = Common::getUserInfo();
        $log = new EmailAccountsLog();
        $log->email_account_id = $id;
        $log->operator_id = $user['user_id'];
        $log->time = time();
        $log->type = $type;
        $log->remark = json_encode([$message]);
        $log->save();
    }

    /**
     * 设置邮箱账号信息
     * @param EmailAccounts $record
     * @param array $params
     * @throws Exception
     */
    private function setEmailAccountData(EmailAccounts &$record, $params)
    {
        $account = $this->getAccount($params['account_id']);
        $record->channel_id = $this->channel_id;
        $record->account_id = $account['id'];
        $record->account_code = $account['account_code'];
        $record->account_name = $account['account_name'];
        $record->email_account = $params['email_account'];
        if (!empty($params['email_password'])) {
            $record->email_password = $params['email_password'];
        }
        $record->imap_url = $params['imap_url'];
        $record->imap_ssl_port = $params['imap_ssl_port'];
        $record->smtp_url = $params['smtp_url'];
        $record->smtp_ssl_port = $params['smtp_ssl_port'];
        $record->is_enabled = $params['is_enabled'];
        $record->allowed_receive = $params['allowed_receive'];
        $record->allowed_send = $params['allowed_send'];
        $record->update_time = time();
    }

    /**
     * @param $prarms
     * @return array
     */
    public function getPageInfo(&$prarms)
    {
        $page = isset($prarms['page']) ? intval($prarms['page']) : 1;
        !$page && $page = 1;
        $pageSize = isset($prarms['pageSize']) ? intval($prarms['pageSize']) : 10;
        !$pageSize && $pageSize = 10;
        return [$page, $pageSize];
    }

    /**
     * 查询邮箱账号
     * @param $params
     * @return array
     */
    public function searchEmailAccount($params)
    {
        list($page, $pageSize) = $this->getPageInfo($params);
        $result['page'] = $page;
        $result['pageSize'] = $pageSize;

        $where['channel_id'] = $this->channel_id;
        if (!empty($params['account_id'])) {
            $where['account_id'] = $params['account_id'];
        }
        if (!empty($params['account_code'])) {
            $where['account_code'] = $params['account_code'];
        }
        if (!empty($params['email_account'])) {
            $where['email_account'] = $params['email_account'];
        }

        $model = new EmailAccounts();
        $count = $model->where($where)->count();
        $recrods = $model->where($where)->order('id', 'DESC')->select();
        if (empty($recrods)) {
            return [
                'page' => $page, 'pageSize' => $pageSize, 'count' => $count, 'data' => [],
            ];
        }
        $emailIds = [-1];
        foreach ($recrods as $val) {
            $emailIds[] = $val['id'];
        }

        //找出收件数
        $listCount = EmailList::where(['email_account_id' => ['in', $emailIds]])
            ->group('email_account_id')
            ->field('count(`id`) as total,email_account_id')
            ->select();
        $listCountArr = [];
        if(!empty($listCount)) {
            foreach($listCountArr as $val) {
                $listCount[$val['email_account_id']] = $val['total'];
            }
        }
        //找出发件数；
        $sendListCount = EmailSentList::where(['email_account_id' => ['in', $emailIds]])
            ->group('email_account_id')
            ->field('count(`id`) total,email_account_id')
            ->select();
        $sendListCountArr = [];
        if(!empty($sendListCount)) {
            foreach($sendListCount as $val) {
                $sendListCountArr[$val['email_account_id']] = $val['total'];
            }
        }

        $data = [];
        foreach ($recrods as $recrod) {
            $_data = $recrod->toArray();
            $_data['email_password'] = '';
            $_data['receive_qty'] = $listCountArr[$recrod['id']] ?? 0;
            $_data['send_qty'] = $sendListCountArr[$recrod['id']] ?? 0;
            $data[] = $_data;
        }
        return ['page' => $page, 'pageSize' => $pageSize, 'count' => $count, 'data' => $data];
    }


    /**
     * 查询邮箱账号
     * @param $params
     * @return array
     */
    public function getInfo($id)
    {
        $model = new EmailAccounts();
        $info = $model->where(['id' => $id])->find();
        if (empty($info)) {
            throw new JsonErrorException('数据不存在');
        }
        $info->email_password = '';
        return $info;
    }


    protected $editFields = [
        'account_id' => [
            'text' => '平台账号',
            'map' => 'account_code'
        ],
        'is_enabled' => [
            'text' => '是否开启',
            'values' => ['不开启', '开启']
        ],
        'allowed_receive' => [
            'text' => '是否允许收件',
            'values' => ['不允许', '允许']
        ],
        'allowed_send' => [
            'text' => '是否允许发件',
            'values' => ['不允许', '允许']
        ],
        'email_account' => '邮箱账号',
        'email_password' => '邮箱密码',
        'imap_url' => 'imap服务器地址',
        'imap_ssl_port' => 'imap服务器端口',
        'smtp_url' => 'smtp服务器地址',
        'smtp_ssl_port' => 'smtp服务器端口',
    ];


    /**
     * 修改邮箱账号
     * @param $params
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function update($params)
    {
        $this->checkData($params);
        $emailAccountId = $params['email_account_id'] ?? $params['id'] ?? '';
        if (empty($emailAccountId)) {
            throw new Exception('邮箱账号id未设置', 400);
        }
        $model = new EmailAccounts();
        $record = $model->where(['id' => $emailAccountId, 'channel_id' => $this->channel_id])->find();
        if (!$record) {
            throw new Exception('邮箱账号id不存在', 404);
        }

        $accountCount = $model->where(['id' => ['<>', $emailAccountId], 'account_id' => $params['account_id'], 'channel_id' => $this->channel_id])->count();
        if ($accountCount) {
            throw new Exception('已添加过此平台帐号ID');
        }

        //因为后端传到前台是清空了邮箱密码的，所以如果不写密码更新，可能密码会传过来一个空字符串，需要过滤
        if(isset($params['email_password']) && trim($params['email_password']) == '') {
            unset($params['email_password']);
        }

        $emailData = $record->getData();
        $this->setEmailAccountData($record, $params);
        //比较数据前后变化；
        $currtData = $record->getData();
        $remark = $this->getChangedFields($emailData, $currtData);

        if (empty($remark)) {
            throw new Exception('修改前后没有变化', 400);
        }

        if (!$record->isUpdate()->save()) {
            throw new Exception('账号修改失败', 400);
        }

        $this->recordLog($emailAccountId, $remark, 1);
        return $emailAccountId;
    }

    /**
     * @param $orig
     * @param $curt
     * @return array
     */
    protected function getChangedFields($orig, $curt)
    {
        $changes = [];
        foreach ($curt as $k => $v) {
            if ($curt[$k] != $orig[$k]) {
                $changes[$k] = [
                    'orig' => $orig[$k],
                    'curt' => $curt[$k]
                ];
            }
        }
        $remark = [];
        $fields = array_keys($this->editFields);
        foreach ($changes as $k => $v) {
            if (in_array($k, $fields)) {
                $set = $this->editFields[$k];
                if (is_string($set)) {
                    $remark[] = '将"' . $set . '"改为: ' . $v['curt'];
                } else {
                    if (isset($set['map'])) {
                        $remark[] = '将"' . $set['text'] . '"改为: ' . $curt[$set['map']];
                    } else {
                        $remark[] = '将"' . $set['text'] . '"改为: ' . $set['values'][$v['curt']];
                    }
                }
            }
        }
        return $remark;
    }


    /**
     * 设置指定邮箱账号id是否启用
     * @param $accountId
     * @param bool $isEnabled
     * @return bool
     * @throws Exception
     */
    public function enableAccount($accountId, $isEnabled = true, $userId = '')
    {
        Db::startTrans();
        try {
            $model = new EmailAccounts;
            $account = $model->where(['id' => $accountId])->lock(true)->find();
            if (!$account) {
                throw new Exception('邮件账号不存在', 404);
            }
            $account->is_enabled = intval(!!$isEnabled);
            $account->isUpdate()->save();
            if (!$userId) {
                $userId = Common::getUserInfo()->toArray()['user_id'];
            }
            $log = new EmailAccountsLog;
            $log->save([
                'email_account_id' => $accountId,
                'type' => 1,
                'operator_id' => $userId,
                'time' => time(),
                'remark' => json_encode(['将是否启用改为: ' . ($isEnabled ? '启用' : '不启用')])
            ]);
            Db::commit();
            return true;
        } catch (Exception $ex) {
            Db::rollback();
            $code = $ex->getCode();
            $msg = $ex->getMessage();
            if ($code == 0) {
                $code = 500;
                $msg = '程序内部错误';
            }
            throw new Exception($msg, $code);
        }
    }

    /**
     * 获取已启用，已授权账号信息   通过渠道或者站点，内容为下拉框模式
     * @param $channel_id
     * @param int $site_code
     * @return mixed
     * @throws \think\Exception
     */
    public function accountInfo($channel_id, $site_code = 0)
    {
        $result = Cache::store('account')->getAccountByChannel($channel_id);
        //获取站点信息
        $channel_name = Cache::store('channel')->getChannelName($channel_id);
        $site = Cache::store('channel')->getSite($channel_name, false);
        $new_list['account'] = [];
        foreach ($result as $k => $v) {
            $temp = [];
            $temp['label'] = $v['code'];
            $temp['value'] = intval($v['id']);
            $temp['account_name'] = $v['account_name'] ?? $v['shop_name'] ?? $v['name'] ??'';
            if (!empty($site_code)) {
                if (isset($v['site_id'])) {
                    if (is_array($v['site_id'])) {
                        $siteArray = $v['site_id'];
                    } else if (is_string($v['site_id'])) {
                        $siteArray = json_decode($v['site_id'], true);
                    } else {
                        $siteArray = [];
                    }
                    if (is_array($siteArray)) {
                        if (in_array($site_code, $siteArray)) {
                            array_push($new_list['account'], $temp);
                        }
                    }
                }
                if (isset($v['site'])) {
                    if (strstr($v['site'], $site_code)) {
                        array_push($new_list['account'], $temp);
                    }
                }
            } else {
                array_push($new_list['account'], $temp);
            }
        }
        $new_site = [];
        foreach ($site as $k => $v) {
            $temp = [];
            $temp['label'] = $v['code'];
            $temp['value'] = $k;
            array_push($new_site, $temp);
        }
        $new_list['site'] = $new_site;
        return $new_list;
    }
}