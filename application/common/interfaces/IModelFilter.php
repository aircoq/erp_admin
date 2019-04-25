<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-30
 * Time: 上午11:14
 */

namespace app\common\interfaces;


use think\Model;

interface IModelFilter
{
    const TYPE_INPUT = 'input';
    const TYPE_SELECT = 'select';
    const TYPE_MULTIPLE_SELECT = 'multiple-select';

    const TYPES = [
        self::TYPE_INPUT,
        self::TYPE_MULTIPLE_SELECT,
        self::TYPE_SELECT
    ];

    const TYPES_SELECT = [
        self::TYPE_SELECT,
        self::TYPE_MULTIPLE_SELECT
    ];

    public static function getName():string ;

    /**
     * @example
     * [
     *  [
     *    'key' => 'preg', //require
     *    'type'=>'input', // require
     *    'name'=> '正规则', // require
     *    'default'=> '',
     *    'valids' => ['requierd']
     *  ],
     *  [
     *    'key' => 'select', //require
     *    'type'=>'select|multiple-select', //require
     *    'options' => [], //require
     *    'name'=>'selectName' //require
     *    'default'=> '',
     *    'valids'=> [],
     *    'childs'=> []
     * ]
     * ]
     * @return array
     */
    public static function config() :array;

}