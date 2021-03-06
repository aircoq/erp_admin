<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/1/2
 * Time: 11:57
 */

namespace app\index\filter;


use app\common\filter\BaseFilter;
use app\common\model\amazon\AmazonAccount;
use app\common\model\ebay\EbayAccount;
use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use app\common\model\User as UserModel;
use app\common\traits\User;
use app\index\service\MemberShipService;
use think\Exception;


class AmazonAccountHealthFilter extends BaseFilter
{
    use User;
    protected $scope = 'AmazonAccountHealth';
    public static function getName(): string
    {
        return 'AmazonAccountHealth权限过滤器';
    }

    public static function config(): array
    {
        $amazonModel = new AmazonAccount();
        $wh['status'] = 1;
        $wh['is_authorization'] = 1;
        $options = $amazonModel->field('id as value, code as label')->where($wh)->select();
        return [
            'key' => 'type',
            'type' => static::TYPE_SELECT,
            'options' => $options
        ];
    }

    public function generate()
    {
        try {
            $type = $this->getConfig();
            $userInfo = Common::getUserInfo();
            $users = $this->getUnderlingInfo($userInfo['user_id']);
            $users = array_unique($users);
            //过滤掉停用的用户
            $whUser['status'] = 1;
//            $whUser['job'] = 'sales';
            $whUser['id'] = ['in', $users];
            $validUserIds = UserModel::where($whUser)->column('id');

            $memberShipService = new MemberShipService();
            $accountList = [];
            foreach ($validUserIds as $k => $validUserId) {
                $temp = $memberShipService->getAccountIDByUserId($validUserId, ChannelAccountConst::channel_amazon);
                $accountList = array_merge($temp, $accountList);
            }
            $accountIds = array_merge($type, $accountList);
            array_push($accountIds, 0);
            $accountIds = array_values(array_unique($accountIds));
            return $accountIds;
        } catch (\Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

}