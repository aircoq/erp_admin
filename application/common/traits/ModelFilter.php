<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-29
 * Time: 下午6:13
 */

namespace app\common\traits;

use app\common\exception\FilterRuleException;
use app\common\filter\BaseFilter;
use app\common\service\Common;
use app\index\service\Role;
use erp\ErpRbac;
use think\db\Query;
use think\Model;
use think\Request;

const ignore_modules = [
    \app\common\model\User::class,
    \app\common\model\Role::class,
    \app\common\model\McaNode::class,
];
trait ModelFilter
{
    protected static $count = 10;

    public static function roleFilter($query = null, $model = null)
    {
        $userInfo = Common::getUserInfo();
        if (!(new Role())->isAdmin($userInfo['user_id'])) {
            if (!in_array($model, ignore_modules)) {
                $rbac = ErpRbac::getRbac();
                $filters = $rbac->getFilters();
                foreach ($filters as $filter => $fparams) {
                    $filter = new $filter($fparams);
                    $result = $filter->filter();
                    list('scope'=>$scope, 'class'=>$class, 'params'=>$params) = $result;
                    static::filter($query, $class, $scope, $params);
                }
            }
        }
    }

    protected static function filter($query, $class, $scope, $params)
    {
        $scopeMethod = "scope$scope";
        $object = new static();
        if (method_exists($object, $scopeMethod)) {
            $params = [$params];
            array_unshift($params, $query);
            call_user_func_array([new static(), $scopeMethod], $params);
            return true;
        }
    }

    /** 作废
     * private function getFilterClass($query)
     * {
     * foreach ($filters as $filter => $params) {
     * $wheres = [];
     * foreach ($params as $param) {
     * $filter = new $filter($param);
     * if (!$filter->checkModel($model)) {
     * dump_detail($model);
     * continue;
     * }
     * $filter->generate();
     * $where = $filter->getWhere();
     * foreach ($where as list($op, $field, $value, $cond)) {
     * $ops = $wheres[$op] ?? [];
     * $cond = strtolower($cond);
     * $ops[$field] = $ops[$field] ?? [];
     * $ops[$field][$cond] = $ops[$field][$cond]??[];
     * array_push($ops[$field][$cond], $value);
     * $wheres[$op] = $ops;
     * }
     * }
     * dump_detail($wheres);
     * foreach ($wheres as $op => $where){
     * switch ($op){
     * case 'AND':
     * foreach ($where as $field => $conds){
     * foreach ($conds as $operate => $values){
     * switch ($operate){
     * case '=':
     * $values = array_unique($values);
     * if(count($values)>1){
     * $query->where($field,'in', $values);
     * }else{
     * dump_detail($values);
     * $query->where($field, $values[0]);
     * };
     * break;
     * case '>=':
     * case '>':
     * $values = min($values);
     * $query->where($field, $operate,  $values);
     * break;
     * case '<=':
     * case '<':
     * $values = max($values);
     * dump_detail($values);
     * $query->where($field, $operate,  $values);
     * break;
     * default:
     * dump_detail("not support $operate");
     * }
     * }
     * }
     * break;
     *
     * }
     * }
     * }
     *
     * $params = $filter->generate();
     * $this->filter(get_class($filter), $filter->scope, $params);
     * }
     */
}