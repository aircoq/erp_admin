<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-23
 * Time: 上午10:25
 */

namespace app\customerservice\filter;


use app\common\filter\BaseFilter;
use app\common\model\amazon\AmazonAccount;
use app\common\model\paypal\PaypalAccount;
use app\common\service\ChannelAccountConst;
use app\index\service\MemberShipService;
use app\common\service\Common;
use app\common\traits\User;

class PaypalAccountFilter extends BaseFilter
{
    use User;
    protected $scope = 'PaypalAccount';

    public static function getName(): string
    {
        return 'Paypal通用帐号权限过滤器';
    }

    public static function config(): array
    {
        $model = new PaypalAccount();
        $options = $model->field('id as value,account_name as label')->select();
        return [
            'key' => 'type',
            'type' => static::TYPE_SELECT,
            'options' => $options
        ];
    }

    public function generate()
    {
        $type = $this->getConfig();
        if (is_array($type)) {
            $accounts = $type;
        } else {
            $accounts = [$type];
        }
        //查询账号
        //$userInfo = Common::getUserInfo();
        //
        //$memberShipService = new MemberShipService();
        //
        ////获取自己和下级用户
        //$users = $this->getUnderlingInfo($userInfo['user_id']);
        //
        //$accounts=[];
        //
        //if($users)
        //{
        //    foreach ($users as $user)
        //    {
        //        $accountList = $memberShipService->getAccountIDByUserId($user, ChannelAccountConst::channel_amazon);
        //        $accounts = array_merge($accounts,$accountList);
        //    }
        //}else{
        //    $accountList=[];
        //    $accounts = array_merge($accounts,$accountList);
        //}
        //$accounts = array_merge($type,$accounts);
        return $accounts;
    }
}