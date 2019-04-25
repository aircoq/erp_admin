<?php

namespace app\common\model;

use app\common\cache\Cache;
use app\common\model\account\AccountApplyDetail;
use app\common\model\ebay\EbaySite;
use app\common\model\AccountCompany;
use app\common\model\Phone;
use app\common\model\Server;
use app\index\service\ChannelService;
use think\Exception;
use think\Model;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/8/22
 * Time: 17:46
 */
class AccountApply extends Model
{
    //0-新增 1-已注册 2-审核中 3-注册成功 4-注册失败 5-已作废 6-已推送

    const STATUS_REGISTER = 1;
    const STATUS_AUDIT = 2;
    const STATUS_REGISTER_OK = 3;
    const STATUS_REGISTER_OK_WITHOUT_KYC = 4;
    const STATUS_PUSH = 5;
    const STATUS_INVALID = 6;

    const STATUS = [
        AccountApply::STATUS_REGISTER => '注册',
        AccountApply::STATUS_AUDIT => '审核中',
        AccountApply::STATUS_REGISTER_OK => '注册结果',
        AccountApply::STATUS_REGISTER_OK_WITHOUT_KYC => '未触发KYC注册结果',
        AccountApply::STATUS_PUSH => '已推送/注册完成',
        AccountApply::STATUS_INVALID => '已作废',
    ];

    const AMAZON_SITE = [
        ['code' => 'US', 'data' => 'US,CA,MX'],
        ['code' => 'UK', 'data' => 'UK,DE,FR,IT,ES'],
        ['code' => 'JP', 'data' => 'JP'],
        ['code' => 'AU', 'data' => 'AU'],
        ['code' => 'BR', 'data' => 'BR'],
        ['code' => 'IN', 'data' => 'IN'],
    ];

    public function getAllSite($channelId)
    {
        $result = [];
        if ($channelId == 2) {
            foreach (self::AMAZON_SITE as $v) {
                $data = explode(',', $v['data']);
                foreach ($data as $site) {
                    $result[] = $site;
                }
            }
            return $result;
        }
        $ChannelService = new ChannelService();
        $channelInfo = $ChannelService->getInfoById($channelId);
        if (!$channelInfo) {
            throw new Exception('当前渠道不存在:' . $channelId);
        }
        $tmp = Cache::store('channel')->getSite($channelInfo['name'], false);
        if ($tmp) {
            foreach ($tmp as $v) {
                $result[] = $v['code'];
            }
        }
        return $result;
    }


    /**
     * 基础账号信息
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 检测渠道账号是否已经绑定过这个服务器了
     * @param $channel_id
     * @param $account_id
     * @param $server_id
     * @return array|bool|false|\PDOStatement|string|Model
     */
    public function isHas($channel_id, $server_id, $account_id = 0)
    {
        if (!empty($account_id)) {
            $where['id'] = ['<>', $account_id];
        }
        $where['channel_id'] = ['=', $channel_id];
        $where['server_id'] = ['=', $server_id];
        $result = $this->where($where)->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return $result;
    }

    /**
     * 获取状态名称
     * @param $status
     * @return string
     */
    public function statusName($status)
    {
        $remark = self::STATUS;
        if (isset($remark[$status])) {
            return $remark[$status];
        }
        return '';
    }

    public function getStatusTxtAttr($value, $data)
    {
        return $this->statusName($data['status']);
    }

    public static function getCompanyCount($companyId, $group = false)
    {
        $model = New AccountApply();
        $where['company_id'] = $companyId;
        if ($group) {
            $reData = $model->where($where)->group('channel_id')->column('count(*)', 'channel_id');
            $reData = $reData ? $reData : ['0' => 0];
        } else {
            $reData = $model->where($where)->count();
            $reData = $reData ? $reData : 0;
        }
        return $reData;
    }

    public function companyRelation()
    {
        return $this->hasOne(AccountCompany::class, 'id', 'company_id');
    }

    public function getChannelTxtAttr($value, $data)
    {
        $department = new Department();
        return $department->getChannelNameAttr($value, $data);
    }

    public function phone()
    {
        return $this->hasOne(Phone::class, 'id', 'phone_id');
    }

    public function server()
    {
        return $this->hasOne(Server::class, 'id', 'server_id');
    }

    public function getServerTxtAttr($value, $data)
    {
        $result = '';
        if (isset($data['id'])) {
            $ApplyData = [
                'id' => $data['id'],
                'server_id' => $data['server_id'],
            ];
            $AccountApply = new self($ApplyData);
            $result = $AccountApply->server ? $AccountApply->server['name'] : '';
        }
        return $result;
    }

    public function getRegisterTxtAttr($value, $data)
    {
        if (!$data['register_id']) {
            return '';
        }
        return Cache::store('user')->getOneUserRealname($data['register_id']);
    }

    public function detail()
    {
        return $this->hasMany(AccountApplyDetail::class, 'account_apply_id', 'id');
    }

    public function setFulfillTimeAttr($value)
    {
        return strtotime($value);
    }

}