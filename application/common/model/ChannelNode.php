<?php
namespace app\common\model;

use think\Model;
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/10/22
 * Time: 14:46
 */
class ChannelNode extends Model
{
    // 关联类型0-基础资料 1-paypal  2-邮局 3-海卖助手
    const module_base = 0;
    const module_paypal = 1;
    const module_email = 2;
    const module_sell = 3;


    /**
     * 基础账号信息
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 检测是否存在，如果存在返回改数据
     * @param $channel_id
     * @param $channel_site
     * @return array|bool|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isHas($channel_id,$channel_site,$website_url)
    {

        $where['channel_id'] = ['=',$channel_id];
        $where['channel_site'] = ['=',$channel_site];
        $where['website_url'] = ['=',$website_url];
        $result = $this->where($where)->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return $result;
    }

    /**
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllChannelNode()
    {
        $reData = [];
        $field = 'channel_id,channel_site,website_url,username_attribute_name,username_attribute_value,password_attribute_name,password_attribute_value,submit_attribute_name,submit_attribute_value';
        $list = $this->field($field)->select();
        foreach ($list as $item){
            $reData[$item['channel_id'].$item['channel_site']] = $item;
        }
        return $reData;
    }

    /**
     * @param $channelId
     * @param string $channelSite
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getChannelNode($channelId,$channelSite = '',$website_url = '')
    {
        $where['channel_id'] = ['eq',$channelId];
        if($channelSite){
            $where['channel_site'] = ['eq',$channelSite];
        }
        if($website_url){
            $where['website_url'] = ['eq',$website_url];
        }
        $field = 'channel_id,channel_site,node_info';
        $info = $this->field($field)->where($where)->find();
        $info['node_info'] = json_decode($info['node_info'],true);
        return $info;
    }

    /**
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getChannelNodeUrl()
    {
        $reData = [];
        $field = 'channel_id,channel_site,website_url';
        $list = $this->field($field)->select();
        foreach ($list as $item){
            $reData[] = $item;
        }
        return $reData;
    }

}