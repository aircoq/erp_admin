<?php
namespace app\common\filter;

use app\common\model\amazon\AmazonAccount;
use app\index\service\MemberShipService;
use app\common\service\Common;

/** 订单过滤器
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/9/25
 * Time: 15:48
 */
class OrderFieldFilter extends BaseFilter
{
    protected $scope = 'Field';

    public static function getName(): string
    {
        return '订单字段过滤器';
    }

    public static function config(): array
    {
        return [
            'key' => 'type',
            'type' => static::TYPE_SELECT,
            'options' => [
                '渠道id' => 'a.channel_id',
                '订单号' =>'a.order_number'
            ]
        ];
    }

    public function generate()
    {
        $field = $this->getConfig();
        $search = '';
        foreach($field as $key =>$value){
            $search .= $value.',';
        }
        $search = rtrim($search,',');
        return $search;
    }
}