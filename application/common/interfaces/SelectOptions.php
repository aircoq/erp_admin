<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-5-12
 * Time: 上午11:45
 */

namespace app\common\interfaces;


interface SelectOptions
{
    public function getOptions($wheres = []);
}