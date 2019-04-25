<?php


namespace app\common\filter;
use app\goods\service\GoodsHelp;

class GoodsTortFilter extends  BaseFilter
{
    protected $scope = 'GoodsTort';

    public static function getName(): string
    {
        return '商品侵权下架渠道过滤';
    }

    public static function config(): array
    {
        $GoodsHelp = new GoodsHelp();
        $showchannel = $GoodsHelp->show_channel;
        $options = [];
        foreach ($showchannel as $v){
            $row = [];
            $row['value'] = $v;
            $row['label'] = $v;
            $options[] = $row;
        }
        return [
            'key' => 'type',
            'type' => static::TYPE_MULTIPLE_SELECT,
            'options' => $options
        ];
    }

    public function generate()
    {
        $type = $this->getConfig();
        return $type;
    }
}