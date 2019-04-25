<?php

namespace app\publish\filter;

use app\common\model\ChannelUserAccountMap;
use app\common\model\ebay\EbayListing;
use app\common\service\Common;
use app\common\model\ebay\EbayAccount;
use app\common\model\User as UserModel;
use app\index\service\MemberShipService;
use app\common\filter\BaseFilter;
use app\common\traits\User;
use think\Exception;

/** ebayListing 过滤器
 * User: zengsh
 * Date: 2017/11/24
 */

class EbayListingFilter extends BaseFilter
{
    use User;
    protected $scope = 'EbayListing';
    public static function getName(): string
    {
        return 'EbayListing权限过滤器';
    }

    public static function config(): array
    {
        $ebayModel = new EbayAccount();
        $wh['is_invalid'] = 1;
        $wh['account_status'] = 1;
        $wh['token'] = ['neq',''];
        $options = $ebayModel->field('id as value, code as label')->where($wh)->select();
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
            //过滤掉停用和不是销售员的用户
            $whUser['status'] = 1;
            $whUser['job'] = 'sales';
            $whUser['id'] = ['in', $users];
            $validUserIds = UserModel::where($whUser)->column('id');

            $memberShipService = new MemberShipService();
            $accountList = [];
            foreach ($validUserIds as $k => $validUserId) {
                $temp = $memberShipService->getAccountIDByUserId($validUserId, 1);
                $accountList = array_merge($temp, $accountList);
            }
            $accountIds = array_merge($type, $accountList);
            array_push($accountIds, 0);
            $accountIds = array_values(array_unique($accountIds));
            //添加范本共享功能时，以accountId过滤不好处理，添加shared_userid限制
//            $userChannelInfo = ChannelUserAccountMap::get(['seller_id' => $userInfo['user_id']]);//目前的逻辑，一个用户只可能绑定一个仓库，只需获取一条数据即可
//            if (empty($userChannelInfo)) {//如果获取不到，就获取他下属人员的信息
//                foreach ($users as $user) {
//                    $userChannelInfo = ChannelUserAccountMap::get(['seller_id' => $user]);
//                    if (!empty($userChannelInfo)) {
//                        break;//获取一个即可
//                    }
//                }
//            }
            //获取同为本地仓/海外仓的用户id
//            $channelInfo = (new ChannelUserAccountMap())->field('seller_id ')->distinct(true)->where(['warehouse_type' => $userChannelInfo->warehouse_type, 'channel_id'=>1])->select();
//            $shared_userId = [];
//            foreach ($channelInfo as $k => $v) {
//                if ($v['seller_id'] != 0) {
//                    $shared_userId[] = $v['seller_id'];
//                }
//            }
            return $accountIds;
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }
}
