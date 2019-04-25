<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-6-1
 * Time: 上午10:29
 * Doc: 可查询列表接口
 */

namespace app\common\interfaces;


interface SearchOptions
{
    public function getOptions($wheres = []);

    public function getLabelByValue($value);
}