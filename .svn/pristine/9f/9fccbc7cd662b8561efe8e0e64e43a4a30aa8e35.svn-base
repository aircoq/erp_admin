<?php
namespace app\customerservice\filter;

use app\common\filter\BaseFilter;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\amazon\AmazonAccount;
use app\common\model\ebay\EbayAccount;
use app\common\model\wish\WishAccount;
use app\common\service\ChannelAccountConst;
use app\common\service\OrderType;
use app\common\service\Common;
use app\common\traits\User;
use app\index\service\MemberShipService;

/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2018/10/16
 * Time: 17:08
 */
class OrderSaleAccountFilter extends BaseFilter
{
    use User;
    protected $scope = 'OrderSale';

    public static function getName(): string
    {
        return '通过账号过滤售后单数据';
    }

    public static function config(): array
    {
        $amazonList = (new AmazonAccount())->field('id as value, code as label')->select();
        $ebayList = (new EbayAccount())->field('id as value, code as label')->select();
        $wishList = (new WishAccount())->field('id as value, code as label')->select();
        $aliexpressList = (new AliexpressAccount())->field('id as value, code as label')->select();
        $amazonList = array_map(function($info){
            $info = $info->toArray();
            $info['value'] = ChannelAccountConst::channel_amazon * OrderType::ChannelVirtual + $info['value'];
            $info['label'] = '【amazon】'.$info['label'];
            return $info;
        },$amazonList);
        $ebayList = array_map(function($info){
            $info = $info->toArray();
            $info['value'] = ChannelAccountConst::channel_ebay * OrderType::ChannelVirtual + $info['value'];
            $info['label'] = '【ebay】'.$info['label'];
            return $info;
        },$ebayList);
        $wishList = array_map(function($info){
            $info = $info->toArray();
            $info['value'] = ChannelAccountConst::channel_wish * OrderType::ChannelVirtual + $info['value'];
            $info['label'] = '【wish】'.$info['label'];
            return $info;
        },$wishList);
        $aliexpressList = array_map(function($info){
            $info = $info->toArray();
            $info['value'] = ChannelAccountConst::channel_aliExpress * OrderType::ChannelVirtual + $info['value'];
            $info['label'] = '【aliexpress】'.$info['label'];
            return $info;
        },$aliexpressList);
        $options = array_merge($amazonList,$ebayList,$wishList,$aliexpressList);
        return [
            'key' => 'type',
            'type' => static::TYPE_SELECT,
            'options' => $options
        ];
    }

    public function generate()
    {
        $type = $this->getConfig();
        foreach ($type as $k => &$virtual){
            if($virtual < OrderType::ChannelVirtual){
                $virtual = ChannelAccountConst::channel_amazon * OrderType::ChannelVirtual + $virtual;
            }
        }
        //查询账号
        $userInfo = Common::getUserInfo();
        $memberShipService = new MemberShipService();
        //获取自己和下级用户
        $userList = $this->getUnderlingInfo($userInfo['user_id']);
        $accountId = [];
        if (!empty($userList)) {
            foreach ($userList as $user_id) {
                $accountList = $memberShipService->getAccountIDByUserId($user_id,0,true);
                $accountId = array_merge($accountId, $accountList);
            }
            $accountId = array_merge($accountId, $type);
        } else {
            $accountList = $memberShipService->getAccountIDByUserId($userInfo['user_id'],0,true);
            $accountId = array_merge($type, $accountList);
        }
        return $accountId;
    }


}