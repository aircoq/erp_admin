<?php

//控制器：app\listing\controller\HealthData
//@title 获取wish店铺数据监控
define('API_GET_getMonitorData','get|get-monitor-data');
//@title wish店铺数据监控
define('API_POST_wishShopMonitor','post|wish-shop-monitor');
//@title wish店铺短信授权验证
define('API_POST_wishShopAuth','post|wish-shop-auth');
//@title 获取wish手机验证码
define('API_POST_getWishMobileCode','post|get-wish-mobile-code');
//@title wish健康数据列表
define('API_GET_wishHealthDataList','get|wish-health-data-list');
//@title wish历史健康数据列表
define('API_GET_wishHistoryHealthData','get|wish-history-health-data');

//控制器：app\warehouse\controller\StockOut
//@title 出库列表
define('API_GET_stockOut','get|stock-out');
//@title 新建出库
define('API_POST_stockOut','post|stock-out');
//@title 出库获取
define('API_GET_stockOut___id','get|stock-out/:id');
//@title 出库编辑
define('API_GET_stockOut___id_edit','get|stock-out/:id/edit');
//@title 出库删除
define('API_DELETE_stockOut___id','delete|stock-out/:id');
//@title 类型列表
define('API_GET_stockOut_types','get|stock-out/types');
//@title 状态列表
define('API_GET_stockOut_statuses','get|stock-out/statuses');
//@title 操作出库
define('API_POST_stockOut_doStockOut','post|stock-out/do-stock-out');
//@title 出库审核
define('API_POST_stockOut_audit','post|stock-out/audit');
//@title 入库记录导出
define('API_POST_stockOut_export','post|stock-out/export');
//@title 海外仓包裹出库
define('API_POST_stockOut_thirdOut','post|stock-out/third-out');
//@title 出库导出字段
define('API_GET_stockOut_exportFields','get|stock-out/export-fields');
//@title 获取商品详情
define('API_GET_stockOut_getGoods','get|stock-out/get-goods');
//@title 出库记录导入商品信息
define('API_POST_stockOut_importGoods','post|stock-out/import-goods');

//控制器：app\index\controller\Channel
//@title 平台列表
define('API_GET_channel','get|channel');
//@title 平台select列表(公共)
define('API_GET_global_channels','get|global/channels');
//@title 平台账号
define('API_GET_channel_channelAccounts','get|channel/channelAccounts');
//@title 平台详情
define('API_GET_channel___id','get|channel/:id');
//@title 添加平台
define('API_POST_channel','post|channel');
//@title 更新平台
define('API_PUT_channel___id','put|channel/:id');
//@title 状态修改
define('API_POST_channel_states','post|channel/states');
//@title 业务员列表
define('API_GET_channel_sellerList','get|channel/seller-list');
//@title 获取渠道占比信息
define('API_GET_channel___id_proportion','get|channel/:id/proportion');
//@title 获取当前渠道对应的销售部门
define('API_GET_channel___id_departments','get|channel/:id/departments');
//@title 保存渠道占比信息
define('API_POST_channel___id_proportion','post|channel/:id/proportion');
//@title 平台站点配置列表
define('API_GET_channel_systemList','get|channel/system-list');
//@title 新增平台配置
define('API_GET_channel_addConfig','get|channel/add-config');
//@title 引用系统平台配置
define('API_POST_channel___id_useConfig','post|channel/:id/use-config');
//@title 获取平台系统配置
define('API_GET_channel___id_config','get|channel/:id/config');
//@title 删除平台配置
define('API_DELETE_channel_config','delete|channel/config');
//@title 更新平台参数配置
define('API_PUT_channel_config','put|channel/config');
//@title 获取平台站点配置
define('API_GET_channel_config','get|channel/config');
//@title 参数设置
define('API_PUT_channel___id_config','put|channel/:id/config');

//控制器：app\warehouse\controller\WarehouseGoods
//@title 库存列表
define('API_GET_warehouseGoods','get|warehouse-goods');
//@title 查看缺货明细
define('API_GET_warehouseGoods___id_oosDetails','get|warehouse-goods/:id/oos-details');
//@title 获取所有仓库
define('API_GET_warehouseGoods_getWarehouse','get|warehouse-goods/getWarehouse');
//@title 库存获取
define('API_GET_warehouseGoods___id','get|warehouse-goods/:id');
//@title 批量修改平台预警数
define('API_POST_warehouseGoods_alert','post|warehouse-goods/alert');
//@title 获取sku可库存数
define('API_GET_warehouseGoods_available_quantity','get|warehouse-goods/available_quantity');
//@title 海外仓库存
define('API_GET_warehouseGoods_overseas','get|warehouse-goods/overseas');
//@title 分配
define('API_GET_warehouseGoods___id_allot','get|warehouse-goods/:id/allot');
//@title 确认分配
define('API_POST_warehouseGoods___id_allot','post|warehouse-goods/:id/allot');
//@title 申请备货信息
define('API_GET_warehouseGoods_apply','get|warehouse-goods/apply');
//@title 申请备货
define('API_POST_warehouseGoods_apply','post|warehouse-goods/apply');
//@title 操作日志
define('API_GET_warehouseGoods_logs','get|warehouse-goods/logs');
//@title 本地仓
define('API_GET_warehouseGoods_local','get|warehouse-goods/local');
//@title 第三方仓库
define('API_GET_warehouseGoods_third','get|warehouse-goods/third');
//@title fba库存
define('API_GET_warehouseGoods_fba','get|warehouse-goods/fba');
//@title 库存详情
define('API_GET_warehouseGoods___id_detail','get|warehouse-goods/:id/detail');
//@title 库存期初
define('API_POST_warehouseGoods_init','post|warehouse-goods/init');
//@title 库存期初
define('API_POST_warehouseGoods_purchaseIn','post|warehouse-goods/purchase-in');
//@title 导出仓库商品库存
define('API_POST_warehouseGoods_export','post|warehouse-goods/export');
//@title 第三方导出字段
define('API_GET_warehouseGoods_exportFields','get|warehouse-goods/export-fields');
//@title sku状态筛选列表
define('API_GET_warehouseGoods_skuStatus','get|warehouse-goods/sku-status');
//@title 第三方库存同步
define('API_POST_warehouseGoods_sync','post|warehouse-goods/sync');
//@title 第三方库存同步
define('API_POST_warehouseGoods_thirdSync','post|warehouse-goods/third-sync');
//@title 第三方sku库存导入
define('API_POST_warehouseGoods_thirdImport','post|warehouse-goods/third-import');
//@title 库存调整
define('API_GET_warehouseGoods_waitingShippingQuantity','get|warehouse-goods/waiting-shipping-quantity');
//@title 库存信息调整
define('API_GET_warehouseGoods_restore','get|warehouse-goods/restore');
//@title 无api批量入库
define('API_POST_warehouseGoods_noApiIn','post|warehouse-goods/no-api-in');
//@title 平台分库存详情
define('API_GET_warehouseGoods_channelDetail','get|warehouse-goods/channel-detail');
//@title 第三方平台分库存借调
define('API_POST_warehouseGoods_thirdAllocate','post|warehouse-goods/third-allocate');
//@title 第三方平台分库存批量借调
define('API_POST_warehouseGoods_thirdMultiAllocate','post|warehouse-goods/third-multi-allocate');
//@title 第三方申请平台分库存调入库存
define('API_GET_warehouseGoods_applyAllocateIn','get|warehouse-goods/apply-allocate-in');
//@title 获取第三方仓库sku的平台库存详情
define('API_GET_warehouseGoods_third_channel_detail','get|warehouse-goods/third_channel_detail');
//@title 获取海外仓库sku的平台库存详情
define('API_GET_warehouseGoods_oversea_channel_detail','get|warehouse-goods/oversea_channel_detail');
//@title 获取中转仓待发库存明细
define('API_GET_warehouseGoods_shipping_detail','get|warehouse-goods/shipping_detail');
//@title sku货位记录
define('API_GET_warehouseGoods_cargoLog','get|warehouse-goods/cargo-log');
//@title 打印fba商品条码
define('API_GET_warehouseGoods_barcode','get|warehouse-goods/barcode');
//@title 活动备货详情
define('API_GET_warehouseGoods_stockingDetail','get|warehouse-goods/stocking-detail');
//@title 备货锁定详情
define('API_GET_warehouseGoods_lockDetail','get|warehouse-goods/lock-detail');

//控制器：app\warehouse\controller\StockIn
//@title 新建入库
define('API_GET_stockIn','get|stock-in');
//@title 新建入库
define('API_POST_stockIn','post|stock-in');
//@title 入库获取
define('API_GET_stockIn___id','get|stock-in/:id');
//@title 入库编辑
define('API_GET_stockIn___id_edit','get|stock-in/:id/edit');
//@title 入库删除
define('API_DELETE_stockIn___id','delete|stock-in/:id');
//@title 类型列表
define('API_GET_stockIn_types','get|stock-in/types');
//@title 操作入库
define('API_POST_stockIn_doStockIn','post|stock-in/do-stock-in');
//@title 状态列表
define('API_GET_stockIn_statuses','get|stock-in/statuses');
//@title 入库审核
define('API_POST_stockIn_audit','post|stock-in/audit');
//@title 新增入库获取产品信息
define('API_GET_stockIn_getGoods','get|stock-in/get-goods');
//@title 入库记录导出
define('API_POST_stockIn_export','post|stock-in/export');
//@title 出库导出字段
define('API_GET_stockIn_exportFields','get|stock-in/export-fields');
//@title 入库记录导入商品信息
define('API_POST_stockIn_importGoods','post|stock-in/import-goods');

//控制器：app\warehouse\controller\Stock
//@title 出入库记录列表
define('API_GET_stock','get|stock');
//@title 出入库记录添加
define('API_POST_stock','post|stock');
//@title 出入库记录获取
define('API_GET_stock___id','get|stock/:id');
//@title 出入库记录编辑
define('API_GET_stock___id_edit','get|stock/:id/edit');
//@title 出入库记录更新
define('API_PUT_stock___id','put|stock/:id');
//@title 出入库记录删除
define('API_DELETE_stock___id','delete|stock/:id');
//@title 获取仓库和出入库状态
define('API_GET_stock_getWarehouseStockStatus','get|stock/getWarehouseStockStatus');

//控制器：app\index\controller\Role
//@title 角色管理列表
define('API_GET_role','get|role');
//@title 新增
define('API_POST_role','post|role');
//@title 角色管理获取
define('API_GET_role___id','get|role/:id');
//@title 修改
define('API_PUT_role___id','put|role/:id');
//@title 删除
define('API_DELETE_role___id','delete|role/:id');
//@title 停用，启用账号'
define('API_GET_role_changeStatus','get|role/changeStatus');
//@title 授权
define('API_GET_role_authorization','get|role/authorization');
//@title 添加成员
define('API_GET_role_addUser','get|role/addUser');
//@title 获取角色节点权限
define('API_GET_role___roleid_node___nodeid_access','get|role/:roleid/node/:nodeid/access');
//@title 保存角色节点权限
define('API_POST_role___roleid_node___nodeid_access','post|role/:roleid/node/:nodeid/access');
//@title 获取角色已配路由
define('API_GET_role___roleid_mcas','get|role/:roleid/mcas');
//@title 设置角色已配路由
define('API_POST_role___roleid_mcas','post|role/:roleid/mcas');
//@title 复制角色
define('API_POST_role___role_id_copy','post|role/:role_id/copy');

//控制器：app\warehouse\controller\Delivery
//@title 配货管理列表
define('API_GET_delivery','get|delivery');
//@title 退回至自动规则前
define('API_POST_delivery_backRule','post|delivery/back-rule');
//@title 邮寄方式
define('API_GET_delivery_shippingMethod','get|delivery/shippingMethod');
//@title 获取所有仓库
define('API_GET_delivery_getWarehouseChannel','get|delivery/getWarehouseChannel');
//@title 获取配货账号
define('API_GET_delivery_accounts','get|delivery/accounts');
//@title orderCounts
define('API_GET_delivery_orderCounts','get|delivery/orderCounts');
//@title 获取平台列表
define('API_GET_delivery_channels','get|delivery/channels');
//@title 分配库存
define('API_GET_delivery_distriuteInventory','get|delivery/distriuteInventory');
//@title 改变仓库
define('API_PUT_delivery_changeWarehouse','put|delivery/changeWarehouse');
//@title 包裹类型
define('API_GET_delivery_packageType','get|delivery/package-type');

//控制器：app\warehouse\controller\PlaceOrder
//@title 物流商下单管理列表
define('API_GET_placeorder','get|placeorder');
//@title 海外仓物流商下单
define('API_GET_placeorder_third','get|placeorder/third');
//@title 获取运输方式
define('API_GET_placeorder_shippingMethod','get|placeorder/shipping-method');
//@title 状态列表
define('API_GET_placeorder___type_statuses','get|placeorder/:type/statuses');
//@title 账号列表
define('API_GET_placeorder_accounts','get|placeorder/accounts');
//@title 获取平台列表
define('API_GET_placeorder_channels','get|placeorder/channels');
//@title 批量上传
define('API_GET_placeorder_batchUpload','get|placeorder/batchUpload');
//@title 无api确认上传
define('API_POST_placeorder_confirmUpload','post|placeorder/confirmUpload');
//@title 无API仓库导出
define('API_POST_placeorder_export','post|placeorder/export');
//@title 交运
define('API_POST_placeorder_shipping','post|placeorder/shipping');
//@title 推送管易
define('API_PUT_placeorder_pushGuanyi','put|placeorder/pushGuanyi');
//@title 释放包裹
define('API_POST_placeorder_reback','post|placeorder/reback');
//@title 速卖通线上发货预报云途
define('API_PUT_placeorder_uploadYt','put|placeorder/uploadYt');
//@title lazada物流商上传
define('API_POST_placeorder_lazadaUpload','post|placeorder/lazada-upload');
//@title vova上传线上物流
define('API_POST_placeorder_vova','post|placeorder/vova');
//@title vova获取线上物流跟踪号
define('API_POST_placeorder_vovaTacking','post|placeorder/vova-tacking');

//控制器：app\warehouse\controller\Allocation
//@title 列表
define('API_GET_allocation','get|allocation');
//@title 详情
define('API_GET_allocation___id','get|allocation/:id');
//@title 更新
define('API_PUT_allocation___id','put|allocation/:id');
//@title 状态列表
define('API_GET_allocation_statusList','get|allocation/status-list');
//@title 保存
define('API_POST_allocation','post|allocation');
//@title 审核
define('API_PUT_allocation___id_audit','put|allocation/:id/audit');
//@title 出库
define('API_POST_allocation___id_deliver','post|allocation/:id/deliver');
//@title 入库
define('API_POST_allocation___id_entry','post|allocation/:id/entry');
//@title 获取商品详情
define('API_GET_allocation_getGoods','get|allocation/get-goods');
//@title 导入商品信息
define('API_POST_allocation_importGoods','post|allocation/import-goods');
//@title 获取附件信息
define('API_GET_allocation___id_getAttachment','get|allocation/:id/get-attachment');
//@title 上传附件信息
define('API_POST_allocation___id_uploadAttachment','post|allocation/:id/upload-attachment');
//@title 日志信息
define('API_GET_allocation___id_logs','get|allocation/:id/logs');
//@title 下载附件
define('API_GET_allocation_attachment','get|allocation/attachment');
//@title 删除附件
define('API_POST_allocation___id_deleteAttachment','post|allocation/:id/delete-attachment');
//@title 引用备货计划
define('API_GET_allocation_stockingDetail','get|allocation/stocking-detail');
//@title 装箱清单
define('API_GET_allocation___id_boxList','get|allocation/:id/box-list');
//@title FNSKU校验
define('API_POST_allocation_verifyFnsku','post|allocation/verify-fnsku');
//@title 调拨单作废
define('API_PUT_allocation___id_cancel','put|allocation/:id/cancel');
//@title 调拨单强制作废
define('API_PUT_allocation___id_forceCancel','put|allocation/:id/force-cancel');
//@title 查看调拨装箱详情
define('API_GET_allocation_detail','get|allocation/detail');
//@title 调拨测试
define('API_GET_allocation_test','get|allocation/test');

//控制器：app\index\controller\Task
//@title 任务列表
define('API_GET_task','get|task');
//@title 任务工作器类列表
define('API_GET_task_classes','get|task/classes');
//@title 某任务工作器的执行列表
define('API_GET_task_workers','get|task/workers');
//@title 某任务工作器安装
define('API_GET_task_install','get|task/install');
//@title 某任务工作器卸载
define('API_GET_task_uninstall','get|task/uninstall');
//@title 重新加载类(任务)
define('API_GET_task_reloadclass','get|task/reloadclass');
//@title 任务参数规则
define('API_GET_task___taskId_rules','get|task/:taskId/rules');
//@title 启停任务
define('API_PUT_task___taskId_status','put|task/:taskId/status');
//@title 任务信息
define('API_GET_task_worker___workerId','get|task/worker/:workerId');
//@title 启停工作任务
define('API_PUT_task_worker_status___workerId','put|task/worker/status/:workerId');
//@title 修改任务信息
define('API_PUT_task_worker___workerId','put|task/worker/:workerId');
//@title 添加工作任务
define('API_POST_task_worker','post|task/worker');
//@title 删除工作任务
define('API_DELETE_task_worker','delete|task/worker');
//@title 查看工作任务日志
define('API_GET_task_worker___workerId_logs','get|task/worker/:workerId/logs');
//@title 修改任务时间
define('API_PUT_task_worker___workerId_changetime','put|task/worker/:workerId/changetime');
//@title 同步任务
define('API_POST_task_synchronous','post|task/synchronous');
//@title 时间任务调度信息
define('API_GET_task_worker_schedulers','get|task/worker_schedulers');
//@title 获取全局任务
define('API_GET_task_global_tasks','get|task/global_tasks');
//@title 添加全局任务
define('API_PUT_task_global_task','put|task/global_task');
//@title 改数全局任务进程数
define('API_PUT_task_global_task_change','put|task/global_task_change');

//控制器：app\api\controller\Guanyiwarehouse
//@title 库存异动接口
define('API_POST_api_Guanyiwarehouse_inventoryChanged','post|api/Guanyiwarehouse/inventoryChanged');
//@title 发货回传接口
define('API_POST_api_Guanyiwarehouse_deliveryReturn','post|api/Guanyiwarehouse/deliveryReturn');
//@title 拒单接口
define('API_POST_api_Guanyiwarehouse_rejectPackage','post|api/Guanyiwarehouse/rejectPackage');

//控制器：app\index\controller\DashBoard
//@title 最近15天平台订单总数
define('API_GET_dashboard_nearby15','get|dashboard/nearby15');
//@title 最近2天平台订单总数
define('API_GET_dashboard_nearby2','get|dashboard/nearby2');
//@title 最近15天平台订单总数[钉钉]
define('API_GET_dashboard_dingtalkNearby15','get|dashboard/dingtalk-nearby15');
//@title 最近15天平台FBA订单总数
define('API_GET_dashboard_fbaNearby15','get|dashboard/fba-nearby15');
//@title 查询账号业绩
define('API_GET_dashboard_accountPerformance','get|dashboard/account-performance');
//@title 订单管理
define('API_GET_dashboard_orders','get|dashboard/orders');
//@title 订单管理
define('API_GET_dashboard_listings','get|dashboard/listings');
//@title 仓库信息
define('API_GET_dashboard_warehouses','get|dashboard/warehouses');
//@title 账号销售量统计
define('API_GET_dashboard_accountInfo','get|dashboard/account-info');

//控制器：app\index\controller\ChannelAccount
//@title 搜索账号
define('API_GET_channelAccount_search','get|channel-account/search');

//控制器：app\index\controller\EbayAccount
//@title ebay账号列表
define('API_GET_ebayAccount','get|ebay-account');
//@title 新增ebay账号
define('API_POST_ebayAccount','post|ebay-account');
//@title 查看ebay账号
define('API_GET_ebayAccount___id','get|ebay-account/:id');
//@title 编辑
define('API_GET_ebayAccount___id_edit','get|ebay-account/:id/edit');
//@title 更新
define('API_PUT_ebayAccount___id','put|ebay-account/:id');
//@title ebay批量设置批量设置抓取参数；
define('API_POST_ebayAccount_set','post|ebay-account/set');
//@title 启用/停用 账号
define('API_POST_ebayAccount_status','post|ebay-account/status');
//@title 获取session的ID
define('API_POST_ebayAccount_getEbaySessionId','post|ebay-account/getEbaySessionId');
//@title 获取援权的token
define('API_POST_ebayAccount_getFetchEbayToken','post|ebay-account/getFetchEbayToken');
//@title 检测账号用户
define('API_POST_ebayAccount_getConfirmIdentity','post|ebay-account/getConfirmIdentity');
//@title 验证ebay的token是否有效
define('API_POST_ebayAccount_geteBayOfficialTime','post|ebay-account/geteBayOfficialTime');
//@title 查看 - ebay账号绑定paypal
define('API_GET_ebayAccount_mapPaypal_view','get|ebay-account/mapPaypal/view');
//@title ebay绑定paypal邮箱
define('API_POST_ebayAccount_ebayMapPaypal','post|ebay-account/ebayMapPaypal');
//@title ebay帐号绑定paypal下载；
define('API_GET_ebayAccount_down','get|ebay-account/down');
//@title ebay帐号获取通知配置；
define('API_GET_ebayAccount_getevent','get|ebay-account/getevent');
//@title ebay帐号设置通知配置；
define('API_POST_ebayAccount_setEvent','post|ebay-account/setEvent');
//@title oauth 认证时，获取登录链接
define('API_GET_ebayAccount___account_id_oauthLogin','get|ebay-account/:account_id/oauth-login');
//@title oauth 认证时，获取token并保存
define('API_POST_ebayAccount___account_id_oauthToken','post|ebay-account/:account_id/oauth-token');

//控制器：app\index\controller\PaypalAccount
//@title 列表
define('API_GET_paypalAccount','get|paypal-account');
//@title 新增
define('API_POST_paypalAccount','post|paypal-account');
//@title 查看
define('API_GET_paypalAccount___id','get|paypal-account/:id');
//@title 编辑
define('API_GET_paypalAccount___id_edit','get|paypal-account/:id/edit');
//@title 更新
define('API_PUT_paypalAccount___id','put|paypal-account/:id');
//@title paypal授权
define('API_PUT_paypalAccount___id_authorization','put|paypal-account/:id/authorization');
//@title paypal显示邮箱密码
define('API_GET_paypalAccount_show','get|paypal-account/show');
//@title 启用/停用 账号
define('API_POST_paypalAccount_status','post|paypal-account/status');
//@title 获取paypal账号
define('API_GET_paypalAccount_account','get|paypal-account/account');
//@title 批量开启
define('API_POST_paypalAccount_batchSet','post|paypal-account/batch-set');
//@title 设置paypal通知
define('API_POST_paypalAccount_events','post|paypal-account/events');
//@title 获取paypal通知
define('API_GET_paypalAccount___id_events','get|paypal-account/:id/events');

//控制器：app\index\controller\Download
//@title 下载模板文件
define('API_GET_downfile','get|downfile');

//控制器：app\system\controller\Menu
//@title 菜单列表
define('API_GET_system_menu','get|system/menu');
//@title 前端菜单数据
define('API_GET_menu_pages','get|menu/pages');
//@title 编辑菜单
define('API_PUT_system_menu___id','put|system/menu/:id');
//@title 改变状态
define('API_PUT_system_menu_changeStatus','put|system/menu/change-status');
//@title 添加菜单
define('API_POST_system_menu_add','post|system/menu/add');
//@title 改变
define('API_PUT_system_menu_change','put|system/menu/change');
//@title 菜单删除
define('API_DELETE_system_menu','delete|system/menu');

//控制器：app\system\controller\Time
//@title 获取当前系统时间
define('API_GET_system_time','get|system/time');

//控制器：app\system\controller\Release
//@title 版本管理列表
define('API_GET_release','get|release');
//@title 版本管理添加
define('API_POST_release','post|release');
//@title 版本管理删除
define('API_DELETE_release___id','delete|release/:id');
//@title 标识已读
define('API_POST_release___id_read','post|release/:id/read');
//@title 获取标识已读
define('API_GET_release_reads','get|release/reads');

//控制器：app\purchase\controller\PurchaseArrival
//@title 列表
define('API_GET_Purchase_PurchaseArrival_index','get|Purchase/PurchaseArrival/index');
//@title 到货->确定按钮
define('API_POST_Purchase_PurchaseArrival_arrival','post|Purchase/PurchaseArrival/arrival');
//@title 到货->列表
define('API_GET_Purchase_PurchaseArrival_orderDetail','get|Purchase/PurchaseArrival/orderDetail');
//@title 测试
define('API_GET_Purchase_PurchaseArrival_test','get|Purchase/PurchaseArrival/test');
//@title 免捡产品
define('API_GET_Purchase_PurchaseArrival_freePick','get|Purchase/PurchaseArrival/freePick');
//@title 打印标签
define('API_GET_Purchase_PurchaseArrival_printLabel','get|Purchase/PurchaseArrival/printLabel');
//@title 分派
define('API_GET_Purchase_PurchaseArrival_assignment','get|Purchase/PurchaseArrival/assignment');
//@title 获取员工信息
define('API_GET_Purchase_getEmployee','get|Purchase/getEmployee');
//@title 获取仓库信息
define('API_GET_Purchase_getWarehouse','get|Purchase/getWarehouse');

//控制器：app\purchase\controller\PurchasePlan
//@title  显示采购计划列表
define('API_GET_purchasePlan','get|purchase-plan');
//@title 新增采购计划
define('API_POST_purchasePlan','post|purchase-plan');
//@title 采购计划获取
define('API_GET_purchasePlan___id','get|purchase-plan/:id');
//@title 批量编辑
define('API_POST_purchasePlan_batchEdit','post|purchase-plan/batchEdit');
//@title 采购计划编辑
define('API_GET_purchasePlan___id_edit','get|purchase-plan/:id/edit');
//@title 采购计划更新
define('API_PUT_purchasePlan___id','put|purchase-plan/:id');
//@title  批量审核
define('API_POST_purchasePlan_changeStatus','post|purchase-plan/changeStatus');
//@title 获取采购计划展开的详情
define('API_GET_purchasePlan_getDetail','get|purchase-plan/getDetail');
//@title  获取操作日志
define('API_GET_purchasePlan_getLogDetail','get|purchase-plan/getLogDetail');
//@title  取sku的基本信息
define('API_GET_purchasePlan_getSkuInfo','get|purchase-plan/getSkuInfo');
//@title  添加或者取备注
define('API_POST_purchasePlan_remarks','post|purchase-plan/remarks');
//@title  采购计划审核前查看采购计划价格变化情况
define('API_GET_purchasePlan_getPurchasePlanPriceChange','get|purchase-plan/getPurchasePlanPriceChange');
//@title  采购计划价格审核
define('API_POST_purchasePlan_purchasePlanPriceAudit','post|purchase-plan/purchasePlanPriceAudit');
//@title 导入采购计划
define('API_POST_purchasePlan_import','post|purchase-plan/import');
//@title 导入商品列表
define('API_POST_purchasePlan_sku_import','post|purchase-plan/sku/import');
//@title 导出采购计划
define('API_POST_purchasePlan_export','post|purchase-plan/export');
//@title 获取导出所有字段
define('API_GET_purchasePlan_exportFields','get|purchase-plan/export-fields');
//@title 取消采购
define('API_POST_purchasePlan_cancel','post|purchase-plan/cancel');
//@title 批量删除
define('API_POST_purchasePlan_batch_delete','post|purchase-plan/batch/delete');
//@title 批量修改采购员
define('API_PUT_purchasePlan_purchaser','put|purchase-plan/purchaser');
//@title 批量修改结算方式
define('API_PUT_purchasePlan_balanceType','put|purchase-plan/balance-type');

//控制器：app\purchase\controller\PurchaseOrder
//@title 显示列表
define('API_GET_purchaseOrder','get|purchase-order');
//@title  查看
define('API_GET_purchaseOrder___id','get|purchase-order/:id');
//@title  显示编辑
define('API_GET_purchaseOrder___id_edit','get|purchase-order/:id/edit');
//@title  更新
define('API_PUT_purchaseOrder___id','put|purchase-order/:id');
//@title 改变状态
define('API_POST_purchaseOrder_changeStatus','post|purchase-order/changeStatus');
//@title 批量改变状态
define('API_POST_purchaseOrder_batch_status','post|purchase-order/batch/status');
//@title  获取批量申请付款
define('API_GET_purchaseOrder_applyPayment','get|purchase-order/applyPayment');
//@title  提交批量申请付款
define('API_POST_purchaseOrder_batchApplyPayment','post|purchase-order/batchApplyPayment');
//@title   删除
define('API_DELETE_purchaseOrder___id','delete|purchase-order/:id');
//@title  获取采购订单展开的详情
define('API_GET_purchaseOrder_getDetail','get|purchase-order/getDetail');
//@title  获取操作日志
define('API_GET_purchaseOrder_getLogDetail','get|purchase-order/getLogDetail');
//@title  添加或者取备注
define('API_POST_purchaseOrder_remarks','post|purchase-order/remarks');
//@title  采购单不等待剩余申请
define('API_POST_purchaseOrder_purchaseNotWaitingAudit','post|purchase-order/purchaseNotWaitingAudit');
//@title  采购单收货记录
define('API_GET_purchaseOrder_getArrivalRecords','get|purchase-order/getArrivalRecords');
//@title  采购单不等待剩余记录
define('API_GET_purchaseOrder_records_defective','get|purchase-order/records/defective');
//@title 获取采购订单物流跟踪信息
define('API_GET_purchaseOrder_getTraceInformation','get|purchase-order/getTraceInformation');
//@title 通过采购单id批量获取物流信息
define('API_GET_purchaseOrder_getTraceInformationBatch','get|purchase-order/getTraceInformationBatch');
//@title 获取采购物流信息
define('API_GET_purchaseOrder_getLogistics','get|purchase-order/get-logistics');
//@title 更新采购单物流信息
define('API_PUT_purchaseOrder_logistics___id','put|purchase-order/logistics/:id');
//@title 通过采购单的ID列表找到SKU的缺货数，如果以后缺货数小于设置的数值，则显示紧急
define('API_GET_purchaseOrder_getPurchaseSkuBeOutOfStock','get|purchase-order/getPurchaseSkuBeOutOfStock');
//@title 导出采购订单
define('API_POST_purchaseOrder_export','post|purchase-order/export');
//@title 采购导出字段
define('API_GET_purchaseOrder_exportFields','get|purchase-order/export-fields');
//@title 获取采购单
define('API_GET_purchaseOrder_getOrders','get|purchase-order/get-orders');
//@title 不等待剩余批量审核
define('API_PUT_purchaseOrder_status','put|purchase-order/status');
//@title 导入
define('API_POST_purchaseOrder_import','post|purchase-order/import');
//@title 添加外部流水号到缓存
define('API_PUT_purchaseOrder_numbers','put|purchase-order/numbers');
//@title 根据外部跟踪号查询物流路径信息
define('API_GET_purchaseOrder___external_number_logisticsTraceInfos','get|purchase-order/:external_number/logisticsTraceInfos');
//@title 设置采购单条目备注
define('API_POST_purchaseOrder___id_remarks','post|purchase-order/:id/remarks');
//@title 采购作废审核
define('API_PUT_purchaseOrder___id_invalid','put|purchase-order/:id/invalid');
//@title 采购单作废并退回采购计划
define('API_POST_purchaseOrder_batchInvalid','post|purchase-order/batchInvalid');
//@title 采购作废申请
define('API_PUT_purchaseOrder___id_invalidApply','put|purchase-order/:id/invalidApply');
//@title 确认到货
define('API_POST_purchaseOrder_sureArrival','post|purchase-order/sure-arrival');
//@title 获取列表汇总信息
define('API_GET_purchaseOrder_calculatingMoney','get|purchase-order/calculating-money');
//@title 下载SKU标签
define('API_GET_purchaseOrder_downSkuLabel','get|purchase-order/down-sku-label');
//@title 批量修改结算方式
define('API_PUT_purchaseOrder_balanceType','put|purchase-order/balance-type');
//@title 手动设置长时间未拆
define('API_POST_purchaseOrder_longTime','post|purchase-order/long-time');
//@title PO缺失列表未处理包裹选择其他异常类型
define('API_PUT_purchaseOrder___abnormal_id_otherAbnormalType','put|purchase-order/:abnormal_id/other-abnormal-type');
//@title 长时间未拆列表
define('API_GET_purchaseOrder_longTime','get|purchase-order/long-time');
//@title 查看未拆异常信息
define('API_GET_purchaseOrder_abnormal___abnormal_id','get|purchase-order/abnormal/:abnormal_id');
//@title 查看丢失数量
define('API_GET_purchaseOrder___abnormal_id_lost','get|purchase-order/:abnormal_id/lost');
//@title 保存或提交丢失数量
define('API_PUT_purchaseOrder___abnormal_id_lost','put|purchase-order/:abnormal_id/lost');
//@title 组长审核丢失数量
define('API_PUT_purchaseOrder___abnormal_id_reviewLeader','put|purchase-order/:abnormal_id/review-leader');
//@title 经理审核丢失数量
define('API_PUT_purchaseOrder___abnormal_id_reviewManager','put|purchase-order/:abnormal_id/review-manager');
//@title 结束丢失数量
define('API_PUT_purchaseOrder___abnormal_id_endDifferenceParcel','put|purchase-order/:abnormal_id/end-difference-parcel');
//@title 包裹退回列表-采购
define('API_GET_purchaseOrder_returnOfGoodsPurchase','get|purchase-order/return-of-goods-purchase');
//@title 采购填入邮寄信息
define('API_PUT_purchaseOrder___abnormal_id_purchase_mail','put|purchase-order/:abnormal_id/purchase/mail');
//@title 采购上传凭证
define('API_PUT_purchaseOrder___abnormal_id_certificateByPurchase','put|purchase-order/:abnormal_id/certificate-by-purchase');

//控制器：app\purchase\controller\PurchaseOrderLogistics
//@title 采购物流信息列表
define('API_GET_purchaseOrderLogistics','get|purchase-order-logistics');
//@title 新建采购单物流信息
define('API_POST_purchaseOrderLogistics','post|purchase-order-logistics');
//@title 更新采购单物流信息
define('API_PUT_purchaseOrderLogistics___id','put|purchase-order-logistics/:id');

//控制器：app\irobotbox\controller\Product
//@title 获取商品信息
define('API_GET_Irobotbox_Product_GetProducts','get|Irobotbox/Product/GetProducts');
//@title 获取商品详细信息
define('API_GET_Irobotbox_Product_GetProductClass','get|Irobotbox/Product/GetProductClass');
//@title 获取商品图片
define('API_GET_Irobotbox_Product_GetProductImages','get|Irobotbox/Product/GetProductImages');
//@title 获取商品库存
define('API_GET_Irobotbox_Product_GetProductInventory','get|Irobotbox/Product/GetProductInventory');
//@title 获取商品采购信息
define('API_GET_Irobotbox_Product_GetProductSupplierPrice','get|Irobotbox/Product/GetProductSupplierPrice');
//@title 获取仓库信息
define('API_GET_Irobotbox_Product_GetWareHouseList','get|Irobotbox/Product/GetWareHouseList');
//@title 下载图片
define('API_GET_Irobotbox_Product_downLoadImg','get|Irobotbox/Product/downLoadImg');
//@title 获取商品条码
define('API_GET_Irobotbox_Product_getProductBar','get|Irobotbox/Product/getProductBar');

//控制器：app\publish\controller\Ebay
//@title 获取ebay商品类目
define('API_POST_Publish_Ebay_getCategorys','post|Publish/Ebay/getCategorys');
//@title 获取ebay商品类目属性
define('API_POST_Publish_Ebay_getSpecifics','post|Publish/Ebay/getSpecifics');
//@title 获取ebay站点
define('API_GET_Publish_Ebay_getSite','get|Publish/Ebay/getSite');
//@title 获取ebay站点对应的增值税选项
define('API_GET_Publish_Ebay_getVatInfo','get|Publish/Ebay/getVatInfo');
//@title 获取ebay账号自定义类目
define('API_POST_Publish_Ebay_getCustomCategory','post|Publish/Ebay/getCustomCategory');
//@title 获取ebay物流方式
define('API_POST_Publish_Ebay_getTrans','post|Publish/Ebay/getTrans');
//@title 获取ebay国家代码
define('API_GET_Publish_Ebay_getCountrys','get|Publish/Ebay/getCountrys');
//@title 获取locations国家代码
define('API_GET_Publish_Ebay_getEbayLocations','get|Publish/Ebay/getEbayLocations');
//@title 获取ebay平台销售账号
define('API_GET_Publish_Ebay_getAccounts','get|Publish/Ebay/getAccounts');
//@title 获取历史选择分类
define('API_POST_Publish_Ebay_getHistoryCategory','post|Publish/Ebay/getHistoryCategory');
//@title 获取ebay类目树
define('API_GET_Publish_Ebay_getCategoryTree','get|Publish/Ebay/getCategoryTree');
//@title 根据关键词查询ebay商户类目
define('API_GET_Publish_Ebay_getCategoryByKeyword','get|Publish/Ebay/getCategoryByKeyword');
//@title 获取ebay店铺自定义类目树
define('API_GET_Publish_Ebay_getCustomCategoryTree','get|Publish/Ebay/getCustomCategoryTree');
//@title 获取ebay备货时间
define('API_GET_Publish_Ebay_getDispatchTimeMax','get|Publish/Ebay/getDispatchTimeMax');
//@title 同步店铺自定义类目
define('API_GET_Publish_ebay_syncStore','get|Publish/ebay/sync-store');
//@title 获取ebay Paypal账号
define('API_GET_Publish_Ebay_getPaypals','get|Publish/Ebay/getPaypals');
//@title 获取ebay 退货时间
define('API_GET_Publish_Ebay_getWithin','get|Publish/Ebay/getWithin');
//@title 同步listing信息
define('API_GET_Publish_Ebay_syncItemInfo','get|Publish/Ebay/syncItemInfo');
//@title 通过item_id来获取在线listing信息
define('API_GET_Publish_ebay_listingInfoByitemid','get|Publish/ebay/listing-info-byitemid');
//@title 通过item_id来获取在线oe
define('API_GET_Publish_ebay_oeSync','get|Publish/ebay/oe-sync');
//@title oe管理新增
define('API_POST_Publish_ebay_oeSave','post|Publish/ebay/oe-save');
//@title oe管理更新
define('API_POST_Publish_ebay_oeUpdate','post|Publish/ebay/oe-update');
//@title oe管理删除
define('API_GET_Publish_ebay_oeRemove','get|Publish/ebay/oe-remove');
//@title oe管理列表
define('API_GET_Publish_ebay_oeList','get|Publish/ebay/oe-list');
//@title oe管理编辑
define('API_GET_Publish_ebay_oeEdit','get|Publish/ebay/oe-edit');
//@title oe模板合并
define('API_POST_Publish_ebay_oeModelmerge','post|Publish/ebay/oe-modelmerge');
//@title oe获取车型信息
define('API_GET_Publish_ebay_oeVechile','get|Publish/ebay/oe-vechile');
//@title oe获取车型品牌
define('API_GET_Publish_ebay_oeMakes','get|Publish/ebay/oe-makes');
//@title 获取ebay在线listing
define('API_GET_Publish_Ebay_getListing','get|Publish/Ebay/getListing');

//控制器：app\publish\controller\EbayCommon
//@title 保存公共模块
define('API_POST_Publish_EbayCommon_saveCommonModel','post|Publish/EbayCommon/saveCommonModel');
//@title 获取公共模块列表
define('API_POST_Publish_EbayCommon_getCommonModeList','post|Publish/EbayCommon/getCommonModeList');
//@title 获取促销设置列表
define('API_GET_Publish_EbayCommon_getPromotionList','get|Publish/EbayCommon/getPromotionList');
//@title 保存促销设置
define('API_POST_Publish_EbayCommon_savePromotion','post|Publish/EbayCommon/savePromotion');
//@title 获取促销设置详情
define('API_GET_Publish_EbayCommon_editPromotion','get|Publish/EbayCommon/editPromotion');
//@title 删除促销设置
define('API_GET_Publish_EbayCommon_removePromotion','get|Publish/EbayCommon/removePromotion');
//@title 获取销售说明列表
define('API_GET_Publish_EbayCommon_getSaleList','get|Publish/EbayCommon/getSaleList');
//@title 保存销售说明
define('API_POST_Publish_EbayCommon_saveSale','post|Publish/EbayCommon/saveSale');
//@title 获取销售说明详情
define('API_GET_Publish_EbayCommon_editSale','get|Publish/EbayCommon/editSale');
//@title 删除销售说明
define('API_GET_Publish_EbayCommon_removeSale','get|Publish/EbayCommon/removeSale');
//@title 获取风格列表
define('API_GET_Publish_EbayCommon_getStyleList','get|Publish/EbayCommon/getStyleList');
//@title 保存风格
define('API_POST_Publish_EbayCommon_saveStyle','post|Publish/EbayCommon/saveStyle');
//@title 获取风格详情
define('API_GET_Publish_EbayCommon_editStyle','get|Publish/EbayCommon/editStyle');
//@title 删除风格
define('API_GET_Publish_EbayCommon_removeStyle','get|Publish/EbayCommon/removeStyle');
//@title 获取议价设置列表
define('API_GET_Publish_EbayCommon_getBargainingList','get|Publish/EbayCommon/getBargainingList');
//@title 保存议价设置
define('API_POST_Publish_EbayCommon_saveBargaining','post|Publish/EbayCommon/saveBargaining');
//@title 获取议价设置详情
define('API_GET_Publish_EbayCommon_editBargaining','get|Publish/EbayCommon/editBargaining');
//@title 删除议价设置
define('API_GET_Publish_EbayCommon_removeBargaining','get|Publish/EbayCommon/removeBargaining');
//@title 获取备货列表
define('API_GET_Publish_EbayCommon_getChoiceList','get|Publish/EbayCommon/getChoiceList');
//@title 保存备货设置
define('API_POST_Publish_EbayCommon_saveChoice','post|Publish/EbayCommon/saveChoice');
//@title 获取备货设置详情
define('API_GET_Publish_EbayCommon_editChoice','get|Publish/EbayCommon/editChoice');
//@title 删除备货设置
define('API_GET_Publish_EbayCommon_removeChoice','get|Publish/EbayCommon/removeChoice');
//@title 获取计数器列表
define('API_GET_Publish_EbayCommon_getCounterList','get|Publish/EbayCommon/getCounterList');
//@title 保存计数器设置
define('API_POST_Publish_EbayCommon_saveCounter','post|Publish/EbayCommon/saveCounter');
//@title 获取计数器设置详情
define('API_GET_Publish_EbayCommon_editCounter','get|Publish/EbayCommon/editCounter');
//@title 删除计数器设置
define('API_GET_Publish_EbayCommon_removeCounter','get|Publish/EbayCommon/removeCounter');
//@title 获取不送达地区列表
define('API_GET_Publish_EbayCommon_getExcludeList','get|Publish/EbayCommon/getExcludeList');
//@title 保存不送达地区设置
define('API_POST_Publish_EbayCommon_saveExclude','post|Publish/EbayCommon/saveExclude');
//@title 获取不送达地区设置详情
define('API_GET_Publish_EbayCommon_editExclude','get|Publish/EbayCommon/editExclude');
//@title 删除不送达地区设置
define('API_GET_Publish_EbayCommon_removeExclude','get|Publish/EbayCommon/removeExclude');
//@title 获取是否自提列表
define('API_GET_Publish_EbayCommon_getPickupList','get|Publish/EbayCommon/getPickupList');
//@title 保存是否自提设置
define('API_POST_Publish_EbayCommon_savePickup','post|Publish/EbayCommon/savePickup');
//@title 获取是否自提设置详情
define('API_GET_Publish_EbayCommon_editPickup','get|Publish/EbayCommon/editPickup');
//@title 删除是否自提设置
define('API_GET_Publish_EbayCommon_removePickup','get|Publish/EbayCommon/removePickup');
//@title 获取发货地设置列表
define('API_GET_Publish_EbayCommon_getLocationList','get|Publish/EbayCommon/getLocationList');
//@title 保存发货地设置
define('API_POST_Publish_EbayCommon_saveLocation','post|Publish/EbayCommon/saveLocation');
//@title 获取发货地设置详情
define('API_GET_Publish_EbayCommon_editLocation','get|Publish/EbayCommon/editLocation');
//@title 删除发货地设置
define('API_GET_Publish_EbayCommon_removeLocation','get|Publish/EbayCommon/removeLocation');
//@title 获取是否私有设置列表
define('API_GET_Publish_EbayCommon_getIndividualList','get|Publish/EbayCommon/getIndividualList');
//@title 保存是否私有设置
define('API_POST_Publish_EbayCommon_saveIndividual','post|Publish/EbayCommon/saveIndividual');
//@title 获取是否私有设置详情
define('API_GET_Publish_EbayCommon_editIndividual','get|Publish/EbayCommon/editIndividual');
//@title 删除是否私有设置
define('API_GET_Publish_EbayCommon_removeIndividual','get|Publish/EbayCommon/removeIndividual');
//@title 获取是否立即付款设置列表
define('API_GET_Publish_EbayCommon_getReceivableslList','get|Publish/EbayCommon/getReceivableslList');
//@title 保存是否立即付款设置
define('API_POST_Publish_EbayCommon_saveReceivablesl','post|Publish/EbayCommon/saveReceivablesl');
//@title 获取是否立即付款设置
define('API_GET_Publish_EbayCommon_editReceivablesl','get|Publish/EbayCommon/editReceivablesl');
//@title 删除是否立即付款设置
define('API_GET_Publish_EbayCommon_removeReceivablesl','get|Publish/EbayCommon/removeReceivablesl');
//@title 获取数量设置列表
define('API_GET_Publish_EbayCommon_getQuantityList','get|Publish/EbayCommon/getQuantityList');
//@title 保存数量设置
define('API_POST_Publish_EbayCommon_saveQuantity','post|Publish/EbayCommon/saveQuantity');
//@title 获取数量设置
define('API_GET_Publish_EbayCommon_editQuantity','get|Publish/EbayCommon/editQuantity');
//@title 删除数量设置
define('API_GET_Publish_EbayCommon_removeQuantity','get|Publish/EbayCommon/removeQuantity');
//@title 获取自定义类型列表
define('API_GET_Publish_EbayCommon_getCateList','get|Publish/EbayCommon/getCateList');
//@title 保存自定义类型
define('API_POST_Publish_EbayCommon_saveCate','post|Publish/EbayCommon/saveCate');
//@title 获取自定义类型
define('API_GET_Publish_EbayCommon_editCate','get|Publish/EbayCommon/editCate');
//@title 删除自定义类型
define('API_GET_Publish_EbayCommon_removeCate','get|Publish/EbayCommon/removeCate');
//@title 获取买家限制设置列表
define('API_GET_Publish_EbayCommon_getRefuseList','get|Publish/EbayCommon/getRefuseList');
//@title 保存买家限制设置
define('API_POST_Publish_EbayCommon_saveRefuse','post|Publish/EbayCommon/saveRefuse');
//@title 获取买家限制设置
define('API_GET_Publish_EbayCommon_editRefuse','get|Publish/EbayCommon/editRefuse');
//@title 删除买家限制设置
define('API_GET_Publish_EbayCommon_removeRefuse','get|Publish/EbayCommon/removeRefuse');
//@title 获取退货政策设置列表
define('API_GET_Publish_EbayCommon_getReturngoodsList','get|Publish/EbayCommon/getReturngoodsList');
//@title 保存退货政策设置
define('API_POST_Publish_EbayCommon_saveReturngoods','post|Publish/EbayCommon/saveReturngoods');
//@title 获取退货政策设置
define('API_GET_Publish_EbayCommon_editReturngoods','get|Publish/EbayCommon/editReturngoods');
//@title 删除退货政策设置
define('API_GET_Publish_EbayCommon_removeReturngoods','get|Publish/EbayCommon/removeReturngoods');
//@title 获取模块组合列表
define('API_GET_Publish_EbayCommon_getCombList','get|Publish/EbayCommon/getCombList');
//@title 保存模块组合
define('API_POST_Publish_EbayCommon_saveComb','post|Publish/EbayCommon/saveComb');
//@title 获取模块组合
define('API_GET_Publish_EbayCommon_editComb','get|Publish/EbayCommon/editComb');
//@title 删除模块组合
define('API_GET_Publish_EbayCommon_removeComb','get|Publish/EbayCommon/removeComb');
//@title 删除公共模块
define('API_POST_Publish_EbayCommon_removeCommonMode','post|Publish/EbayCommon/removeCommonMode');
//@title 获取待编辑模块信息
define('API_POST_Publish_EbayCommon_editCommonMode','post|Publish/EbayCommon/editCommonMode');
//@title 添加物流方式
define('API_POST_Publish_EbayCommon_saveCommonTrans','post|Publish/EbayCommon/saveCommonTrans');
//@title 编辑物流方式
define('API_POST_Publish_EbayCommon_editCommonTrans','post|Publish/EbayCommon/editCommonTrans');
//@title 删除物流方式
define('API_POST_Publish_EbayCommon_removeTrans','post|Publish/EbayCommon/removeTrans');
//@title 上传风格图片到EPS获取https地址
define('API_POST_Publish_ebayCommon_uploadStyleImgs','post|Publish/ebay-common/upload-style-imgs');

//控制器：app\publish\controller\EbayListing
//@title Listing新增页面
define('API_POST_Publish_EbayListing_addListing','post|Publish/EbayListing/addListing');
//@title 保存Listing
define('API_POST_Publish_EbayListing_saveListing','post|Publish/EbayListing/saveListing');
//@title 在线listing更新
define('API_POST_Publish_EbayListing_updateListing','post|Publish/EbayListing/updateListing');
//@title 编辑Listing
define('API_GET_Publish_EbayListing_editListing','get|Publish/EbayListing/editListing');
//@title 获取子产品列表
define('API_GET_Publish_ebayListing_variations','get|Publish/ebay-listing/variations');
//@title listing管理列表
define('API_POST_Publish_EbayListing_listingManagement','post|Publish/EbayListing/listingManagement');
//@title 批量修改状态
define('API_POST_Publish_EbayListing_updateListingStatus','post|Publish/EbayListing/updateListingStatus');
//@title 批量重上
define('API_GET_Publish_EbayListing_bulkHeavyListing','get|Publish/EbayListing/bulkHeavyListing');
//@title 获取范本列表
define('API_GET_Publish_EbayListing_getDraftList','get|Publish/EbayListing/getDraftList');
//@title 复制范本创建listing
define('API_GET_Publish_EbayListing_cListingByDraft','get|Publish/EbayListing/cListingByDraft');
//@title 批量复制范本
define('API_GET_Publish_EbayListing_cDraftByDraft','get|Publish/EbayListing/cDraftByDraft');
//@title 修改范本分类
define('API_POST_Publish_EbayListing_upDraftCate','post|Publish/EbayListing/upDraftCate');
//@title 保存定时规则
define('API_POST_Publish_EbayListing_saveTimingRule','post|Publish/EbayListing/saveTimingRule');
//@title 获取定时规则
define('API_GET_Publish_EbayListing_getTimingRuleList','get|Publish/EbayListing/getTimingRuleList');
//@title 删除定时规则
define('API_GET_Publish_EbayListing_removeTimingRuleList','get|Publish/EbayListing/removeTimingRuleList');
//@title 获取范本主图片
define('API_GET_Publish_EbayListing_getDraftImgs','get|Publish/EbayListing/getDraftImgs');
//@title 修改范本主图
define('API_POST_Publish_EbayListing_upDraftImgs','post|Publish/EbayListing/upDraftImgs');
//@title 修改在线listing价格和数量
define('API_POST_Publish_EbayListing_upPriceQty','post|Publish/EbayListing/upPriceQty');
//@title 修改在线listing销售天数
define('API_POST_Publish_ebayListing_upListingDuration','post|Publish/ebay-listing/up-listing-duration');
//@title 修改在线listing拍卖价格
define('API_POST_Publish_EbayListing_upChinesePrice','post|Publish/EbayListing/upChinesePrice');
//@title 修改在线listing刊登标题
define('API_POST_Publish_EbayListing_upTitle','post|Publish/EbayListing/upTitle');
//@title 修改在线listing店铺分类
define('API_POST_Publish_EbayListing_upStore','post|Publish/EbayListing/upStore');
//@title 修改在线listing公共模块
define('API_POST_Publish_EbayListing_upConmonMod','post|Publish/EbayListing/upConmonMod');
//@title 修改在线listing橱窗图片
define('API_POST_Publish_EbayListing_upImages','post|Publish/EbayListing/upImages');
//@title 批量下架
define('API_POST_Publish_EbayListing_endItems','post|Publish/EbayListing/endItems');
//@title 批量修改账号
define('API_PUT_Publish_EbayListing_upAccounts','put|Publish/EbayListing/up-accounts');
//@title 获取待修改多属性范本
define('API_GET_Publish_ebayListing_drfspecifics','get|Publish/ebay-listing/drfspecifics');
//@title 批量修改范本多属性
define('API_PUT_Publish_EbayListing_upSpecifics','put|Publish/EbayListing/up-specifics');
//@title 批量修改范本标题
define('API_PUT_Publish_EbayListing_upDraftitle','put|Publish/EbayListing/up-draftitle');
//@title 批量修改范本名称前，返回修改前的信息用于前端展示
define('API_GET_Publish_EbayListing_preUpDraftname','get|Publish/EbayListing/preUpDraftname');
//@title 批量修改范本名称
define('API_PUT_Publish_EbayListing_upDraftname','put|Publish/EbayListing/upDraftname');
//@title 批量修改范本出售方式
define('API_PUT_Publish_ebayListing_drafListingtype','put|Publish/ebay-listing/draf-listingtype');
//@title 获取修改记录信息
define('API_GET_Publish_EbayListing_getActionLogs','get|Publish/EbayListing/getActionLogs');
//@title 关联本地产品信息
define('API_POST_Publish_EbayListing_relatedProduc','post|Publish/EbayListing/relatedProduc');
//@title 获取刊登费用
define('API_POST_Publish_EbayListing_getListingFee','post|Publish/EbayListing/getListingFee');
//@title 立即刊登->提交数据
define('API_POST_Publish_ebayListing_publishImmediatelySave','post|Publish/ebay-listing/publish-immediately-save');
//@title 立即刊登->查看结果
define('API_GET_Publish_ebayListing_publishImmediatelyResults','get|Publish/ebay-listing/publish-immediately-results');
//@title 立即刊登
define('API_GET_Publish_ebayListing_publishImmediately','get|Publish/ebay-listing/publish-immediately');
//@title 立即重上
define('API_GET_Publish_ebayListing_relistItm','get|Publish/ebay-listing/relist-itm');
//@title 促销设置
define('API_GET_Publish_ebayListing_promotionListings','get|Publish/ebay-listing/promotion-listings');
//@title 批量导出范本
define('API_GET_Publish_EbayListing_exportDraftInfo','get|Publish/EbayListing/exportDraftInfo');
//@title 批量导入范本
define('API_POST_Publish_EbayListing_importDraftInfo','post|Publish/EbayListing/importDraftInfo');
//@title 批量分享范本
define('API_POST_Publish_EbayListing_shareDraft','post|Publish/EbayListing/shareDraft');
//@title 同步ebay官网特定站点物流方式
define('API_POST_Publish_trans_sync','post|Publish/trans/sync');
//@title 批量检测刊登费用
define('API_GET_Publish_testfees_batch','get|Publish/testfees/batch');

//控制器：app\goods\controller\Goods
//@title 商品刊登统计
define('API_GET_goods_publishStatistics___id','get|goods/publish-statistics/:id');
//@title 商品列表
define('API_GET_goods','get|goods');
//@title 商品添加
define('API_POST_goods','post|goods');
//@title 商品查看
define('API_GET_goods___id','get|goods/:id');
//@title 更改商品销售状态
define('API_POST_goods_changeStatus','post|goods/changeStatus');
//@title 获取商品sku列表
define('API_GET_goods_skus___id','get|goods/skus/:id');
//@title 查看商品基础详情
define('API_GET_goods_base___id','get|goods/base/:id');
//@title 更新产品基础信息
define('API_PUT_goods_base___id','put|goods/base/:id');
//@title 获取平台状态列表
define('API_GET_goods_platformSaleStatus','get|goods/platform-sale-status');
//@title 获取商品规格信息
define('API_GET_goods_specification___id','get|goods/specification/:id');
//@title 查看商品属性列表
define('API_GET_goods_attribute___id','get|goods/attribute/:id');
//@title 商品供应商列表
define('API_GET_goods_supplier___id','get|goods/supplier/:id');
//@title 根据goods_id返回供应商列表
define('API_GET_goods_getGoodSupplierList','get|goods/getGoodSupplierList');
//@title 获取商品描述
define('API_GET_goods_description___id','get|goods/description/:id');
//@title 更新商品规格参数
define('API_PUT_goods_specification___id','put|goods/specification/:id');
//@title 编辑商品属性
define('API_GET_goods_attribute___id_edit','get|goods/attribute/:id/edit');
//@title 更新产品属性
define('API_PUT_goods_attribute___id','put|goods/attribute/:id');
//@title 更新产品描述
define('API_PUT_goods_description___id','put|goods/description/:id');
//@title 更新产品与渠道映射表
define('API_PUT_goods_goodsCategoryMap___id','put|goods/goodsCategoryMap/:id');
//@title 获取产品的渠道映射
define('API_GET_goods_goodsCategoryMap___id','get|goods/goodsCategoryMap/:id');
//@title 产品日志列表
define('API_GET_goods_log___id','get|goods/log/:id');
//@title 添加产品备注信息
define('API_POST_goods_log___id','post|goods/log/:id');
//@title 查看产品sku信息列表
define('API_GET_goods_skuinfo___id','get|goods/skuinfo/:id');
//@title 编辑产品sku的信息
define('API_GET_goods_skuinfo___id_edit','get|goods/skuinfo/:id/edit');
//@title 保存sku列表信息
define('API_PUT_goods_skuinfo___id','put|goods/skuinfo/:id');
//@title 保存平台销售信息
define('API_PUT_goods___id_platformSale','put|goods/:id/platformSale');
//@title 查看产品质检信息
define('API_GET_goods_qcitems___id','get|goods/qcitems/:id');
//@title 编辑产品质检信息
define('API_GET_goods_qcitems___id_edit','get|goods/qcitems/:id/edit');
//@title 保存产品质检信息
define('API_PUT_goods_qcitems___id','put|goods/qcitems/:id');
//@title 获取产品出售状态
define('API_GET_goods_salesStatus','get|goods/sales-status');
//@title 获取物流属性列表
define('API_GET_goods_transportProperty','get|goods/transport-property');
//@title 获取修图需求列表
define('API_GET_goods_imgRequirement','get|goods/img-requirement');
//@title 查询spu列表
define('API_GET_goods_goodsToSpu','get|goods/goodsToSpu');
//@title 更改商品销售状态
define('API_POST_goods_skuStatus','post|goods/skuStatus');
//@title 编辑sku重量尺寸信息
define('API_GET_skuCheck___id_edit','get|sku-check/:id/edit');
//@title 确认sku重量尺寸信息
define('API_PUT_skuCheck___id','put|sku-check/:id');
//@title 获取比对信息
define('API_GET_goods_comparison___id','get|goods/comparison/:id');
//@title 产品导入
define('API_POST_goods_import','post|goods/import');
//@title 产品导入修改
define('API_POST_goods_importUpdate','post|goods/importUpdate');
//@title 获取SKU附属参数
define('API_GET_goods_getSkuIncidentalParameter','get|goods/getSkuIncidentalParameter');
//@title 下载远程服务器图片
define('API_POST_goods_download','post|goods/download');
//@title 导出商品转成joom格式
define('API_GET_goods_export','get|goods/export');
//@title 获取sku打印标签
define('API_GET_goods_skuLabel','get|goods/sku-label');
//@title 批量抓图
define('API_POST_goods_batchCatchPhoto','post|goods/batch-catch-photo');
//@title 推送到赛盒
define('API_POST_goods_batch_pushIrobotbox','post|goods/batch/push-irobotbox');
//@title 测试推送队列
define('API_GET_goods_pushQueue','get|goods/push-queue');
//@title 导出商品sku
define('API_POST_goods_exportSku','post|goods/export-sku');
//@title 导出noon格式商品
define('API_POST_goods_exportNoon','post|goods/export-noon');
//@title 设置采购员
define('API_POST_goods_setPurchaser','post|goods/set-purchaser');
//@title 获取可供选择的导出字段
define('API_GET_goods_exportField','get|goods/export-field');
//@title 推送赛盒
define('API_GET_goods_iroboboxPush','get|goods/irobobox-push');
//@title 推送分销
define('API_POST_goods_distributionPush','post|goods/distribution-push');
//@title 根据id返回商品信息详情
define('API_GET_goods_api___id_info','get|goods/api/:id/info');
//@title 获取图片phash
define('API_POST_goods_getPhash','post|goods/get-phash');
//@title 跑phash数据
define('API_GET_goods_runPhash','get|goods/run-phash');
//@title 跑phash数据
define('API_GET_goods_runDhash','get|goods/run-dhash');
//@title 获取侵权下架
define('API_GET_goods___id_tort','get|goods/:id/tort');
//@title 保存侵权下架
define('API_POST_goods___id_tort','post|goods/:id/tort');
//@title 推送每日开发数
define('API_GET_goods_pullCountDevelop','get|goods/pull-count-develop');
//@title 统计每日开发数
define('API_GET_goods_countDevelop','get|goods/count-develop');
//@title 获取侵权详情
define('API_GET_goods___id_goodsTortDescription','get|goods/:id/goods-tort-description');
//@title 根据ID获取侵权详情.
define('API_GET_goods_goodsTortDescription___id','get|goods/goods-tort-description/:id');
//@title 保存商品侵权详情
define('API_PUT_goods___id_goodsTortDescription','put|goods/:id/goods-tort-description');
//@title 移除商品侵权详情
define('API_DELETE_goods___id_goodsTortDescription','delete|goods/:id/goods-tort-description');
//@title 获取供应商选择列表
define('API_GET_goods_supplierSelect','get|goods/supplier-select');
//@title 获取侵权列表
define('API_GET_goods_goodsTortDescriptionList','get|goods/goods-tort-description-list');

//控制器：app\publish\controller\Wish
//@title wish部门所有员工
define('API_GET_publish_wish_wishUsers','get|publish/wish/wishUsers');
//@title 删除草稿箱
define('API_POST_publish_wish_deleteDraft','post|publish/wish/deleteDraft');
//@title 草稿箱列表
define('API_GET_publish_wish_draft','get|publish/wish/draft');
//@title 加入待刊登序列
define('API_POST_publish_wish_pushQueue','post|publish/wish/pushQueue');
//@title 统计
define('API_GET_publish_wish_stat','get|publish/wish/stat');
//@title wish所有颜色值
define('API_GET_publish_wish_colors','get|publish/wish/colors');
//@title 验证颜色值是否合法
define('API_POST_publish_wish_validateColor','post|publish/wish/validateColor');
//@title 验证size值是否合法
define('API_POST_publish_wish_validateSize','post|publish/wish/validateSize');
//@title 从产品库刊登保存草稿
define('API_POST_publish_wish_saveMany','post|publish/wish/saveMany');
//@title 从产品库刊登多个商品
define('API_POST_publish_wish_addMany','post|publish/wish/addMany');
//@title 删除
define('API_POST_publish_wish_del','post|publish/wish/del');
//@title 获取wish在线tags
define('API_GET_publish_wish_getWishOnlineTags','get|publish/wish/getWishOnlineTags');
//@title 获取wish size设置
define('API_GET_publish_wish_getWishSize','get|publish/wish/getWishSize');
//@title 上传网络图片
define('API_POST_publish_wish_createNetImage','post|publish/wish/createNetImage');
//@title 上传图片
define('API_POST_publish_wish_uploadImages','post|publish/wish/uploadImages');
//@title 获取商品相册
define('API_GET_publish_wish_gallery','get|publish/wish/gallery');
//@title 获取wish销售人员账号信息
define('API_GET_publish_wish_getSellers','get|publish/wish/getSellers');
//@title 获取品牌
define('API_GET_publish_wish_getBrands','get|publish/wish/getBrands');
//@title 获取刊登页面需要的数据
define('API_GET_publish_wish_getData','get|publish/wish/getData');
//@title 保存并同步到平台
define('API_POST_publish_wish_rsync','post|publish/wish/rsync');
//@title wish刊登保存功能
define('API_POST_publish_wish_save','post|publish/wish/save');
//@title wish刊登功能
define('API_POST_publish_wish_add','post|publish/wish/add');
//@title 获取wish待刊登商品列表
define('API_GET_publish_wish_productList','get|publish/wish/productList');
//@title wish已刊登列表
define('API_GET_publish_wish_lists','get|publish/wish/lists');
//@title wish已刊登变体信息
define('API_GET_publish_wish_getSkus','get|publish/wish/getSkus');
//@title 导出商品转成joom格式
define('API_GET_publish_wish_export','get|publish/wish/export');
//@title wish导出所有商品
define('API_GET_publish_wish_downloadAll','get|publish/wish/download-all');
//@title wish导出字段
define('API_GET_publish_wish_downloadFields','get|publish/wish/download-fields');
//@title 查看侵权详情
define('API_GET_publish_wish_showTort','get|publish/wish/showTort');
//@title 调整成本价
define('API_PUT_wish_adjustCost_batch','put|wish/adjust-cost/batch');

//控制器：app\order\controller\Ebay
//@title 订单列表
define('API_GET_ebayOrders','get|ebay-orders');
//@title 查看
define('API_GET_ebayOrders___id','get|ebay-orders/:id');
//@title 取订单各状态的总数
define('API_GET_ebayOrders_statusCount','get|ebay-orders/status-count');
//@title 更新系统订单物流方式
define('API_GET_ebayOrders_updateShipping','get|ebay-orders/update-shipping');
//@title 查找订单存在
define('API_POST_ebayOrders_exists','post|ebay-orders/exists');
//@title ebay同步平台订单；
define('API_POST_ebayOrders_sysc','post|ebay-orders/sysc');
//@title ebay放款模板下载；
define('API_GET_ebayOrders_exportTransferTemplate','get|ebay-orders/export-transfer-template');
//@title ebay导入放款表单；
define('API_POST_ebayOrders_importTransfer','post|ebay-orders/import-transfer');
//@title ebay运输方式1230；
define('API_GET_ebayOrders_shipping','get|ebay-orders/shipping');
//@title 导出ebay订单需要的字段
define('API_GET_ebayOrders_exportFields','get|ebay-orders/export-fields');
//@title 导出ebay订单
define('API_POST_ebayOrders_export','post|ebay-orders/export');
//@title 推送至系统订单
define('API_POST_ebayOrders_pushEbayOrder','post|ebay-orders/push-ebay-order');
//@title 拉取ebay订单
define('API_POST_ebayOrders_syscEbayorder','post|ebay-orders/sysc-ebayorder');
//@title ebay店铺数据统计
define('API_POST_ebayOrders_shopStatistics','post|ebay-orders/shop-statistics');
//@title paypal数据统计
define('API_POST_ebayOrders_paypalStatistics','post|ebay-orders/paypal-statistics');
//@title 店铺数据导出
define('API_POST_ebayOrders_statisticsExport','post|ebay-orders/statistics-export');

//控制器：app\order\controller\Paypal
//@title 列表
define('API_GET_paypalOrders','get|paypal-orders');
//@title 查看
define('API_GET_paypalOrders___id','get|paypal-orders/:id');
//@title 交易类型列表
define('API_GET_paypalOrders_transactionType','get|paypal-orders/transactionType');
//@title 订单收货人国家列表
define('API_GET_paypalOrders_country','get|paypal-orders/country');
//@title 抓取单个个订单
define('API_GET_paypalOrders_getOrder','get|paypal-orders/get-order');
//@title 同步paypal订单；
define('API_POST_paypalOrders_sync','post|paypal-orders/sync');
//@title pyapal数据导出
define('API_POST_paypalOrders_statisticsExport','post|paypal-orders/statistics-export');

//控制器：app\order\controller\Fbs
//@title fbs订单列表
define('API_GET_fbsOrders_index','get|fbs-orders/index');
//@title 查看fbs详情
define('API_GET_fbsOrders_read','get|fbs-orders/read');
//@title fbs订单报表导出
define('API_POST_fbsOrders_export','post|fbs-orders/export');
//@title 获取所有导出字段
define('API_GET_fbsOrders_exportFields','get|fbs-orders/export-fields');

//控制器：app\purchase\controller\PurchaseParcels
//@title 创建包裹
define('API_POST_purchaseParcels_createUpdateParcel','post|purchase-parcels/createUpdateParcel');
//@title 包裹列表
define('API_GET_purchaseParcels_getParcelList','get|purchase-parcels/getParcelList');
//@title 导出包裹查询
define('API_POST_purchaseParcels_export','post|purchase-parcels/export');
//@title 包裹详情
define('API_GET_purchaseParcels_getPurchaseParcelDetail','get|purchase-parcels/getPurchaseParcelDetail');
//@title 根据运单号找采购单列表
define('API_GET_purchaseParcels_getPurchaseOrderInfoByTrackingNo','get|purchase-parcels/getPurchaseOrderInfoByTrackingNo');
//@title 根据订单ID列表找采购单列表
define('API_GET_purchaseParcels_getPurchaseOrderInfoByIds','get|purchase-parcels/getPurchaseOrderInfoByIds');
//@title 收货
define('API_POST_purchaseParcels_receiptParcel','post|purchase-parcels/receiptParcel');
//@title 采购包裹收货审核(废弃, 调用PurchaseParcelsAudit控制器)
define('API_POST_purchaseParcels_auditParcel','post|purchase-parcels/auditParcel');
//@title 包裹列表(包裹拆开)
define('API_GET_purchaseParcels_getParcelListForParcelSplitting','get|purchase-parcels/getParcelListForParcelSplitting');
//@title  标记包裹拆包异常
define('API_PUT_purchaseParcels_abnormal','put|purchase-parcels/abnormal');
//@title  包裹拆包异常列表
define('API_GET_purchaseParcels_abnormal','get|purchase-parcels/abnormal');
//@title  处理包裹异常
define('API_PUT_purchaseParcels_batch_abnormal','put|purchase-parcels/batch/abnormal');
//@title 标记为已处理(拆包异常)
define('API_PUT_purchaseParcels_batch_end','put|purchase-parcels/batch/end');
//@title  拆包员列表
define('API_GET_purchaseParcels_unpackedList','get|purchase-parcels/unpacked-list');
//@title 删除包裹
define('API_POST_purchaseParcels_deletePurchaseParcel','post|purchase-parcels/deletePurchaseParcel');
//@title 编辑包裹(按字段)
define('API_POST_purchaseParcels_updatePurchaseParcelByField','post|purchase-parcels/updatePurchaseParcelByField');
//@title 编辑包裹（重量 运单号 收货台）
define('API_POST_purchaseParcels_updatePurchaseParcel','post|purchase-parcels/updatePurchaseParcel');
//@title 根据采购单ID和包裹编号取得SKU的标签信息
define('API_GET_purchaseParcels_getSkuLabelInfo','get|purchase-parcels/getSkuLabelInfo');
//@title 包裹预接收
define('API_POST_purchaseParcels_readyReceive','post|purchase-parcels/ready-receive');
//@title 根据运单号获取预接收包裹
define('API_GET_purchaseParcels_readyReceive','get|purchase-parcels/ready-receive');
//@title 修改预接收包裹信息
define('API_PUT_purchaseParcels_readyReceive','put|purchase-parcels/ready-receive');
//@title 标记收包异常
define('API_POST_purchaseParcels_receiveAbnormal','post|purchase-parcels/receive-abnormal');
//@title 获取用户一级部门节点
define('API_GET_purchaseParcels_userDepartment','get|purchase-parcels/user-department');
//@title 收包异常列表
define('API_GET_purchaseParcels_receiveAbnormal','get|purchase-parcels/receive-abnormal');
//@title 回复收包异常包裹
define('API_PUT_purchaseParcels_replyLetter','put|purchase-parcels/reply-letter');
//@title 收包异常上传凭证
define('API_PUT_purchaseParcels_certificate','put|purchase-parcels/certificate');
//@title 收包异常包裹标记已处理
define('API_PUT_purchaseParcels_processStatus','put|purchase-parcels/process-status');
//@title 统计异常（拆包，收包）总数
define('API_GET_purchaseParcels_abnormalCount','get|purchase-parcels/abnormal-count');
//@title 包裹异常来源
define('API_GET_purchaseParcels_abnormalSource','get|purchase-parcels/abnormal-source');
//@title 包裹异常跟进(其他接收异常)
define('API_POST_purchaseParcels_parcelAbnormal','post|purchase-parcels/parcel-abnormal');
//@title 获取接收异常类型
define('API_GET_purchaseParcels_abnormalType','get|purchase-parcels/abnormal-type');
//@title 包裹退回列表-仓库
define('API_GET_purchaseParcels_returnOfGoodsWarehouse','get|purchase-parcels/return-of-goods-warehouse');
//@title 仓库填入邮寄信息
define('API_PUT_purchaseParcels___abnormal_id_warehouse_mail','put|purchase-parcels/:abnormal_id/warehouse/mail');
//@title 移动之前拆包异常数据
define('API_GET_purchaseParcels_moveUnpackAbnormalData','get|purchase-parcels/move-unpack-abnormal-data');

//控制器：app\purchase\controller\PurchaseProposal
//@title  显示列表
define('API_GET_purchaseProposal','get|purchase-proposal');
//@title 保存采购建议
define('API_POST_purchaseProposal','post|purchase-proposal');
//@title 查看
define('API_GET_purchaseProposal___id','get|purchase-proposal/:id');
//@title 显示编辑资源表单页.
define('API_GET_purchaseProposal___id_edit','get|purchase-proposal/:id/edit');
//@title 更新的资源
define('API_PUT_purchaseProposal___id','put|purchase-proposal/:id');
//@title 生成采购计划
define('API_POST_purchaseProposal_createPurchasePlan','post|purchase-proposal/createPurchasePlan');
//@title   初始化采购建议
define('API_POST_purchaseProposal_calculatePurchaseProposal','post|purchase-proposal/calculatePurchaseProposal');
//@title 采购员最后一次生成的采购建议 的时间
define('API_GET_purchaseProposal_lastPurchaseProposal','get|purchase-proposal/lastPurchaseProposal');
//@title 获取sku供应商
define('API_GET_purchaseProposal_getSupplier','get|purchase-proposal/getSupplier');
//@title 获取图表数据
define('API_GET_purchaseProposal_chartData','get|purchase-proposal/chart-data');
//@title 更新采购建议的采购数量 采购价格 供应商
define('API_POST_purchaseProposal_updateProposalArgs','post|purchase-proposal/updateProposalArgs');
//@title 更新采购建议的采购数量 采购价格 供应商(生成采购计划前)
define('API_POST_purchaseProposal_updateProposalArgsBeforeCreatePlan','post|purchase-proposal/updateProposalArgsBeforeCreatePlan');
//@title  重置已生成的采购建议的状态为未生成
define('API_POST_purchaseProposal_resetProposalStatus','post|purchase-proposal/resetProposalStatus');
//@title 导出采购建议
define('API_POST_purchaseProposal_export','post|purchase-proposal/export');
//@title 删除采购建议
define('API_POST_purchaseProposal_delete','post|purchase-proposal/delete');

//控制器：app\purchase\controller\PurchaseApply
//@title 显示列表
define('API_GET_purchaseApply','get|purchase-apply');
//@title 付款申请管理导出字段
define('API_GET_purchaseApply_exportFields','get|purchase-apply/export-fields');
//@title 导出采购付款
define('API_POST_purchaseApply_export','post|purchase-apply/export');
//@title 获取状态标签
define('API_GET_purchaseApply_statusLabel','get|purchase-apply/status-label');
//@title  采购审核
define('API_POST_purchaseApply_auditPurchaser','post|purchase-apply/audit-purchaser');
//@title  财务审核
define('API_POST_purchaseApply_auditFinance','post|purchase-apply/audit-finance');
//@title  财务复核
define('API_POST_purchaseApply_auditFinance2','post|purchase-apply/audit-finance2');
//@title 查看
define('API_GET_purchaseApply___id','get|purchase-apply/:id');
//@title 修改
define('API_PUT_purchaseApply___id','put|purchase-apply/:id');
//@title 取消付款
define('API_POST_purchaseApply_cancel','post|purchase-apply/cancel');
//@title 作废
define('API_POST_purchaseApply_invalid','post|purchase-apply/invalid');
//@title 标记已付款
define('API_POST_purchaseApply_markPayed','post|purchase-apply/mark-payed');
//@title 编辑页面
define('API_GET_purchaseApply___id_edit','get|purchase-apply/:id/edit');
//@title 计算付款总金额
define('API_POST_purchaseApply_calculatingMoney','post|purchase-apply/calculating-money');
//@title 导出富友
define('API_POST_purchaseApply_exportFuyou','post|purchase-apply/export-fuyou');
//@title 获取日志记录
define('API_GET_purchaseApply___id_log','get|purchase-apply/:id/log');
//@title 上传发票
define('API_POST_purchaseApply_uploadImages','post|purchase-apply/upload-images');
//@title 导出发票
define('API_GET_purchaseApply_downInvoice___id','get|purchase-apply/down-invoice/:id');
//@title 上传付款回单
define('API_POST_purchaseApply_uploadPaymentImages','post|purchase-apply/upload-payment-images');
//@title 批量修改结算方式
define('API_PUT_purchaseApply_balanceType','put|purchase-apply/balance-type');

//控制器：app\publish\controller\Express
//@title 获取速卖通部门所有员工
define('API_GET_publish_express_users','get|publish/express/users');
//@title 同步分类
define('API_POST_aliexpressRsynCategory','post|aliexpress-rsyn-category');
//@title 速卖通刊登错误码解释
define('API_POST_publish_express_errorExplain','post|publish/express/error-explain');
//@title 速卖通已刊登导出
define('API_POST_publish_express_download','post|publish/express/download');
//@title 速卖通帐号本地授权分类列表
define('API_GET_aliexpressAuthLocalCategory','get|aliexpress-auth-local-category');
//@title 速卖通帐号
define('API_GET_aliexpressAccounts','get|aliexpress-accounts');
//@title 速卖通刊登详情预览
define('API_GET_publish_express_','get|publish/express/');
//@title 速卖通平台根据品牌获取分类
define('API_GET_publish_express_aliexpressGetCategoriesByBrand','get|publish/express/aliexpress-get-categories-by-brand');
//@title 速卖通平台分类列表
define('API_GET_publish_express_aliexpressCategories','get|publish/express/aliexpress-categories');
//@title 保存速卖通刊登分类属性
define('API_POST_aliexpressSavePublishTemplate','post|aliexpress-save-publish-template');
//@title 速卖通分类列表
define('API_GET_aliexpressCategoryTree','get|aliexpress-category-tree');
//@title Aliexpress刊登列表
define('API_GET_publish_express','get|publish/express');
//@title 获取已刊登商品列表
define('API_GET_publish_express_product','get|publish/express/product');
//@title 编辑商品
define('API_GET_publish_express_editProduct','get|publish/express/editProduct');
//@title 未刊登商品列表
define('API_GET_publish_express_unpublish','get|publish/express/unpublish');
//@title 根据pid查询子分类
define('API_GET_publish_express_categorys','get|publish/express/categorys');
//@title 商品上架
define('API_POST_publish_express_online','post|publish/express/online');
//@title 商品下架
define('API_POST_publish_express_offline','post|publish/express/offline');
//@title 根据类目ID获取适合的尺码模板
define('API_GET_publish_express_sizetemp','get|publish/express/sizetemp');
//@title 查询指定分类的属性
define('API_GET_publish_express_attributes','get|publish/express/attributes');
//@title 获取商户账户列表
define('API_GET_publish_express_accounts','get|publish/express/accounts');
//@title 获取商品列表
define('API_GET_publish_express_goods','get|publish/express/goods');
//@title 根据产品ID获取映射到速卖通的分类ID
define('API_GET_publish_express_aliCategoryid','get|publish/express/aliCategoryid');
//@title 获取待刊登的产品详情
define('API_GET_publish_express_productInfo','get|publish/express/productInfo');
//@title 获取仓库列表
define('API_GET_publish_express_warehouses','get|publish/express/warehouses');
//@title 获取商户运费模板
define('API_GET_publish_express_freightTemp','get|publish/express/freightTemp');
//@title 获取商户服务模板
define('API_GET_publish_express_promiseTemp','get|publish/express/promiseTemp');
//@title 获取商户商品分组
define('API_GET_publish_express_groups','get|publish/express/groups');
//@title 判断该商户是否拥有某样产品刊登的分类权限
define('API_GET_publish_express_categoryPermission','get|publish/express/categoryPermission');
//@title 根据产品ID、平台账号ID和分类ID获取公有数据
define('API_GET_publish_express_pulishData','get|publish/express/pulishData');
//@title 根据产品ID获取产品可选的产品图片
define('API_GET_publish_express_images','get|publish/express/images');
//@title 速卖通刊登
define('API_POST_publish_express_publish','post|publish/express/publish');
//@title 上传图片到临时目录
define('API_POST_publish_express_uploadTemp','post|publish/express/uploadTemp');
//@title 图片上传到速卖通图片银行
define('API_POST_publish_express_upload','post|publish/express/upload');
//@title 获取速卖通产品计数单位
define('API_GET_publish_express_productUnit','get|publish/express/productUnit');
//@title 违禁词检测
define('API_POST_publish_express_prohibited','post|publish/express/prohibited');
//@title 根据sku和分类获取listing信息
define('API_GET_publish_express_skuInfo','get|publish/express/skuInfo');
//@title 批量修改标题、服务模板、运费模板、毛重
define('API_POST_publish_express_batchProduct','post|publish/express/batchProduct');
//@title 批量修改尺寸
define('API_POST_publish_express_batchSize','post|publish/express/batchSize');
//@title 批量修改商品计数单位
define('API_POST_publish_express_batchUnit','post|publish/express/batchUnit');
//@title 批量修改商品SKU价格
define('API_POST_publish_express_batchPrice','post|publish/express/batchPrice');
//@title 批量删除
define('API_DELETE_publish_express_batchDelete','delete|publish/express/batchDelete');
//@title 获取平台产品状态
define('API_GET_publish_express_productStatus','get|publish/express/productStatus');
//@title 剩余有效期
define('API_GET_publish_express_expireSearch','get|publish/express/expireSearch');
//@title 获取已刊登商品简易列表
define('API_GET_publish_express_productList','get|publish/express/productList');
//@title 复制商品
define('API_GET_publish_express_copy','get|publish/express/copy');
//@title 根据账号和分类获取品牌信息
define('API_GET_publish_express_brands','get|publish/express/brands');
//@title 获取信息模板
define('API_GET_publish_express___account_id___type_productTemp','get|publish/express/:account_id/:type/productTemp');
//@title 草稿箱
define('API_GET_publish_express_drafts','get|publish/express/drafts');
//@title 待刊登列表
define('API_GET_publish_express_waitPublish','get|publish/express/wait-publish');
//@title 刊登异常列表
define('API_GET_publish_express_failPublish','get|publish/express/fail-publish');
//@title 更改成本价
define('API_GET_publish_express_changeCostPrice','get|publish/express/change-cost-price');
//@title 速卖通批量复制为草稿箱
define('API_POST_publish_express_batchCopy','post|publish/express/batch-copy');
//@title 未刊登列表品牌搜索
define('API_GET_publish_express_productBrand','get|publish/express/product-brand');
//@title 分组列表
define('API_GET_publish_express_regionGroup','get|publish/express/region-group');
//@title 区域模板列表
define('API_GET_publish_express_regionTemplate','get|publish/express/region-template');
//@title 添加分组
define('API_POST_publish_express_addRegionGroup','post|publish/express/add-region-group');
//@title 添加区域模板
define('API_POST_publish_express_addRegionTemplate','post|publish/express/add-region-template');
//@title 编辑区域模板
define('API_POST_publish_express_editRegionTemplate','post|publish/express/edit-region-template');
//@title 编辑分组
define('API_POST_publish_express_editRegionGroup','post|publish/express/edit-region-group');
//@title 删除分组
define('API_POST_publish_express_deleteRegionGroup','post|publish/express/delete-region-group');
//@title 删除区域模板
define('API_POST_publish_express_deleteRegionTemplate','post|publish/express/delete-region-template');
//@title 根据模板id获取模板
define('API_GET_publish_express_regionTemplateInfo','get|publish/express/region-template-info');
//@title 刊登失败批量提交刊登
define('API_POST_publish_express_batchAddFailPublish','post|publish/express/batch-add-fail-publish');
//@title 速卖通刊登保存草稿
define('API_POST_publish_express_saveDraft','post|publish/express/save-draft');
//@title 刊登队列批量提交刊登
define('API_POST_publish_express_batchAddWaitPublish','post|publish/express/batch-add-wait-publish');
//@title 未刊登侵权信息
define('API_POST_publish_express_goodsTortInfo','post|publish/express/goods-tort-info');
//@title 修复线上速卖通刊登异常数据
define('API_GET_publish_express_failPublishSave','get|publish/express/fail-publish-save');

//控制器：app\listing\controller\Wish
//@title 取消wish express
define('API_POST_disableWishExpress','post|disable-wish-express');
//@title 批量设置wish express数据
define('API_POST_batchSettingWishExpress','post|batch-setting-wish-express');
//@title 获取wish express数据
define('API_GET_listing_wish_getWishExpressData','get|listing/wish/getWishExpressData');
//@title wish在线listing修改日志
define('API_GET_listing_wish_logs','get|listing/wish/logs');
//@title 更新修改了的资料listing
define('API_POST_listing_wish_rsyncEditListing','post|listing/wish/rsyncEditListing');
//@title 批量编辑获取sku数据
define('API_GET_listing_wish_batchEdit','get|listing/wish/batchEdit');
//@title 批量编辑提交
define('API_POST_listing_wish_batchEditAction','post|listing/wish/batchEditAction');
//@title 同步listing
define('API_POST_listing_wish_rsyncListing','post|listing/wish/rsyncListing');
//@title 编辑指定国家的运费
define('API_POST_listing_wish_updateShipping','post|listing/wish/updateShipping');
//@title 编辑产品的所有的国家航运价格
define('API_POST_listing_wish_updateMultiShipping','post|listing/wish/updateMultiShipping');
//@title 编辑产品的所有的国家航运价格
define('API_POST_listing_wish_updateMultiShippingRightNow','post|listing/wish/updateMultiShippingRightNow');
//@title 获取wish  express
define('API_GET_listing_wish_getShipping','get|listing/wish/getShipping');
//@title 更新wish在线listing数据
define('API_POST_listing_wish_updateListing','post|listing/wish/updateListing');
//@title 更新已刊登listing数据
define('API_POST_listing_wish_updatePublishedListing','post|listing/wish/updatePublishedListing');
//@title wish刊登模块查看功能
define('API_GET_listing_wish_view','get|listing/wish/view');
//@title wish刊登模块编辑功能
define('API_GET_listing_wish_edit','get|listing/wish/edit');
//@title wish刊登模块复制功能
define('API_GET_listing_wish_copy','get|listing/wish/copy');
//@title 补货
define('API_POST_listing_wish_buhuo','post|listing/wish/buhuo');
//@title 批量上架
define('API_POST_listing_wish_batchEnable','post|listing/wish/batchEnable');
//@title 批量下架
define('API_POST_listing_wish_batchDisable','post|listing/wish/batchDisable');
//@title 在线产品上架
define('API_POST_listing_wish_enable','post|listing/wish/enable');
//@title 在线产品下架
define('API_POST_listing_wish_disable','post|listing/wish/disable');
//@title sku下架
define('API_POST_listing_wish_disableVariant','post|listing/wish/disableVariant');
//@title skus上架
define('API_POST_listing_wish_enableVariant','post|listing/wish/enableVariant');
//@title 批量上架sku
define('API_POST_listing_wish_batchEnableVariant','post|listing/wish/batchEnableVariant');
//@title 批量下架sku
define('API_POST_listing_wish_batchDisableVariant','post|listing/wish/batchDisableVariant');
//@title 批量同步listing,不走队列
define('API_POST_listing_wish_rsyncNowListing','post|listing/wish/rsyncNowListing');

//控制器：app\listing\controller\Aliexpress
//@title 获取选中spu的分类
define('API_GET_listing_aliexpress_getSameSpuCategory','get|listing/aliexpress/get-same-spu-category');
//@title 查询所选SPU的产品分类值,及对应的属性、属性值
define('API_GET_listing_aliexpress_getSameSpu-Attribute','get|listing/aliexpress/get-same-spu-Attribute');
//@title 速卖通获取类似产品
define('API_GET_listing_aliexpress_getSameSpu','get|listing/aliexpress/getSameSpu');
//@title 速卖通在线listing修改日志
define('API_GET_listing_aliexpress_logs','get|listing/aliexpress/logs');
//@title 速卖通卖家橱窗设置
define('API_GET_aliexpressWindowsDetail','get|aliexpress-windows-detail');
//@title 速卖通卖家橱窗设置
define('API_GET_aliexpressWindowsList','get|aliexpress-windows-list');
//@title 速卖通卖家橱窗设置
define('API_POST_setWindowProducts','post|setWindowProducts');
//@title 修改sku库存信息
define('API_POST_editAeStock','post|editAeStock');
//@title 修改sku售价信息
define('API_POST_editAePrice','post|editAePrice');
//@title 修改信息模板
define('API_POST_editAeTemlate','post|editAeTemlate');
//@title 速卖通上架
define('API_POST_onlineAeProduct','post|onlineAeProduct');
//@title 速卖通下架
define('API_POST_offlineAeProduct','post|offlineAeProduct');
//@title 修改产品分组
define('API_POST_editAeGroupId','post|editAeGroupId');
//@title 编辑发货期
define('API_POST_editAeDeliveryTime','post|editAeDeliveryTime');
//@title 延长商品有效期
define('API_POST_editAeWsValidNum','post|editAeWsValidNum');
//@title 商品标题
define('API_POST_editAeSubject','post|editAeSubject');
//@title 商品销售单元
define('API_POST_editAeProductUnit','post|editAeProductUnit');
//@title 修改产品毛重
define('API_POST_editAeGrossWeight','post|editAeGrossWeight');
//@title 修改包装尺寸
define('API_POST_editAePackage','post|editAePackage');
//@title 服务模板设置
define('API_POST_editAePromiseTemplateId','post|editAePromiseTemplateId');
//@title 运费模板设置
define('API_POST_editAeFreightTemplateId','post|editAeFreightTemplateId');
//@title 商品一口价
define('API_POST_editAeProductPrice','post|editAeProductPrice');
//@title 同步listing
define('API_POST_rsyncAeProduct','post|rsyncAeProduct');
//@title 更新修改了资料的listing
define('API_POST_rsyncEditAeProduct','post|rsyncEditAeProduct');

//控制器：app\order\controller\Order
//@title 订单列表
define('API_GET_orders','get|orders');
//@title 读取信息
define('API_GET_orders___id','get|orders/:id');
//@title 读取编辑信息
define('API_GET_orders___id_edit','get|orders/:id/edit');
//@title 更新
define('API_PUT_orders___id','put|orders/:id');
//@title 获取平台渠道
define('API_GET_orders_channel','get|orders/channel');
//@title 获取平台/站点账号信息
define('API_GET_orders_account','get|orders/account');
//@title 账号 店铺 信息
define('API_GET_orders_shop','get|orders/shop');
//@title 获取操作信息
define('API_GET_orders___type_info','get|orders/:type/info');
//@title 生成发票
define('API_GET_orders___order_id_generate','get|orders/:order_id/generate');
//@title 合并包裹功能
define('API_POST_orders___order_id_merge','post|orders/:order_id/merge');
//@title 拆分包裹功能
define('API_POST_orders___order_id_split','post|orders/:order_id/split');
//@title 地址使用
define('API_POST_orders___order_id_address','post|orders/:order_id/address');
//@title 标记已读,删除备注等操作
define('API_POST_orders___order_id___type','post|orders/:order_id/:type');
//@title 获取进度条信息
define('API_GET_orders___order_id_speed','get|orders/:order_id/speed');
//@title 获取产品详情信息
define('API_GET_orders___order_id_detail','get|orders/:order_id/detail');
//@title 获取拆包裹的订单信息
define('API_GET_orders___order_id_split','get|orders/:order_id/split');
//@title 获取合并包裹的订单信息
define('API_GET_orders___order_id_merge','get|orders/:order_id/merge');
//@title 标记为未付款
define('API_POST_orders___order_id_status_0','post|orders/:order_id/status/0');
//@title 标记为已付款
define('API_POST_orders___order_id_status_65536','post|orders/:order_id/status/65536');
//@title 需人工审核
define('API_POST_orders___order_id_status_65792','post|orders/:order_id/status/65792');
//@title 标记为已审核
define('API_POST_orders___order_id_status_65793','post|orders/:order_id/status/65793');
//@title 作废订单
define('API_POST_orders___order_id_status_4294967295','post|orders/:order_id/status/4294967295');
//@title 标记刷单
define('API_POST_orders___order_id_status_9999999999','post|orders/:order_id/status/9999999999');
//@title 取消作废
define('API_POST_orders___order_id_status_cancelInvalid','post|orders/:order_id/status/cancel-invalid');
//@title 取消仓库推送
define('API_POST_orders_cancelPush','post|orders/cancel-push');
//@title 取消物流下单
define('API_POST_orders_cancelLogistics','post|orders/cancel-logistics');
//@title 批量更改运输方式
define('API_POST_orders_updateShipping','post|orders/update-shipping');
//@title 订单重新跑规则
define('API_POST_orders_againRunningRule','post|orders/again-running-rule');
//@title 获取产品-添加货品
define('API_GET_orders_getGoods','get|orders/getGoods');
//@title 获取指定类型单号的买家和归属平台账号的信息
define('API_GET_orders___order_number_type___order_number_buyerInfo','get|orders/:order_number_type/:order_number/buyer-info');
//@title 导出execl
define('API_POST_orders_export','post|orders/export');
//@title execl字段信息
define('API_GET_orders_exportTitle','get|orders/export-title');
//@title sku新增备注信息
define('API_POST_orders___order_id___sku_id_note','post|orders/:order_id/:sku_id/note');
//@title 批量新增订单备注
define('API_POST_orders_batch_remark','post|orders/batch/remark');
//@title 重新获取商品成本
define('API_GET_orders___order_id_cost','get|orders/:order_id/cost');
//@title 导入跟踪号
define('API_POST_orders_tracking_import','post|orders/tracking/import');
//@title 批量作废
define('API_POST_orders_batch_invalid','post|orders/batch/invalid');
//@title 延长买家收货时间
define('API_POST_orders___day_delayTime','post|orders/:day/delay-time');
//@title 通过订单号查询物流信息
define('API_POST_orders___order_id_logisticsInfo','post|orders/:order_id/logistics-info');
//@title 批量更换仓库
define('API_POST_orders_batch_changeWarehouse','post|orders/batch/change-warehouse');
//@title 试跑规则
define('API_POST_orders_trialRule','post|orders/trial-rule');
//@title 取消订单接口
define('API_POST_orders_cancelOrder','post|orders/cancel-order');
//@title 发送发票
define('API_POST_orders_sendInvoice','post|orders/send-invoice');
//@title 批量人工审核
define('API_POST_orders_batchReview','post|orders/batch-review');
//@title 取消订单重新退款
define('API_POST_orders_orderRenewRefund','post|orders/order-renew-refund');
//@title 手动标记已退款
define('API_POST_orders_markRefundFailed','post|orders/mark-refund-failed');
//@title 订单备注批量导入
define('API_POST_orders_batchImport','post|orders/batch-import');
//@title 品连订单回推测试
define('API_GET_orders_test','get|orders/test');
//@title 品连订单回推测试[异常]
define('API_GET_orders_test2','get|orders/test2');
//@title 批量联系买家
define('API_POST_orders_batch_sendMessage','post|orders/batch/send-message');

//控制器：app\goods\controller\Goodsdev
//@title 产品开发列表
define('API_GET_goodsdev','get|goodsdev');
//@title 保存产品开发
define('API_POST_goodsdev','post|goodsdev');
//@title 保存产品开发
define('API_POST_goodsdev_save_baseInfo','post|goodsdev/save/base-info');
//@title 编辑产品开发
define('API_GET___id_edit','get|:id/edit');
//@title 更新产品开发
define('API_PUT___id','put|:id');
//@title 查看分类规格参数
define('API_GET_goodsdev_categorySpecification___id','get|goodsdev/category-specification/:id');
//@title 查看分类属性
define('API_GET_goodsdev_categoryAttribute___id','get|goodsdev/category-attribute/:id');
//@title 查看产品开发基础信息
define('API_GET_goodsdev___id_baseInfo','get|goodsdev/:id/base-info');
//@title 查看产品开发供应商信息
define('API_GET_goodsdev___id_supplier','get|goodsdev/:id/supplier');
//@title 保存供应商信息
define('API_PUT_goodsdev___id_supplier','put|goodsdev/:id/supplier');
//@title 查看产品开发规格信息
define('API_GET_goodsdev___id_specification','get|goodsdev/:id/specification');
//@title 查看产品开发属性
define('API_GET_goodsdev___id_attribute','get|goodsdev/:id/attribute');
//@title 查看产品开发描述
define('API_GET_goodsdev___id_description','get|goodsdev/:id/description');
//@title 查看产品开发日志
define('API_GET_goodsdev___id_logs','get|goodsdev/:id/logs');
//@title 添加产品开发备注
define('API_POST_goodsdev_log___id','post|goodsdev/log/:id');
//@title 更新产品开发描述
define('API_PUT_goodsdev_description___id','put|goodsdev/description/:id');
//@title 更新产品开发基础信息
define('API_PUT_goodsdev_base___id','put|goodsdev/base/:id');
//@title 更新产品开发规格信息
define('API_PUT_goodsdev_specification___id','put|goodsdev/specification/:id');
//@title 获取编辑开发产品属性信息
define('API_GET_goodsdev_attribute___id_edit','get|goodsdev/attribute/:id/edit');
//@title 更新开发产品属性参数
define('API_PUT_goodsdev_attribute___id','put|goodsdev/attribute/:id');
//@title 获取流程按钮组
define('API_GET_goodsdev_processbtn','get|goodsdev/processbtn');
//@title 获取流程处理按钮根据ID
define('API_GET_goodsdev_processbtn___id','get|goodsdev/processbtn/:id');
//@title 产品开发流程操作
define('API_PUT_goodsdev_process___id','put|goodsdev/process/:id');
//@title 获取分类sku
define('API_POST_goodsdev_categorySku','post|goodsdev/category-sku');
//@title 获取平台销售状态
define('API_GET_goodsdev_platformSaleStatus','get|goodsdev/platform-sale-status');
//@title 获取平台分类
define('API_GET_goodsdev___id_platformSale','get|goodsdev/:id/platform-sale');
//@title 保存平台分类
define('API_PUT_goodsdev___id_platformSale','put|goodsdev/:id/platform-sale');
//@title 获取编辑sku
define('API_GET_goodsdev___id_skuList','get|goodsdev/:id/sku-list');
//@title 保存sku列表信息
define('API_PUT_goodsdev___id_skuList','put|goodsdev/:id/sku-list');
//@title 获取编辑产品质检信息
define('API_GET_goodsdev___id_qcitems','get|goodsdev/:id/qcitems');
//@title 获取产品修图要求
define('API_GET_goodsdev___id_imgRequirement','get|goodsdev/:id/img-requirement');
//@title 保存修图要求
define('API_PUT_goodsdev___id_imgRequirement','put|goodsdev/:id/img-requirement');
//@title 保存产品质检信息
define('API_PUT_goodsdev___id_qcitems','put|goodsdev/:id/qcitems');
//@title 获取开发产品节点信息
define('API_GET_goodsdev_node___id','get|goodsdev/node/:id');
//@title 批量处理流程
define('API_POST_goodsdev_batch_process','post|goodsdev/batch/process');
//@title 批量添加开发者矩阵批量添加开发者
define('API_POST_developer_batch_add','post|developer/batch/add');
//@title 修改开发员信息
define('API_PUT_developer___id','put|developer/:id');
//@title 开发员矩阵列表
define('API_GET_developer','get|developer');
//@title 删除开发员信息
define('API_DELETE_developer___id','delete|developer/:id');
//@title 获取开发员矩阵详情
define('API_GET_goodsdev___id_developer','get|goodsdev/:id/developer');
//@title 生成sku
define('API_GET_goodsdev___id_generateSku','get|goodsdev/:id/generate-sku');
//@title 确认生成sku
define('API_PUT_goodsdev___id_generateSku','put|goodsdev/:id/generate-sku');
//@title 保存报关信息
define('API_PUT_goodsdev___id_declare','put|goodsdev/:id/declare');
//@title 指定摄影师
define('API_PUT_goodsdev___id_setGrapher','put|goodsdev/:id/set-grapher');
//@title 开始拍图
define('API_PUT_goodsdev___id_startPhoto','put|goodsdev/:id/start-photo');
//@title 设置原图路径
define('API_PUT_goodsdev___id_setPhotoPath','put|goodsdev/:id/set-photo-path');
//@title 获取拍图待审核信息
define('API_GET_goodsdev___id_photo','get|goodsdev/:id/photo');
//@title 分配翻译员
define('API_PUT_goodsdev___id_setTranslator','put|goodsdev/:id/set-translator');
//@title 获取翻译员信息
define('API_GET_goodsdev___id_translatorInfo','get|goodsdev/:id/translator-info');
//@title 开始翻译
define('API_PUT_goodsdev___id_translatorStarting','put|goodsdev/:id/translator-starting');
//@title 翻译中确定
define('API_PUT_goodsdev___id_translatorIng','put|goodsdev/:id/translator-ing');
//@title 翻译提交审批
define('API_PUT_goodsdev___id___lang_id_translatorSubmit','put|goodsdev/:id/:lang_id/translator-submit');
//@title 审核不通过退回语种
define('API_PUT_goodsdev___id_translatorBack','put|goodsdev/:id/translator-back');
//@title 待分配修图指定美工
define('API_PUT_goodsdev___id_designerSetting','put|goodsdev/:id/designer-setting');
//@title 开始修图
define('API_PUT_goodsdev___id_designerStarting','put|goodsdev/:id/designer-starting');
//@title 保存修图路径
define('API_PUT_goodsdev___id_ps_img_url','put|goodsdev/:id/ps_img_url');
//@title 提交终审..
define('API_PUT_goodsdev___id_final_submit','put|goodsdev/:id/final_submit');
//@title 获取退回的指定节点
define('API_GET_goodsdev___id_backProcess','get|goodsdev/:id/back-process');
//@title 退回的指定节点
define('API_PUT_goodsdev___id_backProcess','put|goodsdev/:id/back-process');
//@title 发布产品
define('API_PUT_goodsdev___id_release','put|goodsdev/:id/release');
//@title 获取菜单
define('API_GET_goodsdev___id_menu','get|goodsdev/:id/menu');

//控制器：app\index\controller\PurchaseSubclassMap
//@title 列表
define('API_GET_subMap','get|sub-map');
//@title 信息
define('API_GET_subMap___id','get|sub-map/:id');
//@title 获取编辑信息
define('API_GET_subMap___id_edit','get|sub-map/:id/edit');
//@title 新增关系
define('API_POST_subMap','post|sub-map');
//@title 更新关系
define('API_PUT_subMap___id','put|sub-map/:id');
//@title 删除
define('API_DELETE_subMap___id','delete|sub-map/:id');
//@title 批量删除
define('API_POST_subMap_batch___type','post|sub-map/batch/:type');

//控制器：app\warehouse\controller\Warehouse
//@title 仓库管理列表
define('API_GET_warehouse','get|warehouse');
//@title 仓库管理添加
define('API_POST_warehouse','post|warehouse');
//@title 仓库管理获取
define('API_GET_warehouse___id','get|warehouse/:id');
//@title 获取仓库邮寄方式
define('API_GET_warehouse___id_shippingList','get|warehouse/:id/shipping-list');
//@title 仓库管理更新
define('API_PUT_warehouse___id','put|warehouse/:id');
//@title 仓库管理删除
define('API_DELETE_warehouse___id','delete|warehouse/:id');
//@title 修改仓库状态
define('API_POST_warehouse_status','post|warehouse/status');
//@title 获取全部物流商
define('API_GET_warehouse_getCarrier','get|warehouse/getCarrier');
//@title 获取物流商对应的物流方式
define('API_GET_warehouse_getShip','get|warehouse/getShip');
//@title 更新仓库
define('API_POST_warehouse_updateWareHouse','post|warehouse/updateWareHouse');
//@title 获取省市区
define('API_GET_warehouse_getArea','get|warehouse/getArea');
//@title 获取仓库类型
define('API_GET_warehouse_getWarehouseType','get|warehouse/getWarehouseType');
//@title search
define('API_GET_warehouse_search','get|warehouse/search');
//@title 获取第三方仓库具体代码
define('API_GET_warehouse_getWarehousesByType','get|warehouse/getWarehousesByType');
//@title 获取所有仓库
define('API_GET_global_warehouse','get|global/warehouse');
//@title 获取海外仓
define('API_GET_warehouse_overseas','get|warehouse/overseas');
//@title 获取本地及海外仓
define('API_GET_warehouse_lists','get|warehouse/lists');
//@title 获取本地仓
define('API_GET_warehouse_local','get|warehouse/local');
//@title 获取第三方仓库
define('API_GET_warehouse_third','get|warehouse/third');
//@title 获取中转仓
define('API_GET_warehouse_transit','get|warehouse/transit');
//@title 仓库列表(传类型)
define('API_GET_warehouse_info','get|warehouse/info');
//@title 仓库类型（产品预报）
define('API_GET_warehouse_thirdType','get|warehouse/third-type');
//@title 参数设置
define('API_POST_warehouse___id_config','post|warehouse/:id/config');
//@title 调拨仓库
define('API_GET_warehouse_allocationList','get|warehouse/allocation-list');
//@title fba仓库列表
define('API_GET_warehouse_fba','get|warehouse/fba');
//@title 仓库站点配置列表
define('API_GET_warehouse_systemList','get|warehouse/system-list');
//@title 新增仓库配置
define('API_GET_warehouse_addConfig','get|warehouse/add-config');
//@title 引用系统仓库配置
define('API_POST_warehouse___id_useConfig','post|warehouse/:id/use-config');
//@title 获取仓库系统配置
define('API_GET_warehouse___id_config','get|warehouse/:id/config');
//@title 删除仓库配置
define('API_DELETE_warehouse_deleteConfig','delete|warehouse/delete-config');
//@title 更新仓库参数配置
define('API_PUT_warehouse_updateConfig','put|warehouse/update-config');
//@title 获取仓库站点配置
define('API_GET_warehouse_config','get|warehouse/config');
//@title 修改备货周期
define('API_PUT_warehouse_stockingCycle','put|warehouse/stocking-cycle');
//@title 获取备货周期
define('API_GET_warehouse_stockingCycle','get|warehouse/stocking-cycle');

//控制器：app\warehouse\controller\Carrier
//@title 显示物流商列表
define('API_GET_carrier','get|carrier');
//@title 添加物流商信息
define('API_POST_carrier','post|carrier');
//@title 显示指定物流商资源
define('API_GET_carrier___id','get|carrier/:id');
//@title 更新物流商信息
define('API_PUT_carrier___id','put|carrier/:id');
//@title 停用/启用物流商
define('API_POST_carrier_status','post|carrier/status');
//@title 同步邮寄方式
define('API_POST_carrier_down_shipping','post|carrier/down/shipping');
//@title 获取API及controller的code类型
define('API_GET_carrier_indexCode','get|carrier/index-code');
//@title 获取平台物流信息
define('API_GET_carrierPlatform___platform___service','get|carrier-platform/:platform/:service');
//@title 获取Wish邮授权url
define('API_GET_carrier_wishpostUrl','get|carrier/wishpost-url');
//@title 获取wangji邮授权url
define('API_GET_carrier_wangjipostUrl','get|carrier/wangjipost-url');
//@title wangji授权
define('API_POST_carrier_wangjiAuthors','post|carrier/wangji-authors');
//@title 面单序列号
define('API_GET_carrier_sequenceNumber','get|carrier/sequence-number');
//@title wish授权
define('API_POST_carrier_wishAuthors','post|carrier/wish-authors');
//@title 获取ebay收货地址
define('API_POST_carrier_ebayAddress','post|carrier/ebay-address');
//@title 获取ebay交运偏好
define('API_POST_carrier_ebayPreference','post|carrier/ebay-preference');
//@title 获取ebay交运偏好
define('API_POST_carrier_ebayToken','post|carrier/ebay-token');
//@title 获取物流邮寄方式列表
define('API_GET_carrier___id_shipping','get|carrier/:id/shipping');
//@title 获取物流邮寄方式列表
define('API_GET_carrier_lists','get|carrier/lists');

//控制器：app\order\controller\ManualOrder
//@title 资源列表
define('API_GET_manualOrders','get|manual-orders');
//@title 获取编辑信息
define('API_GET_manualOrders___id_edit','get|manual-orders/:id/edit');
//@title 查看信息
define('API_GET_manualOrders___id','get|manual-orders/:id');
//@title 保存资源
define('API_POST_manualOrders','post|manual-orders');
//@title 获取销售日期
define('API_GET_manualOrders_date','get|manual-orders/date');
//@title 获取订单号
define('API_GET_manualOrders_number','get|manual-orders/number');
//@title 获取邮寄方式
define('API_GET_manualOrders_shipping','get|manual-orders/shipping');
//@title 导入手工订单
define('API_POST_manualOrders_import','post|manual-orders/import');
//@title 保存导入手工订单
define('API_POST_manualOrders_saveImport','post|manual-orders/save-import');
//@title 导出execl
define('API_POST_manualOrders_export','post|manual-orders/export');
//@title 获取包裹数据
define('API_GET_manualOrders_packageList','get|manual-orders/package-list');
//@title 批量创建补发单
define('API_POST_manualOrders_batchReplacement','post|manual-orders/batch-replacement');
//@title 获取买家信息
define('API_GET_manualOrders_buyerMessage','get|manual-orders/buyer-message');

//控制器：app\order\controller\AuditOrder
//@title 显示资源列表
define('API_GET_ordersAudit','get|orders-audit');
//@title 批量操作
define('API_POST_ordersAudit_batch___type','post|orders-audit/batch/:type');
//@title 获取平台账号
define('API_GET_ordersAudit_channelAccount','get|orders-audit/channelAccount');
//@title 获取未审核状态
define('API_GET_ordersAudit_status','get|orders-audit/status');
//@title 导出execl
define('API_POST_ordersAudit_export','post|orders-audit/export');
//@title 获取人工审核状态
define('API_GET_ordersAudit_manualReviewStatus','get|orders-audit/manual-review-status');
//@title 标记订单联系状态
define('API_POST_ordersAudit_markLinkStatus','post|orders-audit/mark-link-status');

//控制器：app\order\controller\InvoiceRecord
//@title 发票记录列表
define('API_GET_invoices','get|invoices');
//@title 查看发票记录
define('API_GET_invoices___id','get|invoices/:id');
//@title 删除/批量删除
define('API_POST_invoices_batch','post|invoices/batch');

//控制器：app\order\controller\InvoiceRule
//@title 发票规则列表
define('API_GET_invoiceRules','get|invoice-rules');
//@title 编辑
define('API_GET_invoiceRules___id_edit','get|invoice-rules/:id/edit');
//@title 查看信息
define('API_GET_invoiceRules___id','get|invoice-rules/:id');
//@title 保存
define('API_POST_invoiceRules','post|invoice-rules');
//@title 更新发票规则
define('API_PUT_invoiceRules___id','put|invoice-rules/:id');
//@title 删除
define('API_DELETE_invoiceRules___id','delete|invoice-rules/:id');
//@title 获取可选条件
define('API_GET_invoiceRules_items','get|invoice-rules/items');
//@title 排序
define('API_POST_invoiceRules_sort','post|invoice-rules/sort');
//@title 更改规则状态
define('API_POST_invoiceRules___id_status___value','post|invoice-rules/:id/status/:value');
//@title 获取信息
define('API_GET_invoiceRules___type_info','get|invoice-rules/:type/info');

//控制器：app\order\controller\InvoiceTemplate
//@title 模板列表
define('API_GET_invoiceTemplate','get|invoice-template');

//控制器：app\order\controller\Rule
//@title 显示资源列表
define('API_GET_rules','get|rules');
//@title 显示指定的资源
define('API_GET_rules___id','get|rules/:id');
//@title 编辑指定的资源
define('API_GET_rules___id_edit','get|rules/:id/edit');
//@title 保存的资源
define('API_POST_rules','post|rules');
//@title 保存更新的资源
define('API_PUT_rules___id','put|rules/:id');
//@title 删除指定资源
define('API_DELETE_rules___id','delete|rules/:id');
//@title 更改规则状态
define('API_POST_rules_status','post|rules/status');
//@title 获取资源
define('API_POST_rules_resources','post|rules/resources');
//@title 获取发货仓库信息
define('API_GET_rules_warehouse','get|rules/warehouse');
//@title 获取运输方式
define('API_GET_rules_shipping','get|rules/shipping');
//@title 获取订单自动处理方法
define('API_GET_rules_action','get|rules/action');
//@title 保存排序值
define('API_POST_rules_sort','post|rules/sort');
//@title 规则复制
define('API_POST_rules_copy','post|rules/copy');
//@title 规则日志
define('API_GET_rules___rule_id_log','get|rules/:rule_id/log');
//@title 获取默认规则信息
define('API_GET_rules_default','get|rules/default');
//@title 批量修改运输方式
define('API_POST_rules_batch_shipping','post|rules/batch/shipping');

//控制器：app\order\controller\Wish
//@title 显示资源列表
define('API_GET_wishOrders','get|wish-orders');
//@title 显示指定的资源
define('API_GET_wishOrders___id','get|wish-orders/:id');
//@title 获取状态列表
define('API_GET_wishOrders_status','get|wish-orders/status');
//@title 导出execl
define('API_POST_wishOrders_export','post|wish-orders/export');
//@title 获取所有导出字段
define('API_GET_wishOrders_exportFields','get|wish-orders/export-fields');
//@title 更新平台订单信息
define('API_POST_wishOrders_online','post|wish-orders/online');
//@title 快速核查平台订单
define('API_POST_wishOrders_check','post|wish-orders/check');
//@title wish放款模板下载
define('API_GET_wishOrders_exportTransferTemplate','get|wish-orders/export-transfer-template');
//@title wish导入放款表单
define('API_POST_wishOrders_importTransfer','post|wish-orders/import-transfer');
//@title wish导入财务数据
define('API_POST_wishOrder_importSettle','post|wish-order/import-settle');
//@title 快速抓取订单
define('API_POST_wishOrders_pullOrder','post|wish-orders/pull-order');

//控制器：app\order\controller\RuleItem
//@title 显示资源列表
define('API_GET_ruleItems','get|rule-items');

//控制器：app\order\controller\DeclareRule
//@title 列表
define('API_GET_declareRules','get|declare-rules');
//@title 读取规则编辑信息
define('API_GET_declareRules___id_edit','get|declare-rules/:id/edit');
//@title 读取规则信息
define('API_GET_declareRules___id','get|declare-rules/:id');
//@title 新增
define('API_POST_declareRules','post|declare-rules');
//@title 更新
define('API_PUT_declareRules___id','put|declare-rules/:id');
//@title 删除
define('API_DELETE_declareRules___id','delete|declare-rules/:id');
//@title 更改规则状态
define('API_POST_declareRules___id_status___value','post|declare-rules/:id/status/:value');
//@title 获取可选条件
define('API_GET_declareRules_items','get|declare-rules/items');
//@title 获取默认申报设置
define('API_GET_declareRules_defaults','get|declare-rules/defaults');
//@title 获取资源
define('API_POST_declareRules_resources','post|declare-rules/resources');
//@title 保存排序值
define('API_POST_declareRules_sort','post|declare-rules/sort');
//@title 保存默认申报设置
define('API_POST_declareRules_keep','post|declare-rules/keep');
//@title 默认设置的信息
define('API_GET_declareRules_info','get|declare-rules/info');

//控制器：app\order\controller\DownTest
//@title 查看
define('API_GET_downDatas___id','get|down-datas/:id');

//控制器：app\purchase\controller\Supplier
//@title 供应商列表
define('API_GET_supplier','get|supplier');
//@title 保存资源
define('API_POST_supplier','post|supplier');
//@title 查看资源
define('API_GET_supplier___id','get|supplier/:id');
//@title 供应商停用测试
define('API_PUT_supplier_testSupplierDisuse','put|supplier/test-supplier-disuse');
//@title 显示编辑资源表单页.
define('API_GET_supplier___id_edit','get|supplier/:id/edit');
//@title 供应商更新
define('API_PUT_supplier___id','put|supplier/:id');
//@title 删除指定资源
define('API_DELETE_supplier___id','delete|supplier/:id');
//@title 获取信息
define('API_GET_supplier___type_info','get|supplier/:type/info');
//@title 审核
define('API_POST_supplier_status','post|supplier/status');
//@title 设置默认供应商
define('API_POST_supplier_setDefault','post|supplier/setDefault');
//@title 根据供应商ID列表返回供应商列表
define('API_GET_supplier_getSuppliersInfo','get|supplier/getSuppliersInfo');
//@title 按条件导出供应商
define('API_POST_supplier_exportSupplierByConditions','post|supplier/exportSupplierByConditions');
//@title 获取导出供应商所有字段
define('API_GET_supplier_exportFields','get|supplier/export-fields');
//@title 导出供应商
define('API_POST_supplier_export','post|supplier/export');
//@title 导出供应商至富友
define('API_POST_supplier_exportFuiou','post|supplier/export-fuiou');
//@title 获取各个仓库最低报价的交期
define('API_GET_supplier_delivery','get|supplier/delivery');
//@title 通过Excel导入供应商信息
define('API_POST_supplier_import','post|supplier/import');
//@title 获取虚拟供应商
define('API_GET_supplier_virtualSupplier','get|supplier/virtual-supplier');
//@title 获取供应商日志
define('API_GET_supplier___id_log','get|supplier/:id/log');
//@title 下载图片
define('API_GET_supplier_downloadImage','get|supplier/download-image');
//@title 修改采购员
define('API_PUT_supplier_changePurchaser','put|supplier/change-purchaser');
//@title 获取退货天数
define('API_GET_supplier_getReturnGoodsData','get|supplier/get-return-goods-data');
//@title 获取是否贴标、套牌
define('API_GET_supplier_getLabelDeck','get|supplier/get-label-deck');
//@title 获取对应供应商的采购记录的金额
define('API_GET_supplier_getSupplierPurchase','get|supplier/get-supplier-purchase');
//@title 获取供应链部门ID
define('API_GET_supplier_getSupplyChainDepartment-Id','get|supplier/get-supply-chain-department-Id');
//@title 修改自动生成付款申请单
define('API_PUT_supplier_autoPaymentRequest','put|supplier/auto-payment-request');
//@title 批量修改供应商结算方式
define('API_PUT_supplier_changeBalanceType','put|supplier/change-balance-type');
//@title 供应商停用
define('API_PUT_supplier_disable','put|supplier/disable');

//控制器：app\purchase\controller\SupplierOffer
//@title 显示资源列表
define('API_GET_supplierOffer','get|supplier-offer');
//@title 保存报价
define('API_POST_supplierOffer','post|supplier-offer');
//@title 显示编辑资源表单页
define('API_GET_supplierOffer___id_edit','get|supplier-offer/:id/edit');
//@title 审核报价单
define('API_POST_supplierOffer_status','post|supplier-offer/status');
//@title 获取供应商信息
define('API_GET_supplierOffer_supplier','get|supplier-offer/supplier');
//@title 获取货币信息
define('API_GET_supplierOffer_currency','get|supplier-offer/currency');
//@title 获取仓库信息
define('API_GET_supplierOffer_warehouse','get|supplier-offer/warehouse');
//@title 获取品牌信息
define('API_GET_supplierOffer_brand','get|supplier-offer/brand');
//@title 历史报价记录  查一个sku 的所有报价
define('API_GET_supplierOffer_history','get|supplier-offer/history');
//@title 当前报价
define('API_GET_supplierOffer_current','get|supplier-offer/current');
//@title 获取主产品SKU
define('API_GET_supplierOffer_getGoodsSkus','get|supplier-offer/getGoodsSkus');
//@title 获取供应商SKU列表
define('API_GET_supplierOffer_getSupplierSkus','get|supplier-offer/getSupplierSkus');
//@title 获取默认供应商及其报价
define('API_GET_supplierOffer_getDefaultSupplierPriceBySku','get|supplier-offer/getDefaultSupplierPriceBySku');
//@title 导入供应商报价
define('API_POST_supplierOffer_import','post|supplier-offer/import');
//@title 导出商品转成joom格式
define('API_GET_supplierOffer_export','get|supplier-offer/export');
//@title 导出全部
define('API_GET_supplierOffer_exportAll','get|supplier-offer/export-all');

//控制器：app\purchase\controller\SafeDelivery
//@title 安全交期列表
define('API_GET_safe','get|safe');
//@title 设置安全交期
define('API_POST_safe_changeDelivery','post|safe/changeDelivery');
//@title 保存
define('API_POST_safe_keep','post|safe/keep');
//@title 批量导入安全期数据
define('API_POST_safe_import','post|safe/import');
//@title 获取导出所有字段
define('API_GET_safe_exportFields','get|safe/export-fields');
//@title 导出安全交期
define('API_POST_safe_export','post|safe/export');
//@title 获取安全交期
define('API_GET_safe_getDeliveryDays','get|safe/getDeliveryDays');

//控制器：app\purchase\controller\PurchaseRule
//@title 显示资源列表
define('API_GET_purchaseRules','get|purchase-rules');
//@title 查看资源
define('API_GET_purchaseRules___id','get|purchase-rules/:id');
//@title 显示编辑资源表单页
define('API_GET_purchaseRules___id_edit','get|purchase-rules/:id/edit');
//@title 保存更新的资源
define('API_PUT_purchaseRules___id','put|purchase-rules/:id');
//@title 保存资源
define('API_POST_purchaseRules','post|purchase-rules');
//@title 删除指定资源
define('API_DELETE_purchaseRules___id','delete|purchase-rules/:id');
//@title 更改规则状态
define('API_POST_purchaseRules___id_status___value','post|purchase-rules/:id/status/:value');
//@title 获取资源
define('API_POST_purchaseRules_resources','post|purchase-rules/resources');
//@title 保存排序值
define('API_POST_purchaseRules_sort','post|purchase-rules/sort');

//控制器：app\purchase\controller\PurchaseRuleItem
//@title 显示资源列表
define('API_GET_purchaseRulesItems','get|purchase-rules-items');

//控制器：app\index\controller\User
//@title 显示资源列表
define('API_GET_user','get|user');
//@title 添加用户
define('API_POST_user','post|user');
//@title 查看用户
define('API_GET_user___id','get|user/:id');
//@title 查看用户
define('API_GET_user___id_edit','get|user/:id/edit');
//@title 更新用户
define('API_PUT_user___id','put|user/:id');
//@title 删除用户
define('API_DELETE_user___id','delete|user/:id');
//@title 获取所有部门和角色
define('API_GET_user_departmentAndRole','get|user/departmentAndRole');
//@title 停用，启用账号
define('API_GET_user_status','get|user/status');
//@title 批量禁用
define('API_POST_user_batch','post|user/batch');
//@title 修改密码
define('API_POST_user_updatePassword','post|user/updatePassword');
//@title 重置密码
define('API_POST_user___id_resetPassword','post|user/:id/reset-password');
//@title 获取角色下的成员
define('API_GET_user_member','get|user/member');
//@title 获取员工的信息
define('API_GET_user___type_staffs','get|user/:type/staffs');
//@title 获取领导
define('API_GET_user___type___work_leaders','get|user/:type/:work/leaders');
//@title 获取用户filter过滤器列表
define('API_GET_user_getFilters','get|user/getFilters');
//@title 验证旧密码
define('API_POST_user_checkPassword','post|user/check-password');
//@title 模拟登陆
define('API_POST_user_simulationOn','post|user/simulation-on');
//@title 更新当前用户token
define('API_POST_user_updateToken','post|user/update-token');
//@title 获取用户的部门信息
define('API_GET_user___id_getDepartment','get|user/:id/get-department');
//@title 获取用户日志
define('API_GET_user___id_logs','get|user/:id/logs');
//@title 获取登录用户的信息
define('API_GET_user_loginUserPosition','get|user/login-user-position');

//控制器：app\index\controller\DownloadFile
//@title 下载导出的文件
define('API_GET_downloadFile_downExportFile','get|downloadFile/downExportFile');
//@title 下载打印机
define('API_GET_printer','get|printer');
//@title 下载发票pdf文件
define('API_GET_downloadFile_downPdfFile','get|downloadFile/downPdfFile');

//控制器：app\index\controller\Department
//@title 部门列表
define('API_GET_department','get|department');
//@title 部门管理添加
define('API_POST_department','post|department');
//@title 部门管理获取
define('API_GET_department___id','get|department/:id');
//@title 部门管理编辑
define('API_GET_department___id_edit','get|department/:id/edit');
//@title 保存更新的资源
define('API_PUT_department___id','put|department/:id');
//@title 删除
define('API_DELETE_department___id','delete|department/:id');
//@title 停用，启用账号
define('API_GET_department_changeStatus','get|department/changeStatus');
//@title 获取所有部门
define('API_GET_department_getDepartment','get|department/getDepartment');
//@title 获取公司信息
define('API_GET_company','get|company');
//@title 获取用户
define('API_GET_department_getUser','get|department/getUser');
//@title 保存调序
define('API_POST_department_sort','post|department/sort');
//@title 部门类型
define('API_GET_department_type','get|department/type');
//@title 获取部门修改日志
define('API_GET_department___id_logs','get|department/:id/logs');
//@title 获取对应渠道的销售
define('API_GET_department___id_departmentUsers','get|department/:id/department-users');

//控制器：app\index\controller\Account
//@title 读取账号信息
define('API_GET_channels_channels___channel_accounts','get|channels/channels/:channel/accounts');

//控制器：app\index\controller\Config
//@title 显示资源列表
define('API_GET_config','get|config');
//@title 添加
define('API_POST_config','post|config');
//@title 显示指定的资源
define('API_GET_config___id','get|config/:id');
//@title 站点配置
define('API_GET_config_site','get|config/site');
//@title 显示编辑资源表单页.
define('API_GET_config___id_edit','get|config/:id/edit');
//@title 保存更新的资源
define('API_PUT_config___id','put|config/:id');
//@title 保存更新的资源
define('API_PUT_config_paramsConfig___id','put|config/paramsConfig/:id');
//@title 删除
define('API_DELETE_config___id','delete|config/:id');
//@title 获取分组
define('API_GET_config_groups','get|config/groups');
//@title 停用，启用
define('API_GET_config_status','get|config/status');
//@title 排序
define('API_POST_config_sort','post|config/sort');
//@title 数据类型
define('API_GET_config_type','get|config/type');

//控制器：app\index\controller\Express
//@title 读取国内快递信息
define('API_GET_express','get|express');

//控制器：app\system\controller\ConfigParams
//@title 新的系统配置列表
define('API_GET_systemConfig','get|system-config');
//@title 新的系统配置添加
define('API_POST_systemConfig','post|system-config');
//@title 添加分组
define('API_POST_systemConfig_group','post|system-config/group');
//@title 添加配置
define('API_POST_systemConfig_param','post|system-config/param');
//@title 修改分组
define('API_PUT_systemConfig_group','put|system-config/group');
//@title 修改配置
define('API_PUT_systemConfig_param','put|system-config/param');
//@title 删除分组
define('API_DELETE_systemConfig_group___id','delete|system-config/group/:id');
//@title 删除配置
define('API_DELETE_systemConfig_param___id','delete|system-config/param:/id');

//控制器：app\index\controller\DeveloperTeam
//@title 分组列表
define('API_GET_developers','get|developers');
//@title 读取
define('API_GET_developers___id','get|developers/:id');
//@title 获取编辑信息
define('API_GET_developers___id_edit','get|developers/:id/edit');
//@title 保存
define('API_POST_developers','post|developers');
//@title 更新
define('API_PUT_developers___id','put|developers/:id');
//@title 删除
define('API_DELETE_developers___id','delete|developers/:id');
//@title 批量删除
define('API_POST_developers_batch___type','post|developers/batch/:type');
//@title 获取分类信息
define('API_GET_developers_categories','get|developers/categories');

//控制器：app\index\controller\WishAccount
//@title 显示资源列表
define('API_GET_wishAccount','get|wish-account');
//@title 保存新建的资源
define('API_POST_wishAccount','post|wish-account');
//@title 显示指定的资源
define('API_GET_wishAccount___id','get|wish-account/:id');
//@title 显示编辑资源表单页.
define('API_GET_wishAccount___id_edit','get|wish-account/:id/edit');
//@title 保存更新的资源
define('API_PUT_wishAccount___id','put|wish-account/:id');
//@title 停用，启用账号
define('API_POST_wishAccount_states','post|wish-account/states');
//@title 获取授权码
define('API_POST_wishAccount_authorCode','post|wish-account/authorCode');
//@title 查询wish账号
define('API_GET_wishAccount_query','get|wish-account/query');
//@title 获取Token
define('API_POST_wishAccount_token','post|wish-account/token');
//@title 获取Token
define('API_GET_wishAccount_refresh_token___id','get|wish-account/refresh_token/:id');
//@title 授权页面
define('API_POST_wishAccount_authorization','post|wish-account/authorization');
//@title wish 批量开启
define('API_POST_wishAccount_batchSet','post|wish-account/batch-set');

//控制器：app\index\controller\Job
//@title 部门代码列表
define('API_GET_job','get|job');

//控制器：app\index\controller\MemberShip
//@title 成员列表
define('API_GET_memberShip','get|member-ship');
//@title 查看成员账号绑定信息
define('API_GET_memberShip___id','get|member-ship/:id');
//@title 获取编辑成员信息
define('API_GET_memberShip___id_edit','get|member-ship/:id/edit');
//@title 新增成员
define('API_POST_memberShip','post|member-ship');
//@title 更新成员
define('API_PUT_memberShip___id','put|member-ship/:id');
//@title 删除
define('API_DELETE_memberShip___id','delete|member-ship/:id');
//@title 批量删除
define('API_POST_memberShip_batch___type','post|member-ship/batch/:type');
//@title 查找成员关系
define('API_GET_memberShip_memberInfo','get|member-ship/memberInfo');
//@title 获取渠道 销售员-客服信息
define('API_GET_memberShip___type_member','get|member-ship/:type/member');
//@title 刊登获取 销售员-客服信息
define('API_GET_memberShip___channel_id___type_publish','get|member-ship/:channel_id/:type/publish');
//@title 全部导出
define('API_GET_memberShip_download','get|member-ship/download');
//@title 日志
define('API_GET_memberShip_log','get|member-ship/log');
//@title 平台账号成员列表
define('API_GET_memberShip_channelUserAccount','get|member-ship/channel-user-account');
//@title 添加平台账号成员
define('API_POST_memberShip_addAccount','post|member-ship/add-account');

//控制器：app\index\controller\Login
//@title 显示资源列表
define('API_GET_login','get|login');
//@title 登录
define('API_POST_login','post|login');
//@title 退出
define('API_POST_login_quit','post|login/quit');
//@title 权限
define('API_GET_login_permission','get|login/permission');
//@title 获取登录信息
define('API_GET_login_info','get|login/info');
//@title 获取websocket token
define('API_GET_login_wsToken','get|login/ws-token');
//@title 获取验证码
define('API_GET_login_code','get|login/code');

//控制器：app\goods\controller\Category
//@title 产品分类列表
define('API_GET_categories','get|categories');
//@title 分类设置采购员列表
define('API_GET_categories_purchaser','get|categories/purchaser');
//@title 分类设置采购员保存
define('API_PUT_categories___id_purchaserSave','put|categories/:id/purchaser-save');
//@title 保存产品分类
define('API_POST_categories','post|categories');
//@title 查看产品分类
define('API_GET_categories___id','get|categories/:id');
//@title 编辑产品分类
define('API_GET_categories___id_edit','get|categories/:id/edit');
//@title 更新产品分类
define('API_PUT_categories___id','put|categories/:id');
//@title 获取日志列表
define('API_GET_categories___id_logs','get|categories/:id/logs');
//@title 删除产品分类
define('API_DELETE_categories___id','delete|categories/:id');
//@title 删除缓存
define('API_GET_categories_cache','get|categories/cache');
//@title 修改产品分类排序
define('API_PUT_categories_sorts','put|categories/sorts');
//@title 分类列表
define('API_GET_categories_lists','get|categories/lists');

//控制器：app\goods\controller\GoodsSkuMap
//@title 显示资源列表
define('API_GET_skuMap','get|sku-map');
//@title 保存新建的资源
define('API_POST_skuMap','post|sku-map');
//@title 显示指定的资源
define('API_GET_skuMap___id','get|sku-map/:id');
//@title 显示指定的资源
define('API_GET_skuMap___id_edit','get|sku-map/:id/edit');
//@title 保存更新的资源
define('API_PUT_skuMap___id','put|sku-map/:id');
//@title 删除指定资源
define('API_DELETE_skuMap___id','delete|sku-map/:id');
//@title 批量删除
define('API_POST_skuMap_batch','post|sku-map/batch');
//@title 获取平台信息
define('API_GET_skuMap_channel','get|sku-map/channel');
//@title 获取账号信息
define('API_GET_skuMap_account','get|sku-map/account');
//@title 获取本地sku信息
define('API_GET_skuMap_skuInfo','get|sku-map/skuInfo');
//@title 获取员工信息
define('API_GET_skuMap_employee','get|sku-map/employee');
//@title 搜索sku
define('API_GET_skuMap_query','get|sku-map/query');
//@title 查看是否已关联
define('API_GET_skuMap_map','get|sku-map/map');
//@title 导入商品映射信息
define('API_POST_skuMap_import','post|sku-map/import');
//@title 产品映射管理导出
define('API_POST_skuMap_export','post|sku-map/export');
//@title 批量设置虚拟仓发货
define('API_PUT_skuMap_batch_virtual','put|sku-map/batch/virtual');

//控制器：app\customerservice\controller\OrderSale
//@title 首页列表
define('API_GET_orderSales','get|order-sales');
//@title 获取编辑信息
define('API_GET_orderSales___id_edit','get|order-sales/:id/edit');
//@title 查看售后信息
define('API_GET_orderSales___id','get|order-sales/:id');
//@title 更新售后信息
define('API_PUT_orderSales___id','put|order-sales/:id');
//@title 批量提交
define('API_POST_orderSales_batchUpdate','post|order-sales/batch-update');
//@title 新建售后信息
define('API_POST_orderSales','post|order-sales');
//@title 删除售后
define('API_DELETE_orderSales___id','delete|order-sales/:id');
//@title 获取状态
define('API_GET_orderSales___type_info','get|order-sales/:type/info');
//@title 获取渠道信息
define('API_GET_orderSales_channels','get|order-sales/channels');
//@title 审批通过
define('API_POST_orderSales_adopt_status','post|order-sales/adopt/status');
//@title 退回修改
define('API_POST_orderSales_retreat_status','post|order-sales/retreat/status');
//@title 退款标记为完成
define('API_POST_orderSales_complete_status','post|order-sales/complete/status');
//@title 退款重新执行
define('API_POST_orderSales_again_status','post|order-sales/again/status');
//@title 提交审批
define('API_POST_orderSales_submit','post|order-sales/submit');
//@title 查找订单
define('API_GET_orderSales_find','get|order-sales/find');
//@title execl字段信息
define('API_GET_orderSales_exportTitle','get|order-sales/export-title');
//@title 导出execl
define('API_POST_orderSales_export','post|order-sales/export');
//@title 批量审核
define('API_POST_orderSales_batchAdopt','post|order-sales/batch-adopt');
//@title 批量退回修改
define('API_POST_orderSales_batchRetreat','post|order-sales/batch-retreat');

//控制器：app\customerservice\controller\SaleReason
//@title 列表
define('API_GET_saleReasons','get|sale-reasons');
//@title 售后原因添加
define('API_POST_saleReasons','post|sale-reasons');
//@title 售后原因删除
define('API_DELETE_saleReasons___id','delete|sale-reasons/:id');

//控制器：app\api\controller\Get
//@title 默认访问页面
define('API_GET_get','get|get');

//控制器：app\api\controller\Post
//@title 默认访问页面
define('API_POST_post','post|post');

//控制器：app\index\controller\Node
//@title 服务端节点列表
define('API_GET_node','get|node');
//@title 获取节点页面信息
define('API_GET_node_pageNode','get|node/pageNode');
//@title 忽略权限的节点列表
define('API_GET_node_ignoreVists','get|node/ignore-vists');
//@title 设置节点页面信息
define('API_PUT_node_pageNode','put|node/pageNode');
//@title 获取节点过虑器列表
define('API_GET_node_filterNode','get|node/filterNode');
//@title 设置节点过虑器列表
define('API_PUT_node_filterNode','put|node/filterNode');
//@title 获取节点信息
define('API_GET_node_config___nodeid','get|node/config/:nodeid');
//@title 停用，启用
define('API_GET_node_changeStatus','get|node/changeStatus');
//@title 排序
define('API_POST_node_sort','post|node/sort');

//控制器：app\customerservice\controller\AliexpressEvaluate
//@title 评价列表
define('API_GET_aliEvaluate','get|ali-evaluate');
//@title 评价明细
define('API_GET_aliEvaluate___id','get|ali-evaluate/:id');
//@title 回评
define('API_POST_aliEvaluate_evaluate','post|ali-evaluate/evaluate');
//@title 批量回评
define('API_POST_aliEvaluate_batchEvaluate','post|ali-evaluate/batchEvaluate');
//@title 追加评论
define('API_POST_aliEvaluate_append','post|ali-evaluate/append');
//@title 获取评价模板内容
define('API_GET_aliEvaluate_tmpContent','get|ali-evaluate/tmpContent');
//@title 获取各状态数量
define('API_GET_aliEvaluate_statistics','get|ali-evaluate/statistics');
//@title 系统订单评价
define('API_POST_aliEvaluate_evaluateOrder','post|ali-evaluate/evaluate-order');
//@title 获取评价分类标签
define('API_GET_aliEvaluate_statisticsScore','get|ali-evaluate/statistics-score');

//控制器：app\customerservice\controller\AliexpressIssue
//@title 纠纷列表
define('API_GET_aliIssue','get|ali-issue');
//@title 查询纠纷详细
define('API_GET_aliIssue___id','get|ali-issue/:id');
//@title 上传纠纷图片
define('API_POST_aliIssue_uploadImages','post|ali-issue/upload-images');
//@title 同意普通纠纷方案
define('API_POST_aliIssue_agreeSolution','post|ali-issue/agree-solution');
//@title 新增(拒绝某个买家方案)
define('API_POST_aliIssue_addSolution','post|ali-issue/add-solution');
//@title 修改方案
define('API_POST_aliIssue_editSolution','post|ali-issue/edit-solution');
//@title 获取标签统计数量
define('API_GET_aliIssue_getLabel','get|ali-issue/get-label');
//@title 获取速卖通卖家退货地址
define('API_GET_aliIssue_getRefundAddress___account_id','get|ali-issue/get-refund-address/:account_id');
//@title 获取纠纷历史
define('API_GET_aliIssue_getProcess___issue_id','get|ali-issue/get-process/:issue_id');
//@title 立即抓取
define('API_POST_aliIssue_sync','post|ali-issue/sync');

//控制器：app\customerservice\controller\AliexpressMsg
//@title 收件箱列表
define('API_GET_aliexpressMsg','get|aliexpress-msg');
//@title 站内信明细
define('API_GET_aliexpressMsg___id','get|aliexpress-msg/:id');
//@title 获取Aliexpress标签
define('API_GET_aliexpressMsg_rank','get|aliexpress-msg/rank');
//@title 获取消息明细
define('API_GET_aliexpressMsg___id_detail','get|aliexpress-msg/:id/detail');
//@title 获取所有标签下站内信数量
define('API_GET_aliexpressMsg_rankStatistics','get|aliexpress-msg/rankStatistics');
//@title 获取客服对应的账号
define('API_GET_aliexpressMsg_account','get|aliexpress-msg/account');
//@title 获取站内信处理优先级
define('API_GET_aliexpressMsg_level','get|aliexpress-msg/level');
//@title 获取站内信各优先级下数量
define('API_GET_aliexpressMsg_levelStatistics','get|aliexpress-msg/levelStatistics');
//@title 修改优先级
define('API_POST_aliexpressMsg___id_changeLevel___level','post|aliexpress-msg/:id/changeLevel/:level');
//@title 获取相关订单信息
define('API_GET_aliexpressMsg___id_orders','get|aliexpress-msg/:id/orders');
//@title 回复消息
define('API_POST_aliexpressMsg_replay','post|aliexpress-msg/replay');
//@title 发送新站内信消息
define('API_POST_aliexpressMsg_addMsg','post|aliexpress-msg/add-msg');
//@title 打标签(已改为奇门接口)
define('API_POST_aliexpressMsg___id_changeRank___rank','post|aliexpress-msg/:id/changeRank/:rank');
//@title 处理消息(已改为奇门接口)
define('API_POST_aliexpressMsg_batchProcessed','post|aliexpress-msg/batchProcessed');
//@title 标记消息已读(已改为奇门接口)
define('API_POST_aliexpressMsg___id_readMsg','post|aliexpress-msg/:id/readMsg');
//@title 获取回复模板内容
define('API_GET_aliexpressMsg_tmpContent','get|aliexpress-msg/tmpContent');
//@title 获取客服
define('API_GET_aliexpressMsg_customer','get|aliexpress-msg/customer');
//@title 联系订单买家
define('API_POST_aliexpressMsg_','post|aliexpress-msg/');
//@title 获取联系买家模板内容
define('API_GET_aliexpressMsg_tempDetailOrder','get|aliexpress-msg/temp-detail-order');
//@title 展开更多消息
define('API_GET_aliexpressMsg_moreMsg','get|aliexpress-msg/more-msg');
//@title 测试同步
define('API_GET_aliexpressMsg_testSyn','get|aliexpress-msg/testSyn');
//@title 同步站内信
define('API_POST_aliexpressMsg_sync','post|aliexpress-msg/sync');
//@title 根据平台订单号获取站内信消息
define('API_GET_aliexpressMsg_order___order_no','get|aliexpress-msg/order/:order_no');

//控制器：app\customerservice\controller\AliexpressOutbox
//@title 发件箱列表
define('API_GET_aliOutbox','get|ali-outbox');
//@title 消息明细
define('API_GET_aliOutbox___id','get|ali-outbox/:id');
//@title 重发消息
define('API_POST_aliOutbox___id_resend','post|ali-outbox/:id/resend');
//@title 速卖通发件箱删除
define('API_DELETE_aliOutbox___id','delete|ali-outbox/:id');

//控制器：app\order\controller\Aliexpress
//@title 速卖通订单列表
define('API_GET_aliexpressOrder','get|aliexpress-order');
//@title 订单详细
define('API_GET_aliexpressOrder___id','get|aliexpress-order/:id');
//@title 获取所有订单状态
define('API_GET_aliexpressOrder_status','get|aliexpress-order/status');
//@title 延迟收货时间
define('API_PUT_aliexpressOrder_times','put|aliexpress-order/times');
//@title 速卖通订单导入
define('API_POST_aliexpressOrder_import','post|aliexpress-order/import');
//@title Aliexpress查找订单存在
define('API_POST_aliexpressOrder_exists','post|aliexpress-order/exists');
//@title Aliexpress同步平台订单；
define('API_POST_aliexpressOrder_sysc','post|aliexpress-order/sysc');
//@title 导出
define('API_POST_aliexpressOrder_export','post|aliexpress-order/export');
//@title 速卖通导出字段
define('API_GET_aliexpressOrder_exportFields','get|aliexpress-order/export-fields');
//@title 推送ali order 至系统订单
define('API_POST_aliexpressOrder_pushAliorder','post|aliexpress-order/push-aliorder');
//@title 拉取速卖通订单
define('API_POST_aliexpressOrder_syscAliorder','post|aliexpress-order/sysc-aliorder');
//@title aliexpress导入交易记录数据
define('API_POST_aliexpressOrder_importSettle','post|aliexpress-order/import-settle');

//控制器：app\index\controller\Currency
//@title 查看币种列表
define('API_GET_currency','get|currency');
//@title 查看汇率历史记录
define('API_GET_currency___id','get|currency/:id');
//@title 创建币种
define('API_POST_currency','post|currency');
//@title 编辑币种
define('API_GET_currency___id_edit','get|currency/:id/edit');
//@title 更新币种汇率
define('API_PUT_currency___id','put|currency/:id');
//@title 更新官方汇率
define('API_POST_currency_updateOfficialRate','post|currency/updateOfficialRate');
//@title 查询官方汇率(新增币种)
define('API_POST_currency_selectOfficialRate','post|currency/selectOfficialRate');
//@title 修改币种排序
define('API_PUT_currency_sorts','put|currency/sorts');
//@title 币种字段
define('API_GET_currency_dictionary','get|currency/dictionary');

//控制器：app\order\controller\Amazon
//@title Amazon订单列表
define('API_GET_amazonOrders','get|amazon-orders');
//@title 查看amazon订单
define('API_GET_amazonOrders___id','get|amazon-orders/:id');
//@title 获取Amazon订单状态
define('API_GET_amazon_order_status','get|amazon/order_status');
//@title 获取指定类型单号的买家和归属平台账号的信息
define('API_GET_amazonOrders___order_number_type___order_number_buyerInfo','get|amazon-orders/:order_number_type/:order_number/buyer-info');
//@title Amazon查找订单存在
define('API_POST_amazonOrders_exists','post|amazon-orders/exists');
//@title amazon同步平台订单；
define('API_POST_amazonOrders_sysc','post|amazon-orders/sysc');
//@title amazon放款模板下载；
define('API_GET_amazonOrders_exportTransferTemplate','get|amazon-orders/export-transfer-template');
//@title amazon导入放款表单；
define('API_POST_amazonOrders_importTransfer','post|amazon-orders/import-transfer');
//@title 亚马逊订单报表导出
define('API_POST_amazonOrders_export','post|amazon-orders/export');
//@title 获取所有导出字段
define('API_GET_amazonOrders_exportFields','get|amazon-orders/export-fields');
//@title 拉取亚马逊订单
define('API_POST_amazonOrders_syscAmazonOrder','post|amazon-orders/sysc-amazon-order');
//@title 推送至系统订单
define('API_POST_amazonOrders_pushAmazonOrder','post|amazon-orders/push-amazon-order');

//控制器：app\customerservice\controller\ShopeeDispute
//@title 纠纷清单（取消订单、退款退货）
define('API_GET_shopeeDispute','get|shopee-dispute');
//@title 导出纠纷数据
define('API_POST_shopeeDispute_export','post|shopee-dispute/export');
//@title 订单取消分组统计数量
define('API_GET_shopeeDispute_cancel_groupCount','get|shopee-dispute/cancel/group-count');
//@title 订单退货分组统计数量
define('API_GET_shopeeDispute_return_groupCount','get|shopee-dispute/return/group-count');
//@title 刷新订单取消
define('API_POST_shopeeDispute_cancel_refresh','post|shopee-dispute/cancel/refresh');
//@title 刷新订单退货
define('API_POST_shopeeDispute_return_refresh','post|shopee-dispute/return/refresh');
//@title 订单取消申请商品详情
define('API_GET_shopeeDispute_cancel___ordersn','get|shopee-dispute/cancel/:ordersn');
//@title 订单取消日志详情
define('API_GET_shopeeDispute___ordersn_cancelLog','get|shopee-dispute/:ordersn/cancel-log');
//@title 订单退货申请详情
define('API_GET_shopeeDispute_return___returnsn','get|shopee-dispute/return/:returnsn');
//@title 订单退货申请纠纷
define('API_GET_shopeeDispute___returnsn_dispute','get|shopee-dispute/:returnsn/dispute');
//@title 订单退货申请日志
define('API_GET_shopeeDispute___returnsn_log','get|shopee-dispute/:returnsn/log');
//@title 关联售后单ID
define('API_PUT_shopeeDispute___returnsn_afterSale','put|shopee-dispute/:returnsn/after-sale');
//@title 卖方取消订单
define('API_PUT_shopeeDispute___ordersn_cancel','put|shopee-dispute/:ordersn/cancel');
//@title 接受买方取消订单
define('API_PUT_shopeeDispute___ordersn_accept','put|shopee-dispute/:ordersn/accept');
//@title 拒绝买方取消订单
define('API_PUT_shopeeDispute___ordersn_reject','put|shopee-dispute/:ordersn/reject');
//@title 卖方接受退货
define('API_PUT_shopeeDispute___returnsn_confirm','put|shopee-dispute/:returnsn/confirm');
//@title 卖方争议退货
define('API_POST_shopeeDispute___returnsn_dispute','post|shopee-dispute/:returnsn/dispute');

//控制器：app\customerservice\controller\EbayFeedback
//@title 评价列表
define('API_GET_ebayFeedback','get|ebay-feedback');
//@title 查看评价
define('API_GET_ebayFeedback___id','get|ebay-feedback/:id');
//@title 编辑评价
define('API_GET_ebayFeedback___id_edit','get|ebay-feedback/:id/edit');
//@title 评价/回评
define('API_POST_ebayFeedback_comment','post|ebay-feedback/comment');
//@title 批量评价
define('API_POST_ebayFeedback_batch_comment','post|ebay-feedback/batch/comment');
//@title 重新发送评价
define('API_POST_ebayFeedback_repeat','post|ebay-feedback/repeat');
//@title 追评
define('API_POST_ebayFeedback_respond','post|ebay-feedback/respond');
//@title 跟进
define('API_POST_ebayFeedback_sendMsg','post|ebay-feedback/sendMsg');
//@title 获取评价模板内容
define('API_GET_ebayFeedback_tplContent','get|ebay-feedback/tplContent');
//@title 更改评价状态
define('API_POST_ebayFeedback_status','post|ebay-feedback/status');
//@title 回复买家评价
define('API_POST_ebayFeedback_reply','post|ebay-feedback/reply');
//@title 统计回评状态-数量
define('API_GET_ebayFeedback_status','get|ebay-feedback/status');

//控制器：app\customerservice\controller\EbayDispute
//@title 纠纷列表
define('API_GET_ebayDispute','get|ebay-dispute');
//@title 查看纠纷
define('API_GET_ebayDispute___id','get|ebay-dispute/:id');
//@title 更新纠纷信息
define('API_PUT_ebayDispute___id','put|ebay-dispute/:id');
//@title 批量更新纠纷信息
define('API_PUT_ebayDispute_batchUpdate','put|ebay-dispute/batch-update');
//@title 获取纠纷类型列表
define('API_GET_ebayDispute_types','get|ebay-dispute/types');
//@title 纠纷状态列表
define('API_GET_ebayDispute_status','get|ebay-dispute/status');
//@title 获取搜索字段键值数组
define('API_GET_ebayDispute_search_fields','get|ebay-dispute/search/fields');
//@title 纠纷类型对应的ID描述值
define('API_GET_ebayDispute_typeIds','get|ebay-dispute/typeIds');
//@title 卖家处理‘取消订单’纠纷
define('API_POST_ebayDispute_operate_cancel','post|ebay-dispute/operate/cancel');
//@title 卖家处理‘升级’纠纷
define('API_POST_ebayDispute_operate_case','post|ebay-dispute/operate/case');
//@title 卖家处理‘未收到货’纠纷
define('API_POST_ebayDispute_operate_inquiry','post|ebay-dispute/operate/inquiry');
//@title 卖家处理‘退货退款’纠纷
define('API_POST_ebayDispute_operate_return','post|ebay-dispute/operate/return');
//@title 获取原因列表 - 下拉框
define('API_GET_ebayDispute_reasons','get|ebay-dispute/reasons');

//控制器：app\customerservice\controller\EbayMessage
//@title ebay收件箱列表
define('API_GET_ebayMessage','get|ebay-message');
//@title 发件箱列表
define('API_GET_ebayMessage_getMessageList_outbox','get|ebay-message/getMessageList/outbox');
//@title ebay来信
define('API_GET_ebayMessage_getMessageList','get|ebay-message/getMessageList');
//@title 查看站内信
define('API_GET_ebayMessage___id','get|ebay-message/:id');
//@title 加载更多站内信
define('API_GET_ebayMessage_list','get|ebay-message/list');
//@title 删除站内信
define('API_DELETE_ebayMessage___id','delete|ebay-message/:id');
//@title 获取订单列表
define('API_GET_ebayMessage_getOrderList','get|ebay-message/getOrderList');
//@title 获取客服对应的账号
define('API_GET_ebayMessage_account','get|ebay-message/account');
//@title 发送消息
define('API_POST_ebayMessage_send','post|ebay-message/send');
//@title 回复消息
define('API_POST_ebayMessage_replay','post|ebay-message/replay');
//@title 重新发送
define('API_POST_ebayMessage_resend','post|ebay-message/resend');
//@title 批量重新发送
define('API_POST_ebayMessage_resend_batch','post|ebay-message/resend/batch');
//@title 修改状态
define('API_POST_ebayMessage_status','post|ebay-message/status');
//@title ebay客服账号列表
define('API_GET_ebayMessage_getEbayCustomer','get|ebay-message/getEbayCustomer');
//@title 消息优先级消息统计
define('API_GET_ebayMessage_getLevelCount','get|ebay-message/getLevelCount');
//@title 优先级消息列表
define('API_GET_ebayMessage_level','get|ebay-message/level');
//@title 修改站内信优先级
define('API_POST_ebayMessage_updateMessageLevel','post|ebay-message/updateMessageLevel');
//@title 获取来往信息列表
define('API_GET_ebayMessage_getGroupDatas','get|ebay-message/getGroupDatas');
//@title 更换站内信客服id
define('API_POST_ebayMessage_changeCustomer','post|ebay-message/change-customer');
//@title 更新指定id的站内信标签
define('API_PUT_ebayMessage___id','put|ebay-message/:id');
//@title 站内信添加删除备注
define('API_POST_ebayMessage_remark','post|ebay-message/remark');
//@title 测试队列接收运行
define('API_POST_ebayMessage_queue','post|ebay-message/queue');
//@title 测试servers
define('API_POST_ebayMessage_server','post|ebay-message/server');

//控制器：app\customerservice\controller\MsgRule
//@title 自动发送规则列表
define('API_GET_msgRule','get|msg-rule');
//@title 新增
define('API_POST_msgRule','post|msg-rule');
//@title 编辑
define('API_GET_msgRule___id_edit','get|msg-rule/:id/edit');
//@title 更新
define('API_PUT_msgRule___id','put|msg-rule/:id');
//@title 删除
define('API_DELETE_msgRule___id','delete|msg-rule/:id');
//@title 更新状态（开启/停用）
define('API_POST_msgRule_batch_update','post|msg-rule/batch/update');
//@title  排序
define('API_POST_msgRule_changeSort','post|msg-rule/changeSort');
//@title  统计每个触发时间下面的规则条数
define('API_GET_msgRule_triggerStatistics','get|msg-rule/triggerStatistics');
//@title  触发规则条件列表
define('API_GET_msgRule_triggerRules','get|msg-rule/triggerRules');
//@title  发送邮规则条件列表
define('API_GET_msgRule_emailRules','get|msg-rule/emailRules');
//@title  平台列表
define('API_GET_msgRule_platform','get|msg-rule/platform');
//@title 匹配测试
define('API_POST_msgRule_triggerEventTest','post|msg-rule/triggerEventTest');
//@title 加入站内信/评价自动发送列队
define('API_POST_msgRule_msgReviewAutoSendQueueTest','post|msg-rule/msgReviewAutoSendQueueTest');
//@title 手动加入站内信队列
define('API_POST_msgRule_addSendMsg','post|msg-rule/addSendMsg');
//@title 自动发送规则列表条件
define('API_GET_where','get|where');
//@title 设置回复内容md5值(临时)
define('API_POST_msgRule_content_md5','post|msg-rule/content_md5');
//@title 设置去重字段only_key md5值（临时）
define('API_POST_msgRule_only_key_md5','post|msg-rule/only_key_md5');

//控制器：app\customerservice\controller\MsgRuleItem
//@title 自动发送规则匹配项列表
define('API_GET_msgRuleItems','get|msg-rule-items');

//控制器：app\customerservice\controller\MsgTemplate
//@title 列表
define('API_GET_msgTpl','get|msg-tpl');
//@title 查看
define('API_GET_msgTpl___id','get|msg-tpl/:id');
//@title 新增
define('API_POST_msgTpl','post|msg-tpl');
//@title 编辑
define('API_GET_msgTpl___id_edit','get|msg-tpl/:id/edit');
//@title 更新
define('API_PUT_msgTpl___id','put|msg-tpl/:id');
//@title 删除
define('API_DELETE_msgTpl___id','delete|msg-tpl/:id');
//@title 删除
define('API_POST_msgTpl_batch_delete','post|msg-tpl/batch/delete');
//@title 获取模板分类
define('API_GET_msgTpl_getTypes','get|msg-tpl/getTypes');
//@title 获取模板数据字段列表
define('API_GET_msgTpl_getFields','get|msg-tpl/getFields');
//@title 获取指定平台的所有模板列表
define('API_GET_msgTpl_getTemplates','get|msg-tpl/getTemplates');
//@title 获取所有平台的所有模板
define('API_GET_msgTpl_getAllTpls','get|msg-tpl/getAllTpls');
//@title 获取模板内容
define('API_GET_msgTpl_content','get|msg-tpl/content');

//控制器：app\customerservice\controller\MsgTemplateGroup
//@title 获取指定平台模板分组列表
define('API_GET_msgTplGroup','get|msg-tpl-group');
//@title 查看
define('API_GET_msgTplGroup___id','get|msg-tpl-group/:id');
//@title 新增
define('API_POST_msgTplGroup','post|msg-tpl-group');
//@title 编辑
define('API_GET_msgTplGroup___id_edit','get|msg-tpl-group/:id/edit');
//@title 更新
define('API_PUT_msgTplGroup___id','put|msg-tpl-group/:id');
//@title 删除
define('API_DELETE_msgTplGroup___id','delete|msg-tpl-group/:id');

//控制器：app\customerservice\controller\AmazonFeedback
//@title 亚马逊评价
define('API_GET_amazon_getFeedbacks','get|amazon/getFeedbacks');
//@title 中差评原因处理(提交中差评原因)
define('API_POST_amazon_submitFeedbackReason','post|amazon/submitFeedbackReason');
//@title 中差评原因处理情况()
define('API_POST_amazon_submitFeedbackDealingStatus','post|amazon/submitFeedbackDealingStatus');
//@title 客服列表
define('API_GET_amazon_getCustomerServiceOfficers','get|amazon/getCustomerServiceOfficers');

//控制器：app\goods\controller\Attribute
//@title 查看属性列表
define('API_GET_attributes','get|attributes');
//@title 保存属性
define('API_POST_attributes','post|attributes');
//@title 查看属性详情
define('API_GET_attributes___id','get|attributes/:id');
//@title 编辑属性
define('API_GET_attributes___id_edit','get|attributes/:id/edit');
//@title 更新属性
define('API_PUT_attributes___id','put|attributes/:id');
//@title 删除属性
define('API_DELETE_attributes___id','delete|attributes/:id');
//@title 属性字典
define('API_GET_attribute_dictionary','get|attribute/dictionary');
//@title 属性质检字典
define('API_GET_attribute_qc_dictionary___id','get|attribute/qc_dictionary/:id');
//@title 属性code
define('API_GET_attribute_code','get|attribute/code');
//@title 获取属性值根据属性Id
define('API_GET_attribute_getAttributeValue___id','get|attribute/getAttributeValue/:id');
//@title 修改属性排序
define('API_PUT_attribute_sorts','put|attribute/sorts');

//控制器：app\index\controller\AmazonAccount
//@title Amazon账号列表
define('API_GET_amazonAccount','get|amazon-account');
//@title 保存账号信息
define('API_POST_amazonAccount','post|amazon-account');
//@title 显示指定Amazon账号
define('API_GET_amazonAccount___id','get|amazon-account/:id');
//@title 编辑Amazon账号
define('API_GET_amazonAccount___id_edit','get|amazon-account/:id/edit');
//@title 更新Amazon账号
define('API_PUT_amazonAccount___id','put|amazon-account/:id');
//@title 批量设置amazon账号有效状态
define('API_PUT_amazonAccount_batchSetValid','put|amazon-account/batch-set-valid');
//@title 更新Amazon账号授权信息
define('API_PUT_amazonAccountToken___id','put|amazon-account-token/:id');
//@title amazon批量设置抓取参数；
define('API_POST_amazonAccount_set','post|amazon-account/set');
//@title 停用，启用账号
define('API_POST_amazonAccount_status','post|amazon-account/status');
//@title 获取Amazon站点
define('API_GET_amazon_site','get|amazon/site');
//@title 获取亚马逊开发者授权信息
define('API_GET_amazonAccount_getDeveloperAccount___site','get|amazon-account/get-developer-account/:site');
//@title 二维数组排序
define('API_GET_amazon_my_sort','get|amazon/my_sort');

//控制器：app\publish\controller\AmazonAttribute
//@title 属性匹配
define('API_POST_amazonAttribute_match','post|amazon-attribute/match');
//@title 导入XSD文件并解析入库
define('API_GET_amazonAttribute_import','get|amazon-attribute/import');
//@title 更新分类元素属站点
define('API_GET_amazonAttribute_elementSite','get|amazon-attribute/elementSite');
//@title 获取产品基础信息
define('API_GET_amazonAttribute_productBase','get|amazon-attribute/productBase');
//@title 亚马逊属性配置展示
define('API_GET_amazonAttribute_config','get|amazon-attribute/config');
//@title 获取XSD模板分类
define('API_GET_amazonAttribute_xsdCategory','get|amazon-attribute/xsd-category');
//@title 保存站点属性配置
define('API_POST_amazonSaveXsdAttribute','post|amazon-save-xsd-attribute');
//@title 获取XSD模板属性
define('API_POST_amazonXsdAttribute','post|amazon-xsd-attribute');
//@title 获取XSD模板分类树
define('API_GET_amazonXsdCategoryTree','get|amazon-xsd-category-tree');

//控制器：app\publish\controller\AmazonPublish
//@title amazon未刊登列表
define('API_GET_publish_amazon_unpublished','get|publish/amazon/unpublished');
//@title 未刊登侵权信息
define('API_GET_publish_amazon_goodsTortInfo___goods_id','get|publish/amazon/goods-tort-info/:goods_id');
//@title amazon开始刊登时获取模板
define('API_GET_publish_amazon_template','get|publish/amazon/template');
//@title amazon刊登获取分类/产品模板列表
define('API_GET_publish_amazon_templatelist','get|publish/amazon/templatelist');
//@title amazon刊登站点列表；
define('API_GET_publish_amazon_site','get|publish/amazon/site');
//@title amazon刊登用站点取帐号列表；
define('API_GET_publish_amazon_account','get|publish/amazon/account');
//@title amazon刊登详情获取刊登字段；
define('API_GET_publish_amazon_field','get|publish/amazon/field');
//@title amazon刊登详情保存；
define('API_POST_publish_amazon_detail','post|publish/amazon/detail');
//@title amazon刊登详情保存；
define('API_GET_publish_amazon_edit','get|publish/amazon/edit');
//@title amazon刊登记录更改为失败；
define('API_GET_publish_amazon___id_defeat','get|publish/amazon/:id/defeat');
//@title amazon刊登修复；
define('API_GET_publish_amazonTask___type___id___status','get|publish/amazon-task/:type/:id/:status');
//@title amazon刊登翻译；
define('API_POST_publish_amazon_translate','post|publish/amazon/translate');
//@title amazon获取UPC;
define('API_GET_publish_amazon___num_upc','get|publish/amazon/:num/upc');
//@title amazon编辑刊登完成后的内容;
define('API_GET_publish_amazon___id___type_reedit','get|publish/amazon/:id/:type/reedit');
//@title amazon编辑刊登异常导出;
define('API_GET_publish_amazon_errorExport','get|publish/amazon/error-export');
//@title amazonn添加UPC参数;
define('API_POST_publish_amazon_addUpcParams','post|publish/amazon/add-upc-params');
//@title amazonn批量复制;
define('API_POST_publish_amazon_batchCopy','post|publish/amazon/batch-copy');
//@title amazon批量跟卖;
define('API_POST_publish_amazon_batchHeelSale','post|publish/amazon/batch-heel-sale');
//@title amazon跟卖列表
define('API_GET_publish_amazon_heelSaleList','get|publish/amazon/heel-sale-list');
//@title amazon定时上下架添加规则
define('API_POST_publish_amazon_addUpLowerRule','post|publish/amazon/add-up-lower-rule');
//@title amazon定时上下架规则列表
define('API_GET_publish_amazon_upLowerRuleList','get|publish/amazon/up-lower-rule-list');
//@title 定时上架规则状态修改
define('API_GET_publish_amazon_upLowerRuleStatus','get|publish/amazon/up-lower-rule-status');
//@title 定时上架规则删除
define('API_POST_publish_amazon_upLowerRuleDelete','post|publish/amazon/up-lower-rule-delete');
//@title 定时上架规则详情
define('API_GET_publish_amazon_upLowerRuleDetail','get|publish/amazon/up-lower-rule-detail');
//@title amazon定时上架规则编辑
define('API_POST_publish_amazon_upLowerRuleEdit','post|publish/amazon/up-lower-rule-edit');
//@title 定时上下架开启
define('API_POST_publish_amazon_upLowerOpen','post|publish/amazon/up-lower-open');
//@title 关闭定时上下架
define('API_POST_publish_amazon_upLowerClose','post|publish/amazon/up-lower-close');
//@title 亚马逊跟卖投诉管理列表
define('API_GET_publish_amazon_heelSaleComplain','get|publish/amazon/heel-sale-complain');
//@title 处理跟卖投诉状态
define('API_POST_publish_amazon_complainStatus','post|publish/amazon/complain-status');
//@title 删除跟卖投诉
define('API_POST_publish_amazon_complainDelete','post|publish/amazon/complain-delete');
//@title 抓取asin跟卖
define('API_POST_publish_amazon_heelSaleGet','post|publish/amazon/heel-sale-get');
//@title ASIN审核
define('API_POST_publish_amazon_reviewAsin','post|publish/amazon/review-asin');
//@title 亚马逊批量跟卖修改信息查询
define('API_POST_publish_amazon_heelSaleInfo','post|publish/amazon/heel-sale-info');
//@title 亚马逊批量跟卖修改信息提交
define('API_POST_publish_amazon_heelSaleBatchEdit','post|publish/amazon/heel-sale-batch-edit');
//@title 亚马逊跟卖批量删除
define('API_POST_publish_amazon_heelSaleBathDel','post|publish/amazon/heel-sale-bath-del');

//控制器：app\publish\controller\AmazonPublishListing
//@title 获取仓库列表
define('API_GET_publish_amazonPublish_warehouses','get|publish/amazon-publish/warehouses');
//@title 获取站点列表
define('API_GET_publish_amazonPublish_sites','get|publish/amazon-publish/sites');
//@title 获取类目绑定的普通属性
define('API_GET_publish_amazonPublish_commonAttribute','get|publish/amazon-publish/common-attribute');
//@title 获取分类树
define('API_GET_publish_amazonPublish_category','get|publish/amazon-publish/category');
//@title 亚马逊分类搜索
define('API_GET_publish_amazonPublish_searchCategories','get|publish/amazon-publish/search-categories');
//@title 查询产品列表
define('API_GET_publish_amazonPublish_getListing','get|publish/amazon-publish/get-listing');
//@title 产品列表刊登状态刷新
define('API_GET_publish_amazonPublish_refresh_status','get|publish/amazon-publish/refresh_status');
//@title 获取一个产品的信息
define('API_GET_publish_amazonPublish_getOne','get|publish/amazon-publish/get-one');
//@title 删除或批量删除刊登记录
define('API_GET_publish_amazonPublish_deleteListing','get|publish/amazon-publish/delete-listing');
//@title 已更改价格
define('API_POST_publish_amazonPublish_adjustedPrice','post|publish/amazon-publish/adjusted-price');

//控制器：app\publish\controller\AmazonTask
//@title 上传产品信息
define('API_GET_publish_amazonTask_uploadProduct','get|publish/amazon-task/upload-product');
//@title 上传关系
define('API_GET_publish_amazonTask_uploadRelation','get|publish/amazon-task/upload-relation');
//@title 上传产品价格
define('API_GET_publish_amazonTask_uploadPrice','get|publish/amazon-task/upload-price');
//@title 上传产品数量
define('API_GET_publish_amazonTask_uploadQuantity','get|publish/amazon-task/upload-quantity');
//@title 上传产品图片
define('API_GET_publish_amazonTask_uploadImages','get|publish/amazon-task/upload-images');
//@title 获取上传结果
define('API_GET_publish_amazonTask_getSubmission','get|publish/amazon-task/get-submission');

//控制器：app\publish\controller\AmazonPublishTask
//@title 每日刊登列表；
define('API_GET_publish_amazonTask','get|publish/amazon-task');
//@title 产品标签；
define('API_GET_publish_amazonTask_tags','get|publish/amazon-task/tags');

//控制器：app\system\controller\Country
//@title 国家列表
define('API_GET_country','get|country');
//@title 分区国家
define('API_GET_country_lists','get|country/lists');
//@title 显示地区列表
define('API_GET_zone','get|zone');

//控制器：app\goods\controller\Brand
//@title 品牌列表
define('API_GET_brand','get|brand');
//@title 保存品牌
define('API_POST_brand','post|brand');
//@title 编辑品牌
define('API_GET_brand___id_edit','get|brand/:id/edit');
//@title 更新品牌
define('API_PUT_brand___id','put|brand/:id');
//@title 删除品牌
define('API_DELETE_brand___id','delete|brand/:id');
//@title 获取品牌字段值
define('API_GET_brand_dictionary','get|brand/dictionary');
//@title 产品品牌风险字典
define('API_GET_tort_dictionary','get|tort/dictionary');

//控制器：app\goods\controller\CategoryAttribute
//@title 保存产品分类属性关联
define('API_POST_setAttributes','post|set-attributes');
//@title 查看产品分类属性
define('API_GET_setAttributes___id','get|set-attributes/:id');

//控制器：app\goods\controller\CategoryQc
//@title 保存分类质检关联
define('API_POST_setQc','post|set-qc');
//@title 查看产品分类质检
define('API_GET_setQc___id','get|set-qc/:id');
//@title 获取检具字段值
define('API_GET_goods_check_tool','get|goods/check_tool');
//@title 获取质检组信息
define('API_GET_setQc_group','get|set-qc/group');

//控制器：app\goods\controller\ChannelCategory
//@title 获取所有的平台
define('API_GET_channelCategories','get|channel-categories');
//@title 获取部分平台
define('API_GET_channelPart','get|channel-part');
//@title 获取平台的站点
define('API_GET_channelCategories___id','get|channel-categories/:id');
//@title 获取平台下某站点所有分类
define('API_GET_channelCategories___channel___site','get|channel-categories/:channel/:site');
//@title 获取分类
define('API_GET_channelCategories___channel___site___cid','get|channel-categories/:channel/:site/:cid');

//控制器：app\goods\controller\GoodsImage
//@title 保存产品图片
define('API_POST_goodsImage','post|goods-image');
//@title 查看产品图片
define('API_GET_goodsImage___id','get|goods-image/:id');
//@title 获取相关的资源，支持 goodsid 与 sku_id
define('API_GET_goodsImage_getThumb','get|goods-image/get-thumb');
//@title 保存产品图片
define('API_POST_goodsImage_selfImage','post|goods-image/self-image');
//@title 获取刊登图片
define('API_GET_goodsImage_listing','get|goods-image/listing');
//@title 获取自定义图片
define('API_GET_goodsImage_selfImage','get|goods-image/self-image');
//@title 获取产品图片计算路径
define('API_GET_goodsImage_path','get|goods-image/path');

//控制器：app\goods\controller\Packing
//@title 显示包装列表
define('API_GET_packing','get|packing');
//@title 创建包装信息
define('API_POST_packing','post|packing');
//@title 编辑包装
define('API_GET_packing___id_edit','get|packing/:id/edit');
//@title 更新包装信息
define('API_PUT_packing___id','put|packing/:id');
//@title 删除包装
define('API_DELETE_packing___id','delete|packing/:id');
//@title 获取供应商信息
define('API_GET_packing_getSupplier','get|packing/getSupplier');
//@title 获取币种类型
define('API_GET_packing_getCurrency','get|packing/getCurrency');
//@title 获取包装字典
define('API_GET_packing_dictionary','get|packing/dictionary');

//控制器：app\goods\controller\Unit
//@title 单位管理列表
define('API_GET_unit','get|unit');
//@title 保存单位
define('API_POST_unit','post|unit');
//@title 编辑单位
define('API_GET_unit___id_edit','get|unit/:id/edit');
//@title 更新单位
define('API_PUT_unit___id','put|unit/:id');
//@title 删除单位
define('API_DELETE_unit___id','delete|unit/:id');
//@title 获取单位字段值
define('API_GET_unit_dictionary','get|unit/dictionary');

//控制器：app\goods\controller\Tag
//@title 显示标签列表
define('API_GET_tag','get|tag');
//@title 保存标签
define('API_POST_tag','post|tag');
//@title 编辑标签
define('API_GET_tag___id_edit','get|tag/:id/edit');
//@title 更新标签
define('API_PUT_tag___id','put|tag/:id');
//@title 删除标签
define('API_DELETE_tag___id','delete|tag/:id');
//@title 获取标签字段值
define('API_GET_tag_dictionary','get|tag/dictionary');

//控制器：app\index\controller\AliexpressAccount
//@title 显示资源列表
define('API_GET_aliexpressAccount','get|aliexpress-account');
//@title 保存新建的资源
define('API_POST_aliexpressAccount','post|aliexpress-account');
//@title 显示指定的资源
define('API_GET_aliexpressAccount___id','get|aliexpress-account/:id');
//@title 显示编辑资源表单页.
define('API_GET_aliexpressAccount___id_edit','get|aliexpress-account/:id/edit');
//@title 保存更新的资源
define('API_PUT_aliexpressAccount___id','put|aliexpress-account/:id');
//@title 停用，启用账号
define('API_POST_aliexpressAccount_states','post|aliexpress-account/states');
//@title 显示授权页面
define('API_POST_aliexpressAccount_authorization','post|aliexpress-account/authorization');
//@title 为已授权的用户开通消息服务
define('API_GET_aliexpressAccount_userPermit','get|aliexpress-account/user-permit');
//@title 批量为已授权的用户开通消息服务
define('API_GET_aliexpressAccount_userPermitBatch','get|aliexpress-account/user-permit-batch');
//@title 获取已开通消息主题列表
define('API_GET_aliexpressAccount_topic','get|aliexpress-account/topic');
//@title 取消用户的消息服务
define('API_POST_aliexpressAccount_userCancel','post|aliexpress-account/userCancel');
//@title 获取授权码
define('API_POST_aliexpressAccount_getAuthorCode','post|aliexpress-account/getAuthorCode');
//@title 获取Token
define('API_POST_aliexpressAccount_getToken','post|aliexpress-account/getToken');
//@title 批量设置
define('API_POST_aliexpressAccount_batchUpdate','post|aliexpress-account/batch-update');

//控制器：app\system\controller\Lang
//@title 语言管理列表
define('API_GET_system_lang','get|system/lang');
//@title 语言管理添加
define('API_POST_system_lang','post|system/lang');
//@title 语言管理获取
define('API_GET_system_lang___id','get|system/lang/:id');
//@title 语言管理编辑
define('API_GET_system_lang___id_edit','get|system/lang/:id/edit');
//@title 语言管理更新
define('API_PUT_system_lang___id','put|system/lang/:id');
//@title 语言管理删除
define('API_DELETE_system_lang___id','delete|system/lang/:id');
//@title 获取语言字典
define('API_GET_lang_dictionary','get|lang/dictionary');

//控制器：app\warehouse\controller\ShippingMethod
//@title 新增物流方式
define('API_POST_shippingMethod','post|shipping-method');
//@title 查看物流方式
define('API_GET_shippingMethod___id','get|shipping-method/:id');
//@title 更新物流方式
define('API_PUT_shippingMethod___id','put|shipping-method/:id');
//@title 更面单信息
define('API_PUT_shippingMethod_label___id','put|shipping-method/label/:id');
//@title 获取面单信息
define('API_GET_shippingMethod_label___id','get|shipping-method/label/:id');
//@title 获取物流方式时效详情
define('API_GET_shippingMethod_detail___id','get|shipping-method/detail/:id');
//@title 获取运费折扣
define('API_GET_shippingMethod_fee___id','get|shipping-method/fee/:id');
//@title 修改运费折扣
define('API_PUT_shippingMethod_updateFee___id','put|shipping-method/update-fee/:id');
//@title 保存运费详情
define('API_POST_shippingMethod_detail___id','post|shipping-method/detail/:id');
//@title 速卖通线上发货设置地址
define('API_PUT_shippingMethod_aliAddress___id','put|shipping-method/ali-address/:id');
//@title 速卖通线上发货批量设置地址
define('API_PUT_shippingMethod_aliAddress_batch','put|shipping-method/ali-address/batch');
//@title 导入运费详情
define('API_POST_shippingMethod_import_detail','post|shipping-method/import/detail');
//@title 计算物流费用
define('API_POST_shippingMethod___id_shippingfee','post|shipping-method/:id/shippingfee');
//@title 修改物流方式状态
define('API_PUT_shippingMethod___id_status','put|shipping-method/:id/status');
//@title 试算运费页面
define('API_GET_shippingMethod_trial_index','get|shipping-method/trial/index');
//@title 试算运费
define('API_GET_shippingMethod_trial','get|shipping-method/trial');
//@title 试算运费物流方式接口
define('API_GET_shippingMethod_dictionary','get|shipping-method/dictionary');
//@title 订单物流接口
define('API_GET_shippingMethod_listOrder','get|shipping-method/list-order');
//@title 物流信息列表
define('API_GET_shippingMethod_info','get|shipping-method/info');
//@title 规则接口
define('API_GET_shippingMethod_listRule','get|shipping-method/list-rule');
//@title 仓库物流列表
define('API_GET_shippingMethod_lists','get|shipping-method/lists');
//@title 面单序列号
define('API_GET_shippingMethod_sequenceNumber','get|shipping-method/sequence-number');
//@title 获取物流属性
define('API_GET_shippingMethod_Property','get|shipping-method/Property');
//@title 物流日志
define('API_GET_shippingMethod_log','get|shipping-method/log');
//@title 邮寄方式报价复制
define('API_POST_shippingMethod_copy','post|shipping-method/copy');
//@title 特殊拣货分类
define('API_GET_shippingMethod_labelNormList','get|shipping-method/label-norm-list');
//@title 修改水印坐标
define('API_PUT_shippingMethod_saveCoordinate','put|shipping-method/save-coordinate');
//@title 保存可发货平台
define('API_PUT_shippingMethod_channel___id','put|shipping-method/channel/:id');
//@title 获取可发货平台
define('API_GET_shippingMethod_channel___id','get|shipping-method/channel/:id');
//@title 导入分段
define('API_POST_shippingMethod_import_stageFee','post|shipping-method/import/stage-fee');
//@title 启用/禁用分区
define('API_PUT_shippingMethod_detail_status','put|shipping-method/detail/status');
//@title 导入可达天数
define('API_POST_shippingMethod_importDay','post|shipping-method/import-day');
//@title 报价对比
define('API_POST_shippingMethod_comparePrice','post|shipping-method/compare-price');

//控制器：app\warehouse\controller\WarehouseCargoShift
//@title 显示资源列表
define('API_GET_warehouseCargoShift','get|warehouse-cargo-shift');
//@title 显示pda上架/下架列表
define('API_GET_warehouseCargoShift_list','get|warehouse-cargo-shift/list');
//@title 查看
define('API_GET_warehouseCargoShift___id','get|warehouse-cargo-shift/:id');
//@title 审核
define('API_PUT_warehouseCargoShift_check','put|warehouse-cargo-shift/check');
//@title 批量库位转移
define('API_POST_warehouseCargoShift_batch_shift','post|warehouse-cargo-shift/batch/shift');
//@title 上架/下架查看
define('API_GET_warehouseCargoShift_detail','get|warehouse-cargo-shift/detail');
//@title 下架
define('API_PUT_warehouseCargoShift_unshelves','put|warehouse-cargo-shift/unshelves');
//@title 上架
define('API_PUT_warehouseCargoShift_shelves','put|warehouse-cargo-shift/shelves');
//@title 强制完成上架
define('API_GET_forced___id','get|forced/:id');
//@title 状态
define('API_GET_warehouseCargoShift_statusList','get|warehouse-cargo-shift/status-list');

//控制器：app\finance\controller\FinancePurchase
//@title  显示列表
define('API_GET_financePurchase','get|finance-purchase');
//@title  批量标记付款
define('API_POST_financePurchase_batchChangeStatus','post|finance-purchase/batchChangeStatus');
//@title 导出采购结算
define('API_POST_financePurchase_export','post|finance-purchase/export');

//控制器：app\publish\controller\AliAuthCategory
//@title 获取模板内容
define('API_GET_aliexpreeeProductTemplateContent','get|aliexpreee-product-template-content');
//@title 速卖通店铺准入行业列表
define('API_GET_aliexpreeeCategoryMapList','get|aliexpreee-category-map-list');
//@title 新增刊登分类
define('API_POST_addPublishAliCategory','post|add-publish-ali-category');
//@title 编辑刊登分类
define('API_POST_editPublishAliCategory','post|edit-publish-ali-category');
//@title 删除速卖通授权分类
define('API_POST_aliexpressAuthCategoryDelete','post|aliexpress-auth-category-delete');
//@title 编辑速卖通授权分类
define('API_GET_aliexpressAuthCategoryEdit','get|aliexpress-auth-category-edit');
//@title 速卖通信息模板列表
define('API_GET_aliexpressProductTemplateList','get|aliexpress-product-template-list');
//@title 创建速卖通关联信息模板
define('API_POST_createRelationProductTemplate','post|create-relation-product-template');
//@title 创建速卖通自定义信息模板
define('API_POST_createCustomProductTemplate','post|create-custom-product-template');
//@title 关联信息模板预览
define('API_POST_review','post|review');
//@title 删除速卖通信息模板
define('API_POST_deleteProductTemplate','post|delete-product-template');
//@title 编辑速卖通信息模板
define('API_POST_editProductTemplate','post|edit-product-template');
//@title 获取关联信息模板图片
define('API_GET_getRelationTemplateImages','get|get-relation-template-images');
//@title 获取关联信息模板和自定义信息模板
define('API_GET_getRelationAndCustomTemplate','get|get-relation-and-custom-template');

//控制器：app\index\controller\ImportData
//@title 产品资料导入列表
define('API_GET_import','get|import');
//@title 产品导入
define('API_GET_import_goods','get|import/goods');
//@title 导入单属性SKU
define('API_GET_import_singleSku','get|import/single-sku');
//@title 导入sku属性
define('API_GET_import_skuAttribute','get|import/sku-attribute');
//@title 导入sku属性fix
define('API_GET_import_handleAttribute','get|import/handle-attribute');
//@title 导入sku属性数据库
define('API_GET_import_dataAttribute','get|import/data-attribute');
//@title 导入赛盒数据
define('API_GET_import_saiheData','get|import/saihe-data');
//@title 导入赛盒数据
define('API_GET_import_saiheGoods','get|import/saihe-goods');
//@title 导入赛盒库存
define('API_GET_import_saiheStock','get|import/saihe-stock');
//@title 导入赛盒采购
define('API_GET_import_saihePurchase','get|import/saihe-purchase');
//@title 导入skuMap
define('API_GET_import_skuMap','get|import/sku-map');
//@title 产品导入模板
define('API_GET_import_export','get|import/export');
//@title 导入属性
define('API_GET_import_attribute','get|import/attribute');
//@title 导入属性值
define('API_GET_import_attributeValue','get|import/attribute-value');

//控制器：app\publish\controller\EbayPublish
//@title eBay未刊登列表
define('API_GET_ebayUnpublished','get|ebay-unpublished');

//控制器：app\listing\controller\Ebay
//@title 同步促销规则
define('API_POST_rsyncEbayPromotion','post|rsync-ebay-promotion');
//@title 应用公共模块
define('API_POST_applicationEbayCommonModule','post|application-ebay-common-module');
//@title 获取商品所有图片
define('API_POST_updateEbayProductSale_note','post|update-ebay-product-sale_note');
//@title 获取商品所有图片
define('API_GET_getEbayProductImages','get|get-ebay-product-images');
//@title 修改商品图片
define('API_POST_updateEbayProductImages','post|update-ebay-product-images');
//@title 促销折扣设置
define('API_POST_ebayPromotion','post|ebay-promotion');
//@title 自动补货设置
define('API_POST_ebayReplenishment','post|ebayReplenishment');
//@title 重新上架规则
define('API_POST_ebayReshelf','post|ebayReshelf');
//@title Ebay上架
define('API_POST_onlineEbayProduct','post|onlineEbayProduct');
//@title Ebay下架
define('API_POST_offlineEbayProduct','post|offlineEbayProduct');
//@title 店铺分类
define('API_POST_editEbayShopCategory','post|editEbayShopCategory');
//@title 商品标题
define('API_POST_editEbayTitle','post|editEbayTitle');
//@title 商品一口价和可售数量
define('API_POST_editEbayProductPriceQuantity','post|editEbayProductPriceQuantity');
//@title 商品拍卖价
define('API_POST_editEbayProductAuctionPrice','post|editEbayProductAuctionPrice');
//@title 同步listing
define('API_POST_rsyncEbayProduct','post|rsyncEbayProduct');
//@title 更新修改了资料的listing
define('API_POST_rsyncEditEbayProduct','post|rsyncEditEbayProduct');

//控制器：app\publish\controller\Common
//@title 生成sku
define('API_POST_createSkuCode','post|create-sku-code');
//@title 生成捆绑sku
define('API_POST_createBindSku','post|create-bind-sku');
//@title 上传网络图片
define('API_POST_uploadNetImages','post|upload-net-images');
//@title 上传本地图片
define('API_POST_uploadLocalImages','post|upload-local-images');
//@title 获取本地仓库列表
define('API_GET_localWarehouse','get|local-warehouse');

//控制器：app\warehouse\controller\GuanYiWarehouse
//@title 创建国家(所有)
define('API_GET_Guanyiwarehouse_warehouse_Guanyiwarehouse_country','get|Guanyiwarehouse/warehouse/Guanyiwarehouse/country');
//@title 创建商品颜色
define('API_GET_Guanyiwarehouse_warehouse_Guanyiwarehouse_skucolor','get|Guanyiwarehouse/warehouse/Guanyiwarehouse/skucolor');
//@title 创建商品尺寸
define('API_GET_Guanyiwarehouse_warehouse_Guanyiwarehouse_skusize','get|Guanyiwarehouse/warehouse/Guanyiwarehouse/skusize');
//@title 创建商品分类
define('API_GET_Guanyiwarehouse_warehouse_Guanyiwarehouse_skucategory','get|Guanyiwarehouse/warehouse/Guanyiwarehouse/skucategory');
//@title 创建供应商
define('API_GET_Guanyiwarehouse_warehouse_GuanYiWarehouse_supplier','get|Guanyiwarehouse/warehouse/GuanYiWarehouse/supplier');
//@title 创建SKU
define('API_GET_Guanyiwarehouse_warehouse_GuanYiWarehouse_producttest','get|Guanyiwarehouse/warehouse/GuanYiWarehouse/producttest');
//@title 采购接货通知单 到货通知单
define('API_GET_Guanyiwarehouse_warehouse_GuanYiWarehouse_purchaseAsn','get|Guanyiwarehouse/warehouse/GuanYiWarehouse/purchaseAsn');
//@title 取消送货通知单
define('API_GET_Guanyiwarehouse_warehouse_GuanYiWarehouse_cancelPurchaseAsn','get|Guanyiwarehouse/warehouse/GuanYiWarehouse/cancelPurchaseAsn');
//@title 传递包裹 发货通知单
define('API_GET_Guanyiwarehouse_warehouse_GuanYiWarehouse_createDN','get|Guanyiwarehouse/warehouse/GuanYiWarehouse/createDN');
//@title 取消包裹通知
define('API_GET_Guanyiwarehouse_warehouse_GuanYiWarehouse_cancelpackageType','get|Guanyiwarehouse/warehouse/GuanYiWarehouse/cancelpackageType');
//@title 管易推送邮寄方式
define('API_GET_Guanyiwarehouse_warehouse_GuanYiWarehouse_shippingMethod','get|Guanyiwarehouse/warehouse/GuanYiWarehouse/shippingMethod');
//@title 管易推送产品
define('API_GET_Guanyiwarehouse_warehouse_GuanYiWarehouse_code','get|Guanyiwarehouse/warehouse/GuanYiWarehouse/code');
//@title 管易推送承运商
define('API_GET_Guanyiwarehouse_warehouse_GuanYiWarehouse_carrier','get|Guanyiwarehouse/warehouse/GuanYiWarehouse/carrier');
//@title 管易推送平台
define('API_GET_Guanyiwarehouse_warehouse_GuanYiWarehouse_shop','get|Guanyiwarehouse/warehouse/GuanYiWarehouse/shop');
//@title 管易平台推送包裹
define('API_GET_Guanyiwarehouse_warehouse_GuanYiWarehouse_package','get|Guanyiwarehouse/warehouse/GuanYiWarehouse/package');
//@title 采购入库(到货通知单)
define('API_GET_Guanyiwarehouse_warehouse_GuanYiWarehouse_purchaseArrival','get|Guanyiwarehouse/warehouse/GuanYiWarehouse/purchaseArrival');
//@title 自动更新数据
define('API_GET_guanyi_update_datas','get|guanyi/update/datas');
//@title (麻烦请无关信息添加后不要提交svn， 谢谢)
define('API_GET_guanyi_test','get|guanyi/test');

//控制器：app\order\controller\Synchronize
//@title 同步发货列表
define('API_GET_synchronizes','get|synchronizes');
//@title 历史信息
define('API_GET_synchronizes_history','get|synchronizes/history');
//@title 获取状态
define('API_GET_synchronizes___type_status','get|synchronizes/:type/status');
//@title 开始同步
define('API_POST_synchronizes_start','post|synchronizes/start');
//@title 忽略
define('API_POST_synchronizes_ignore','post|synchronizes/ignore');
//@title 剩余时间
define('API_GET_synchronizes_surplus','get|synchronizes/surplus');
//@title 邮寄方式
define('API_GET_synchronizes_shipping','get|synchronizes/shipping');
//@title 再次同步
define('API_PUT_synchronizes_renew','put|synchronizes/renew');

//控制器：app\publish\controller\AliexpressBrand
//@title 获取Aliexpress分类下面所有品牌
define('API_GET_aliBrand_brands','get|ali-brand/brands');
//@title 保存品牌设置
define('API_POST_aliBrand_setBrands','post|ali-brand/set-brands');
//@title 获取最新品牌
define('API_POST_aliBrand_synBrands','post|ali-brand/syn-brands');
//@title 获取产品分组
define('API_GET_rsyncAliexpressGroups','get|rsync-aliexpress-groups');
//@title 获取运费模板
define('API_GET_rsyncAliexpressTransport','get|rsync-aliexpress-transport');
//@title 获取服务模板
define('API_GET_rsyncAliexpressPromise','get|rsync-aliexpress-promise');

//控制器：app\order\controller\Package
//@title 包裹列表
define('API_GET_packages','get|packages');
//@title 读取
define('API_GET_packages___id','get|packages/:id');
//@title 更新包裹
define('API_PUT_packages___id','put|packages/:id');
//@title 获取操作信息
define('API_GET_packages___type_info','get|packages/:type/info');
//@title 包裹进度条
define('API_GET_packages___number_speed','get|packages/:number/speed');
//@title 包裹类型
define('API_GET_packages_type','get|packages/type');
//@title 批量打印面单
define('API_POST_packages_print','post|packages/print');
//@title 批量打印发票
define('API_POST_packages_print_invoice','post|packages/print/invoice');
//@title 预览面单
define('API_GET_packages___id_preview','get|packages/:id/preview');
//@title 预览报关面单
define('API_GET_packages___id_declareView','get|packages/:id/declare-view');
//@title 获取面单信息
define('API_POST_packages_batch_label','post|packages/batch/label');
//@title 获取跟踪号信息
define('API_POST_packages_batch_tracking','post|packages/batch/tracking');
//@title 导出execl
define('API_POST_packages_export','post|packages/export');
//@title execl字段信息
define('API_GET_packages_exportTitle','get|packages/export-title');
//@title 申请取消拣货单
define('API_POST_packages_applyCancel','post|packages/apply-cancel');
//@title 复制包裹信息
define('API_POST_packages_copy','post|packages/copy');
//@title 获取分配库存状态
define('API_GET_packages_distributionType_info','get|packages/distribution-type/info');
//@title 获取拣货单状态
define('API_GET_packages_pickingType_info','get|packages/picking-type/info');
//@title 获取包装状态
define('API_GET_packages_packingType_info','get|packages/packing-type/info');
//@title 获取包裹是否缺货状态
define('API_GET_packages_oosType_info','get|packages/oos-type/info');
//@title 取消物流下单
define('API_POST_packages_cancelLogistics','post|packages/cancel-logistics');
//@title 包裹批量拦截
define('API_POST_packages_packageIntercept','post|packages/package-intercept');
//@title zoodmall线上物流导出
define('API_POST_packages_zoodmallExport','post|packages/zoodmall-export');
//@title 停止揽收包裹列表
define('API_GET_packages_stopCollecting','get|packages/stop-collecting');
//@title 同步物流商发货
define('API_PUT_packages_batch_logisticsDelivery','put|packages/batch/logistics-delivery');
//@title 同步签收状态
define('API_PUT_packages_batch_logisticsReceipt','put|packages/batch/logistics-receipt');
//@title 物流状态
define('API_GET_packages_shipping_type','get|packages/shipping/type');
//@title 批量更换包裹号
define('API_PUT_packages_batch_packageNumber','put|packages/batch/package-number');

//控制器：app\order\controller\Fba
//@title fba订单列表
define('API_GET_fbaOrders','get|fba-orders');
//@title 获取订单详情信息
define('API_GET_fbaOrders___order_id_info','get|fba-orders/:order_id/info');
//@title 销售额统计
define('API_GET_fbaOrders_report','get|fba-orders/report');
//@title execl字段信息
define('API_GET_fbaOrders_exportTitle','get|fba-orders/export-title');
//@title 导出execl
define('API_POST_fbaOrders_export','post|fba-orders/export');

//控制器：app\goods\controller\GoodsSku
//@title 查询商品
define('API_GET_goodsSku_query','get|goods-sku/query');
//@title 根据id，sku，别名取得sku信息
define('API_GET_goodsSku_info','get|goods-sku/info');
//@title 根据sku返回详细信息
define('API_GET_goodsSku_api___sku_info','get|goods-sku/api/:sku/info');
//@title 根据sku_id获取兄弟元素
define('API_GET_goodsSku___id_siblings','get|goods-sku/:id/siblings');
//@title 删除sku
define('API_POST_goodsSku_batch_delete','post|goods-sku/batch/delete');
//@title 包裹重量差异列表
define('API_GET_goodsSku_diffWeight','get|goods-sku/diff-weight');
//@title 包裹重量差异列表导出
define('API_POST_goodsSku_diffWeightExport','post|goods-sku/diff-weight-export');
//@title 批量设置停售sku
define('API_POST_goodsSku_batch_stopped','post|goods-sku/batch/stopped');
//@title 停售sku渠道
define('API_GET_goodsSku_stoppedChannel','get|goods-sku/stopped-channel');

//控制器：app\warehouse\controller\Label
//@title 标签模板列表
define('API_GET_label','get|label');
//@title 读取标签模板信息
define('API_GET_label___id','get|label/:id');
//@title 保存标签模板信息
define('API_POST_label','post|label');
//@title 删除标签模板
define('API_DELETE_label_delTemp','delete|label/del-temp');
//@title 获取所有标签模板类型
define('API_GET_label_labelTypes','get|label/label-types');
//@title 根据标签模板类型获取适用字段
define('API_GET_label_labelFields___type','get|label/label-fields/:type');
//@title 复制标签模板
define('API_GET_label_copy___id','get|label/copy/:id');
//@title 获取指定类型的模板列表
define('API_GET_label_tempList___type','get|label/temp-list/:type');

//控制器：app\publish\controller\PricingRule
//@title 定价规则列表
define('API_GET_pricingRules','get|pricing-rules');
//@title 定价规则获取
define('API_GET_pricingRules___id','get|pricing-rules/:id');
//@title 定价规则添加
define('API_POST_pricingRules','post|pricing-rules');
//@title 定价规则更新
define('API_PUT_pricingRules___id','put|pricing-rules/:id');
//@title 定价规则删除
define('API_DELETE_pricingRules___id','delete|pricing-rules/:id');
//@title 保存排序值
define('API_POST_pricingRules_sort','post|pricing-rules/sort');
//@title 规则复制
define('API_POST_pricingRules_copy','post|pricing-rules/copy');
//@title 更改规则状态
define('API_POST_pricingRules___id_status___value','post|pricing-rules/:id/status/:value');
//@title 获取可选条件
define('API_GET_pricingRules_items','get|pricing-rules/items');
//@title 获取默认设置
define('API_GET_pricingRules_default','get|pricing-rules/default');
//@title 匹配规则计算销售价
define('API_POST_pricingRules_calculate','post|pricing-rules/calculate');

//控制器：app\warehouse\controller\LabelPrint
//@title 获取产品标签打印数据
define('API_GET_labelPrint','get|label-print');
//@title 批量获取产品标签打印数据
define('API_GET_labelPrint_batchSku','get|label-print/batch-sku');
//@title 获取箱唛标签打印数据
define('API_GET_labelPrint_boxLabel','get|label-print/box-label');
//@title 批量获取箱唛标签打印数据
define('API_GET_labelPrint_batchBox','get|label-print/batch-box');

//控制器：app\goods\controller\GoodsPreDev
//@title 预开发产品列表
define('API_GET_goodsPreDev','get|goods-pre-dev');
//@title 查看预产品开发
define('API_GET_goodsPreDev___id','get|goods-pre-dev/:id');
//@title 新增预产品开发
define('API_POST_goodsPreDev','post|goods-pre-dev');
//@title 编辑预产品开发
define('API_GET_goodsPreDev___id_edit','get|goods-pre-dev/:id/edit');
//@title 更新预产品开发
define('API_PUT_goodsPreDev___id','put|goods-pre-dev/:id');
//@title 查看预产品开发日志
define('API_GET_goodsPreDev_log___id','get|goods-pre-dev/log/:id');
//@title 获取预产品开发审核按钮
define('API_GET_goodsPreDev_audit','get|goods-pre-dev/audit');
//@title 审核预产品开发流程
define('API_POST_goodsPreDev_audit','post|goods-pre-dev/audit');
//@title 获取预产品产品开发流程
define('API_GET_goodsPreDev_process','get|goods-pre-dev/process');
//@title 获取预产品开发申请人
define('API_GET_goodsPreDev_proposer','get|goods-pre-dev/proposer');
//@title 获取初始渠道列表
define('API_GET_goodsPreDev_channel','get|goods-pre-dev/channel');

//控制器：app\customerservice\controller\AmazonEmail
//@title 客服邮件查询接口
define('API_GET_amazonEmails','get|amazon-emails');
//@title 客户历史邮件查询接口
define('API_GET_amazonEmails_senders___email_address','get|amazon-emails/senders/:email_address');
//@title 更新指定id的邮件
define('API_PUT_amazonEmails___id','put|amazon-emails/:id');
//@title 获取客户的历史订单
define('API_GET_orders_buyerAmazonOrders___buyer_id','get|orders/buyer-amazon-orders/:buyer_id');
//@title 获取能够管理制定账号的客服列表
define('API_GET_amazonEmails_account_customers','get|amazon-emails/account/customers');
//@title 亚马逊邮件标记已读
define('API_POST_amazonEmails_read','post|amazon-emails/read');
//@title 匹配回复模板内容
define('API_GET_amazonEmails_tpl_content','get|amazon-emails/tpl/content');
//@title 获取客服对应的账号
define('API_GET_amazonMessage_account','get|amazon-message/account');
//@title 获取所有站点
define('API_GET_amazonEmails_site','get|amazon-emails/site');
//@title 获取全部可发送邮件的账号
define('API_GET_amazonEmails_amazonEmailAccount','get|amazon-emails/amazon-emailAccount');

//控制器：app\customerservice\controller\AmazonSentEmail
//@title 查询Amazon发送邮件接口
define('API_GET_amazonEmails_sentEmails','get|amazon-emails/sent-emails');
//@title Amazon发送邮件
define('API_POST_amazonEmails_sentEmails_send','post|amazon-emails/sent-emails/send');
//@title 回复Amazon邮件
define('API_POST_amazonEmails_replyEmails','post|amazon-emails/reply-emails');
//@title Amazon失败邮件重新发送
define('API_POST_amazonEmails_sentMails_resend___mail_id','post|amazon-emails/sent-mails/resend/:mail_id');

//控制器：app\customerservice\controller\AmazonEmailAccount
//@title 获取邮箱账号列表
define('API_GET_amazonEmails_emailAccount','get|amazon-emails/email-account');
//@title 查看amazon邮箱账号
define('API_GET_amazonEmails_emailAccount___id','get|amazon-emails/email-account/:id');
//@title 获取能够发送邮件的amazon帐号
define('API_GET_amazonEmails_account','get|amazon-emails/account');
//@title 添加amazon邮箱账号
define('API_POST_amazonEmails_emailAccount','post|amazon-emails/email-account');
//@title 添加amazon邮箱账号
define('API_PUT_amazonEmails_emailAccount___email_account_id','put|amazon-emails/email-account/:email_account_id');
//@title 删除指定amazon邮箱账号
define('API_DELETE_amazonEmails_emailAccount___email_account_id','delete|amazon-emails/email-account/:email_account_id');
//@title 获取指定amazon邮箱的log
define('API_GET_amazonEmails_emailAccount_log___email_account_id','get|amazon-emails/email-account/log/:email_account_id');
//@title 设置amazon邮箱账号是否启用
define('API_PUT_amazonEmails_emailAccount___email_account_id_enabled','put|amazon-emails/email-account/:email_account_id/enabled');

//控制器：app\index\controller\Server
//@title 服务器列表
define('API_GET_servers','get|servers');
//@title 获取服务器信息
define('API_GET_servers___id_edit','get|servers/:id/edit');
//@title 保存服务器信息
define('API_POST_servers','post|servers');
//@title 更新服务器信息
define('API_PUT_servers___id','put|servers/:id');
//@title 删除服务器信息
define('API_DELETE_servers___id','delete|servers/:id');
//@title 获取服务器ip地址
define('API_GET_servers_ip','get|servers/ip');
//@title 用户授权
define('API_POST_servers_authorization','post|servers/authorization');
//@title 获取用户授权信息
define('API_GET_servers_authorizationInfo','get|servers/authorization-info');
//@title 导出服务器execl
define('API_POST_servers_export','post|servers/export');
//@title 导出服务器成员execl
define('API_POST_servers_exportUser','post|servers/export-user');
//@title 批量设置上报周期
define('API_POST_servers_reporting_batch','post|servers/reporting/batch');
//@title 获取服务器类型
define('API_GET_servers_type','get|servers/type');
//@title 获取服务器 ip类型
define('API_GET_servers_iptype','get|servers/iptype');
//@title 停用，启用服务器
define('API_POST_servers_status','post|servers/status');
//@title 被引用详情
define('API_GET_servers___id_useInfo','get|servers/:id/use-info');
//@title 日志
define('API_GET_servers___id_log','get|servers/:id/log');
//@title 删除服务器成员
define('API_DELETE_servers___id_user','delete|servers/:id/user');
//@title 批量添加服务器成员
define('API_POST_servers___id_users','post|servers/:id/users');
//@title 外网类型
define('API_GET_servers_extranetType','get|servers/extranet-type');

//控制器：app\index\controller\Queue
//@title 队列管理列表
define('API_GET_queue','get|queue');
//@title 重新获取队列数据
define('API_POST_queue_reload','post|queue/reload');
//@title 重新获取队列当前进度
define('API_GET_queue_schedule','get|queue/schedule');
//@title 重新获取队列元素
define('API_GET_queue_elements','get|queue/elements');
//@title 清空队列元素
define('API_POST_queue_clear','post|queue/clear');
//@title 获取队列日志
define('API_GET_queue_logs','get|queue/logs');
//@title 删除队列元素（正在执行中的元素没中断）
define('API_DELETE_queue_removeElement','delete|queue/remove-element');
//@title 设置队列所在runtype
define('API_PUT_queue_changeRuntype','put|queue/change-runtype');
//@title 获取队列runtype列表
define('API_GET_queue_runtypes','get|queue/runtypes');
//@title 获取状态信息
define('API_GET_queue_status','get|queue/status');
//@title 强制关闭队列进程
define('API_GET_queue_forceKill','get|queue/force-kill');
//@title 修改指定队列的当前运行状态
define('API_GET_queue_changeRunStatus','get|queue/change-run-status');
//@title 修改队列的状态
define('API_POST_queue_status','post|queue/status');
//@title 设置swoole的table_queue计数
define('API_PUT_queue_queueCount','put|queue/queue-count');
//@title 获取waitQueue
define('API_GET_queue_consuming','get|queue/consuming');
//@title 获取手机验证码
define('API_GET_queue_catpondCode','get|queue/catpond-code');

//控制器：app\index\controller\Buyer
//@title 买家列表
define('API_GET_buyers','get|buyers');
//@title 查看买家信息
define('API_GET_buyers___id','get|buyers/:id');
//@title 获取编辑买家信息
define('API_GET_buyers___id_edit','get|buyers/:id/edit');
//@title 保存买家信息
define('API_POST_buyers','post|buyers');
//@title 更新买家信息
define('API_PUT_buyers___id','put|buyers/:id');
//@title 删除买家信息
define('API_DELETE_buyers___id','delete|buyers/:id');
//@title 批量删除
define('API_POST_buyers_batch_delete','post|buyers/batch/delete');
//@title 导入买家批量修改状态
define('API_POST_buyers_batchUpdate','post|buyers/batch-update');
//@title 买家批量修改导入模板下载；
define('API_GET_buyers_updateTemplate','get|buyers/update-template');

//控制器：app\report\controller\ProfitStatement
//@title 台利润列表
define('API_GET_report_financial_profitStatement','get|report/financial/profit-statement');
//@title amazon平台利润列表
define('API_GET_report_financial_profitStatement_amazon','get|report/financial/profit-statement/amazon');
//@title wish平台利润列表
define('API_GET_report_financial_profitStatement_wish','get|report/financial/profit-statement/wish');
//@title 速卖通平台利润列表
define('API_GET_report_financial_profitStatement_aliExpress','get|report/financial/profit-statement/ali-express');
//@title ebay平台利润列表
define('API_GET_report_financial_profitStatement_ebay','get|report/financial/profit-statement/ebay');
//@title joom平台利润列表
define('API_GET_report_financial_profitStatement_joom','get|report/financial/profit-statement/joom');
//@title lazada平台利润列表
define('API_GET_report_financial_profitStatement_lazada','get|report/financial/profit-statement/lazada');
//@title shopee平台利润列表
define('API_GET_report_financial_profitStatement_shopee','get|report/financial/profit-statement/shopee');
//@title paytm平台利润列表
define('API_GET_report_financial_profitStatement_paytm','get|report/financial/profit-statement/paytm');
//@title pandao平台利润列表
define('API_GET_report_financial_profitStatement_pandao','get|report/financial/profit-statement/pandao');
//@title walmart平台利润列表
define('API_GET_report_financial_profitStatement_walmart','get|report/financial/profit-statement/walmart');
//@title jumia平台利润列表
define('API_GET_report_financial_profitStatement_jumia','get|report/financial/profit-statement/jumia');
//@title vova平台利润列表
define('API_GET_report_financial_profitStatement_vova','get|report/financial/profit-statement/vova');
//@title umka平台利润列表
define('API_GET_report_financial_profitStatement_umka','get|report/financial/profit-statement/umka');
//@title cd平台利润列表
define('API_GET_report_financial_profitStatement_cd','get|report/financial/profit-statement/cd');
//@title newegg平台利润列表
define('API_GET_report_financial_profitStatement_newegg','get|report/financial/profit-statement/newegg');
//@title oberlo平台利润列表
define('API_GET_report_financial_profitStatement_oberlo','get|report/financial/profit-statement/oberlo');
//@title zoodmall平台利润列表
define('API_GET_report_financial_profitStatement_zoodmall','get|report/financial/profit-statement/zoodmall');
//@title yandex平台利润列表
define('API_GET_report_financial_profitStatement_yandex','get|report/financial/profit-statement/yandex');
//@title 订单平台利润列表导出接口
define('API_POST_report_financial_export_profitStatement','post|report/financial/export/profit-statement');
//@title 订单货品统计信息
define('API_GET_report_financial_order_skus','get|report/financial/order/skus');

//控制器：app\report\controller\ReportShipped
//@title 获取已发货记录列表
define('API_GET_report_shipped','get|report/shipped');
//@title 导出
define('API_POST_report_shipped_export','post|report/shipped/export');

//控制器：app\report\controller\ReportShortage
//@title 获取缺货记录列表
define('API_GET_report_shortage','get|report/shortage');
//@title 导出
define('API_POST_report_shortage_export','post|report/shortage/export');

//控制器：app\report\controller\ReportUnshipped
//@title 获取未发货记录列表
define('API_GET_report_unshipped','get|report/unshipped');
//@title 导出
define('API_POST_report_unshipped_export','post|report/unshipped/export');

//控制器：app\report\controller\ReportUnpacked
//@title 获取未拆包记录列表
define('API_GET_report_unpacked','get|report/unpacked');
//@title 导出
define('API_POST_report_unpacked_export','post|report/unpacked/export');

//控制器：app\report\controller\Performance
//@title 平台利润汇总表
define('API_GET_report_financial_performance','get|report/financial/performance');
//@title ebay平台利润汇总表
define('API_GET_report_financial_performance_ebay','get|report/financial/performance/ebay');
//@title amazon平台利润汇总表
define('API_GET_report_financial_performance_amazon','get|report/financial/performance/amazon');
//@title wish平台利润汇总表
define('API_GET_report_financial_performance_wish','get|report/financial/performance/wish');
//@title aliExpress平台利润汇总表
define('API_GET_report_financial_performance_ali','get|report/financial/performance/ali');
//@title fba平台利润汇总表
define('API_GET_report_financial_performance_fba','get|report/financial/performance/fba');
//@title 销售利润汇总列表导出接口
define('API_POST_report_financial_export_performance','post|report/financial/export/performance');
//@title 保存资源
define('API_POST_report_financial_performance','post|report/financial/performance');
//@title 查看资源
define('API_GET_report_financial_performance___id','get|report/financial/performance/:id');

//控制器：app\report\controller\AmazonAccountMonitor
//@title 列表详情
define('API_GET_report_amazonMonitor','get|report/amazon-monitor');
//@title 导出
define('API_POST_report_amazonMonitor_export','post|report/amazon-monitor/export');

//控制器：app\index\controller\BuyerAddress
//@title 买家地址列表
define('API_GET_buyerAddresses','get|buyer-addresses');
//@title 保存买家地址信息
define('API_POST_buyerAddresses','post|buyer-addresses');
//@title 更新买家地址信息
define('API_PUT_buyerAddresses___id','put|buyer-addresses/:id');
//@title 删除买家地址
define('API_DELETE_buyerAddresses___id','delete|buyer-addresses/:id');
//@title 设置默认地址
define('API_POST_buyerAddresses_default','post|buyer-addresses/default');

//控制器：app\order\controller\BrushOrder
//@title 刷单列表
define('API_GET_brushOrders','get|brush-orders');
//@title 同步发货设置
define('API_POST_brushOrders___order_id_synchronize','post|brush-orders/:order_id/synchronize');
//@title 开始同步
define('API_POST_brushOrders_start','post|brush-orders/start');
//@title 导出execl
define('API_POST_brushOrders_export','post|brush-orders/export');
//@title execl字段信息
define('API_GET_brushOrders_exportTitle','get|brush-orders/export-title');

//控制器：app\report\controller\ExportFileList
//@title 获取用户的导出文件申请列表
define('API_GET_report_exportFiles','get|report/export-files');
//@title 删除报表
define('API_DELETE_report_exportFiles_deletes___id','delete|report/export-files/deletes/:id');

//控制器：app\index\controller\ServerLog
//@title 服务器访问日志列表
define('API_GET_serverLogs','get|server-logs');

//控制器：app\carrier\controller\AliexpressAddress
//@title 获取速卖通地址信息
define('API_GET_aliAddress','get|ali-address');

//控制器：app\index\controller\BasicAccount
//@title 显示资源列表
define('API_GET_accountBasics','get|account-basics');
//@title 保存新建的资源
define('API_POST_accountBasics','post|account-basics');
//@title 显示指定的资源
define('API_GET_accountBasics___id','get|account-basics/:id');
//@title 显示编辑资源表单页.
define('API_GET_accountBasics___id_edit','get|account-basics/:id/edit');
//@title 保存更新的资源
define('API_PUT_accountBasics___id','put|account-basics/:id');
//@title 获取状态列表信息
define('API_GET_accountBasics_status___info','get|account-basics/status/:info');
//@title 更改账号状态
define('API_POST_accountBasics_batch___type','post|account-basics/batch/:type');
//@title 显示密码
define('API_GET_accountBasics_password','get|account-basics/password');
//@title 服务器已绑定的账号列表
define('API_GET_accountBasics_alreadyBind','get|account-basics/already-bind');
//@title 自动识别图片
define('API_GET_accountBasics_automatic','get|account-basics/automatic');
//@title 资料日志
define('API_GET_accountBasics___account_id_log','get|account-basics/:account_id/log');
//@title 读取运营负责人
define('API_GET_accountBasics_user','get|account-basics/user');
//@title 获取状态列表信息
define('API_GET_accountBasics_changes','get|account-basics/changes');
//@title 资料旧手机日志
define('API_GET_accountBasics___account_id_phoneLog','get|account-basics/:account_id/phone-log');

//控制器：app\index\controller\AccountUser
//@title 显示资源列表
define('API_GET_accountUsers','get|account-users');
//@title 保存新建的资源
define('API_POST_accountUsers','post|account-users');
//@title 批量添加、删除账号成员
define('API_POST_accountUsers_batch','post|account-users/batch');

//控制器：app\test\controller\ChangeCarrier
//@title 获取数据
define('API_GET_changeCarrier_getData','get|change-carrier/get-data');
//@title 设置数据
define('API_GET_changeCarrier_setData__','get|change-carrier/set-data:');
//@title 发送数据请求
define('API_GET_changeCarrier_senderData','get|change-carrier/sender-data');

//控制器：app\listing\controller\Item
//@title 更新线上sku与本地sku关系
define('API_POST_updateSkuRelation','post|update-sku-relation');

//控制器：app\order\controller\OrderHold
//@title 列表信息
define('API_GET_orderHold','get|order-hold');
//@title 获取详情
define('API_GET_orderHold___id','get|order-hold/:id');
//@title 获取编辑详情
define('API_GET_orderHold___id_edit','get|order-hold/:id/edit');
//@title 更新
define('API_PUT_orderHold___id','put|order-hold/:id');
//@title 新增拦截
define('API_POST_orderHold','post|order-hold');
//@title 批量操作
define('API_POST_orderHold_batch','post|order-hold/batch');
//@title 原因信息
define('API_GET_orderHold_reason','get|order-hold/reason');
//@title 拦截状态
define('API_GET_orderHold_status','get|order-hold/status');
//@title 导出execl
define('API_POST_orderHold_export','post|order-hold/export');

//控制器：app\order\controller\VirtualOrderHold
//@title 获取列表信息
define('API_GET_virtualHold','get|virtual-hold');
//@title 获取详情信息
define('API_GET_virtualHold___id','get|virtual-hold/:id');
//@title 获取编辑信息
define('API_GET_virtualHold___id_edit','get|virtual-hold/:id/edit');
//@title 新增记录
define('API_POST_virtualHold','post|virtual-hold');
//@title 修改记录
define('API_PUT_virtualHold___id','put|virtual-hold/:id');
//@title 删除记录
define('API_DELETE_virtualHold___id','delete|virtual-hold/:id');
//@title 批量删除
define('API_POST_virtualHold_batch_delete','post|virtual-hold/batch/delete');
//@title 虚拟订单批量导入
define('API_POST_virtualHold_batchImport','post|virtual-hold/batch-import');
//@title 虚拟订单导入模板下载；
define('API_GET_virtualHold_importTemplate','get|virtual-hold/import-template');

//控制器：app\order\controller\VirtualOrder
//@title 虚拟订单申请
define('API_POST_virtualOrder','post|virtual-order');
//@title 虚拟订单列表
define('API_GET_virtualOrder','get|virtual-order');
//@title 查看订单信息
define('API_GET_virtualOrder___id','get|virtual-order/:id');
//@title 批量分配
define('API_POST_virtualOrder_batch_allot','post|virtual-order/batch/allot');
//@title 返回虚拟订单列表的状态信息
define('API_GET_virtualOrder_status','get|virtual-order/status');
//@title 组长审批
define('API_POST_virtualOrder_audit_headman','post|virtual-order/audit/headman');
//@title 批量作废
define('API_POST_virtualOrder_batch_cancel','post|virtual-order/batch/cancel');
//@title 查看订单信息
define('API_GET_virtualOrder___id_logs','get|virtual-order/:id/logs');
//@title 导入sku信息
define('API_POST_virtualOrder_import','post|virtual-order/import');
//@title 刷单类型
define('API_GET_virtualOrder_missionType','get|virtual-order/mission-type');
//@title 任务列表的状态信息
define('API_GET_virtualOrder_mission_status','get|virtual-order/mission/status');
//@title 货币类型
define('API_GET_virtualOrder_currency','get|virtual-order/currency');
//@title 刷单任务列表
define('API_GET_virtualOrder_missionList','get|virtual-order/mission-list');
//@title 任务详情
define('API_GET_virtualOrder_mission___id','get|virtual-order/mission/:id');
//@title 负责人列表
define('API_GET_virtualOrder_principalList','get|virtual-order/principal-list');
//@title 指定负责人
define('API_POST_virtualOrder_mission_allocation','post|virtual-order/mission/allocation');
//@title 买家列表
define('API_GET_virtualOrder_buyerList','get|virtual-order/buyer-list');
//@title 指定买家
define('API_POST_virtualOrder_mission_buyer','post|virtual-order/mission/buyer');
//@title 查看任务日志
define('API_GET_virtualOrder_mission___id_logs','get|virtual-order/mission/:id/logs');
//@title 自动指定买家
define('API_POST_virtualOrder_mission_buyerAutomation','post|virtual-order/mission/buyer-automation');
//@title 国外用户列表
define('API_GET_virtualOrder_userList','get|virtual-order/user-list');
//@title 国外用户-添加
define('API_POST_virtualOrder_userAdd','post|virtual-order/user-add');
//@title 国外用户-详细信息
define('API_GET_virtualOrder_userInfo','get|virtual-order/user-info');
//@title 国外用户-保存
define('API_POST_virtualOrder_userEditor','post|virtual-order/user-editor');
//@title 国外用户-更改状态
define('API_POST_virtualOrder_userStatus','post|virtual-order/user-status');
//@title 更新用户密码
define('API_POST_virtualOrder_userSave','post|virtual-order/user-save');
//@title 刷单任务处理
define('API_POST_virtualOrder_dispose','post|virtual-order/dispose');
//@title 导入平台订单号
define('API_POST_virtualOrder_channelImport','post|virtual-order/channel-import');
//@title 上传图片
define('API_POST_virtualOrder_imgImport','post|virtual-order/img-import');
//@title 获取国内刷单列表
define('API_GET_virtualOrder_inlandTask_list','get|virtual-order/inland-task/list');
//@title 获取国家信息
define('API_GET_virtualOrder_country','get|virtual-order/country');

//控制器：app\report\controller\OrderDetail
//@title 列表详情
define('API_GET_report_orderDetails','get|report/order-details');
//@title 导出
define('API_POST_report_orderDetails_export','post|report/order-details/export');

//控制器：app\report\controller\GoodsAnalysis
//@title 列表详情
define('API_GET_report_goodsAnalysis','get|report/goods-analysis');
//@title 导出
define('API_POST_report_goodsAnalysis_export','post|report/goods-analysis/export');
//@title 同步销量
define('API_POST_report_goodsAnalysis_synchronous','post|report/goods-analysis/synchronous');

//控制器：app\report\controller\SaleRefund
//@title 列表详情
define('API_GET_report_saleRefund','get|report/sale-refund');
//@title 导出
define('API_POST_report_saleRefund_export','post|report/sale-refund/export');

//控制器：app\report\controller\SaleStock
//@title 列表详情
define('API_GET_report_saleStock','get|report/sale-stock');
//@title 导出
define('API_POST_report_saleStock_export','post|report/sale-stock/export');

//控制器：app\report\controller\Settlement
//@title 统计财务结算表
define('API_GET_settlement_index_settle','get|settlement/index_settle');
//@title aliexpress放款帐期详情导出
define('API_POST_settlement_export','post|settlement/export');
//@title 统计财务结算表详情
define('API_GET_settlement_settle_detail','get|settlement/settle_detail');

//控制器：app\warehouse\controller\WarehouseArea
//@title 显示分区列表
define('API_GET_warehouseArea','get|warehouse-area');
//@title 保存新建的分区
define('API_POST_warehouseArea','post|warehouse-area');
//@title 分区详情
define('API_GET_warehouseArea___id','get|warehouse-area/:id');
//@title 显示指定的资源
define('API_GET_warehouseArea___id_edit','get|warehouse-area/:id/edit');
//@title 保存更新的分区
define('API_PUT_warehouseArea___id','put|warehouse-area/:id');
//@title 删除指定分区
define('API_DELETE_warehouseArea___id','delete|warehouse-area/:id');
//@title 分区列表
define('API_GET_warehouseArea_lists','get|warehouse-area/lists');
//@title 状态更新
define('API_PUT_warehouseArea___id_status','put|warehouse-area/:id/status');
//@title 分区功能列表
define('API_GET_warehouseArea_types','get|warehouse-area/types');
//@title 获取多品分拣人
define('API_GET_warehouseArea___warehouse_id_picker','get|warehouse-area/:warehouse_id/picker');
//@title 设置多品分拣人
define('API_PUT_warehouseArea___warehouse_id_picker','put|warehouse-area/:warehouse_id/picker');
//@title 测试接口
define('API_GET_warehouseArea_test','get|warehouse-area/test');

//控制器：app\warehouse\controller\WarehouseCargoClass
//@title 显示资源列表
define('API_GET_warehouseCargoClass','get|warehouse-cargo-class');
//@title 保存新建的资源
define('API_POST_warehouseCargoClass','post|warehouse-cargo-class');
//@title 显示指定的资源
define('API_GET_warehouseCargoClass___id','get|warehouse-cargo-class/:id');
//@title 显示指定的资源
define('API_GET_warehouseCargoClass___id_edit','get|warehouse-cargo-class/:id/edit');
//@title  保存更新的资源
define('API_PUT_warehouseCargoClass___id','put|warehouse-cargo-class/:id');
//@title 删除指定资源
define('API_DELETE_warehouseCargoClass___id','delete|warehouse-cargo-class/:id');
//@title 状态更新
define('API_PUT_warehouseCargoClass___id_status','put|warehouse-cargo-class/:id/status');
//@title 货位类型列表
define('API_GET_warehouseCargoClass_lists','get|warehouse-cargo-class/lists');

//控制器：app\warehouse\controller\WarehouseCargo
//@title 显示资源列表
define('API_GET_warehouseCargo','get|warehouse-cargo');
//@title 导出
define('API_POST_warehouseCargo_export','post|warehouse-cargo/export');
//@title 保存新建的资源
define('API_POST_warehouseCargo','post|warehouse-cargo');
//@title 显示指定的资源
define('API_GET_warehouseCargo___id','get|warehouse-cargo/:id');
//@title 显示指定的资源
define('API_GET_warehouseCargo___id_edit','get|warehouse-cargo/:id/edit');
//@title 保存更新的资源
define('API_PUT_warehouseCargo___id','put|warehouse-cargo/:id');
//@title 删除指定资源
define('API_DELETE_warehouseCargo___id','delete|warehouse-cargo/:id');
//@title 批量删除
define('API_DELETE_warehouseCargo','delete|warehouse-cargo');
//@title 货位导入
define('API_POST_warehouseCargo_import','post|warehouse-cargo/import');
//@title 状态更新
define('API_PUT_warehouseCargo___id_status','put|warehouse-cargo/:id/status');
//@title 批量状态更新
define('API_PUT_warehouseCargo_status','put|warehouse-cargo/status');
//@title 仓库货位列表(移库)
define('API_GET_warehouseCargo_lists','get|warehouse-cargo/lists');
//@title 标签打印
define('API_GET_warehouseCargo_print','get|warehouse-cargo/print');
//@title 仓库货位列表(绑定)
define('API_GET_warehouseCargo_recommend','get|warehouse-cargo/recommend');

//控制器：app\warehouse\controller\WarehouseShelf
//@title 显示资源列表
define('API_GET_warehouseShelf','get|warehouse-shelf');
//@title 保存新建的资源
define('API_POST_warehouseShelf','post|warehouse-shelf');
//@title 显示指定的资源
define('API_GET_warehouseShelf___id','get|warehouse-shelf/:id');
//@title 显示指定的资源
define('API_GET_warehouseShelf___id_edit','get|warehouse-shelf/:id/edit');
//@title 保存更新的资源
define('API_PUT_warehouseShelf___id','put|warehouse-shelf/:id');
//@title  删除指定资源
define('API_DELETE_warehouseShelf___id','delete|warehouse-shelf/:id');
//@title 货架列表
define('API_GET_warehouseShelf_lists','get|warehouse-shelf/lists');
//@title 状态更新
define('API_PUT_warehouseShelf___id_status','put|warehouse-shelf/:id/status');
//@title 获取对面通道
define('API_GET_warehouseShelf_face_aisle','get|warehouse-shelf/face_aisle');

//控制器：app\warehouse\controller\WarehouseCargoGoods
//@title 显示资源列表
define('API_GET_warehouseCargoGoods','get|warehouse-cargo-goods');
//@title 导出货位库存
define('API_POST_warehouseCargoGoods_export','post|warehouse-cargo-goods/export');
//@title 日志操作类型
define('API_GET_warehouseCargoGoods_logTypes','get|warehouse-cargo-goods/log-types');
//@title 操作明细
define('API_GET_warehouseCargoGoods_logs','get|warehouse-cargo-goods/logs');
//@title 商品移库
define('API_POST_warehouseCargoGoods_shift','post|warehouse-cargo-goods/shift');
//@title 货位库存解绑
define('API_DELETE_warehouseCargoGoods___id','delete|warehouse-cargo-goods/:id');
//@title 手动绑定货位
define('API_POST_warehouseCargoGoods_bind','post|warehouse-cargo-goods/bind');
//@title 自动绑定货位
define('API_POST_warehouseCargoGoods_autoBind','post|warehouse-cargo-goods/auto-bind');
//@title 冻结库存调整
define('API_POST_warehouseCargoGoods_modifyHold','post|warehouse-cargo-goods/modify-hold');
//@title 批量库位转移
define('API_POST_warehouseCargoGoods_batch_shift','post|warehouse-cargo-goods/batch/shift');

//控制器：app\index\controller\LocalBuyerAccount
//@title 本地买手列表
define('API_GET_localBuyers','get|local-buyers');
//@title 获取服务器信息
define('API_GET_localBuyers___id_edit','get|local-buyers/:id/edit');
//@title 保存服务器信息
define('API_POST_localBuyers','post|local-buyers');
//@title 更新服务器信息
define('API_PUT_localBuyers___id','put|local-buyers/:id');
//@title 删除服务器信息
define('API_DELETE_localBuyers___id','delete|local-buyers/:id');
//@title 批量删除
define('API_POST_localBuyers_batch','post|local-buyers/batch');
//@title 显示密码
define('API_GET_localBuyers_password','get|local-buyers/password');

//控制器：app\publish\controller\AmazonProductExport
//@title 导出产品列表
define('API_GET_publish_amazonProductExport','get|publish/amazon-product-export');
//@title 查看指定产品信息
define('API_GET_publish_amazonProductExport___goods_id','get|publish/amazon-product-export/:goods_id');
//@title 修改指定产品的信息
define('API_PUT_publish_amazonProductExport___goods_id','put|publish/amazon-product-export/:goods_id');
//@title 删除指定产品
define('API_DELETE_publish_amazonProductExport___goods_id','delete|publish/amazon-product-export/:goods_id');
//@title 获取系统中的商品信息
define('API_GET_publish_amazonProductExport_goods___goods_id','get|publish/amazon-product-export/goods/:goods_id');
//@title 添加系统的产品到导出列表
define('API_POST_publish_amazonProductExport','post|publish/amazon-product-export');
//@title 下载需要导出的产品
define('API_GET_publish_amazonProductExport_download','get|publish/amazon-product-export/download');

//控制器：app\publish\controller\AmazonListing
//@title listing 列表
define('API_GET_publish_amazonListing','get|publish/amazon-listing');
//@title listing导出
define('API_GET_publish_amazonListing_export','get|publish/amazon-listing/export');
//@title 查看指定产品信息
define('API_GET_publish_amazonListing_detail___listing_id','get|publish/amazon-listing/detail/:listing_id');
//@title 查看指定产品信息
define('API_GET_publish_amazonListing_relation','get|publish/amazon-listing/relation');
//@title 查找asin
define('API_POST_publish_amazonListing_asins','post|publish/amazon-listing/asins');
//@title 批量删除listing
define('API_DELETE_publish_amazonListing_batch','delete|publish/amazon-listing/batch');

//控制器：app\listing\controller\Test
//@title 刊登提交测试
define('API_GET_wishTest','get|wish-test');

//控制器：app\warehouse\controller\MakePicking
//@title 列表
define('API_GET_makePickings','get|make-pickings');
//@title 生成拣货单
define('API_POST_makePickings','post|make-pickings');
//@title 剩余时间
define('API_GET_makePickings_surplus','get|make-pickings/surplus');
//@title 渠道信息
define('API_GET_makePickings_channels','get|make-pickings/channels');
//@title 运算符
define('API_GET_makePickings_operator','get|make-pickings/operator');
//@title 邮寄方式
define('API_GET_makePickings_shipping','get|make-pickings/shipping');
//@title 批量生成拣货单
define('API_POST_makePickings_batch','post|make-pickings/batch');
//@title 重返上架生成拣货单
define('API_POST_makePickings_make','post|make-pickings/make');
//@title 生成快速出货区拣货单
define('API_POST_makePickings_makeQuick','post|make-pickings/make-quick');

//控制器：app\warehouse\controller\SortingShelf
//@title 显示播种车 列表
define('API_GET_sortingShelf','get|sorting-shelf');
//@title 保存新建播种车
define('API_POST_sortingShelf','post|sorting-shelf');
//@title 显示指定的资源
define('API_GET_sortingShelf___id','get|sorting-shelf/:id');
//@title 保存更新的资源
define('API_PUT_sortingShelf___id','put|sorting-shelf/:id');
//@title 删除指定资源
define('API_DELETE_sortingShelf___id','delete|sorting-shelf/:id');
//@title 状态更新
define('API_PUT_sortingShelf___id_status_','put|sorting-shelf/:id/status/');
//@title 播种车列表
define('API_GET_sortingShelf_lists','get|sorting-shelf/lists');

//控制器：app\warehouse\controller\Picking
//@title 拣货单列表
define('API_GET_pickings','get|pickings');
//@title 查看
define('API_GET_pickings___id','get|pickings/:id');
//@title 拣货单状态
define('API_GET_pickings_status','get|pickings/status');
//@title 拣货单包裹状态
define('API_GET_pickings_package_status','get|pickings/package/status');
//@title 获取子拣货单信息
define('API_GET_pickings___id_sub','get|pickings/:id/sub');
//@title 拣货单类型
define('API_GET_pickings_type','get|pickings/type');
//@title 拣货单详情
define('API_GET_pickings___id_detail','get|pickings/:id/detail');
//@title 打印拣货单
define('API_GET_pickings___id_print','get|pickings/:id/print');
//@title 打印面单地址
define('API_GET_pickings___id_label','get|pickings/:id/label');
//@title 打印带有面单详情的面单
define('API_GET_pickings___id_detailLabel','get|pickings/:id/detail-label');
//@title 打印发票
define('API_GET_pickings___id_invoice','get|pickings/:id/invoice');
//@title 获取运输方式
define('API_GET_pickings_shipping','get|pickings/shipping');
//@title 作废
define('API_POST_pickings___id_invalid','post|pickings/:id/invalid');
//@title 查看包裹信息
define('API_GET_pickings___id_packages','get|pickings/:id/packages');
//@title 查看拣货单周转箱信息
define('API_GET_pickings___id_turnover','get|pickings/:id/turnover');
//@title 下架完成拣货
define('API_POST_pickings___id_complete','post|pickings/:id/complete');
//@title 标记为正在拣货
define('API_POST_pickings___id_pickingProcess','post|pickings/:id/picking-process');
//@title 标记为等待分拣
define('API_POST_pickings___id_waitSorting','post|pickings/:id/wait-sorting');
//@title 标记为集结完成
define('API_POST_pickings___id_pickingMassed','post|pickings/:id/picking-massed');
//@title 正在分拣作业
define('API_GET_pickings_sorting','get|pickings/sorting');
//@title 正在包装作业
define('API_GET_pickings_packing','get|pickings/packing');
//@title 更换拣货人
define('API_POST_pickings___id_shipper','post|pickings/:id/shipper');
//@title 标记为包装完成
define('API_POST_pickings___id_signPackingComplete','post|pickings/:id/sign-packing-complete');
//@title 快速发货区-拣货单商品列表
define('API_GET_pickings_quickPickingDetail','get|pickings/quick-picking-detail');
//@title 拣货单包裹列表
define('API_GET_pickings_quickPickingPackage','get|pickings/quick-picking-package');
//@title 快速发货区移除包裹
define('API_POST_pickings_quickPickingRemove','post|pickings/quick-picking-remove');
//@title 拣货单操作日志
define('API_GET_pickings___id_log','get|pickings/:id/log');
//@title 获取周转箱商品信息
define('API_GET_pickings___id_turnover_detail','get|pickings/:id/turnover/detail');
//@title 转移周转箱功能
define('API_POST_pickings___id_turnover_transfer','post|pickings/:id/turnover/transfer');

//控制器：app\warehouse\controller\TurnoverBox
//@title 显示周转箱列表
define('API_GET_turnoverBox','get|turnover-box');
//@title 保存新建的周转箱
define('API_POST_turnoverBox','post|turnover-box');
//@title 显示指定的资源
define('API_GET_turnoverBox___id','get|turnover-box/:id');
//@title 显示指定的资源
define('API_GET_turnoverBox___id_edit','get|turnover-box/:id/edit');
//@title 保存更新的资源
define('API_PUT_turnoverBox___id','put|turnover-box/:id');
//@title 删除指定资源
define('API_DELETE_turnoverBox___id','delete|turnover-box/:id');
//@title 周转箱作废
define('API_PUT_turnoverBox___id_invalid','put|turnover-box/:id/invalid');
//@title 获取操作日志
define('API_GET_turnoverBox___id_logs','get|turnover-box/:id/logs');
//@title 周转箱集结
define('API_PUT_turnoverBox_mass','put|turnover-box/mass');
//@title 标签打印
define('API_GET_turnoverBox_print','get|turnover-box/print');
//@title 批量释放周装箱
define('API_POST_turnoverBox_batchRemove','post|turnover-box/batch-remove');

//控制器：app\warehouse\controller\MassZone
//@title 显示集结区列表
define('API_GET_massZone','get|mass-zone');
//@title 保存新建的集结区
define('API_POST_massZone','post|mass-zone');
//@title 显示指定的资源
define('API_GET_massZone___id','get|mass-zone/:id');
//@title 显示指定的资源
define('API_GET_massZone___id_edit','get|mass-zone/:id/edit');
//@title 保存更新的资源
define('API_PUT_massZone___id','put|mass-zone/:id');
//@title 删除指定资源
define('API_DELETE_massZone___id','delete|mass-zone/:id');
//@title 状态更新
define('API_PUT_massZone___id_status_','put|mass-zone/:id/status/');
//@title 集结区管理
define('API_GET_massZone_lists','get|mass-zone/lists');

//控制器：app\warehouse\controller\DeliveryCheck
//@title 扫描周转箱号或拣货单号
define('API_POST_deliveryCheck_singleBox','post|delivery-check/single-box');
//@title 扫描sku
define('API_POST_deliveryCheck_singleSku','post|delivery-check/single-sku');
//@title 单品多件确认
define('API_POST_deliveryCheck_sureSku','post|delivery-check/sure-sku');
//@title 扫描面单号(多品复核)
define('API_POST_deliveryCheck_checkShippingNumber','post|delivery-check/check-shipping-number');
//@title 复核打印
define('API_POST_deliveryCheck_printShippingNumber','post|delivery-check/print-shipping-number');
//@title 扫描周转箱号 二次分拣
define('API_POST_deliveryCheck_checkTurnoverBox','post|delivery-check/check-turnover-box');
//@title 多品分拣确认
define('API_POST_deliveryCheck_auditTurnoverBox','post|delivery-check/audit-turnover-box');
//@title 二次分拣
define('API_POST_deliveryCheck_twiceSorting','post|delivery-check/twice-sorting');
//@title 获取篮子信息
define('API_GET_deliveryCheck_basketInfo','get|delivery-check/basket-info');
//@title 获取播种车信息(二次分拣)
define('API_GET_deliveryCheck_gridInfo___picking_id','get|delivery-check/grid-info/:picking_id');
//@title 获取周转篮列表(二次分拣)
define('API_GET_deliveryCheck_basketList___picking_id','get|delivery-check/basket-list/:picking_id');
//@title 将周转篮重置未开始分拣
define('API_POST_deliveryCheck_resetTwiceSoring___id','post|delivery-check/reset-twice-soring/:id');
//@title 重置单个篮子变为重新分拣
define('API_POST_deliveryCheck_resetBasket','post|delivery-check/reset-basket');
//@title 包裹列表
define('API_GET_deliveryCheck_packageList___id','get|delivery-check/package-list/:id');
//@title 包裹面单打印
define('API_POST_deliveryCheck___package_id_print','post|delivery-check/:package_id/print');
//@title 包裹面单测试打印
define('API_POST_deliveryCheck___package_id_testPrint','post|delivery-check/:package_id/test-print');
//@title 测试打印html面单
define('API_POST_deliveryCheck___package_id_testHtmlPrint','post|delivery-check/:package_id/test-html-print');
//@title 根据包裹号直接打印 .
define('API_POST_deliveryCheck_packageNumberPrint','post|delivery-check/package-number-print');
//@title 批量打印
define('API_POST_deliveryCheck_batchPrint','post|delivery-check/batch-print');
//@title 批量打印篮子面单
define('API_POST_deliveryCheck_batchPrintBasket','post|delivery-check/batch-print-basket');
//@title 批量打印篮子标签
define('API_POST_deliveryCheck_batch_printBasketLabel','post|delivery-check/batch/print-basket-label');
//@title 获取拣货单面单规格
define('API_GET_deliveryCheck_pickingLabelInfo','get|delivery-check/picking-label-info');
//@title 中止单品复核
define('API_POST_deliveryCheck___picking_id_stop','post|delivery-check/:picking_id/stop');
//@title 停用当前周转箱
define('API_POST_deliveryCheck_stopTurnoverBox','post|delivery-check/stop-turnover-box');
//@title 确认退出周转箱
define('API_POST_deliveryCheck_sureStopBox','post|delivery-check/sure-stop-box');
//@title 中止二次分拣
define('API_POST_deliveryCheck___picking_id_stopPicking','post|delivery-check/:picking_id/stop-picking');
//@title 确认异常
define('API_POST_deliveryCheck___package_id_confirmError','post|delivery-check/:package_id/confirm-error');
//@title 批量重新复核
define('API_POST_deliveryCheck_batchResetSingle','post|delivery-check/batch-reset-single');
//@title 快速出货区重置缓存
define('API_GET_deliveryCheck_resetQuickCache','get|delivery-check/reset-quick-cache');
//@title 清除已扫描信息
define('API_POST_deliveryCheck_flushChecking','post|delivery-check/flush-checking');
//@title 重新打印
define('API_POST_deliveryCheck_printLabel','post|delivery-check/print-label');
//@title 替换面单
define('API_POST_deliveryCheck_printChangeLabel','post|delivery-check/print-change-label');
//@title 结束单品拣货单
define('API_GET_deliveryCheck_stopSinglePicking','get|delivery-check/stop-single-picking');
//@title 确认结束拣货单
define('API_POST_deliveryCheck_sureStopSinglePicking','post|delivery-check/sure-stop-single-picking');
//@title 测试推送生成html
define('API_GET_deliveryCheck_pushHtmlQueue','get|delivery-check/push-html-queue');
//@title 获取百度api语音合成token
define('API_GET_deliveryCheck_getBaiduToken','get|delivery-check/get-baidu-token');
//@title 按面单包装
define('API_POST_deliveryCheck_labelCheck','post|delivery-check/label-check');
//@title 排除法批量包装
define('API_POST_deliveryCheck_exclusionCheck','post|delivery-check/exclusion-check');
//@title 获取包到一半的包裹
define('API_GET_deliveryCheck___id_watchCache','get|delivery-check/:id/watch-cache');
//@title 删除单品多件缓存.
define('API_POST_deliveryCheck_delete_watchKey','post|delivery-check/delete/watch-key');

//控制器：app\warehouse\controller\PackageCollection
//@title 扫描面单号称重
define('API_POST_packageCollection_setWeight','post|package-collection/set-weight');
//@title 根据面单号获取运输方式
define('API_GET_packageCollection_shipping','get|package-collection/shipping');
//@title 读取集包单信息
define('API_GET_packageCollection___id','get|package-collection/:id');
//@title 集包完成
define('API_PUT_packageCollection___id','put|package-collection/:id');
//@title 获取类型
define('API_GET_packageCollection_typeList','get|package-collection/type-list');
//@title 集包单复核信息
define('API_GET_packageCollection_checkInfo___code','get|package-collection/check-info/:code');
//@title 复核
define('API_PUT_packageCollection_check','put|package-collection/check');
//@title 状态信息
define('API_GET_packageCollection_status','get|package-collection/status');
//@title 列表
define('API_GET_packageCollection','get|package-collection');
//@title 左边菜单
define('API_GET_packageCollection_leftMenu','get|package-collection/left-menu');
//@title 批量交接
define('API_POST_packageCollection_batch','post|package-collection/batch');
//@title 批量加入队列出货
define('API_POST_packageCollection_batch_outQueue','post|package-collection/batch/out-queue');
//@title 批量出库
define('API_POST_packageCollection_batchOut','post|package-collection/batch-out');
//@title 包裹列表
define('API_GET_packageCollection___id_packageList','get|package-collection/:id/package-list');
//@title 批量继续集包
define('API_GET_packageCollection_historyCollection','get|package-collection/history-collection');
//@title 包裹详情
define('API_GET_packageCollection___id_info','get|package-collection/:id/info');
//@title 移除包裹
define('API_DELETE_packageCollection_package___id','delete|package-collection/package/:id');
//@title 问题包裹
define('API_GET_packageCollection_problem','get|package-collection/problem');
//@title 获取异常详情
define('API_GET_packageCollection_problemInfo','get|package-collection/problem-info');
//@title 状态
define('API_GET_packageCollection_problem_status','get|package-collection/problem/status');
//@title 处理
define('API_PUT_packageCollection_problem_handle','put|package-collection/problem/handle');
//@title 集包单作废
define('API_PUT_packageCollection_cancel___id','put|package-collection/cancel/:id');
//@title 根据单号交接
define('API_POST_packageCollection___code_handover','post|package-collection/:code/handover');
//@title 根据单号出库
define('API_POST_packageCollection___code_out','post|package-collection/:code/out');
//@title 包裹作废
define('API_PUT_packageCollection_problem___id_packageCancel','put|package-collection/problem/:id/package-cancel');
//@title 批量复核
define('API_POST_packageCollection_batchCheck','post|package-collection/batch-check');
//@title 批量作废
define('API_POST_packageCollection_batch_packageCancel','post|package-collection/batch/package-cancel');
//@title 设置包裹预估重量
define('API_PUT_packageCollection_problem___package_id_estimatedWeight','put|package-collection/problem/:package_id/estimated-weight');
//@title 批量设置预估重量
define('API_POST_packageCollection_batch_setWeight','post|package-collection/batch/set-weight');
//@title 批量处理异常
define('API_POST_packageCollection_problem_batchHandle','post|package-collection/problem/batch-handle');
//@title 批量移除
define('API_POST_packageCollection_batchDel','post|package-collection/batch-del');
//@title 打印后回调
define('API_POST_packageCollection_problem_printCallback','post|package-collection/problem/print-callback');
//@title 继续下单
define('API_POST_packageCollection_problem_continueOrder','post|package-collection/problem/continue-order');
//@title 更改邮寄方式
define('API_POST_packageCollection_problem_changeShipping','post|package-collection/problem/change-shipping');
//@title 导出集包包裹
define('API_POST_packageCollection_export','post|package-collection/export');
//@title 获取异常类型
define('API_GET_packageCollection_problemType','get|package-collection/problem-type');
//@title 自我生成集包信息
define('API_POST_packageCollection_selfDo','post|package-collection/self-do');
//@title 获取异常处理措施
define('API_GET_packageCollection_problemMethod','get|package-collection/problem-method');
//@title 加入异常
define('API_POST_packageCollection_addProblem','post|package-collection/add-problem');
//@title 加入物流尺寸异常
define('API_POST_packageCollection_addSizeProblem','post|package-collection/add-size-problem');
//@title 重新集包
define('API_POST_packageCollection_resetCollection','post|package-collection/reset-collection');
//@title 批量重新集包
define('API_POST_packageCollection_batch_resetCollection','post|package-collection/batch/reset-collection');
//@title 手工加入袋子
define('API_GET_packageCollection_addPackage','get|package-collection/add-package');
//@title 物流未集包列表
define('API_GET_packageCollection_waitProblem','get|package-collection/wait-problem');
//@title 物流类型
define('API_GET_packageCollection_waitProblemType','get|package-collection/wait-problem-type');
//@title 修复包裹数
define('API_GET_packageCollection_retReport','get|package-collection/ret-report');

//控制器：app\warehouse\controller\PutawayOrder
//@title 显示列表
define('API_GET_putawayOrder','get|putaway-order');
//@title 新增
define('API_POST_putawayOrder_create','post|putaway-order/create');
//@title 上架
define('API_POST_putawayOrder_save','post|putaway-order/save');
//@title 查看
define('API_GET_putawayOrder___id','get|putaway-order/:id');
//@title 完成上架
define('API_GET_putawayOrder_status___id','get|putaway-order/status/:id');
//@title 分区类型
define('API_GET_putawayOrder_types','get|putaway-order/types');
//@title 作废采购上架单
define('API_POST_putawayOrder_invalid','post|putaway-order/invalid');
//@title 强制完成采购上架单
define('API_POST_putawayOrder_force','post|putaway-order/force');
//@title 完成上架采购上架单
define('API_POST_putawayOrder_finish','post|putaway-order/finish');

//控制器：app\warehouse\controller\PickingProcess
//@title 移动端拣货单列表
define('API_GET_pickingProcess','get|picking-process');
//@title 拣货单任务详情
define('API_GET_pickingProcess___id_details','get|picking-process/:id/details');
//@title 绑定周转箱
define('API_POST_pickingProcess___id_bind','post|picking-process/:id/bind');
//@title 拣货单商品下架
define('API_POST_pickingProcess___id_off','post|picking-process/:id/off');
//@title 下架
define('API_POST_pickingProcess_offShelve','post|picking-process/off-shelve');
//@title 完成拣货
define('API_POST_pickingProcess___id_complete','post|picking-process/:id/complete');

//控制器：app\warehouse\controller\PutawayWaitingGoods
//@title 显示列表
define('API_GET_putawayWaitingGoods','get|putaway-waiting-goods');
//@title 新增
define('API_POST_putawayWaitingGoods_create','post|putaway-waiting-goods/create');
//@title 获取状态
define('API_GET_putawayWaitingGoods_status','get|putaway-waiting-goods/status');
//@title 仓库区域类型
define('API_GET_putawayWaitingGoods_warehouseAreaTypes','get|putaway-waiting-goods/warehouseAreaTypes');
//@title SKU查询
define('API_GET_putawayWaitingGoods_goods___id','get|putaway-waiting-goods/goods/:id');
//@title 直接上架
define('API_POST_putawayWaitingGoods_update','post|putaway-waiting-goods/update');
//@title 直接上架批量
define('API_POST_putawayWaitingGoods_batch_update','post|putaway-waiting-goods/batch/update');
//@title 货位+SKU直接上架
define('API_POST_putawayWaitingGoods_cargoSkus','post|putaway-waiting-goods/cargoSkus');
//@title 根据SKU查货位
define('API_GET_putawayWaitingGoods_cargos___sku','get|putaway-waiting-goods/cargos/:sku');

//控制器：app\goods\controller\GoodsDeclare
//@title 列表
define('API_GET_goodsDeclare','get|goods-declare');
//@title 保存
define('API_POST_goodsDeclare','post|goods-declare');
//@title 更新
define('API_PUT_goodsDeclare___id','put|goods-declare/:id');
//@title 查看详情
define('API_GET_goodsDeclare___id','get|goods-declare/:id');
//@title 查看编辑详情
define('API_GET_goodsDeclare___id_edit','get|goods-declare/:id/edit');
//@title 删除
define('API_DELETE_goodsDeclare___id','delete|goods-declare/:id');

//控制器：app\warehouse\controller\WarehouseGoodsChannel
//@title 显示平台库存列表
define('API_GET_warehouseGoodsChannel','get|warehouse-goods-channel');
//@title 保存
define('API_POST_warehouseGoodsChannel','post|warehouse-goods-channel');
//@title 显示指定的资源
define('API_GET_warehouseGoodsChannel___id','get|warehouse-goods-channel/:id');
//@title 保存更新的资源
define('API_PUT_warehouseGoodsChannel___id','put|warehouse-goods-channel/:id');
//@title 删除指定资源
define('API_DELETE_warehouseGoodsChannel___id','delete|warehouse-goods-channel/:id');
//@title 平台借调
define('API_POST_warehouseGoodsChannel_lend','post|warehouse-goods-channel/lend');

//控制器：app\warehouse\controller\ReturnShelves
//@title 重返上架列表
define('API_GET_returnShelves','get|return-shelves');
//@title 新增重返上架单
define('API_POST_returnShelves','post|return-shelves');
//@title 验证重返上架数量
define('API_GET_returnShelves_quantity','get|return-shelves/quantity');
//@title 作废重返上架单
define('API_DELETE_returnShelves_delete','delete|return-shelves/delete');
//@title 查看重返上架单
define('API_GET_returnShelves___id','get|return-shelves/:id');
//@title 操作重返上架
define('API_PUT_returnShelves___id','put|return-shelves/:id');
//@title 完成重返上架
define('API_PUT_returnShelves_finish','put|return-shelves/finish');
//@title 强制完成
define('API_PUT_returnShelves_force','put|return-shelves/force');
//@title 重返上架详情列表
define('API_GET_returnShelves_getDetail','get|return-shelves/get-detail');

//控制器：app\warehouse\controller\PackageReturn
//@title 包裹退回信息列表
define('API_GET_packageReturn','get|package-return');
//@title 包裹退回信息详情
define('API_GET_packageReturn___id','get|package-return/:id');
//@title 录入包裹信息
define('API_POST_packageReturn_handle','post|package-return/handle');
//@title 获取原因信息
define('API_GET_packageReturn_reason','get|package-return/reason');
//@title 获取状态
define('API_GET_packageReturn_status','get|package-return/status');
//@title 标记为待重发
define('API_POST_packageReturn___id_waitForReissued','post|package-return/:id/wait-for-reissued');
//@title 待入库sku信息
define('API_GET_packageReturn___id_storageInfo','get|package-return/:id/storage-info');
//@title 标记为待入库
define('API_POST_packageReturn___id_waitForStorage','post|package-return/:id/wait-for-storage');
//@title 入库
define('API_POST_packageReturn___id_storage','post|package-return/:id/storage');
//@title 标记为已重发
define('API_POST_packageReturn___id_alreadyReissued','post|package-return/:id/already-reissued');
//@title 打印面单
define('API_GET_packageReturn___id_print','get|package-return/:id/print');
//@title 批量标记待重发
define('API_POST_packageReturn_batch_waitForReissued','post|package-return/batch/wait-for-reissued');
//@title 批量标记待入库
define('API_POST_packageReturn_batch_waitForStorage','post|package-return/batch/wait-for-storage');
//@title 批量入库
define('API_POST_packageReturn_batch_storage','post|package-return/batch/storage');
//@title 新增备注
define('API_POST_packageReturn___id_note','post|package-return/:id/note');
//@title 导出execl
define('API_POST_packageReturn_export','post|package-return/export');
//@title 导入退回包裹
define('API_POST_packageReturn_import','post|package-return/import');
//@title 保存导入退回包裹
define('API_POST_packageReturn_saveImport','post|package-return/save-import');

//控制器：app\warehouse\controller\WarehouseGoodsCheck
//@title 盘点单列表
define('API_GET_warehouseGoodsCheck','get|warehouse-goods-check');
//@title 新增
define('API_POST_warehouseGoodsCheck_create','post|warehouse-goods-check/create');
//@title 编辑
define('API_POST_warehouseGoodsCheck_updates','post|warehouse-goods-check/updates');
//@title 查看
define('API_GET_warehouseGoodsCheck___id','get|warehouse-goods-check/:id');
//@title 盘点数据核查
define('API_POST_warehouseGoodsCheck_exists','post|warehouse-goods-check/exists');
//@title 盘点
define('API_POST_warehouseGoodsCheck_save','post|warehouse-goods-check/save');
//@title 批量盘点
define('API_POST_warehouseGoodsCheck_batch_save','post|warehouse-goods-check/batch/save');
//@title 完成盘点
define('API_GET_warehouseGoodsCheck_finish___id','get|warehouse-goods-check/finish/:id');
//@title 盘点单状态
define('API_GET_warehouseGoodsCheck_status','get|warehouse-goods-check/status');
//@title 盘点单作废
define('API_DELETE_warehouseGoodsCheck_cancels___id','delete|warehouse-goods-check/cancels/:id');
//@title 盘点单删除
define('API_DELETE_warehouseGoodsCheck_deletes___id','delete|warehouse-goods-check/deletes/:id');
//@title 盘点单删除
define('API_POST_warehouseGoodsCheck_deleteDetails','post|warehouse-goods-check/delete-details');
//@title 盘点单重盘
define('API_PUT_warehouseGoodsCheck_recheck___id','put|warehouse-goods-check/recheck/:id');
//@title 盘点单详情列表
define('API_GET_warehouseGoodsCheck_getDetailList','get|warehouse-goods-check/get-detail-list');
//@title 获取盘点单信息
define('API_GET_warehouseGoodsCheck___id_getInfo','get|warehouse-goods-check/:id/get-info');

//控制器：app\warehouse\controller\WarehouseGoodsChannelLog
//@title 第三方调库存管理列表
define('API_GET_warehouseGoodsChannelLog','get|warehouse-goods-channel-log');
//@title 第三方仓库申请分配审核
define('API_POST_warehouseGoodsChannelLog_audit','post|warehouse-goods-channel-log/audit');
//@title 第三方协调分配审核
define('API_POST_warehouseGoodsChannelLog_coordinateAudit','post|warehouse-goods-channel-log/coordinate-audit');
//@title 第三方批量协调分配审核
define('API_POST_warehouseGoodsChannelLog_mcoordinateAudit','post|warehouse-goods-channel-log/mcoordinate-audit');
//@title 第三方仓库申请分配批量审核
define('API_POST_warehouseGoodsChannelLog_multiAudit','post|warehouse-goods-channel-log/multi-audit');
//@title 第三方仓库分配拒绝
define('API_POST_warehouseGoodsChannelLog_deny','post|warehouse-goods-channel-log/deny');
//@title 第三方仓库批量分配拒绝
define('API_POST_warehouseGoodsChannelLog_multiDeny','post|warehouse-goods-channel-log/multi-deny');
//@title 第三方仓库协调拒绝
define('API_POST_warehouseGoodsChannelLog_coordinateDeny','post|warehouse-goods-channel-log/coordinate-deny');
//@title 第三方仓库批量协调拒绝
define('API_POST_warehouseGoodsChannelLog_mcoordinateDeny','post|warehouse-goods-channel-log/mcoordinate-deny');
//@title 查看借调详情
define('API_GET_warehouseGoodsChannelLog___id','get|warehouse-goods-channel-log/:id');
//@title 状态列表
define('API_GET_warehouseGoodsChannelLog_status','get|warehouse-goods-channel-log/status');
//@title 创建人
define('API_GET_warehouseGoodsChannelLog_creator','get|warehouse-goods-channel-log/creator');
//@title 获取所有审核人
define('API_GET_warehouseGoodsChannelLog_auditor','get|warehouse-goods-channel-log/auditor');
//@title 更改审批人
define('API_POST_warehouseGoodsChannelLog_changeAuditor','post|warehouse-goods-channel-log/changeAuditor');
//@title 批量更改审批人
define('API_POST_warehouseGoodsChannelLog_multiChangeAuditor','post|warehouse-goods-channel-log/multi-changeAuditor');
//@title 获取该订单下平台审批人
define('API_GET_warehouseGoodsChannelLog_verifier','get|warehouse-goods-channel-log/verifier');
//@title 获取所有平台所有审批人
define('API_GET_warehouseGoodsChannelLog_getAllVerifier','get|warehouse-goods-channel-log/get-all-verifier');

//控制器：app\listing\controller\Amazon
//@title 亚马逊在线listing修改日志
define('API_GET_listing_amazon_actionLogs','get|listing/amazon/action-logs');
//@title 修改Listing日志
define('API_POST_listing_amazon_editListing','post|listing/amazon/edit-listing');
//@title 亚马逊批量修改销售价
define('API_POST_listing_amazon_batchEditPrice','post|listing/amazon/batch-edit-price');

//控制器：app\warehouse\controller\Stocking
//@title 备货申请列表
define('API_GET_stocking_applyList','get|stocking/apply-list');
//@title 批量确认备货申请
define('API_PUT_stocking_batch_sure','put|stocking/batch/sure');
//@title 获取可合并备货计划
define('API_GET_stocking_relatedPlan','get|stocking/related-plan');
//@title 批量删除
define('API_DELETE_stocking_batch_delete','delete|stocking/batch/delete');
//@title 状态信息
define('API_GET_stocking_status','get|stocking/status');
//@title 备货计划列表
define('API_GET_stocking','get|stocking');
//@title 备货计划详情
define('API_GET_stocking___id','get|stocking/:id');
//@title 提交备货计划
define('API_PUT_stocking_batch_commit','put|stocking/batch/commit');
//@title 修改备货计划SKU数量
define('API_PUT_stocking___id','put|stocking/:id');
//@title 备注备货计划
define('API_PUT_stocking___id_remark','put|stocking/:id/remark');
//@title 作废备货计划
define('API_DELETE_stocking___id','delete|stocking/:id');
//@title 审核备货计划
define('API_PUT_stocking___id_audit','put|stocking/:id/audit');
//@title 审核日志
define('API_GET_stocking___id_auditLog','get|stocking/:id/audit-log');
//@title 新建采购计划列表
define('API_GET_stocking_skuList','get|stocking/sku-list');
//@title 删除备货计划SKU
define('API_DELETE_stocking___id_sku___sku_id','delete|stocking/:id/sku/:sku_id');
//@title 备货申请表选择列表
define('API_GET_stocking___sku_id_chooseList','get|stocking/:sku_id/choose-list');
//@title 保存备货计划
define('API_POST_stocking_savePlan','post|stocking/save-plan');
//@title 根据备货单号获取备货计划列表
define('API_GET_stocking_listByCode','get|stocking/list-by-code');
//@title 开发状态信息
define('API_GET_stocking_developmentStatus','get|stocking/development-status');
//@title 采购状态信息
define('API_GET_stocking_purchaseStatus','get|stocking/purchase-status');
//@title 批量释放库存
define('API_POST_stocking_batchRelease','post|stocking/batch-release');
//@title excel字段信息
define('API_GET_stocking_exportTitle','get|stocking/export-title');
//@title 导出excel
define('API_POST_stocking_export','post|stocking/export');

//控制器：app\warehouse\controller\Barcode
//@title 条码查询
define('API_POST_barcode_datas','post|barcode/datas');

//控制器：app\warehouse\controller\RebackShelves
//@title 退回待上架
define('API_GET_rebackShelves','get|reback-shelves');
//@title 批量退回待上架
define('API_POST_rebackShelves_batch_save','post|reback-shelves/batch/save');

//控制器：app\purchase\controller\PurchaseParcelsAudit
//@title 采购拆包审核
define('API_GET_purchaseParcelsAudit','get|purchase-parcels-audit');
//@title 采购拆包审核
define('API_POST_purchaseParcelsAudit','post|purchase-parcels-audit');

//控制器：app\publish\controller\EbayCategorySearch
//@title 关键字分类搜索
define('API_POST_ebayCategorySearch','post|ebay-category-search');

//控制器：app\publish\controller\Wangxiaowang
//@title 旺销王-热词搜索
define('API_POST_alihelpHot','post|alihelp-hot');
//@title 旺销王-热词语言选项
define('API_GET_alihelpHotlang','get|alihelp-hotlang');
//@title 旺销王-直通车搜索
define('API_POST_alihelpBcar','post|alihelp-bcar');

//控制器：app\index\controller\JoomAccount
//@title joom帐号列表
define('API_GET_joomAccount','get|joom-account');
//@title 保存新建的资源
define('API_POST_joomAccount','post|joom-account');
//@title 显示指定的资源
define('API_GET_joomAccount___id','get|joom-account/:id');
//@title 显示指定的资源
define('API_GET_joomAccount___id_edit','get|joom-account/:id/edit');
//@title 保存更新的资源
define('API_PUT_joomAccount___id','put|joom-account/:id');
//@title JOOM账号停用，启用
define('API_POST_joomAccount_status','post|joom-account/status');
//@title 批量开启
define('API_POST_joomAccount_batchSet','post|joom-account/batch-set');

//控制器：app\index\controller\JoomShop
//@title joom帐号列表
define('API_GET_joomShop','get|joom-shop');
//@title 拉取帐号对应的店铺数量；
define('API_GET_joomShop_accounts','get|joom-shop/accounts');
//@title 保存新建的资源
define('API_POST_joomShop','post|joom-shop');
//@title 显示指定的资源
define('API_GET_joomShop___id','get|joom-shop/:id');
//@title 显示编辑资源表单页.
define('API_GET_joomShop___id_edit','get|joom-shop/:id/edit');
//@title 保存更新的资源
define('API_PUT_joomShop___id','put|joom-shop/:id');
//@title joom批量设置抓取参数；
define('API_POST_joomShop_set','post|joom-shop/set');
//@title JOOM店铺停用，启用
define('API_POST_joomShop_status','post|joom-shop/status');
//@title joom获取授权码code
define('API_POST_joomShop_authorCode','post|joom-shop/authorCode');
//@title joom获取Token
define('API_POST_joomShop_token','post|joom-shop/token');
//@title joom打开授权页面
define('API_POST_joomShop_authorization','post|joom-shop/authorization');

//控制器：app\purchase\controller\PurchaseParcelsRecords
//@title 接收未审核列表
define('API_GET_purchaseParcelsRecords','get|purchase-parcels-records');
//@title 批量删除未审核包裹明细
define('API_POST_purchaseParcelsRecords_batchDelete','post|purchase-parcels-records/batchDelete');

//控制器：app\publish\controller\JoomCategory
//@title 帐号店铺分类列表
define('API_GET_joomCategory','get|joom-category');
//@title 返回帐号店铺分类ID数组
define('API_POST_joomCategory_getcategory','post|joom-category/getcategory');
//@title 拿取Joom帐号
define('API_GET_joomCategory_accounts','get|joom-category/accounts');
//@title 拿取Joom帐号对应的店铺
define('API_GET_joomCategory_shops','get|joom-category/shops');
//@title 拿取商品分类
define('API_GET_joomCategory_category','get|joom-category/category');
//@title 设置账号店铺分类；
define('API_POST_joomCategory','post|joom-category');
//@title 设置账号店铺分类；
define('API_POST_joomCategory_del','post|joom-category/del');
//@title 根据产品ID返回能刊登的店铺；
define('API_GET_joomCategory_checkshops','get|joom-category/checkshops');

//控制器：app\publish\controller\JoomListing
//@title JoomListing在售下架列表
define('API_GET_joomlisting','get|joomlisting');
//@title 获取JoomListing列表里variant的数据；
define('API_GET_joomlisting_variant','get|joomlisting/variant');
//@title 获取Joomlisting销售员列表
define('API_GET_joomlisting_users','get|joomlisting/users');
//@title Joomlisting批量同步更新
define('API_POST_joomlisting_sync','post|joomlisting/sync');
//@title 产品上架和批量上架接口
define('API_POST_joomlisting_enable','post|joomlisting/enable');
//@title 产品下架和批量下架接口
define('API_POST_joomlisting_disable','post|joomlisting/disable');
//@title 变体上架和批量上架接口
define('API_POST_joomlisting_variantEnable','post|joomlisting/variantEnable');
//@title 变体下架和批量下架接口
define('API_POST_joomlisting_variantDisable','post|joomlisting/variantDisable');
//@title 获取Joomlisting操作日志
define('API_GET_joomlisting_logs','get|joomlisting/logs');
//@title 获取Joom刊登记录
define('API_GET_joomlisting_record','get|joomlisting/record');
//@title 删除Joom刊登出错的数据
define('API_GET_joomlisting_delrecord','get|joomlisting/delrecord');
//@title Joom记录里批量刊登数据
define('API_GET_joomlisting_publish','get|joomlisting/publish');

//控制器：app\publish\controller\JoomTagSearch
//@title Joom关键字标签搜索
define('API_GET_joomtagSearch','get|joomtag-search');

//控制器：app\index\controller\Ali1688Account
//@title 1688账号列表
define('API_GET_ali1688Account','get|ali1688-account');
//@title 查看
define('API_GET_ali1688Account___id','get|ali1688-account/:id');
//@title 新增
define('API_POST_ali1688Account','post|ali1688-account');
//@title 更新
define('API_PUT_ali1688Account___id','put|ali1688-account/:id');
//@title 启用停用
define('API_POST_ali1688Account_states','post|ali1688-account/states');
//@title 获取授
define('API_POST_ali1688Account_getAuthorCode','post|ali1688-account/getAuthorCode');
//@title 获取token
define('API_POST_ali1688Account_getToken','post|ali1688-account/getToken');
//@title 批量开启
define('API_POST_ali1688Account_batchSet','post|ali1688-account/batch-set');

//控制器：app\warehouse\controller\PackageNotCollection
//@title 未集包列表
define('API_GET_packageNotCollection','get|package-not-collection');
//@title 获取运输方式
define('API_GET_packageNotCollection_shipping','get|package-not-collection/shipping');
//@title 退回到待生成拣货单
define('API_POST_packageNotCollection_back','post|package-not-collection/back');

//控制器：app\api\controller\EbayNotification
//@title ebay 接收接口
define('API_POST_api_ebay_notification','post|api/ebay/notification');

//控制器：app\warehouse\controller\WaitForPacking
//@title 等待生成拣货单包裹列表
define('API_GET_waitForPacking','get|wait-for-packing');
//@title 获取运输方式
define('API_GET_waitForPacking_shipping','get|wait-for-packing/shipping');

//控制器：app\warehouse\controller\WaitForMakePicking
//@title 等待生成拣货单包裹列表
define('API_GET_waitForMakePicking','get|wait-for-make-picking');
//@title 等待生成拣货单包裹sku列表
define('API_GET_waitForMakePicking_sku','get|wait-for-make-picking/sku');
//@title 获取运输方式
define('API_GET_waitForMakePicking_shipping','get|wait-for-make-picking/shipping');
//@title 配货未符合生成拣货单包裹列表
define('API_GET_waitForMakePicking_notConforming','get|wait-for-make-picking/not-conforming');

//控制器：app\internalletter\controller\InternalLetter
//@title 发送站内信
define('API_POST_internalLetters','post|internal-letters');
//@title 发钉钉工作消息
define('API_GET_internalLetters_message','get|internal-letters/message');
//@title 保存到草稿箱
define('API_POST_internalLetters_draftbox','post|internal-letters/draftbox');
//@title 收件箱
define('API_GET_internalLetters_receivedLetters','get|internal-letters/received-letters');
//@title 发件箱
define('API_GET_internalLetters_sentLetter','get|internal-letters/sent-letter');
//@title 草稿箱
define('API_GET_internalLetters_draft','get|internal-letters/draft');
//@title 草稿编辑
define('API_GET_internalLetters_draftEdit','get|internal-letters/draft-edit');
//@title 草稿箱批量发送
define('API_POST_internalLetters_batchSend','post|internal-letters/batch-send');
//@title 草稿箱批量删除
define('API_DELETE_internalLetters_draftDelete','delete|internal-letters/draft-delete');
//@title 看收信
define('API_GET_internalLetters_viewLetter','get|internal-letters/view-letter');
//@title 看发信
define('API_GET_internalLetters_viewSentLetter','get|internal-letters/view-sent-letter');
//@title 设置已读
define('API_PUT_internalLetters_read','put|internal-letters/read');
//@title 全部已读
define('API_PUT_internalLetters_allRead','put|internal-letters/all-read');
//@title 看草稿
define('API_GET_internalLetters_viewDraft','get|internal-letters/view-draft');
//@title 删除收信
define('API_DELETE_internalLetters_deleteReceivedLetters','delete|internal-letters/delete-received-letters');
//@title 删除发信
define('API_POST_internalLetters_deletSentLetters','post|internal-letters/delet-sent-letters');
//@title 获取所有站内信类型
define('API_GET_internalLetters_type','get|internal-letters/type');
//@title 获取所有用户信息
define('API_GET_internalLetters_userInfo','get|internal-letters/user-info');
//@title 下载附件
define('API_GET_internalLetters_attachment','get|internal-letters/attachment');
//@title 新站内信通知
define('API_GET_internalLetters_notification','get|internal-letters/notification');
//@title 发送钉钉群消息
define('API_POST_internalLetters_chat','post|internal-letters/chat');
//@title 保存联系人模板
define('API_POST_internalLetters_templates','post|internal-letters/templates');
//@title 删除联系人模板
define('API_DELETE_internalLetters_templates','delete|internal-letters/templates');
//@title 获取联系人模板
define('API_GET_internalLetters_templates','get|internal-letters/templates');
//@title 获取联系人模板详情
define('API_GET_internalLetters_templates—detail','get|internal-letters/templates—detail');
//@title 搜索已添加用户
define('API_GET_internalLetters_userTemplates','get|internal-letters/user-templates');

//控制器：app\publish\controller\JoomAttr
//@title Joom商品颜色列表
define('API_GET_joomattr_color','get|joomattr/color');
//@title Joom商品尺寸列表
define('API_GET_joomattr_size','get|joomattr/size');

//控制器：app\publish\controller\Joom
//@title joom敏感货
define('API_GET_publish_joom_dangerousKind','get|publish/joom/dangerous-kind');
//@title joom导出
define('API_POST_publish_joom_download','post|publish/joom/download');
//@title 获取joom待刊登商品列表
define('API_GET_publish_joom_productList','get|publish/joom/productList');
//@title joom获取商品数据
define('API_GET_publish_joom_getData','get|publish/joom/getData');
//@title joom编辑修改
define('API_GET_publish_joom_edit_id___id_status___status','get|publish/joom/edit/id/:id/status/:status');
//@title joom新增刊登提交数据
define('API_POST_publish_joom_add','post|publish/joom/add');
//@title joom更新刊登提交数据
define('API_POST_publish_joom_update','post|publish/joom/update');

//控制器：app\publish\controller\AmazonTemplate
//@title amazon产品模板列表
define('API_GET_amazonTemplate_product','get|amazon-template/product');
//@title amazon分类模板列表
define('API_GET_amazonTemplate_category','get|amazon-template/category');
//@title amazon模板创建人和站点
define('API_GET_amazonTemplate___type_creator','get|amazon-template/:type/creator');
//@title amazon读取模板详情
define('API_GET_amazonTemplate___id','get|amazon-template/:id');
//@title amazon编辑模板详情
define('API_GET_amazonTemplate___id_edit','get|amazon-template/:id/edit');
//@title amazon启用停用模板
define('API_GET_amazonTemplate_status___id___enable','get|amazon-template/status/:id/:enable');
//@title amazon更新模板详情
define('API_PUT_amazonTemplate','put|amazon-template');
//@title amazon新增模板
define('API_POST_amazonTemplate','post|amazon-template');
//@title amazon产品元素列表
define('API_GET_amazonTemplate_productbase','get|amazon-template/productbase');
//@title amazon分类列表
define('API_GET_amazonTemplate_categorybase___site','get|amazon-template/categorybase/:site');
//@title amazon分类下所属元素列表
define('API_GET_amazonTemplate_categoryele','get|amazon-template/categoryele');
//@title amazon批量删除模板
define('API_GET_amazonTemplate_del','get|amazon-template/del');
//@title amazon更新数据；
define('API_GET_amazonTemplate_updateOldData','get|amazon-template/update-old-data');

//控制器：app\publish\controller\Collect
//@title 速卖通部门所有员工
define('API_GET_aliexpressUsers','get|aliexpress-users');
//@title 刊登数据采集列表
define('API_GET_publishCollectIndex','get|publish-collect-index');
//@title 添加采集
define('API_POST_publishCollectAdd','post|publish-collect-add');
//@title 认领
define('API_POST_publishCollectClaim','post|publish-collect-claim');
//@title 绑定本地商品
define('API_POST_publishCollectBindGoods','post|publish-collect-bind-goods');
//@title 刊登采集删除
define('API_POST_publishCollectDelete','post|publish-collect-delete');

//控制器：app\warehouse\controller\Report
//@title 仓上架统计
define('API_GET_warehouse_report_shelf','get|warehouse/report/shelf');
//@title 贴标统计
define('API_GET_warehouse_report_label','get|warehouse/report/label');
//@title 下架统计
define('API_GET_warehouse_report_picking','get|warehouse/report/picking');
//@title 集包统计
define('API_GET_warehouse_report_collection','get|warehouse/report/collection');
//@title 分拣统计
define('API_GET_warehouse_report_sorting','get|warehouse/report/sorting');
//@title 打包统计
define('API_GET_warehouse_report_packing','get|warehouse/report/packing');
//@title 拆包统计
define('API_GET_warehouse_report_unpack','get|warehouse/report/unpack');
//@title 拆包入库统计
define('API_GET_warehouse_report_unpackStore','get|warehouse/report/unpack-store');
//@title 出库交接统计
define('API_GET_warehouse_report_OutTransfer','get|warehouse/report/Out-transfer');
//@title 今日看板
define('API_GET_warehouse_report_today','get|warehouse/report/today');
//@title 今日看板仓库统计one
define('API_GET_warehouse_report_warehouseStatisticsOne','get|warehouse/report/warehouse-statistics-one');
//@title 今日看板仓库统计two
define('API_GET_warehouse_report_warehouseStatisticsTwo','get|warehouse/report/warehouse-statistics-two');
//@title 仓库绩效统计
define('API_GET_warehouse_report_capacityStatistics','get|warehouse/report/capacity-statistics');
//@title 报表导出
define('API_POST_warehouse_report_export','post|warehouse/report/export');

//控制器：app\order\controller\StockOrder
//@title 缺货订单列表
define('API_GET_stockOrders','get|stock-orders');
//@title execl字段信息
define('API_GET_stockOrders_exportTitle','get|stock-orders/export-title');
//@title 导出execl
define('API_POST_stockOrders_export','post|stock-orders/export');

//控制器：app\order\controller\ProvidersException
//@title 物流下单异常包裹列表
define('API_GET_providersException','get|providers-exception');
//@title 获取异常总条数
define('API_GET_providersException_total','get|providers-exception/total');
//@title 批量重跑申报规则
define('API_POST_providersException_batch_runningDeclare','post|providers-exception/batch/running-declare');
//@title execl字段信息
define('API_GET_providersException_exportTitle','get|providers-exception/export-title');
//@title 导出
define('API_POST_providersException_export','post|providers-exception/export');
//@title 批量作废
define('API_POST_providersException_batch_invalid','post|providers-exception/batch/invalid');
//@title 批量更换包裹号
define('API_POST_providersException_batch_changePackageNumber','post|providers-exception/batch/changePackageNumber');

//控制器：app\publish\controller\Pandao
//@title Pandao刊登记录提交刊登
define('API_POST_publish_pandao_pushQueue','post|publish/pandao/push-queue');
//@title Pandao操作日志
define('API_GET_publish_pandao_logs','get|publish/pandao/logs');
//@title Pandao同步listing
define('API_POST_publish_pandao_rsyncProduct','post|publish/pandao/rsync-product');
//@title Pandao批量下架
define('API_POST_publish_pandao_batchDisable','post|publish/pandao/batch-disable');
//@title Pandao批量上架
define('API_POST_publish_pandao_batchEnable','post|publish/pandao/batch-enable');
//@title Pandao删除刊登记录
define('API_DELETE_publish_pandao_delete','delete|publish/pandao/delete');
//@title Pandao编辑修改获取数据
define('API_GET_publish_pandao_edit_id___id_status___status','get|publish/pandao/edit/id/:id/status/:status');
//@title Pandao更新修改了的数据
define('API_POST_publish_pandao_update','post|publish/pandao/update');
//@title pandao获取商品数据
define('API_GET_publish_pandao_getdata','get|publish/pandao/getdata');
//@title Pandao新增刊登
define('API_POST_publish_pandao_add','post|publish/pandao/add');
//@title Pandao销售人员列表
define('API_GET_pandaoSellers','get|pandao-sellers');
//@title Pandao在售listing
define('API_GET_pandaoOnSelling','get|pandao-on-selling');
//@title Pandao停售listing
define('API_GET_pandaoSoldOut','get|pandao-sold-out');
//@title Pandao刊登记录
define('API_GET_pandaoPublishRecord','get|pandao-publish-record');
//@title Pandao待刊登商品列表
define('API_GET_publish_pandao_waitUpload','get|publish/pandao/wait-upload');
//@title 获取刊登账号
define('API_GET_publish_pandao_accounts','get|publish/pandao/accounts');
//@title 下载列表
define('API_POST_publish_pandao_export','post|publish/pandao/export');

//控制器：app\index\controller\PandaoAccount
//@title pandao账号列表
define('API_GET_pandaoAccount','get|pandao-account');
//@title 添加账号
define('API_POST_pandaoAccount_add','post|pandao-account/add');
//@title  pandao账号授权
define('API_POST_pandaoAccount_authorization','post|pandao-account/authorization');
//@title 查看账号
define('API_GET_pandaoAccount___id','get|pandao-account/:id');
//@title 编辑账号
define('API_GET_pandaoAccount___id_edit','get|pandao-account/:id/edit');
//@title 更新账号
define('API_POST_pandaoAccount_update','post|pandao-account/update');
//@title 停用，启用账号
define('API_POST_pandaoAccount_states','post|pandao-account/states');
//@title 批量开启
define('API_POST_pandaoAccount_batchSet','post|pandao-account/batch-set');

//控制器：app\index\controller\ExportTemplate
//@title 获取我的模板
define('API_GET_exportTemplate','get|export-template');
//@title 获取导出模板详情
define('API_GET_exportTemplate___id','get|export-template/:id');
//@title 保存模板
define('API_POST_exportTemplate','post|export-template');
//@title 删除导出模板
define('API_DELETE_exportTemplate___id','delete|export-template/:id');

//控制器：app\warehouse\controller\RebackShelvesOrder
//@title 退货上架单列表
define('API_GET_rebackShelvesOrder','get|reback-shelves-order');
//@title 生成上架单
define('API_POST_rebackShelvesOrder','post|reback-shelves-order');
//@title 验证退货上架数量
define('API_GET_rebackShelvesOrder_quantity','get|reback-shelves-order/quantity');
//@title 作废退货上架单
define('API_DELETE_rebackShelvesOrder','delete|reback-shelves-order');
//@title 查看退货上架单
define('API_GET_rebackShelvesOrder___id','get|reback-shelves-order/:id');
//@title 操作退货上架
define('API_PUT_rebackShelvesOrder___id','put|reback-shelves-order/:id');
//@title 完成退货上架单
define('API_PUT_rebackShelvesOrder_finish','put|reback-shelves-order/finish');
//@title 强制完成退货上架单
define('API_PUT_rebackShelvesOrder_force','put|reback-shelves-order/force');

//控制器：app\index\controller\ShopeeAccount
//@title 获取shopee账户列表
define('API_GET_shopeeAccount','get|shopee-account');
//@title 获取shopee账户详情
define('API_GET_shopeeAccount___id','get|shopee-account/:id');
//@title 保存shopee账户详情
define('API_POST_shopeeAccount','post|shopee-account');
//@title 保存shopee账户授权
define('API_PUT_shopeeAccount_saveToken','put|shopee-account/save-token');
//@title 系统状态切换
define('API_POST_shopeeAccount_changeStatus','post|shopee-account/change-status');
//@title 获取站点
define('API_GET_shopeeAccount_site','get|shopee-account/site');
//@title 获取账号
define('API_GET_shopeeAccount_account','get|shopee-account/account');
//@title 批量开启
define('API_POST_shopeeAccount_batchSet','post|shopee-account/batch-set');

//控制器：app\customerservice\controller\EmailAccount
//@title 获取邮箱账号列表
define('API_GET_emailAccount','get|email-account');
//@title 查看邮箱账号
define('API_GET_emailAccount___id','get|email-account/:id');
//@title 添加邮箱账号
define('API_POST_emailAccount','post|email-account');
//@title 添加邮箱账号
define('API_PUT_emailAccount___email_account_id','put|email-account/:email_account_id');
//@title 删除指定邮箱账号
define('API_DELETE_emailAccount','delete|email-account');
//@title 获取指定邮箱的log
define('API_GET_emailAccount_log___email_account_id','get|email-account/log/:email_account_id');
//@title 设置邮箱是否启用
define('API_PUT_emailAccount___email_account_id_enabled','put|email-account/:email_account_id/enabled');
//@title 不过滤获取平台/站点账号简称
define('API_GET_emailAccount_account','get|email-account/account');

//控制器：app\customerservice\controller\EbaySentEmail
//@title 查询Ebay邮件列表
define('API_GET_ebayEmails_sentList','get|ebay-emails/sent-list');
//@title Ebay发送邮件
define('API_POST_ebayEmails_send','post|ebay-emails/send');
//@title 回复Ebay邮件
define('API_POST_ebayEmails_reply','post|ebay-emails/reply');
//@title Ebay失败邮件重新发送
define('API_POST_ebayEmails_resend___mail_id','post|ebay-emails/resend/:mail_id');
//@title Ebay单号获取帐号邮箱
define('API_GET_ebayEmails_getBuyerInfo','get|ebay-emails/getBuyerInfo');
//@title 获取单账号客服列表
define('API_GET_ebayEmails_account_customers','get|ebay-emails/account/customers');

//控制器：app\customerservice\controller\MessageTransfer
//@title 站内信待处理列表
define('API_GET_messageTransfer','get|message-transfer');
//@title 帐号未处理信息条数；
define('API_GET_messageTransfer_accountTotal','get|message-transfer/account-total');
//@title 转发站内信
define('API_POST_messageTransfer_transfer','post|message-transfer/transfer');
//@title 转派记录
define('API_GET_messageTransfer_record','get|message-transfer/record');
//@title 转派操作人
define('API_GET_messageTransfer_creator','get|message-transfer/creator');

//控制器：app\index\controller\LazadaAccount
//@title 显示资源列表
define('API_GET_lazadaAccount','get|lazada-account');
//@title 保存新建的资源
define('API_POST_lazadaAccount','post|lazada-account');
//@title 显示指定的资源
define('API_GET_lazadaAccount___id','get|lazada-account/:id');
//@title 显示编辑资源表单页.
define('API_GET_lazadaAccount___id_edit','get|lazada-account/:id/edit');
//@title 保存更新的资源
define('API_PUT_lazadaAccount___id','put|lazada-account/:id');
//@title 停用，启用账号
define('API_POST_lazadaAccount_states','post|lazada-account/states');
//@title 获取授权码
define('API_POST_lazadaAccount_authorcode','post|lazada-account/authorcode');
//@title 查询lazada账号
define('API_GET_lazadaAccount_query','get|lazada-account/query');
//@title 获取Token
define('API_POST_lazadaAccount_token','post|lazada-account/token');
//@title 获取Token
define('API_GET_lazadaAccount_refresh_token___id','get|lazada-account/refresh_token/:id');
//@title 授权页面
define('API_POST_lazadaAccount_authorization','post|lazada-account/authorization');
//@title 获取Lazada站点
define('API_GET_lazada_site','get|lazada/site');
//@title 批量修改账号的抓取状态
define('API_POST_lazadaAccount_update_download','post|lazada-account/update_download');

//控制器：app\api\controller\AccountHealt
//@title wish接收帐号健康数据
define('API_POST_api_healthReceive_wish___id','post|api/health-receive/wish/:id');
//@title 速卖通接收帐号健康数据
define('API_POST_api_healthReceive_aliexpress___id','post|api/health-receive/aliexpress/:id');
//@title ebay接收帐号健康数据
define('API_POST_api_healthReceive_ebay_','post|api/health-receive/ebay/');
//@title amazon接收帐号健康数据
define('API_POST_api_healthReceive_amazon___id','post|api/health-receive/amazon/:id');

//控制器：app\index\controller\WishAccountHealth
//@title 查看列表
define('API_GET_wishAccountHealth','get|wish-account-health');
//@title 帐号筛选列表
define('API_GET_wishAccountHealth_account','get|wish-account-health/account');
//@title 导出列表
define('API_GET_wishAccountHealth_export','get|wish-account-health/export');
//@title 查看历史数据；
define('API_GET_wishAccountHealth___wish_account_id_history','get|wish-account-health/:wish_account_id/history');
//@title 查看付款记录；
define('API_GET_wishAccountHealth___wish_account_id_payment','get|wish-account-health/:wish_account_id/payment');
//@title 批量设置监控值
define('API_POST_wishAccountHealth','post|wish-account-health');
//@title 单个设置监控值
define('API_PUT_wishAccountHealth','put|wish-account-health');
//@title 立即抓取
define('API_POST_wishAccountHealth_repitle','post|wish-account-health/repitle');
//@title 读取wish帐号目标率
define('API_GET_wishAccountHealth___wish_account_id_goal','get|wish-account-health/:wish_account_id/goal');

//控制器：app\index\controller\AliexpressAccountHealth
//@title 查看列表
define('API_GET_aliexpressAccountHealth','get|aliexpress-account-health');
//@title 帐号筛选列表
define('API_GET_aliexpressAccountHealth_account','get|aliexpress-account-health/account');
//@title 导出列表
define('API_GET_aliexpressAccountHealth_export','get|aliexpress-account-health/export');
//@title 查看历史数据；
define('API_GET_aliexpressAccountHealth___account_id_history','get|aliexpress-account-health/:account_id/history');
//@title 查看付款记录；
define('API_GET_aliexpressAccountHealth___account_id___type_payment','get|aliexpress-account-health/:account_id/:type/payment');
//@title 批量设置监控值
define('API_POST_aliexpressAccountHealth','post|aliexpress-account-health');
//@title 单个设置监控值
define('API_PUT_aliexpressAccountHealth','put|aliexpress-account-health');
//@title 立即抓取
define('API_POST_aliexpressAccountHealth_repitle','post|aliexpress-account-health/repitle');
//@title 读取aliexpress帐号目标率
define('API_GET_aliexpressAccountHealth___account_id_goal','get|aliexpress-account-health/:account_id/goal');

//控制器：app\warehouse\controller\WarehouseGoodsForecast
//@title 产品预报列表
define('API_GET_warehouseGoodsForecast','get|warehouse-goods-forecast');
//@title 新增产品预报
define('API_POST_warehouseGoodsForecast','post|warehouse-goods-forecast');
//@title 分区详情
define('API_GET_warehouseGoodsForecast___id','get|warehouse-goods-forecast/:id');
//@title 第三方产品分类
define('API_GET_warehouseGoodsForecast_category','get|warehouse-goods-forecast/category');
//@title 预报状态
define('API_GET_warehouseGoodsForecast_status','get|warehouse-goods-forecast/status');
//@title 选择添加sku
define('API_GET_warehouseGoodsForecast_getGoods','get|warehouse-goods-forecast/get-goods');
//@title 关联
define('API_POST_warehouseGoodsForecast_relateSku','post|warehouse-goods-forecast/relate-sku');

//控制器：app\order\controller\JoomOrder
//@title 订单列表
define('API_GET_joomOrders','get|joom-orders');
//@title 查看
define('API_GET_joomOrders___id','get|joom-orders/:id');
//@title 取订单各状态的总数
define('API_GET_joomOrders_statusCount','get|joom-orders/status-count');
//@title 取账户
define('API_GET_joomOrders_accounts','get|joom-orders/accounts');
//@title 取店铺列表
define('API_POST_joomOrders_stores','post|joom-orders/stores');
//@title 获取账号列表
define('API_POST_joomOrders_accountNames','post|joom-orders/account-names');
//@title 检查订单是否存在
define('API_POST_joomOrders_check','post|joom-orders/check');
//@title joom订单导出
define('API_GET_joomOrders_export','get|joom-orders/export');

//控制器：app\order\controller\PandaoOrder
//@title 订单列表
define('API_GET_pandaoOrders','get|pandao-orders');
//@title 查看
define('API_GET_pandaoOrders___id','get|pandao-orders/:id');
//@title 取订单各状态的总数
define('API_GET_pandaoOrders_statusCount','get|pandao-orders/status-count');
//@title 取账户
define('API_GET_pandaoOrders_accounts','get|pandao-orders/accounts');
//@title 检查订单是否存在
define('API_POST_pandaoOrders_check','post|pandao-orders/check');
//@title mymall订单导出
define('API_POST_pandaoOrders_export','post|pandao-orders/export');
//@title 获取所有导出字段
define('API_GET_pandaoOrders_exportFields','get|pandao-orders/export-fields');

//控制器：app\order\controller\PaytmOrder
//@title 订单列表
define('API_GET_paytmOrders','get|paytm-orders');
//@title 查看
define('API_GET_paytmOrders___id','get|paytm-orders/:id');
//@title 取订单各状态的总数
define('API_GET_paytmOrders_statusCount','get|paytm-orders/status-count');
//@title 取账户
define('API_GET_paytmOrders_accounts','get|paytm-orders/accounts');
//@title 检查订单是否存在
define('API_POST_paytmOrders_check','post|paytm-orders/check');

//控制器：app\order\controller\Shopee
//@title Shopee列表
define('API_GET_shopeeOrder','get|shopee-order');
//@title Shopee获取数量
define('API_GET_shopeeOrder_getCount','get|shopee-order/get-count');
//@title Shopee详情
define('API_GET_shopeeOrder___id','get|shopee-order/:id');
//@title 下载订单
define('API_GET_shopeeOrder_refOne','get|shopee-order/ref-one');
//@title 检测shopee单号是否已经存在
define('API_POST_shopeeOrder_checkOrderSn','post|shopee-order/check-order-sn');
//@title shopee测试推送到系统订单
define('API_GET_shopeeOrder_selfPush','get|shopee-order/self-push');

//控制器：app\order\controller\Lazada
//@title 显示资源列表
define('API_GET_lazadaOrders','get|lazada-orders');
//@title 显示指定的资源
define('API_GET_lazadaOrders___id','get|lazada-orders/:id');
//@title 获取状态列表
define('API_GET_lazadaOrders_status','get|lazada-orders/status');
//@title 审核平台订单
define('API_POST_lazadaOrders_check','post|lazada-orders/check');
//@title  单独漏掉的lazada订单
define('API_GET_lazadaOrders_getOneOrder','get|lazada-orders/getOneOrder');
//@title  按账号单独拉取漏掉的lazada订单
define('API_POST_lazadaOrders_getAllOrder','post|lazada-orders/getAllOrder');
//@title  单独漏掉的lazada订单
define('API_GET_lazadaOrders_toLocal','get|lazada-orders/toLocal');
//@title  删除lazada订单
define('API_GET_lazadaOrders_deleteId','get|lazada-orders/delete-id');
//@title lazada订单导出
define('API_POST_lazadaOrders_export','post|lazada-orders/export');
//@title 获取所有导出字段
define('API_GET_lazadaOrders_exportFields','get|lazada-orders/export-fields');

//控制器：app\index\controller\PaytmAccount
//@title paytm账号列表
define('API_GET_paytmAccount','get|paytm-account');
//@title 添加账号
define('API_POST_paytmAccount','post|paytm-account');
//@title 更新账号
define('API_PUT_paytmAccount','put|paytm-account');
//@title 查看账号
define('API_GET_paytmAccount___id','get|paytm-account/:id');
//@title 获取订单授权信息
define('API_GET_paytmAccount_token___id','get|paytm-account/token/:id');
//@title  paytm订单账号授权
define('API_PUT_paytmAccount_token','put|paytm-account/token');
//@title  paytm商品账号授权
define('API_PUT_paytmAccount_tokencat','put|paytm-account/tokencat');
//@title 停用，启用账号
define('API_POST_paytmAccount_states','post|paytm-account/states');
//@title 批量开启
define('API_POST_paytmAccount_batchSet','post|paytm-account/batch-set');

//控制器：app\publish\controller\Shopee
//@title shopee批量修改
define('API_POST_shopeeBatchSetting','post|shopee-batch-setting');
//@title shopee编辑折扣折扣
define('API_GET_shopeeDiscountEdit','get|shopee-discount-edit');
//@title shopee添加折扣
define('API_POST_shopeeDiscountAdd','post|shopee-discount-add');
//@title shopee折扣列表
define('API_GET_shopeeDiscount','get|shopee-discount');
//@title shopee刊登记录提交刊登
define('API_POST_publish_shopee_pushQueue','post|publish/shopee/push-queue');
//@title shopee操作日志
define('API_GET_publish_shopee_logs','get|publish/shopee/logs');
//@title shopee同步listing
define('API_POST_publish_shopee_rsyncProduct','post|publish/shopee/rsync-product');
//@title shopee批量下架
define('API_PUT_shopee_delItem_batch','put|shopee/del-item/batch');
//@title shopee批量上架
define('API_POST_publish_shopee_batchEnable','post|publish/shopee/batch-enable');
//@title shopee删除刊登记录
define('API_DELETE_publish_shopee_delete','delete|publish/shopee/delete');
//@title shopee编辑修改获取数据
define('API_GET_shopee___id___status','get|shopee/:id/:status');
//@title shopee更新修改了的数据
define('API_POST_publish_shopee_update','post|publish/shopee/update');
//@title shopee获取商品数据
define('API_GET_publish_shopee_getdata','get|publish/shopee/getdata');
//@title shopee新增刊登
define('API_POST_publish_shopee_add','post|publish/shopee/add');
//@title shopee销售人员列表
define('API_GET_shopeeSellers','get|shopee-sellers');
//@title shopee当前登录用户管理账号
define('API_GET_publish_shopee_accounts','get|publish/shopee/accounts');
//@title shopee在售listing
define('API_GET_shopeeOnSelling','get|shopee-on-selling');
//@title shopee停售listing
define('API_GET_shopeeStopSelling','get|shopee-stop-selling');
//@title shopee停售listing
define('API_GET_shopeeSoldOut','get|shopee-sold-out');
//@title shopee刊登记录
define('API_GET_shopeePublishRecord','get|shopee-publish-record');
//@title shopee待刊登商品列表
define('API_GET_publish_shopee_waitUpload','get|publish/shopee/wait-upload');
//@title shopee分类
define('API_GET_publish_shopee_category','get|publish/shopee/category');
//@title shopee分类属性
define('API_GET_publish_shopee_attribute','get|publish/shopee/attribute');
//@title shopee物流信息
define('API_GET_publish_shopee_logistics','get|publish/shopee/logistics');
//@title 同步账号物流设置
define('API_PUT_shopee___account_id_syncLogistics','put|shopee/:account_id/sync-logistics');
//@title shopee获取产品的本地spu列表
define('API_GET_publish_shopee_variant','get|publish/shopee/variant');
define('API_GET_shopee_queue','get|publish/shopee/queue');//测试

//控制器：app\order\controller\WalmartOrder
//@title 订单列表
define('API_GET_walmartOrders','get|walmart-orders');
//@title 查看
define('API_GET_walmartOrders___id','get|walmart-orders/:id');
//@title 取订单各状态的总数
define('API_GET_walmartOrders_statusCount','get|walmart-orders/status-count');
//@title 检查订单是否存在
define('API_POST_walmartOrders_check','post|walmart-orders/check');

//控制器：app\index\controller\WalmartAccount
//@title walmart账号列表
define('API_GET_walmartAccount','get|walmart-account');
//@title 添加账号
define('API_POST_walmartAccount','post|walmart-account');
//@title 更新账号
define('API_PUT_walmartAccount','put|walmart-account');
//@title 查看账号
define('API_GET_walmartAccount___id','get|walmart-account/:id');
//@title 获取walmart站点
define('API_GET_walmart_site','get|walmart/site');
//@title 获取订单授权信息
define('API_GET_walmartAccount_token___id','get|walmart-account/token/:id');
//@title  walmart订单账号授权
define('API_PUT_walmartAccount_token','put|walmart-account/token');
//@title 停用，启用账号
define('API_POST_walmartAccount_states','post|walmart-account/states');
//@title 批量开启
define('API_POST_walmartAccount_batchSet','post|walmart-account/batch-set');

//控制器：app\publish\controller\AmazonPublishDraft
//@title amazon刊登草稿箱列表；
define('API_GET_publish_amazon_draft','get|publish/amazon/draft');
//@title amazon刊登草稿去编辑；
define('API_GET_publish_amazon___id_draft','get|publish/amazon/:id/draft');
//@title amazon刊登草稿保存；
define('API_POST_publish_amazon_draft','post|publish/amazon/draft');
//@title amazon刊登草稿更新；
define('API_PUT_publish_amazon_draft','put|publish/amazon/draft');
//@title amazon刊登草稿删除；
define('API_DELETE_publish_amazon_draft','delete|publish/amazon/draft');

//控制器：app\publish\controller\Export
//@title SPU在各个平台的已刊登数量报表导出
define('API_POST_publishTimeStatisticExport','post|publish-time-statistic-export');
//@title SPU在各个平台的已刊登数量
define('API_GET_publishTimeStatistic','get|publish-time-statistic');
//@title 刊登报表导出
define('API_POST_publishStatisticExport','post|publish-statistic-export');
//@title spu刊登统计
define('API_GET_publishStatistic','get|publish-statistic');
//@title 刊登部分导出
define('API_POST_publishExport','post|publish-export');
//@title 刊登全部导出
define('API_POST_publishExportAll','post|publish-export-all');
//@title 刊登报表导出字段
define('API_GET_publishExportFields','get|publish-export-fields');

//控制器：app\warehouse\controller\StockLack
//@title 缺货列表
define('API_GET_stockLack','get|stock-lack');
//@title 导出
define('API_POST_stockLack_export','post|stock-lack/export');

//控制器：app\order\controller\CheckOrder
//@title Excel检查订单
define('API_POST_checkOrders___type','post|check-orders/:type');

//控制器：app\index\controller\JumiaAccount
//@title jumia账号列表
define('API_GET_jumiaAccount','get|jumia-account');
//@title 添加账号
define('API_POST_jumiaAccount','post|jumia-account');
//@title 更新账号
define('API_PUT_jumiaAccount','put|jumia-account');
//@title 保存授权信息
define('API_PUT_jumiaAccount_saveToken','put|jumia-account/save-token');
//@title 查看账号
define('API_GET_jumiaAccount___id','get|jumia-account/:id');
//@title 停用，启用账号
define('API_POST_jumiaAccount_states','post|jumia-account/states');
//@title 批量开启
define('API_POST_jumiaAccount_batchSet','post|jumia-account/batch-set');

//控制器：app\order\controller\JumiaOrder
//@title 订单列表
define('API_GET_jumiaOrders','get|jumia-orders');
//@title 查看
define('API_GET_jumiaOrders___id','get|jumia-orders/:id');
//@title 取订单各状态的总数
define('API_GET_jumiaOrders_statusCount','get|jumia-orders/status-count');
//@title 检查订单是否存在
define('API_POST_jumiaOrders_check','post|jumia-orders/check');

//控制器：app\goods\controller\Download
//@title 导出商品到shopee平台
define('API_POST_goods_download_shopee','post|goods/download/shopee');
//@title 导出商品到discount平台
define('API_POST_goods_download_discount','post|goods/download/discount');
//@title 导出商品到walmart平台
define('API_POST_goods_download_walmart','post|goods/download/walmart');
//@title 导出商品到lazada平台
define('API_POST_goods_download_lazada','post|goods/download/lazada');

//控制器：app\index\controller\CdAccount
//@title cd账号列表
define('API_GET_cdAccount','get|cd-account');
//@title 添加账号
define('API_POST_cdAccount','post|cd-account');
//@title 更新账号
define('API_PUT_cdAccount','put|cd-account');
//@title 查看账号
define('API_GET_cdAccount___id','get|cd-account/:id');
//@title 获取订单授权信息
define('API_GET_cdAccount_token___id','get|cd-account/token/:id');
//@title  cd订单账号授权
define('API_PUT_cdAccount_token','put|cd-account/token');
//@title 停用，启用账号
define('API_POST_cdAccount_states','post|cd-account/states');
//@title 验证账号
define('API_POST_cdAccount_check','post|cd-account/check');
//@title 批量开启
define('API_POST_cdAccount_batchSet','post|cd-account/batch-set');

//控制器：app\order\controller\CdOrder
//@title 订单列表
define('API_GET_cdOrders','get|cd-orders');
//@title 查看
define('API_GET_cdOrders___id','get|cd-orders/:id');
//@title 取订单各状态的总数
define('API_GET_cdOrders_statusCount','get|cd-orders/status-count');
//@title 取账户
define('API_GET_cdOrders_accounts','get|cd-orders/accounts');
//@title 检查订单是否存在
define('API_POST_cdOrders_check','post|cd-orders/check');

//控制器：app\index\controller\EbayAccountHealth
//@title 查看列表
define('API_GET_ebayAccountHealth','get|ebay-account-health');
//@title 获取指定设置
define('API_GET_ebayAccountHealth_setting___account_id___region','get|ebay-account-health/setting/:account_id/:region');
//@title 设置监测阈值
define('API_POST_ebayAccountHealth_setting_batch','post|ebay-account-health/setting/batch');
//@title 立即执行一次抓取
define('API_POST_ebayAccountHealth_sync_batch','post|ebay-account-health/sync/batch');
//@title 导出数据
define('API_GET_ebayAccountHealth_export','get|ebay-account-health/export');
//@title 获取有权限的账号
define('API_GET_ebayAccountHealth_accounts','get|ebay-account-health/accounts');

//控制器：app\report\controller\ExpressConfirm
//@title 快递确认单列表
define('API_GET_report_expressConfirm','get|report/express-confirm');
//@title execl字段信息
define('API_GET_report_expressConfirm_exportTitle','get|report/express-confirm/export-title');
//@title 导出
define('API_POST_report_expressConfirm_export','post|report/express-confirm/export');
//@title 汇总导出
define('API_POST_report_expressConfirm_exports','post|report/express-confirm/exports');

//控制器：app\index\controller\AccountApply
//@title 显示资源列表
define('API_GET_accountApply','get|account-apply');
//@title 保存新建的资源
define('API_POST_accountApply','post|account-apply');
//@title 显示指定的资源
define('API_GET_accountApply___id','get|account-apply/:id');
//@title 显示编辑资源表单页.
define('API_GET_accountApply___id_edit','get|account-apply/:id/edit');
//@title 保存更新[基本资料]
define('API_PUT_accountApply___id','put|account-apply/:id');
//@title 保存更新[注册信息]
define('API_PUT_accountApply___id_register','put|account-apply/:id/register');
//@title 保存更新[审核]
define('API_PUT_accountApply___id_audit','put|account-apply/:id/audit');
//@title 保存更新[作废]
define('API_PUT_accountApply___id_cancellation','put|account-apply/:id/cancellation');
//@title 保存更新[注册结果]
define('API_PUT_accountApply___id_result','put|account-apply/:id/result');
//@title 更改账号状态
define('API_POST_accountApply_batch___type','post|account-apply/batch/:type');
//@title 显示密码
define('API_GET_accountApply_password','get|account-apply/password');
//@title 服务器已绑定的账号列表
define('API_GET_accountApply_alreadyBind','get|account-apply/already-bind');
//@title 自动识别图片
define('API_GET_accountApply_automatic','get|account-apply/automatic');
//@title 日志
define('API_GET_accountApply___id_log','get|account-apply/:id/log');
//@title 读取运营负责人
define('API_GET_accountApply_user','get|account-apply/user');
//@title 获取状态
define('API_GET_accountApply_status','get|account-apply/status');

//控制器：app\publish\controller\EbayCtrl
//@title 获取推荐的分类
define('API_GET_ebay_suggestedCategories','get|ebay/suggested-categories');
//@title 获取范本/listing店铺分类
define('API_GET_ebay_dlStoreCategory_batch','get|ebay/dl-store-category/batch');
//@title 获取指定账号指定店铺分类的分类链
define('API_GET_ebay_storeCategoryChain___store_category_id___account_id','get|ebay/store-category-chain/:store_category_id/:account_id');
//@title 更新listing店铺分类
define('API_POST_ebay_listingStoreCategory_batch','post|ebay/listing-store-category/batch');
//@title 批量修改范本店铺分类
define('API_PUT_ebay_draftStoreCategory_batch','put|ebay/draft-store-category/batch');
//@title 批量获取listing/范本主图
define('API_GET_ebay_dlMainImgs_batch','get|ebay/dl-main-imgs/batch');
//@title 批量在线更新listing主图
define('API_POST_ebay_listingMainImgs_batch','post|ebay/listing-main-imgs/batch');
//@title 批量切换站点设置账号
define('API_POST_ebay_changeSite_batch','post|ebay/change-site/batch');
//@title 批量将listing成本价更改为调整后的价格
define('API_PUT_ebay_adjustPrice_batch','put|ebay/adjust-price/batch');
//@title 批量修改范本拍卖刊登天数
define('API_PUT_ebay_dChineseListingDuration_batch','put|ebay/d-chinese-listing-duration/batch');
//@title 翻译
define('API_POST_ebay_translate_batch','post|ebay/translate/batch');
//@title 获取标题库列表
define('API_GET_publish_ebay_titles','get|publish/ebay/titles');
//@title 获取指定商品标题库详情
define('API_GET_publish_ebay_titles___goods_id','get|publish/ebay/titles/:goods_id');
//@title 批量获取商品标题库详情
define('API_GET_publish_ebay_titles_batch','get|publish/ebay/titles/batch');
//@title 保存单条标题库详情
define('API_PUT_publish_ebay_titles___goods_id','put|publish/ebay/titles/:goods_id');
//@title 批量保存商品标题库详情
define('API_PUT_publish_ebay_titles_batch','put|publish/ebay/titles/batch');
//@title 对范本标题随机排序
define('API_PUT_publish_ebay_draftTitle_random','put|publish/ebay/draft-title/random');
//@title 复制listing并更改账号
define('API_POST_publish_ebay_copyListing','post|publish/ebay/copy-listing');
//@title 批量检测刊登
define('API_POST_publishEbay_checkPublish_batch','post|publish-ebay/check-publish/batch');
//@title 批量删除
define('API_DELETE_publishEbay_deleteListing_batch','delete|publish-ebay/delete-listing/batch');
//@title 一键展开变体
define('API_GET_publishEbay_spreadVariants_batch','get|publish-ebay/spread-variants/batch');
//@title 队列刊登
define('API_POST_publishEbay_publishQueue_batch','post|publish-ebay/publish-queue/batch');
//@title 批量设置账号
define('API_POST_publishEbay_listingAccount_batch','post|publish-ebay/listing-account/batch');
//@title 批量修改一口价及可售量
define('API_POST_publishEbay_fixedPriceQty_batch','post|publish-ebay/fixed-price-qty/batch');
//@title 批量修改拍卖价
define('API_POST_publishEbay_chinesePrice_batch','post|publish-ebay/chinese-price/batch');
//@title 批量修改标题
define('API_POST_publishEbay_listingTitle_batch','post|publish-ebay/listing-title/batch');
//@title 批量修改商店分类
define('API_POST_publishEbay_listingStoreCategory_batch','post|publish-ebay/listing-store-category/batch');
//@title 批量获取刊登图
define('API_GET_publishEbay_publishImgs_batch','get|publish-ebay/publish-imgs/batch');
//@title 批量设置刊登图
define('API_POST_publishEbay_publishImgs_batch','post|publish-ebay/publish-imgs/batch');
//@title 批量设置平台分类属性
define('API_POST_publishEbay_specifics_batch','post|publish-ebay/specifics/batch');
//@title 批量设置一口价刊登天数
define('API_POST_publishEbay_listingDuration_batch','post|publish-ebay/listing-duration/batch');
//@title 批量应用公共模块
define('API_POST_publishEbay_applyCommonModule_batch','post|publish-ebay/apply-common-module/batch');
//@title 立即刊登保存
define('API_POST_publishEbay_publishImmediatelySave','post|publish-ebay/publish-immediately-save');
//@title 立即刊登
define('API_POST_publishEbay_publishImmediately','post|publish-ebay/publish-immediately');
//@title 立即刊登结果查询
define('API_GET_publishEbay_publishImmediatelyResult','get|publish-ebay/publish-immediately-result');
//@title 批量设置自动补货
define('API_POST_publishEbay_replenish_batch','post|publish-ebay/replenish/batch');
//@title 获取标题库关键词库
define('API_GET_title_suggestWord','get|title/suggest-word');
//@title 通过导入方式在线更新listing
define('API_POST_publishEbay_updateListing_import','post|publish-ebay/update-listing/import');
//@title 拉取指定item id的listing
define('API_POST_publishEbay_pullListing','post|publish-ebay/pull-listing');
//@title 设置虚拟仓发货
define('API_POST_publishEbay_virtualSend','post|publish-ebay/virtual-send');
//@title 在线listing数据导出
define('API_GET_publishEbay_onlineExport','get|publish-ebay/online-export');
//@title 取消定时或队列刊登
define('API_POST_publishEbay_cancelQueuePublish','post|publish-ebay/cancel-queue-publish');
//@title 修改在线数据导出
define('API_GET_publishEbay_onlineExportModify','get|publish-ebay/online-export-modify');
//@title 获取范本信息
define('API_GET_publishEbay_draft','get|publish-ebay/draft');
//@title 设置范本
define('API_POST_publishEbay_draft','post|publish-ebay/draft');
//@title 范本列表
define('API_GET_publishEbay_drafts','get|publish-ebay/drafts');
//@title ebay测试
define('API_POST_ebay_test','post|ebay/test');
//@title 复制范本转站点账号
define('API_POST_publishEbay_changeSiteFromDraft_batch','post|publish-ebay/change-site-from-draft/batch');
//@title 在线spu统计导出
define('API_GET_publishEbay_onlineSpu_export','get|publish-ebay/online-spu/export');

//控制器：app\publish\controller\AmazonPublishDoc
//@title amazon范本列表；
define('API_GET_publish_amazon_doc','get|publish/amazon/doc');
//@title amazon未写范本列表；
define('API_GET_publish_amazon_undoc','get|publish/amazon/undoc');
//@title amazon范本创建人；
define('API_GET_publish_amazon_docCreator','get|publish/amazon/doc-creator');
//@title amazon范本删除；
define('API_GET_publish_amazon_docDel','get|publish/amazon/doc-del');
//@title amazon范本新增编辑基础；
define('API_GET_publish_amazon_docBaseField','get|publish/amazon/doc-base-field');
//@title amazon范本新增；
define('API_GET_publish_amazon_docSiteField','get|publish/amazon/doc-site-field');
//@title amazon范本编辑和复制；
define('API_GET_publish_amazon_docEditField','get|publish/amazon/doc-edit-field');
//@title amazon范本保存；
define('API_POST_publish_amazon_docSave','post|publish/amazon/doc-save');

//控制器：app\progress\controller\Progress
//@title 需求管理首页
define('API_GET_progress','get|progress');
//@title 新增需求
define('API_POST_progress_add','post|progress/add');
//@title 更新需求
define('API_POST_progressUpdate','post|progress-update');
//@title 更新需求状态
define('API_POST_progress_updateStatus','post|progress/update-status');
//@title 需求删除
define('API_DELETE_progressDelete','delete|progress-delete');
//@title 需求管理获取用户角色
define('API_GET_progressPermission','get|progress-permission');

//控制器：app\warehouse\controller\PickingException
//@title 拣货异常列表
define('API_GET_pickingsException_exception','get|pickings-exception/exception');
//@title 拣货异常详情
define('API_GET_pickingsException_exceptionDetail','get|pickings-exception/exception-detail');
//@title 异常拣货批量处理
define('API_POST_pickingsException_batchProcessing','post|pickings-exception/batch-processing');
//@title 异常拣货单sku 创建盘点单
define('API_POST_pickingsException_goodsCheck','post|pickings-exception/goods-check');

//控制器：app\warehouse\controller\Collector
//@title 揽收商列表
define('API_GET_collector','get|collector');
//@title 添加物流商信息
define('API_POST_collector','post|collector');
//@title 显示指定物流商资源
define('API_GET_collector___id','get|collector/:id');
//@title 保存更新的分区
define('API_PUT_collector___id','put|collector/:id');
//@title 状态更新
define('API_PUT_collector___id_status','put|collector/:id/status');
//@title 邮寄方式列表
define('API_GET_collector___id_shippingLists','get|collector/:id/shipping-lists');
//@title 邮寄方式列表
define('API_GET_collector_list','get|collector/list');

//控制器：app\index\controller\VirtualUser
//@title 显示资源列表
define('API_GET_virtualUser','get|virtual-user');
//@title 登录
define('API_POST_virtualUser_login','post|virtual-user/login');
//@title 退出
define('API_POST_virtualUser_quit','post|virtual-user/quit');
//@title 获取登录信息
define('API_GET_virtualUser_info','get|virtual-user/info');
//@title 获取国家信息
define('API_GET_virtualUser_country','get|virtual-user/country');
//@title 获取平台信息
define('API_GET_virtualUser_channel','get|virtual-user/channel');
//@title 获取验证码
define('API_GET_virtualUser_code','get|virtual-user/code');
//@title 注册
define('API_POST_virtualUser_register','post|virtual-user/register');
//@title 刷单任务列表
define('API_GET_virtualUser_list','get|virtual-user/list');
//@title 刷单任务状态列表
define('API_GET_virtualUser_status','get|virtual-user/status');
//@title 刷单任务处理
define('API_POST_virtualUser_dispose','post|virtual-user/dispose');
//@title 刷单任务回评
define('API_POST_virtualUser_review','post|virtual-user/review');
//@title 用户详细信息
define('API_GET_virtualUser_userInfo','get|virtual-user/user-info');
//@title 更新用户密码
define('API_POST_virtualUser_userSave','post|virtual-user/user-save');
//@title 更新用户信息
define('API_POST_virtualUser_editor','post|virtual-user/editor');
//@title 货币类型
define('API_GET_virtualUser_currency','get|virtual-user/currency');
//@title 关于我们
define('API_GET_virtualUser_aboutUs','get|virtual-user/about-us');

//控制器：app\index\controller\ServerNetwork
//@title 服务器使用记录
define('API_GET_serverNetwork','get|server-network');
//@title 保存服务器信息
define('API_POST_serverNetwork','post|server-network');

//控制器：app\order\controller\VirtualRule
//@title 显示资源列表
define('API_GET_virtualRules','get|virtual-rules');
//@title 显示指定的资源
define('API_GET_virtualRules___id','get|virtual-rules/:id');
//@title 编辑指定的资源
define('API_GET_virtualRules___id_edit','get|virtual-rules/:id/edit');
//@title 保存的资源
define('API_POST_virtualRules','post|virtual-rules');
//@title 保存更新的资源
define('API_PUT_virtualRules___id','put|virtual-rules/:id');
//@title 删除指定资源
define('API_DELETE_virtualRules___id','delete|virtual-rules/:id');
//@title 更改规则状态
define('API_POST_virtualRules_status','post|virtual-rules/status');
//@title 获取资源
define('API_POST_virtualRules_resources','post|virtual-rules/resources');
//@title 获取发货仓库信息
define('API_GET_virtualRules_warehouse','get|virtual-rules/warehouse');
//@title 获取运输方式
define('API_GET_virtualRules_shipping','get|virtual-rules/shipping');
//@title 获取订单自动处理方法
define('API_GET_virtualRules_action','get|virtual-rules/action');
//@title 保存排序值
define('API_POST_virtualRules_sort','post|virtual-rules/sort');
//@title 规则复制
define('API_POST_virtualRules_copy','post|virtual-rules/copy');
//@title 规则日志
define('API_GET_virtualRules___virtualRule_id_log','get|virtual-rules/:virtualRule_id/log');
//@title 拉取平台数据
define('API_GET_virtualRules_channel','get|virtual-rules/channel');
//@title 拉取创建人数据
define('API_GET_virtualRules_creator','get|virtual-rules/creator');

//控制器：app\order\controller\VirtualRuleItem
//@title 显示资源列表
define('API_GET_virtualRuleItems','get|virtual-rule-items');

//控制器：app\index\controller\AmazonAccountHealth
//@title 查看列表
define('API_GET_amazonAccountHealth','get|amazon-account-health');
//@title 帐号筛选列表
define('API_GET_amazonAccountHealth_account','get|amazon-account-health/account');
//@title 导出列表
define('API_GET_amazonAccountHealth_export','get|amazon-account-health/export');
//@title 查看历史数据；
define('API_GET_amazonAccountHealth___amazon_account_id_history','get|amazon-account-health/:amazon_account_id/history');
//@title 批量设置监控值
define('API_POST_amazonAccountHealth','post|amazon-account-health');
//@title 单个设置监控值
define('API_PUT_amazonAccountHealth','put|amazon-account-health');
//@title 立即抓取
define('API_POST_amazonAccountHealth_repitle','post|amazon-account-health/repitle');
//@title 读取amazon帐号目标率
define('API_GET_amazonAccountHealth___amazon_account_id_goal','get|amazon-account-health/:amazon_account_id/goal');
//@title 读取amazon帐余额统计
define('API_GET_amazonAccountHealth_balance','get|amazon-account-health/balance');
//@title 读取amazon帐余额详情
define('API_GET_amazonAccountHealth_balanceDetails','get|amazon-account-health/balance-details');

//控制器：app\warehouse\controller\StockRule
//@title 显示资源列表
define('API_GET_stockRules','get|stock-rules');
//@title 查看资源
define('API_GET_stockRules___id','get|stock-rules/:id');
//@title 显示编辑资源表单页
define('API_GET_stockRules___id_edit','get|stock-rules/:id/edit');
//@title 保存更新的资源
define('API_PUT_stockRules___id','put|stock-rules/:id');
//@title 保存资源
define('API_POST_stockRules','post|stock-rules');
//@title 保存默认规则
define('API_POST_stockRules_default','post|stock-rules/default');
//@title 获取默认规则
define('API_GET_stockRules_default','get|stock-rules/default');
//@title 删除指定资源
define('API_DELETE_stockRules___id','delete|stock-rules/:id');
//@title 更改规则状态
define('API_POST_stockRules___id_status___value','post|stock-rules/:id/status/:value');
//@title 获取资源
define('API_POST_stockRules_resources','post|stock-rules/resources');
//@title 保存排序值
define('API_POST_stockRules_sort','post|stock-rules/sort');
//@title 获取审批人资源
define('API_GET_stockRules_approve_level','get|stock-rules/approve_level');

//控制器：app\warehouse\controller\StockRuleItem
//@title 显示资源列表
define('API_GET_stockRulesItems','get|stock-rules-items');

//控制器：app\warehouse\controller\AllocationBoxClass
//@title 显示箱子列表
define('API_GET_allocationBox','get|allocation-box');
//@title 保存新建的箱子
define('API_POST_allocationBox','post|allocation-box');
//@title 显示指定的资源
define('API_GET_allocationBox___id','get|allocation-box/:id');
//@title 保存更新的资源
define('API_PUT_allocationBox___id','put|allocation-box/:id');
//@title 删除指定箱子
define('API_DELETE_allocationBox___id','delete|allocation-box/:id');
//@title 状态更新
define('API_PUT_allocationBox___id_status_','put|allocation-box/:id/status/');

//控制器：app\report\controller\CustomerMessage
//@title 列表详情
define('API_GET_report_customerMessage','get|report/customer-message');
//@title 导出
define('API_POST_report_customerMessage_export','post|report/customer-message/export');
//@title 客服账号列表
define('API_GET_report_customerMessage_customer','get|report/customer-message/customer');

//控制器：app\index\controller\ZoodmallAccount
//@title zoodmall账号列表
define('API_GET_zoodmallAccount','get|zoodmall-account');
//@title 添加账号
define('API_POST_zoodmallAccount','post|zoodmall-account');
//@title 更新账号
define('API_PUT_zoodmallAccount','put|zoodmall-account');
//@title 查看账号
define('API_GET_zoodmallAccount___id','get|zoodmall-account/:id');
//@title 获取订单授权信息
define('API_GET_zoodmallAccount_token___id','get|zoodmall-account/token/:id');
//@title  zoodmall订单账号授权
define('API_PUT_zoodmallAccount_token','put|zoodmall-account/token');
//@title 停用，启用账号
define('API_POST_zoodmallAccount_states','post|zoodmall-account/states');
//@title 批量开启
define('API_POST_zoodmallAccount_batchSet','post|zoodmall-account/batch-set');

//控制器：app\index\controller\VovaAccount
//@title vova账号列表
define('API_GET_vovaAccount','get|vova-account');
//@title 添加账号
define('API_POST_vovaAccount','post|vova-account');
//@title 更新账号
define('API_PUT_vovaAccount','put|vova-account');
//@title 查看账号
define('API_GET_vovaAccount___id','get|vova-account/:id');
//@title 获取订单授权信息
define('API_GET_vovaAccount_token___id','get|vova-account/token/:id');
//@title  vova订单账号授权
define('API_PUT_vovaAccount_token','put|vova-account/token');
//@title 停用，启用账号
define('API_POST_vovaAccount_states','post|vova-account/states');
//@title 批量开启
define('API_POST_vovaAccount_batchSet','post|vova-account/batch-set');

//控制器：app\order\controller\VovaOrder
//@title 订单列表
define('API_GET_vovaOrders','get|vova-orders');
//@title 查看
define('API_GET_vovaOrders___id','get|vova-orders/:id');
//@title 取订单各状态的总数
define('API_GET_vovaOrders_statusCount','get|vova-orders/status-count');
//@title 检查订单是否存在
define('API_POST_vovaOrders_check','post|vova-orders/check');
//@title  单独漏掉的vova订单
define('API_GET_vovaOrders_getoneorder','get|vova-orders/getoneorder');
//@title  vova物流对应的carrier_id
define('API_GET_vovaOrders_getPress','get|vova-orders/get-press');
//@title Vova订单导出
define('API_POST_vovaOrders_export','post|vova-orders/export');
//@title 获取所有导出字段
define('API_GET_vovaOrders_exportFields','get|vova-orders/export-fields');

//控制器：app\index\controller\PddAccount
//@title 显示资源列表
define('API_GET_pddAccount','get|pdd-account');
//@title 添加账号
define('API_POST_pddAccount','post|pdd-account');
//@title 显示指定的资源
define('API_GET_pddAccount___id','get|pdd-account/:id');
//@title 更新账号
define('API_PUT_pddAccount','put|pdd-account');
//@title 停用，启用账号
define('API_POST_pddAccount_states','post|pdd-account/states');
//@title 获取授权码
define('API_POST_pddAccount_authorcode','post|pdd-account/authorcode');
//@title 查询pdd账号
define('API_GET_pddAccount_query','get|pdd-account/query');
//@title 获取Token
define('API_POST_pddAccount_token','post|pdd-account/token');
//@title 获取Token
define('API_GET_pddAccount_refresh_token___id','get|pdd-account/refresh_token/:id');
//@title 授权页面
define('API_POST_pddAccount_authorization','post|pdd-account/authorization');
//@title 批量修改账号的抓取状态
define('API_POST_pddAccount_update_download','post|pdd-account/update_download');

//控制器：app\order\controller\PddOrder
//@title 订单列表
define('API_GET_pddOrder','get|pdd-order');
//@title 查看
define('API_GET_pddOrder___id','get|pdd-order/:id');
//@title 订单状态
define('API_GET_pddOrder_statusCount','get|pdd-order/status-count');
//@title 检查订单是否存在
define('API_POST_pddOrder_check','post|pdd-order/check');
//@title  pdd 物流对应的carrier_id
define('API_GET_getpddpress','get|getpddpress');
//@title  按账号单独拉取漏掉的pdd订单
define('API_POST_pddOrders_getorders','post|pdd-orders/getorders');

//控制器：app\index\controller\UmkaAccount
//@title 显示资源列表
define('API_GET_umkaAccount','get|umka-account');
//@title 添加账号
define('API_POST_umkaAccount','post|umka-account');
//@title 显示指定的资源
define('API_GET_umkaAccount___id','get|umka-account/:id');
//@title 更新账号
define('API_PUT_umkaAccount','put|umka-account');
//@title 停用，启用账号
define('API_POST_umkaAccount_states','post|umka-account/states');
//@title 查询umka账号
define('API_GET_umkaAccount_query','get|umka-account/query');
//@title 获取Token
define('API_POST_umkaAccount_token','post|umka-account/token');
//@title 获取Token
define('API_GET_umkaAccount_refresh_token___id','get|umka-account/refresh_token/:id');
//@title 授权页面
define('API_POST_umkaAccount_authorization','post|umka-account/authorization');
//@title 批量修改账号的抓取状态
define('API_POST_umkaAccount_update_download','post|umka-account/update_download');

//控制器：app\order\controller\UmkaOrder
//@title 订单列表
define('API_GET_umkaOrder','get|umka-order');
//@title 查看
define('API_GET_umkaOrder___id','get|umka-order/:id');
//@title 订单状态
define('API_GET_umkaOrder_statusCount','get|umka-order/status-count');
//@title 检查订单是否存在
define('API_POST_umkaOrder_check','post|umka-order/check');
//@title  按账号单独拉取漏掉的umka订单
define('API_POST_umkaOrder_getorders','post|umka-order/getorders');
//@title  umka 物流对应的carrier_id
define('API_GET_getPress','get|get-press');

//控制器：app\order\controller\ZoodmallOrder
//@title 订单列表
define('API_GET_zoodmallOrders','get|zoodmall-orders');
//@title 查看
define('API_GET_zoodmallOrders___id','get|zoodmall-orders/:id');
//@title 取订单各状态的总数
define('API_GET_zoodmallOrders_statusCount','get|zoodmall-orders/status-count');
//@title 检查订单是否存在
define('API_POST_zoodmallOrders_check','post|zoodmall-orders/check');

//控制器：app\purchase\controller\PurchaseParcelsBox
//@title 创建卡板
define('API_POST_purchaseParcelsBox','post|purchase-parcels-box');
//@title 结束卡板
define('API_PUT_purchaseParcelsBox___id','put|purchase-parcels-box/:id');
//@title 批量删除卡板
define('API_DELETE_purchaseParcelsBox_batch','delete|purchase-parcels-box/batch');
//@title 卡板管理
define('API_GET_purchaseParcelsBox','get|purchase-parcels-box');
//@title 卡板状态
define('API_GET_purchaseParcelBox_status','get|purchase-parcel-box/status');
//@title 卡板详情
define('API_GET_purchaseParcelsBox___id_parcel','get|purchase-parcels-box/:id/parcel');
//@title 卡板日志
define('API_GET_purchaseParcelsBox___id_log','get|purchase-parcels-box/:id/log');
//@title 批量打印
define('API_POST_purchaseParcelsBox_batch_print','post|purchase-parcels-box/batch/print');
//@title 扫描卡板
define('API_PUT_purchaseParcelsBox___id_scanning','put|purchase-parcels-box/:id/scanning');
//@title 批量打板完成
define('API_PUT_purchaseParcelsBox_batch_finish','put|purchase-parcels-box/batch/finish');
//@title 拆板负责人
define('API_GET_purchaseParcelsBox_unpackName','get|purchase-parcels-box/unpack-name');
//@title 批量拆板完成（修改状态为强制完成）
define('API_PUT_purchaseParcelsBox_batch_force','put|purchase-parcels-box/batch/force');

//控制器：app\api\controller\YksPurchase
//@title 有棵树公用api接口
define('API_POST_api_yks_index','post|api/yks/index');
//@title 测试推送有棵树
define('API_POST_api_yks_pushTest123','post|api/yks/push-test123');

//控制器：app\report\controller\PublishByPicking
//@title 列表详情
define('API_GET_report_publishByPicking','get|report/publish-by-picking');
//@title sup详情
define('API_GET_report_publishByPicking_sup','get|report/publish-by-picking/sup');
//@title 导出
define('API_POST_report_publishByPicking_export','post|report/publish-by-picking/export');

//控制器：app\report\controller\PublishByShelf
//@title 列表详情
define('API_GET_report_publishByShelf','get|report/publish-by-shelf');
//@title sup详情
define('API_GET_report_publishByShelf_sup','get|report/publish-by-shelf/sup');
//@title 导出
define('API_POST_report_publishByShelf_export','post|report/publish-by-shelf/export');
//@title 获取spu刊登统计列表
define('API_GET_report_publishByShelf_spu','get|report/publish-by-shelf/spu');
//@title 获取刊登的账号刊登次数
define('API_GET_report_publishByShelf_spu_accountDetail','get|report/publish-by-shelf/spu/account-detail');

//控制器：app\report\controller\FirstOrderSkuList
//@title 首次出单列表
define('API_GET_firstOrder','get|first-order');
//@title execl字段信息
define('API_GET_firstOrder_exportTitle','get|first-order/export-title');
//@title 导出
define('API_POST_firstOrder_export','post|first-order/export');

//控制器：app\index\controller\YandexAccount
//@title yandex账号列表
define('API_GET_yandexAccount','get|yandex-account');
//@title 添加账号
define('API_POST_yandexAccount','post|yandex-account');
//@title 更新账号
define('API_PUT_yandexAccount','put|yandex-account');
//@title 查看账号
define('API_GET_yandexAccount___id','get|yandex-account/:id');
//@title 获取订单授权信息
define('API_GET_yandexAccount_token___id','get|yandex-account/token/:id');
//@title  yandex订单账号授权
define('API_PUT_yandexAccount_token','put|yandex-account/token');
//@title 停用，启用账号
define('API_POST_yandexAccount_states','post|yandex-account/states');
//@title 批量开启
define('API_POST_yandexAccount_batchSet','post|yandex-account/batch-set');

//控制器：app\order\controller\YandexOrder
//@title 订单列表
define('API_GET_yandexOrders','get|yandex-orders');
//@title 查看
define('API_GET_yandexOrders___id','get|yandex-orders/:id');
//@title 取订单各状态的总数
define('API_GET_yandexOrders_statusCount','get|yandex-orders/status-count');
//@title 检查订单是否存在
define('API_POST_yandexOrders_check','post|yandex-orders/check');

//控制器：app\purchase\controller\VirtualFinancePurchase
//@title 虚拟付款记录列表
define('API_GET_virtualFinancePurchase','get|virtual-finance-purchase');
//@title 虚拟付款申请详情
define('API_GET_virtualFinancePurchase___id','get|virtual-finance-purchase/:id');
//@title 审核虚拟付款申请
define('API_GET_virtualFinancePurchase___id_review','get|virtual-finance-purchase/:id/review');
//@title 审核是否通过虚拟付款申请
define('API_PUT_virtualFinancePurchase___id_review','put|virtual-finance-purchase/:id/review');
//@title 批量审核虚拟付款申请
define('API_PUT_virtualFinancePurchase_batch_review','put|virtual-finance-purchase/batch/review');
//@title 获取虚拟采购单审核列表
define('API_GET_virtualFinancePurchase_reviewStatus','get|virtual-finance-purchase/review-status');
//@title 推送有棵树
define('API_POST_virtualFinancePurchase_pushYks','post|virtual-finance-purchase/push-yks');
//@title 虚拟采购单导出
define('API_POST_virtualFinancePurchase_export','post|virtual-finance-purchase/export');
//@title 虚拟采购单导出字段
define('API_GET_virtualFinancePurchase_exportFields','get|virtual-finance-purchase/export-fields');
//@title 计算虚拟付款记录的应付款总金额和已付款总金额
define('API_GET_virtualFinancePurchase_totalAmount','get|virtual-finance-purchase/total-amount');
//@title 导出订购单
define('API_POST_virtualFinancePurchase_exportPurchase','post|virtual-finance-purchase/export-purchase');
//@title 预览订购单
define('API_POST_virtualFinancePurchase_readPurchase','post|virtual-finance-purchase/read-purchase');
//@title 预览收货单
define('API_POST_virtualFinancePurchase_readReceipt','post|virtual-finance-purchase/read-receipt');
//@title 预览入库单
define('API_POST_virtualFinancePurchase_readInStock','post|virtual-finance-purchase/read-in-stock');
//@title 预览送货单
define('API_POST_virtualFinancePurchase_readDeliver','post|virtual-finance-purchase/read-deliver');
//@title 预览发票
define('API_POST_virtualFinancePurchase_readInvoice','post|virtual-finance-purchase/read-invoice');
//@title 导出收货单
define('API_POST_virtualFinancePurchase_export-Receipt','post|virtual-finance-purchase/export-Receipt');
//@title 导出入库单
define('API_POST_virtualFinancePurchase_exportInStock','post|virtual-finance-purchase/export-in-stock');
//@title 导出送货单
define('API_POST_virtualFinancePurchase_exportDeliver','post|virtual-finance-purchase/export-deliver');
//@title 导出发票
define('API_POST_virtualFinancePurchase_exportInvoice','post|virtual-finance-purchase/export-invoice');

//控制器：app\purchase\controller\VirtualPurchaseOrder
//@title 虚拟采购单列表
define('API_GET_virtualPurchaseOrder','get|virtual-purchase-order');
//@title 虚拟采购单详情
define('API_GET_virtualPurchaseOrder___id','get|virtual-purchase-order/:id');
//@title 虚拟采购单商品详情
define('API_GET_virtualPurchaseOrder___id_detail','get|virtual-purchase-order/:id/detail');
//@title 批量生成虚拟采购单
define('API_POST_virtualPurchaseOrder_create','post|virtual-purchase-order/create');
//@title 批量生成虚拟付款申请
define('API_POST_virtualPurchaseOrder_createFinance','post|virtual-purchase-order/create-finance');
//@title 推送有棵树
define('API_POST_virtualPurchaseOrder_pushYks','post|virtual-purchase-order/push-yks');
//@title 虚拟采购单导出
define('API_POST_virtualPurchaseOrder_export','post|virtual-purchase-order/export');
//@title 虚拟采购单导出字段
define('API_GET_virtualPurchaseOrder_exportFields','get|virtual-purchase-order/export-fields');
//@title 计算虚拟采购单的应付款总金额和已付款总金额
define('API_GET_virtualPurchaseOrder_totalAmount','get|virtual-purchase-order/total-amount');

//控制器：app\warehouse\controller\WishCarrier
//@title 列表
define('API_GET_wishCarrier','get|wish-carrier');
//@title 添加
define('API_POST_wishCarrier','post|wish-carrier');
//@title 显示指定物流商资源
define('API_GET_wishCarrier___id','get|wish-carrier/:id');
//@title 更新物流商信息
define('API_PUT_wishCarrier___id','put|wish-carrier/:id');
//@title 获取Wish邮授权url
define('API_GET_wishCarrier_wishpostUrl','get|wish-carrier/wishpost-url');
//@title wish授权
define('API_POST_wishCarrier_wishAuthors','post|wish-carrier/wish-authors');
//@title 获取wish绑定账号信息
define('API_GET_wishCarrier___id_accountList','get|wish-carrier/:id/account-list');
//@title wish绑定账号
define('API_POST_wishCarrier___id_accountBind','post|wish-carrier/:id/account-bind');
//@title wish绑定账号解绑
define('API_POST_wishCarrier___id_accountUnbind','post|wish-carrier/:id/account-unbind');
//@title 获取绑定日志
define('API_GET_wishCarrier___id_bindLog','get|wish-carrier/:id/bind-log');
//@title 获取wish账号信息
define('API_GET_wishCarrier_account','get|wish-carrier/account');

//控制器：app\report\controller\PublishByTime
//@title 列表详情
define('API_GET_report_publishByTimes','get|report/publish-by-times');
//@title 平台列表数据
define('API_GET_report_publishByTimes_channel','get|report/publish-by-times/channel');
//@title 账号详情数据
define('API_GET_report_publishByTimes_shelf','get|report/publish-by-times/shelf');
//@title 导出
define('API_POST_report_publishByTimes_export','post|report/publish-by-times/export');

//控制器：app\customerservice\controller\EbayEmail
//@title 收件箱
define('API_GET_ebayEmails','get|ebay-emails');
//@title 侵权邮件收件箱
define('API_GET_ebayEmails_infringementBox','get|ebay-emails/infringement-box');
//@title 发件箱
define('API_GET_ebayEmails_outbox','get|ebay-emails/outbox');
//@title 转到收件箱
define('API_PUT_ebayEmails_turnInbox','put|ebay-emails/turn-inbox');
//@title 垃圾箱
define('API_GET_ebayEmails_trashbox','get|ebay-emails/trashbox');
//@title 收取指定平台账号的邮件
define('API_GET_ebayEmails_emailAccount_receive___account_id','get|ebay-emails/email-account/receive/:account_id');
//@title 标记已读
define('API_PUT_ebayEmails_read','put|ebay-emails/read');
//@title 标记未读
define('API_PUT_ebayEmails_unread','put|ebay-emails/unread');
//@title 标记未读
define('API_PUT_ebayEmails_trash','put|ebay-emails/trash');
//@title 获取客服对应的账号
define('API_GET_ebayEmails_account','get|ebay-emails/account');
//@title 失败邮件重新发送
define('API_POST_ebayEmails_resend','post|ebay-emails/resend');
//@title 收件人邮件列表
define('API_GET_ebayEmails_receiverMailAddr','get|ebay-emails/receiver-mailAddr');
//@title 发件人邮件列表
define('API_GET_ebayEmails_sendMailAddr','get|ebay-emails/send-mailAddr');
//@title 未读邮件数
define('API_GET_ebayEmails_unread','get|ebay-emails/unread');
//@title 标记置顶
define('API_PUT_ebayEmails_top','put|ebay-emails/top');
//@title 取消置顶
define('API_PUT_ebayEmails_cancelTop','put|ebay-emails/cancel-top');

//控制器：app\warehouse\controller\LocalStocking
//@title 获取活动备货状态
define('API_GET_localStocking_status','get|local-stocking/status');
//@title 获取活动备货列表
define('API_GET_localStocking','get|local-stocking');
//@title 创建活动备货申请
define('API_POST_localStocking','post|local-stocking');
//@title 查看活动备货详情
define('API_GET_localStocking___id','get|local-stocking/:id');
//@title 审核活动备货申请
define('API_POST_localStocking_adopt___id','post|local-stocking/adopt/:id');
//@title 导入商品信息
define('API_POST_localStocking_importGoods','post|local-stocking/import-goods');

//控制器：app\publish\controller\WishExpress
//@title 添加物流模版
define('API_POST_publish_wishExpress_addTemplate','post|publish/wish-express/add-template');
//@title 编辑物流模版
define('API_POST_publish_wishExpress_editTemplate','post|publish/wish-express/edit-template');
//@title 批量删除模版
define('API_DELETE_publish_wishExpress_batchDelete','delete|publish/wish-express/batch-delete');
//@title wish物流价格模版列表
define('API_GET_publish_wishExpress_lists','get|publish/wish-express/lists');
//@title 获取模版详情
define('API_GET_publish_wishExpress_detail','get|publish/wish-express/detail');

//控制器：app\order\controller\PackageError
//@title 物流异常包裹解决方案列表
define('API_GET_packagesError','get|packages-error');
//@title 添加下单异常错误信息
define('API_POST_packagesError_','post|packages-error/');
//@title 更新异常错误信息
define('API_PUT_packagesError_','put|packages-error/');
//@title 获取创建人信息
define('API_GET_packagesError_developers','get|packages-error/developers');
//@title 获取更新人信息
define('API_GET_packagesError_updaters','get|packages-error/updaters');
//@title 获取下单报错信息
define('API_GET_packagesError_error','get|packages-error/error');

//控制器：app\index\controller\ChannelNode
//@title 平台自动登录列表
define('API_GET_channelNode','get|channel-node');
//@title 获取平台自动登录信息
define('API_GET_channelNode___id_edit','get|channel-node/:id/edit');
//@title 保存平台自动登录信息
define('API_POST_channelNode','post|channel-node');
//@title 更新平台自动登录信息
define('API_PUT_channelNode___id','put|channel-node/:id');
//@title 删除
define('API_DELETE_channelNode___id','delete|channel-node/:id');
//@title 节点类型
define('API_GET_channelNode_nodeType','get|channel-node/node-type');

//控制器：app\report\controller\SkuSalesDynamic
//@title 列表详情
define('API_GET_report_skuSalesDynamic','get|report/sku-sales-dynamic');
//@title execl字段信息
define('API_GET_report_skuSalesDynamic_exportTitle','get|report/sku-sales-dynamic/export-title');
//@title 导出execl
define('API_POST_report_skuSalesDynamic_export','post|report/sku-sales-dynamic/export');

//控制器：app\order\controller\Fbp
//@title fbp订单列表
define('API_GET_fbpOrders','get|fbp-orders');
//@title fbp销售额统计
define('API_GET_fbpOrders_report','get|fbp-orders/report');
//@title 获取所有导出字段
define('API_GET_fbpOrders_exportFields','get|fbp-orders/export-fields');
//@title fbp订单导出
define('API_POST_fbpOrders_export','post|fbp-orders/export');

//控制器：app\index\controller\EmailServer
//@title 平台自动登录列表
define('API_GET_emailServer','get|email-server');
//@title 获取平台自动登录信息
define('API_GET_emailServer___id_edit','get|email-server/:id/edit');
//@title 保存平台自动登录信息
define('API_POST_emailServer','post|email-server');
//@title 更新平台自动登录信息
define('API_PUT_emailServer___id','put|email-server/:id');
//@title 删除
define('API_DELETE_emailServer___id___account_id','delete|email-server/:id/:account_id');

//控制器：app\index\controller\FbpAccount
//@title fbp账号列表
define('API_GET_fbpAccount','get|fbp-account');
//@title 添加账号
define('API_POST_fbpAccount','post|fbp-account');
//@title 更新账号
define('API_PUT_fbpAccount','put|fbp-account');
//@title 查看账号
define('API_GET_fbpAccount___id','get|fbp-account/:id');
//@title 获取订单授权信息
define('API_GET_fbpAccount_token___id','get|fbp-account/token/:id');
//@title  fbp订单账号授权
define('API_PUT_fbpAccount_token','put|fbp-account/token');
//@title  fbp商品账号授权
define('API_PUT_fbpAccount_tokencat','put|fbp-account/tokencat');
//@title 停用，启用账号
define('API_POST_fbpAccount_states','post|fbp-account/states');
//@title 批量开启
define('API_POST_fbpAccount_batchSet','post|fbp-account/batch-set');

//控制器：app\report\controller\MonthlyTargetUser
//@title 列表详情
define('API_GET_monthlyTargetUser','get|monthly-target-user');
//@title 保存成员
define('API_POST_monthlyTargetUser_add','post|monthly-target-user/add');
//@title 保存更新的资源
define('API_PUT_monthlyTargetUser___id','put|monthly-target-user/:id');
//@title 删除绑定关系
define('API_DELETE___id','delete|:id');
//@title 目标成员管理[销售]获取
define('API_GET_monthlyTargetUser___id','get|monthly-target-user/:id');
//@title 目标成员管理[销售]编辑
define('API_GET_monthlyTargetUser___id_edit','get|monthly-target-user/:id/edit');
//@title 拉取部门以及上级信息
define('API_GET_monthlyTargetUser_getDepartment','get|monthly-target-user/get-department');

//控制器：app\report\controller\MonthlyTargetDepartment
//@title 目标部门管理[销售]列表
define('API_GET_monthlyTargetDepartment','get|monthly-target-department');
//@title 目标部门管理[销售]添加
define('API_POST_monthlyTargetDepartment','post|monthly-target-department');
//@title 目标部门管理[销售]获取
define('API_GET_monthlyTargetDepartment___id','get|monthly-target-department/:id');
//@title 目标部门管理[销售]编辑
define('API_GET_monthlyTargetDepartment___id_edit','get|monthly-target-department/:id/edit');
//@title 目标部门管理[销售]更新
define('API_PUT_monthlyTargetDepartment___id','put|monthly-target-department/:id');
//@title 目标部门管理[销售]删除
define('API_DELETE_monthlyTargetDepartment___id','delete|monthly-target-department/:id');
//@title 停用，启用账号
define('API_GET_monthlyTargetDepartment_changeStatus','get|monthly-target-department/change-status');
//@title 获取所有部门
define('API_GET_monthlyTargetDepartment_getDepartment','get|monthly-target-department/get-department');
//@title 部门类型
define('API_GET_monthlyTargetDepartment_type','get|monthly-target-department/type');

//控制器：app\report\controller\MonthlyTargetAmount
//@title 列表详情
define('API_GET_monthlyTargetAmount','get|monthly-target-amount');
//@title 首页简报
define('API_GET_monthlyTargetAmount_allTarget','get|monthly-target-amount/all-target');
//@title 下载部门与成员组成表
define('API_POST_monthlyTargetAmount_export','post|monthly-target-amount/export');
//@title 下载月度目标报表
define('API_POST_monthlyTargetAmount_exportMonthly','post|monthly-target-amount/export-monthly');
//@title 导入成员考核目标
define('API_POST_monthlyTargetAmount_import','post|monthly-target-amount/import');
//@title 保存导入成员考核目标
define('API_POST_monthlyTargetAmount_saveImport','post|monthly-target-amount/save-import');
//@title 重新计算部门人数与平台账号数
define('API_POST_monthlyTargetAmount_recalculate','post|monthly-target-amount/recalculate');

//控制器：app\order\controller\VirtualRefund
//@title 虚拟订单返款申请
define('API_POST_virtualRefund','post|virtual-refund');
//@title 虚拟订单返款列表
define('API_GET_virtualRefund','get|virtual-refund');
//@title 首次返款申请单
define('API_GET_virtualRefund_getTask','get|virtual-refund/get-task');
//@title 查看返款申请单
define('API_GET_virtualRefund___id','get|virtual-refund/:id');
//@title 提交/重新返款申请单
define('API_POST_virtualRefund_addRefund','post|virtual-refund/add-refund');
//@title 标记审核状态
define('API_POST_virtualRefund_approval','post|virtual-refund/approval');
//@title 批量标记审核状态
define('API_POST_virtualRefund_batch_approval','post|virtual-refund/batch/approval');
//@title 批量标记返款状态
define('API_POST_virtualRefund_batch_refund','post|virtual-refund/batch/refund');
//@title 导出execl
define('API_POST_virtualRefund_export','post|virtual-refund/export');
//@title execl字段信息
define('API_GET_virtualRefund_exportTitle','get|virtual-refund/export-title');

//控制器：app\index\controller\WishShippingRate
//@title 显示资源列表
define('API_GET_wishShippingRate','get|wish-shipping-rate');
//@title 显示指定的资源
define('API_GET_wishShippingRate___id','get|wish-shipping-rate/:id');
//@title 显示编辑资源表单页.
define('API_GET_wishShippingRate___id_edit','get|wish-shipping-rate/:id/edit');
//@title 保存更新的资源
define('API_PUT_wishShippingRate___id','put|wish-shipping-rate/:id');
//@title 计算订单占比
define('API_POST_wishShippingRate_orderRate','post|wish-shipping-rate/order-rate');
//@title 计算重量运费
define('API_POST_wishShippingRate_shippingCharge','post|wish-shipping-rate/shipping-charge');
//@title wish重量与费用列表
define('API_GET_wishShippingRate_weightList','get|wish-shipping-rate/weight-list');

//控制器：app\customerservice\controller\AfterSaleRule
//@title 售后单规则列表
define('API_GET_afterSaleRules','get|after-sale-rules');
//@title 规则详情
define('API_GET_afterSaleRules___id','get|after-sale-rules/:id');
//@title 新增订单
define('API_POST_afterSaleRules','post|after-sale-rules');
//@title 更新规则
define('API_PUT_afterSaleRules___id','put|after-sale-rules/:id');
//@title 删除规则
define('API_DELETE_afterSaleRules___id','delete|after-sale-rules/:id');
//@title 修改规则状态
define('API_POST_afterSaleRules_status','post|after-sale-rules/status');
//@title 保存排序值
define('API_POST_afterSaleRules_sort','post|after-sale-rules/sort');
//@title 获取售后单规则
define('API_GET_afterSaleRules_ruleItem','get|after-sale-rules/rule-item');
//@title 平台列表
define('API_GET_afterSaleRules_channel','get|after-sale-rules/channel');

//控制器：app\order\controller\PackageException
//@title 异常包裹列表
define('API_GET_packageException','get|package-exception');
//@title execl字段信息
define('API_GET_packageException_exportTitle','get|package-exception/export-title');
//@title 获取异常包裹状态
define('API_GET_packageException_status','get|package-exception/status');
//@title 导出
define('API_POST_packageException_export','post|package-exception/export');

//控制器：app\index\controller\AccountCompany
//@title 平台公司资料列表
define('API_GET_accountCompany','get|account-company');
//@title 显示指定的资源
define('API_GET_accountCompany___id','get|account-company/:id');
//@title 获取平台公司资料信息
define('API_GET_accountCompany___id_edit','get|account-company/:id/edit');
//@title 保存平台公司资料信息
define('API_POST_accountCompany','post|account-company');
//@title 更新平台公司资料信息[公司资料]
define('API_PUT_accountCompany___id','put|account-company/:id');
//@title 更新平台公司资料信息[账号信息]
define('API_PUT_accountCompany___id_account','put|account-company/:id/account');
//@title 更新平台公司资料信息[VAT]
define('API_PUT_accountCompany___id_vat','put|account-company/:id/vat');
//@title 日志
define('API_GET_accountCompany___id_log','get|account-company/:id/log');
//@title 拉取公司名称列表
define('API_GET_accountCompany_company','get|account-company/company');
//@title 修改状态
define('API_POST_accountCompany___id_status','post|account-company/:id/status');
//@title 公司类型
define('API_GET_accountCompany_type','get|account-company/type');
//@title 资料来源
define('API_GET_accountCompany_source','get|account-company/source');

//控制器：app\report\controller\DevelopMonthlyTargetUser
//@title 列表详情
define('API_GET_developMonthlyTargetUser','get|develop-monthly-target-user');
//@title 保存成员
define('API_POST_developMonthlyTargetUser_add','post|develop-monthly-target-user/add');
//@title 保存更新的资源
define('API_PUT_developMonthlyTargetUser___id','put|develop-monthly-target-user/:id');
//@title 目标成员管理[开发]获取
define('API_GET_developMonthlyTargetUser___id','get|develop-monthly-target-user/:id');
//@title 目标成员管理[开发]编辑
define('API_GET_developMonthlyTargetUser___id_edit','get|develop-monthly-target-user/:id/edit');
//@title 拉取部门以及上级信息
define('API_GET_developMonthlyTargetUser_getDepartment','get|develop-monthly-target-user/get-department');

//控制器：app\report\controller\DevelopMonthlyTargetDepartment
//@title 目标部门管理[开发]列表
define('API_GET_developMonthlyTargetDepartment','get|develop-monthly-target-department');
//@title 目标部门管理[开发]添加
define('API_POST_developMonthlyTargetDepartment','post|develop-monthly-target-department');
//@title 目标部门管理[开发]获取
define('API_GET_developMonthlyTargetDepartment___id','get|develop-monthly-target-department/:id');
//@title 目标部门管理[开发]编辑
define('API_GET_developMonthlyTargetDepartment___id_edit','get|develop-monthly-target-department/:id/edit');
//@title 目标部门管理[开发]更新
define('API_PUT_developMonthlyTargetDepartment___id','put|develop-monthly-target-department/:id');
//@title 目标部门管理[开发]删除
define('API_DELETE_developMonthlyTargetDepartment___id','delete|develop-monthly-target-department/:id');
//@title 停用，启用账号
define('API_GET_developMonthlyTargetDepartment_changeStatus','get|develop-monthly-target-department/change-status');
//@title 获取所有部门
define('API_GET_developMonthlyTargetDepartment_getDepartment','get|develop-monthly-target-department/get-department');
//@title 部门类型
define('API_GET_developMonthlyTargetDepartment_type','get|develop-monthly-target-department/type');

//控制器：app\report\controller\DevelopMonthlyTargetAmount
//@title 列表详情
define('API_GET_developMonthlyTargetAmount','get|develop-monthly-target-amount');
//@title 首页简报
define('API_GET_developMonthlyTargetAmount_allTarget','get|develop-monthly-target-amount/all-target');
//@title 下载部门与成员组成表
define('API_POST_developMonthlyTargetAmount_export','post|develop-monthly-target-amount/export');
//@title 下载月度目标报表
define('API_POST_developMonthlyTargetAmount_exportMonthly','post|develop-monthly-target-amount/export-monthly');
//@title 导入成员考核目标
define('API_POST_developMonthlyTargetAmount_import','post|develop-monthly-target-amount/import');
//@title 保存导入成员考核目标
define('API_POST_developMonthlyTargetAmount_saveImport','post|develop-monthly-target-amount/save-import');
//@title 重新计算部门人数
define('API_POST_developMonthlyTargetAmount_recalculate','post|develop-monthly-target-amount/recalculate');

//控制器：app\warehouse\controller\PickingManage
//@title 调拨拣货单列表
define('API_GET_pickingsManage','get|pickings-manage');
//@title 查看
define('API_GET_pickingsManage___id','get|pickings-manage/:id');
//@title 拣货单详情
define('API_GET_pickingsManage___id_detail','get|pickings-manage/:id/detail');
//@title 查看拣货单周转箱信息
define('API_GET_pickingsManage___id_turnover','get|pickings-manage/:id/turnover');
//@title 正在拣货
define('API_POST_pickingsManage___id_marking','post|pickings-manage/:id/marking');
//@title 打印拣货单
define('API_GET_pickingsManage___id_print','get|pickings-manage/:id/print');
//@title 拣货单操作日志
define('API_GET_pickingsManage___id_log','get|pickings-manage/:id/log');
//@title 作废
define('API_POST_pickingsManage___id_invalid','post|pickings-manage/:id/invalid');
//@title 获取调拨拣货单状态
define('API_GET_pickingsManage_status','get|pickings-manage/status');
//@title 下架完成拣货
define('API_POST_pickingsManage___id_complete','post|pickings-manage/:id/complete');
//@title 单个SKU下架
define('API_POST_pickingsManage___id_off','post|pickings-manage/:id/off');
//@title 打印商品条码
define('API_GET_pickingsManage___id_printBarcode','get|pickings-manage/:id/print-barcode');

//控制器：app\publish\controller\AmazonNotice
//@title 亚马逊账号通知信息
define('API_GET_publish_amazonNotice_noticeInfo','get|publish/amazon-notice/notice-info');
//@title 亚马逊账号通知设置
define('API_POST_publish_amazonNotice_setNotice','post|publish/amazon-notice/set-notice');
//@title 亚马逊账号通知测试
define('API_GET_publish_amazonNotice_noticeCeshi','get|publish/amazon-notice/notice-ceshi');
//@title 亚马逊账号通知消息
define('API_POST_publish_amazonNotice_checkNotice','post|publish/amazon-notice/check-notice');

//控制器：app\customerservice\controller\PaypalDispute
//@title paypal纠纷列表
define('API_GET_paypalDispute','get|paypal-dispute');
//@title paypal纠纷统计
define('API_GET_paypalDispute_statistics','get|paypal-dispute/statistics');
//@title paypal更新纠纷
define('API_PUT_paypalDispute___id','put|paypal-dispute/:id');
//@title paypal帐号筛选
define('API_GET_paypalDispute_accounts','get|paypal-dispute/accounts');
//@title 查看paypal纠纷详情
define('API_GET_paypalDispute___id_read','get|paypal-dispute/:id/read');
//@title 处理paypal纠纷详情
define('API_GET_paypalDispute___id','get|paypal-dispute/:id');
//@title paypal处理纠纷
define('API_POST_paypalDispute___type','post|paypal-dispute/:type');
//@title paypal纠纷添加新地址
define('API_POST_paypalDispute_address','post|paypal-dispute/address');
//@title paypal纠纷拿取地址
define('API_GET_paypalDispute___aid_address','get|paypal-dispute/:aid/address');
//@title paypal纠纷拿给客户付款订单
define('API_GET_paypalDispute___id_refund_order','get|paypal-dispute/:id/refund_order');
//@title paypal纠纷物流选取；
define('API_GET_paypalDispute_carriers','get|paypal-dispute/carriers');
//@title paypal纠纷同意赔偿原因；
define('API_GET_paypalDispute_accept_reason','get|paypal-dispute/accept_reason');

//控制器：app\warehouse\controller\ReturnWaitShelf
//@title 列表
define('API_GET_returnWaitShelf','get|return-wait-shelf');
//@title 待入库详情
define('API_GET_returnWaitShelf___id_detail','get|return-wait-shelf/:id/detail');
//@title 批量重返上架
define('API_POST_returnWaitShelf_batch_save','post|return-wait-shelf/batch/save');

//控制器：app\report\controller\AmazonSettlementReport
//@title Amazon结算报告列表
define('API_GET_report_amazonSettlement_summary','get|report/amazon-settlement/summary');
//@title Amazon结算报告列表详情
define('API_GET_report_amazonSettlement_summaryDetail','get|report/amazon-settlement/summary-detail');
//@title  Amazon结算报告导出
define('API_POST_report_amazonSettlement_summaryExport','post|report/amazon-settlement/summary-export');
//@title 获取可供选择的导出字段
define('API_GET_report_amazonSettlement_exportField','get|report/amazon-settlement/export-field');
//@title 检查结算报告缺失
define('API_GET_report_amazonSettlement_checkReport','get|report/amazon-settlement/check-report');
//@title 更新report-summary
define('API_GET_report_amazonSettlement_updateSummary','get|report/amazon-settlement/update-summary');
//@title 修复report错误数据
define('API_GET_report_amazonSettlement_repair','get|report/amazon-settlement/repair');
//@title 页面获取账号分页
define('API_GET_report_amazonSettlement_account','get|report/amazon-settlement/account');

//控制器：app\warehouse\controller\PackingManage
//@title 包装作业列表
define('API_GET_packingManage','get|packing-manage');
//@title 包装开始
define('API_POST_packingManage_startPacking','post|packing-manage/start-packing');
//@title 添加调拨箱
define('API_POST_packingManage','post|packing-manage');
//@title 扫描SKU
define('API_POST_packingManage_insertPackBox','post|packing-manage/insert-pack-box');
//@title 修改sku数量
define('API_POST_packingManage_changeQuantity','post|packing-manage/change-quantity');
//@title 包装完成
define('API_POST_packingManage_packingFinish','post|packing-manage/packing-finish');
//@title 删除调拨箱详情
define('API_POST_packingManage_deleteBoxDetail','post|packing-manage/delete-box-detail');
//@title 修改调拨箱尺寸
define('API_POST_packingManage_modifySize','post|packing-manage/modify-size');

//控制器：app\index\controller\Software
//@title 显示资源列表
define('API_GET_software','get|software');
//@title 保存新建的资源
define('API_POST_software','post|software');
//@title 更改账号状态
define('API_POST_software_batch___type','post|software/batch/:type');
//@title 修改状态
define('API_POST_software___id_status','post|software/:id/status');
//@title 获取状态
define('API_GET_software_type','get|software/type');
//@title 删除软件
define('API_DELETE_software___id','delete|software/:id');
//@title 发布软件版本
define('API_POST_software___id_version','post|software/:id/version');
//@title 历史版本
define('API_GET_software___id_version','get|software/:id/version');

//控制器：app\index\controller\ServerSoftware
//@title 显示资源列表
define('API_GET_serverSoftware','get|server-software');
//@title 批量操作【更新客户端版本】
define('API_POST_serverSoftware_batch___type','post|server-software/batch/:type');
//@title 修改状态
define('API_POST_serverSoftware___id_status','post|server-software/:id/status');

//控制器：app\finance\controller\WishSettlement
//@title wish结算报告列表
define('API_GET_wishSettlement_index_settle','get|wish-settlement/index_settle');
//@title wish结算报告导出
define('API_POST_wishSettlement_export','post|wish-settlement/export');
//@title wish汇总结算报告导出
define('API_POST_wishSettlement_exportSum','post|wish-settlement/export-sum');

//控制器：app\warehouse\controller\AllocationShipping
//@title 列表
define('API_GET_allocationShipping','get|allocation-shipping');
//@title 箱子出库交接
define('API_POST_allocationShipping_deliver','post|allocation-shipping/deliver');
//@title 箱子出库交接
define('API_POST_allocationShipping_batchDeliver','post|allocation-shipping/batch-deliver');
//@title 批量出库详情
define('API_GET_allocationShipping_detail','get|allocation-shipping/detail');
//@title 强制交货完成
define('API_POST_allocationShipping_forceDeliver','post|allocation-shipping/force-deliver');

//控制器：app\warehouse\controller\AllocationLogistics
//@title 列表
define('API_GET_allocationLogistics','get|allocation-logistics');
//@title 上传物流信息
define('API_PUT_allocationLogistics_upload','put|allocation-logistics/upload');
//@title 导出装箱清单
define('API_POST_allocationLogistics_exportList','post|allocation-logistics/export-list');
//@title 导入运费
define('API_POST_allocationLogistics_importTracking','post|allocation-logistics/import-tracking');

//控制器：app\warehouse\controller\ShippingAddress
//@title 物流地址设置列表
define('API_GET_shippingAddress','get|shipping-address');
//@title 查看地址
define('API_GET_shippingAddress___id','get|shipping-address/:id');
//@title 保存地址
define('API_POST_shippingAddress','post|shipping-address');
//@title 更新地址
define('API_PUT_shippingAddress___id','put|shipping-address/:id');
//@title 更新地址
define('API_DELETE_shippingAddress___id','delete|shipping-address/:id');

//控制器：app\purchase\controller\SupplierDiscussRecord
//@title 供应商洽谈记录列表
define('API_GET_supplierDiscussRecord','get|supplier-discuss-record');
//@title 添加供应商洽谈记录
define('API_POST_supplierDiscussRecord','post|supplier-discuss-record');
//@title 查看资源
define('API_GET_supplierDiscussRecord___id','get|supplier-discuss-record/:id');
//@title 获取信息
define('API_GET_supplierDiscussRecord___type_info','get|supplier-discuss-record/:type/info');

//控制器：app\warehouse\controller\DefectiveGoodsDeclare
//@title 显示次品列表
define('API_GET_defectiveGoodsDeclare','get|defective-goods-declare');
//@title 新增
define('API_POST_defectiveGoodsDeclare_create','post|defective-goods-declare/create');
//@title 查看审核列表
define('API_GET_defectiveGoodsDeclare___id','get|defective-goods-declare/:id');
//@title 申报状态
define('API_GET_defectiveGoodsDeclare_status','get|defective-goods-declare/status');
//@title 审核是否通过
define('API_POST_defectiveGoodsDeclare_check','post|defective-goods-declare/check');

//控制器：app\index\controller\Phone
//@title 手机号管理列表
define('API_GET_phone','get|phone');
//@title 手机号管理获取
define('API_GET_phone___id','get|phone/:id');
//@title 手机号管理添加
define('API_POST_phone','post|phone');
//@title 切换状态
define('API_PUT_phone___id_status','put|phone/:id/status');
//@title 获取可用手机号列表
define('API_GET_phone_canUse','get|phone/can-use');
//@title 获取邮箱可用手机号列表
define('API_GET_phone_emailUse','get|phone/email-use');
//@title 获取关联的帐号
define('API_GET_phone___id_accounts','get|phone/:id/accounts');

//控制器：app\report\controller\WarehousePackage
//@title 仓库统计
define('API_GET_report_warehousePackage','get|report/warehouse-package');
//@title 未操作包裹详情
define('API_GET_report_warehousePackage_unpackedDetail','get|report/warehouse-package/unpacked-detail');
//@title 未发货记录
define('API_GET_report_warehousePackage_logUnfilled','get|report/warehouse-package/log-unfilled');
//@title 未发货记录详情
define('API_GET_report_warehousePackage_logUnfilledDetails','get|report/warehouse-package/log-unfilled-details');
//@title 已发货记录
define('API_GET_report_warehousePackage_logShipped','get|report/warehouse-package/log-shipped');
//@title 已发货记录详情
define('API_GET_report_warehousePackage_logShippedDetails','get|report/warehouse-package/log-shipped-details');
//@title 未拆包记录
define('API_GET_report_warehousePackage_logNotOpened','get|report/warehouse-package/log-not-opened');
//@title 缺货记录
define('API_GET_report_warehousePackage_logStock','get|report/warehouse-package/log-stock');
//@title 缺货记录详情
define('API_GET_report_warehousePackage_logStockDetails','get|report/warehouse-package/log-stock-details');
//@title 仓库列表
define('API_GET_report_warehousePackage_warehouse','get|report/warehouse-package/warehouse');
//@title 手动跑任务
define('API_GET_report_warehousePackage_manual','get|report/warehouse-package/manual');

//控制器：app\warehouse\controller\StockingAdvice
//@title 备货建议列表
define('API_GET_stockingAdvice','get|stocking-advice');
//@title 备货建议详情
define('API_GET_stockingAdvice___id','get|stocking-advice/:id');
//@title 获取备货数量
define('API_GET_stockingAdvice_stockingQuantity','get|stocking-advice/stocking-quantity');
//@title 状态信息
define('API_GET_stockingAdvice_status','get|stocking-advice/status');
//@title 最小起订量
define('API_GET_stockingAdvice_minQuantity','get|stocking-advice/min-quantity');
//@title 分配详情接口
define('API_GET_stockingAdvice___id_distributionDetails','get|stocking-advice/:id/distribution-details');
//@title 开发审核
define('API_PUT_stockingAdvice_developReview','put|stocking-advice/develop-review');
//@title 开发批量审批
define('API_PUT_stockingAdvice_batchDevelopReview','put|stocking-advice/batch-develop-review');
//@title 批量驳回接口
define('API_PUT_stockingAdvice_batchDevelopReject','put|stocking-advice/batch-develop-reject');
//@title 采购批量审核接口
define('API_PUT_stockingAdvice_developProcessingPlan','put|stocking-advice/develop-processing-plan');
//@title excel字段信息
define('API_GET_stockingAdvice_exportTitle','get|stocking-advice/export-title');
//@title 导出excel
define('API_POST_stockingAdvice_export','post|stocking-advice/export');
//@title 变更供应商
define('API_PUT_stockingAdvice_supplier','put|stocking-advice/supplier');

//控制器：app\index\controller\Email
//@title 新建邮箱
define('API_POST_email','post|email');
//@title 修改邮箱号
define('API_PUT_email___id','put|email/:id');
//@title 邮箱号列表
define('API_GET_email','get|email');
//@title 邮箱号详情
define('API_GET_email___id','get|email/:id');
//@title 查看密码
define('API_GET_email___id_password','get|email/:id/password');
//@title 批量去除错误信息
define('API_PUT_email_batch_errorMsg','put|email/batch/error-msg');
//@title 获取可用邮箱列表
define('API_GET_email_availableList','get|email/available-list');
//@title 获取已注册帐号的邮箱
define('API_GET_email_usedList','get|email/used-list');

//控制器：app\index\controller\Postoffice
//@title 邮局信息列表
define('API_GET_postoffice','get|postoffice');
//@title 获取单条邮局详情
define('API_GET_postoffice___id','get|postoffice/:id');
//@title 新增邮局信息
define('API_POST_postoffice','post|postoffice');
//@title 修改邮局信息
define('API_PUT_postoffice___id','put|postoffice/:id');
//@title 切换状态
define('API_PUT_postoffice___id_status','put|postoffice/:id/status');
//@title 获取可用邮局列表
define('API_GET_postoffice_availableList','get|postoffice/available-list');

//控制器：app\finance\controller\BankAccount
//@title 新增银行账户
define('API_POST_bankAccount','post|bank-account');
//@title 银行账户列表
define('API_GET_bankAccount','get|bank-account');
//@title 银行账户信息
define('API_GET_bankAccount___id','get|bank-account/:id');
//@title 更新银行记录
define('API_PUT_bankAccount___id','put|bank-account/:id');
//@title 导出csv
define('API_POST_bankAccount_export','post|bank-account/export');
//@title 获取银行信息
define('API_GET_bankAccount_bank','get|bank-account/bank');
//@title 城市信息列表
define('API_GET_bankAccount_cities','get|bank-account/cities');
//@title 省份信息列表
define('API_GET_bankAccount_provinces','get|bank-account/provinces');

//控制器：app\warehouse\controller\ReturnWaitShelves
//@title 列表
define('API_GET_returnWaitShelves','get|return-wait-shelves');
//@title 待入库详情
define('API_GET_returnWaitShelves___id_detail','get|return-wait-shelves/:id/detail');
//@title 批量重返上架
define('API_POST_returnWaitShelves_batch_save','post|return-wait-shelves/batch/save');
//@title 待入库详情
define('API_GET_returnWaitShelves_status','get|return-wait-shelves/status');

//控制器：app\index\controller\DarazAccount
//@title 保存新建资源
define('API_POST_darazAccount','post|daraz-account');
//@title 获取daraz站点
define('API_GET_darazAccount_sites','get|daraz-account/sites');
//@title 显示指定的资源
define('API_GET_darazAccount_read','get|daraz-account/read');
//@title Daraz账号管理列表
define('API_GET_darazAccount','get|daraz-account');
//@title 保存更新的资源
define('API_PUT_darazAccount___id','put|daraz-account/:id');
//@title 保存daraz账户授权
define('API_PUT_darazAccount_authorization','put|daraz-account/authorization');
//@title 系统状态切换
define('API_POST_darazAccount_changeStatus','post|daraz-account/change-status');

//控制器：app\index\controller\RegisterCompany
//@title 注册公司管理列表
define('API_GET_registerCompany','get|register-company');
//@title 添加法人信息
define('API_POST_registerCompany_legalInfo','post|register-company/legal-info');
//@title 更新法人信息
define('API_PUT_registerCompany___id_legalInfo','put|register-company/:id/legal-info');
//@title 获取法人信息详情
define('API_GET_registerCompany___id_legalInfo','get|register-company/:id/legal-info');
//@title 状态列表
define('API_GET_registerCompany_status','get|register-company/status');
//@title 保存公司信息
define('API_PUT_registerCompany___id_companyInfo','put|register-company/:id/company-info');
//@title 获取公司信息
define('API_GET_registerCompany___id_companyInfo','get|register-company/:id/company-info');
//@title 上传营业执照
define('API_PUT_registerCompany___id_charter','put|register-company/:id/charter');
//@title 保存结账信息
define('API_PUT_registerCompany___id_settlement','put|register-company/:id/settlement');
//@title 获取操作日志信息
define('API_GET_registerCompany___id_logs','get|register-company/:id/logs');
//@title 获取结账信息
define('API_GET_registerCompany___id_settlement','get|register-company/:id/settlement');
//@title 获取营业执照
define('API_GET_registerCompany___id_charter','get|register-company/:id/charter');

//控制器：app\customerservice\controller\KeywordsManage
//@title 关键词列表
define('API_GET_keywordsManage','get|keywords-manage');
//@title 显示一条记录
define('API_GET_keywordsManage_view','get|keywords-manage/view');
//@title 增加一条记录
define('API_POST_keywordsManage_add','post|keywords-manage/add');
//@title 删除一条记录
define('API_DELETE_keywordsManage_delete','delete|keywords-manage/delete');
//@title 关键词类型
define('API_GET_keywordsManage_type','get|keywords-manage/type');
//@title 渠道
define('API_GET_keywordsManage_channel','get|keywords-manage/channel');
//@title 根据权限过滤渠道
define('API_GET_keywordsManage_permissionedChannel','get|keywords-manage/permissioned-channel');
//@title 关键词启用状态
define('API_PUT_keywordsManage_keywordStatus','put|keywords-manage/keyword-status');

//控制器：app\customerservice\controller\KeywordsRecord
//@title 关键词抓取记录列表
define('API_GET_keywordsList','get|keywords-list');
//@title 增加关键词抓取记录
define('API_POST_keywordsList_add','post|keywords-list/add');
//@title 查看消息
define('API_GET_keywordsList_view','get|keywords-list/view');
//@title 关键词类型
define('API_GET_keywordsList_type','get|keywords-list/type');
//@title 获取渠道
define('API_GET_keywordsList_channel','get|keywords-list/channel');
//@title 获取ebay账号
define('API_GET_keywordsList_ebayAccount','get|keywords-list/ebay-account');
//@title 获取amazon账号
define('API_GET_keywordsList_amazonAccount','get|keywords-list/amazon-account');
//@title 获取aliexpress账号
define('API_GET_keywordsList_aliexpressAccount','get|keywords-list/aliexpress-account');

//控制器：app\publish\controller\AmazonShippingGroupName
//@title amazon运费模板名列表
define('API_GET_publish_amazonShippingGroupName','get|publish/amazon-shipping-group-name');
//@title 帐号运费模板名
define('API_GET_publish_amazonShippingGroupName___account_id_read','get|publish/amazon-shipping-group-name/:account_id/read');
//@title 添加模板名
define('API_POST_publish_amazonShippingGroupName','post|publish/amazon-shipping-group-name');
//@title 修改模板名
define('API_PUT_publish_amazonShippingGroupName','put|publish/amazon-shipping-group-name');
//@title 删除模板名
define('API_DELETE_publish_amazonShippingGroupName','delete|publish/amazon-shipping-group-name');

//控制器：app\order\controller\VirtualTracking
//@title 虚拟订单列表
define('API_GET_virtualTracking','get|virtual-tracking');
//@title 生成虚拟跟踪号
define('API_POST_virtualTracking___id_virtualNumber','post|virtual-tracking/:id/virtual-number');
//@title 保存虚拟跟踪号
define('API_PUT_virtualTracking___id_virtualNumber','put|virtual-tracking/:id/virtual-number');
//@title 批量生成虚拟跟踪号
define('API_POST_virtualTracking_batch_virtualNumber','post|virtual-tracking/batch/virtual-number');
//@title 批量保存虚拟跟踪号
define('API_PUT_virtualTracking_batch_virtualNumber','put|virtual-tracking/batch/virtual-number');
//@title 批量标记处理
define('API_PUT_virtualTracking_batch_dispose','put|virtual-tracking/batch/dispose');
//@title 导出execl
define('API_POST_virtualTracking_export','post|virtual-tracking/export');
//@title execl字段信息
define('API_GET_virtualTracking_title','get|virtual-tracking/title');

//控制器：app\publish\controller\EbayBestOffer
//@title 获取best offers列表
define('API_GET_ebay_bestOffers','get|ebay/best-offers');
//@title 同步best offer
define('API_POST_ebay_bestOffers_sync','post|ebay/best-offers/sync');
//@title 删除best offer
define('API_DELETE_ebay_bestOffers_batch','delete|ebay/best-offers/batch');
//@title 处理best offer
define('API_POST_ebay_bestOffers_batch','post|ebay/best-offers/batch');

//控制器：app\order\controller\DarazOrder
//@title 订单列表
define('API_GET_darazOrders','get|daraz-orders');
//@title 显示指定的资源
define('API_GET_darazOrders___id','get|daraz-orders/:id');
//@title 获取状态列表
define('API_GET_darazOrders_status','get|daraz-orders/status');
//@title 添加物流商
define('API_POST_darazOrders_addCarrier','post|daraz-orders/add-carrier');

//控制器：app\index\controller\CreditCard
//@title 信用卡账号列表
define('API_GET_creditCard','get|credit-card');
//@title 新增信用卡记录
define('API_POST_creditCard','post|credit-card');
//@title 显示信用卡详细.
define('API_GET_creditCard___id_edit','get|credit-card/:id/edit');
//@title 修改信用卡记录
define('API_POST_creditCard___id_update','post|credit-card/:id/update');
//@title 删除信用卡记录
define('API_DELETE_creditCard___id_delete','delete|credit-card/:id/delete');
//@title 查询信用卡类别列表
define('API_GET_creditCard_category','get|credit-card/category');

//控制器：app\index\controller\ChannelDistribution
//@title 获取展示的产品状态
define('API_GET_channelDistribution_status','get|channel-distribution/status');
//@title 获取一级分类
define('API_GET_channelDistribution_firstCategories','get|channel-distribution/first-categories');
//@title 获取站点
define('API_GET_channelDistribution___id_sites','get|channel-distribution/:id/sites');
//@title 获取平台帐号
define('API_GET_channelDistribution___id_accounts','get|channel-distribution/:id/accounts');
//@title 获取平台部门
define('API_GET_channelDistribution___id_departments','get|channel-distribution/:id/departments');
//@title 获取受限职位
define('API_GET_channelDistribution_positions','get|channel-distribution/positions');
//@title 整个保存
define('API_PUT_channelDistribution___id','put|channel-distribution/:id');
