<?php
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/11/28
 * Time: 16:33
 */

namespace app\common\filter;
use app\common\cache\Cache;
use app\common\service\Common;
use app\common\traits\User;

class ChannelsFilter extends BaseFilter
{
    use User;
    protected $scope = 'Channels';

    public static function getName(): string
    {
        return '通过平台过滤数据';
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