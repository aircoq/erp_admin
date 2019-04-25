<?php
namespace app\common\service;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/3/18
 * Time: 18:41
 */
class Sort
{
    static $s_field;
    static $s_sc;

    /** 二维数组按键值排序
     * @param $a  【需要排序的数组】
     * @param $sort  【排序的键值】
     * @param string $d  【默认ASC，带上参为 DESC】
     * @return mixed
     */
    public function array_sort(&$a, $sort, $d = '')
    {
        self::$s_field = $sort;
        self::$s_sc = $d;
        usort($a, array($this,"array_sort_callback"));
        return $a;
    }

    /** 排序回调方法，请勿删除
     * @param $a
     * @param $b
     * @return int
     */
    public function array_sort_callback($a, $b)
    {
        $s_a  = self::$s_sc ? $b : $a;
        $s_b = self::$s_sc ? $a : $b;
        $field = self::$s_field;
        switch(true){
            case (is_string($s_a[$field]) && is_string($s_b[$field])):
                return strcmp($s_a[$field],$s_b[$field]);
            break;
            default:
                if($s_a[$field] == $s_b[$field]){
                    return 0;
                }else{
                    return $s_a[$field] > $s_b[$field] ? -1 : 1;
                }
        }
    }
}