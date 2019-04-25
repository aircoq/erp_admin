<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-11-24
 * Time: 下午3:44
 */

namespace app\publish\filter;

use app\common\filter\BaseFilter;
use app\common\model\aliexpress\AliexpressAccount;
use app\index\service\MemberShipService;
use app\common\service\Common;
use app\common\traits\User;

class AliexpressFilter extends BaseFilter
{
    use User;
    protected $scope = 'Listing';

    public static function getName(): string
    {
        return 'aliexpress-listing权限过滤器';
    }

    public static function config(): array
    {
        $model = new AliexpressAccount();
        $options = $model->field('id as value, code as label')->select();
        return [
            'key' => 'type',
            'type' => static::TYPE_SELECT,
            'options' => $options
        ];
    }

    public function generate()
    {
        $type = $this->getConfig();
        //查询账号
        $userInfo = Common::getUserInfo();

        $memberShipService = new MemberShipService();

        //获取自己和下级用户
        $users = $this->getUnderlingInfo($userInfo['user_id']);

        $accounts=[];

        if($users)
        {
            foreach ($users as $user)
            {
                $accountList = $memberShipService->getAccountIDByUserId($user);
                $accounts = array_merge($accounts,$accountList);
            }
        }

        $accounts = array_merge($type,$accounts);

        return $accounts;
    }
}