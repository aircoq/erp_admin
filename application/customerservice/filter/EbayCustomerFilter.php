<?php

namespace app\customerservice\filter;

use app\common\model\ChannelUserAccountMap;
use app\common\service\Common;
use app\common\model\ebay\EbayAccount;
use app\index\service\MemberShipService;
use app\common\filter\BaseFilter;
use app\common\traits\User;
use think\Exception;

class EbayCustomerFilter extends BaseFilter
{
    use User;

    protected $scope = 'EbayCustomer';

    public static function getName(): string
    {
        return 'Ebay客服权限过滤器';
    }

    public static function config(): array
    {
        $service = new MemberShipService();
        $list = $service->member(1, 0, 'customer');
        $options = [];
        foreach ($list as $val) {
            $options[] = [
                'label' => $val['realname'],
                'value' => $val['customer_id']
            ];
        }
        return [
            'key' => 'type',
            'type' => static::TYPE_SELECT,
            'options' => $options
        ];
    }

    public function generate()
    {
        try {
            $userIds = [];
            $userInfo = Common::getUserInfo();
            $userIds[] = $userInfo['user_id'];

            $customer_ids = $this->getConfig();
            if (!empty($userIds[0]) && is_numeric($userIds[0])) {
                $userIds = array_merge($userIds, $customer_ids);
            }

            //找出下属人员；
            foreach ($userIds as $id) {
                $users = $this->getUnderlingInfo($id);
                $userIds = array_merge($userIds, $users);
            }

            return array_merge(array_unique($userIds));
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }
}
