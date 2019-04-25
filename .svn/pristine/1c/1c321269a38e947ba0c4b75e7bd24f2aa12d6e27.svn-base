<?php


namespace app\common\model;


use app\goods\service\TortImport;
use think\Model;
use app\common\cache\Cache;
use app\order\service\OrderService;

class GoodsTortDescription extends Model
{


    private $channelData = [];

    private function getChannel()
    {
        if ($this->channelData === []) {
            $this->channelData = Cache::store('channel')->getChannel();
        }
        return $this->channelData;
    }

    public function getChannelNameAttr($value, $data)
    {
        $result = $this->getChannel();
        $new_list = [];
        foreach ($result as $k => $v) {
            $new_list[$v['id']] = $v['name'];
        }
        return isset($new_list[$data['channel_id']]) ? $new_list[$data['channel_id']] : '';
    }

    private $TEMP_ACCOUNT_NAME = [];

    public function getAccountNameAttr($value, $data)
    {
        $OrderService = new OrderService();
        $key = $data['channel_id'] . "_" . $data['account_id'];
        if (isset($this->TEMP_ACCOUNT_NAME[$key])) {
            return $this->TEMP_ACCOUNT_NAME[$key];
        }
        $this->TEMP_ACCOUNT_NAME[$key] = $OrderService->getAccountName($data['channel_id'], $data['account_id']);
        return $this->TEMP_ACCOUNT_NAME[$key];
    }

    public function getTortTypeAttr($value)
    {
        if ($tortType = (string)array_search($value, TortImport::TYPE)) {
            return $tortType;
        }
        return '';
    }
}