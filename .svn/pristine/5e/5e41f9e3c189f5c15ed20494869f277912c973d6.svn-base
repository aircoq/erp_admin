<?php

namespace app\report\filter;

use app\common\cache\Cache;
use app\common\filter\BaseFilter;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\amazon\AmazonAccount;
use app\common\model\ebay\EbayAccount;
use app\common\model\wish\WishAccount;
use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use app\common\traits\User;
use app\common\model\User as ModelUser;
use app\index\service\MemberShipService;

/**
 * Created by PhpStorm.
 * User: ZhouFurong
 * Date: 2019/4/25
 * Time: 16:20
 */
class AccountOperationFilter extends BaseFilter
{
    use User;
    protected $scope = 'AccountChannel';

    public static function getName(): string
    {
        return '通过账号过滤账号运营分析数据';
    }

    public static function config(): array
    {
	    $amazonList = (new AmazonAccount())->field('id as value, code as label')->select();
	    $ebayList = (new EbayAccount())->field('id as value, code as label')->select();
	    $wishList = (new WishAccount())->field('id as value, code as label')->select();
	    $aliexpressList = (new AliexpressAccount())->field('id as value, code as label')->select();
	    $amazonList = array_map(function ($info) {
		    $info = $info->toArray();
		    $info['value'] = ChannelAccountConst::channel_amazon  + $info['value'];
		    $info['label'] = '【amazon】' . $info['label'];
		    return $info;
	    }, $amazonList);
	    $ebayList = array_map(function ($info) {
		    $info = $info->toArray();
		    $info['value'] = ChannelAccountConst::channel_ebay *  + $info['value'] ;
		    $info['label'] = '【ebay】' . $info['label'];
		    return $info;
	    }, $ebayList);
	    $wishList = array_map(function ($info) {
		    $info = $info->toArray();
		    $info['value'] = ChannelAccountConst::channel_wish  + $info['value'];
		    $info['label'] = '【wish】' . $info['label'];
		    return $info;
	    }, $wishList);
	    $aliexpressList = array_map(function ($info) {
		    $info = $info->toArray();
		    $info['value'] = ChannelAccountConst::channel_aliExpress + $info['value'];
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

    public function generate($userId = 0)
    {
	    //查询账号
	    $memberShipService = new MemberShipService();
	    $channelId = [];
	    if($userId == 0){
		    $userInfo = Common::getUserInfo();
		    $userId = $userInfo['user_id'];
		    $channelId = $memberShipService->getBelongChannel($userId);
	    }
	    $cache = Cache::handler();
	    $key = 'cache:AccountOperationByUserId:' . $userId;
	    if ($cache->exists($key)) {
		    $accountId = $cache->get($key);
		    return json_decode($accountId,true);
	    }
	    $type = $this->getConfig();
	    //获取自己和下级用户
	    $userList = $this->getUnderlingInfo($userId);
	    $accountId = [];
	    if(!empty($userList)) {
	    	foreach ($userList as $user_id){
	    		$accountList = $memberShipService->getAccountIDByUserId($user_id,0,true);
	    		$accountId = array_merge($accountId,$accountList);
		    }
	    	$accountId = array_merge($accountId,$type);
	    }else{
		    $accountList = $memberShipService->getAccountIDByUserId($userId, 0, true);
		    $accountId = array_merge($type, $accountList);
	    }
	    $accountId = array_unique($accountId);
	    if (count($accountId) > 50) {
		    Cache::handler()->set($key, json_encode($accountId), 60 * 10);
	    }
		$data = [
			'account_id'=>$accountId,
			'channel_id'=>$channelId
		];
	    return $data;
    }
}

