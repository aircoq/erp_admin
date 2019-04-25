<?php
namespace app\report\filter;

use app\common\cache\Cache;
use app\common\filter\BaseFilter;
use app\common\model\monthly\MonthlyTargetDepartment;
use app\common\model\monthly\MonthlyTargetDepartmentUserMap;
use app\common\service\Common;
use app\common\traits\User;
use app\report\service\MonthlyTargetDepartmentService;
use app\report\service\OrderExportService;

/** 账号过滤订单信息
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/11/05
 * Time: 9:52
 */
class MonthlyTargetAmountDepartmentFilter extends BaseFilter
{
    use User;
    protected $scope = 'MonthlyTargetAmountDepartment';

    public static function getName(): string
    {
        return '通过顶级部门过滤报表数据';
    }

    public static function config(): array
    {
        $where = [
            'status' => 0,
            'pid' => 0,
        ];
        $targetUserMapMode = new MonthlyTargetDepartment();
        $title = $targetUserMapMode->where($where)->order('sort desc')->column('name','id');
        $options = [];
        foreach ($title as $id => $name) {
            $newTitleData['value'] = $id;
            $newTitleData['label'] = $name;
            array_push($options, $newTitleData);
        }
        return [
            'key' => 'type',
            'type' => static::TYPE_SELECT,
            'options' => $options
        ];
    }

    public function generate()
    {
        $type = $this->getConfig();
        return $type;
    }
}