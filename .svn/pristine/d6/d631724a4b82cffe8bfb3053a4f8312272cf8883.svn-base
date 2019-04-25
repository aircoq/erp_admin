<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\PriceRuleSet;

/** 刊登定价规则
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/7/18
 * Time: 16:50
 */
class PricingRule extends Cache
{
    protected $_key = 'zset:pricing:rule_info_by_channel';

    /** 定价规则信息
     * @param int $channel_id
     * @return array|mixed
     */
    public function pricingRuleInfo($channel_id)
    {
        $key = $this->_key . '_' . $channel_id;
        if ($this->redis->exists($key)) {
            $result = $this->redis->zRange($key, 0, -1, true);
            $new_array = [];
            foreach ($result as $key => $value) {
                array_push($new_array, json_decode($key, true));
            }
            return $new_array;
        }
        $priceRuleSetModel = new PriceRuleSet();
        $result = $priceRuleSetModel->field('id,title,channel_id,sort,end_time,start_time,status,action_value')->with('item')->where(['status' => 0])->where('channel_id',
            ['=', $channel_id], ['=', 0], 'or')->order('sort desc')->select();
        $new_array = [];
        foreach ($result as $k => $v) {
            $v = $v->toArray();
            $this->redis->zAdd($key, $v['sort'], json_encode($v));
            array_push($new_array, $v);
        }
        return $new_array;
    }

    /** 删除定价规则
     * @throws \think\Exception
     */
    public function delPricingRuleInfo()
    {
        $channelList = Cache::store('channel')->getChannel();
        foreach ($channelList as $channel => $list) {
            $key = $this->_key . '_' . $list['id'];
            $this->redis->del($key);
        }
        $key = $this->_key . '_0';
        $this->redis->del($key);
    }
}