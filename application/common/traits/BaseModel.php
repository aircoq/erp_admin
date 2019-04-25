<?php
namespace app\common\traits;

use think\model\Relation;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/13
 * Time: 14:30
 */
trait BaseModel
{
    /**
     * 根据关联条件查询当前模型
     * @access public
     * @param string $relation 关联方法名
     * @param mixed $where 查询条件（数组或者闭包）
     * @param mixed $field 查询条件（数组或者闭包）
     * @param mixed $join 关联方式
     * @return Model
     */
    public static function hasWhere($relation, $where = [], $field = '',$join = NULL)
    {
        $model = new static();
        $info = $model->$relation()->getRelationInfo();
        if(empty($info['joinType'])){
            $info['joinType'] = $join;
        }
        switch ($info['type']) {
            case Relation::HAS_ONE:
            case Relation::HAS_MANY:
                $table = $info['model']::getTable();
                if (is_array($where)) {
                    foreach ($where as $key => $val) {
                        if (false === strpos($key, '.')) {
                            $where['b.' . $key] = $val;
                            unset($where[$key]);
                        }
                    }
                }
                if (empty($field)) {
                    return $model->db()->alias('a')
                        ->field('a.*')
                        ->distinct(true)
                        ->join($table . ' b', 'a.' . $info['localKey'] . '=b.' . $info['foreignKey'], $info['joinType'])
                        ->where($where);
                } else {
                    return $model->db()->alias('a')
                        ->field($field)
                        ->distinct(true)
                        ->join($table . ' b', 'a.' . $info['localKey'] . '=b.' . $info['foreignKey'], $info['joinType'])
                        ->where($where);
                }
            case Relation::HAS_MANY_THROUGH: // TODO
            default:
                return $model;
        }
    }


}