<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-14
 * Time: 下午2:28
 */

// application/common 公共模块
/**
 * model所有模块的模型定义在这里
 * model/{FUNCTIONAL}/**
 *  模型类要做好表与表之间的关联。 避免使用Db类来操作
 *  模型内部缓存实现 public static function xxxc($param,...) -> xxxx
 *  PS:warehouse_goods->goods
 *  PS:warehouse_goods->goods_sku
 *
 */

// application/{FUNCTIONAL}各功能模块
/**
 * validate/* 验证类，每个验证使用一个验证器类
 *
 * server/* 服务类
 *    内部逻辑层，实现了访问类所需功能，或外部逻辑层的接口
 *    *模块之间的调用，只能通过server来完成
 *    *server类后期会定义一些use trait 来完成对权限管理
 *
 * controller/* 访问类：
 *    *与客户端的门面，不能直接操作模型来实现功能，必需配合server类完成功能
 *
 *
 * cache/* 缓存类
 *    use CachePersist 持久化
 *    use CacheTransient 临时的
 *    模块内部缓存数据层
 *
 * task/* 任务类
 *    模块的任务 extends AbsTasker
 */

//application/task.php 公共任务注册文件
//application/route.php 公共路由配置文件
//application/config.php 公共应用配置文件
//application/database.php 公共数据配置文件
//application/common.php 公共函数定义文件
//application/tags.php 公共行为注入文件


/** server类 注释标准
 * //类头
 * Doc: 类描述
 * User: wuchuguang
 * Date: 17-3-14
 * Time: 下午2:28
 *
 *
 * //方法
 * @doc 功能描述
 * @param $param1 type
 * @param $param2 type
 * @param $param3 type
 * @return type
 */