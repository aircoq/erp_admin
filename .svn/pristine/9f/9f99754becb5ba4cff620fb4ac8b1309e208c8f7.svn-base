<?php


namespace app\common\model;
use app\common\traits\ModelFilter;
use think\db\Query;

use erp\ErpModel;

class ExportField extends ErpModel
{
    use ModelFilter;

    public function scopeExportTemplate(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.field_key', 'in', $params);
        }
    }
    public const TYPE_GOODS = 1;
}