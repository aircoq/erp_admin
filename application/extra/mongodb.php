<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/6/22
 * Time: 14:59
 */
return [
    // 数据库类型
    'type'            => '\think\mongo\Connection',
    // 服务器地址
    'hostname'        => '172.18.8.95',
    // 数据库名
    'database'        => 'erp-admin',
    // 是否是复制集
    'is_replica_set'  => false,
    // 用户名
    'username'        => 'erp',
    // 密码
    'password'        => 'rondaful',
    // 端口
    'hostport'        => '27017',
    // 连接dsn
    'dsn'             => '',
    // 数据库连接参数
    'params'          => [],
    // 数据库编码默认采用utf8
    'charset'         => 'utf8',
    // 主键名
    'pk'              => '',
    // 主键类型
    'pk_type'         => '',
    // 数据库表前缀
    'prefix'          => '',
    // 数据库调试模式
    'debug'           => false,
    // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'deploy'          => 0,
    // 数据库读写是否分离 主从式有效
    'rw_separate'     => false,
    // 读写分离后 主服务器数量
    'master_num'      => 1,
    // 指定从服务器序号
    'slave_no'        => '',
    // 是否严格检查字段是否存在
    'fields_strict'   => false,
    // 数据集返回类型
    'resultset_type'  => 'array',
    // 自动写入时间戳字段
    'auto_timestamp'  => false,
    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',
    // 是否需要进行SQL性能分析
    'sql_explain'     => false,
    // 是否_id转换为id
    'pk_convert_id'   => false,
    // typeMap
    'type_map'        => ['root' => 'array', 'document' => 'array'],
    // Query对象
    'query'           => '\\think\\mongo\\Query',
];
