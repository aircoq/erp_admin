<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-8
 * Time: 下午2:38
 */

namespace app\common\service;


use app\common\interfaces\SelectOptions;
use app\common\model\Channel as Model;
use app\publish\interfaces\AliexpressStatistics;
use app\publish\interfaces\WishStat;
use app\publish\interfaces\EbayStat;
use app\publish\service\WishHelper;
use erp\AbsServer;

class Channel extends AbsServer implements SelectOptions
{
    protected $model = \app\common\model\Channel::class;
    public function getChannel($channelId)
    {
        return Model::get($channelId);
    }

    public function getChannels()
    {
        return $this->model->where('status', 0)->select();
    }

    public function getPublishServer($channelId)
    {
        /*switch ($channelId){
            case ChannelAccountConst::channel_wish:
                return new WishStat;
			case ChannelAccountConst::channel_ebay:
                return new EbayStat;
            case ChannelAccountConst::channel_aliExpress:
                return new AliexpressStatistics;
        }*/
        return null;
    }

    public function getOptions($where = [])
    {
        $this->listenFilter(\app\order\access\Channel::class);
        $channels = $this->getChannels();
        return array_map(function($channel){
            return ['label'=>$channel->title, 'value'=>$channel->id];
        },$channels);
    }
}