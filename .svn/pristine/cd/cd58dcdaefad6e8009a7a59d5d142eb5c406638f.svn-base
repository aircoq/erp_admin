<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/3/28
 * Time: 14:55
 */
use think\Config;
use MongoDB\Client;
if (!function_exists('mongo')) {
    /**
     * tp提供的mongodb扩展库，虽然可以使用模型，但是有些方法没有
     * 这个助手函数使用官方提供的扩展库，方法更全面灵活，可以配合tp提供的扩展库使用
     * @param string    $collectionName mongodb集合名
     * @param string    $dbName mongodb数据名
     * @return \MongoDB\Collection
     */
    function mongo($collectionName, $dbName = 'erp-admin')
    {
        $mongoConfig = Config::get('mongodb');
        $userPwd = '';
        if ($mongoConfig['username']) {
            $userPwd = $mongoConfig['username'].':'.($mongoConfig['password']??'').'@';
        }
        $uri = 'mongodb://'.$userPwd.$mongoConfig['hostname'].':'.$mongoConfig['hostport'].'/?authSource='.$dbName;
        return (new Client($uri))->selectDatabase($dbName)->selectCollection($collectionName);
    }
}