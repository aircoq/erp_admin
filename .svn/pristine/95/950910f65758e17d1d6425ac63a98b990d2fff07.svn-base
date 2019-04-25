<?php

namespace app\goods\filter;

use app\common\cache\Cache;
use app\common\filter\BaseFilter;
use app\common\service\Common;
use app\common\traits\User;
use app\index\service\MemberShipService;

/** 平台过滤渠道信息
 * Created by PhpStorm.
 * User: denghaibo
 * Date: 2018/12/24
 * Time: 14:12
 */
class ChannelByChannelFilter extends BaseFilter
{
    use User;
    protected $scope = 'Channel';

    public static function getName(): string
    {
        return '通过平台过滤渠道数据';
    }

    public static function config(): array
    {
        $channelList = Cache::store('channel')->getChannel();
        $channelData = [];
        foreach ($channelList as $k => $value){
            $temp['value'] = $value['id'];
            $temp['label'] = $value['name'];
            array_push($channelData,$temp);
        }
        return [
            'key' => 'type',
            'type' => static::TYPE_MULTIPLE_SELECT,
            'options' => $channelData
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
        $type = $this->getConfig();
        $userInfo  = Common::getUserInfo();
        $userList = $this->getUnderlingInfo($userInfo['user_id']);
        $channels = [];
        foreach ($userList as $k => $user_id){
            $channelData = (new MemberShipService())->getBelongChannel($user_id);
            if(is_array($channelData)){
                $channels = array_merge($channels,$channelData);
            }
        }
        $channels = array_unique($channels);
        if(is_array($type)){
            $channels = array_merge($channels,$type);
        }else{
            array_push($channels,$type);
        }
        return $channels;
    }
}