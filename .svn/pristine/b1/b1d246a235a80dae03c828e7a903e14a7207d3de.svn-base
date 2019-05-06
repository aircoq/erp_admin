<?php
namespace app\index\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\service\ChannelAccountConst;
use think\Db;
use think\db\Query;
use think\Exception;
use think\Model;

class ChannelAccountService
{
    /**
     * 已注册服务
     * @var Service
     */
    private static $service_list = [];
    private static $model_list = [];

    public function service($channelId = 0)
    {
        if (isset(self::$service_list[$channelId])) {
            return self::$service_list[$channelId];
        }

        $conf_list = [
            ChannelAccountConst::channel_amazon => \app\index\service\AmazonAccountService::class,
            ChannelAccountConst::channel_Joom => \app\index\service\JoomAccountService::class,
            ChannelAccountConst::channel_Fummart => \app\index\service\FunmartAccountService::class,
            ChannelAccountConst::channel_Oberlo => \app\index\service\OberloAccountService::class,
            ChannelAccountConst::channel_Daraz => \app\index\service\DarazAccountService::class,
            ChannelAccountConst::channel_Vova => \app\index\service\VovaAccountService::class,
            ChannelAccountConst::channel_Zoodmall => \app\index\service\ZoodmallAccountService::class,
            ChannelAccountConst::channel_aliExpress => \app\index\service\AliexpressAccountService::class,
            ChannelAccountConst::Channel_Jumia => \app\index\service\JumiaAccountService::class,
            ChannelAccountConst::channel_CD => \app\index\service\CdAccountService::class,
            ChannelAccountConst::channel_Walmart => \app\index\service\WalmartAccountService::class,
            ChannelAccountConst::channel_Lazada => \app\index\service\LazadaAccountService::class,
            ChannelAccountConst::channel_Pdd => \app\index\service\PddAccountService::class,
            ChannelAccountConst::channel_Paytm => \app\index\service\PaytmAccountService::class,
            ChannelAccountConst::channel_Pandao => \app\index\service\PandaoAccountService::class,
            ChannelAccountConst::Channel_umka => \app\index\service\UmkaAccountService::class,
            ChannelAccountConst::channel_Yandex => \app\index\service\YandexAccountService::class,
            ChannelAccountConst::channel_wish => \app\index\service\WishAccountService::class,
            ChannelAccountConst::channel_Shopee => \app\index\service\ShopeeAccountService::class,
            ChannelAccountConst::channel_ebay => \app\index\service\EbayAccountService::class,
        ];

        if (!$service = $conf_list[$channelId] ?? null) {
            throw new Exception('未注册' . $channelId);
        }
        return self::$service_list[$channelId] = new $service();
    }

    public function model($channelId = 0): Model
    {
        if (isset(self::$model_list[$channelId])) {
            return self::$model_list[$channelId];
        }

        $conf_list = [
            ChannelAccountConst::channel_amazon => \app\common\model\amazon\AmazonAccount::class,
            ChannelAccountConst::channel_Joom => \app\common\model\joom\JoomAccount::class,
            ChannelAccountConst::channel_Fummart => \app\common\model\fummart\FummartAccount::class,
            ChannelAccountConst::channel_Oberlo => \app\common\model\oberlo\OberloAccount::class,
            ChannelAccountConst::channel_Daraz => \app\common\model\daraz\DarazAccount::class,
            ChannelAccountConst::channel_Vova => \app\common\model\zoodmall\ZoodmallAccount::class,
            ChannelAccountConst::channel_Zoodmall => \app\common\model\zoodmall\ZoodmallAccount::class,
            ChannelAccountConst::channel_aliExpress => \app\common\model\aliexpress\AliexpressAccount::class,
            ChannelAccountConst::Channel_Jumia => \app\common\model\jumia\JumiaAccount::class,
            ChannelAccountConst::channel_CD => \app\common\model\cd\CdAccount::class,
            ChannelAccountConst::channel_Walmart => \app\common\model\walmart\WalmartAccount::class,
            ChannelAccountConst::channel_Lazada => \app\common\model\lazada\LazadaAccount::class,
            ChannelAccountConst::channel_Pdd => \app\common\model\pdd\PddAccount::class,
            ChannelAccountConst::channel_Paytm => \app\common\model\paytm\PaytmAccount::class,
            ChannelAccountConst::channel_Pandao => \app\common\model\pandao\PandaoAccount::class,
            ChannelAccountConst::Channel_umka => \app\common\model\umka\UmkaAccount::class,
            ChannelAccountConst::channel_Yandex => \app\common\model\yandex\YandexAccount::class,
            ChannelAccountConst::channel_wish => \app\common\model\wish\WishAccount::class,
            ChannelAccountConst::channel_Shopee => \app\common\model\shopee\ShopeeAccount::class,
            ChannelAccountConst::channel_ebay => \app\common\model\ebay\EbayAccount::class,
        ];

        if (!$model = $conf_list[$channelId] ?? null) {
            throw new Exception('未注册' . $channelId);
        }
        
        return self::$model_list[$channelId] = new $model();
    }

    /**
     * @author lingjiawen
     * @dateTime 2019-05-04
     * @param    integer     $channelId 平邑id
     * @param    int|integer $id        账号id
     * @param    bool        $enable    true|false
     */
    public function setStatus($channelId = 0, int $base_account_id = 0, bool $enable)
    {
        $ids = $this->getIdsByBaseId($channelId, $base_account_id);
        // 暂不做限制
        // $this->checkChangeStatus($channelId, $ids);
        foreach ($ids as $id) {
            $this->service($channelId)->changeStatus($id, $enable);
        }
        return true;
    }

    /**
     * @author lingjiawen
     * @dateTime 2019-05-04
     * @param    integer     $channelId       平台id
     * @param    int|integer $base_account_id 基础账号id
     * @return   array                       [1,2,3]
     */
    public function getIdsByBaseId($channelId = 0, int $base_account_id = 0): array
    {
        return $this->model($channelId)->where(['base_account_id' => $base_account_id])->column('id') ?? [];
    }

    /**
     * 限制状态更改
     * @author lingjiawen
     * @dateTime 2019-05-04
     * @param    Model      $model 平台账号model
     * @param    array      $ids   [1,2,3]
     * @return   bool       true|false
     * @return JsonErrorException
     */
    public function checkChangeStatus($channelId = 0, array $ids): bool
    {
        $ids = $this->model($channelId)->where('id', 'in', $ids)->column('base_account_id');
        $list = db('account_site')->where('base_account_id', 'in', $ids)->column('account_code,site_status');

        $black_list = [];

        foreach ($list as $k => $v) {
            !in_array($v, [1, 2, 3, 4]) && $black_list[] = $k . '[' . $v . ']';
        }
        if ($black_list) {
            throw new JsonErrorException(implode(',', $black_list) . ',未分配、已回收、已作废状态不可更改系统状态', 400);
        }

        return count($list) == count($ids);
    }

    // 实现中
    public function batchUpdate($channelId = 0, array $update_data)
    {
        return true;
    }

    // 实现中
    public function lists($channelId = 0, array $param, string $field = '*')
    {
        return true;
        /**
         * 初始化参数
         */
        $operator = ['eq' => '=', 'gt' => '>', 'lt' => '<'];
        $page = isset($param['page']) ? intval($param['page']) : 1;
        $pageSize = isset($param['pageSize']) ? intval($param['pageSize']) : 50;
        $time_type = isset($param['time_type']) and in_array($param['time_type'], ['register', 'fulfill']) ? $param['time_type'] : '';
        $start_time = isset($param['start_time']) ? strtotime($param['start_time']) : 0;
        $end_time = isset($param['end_time']) ? strtotime($param['end_time']) : 0;
        $site = $param['site'] ?? '';
        $status = isset($param['status']) && is_numeric($param['status']) ? intval($param['status']) : -1;
        $site_status = isset($param['site_status']) && is_numeric($param['site_status']) ? intval($param['site_status']) : -1;
        $seller_id = isset($param['seller_id']) ? intval($param['seller_id']) : 0;
        $customer_id = isset($param['customer_id']) ? intval($param['customer_id']) : 0;
        $is_authorization = isset($param['authorization']) && is_numeric($param['authorization']) ? intval($param['authorization']) : -1;
        $is_invalid = isset($param['is_invalid']) && is_numeric($param['is_invalid']) ? intval($param['is_invalid']) : -1;
        $snType = !empty($param['snType']) && in_array($param['snType'], ['account_name', 'code']) ? $param['snType'] : '';
        $snText = !empty($param['snText']) ? $param['snText'] : '';
        $taskName = !empty($param['taskName']) && in_array($param['taskName'], ['download_listing', 'download_order', 'sync_delivery', 'download_health']) ? $param['taskName'] : '';
        $taskCondition = !empty($param['taskCondition']) && isset($operator[trim($param['taskCondition'])]) ? $operator[trim($param['taskCondition'])] : '';
        $taskTime = isset($param['taskTime']) && is_numeric($param['taskTime']) ? intval($param['taskTime']) : '';

        /**
         * 参数处理
         */
        if ($time_type && $end_time && $start_time > $end_time) {
            return [
                'count' => 0,
                'data' => [],
                'page' => $page,
                'pageSize' => $pageSize,
            ];
        }
        !$page and $page = 1;
        if ($page > $pageSize) {
            $pageSize = $page;
        }

        /**
         * field处理
         */
        if ($field == '*') {
            $field = 'am.*';
        } else {
            $fieldArr = explode(',', $field);
            foreach ($fieldArr as $f) {
                $field .= 'am.' . $f;
            }
        }

        if (empty($field)) {
            throw new Exception('参数错误');
        }

        /**
         * where数组条件
         */
        $where = [];
        $seller_id and $where['c.seller_id'] = $seller_id;
        $customer_id and $where['c.customer_id'] = $customer_id;
        $is_invalid >= 0 and $where['am.is_invalid'] = $is_invalid;
        $is_authorization >= 0 and $where['am.is_authorization'] = $is_authorization;
        $site and $where['am.site'] = $site;
        $status >= 0 and $where['am.status'] = $status;
        $site_status >= 0 and $where['s.site_status'] = $site_status;

        if ($taskName && $taskCondition && !is_string($taskTime)) {
            $where['am.' . $taskName] = [$taskCondition, $taskTime];
        }

        if ($snType && $snText) {
            $where['am.' . $snType] = ['like', '%' . $snText . '%'];
        }

        /**
         * 需要按时间查询时处理
         */
        if ($time_type) {
            /**
             * 处理需要查询的时间类型
             */
            switch ($time_type) {
                case 'register':
                    $time_type = 'a.register_time';
                    break;
                case 'fulfill':
                    $time_type = 'a.fulfill_time';
                    break;

                default:
                    $start_time = 0;
                    $end_time = 0;
                    break;
            }
            /**
             * 设置条件
             */
            if ($start_time && $end_time) {
                $where[$time_type] = ['between time', [$start_time, $end_time]];
            } else {
                if ($start_time) {
                    $where[$time_type] = ['>', $start_time];
                }
                if ($end_time) {
                    $where[$time_type] = ['<', $end_time];
                }
            }
        }

        $count = $this->model($channelId)
            ->alias('am')
            ->where($where)
            ->join('__ACCOUNT__ a', 'a.id=am.base_account_id')
            ->join('__CHANNEL_USER_ACCOUNT_MAP__ c', 'c.account_id=am.id AND c.channel_id=a.channel_id', 'LEFT')
            ->join('__ACCOUNT_SITE__ s', 's.base_account_id=am.base_account_id AND s.account_code=am.code', 'LEFT')
            ->count();

        //没有数据就返回
        if (!$count) {
            return [
                'count' => 0,
                'data' => [],
                'page' => $page,
                'pageSize' => $pageSize,
            ];
        }

        $field .= ',s.site_status,c.seller_id,c.customer_id,a.register_time,a.fulfill_time';
        //有数据就取出
        $list = $this->model($channelId)
            ->alias('am')
            ->field($field)
            ->join('__ACCOUNT__ a', 'a.id=am.base_account_id')
            ->join('__CHANNEL_USER_ACCOUNT_MAP__ c', 'c.account_id=am.id AND c.channel_id=a.channel_id', 'LEFT')
            ->join('__ACCOUNT_SITE__ s', 's.base_account_id=am.base_account_id AND s.account_code=am.code', 'LEFT')
            ->where($where)
            ->page($page, $pageSize)
            ->order('am.id DESC')
            ->select();

        return [
            'count' => $count,
            'data' => $list,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    /**
     * @author lingjiawen
     * @dateTime 2019-05-04
     * @param    integer    $channelId 平台id
     * @param    string     $ids        ids 1,2,3
     * @return   bool       true
     * @return JsonErrorException
     */
    public function enableAccount($channelId = 0, string $ids = '')
    {
        return true;
    }

    /**
     * @author lingjiawen
     * @dateTime 2019-05-04
     * @param    integer    $channelId 平台id
     * @param    string     $ids        ids 1,2,3
     * @return   bool       true
     * @return JsonErrorException
     */
    public function disenableAccount($channelId = 0, string $ids = '')
    {
        return true;
    }
}
