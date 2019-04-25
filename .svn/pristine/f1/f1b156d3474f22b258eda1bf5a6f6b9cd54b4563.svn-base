<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\Lang as langModel;
/**
 * Created by Netbean.
 * User: empty
 * Date: 2016/12/23
 * Time: 12:05
 */
class Lang extends Cache
{
    /** 获取标签字典
     * @return array
     */
    public function getLang()
    {
        if($this->redis->exists('cache:lang')){
            $result = json_decode($this->redis->get('cache:lang'),true);
            return $result;
        }
        //查表
        $result = langModel::field('id,name as code,title as name')->where(['status' => 1])->select();
        $this->redis->set('cache:lang',json_encode($result));
        return $result;
    }
}

