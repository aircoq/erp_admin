<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-7
 * Time: 下午6:26
 */

namespace app\index\service;

use app\common\model\Channel;
use app\common\model\Config;
use think\Exception;
use app\common\model\ChannelConfig as Model;
use app\common\traits\ConfigCommon;

class ChannelConfig
{
    use ConfigCommon;

    private $channelId;
    private $model;

    /**
     * 初始化函数
     * @param int $channel_id
     */
    public function __construct($channel_id)
    {
        $this->channelId = $channel_id;
        $this->model = new Model();
    }

    /**
     * @desc 获取仓库配置信息
     * @param null $key
     * @return mixed|string
     * @throws \think\Exception
     */
    public function getConfig($key)
    {
        $dataValue = '';
        $config = $this->model->where('channel_id', $this->channelId)->where('name', $key)->find();
        if(empty($config)){ //没有已系统站点配置的为主
            $dataValue = $this->getConfigData($key);
        } else {
            if (!empty($config)) {
                switch($config['type']){
                    case 1:
                        $dataValue = $config['value'];
                        break;
                    case 2:
                        $dataValue = $config['value'];
                        break;
                    case 3:
                        $dataValue = $config['value'];
                        if(!empty($dataValue)){
                            $dataValue = json_decode($dataValue,true);
                        }
                        break;
                }
            }
        }
        return $dataValue;
    }

    /**
     * 自动回写之前的平台配置信息
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function automationChannelConfig()
    {
        $list = (new Channel())->select();
        foreach ($list as $v){
            if($v['duplication'] == 1){
                $params = [
                    'channel_duplication' => '{"value":"1","child":[]}'
                ];
                $this->addConfig($v['id'],'channel_duplication',$params);
            }
            if($v['config']){
                $data = json_decode($v['config'],true);
                if(isset($data['delivery_deadline']) && $data['delivery_deadline'] > 0) {
                    $params = [
                        'channel_delivery_deadline' => $data['delivery_deadline']
                    ];
                    $this->addConfig($v['id'],'channel_delivery_deadline',$params);
                }
                if(isset($data['list_num']) && $data['list_num'] > 0){
                    $params = [
                        'channel_list_num' => $data['list_num']
                    ];
                    $this->addConfig($v['id'],'channel_list_num',$params);
                }
                if(isset($data['examination_cycle']) && $data['examination_cycle'] > 0){
                    $params = [
                        'channel_examination_cycle' => $data['examination_cycle']
                    ];
                    $this->addConfig($v['id'],'channel_examination_cycle',$params);
                }
            }

        }
    }

    private function addConfig($channelId,$name,$params)
    {
        $id = (new Config())->where('name',$name)->value('id');
        if($id){
            (new ChannelService())->useConfig($channelId, [$id]);
            (new ChannelService())->setting($channelId, $params);
        }
        return true;
    }
}