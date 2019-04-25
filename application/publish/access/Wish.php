<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-11-24
 * Time: 下午2:13
 */

namespace app\publish\access;

use erp\AbsFilterRule;
use erp\FilterConfig;
use erp\FilterParam;
class Wish extends AbsFilterRule
{
    private $model;
    public function getTitle()
    {
        return "wish在线listing";
    }

    public function filter()
    {
        $channel = $this->getParam('channel');
        $this->model->where('id', $channel->channel);
    }

    public function paramsConfig(FilterConfig $config)
    {
        $server = new \app\common\service\Channel();
        $param1 = new FilterParam('channel',"平台");
        $param1->setOpts($server->getOptions());
        $config->addConfig($param1);
    }
}