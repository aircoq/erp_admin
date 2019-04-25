<?php

namespace app\report\filter;

use app\common\cache\Cache;
use app\common\filter\BaseFilter;
use app\common\traits\User;

/** 平台过滤渠道信息
 * Created by PhpStorm.
 * User: zhaixueli
 * Date: 2019/1/18
 * Time: 11:33
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
        return $type;
    }
}