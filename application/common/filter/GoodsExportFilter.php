<?php


namespace app\common\filter;
use app\common\service\Common;
use app\common\traits\User;
use app\goods\service\GoodsImport;

class GoodsExportFilter extends BaseFilter
{
    use User;
    protected $scope = 'ExportTemplate';

    public static function getName(): string
    {
        return '商品导出字段权限过滤';
    }

    public static function config(): array
    {
        $GoodsImport = new GoodsImport();
        $tmp = $GoodsImport->getBaseField();
        $options = [];
        foreach ($tmp as $v){
            $row = [];
            $row['value'] = $v['key'];
            $row['label'] = $v['title'];
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
