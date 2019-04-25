<?php
namespace app\common\traits;
use app\common\cache\Cache;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/12/25
 * Time: 11:05
 */
trait ConfigCommon
{
    private $configIdentification;

    /**
     * @param $key
     */
    public function setConfigIdentification($key)
    {
        $this->configIdentification = $key;
    }

    /**
     * 获取配置信息
     * @param null $key
     * @return mixed|string
     * @throws \think\Exception
     */
    public function getConfigData($key = null)
    {
        if(!empty($key)){
            $this->configIdentification = $key;
        }
        $dataValue = '';
        $config = Cache::store('configParams')->getConfig($this->configIdentification);
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
        return $dataValue;
    }
}