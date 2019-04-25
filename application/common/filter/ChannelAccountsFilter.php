<?php
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/11/28
 * Time: 16:33
 */

namespace app\common\filter;
use app\common\cache\Cache;
use app\common\service\Common;
use app\common\traits\User;
use app\common\filter\BaseFilter;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\amazon\AmazonAccount;
use app\common\model\ebay\EbayAccount;
use app\common\model\wish\WishAccount;
use app\common\service\ChannelAccountConst;
use app\common\service\OrderType;
use app\index\service\MemberShipService;

class ChannelAccountsFilter extends BaseFilter
{
    use User;
    protected $scope = 'ChannelAccounts';

    public static function getName(): string
    {
        return '通过平台账号过滤数据';
    }

    public static function config(): array
    {
        $amazonList = (new AmazonAccount())->field('id as value, code as label')->select();
        $ebayList = (new EbayAccount())->field('id as value, code as label')->select();
        $wishList = (new WishAccount())->field('id as value, code as label')->select();
        $aliexpressList = (new AliexpressAccount())->field('id as value, code as label')->select();
        $amazonList = array_map(function ($info) {
            $info = $info->toArray();
            $info['value'] = ChannelAccountConst::channel_amazon * OrderType::ChannelVirtual + $info['value'];
            $info['label'] = '【amazon】' . $info['label'];
            return $info;
        }, $amazonList);
        $ebayList = array_map(function ($info) {
            $info = $info->toArray();
            $info['value'] = ChannelAccountConst::channel_ebay * OrderType::ChannelVirtual + $info['value'];
            $info['label'] = '【ebay】' . $info['label'];
            return $info;
        }, $ebayList);
        $wishList = array_map(function ($info) {
            $info = $info->toArray();
            $info['value'] = ChannelAccountConst::channel_wish * OrderType::ChannelVirtual + $info['value'];
            $info['label'] = '【wish】' . $info['label'];
            return $info;
        }, $wishList);
        $aliexpressList = array_map(function ($info) {
            $info = $info->toArray();
            $info['value'] = ChannelAccountConst::channel_aliExpress * OrderType::ChannelVirtual + $info['value'];
            $info['label'] = '【aliexpress】' . $info['label'];
            return $info;
        }, $aliexpressList);
        $options = array_merge($amazonList, $ebayList, $wishList, $aliexpressList);
        return [
            'key' => 'type',
            'type' => static::TYPE_SELECT,
            'options' => $options
        ];
    }

    /**
     *
     * @return array|bool|mixed|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function generate()
    {
        //查询账号
        $userInfo = Common::getUserInfo();
        $cache = Cache::handler();
        $key = 'cache:OrderByAccountFilterByUserId:' . $userInfo['user_id'];
        if ($cache->exists($key)) {
            $accountId = $cache->get($key);
            return json_decode($accountId,true);
        }
        $type = $this->getConfig();
        foreach ($type as $k => &$virtual) {
            if ($virtual < OrderType::ChannelVirtual) {
                $virtual = ChannelAccountConst::channel_amazon * OrderType::ChannelVirtual + $virtual;
            }
        }
        $memberShipService = new MemberShipService();
        //获取自己和下级用户
        $userList = $this->getUnderlingInfo($userInfo['user_id']);
        $accountId = [];
        if (!empty($userList)) {
            foreach ($userList as $user_id) {
                $accountList = $memberShipService->getAccountIDByUserId($user_id, 0, true);
                $accountId = array_merge($accountId, $accountList);
            }
            $accountId = array_merge($accountId, $type);
        } else {
            $accountList = $memberShipService->getAccountIDByUserId($userInfo['user_id'], 0, true);
            $accountId = array_merge($type, $accountList);
        }
        $accountId = array_unique($accountId);
        if (count($accountId) > 50) {
            Cache::handler()->set($key, json_encode($accountId), 60 * 10);
        }
        return $accountId;
    }
}