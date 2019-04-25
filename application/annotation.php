<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-7-31
 * Time: 下午4:46
 */

$autoAnnotations = [];
if(file_exists(APP_PATH."annotation_auto.php")){
    $autoAnnotations = include APP_PATH.'annotation_auto.php';
}



return $autoAnnotations;