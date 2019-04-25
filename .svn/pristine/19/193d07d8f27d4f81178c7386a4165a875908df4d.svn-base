<?php

namespace app\goods\controller;

use app\common\service\ChannelAccountConst;
use app\common\service\Filter;
use app\common\traits\User;
use app\goods\filter\ChannelByChannelFilter;
use think\Request;
use app\common\controller\Base;
use app\common\cache\Cache;

/**
 * Class ChannelCategory
 * @title 平台分类绑定
 * @module 商品系统
 * @author ZhaiBin
 * @package app\goods\controller
 */
class ChannelCategory extends Base
{
    use User;

    /**
     * @title 获取所有的平台
     * @url /channel-categories
     * @method get
     * @return \think\Response
     * @apiFilter app\goods\filter\ChannelByChannelFilter
     */
    public function index()
    {
        $request = Request::instance();
        $channelList = Cache::store('channel')->getChannel();
        $result = [];
        $is_filter = false;
        if (strpos($request->header('referer'), 'msg-tpl') !== false) {
            $is_filter = true;
        }
        if ($is_filter) {
            if (!$this->isAdmin()) {
                $channels = [];
                $object = new Filter(ChannelByChannelFilter::class,true);
                if ($object->filterIsEffective()) {
                    $channels = $object->getFilterContent();
                }
                foreach ($channelList as $key => $value) {
                    if (in_array($value['id'], $channels)) {
                        array_push($result, $value);
                    }
                }
            } else {
                $channels = [ChannelAccountConst::channel_ebay, ChannelAccountConst::channel_amazon, ChannelAccountConst::channel_aliExpress];
                foreach ($channelList as $key => $value) {
                    if (in_array($value['id'], $channels)) {
                        array_push($result, $value);
                    }
                }
            }
        } else {
            foreach ($channelList as $key => $value) {
                array_push($result, $value);
            }
        }
        return json($result, 200);
    }

    /**
     * @title 获取部分平台
     * @url /channel-part
     * @method get
     * @return \think\Response
     * @apiFilter app\goods\filter\ChannelByChannelFilter
     */
    public function getPartialChannel()
    {
        $request = Request::instance();
        $params = $request->param();
        $channelList = Cache::store('channel')->getPartialChannel($params['channel_id']);
        $result = [];
        foreach ($channelList as $key => $value) {
            array_push($result, $value);
        }
        return json($result, 200);
    }

    /**
     * @title 获取平台的站点
     * @url /channel-categories/:id(\w+)
     * @method get
     * @param  string $id
     * @return \think\Response
     */
    public function read($id)
    {
        $result = Cache::store('channel')->getSite($id, true);
        return json(array_values($result), 200);
    }

    /**
     * @title 获取平台下某站点所有分类
     * @method get
     * @url /channel-categories/:channel(\w+)/:site(\w+)
     * @return \think\Response
     */
    public function siteCategory()
    {
        $request = Request::instance();
        $params = $request->param();
        if (is_numeric($params['site'])) {
            $result = Cache::store('channel')->getCate($params['channel'], $params['site'], null);
        } else {
            $result = Cache::store('channel')->getCate($params['channel'], null, $params['site']);
        }
        return json($result, 200);
    }

    /**
     * @title 获取分类
     * @method get
     * @url /channel-categories/:channel(\w+)/:site(\w+)/:cid(\w+)
     * @return \think\Response
     */
    public function getCategory()
    {
        $request = Request::instance();
        $params = $request->param();
        $result = Cache::store('channel')->getCate($params['channel'], $params['cid'], $params['site']);
        return json($result, 200);
    }
}
