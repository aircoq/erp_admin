<?php

namespace app\customerservice\filter;

use app\common\cache\Cache;
use app\common\filter\BaseFilter;
use app\common\service\Common;
use app\common\traits\User;
use app\index\service\MemberShipService;
use app\common\service\ChannelAccountConst;

/** 关键词过滤渠道信息
 * Created by PhpStorm.
 * User: denghaibo
 * Date: 2019/3/4
 * Time: 14:12
 */
class KeywordChannelFilter extends BaseFilter
{
    use User;
    protected $scope = 'Channel';

    public static function getName(): string
    {
        return '根据权限过滤渠道';
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
        $result = [];
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
        $channelList = array_unique($channels);

        $channels_default = [ChannelAccountConst::channel_ebay, ChannelAccountConst::channel_amazon, ChannelAccountConst::channel_aliExpress];
        foreach ($channelList as $key => $value) {
            if (in_array($value, $channels_default)) {
                array_push($result, $value);
            }
        }

        if(is_array($type)){
            $result = array_merge($result,$type);
        }else{
            array_push($result,$type);
        }

        return $result;
    }
}