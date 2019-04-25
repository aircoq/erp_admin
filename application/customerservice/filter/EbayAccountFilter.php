<?php

namespace app\customerservice\filter;

use app\common\model\ChannelUserAccountMap;
use app\common\service\Common;
use app\common\model\ebay\EbayAccount;
use app\index\service\MemberShipService;
use app\common\filter\BaseFilter;
use app\common\traits\User;
use think\Exception;

class EbayAccountFilter extends BaseFilter
{
    use User;

    protected $scope = 'EbayAccount';

    public static function getName(): string
    {
        return 'Ebay通用帐号权限过滤器';
    }

    public static function config(): array
    {
        $ebayModel = new EbayAccount();
        $options = $ebayModel->field('id as value, code as label')->select();
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
            $memberShipService = new MemberShipService();
            $accountList = [];
            foreach ($users as $k => $user) {
                $temp = $memberShipService->getAccountIDByUserId($user, 1);
                $accountList = array_merge($temp, $accountList);
            }
            //传过来的参数可能是帐号ID，也可能是0，如果是0，就不要加进去；
            if ($type != 0) {
                $accountIds = array_merge(array_unique(array_filter(array_merge($type, $accountList))));
            } else {
                $accountIds = array_merge(array_unique(array_filter($accountList)));
            }

            //添加范本共享功能时，以accountId过滤不好处理，添加shared_userid限制
            $userChannelInfo = ChannelUserAccountMap::get(['seller_id' => $userInfo['user_id']]);//目前的逻辑，一个用户只可能绑定一个仓库，只需获取一条数据即可
            if (empty($userChannelInfo)) {//如果获取不到，就获取他下属人员的信息
                foreach ($users as $user) {
                    $userChannelInfo = ChannelUserAccountMap::get(['seller_id' => $user]);
                    if (!empty($userChannelInfo)) {
                        break;//获取一个即可
                    }
                }
            }
            //获取同为本地仓/海外仓的用户id
            $shared_userId = [];
            if (!empty($channelInfo)) {
                $channelInfo = (new ChannelUserAccountMap())->field('seller_id ')->distinct(true)->where(['warehouse_type' => $userChannelInfo->warehouse_type])->select();
                foreach ($channelInfo as $k => $v) {
                    $shared_userId[] = $v['seller_id'];
                }
            }
            return [$accountIds, $shared_userId];
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }
}
