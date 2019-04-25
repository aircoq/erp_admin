<?php
namespace app\report\filter;

use app\common\cache\Cache;
use app\common\filter\BaseFilter;
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
class MonthlyTargetAmountFilter extends BaseFilter
{
    use User;
    protected $scope = 'MonthlyTargetAmount';

    public static function getName(): string
    {
        return '通过用户ID过滤报表数据';
    }

    public static function config(): array
    {
        $targetUserMapMode = new MonthlyTargetDepartmentUserMap();
        $title = $targetUserMapMode->where('status',0)->column('department_id','user_id');
        $allDerpartment = (new MonthlyTargetDepartmentService())->getAllDepartmentTree();
        $options = [];
        foreach ($title as $user_id => $department_id) {
            $realname = Cache::store('user')->getOneUserRealname($user_id);
            $newTitleData['value'] = $user_id;
            $newTitleData['label'] = $allDerpartment[$department_id]['name_path'] .'-' .$realname;
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
        foreach ($type as $key =>$user_id){
            if($user_id == 0){
                $user = Common::getUserInfo();
                $type[$key] = (int)$user['user_id'];
                break;
            }
        }
        return $type;
    }
}