<?php

//控制器：app\listing\controller\HealthData
//获取wish店铺数据监控
think\Route::get('/get-monitor-data$', 'listing/HealthData/getMonitorData',[], []);
//wish店铺数据监控
think\Route::post('/wish-shop-monitor$', 'listing/HealthData/monitor',[], []);
//wish店铺短信授权验证
think\Route::post('/wish-shop-auth$', 'listing/HealthData/auth',[], []);
//获取wish手机验证码
think\Route::post('/get-wish-mobile-code$', 'listing/HealthData/getCode',[], []);
//wish健康数据列表
think\Route::get('/wish-health-data-list$', 'listing/HealthData/lists',[], []);
//wish历史健康数据列表
think\Route::get('/wish-history-health-data$', 'listing/HealthData/history',[], []);

//控制器：app\warehouse\controller\StockOut
//出库列表
think\Route::get('/stock-out$', 'warehouse/StockOut/index',[], []);
//新建出库
think\Route::post('/stock-out$', 'warehouse/StockOut/save',[], []);
//出库获取
think\Route::get('/stock-out/:id$', 'warehouse/StockOut/read',[], ['id'=>'(\d+)']);
//出库编辑
think\Route::GET('/stock-out/:id/edit$', 'warehouse/StockOut/edit',[], ['id'=>'(\d+)']);
//出库删除
think\Route::DELETE('/stock-out/:id$', 'warehouse/StockOut/delete',[], ['id'=>'(\d+)']);
//类型列表
think\Route::get('/stock-out/types$', 'warehouse/StockOut/types',[], []);
//状态列表
think\Route::get('/stock-out/statuses$', 'warehouse/StockOut/statuses',[], []);
//操作出库
think\Route::post('/stock-out/do-stock-out$', 'warehouse/StockOut/doStockOut',[], []);
//出库审核
think\Route::post('/stock-out/audit$', 'warehouse/StockOut/audit',[], []);
//入库记录导出
think\Route::post('/stock-out/export$', 'warehouse/StockOut/export',[], []);
//海外仓包裹出库
think\Route::post('/stock-out/third-out$', 'warehouse/StockOut/thirdOut',[], []);
//出库导出字段
think\Route::get('/stock-out/export-fields$', 'warehouse/StockOut/getExportFields',[], []);
//获取商品详情
think\Route::get('/stock-out/get-goods$', 'warehouse/StockOut/getGoods',[], []);
//出库记录导入商品信息
think\Route::post('/stock-out/import-goods$', 'warehouse/StockOut/importGoods',[], []);

//控制器：app\index\controller\Channel
//平台列表
think\Route::get('/channel$', 'index/Channel/index',[], []);
//平台select列表(公共)
think\Route::get('/global/channels$', 'index/Channel/channels',[], []);
//平台账号
think\Route::get('/channel/channelAccounts$', 'index/Channel/channelAccounts',[], []);
//平台详情
think\Route::GET('/channel/:id$', 'index/Channel/read',[], []);
//添加平台
think\Route::POST('/channel$', 'index/Channel/save',[], []);
//更新平台
think\Route::put('/channel/:id$', 'index/Channel/update',[], ['id'=>'(\d+)']);
//状态修改
think\Route::post('/channel/states$', 'index/Channel/changeStatus',[], []);
//业务员列表
think\Route::get('/channel/seller-list$', 'index/Channel/seller',[], []);
//获取渠道占比信息
think\Route::get('/channel/:id/proportion$', 'index/Channel/getProportion',[], ['id'=>'(\d+)']);
//获取当前渠道对应的销售部门
think\Route::get('/channel/:id/departments$', 'index/Channel/getDepartmentByChannelId',[], ['id'=>'(\d+)']);
//保存渠道占比信息
think\Route::post('/channel/:id/proportion$', 'index/Channel/saveProportion',[], ['id'=>'(\d+)']);
//平台站点配置列表
think\Route::get('/channel/system-list$', 'index/Channel/getSystemConfig',[], []);
//新增平台配置
think\Route::get('/channel/add-config$', 'index/Channel/addConfig',[], []);
//引用系统平台配置
think\Route::post('/channel/:id/use-config$', 'index/Channel/useConfig',[], []);
//获取平台系统配置
think\Route::get('/channel/:id/config$', 'index/Channel/getConfigDetail',[], []);
//删除平台配置
think\Route::delete('/channel/config$', 'index/Channel/deleteConfig',[], []);
//更新平台参数配置
think\Route::put('/channel/config$', 'index/Channel/updateConfig',[], []);
//获取平台站点配置
think\Route::get('/channel/config$', 'index/Channel/getConfig',[], []);
//参数设置
think\Route::put('/channel/:id/config$', 'index/Channel/config',[], ['id'=>'(\d+)']);

//控制器：app\warehouse\controller\WarehouseGoods
//库存列表
think\Route::get('/warehouse-goods$', 'warehouse/WarehouseGoods/index',[], []);
//查看缺货明细
think\Route::get('/warehouse-goods/:id/oos-details$', 'warehouse/WarehouseGoods/oosDetails',[], []);
//获取所有仓库
think\Route::get('/warehouse-goods/getWarehouse$', 'warehouse/WarehouseGoods/getWarehouse',[], []);
//库存获取
think\Route::get('/warehouse-goods/:id$', 'warehouse/WarehouseGoods/read',[], ['id'=>'(\d+)']);
//批量修改平台预警数
think\Route::post('/warehouse-goods/alert$', 'warehouse/WarehouseGoods/alert',[], []);
//获取sku可库存数
think\Route::get('/warehouse-goods/available_quantity$', 'warehouse/WarehouseGoods/availableQuantity',[], []);
//海外仓库存
think\Route::get('/warehouse-goods/overseas$', 'warehouse/WarehouseGoods/overseas',[], []);
//分配
think\Route::get('/warehouse-goods/:id/allot$', 'warehouse/WarehouseGoods/allotInfo',[], ['id'=>'(\d+)']);
//确认分配
think\Route::post('/warehouse-goods/:id/allot$', 'warehouse/WarehouseGoods/allot',[], ['id'=>'(\d+)']);
//申请备货信息
think\Route::get('/warehouse-goods/apply$', 'warehouse/WarehouseGoods/applyInfo',[], []);
//申请备货
think\Route::post('/warehouse-goods/apply$', 'warehouse/WarehouseGoods/apply',[], []);
//操作日志
think\Route::get('/warehouse-goods/logs$', 'warehouse/WarehouseGoods/logs',[], []);
//本地仓
think\Route::get('/warehouse-goods/local$', 'warehouse/WarehouseGoods/local',[], []);
//第三方仓库
think\Route::get('/warehouse-goods/third$', 'warehouse/WarehouseGoods/third',[], []);
//fba库存
think\Route::get('/warehouse-goods/fba$', 'warehouse/WarehouseGoods/fba',[], []);
//库存详情
think\Route::get('/warehouse-goods/:id/detail$', 'warehouse/WarehouseGoods/detail',[], ['id'=>'(\d+)']);
//库存期初
think\Route::post('/warehouse-goods/init$', 'warehouse/WarehouseGoods/start',[], []);
//库存期初
think\Route::post('/warehouse-goods/purchase-in$', 'warehouse/WarehouseGoods/purchaseIn',[], []);
//导出仓库商品库存
think\Route::POST('/warehouse-goods/export$', 'warehouse/WarehouseGoods/export',[], []);
//第三方导出字段
think\Route::get('/warehouse-goods/export-fields$', 'warehouse/WarehouseGoods/getExportFields',[], []);
//sku状态筛选列表
think\Route::get('/warehouse-goods/sku-status$', 'warehouse/WarehouseGoods/getSkuStatus',[], []);
//第三方库存同步
think\Route::post('/warehouse-goods/sync$', 'warehouse/WarehouseGoods/sync',[], []);
//第三方库存同步
think\Route::post('/warehouse-goods/third-sync$', 'warehouse/WarehouseGoods/thirdSync',[], []);
//第三方sku库存导入
think\Route::post('/warehouse-goods/third-import$', 'warehouse/WarehouseGoods/thirdImport',[], []);
//库存调整
think\Route::get('/warehouse-goods/waiting-shipping-quantity$', 'warehouse/WarehouseGoods/waitingShippingQuantity',[], []);
//库存信息调整
think\Route::get('/warehouse-goods/restore$', 'warehouse/WarehouseGoods/restore',[], []);
//无api批量入库
think\Route::post('/warehouse-goods/no-api-in$', 'warehouse/WarehouseGoods/noApiIN',[], []);
//平台分库存详情
think\Route::get('/warehouse-goods/channel-detail$', 'warehouse/WarehouseGoods/channelDetails',[], []);
//第三方平台分库存借调
think\Route::post('/warehouse-goods/third-allocate$', 'warehouse/WarehouseGoods/thirdAllocate',[], []);
//第三方平台分库存批量借调
think\Route::post('/warehouse-goods/third-multi-allocate$', 'warehouse/WarehouseGoods/thirdMultiAllocate',[], []);
//第三方申请平台分库存调入库存
think\Route::get('/warehouse-goods/apply-allocate-in$', 'warehouse/WarehouseGoods/applyAllocateIn',[], []);
//获取第三方仓库sku的平台库存详情
think\Route::get('/warehouse-goods/third_channel_detail$', 'warehouse/WarehouseGoods/getThirdSkuChannelDetail',[], []);
//获取海外仓库sku的平台库存详情
think\Route::get('/warehouse-goods/oversea_channel_detail$', 'warehouse/WarehouseGoods/getOverSeasSkuChannelDetail',[], []);
//获取中转仓待发库存明细
think\Route::get('/warehouse-goods/shipping_detail$', 'warehouse/WarehouseGoods/getShippingDetail',[], []);
//sku货位记录
think\Route::get('/warehouse-goods/cargo-log$', 'warehouse/WarehouseGoods/getCargoLog',[], []);
//打印fba商品条码
think\Route::get('/warehouse-goods/barcode$', 'warehouse/WarehouseGoods/barcode',[], []);
//活动备货详情
think\Route::get('/warehouse-goods/stocking-detail$', 'warehouse/WarehouseGoods/getStockingDetail',[], []);
//备货锁定详情
think\Route::get('/warehouse-goods/lock-detail$', 'warehouse/WarehouseGoods/getLockDetail',[], []);

//控制器：app\warehouse\controller\StockIn
//新建入库
think\Route::get('/stock-in$', 'warehouse/StockIn/index',[], []);
//新建入库
think\Route::post('/stock-in$', 'warehouse/StockIn/save',[], []);
//入库获取
think\Route::get('/stock-in/:id$', 'warehouse/StockIn/read',[], ['id'=>'(\d+)']);
//入库编辑
think\Route::GET('/stock-in/:id/edit$', 'warehouse/StockIn/edit',[], ['id'=>'(\d+)']);
//入库删除
think\Route::DELETE('/stock-in/:id$', 'warehouse/StockIn/delete',[], ['id'=>'(\d+)']);
//类型列表
think\Route::get('/stock-in/types$', 'warehouse/StockIn/types',[], []);
//操作入库
think\Route::post('/stock-in/do-stock-in$', 'warehouse/StockIn/doStockIn',[], []);
//状态列表
think\Route::get('/stock-in/statuses$', 'warehouse/StockIn/statuses',[], []);
//入库审核
think\Route::post('/stock-in/audit$', 'warehouse/StockIn/audit',[], []);
//新增入库获取产品信息
think\Route::get('/stock-in/get-goods$', 'warehouse/StockIn/getGoods',[], []);
//入库记录导出
think\Route::post('/stock-in/export$', 'warehouse/StockIn/export',[], []);
//出库导出字段
think\Route::get('/stock-in/export-fields$', 'warehouse/StockIn/getExportFields',[], []);
//入库记录导入商品信息
think\Route::post('/stock-in/import-goods$', 'warehouse/StockIn/importGoods',[], []);

//控制器：app\warehouse\controller\Stock
//出入库记录列表
think\Route::get('/stock$', 'warehouse/Stock/index',[], []);
//出入库记录添加
think\Route::post('/stock$', 'warehouse/Stock/save',[], []);
//出入库记录获取
think\Route::get('/stock/:id$', 'warehouse/Stock/read',[], ['id'=>'(\d+)']);
//出入库记录编辑
think\Route::GET('/stock/:id/edit$', 'warehouse/Stock/edit',[], ['id'=>'(\d+)']);
//出入库记录更新
think\Route::PUT('/stock/:id$', 'warehouse/Stock/update',[], ['id'=>'(\d+)']);
//出入库记录删除
think\Route::DELETE('/stock/:id$', 'warehouse/Stock/delete',[], ['id'=>'(\d+)']);
//获取仓库和出入库状态
think\Route::get('/stock/getWarehouseStockStatus$', 'warehouse/Stock/getWarehouseStockStatus',[], []);

//控制器：app\index\controller\Role
//角色管理列表
think\Route::get('/role$', 'index/Role/index',[], []);
//新增
think\Route::post('/role$', 'index/Role/save',[], []);
//角色管理获取
think\Route::get('/role/:id$', 'index/Role/read',[], ['id'=>'(\d+)']);
//修改
think\Route::PUT('/role/:id$', 'index/Role/update',[], ['id'=>'(\d+)']);
//删除
think\Route::delete('/role/:id$', 'index/Role/delete',[], []);
//停用，启用账号'
think\Route::get('/role/changeStatus$', 'index/Role/changeStatus',[], []);
//授权
think\Route::get('/role/authorization$', 'index/Role/authorization',[], []);
//添加成员
think\Route::get('/role/addUser$', 'index/Role/addUser',[], []);
//获取角色节点权限
think\Route::get('/role/:roleid/node/:nodeid/access$', 'index/Role/getNodeAccess',[], []);
//保存角色节点权限
think\Route::POST('/role/:roleid/node/:nodeid/access$', 'index/Role/setNodeAccess',[], []);
//获取角色已配路由
think\Route::get('/role/:roleid/mcas$', 'index/Role/getMcas',[], []);
//设置角色已配路由
think\Route::post('/role/:roleid/mcas$', 'index/Role/setMcas',[], []);
//复制角色
think\Route::post('/role/:role_id/copy$', 'index/Role/copy',[], []);

//控制器：app\warehouse\controller\Delivery
//配货管理列表
think\Route::get('/delivery$', 'warehouse/Delivery/index',[], []);
//退回至自动规则前
think\Route::post('/delivery/back-rule$', 'warehouse/Delivery/backBeforeDelivery',[], []);
//邮寄方式
think\Route::get('/delivery/shippingMethod$', 'warehouse/Delivery/shippingMethod',[], []);
//获取所有仓库
think\Route::get('/delivery/getWarehouseChannel$', 'warehouse/Delivery/getWarehouseChannel',[], []);
//获取配货账号
think\Route::get('/delivery/accounts$', 'warehouse/Delivery/accounts',[], []);
//orderCounts
think\Route::get('/delivery/orderCounts$', 'warehouse/Delivery/orderCounts',[], []);
//获取平台列表
think\Route::get('/delivery/channels$', 'warehouse/Delivery/channels',[], []);
//分配库存
think\Route::get('/delivery/distriuteInventory$', 'warehouse/Delivery/distriuteInventory',[], []);
//改变仓库
think\Route::put('/delivery/changeWarehouse$', 'warehouse/Delivery/changeWarehouse',[], []);
//包裹类型
think\Route::get('/delivery/package-type$', 'warehouse/Delivery/packageType',[], []);

//控制器：app\warehouse\controller\PlaceOrder
//物流商下单管理列表
think\Route::get('/placeorder$', 'warehouse/PlaceOrder/index',[], []);
//海外仓物流商下单
think\Route::get('/placeorder/third$', 'warehouse/PlaceOrder/third',[], []);
//获取运输方式
think\Route::get('/placeorder/shipping-method$', 'warehouse/PlaceOrder/shippingMethod',[], []);
//状态列表
think\Route::get('/placeorder/:type/statuses$', 'warehouse/PlaceOrder/statuses',[], []);
//账号列表
think\Route::get('/placeorder/accounts$', 'warehouse/PlaceOrder/accounts',[], []);
//获取平台列表
think\Route::get('/placeorder/channels$', 'warehouse/PlaceOrder/channels',[], []);
//批量上传
think\Route::get('/placeorder/batchUpload$', 'warehouse/PlaceOrder/batchUpload',[], []);
//无api确认上传
think\Route::post('/placeorder/confirmUpload$', 'warehouse/PlaceOrder/confirmUpload',[], []);
//无API仓库导出
think\Route::post('/placeorder/export$', 'warehouse/PlaceOrder/export',[], []);
//交运
think\Route::post('/placeorder/shipping$', 'warehouse/PlaceOrder/shipping',[], []);
//推送管易
think\Route::put('/placeorder/pushGuanyi$', 'warehouse/PlaceOrder/pushGuanyi',[], []);
//释放包裹
think\Route::post('/placeorder/reback$', 'warehouse/PlaceOrder/reback',[], []);
//速卖通线上发货预报云途
think\Route::put('/placeorder/uploadYt$', 'warehouse/PlaceOrder/uploadYt',[], []);
//lazada物流商上传
think\Route::post('/placeorder/lazada-upload$', 'warehouse/PlaceOrder/LazadaUpload',[], []);
//vova上传线上物流
think\Route::post('/placeorder/vova$', 'warehouse/PlaceOrder/VoVaUpload',[], []);
//vova获取线上物流跟踪号
think\Route::post('/placeorder/vova-tacking$', 'warehouse/PlaceOrder/vovaTacking',[], []);

//控制器：app\warehouse\controller\Allocation
//列表
think\Route::get('/allocation$', 'warehouse/Allocation/index',[], []);
//详情
think\Route::get('/allocation/:id$', 'warehouse/Allocation/read',[], ['id'=>'(\d+)']);
//更新
think\Route::PUT('/allocation/:id$', 'warehouse/Allocation/update',[], ['id'=>'(\d+)']);
//状态列表
think\Route::get('/allocation/status-list$', 'warehouse/Allocation/statusList',[], []);
//保存
think\Route::post('/allocation$', 'warehouse/Allocation/save',[], []);
//审核
think\Route::put('/allocation/:id/audit$', 'warehouse/Allocation/audit',[], []);
//出库
think\Route::post('/allocation/:id/deliver$', 'warehouse/Allocation/deliver',[], []);
//入库
think\Route::post('/allocation/:id/entry$', 'warehouse/Allocation/entry',[], []);
//获取商品详情
think\Route::get('/allocation/get-goods$', 'warehouse/Allocation/getGoods',[], []);
//导入商品信息
think\Route::post('/allocation/import-goods$', 'warehouse/Allocation/importGoods',[], []);
//获取附件信息
think\Route::get('/allocation/:id/get-attachment$', 'warehouse/Allocation/getAttachment',[], ['id'=>'(\d+)']);
//上传附件信息
think\Route::post('/allocation/:id/upload-attachment$', 'warehouse/Allocation/uploadAttachment',[], ['id'=>'(\d+)']);
//日志信息
think\Route::get('/allocation/:id/logs$', 'warehouse/Allocation/getLog',[], ['id'=>'(\d+)']);
//下载附件
think\Route::get('/allocation/attachment$', 'warehouse/Allocation/attachment',[], []);
//删除附件
think\Route::post('/allocation/:id/delete-attachment$', 'warehouse/Allocation/delAttachment',[], ['id'=>'(\d+)']);
//引用备货计划
think\Route::get('/allocation/stocking-detail$', 'warehouse/Allocation/getStockingDetail',[], []);
//装箱清单
think\Route::get('/allocation/:id/box-list$', 'warehouse/Allocation/getBoxList',[], ['id'=>'(\d+)']);
//FNSKU校验
think\Route::post('/allocation/verify-fnsku$', 'warehouse/Allocation/verifyFnsku',[], []);
//调拨单作废
think\Route::put('/allocation/:id/cancel$', 'warehouse/Allocation/cancel',[], ['id'=>'(\d+)']);
//调拨单强制作废
think\Route::put('/allocation/:id/force-cancel$', 'warehouse/Allocation/forceCancel',[], ['id'=>'(\d+)']);
//查看调拨装箱详情
think\Route::get('/allocation/detail$', 'warehouse/Allocation/getDetail',[], []);
//调拨测试
think\Route::get('/allocation/test$', 'warehouse/Allocation/test',[], []);

//控制器：app\index\controller\Task
//任务列表
think\Route::get('/task$', 'index/Task/index',[], []);
//任务工作器类列表
think\Route::get('/task/classes$', 'index/Task/taskClasses',[], []);
//某任务工作器的执行列表
think\Route::get('/task/workers$', 'index/Task/taskWorkers',[], []);
//某任务工作器安装
think\Route::get('/task/install$', 'index/Task/taskInstall',[], []);
//某任务工作器卸载
think\Route::get('/task/uninstall$', 'index/Task/taskUninstall',[], []);
//重新加载类(任务)
think\Route::get('/task/reloadclass$', 'index/Task/reload',[], []);
//任务参数规则
think\Route::get('/task/:taskId/rules$', 'index/Task/rules',[], []);
//启停任务
think\Route::put('/task/:taskId/status$', 'index/Task/status',[], []);
//任务信息
think\Route::get('/task/worker/:workerId$', 'index/Task/worker_get',[], []);
//启停工作任务
think\Route::put('/task/worker/status/:workerId$', 'index/Task/worker_status',[], []);
//修改任务信息
think\Route::put('/task/worker/:workerId$', 'index/Task/worker_mdf',[], []);
//添加工作任务
think\Route::post('/task/worker$', 'index/Task/worker_new',[], []);
//删除工作任务
think\Route::delete('/task/worker$', 'index/Task/worker_rem',[], []);
//查看工作任务日志
think\Route::get('/task/worker/:workerId/logs$', 'index/Task/worker_log',[], []);
//修改任务时间
think\Route::put('/task/worker/:workerId/changetime$', 'index/Task/worker_changetime',[], []);
//同步任务
think\Route::post('/task/synchronous$', 'index/Task/synchronous',[], []);
//时间任务调度信息
think\Route::get('/task/worker_schedulers$', 'index/Task/worker_schedulers',[], []);
//获取全局任务
think\Route::get('/task/global_tasks$', 'index/Task/global_tasks',[], []);
//添加全局任务
think\Route::put('/task/global_task$', 'index/Task/global_task_add',[], []);
//改数全局任务进程数
think\Route::put('/task/global_task_change$', 'index/Task/global_task_change',[], []);

//控制器：app\api\controller\Guanyiwarehouse
//库存异动接口
think\Route::POST('/api/Guanyiwarehouse/inventoryChanged$', 'api/Guanyiwarehouse/inventoryChanged',[], []);
//发货回传接口
think\Route::POST('/api/Guanyiwarehouse/deliveryReturn$', 'api/Guanyiwarehouse/deliveryReturn',[], []);
//拒单接口
think\Route::POST('/api/Guanyiwarehouse/rejectPackage$', 'api/Guanyiwarehouse/rejectPackage',[], []);

//控制器：app\index\controller\DashBoard
//最近15天平台订单总数
think\Route::get('/dashboard/nearby15$', 'index/DashBoard/nearby15',[], []);
//最近2天平台订单总数
think\Route::get('/dashboard/nearby2$', 'index/DashBoard/nearby2',[], []);
//最近15天平台订单总数[钉钉]
think\Route::get('/dashboard/dingtalk-nearby15$', 'index/DashBoard/nearby16',[], []);
//最近15天平台FBA订单总数
think\Route::get('/dashboard/fba-nearby15$', 'index/DashBoard/fbaNearby15',[], []);
//查询账号业绩
think\Route::get('/dashboard/account-performance$', 'index/DashBoard/accountPerformance',[], []);
//订单管理
think\Route::get('/dashboard/orders$', 'index/DashBoard/orderInfo',[], []);
//订单管理
think\Route::get('/dashboard/listings$', 'index/DashBoard/listingCount',[], []);
//仓库信息
think\Route::get('/dashboard/warehouses$', 'index/DashBoard/warehouseInfo',[], []);
//账号销售量统计
think\Route::get('/dashboard/account-info$', 'index/DashBoard/accountInfo',[], []);

//控制器：app\index\controller\ChannelAccount
//搜索账号
think\Route::get('/channel-account/search$', 'index/ChannelAccount/search',[], []);

//控制器：app\index\controller\EbayAccount
//ebay账号列表
think\Route::GET('/ebay-account$', 'index/EbayAccount/index',[], []);
//新增ebay账号
think\Route::POST('/ebay-account$', 'index/EbayAccount/save',[], []);
//查看ebay账号
think\Route::GET('/ebay-account/:id$', 'index/EbayAccount/read',[], []);
//编辑
think\Route::GET('/ebay-account/:id/edit$', 'index/EbayAccount/edit',[], []);
//更新
think\Route::PUT('/ebay-account/:id$', 'index/EbayAccount/update',[], []);
//ebay批量设置批量设置抓取参数；
think\Route::post('/ebay-account/set$', 'index/EbayAccount/batchSet',[], []);
//启用/停用 账号
think\Route::POST('/ebay-account/status$', 'index/EbayAccount/changeStatus',[], []);
//获取session的ID
think\Route::POST('/ebay-account/getEbaySessionId$', 'index/EbayAccount/getEbaySessionId',[], []);
//获取援权的token
think\Route::POST('/ebay-account/getFetchEbayToken$', 'index/EbayAccount/getFetchEbayToken',[], []);
//检测账号用户
think\Route::POST('/ebay-account/getConfirmIdentity$', 'index/EbayAccount/getConfirmIdentity',[], []);
//验证ebay的token是否有效
think\Route::POST('/ebay-account/geteBayOfficialTime$', 'index/EbayAccount/geteBayOfficialTime',[], []);
//查看 - ebay账号绑定paypal
think\Route::GET('/ebay-account/mapPaypal/view$', 'index/EbayAccount/ebayMapPaypalView',[], []);
//ebay绑定paypal邮箱
think\Route::POST('/ebay-account/ebayMapPaypal$', 'index/EbayAccount/ebayMapPaypal',[], []);
//ebay帐号绑定paypal下载；
think\Route::GET('/ebay-account/down$', 'index/EbayAccount/down',[], []);
//ebay帐号获取通知配置；
think\Route::GET('/ebay-account/getevent$', 'index/EbayAccount/getEventfield',[], []);
//ebay帐号设置通知配置；
think\Route::POST('/ebay-account/setEvent$', 'index/EbayAccount/setNotification',[], []);
//oauth 认证时，获取登录链接
think\Route::GET('/ebay-account/:account_id/oauth-login$', 'index/EbayAccount/getOAuthLoginUrl',[], []);
//oauth 认证时，获取token并保存
think\Route::POST('/ebay-account/:account_id/oauth-token$', 'index/EbayAccount/getOAuthToken',[], []);

//控制器：app\index\controller\PaypalAccount
//列表
think\Route::GET('/paypal-account$', 'index/PaypalAccount/index',[], []);
//新增
think\Route::POST('/paypal-account$', 'index/PaypalAccount/save',[], []);
//查看
think\Route::GET('/paypal-account/:id$', 'index/PaypalAccount/read',[], []);
//编辑
think\Route::GET('/paypal-account/:id/edit$', 'index/PaypalAccount/edit',[], []);
//更新
think\Route::PUT('/paypal-account/:id$', 'index/PaypalAccount/update',[], []);
//paypal授权
think\Route::PUT('/paypal-account/:id/authorization$', 'index/PaypalAccount/authorization',[], []);
//paypal显示邮箱密码
think\Route::GET('/paypal-account/show$', 'index/PaypalAccount/show',[], []);
//启用/停用 账号
think\Route::POST('/paypal-account/status$', 'index/PaypalAccount/changeStatus',[], []);
//获取paypal账号
think\Route::GET('/paypal-account/account$', 'index/PaypalAccount/getPaypalAccount',[], []);
//批量开启
think\Route::post('/paypal-account/batch-set$', 'index/PaypalAccount/batchSet',[], []);
//设置paypal通知
think\Route::POST('/paypal-account/events$', 'index/PaypalAccount/setNotifacation',[], []);
//获取paypal通知
think\Route::GET('/paypal-account/:id/events$', 'index/PaypalAccount/getNotifacation',[], []);

//控制器：app\index\controller\Download
//下载模板文件
think\Route::get('/downfile$', 'index/Download/index',[], []);

//控制器：app\system\controller\Menu
//菜单列表
think\Route::get('/system/menu$', 'system/Menu/index',[], []);
//前端菜单数据
think\Route::get('/menu/pages$', 'system/Menu/pages',[], []);
//编辑菜单
think\Route::put('/system/menu/:id$', 'system/Menu/setting',[], []);
//改变状态
think\Route::put('/system/menu/change-status$', 'system/Menu/change_status',[], []);
//添加菜单
think\Route::post('/system/menu/add$', 'system/Menu/add',[], []);
//改变
think\Route::put('/system/menu/change$', 'system/Menu/change',[], []);
//菜单删除
think\Route::DELETE('/system/menu$', 'system/Menu/delete',[], []);

//控制器：app\system\controller\Time
//获取当前系统时间
think\Route::get('/system/time$', 'system/Time/time',[], []);

//控制器：app\system\controller\Release
//版本管理列表
think\Route::get('/release$', 'system/Release/index',[], []);
//版本管理添加
think\Route::post('/release$', 'system/Release/save',[], []);
//版本管理删除
think\Route::DELETE('/release/:id$', 'system/Release/delete',[], ['id'=>'(\d+)']);
//标识已读
think\Route::post('/release/:id/read$', 'system/Release/read',[], ['id'=>'(\d+)']);
//获取标识已读
think\Route::get('/release/reads$', 'system/Release/getReads',[], []);

//控制器：app\purchase\controller\PurchaseArrival
//列表
think\Route::get('/Purchase/PurchaseArrival/index$', 'purchase/PurchaseArrival/index',[], []);
//到货->确定按钮
think\Route::POST('/Purchase/PurchaseArrival/arrival$', 'purchase/PurchaseArrival/arrival',[], []);
//到货->列表
think\Route::GET('/Purchase/PurchaseArrival/orderDetail$', 'purchase/PurchaseArrival/orderDetail',[], []);
//测试
think\Route::get('/Purchase/PurchaseArrival/test$', 'purchase/PurchaseArrival/test',[], []);
//免捡产品
think\Route::get('/Purchase/PurchaseArrival/freePick$', 'purchase/PurchaseArrival/freePick',[], []);
//打印标签
think\Route::get('/Purchase/PurchaseArrival/printLabel$', 'purchase/PurchaseArrival/printLabel',[], []);
//分派
think\Route::get('/Purchase/PurchaseArrival/assignment$', 'purchase/PurchaseArrival/assignment',[], []);
//获取员工信息
think\Route::get('/Purchase/getEmployee$', 'purchase/PurchaseArrival/getEmployee',[], []);
//获取仓库信息
think\Route::get('/Purchase/getWarehouse$', 'purchase/PurchaseArrival/getWarehouse',[], []);

//控制器：app\purchase\controller\PurchasePlan
// 显示采购计划列表
think\Route::get('/purchase-plan$', 'purchase/PurchasePlan/index',[], []);
//新增采购计划
think\Route::post('/purchase-plan$', 'purchase/PurchasePlan/save',[], []);
//采购计划获取
think\Route::get('/purchase-plan/:id$', 'purchase/PurchasePlan/read',[], ['id'=>'(\d+)']);
//批量编辑
think\Route::post('/purchase-plan/batchEdit$', 'purchase/PurchasePlan/batchEdit',[], []);
//采购计划编辑
think\Route::GET('/purchase-plan/:id/edit$', 'purchase/PurchasePlan/edit',[], ['id'=>'(\d+)']);
//采购计划更新
think\Route::PUT('/purchase-plan/:id$', 'purchase/PurchasePlan/update',[], ['id'=>'(\d+)']);
// 批量审核
think\Route::post('/purchase-plan/changeStatus$', 'purchase/PurchasePlan/changeStatus',[], []);
//获取采购计划展开的详情
think\Route::GET('/purchase-plan/getDetail$', 'purchase/PurchasePlan/getDetail',[], []);
// 获取操作日志
think\Route::GET('/purchase-plan/getLogDetail$', 'purchase/PurchasePlan/getLogDetail',[], []);
// 取sku的基本信息
think\Route::GET('/purchase-plan/getSkuInfo$', 'purchase/PurchasePlan/getSkuInfo',[], []);
// 添加或者取备注
think\Route::post('/purchase-plan/remarks$', 'purchase/PurchasePlan/remarks',[], []);
// 采购计划审核前查看采购计划价格变化情况
think\Route::get('/purchase-plan/getPurchasePlanPriceChange$', 'purchase/PurchasePlan/getPurchasePlanPriceChange',[], []);
// 采购计划价格审核
think\Route::post('/purchase-plan/purchasePlanPriceAudit$', 'purchase/PurchasePlan/purchasePlanPriceAudit',[], []);
//导入采购计划
think\Route::post('/purchase-plan/import$', 'purchase/PurchasePlan/excelImport',[], []);
//导入商品列表
think\Route::post('/purchase-plan/sku/import$', 'purchase/PurchasePlan/excelSkusImport',[], []);
//导出采购计划
think\Route::POST('/purchase-plan/export$', 'purchase/PurchasePlan/export',[], []);
//获取导出所有字段
think\Route::get('/purchase-plan/export-fields$', 'purchase/PurchasePlan/getExportFields',[], []);
//取消采购
think\Route::post('/purchase-plan/cancel$', 'purchase/PurchasePlan/cancel',[], []);
//批量删除
think\Route::post('/purchase-plan/batch/delete$', 'purchase/PurchasePlan/batchDelete',[], []);
//批量修改采购员
think\Route::put('/purchase-plan/purchaser$', 'purchase/PurchasePlan/changePurchaser',[], []);
//批量修改结算方式
think\Route::put('/purchase-plan/balance-type$', 'purchase/PurchasePlan/changeBalanceType',[], []);

//控制器：app\purchase\controller\PurchaseOrder
//显示列表
think\Route::get('/purchase-order$', 'purchase/PurchaseOrder/index',[], []);
// 查看
think\Route::get('/purchase-order/:id$', 'purchase/PurchaseOrder/read',[], ['id'=>'(\d+)']);
// 显示编辑
think\Route::GET('/purchase-order/:id/edit$', 'purchase/PurchaseOrder/edit',[], ['id'=>'(\d+)']);
// 更新
think\Route::PUT('/purchase-order/:id$', 'purchase/PurchaseOrder/update',[], ['id'=>'(\d+)']);
//改变状态
think\Route::POST('/purchase-order/changeStatus$', 'purchase/PurchaseOrder/changeStatus',[], []);
//批量改变状态
think\Route::POST('/purchase-order/batch/status$', 'purchase/PurchaseOrder/batchChangeStatus',[], []);
// 获取批量申请付款
think\Route::GET('/purchase-order/applyPayment$', 'purchase/PurchaseOrder/applyPayment',[], []);
// 提交批量申请付款
think\Route::POST('/purchase-order/batchApplyPayment$', 'purchase/PurchaseOrder/batchApplyPayment',[], []);
//  删除
think\Route::DELETE('/purchase-order/:id$', 'purchase/PurchaseOrder/delete',[], ['id'=>'(\d+)']);
// 获取采购订单展开的详情
think\Route::GET('/purchase-order/getDetail$', 'purchase/PurchaseOrder/getDetail',[], []);
// 获取操作日志
think\Route::GET('/purchase-order/getLogDetail$', 'purchase/PurchaseOrder/getLogDetail',[], []);
// 添加或者取备注
think\Route::POST('/purchase-order/remarks$', 'purchase/PurchaseOrder/remarks',[], []);
// 采购单不等待剩余申请
think\Route::post('/purchase-order/purchaseNotWaitingAudit$', 'purchase/PurchaseOrder/purchaseNotWaitingAudit',[], []);
// 采购单收货记录
think\Route::get('/purchase-order/getArrivalRecords$', 'purchase/PurchaseOrder/getArrivalRecords',[], []);
// 采购单不等待剩余记录
think\Route::get('/purchase-order/records/defective$', 'purchase/PurchaseOrder/getDefectiveRecords',[], []);
//获取采购订单物流跟踪信息
think\Route::get('/purchase-order/getTraceInformation$', 'purchase/PurchaseOrder/getTraceInformation',[], []);
//通过采购单id批量获取物流信息
think\Route::get('/purchase-order/getTraceInformationBatch$', 'purchase/PurchaseOrder/getTraceInformationBatch',[], []);
//获取采购物流信息
think\Route::get('/purchase-order/get-logistics$', 'purchase/PurchaseOrder/getLogistics',[], []);
//更新采购单物流信息
think\Route::put('/purchase-order/logistics/:id$', 'purchase/PurchaseOrder/logistics',[], []);
//通过采购单的ID列表找到SKU的缺货数，如果以后缺货数小于设置的数值，则显示紧急
think\Route::get('/purchase-order/getPurchaseSkuBeOutOfStock$', 'purchase/PurchaseOrder/getPurchaseSkuBeOutOfStock',[], []);
//导出采购订单
think\Route::POST('/purchase-order/export$', 'purchase/PurchaseOrder/export',[], []);
//采购导出字段
think\Route::get('/purchase-order/export-fields$', 'purchase/PurchaseOrder/getExportFields',[], []);
//获取采购单
think\Route::get('/purchase-order/get-orders$', 'purchase/PurchaseOrder/getOrders',[], []);
//不等待剩余批量审核
think\Route::put('/purchase-order/status$', 'purchase/PurchaseOrder/status',[], []);
//导入
think\Route::post('/purchase-order/import$', 'purchase/PurchaseOrder/import',[], []);
//添加外部流水号到缓存
think\Route::put('/purchase-order/numbers$', 'purchase/PurchaseOrder/externalNumber',[], []);
//根据外部跟踪号查询物流路径信息
think\Route::get('/purchase-order/:external_number/logisticsTraceInfos$', 'purchase/PurchaseOrder/getLogisticsTraceInfo',[], []);
//设置采购单条目备注
think\Route::post('/purchase-order/:id/remarks$', 'purchase/PurchaseOrder/detailRemark',[], []);
//采购作废审核
think\Route::put('/purchase-order/:id/invalid$', 'purchase/PurchaseOrder/invalidAudit',[], []);
//采购单作废并退回采购计划
think\Route::post('/purchase-order/batchInvalid$', 'purchase/PurchaseOrder/batchInvalid',[], []);
//采购作废申请
think\Route::put('/purchase-order/:id/invalidApply$', 'purchase/PurchaseOrder/invalidApply',[], []);
//确认到货
think\Route::post('/purchase-order/sure-arrival$', 'purchase/PurchaseOrder/sureArrival',[], []);
//获取列表汇总信息
think\Route::get('/purchase-order/calculating-money$', 'purchase/PurchaseOrder/calculatingMoney',[], []);
//下载SKU标签
think\Route::get('/purchase-order/down-sku-label$', 'purchase/PurchaseOrder/downSkuLabel',[], []);
//批量修改结算方式
think\Route::put('/purchase-order/balance-type$', 'purchase/PurchaseOrder/changeBalanceType',[], []);
//手动设置长时间未拆
think\Route::POST('/purchase-order/long-time$', 'purchase/PurchaseOrder/setLongTime',[], []);
//PO缺失列表未处理包裹选择其他异常类型
think\Route::PUT('/purchase-order/:abnormal_id/other-abnormal-type$', 'purchase/PurchaseOrder/otherAbnormalType',[], []);
//长时间未拆列表
think\Route::GET('/purchase-order/long-time$', 'purchase/PurchaseOrder/getLongTime',[], []);
//查看未拆异常信息
think\Route::GET('/purchase-order/abnormal/:abnormal_id$', 'purchase/PurchaseOrder/getAbnormalInfo',[], []);
//查看丢失数量
think\Route::GET('/purchase-order/:abnormal_id/lost$', 'purchase/PurchaseOrder/lostParcel',[], []);
//保存或提交丢失数量
think\Route::PUT('/purchase-order/:abnormal_id/lost$', 'purchase/PurchaseOrder/saveLostParcel',[], []);
//组长审核丢失数量
think\Route::PUT('/purchase-order/:abnormal_id/review-leader$', 'purchase/PurchaseOrder/auditByLeader',[], []);
//经理审核丢失数量
think\Route::PUT('/purchase-order/:abnormal_id/review-manager$', 'purchase/PurchaseOrder/auditByManager',[], []);
//结束丢失数量
think\Route::PUT('/purchase-order/:abnormal_id/end-difference-parcel$', 'purchase/PurchaseOrder/finishLoseParcel',[], []);
//包裹退回列表-采购
think\Route::GET('/purchase-order/return-of-goods-purchase$', 'purchase/PurchaseOrder/returnByPurchase',[], []);
//采购填入邮寄信息
think\Route::PUT('/purchase-order/:abnormal_id/purchase/mail$', 'purchase/PurchaseOrder/updateReturnByPurchase',[], []);
//采购上传凭证
think\Route::PUT('/purchase-order/:abnormal_id/certificate-by-purchase$', 'purchase/PurchaseOrder/certificateByPurchase',[], []);

//控制器：app\purchase\controller\PurchaseOrderLogistics
//采购物流信息列表
think\Route::get('/purchase-order-logistics$', 'purchase/PurchaseOrderLogistics/index',[], []);
//新建采购单物流信息
think\Route::post('/purchase-order-logistics$', 'purchase/PurchaseOrderLogistics/save',[], []);
//更新采购单物流信息
think\Route::put('/purchase-order-logistics/:id$', 'purchase/PurchaseOrderLogistics/update',[], []);

//控制器：app\irobotbox\controller\Product
//获取商品信息
think\Route::get('/Irobotbox/Product/GetProducts$', 'irobotbox/Product/GetProducts',[], []);
//获取商品详细信息
think\Route::get('/Irobotbox/Product/GetProductClass$', 'irobotbox/Product/GetProductClass',[], []);
//获取商品图片
think\Route::get('/Irobotbox/Product/GetProductImages$', 'irobotbox/Product/GetProductImages',[], []);
//获取商品库存
think\Route::get('/Irobotbox/Product/GetProductInventory$', 'irobotbox/Product/GetProductInventory',[], []);
//获取商品采购信息
think\Route::get('/Irobotbox/Product/GetProductSupplierPrice$', 'irobotbox/Product/GetProductSupplierPrice',[], []);
//获取仓库信息
think\Route::get('/Irobotbox/Product/GetWareHouseList$', 'irobotbox/Product/GetWareHouseList',[], []);
//下载图片
think\Route::get('/Irobotbox/Product/downLoadImg$', 'irobotbox/Product/downLoadImg',[], []);
//获取商品条码
think\Route::get('/Irobotbox/Product/getProductBar$', 'irobotbox/Product/getProductBar',[], []);

//控制器：app\publish\controller\Ebay
//获取ebay商品类目
think\Route::POST('/Publish/Ebay/getCategorys$', 'publish/Ebay/getCategorys',[], []);
//获取ebay商品类目属性
think\Route::POST('/Publish/Ebay/getSpecifics$', 'publish/Ebay/getSpecifics',[], []);
//获取ebay站点
think\Route::get('/Publish/Ebay/getSite$', 'publish/Ebay/getSite',[], []);
//获取ebay站点对应的增值税选项
think\Route::get('/Publish/Ebay/getVatInfo$', 'publish/Ebay/getVatInfo',[], []);
//获取ebay账号自定义类目
think\Route::POST('/Publish/Ebay/getCustomCategory$', 'publish/Ebay/getCustomCategory',[], []);
//获取ebay物流方式
think\Route::POST('/Publish/Ebay/getTrans$', 'publish/Ebay/getTrans',[], []);
//获取ebay国家代码
think\Route::get('/Publish/Ebay/getCountrys$', 'publish/Ebay/getCountrys',[], []);
//获取locations国家代码
think\Route::get('/Publish/Ebay/getEbayLocations$', 'publish/Ebay/getEbayLocations',[], []);
//获取ebay平台销售账号
think\Route::get('/Publish/Ebay/getAccounts$', 'publish/Ebay/getAccounts',[], []);
//获取历史选择分类
think\Route::POST('/Publish/Ebay/getHistoryCategory$', 'publish/Ebay/getHistoryCategory',[], []);
//获取ebay类目树
think\Route::GET('/Publish/Ebay/getCategoryTree$', 'publish/Ebay/getCategoryTree',[], []);
//根据关键词查询ebay商户类目
think\Route::GET('/Publish/Ebay/getCategoryByKeyword$', 'publish/Ebay/getCategoryByKeyword',[], []);
//获取ebay店铺自定义类目树
think\Route::GET('/Publish/Ebay/getCustomCategoryTree$', 'publish/Ebay/getCustomCategoryTree',[], []);
//获取ebay备货时间
think\Route::GET('/Publish/Ebay/getDispatchTimeMax$', 'publish/Ebay/getDispatchTimeMax',[], []);
//同步店铺自定义类目
think\Route::GET('/Publish/ebay/sync-store$', 'publish/Ebay/syncStore',[], []);
//获取ebay Paypal账号
think\Route::GET('/Publish/Ebay/getPaypals$', 'publish/Ebay/getPaypals',[], []);
//获取ebay 退货时间
think\Route::GET('/Publish/Ebay/getWithin$', 'publish/Ebay/getWithin',[], []);
//同步listing信息
think\Route::get('/Publish/Ebay/syncItemInfo$', 'publish/Ebay/syncItemInfo',[], []);
//通过item_id来获取在线listing信息
think\Route::get('/Publish/ebay/listing-info-byitemid$', 'publish/Ebay/getItemInfoByItemid',[], []);
//通过item_id来获取在线oe
think\Route::get('/Publish/ebay/oe-sync$', 'publish/Ebay/syncOeByimtemId',[], []);
//oe管理新增
think\Route::POST('/Publish/ebay/oe-save$', 'publish/Ebay/oeSave',[], []);
//oe管理更新
think\Route::POST('/Publish/ebay/oe-update$', 'publish/Ebay/oeUpdate',[], []);
//oe管理删除
think\Route::GET('/Publish/ebay/oe-remove$', 'publish/Ebay/oeRemove',[], []);
//oe管理列表
think\Route::GET('/Publish/ebay/oe-list$', 'publish/Ebay/oeList',[], []);
//oe管理编辑
think\Route::GET('/Publish/ebay/oe-edit$', 'publish/Ebay/oeEdit',[], []);
//oe模板合并
think\Route::POST('/Publish/ebay/oe-modelmerge$', 'publish/Ebay/oeModelMerge',[], []);
//oe获取车型信息
think\Route::GET('/Publish/ebay/oe-vechile$', 'publish/Ebay/oeVechile',[], []);
//oe获取车型品牌
think\Route::GET('/Publish/ebay/oe-makes$', 'publish/Ebay/oeMakes',[], []);
//获取ebay在线listing
think\Route::get('/Publish/Ebay/getListing$', 'publish/Ebay/getListing',[], []);

//控制器：app\publish\controller\EbayCommon
//保存公共模块
think\Route::POST('/Publish/EbayCommon/saveCommonModel$', 'publish/EbayCommon/saveCommonModel',[], []);
//获取公共模块列表
think\Route::POST('/Publish/EbayCommon/getCommonModeList$', 'publish/EbayCommon/getCommonModeList',[], []);
//获取促销设置列表
think\Route::get('/Publish/EbayCommon/getPromotionList$', 'publish/EbayCommon/getPromotionList',[], []);
//保存促销设置
think\Route::POST('/Publish/EbayCommon/savePromotion$', 'publish/EbayCommon/savePromotion',[], []);
//获取促销设置详情
think\Route::get('/Publish/EbayCommon/editPromotion$', 'publish/EbayCommon/editPromotion',[], []);
//删除促销设置
think\Route::get('/Publish/EbayCommon/removePromotion$', 'publish/EbayCommon/removePromotion',[], []);
//获取销售说明列表
think\Route::get('/Publish/EbayCommon/getSaleList$', 'publish/EbayCommon/getSaleList',[], []);
//保存销售说明
think\Route::POST('/Publish/EbayCommon/saveSale$', 'publish/EbayCommon/saveSale',[], []);
//获取销售说明详情
think\Route::get('/Publish/EbayCommon/editSale$', 'publish/EbayCommon/editSale',[], []);
//删除销售说明
think\Route::get('/Publish/EbayCommon/removeSale$', 'publish/EbayCommon/removeSale',[], []);
//获取风格列表
think\Route::get('/Publish/EbayCommon/getStyleList$', 'publish/EbayCommon/getStyleList',[], []);
//保存风格
think\Route::POST('/Publish/EbayCommon/saveStyle$', 'publish/EbayCommon/saveStyle',[], []);
//获取风格详情
think\Route::get('/Publish/EbayCommon/editStyle$', 'publish/EbayCommon/editStyle',[], []);
//删除风格
think\Route::get('/Publish/EbayCommon/removeStyle$', 'publish/EbayCommon/removeStyle',[], []);
//获取议价设置列表
think\Route::get('/Publish/EbayCommon/getBargainingList$', 'publish/EbayCommon/getBargainingList',[], []);
//保存议价设置
think\Route::POST('/Publish/EbayCommon/saveBargaining$', 'publish/EbayCommon/saveBargaining',[], []);
//获取议价设置详情
think\Route::get('/Publish/EbayCommon/editBargaining$', 'publish/EbayCommon/editBargaining',[], []);
//删除议价设置
think\Route::get('/Publish/EbayCommon/removeBargaining$', 'publish/EbayCommon/removeBargaining',[], []);
//获取备货列表
think\Route::get('/Publish/EbayCommon/getChoiceList$', 'publish/EbayCommon/getChoiceList',[], []);
//保存备货设置
think\Route::POST('/Publish/EbayCommon/saveChoice$', 'publish/EbayCommon/saveChoice',[], []);
//获取备货设置详情
think\Route::get('/Publish/EbayCommon/editChoice$', 'publish/EbayCommon/editChoice',[], []);
//删除备货设置
think\Route::get('/Publish/EbayCommon/removeChoice$', 'publish/EbayCommon/removeChoice',[], []);
//获取计数器列表
think\Route::get('/Publish/EbayCommon/getCounterList$', 'publish/EbayCommon/getCounterList',[], []);
//保存计数器设置
think\Route::POST('/Publish/EbayCommon/saveCounter$', 'publish/EbayCommon/saveCounter',[], []);
//获取计数器设置详情
think\Route::get('/Publish/EbayCommon/editCounter$', 'publish/EbayCommon/editCounter',[], []);
//删除计数器设置
think\Route::get('/Publish/EbayCommon/removeCounter$', 'publish/EbayCommon/removeCounter',[], []);
//获取不送达地区列表
think\Route::get('/Publish/EbayCommon/getExcludeList$', 'publish/EbayCommon/getExcludeList',[], []);
//保存不送达地区设置
think\Route::POST('/Publish/EbayCommon/saveExclude$', 'publish/EbayCommon/saveExclude',[], []);
//获取不送达地区设置详情
think\Route::get('/Publish/EbayCommon/editExclude$', 'publish/EbayCommon/editExclude',[], []);
//删除不送达地区设置
think\Route::get('/Publish/EbayCommon/removeExclude$', 'publish/EbayCommon/removeExclude',[], []);
//获取是否自提列表
think\Route::get('/Publish/EbayCommon/getPickupList$', 'publish/EbayCommon/getPickupList',[], []);
//保存是否自提设置
think\Route::POST('/Publish/EbayCommon/savePickup$', 'publish/EbayCommon/savePickup',[], []);
//获取是否自提设置详情
think\Route::get('/Publish/EbayCommon/editPickup$', 'publish/EbayCommon/editPickup',[], []);
//删除是否自提设置
think\Route::get('/Publish/EbayCommon/removePickup$', 'publish/EbayCommon/removePickup',[], []);
//获取发货地设置列表
think\Route::get('/Publish/EbayCommon/getLocationList$', 'publish/EbayCommon/getLocationList',[], []);
//保存发货地设置
think\Route::POST('/Publish/EbayCommon/saveLocation$', 'publish/EbayCommon/saveLocation',[], []);
//获取发货地设置详情
think\Route::get('/Publish/EbayCommon/editLocation$', 'publish/EbayCommon/editLocation',[], []);
//删除发货地设置
think\Route::get('/Publish/EbayCommon/removeLocation$', 'publish/EbayCommon/removeLocation',[], []);
//获取是否私有设置列表
think\Route::get('/Publish/EbayCommon/getIndividualList$', 'publish/EbayCommon/getIndividualList',[], []);
//保存是否私有设置
think\Route::POST('/Publish/EbayCommon/saveIndividual$', 'publish/EbayCommon/saveIndividual',[], []);
//获取是否私有设置详情
think\Route::get('/Publish/EbayCommon/editIndividual$', 'publish/EbayCommon/editIndividual',[], []);
//删除是否私有设置
think\Route::get('/Publish/EbayCommon/removeIndividual$', 'publish/EbayCommon/removeIndividual',[], []);
//获取是否立即付款设置列表
think\Route::get('/Publish/EbayCommon/getReceivableslList$', 'publish/EbayCommon/getReceivableslList',[], []);
//保存是否立即付款设置
think\Route::POST('/Publish/EbayCommon/saveReceivablesl$', 'publish/EbayCommon/saveReceivablesl',[], []);
//获取是否立即付款设置
think\Route::get('/Publish/EbayCommon/editReceivablesl$', 'publish/EbayCommon/editReceivablesl',[], []);
//删除是否立即付款设置
think\Route::get('/Publish/EbayCommon/removeReceivablesl$', 'publish/EbayCommon/removeReceivablesl',[], []);
//获取数量设置列表
think\Route::get('/Publish/EbayCommon/getQuantityList$', 'publish/EbayCommon/getQuantityList',[], []);
//保存数量设置
think\Route::POST('/Publish/EbayCommon/saveQuantity$', 'publish/EbayCommon/saveQuantity',[], []);
//获取数量设置
think\Route::get('/Publish/EbayCommon/editQuantity$', 'publish/EbayCommon/editQuantity',[], []);
//删除数量设置
think\Route::get('/Publish/EbayCommon/removeQuantity$', 'publish/EbayCommon/removeQuantity',[], []);
//获取自定义类型列表
think\Route::get('/Publish/EbayCommon/getCateList$', 'publish/EbayCommon/getCateList',[], []);
//保存自定义类型
think\Route::POST('/Publish/EbayCommon/saveCate$', 'publish/EbayCommon/saveCate',[], []);
//获取自定义类型
think\Route::get('/Publish/EbayCommon/editCate$', 'publish/EbayCommon/editCate',[], []);
//删除自定义类型
think\Route::get('/Publish/EbayCommon/removeCate$', 'publish/EbayCommon/removeCate',[], []);
//获取买家限制设置列表
think\Route::get('/Publish/EbayCommon/getRefuseList$', 'publish/EbayCommon/getRefuseList',[], []);
//保存买家限制设置
think\Route::POST('/Publish/EbayCommon/saveRefuse$', 'publish/EbayCommon/saveRefuse',[], []);
//获取买家限制设置
think\Route::get('/Publish/EbayCommon/editRefuse$', 'publish/EbayCommon/editRefuse',[], []);
//删除买家限制设置
think\Route::get('/Publish/EbayCommon/removeRefuse$', 'publish/EbayCommon/removeRefuse',[], []);
//获取退货政策设置列表
think\Route::get('/Publish/EbayCommon/getReturngoodsList$', 'publish/EbayCommon/getReturngoodsList',[], []);
//保存退货政策设置
think\Route::POST('/Publish/EbayCommon/saveReturngoods$', 'publish/EbayCommon/saveReturngoods',[], []);
//获取退货政策设置
think\Route::get('/Publish/EbayCommon/editReturngoods$', 'publish/EbayCommon/editReturngoods',[], []);
//删除退货政策设置
think\Route::get('/Publish/EbayCommon/removeReturngoods$', 'publish/EbayCommon/removeReturngoods',[], []);
//获取模块组合列表
think\Route::get('/Publish/EbayCommon/getCombList$', 'publish/EbayCommon/getCombList',[], []);
//保存模块组合
think\Route::POST('/Publish/EbayCommon/saveComb$', 'publish/EbayCommon/saveComb',[], []);
//获取模块组合
think\Route::get('/Publish/EbayCommon/editComb$', 'publish/EbayCommon/editComb',[], []);
//删除模块组合
think\Route::get('/Publish/EbayCommon/removeComb$', 'publish/EbayCommon/removeComb',[], []);
//删除公共模块
think\Route::POST('/Publish/EbayCommon/removeCommonMode$', 'publish/EbayCommon/removeCommonMode',[], []);
//获取待编辑模块信息
think\Route::POST('/Publish/EbayCommon/editCommonMode$', 'publish/EbayCommon/editCommonMode',[], []);
//添加物流方式
think\Route::POST('/Publish/EbayCommon/saveCommonTrans$', 'publish/EbayCommon/saveCommonTrans',[], []);
//编辑物流方式
think\Route::POST('/Publish/EbayCommon/editCommonTrans$', 'publish/EbayCommon/editCommonTrans',[], []);
//删除物流方式
think\Route::POST('/Publish/EbayCommon/removeTrans$', 'publish/EbayCommon/removeTrans',[], []);
//上传风格图片到EPS获取https地址
think\Route::POST('/Publish/ebay-common/upload-style-imgs$', 'publish/EbayCommon/uploadStyleImgs',[], []);

//控制器：app\publish\controller\EbayListing
//Listing新增页面
think\Route::POST('/Publish/EbayListing/addListing$', 'publish/EbayListing/addListing',[], []);
//保存Listing
think\Route::POST('/Publish/EbayListing/saveListing$', 'publish/EbayListing/saveListing',[], []);
//在线listing更新
think\Route::POST('/Publish/EbayListing/updateListing$', 'publish/EbayListing/updateListing',[], []);
//编辑Listing
think\Route::GET('/Publish/EbayListing/editListing$', 'publish/EbayListing/editListing',[], []);
//获取子产品列表
think\Route::GET('/Publish/ebay-listing/variations$', 'publish/EbayListing/variations',[], []);
//listing管理列表
think\Route::POST('/Publish/EbayListing/listingManagement$', 'publish/EbayListing/listingManagement',[], []);
//批量修改状态
think\Route::POST('/Publish/EbayListing/updateListingStatus$', 'publish/EbayListing/updateListingStatus',[], []);
//批量重上
think\Route::GET('/Publish/EbayListing/bulkHeavyListing$', 'publish/EbayListing/bulkHeavyListing',[], []);
//获取范本列表
think\Route::get('/Publish/EbayListing/getDraftList$', 'publish/EbayListing/getDraftList',[], []);
//复制范本创建listing
think\Route::get('/Publish/EbayListing/cListingByDraft$', 'publish/EbayListing/cListingByDraft',[], []);
//批量复制范本
think\Route::get('/Publish/EbayListing/cDraftByDraft$', 'publish/EbayListing/cDraftByDraft',[], []);
//修改范本分类
think\Route::POST('/Publish/EbayListing/upDraftCate$', 'publish/EbayListing/upDraftCate',[], []);
//保存定时规则
think\Route::POST('/Publish/EbayListing/saveTimingRule$', 'publish/EbayListing/saveTimingRule',[], []);
//获取定时规则
think\Route::GET('/Publish/EbayListing/getTimingRuleList$', 'publish/EbayListing/getTimingRuleList',[], []);
//删除定时规则
think\Route::GET('/Publish/EbayListing/removeTimingRuleList$', 'publish/EbayListing/removeTimingRuleList',[], []);
//获取范本主图片
think\Route::GET('/Publish/EbayListing/getDraftImgs$', 'publish/EbayListing/getDraftImgs',[], []);
//修改范本主图
think\Route::POST('/Publish/EbayListing/upDraftImgs$', 'publish/EbayListing/upDraftImgs',[], []);
//修改在线listing价格和数量
think\Route::POST('/Publish/EbayListing/upPriceQty$', 'publish/EbayListing/upPriceQty',[], []);
//修改在线listing销售天数
think\Route::POST('/Publish/ebay-listing/up-listing-duration$', 'publish/EbayListing/upListingDuration',[], []);
//修改在线listing拍卖价格
think\Route::POST('/Publish/EbayListing/upChinesePrice$', 'publish/EbayListing/upChinesePrice',[], []);
//修改在线listing刊登标题
think\Route::POST('/Publish/EbayListing/upTitle$', 'publish/EbayListing/upTitle',[], []);
//修改在线listing店铺分类
think\Route::POST('/Publish/EbayListing/upStore$', 'publish/EbayListing/upStore',[], []);
//修改在线listing公共模块
think\Route::POST('/Publish/EbayListing/upConmonMod$', 'publish/EbayListing/upConmonMod',[], []);
//修改在线listing橱窗图片
think\Route::POST('/Publish/EbayListing/upImages$', 'publish/EbayListing/upImages',[], []);
//批量下架
think\Route::POST('/Publish/EbayListing/endItems$', 'publish/EbayListing/endItems',[], []);
//批量修改账号
think\Route::put('/Publish/EbayListing/up-accounts$', 'publish/EbayListing/upAccount',[], []);
//获取待修改多属性范本
think\Route::get('/Publish/ebay-listing/drfspecifics$', 'publish/EbayListing/getDrfSpecifics',[], []);
//批量修改范本多属性
think\Route::put('/Publish/EbayListing/up-specifics$', 'publish/EbayListing/upSpecifics',[], []);
//批量修改范本标题
think\Route::put('/Publish/EbayListing/up-draftitle$', 'publish/EbayListing/upDraftitle',[], []);
//批量修改范本名称前，返回修改前的信息用于前端展示
think\Route::get('/Publish/EbayListing/preUpDraftname$', 'publish/EbayListing/preUpDraftname',[], []);
//批量修改范本名称
think\Route::put('/Publish/EbayListing/upDraftname$', 'publish/EbayListing/upDraftname',[], []);
//批量修改范本出售方式
think\Route::put('/Publish/ebay-listing/draf-listingtype$', 'publish/EbayListing/upDraftListingType',[], []);
//获取修改记录信息
think\Route::GET('/Publish/EbayListing/getActionLogs$', 'publish/EbayListing/getActionLogs',[], []);
//关联本地产品信息
think\Route::POST('/Publish/EbayListing/relatedProduc$', 'publish/EbayListing/relatedProduc',[], []);
//获取刊登费用
think\Route::POST('/Publish/EbayListing/getListingFee$', 'publish/EbayListing/getListingFee',[], []);
//立即刊登->提交数据
think\Route::POST('/Publish/ebay-listing/publish-immediately-save$', 'publish/EbayListing/publishImmediatelySave',[], []);
//立即刊登->查看结果
think\Route::GET('/Publish/ebay-listing/publish-immediately-results$', 'publish/EbayListing/publishImmediatelyResults',[], []);
//立即刊登
think\Route::GET('/Publish/ebay-listing/publish-immediately$', 'publish/EbayListing/publishImmediately',[], []);
//立即重上
think\Route::GET('/Publish/ebay-listing/relist-itm$', 'publish/EbayListing/relistItm',[], []);
//促销设置
think\Route::GET('/Publish/ebay-listing/promotion-listings$', 'publish/EbayListing/promotionListings',[], []);
//批量导出范本
think\Route::GET('/Publish/EbayListing/exportDraftInfo$', 'publish/EbayListing/exportDraftInfo',[], []);
//批量导入范本
think\Route::POST('/Publish/EbayListing/importDraftInfo$', 'publish/EbayListing/importDraftInfo',[], []);
//批量分享范本
think\Route::POST('/Publish/EbayListing/shareDraft$', 'publish/EbayListing/shareDraft',[], []);
//同步ebay官网特定站点物流方式
think\Route::POST('/Publish/trans/sync$', 'publish/EbayListing/syncTrans',[], []);
//批量检测刊登费用
think\Route::GET('/Publish/testfees/batch$', 'publish/EbayListing/testListingFees',[], []);

//控制器：app\goods\controller\Goods
//商品刊登统计
think\Route::get('/goods/publish-statistics/:id$', 'goods/Goods/statistics',[], ['id'=>'(\d+)']);
//商品列表
think\Route::get('/goods$', 'goods/Goods/index',[], []);
//商品添加
think\Route::post('/goods$', 'goods/Goods/save',[], []);
//商品查看
think\Route::get('/goods/:id$', 'goods/Goods/read',[], []);
//更改商品销售状态
think\Route::POST('/goods/changeStatus$', 'goods/Goods/changeStatus',[], []);
//获取商品sku列表
think\Route::get('/goods/skus/:id$', 'goods/Goods/getGoodsSkus',[], ['id'=>'(\d+)']);
//查看商品基础详情
think\Route::get('/goods/base/:id$', 'goods/Goods/getBaseInfo',[], ['id'=>'(\d+)']);
//更新产品基础信息
think\Route::put('/goods/base/:id$', 'goods/Goods/updateBaseInfo',[], ['id'=>'(\d+)']);
//获取平台状态列表
think\Route::get('/goods/platform-sale-status$', 'goods/Goods/getPlatformSaleStatus',[], []);
//获取商品规格信息
think\Route::get('/goods/specification/:id$', 'goods/Goods/getSpecification',[], ['id'=>'(\d+)']);
//查看商品属性列表
think\Route::get('/goods/attribute/:id$', 'goods/Goods/getAttribute',[], ['id'=>'(\d+)']);
//商品供应商列表
think\Route::get('/goods/supplier/:id$', 'goods/Goods/getSupplier',[], ['id'=>'(\d+)']);
//根据goods_id返回供应商列表
think\Route::get('/goods/getGoodSupplierList$', 'goods/Goods/getGoodSupplierList',[], []);
//获取商品描述
think\Route::get('/goods/description/:id$', 'goods/Goods/getDescription',[], ['id'=>'(\d+)']);
//更新商品规格参数
think\Route::put('/goods/specification/:id$', 'goods/Goods/updateSpecification',[], ['id'=>'(\d+)']);
//编辑商品属性
think\Route::get('/goods/attribute/:id/edit$', 'goods/Goods/editAttribute',[], ['id'=>'(\d+)']);
//更新产品属性
think\Route::put('/goods/attribute/:id$', 'goods/Goods/updateAttribute',[], ['id'=>'(\d+)']);
//更新产品描述
think\Route::put('/goods/description/:id$', 'goods/Goods/updateDescription',[], ['id'=>'(\d+)']);
//更新产品与渠道映射表
think\Route::put('/goods/goodsCategoryMap/:id$', 'goods/Goods/updateGoodsCategoryMap',[], ['id'=>'(\d+)']);
//获取产品的渠道映射
think\Route::get('/goods/goodsCategoryMap/:id$', 'goods/Goods/getGoodsCategoryMap',[], ['id'=>'(\d+)']);
//产品日志列表
think\Route::get('/goods/log/:id$', 'goods/Goods/getLog',[], ['id'=>'(\d+)']);
//添加产品备注信息
think\Route::post('/goods/log/:id$', 'goods/Goods/addLog',[], ['id'=>'(\d+)']);
//查看产品sku信息列表
think\Route::get('/goods/skuinfo/:id$', 'goods/Goods/getSkuInfo',[], ['id'=>'(\d+)']);
//编辑产品sku的信息
think\Route::get('/goods/skuinfo/:id/edit$', 'goods/Goods/editSkuInfo',[], ['id'=>'(\d+)']);
//保存sku列表信息
think\Route::put('/goods/skuinfo/:id$', 'goods/Goods/saveSkuInfos',[], ['id'=>'(\d+)']);
//保存平台销售信息
think\Route::put('/goods/:id/platformSale$', 'goods/Goods/savePlatformSale',[], []);
//查看产品质检信息
think\Route::get('/goods/qcitems/:id$', 'goods/Goods/getQcItems',[], ['id'=>'(\d+)']);
//编辑产品质检信息
think\Route::get('/goods/qcitems/:id/edit$', 'goods/Goods/editQcItems',[], ['id'=>'(\d+)']);
//保存产品质检信息
think\Route::put('/goods/qcitems/:id$', 'goods/Goods/saveQcItems',[], ['id'=>'(\d+)']);
//获取产品出售状态
think\Route::get('/goods/sales-status$', 'goods/Goods/getSalesStatus',[], []);
//获取物流属性列表
think\Route::get('/goods/transport-property$', 'goods/Goods/transportProperty',[], []);
//获取修图需求列表
think\Route::get('/goods/img-requirement$', 'goods/Goods/imgRequirements',[], []);
//查询spu列表
think\Route::get('/goods/goodsToSpu$', 'goods/Goods/goodsToSpu',[], []);
//更改商品销售状态
think\Route::POST('/goods/skuStatus$', 'goods/Goods/changeSkuStatus',[], []);
//编辑sku重量尺寸信息
think\Route::get('/sku-check/:id/edit$', 'goods/Goods/editSkuCheck',[], ['id'=>'(\d+)']);
//确认sku重量尺寸信息
think\Route::put('/sku-check/:id$', 'goods/Goods/updateSkuCheck',[], ['id'=>'(\d+)']);
//获取比对信息
think\Route::get('/goods/comparison/:id$', 'goods/Goods/comparison',[], ['id'=>'(\d+)']);
//产品导入
think\Route::post('/goods/import$', 'goods/Goods/import',[], []);
//产品导入修改
think\Route::post('/goods/importUpdate$', 'goods/Goods/importUpdate',[], []);
//获取SKU附属参数
think\Route::get('/goods/getSkuIncidentalParameter$', 'goods/Goods/getSkuIncidentalParameter',[], []);
//下载远程服务器图片
think\Route::post('/goods/download$', 'goods/Goods/downloadImages',[], []);
//导出商品转成joom格式
think\Route::get('/goods/export$', 'goods/Goods/export',[], []);
//获取sku打印标签
think\Route::get('/goods/sku-label$', 'goods/Goods/getSkuLabel',[], []);
//批量抓图
think\Route::post('/goods/batch-catch-photo$', 'goods/Goods/batchCatchPhoto',[], []);
//推送到赛盒
think\Route::post('/goods/batch/push-irobotbox$', 'goods/Goods/pushIrobotbox',[], []);
//测试推送队列
think\Route::get('/goods/push-queue$', 'goods/Goods/pushGoodsPublishMapQueue',[], []);
//导出商品sku
think\Route::post('/goods/export-sku$', 'goods/Goods/exportSku',[], []);
//导出noon格式商品
think\Route::post('/goods/export-noon$', 'goods/Goods/exportNooN',[], []);
//设置采购员
think\Route::post('/goods/set-purchaser$', 'goods/Goods/setPurchaserId',[], []);
//获取可供选择的导出字段
think\Route::get('/goods/export-field$', 'goods/Goods/getExportField',[], []);
//推送赛盒
think\Route::get('/goods/irobobox-push$', 'goods/Goods/testIrobotbox',[], []);
//推送分销
think\Route::post('/goods/distribution-push$', 'goods/Goods/testDistribution',[], []);
//根据id返回商品信息详情
think\Route::get('/goods/api/:id/info$', 'goods/Goods/apiGoodsInfo',[], []);
//获取图片phash
think\Route::post('/goods/get-phash$', 'goods/Goods/getPhash',[], []);
//跑phash数据
think\Route::get('/goods/run-phash$', 'goods/Goods/runPhash',[], []);
//跑phash数据
think\Route::get('/goods/run-dhash$', 'goods/Goods/runDhash',[], []);
//获取侵权下架
think\Route::get('/goods/:id/tort$', 'goods/Goods/getTort',[], ['id'=>'(\d+)']);
//保存侵权下架
think\Route::post('/goods/:id/tort$', 'goods/Goods/saveTort',[], ['id'=>'(\d+)']);
//推送每日开发数
think\Route::get('/goods/pull-count-develop$', 'goods/Goods/pullCountDevelop',[], []);
//统计每日开发数
think\Route::get('/goods/count-develop$', 'goods/Goods/countDevelop',[], []);
//获取侵权详情
think\Route::get('/goods/:id/goods-tort-description$', 'goods/Goods/getGoodsTortDescription',[], []);
//根据ID获取侵权详情.
think\Route::get('/goods/goods-tort-description/:id$', 'goods/Goods/getGoodsTortDescriptionById',[], []);
//保存商品侵权详情
think\Route::put('/goods/:id/goods-tort-description$', 'goods/Goods/saveGoodsTortDescription',[], []);
//移除商品侵权详情
think\Route::delete('/goods/:id/goods-tort-description$', 'goods/Goods/removeGoodsTortDescription',[], []);
//获取供应商选择列表
think\Route::get('/goods/supplier-select$', 'goods/Goods/getSupplierSelect',[], []);
//获取侵权列表
think\Route::get('/goods/goods-tort-description-list$', 'goods/Goods/getTortList',[], []);

//控制器：app\publish\controller\Wish
//wish部门所有员工
think\Route::get('/publish/wish/wishUsers$', 'publish/Wish/wishUsers',[], []);
//删除草稿箱
think\Route::post('/publish/wish/deleteDraft$', 'publish/Wish/deleteDraft',[], []);
//草稿箱列表
think\Route::get('/publish/wish/draft$', 'publish/Wish/draft',[], []);
//加入待刊登序列
think\Route::post('/publish/wish/pushQueue$', 'publish/Wish/pushQueue',[], []);
//统计
think\Route::get('/publish/wish/stat$', 'publish/Wish/stat',[], []);
//wish所有颜色值
think\Route::get('/publish/wish/colors$', 'publish/Wish/colors',[], []);
//验证颜色值是否合法
think\Route::post('/publish/wish/validateColor$', 'publish/Wish/validateColor',[], []);
//验证size值是否合法
think\Route::post('/publish/wish/validateSize$', 'publish/Wish/validateSize',[], []);
//从产品库刊登保存草稿
think\Route::post('/publish/wish/saveMany$', 'publish/Wish/saveMany',[], []);
//从产品库刊登多个商品
think\Route::post('/publish/wish/addMany$', 'publish/Wish/addMany',[], []);
//删除
think\Route::post('/publish/wish/del$', 'publish/Wish/del',[], []);
//获取wish在线tags
think\Route::get('/publish/wish/getWishOnlineTags$', 'publish/Wish/getWishOnlineTags',[], []);
//获取wish size设置
think\Route::get('/publish/wish/getWishSize$', 'publish/Wish/getWishSize',[], []);
//上传网络图片
think\Route::post('/publish/wish/createNetImage$', 'publish/Wish/createNetImage',[], []);
//上传图片
think\Route::post('/publish/wish/uploadImages$', 'publish/Wish/uploadImages',[], []);
//获取商品相册
think\Route::get('/publish/wish/gallery$', 'publish/Wish/gallery',[], []);
//获取wish销售人员账号信息
think\Route::get('/publish/wish/getSellers$', 'publish/Wish/getSellers',[], []);
//获取品牌
think\Route::get('/publish/wish/getBrands$', 'publish/Wish/getBrands',[], []);
//获取刊登页面需要的数据
think\Route::get('/publish/wish/getData$', 'publish/Wish/getData',[], []);
//保存并同步到平台
think\Route::post('/publish/wish/rsync$', 'publish/Wish/rsync',[], []);
//wish刊登保存功能
think\Route::post('/publish/wish/save$', 'publish/Wish/save',[], []);
//wish刊登功能
think\Route::post('/publish/wish/add$', 'publish/Wish/add',[], []);
//获取wish待刊登商品列表
think\Route::get('/publish/wish/productList$', 'publish/Wish/productList',[], []);
//wish已刊登列表
think\Route::get('/publish/wish/lists$', 'publish/Wish/lists',[], []);
//wish已刊登变体信息
think\Route::get('/publish/wish/getSkus$', 'publish/Wish/getSkus',[], []);
//导出商品转成joom格式
think\Route::get('/publish/wish/export$', 'publish/Wish/export',[], []);
//wish导出所有商品
think\Route::get('/publish/wish/download-all$', 'publish/Wish/downloadAll',[], []);
//wish导出字段
think\Route::get('/publish/wish/download-fields$', 'publish/Wish/downloadFields',[], []);
//调整成本价
think\Route::put('/wish/adjust-cost/batch$', 'publish/Wish/adjustCostPrice',[], []);

//控制器：app\order\controller\Ebay
//订单列表
think\Route::GET('/ebay-orders$', 'order/Ebay/index',[], []);
//查看
think\Route::GET('/ebay-orders/:id$', 'order/Ebay/read',[], ['id'=>'(\d+)']);
//取订单各状态的总数
think\Route::GET('/ebay-orders/status-count$', 'order/Ebay/statusCount',[], []);
//更新系统订单物流方式
think\Route::GET('/ebay-orders/update-shipping$', 'order/Ebay/updateShipping',[], []);
//查找订单存在
think\Route::POST('/ebay-orders/exists$', 'order/Ebay/exists',[], []);
//ebay同步平台订单；
think\Route::POST('/ebay-orders/sysc$', 'order/Ebay/sysc',[], []);
//ebay放款模板下载；
think\Route::GET('/ebay-orders/export-transfer-template$', 'order/Ebay/exportTemplate',[], []);
//ebay导入放款表单；
think\Route::POST('/ebay-orders/import-transfer$', 'order/Ebay/importTransfer',[], []);
//ebay运输方式1230；
think\Route::GET('/ebay-orders/shipping$', 'order/Ebay/shipping',[], []);
//导出ebay订单需要的字段
think\Route::GET('/ebay-orders/export-fields$', 'order/Ebay/exportOrderFields',[], []);
//导出ebay订单
think\Route::post('/ebay-orders/export$', 'order/Ebay/exportOrder',[], []);
//推送至系统订单
think\Route::Post('/ebay-orders/push-ebay-order$', 'order/Ebay/pushEbayOrder',[], []);
//拉取ebay订单
think\Route::post('/ebay-orders/sysc-ebayorder$', 'order/Ebay/syscEbayOrder',[], []);
//ebay店铺数据统计
think\Route::POST('/ebay-orders/shop-statistics$', 'order/Ebay/shopStatistics',[], []);
//paypal数据统计
think\Route::POST('/ebay-orders/paypal-statistics$', 'order/Ebay/pyapalStatistics',[], []);
//店铺数据导出
think\Route::POST('/ebay-orders/statistics-export$', 'order/Ebay/exportShopData',[], []);

//控制器：app\order\controller\Paypal
//列表
think\Route::GET('/paypal-orders$', 'order/Paypal/index',[], []);
//查看
think\Route::GET('/paypal-orders/:id$', 'order/Paypal/read',[], []);
//交易类型列表
think\Route::GET('/paypal-orders/transactionType$', 'order/Paypal/getTransactionType',[], []);
//订单收货人国家列表
think\Route::GET('/paypal-orders/country$', 'order/Paypal/getCountryList',[], []);
//抓取单个个订单
think\Route::GET('/paypal-orders/get-order$', 'order/Paypal/getOrderById',[], []);
//同步paypal订单；
think\Route::POST('/paypal-orders/sync$', 'order/Paypal/sync',[], []);
//pyapal数据导出
think\Route::POST('/paypal-orders/statistics-export$', 'order/Paypal/exportPaypalData',[], []);

//控制器：app\order\controller\Fbs
//fbs订单列表
think\Route::get('/fbs-orders/index$', 'order/Fbs/index',[], []);
//查看fbs详情
think\Route::get('/fbs-orders/read$', 'order/Fbs/read',[], []);
//fbs订单报表导出
think\Route::POST('/fbs-orders/export$', 'order/Fbs/export',[], []);
//获取所有导出字段
think\Route::get('/fbs-orders/export-fields$', 'order/Fbs/getExportFields',[], []);

//控制器：app\purchase\controller\PurchaseParcels
//创建包裹
think\Route::POST('/purchase-parcels/createUpdateParcel$', 'purchase/PurchaseParcels/createUpdateParcel',[], []);
//包裹列表
think\Route::GET('/purchase-parcels/getParcelList$', 'purchase/PurchaseParcels/getParcelList',[], []);
//导出包裹查询
think\Route::POST('/purchase-parcels/export$', 'purchase/PurchaseParcels/export',[], []);
//包裹详情
think\Route::GET('/purchase-parcels/getPurchaseParcelDetail$', 'purchase/PurchaseParcels/getPurchaseParcelDetail',[], []);
//根据运单号找采购单列表
think\Route::GET('/purchase-parcels/getPurchaseOrderInfoByTrackingNo$', 'purchase/PurchaseParcels/getPurchaseOrderInfoByTrackingNo',[], []);
//根据订单ID列表找采购单列表
think\Route::GET('/purchase-parcels/getPurchaseOrderInfoByIds$', 'purchase/PurchaseParcels/getPurchaseOrderInfoByIds',[], []);
//收货
think\Route::POST('/purchase-parcels/receiptParcel$', 'purchase/PurchaseParcels/receiptParcel',[], []);
//采购包裹收货审核(废弃, 调用PurchaseParcelsAudit控制器)
think\Route::POST('/purchase-parcels/auditParcel$', 'purchase/PurchaseParcels/auditParcel',[], []);
//包裹列表(包裹拆开)
think\Route::GET('/purchase-parcels/getParcelListForParcelSplitting$', 'purchase/PurchaseParcels/getParcelListForParcelSplitting',[], []);
// 标记包裹拆包异常
think\Route::PUT('/purchase-parcels/abnormal$', 'purchase/PurchaseParcels/setParcelException',[], []);
// 包裹拆包异常列表
think\Route::GET('/purchase-parcels/abnormal$', 'purchase/PurchaseParcels/getParcelExceptionList',[], []);
// 处理包裹异常
think\Route::PUT('/purchase-parcels/batch/abnormal$', 'purchase/PurchaseParcels/editParcelException',[], []);
//标记为已处理(拆包异常)
think\Route::PUT('/purchase-parcels/batch/end$', 'purchase/PurchaseParcels/setParcelsExceptionOver',[], []);
// 拆包员列表
think\Route::GET('/purchase-parcels/unpacked-list$', 'purchase/PurchaseParcels/unpackedNameList',[], []);
//删除包裹
think\Route::POST('/purchase-parcels/deletePurchaseParcel$', 'purchase/PurchaseParcels/deletePurchaseParcel',[], []);
//编辑包裹(按字段)
think\Route::post('/purchase-parcels/updatePurchaseParcelByField$', 'purchase/PurchaseParcels/updatePurchaseParcelByField',[], []);
//编辑包裹（重量 运单号 收货台）
think\Route::post('/purchase-parcels/updatePurchaseParcel$', 'purchase/PurchaseParcels/updatePurchaseParcel',[], []);
//根据采购单ID和包裹编号取得SKU的标签信息
think\Route::get('/purchase-parcels/getSkuLabelInfo$', 'purchase/PurchaseParcels/getSkuLabelInfo',[], []);
//包裹预接收
think\Route::POST('/purchase-parcels/ready-receive$', 'purchase/PurchaseParcels/createReadyReceiveParcels',[], []);
//根据运单号获取预接收包裹
think\Route::GET('/purchase-parcels/ready-receive$', 'purchase/PurchaseParcels/getReadyReceiveParcels',[], []);
//修改预接收包裹信息
think\Route::PUT('/purchase-parcels/ready-receive$', 'purchase/PurchaseParcels/updateReadyReceiveParcels',[], []);
//标记收包异常
think\Route::POST('/purchase-parcels/receive-abnormal$', 'purchase/PurchaseParcels/setReceiveAbnormal',[], []);
//获取用户一级部门节点
think\Route::GET('/purchase-parcels/user-department$', 'purchase/PurchaseParcels/getUserDepartment',[], []);
//收包异常列表
think\Route::GET('/purchase-parcels/receive-abnormal$', 'purchase/PurchaseParcels/getReceiveAbnormal',[], []);
//回复收包异常包裹
think\Route::PUT('/purchase-parcels/reply-letter$', 'purchase/PurchaseParcels/replyReceiveAbnormal',[], []);
//收包异常上传凭证
think\Route::PUT('/purchase-parcels/certificate$', 'purchase/PurchaseParcels/uploadCertificate',[], []);
//收包异常包裹标记已处理
think\Route::PUT('/purchase-parcels/process-status$', 'purchase/PurchaseParcels/setAbnormalProcess',[], []);
//统计异常（拆包，收包）总数
think\Route::GET('/purchase-parcels/abnormal-count$', 'purchase/PurchaseParcels/getAbnormalCount',[], []);
//包裹异常来源
think\Route::GET('/purchase-parcels/abnormal-source$', 'purchase/PurchaseParcels/getSource',[], []);
//包裹异常跟进(其他接收异常)
think\Route::POST('/purchase-parcels/parcel-abnormal$', 'purchase/PurchaseParcels/setReceiveAbnormalType',[], []);
//获取接收异常类型
think\Route::GET('/purchase-parcels/abnormal-type$', 'purchase/PurchaseParcels/getReceiveAbnormalType',[], []);
//包裹退回列表-仓库
think\Route::GET('/purchase-parcels/return-of-goods-warehouse$', 'purchase/PurchaseParcels/returnByWarehouse',[], []);
//仓库填入邮寄信息
think\Route::PUT('/purchase-parcels/:abnormal_id/warehouse/mail$', 'purchase/PurchaseParcels/updateReturnByWarehouse',[], []);
//移动之前拆包异常数据
think\Route::GET('/purchase-parcels/move-unpack-abnormal-data$', 'purchase/PurchaseParcels/moveUnpackAbnormalData',[], []);

//控制器：app\purchase\controller\PurchaseProposal
// 显示列表
think\Route::get('/purchase-proposal$', 'purchase/PurchaseProposal/index',[], []);
//保存采购建议
think\Route::post('/purchase-proposal$', 'purchase/PurchaseProposal/save',[], []);
//查看
think\Route::get('/purchase-proposal/:id$', 'purchase/PurchaseProposal/read',[], ['id'=>'(\d+)']);
//显示编辑资源表单页.
think\Route::GET('/purchase-proposal/:id/edit$', 'purchase/PurchaseProposal/edit',[], ['id'=>'(\d+)']);
//更新的资源
think\Route::PUT('/purchase-proposal/:id$', 'purchase/PurchaseProposal/update',[], ['id'=>'(\d+)']);
//生成采购计划
think\Route::POST('/purchase-proposal/createPurchasePlan$', 'purchase/PurchaseProposal/createPurchasePlan',[], []);
//  初始化采购建议
think\Route::POST('/purchase-proposal/calculatePurchaseProposal$', 'purchase/PurchaseProposal/calculatePurchaseProposal',[], []);
//采购员最后一次生成的采购建议 的时间
think\Route::GET('/purchase-proposal/lastPurchaseProposal$', 'purchase/PurchaseProposal/lastPurchaseProposal',[], []);
//获取sku供应商
think\Route::GET('/purchase-proposal/getSupplier$', 'purchase/PurchaseProposal/getSupplier',[], []);
//获取图表数据
think\Route::GET('/purchase-proposal/chart-data$', 'purchase/PurchaseProposal/chartData',[], []);
//更新采购建议的采购数量 采购价格 供应商
think\Route::post('/purchase-proposal/updateProposalArgs$', 'purchase/PurchaseProposal/updateProposalArgs',[], []);
//更新采购建议的采购数量 采购价格 供应商(生成采购计划前)
think\Route::post('/purchase-proposal/updateProposalArgsBeforeCreatePlan$', 'purchase/PurchaseProposal/updateProposalArgsBeforeCreatePlan',[], []);
// 重置已生成的采购建议的状态为未生成
think\Route::post('/purchase-proposal/resetProposalStatus$', 'purchase/PurchaseProposal/resetProposalStatus',[], []);
//导出采购建议
think\Route::POST('/purchase-proposal/export$', 'purchase/PurchaseProposal/export',[], []);
//删除采购建议
think\Route::POST('/purchase-proposal/delete$', 'purchase/PurchaseProposal/delete',[], []);

//控制器：app\purchase\controller\PurchaseApply
//显示列表
think\Route::get('/purchase-apply$', 'purchase/PurchaseApply/index',[], []);
//付款申请管理导出字段
think\Route::get('/purchase-apply/export-fields$', 'purchase/PurchaseApply/getExportFields',[], []);
//导出采购付款
think\Route::POST('/purchase-apply/export$', 'purchase/PurchaseApply/export',[], []);
//获取状态标签
think\Route::get('/purchase-apply/status-label$', 'purchase/PurchaseApply/getStatusLabel',[], []);
// 采购审核
think\Route::POST('/purchase-apply/audit-purchaser$', 'purchase/PurchaseApply/auditForPurchaser',[], []);
// 财务审核
think\Route::POST('/purchase-apply/audit-finance$', 'purchase/PurchaseApply/auditForFinance',[], []);
// 财务复核
think\Route::POST('/purchase-apply/audit-finance2$', 'purchase/PurchaseApply/auditForFinance2',[], []);
//查看
think\Route::get('/purchase-apply/:id$', 'purchase/PurchaseApply/read',[], ['id'=>'(\d+)']);
//修改
think\Route::PUT('/purchase-apply/:id$', 'purchase/PurchaseApply/update',[], ['id'=>'(\d+)']);
//取消付款
think\Route::POST('/purchase-apply/cancel$', 'purchase/PurchaseApply/cancel',[], []);
//作废
think\Route::POST('/purchase-apply/invalid$', 'purchase/PurchaseApply/invalid',[], []);
//标记已付款
think\Route::POST('/purchase-apply/mark-payed$', 'purchase/PurchaseApply/markPayed',[], []);
//编辑页面
think\Route::GET('/purchase-apply/:id/edit$', 'purchase/PurchaseApply/edit',[], ['id'=>'(\d+)']);
//计算付款总金额
think\Route::post('/purchase-apply/calculating-money$', 'purchase/PurchaseApply/calculatingMoney',[], []);
//导出富友
think\Route::post('/purchase-apply/export-fuyou$', 'purchase/PurchaseApply/exportFuYou',[], []);
//获取日志记录
think\Route::get('/purchase-apply/:id/log$', 'purchase/PurchaseApply/log',[], []);
//上传发票
think\Route::post('/purchase-apply/upload-images$', 'purchase/PurchaseApply/uploadImages',[], []);
//导出发票
think\Route::get('/purchase-apply/down-invoice/:id$', 'purchase/PurchaseApply/downInvoice',[], []);
//上传付款回单
think\Route::post('/purchase-apply/upload-payment-images$', 'purchase/PurchaseApply/uploadPaymentImages',[], []);
//批量修改结算方式
think\Route::put('/purchase-apply/balance-type$', 'purchase/PurchaseApply/changeBalanceType',[], []);

//控制器：app\publish\controller\Express
//获取速卖通部门所有员工
think\Route::get('/publish/express/users$', 'publish/Express/users',[], []);
//同步分类
think\Route::post('/aliexpress-rsyn-category$', 'publish/Express/rsyncCategory',[], []);
//速卖通刊登错误码解释
think\Route::post('/publish/express/error-explain$', 'publish/Express/publishError',[], []);
//速卖通已刊登导出
think\Route::post('/publish/express/download$', 'publish/Express/download',[], []);
//速卖通帐号本地授权分类列表
think\Route::GET('/aliexpress-auth-local-category$', 'publish/Express/getAuthCategorys',[], []);
//速卖通帐号
think\Route::GET('/aliexpress-accounts$', 'publish/Express/getAccounts',[], []);
//速卖通刊登详情预览
think\Route::GET('/publish/express/$', 'publish/Express/review',[], []);
//速卖通平台根据品牌获取分类
think\Route::GET('/publish/express/aliexpress-get-categories-by-brand$', 'publish/Express/getCategoryByBrand',[], []);
//速卖通平台分类列表
think\Route::GET('/publish/express/aliexpress-categories$', 'publish/Express/getAliexpressAllCategory',[], []);
//保存速卖通刊登分类属性
think\Route::post('/aliexpress-save-publish-template$', 'publish/Express/savePublishTemplate',[], []);
//速卖通分类列表
think\Route::get('/aliexpress-category-tree$', 'publish/Express/category_tree',[], []);
//Aliexpress刊登列表
think\Route::get('/publish/express$', 'publish/Express/index',[], []);
//获取已刊登商品列表
think\Route::GET('/publish/express/product$', 'publish/Express/getProduct',[], []);
//编辑商品
think\Route::get('/publish/express/editProduct$', 'publish/Express/editProduct',[], []);
//未刊登商品列表
think\Route::get('/publish/express/unpublish$', 'publish/Express/unPublish',[], []);
//根据pid查询子分类
think\Route::GET('/publish/express/categorys$', 'publish/Express/Getpostcategorybyid',[], []);
//商品上架
think\Route::post('/publish/express/online$', 'publish/Express/Onlineaeproduct',[], []);
//商品下架
think\Route::post('/publish/express/offline$', 'publish/Express/Offlineaeproduct',[], []);
//根据类目ID获取适合的尺码模板
think\Route::get('/publish/express/sizetemp$', 'publish/Express/Getsizechartinfobycategoryid',[], []);
//查询指定分类的属性
think\Route::get('/publish/express/attributes$', 'publish/Express/Getattrbycategoryid',[], []);
//获取商户账户列表
think\Route::get('/publish/express/accounts$', 'publish/Express/Getaliexpressaccount',[], []);
//获取商品列表
think\Route::get('/publish/express/goods$', 'publish/Express/Getgoods',[], []);
//根据产品ID获取映射到速卖通的分类ID
think\Route::get('/publish/express/aliCategoryid$', 'publish/Express/Getgoodsaliexpresscategoryid',[], []);
//获取待刊登的产品详情
think\Route::get('/publish/express/productInfo$', 'publish/Express/Getproductinfo',[], []);
//获取仓库列表
think\Route::get('/publish/express/warehouses$', 'publish/Express/Getwarehouse',[], []);
//获取商户运费模板
think\Route::get('/publish/express/freightTemp$', 'publish/Express/Getfreighttemplate',[], []);
//获取商户服务模板
think\Route::get('/publish/express/promiseTemp$', 'publish/Express/Getpromisetemplate',[], []);
//获取商户商品分组
think\Route::get('/publish/express/groups$', 'publish/Express/Getproductgroup',[], []);
//判断该商户是否拥有某样产品刊登的分类权限
think\Route::get('/publish/express/categoryPermission$', 'publish/Express/Whethercategorypower',[], []);
//根据产品ID、平台账号ID和分类ID获取公有数据
think\Route::get('/publish/express/pulishData$', 'publish/Express/Getpulishdata',[], []);
//根据产品ID获取产品可选的产品图片
think\Route::get('/publish/express/images$', 'publish/Express/Getpublishimage',[], []);
//速卖通刊登
think\Route::post('/publish/express/publish$', 'publish/Express/Publish',[], []);
//上传图片到临时目录
think\Route::post('/publish/express/uploadTemp$', 'publish/Express/UploadimageTemp',[], []);
//图片上传到速卖通图片银行
think\Route::post('/publish/express/upload$', 'publish/Express/Uploadimage',[], []);
//获取速卖通产品计数单位
think\Route::get('/publish/express/productUnit$', 'publish/Express/getProductUnit',[], []);
//违禁词检测
think\Route::post('/publish/express/prohibited$', 'publish/Express/checkProhibitedWords',[], []);
//根据sku和分类获取listing信息
think\Route::get('/publish/express/skuInfo$', 'publish/Express/getSkuInfo',[], []);
//批量修改标题、服务模板、运费模板、毛重
think\Route::post('/publish/express/batchProduct$', 'publish/Express/batchEditProduct',[], []);
//批量修改尺寸
think\Route::post('/publish/express/batchSize$', 'publish/Express/batchEditSize',[], []);
//批量修改商品计数单位
think\Route::post('/publish/express/batchUnit$', 'publish/Express/batchEditProductUnit',[], []);
//批量修改商品SKU价格
think\Route::post('/publish/express/batchPrice$', 'publish/Express/batchEditSkuPrice',[], []);
//批量删除
think\Route::delete('/publish/express/batchDelete$', 'publish/Express/batchDelete',[], []);
//获取平台产品状态
think\Route::get('/publish/express/productStatus$', 'publish/Express/getProductStatus',[], []);
//剩余有效期
think\Route::get('/publish/express/expireSearch$', 'publish/Express/getExpireSearch',[], []);
//获取已刊登商品简易列表
think\Route::get('/publish/express/productList$', 'publish/Express/getProductList',[], []);
//复制商品
think\Route::get('/publish/express/copy$', 'publish/Express/copyProduct',[], []);
//根据账号和分类获取品牌信息
think\Route::get('/publish/express/brands$', 'publish/Express/brands',[], []);
//获取信息模板
think\Route::get('/publish/express/:account_id/:type/productTemp$', 'publish/Express/getProductTemp',[], ['account_id'=>'(\d+)', 'type'=>'(\w+)']);
//草稿箱
think\Route::GET('/publish/express/drafts$', 'publish/Express/drafts',[], []);
//待刊登列表
think\Route::GET('/publish/express/wait-publish$', 'publish/Express/waitPublish',[], []);
//刊登异常列表
think\Route::GET('/publish/express/fail-publish$', 'publish/Express/failPublish',[], []);
//更改成本价
think\Route::GET('/publish/express/change-cost-price$', 'publish/Express/changeCostPrice',[], []);
//速卖通批量复制为草稿箱
think\Route::POST('/publish/express/batch-copy$', 'publish/Express/batchCopy',[], []);
//未刊登列表品牌搜索
think\Route::GET('/publish/express/product-brand$', 'publish/Express/productBrand',[], []);
//分组列表
think\Route::GET('/publish/express/region-group$', 'publish/Express/regionGroup',[], []);
//区域模板列表
think\Route::GET('/publish/express/region-template$', 'publish/Express/regionTemplate',[], []);
//添加分组
think\Route::POST('/publish/express/add-region-group$', 'publish/Express/addRegionGroup',[], []);
//添加区域模板
think\Route::POST('/publish/express/add-region-template$', 'publish/Express/addRegionTemplate',[], []);
//编辑区域模板
think\Route::POST('/publish/express/edit-region-template$', 'publish/Express/editRegionTemplate',[], []);
//编辑分组
think\Route::POST('/publish/express/edit-region-group$', 'publish/Express/editRegionGroup',[], []);
//删除分组
think\Route::post('/publish/express/delete-region-group$', 'publish/Express/deleteRegionGroup',[], []);
//删除区域模板
think\Route::post('/publish/express/delete-region-template$', 'publish/Express/deleteRegionTemplate',[], []);
//根据模板id获取模板
think\Route::GET('/publish/express/region-template-info$', 'publish/Express/regionTemplateInfo',[], []);
//刊登失败批量提交刊登
think\Route::POST('/publish/express/batch-add-fail-publish$', 'publish/Express/batchAddFailPublish',[], []);
//速卖通刊登保存草稿
think\Route::post('/publish/express/save-draft$', 'publish/Express/saveDraft',[], []);
//刊登队列批量提交刊登
think\Route::POST('/publish/express/batch-add-wait-publish$', 'publish/Express/batchAddWaitPublish',[], []);
//未刊登侵权信息
think\Route::POST('/publish/express/goods-tort-info$', 'publish/Express/goodsTortInfo',[], []);
//修复线上速卖通刊登异常数据
think\Route::GET('/publish/express/fail-publish-save$', 'publish/Express/failPublishSave',[], []);

//控制器：app\listing\controller\Wish
//取消wish express
think\Route::post('/disable-wish-express$', 'listing/Wish/batachDisableWishExpress',[], []);
//批量设置wish express数据
think\Route::post('/batch-setting-wish-express$', 'listing/Wish/batchSettingWishExpress',[], []);
//获取wish express数据
think\Route::get('/listing/wish/getWishExpressData$', 'listing/Wish/wishExpress',[], []);
//wish在线listing修改日志
think\Route::get('/listing/wish/logs$', 'listing/Wish/logs',[], []);
//更新修改了的资料listing
think\Route::post('/listing/wish/rsyncEditListing$', 'listing/Wish/rsyncEditListing',[], []);
//批量编辑获取sku数据
think\Route::get('/listing/wish/batchEdit$', 'listing/Wish/batchEdit',[], []);
//批量编辑提交
think\Route::post('/listing/wish/batchEditAction$', 'listing/Wish/batchEditAction',[], []);
//同步listing
think\Route::post('/listing/wish/rsyncListing$', 'listing/Wish/rsyncListing',[], []);
//编辑指定国家的运费
think\Route::post('/listing/wish/updateShipping$', 'listing/Wish/updateShipping',[], []);
//编辑产品的所有的国家航运价格
think\Route::post('/listing/wish/updateMultiShipping$', 'listing/Wish/updateMultiShipping',[], []);
//编辑产品的所有的国家航运价格
think\Route::post('/listing/wish/updateMultiShippingRightNow$', 'listing/Wish/updateMultiShippingRightNow',[], []);
//获取wish  express
think\Route::get('/listing/wish/getShipping$', 'listing/Wish/getShipping',[], []);
//更新wish在线listing数据
think\Route::post('/listing/wish/updateListing$', 'listing/Wish/updateListing',[], []);
//更新已刊登listing数据
think\Route::post('/listing/wish/updatePublishedListing$', 'listing/Wish/updatePublishedListing',[], []);
//wish刊登模块查看功能
think\Route::get('/listing/wish/view$', 'listing/Wish/view',[], []);
//wish刊登模块编辑功能
think\Route::get('/listing/wish/edit$', 'listing/Wish/edit',[], []);
//wish刊登模块复制功能
think\Route::get('/listing/wish/copy$', 'listing/Wish/copy',[], []);
//补货
think\Route::post('/listing/wish/buhuo$', 'listing/Wish/buhuo',[], []);
//批量上架
think\Route::post('/listing/wish/batchEnable$', 'listing/Wish/batchEnable',[], []);
//批量下架
think\Route::post('/listing/wish/batchDisable$', 'listing/Wish/batchDisable',[], []);
//在线产品上架
think\Route::post('/listing/wish/enable$', 'listing/Wish/enable',[], []);
//在线产品下架
think\Route::post('/listing/wish/disable$', 'listing/Wish/disable',[], []);
//sku下架
think\Route::post('/listing/wish/disableVariant$', 'listing/Wish/disableVariant',[], []);
//skus上架
think\Route::post('/listing/wish/enableVariant$', 'listing/Wish/enableVariant',[], []);
//批量上架sku
think\Route::post('/listing/wish/batchEnableVariant$', 'listing/Wish/batchEnableVariant',[], []);
//批量下架sku
think\Route::post('/listing/wish/batchDisableVariant$', 'listing/Wish/batchDisableVariant',[], []);
//批量同步listing,不走队列
think\Route::post('/listing/wish/rsyncNowListing$', 'listing/Wish/rsyncNowListing',[], []);

//控制器：app\listing\controller\Aliexpress
//获取选中spu的分类
think\Route::get('/listing/aliexpress/get-same-spu-category$', 'listing/Aliexpress/getSameSpuCategory',[], []);
//查询所选SPU的产品分类值,及对应的属性、属性值
think\Route::get('/listing/aliexpress/get-same-spu-Attribute$', 'listing/Aliexpress/getSameSpuAttribute',[], []);
//速卖通获取类似产品
think\Route::get('/listing/aliexpress/getSameSpu$', 'listing/Aliexpress/getSameSpu',[], []);
//速卖通在线listing修改日志
think\Route::get('/listing/aliexpress/logs$', 'listing/Aliexpress/logs',[], []);
//速卖通卖家橱窗设置
think\Route::get('/aliexpress-windows-detail$', 'listing/Aliexpress/windowdetail',[], []);
//速卖通卖家橱窗设置
think\Route::get('/aliexpress-windows-list$', 'listing/Aliexpress/windowList',[], []);
//速卖通卖家橱窗设置
think\Route::post('/setWindowProducts$', 'listing/Aliexpress/window',[], []);
//修改sku库存信息
think\Route::post('/editAeStock$', 'listing/Aliexpress/editStock',[], []);
//修改sku售价信息
think\Route::post('/editAePrice$', 'listing/Aliexpress/editPrice',[], []);
//修改信息模板
think\Route::post('/editAeTemlate$', 'listing/Aliexpress/editTemlate',[], []);
//速卖通上架
think\Route::post('/onlineAeProduct$', 'listing/Aliexpress/onlineAeProduct',[], []);
//速卖通下架
think\Route::post('/offlineAeProduct$', 'listing/Aliexpress/offlineAeProduct',[], []);
//修改产品分组
think\Route::post('/editAeGroupId$', 'listing/Aliexpress/editGroupId',[], []);
//编辑发货期
think\Route::post('/editAeDeliveryTime$', 'listing/Aliexpress/editDeliveryTime',[], []);
//延长商品有效期
think\Route::post('/editAeWsValidNum$', 'listing/Aliexpress/editWsValidNum',[], []);
//商品标题
think\Route::post('/editAeSubject$', 'listing/Aliexpress/editSubject',[], []);
//商品销售单元
think\Route::post('/editAeProductUnit$', 'listing/Aliexpress/editProductUnit',[], []);
//修改产品毛重
think\Route::post('/editAeGrossWeight$', 'listing/Aliexpress/editGrossWeight',[], []);
//修改包装尺寸
think\Route::post('/editAePackage$', 'listing/Aliexpress/package',[], []);
//服务模板设置
think\Route::post('/editAePromiseTemplateId$', 'listing/Aliexpress/promiseTemplateId',[], []);
//运费模板设置
think\Route::post('/editAeFreightTemplateId$', 'listing/Aliexpress/freightTemplateId',[], []);
//商品一口价
think\Route::post('/editAeProductPrice$', 'listing/Aliexpress/productPrice',[], []);
//同步listing
think\Route::post('/rsyncAeProduct$', 'listing/Aliexpress/rsync',[], []);
//更新修改了资料的listing
think\Route::post('/rsyncEditAeProduct$', 'listing/Aliexpress/rsyncEditAeProduct',[], []);

//控制器：app\order\controller\Order
//订单列表
think\Route::get('/orders$', 'order/Order/index',[], []);
//读取信息
think\Route::get('/orders/:id$', 'order/Order/read',[], ['id'=>'(\d+)']);
//读取编辑信息
think\Route::GET('/orders/:id/edit$', 'order/Order/edit',[], ['id'=>'(\d+)']);
//更新
think\Route::PUT('/orders/:id$', 'order/Order/update',[], ['id'=>'(\d+)']);
//获取平台渠道
think\Route::get('/orders/channel$', 'order/Order/channel',[], []);
//获取平台/站点账号信息
think\Route::get('/orders/account$', 'order/Order/account',[], []);
//账号 店铺 信息
think\Route::get('/orders/shop$', 'order/Order/shop',[], []);
//获取操作信息
think\Route::get('/orders/:type/info$', 'order/Order/info',[], ['type'=>'(\w+)']);
//生成发票
think\Route::get('/orders/:order_id/generate$', 'order/Order/generate',[], ['order_id'=>'(\d+)']);
//合并包裹功能
think\Route::post('/orders/:order_id/merge$', 'order/Order/merge',[], ['order_id'=>'(\d+)']);
//拆分包裹功能
think\Route::post('/orders/:order_id/split$', 'order/Order/split',[], ['order_id'=>'(\d+)']);
//地址使用
think\Route::post('/orders/:order_id/address$', 'order/Order/address',[], ['order_id'=>'(\d+)']);
//标记已读,删除备注等操作
think\Route::post('/orders/:order_id/:type$', 'order/Order/execute',[], ['order_id'=>'(\d+)', 'type'=>'(\w+)']);
//获取进度条信息
think\Route::get('/orders/:order_id/speed$', 'order/Order/speed',[], ['order_id'=>'(\d+)']);
//获取产品详情信息
think\Route::get('/orders/:order_id/detail$', 'order/Order/detail',[], ['order_id'=>'(\d+)']);
//获取拆包裹的订单信息
think\Route::get('/orders/:order_id/split$', 'order/Order/splitInfo',[], ['order_id'=>'(\d+)']);
//获取合并包裹的订单信息
think\Route::get('/orders/:order_id/merge$', 'order/Order/mergeInfo',[], ['order_id'=>'(\d+)']);
//标记为未付款
think\Route::post('/orders/:order_id/status/0$', 'order/Order/status',[], ['order_id'=>'(\d+)']);
//标记为已付款
think\Route::post('/orders/:order_id/status/65536$', 'order/Order/paying',[], ['order_id'=>'(\d+)']);
//需人工审核
think\Route::post('/orders/:order_id/status/65792$', 'order/Order/review',[], ['order_id'=>'(\d+)']);
//标记为已审核
think\Route::post('/orders/:order_id/status/65793$', 'order/Order/audited',[], ['order_id'=>'(\d+)']);
//作废订单
think\Route::post('/orders/:order_id/status/4294967295$', 'order/Order/invalid',[], ['order_id'=>'(\d+)']);
//标记刷单
think\Route::post('/orders/:order_id/status/9999999999$', 'order/Order/brush',[], ['order_id'=>'(\d+)']);
//取消作废
think\Route::post('/orders/:order_id/status/cancel-invalid$', 'order/Order/cancel',[], ['order_id'=>'(\d+)']);
//取消仓库推送
think\Route::post('/orders/cancel-push$', 'order/Order/cancelPush',[], []);
//取消物流下单
think\Route::post('/orders/cancel-logistics$', 'order/Order/cancelLogistics',[], []);
//批量更改运输方式
think\Route::post('/orders/update-shipping$', 'order/Order/updateShipping',[], []);
//订单重新跑规则
think\Route::post('/orders/again-running-rule$', 'order/Order/againRunningRule',[], []);
//获取产品-添加货品
think\Route::get('/orders/getGoods$', 'order/Order/getGoods',[], []);
//获取指定类型单号的买家和归属平台账号的信息
think\Route::get('/orders/:order_number_type/:order_number/buyer-info$', 'order/Order/getOrderBuyerInfo',[], []);
//导出execl
think\Route::post('/orders/export$', 'order/Order/export',[], []);
//execl字段信息
think\Route::get('/orders/export-title$', 'order/Order/title',[], []);
//sku新增备注信息
think\Route::post('/orders/:order_id/:sku_id/note$', 'order/Order/note',[], []);
//批量新增订单备注
think\Route::post('/orders/batch/remark$', 'order/Order/remark',[], []);
//重新获取商品成本
think\Route::get('/orders/:order_id/cost$', 'order/Order/retryCost',[], []);
//导入跟踪号
think\Route::post('/orders/tracking/import$', 'order/Order/import',[], []);
//批量作废
think\Route::post('/orders/batch/invalid$', 'order/Order/batchInvalid',[], []);
//延长买家收货时间
think\Route::post('/orders/:day/delay-time$', 'order/Order/orderDelayTime',[], ['day'=>'(\d+)']);
//通过订单号查询物流信息
think\Route::post('/orders/:order_id/logistics-info$', 'order/Order/getLogisticsInfo',[], ['order_id'=>'(\d+)']);
//批量更换仓库
think\Route::post('/orders/batch/change-warehouse$', 'order/Order/batchChangeWarehouse',[], []);
//试跑规则
think\Route::post('/orders/trial-rule$', 'order/Order/trialAgainRunningRule',[], []);
//取消订单接口
think\Route::post('/orders/cancel-order$', 'order/Order/cancelOrder',[], []);
//发送发票
think\Route::post('/orders/send-invoice$', 'order/Order/sendInvoice',[], []);
//批量人工审核
think\Route::post('/orders/batch-review$', 'order/Order/batchReview',[], []);
//取消订单重新退款
think\Route::post('/orders/order-renew-refund$', 'order/Order/orderRenewRefund',[], []);
//手动标记已退款
think\Route::post('/orders/mark-refund-failed$', 'order/Order/markRefundFailed',[], []);
//订单备注批量导入
think\Route::POST('/orders/batch-import$', 'order/Order/batchImport',[], []);
//品连订单回推测试
think\Route::get('/orders/test$', 'order/Order/tests',[], []);
//品连订单回推测试[异常]
think\Route::get('/orders/test2$', 'order/Order/tests2',[], []);
//批量联系买家
think\Route::post('/orders/batch/send-message$', 'order/Order/sendMessage',[], []);

//控制器：app\goods\controller\Goodsdev
//产品开发列表
think\Route::get('/goodsdev$', 'goods/Goodsdev/index',[], []);
//保存产品开发
think\Route::post('/goodsdev$', 'goods/Goodsdev/save',[], []);
//保存产品开发
think\Route::post('/goodsdev/save/base-info$', 'goods/Goodsdev/saveBaseInfo',[], []);
//编辑产品开发
think\Route::get('/:id/edit$', 'goods/Goodsdev/edit',[], []);
//更新产品开发
think\Route::put('/:id$', 'goods/Goodsdev/update',[], []);
//查看分类规格参数
think\Route::get('/goodsdev/category-specification/:id$', 'goods/Goodsdev/getCateSpecification',[], ['id'=>'(\d+)']);
//查看分类属性
think\Route::get('/goodsdev/category-attribute/:id$', 'goods/Goodsdev/getCateAttribute',[], ['id'=>'(\d+)']);
//查看产品开发基础信息
think\Route::get('/goodsdev/:id/base-info$', 'goods/Goodsdev/getBaseInfo',[], ['id'=>'(\d+)']);
//查看产品开发供应商信息
think\Route::get('/goodsdev/:id/supplier$', 'goods/Goodsdev/getSupplierInfo',[], ['id'=>'(\d+)']);
//保存供应商信息
think\Route::put('/goodsdev/:id/supplier$', 'goods/Goodsdev/saveSupplierInfo',[], ['id'=>'(\d+)']);
//查看产品开发规格信息
think\Route::get('/goodsdev/:id/specification$', 'goods/Goodsdev/getSpecification',[], ['id'=>'(\d+)']);
//查看产品开发属性
think\Route::get('/goodsdev/:id/attribute$', 'goods/Goodsdev/getAttribute',[], ['id'=>'(\d+)']);
//查看产品开发描述
think\Route::get('/goodsdev/:id/description$', 'goods/Goodsdev/getDescription',[], ['id'=>'(\d+)']);
//查看产品开发日志
think\Route::get('/goodsdev/:id/logs$', 'goods/Goodsdev/getLog',[], ['id'=>'(\d+)']);
//添加产品开发备注
think\Route::post('/goodsdev/log/:id$', 'goods/Goodsdev/addLog',[], ['id'=>'(\d+)']);
//更新产品开发描述
think\Route::put('/goodsdev/description/:id$', 'goods/Goodsdev/updateDescription',[], ['id'=>'(\d+)']);
//更新产品开发基础信息
think\Route::put('/goodsdev/base/:id$', 'goods/Goodsdev/updateBaseInfo',[], ['id'=>'(\d+)']);
//更新产品开发规格信息
think\Route::put('/goodsdev/specification/:id$', 'goods/Goodsdev/updateSpecification',[], ['id'=>'(\d+)']);
//获取编辑开发产品属性信息
think\Route::get('/goodsdev/attribute/:id/edit$', 'goods/Goodsdev/editAttribute',[], ['id'=>'(\d+)']);
//更新开发产品属性参数
think\Route::put('/goodsdev/attribute/:id$', 'goods/Goodsdev/updateAttribute',[], ['id'=>'(\d+)']);
//获取流程按钮组
think\Route::get('/goodsdev/processbtn$', 'goods/Goodsdev/getProcessBtn',[], []);
//获取流程处理按钮根据ID
think\Route::get('/goodsdev/processbtn/:id$', 'goods/Goodsdev/getProcessBtnById',[], ['id'=>'(\d+)']);
//产品开发流程操作
think\Route::put('/goodsdev/process/:id$', 'goods/Goodsdev/process',[], ['id'=>'(\d+)']);
//获取分类sku
think\Route::post('/goodsdev/category-sku$', 'goods/Goodsdev/getCategorySkuLists',[], []);
//获取平台销售状态
think\Route::get('/goodsdev/platform-sale-status$', 'goods/Goodsdev/getPlatformSaleStatus',[], []);
//获取平台分类
think\Route::get('/goodsdev/:id/platform-sale$', 'goods/Goodsdev/getPlatformSale',[], []);
//保存平台分类
think\Route::put('/goodsdev/:id/platform-sale$', 'goods/Goodsdev/putPlatformSale',[], []);
//获取编辑sku
think\Route::get('/goodsdev/:id/sku-list$', 'goods/Goodsdev/getSkuList',[], ['id'=>'(\d+)']);
//保存sku列表信息
think\Route::put('/goodsdev/:id/sku-list$', 'goods/Goodsdev/saveSkuLists',[], ['id'=>'(\d+)']);
//获取编辑产品质检信息
think\Route::get('/goodsdev/:id/qcitems$', 'goods/Goodsdev/editQcItems',[], ['id'=>'(\d+)']);
//获取产品修图要求
think\Route::get('/goodsdev/:id/img-requirement$', 'goods/Goodsdev/getImgRequirement',[], ['id'=>'(\d+)']);
//保存修图要求
think\Route::put('/goodsdev/:id/img-requirement$', 'goods/Goodsdev/saveImgRequirement',[], ['id'=>'(\d+)']);
//保存产品质检信息
think\Route::put('/goodsdev/:id/qcitems$', 'goods/Goodsdev/saveQcItems',[], ['id'=>'(\d+)']);
//获取开发产品节点信息
think\Route::get('/goodsdev/node/:id$', 'goods/Goodsdev/node',[], ['id'=>'(\d+)']);
//批量处理流程
think\Route::post('/goodsdev/batch/process$', 'goods/Goodsdev/batchProcess',[], []);
//批量添加开发者矩阵批量添加开发者
think\Route::post('/developer/batch/add$', 'goods/Goodsdev/addDeveloper',[], []);
//修改开发员信息
think\Route::put('/developer/:id$', 'goods/Goodsdev/developerUpdate',[], ['id'=>'(\d+)']);
//开发员矩阵列表
think\Route::get('/developer$', 'goods/Goodsdev/developer',[], []);
//删除开发员信息
think\Route::delete('/developer/:id$', 'goods/Goodsdev/removeDeveloper',[], ['id'=>'(\d+)']);
//获取开发员矩阵详情
think\Route::get('/goodsdev/:id/developer$', 'goods/Goodsdev/getDeveloperById',[], []);
//生成sku
think\Route::get('/goodsdev/:id/generate-sku$', 'goods/Goodsdev/generateSku',[], []);
//确认生成sku
think\Route::put('/goodsdev/:id/generate-sku$', 'goods/Goodsdev/sureGenerateSku',[], []);
//保存报关信息
think\Route::put('/goodsdev/:id/declare$', 'goods/Goodsdev/saveDeclare',[], []);
//指定摄影师
think\Route::put('/goodsdev/:id/set-grapher$', 'goods/Goodsdev/setGrapher',[], []);
//开始拍图
think\Route::put('/goodsdev/:id/start-photo$', 'goods/Goodsdev/startPhoto',[], []);
//设置原图路径
think\Route::put('/goodsdev/:id/set-photo-path$', 'goods/Goodsdev/setPhotoPath',[], []);
//获取拍图待审核信息
think\Route::get('/goodsdev/:id/photo$', 'goods/Goodsdev/getPhotoInfo',[], []);
//分配翻译员
think\Route::put('/goodsdev/:id/set-translator$', 'goods/Goodsdev/setTranslator',[], []);
//获取翻译员信息
think\Route::get('/goodsdev/:id/translator-info$', 'goods/Goodsdev/getTranslatorInfo',[], []);
//开始翻译
think\Route::put('/goodsdev/:id/translator-starting$', 'goods/Goodsdev/startTranslator',[], []);
//翻译中确定
think\Route::put('/goodsdev/:id/translator-ing$', 'goods/Goodsdev/translatorIng',[], []);
//翻译提交审批
think\Route::put('/goodsdev/:id/:lang_id/translator-submit$', 'goods/Goodsdev/translatorSubmit',[], []);
//审核不通过退回语种
think\Route::put('/goodsdev/:id/translator-back$', 'goods/Goodsdev/translatorBack',[], []);
//待分配修图指定美工
think\Route::put('/goodsdev/:id/designer-setting$', 'goods/Goodsdev/designerSetting',[], []);
//开始修图
think\Route::put('/goodsdev/:id/designer-starting$', 'goods/Goodsdev/designerStarting',[], []);
//保存修图路径
think\Route::put('/goodsdev/:id/ps_img_url$', 'goods/Goodsdev/psImgUrl',[], []);
//提交终审..
think\Route::put('/goodsdev/:id/final_submit$', 'goods/Goodsdev/finalSubmit',[], []);
//获取退回的指定节点
think\Route::get('/goodsdev/:id/back-process$', 'goods/Goodsdev/getBackProcess',[], []);
//退回的指定节点
think\Route::put('/goodsdev/:id/back-process$', 'goods/Goodsdev/backProcess',[], []);
//发布产品
think\Route::put('/goodsdev/:id/release$', 'goods/Goodsdev/release',[], []);
//获取菜单
think\Route::get('/goodsdev/:id/menu$', 'goods/Goodsdev/menu',[], []);

//控制器：app\index\controller\PurchaseSubclassMap
//列表
think\Route::get('/sub-map$', 'index/PurchaseSubclassMap/index',[], []);
//信息
think\Route::get('/sub-map/:id$', 'index/PurchaseSubclassMap/read',[], ['id'=>'(\d+)']);
//获取编辑信息
think\Route::GET('/sub-map/:id/edit$', 'index/PurchaseSubclassMap/edit',[], ['id'=>'(\d+)']);
//新增关系
think\Route::post('/sub-map$', 'index/PurchaseSubclassMap/save',[], []);
//更新关系
think\Route::PUT('/sub-map/:id$', 'index/PurchaseSubclassMap/update',[], ['id'=>'(\d+)']);
//删除
think\Route::DELETE('/sub-map/:id$', 'index/PurchaseSubclassMap/delete',[], ['id'=>'(\d+)']);
//批量删除
think\Route::post('/sub-map/batch/:type$', 'index/PurchaseSubclassMap/batch',[], ['type'=>'(\w+)']);

//控制器：app\warehouse\controller\Warehouse
//仓库管理列表
think\Route::get('/warehouse$', 'warehouse/Warehouse/index',[], []);
//仓库管理添加
think\Route::post('/warehouse$', 'warehouse/Warehouse/save',[], []);
//仓库管理获取
think\Route::get('/warehouse/:id$', 'warehouse/Warehouse/read',[], ['id'=>'(\d+)']);
//获取仓库邮寄方式
think\Route::get('/warehouse/:id/shipping-list$', 'warehouse/Warehouse/shippingList',[], ['id'=>'(\d+)']);
//仓库管理更新
think\Route::PUT('/warehouse/:id$', 'warehouse/Warehouse/update',[], ['id'=>'(\d+)']);
//仓库管理删除
think\Route::DELETE('/warehouse/:id$', 'warehouse/Warehouse/delete',[], ['id'=>'(\d+)']);
//修改仓库状态
think\Route::post('/warehouse/status$', 'warehouse/Warehouse/changeStates',[], []);
//获取全部物流商
think\Route::get('/warehouse/getCarrier$', 'warehouse/Warehouse/getCarrier',[], []);
//获取物流商对应的物流方式
think\Route::get('/warehouse/getShip$', 'warehouse/Warehouse/getShip',[], []);
//更新仓库
think\Route::post('/warehouse/updateWareHouse$', 'warehouse/Warehouse/updateWarehouse',[], []);
//获取省市区
think\Route::get('/warehouse/getArea$', 'warehouse/Warehouse/getArea',[], []);
//获取仓库类型
think\Route::get('/warehouse/getWarehouseType$', 'warehouse/Warehouse/getWarehouseType',[], []);
//search
think\Route::get('/warehouse/search$', 'warehouse/Warehouse/search',[], []);
//获取第三方仓库具体代码
think\Route::get('/warehouse/getWarehousesByType$', 'warehouse/Warehouse/getWarehousesByType',[], []);
//获取所有仓库
think\Route::get('/global/warehouse$', 'warehouse/Warehouse/warehouses',[], []);
//获取海外仓
think\Route::get('/warehouse/overseas$', 'warehouse/Warehouse/overseas',[], []);
//获取本地及海外仓
think\Route::get('/warehouse/lists$', 'warehouse/Warehouse/lists',[], []);
//获取本地仓
think\Route::get('/warehouse/local$', 'warehouse/Warehouse/local',[], []);
//获取第三方仓库
think\Route::get('/warehouse/third$', 'warehouse/Warehouse/third',[], []);
//获取中转仓
think\Route::get('/warehouse/transit$', 'warehouse/Warehouse/transit',[], []);
//仓库列表(传类型)
think\Route::get('/warehouse/info$', 'warehouse/Warehouse/info',[], []);
//仓库类型（产品预报）
think\Route::get('/warehouse/third-type$', 'warehouse/Warehouse/thirdType',[], []);
//参数设置
think\Route::post('/warehouse/:id/config$', 'warehouse/Warehouse/config',[], ['id'=>'(\d+)']);
//调拨仓库
think\Route::get('/warehouse/allocation-list$', 'warehouse/Warehouse/getAllocationWarehouse',[], []);
//fba仓库列表
think\Route::get('/warehouse/fba$', 'warehouse/Warehouse/fba',[], []);
//仓库站点配置列表
think\Route::get('/warehouse/system-list$', 'warehouse/Warehouse/getSystemConfig',[], []);
//新增仓库配置
think\Route::get('/warehouse/add-config$', 'warehouse/Warehouse/addConfig',[], []);
//引用系统仓库配置
think\Route::post('/warehouse/:id/use-config$', 'warehouse/Warehouse/useConfig',[], []);
//获取仓库系统配置
think\Route::get('/warehouse/:id/config$', 'warehouse/Warehouse/getConfigDetail',[], []);
//删除仓库配置
think\Route::delete('/warehouse/delete-config$', 'warehouse/Warehouse/deleteConfig',[], []);
//更新仓库参数配置
think\Route::put('/warehouse/update-config$', 'warehouse/Warehouse/updateConfig',[], []);
//获取仓库站点配置
think\Route::get('/warehouse/config$', 'warehouse/Warehouse/getConfig',[], []);
//修改备货周期
think\Route::put('/warehouse/stocking-cycle$', 'warehouse/Warehouse/saveStockingCycle',[], []);
//获取备货周期
think\Route::get('/warehouse/stocking-cycle$', 'warehouse/Warehouse/getStockingCycle',[], []);

//控制器：app\warehouse\controller\Carrier
//显示物流商列表
think\Route::get('/carrier$', 'warehouse/Carrier/index',[], []);
//添加物流商信息
think\Route::post('/carrier$', 'warehouse/Carrier/save',[], []);
//显示指定物流商资源
think\Route::get('/carrier/:id$', 'warehouse/Carrier/read',[], ['id'=>'(\d+)']);
//更新物流商信息
think\Route::put('/carrier/:id$', 'warehouse/Carrier/update',[], ['id'=>'(\d+)']);
//停用/启用物流商
think\Route::post('/carrier/status$', 'warehouse/Carrier/changeStates',[], []);
//同步邮寄方式
think\Route::POST('/carrier/down/shipping$', 'warehouse/Carrier/synShippingMethod',[], []);
//获取API及controller的code类型
think\Route::get('/carrier/index-code$', 'warehouse/Carrier/getIndexCode',[], []);
//获取平台物流信息
think\Route::get('/carrier-platform/:platform/:service$', 'warehouse/Carrier/platform',[], ['platform'=>'(\w+)', 'service'=>'(\w+)']);
//获取Wish邮授权url
think\Route::get('/carrier/wishpost-url$', 'warehouse/Carrier/getWishAuthUrl',[], []);
//获取wangji邮授权url
think\Route::get('/carrier/wangjipost-url$', 'warehouse/Carrier/getWangjiAuthUrl',[], []);
//wangji授权
think\Route::post('/carrier/wangji-authors$', 'warehouse/Carrier/wangjiAuthors',[], []);
//面单序列号
think\Route::get('/carrier/sequence-number$', 'warehouse/Carrier/sequenceNumber',[], []);
//wish授权
think\Route::post('/carrier/wish-authors$', 'warehouse/Carrier/wishAuthors',[], []);
//获取ebay收货地址
think\Route::post('/carrier/ebay-address$', 'warehouse/Carrier/ebayAddress',[], []);
//获取ebay交运偏好
think\Route::post('/carrier/ebay-preference$', 'warehouse/Carrier/updateConsignPreference',[], []);
//获取ebay交运偏好
think\Route::post('/carrier/ebay-token$', 'warehouse/Carrier/ebayToken',[], []);
//获取物流邮寄方式列表
think\Route::get('/carrier/:id/shipping$', 'warehouse/Carrier/shipping',[], ['id'=>'(\d+)']);
//获取物流邮寄方式列表
think\Route::get('/carrier/lists$', 'warehouse/Carrier/lists',[], []);

//控制器：app\order\controller\ManualOrder
//资源列表
think\Route::get('/manual-orders$', 'order/ManualOrder/index',[], []);
//获取编辑信息
think\Route::GET('/manual-orders/:id/edit$', 'order/ManualOrder/edit',[], ['id'=>'(\d+)']);
//查看信息
think\Route::get('/manual-orders/:id$', 'order/ManualOrder/read',[], ['id'=>'(\d+)']);
//保存资源
think\Route::post('/manual-orders$', 'order/ManualOrder/save',[], []);
//获取销售日期
think\Route::get('/manual-orders/date$', 'order/ManualOrder/date',[], []);
//获取订单号
think\Route::get('/manual-orders/number$', 'order/ManualOrder/number',[], []);
//获取邮寄方式
think\Route::get('/manual-orders/shipping$', 'order/ManualOrder/shipping',[], []);
//导入手工订单
think\Route::post('/manual-orders/import$', 'order/ManualOrder/import',[], []);
//保存导入手工订单
think\Route::post('/manual-orders/save-import$', 'order/ManualOrder/saveImport',[], []);
//导出execl
think\Route::post('/manual-orders/export$', 'order/ManualOrder/export',[], []);
//获取包裹数据
think\Route::get('/manual-orders/package-list$', 'order/ManualOrder/getPackageList',[], []);
//批量创建补发单
think\Route::post('/manual-orders/batch-replacement$', 'order/ManualOrder/batchReplacement',[], []);
//获取买家信息
think\Route::get('/manual-orders/buyer-message$', 'order/ManualOrder/getBuyerMessage',[], []);

//控制器：app\order\controller\AuditOrder
//显示资源列表
think\Route::get('/orders-audit$', 'order/AuditOrder/index',[], []);
//批量操作
think\Route::post('/orders-audit/batch/:type$', 'order/AuditOrder/batch',[], ['type'=>'(\w+)']);
//获取平台账号
think\Route::get('/orders-audit/channelAccount$', 'order/AuditOrder/channelAccount',[], []);
//获取未审核状态
think\Route::get('/orders-audit/status$', 'order/AuditOrder/status',[], []);
//导出execl
think\Route::post('/orders-audit/export$', 'order/AuditOrder/export',[], []);
//获取人工审核状态
think\Route::get('/orders-audit/manual-review-status$', 'order/AuditOrder/ManualReviewStatus',[], []);
//标记订单联系状态
think\Route::post('/orders-audit/mark-link-status$', 'order/AuditOrder/markLinkBuyerStatus',[], []);

//控制器：app\order\controller\InvoiceRecord
//发票记录列表
think\Route::get('/invoices$', 'order/InvoiceRecord/index',[], []);
//查看发票记录
think\Route::get('/invoices/:id$', 'order/InvoiceRecord/read',[], ['id'=>'(\d+)']);
//删除/批量删除
think\Route::post('/invoices/batch$', 'order/InvoiceRecord/batch',[], []);

//控制器：app\order\controller\InvoiceRule
//发票规则列表
think\Route::get('/invoice-rules$', 'order/InvoiceRule/index',[], []);
//编辑
think\Route::GET('/invoice-rules/:id/edit$', 'order/InvoiceRule/edit',[], ['id'=>'(\d+)']);
//查看信息
think\Route::get('/invoice-rules/:id$', 'order/InvoiceRule/read',[], ['id'=>'(\d+)']);
//保存
think\Route::post('/invoice-rules$', 'order/InvoiceRule/save',[], []);
//更新发票规则
think\Route::PUT('/invoice-rules/:id$', 'order/InvoiceRule/update',[], ['id'=>'(\d+)']);
//删除
think\Route::DELETE('/invoice-rules/:id$', 'order/InvoiceRule/delete',[], ['id'=>'(\d+)']);
//获取可选条件
think\Route::get('/invoice-rules/items$', 'order/InvoiceRule/item',[], []);
//排序
think\Route::post('/invoice-rules/sort$', 'order/InvoiceRule/sort',[], []);
//更改规则状态
think\Route::post('/invoice-rules/:id/status/:value$', 'order/InvoiceRule/status',[], ['id'=>'(\d+)', 'value'=>'(\d+)']);
//获取信息
think\Route::get('/invoice-rules/:type/info$', 'order/InvoiceRule/info',[], ['type'=>'(\w+)']);

//控制器：app\order\controller\InvoiceTemplate
//模板列表
think\Route::get('/invoice-template$', 'order/InvoiceTemplate/index',[], []);

//控制器：app\order\controller\Rule
//显示资源列表
think\Route::get('/rules$', 'order/Rule/index',[], []);
//显示指定的资源
think\Route::get('/rules/:id$', 'order/Rule/read',[], ['id'=>'(\d+)']);
//编辑指定的资源
think\Route::GET('/rules/:id/edit$', 'order/Rule/edit',[], ['id'=>'(\d+)']);
//保存的资源
think\Route::post('/rules$', 'order/Rule/save',[], []);
//保存更新的资源
think\Route::PUT('/rules/:id$', 'order/Rule/update',[], ['id'=>'(\d+)']);
//删除指定资源
think\Route::DELETE('/rules/:id$', 'order/Rule/delete',[], ['id'=>'(\d+)']);
//更改规则状态
think\Route::post('/rules/status$', 'order/Rule/changeStatus',[], []);
//获取资源
think\Route::post('/rules/resources$', 'order/Rule/resources',[], []);
//获取发货仓库信息
think\Route::get('/rules/warehouse$', 'order/Rule/warehouse',[], []);
//获取运输方式
think\Route::get('/rules/shipping$', 'order/Rule/shipping',[], []);
//获取订单自动处理方法
think\Route::get('/rules/action$', 'order/Rule/getAction',[], []);
//保存排序值
think\Route::post('/rules/sort$', 'order/Rule/changeSort',[], []);
//规则复制
think\Route::post('/rules/copy$', 'order/Rule/ruleCopy',[], []);
//规则日志
think\Route::get('/rules/:rule_id/log$', 'order/Rule/log',[], []);
//获取默认规则信息
think\Route::get('/rules/default$', 'order/Rule/default',[], []);
//批量修改运输方式
think\Route::post('/rules/batch/shipping$', 'order/Rule/batchShipping',[], []);

//控制器：app\order\controller\Wish
//显示资源列表
think\Route::get('/wish-orders$', 'order/Wish/index',[], []);
//显示指定的资源
think\Route::get('/wish-orders/:id$', 'order/Wish/read',[], ['id'=>'(\w+)']);
//获取状态列表
think\Route::get('/wish-orders/status$', 'order/Wish/status',[], []);
//导出execl
think\Route::post('/wish-orders/export$', 'order/Wish/export',[], []);
//获取所有导出字段
think\Route::get('/wish-orders/export-fields$', 'order/Wish/getExportFields',[], []);
//更新平台订单信息
think\Route::post('/wish-orders/online$', 'order/Wish/online',[], []);
//快速核查平台订单
think\Route::post('/wish-orders/check$', 'order/Wish/check',[], []);
//wish放款模板下载
think\Route::GET('/wish-orders/export-transfer-template$', 'order/Wish/exportTemplate',[], []);
//wish导入放款表单
think\Route::POST('/wish-orders/import-transfer$', 'order/Wish/importTransfer',[], []);
//wish导入财务数据
think\Route::post('/wish-order/import-settle$', 'order/Wish/importWishSettle',[], []);
//快速抓取订单
think\Route::POST('/wish-orders/pull-order$', 'order/Wish/pullOrder',[], []);

//控制器：app\order\controller\RuleItem
//显示资源列表
think\Route::get('/rule-items$', 'order/RuleItem/index',[], []);

//控制器：app\order\controller\DeclareRule
//列表
think\Route::get('/declare-rules$', 'order/DeclareRule/index',[], []);
//读取规则编辑信息
think\Route::GET('/declare-rules/:id/edit$', 'order/DeclareRule/edit',[], ['id'=>'(\d+)']);
//读取规则信息
think\Route::get('/declare-rules/:id$', 'order/DeclareRule/read',[], ['id'=>'(\d+)']);
//新增
think\Route::post('/declare-rules$', 'order/DeclareRule/save',[], []);
//更新
think\Route::PUT('/declare-rules/:id$', 'order/DeclareRule/update',[], ['id'=>'(\d+)']);
//删除
think\Route::DELETE('/declare-rules/:id$', 'order/DeclareRule/delete',[], ['id'=>'(\d+)']);
//更改规则状态
think\Route::post('/declare-rules/:id/status/:value$', 'order/DeclareRule/status',[], ['id'=>'(\d+)', 'value'=>'(\d+)']);
//获取可选条件
think\Route::get('/declare-rules/items$', 'order/DeclareRule/item',[], []);
//获取默认申报设置
think\Route::get('/declare-rules/defaults$', 'order/DeclareRule/defaultRule',[], []);
//获取资源
think\Route::post('/declare-rules/resources$', 'order/DeclareRule/resources',[], []);
//保存排序值
think\Route::post('/declare-rules/sort$', 'order/DeclareRule/sort',[], []);
//保存默认申报设置
think\Route::post('/declare-rules/keep$', 'order/DeclareRule/keep',[], []);
//默认设置的信息
think\Route::get('/declare-rules/info$', 'order/DeclareRule/defaultInfo',[], []);

//控制器：app\order\controller\DownTest
//查看
think\Route::GET('/down-datas/:id$', 'order/DownTest/read',[], ['id'=>'(\d+)']);

//控制器：app\purchase\controller\Supplier
//供应商列表
think\Route::get('/supplier$', 'purchase/Supplier/index',[], []);
//保存资源
think\Route::post('/supplier$', 'purchase/Supplier/save',[], []);
//查看资源
think\Route::get('/supplier/:id$', 'purchase/Supplier/read',[], ['id'=>'(\d+)']);
//供应商停用测试
think\Route::put('/supplier/test-supplier-disuse$', 'purchase/Supplier/testSupplierDisuse',[], []);
//显示编辑资源表单页.
think\Route::GET('/supplier/:id/edit$', 'purchase/Supplier/edit',[], ['id'=>'(\d+)']);
//供应商更新
think\Route::put('/supplier/:id$', 'purchase/Supplier/update',[], ['id'=>'(\d+)']);
//删除指定资源
think\Route::DELETE('/supplier/:id$', 'purchase/Supplier/delete',[], ['id'=>'(\d+)']);
//获取信息
think\Route::get('/supplier/:type/info$', 'purchase/Supplier/info',[], ['type'=>'(\w+)']);
//审核
think\Route::post('/supplier/status$', 'purchase/Supplier/status',[], []);
//设置默认供应商
think\Route::post('/supplier/setDefault$', 'purchase/Supplier/setDefault',[], []);
//根据供应商ID列表返回供应商列表
think\Route::get('/supplier/getSuppliersInfo$', 'purchase/Supplier/getSuppliersInfo',[], []);
//按条件导出供应商
think\Route::post('/supplier/exportSupplierByConditions$', 'purchase/Supplier/exportSupplierByConditions',[], []);
//获取导出供应商所有字段
think\Route::get('/supplier/export-fields$', 'purchase/Supplier/getExportFields',[], []);
//导出供应商
think\Route::POST('/supplier/export$', 'purchase/Supplier/export',[], []);
//导出供应商至富友
think\Route::POST('/supplier/export-fuiou$', 'purchase/Supplier/exportFuiou',[], []);
//获取各个仓库最低报价的交期
think\Route::GET('/supplier/delivery$', 'purchase/Supplier/getSaleDelivery',[], []);
//通过Excel导入供应商信息
think\Route::post('/supplier/import$', 'purchase/Supplier/importSupplier',[], []);
//获取虚拟供应商
think\Route::GET('/supplier/virtual-supplier$', 'purchase/Supplier/getVirtualSupper',[], []);
//获取供应商日志
think\Route::GET('/supplier/:id/log$', 'purchase/Supplier/getSupplierLog',[], []);
//下载图片
think\Route::GET('/supplier/download-image$', 'purchase/Supplier/downloadImg',[], []);
//修改采购员
think\Route::PUT('/supplier/change-purchaser$', 'purchase/Supplier/changePurchaser',[], []);
//获取退货天数
think\Route::GET('/supplier/get-return-goods-data$', 'purchase/Supplier/getReturnGoodsData',[], []);
//获取是否贴标、套牌
think\Route::GET('/supplier/get-label-deck$', 'purchase/Supplier/getLabelDeck',[], []);
//获取对应供应商的采购记录的金额
think\Route::GET('/supplier/get-supplier-purchase$', 'purchase/Supplier/getSupplierPurchase',[], []);
//获取供应链部门ID
think\Route::GET('/supplier/get-supply-chain-department-Id$', 'purchase/Supplier/getSupplyChainDepartmentId',[], []);
//修改自动生成付款申请单
think\Route::PUT('/supplier/auto-payment-request$', 'purchase/Supplier/autoPaymentRequest',[], []);
//批量修改供应商结算方式
think\Route::PUT('/supplier/change-balance-type$', 'purchase/Supplier/changeBalanceType',[], []);
//供应商停用
think\Route::PUT('/supplier/disable$', 'purchase/Supplier/disable',[], []);

//控制器：app\purchase\controller\SupplierOffer
//显示资源列表
think\Route::get('/supplier-offer$', 'purchase/SupplierOffer/index',[], []);
//保存报价
think\Route::post('/supplier-offer$', 'purchase/SupplierOffer/save',[], []);
//显示编辑资源表单页
think\Route::GET('/supplier-offer/:id/edit$', 'purchase/SupplierOffer/edit',[], ['id'=>'(\d+)']);
//审核报价单
think\Route::post('/supplier-offer/status$', 'purchase/SupplierOffer/status',[], []);
//获取供应商信息
think\Route::get('/supplier-offer/supplier$', 'purchase/SupplierOffer/supplier',[], []);
//获取货币信息
think\Route::get('/supplier-offer/currency$', 'purchase/SupplierOffer/currency',[], []);
//获取仓库信息
think\Route::get('/supplier-offer/warehouse$', 'purchase/SupplierOffer/warehouse',[], []);
//获取品牌信息
think\Route::get('/supplier-offer/brand$', 'purchase/SupplierOffer/brand',[], []);
//历史报价记录  查一个sku 的所有报价
think\Route::get('/supplier-offer/history$', 'purchase/SupplierOffer/history',[], []);
//当前报价
think\Route::get('/supplier-offer/current$', 'purchase/SupplierOffer/current',[], []);
//获取主产品SKU
think\Route::get('/supplier-offer/getGoodsSkus$', 'purchase/SupplierOffer/getGoodsSkus',[], []);
//获取供应商SKU列表
think\Route::get('/supplier-offer/getSupplierSkus$', 'purchase/SupplierOffer/getSupplierSkus',[], []);
//获取默认供应商及其报价
think\Route::get('/supplier-offer/getDefaultSupplierPriceBySku$', 'purchase/SupplierOffer/getDefaultSupplierPriceBySku',[], []);
//导入供应商报价
think\Route::post('/supplier-offer/import$', 'purchase/SupplierOffer/excelImport',[], []);
//导出商品转成joom格式
think\Route::get('/supplier-offer/export$', 'purchase/SupplierOffer/export',[], []);
//导出全部
think\Route::get('/supplier-offer/export-all$', 'purchase/SupplierOffer/exportAll',[], []);

//控制器：app\purchase\controller\SafeDelivery
//安全交期列表
think\Route::get('/safe$', 'purchase/SafeDelivery/index',[], []);
//设置安全交期
think\Route::post('/safe/changeDelivery$', 'purchase/SafeDelivery/changeDelivery',[], []);
//保存
think\Route::post('/safe/keep$', 'purchase/SafeDelivery/keep',[], []);
//批量导入安全期数据
think\Route::post('/safe/import$', 'purchase/SafeDelivery/import',[], []);
//获取导出所有字段
think\Route::get('/safe/export-fields$', 'purchase/SafeDelivery/getExportFields',[], []);
//导出安全交期
think\Route::post('/safe/export$', 'purchase/SafeDelivery/export',[], []);
//获取安全交期
think\Route::get('/safe/getDeliveryDays$', 'purchase/SafeDelivery/getDeliveryDays',[], []);

//控制器：app\purchase\controller\PurchaseRule
//显示资源列表
think\Route::get('/purchase-rules$', 'purchase/PurchaseRule/index',[], []);
//查看资源
think\Route::get('/purchase-rules/:id$', 'purchase/PurchaseRule/read',[], ['id'=>'(\d+)']);
//显示编辑资源表单页
think\Route::GET('/purchase-rules/:id/edit$', 'purchase/PurchaseRule/edit',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/purchase-rules/:id$', 'purchase/PurchaseRule/update',[], ['id'=>'(\d+)']);
//保存资源
think\Route::post('/purchase-rules$', 'purchase/PurchaseRule/save',[], []);
//删除指定资源
think\Route::DELETE('/purchase-rules/:id$', 'purchase/PurchaseRule/delete',[], ['id'=>'(\d+)']);
//更改规则状态
think\Route::post('/purchase-rules/:id/status/:value$', 'purchase/PurchaseRule/status',[], ['id'=>'(\d+)', 'value'=>'(\d+)']);
//获取资源
think\Route::post('/purchase-rules/resources$', 'purchase/PurchaseRule/resources',[], []);
//保存排序值
think\Route::post('/purchase-rules/sort$', 'purchase/PurchaseRule/sort',[], []);

//控制器：app\purchase\controller\PurchaseRuleItem
//显示资源列表
think\Route::get('/purchase-rules-items$', 'purchase/PurchaseRuleItem/index',[], []);

//控制器：app\index\controller\User
//显示资源列表
think\Route::get('/user$', 'index/User/index',[], []);
//添加用户
think\Route::post('/user$', 'index/User/save',[], []);
//查看用户
think\Route::get('/user/:id$', 'index/User/read',[], ['id'=>'(\d+)']);
//查看用户
think\Route::GET('/user/:id/edit$', 'index/User/edit',[], ['id'=>'(\d+)']);
//更新用户
think\Route::PUT('/user/:id$', 'index/User/update',[], ['id'=>'(\d+)']);
//删除用户
think\Route::DELETE('/user/:id$', 'index/User/delete',[], ['id'=>'(\d+)']);
//获取所有部门和角色
think\Route::get('/user/departmentAndRole$', 'index/User/departmentAndRole',[], []);
//停用，启用账号
think\Route::get('/user/status$', 'index/User/changeStatus',[], []);
//批量禁用
think\Route::post('/user/batch$', 'index/User/batch',[], []);
//修改密码
think\Route::post('/user/updatePassword$', 'index/User/updatePassword',[], []);
//重置密码
think\Route::post('/user/:id/reset-password$', 'index/User/resetPassword',[], []);
//获取角色下的成员
think\Route::get('/user/member$', 'index/User/member',[], []);
//获取员工的信息
think\Route::get('/user/:type/staffs$', 'index/User/staffs',[], ['type'=>'(\w+)']);
//获取领导
think\Route::get('/user/:type/:work/leaders$', 'index/User/leaders',[], ['type'=>'(\w+)', 'work'=>'(\w+)']);
//获取用户filter过滤器列表
think\Route::get('/user/getFilters$', 'index/User/getFilters',[], []);
//验证旧密码
think\Route::post('/user/check-password$', 'index/User/checkPassword',[], []);
//模拟登陆
think\Route::post('/user/simulation-on$', 'index/User/simulationOn',[], []);
//更新当前用户token
think\Route::post('/user/update-token$', 'index/User/updateToken',[], []);
//获取用户的部门信息
think\Route::get('/user/:id/get-department$', 'index/User/getUserDepartment',[], []);
//获取用户日志
think\Route::get('/user/:id/logs$', 'index/User/getUserLog',[], []);
//获取登录用户的信息
think\Route::get('/user/login-user-position$', 'index/User/getUserPositionByLoginUser',[], []);

//控制器：app\index\controller\DownloadFile
//下载导出的文件
think\Route::get('/downloadFile/downExportFile$', 'index/DownloadFile/downExportFile',[], []);
//下载打印机
think\Route::get('/printer$', 'index/DownloadFile/downPrint',[], []);
//下载发票pdf文件
think\Route::get('/downloadFile/downPdfFile$', 'index/DownloadFile/downPdfFile',[], []);

//控制器：app\index\controller\Department
//部门列表
think\Route::get('/department$', 'index/Department/index',[], []);
//部门管理添加
think\Route::post('/department$', 'index/Department/save',[], []);
//部门管理获取
think\Route::get('/department/:id$', 'index/Department/read',[], ['id'=>'(\d+)']);
//部门管理编辑
think\Route::GET('/department/:id/edit$', 'index/Department/edit',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/department/:id$', 'index/Department/update',[], ['id'=>'(\d+)']);
//删除
think\Route::DELETE('/department/:id$', 'index/Department/delete',[], ['id'=>'(\d+)']);
//停用，启用账号
think\Route::get('/department/changeStatus$', 'index/Department/changeStatus',[], []);
//获取所有部门
think\Route::get('/department/getDepartment$', 'index/Department/getDepartment',[], []);
//获取公司信息
think\Route::get('/company$', 'index/Department/company',[], []);
//获取用户
think\Route::get('/department/getUser$', 'index/Department/getUser',[], []);
//保存调序
think\Route::post('/department/sort$', 'index/Department/sort',[], []);
//部门类型
think\Route::get('/department/type$', 'index/Department/type',[], []);
//获取部门修改日志
think\Route::get('/department/:id/logs$', 'index/Department/log',[], ['id'=>'(\d+)']);
//获取对应渠道的销售
think\Route::get('/department/:id/department-users$', 'index/Department/departmentUserByChannelId',[], []);

//控制器：app\index\controller\Account
//读取账号信息
think\Route::get('/channels/channels/:channel/accounts$', 'index/Account/accounts',[], ['channel'=>'(\w+)']);

//控制器：app\index\controller\Config
//显示资源列表
think\Route::get('/config$', 'index/Config/index',[], []);
//添加
think\Route::post('/config$', 'index/Config/save',[], []);
//显示指定的资源
think\Route::get('/config/:id$', 'index/Config/read',[], ['id'=>'(\w+)']);
//站点配置
think\Route::get('/config/site$', 'index/Config/readSite',[], []);
//显示编辑资源表单页.
think\Route::GET('/config/:id/edit$', 'index/Config/edit',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/config/:id$', 'index/Config/update',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::put('/config/paramsConfig/:id$', 'index/Config/paramsConfig',[], []);
//删除
think\Route::DELETE('/config/:id$', 'index/Config/delete',[], ['id'=>'(\d+)']);
//获取分组
think\Route::get('/config/groups$', 'index/Config/groups',[], []);
//停用，启用
think\Route::get('/config/status$', 'index/Config/changeStatus',[], []);
//排序
think\Route::post('/config/sort$', 'index/Config/sort',[], []);
//数据类型
think\Route::get('/config/type$', 'index/Config/type',[], []);

//控制器：app\index\controller\Express
//读取国内快递信息
think\Route::get('/express$', 'index/Express/index',[], []);

//控制器：app\system\controller\ConfigParams
//新的系统配置列表
think\Route::get('/system-config$', 'system/ConfigParams/index',[], []);
//新的系统配置添加
think\Route::post('/system-config$', 'system/ConfigParams/save',[], []);
//添加分组
think\Route::post('/system-config/group$', 'system/ConfigParams/add_group',[], []);
//添加配置
think\Route::post('/system-config/param$', 'system/ConfigParams/add_param',[], []);
//修改分组
think\Route::put('/system-config/group$', 'system/ConfigParams/mdf_group',[], []);
//修改配置
think\Route::put('/system-config/param$', 'system/ConfigParams/mdf_param',[], []);
//删除分组
think\Route::delete('/system-config/group/:id$', 'system/ConfigParams/del_group',[], []);
//删除配置
think\Route::delete('/system-config/param:/id$', 'system/ConfigParams/del_param',[], []);

//控制器：app\index\controller\DeveloperTeam
//分组列表
think\Route::get('/developers$', 'index/DeveloperTeam/index',[], []);
//读取
think\Route::get('/developers/:id$', 'index/DeveloperTeam/read',[], ['id'=>'(\d+)']);
//获取编辑信息
think\Route::GET('/developers/:id/edit$', 'index/DeveloperTeam/edit',[], ['id'=>'(\d+)']);
//保存
think\Route::post('/developers$', 'index/DeveloperTeam/save',[], []);
//更新
think\Route::PUT('/developers/:id$', 'index/DeveloperTeam/update',[], ['id'=>'(\d+)']);
//删除
think\Route::DELETE('/developers/:id$', 'index/DeveloperTeam/delete',[], ['id'=>'(\d+)']);
//批量删除
think\Route::post('/developers/batch/:type$', 'index/DeveloperTeam/batch',[], ['type'=>'(\w+)']);
//获取分类信息
think\Route::get('/developers/categories$', 'index/DeveloperTeam/category',[], []);

//控制器：app\index\controller\WishAccount
//显示资源列表
think\Route::get('/wish-account$', 'index/WishAccount/index',[], []);
//保存新建的资源
think\Route::post('/wish-account$', 'index/WishAccount/save',[], []);
//显示指定的资源
think\Route::get('/wish-account/:id$', 'index/WishAccount/read',[], ['id'=>'(\d+)']);
//显示编辑资源表单页.
think\Route::GET('/wish-account/:id/edit$', 'index/WishAccount/edit',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/wish-account/:id$', 'index/WishAccount/update',[], ['id'=>'(\d+)']);
//停用，启用账号
think\Route::post('/wish-account/states$', 'index/WishAccount/changeStates',[], []);
//获取授权码
think\Route::post('/wish-account/authorCode$', 'index/WishAccount/authorCode',[], []);
//查询wish账号
think\Route::get('/wish-account/query$', 'index/WishAccount/query',[], []);
//获取Token
think\Route::post('/wish-account/token$', 'index/WishAccount/token',[], []);
//获取Token
think\Route::GET('/wish-account/refresh_token/:id$', 'index/WishAccount/refresh_token',[], []);
//授权页面
think\Route::post('/wish-account/authorization$', 'index/WishAccount/authorization',[], []);
//wish 批量开启
think\Route::post('/wish-account/batch-set$', 'index/WishAccount/batchSet',[], []);

//控制器：app\index\controller\Job
//部门代码列表
think\Route::get('/job$', 'index/Job/index',[], []);

//控制器：app\index\controller\MemberShip
//成员列表
think\Route::get('/member-ship$', 'index/MemberShip/index',[], []);
//查看成员账号绑定信息
think\Route::get('/member-ship/:id$', 'index/MemberShip/read',[], ['id'=>'(\w+)']);
//获取编辑成员信息
think\Route::GET('/member-ship/:id/edit$', 'index/MemberShip/edit',[], ['id'=>'(\w+)']);
//新增成员
think\Route::post('/member-ship$', 'index/MemberShip/save',[], []);
//更新成员
think\Route::put('/member-ship/:id$', 'index/MemberShip/update',[], ['id'=>'(\w+)']);
//删除
think\Route::DELETE('/member-ship/:id$', 'index/MemberShip/delete',[], ['id'=>'(\w+)']);
//批量删除
think\Route::post('/member-ship/batch/:type$', 'index/MemberShip/batch',[], ['type'=>'(\w+)']);
//查找成员关系
think\Route::get('/member-ship/memberInfo$', 'index/MemberShip/memberInfo',[], []);
//获取渠道 销售员-客服信息
think\Route::get('/member-ship/:type/member$', 'index/MemberShip/member',[], ['type'=>'(\w+)']);
//刊登获取 销售员-客服信息
think\Route::get('/member-ship/:channel_id/:type/publish$', 'index/MemberShip/publish',[], ['channel_id'=>'(\d+)', 'type'=>'(\w+)']);
//全部导出
think\Route::get('/member-ship/download$', 'index/MemberShip/download',[], []);
//日志
think\Route::get('/member-ship/log$', 'index/MemberShip/log',[], []);
//平台账号成员列表
think\Route::get('/member-ship/channel-user-account$', 'index/MemberShip/channelUserAccount',[], []);
//添加平台账号成员
think\Route::post('/member-ship/add-account$', 'index/MemberShip/addAccount',[], []);

//控制器：app\index\controller\Login
//显示资源列表
think\Route::get('/login$', 'index/Login/index',[], []);
//登录
think\Route::post('/login$', 'index/Login/save',[], []);
//退出
think\Route::post('/login/quit$', 'index/Login/quit',[], []);
//权限
think\Route::get('/login/permission$', 'index/Login/permission',[], []);
//获取登录信息
think\Route::get('/login/info$', 'index/Login/info',[], []);
//获取websocket token
think\Route::get('/login/ws-token$', 'index/Login/webSocketToken',[], []);
//获取验证码
think\Route::get('/login/code$', 'index/Login/captcha',[], []);

//控制器：app\goods\controller\Category
//产品分类列表
think\Route::get('/categories$', 'goods/Category/index',[], []);
//分类设置采购员列表
think\Route::get('/categories/purchaser$', 'goods/Category/purchaser_id',[], []);
//分类设置采购员保存
think\Route::put('/categories/:id/purchaser-save$', 'goods/Category/purchaser_save',[], ['id'=>'(\d+)']);
//保存产品分类
think\Route::post('/categories$', 'goods/Category/save',[], []);
//查看产品分类
think\Route::get('/categories/:id$', 'goods/Category/read',[], ['id'=>'(\d+)']);
//编辑产品分类
think\Route::get('/categories/:id/edit$', 'goods/Category/edit',[], ['id'=>'(\d+)']);
//更新产品分类
think\Route::put('/categories/:id$', 'goods/Category/update',[], ['id'=>'(\d+)']);
//获取日志列表
think\Route::get('/categories/:id/logs$', 'goods/Category/logs',[], ['id'=>'(\d+)']);
//删除产品分类
think\Route::delete('/categories/:id$', 'goods/Category/delete',[], ['id'=>'(\d+)']);
//删除缓存
think\Route::get('/categories/cache$', 'goods/Category/delCache',[], []);
//修改产品分类排序
think\Route::put('/categories/sorts$', 'goods/Category/sorts',[], []);
//分类列表
think\Route::get('/categories/lists$', 'goods/Category/lists',[], []);

//控制器：app\goods\controller\GoodsSkuMap
//显示资源列表
think\Route::get('/sku-map$', 'goods/GoodsSkuMap/index',[], []);
//保存新建的资源
think\Route::post('/sku-map$', 'goods/GoodsSkuMap/save',[], []);
//显示指定的资源
think\Route::get('/sku-map/:id$', 'goods/GoodsSkuMap/read',[], ['id'=>'(\d+)']);
//显示指定的资源
think\Route::GET('/sku-map/:id/edit$', 'goods/GoodsSkuMap/edit',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/sku-map/:id$', 'goods/GoodsSkuMap/update',[], ['id'=>'(\d+)']);
//删除指定资源
think\Route::DELETE('/sku-map/:id$', 'goods/GoodsSkuMap/delete',[], ['id'=>'(\d+)']);
//批量删除
think\Route::post('/sku-map/batch$', 'goods/GoodsSkuMap/batch',[], []);
//获取平台信息
think\Route::get('/sku-map/channel$', 'goods/GoodsSkuMap/channel',[], []);
//获取账号信息
think\Route::get('/sku-map/account$', 'goods/GoodsSkuMap/account',[], []);
//获取本地sku信息
think\Route::get('/sku-map/skuInfo$', 'goods/GoodsSkuMap/skuInfo',[], []);
//获取员工信息
think\Route::get('/sku-map/employee$', 'goods/GoodsSkuMap/employee',[], []);
//搜索sku
think\Route::get('/sku-map/query$', 'goods/GoodsSkuMap/query',[], []);
//查看是否已关联
think\Route::get('/sku-map/map$', 'goods/GoodsSkuMap/map',[], []);
//导入商品映射信息
think\Route::post('/sku-map/import$', 'goods/GoodsSkuMap/excelImport',[], []);
//产品映射管理导出
think\Route::post('/sku-map/export$', 'goods/GoodsSkuMap/export',[], []);
//批量设置虚拟仓发货
think\Route::put('/sku-map/batch/virtual$', 'goods/GoodsSkuMap/batchSetVirtual',[], []);

//控制器：app\customerservice\controller\OrderSale
//首页列表
think\Route::get('/order-sales$', 'customerservice/OrderSale/index',[], []);
//获取编辑信息
think\Route::GET('/order-sales/:id/edit$', 'customerservice/OrderSale/edit',[], ['id'=>'(\d+)']);
//查看售后信息
think\Route::get('/order-sales/:id$', 'customerservice/OrderSale/read',[], ['id'=>'(\d+)']);
//更新售后信息
think\Route::PUT('/order-sales/:id$', 'customerservice/OrderSale/update',[], ['id'=>'(\d+)']);
//批量提交
think\Route::post('/order-sales/batch-update$', 'customerservice/OrderSale/batchUpdate',[], []);
//新建售后信息
think\Route::post('/order-sales$', 'customerservice/OrderSale/save',[], []);
//删除售后
think\Route::DELETE('/order-sales/:id$', 'customerservice/OrderSale/delete',[], ['id'=>'(\d+)']);
//获取状态
think\Route::get('/order-sales/:type/info$', 'customerservice/OrderSale/info',[], ['type'=>'(\w+)']);
//获取渠道信息
think\Route::get('/order-sales/channels$', 'customerservice/OrderSale/channels',[], []);
//审批通过
think\Route::post('/order-sales/adopt/status$', 'customerservice/OrderSale/adopt',[], []);
//退回修改
think\Route::post('/order-sales/retreat/status$', 'customerservice/OrderSale/retreat',[], []);
//退款标记为完成
think\Route::post('/order-sales/complete/status$', 'customerservice/OrderSale/complete',[], []);
//退款重新执行
think\Route::post('/order-sales/again/status$', 'customerservice/OrderSale/again',[], []);
//提交审批
think\Route::post('/order-sales/submit$', 'customerservice/OrderSale/submit',[], []);
//查找订单
think\Route::get('/order-sales/find$', 'customerservice/OrderSale/findOrder',[], []);
//execl字段信息
think\Route::get('/order-sales/export-title$', 'customerservice/OrderSale/title',[], []);
//导出execl
think\Route::post('/order-sales/export$', 'customerservice/OrderSale/export',[], []);
//批量审核
think\Route::post('/order-sales/batch-adopt$', 'customerservice/OrderSale/batchAdopt',[], []);
//批量退回修改
think\Route::post('/order-sales/batch-retreat$', 'customerservice/OrderSale/batchRetreat',[], []);

//控制器：app\customerservice\controller\SaleReason
//列表
think\Route::get('/sale-reasons$', 'customerservice/SaleReason/index',[], []);
//售后原因添加
think\Route::post('/sale-reasons$', 'customerservice/SaleReason/save',[], []);
//售后原因删除
think\Route::DELETE('/sale-reasons/:id$', 'customerservice/SaleReason/delete',[], ['id'=>'(\d+)']);

//控制器：app\api\controller\Get
//默认访问页面
think\Route::get('/get$', 'api/Get/index',[], []);

//控制器：app\api\controller\Post
//默认访问页面
think\Route::post('/post$', 'api/Post/index',[], []);

//控制器：app\index\controller\Node
//服务端节点列表
think\Route::get('/node$', 'index/Node/index',[], []);
//获取节点页面信息
think\Route::get('/node/pageNode$', 'index/Node/getPageNode',[], []);
//忽略权限的节点列表
think\Route::get('/node/ignore-vists$', 'index/Node/getIgnoreVists',[], []);
//设置节点页面信息
think\Route::put('/node/pageNode$', 'index/Node/setPageNode',[], []);
//获取节点过虑器列表
think\Route::get('/node/filterNode$', 'index/Node/getFilterNode',[], []);
//设置节点过虑器列表
think\Route::put('/node/filterNode$', 'index/Node/setFilterNode',[], []);
//获取节点信息
think\Route::get('/node/config/:nodeid$', 'index/Node/nodeConfig',[], []);
//停用，启用
think\Route::get('/node/changeStatus$', 'index/Node/changeStatus',[], []);
//排序
think\Route::POST('/node/sort$', 'index/Node/sort',[], []);

//控制器：app\customerservice\controller\AliexpressEvaluate
//评价列表
think\Route::get('/ali-evaluate$', 'customerservice/AliexpressEvaluate/index',[], []);
//评价明细
think\Route::get('/ali-evaluate/:id$', 'customerservice/AliexpressEvaluate/read',[], ['id'=>'(\d+)']);
//回评
think\Route::post('/ali-evaluate/evaluate$', 'customerservice/AliexpressEvaluate/evaluate',[], []);
//批量回评
think\Route::post('/ali-evaluate/batchEvaluate$', 'customerservice/AliexpressEvaluate/batchEvaluate',[], []);
//追加评论
think\Route::post('/ali-evaluate/append$', 'customerservice/AliexpressEvaluate/appendEvaluate',[], []);
//获取评价模板内容
think\Route::get('/ali-evaluate/tmpContent$', 'customerservice/AliexpressEvaluate/getTmpContent',[], []);
//获取各状态数量
think\Route::get('/ali-evaluate/statistics$', 'customerservice/AliexpressEvaluate/statistics',[], []);
//系统订单评价
think\Route::post('/ali-evaluate/evaluate-order$', 'customerservice/AliexpressEvaluate/evaluateOnOrder',[], []);
//获取评价分类标签
think\Route::get('/ali-evaluate/statistics-score$', 'customerservice/AliexpressEvaluate/statisticsByScore',[], []);

//控制器：app\customerservice\controller\AliexpressIssue
//纠纷列表
think\Route::get('/ali-issue$', 'customerservice/AliexpressIssue/index',[], []);
//查询纠纷详细
think\Route::get('/ali-issue/:id$', 'customerservice/AliexpressIssue/read',[], ['id'=>'(\d+)']);
//上传纠纷图片
think\Route::post('/ali-issue/upload-images$', 'customerservice/AliexpressIssue/uploadImages',[], []);
//同意普通纠纷方案
think\Route::post('/ali-issue/agree-solution$', 'customerservice/AliexpressIssue/agreeSolution',[], []);
//新增(拒绝某个买家方案)
think\Route::post('/ali-issue/add-solution$', 'customerservice/AliexpressIssue/addSolution',[], []);
//修改方案
think\Route::post('/ali-issue/edit-solution$', 'customerservice/AliexpressIssue/editSolution',[], []);
//获取标签统计数量
think\Route::get('/ali-issue/get-label$', 'customerservice/AliexpressIssue/getLabelStatistics',[], []);
//获取速卖通卖家退货地址
think\Route::get('/ali-issue/get-refund-address/:account_id$', 'customerservice/AliexpressIssue/getRefundAddress',[], []);
//获取纠纷历史
think\Route::get('/ali-issue/get-process/:issue_id$', 'customerservice/AliexpressIssue/getProcess',[], []);
//立即抓取
think\Route::post('/ali-issue/sync$', 'customerservice/AliexpressIssue/sync',[], []);

//控制器：app\customerservice\controller\AliexpressMsg
//收件箱列表
think\Route::get('/aliexpress-msg$', 'customerservice/AliexpressMsg/index',[], []);
//站内信明细
think\Route::get('/aliexpress-msg/:id$', 'customerservice/AliexpressMsg/read',[], ['id'=>'(\d+)']);
//获取Aliexpress标签
think\Route::get('/aliexpress-msg/rank$', 'customerservice/AliexpressMsg/getRanks',[], []);
//获取消息明细
think\Route::get('/aliexpress-msg/:id/detail$', 'customerservice/AliexpressMsg/getDetail',[], ['id'=>'(\d+)']);
//获取所有标签下站内信数量
think\Route::get('/aliexpress-msg/rankStatistics$', 'customerservice/AliexpressMsg/getRnakStatistics',[], []);
//获取客服对应的账号
think\Route::GET('/aliexpress-msg/account$', 'customerservice/AliexpressMsg/getAccountList',[], []);
//获取站内信处理优先级
think\Route::get('/aliexpress-msg/level$', 'customerservice/AliexpressMsg/getLevel',[], []);
//获取站内信各优先级下数量
think\Route::get('/aliexpress-msg/levelStatistics$', 'customerservice/AliexpressMsg/getLevelStatistics',[], []);
//修改优先级
think\Route::post('/aliexpress-msg/:id/changeLevel/:level$', 'customerservice/AliexpressMsg/changeLevel',[], ['id'=>'(\w+)', 'level'=>'(\d+)']);
//获取相关订单信息
think\Route::get('/aliexpress-msg/:id/orders$', 'customerservice/AliexpressMsg/getRelatedOrders',[], ['id'=>'(\d+)']);
//回复消息
think\Route::post('/aliexpress-msg/replay$', 'customerservice/AliexpressMsg/replay',[], []);
//发送新站内信消息
think\Route::post('/aliexpress-msg/add-msg$', 'customerservice/AliexpressMsg/addMsg',[], []);
//打标签(已改为奇门接口)
think\Route::post('/aliexpress-msg/:id/changeRank/:rank$', 'customerservice/AliexpressMsg/changeRank',[], ['id'=>'(\w+)', 'rank'=>'(\d+)']);
//处理消息(已改为奇门接口)
think\Route::post('/aliexpress-msg/batchProcessed$', 'customerservice/AliexpressMsg/processedMsg',[], []);
//标记消息已读(已改为奇门接口)
think\Route::post('/aliexpress-msg/:id/readMsg$', 'customerservice/AliexpressMsg/readMsg',[], ['id'=>'(\d+)']);
//获取回复模板内容
think\Route::get('/aliexpress-msg/tmpContent$', 'customerservice/AliexpressMsg/getTemplateDetail',[], []);
//获取客服
think\Route::get('/aliexpress-msg/customer$', 'customerservice/AliexpressMsg/getCustomers',[], []);
//联系订单买家
think\Route::post('/aliexpress-msg/$', 'customerservice/AliexpressMsg/replayOnOrder',[], []);
//获取联系买家模板内容
think\Route::get('/aliexpress-msg/temp-detail-order$', 'customerservice/AliexpressMsg/getTempDetailForOrder',[], []);
//展开更多消息
think\Route::get('/aliexpress-msg/more-msg$', 'customerservice/AliexpressMsg/getMoreMsg',[], []);
//测试同步
think\Route::get('/aliexpress-msg/testSyn$', 'customerservice/AliexpressMsg/testSyn',[], []);
//同步站内信
think\Route::post('/aliexpress-msg/sync$', 'customerservice/AliexpressMsg/sync',[], []);
//根据平台订单号获取站内信消息
think\Route::get('/aliexpress-msg/order/:order_no$', 'customerservice/AliexpressMsg/getMsgByOrderNo',[], ['order_no'=>'(\d+)']);

//控制器：app\customerservice\controller\AliexpressOutbox
//发件箱列表
think\Route::get('/ali-outbox$', 'customerservice/AliexpressOutbox/index',[], []);
//消息明细
think\Route::get('/ali-outbox/:id$', 'customerservice/AliexpressOutbox/read',[], ['id'=>'(\d+)']);
//重发消息
think\Route::post('/ali-outbox/:id/resend$', 'customerservice/AliexpressOutbox/resend',[], ['id'=>'(\d+)']);
//速卖通发件箱删除
think\Route::DELETE('/ali-outbox/:id$', 'customerservice/AliexpressOutbox/delete',[], ['id'=>'(\d+)']);

//控制器：app\order\controller\Aliexpress
//速卖通订单列表
think\Route::get('/aliexpress-order$', 'order/Aliexpress/index',[], []);
//订单详细
think\Route::get('/aliexpress-order/:id$', 'order/Aliexpress/read',[], ['id'=>'(\d+)']);
//获取所有订单状态
think\Route::get('/aliexpress-order/status$', 'order/Aliexpress/status',[], []);
//延迟收货时间
think\Route::put('/aliexpress-order/times$', 'order/Aliexpress/delayTime',[], []);
//速卖通订单导入
think\Route::post('/aliexpress-order/import$', 'order/Aliexpress/import',[], []);
//Aliexpress查找订单存在
think\Route::post('/aliexpress-order/exists$', 'order/Aliexpress/exists',[], []);
//Aliexpress同步平台订单；
think\Route::POST('/aliexpress-order/sysc$', 'order/Aliexpress/sysc',[], []);
//导出
think\Route::post('/aliexpress-order/export$', 'order/Aliexpress/export',[], []);
//速卖通导出字段
think\Route::GET('/aliexpress-order/export-fields$', 'order/Aliexpress/getExportFields',[], []);
//推送ali order 至系统订单
think\Route::post('/aliexpress-order/push-aliorder$', 'order/Aliexpress/pushAliOrder',[], []);
//拉取速卖通订单
think\Route::post('/aliexpress-order/sysc-aliorder$', 'order/Aliexpress/syscAliorder',[], []);
//aliexpress导入交易记录数据
think\Route::post('/aliexpress-order/import-settle$', 'order/Aliexpress/importAliexpressSettle',[], []);

//控制器：app\index\controller\Currency
//查看币种列表
think\Route::get('/currency$', 'index/Currency/index',[], []);
//查看汇率历史记录
think\Route::get('/currency/:id$', 'index/Currency/read',[], ['id'=>'(\d+)']);
//创建币种
think\Route::post('/currency$', 'index/Currency/save',[], []);
//编辑币种
think\Route::get('/currency/:id/edit$', 'index/Currency/edit',[], ['id'=>'(\d+)']);
//更新币种汇率
think\Route::put('/currency/:id$', 'index/Currency/update',[], ['id'=>'(\d+)']);
//更新官方汇率
think\Route::post('/currency/updateOfficialRate$', 'index/Currency/updateOfficialRate',[], []);
//查询官方汇率(新增币种)
think\Route::post('/currency/selectOfficialRate$', 'index/Currency/selectOfficialRate',[], []);
//修改币种排序
think\Route::put('/currency/sorts$', 'index/Currency/sorts',[], []);
//币种字段
think\Route::get('/currency/dictionary$', 'index/Currency/dictionary',[], []);

//控制器：app\order\controller\Amazon
//Amazon订单列表
think\Route::get('/amazon-orders$', 'order/Amazon/index',[], []);
//查看amazon订单
think\Route::get('/amazon-orders/:id$', 'order/Amazon/read',[], ['id'=>'(\d+)']);
//获取Amazon订单状态
think\Route::get('/amazon/order_status$', 'order/Amazon/status',[], []);
//获取指定类型单号的买家和归属平台账号的信息
think\Route::get('/amazon-orders/:order_number_type/:order_number/buyer-info$', 'order/Amazon/getOrderBuyerInfo',[], []);
//Amazon查找订单存在
think\Route::POST('/amazon-orders/exists$', 'order/Amazon/exists',[], []);
//amazon同步平台订单；
think\Route::POST('/amazon-orders/sysc$', 'order/Amazon/sysc',[], []);
//amazon放款模板下载；
think\Route::GET('/amazon-orders/export-transfer-template$', 'order/Amazon/exportTemplate',[], []);
//amazon导入放款表单；
think\Route::POST('/amazon-orders/import-transfer$', 'order/Amazon/importTransfer',[], []);
//亚马逊订单报表导出
think\Route::POST('/amazon-orders/export$', 'order/Amazon/export',[], []);
//获取所有导出字段
think\Route::get('/amazon-orders/export-fields$', 'order/Amazon/getExportFields',[], []);
//拉取亚马逊订单
think\Route::post('/amazon-orders/sysc-amazon-order$', 'order/Amazon/syscAmazonOrder',[], []);
//推送至系统订单
think\Route::Post('/amazon-orders/push-amazon-order$', 'order/Amazon/pushAmazonOrder',[], []);

//控制器：app\customerservice\controller\ShopeeDispute
//纠纷清单（取消订单、退款退货）
think\Route::GET('/shopee-dispute$', 'customerservice/ShopeeDispute/index',[], []);
//导出纠纷数据
think\Route::POST('/shopee-dispute/export$', 'customerservice/ShopeeDispute/export',[], []);
//订单取消分组统计数量
think\Route::GET('/shopee-dispute/cancel/group-count$', 'customerservice/ShopeeDispute/cancelGroupCount',[], []);
//订单退货分组统计数量
think\Route::GET('/shopee-dispute/return/group-count$', 'customerservice/ShopeeDispute/returnGroupCount',[], []);
//刷新订单取消
think\Route::POST('/shopee-dispute/cancel/refresh$', 'customerservice/ShopeeDispute/refreshCancel',[], []);
//刷新订单退货
think\Route::POST('/shopee-dispute/return/refresh$', 'customerservice/ShopeeDispute/refreshReturn',[], []);
//订单取消申请商品详情
think\Route::get('/shopee-dispute/cancel/:ordersn$', 'customerservice/ShopeeDispute/getCancelDetail',[], []);
//订单取消日志详情
think\Route::get('/shopee-dispute/:ordersn/cancel-log$', 'customerservice/ShopeeDispute/getCancelLog',[], []);
//订单退货申请详情
think\Route::get('/shopee-dispute/return/:returnsn$', 'customerservice/ShopeeDispute/getReturnDetail',[], []);
//订单退货申请纠纷
think\Route::get('/shopee-dispute/:returnsn/dispute$', 'customerservice/ShopeeDispute/getReturnDispute',[], []);
//订单退货申请日志
think\Route::get('/shopee-dispute/:returnsn/log$', 'customerservice/ShopeeDispute/getReturnLog',[], []);
//关联售后单ID
think\Route::put('/shopee-dispute/:returnsn/after-sale$', 'customerservice/ShopeeDispute/relateAfterSale',[], []);
//卖方取消订单
think\Route::put('/shopee-dispute/:ordersn/cancel$', 'customerservice/ShopeeDispute/cancelOrder',[], []);
//接受买方取消订单
think\Route::put('/shopee-dispute/:ordersn/accept$', 'customerservice/ShopeeDispute/acceptBuyerCancellation',[], []);
//拒绝买方取消订单
think\Route::put('/shopee-dispute/:ordersn/reject$', 'customerservice/ShopeeDispute/rejectBuyerCancellation',[], []);
//卖方接受退货
think\Route::put('/shopee-dispute/:returnsn/confirm$', 'customerservice/ShopeeDispute/confirmReturn',[], []);
//卖方争议退货
think\Route::post('/shopee-dispute/:returnsn/dispute$', 'customerservice/ShopeeDispute/disputeReturn',[], []);

//控制器：app\customerservice\controller\EbayFeedback
//评价列表
think\Route::GET('/ebay-feedback$', 'customerservice/EbayFeedback/index',[], []);
//查看评价
think\Route::GET('/ebay-feedback/:id$', 'customerservice/EbayFeedback/read',[], []);
//编辑评价
think\Route::GET('/ebay-feedback/:id/edit$', 'customerservice/EbayFeedback/edit',[], []);
//评价/回评
think\Route::POST('/ebay-feedback/comment$', 'customerservice/EbayFeedback/leaveComment',[], []);
//批量评价
think\Route::POST('/ebay-feedback/batch/comment$', 'customerservice/EbayFeedback/batchLeaveComment',[], []);
//重新发送评价
think\Route::POST('/ebay-feedback/repeat$', 'customerservice/EbayFeedback/repeatComment',[], []);
//追评
think\Route::POST('/ebay-feedback/respond$', 'customerservice/EbayFeedback/respondComment',[], []);
//跟进
think\Route::POST('/ebay-feedback/sendMsg$', 'customerservice/EbayFeedback/sendMessage',[], []);
//获取评价模板内容
think\Route::GET('/ebay-feedback/tplContent$', 'customerservice/EbayFeedback/getEvaluateTmpContent',[], []);
//更改评价状态
think\Route::POST('/ebay-feedback/status$', 'customerservice/EbayFeedback/changeStatus',[], []);
//回复买家评价
think\Route::POST('/ebay-feedback/reply$', 'customerservice/EbayFeedback/reply',[], []);
//统计回评状态-数量
think\Route::GET('/ebay-feedback/status$', 'customerservice/EbayFeedback/statusStatistics',[], []);

//控制器：app\customerservice\controller\EbayDispute
//纠纷列表
think\Route::GET('/ebay-dispute$', 'customerservice/EbayDispute/index',[], []);
//查看纠纷
think\Route::GET('/ebay-dispute/:id$', 'customerservice/EbayDispute/read',[], []);
//更新纠纷信息
think\Route::PUT('/ebay-dispute/:id$', 'customerservice/EbayDispute/update',[], []);
//批量更新纠纷信息
think\Route::PUT('/ebay-dispute/batch-update$', 'customerservice/EbayDispute/batchUpdate',[], []);
//获取纠纷类型列表
think\Route::GET('/ebay-dispute/types$', 'customerservice/EbayDispute/getDisputeType',[], []);
//纠纷状态列表
think\Route::GET('/ebay-dispute/status$', 'customerservice/EbayDispute/getDisputeStatus',[], []);
//获取搜索字段键值数组
think\Route::GET('/ebay-dispute/search/fields$', 'customerservice/EbayDispute/getSearchField',[], []);
//纠纷类型对应的ID描述值
think\Route::GET('/ebay-dispute/typeIds$', 'customerservice/EbayDispute/typeIds',[], []);
//卖家处理‘取消订单’纠纷
think\Route::POST('/ebay-dispute/operate/cancel$', 'customerservice/EbayDispute/operateCancel',[], []);
//卖家处理‘升级’纠纷
think\Route::POST('/ebay-dispute/operate/case$', 'customerservice/EbayDispute/operateCase',[], []);
//卖家处理‘未收到货’纠纷
think\Route::POST('/ebay-dispute/operate/inquiry$', 'customerservice/EbayDispute/operateInquiry',[], []);
//卖家处理‘退货退款’纠纷
think\Route::POST('/ebay-dispute/operate/return$', 'customerservice/EbayDispute/operateReturn',[], []);
//获取原因列表 - 下拉框
think\Route::GET('/ebay-dispute/reasons$', 'customerservice/EbayDispute/getReasons',[], []);

//控制器：app\customerservice\controller\EbayMessage
//ebay收件箱列表
think\Route::GET('/ebay-message$', 'customerservice/EbayMessage/index',[], []);
//发件箱列表
think\Route::GET('/ebay-message/getMessageList/outbox$', 'customerservice/EbayMessage/getMessageListOutbox',[], []);
//ebay来信
think\Route::GET('/ebay-message/getMessageList$', 'customerservice/EbayMessage/getMessageListInbox',[], []);
//查看站内信
think\Route::GET('/ebay-message/:id$', 'customerservice/EbayMessage/read',[], []);
//加载更多站内信
think\Route::GET('/ebay-message/list$', 'customerservice/EbayMessage/message_list',[], []);
//删除站内信
think\Route::DELETE('/ebay-message/:id$', 'customerservice/EbayMessage/delete',[], []);
//获取订单列表
think\Route::GET('/ebay-message/getOrderList$', 'customerservice/EbayMessage/getOrderList',[], []);
//获取客服对应的账号
think\Route::GET('/ebay-message/account$', 'customerservice/EbayMessage/getAccountList',[], []);
//发送消息
think\Route::POST('/ebay-message/send$', 'customerservice/EbayMessage/send',[], []);
//回复消息
think\Route::POST('/ebay-message/replay$', 'customerservice/EbayMessage/replay',[], []);
//重新发送
think\Route::POST('/ebay-message/resend$', 'customerservice/EbayMessage/resend',[], []);
//批量重新发送
think\Route::POST('/ebay-message/resend/batch$', 'customerservice/EbayMessage/resendBatch',[], []);
//修改状态
think\Route::POST('/ebay-message/status$', 'customerservice/EbayMessage/changeStatus',[], []);
//ebay客服账号列表
think\Route::GET('/ebay-message/getEbayCustomer$', 'customerservice/EbayMessage/getEbayCustomer',[], []);
//消息优先级消息统计
think\Route::GET('/ebay-message/getLevelCount$', 'customerservice/EbayMessage/getMessageLevelCount',[], []);
//优先级消息列表
think\Route::GET('/ebay-message/level$', 'customerservice/EbayMessage/getMessageLevel',[], []);
//修改站内信优先级
think\Route::POST('/ebay-message/updateMessageLevel$', 'customerservice/EbayMessage/updateMessageLevel',[], []);
//获取来往信息列表
think\Route::GET('/ebay-message/getGroupDatas$', 'customerservice/EbayMessage/getGroupDatas',[], []);
//更换站内信客服id
think\Route::post('/ebay-message/change-customer$', 'customerservice/EbayMessage/changeCustomer',[], []);
//更新指定id的站内信标签
think\Route::put('/ebay-message/:id$', 'customerservice/EbayMessage/update',[], []);
//站内信添加删除备注
think\Route::post('/ebay-message/remark$', 'customerservice/EbayMessage/remark',[], []);
//测试队列接收运行
think\Route::post('/ebay-message/queue$', 'customerservice/EbayMessage/queue',[], []);
//测试servers
think\Route::post('/ebay-message/server$', 'customerservice/EbayMessage/server',[], []);

//控制器：app\customerservice\controller\MsgRule
//自动发送规则列表
think\Route::GET('/msg-rule$', 'customerservice/MsgRule/index',[], []);
//新增
think\Route::POST('/msg-rule$', 'customerservice/MsgRule/save',[], []);
//编辑
think\Route::GET('/msg-rule/:id/edit$', 'customerservice/MsgRule/edit',[], []);
//更新
think\Route::PUT('/msg-rule/:id$', 'customerservice/MsgRule/update',[], []);
//删除
think\Route::DELETE('/msg-rule/:id$', 'customerservice/MsgRule/delete',[], []);
//更新状态（开启/停用）
think\Route::POST('/msg-rule/batch/update$', 'customerservice/MsgRule/updateStatus',[], []);
// 排序
think\Route::POST('/msg-rule/changeSort$', 'customerservice/MsgRule/changeSort',[], []);
// 统计每个触发时间下面的规则条数
think\Route::GET('/msg-rule/triggerStatistics$', 'customerservice/MsgRule/getTriggerRuleStatistics',[], []);
// 触发规则条件列表
think\Route::GET('/msg-rule/triggerRules$', 'customerservice/MsgRule/getTriggerRules',[], []);
// 发送邮规则条件列表
think\Route::GET('/msg-rule/emailRules$', 'customerservice/MsgRule/getSendEmailRules',[], []);
// 平台列表
think\Route::GET('/msg-rule/platform$', 'customerservice/MsgRule/getPlatform',[], []);
//匹配测试
think\Route::post('/msg-rule/triggerEventTest$', 'customerservice/MsgRule/triggerEventTest',[], []);
//加入站内信/评价自动发送列队
think\Route::post('/msg-rule/msgReviewAutoSendQueueTest$', 'customerservice/MsgRule/msgReviewAutoSendQueueTest',[], []);
//手动加入站内信队列
think\Route::post('/msg-rule/addSendMsg$', 'customerservice/MsgRule/addSendMsg',[], []);
//自动发送规则列表条件
think\Route::get('/where$', 'customerservice/MsgRule/index_where',[], []);
//设置回复内容md5值(临时)
think\Route::post('/msg-rule/content_md5$', 'customerservice/MsgRule/set_content_md5',[], []);
//设置去重字段only_key md5值（临时）
think\Route::post('/msg-rule/only_key_md5$', 'customerservice/MsgRule/set_only_key_md5',[], []);

//控制器：app\customerservice\controller\MsgRuleItem
//自动发送规则匹配项列表
think\Route::GET('/msg-rule-items$', 'customerservice/MsgRuleItem/index',[], []);

//控制器：app\customerservice\controller\MsgTemplate
//列表
think\Route::GET('/msg-tpl$', 'customerservice/MsgTemplate/index',[], []);
//查看
think\Route::GET('/msg-tpl/:id$', 'customerservice/MsgTemplate/read',[], []);
//新增
think\Route::POST('/msg-tpl$', 'customerservice/MsgTemplate/save',[], []);
//编辑
think\Route::GET('/msg-tpl/:id/edit$', 'customerservice/MsgTemplate/edit',[], []);
//更新
think\Route::PUT('/msg-tpl/:id$', 'customerservice/MsgTemplate/update',[], []);
//删除
think\Route::DELETE('/msg-tpl/:id$', 'customerservice/MsgTemplate/delete',[], []);
//删除
think\Route::POST('/msg-tpl/batch/delete$', 'customerservice/MsgTemplate/batchDelete',[], []);
//获取模板分类
think\Route::GET('/msg-tpl/getTypes$', 'customerservice/MsgTemplate/getTypes',[], []);
//获取模板数据字段列表
think\Route::GET('/msg-tpl/getFields$', 'customerservice/MsgTemplate/getFieldDatas',[], []);
//获取指定平台的所有模板列表
think\Route::GET('/msg-tpl/getTemplates$', 'customerservice/MsgTemplate/getTemplates',[], []);
//获取所有平台的所有模板
think\Route::GET('/msg-tpl/getAllTpls$', 'customerservice/MsgTemplate/getAllTemplates',[], []);
//获取模板内容
think\Route::GET('/msg-tpl/content$', 'customerservice/MsgTemplate/getTplContent',[], []);

//控制器：app\customerservice\controller\MsgTemplateGroup
//获取指定平台模板分组列表
think\Route::GET('/msg-tpl-group$', 'customerservice/MsgTemplateGroup/index',[], []);
//查看
think\Route::GET('/msg-tpl-group/:id$', 'customerservice/MsgTemplateGroup/read',[], []);
//新增
think\Route::POST('/msg-tpl-group$', 'customerservice/MsgTemplateGroup/save',[], []);
//编辑
think\Route::GET('/msg-tpl-group/:id/edit$', 'customerservice/MsgTemplateGroup/edit',[], []);
//更新
think\Route::PUT('/msg-tpl-group/:id$', 'customerservice/MsgTemplateGroup/update',[], []);
//删除
think\Route::DELETE('/msg-tpl-group/:id$', 'customerservice/MsgTemplateGroup/delete',[], []);

//控制器：app\customerservice\controller\AmazonFeedback
//亚马逊评价
think\Route::GET('/amazon/getFeedbacks$', 'customerservice/AmazonFeedback/index',[], []);
//中差评原因处理(提交中差评原因)
think\Route::POST('/amazon/submitFeedbackReason$', 'customerservice/AmazonFeedback/submitFeedbackReason',[], []);
//中差评原因处理情况()
think\Route::POST('/amazon/submitFeedbackDealingStatus$', 'customerservice/AmazonFeedback/submitFeedbackDealingStatus',[], []);
//客服列表
think\Route::GET('/amazon/getCustomerServiceOfficers$', 'customerservice/AmazonFeedback/customerServiceOfficers',[], []);

//控制器：app\goods\controller\Attribute
//查看属性列表
think\Route::get('/attributes$', 'goods/Attribute/index',[], []);
//保存属性
think\Route::post('/attributes$', 'goods/Attribute/save',[], []);
//查看属性详情
think\Route::get('/attributes/:id$', 'goods/Attribute/read',[], ['id'=>'(\d+)']);
//编辑属性
think\Route::get('/attributes/:id/edit$', 'goods/Attribute/edit',[], ['id'=>'(\d+)']);
//更新属性
think\Route::put('/attributes/:id$', 'goods/Attribute/update',[], ['id'=>'(\d+)']);
//删除属性
think\Route::delete('/attributes/:id$', 'goods/Attribute/delete',[], ['id'=>'(\d+)']);
//属性字典
think\Route::get('/attribute/dictionary$', 'goods/Attribute/dictionary',[], []);
//属性质检字典
think\Route::get('/attribute/qc_dictionary/:id$', 'goods/Attribute/qc_dictionary',[], ['id'=>'(\d+)']);
//属性code
think\Route::get('/attribute/code$', 'goods/Attribute/attributeCode',[], []);
//获取属性值根据属性Id
think\Route::get('/attribute/getAttributeValue/:id$', 'goods/Attribute/getAttributeValue',[], ['id'=>'(\d+)']);
//修改属性排序
think\Route::put('/attribute/sorts$', 'goods/Attribute/sorts',[], []);

//控制器：app\index\controller\AmazonAccount
//Amazon账号列表
think\Route::get('/amazon-account$', 'index/AmazonAccount/index',[], []);
//保存账号信息
think\Route::post('/amazon-account$', 'index/AmazonAccount/save',[], []);
//显示指定Amazon账号
think\Route::get('/amazon-account/:id$', 'index/AmazonAccount/read',[], ['id'=>'(\d+)']);
//编辑Amazon账号
think\Route::get('/amazon-account/:id/edit$', 'index/AmazonAccount/edit',[], ['id'=>'(\d+)']);
//更新Amazon账号
think\Route::put('/amazon-account/:id$', 'index/AmazonAccount/update',[], ['id'=>'(\d+)']);
//批量设置amazon账号有效状态
think\Route::put('/amazon-account/batch-set-valid$', 'index/AmazonAccount/batchUpdateIsValid',[], []);
//更新Amazon账号授权信息
think\Route::put('/amazon-account-token/:id$', 'index/AmazonAccount/saveToken',[], ['id'=>'(\d+)']);
//amazon批量设置抓取参数；
think\Route::post('/amazon-account/set$', 'index/AmazonAccount/batchSet',[], []);
//停用，启用账号
think\Route::post('/amazon-account/status$', 'index/AmazonAccount/changeStatus',[], []);
//获取Amazon站点
think\Route::get('/amazon/site$', 'index/AmazonAccount/site',[], []);
//获取亚马逊开发者授权信息
think\Route::get('/amazon-account/get-developer-account/:site$', 'index/AmazonAccount/getDeveloperAccount',[], []);
//二维数组排序
think\Route::get('/amazon/my_sort$', 'index/AmazonAccount/my_sort',[], []);

//控制器：app\publish\controller\AmazonAttribute
//属性匹配
think\Route::post('/amazon-attribute/match$', 'publish/AmazonAttribute/match',[], []);
//导入XSD文件并解析入库
think\Route::get('/amazon-attribute/import$', 'publish/AmazonAttribute/importXsd',[], []);
//更新分类元素属站点
think\Route::get('/amazon-attribute/elementSite$', 'publish/AmazonAttribute/elementSite',[], []);
//获取产品基础信息
think\Route::get('/amazon-attribute/productBase$', 'publish/AmazonAttribute/productBase',[], []);
//亚马逊属性配置展示
think\Route::get('/amazon-attribute/config$', 'publish/AmazonAttribute/getXsdAttributeConfig',[], []);
//获取XSD模板分类
think\Route::get('/amazon-attribute/xsd-category$', 'publish/AmazonAttribute/getXsdCategory',[], []);
//保存站点属性配置
think\Route::post('/amazon-save-xsd-attribute$', 'publish/AmazonAttribute/saveSelectedAttribute',[], []);
//获取XSD模板属性
think\Route::post('/amazon-xsd-attribute$', 'publish/AmazonAttribute/getSelectAttribute',[], []);
//获取XSD模板分类树
think\Route::get('/amazon-xsd-category-tree$', 'publish/AmazonAttribute/getXsdCategoryTree',[], []);

//控制器：app\publish\controller\AmazonPublish
//amazon未刊登列表
think\Route::get('/publish/amazon/unpublished$', 'publish/AmazonPublish/unpublished',[], []);
//未刊登侵权信息
think\Route::GET('/publish/amazon/goods-tort-info/:goods_id$', 'publish/AmazonPublish/goodsTortInfo',[], ['goods_id'=>'(\d+)']);
//amazon开始刊登时获取模板
think\Route::get('/publish/amazon/template$', 'publish/AmazonPublish/template',[], []);
//amazon刊登获取分类/产品模板列表
think\Route::get('/publish/amazon/templatelist$', 'publish/AmazonPublish/getTemplateList',[], []);
//amazon刊登站点列表；
think\Route::get('/publish/amazon/site$', 'publish/AmazonPublish/getAmazonSite',[], []);
//amazon刊登用站点取帐号列表；
think\Route::GET('/publish/amazon/account$', 'publish/AmazonPublish/account',[], []);
//amazon刊登详情获取刊登字段；
think\Route::GET('/publish/amazon/field$', 'publish/AmazonPublish/field',[], []);
//amazon刊登详情保存；
think\Route::POST('/publish/amazon/detail$', 'publish/AmazonPublish/detail',[], []);
//amazon刊登详情保存；
think\Route::GET('/publish/amazon/edit$', 'publish/AmazonPublish/edit',[], []);
//amazon刊登记录更改为失败；
think\Route::GET('/publish/amazon/:id/defeat$', 'publish/AmazonPublish/defeat',[], []);
//amazon刊登修复；
think\Route::GET('/publish/amazon-task/:type/:id/:status$', 'publish/AmazonPublish/task',[], []);
//amazon刊登翻译；
think\Route::POST('/publish/amazon/translate$', 'publish/AmazonPublish/translate',[], []);
//amazon获取UPC;
think\Route::get('/publish/amazon/:num/upc$', 'publish/AmazonPublish/getUpc',[], []);
//amazon编辑刊登完成后的内容;
think\Route::get('/publish/amazon/:id/:type/reedit$', 'publish/AmazonPublish/reEdit',[], []);
//amazon编辑刊登异常导出;
think\Route::get('/publish/amazon/error-export$', 'publish/AmazonPublish/errorExport',[], []);
//amazonn添加UPC参数;
think\Route::post('/publish/amazon/add-upc-params$', 'publish/AmazonPublish/addUpcParam',[], []);
//amazonn批量复制;
think\Route::post('/publish/amazon/batch-copy$', 'publish/AmazonPublish/batchCopy',[], []);
//amazon批量跟卖;
think\Route::post('/publish/amazon/batch-heel-sale$', 'publish/AmazonPublish/batchHeelSale',[], []);
//amazon跟卖列表
think\Route::get('/publish/amazon/heel-sale-list$', 'publish/AmazonPublish/heelSaleList',[], []);
//amazon定时上下架添加规则
think\Route::post('/publish/amazon/add-up-lower-rule$', 'publish/AmazonPublish/addUpLowerRule',[], []);
//amazon定时上下架规则列表
think\Route::get('/publish/amazon/up-lower-rule-list$', 'publish/AmazonPublish/upLowerRuleList',[], []);
//定时上架规则状态修改
think\Route::get('/publish/amazon/up-lower-rule-status$', 'publish/AmazonPublish/upLowerRuleStatus',[], []);
//定时上架规则删除
think\Route::post('/publish/amazon/up-lower-rule-delete$', 'publish/AmazonPublish/upLowerRuleDelete',[], []);
//定时上架规则详情
think\Route::get('/publish/amazon/up-lower-rule-detail$', 'publish/AmazonPublish/upLowerRuleDetail',[], []);
//amazon定时上架规则编辑
think\Route::post('/publish/amazon/up-lower-rule-edit$', 'publish/AmazonPublish/upLowerRuleEdit',[], []);
//定时上下架开启
think\Route::post('/publish/amazon/up-lower-open$', 'publish/AmazonPublish/upLowerOpen',[], []);
//关闭定时上下架
think\Route::post('/publish/amazon/up-lower-close$', 'publish/AmazonPublish/upLowerClose',[], []);
//亚马逊跟卖投诉管理列表
think\Route::get('/publish/amazon/heel-sale-complain$', 'publish/AmazonPublish/heelSaleComplain',[], []);
//处理跟卖投诉状态
think\Route::post('/publish/amazon/complain-status$', 'publish/AmazonPublish/complainStatus',[], []);
//删除跟卖投诉
think\Route::post('/publish/amazon/complain-delete$', 'publish/AmazonPublish/complainDelete',[], []);
//抓取asin跟卖
think\Route::post('/publish/amazon/heel-sale-get$', 'publish/AmazonPublish/heelSaleGet',[], []);
//ASIN审核
think\Route::post('/publish/amazon/review-asin$', 'publish/AmazonPublish/reviewAsin',[], []);
//亚马逊批量跟卖修改信息查询
think\Route::post('/publish/amazon/heel-sale-info$', 'publish/AmazonPublish/heelSaleInfo',[], []);
//亚马逊批量跟卖修改信息提交
think\Route::post('/publish/amazon/heel-sale-batch-edit$', 'publish/AmazonPublish/heelSaleBatchEdit',[], []);
//亚马逊跟卖批量删除
think\Route::post('/publish/amazon/heel-sale-bath-del$', 'publish/AmazonPublish/heelSaleBatchDel',[], []);

//控制器：app\publish\controller\AmazonPublishListing
//获取仓库列表
think\Route::get('/publish/amazon-publish/warehouses$', 'publish/AmazonPublishListing/getWarehouses',[], []);
//获取站点列表
think\Route::get('/publish/amazon-publish/sites$', 'publish/AmazonPublishListing/getSiteList',[], []);
//获取类目绑定的普通属性
think\Route::get('/publish/amazon-publish/common-attribute$', 'publish/AmazonPublishListing/getAttributeByCategoryId',[], []);
//获取分类树
think\Route::get('/publish/amazon-publish/category$', 'publish/AmazonPublishListing/getCategoryByParentId',[], []);
//亚马逊分类搜索
think\Route::GET('/publish/amazon-publish/search-categories$', 'publish/AmazonPublishListing/getSearchCategory',[], []);
//查询产品列表
think\Route::GET('/publish/amazon-publish/get-listing$', 'publish/AmazonPublishListing/getPublishListing',[], []);
//产品列表刊登状态刷新
think\Route::GET('/publish/amazon-publish/refresh_status$', 'publish/AmazonPublishListing/getPublishStatus',[], []);
//获取一个产品的信息
think\Route::GET('/publish/amazon-publish/get-one$', 'publish/AmazonPublishListing/getByProductId',[], []);
//删除或批量删除刊登记录
think\Route::GET('/publish/amazon-publish/delete-listing$', 'publish/AmazonPublishListing/deleteByProductId',[], []);
//已更改价格
think\Route::POST('/publish/amazon-publish/adjusted-price$', 'publish/AmazonPublishListing/adjustedPrice',[], []);

//控制器：app\publish\controller\AmazonTask
//上传产品信息
think\Route::get('/publish/amazon-task/upload-product$', 'publish/AmazonTask/uploadProduct',[], []);
//上传关系
think\Route::get('/publish/amazon-task/upload-relation$', 'publish/AmazonTask/uploadProductRelation',[], []);
//上传产品价格
think\Route::get('/publish/amazon-task/upload-price$', 'publish/AmazonTask/uploadPrice',[], []);
//上传产品数量
think\Route::get('/publish/amazon-task/upload-quantity$', 'publish/AmazonTask/uploadQuantity',[], []);
//上传产品图片
think\Route::get('/publish/amazon-task/upload-images$', 'publish/AmazonTask/uploadImages',[], []);
//获取上传结果
think\Route::get('/publish/amazon-task/get-submission$', 'publish/AmazonTask/getSubmissionResult',[], []);

//控制器：app\publish\controller\AmazonPublishTask
//每日刊登列表；
think\Route::GET('/publish/amazon-task$', 'publish/AmazonPublishTask/index',[], []);
//产品标签；
think\Route::GET('/publish/amazon-task/tags$', 'publish/AmazonPublishTask/tags',[], []);

//控制器：app\system\controller\Country
//国家列表
think\Route::get('/country$', 'system/Country/index',[], []);
//分区国家
think\Route::get('/country/lists$', 'system/Country/lists',[], []);
//显示地区列表
think\Route::get('/zone$', 'system/Country/zone',[], []);

//控制器：app\goods\controller\Brand
//品牌列表
think\Route::get('/brand$', 'goods/Brand/index',[], []);
//保存品牌
think\Route::post('/brand$', 'goods/Brand/save',[], []);
//编辑品牌
think\Route::get('/brand/:id/edit$', 'goods/Brand/edit',[], ['id'=>'(\d+)']);
//更新品牌
think\Route::put('/brand/:id$', 'goods/Brand/update',[], ['id'=>'(\d+)']);
//删除品牌
think\Route::delete('/brand/:id$', 'goods/Brand/delete',[], ['id'=>'(\d+)']);
//获取品牌字段值
think\Route::get('/brand/dictionary$', 'goods/Brand/dictionary',[], []);
//产品品牌风险字典
think\Route::get('/tort/dictionary$', 'goods/Brand/tortDictionary',[], []);

//控制器：app\goods\controller\CategoryAttribute
//保存产品分类属性关联
think\Route::post('/set-attributes$', 'goods/CategoryAttribute/save',[], []);
//查看产品分类属性
think\Route::get('/set-attributes/:id$', 'goods/CategoryAttribute/read',[], ['id'=>'(\d+)']);

//控制器：app\goods\controller\CategoryQc
//保存分类质检关联
think\Route::post('/set-qc$', 'goods/CategoryQc/save',[], []);
//查看产品分类质检
think\Route::get('/set-qc/:id$', 'goods/CategoryQc/read',[], ['id'=>'(\d+)']);
//获取检具字段值
think\Route::get('/goods/check_tool$', 'goods/CategoryQc/checkTool',[], []);
//获取质检组信息
think\Route::get('/set-qc/group$', 'goods/CategoryQc/getGroups',[], []);

//控制器：app\goods\controller\ChannelCategory
//获取所有的平台
think\Route::get('/channel-categories$', 'goods/ChannelCategory/index',[], []);
//获取部分平台
think\Route::get('/channel-part$', 'goods/ChannelCategory/getPartialChannel',[], []);
//获取平台的站点
think\Route::get('/channel-categories/:id$', 'goods/ChannelCategory/read',[], ['id'=>'(\w+)']);
//获取平台下某站点所有分类
think\Route::get('/channel-categories/:channel/:site$', 'goods/ChannelCategory/siteCategory',[], ['channel'=>'(\w+)', 'site'=>'(\w+)']);
//获取分类
think\Route::get('/channel-categories/:channel/:site/:cid$', 'goods/ChannelCategory/getCategory',[], ['channel'=>'(\w+)', 'site'=>'(\w+)', 'cid'=>'(\w+)']);

//控制器：app\goods\controller\GoodsImage
//保存产品图片
think\Route::post('/goods-image$', 'goods/GoodsImage/save',[], []);
//查看产品图片
think\Route::get('/goods-image/:id$', 'goods/GoodsImage/read',[], ['id'=>'(\d+)']);
//获取相关的资源，支持 goodsid 与 sku_id
think\Route::get('/goods-image/get-thumb$', 'goods/GoodsImage/getThumb',[], []);
//保存产品图片
think\Route::post('/goods-image/self-image$', 'goods/GoodsImage/addSelfImage',[], []);
//获取刊登图片
think\Route::get('/goods-image/listing$', 'goods/GoodsImage/listing',[], []);
//获取自定义图片
think\Route::get('/goods-image/self-image$', 'goods/GoodsImage/getSelfImage',[], []);
//获取产品图片计算路径
think\Route::get('/goods-image/path$', 'goods/GoodsImage/getImagePath',[], []);

//控制器：app\goods\controller\Packing
//显示包装列表
think\Route::get('/packing$', 'goods/Packing/index',[], []);
//创建包装信息
think\Route::post('/packing$', 'goods/Packing/save',[], []);
//编辑包装
think\Route::get('/packing/:id/edit$', 'goods/Packing/edit',[], ['id'=>'(\d+)']);
//更新包装信息
think\Route::put('/packing/:id$', 'goods/Packing/update',[], ['id'=>'(\d+)']);
//删除包装
think\Route::delete('/packing/:id$', 'goods/Packing/delete',[], ['id'=>'(\d+)']);
//获取供应商信息
think\Route::get('/packing/getSupplier$', 'goods/Packing/getSupplier',[], []);
//获取币种类型
think\Route::get('/packing/getCurrency$', 'goods/Packing/getCurrency',[], []);
//获取包装字典
think\Route::get('/packing/dictionary$', 'goods/Packing/dictionary',[], []);

//控制器：app\goods\controller\Unit
//单位管理列表
think\Route::get('/unit$', 'goods/Unit/index',[], []);
//保存单位
think\Route::post('/unit$', 'goods/Unit/save',[], []);
//编辑单位
think\Route::get('/unit/:id/edit$', 'goods/Unit/edit',[], ['id'=>'(\d+)']);
//更新单位
think\Route::put('/unit/:id$', 'goods/Unit/update',[], ['id'=>'(\d+)']);
//删除单位
think\Route::delete('/unit/:id$', 'goods/Unit/delete',[], ['id'=>'(\d+)']);
//获取单位字段值
think\Route::get('/unit/dictionary$', 'goods/Unit/dictionary',[], []);

//控制器：app\goods\controller\Tag
//显示标签列表
think\Route::get('/tag$', 'goods/Tag/index',[], []);
//保存标签
think\Route::post('/tag$', 'goods/Tag/save',[], []);
//编辑标签
think\Route::get('/tag/:id/edit$', 'goods/Tag/edit',[], ['id'=>'(\d+)']);
//更新标签
think\Route::put('/tag/:id$', 'goods/Tag/update',[], ['id'=>'(\d+)']);
//删除标签
think\Route::delete('/tag/:id$', 'goods/Tag/delete',[], ['id'=>'(\d+)']);
//获取标签字段值
think\Route::get('/tag/dictionary$', 'goods/Tag/dictionary',[], []);

//控制器：app\index\controller\AliexpressAccount
//显示资源列表
think\Route::get('/aliexpress-account$', 'index/AliexpressAccount/index',[], []);
//保存新建的资源
think\Route::post('/aliexpress-account$', 'index/AliexpressAccount/save',[], []);
//显示指定的资源
think\Route::get('/aliexpress-account/:id$', 'index/AliexpressAccount/read',[], ['id'=>'(\d+)']);
//显示编辑资源表单页.
think\Route::GET('/aliexpress-account/:id/edit$', 'index/AliexpressAccount/edit',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/aliexpress-account/:id$', 'index/AliexpressAccount/update',[], ['id'=>'(\d+)']);
//停用，启用账号
think\Route::post('/aliexpress-account/states$', 'index/AliexpressAccount/changeStates',[], []);
//显示授权页面
think\Route::post('/aliexpress-account/authorization$', 'index/AliexpressAccount/authorization',[], []);
//为已授权的用户开通消息服务
think\Route::get('/aliexpress-account/user-permit$', 'index/AliexpressAccount/userPermit',[], []);
//批量为已授权的用户开通消息服务
think\Route::get('/aliexpress-account/user-permit-batch$', 'index/AliexpressAccount/userPermitBatch',[], []);
//获取已开通消息主题列表
think\Route::get('/aliexpress-account/topic$', 'index/AliexpressAccount/notificationTopicList',[], []);
//取消用户的消息服务
think\Route::post('/aliexpress-account/userCancel$', 'index/AliexpressAccount/userCancel',[], []);
//获取授权码
think\Route::post('/aliexpress-account/getAuthorCode$', 'index/AliexpressAccount/getAuthorCode',[], []);
//获取Token
think\Route::post('/aliexpress-account/getToken$', 'index/AliexpressAccount/getToken',[], []);
//批量设置
think\Route::post('/aliexpress-account/batch-update$', 'index/AliexpressAccount/batchUpdate',[], []);

//控制器：app\system\controller\Lang
//语言管理列表
think\Route::get('/system/lang$', 'system/Lang/index',[], []);
//语言管理添加
think\Route::post('/system/lang$', 'system/Lang/save',[], []);
//语言管理获取
think\Route::get('/system/lang/:id$', 'system/Lang/read',[], ['id'=>'(\d+)']);
//语言管理编辑
think\Route::GET('/system/lang/:id/edit$', 'system/Lang/edit',[], ['id'=>'(\d+)']);
//语言管理更新
think\Route::PUT('/system/lang/:id$', 'system/Lang/update',[], ['id'=>'(\d+)']);
//语言管理删除
think\Route::DELETE('/system/lang/:id$', 'system/Lang/delete',[], ['id'=>'(\d+)']);
//获取语言字典
think\Route::get('/lang/dictionary$', 'system/Lang/dictionary',[], []);

//控制器：app\warehouse\controller\ShippingMethod
//新增物流方式
think\Route::post('/shipping-method$', 'warehouse/ShippingMethod/save',[], []);
//查看物流方式
think\Route::get('/shipping-method/:id$', 'warehouse/ShippingMethod/read',[], ['id'=>'(\d+)']);
//更新物流方式
think\Route::put('/shipping-method/:id$', 'warehouse/ShippingMethod/update',[], ['id'=>'(\d+)']);
//更面单信息
think\Route::put('/shipping-method/label/:id$', 'warehouse/ShippingMethod/label',[], ['id'=>'(\d+)']);
//获取面单信息
think\Route::get('/shipping-method/label/:id$', 'warehouse/ShippingMethod/getLabel',[], ['id'=>'(\d+)']);
//获取物流方式时效详情
think\Route::get('/shipping-method/detail/:id$', 'warehouse/ShippingMethod/getShippingMethodDetail',[], ['id'=>'(\d+)']);
//获取运费折扣
think\Route::get('/shipping-method/fee/:id$', 'warehouse/ShippingMethod/getDiscountFee',[], ['id'=>'(\d+)']);
//修改运费折扣
think\Route::put('/shipping-method/update-fee/:id$', 'warehouse/ShippingMethod/updateFeeDiscount',[], ['id'=>'(\d+)']);
//保存运费详情
think\Route::post('/shipping-method/detail/:id$', 'warehouse/ShippingMethod/saveShippingMethodDetail',[], ['id'=>'(\d+)']);
//速卖通线上发货设置地址
think\Route::put('/shipping-method/ali-address/:id$', 'warehouse/ShippingMethod/aliAddress',[], ['id'=>'(\d+)']);
//速卖通线上发货批量设置地址
think\Route::put('/shipping-method/ali-address/batch$', 'warehouse/ShippingMethod/batchAliAddress',[], []);
//导入运费详情
think\Route::post('/shipping-method/import/detail$', 'warehouse/ShippingMethod/excelImportDetail',[], []);
//计算物流费用
think\Route::post('/shipping-method/:id/shippingfee$', 'warehouse/ShippingMethod/shippingfee',[], ['id'=>'(\d+)']);
//修改物流方式状态
think\Route::put('/shipping-method/:id/status$', 'warehouse/ShippingMethod/status',[], ['id'=>'(\d+)']);
//试算运费页面
think\Route::get('/shipping-method/trial/index$', 'warehouse/ShippingMethod/trialIndex',[], []);
//试算运费
think\Route::get('/shipping-method/trial$', 'warehouse/ShippingMethod/trial',[], []);
//试算运费物流方式接口
think\Route::get('/shipping-method/dictionary$', 'warehouse/ShippingMethod/dictionary',[], []);
//订单物流接口
think\Route::get('/shipping-method/list-order$', 'warehouse/ShippingMethod/listOrder',[], []);
//物流信息列表
think\Route::get('/shipping-method/info$', 'warehouse/ShippingMethod/info',[], []);
//规则接口
think\Route::get('/shipping-method/list-rule$', 'warehouse/ShippingMethod/listRule',[], []);
//仓库物流列表
think\Route::get('/shipping-method/lists$', 'warehouse/ShippingMethod/lists',[], []);
//面单序列号
think\Route::get('/shipping-method/sequence-number$', 'warehouse/ShippingMethod/sequenceNumber',[], []);
//获取物流属性
think\Route::get('/shipping-method/Property$', 'warehouse/ShippingMethod/getTransportProperties',[], []);
//物流日志
think\Route::get('/shipping-method/log$', 'warehouse/ShippingMethod/logs',[], []);
//邮寄方式报价复制
think\Route::post('/shipping-method/copy$', 'warehouse/ShippingMethod/copy',[], []);
//特殊拣货分类
think\Route::get('/shipping-method/label-norm-list$', 'warehouse/ShippingMethod/pickingLists',[], []);
//修改水印坐标
think\Route::put('/shipping-method/save-coordinate$', 'warehouse/ShippingMethod/saveCoordinate',[], []);
//保存可发货平台
think\Route::put('/shipping-method/channel/:id$', 'warehouse/ShippingMethod/channel',[], ['id'=>'(\d+)']);
//获取可发货平台
think\Route::get('/shipping-method/channel/:id$', 'warehouse/ShippingMethod/getChannel',[], ['id'=>'(\d+)']);
//导入分段
think\Route::post('/shipping-method/import/stage-fee$', 'warehouse/ShippingMethod/importStageFee',[], []);
//启用/禁用分区
think\Route::put('/shipping-method/detail/status$', 'warehouse/ShippingMethod/detailStatus',[], []);
//导入可达天数
think\Route::post('/shipping-method/import-day$', 'warehouse/ShippingMethod/importDay',[], []);
//报价对比
think\Route::post('/shipping-method/compare-price$', 'warehouse/ShippingMethod/comparePrice',[], []);

//控制器：app\warehouse\controller\WarehouseCargoShift
//显示资源列表
think\Route::get('/warehouse-cargo-shift$', 'warehouse/WarehouseCargoShift/index',[], []);
//显示pda上架/下架列表
think\Route::get('/warehouse-cargo-shift/list$', 'warehouse/WarehouseCargoShift/indexPda',[], []);
//查看
think\Route::GET('/warehouse-cargo-shift/:id$', 'warehouse/WarehouseCargoShift/read',[], ['id'=>'(\d+)']);
//审核
think\Route::put('/warehouse-cargo-shift/check$', 'warehouse/WarehouseCargoShift/check',[], []);
//批量库位转移
think\Route::post('/warehouse-cargo-shift/batch/shift$', 'warehouse/WarehouseCargoShift/batchShift',[], []);
//上架/下架查看
think\Route::get('/warehouse-cargo-shift/detail$', 'warehouse/WarehouseCargoShift/detail',[], []);
//下架
think\Route::put('/warehouse-cargo-shift/unshelves$', 'warehouse/WarehouseCargoShift/unshelves',[], []);
//上架
think\Route::put('/warehouse-cargo-shift/shelves$', 'warehouse/WarehouseCargoShift/shelves',[], []);
//强制完成上架
think\Route::get('/forced/:id$', 'warehouse/WarehouseCargoShift/forcedShelving',[], []);
//状态
think\Route::get('/warehouse-cargo-shift/status-list$', 'warehouse/WarehouseCargoShift/statusList',[], []);

//控制器：app\finance\controller\FinancePurchase
// 显示列表
think\Route::get('/finance-purchase$', 'finance/FinancePurchase/index',[], []);
// 批量标记付款
think\Route::post('/finance-purchase/batchChangeStatus$', 'finance/FinancePurchase/batchChangeStatus',[], []);
//导出采购结算
think\Route::POST('/finance-purchase/export$', 'finance/FinancePurchase/export',[], []);

//控制器：app\publish\controller\AliAuthCategory
//获取模板内容
think\Route::get('/aliexpreee-product-template-content$', 'publish/AliAuthCategory/detail',[], []);
//速卖通店铺准入行业列表
think\Route::get('/aliexpreee-category-map-list$', 'publish/AliAuthCategory/lists',[], []);
//新增刊登分类
think\Route::post('/add-publish-ali-category$', 'publish/AliAuthCategory/add',[], []);
//编辑刊登分类
think\Route::post('/edit-publish-ali-category$', 'publish/AliAuthCategory/edit',[], []);
//删除速卖通授权分类
think\Route::post('/aliexpress-auth-category-delete$', 'publish/AliAuthCategory/del',[], []);
//编辑速卖通授权分类
think\Route::get('/aliexpress-auth-category-edit$', 'publish/AliAuthCategory/getEditData',[], []);
//速卖通信息模板列表
think\Route::get('/aliexpress-product-template-list$', 'publish/AliAuthCategory/product_template_list',[], []);
//创建速卖通关联信息模板
think\Route::post('/create-relation-product-template$', 'publish/AliAuthCategory/create_relation_product_template',[], []);
//创建速卖通自定义信息模板
think\Route::post('/create-custom-product-template$', 'publish/AliAuthCategory/create_custom_product_template',[], []);
//关联信息模板预览
think\Route::post('/review$', 'publish/AliAuthCategory/review',[], []);
//删除速卖通信息模板
think\Route::post('/delete-product-template$', 'publish/AliAuthCategory/deleteProductTemplate',[], []);
//编辑速卖通信息模板
think\Route::post('/edit-product-template$', 'publish/AliAuthCategory/editProductTemplate',[], []);
//获取关联信息模板图片
think\Route::get('/get-relation-template-images$', 'publish/AliAuthCategory/getRelationTemplateData',[], []);
//获取关联信息模板和自定义信息模板
think\Route::get('/get-relation-and-custom-template$', 'publish/AliAuthCategory/getRelationAndCustomTemplate',[], []);

//控制器：app\index\controller\ImportData
//产品资料导入列表
think\Route::get('/import$', 'index/ImportData/index',[], []);
//产品导入
think\Route::get('/import/goods$', 'index/ImportData/goods',[], []);
//导入单属性SKU
think\Route::get('/import/single-sku$', 'index/ImportData/singleSku',[], []);
//导入sku属性
think\Route::get('/import/sku-attribute$', 'index/ImportData/attribute',[], []);
//导入sku属性fix
think\Route::get('/import/handle-attribute$', 'index/ImportData/handleAttribute',[], []);
//导入sku属性数据库
think\Route::get('/import/data-attribute$', 'index/ImportData/dataAttribute',[], []);
//导入赛盒数据
think\Route::get('/import/saihe-data$', 'index/ImportData/importDataSaihe',[], []);
//导入赛盒数据
think\Route::get('/import/saihe-goods$', 'index/ImportData/importGoodsSaihe',[], []);
//导入赛盒库存
think\Route::get('/import/saihe-stock$', 'index/ImportData/importStockSaihe',[], []);
//导入赛盒采购
think\Route::get('/import/saihe-purchase$', 'index/ImportData/importPurchaseSaihe',[], []);
//导入skuMap
think\Route::get('/import/sku-map$', 'index/ImportData/importSkuMap',[], []);
//产品导入模板
think\Route::get('/import/export$', 'index/ImportData/export',[], []);
//导入属性
think\Route::get('/import/attribute$', 'index/ImportData/importAttribute',[], []);
//导入属性值
think\Route::get('/import/attribute-value$', 'index/ImportData/importAttributeValue',[], []);

//控制器：app\publish\controller\EbayPublish
//eBay未刊登列表
think\Route::get('/ebay-unpublished$', 'publish/EbayPublish/unpublished',[], []);

//控制器：app\listing\controller\Ebay
//同步促销规则
think\Route::post('/rsync-ebay-promotion$', 'listing/Ebay/rsyncPromotion',[], []);
//应用公共模块
think\Route::post('/application-ebay-common-module$', 'listing/Ebay/appCommonModule',[], []);
//获取商品所有图片
think\Route::post('/update-ebay-product-sale_note$', 'listing/Ebay/updateProdcutSale',[], []);
//获取商品所有图片
think\Route::get('/get-ebay-product-images$', 'listing/Ebay/getProductImages',[], []);
//修改商品图片
think\Route::post('/update-ebay-product-images$', 'listing/Ebay/productImages',[], []);
//促销折扣设置
think\Route::post('/ebay-promotion$', 'listing/Ebay/promotion_cost',[], []);
//自动补货设置
think\Route::post('/ebayReplenishment$', 'listing/Ebay/ebayReplenishment',[], []);
//重新上架规则
think\Route::post('/ebayReshelf$', 'listing/Ebay/ebayReshelf',[], []);
//Ebay上架
think\Route::post('/onlineEbayProduct$', 'listing/Ebay/onlineEbayProduct',[], []);
//Ebay下架
think\Route::post('/offlineEbayProduct$', 'listing/Ebay/offlineEbayProduct',[], []);
//店铺分类
think\Route::post('/editEbayShopCategory$', 'listing/Ebay/editEbayShopCategory',[], []);
//商品标题
think\Route::post('/editEbayTitle$', 'listing/Ebay/editEbayTitle',[], []);
//商品一口价和可售数量
think\Route::post('/editEbayProductPriceQuantity$', 'listing/Ebay/editEbayProductPriceQuantity',[], []);
//商品拍卖价
think\Route::post('/editEbayProductAuctionPrice$', 'listing/Ebay/editEbayProductAuctionPrice',[], []);
//同步listing
think\Route::post('/rsyncEbayProduct$', 'listing/Ebay/rsync',[], []);
//更新修改了资料的listing
think\Route::post('/rsyncEditEbayProduct$', 'listing/Ebay/rsyncEditEbayProduct',[], []);

//控制器：app\publish\controller\Common
//生成sku
think\Route::post('/create-sku-code$', 'publish/Common/createSku',[], []);
//生成捆绑sku
think\Route::post('/create-bind-sku$', 'publish/Common/createBindSku',[], []);
//上传网络图片
think\Route::post('/upload-net-images$', 'publish/Common/uploadNetImage',[], []);
//上传本地图片
think\Route::post('/upload-local-images$', 'publish/Common/uploadLocalImages',[], []);
//获取本地仓库列表
think\Route::get('/local-warehouse$', 'publish/Common/getLocalWareHouse',[], []);

//控制器：app\warehouse\controller\GuanYiWarehouse
//创建国家(所有)
think\Route::get('/Guanyiwarehouse/warehouse/Guanyiwarehouse/country$', 'warehouse/GuanYiWarehouse/country',[], []);
//创建商品颜色
think\Route::get('/Guanyiwarehouse/warehouse/Guanyiwarehouse/skucolor$', 'warehouse/GuanYiWarehouse/skuColor',[], []);
//创建商品尺寸
think\Route::get('/Guanyiwarehouse/warehouse/Guanyiwarehouse/skusize$', 'warehouse/GuanYiWarehouse/SKUSize',[], []);
//创建商品分类
think\Route::get('/Guanyiwarehouse/warehouse/Guanyiwarehouse/skucategory$', 'warehouse/GuanYiWarehouse/SKUCategory',[], []);
//创建供应商
think\Route::get('/Guanyiwarehouse/warehouse/GuanYiWarehouse/supplier$', 'warehouse/GuanYiWarehouse/supplier',[], []);
//创建SKU
think\Route::get('/Guanyiwarehouse/warehouse/GuanYiWarehouse/producttest$', 'warehouse/GuanYiWarehouse/producttest',[], []);
//采购接货通知单 到货通知单
think\Route::get('/Guanyiwarehouse/warehouse/GuanYiWarehouse/purchaseAsn$', 'warehouse/GuanYiWarehouse/purchaseAsn',[], []);
//取消送货通知单
think\Route::get('/Guanyiwarehouse/warehouse/GuanYiWarehouse/cancelPurchaseAsn$', 'warehouse/GuanYiWarehouse/cancelPurchaseAsn',[], []);
//传递包裹 发货通知单
think\Route::get('/Guanyiwarehouse/warehouse/GuanYiWarehouse/createDN$', 'warehouse/GuanYiWarehouse/createDN',[], []);
//取消包裹通知
think\Route::get('/Guanyiwarehouse/warehouse/GuanYiWarehouse/cancelpackageType$', 'warehouse/GuanYiWarehouse/cancelpackageType',[], []);
//管易推送邮寄方式
think\Route::get('/Guanyiwarehouse/warehouse/GuanYiWarehouse/shippingMethod$', 'warehouse/GuanYiWarehouse/shippingMethod',[], []);
//管易推送产品
think\Route::get('/Guanyiwarehouse/warehouse/GuanYiWarehouse/code$', 'warehouse/GuanYiWarehouse/code',[], []);
//管易推送承运商
think\Route::get('/Guanyiwarehouse/warehouse/GuanYiWarehouse/carrier$', 'warehouse/GuanYiWarehouse/carrier',[], []);
//管易推送平台
think\Route::get('/Guanyiwarehouse/warehouse/GuanYiWarehouse/shop$', 'warehouse/GuanYiWarehouse/shop',[], []);
//管易平台推送包裹
think\Route::get('/Guanyiwarehouse/warehouse/GuanYiWarehouse/package$', 'warehouse/GuanYiWarehouse/package',[], []);
//采购入库(到货通知单)
think\Route::get('/Guanyiwarehouse/warehouse/GuanYiWarehouse/purchaseArrival$', 'warehouse/GuanYiWarehouse/purchaseArrival',[], []);
//自动更新数据
think\Route::get('/guanyi/update/datas$', 'warehouse/GuanYiWarehouse/auto_update',[], []);
//(麻烦请无关信息添加后不要提交svn， 谢谢)
think\Route::get('/guanyi/test$', 'warehouse/GuanYiWarehouse/test',[], []);

//控制器：app\order\controller\Synchronize
//同步发货列表
think\Route::get('/synchronizes$', 'order/Synchronize/index',[], []);
//历史信息
think\Route::get('/synchronizes/history$', 'order/Synchronize/history',[], []);
//获取状态
think\Route::get('/synchronizes/:type/status$', 'order/Synchronize/status',[], ['type'=>'(\w+)']);
//开始同步
think\Route::post('/synchronizes/start$', 'order/Synchronize/start',[], []);
//忽略
think\Route::post('/synchronizes/ignore$', 'order/Synchronize/ignore',[], []);
//剩余时间
think\Route::get('/synchronizes/surplus$', 'order/Synchronize/surplus',[], []);
//邮寄方式
think\Route::get('/synchronizes/shipping$', 'order/Synchronize/shipping',[], []);
//再次同步
think\Route::put('/synchronizes/renew$', 'order/Synchronize/renewSynchronize',[], []);

//控制器：app\publish\controller\AliexpressBrand
//获取Aliexpress分类下面所有品牌
think\Route::get('/ali-brand/brands$', 'publish/AliexpressBrand/brands',[], []);
//保存品牌设置
think\Route::post('/ali-brand/set-brands$', 'publish/AliexpressBrand/setBrands',[], []);
//获取最新品牌
think\Route::post('/ali-brand/syn-brands$', 'publish/AliexpressBrand/synBrands',[], []);
//获取产品分组
think\Route::get('/rsync-aliexpress-groups$', 'publish/AliexpressBrand/rsyncGroups',[], []);
//获取运费模板
think\Route::get('/rsync-aliexpress-transport$', 'publish/AliexpressBrand/rsyncTransport',[], []);
//获取服务模板
think\Route::get('/rsync-aliexpress-promise$', 'publish/AliexpressBrand/rsyncPromise',[], []);

//控制器：app\order\controller\Package
//包裹列表
think\Route::get('/packages$', 'order/Package/index',[], []);
//读取
think\Route::get('/packages/:id$', 'order/Package/read',[], ['id'=>'(\w+)']);
//更新包裹
think\Route::PUT('/packages/:id$', 'order/Package/update',[], ['id'=>'(\w+)']);
//获取操作信息
think\Route::get('/packages/:type/info$', 'order/Package/info',[], ['type'=>'(\w+)']);
//包裹进度条
think\Route::get('/packages/:number/speed$', 'order/Package/speed',[], ['number'=>'(\w+)']);
//包裹类型
think\Route::get('/packages/type$', 'order/Package/type',[], []);
//批量打印面单
think\Route::post('/packages/print$', 'order/Package/printLabel',[], []);
//批量打印发票
think\Route::post('/packages/print/invoice$', 'order/Package/printInvoice',[], []);
//预览面单
think\Route::get('/packages/:id/preview$', 'order/Package/preview',[], []);
//预览报关面单
think\Route::get('/packages/:id/declare-view$', 'order/Package/declareLabelView',[], []);
//获取面单信息
think\Route::post('/packages/batch/label$', 'order/Package/label',[], []);
//获取跟踪号信息
think\Route::post('/packages/batch/tracking$', 'order/Package/tracking',[], []);
//导出execl
think\Route::post('/packages/export$', 'order/Package/export',[], []);
//execl字段信息
think\Route::get('/packages/export-title$', 'order/Package/title',[], []);
//申请取消拣货单
think\Route::post('/packages/apply-cancel$', 'order/Package/applyCancel',[], []);
//复制包裹信息
think\Route::post('/packages/copy$', 'order/Package/copy',[], []);
//获取分配库存状态
think\Route::get('/packages/distribution-type/info$', 'order/Package/distribution',[], []);
//获取拣货单状态
think\Route::get('/packages/picking-type/info$', 'order/Package/picking',[], []);
//获取包装状态
think\Route::get('/packages/packing-type/info$', 'order/Package/packing',[], []);
//获取包裹是否缺货状态
think\Route::get('/packages/oos-type/info$', 'order/Package/oos',[], []);
//取消物流下单
think\Route::post('/packages/cancel-logistics$', 'order/Package/cancelLogistics',[], []);
//包裹批量拦截
think\Route::post('/packages/package-intercept$', 'order/Package/packageIntercept',[], []);
//zoodmall线上物流导出
think\Route::post('/packages/zoodmall-export$', 'order/Package/zoodMallExport',[], []);
//停止揽收包裹列表
think\Route::get('/packages/stop-collecting$', 'order/Package/stopCollectingList',[], []);
//同步物流商发货
think\Route::put('/packages/batch/logistics-delivery$', 'order/Package/logisticsDelivery',[], []);
//同步签收状态
think\Route::put('/packages/batch/logistics-receipt$', 'order/Package/logisticsReceipt',[], []);
//物流状态
think\Route::get('/packages/shipping/type$', 'order/Package/packageShippingType',[], []);
//批量更换包裹号
think\Route::put('/packages/batch/package-number$', 'order/Package/packageNumber',[], []);

//控制器：app\order\controller\Fba
//fba订单列表
think\Route::get('/fba-orders$', 'order/Fba/index',[], []);
//获取订单详情信息
think\Route::get('/fba-orders/:order_id/info$', 'order/Fba/info',[], []);
//销售额统计
think\Route::get('/fba-orders/report$', 'order/Fba/report',[], []);
//execl字段信息
think\Route::get('/fba-orders/export-title$', 'order/Fba/title',[], []);
//导出execl
think\Route::post('/fba-orders/export$', 'order/Fba/export',[], []);

//控制器：app\goods\controller\GoodsSku
//查询商品
think\Route::get('/goods-sku/query$', 'goods/GoodsSku/query',[], []);
//根据id，sku，别名取得sku信息
think\Route::get('/goods-sku/info$', 'goods/GoodsSku/getSkuInfo',[], []);
//根据sku返回详细信息
think\Route::get('/goods-sku/api/:sku/info$', 'goods/GoodsSku/apiSkuInfo',[], []);
//根据sku_id获取兄弟元素
think\Route::get('/goods-sku/:id/siblings$', 'goods/GoodsSku/getSkuSiblings',[], []);
//删除sku
think\Route::post('/goods-sku/batch/delete$', 'goods/GoodsSku/batchDelete',[], []);
//包裹重量差异列表
think\Route::get('/goods-sku/diff-weight$', 'goods/GoodsSku/diffWeight',[], []);
//包裹重量差异列表导出
think\Route::post('/goods-sku/diff-weight-export$', 'goods/GoodsSku/diffWeightExport',[], []);
//批量设置停售sku
think\Route::post('/goods-sku/batch/stopped$', 'goods/GoodsSku/stopped',[], []);
//停售sku渠道
think\Route::get('/goods-sku/stopped-channel$', 'goods/GoodsSku/stoppedChannel',[], []);

//控制器：app\warehouse\controller\Label
//标签模板列表
think\Route::get('/label$', 'warehouse/Label/index',[], []);
//读取标签模板信息
think\Route::get('/label/:id$', 'warehouse/Label/read',[], ['id'=>'(\d+)']);
//保存标签模板信息
think\Route::post('/label$', 'warehouse/Label/save',[], []);
//删除标签模板
think\Route::delete('/label/del-temp$', 'warehouse/Label/deleteTemp',[], []);
//获取所有标签模板类型
think\Route::get('/label/label-types$', 'warehouse/Label/labelType',[], []);
//根据标签模板类型获取适用字段
think\Route::get('/label/label-fields/:type$', 'warehouse/Label/getLabelFields',[], ['type'=>'(\d+)']);
//复制标签模板
think\Route::get('/label/copy/:id$', 'warehouse/Label/copy',[], ['id'=>'(\d+)']);
//获取指定类型的模板列表
think\Route::get('/label/temp-list/:type$', 'warehouse/Label/getTempByType',[], ['type'=>'(\d+)']);

//控制器：app\publish\controller\PricingRule
//定价规则列表
think\Route::get('/pricing-rules$', 'publish/PricingRule/index',[], []);
//定价规则获取
think\Route::get('/pricing-rules/:id$', 'publish/PricingRule/read',[], ['id'=>'(\d+)']);
//定价规则添加
think\Route::post('/pricing-rules$', 'publish/PricingRule/save',[], []);
//定价规则更新
think\Route::PUT('/pricing-rules/:id$', 'publish/PricingRule/update',[], ['id'=>'(\d+)']);
//定价规则删除
think\Route::DELETE('/pricing-rules/:id$', 'publish/PricingRule/delete',[], ['id'=>'(\d+)']);
//保存排序值
think\Route::post('/pricing-rules/sort$', 'publish/PricingRule/sort',[], []);
//规则复制
think\Route::post('/pricing-rules/copy$', 'publish/PricingRule/copy',[], []);
//更改规则状态
think\Route::post('/pricing-rules/:id/status/:value$', 'publish/PricingRule/status',[], []);
//获取可选条件
think\Route::get('/pricing-rules/items$', 'publish/PricingRule/item',[], []);
//获取默认设置
think\Route::get('/pricing-rules/default$', 'publish/PricingRule/defaultRule',[], []);
//匹配规则计算销售价
think\Route::post('/pricing-rules/calculate$', 'publish/PricingRule/calculate',[], []);

//控制器：app\warehouse\controller\LabelPrint
//获取产品标签打印数据
think\Route::get('/label-print$', 'warehouse/LabelPrint/index',[], []);
//批量获取产品标签打印数据
think\Route::get('/label-print/batch-sku$', 'warehouse/LabelPrint/batchSkuLabel',[], []);
//获取箱唛标签打印数据
think\Route::get('/label-print/box-label$', 'warehouse/LabelPrint/boxMarkLabel',[], []);
//批量获取箱唛标签打印数据
think\Route::get('/label-print/batch-box$', 'warehouse/LabelPrint/batchBoxMarkLabel',[], []);

//控制器：app\goods\controller\GoodsPreDev
//预开发产品列表
think\Route::get('/goods-pre-dev$', 'goods/GoodsPreDev/index',[], []);
//查看预产品开发
think\Route::get('/goods-pre-dev/:id$', 'goods/GoodsPreDev/read',[], []);
//新增预产品开发
think\Route::post('/goods-pre-dev$', 'goods/GoodsPreDev/save',[], []);
//编辑预产品开发
think\Route::get('/goods-pre-dev/:id/edit$', 'goods/GoodsPreDev/edit',[], []);
//更新预产品开发
think\Route::put('/goods-pre-dev/:id$', 'goods/GoodsPreDev/update',[], []);
//查看预产品开发日志
think\Route::get('/goods-pre-dev/log/:id$', 'goods/GoodsPreDev/getLog',[], ['id'=>'(\d+)']);
//获取预产品开发审核按钮
think\Route::get('/goods-pre-dev/audit$', 'goods/GoodsPreDev/getAuditBtn',[], []);
//审核预产品开发流程
think\Route::post('/goods-pre-dev/audit$', 'goods/GoodsPreDev/audit',[], []);
//获取预产品产品开发流程
think\Route::get('/goods-pre-dev/process$', 'goods/GoodsPreDev/getProcess',[], []);
//获取预产品开发申请人
think\Route::get('/goods-pre-dev/proposer$', 'goods/GoodsPreDev/getProposer',[], []);
//获取初始渠道列表
think\Route::get('/goods-pre-dev/channel$', 'goods/GoodsPreDev/getInitChannel',[], []);

//控制器：app\customerservice\controller\AmazonEmail
//客服邮件查询接口
think\Route::get('/amazon-emails$', 'customerservice/AmazonEmail/index',[], []);
//客户历史邮件查询接口
think\Route::get('/amazon-emails/senders/:email_address$', 'customerservice/AmazonEmail/getCustomerAllEmails',[], []);
//更新指定id的邮件
think\Route::put('/amazon-emails/:id$', 'customerservice/AmazonEmail/update',[], []);
//获取客户的历史订单
think\Route::get('/orders/buyer-amazon-orders/:buyer_id$', 'customerservice/AmazonEmail/getAmazonBuyerHistoryOrders',[], []);
//获取能够管理制定账号的客服列表
think\Route::get('/amazon-emails/account/customers$', 'customerservice/AmazonEmail/getAmazonAccountCustomerList',[], []);
//亚马逊邮件标记已读
think\Route::Post('/amazon-emails/read$', 'customerservice/AmazonEmail/markRead',[], []);
//匹配回复模板内容
think\Route::get('/amazon-emails/tpl/content$', 'customerservice/AmazonEmail/matchTemplateContent',[], []);
//获取客服对应的账号
think\Route::GET('/amazon-message/account$', 'customerservice/AmazonEmail/getAmazonAccountMessageTotal',[], []);
//获取所有站点
think\Route::GET('/amazon-emails/site$', 'customerservice/AmazonEmail/getAllSite',[], []);
//获取全部可发送邮件的账号
think\Route::GET('/amazon-emails/amazon-emailAccount$', 'customerservice/AmazonEmail/emailAccount',[], []);

//控制器：app\customerservice\controller\AmazonSentEmail
//查询Amazon发送邮件接口
think\Route::get('/amazon-emails/sent-emails$', 'customerservice/AmazonSentEmail/index',[], []);
//Amazon发送邮件
think\Route::post('/amazon-emails/sent-emails/send$', 'customerservice/AmazonSentEmail/create',[], []);
//回复Amazon邮件
think\Route::post('/amazon-emails/reply-emails$', 'customerservice/AmazonSentEmail/replyEmail',[], []);
//Amazon失败邮件重新发送
think\Route::post('/amazon-emails/sent-mails/resend/:mail_id$', 'customerservice/AmazonSentEmail/reSendMail',[], []);

//控制器：app\customerservice\controller\AmazonEmailAccount
//获取邮箱账号列表
think\Route::get('/amazon-emails/email-account$', 'customerservice/AmazonEmailAccount/index',[], []);
//查看amazon邮箱账号
think\Route::GET('/amazon-emails/email-account/:id$', 'customerservice/AmazonEmailAccount/read',[], []);
//获取能够发送邮件的amazon帐号
think\Route::get('/amazon-emails/account$', 'customerservice/AmazonEmailAccount/getEnabledEmailAccount',[], []);
//添加amazon邮箱账号
think\Route::post('/amazon-emails/email-account$', 'customerservice/AmazonEmailAccount/create',[], []);
//添加amazon邮箱账号
think\Route::put('/amazon-emails/email-account/:email_account_id$', 'customerservice/AmazonEmailAccount/update',[], []);
//删除指定amazon邮箱账号
think\Route::delete('/amazon-emails/email-account/:email_account_id$', 'customerservice/AmazonEmailAccount/delete',[], []);
//获取指定amazon邮箱的log
think\Route::get('/amazon-emails/email-account/log/:email_account_id$', 'customerservice/AmazonEmailAccount/getEmailAccountLog',[], []);
//设置amazon邮箱账号是否启用
think\Route::put('/amazon-emails/email-account/:email_account_id/enabled$', 'customerservice/AmazonEmailAccount/enableAccount',[], []);

//控制器：app\index\controller\Server
//服务器列表
think\Route::get('/servers$', 'index/Server/index',[], []);
//获取服务器信息
think\Route::GET('/servers/:id/edit$', 'index/Server/edit',[], ['id'=>'(\d+)']);
//保存服务器信息
think\Route::post('/servers$', 'index/Server/save',[], []);
//更新服务器信息
think\Route::PUT('/servers/:id$', 'index/Server/update',[], ['id'=>'(\d+)']);
//删除服务器信息
think\Route::DELETE('/servers/:id$', 'index/Server/delete',[], ['id'=>'(\d+)']);
//获取服务器ip地址
think\Route::get('/servers/ip$', 'index/Server/ip',[], []);
//用户授权
think\Route::post('/servers/authorization$', 'index/Server/authorization',[], []);
//获取用户授权信息
think\Route::get('/servers/authorization-info$', 'index/Server/authorizationInfo',[], []);
//导出服务器execl
think\Route::post('/servers/export$', 'index/Server/export',[], []);
//导出服务器成员execl
think\Route::post('/servers/export-user$', 'index/Server/exportUser',[], []);
//批量设置上报周期
think\Route::post('/servers/reporting/batch$', 'index/Server/reporting',[], []);
//获取服务器类型
think\Route::get('/servers/type$', 'index/Server/type',[], []);
//获取服务器 ip类型
think\Route::get('/servers/iptype$', 'index/Server/iptype',[], []);
//停用，启用服务器
think\Route::post('/servers/status$', 'index/Server/changeStatus',[], []);
//被引用详情
think\Route::get('/servers/:id/use-info$', 'index/Server/useInfo',[], []);
//日志
think\Route::get('/servers/:id/log$', 'index/Server/log',[], []);
//删除服务器成员
think\Route::delete('/servers/:id/user$', 'index/Server/deleteUser',[], []);
//批量添加服务器成员
think\Route::post('/servers/:id/users$', 'index/Server/addUser',[], []);
//外网类型
think\Route::get('/servers/extranet-type$', 'index/Server/extranetType',[], []);

//控制器：app\index\controller\Queue
//队列管理列表
think\Route::get('/queue$', 'index/Queue/index',[], []);
//重新获取队列数据
think\Route::post('/queue/reload$', 'index/Queue/reload',[], []);
//重新获取队列当前进度
think\Route::get('/queue/schedule$', 'index/Queue/taskSchedule',[], []);
//重新获取队列元素
think\Route::get('/queue/elements$', 'index/Queue/elements',[], []);
//清空队列元素
think\Route::post('/queue/clear$', 'index/Queue/clear',[], []);
//获取队列日志
think\Route::get('/queue/logs$', 'index/Queue/logs',[], []);
//删除队列元素（正在执行中的元素没中断）
think\Route::delete('/queue/remove-element$', 'index/Queue/removeElement',[], []);
//设置队列所在runtype
think\Route::put('/queue/change-runtype$', 'index/Queue/changeRuntype',[], []);
//获取队列runtype列表
think\Route::get('/queue/runtypes$', 'index/Queue/runtypes',[], []);
//获取状态信息
think\Route::get('/queue/status$', 'index/Queue/status',[], []);
//强制关闭队列进程
think\Route::get('/queue/force-kill$', 'index/Queue/kill',[], []);
//修改指定队列的当前运行状态
think\Route::get('/queue/change-run-status$', 'index/Queue/changeRunStatus',[], []);
//修改队列的状态
think\Route::post('/queue/status$', 'index/Queue/changeStatus',[], []);
//设置swoole的table_queue计数
think\Route::put('/queue/queue-count$', 'index/Queue/setSwooleTableQueue',[], []);
//获取waitQueue
think\Route::get('/queue/consuming$', 'index/Queue/consumingNews',[], []);
//获取手机验证码
think\Route::get('/queue/catpond-code$', 'index/Queue/catpondMessage',[], []);

//控制器：app\index\controller\Buyer
//买家列表
think\Route::get('/buyers$', 'index/Buyer/index',[], []);
//查看买家信息
think\Route::get('/buyers/:id$', 'index/Buyer/read',[], ['id'=>'(\d+)']);
//获取编辑买家信息
think\Route::GET('/buyers/:id/edit$', 'index/Buyer/edit',[], ['id'=>'(\d+)']);
//保存买家信息
think\Route::post('/buyers$', 'index/Buyer/save',[], []);
//更新买家信息
think\Route::PUT('/buyers/:id$', 'index/Buyer/update',[], ['id'=>'(\d+)']);
//删除买家信息
think\Route::DELETE('/buyers/:id$', 'index/Buyer/delete',[], ['id'=>'(\d+)']);
//批量删除
think\Route::post('/buyers/batch/delete$', 'index/Buyer/batch',[], []);
//导入买家批量修改状态
think\Route::POST('/buyers/batch-update$', 'index/Buyer/batchUpdate',[], []);
//买家批量修改导入模板下载；
think\Route::GET('/buyers/update-template$', 'index/Buyer/updateTemplate',[], []);

//控制器：app\report\controller\ProfitStatement
//台利润列表
think\Route::get('/report/financial/profit-statement$', 'report/ProfitStatement/index',[], []);
//amazon平台利润列表
think\Route::get('/report/financial/profit-statement/amazon$', 'report/ProfitStatement/amazon',[], []);
//wish平台利润列表
think\Route::get('/report/financial/profit-statement/wish$', 'report/ProfitStatement/wish',[], []);
//速卖通平台利润列表
think\Route::get('/report/financial/profit-statement/ali-express$', 'report/ProfitStatement/aliExpress',[], []);
//ebay平台利润列表
think\Route::get('/report/financial/profit-statement/ebay$', 'report/ProfitStatement/ebay',[], []);
//joom平台利润列表
think\Route::get('/report/financial/profit-statement/joom$', 'report/ProfitStatement/joom',[], []);
//lazada平台利润列表
think\Route::get('/report/financial/profit-statement/lazada$', 'report/ProfitStatement/lazada',[], []);
//shopee平台利润列表
think\Route::get('/report/financial/profit-statement/shopee$', 'report/ProfitStatement/shopee',[], []);
//paytm平台利润列表
think\Route::get('/report/financial/profit-statement/paytm$', 'report/ProfitStatement/paytm',[], []);
//pandao平台利润列表
think\Route::get('/report/financial/profit-statement/pandao$', 'report/ProfitStatement/pandao',[], []);
//walmart平台利润列表
think\Route::get('/report/financial/profit-statement/walmart$', 'report/ProfitStatement/walmart',[], []);
//jumia平台利润列表
think\Route::get('/report/financial/profit-statement/jumia$', 'report/ProfitStatement/jumia',[], []);
//vova平台利润列表
think\Route::get('/report/financial/profit-statement/vova$', 'report/ProfitStatement/vova',[], []);
//umka平台利润列表
think\Route::get('/report/financial/profit-statement/umka$', 'report/ProfitStatement/umka',[], []);
//cd平台利润列表
think\Route::get('/report/financial/profit-statement/cd$', 'report/ProfitStatement/cd',[], []);
//newegg平台利润列表
think\Route::get('/report/financial/profit-statement/newegg$', 'report/ProfitStatement/newegg',[], []);
//oberlo平台利润列表
think\Route::get('/report/financial/profit-statement/oberlo$', 'report/ProfitStatement/oberlo',[], []);
//zoodmall平台利润列表
think\Route::get('/report/financial/profit-statement/zoodmall$', 'report/ProfitStatement/zoodmall',[], []);
//yandex平台利润列表
think\Route::get('/report/financial/profit-statement/yandex$', 'report/ProfitStatement/yandex',[], []);
//订单平台利润列表导出接口
think\Route::post('/report/financial/export/profit-statement$', 'report/ProfitStatement/create',[], []);
//订单货品统计信息
think\Route::get('/report/financial/order/skus$', 'report/ProfitStatement/getOrderSkus',[], []);

//控制器：app\report\controller\ReportShipped
//获取已发货记录列表
think\Route::get('/report/shipped$', 'report/ReportShipped/index',[], []);
//导出
think\Route::post('/report/shipped/export$', 'report/ReportShipped/export',[], []);

//控制器：app\report\controller\ReportShortage
//获取缺货记录列表
think\Route::get('/report/shortage$', 'report/ReportShortage/index',[], []);
//导出
think\Route::post('/report/shortage/export$', 'report/ReportShortage/export',[], []);

//控制器：app\report\controller\ReportUnshipped
//获取未发货记录列表
think\Route::get('/report/unshipped$', 'report/ReportUnshipped/index',[], []);
//导出
think\Route::post('/report/unshipped/export$', 'report/ReportUnshipped/export',[], []);

//控制器：app\report\controller\ReportUnpacked
//获取未拆包记录列表
think\Route::get('/report/unpacked$', 'report/ReportUnpacked/index',[], []);
//导出
think\Route::post('/report/unpacked/export$', 'report/ReportUnpacked/export',[], []);

//控制器：app\report\controller\Performance
//平台利润汇总表
think\Route::get('/report/financial/performance$', 'report/Performance/index',[], []);
//ebay平台利润汇总表
think\Route::get('/report/financial/performance/ebay$', 'report/Performance/ebay',[], []);
//amazon平台利润汇总表
think\Route::get('/report/financial/performance/amazon$', 'report/Performance/amazon',[], []);
//wish平台利润汇总表
think\Route::get('/report/financial/performance/wish$', 'report/Performance/wish',[], []);
//aliExpress平台利润汇总表
think\Route::get('/report/financial/performance/ali$', 'report/Performance/aliExpress',[], []);
//fba平台利润汇总表
think\Route::get('/report/financial/performance/fba$', 'report/Performance/fba',[], []);
//销售利润汇总列表导出接口
think\Route::post('/report/financial/export/performance$', 'report/Performance/create',[], []);
//保存资源
think\Route::post('/report/financial/performance$', 'report/Performance/save',[], []);
//查看资源
think\Route::get('/report/financial/performance/:id$', 'report/Performance/read',[], ['id'=>'(\d+)']);

//控制器：app\report\controller\AmazonAccountMonitor
//列表详情
think\Route::get('/report/amazon-monitor$', 'report/AmazonAccountMonitor/index',[], []);
//导出
think\Route::post('/report/amazon-monitor/export$', 'report/AmazonAccountMonitor/export',[], []);

//控制器：app\index\controller\BuyerAddress
//买家地址列表
think\Route::get('/buyer-addresses$', 'index/BuyerAddress/index',[], []);
//保存买家地址信息
think\Route::post('/buyer-addresses$', 'index/BuyerAddress/save',[], []);
//更新买家地址信息
think\Route::PUT('/buyer-addresses/:id$', 'index/BuyerAddress/update',[], ['id'=>'(\d+)']);
//删除买家地址
think\Route::DELETE('/buyer-addresses/:id$', 'index/BuyerAddress/delete',[], ['id'=>'(\d+)']);
//设置默认地址
think\Route::post('/buyer-addresses/default$', 'index/BuyerAddress/defaultAddress',[], []);

//控制器：app\order\controller\BrushOrder
//刷单列表
think\Route::get('/brush-orders$', 'order/BrushOrder/index',[], []);
//同步发货设置
think\Route::post('/brush-orders/:order_id/synchronize$', 'order/BrushOrder/synchronize',[], []);
//开始同步
think\Route::post('/brush-orders/start$', 'order/BrushOrder/start',[], []);
//导出execl
think\Route::post('/brush-orders/export$', 'order/BrushOrder/export',[], []);
//execl字段信息
think\Route::get('/brush-orders/export-title$', 'order/BrushOrder/title',[], []);

//控制器：app\report\controller\ExportFileList
//获取用户的导出文件申请列表
think\Route::get('/report/export-files$', 'report/ExportFileList/index',[], []);
//删除报表
think\Route::delete('/report/export-files/deletes/:id$', 'report/ExportFileList/deletes',[], []);

//控制器：app\index\controller\ServerLog
//服务器访问日志列表
think\Route::get('/server-logs$', 'index/ServerLog/index',[], []);

//控制器：app\carrier\controller\AliexpressAddress
//获取速卖通地址信息
think\Route::get('/ali-address$', 'carrier/AliexpressAddress/index',[], []);

//控制器：app\index\controller\BasicAccount
//显示资源列表
think\Route::get('/account-basics$', 'index/BasicAccount/index',[], []);
//保存新建的资源
think\Route::post('/account-basics$', 'index/BasicAccount/save',[], []);
//显示指定的资源
think\Route::get('/account-basics/:id$', 'index/BasicAccount/read',[], ['id'=>'(\d+)']);
//显示编辑资源表单页.
think\Route::GET('/account-basics/:id/edit$', 'index/BasicAccount/edit',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/account-basics/:id$', 'index/BasicAccount/update',[], ['id'=>'(\d+)']);
//获取状态列表信息
think\Route::get('/account-basics/status/:info$', 'index/BasicAccount/info',[], []);
//更改账号状态
think\Route::post('/account-basics/batch/:type$', 'index/BasicAccount/batch',[], []);
//显示密码
think\Route::get('/account-basics/password$', 'index/BasicAccount/show',[], []);
//服务器已绑定的账号列表
think\Route::get('/account-basics/already-bind$', 'index/BasicAccount/alreadyBind',[], []);
//自动识别图片
think\Route::get('/account-basics/automatic$', 'index/BasicAccount/automatic',[], []);
//资料日志
think\Route::get('/account-basics/:account_id/log$', 'index/BasicAccount/log',[], []);
//读取运营负责人
think\Route::get('/account-basics/user$', 'index/BasicAccount/getUser',[], []);
//获取状态列表信息
think\Route::get('/account-basics/changes$', 'index/BasicAccount/changes',[], []);
//资料旧手机日志
think\Route::get('/account-basics/:account_id/phone-log$', 'index/BasicAccount/phoneLog',[], []);

//控制器：app\index\controller\AccountUser
//显示资源列表
think\Route::get('/account-users$', 'index/AccountUser/index',[], []);
//保存新建的资源
think\Route::post('/account-users$', 'index/AccountUser/save',[], []);
//批量添加、删除账号成员
think\Route::post('/account-users/batch$', 'index/AccountUser/batch',[], []);

//控制器：app\test\controller\ChangeCarrier
//获取数据
think\Route::get('/change-carrier/get-data$', 'test/ChangeCarrier/getData',[], []);
//设置数据
think\Route::get('/change-carrier/set-data:$', 'test/ChangeCarrier/setData',[], []);
//发送数据请求
think\Route::get('/change-carrier/sender-data$', 'test/ChangeCarrier/senderData',[], []);

//控制器：app\listing\controller\Item
//更新线上sku与本地sku关系
think\Route::post('/update-sku-relation$', 'listing/Item/updateRelation',[], []);

//控制器：app\order\controller\OrderHold
//列表信息
think\Route::get('/order-hold$', 'order/OrderHold/index',[], []);
//获取详情
think\Route::get('/order-hold/:id$', 'order/OrderHold/read',[], ['id'=>'(\d+)']);
//获取编辑详情
think\Route::GET('/order-hold/:id/edit$', 'order/OrderHold/edit',[], ['id'=>'(\d+)']);
//更新
think\Route::PUT('/order-hold/:id$', 'order/OrderHold/update',[], ['id'=>'(\d+)']);
//新增拦截
think\Route::post('/order-hold$', 'order/OrderHold/save',[], []);
//批量操作
think\Route::post('/order-hold/batch$', 'order/OrderHold/batch',[], []);
//原因信息
think\Route::get('/order-hold/reason$', 'order/OrderHold/reason',[], []);
//拦截状态
think\Route::get('/order-hold/status$', 'order/OrderHold/status',[], []);
//导出execl
think\Route::post('/order-hold/export$', 'order/OrderHold/export',[], []);

//控制器：app\order\controller\VirtualOrderHold
//获取列表信息
think\Route::get('/virtual-hold$', 'order/VirtualOrderHold/index',[], []);
//获取详情信息
think\Route::get('/virtual-hold/:id$', 'order/VirtualOrderHold/read',[], ['id'=>'(\d+)']);
//获取编辑信息
think\Route::GET('/virtual-hold/:id/edit$', 'order/VirtualOrderHold/edit',[], ['id'=>'(\d+)']);
//新增记录
think\Route::post('/virtual-hold$', 'order/VirtualOrderHold/save',[], []);
//修改记录
think\Route::PUT('/virtual-hold/:id$', 'order/VirtualOrderHold/update',[], ['id'=>'(\d+)']);
//删除记录
think\Route::DELETE('/virtual-hold/:id$', 'order/VirtualOrderHold/delete',[], ['id'=>'(\d+)']);
//批量删除
think\Route::post('/virtual-hold/batch/delete$', 'order/VirtualOrderHold/batch',[], []);
//虚拟订单批量导入
think\Route::POST('/virtual-hold/batch-import$', 'order/VirtualOrderHold/batchImport',[], []);
//虚拟订单导入模板下载；
think\Route::GET('/virtual-hold/import-template$', 'order/VirtualOrderHold/importTemplate',[], []);

//控制器：app\order\controller\VirtualOrder
//虚拟订单申请
think\Route::post('/virtual-order$', 'order/VirtualOrder/save',[], []);
//虚拟订单列表
think\Route::get('/virtual-order$', 'order/VirtualOrder/index',[], []);
//查看订单信息
think\Route::get('/virtual-order/:id$', 'order/VirtualOrder/read',[], ['id'=>'(\d+)']);
//批量分配
think\Route::post('/virtual-order/batch/allot$', 'order/VirtualOrder/batchAllot',[], []);
//返回虚拟订单列表的状态信息
think\Route::get('/virtual-order/status$', 'order/VirtualOrder/status',[], []);
//组长审批
think\Route::post('/virtual-order/audit/headman$', 'order/VirtualOrder/auditHeadman',[], []);
//批量作废
think\Route::post('/virtual-order/batch/cancel$', 'order/VirtualOrder/cancel',[], []);
//查看订单信息
think\Route::get('/virtual-order/:id/logs$', 'order/VirtualOrder/logs',[], []);
//导入sku信息
think\Route::post('/virtual-order/import$', 'order/VirtualOrder/import',[], []);
//刷单类型
think\Route::get('/virtual-order/mission-type$', 'order/VirtualOrder/missionType',[], []);
//任务列表的状态信息
think\Route::get('/virtual-order/mission/status$', 'order/VirtualOrder/missionStatus',[], []);
//货币类型
think\Route::get('/virtual-order/currency$', 'order/VirtualOrder/currency',[], []);
//刷单任务列表
think\Route::get('/virtual-order/mission-list$', 'order/VirtualOrder/missionList',[], []);
//任务详情
think\Route::get('/virtual-order/mission/:id$', 'order/VirtualOrder/missionRead',[], []);
//负责人列表
think\Route::get('/virtual-order/principal-list$', 'order/VirtualOrder/principalList',[], []);
//指定负责人
think\Route::post('/virtual-order/mission/allocation$', 'order/VirtualOrder/missionAllocation',[], []);
//买家列表
think\Route::get('/virtual-order/buyer-list$', 'order/VirtualOrder/buyerList',[], []);
//指定买家
think\Route::post('/virtual-order/mission/buyer$', 'order/VirtualOrder/missionAllocationBuyer',[], []);
//查看任务日志
think\Route::get('/virtual-order/mission/:id/logs$', 'order/VirtualOrder/missionLogs',[], []);
//自动指定买家
think\Route::post('/virtual-order/mission/buyer-automation$', 'order/VirtualOrder/missionAllocationBuyerAutomation',[], []);
//国外用户列表
think\Route::get('/virtual-order/user-list$', 'order/VirtualOrder/userList',[], []);
//国外用户-添加
think\Route::post('/virtual-order/user-add$', 'order/VirtualOrder/userAdd',[], []);
//国外用户-详细信息
think\Route::get('/virtual-order/user-info$', 'order/VirtualOrder/userInfo',[], []);
//国外用户-保存
think\Route::post('/virtual-order/user-editor$', 'order/VirtualOrder/userUpdate',[], []);
//国外用户-更改状态
think\Route::post('/virtual-order/user-status$', 'order/VirtualOrder/userStatus',[], []);
//更新用户密码
think\Route::post('/virtual-order/user-save$', 'order/VirtualOrder/userSave',[], []);
//刷单任务处理
think\Route::post('/virtual-order/dispose$', 'order/VirtualOrder/dispose',[], []);
//导入平台订单号
think\Route::post('/virtual-order/channel-import$', 'order/VirtualOrder/channelImport',[], []);
//上传图片
think\Route::post('/virtual-order/img-import$', 'order/VirtualOrder/imgImport',[], []);
//获取国内刷单列表
think\Route::get('/virtual-order/inland-task/list$', 'order/VirtualOrder/inlandTaskList',[], []);
//获取国家信息
think\Route::get('/virtual-order/country$', 'order/VirtualOrder/country',[], []);

//控制器：app\report\controller\OrderDetail
//列表详情
think\Route::get('/report/order-details$', 'report/OrderDetail/index',[], []);
//导出
think\Route::post('/report/order-details/export$', 'report/OrderDetail/export',[], []);

//控制器：app\report\controller\GoodsAnalysis
//列表详情
think\Route::get('/report/goods-analysis$', 'report/GoodsAnalysis/index',[], []);
//导出
think\Route::post('/report/goods-analysis/export$', 'report/GoodsAnalysis/export',[], []);
//同步销量
think\Route::post('/report/goods-analysis/synchronous$', 'report/GoodsAnalysis/synchronous',[], []);

//控制器：app\report\controller\SaleRefund
//列表详情
think\Route::get('/report/sale-refund$', 'report/SaleRefund/index',[], []);
//导出
think\Route::post('/report/sale-refund/export$', 'report/SaleRefund/export',[], []);

//控制器：app\report\controller\SaleStock
//列表详情
think\Route::get('/report/sale-stock$', 'report/SaleStock/index',[], []);
//导出
think\Route::post('/report/sale-stock/export$', 'report/SaleStock/applyExport',[], []);

//控制器：app\report\controller\Settlement
//统计财务结算表
think\Route::get('/settlement/index_settle$', 'report/Settlement/indexSettle',[], []);
//aliexpress放款帐期详情导出
think\Route::post('/settlement/export$', 'report/Settlement/export',[], []);
//统计财务结算表详情
think\Route::get('/settlement/settle_detail$', 'report/Settlement/settleDetail',[], []);

//控制器：app\warehouse\controller\WarehouseArea
//显示分区列表
think\Route::get('/warehouse-area$', 'warehouse/WarehouseArea/index',[], []);
//保存新建的分区
think\Route::post('/warehouse-area$', 'warehouse/WarehouseArea/save',[], []);
//分区详情
think\Route::get('/warehouse-area/:id$', 'warehouse/WarehouseArea/read',[], ['id'=>'(\d+)']);
//显示指定的资源
think\Route::GET('/warehouse-area/:id/edit$', 'warehouse/WarehouseArea/edit',[], ['id'=>'(\d+)']);
//保存更新的分区
think\Route::PUT('/warehouse-area/:id$', 'warehouse/WarehouseArea/update',[], ['id'=>'(\d+)']);
//删除指定分区
think\Route::DELETE('/warehouse-area/:id$', 'warehouse/WarehouseArea/delete',[], ['id'=>'(\d+)']);
//分区列表
think\Route::get('/warehouse-area/lists$', 'warehouse/WarehouseArea/lists',[], []);
//状态更新
think\Route::put('/warehouse-area/:id/status$', 'warehouse/WarehouseArea/changeStatus',[], ['id'=>'(\d+)']);
//分区功能列表
think\Route::get('/warehouse-area/types$', 'warehouse/WarehouseArea/appTypes',[], []);
//获取多品分拣人
think\Route::get('/warehouse-area/:warehouse_id/picker$', 'warehouse/WarehouseArea/pickerInfo',[], []);
//设置多品分拣人
think\Route::put('/warehouse-area/:warehouse_id/picker$', 'warehouse/WarehouseArea/picker',[], []);
//测试接口
think\Route::get('/warehouse-area/test$', 'warehouse/WarehouseArea/test',[], []);

//控制器：app\warehouse\controller\WarehouseCargoClass
//显示资源列表
think\Route::get('/warehouse-cargo-class$', 'warehouse/WarehouseCargoClass/index',[], []);
//保存新建的资源
think\Route::post('/warehouse-cargo-class$', 'warehouse/WarehouseCargoClass/save',[], []);
//显示指定的资源
think\Route::get('/warehouse-cargo-class/:id$', 'warehouse/WarehouseCargoClass/read',[], ['id'=>'(\d+)']);
//显示指定的资源
think\Route::GET('/warehouse-cargo-class/:id/edit$', 'warehouse/WarehouseCargoClass/edit',[], ['id'=>'(\d+)']);
// 保存更新的资源
think\Route::PUT('/warehouse-cargo-class/:id$', 'warehouse/WarehouseCargoClass/update',[], ['id'=>'(\d+)']);
//删除指定资源
think\Route::DELETE('/warehouse-cargo-class/:id$', 'warehouse/WarehouseCargoClass/delete',[], ['id'=>'(\d+)']);
//状态更新
think\Route::put('/warehouse-cargo-class/:id/status$', 'warehouse/WarehouseCargoClass/changeStatus',[], ['id'=>'(\d+)']);
//货位类型列表
think\Route::get('/warehouse-cargo-class/lists$', 'warehouse/WarehouseCargoClass/lists',[], []);

//控制器：app\warehouse\controller\WarehouseCargo
//显示资源列表
think\Route::get('/warehouse-cargo$', 'warehouse/WarehouseCargo/index',[], []);
//导出
think\Route::post('/warehouse-cargo/export$', 'warehouse/WarehouseCargo/export',[], []);
//保存新建的资源
think\Route::post('/warehouse-cargo$', 'warehouse/WarehouseCargo/save',[], []);
//显示指定的资源
think\Route::get('/warehouse-cargo/:id$', 'warehouse/WarehouseCargo/read',[], ['id'=>'(\d+)']);
//显示指定的资源
think\Route::GET('/warehouse-cargo/:id/edit$', 'warehouse/WarehouseCargo/edit',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/warehouse-cargo/:id$', 'warehouse/WarehouseCargo/update',[], ['id'=>'(\d+)']);
//删除指定资源
think\Route::DELETE('/warehouse-cargo/:id$', 'warehouse/WarehouseCargo/delete',[], ['id'=>'(\d+)']);
//批量删除
think\Route::delete('/warehouse-cargo$', 'warehouse/WarehouseCargo/batchDelete',[], []);
//货位导入
think\Route::post('/warehouse-cargo/import$', 'warehouse/WarehouseCargo/import',[], []);
//状态更新
think\Route::put('/warehouse-cargo/:id/status$', 'warehouse/WarehouseCargo/changeStatus',[], ['id'=>'(\d+)']);
//批量状态更新
think\Route::put('/warehouse-cargo/status$', 'warehouse/WarehouseCargo/batchChangeStatus',[], []);
//仓库货位列表(移库)
think\Route::get('/warehouse-cargo/lists$', 'warehouse/WarehouseCargo/lists',[], []);
//标签打印
think\Route::get('/warehouse-cargo/print$', 'warehouse/WarehouseCargo/labelPrint',[], []);
//仓库货位列表(绑定)
think\Route::get('/warehouse-cargo/recommend$', 'warehouse/WarehouseCargo/recommend',[], []);

//控制器：app\warehouse\controller\WarehouseShelf
//显示资源列表
think\Route::get('/warehouse-shelf$', 'warehouse/WarehouseShelf/index',[], []);
//保存新建的资源
think\Route::post('/warehouse-shelf$', 'warehouse/WarehouseShelf/save',[], []);
//显示指定的资源
think\Route::get('/warehouse-shelf/:id$', 'warehouse/WarehouseShelf/read',[], ['id'=>'(\d+)']);
//显示指定的资源
think\Route::GET('/warehouse-shelf/:id/edit$', 'warehouse/WarehouseShelf/edit',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/warehouse-shelf/:id$', 'warehouse/WarehouseShelf/update',[], ['id'=>'(\d+)']);
// 删除指定资源
think\Route::DELETE('/warehouse-shelf/:id$', 'warehouse/WarehouseShelf/delete',[], ['id'=>'(\d+)']);
//货架列表
think\Route::get('/warehouse-shelf/lists$', 'warehouse/WarehouseShelf/lists',[], []);
//状态更新
think\Route::put('/warehouse-shelf/:id/status$', 'warehouse/WarehouseShelf/changeStatus',[], ['id'=>'(\d+)']);
//获取对面通道
think\Route::get('/warehouse-shelf/face_aisle$', 'warehouse/WarehouseShelf/faceAisle',[], []);

//控制器：app\warehouse\controller\WarehouseCargoGoods
//显示资源列表
think\Route::get('/warehouse-cargo-goods$', 'warehouse/WarehouseCargoGoods/index',[], []);
//导出货位库存
think\Route::post('/warehouse-cargo-goods/export$', 'warehouse/WarehouseCargoGoods/export',[], []);
//日志操作类型
think\Route::get('/warehouse-cargo-goods/log-types$', 'warehouse/WarehouseCargoGoods/getLogTypes',[], []);
//操作明细
think\Route::get('/warehouse-cargo-goods/logs$', 'warehouse/WarehouseCargoGoods/logs',[], []);
//商品移库
think\Route::post('/warehouse-cargo-goods/shift$', 'warehouse/WarehouseCargoGoods/shift',[], []);
//货位库存解绑
think\Route::delete('/warehouse-cargo-goods/:id$', 'warehouse/WarehouseCargoGoods/delete',[], ['id'=>'(\d+)']);
//手动绑定货位
think\Route::post('/warehouse-cargo-goods/bind$', 'warehouse/WarehouseCargoGoods/bind',[], []);
//自动绑定货位
think\Route::post('/warehouse-cargo-goods/auto-bind$', 'warehouse/WarehouseCargoGoods/auto_bind',[], []);
//冻结库存调整
think\Route::post('/warehouse-cargo-goods/modify-hold$', 'warehouse/WarehouseCargoGoods/modifyHoldQuantity',[], []);
//批量库位转移
think\Route::post('/warehouse-cargo-goods/batch/shift$', 'warehouse/WarehouseCargoGoods/batchShift',[], []);

//控制器：app\index\controller\LocalBuyerAccount
//本地买手列表
think\Route::get('/local-buyers$', 'index/LocalBuyerAccount/index',[], []);
//获取服务器信息
think\Route::GET('/local-buyers/:id/edit$', 'index/LocalBuyerAccount/edit',[], ['id'=>'(\d+)']);
//保存服务器信息
think\Route::post('/local-buyers$', 'index/LocalBuyerAccount/save',[], []);
//更新服务器信息
think\Route::PUT('/local-buyers/:id$', 'index/LocalBuyerAccount/update',[], ['id'=>'(\d+)']);
//删除服务器信息
think\Route::DELETE('/local-buyers/:id$', 'index/LocalBuyerAccount/delete',[], ['id'=>'(\d+)']);
//批量删除
think\Route::post('/local-buyers/batch$', 'index/LocalBuyerAccount/batch',[], []);
//显示密码
think\Route::get('/local-buyers/password$', 'index/LocalBuyerAccount/show',[], []);

//控制器：app\publish\controller\AmazonProductExport
//导出产品列表
think\Route::get('/publish/amazon-product-export$', 'publish/AmazonProductExport/index',[], []);
//查看指定产品信息
think\Route::get('/publish/amazon-product-export/:goods_id$', 'publish/AmazonProductExport/view',[], ['goods_id'=>'(\d+)']);
//修改指定产品的信息
think\Route::PUT('/publish/amazon-product-export/:goods_id$', 'publish/AmazonProductExport/update',[], ['goods_id'=>'(\d+)']);
//删除指定产品
think\Route::delete('/publish/amazon-product-export/:goods_id$', 'publish/AmazonProductExport/delete',[], ['goods_id'=>'(\d+)']);
//获取系统中的商品信息
think\Route::get('/publish/amazon-product-export/goods/:goods_id$', 'publish/AmazonProductExport/goods',[], ['goods_id'=>'(\d+)']);
//添加系统的产品到导出列表
think\Route::POST('/publish/amazon-product-export$', 'publish/AmazonProductExport/add',[], []);
//下载需要导出的产品
think\Route::GET('/publish/amazon-product-export/download$', 'publish/AmazonProductExport/download',[], []);

//控制器：app\publish\controller\AmazonListing
//listing 列表
think\Route::get('/publish/amazon-listing$', 'publish/AmazonListing/index',[], []);
//listing导出
think\Route::get('/publish/amazon-listing/export$', 'publish/AmazonListing/export',[], []);
//查看指定产品信息
think\Route::get('/publish/amazon-listing/detail/:listing_id$', 'publish/AmazonListing/detail',[], ['listing_id'=>'(\d+)']);
//查看指定产品信息
think\Route::get('/publish/amazon-listing/relation$', 'publish/AmazonListing/relation',[], []);
//查找asin
think\Route::post('/publish/amazon-listing/asins$', 'publish/AmazonListing/asins',[], []);
//批量删除listing
think\Route::delete('/publish/amazon-listing/batch$', 'publish/AmazonListing/batchDel',[], []);

//控制器：app\listing\controller\Test
//刊登提交测试
think\Route::get('/wish-test$', 'listing/Test/index',[], []);

//控制器：app\warehouse\controller\MakePicking
//列表
think\Route::get('/make-pickings$', 'warehouse/MakePicking/index',[], []);
//生成拣货单
think\Route::post('/make-pickings$', 'warehouse/MakePicking/save',[], []);
//剩余时间
think\Route::get('/make-pickings/surplus$', 'warehouse/MakePicking/surplus',[], []);
//渠道信息
think\Route::get('/make-pickings/channels$', 'warehouse/MakePicking/channel',[], []);
//运算符
think\Route::get('/make-pickings/operator$', 'warehouse/MakePicking/operator',[], []);
//邮寄方式
think\Route::get('/make-pickings/shipping$', 'warehouse/MakePicking/shipping',[], []);
//批量生成拣货单
think\Route::post('/make-pickings/batch$', 'warehouse/MakePicking/batch',[], []);
//重返上架生成拣货单
think\Route::post('/make-pickings/make$', 'warehouse/MakePicking/make',[], []);
//生成快速出货区拣货单
think\Route::post('/make-pickings/make-quick$', 'warehouse/MakePicking/makeQuick',[], []);

//控制器：app\warehouse\controller\SortingShelf
//显示播种车 列表
think\Route::get('/sorting-shelf$', 'warehouse/SortingShelf/index',[], []);
//保存新建播种车
think\Route::post('/sorting-shelf$', 'warehouse/SortingShelf/save',[], []);
//显示指定的资源
think\Route::get('/sorting-shelf/:id$', 'warehouse/SortingShelf/read',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/sorting-shelf/:id$', 'warehouse/SortingShelf/update',[], ['id'=>'(\d+)']);
//删除指定资源
think\Route::DELETE('/sorting-shelf/:id$', 'warehouse/SortingShelf/delete',[], ['id'=>'(\d+)']);
//状态更新
think\Route::put('/sorting-shelf/:id/status/$', 'warehouse/SortingShelf/changeStatus',[], ['id'=>'(\d+)']);
//播种车列表
think\Route::get('/sorting-shelf/lists$', 'warehouse/SortingShelf/lists',[], []);

//控制器：app\warehouse\controller\Picking
//拣货单列表
think\Route::get('/pickings$', 'warehouse/Picking/index',[], []);
//查看
think\Route::get('/pickings/:id$', 'warehouse/Picking/read',[], ['id'=>'(\d+)']);
//拣货单状态
think\Route::get('/pickings/status$', 'warehouse/Picking/status',[], []);
//拣货单包裹状态
think\Route::get('/pickings/package/status$', 'warehouse/Picking/packageStatus',[], []);
//获取子拣货单信息
think\Route::get('/pickings/:id/sub$', 'warehouse/Picking/sub',[], []);
//拣货单类型
think\Route::get('/pickings/type$', 'warehouse/Picking/type',[], []);
//拣货单详情
think\Route::get('/pickings/:id/detail$', 'warehouse/Picking/detail',[], []);
//打印拣货单
think\Route::get('/pickings/:id/print$', 'warehouse/Picking/printOrder',[], []);
//打印面单地址
think\Route::get('/pickings/:id/label$', 'warehouse/Picking/label',[], []);
//打印带有面单详情的面单
think\Route::get('/pickings/:id/detail-label$', 'warehouse/Picking/detailLabel',[], []);
//打印发票
think\Route::get('/pickings/:id/invoice$', 'warehouse/Picking/invoice',[], []);
//获取运输方式
think\Route::get('/pickings/shipping$', 'warehouse/Picking/shipping',[], []);
//作废
think\Route::post('/pickings/:id/invalid$', 'warehouse/Picking/invalid',[], []);
//查看包裹信息
think\Route::get('/pickings/:id/packages$', 'warehouse/Picking/package',[], []);
//查看拣货单周转箱信息
think\Route::get('/pickings/:id/turnover$', 'warehouse/Picking/turnover',[], []);
//下架完成拣货
think\Route::post('/pickings/:id/complete$', 'warehouse/Picking/complete',[], []);
//标记为正在拣货
think\Route::post('/pickings/:id/picking-process$', 'warehouse/Picking/pickingProcess',[], []);
//标记为等待分拣
think\Route::post('/pickings/:id/wait-sorting$', 'warehouse/Picking/waitSorting',[], []);
//标记为集结完成
think\Route::post('/pickings/:id/picking-massed$', 'warehouse/Picking/pickingMassed',[], []);
//正在分拣作业
think\Route::get('/pickings/sorting$', 'warehouse/Picking/sorting',[], []);
//正在包装作业
think\Route::get('/pickings/packing$', 'warehouse/Picking/packing',[], []);
//更换拣货人
think\Route::post('/pickings/:id/shipper$', 'warehouse/Picking/shipper',[], []);
//标记为包装完成
think\Route::post('/pickings/:id/sign-packing-complete$', 'warehouse/Picking/packingComplete',[], []);
//快速发货区-拣货单商品列表
think\Route::get('/pickings/quick-picking-detail$', 'warehouse/Picking/quickPickingDetail',[], []);
//拣货单包裹列表
think\Route::get('/pickings/quick-picking-package$', 'warehouse/Picking/quickPickingPackage',[], []);
//快速发货区移除包裹
think\Route::post('/pickings/quick-picking-remove$', 'warehouse/Picking/quickPickingRemove',[], []);
//拣货单操作日志
think\Route::get('/pickings/:id/log$', 'warehouse/Picking/log',[], []);
//获取周转箱商品信息
think\Route::get('/pickings/:id/turnover/detail$', 'warehouse/Picking/turnoverDetail',[], []);
//转移周转箱功能
think\Route::post('/pickings/:id/turnover/transfer$', 'warehouse/Picking/turnoverTransfer',[], []);

//控制器：app\warehouse\controller\TurnoverBox
//显示周转箱列表
think\Route::get('/turnover-box$', 'warehouse/TurnoverBox/index',[], []);
//保存新建的周转箱
think\Route::post('/turnover-box$', 'warehouse/TurnoverBox/save',[], []);
//显示指定的资源
think\Route::get('/turnover-box/:id$', 'warehouse/TurnoverBox/read',[], ['id'=>'(\d+)']);
//显示指定的资源
think\Route::GET('/turnover-box/:id/edit$', 'warehouse/TurnoverBox/edit',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/turnover-box/:id$', 'warehouse/TurnoverBox/update',[], ['id'=>'(\d+)']);
//删除指定资源
think\Route::DELETE('/turnover-box/:id$', 'warehouse/TurnoverBox/delete',[], ['id'=>'(\d+)']);
//周转箱作废
think\Route::put('/turnover-box/:id/invalid$', 'warehouse/TurnoverBox/invalid',[], ['id'=>'(\d+)']);
//获取操作日志
think\Route::get('/turnover-box/:id/logs$', 'warehouse/TurnoverBox/logs',[], ['id'=>'(\d+)']);
//周转箱集结
think\Route::put('/turnover-box/mass$', 'warehouse/TurnoverBox/mass',[], []);
//标签打印
think\Route::get('/turnover-box/print$', 'warehouse/TurnoverBox/labelPrint',[], []);
//批量释放周装箱
think\Route::post('/turnover-box/batch-remove$', 'warehouse/TurnoverBox/batchRemove',[], []);

//控制器：app\warehouse\controller\MassZone
//显示集结区列表
think\Route::get('/mass-zone$', 'warehouse/MassZone/index',[], []);
//保存新建的集结区
think\Route::post('/mass-zone$', 'warehouse/MassZone/save',[], []);
//显示指定的资源
think\Route::get('/mass-zone/:id$', 'warehouse/MassZone/read',[], ['id'=>'(\d+)']);
//显示指定的资源
think\Route::GET('/mass-zone/:id/edit$', 'warehouse/MassZone/edit',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/mass-zone/:id$', 'warehouse/MassZone/update',[], ['id'=>'(\d+)']);
//删除指定资源
think\Route::DELETE('/mass-zone/:id$', 'warehouse/MassZone/delete',[], ['id'=>'(\d+)']);
//状态更新
think\Route::put('/mass-zone/:id/status/$', 'warehouse/MassZone/changeStatus',[], ['id'=>'(\d+)']);
//集结区管理
think\Route::get('/mass-zone/lists$', 'warehouse/MassZone/lists',[], []);

//控制器：app\warehouse\controller\DeliveryCheck
//扫描周转箱号或拣货单号
think\Route::post('/delivery-check/single-box$', 'warehouse/DeliveryCheck/checkSingleBox',[], []);
//扫描sku
think\Route::post('/delivery-check/single-sku$', 'warehouse/DeliveryCheck/checkSingleSku',[], []);
//单品多件确认
think\Route::post('/delivery-check/sure-sku$', 'warehouse/DeliveryCheck/sureSku',[], []);
//扫描面单号(多品复核)
think\Route::post('/delivery-check/check-shipping-number$', 'warehouse/DeliveryCheck/checkShippingNumber',[], []);
//复核打印
think\Route::post('/delivery-check/print-shipping-number$', 'warehouse/DeliveryCheck/printShippingNumber',[], []);
//扫描周转箱号 二次分拣
think\Route::post('/delivery-check/check-turnover-box$', 'warehouse/DeliveryCheck/checkTurnoverBox',[], []);
//多品分拣确认
think\Route::post('/delivery-check/audit-turnover-box$', 'warehouse/DeliveryCheck/auditTurnoverBox',[], []);
//二次分拣
think\Route::post('/delivery-check/twice-sorting$', 'warehouse/DeliveryCheck/twiceSorting',[], []);
//获取篮子信息
think\Route::get('/delivery-check/basket-info$', 'warehouse/DeliveryCheck/basketInfo',[], []);
//获取播种车信息(二次分拣)
think\Route::get('/delivery-check/grid-info/:picking_id$', 'warehouse/DeliveryCheck/gridInfo',[], []);
//获取周转篮列表(二次分拣)
think\Route::get('/delivery-check/basket-list/:picking_id$', 'warehouse/DeliveryCheck/basketList',[], []);
//将周转篮重置未开始分拣
think\Route::post('/delivery-check/reset-twice-soring/:id$', 'warehouse/DeliveryCheck/resetTwiceSoring',[], []);
//重置单个篮子变为重新分拣
think\Route::post('/delivery-check/reset-basket$', 'warehouse/DeliveryCheck/resetBasket',[], []);
//包裹列表
think\Route::get('/delivery-check/package-list/:id$', 'warehouse/DeliveryCheck/packageList',[], []);
//包裹面单打印
think\Route::post('/delivery-check/:package_id/print$', 'warehouse/DeliveryCheck/print',[], []);
//包裹面单测试打印
think\Route::post('/delivery-check/:package_id/test-print$', 'warehouse/DeliveryCheck/testPrint',[], []);
//测试打印html面单
think\Route::post('/delivery-check/:package_id/test-html-print$', 'warehouse/DeliveryCheck/testHtmlPrint',[], []);
//根据包裹号直接打印 .
think\Route::post('/delivery-check/package-number-print$', 'warehouse/DeliveryCheck/packageNumberPrint',[], []);
//批量打印
think\Route::post('/delivery-check/batch-print$', 'warehouse/DeliveryCheck/batchPrint',[], []);
//批量打印篮子面单
think\Route::post('/delivery-check/batch-print-basket$', 'warehouse/DeliveryCheck/batchPrintBasket',[], []);
//批量打印篮子标签
think\Route::post('/delivery-check/batch/print-basket-label$', 'warehouse/DeliveryCheck/batchPrintBasketLabel',[], []);
//获取拣货单面单规格
think\Route::get('/delivery-check/picking-label-info$', 'warehouse/DeliveryCheck/getPickingLabelInfo',[], []);
//中止单品复核
think\Route::post('/delivery-check/:picking_id/stop$', 'warehouse/DeliveryCheck/stop',[], []);
//停用当前周转箱
think\Route::post('/delivery-check/stop-turnover-box$', 'warehouse/DeliveryCheck/stopTurnoverBox',[], []);
//确认退出周转箱
think\Route::post('/delivery-check/sure-stop-box$', 'warehouse/DeliveryCheck/sureStopTurnoverBox',[], []);
//中止二次分拣
think\Route::post('/delivery-check/:picking_id/stop-picking$', 'warehouse/DeliveryCheck/stopPicking',[], []);
//确认异常
think\Route::post('/delivery-check/:package_id/confirm-error$', 'warehouse/DeliveryCheck/confirmError',[], []);
//批量重新复核
think\Route::post('/delivery-check/batch-reset-single$', 'warehouse/DeliveryCheck/batchResetSingle',[], []);
//快速出货区重置缓存
think\Route::get('/delivery-check/reset-quick-cache$', 'warehouse/DeliveryCheck/resetQuickCache',[], []);
//清除已扫描信息
think\Route::post('/delivery-check/flush-checking$', 'warehouse/DeliveryCheck/flushChecking',[], []);
//重新打印
think\Route::post('/delivery-check/print-label$', 'warehouse/DeliveryCheck/printLabel',[], []);
//替换面单
think\Route::post('/delivery-check/print-change-label$', 'warehouse/DeliveryCheck/channelLabel',[], []);
//结束单品拣货单
think\Route::get('/delivery-check/stop-single-picking$', 'warehouse/DeliveryCheck/stopSinglePicking',[], []);
//确认结束拣货单
think\Route::post('/delivery-check/sure-stop-single-picking$', 'warehouse/DeliveryCheck/sureStopSinglePicking',[], []);
//测试推送生成html
think\Route::get('/delivery-check/push-html-queue$', 'warehouse/DeliveryCheck/pushHtml',[], []);
//获取百度api语音合成token
think\Route::get('/delivery-check/get-baidu-token$', 'warehouse/DeliveryCheck/getBaiduToken',[], []);
//按面单包装
think\Route::post('/delivery-check/label-check$', 'warehouse/DeliveryCheck/labelCheck',[], []);
//排除法批量包装
think\Route::post('/delivery-check/exclusion-check$', 'warehouse/DeliveryCheck/exclusionCheck',[], []);
//获取包到一半的包裹
think\Route::get('/delivery-check/:id/watch-cache$', 'warehouse/DeliveryCheck/getWatchData',[], ['id'=>'(\d+)']);
//删除单品多件缓存.
think\Route::post('/delivery-check/delete/watch-key$', 'warehouse/DeliveryCheck/delWatchKey',[], []);

//控制器：app\warehouse\controller\PackageCollection
//扫描面单号称重
think\Route::post('/package-collection/set-weight$', 'warehouse/PackageCollection/saveAction',[], []);
//根据面单号获取运输方式
think\Route::get('/package-collection/shipping$', 'warehouse/PackageCollection/getShippingByShippingNumber',[], []);
//读取集包单信息
think\Route::get('/package-collection/:id$', 'warehouse/PackageCollection/read',[], ['id'=>'(\d+)']);
//集包完成
think\Route::PUT('/package-collection/:id$', 'warehouse/PackageCollection/update',[], ['id'=>'(\d+)']);
//获取类型
think\Route::get('/package-collection/type-list$', 'warehouse/PackageCollection/typeList',[], []);
//集包单复核信息
think\Route::get('/package-collection/check-info/:code$', 'warehouse/PackageCollection/checkInfo',[], []);
//复核
think\Route::put('/package-collection/check$', 'warehouse/PackageCollection/check',[], []);
//状态信息
think\Route::get('/package-collection/status$', 'warehouse/PackageCollection/status',[], []);
//列表
think\Route::get('/package-collection$', 'warehouse/PackageCollection/index',[], []);
//左边菜单
think\Route::get('/package-collection/left-menu$', 'warehouse/PackageCollection/leftMenu',[], []);
//批量交接
think\Route::post('/package-collection/batch$', 'warehouse/PackageCollection/batch',[], []);
//批量加入队列出货
think\Route::post('/package-collection/batch/out-queue$', 'warehouse/PackageCollection/batchAddQueue',[], []);
//批量出库
think\Route::post('/package-collection/batch-out$', 'warehouse/PackageCollection/batchOut',[], []);
//包裹列表
think\Route::get('/package-collection/:id/package-list$', 'warehouse/PackageCollection/packageList',[], []);
//批量继续集包
think\Route::get('/package-collection/history-collection$', 'warehouse/PackageCollection/getHistoryPackage',[], []);
//包裹详情
think\Route::get('/package-collection/:id/info$', 'warehouse/PackageCollection/info',[], []);
//移除包裹
think\Route::delete('/package-collection/package/:id$', 'warehouse/PackageCollection/packageDelete',[], []);
//问题包裹
think\Route::get('/package-collection/problem$', 'warehouse/PackageCollection/problem',[], []);
//获取异常详情
think\Route::get('/package-collection/problem-info$', 'warehouse/PackageCollection/getProblemByPackageNumber',[], []);
//状态
think\Route::get('/package-collection/problem/status$', 'warehouse/PackageCollection/problemStatus',[], []);
//处理
think\Route::put('/package-collection/problem/handle$', 'warehouse/PackageCollection/problemHandle',[], []);
//集包单作废
think\Route::put('/package-collection/cancel/:id$', 'warehouse/PackageCollection/cancel',[], []);
//根据单号交接
think\Route::post('/package-collection/:code/handover$', 'warehouse/PackageCollection/handover',[], []);
//根据单号出库
think\Route::post('/package-collection/:code/out$', 'warehouse/PackageCollection/out',[], []);
//包裹作废
think\Route::put('/package-collection/problem/:id/package-cancel$', 'warehouse/PackageCollection/problemPackageCancel',[], []);
//批量复核
think\Route::post('/package-collection/batch-check$', 'warehouse/PackageCollection/batchCheck',[], []);
//批量作废
think\Route::post('/package-collection/batch/package-cancel$', 'warehouse/PackageCollection/batchProblemPackageCancel',[], []);
//设置包裹预估重量
think\Route::put('/package-collection/problem/:package_id/estimated-weight$', 'warehouse/PackageCollection/packageEstimatedWeight',[], []);
//批量设置预估重量
think\Route::post('/package-collection/batch/set-weight$', 'warehouse/PackageCollection/batchSetWeight',[], []);
//批量处理异常
think\Route::post('/package-collection/problem/batch-handle$', 'warehouse/PackageCollection/batchHandle',[], []);
//批量移除
think\Route::post('/package-collection/batch-del$', 'warehouse/PackageCollection/batchRemovePackage',[], []);
//打印后回调
think\Route::post('/package-collection/problem/print-callback$', 'warehouse/PackageCollection/printCallback',[], []);
//继续下单
think\Route::post('/package-collection/problem/continue-order$', 'warehouse/PackageCollection/continueOrder',[], []);
//更改邮寄方式
think\Route::post('/package-collection/problem/change-shipping$', 'warehouse/PackageCollection/changeShipping',[], []);
//导出集包包裹
think\Route::post('/package-collection/export$', 'warehouse/PackageCollection/exportPackage',[], []);
//获取异常类型
think\Route::get('/package-collection/problem-type$', 'warehouse/PackageCollection/getProblemType',[], []);
//自我生成集包信息
think\Route::post('/package-collection/self-do$', 'warehouse/PackageCollection/selfDo',[], []);
//获取异常处理措施
think\Route::get('/package-collection/problem-method$', 'warehouse/PackageCollection/getProblemMethod',[], []);
//加入异常
think\Route::post('/package-collection/add-problem$', 'warehouse/PackageCollection/addProblem',[], []);
//加入物流尺寸异常
think\Route::post('/package-collection/add-size-problem$', 'warehouse/PackageCollection/addSizeProblem',[], []);
//重新集包
think\Route::post('/package-collection/reset-collection$', 'warehouse/PackageCollection/resetCollection',[], []);
//批量重新集包
think\Route::post('/package-collection/batch/reset-collection$', 'warehouse/PackageCollection/batchResetCollection',[], []);
//手工加入袋子
think\Route::get('/package-collection/add-package$', 'warehouse/PackageCollection/addShippingPackage',[], []);
//物流未集包列表
think\Route::get('/package-collection/wait-problem$', 'warehouse/PackageCollection/waitProblem',[], []);
//物流类型
think\Route::get('/package-collection/wait-problem-type$', 'warehouse/PackageCollection/waitProblemType',[], []);
//修复包裹数
think\Route::get('/package-collection/ret-report$', 'warehouse/PackageCollection/retReport',[], []);

//控制器：app\warehouse\controller\PutawayOrder
//显示列表
think\Route::GET('/putaway-order$', 'warehouse/PutawayOrder/index',[], []);
//新增
think\Route::post('/putaway-order/create$', 'warehouse/PutawayOrder/create',[], []);
//上架
think\Route::post('/putaway-order/save$', 'warehouse/PutawayOrder/save',[], []);
//查看
think\Route::get('/putaway-order/:id$', 'warehouse/PutawayOrder/read',[], ['id'=>'(\d+)']);
//完成上架
think\Route::get('/putaway-order/status/:id$', 'warehouse/PutawayOrder/status',[], []);
//分区类型
think\Route::get('/putaway-order/types$', 'warehouse/PutawayOrder/types',[], []);
//作废采购上架单
think\Route::post('/putaway-order/invalid$', 'warehouse/PutawayOrder/invalid',[], []);
//强制完成采购上架单
think\Route::post('/putaway-order/force$', 'warehouse/PutawayOrder/force',[], []);
//完成上架采购上架单
think\Route::post('/putaway-order/finish$', 'warehouse/PutawayOrder/finish',[], []);

//控制器：app\warehouse\controller\PickingProcess
//移动端拣货单列表
think\Route::get('/picking-process$', 'warehouse/PickingProcess/index',[], []);
//拣货单任务详情
think\Route::get('/picking-process/:id/details$', 'warehouse/PickingProcess/detail',[], []);
//绑定周转箱
think\Route::post('/picking-process/:id/bind$', 'warehouse/PickingProcess/bind',[], []);
//拣货单商品下架
think\Route::post('/picking-process/:id/off$', 'warehouse/PickingProcess/off',[], []);
//下架
think\Route::post('/picking-process/off-shelve$', 'warehouse/PickingProcess/offShelve',[], []);
//完成拣货
think\Route::post('/picking-process/:id/complete$', 'warehouse/PickingProcess/complete',[], []);

//控制器：app\warehouse\controller\PutawayWaitingGoods
//显示列表
think\Route::GET('/putaway-waiting-goods$', 'warehouse/PutawayWaitingGoods/index',[], []);
//新增
think\Route::post('/putaway-waiting-goods/create$', 'warehouse/PutawayWaitingGoods/create',[], []);
//获取状态
think\Route::get('/putaway-waiting-goods/status$', 'warehouse/PutawayWaitingGoods/status',[], []);
//仓库区域类型
think\Route::get('/putaway-waiting-goods/warehouseAreaTypes$', 'warehouse/PutawayWaitingGoods/warehouseAreaTypes',[], []);
//SKU查询
think\Route::get('/putaway-waiting-goods/goods/:id$', 'warehouse/PutawayWaitingGoods/goods',[], []);
//直接上架
think\Route::post('/putaway-waiting-goods/update$', 'warehouse/PutawayWaitingGoods/update',[], []);
//直接上架批量
think\Route::post('/putaway-waiting-goods/batch/update$', 'warehouse/PutawayWaitingGoods/batchUpdate',[], []);
//货位+SKU直接上架
think\Route::post('/putaway-waiting-goods/cargoSkus$', 'warehouse/PutawayWaitingGoods/cargoSkus',[], []);
//根据SKU查货位
think\Route::get('/putaway-waiting-goods/cargos/:sku$', 'warehouse/PutawayWaitingGoods/cargos',[], []);

//控制器：app\goods\controller\GoodsDeclare
//列表
think\Route::get('/goods-declare$', 'goods/GoodsDeclare/index',[], []);
//保存
think\Route::post('/goods-declare$', 'goods/GoodsDeclare/save',[], []);
//更新
think\Route::PUT('/goods-declare/:id$', 'goods/GoodsDeclare/update',[], ['id'=>'(\d+)']);
//查看详情
think\Route::get('/goods-declare/:id$', 'goods/GoodsDeclare/read',[], ['id'=>'(\d+)']);
//查看编辑详情
think\Route::GET('/goods-declare/:id/edit$', 'goods/GoodsDeclare/edit',[], ['id'=>'(\d+)']);
//删除
think\Route::DELETE('/goods-declare/:id$', 'goods/GoodsDeclare/delete',[], ['id'=>'(\d+)']);

//控制器：app\warehouse\controller\WarehouseGoodsChannel
//显示平台库存列表
think\Route::get('/warehouse-goods-channel$', 'warehouse/WarehouseGoodsChannel/index',[], []);
//保存
think\Route::post('/warehouse-goods-channel$', 'warehouse/WarehouseGoodsChannel/save',[], []);
//显示指定的资源
think\Route::get('/warehouse-goods-channel/:id$', 'warehouse/WarehouseGoodsChannel/read',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/warehouse-goods-channel/:id$', 'warehouse/WarehouseGoodsChannel/update',[], ['id'=>'(\d+)']);
//删除指定资源
think\Route::DELETE('/warehouse-goods-channel/:id$', 'warehouse/WarehouseGoodsChannel/delete',[], ['id'=>'(\d+)']);
//平台借调
think\Route::post('/warehouse-goods-channel/lend$', 'warehouse/WarehouseGoodsChannel/lend',[], []);

//控制器：app\warehouse\controller\ReturnShelves
//重返上架列表
think\Route::get('/return-shelves$', 'warehouse/ReturnShelves/index',[], []);
//新增重返上架单
think\Route::post('/return-shelves$', 'warehouse/ReturnShelves/create',[], []);
//验证重返上架数量
think\Route::get('/return-shelves/quantity$', 'warehouse/ReturnShelves/quantity',[], []);
//作废重返上架单
think\Route::delete('/return-shelves/delete$', 'warehouse/ReturnShelves/delete',[], []);
//查看重返上架单
think\Route::get('/return-shelves/:id$', 'warehouse/ReturnShelves/read',[], ['id'=>'(\d+)']);
//操作重返上架
think\Route::put('/return-shelves/:id$', 'warehouse/ReturnShelves/update',[], ['id'=>'(\d+)']);
//完成重返上架
think\Route::put('/return-shelves/finish$', 'warehouse/ReturnShelves/finish',[], []);
//强制完成
think\Route::put('/return-shelves/force$', 'warehouse/ReturnShelves/force',[], []);
//重返上架详情列表
think\Route::get('/return-shelves/get-detail$', 'warehouse/ReturnShelves/getDetail',[], []);

//控制器：app\warehouse\controller\PackageReturn
//包裹退回信息列表
think\Route::get('/package-return$', 'warehouse/PackageReturn/index',[], []);
//包裹退回信息详情
think\Route::get('/package-return/:id$', 'warehouse/PackageReturn/read',[], ['id'=>'(\d+)']);
//录入包裹信息
think\Route::post('/package-return/handle$', 'warehouse/PackageReturn/handle',[], []);
//获取原因信息
think\Route::get('/package-return/reason$', 'warehouse/PackageReturn/reason',[], []);
//获取状态
think\Route::get('/package-return/status$', 'warehouse/PackageReturn/status',[], []);
//标记为待重发
think\Route::post('/package-return/:id/wait-for-reissued$', 'warehouse/PackageReturn/waitForReissued',[], []);
//待入库sku信息
think\Route::get('/package-return/:id/storage-info$', 'warehouse/PackageReturn/storageInfo',[], []);
//标记为待入库
think\Route::post('/package-return/:id/wait-for-storage$', 'warehouse/PackageReturn/waitForStorage',[], []);
//入库
think\Route::post('/package-return/:id/storage$', 'warehouse/PackageReturn/storage',[], []);
//标记为已重发
think\Route::post('/package-return/:id/already-reissued$', 'warehouse/PackageReturn/alreadyReissued',[], []);
//打印面单
think\Route::get('/package-return/:id/print$', 'warehouse/PackageReturn/printLabel',[], []);
//批量标记待重发
think\Route::post('/package-return/batch/wait-for-reissued$', 'warehouse/PackageReturn/batchWaitForReissued',[], []);
//批量标记待入库
think\Route::post('/package-return/batch/wait-for-storage$', 'warehouse/PackageReturn/batchWaitForStorage',[], []);
//批量入库
think\Route::post('/package-return/batch/storage$', 'warehouse/PackageReturn/batchStorage',[], []);
//新增备注
think\Route::post('/package-return/:id/note$', 'warehouse/PackageReturn/note',[], []);
//导出execl
think\Route::post('/package-return/export$', 'warehouse/PackageReturn/export',[], []);
//导入退回包裹
think\Route::post('/package-return/import$', 'warehouse/PackageReturn/import',[], []);
//保存导入退回包裹
think\Route::post('/package-return/save-import$', 'warehouse/PackageReturn/saveImport',[], []);

//控制器：app\warehouse\controller\WarehouseGoodsCheck
//盘点单列表
think\Route::get('/warehouse-goods-check$', 'warehouse/WarehouseGoodsCheck/index',[], []);
//新增
think\Route::post('/warehouse-goods-check/create$', 'warehouse/WarehouseGoodsCheck/create',[], []);
//编辑
think\Route::post('/warehouse-goods-check/updates$', 'warehouse/WarehouseGoodsCheck/updates',[], []);
//查看
think\Route::get('/warehouse-goods-check/:id$', 'warehouse/WarehouseGoodsCheck/read',[], ['id'=>'(\d+)']);
//盘点数据核查
think\Route::post('/warehouse-goods-check/exists$', 'warehouse/WarehouseGoodsCheck/exists',[], []);
//盘点
think\Route::post('/warehouse-goods-check/save$', 'warehouse/WarehouseGoodsCheck/save',[], []);
//批量盘点
think\Route::post('/warehouse-goods-check/batch/save$', 'warehouse/WarehouseGoodsCheck/batchSave',[], []);
//完成盘点
think\Route::get('/warehouse-goods-check/finish/:id$', 'warehouse/WarehouseGoodsCheck/finish',[], []);
//盘点单状态
think\Route::get('/warehouse-goods-check/status$', 'warehouse/WarehouseGoodsCheck/status',[], []);
//盘点单作废
think\Route::delete('/warehouse-goods-check/cancels/:id$', 'warehouse/WarehouseGoodsCheck/cancels',[], []);
//盘点单删除
think\Route::delete('/warehouse-goods-check/deletes/:id$', 'warehouse/WarehouseGoodsCheck/deletes',[], []);
//盘点单删除
think\Route::post('/warehouse-goods-check/delete-details$', 'warehouse/WarehouseGoodsCheck/deleteDetails',[], []);
//盘点单重盘
think\Route::put('/warehouse-goods-check/recheck/:id$', 'warehouse/WarehouseGoodsCheck/recheck',[], []);
//盘点单详情列表
think\Route::get('/warehouse-goods-check/get-detail-list$', 'warehouse/WarehouseGoodsCheck/getDetailList',[], []);
//获取盘点单信息
think\Route::get('/warehouse-goods-check/:id/get-info$', 'warehouse/WarehouseGoodsCheck/getInfo',[], []);

//控制器：app\warehouse\controller\WarehouseGoodsChannelLog
//第三方调库存管理列表
think\Route::get('/warehouse-goods-channel-log$', 'warehouse/WarehouseGoodsChannelLog/index',[], []);
//第三方仓库申请分配审核
think\Route::post('/warehouse-goods-channel-log/audit$', 'warehouse/WarehouseGoodsChannelLog/audit',[], []);
//第三方协调分配审核
think\Route::post('/warehouse-goods-channel-log/coordinate-audit$', 'warehouse/WarehouseGoodsChannelLog/coordinateAudit',[], []);
//第三方批量协调分配审核
think\Route::post('/warehouse-goods-channel-log/mcoordinate-audit$', 'warehouse/WarehouseGoodsChannelLog/multiCoordinateAudit',[], []);
//第三方仓库申请分配批量审核
think\Route::post('/warehouse-goods-channel-log/multi-audit$', 'warehouse/WarehouseGoodsChannelLog/multiAudit',[], []);
//第三方仓库分配拒绝
think\Route::post('/warehouse-goods-channel-log/deny$', 'warehouse/WarehouseGoodsChannelLog/deny',[], []);
//第三方仓库批量分配拒绝
think\Route::post('/warehouse-goods-channel-log/multi-deny$', 'warehouse/WarehouseGoodsChannelLog/multiDeny',[], []);
//第三方仓库协调拒绝
think\Route::post('/warehouse-goods-channel-log/coordinate-deny$', 'warehouse/WarehouseGoodsChannelLog/coordinateDeny',[], []);
//第三方仓库批量协调拒绝
think\Route::post('/warehouse-goods-channel-log/mcoordinate-deny$', 'warehouse/WarehouseGoodsChannelLog/multiCoordinateDeny',[], []);
//查看借调详情
think\Route::get('/warehouse-goods-channel-log/:id$', 'warehouse/WarehouseGoodsChannelLog/read',[], ['id'=>'(\d+)']);
//状态列表
think\Route::get('/warehouse-goods-channel-log/status$', 'warehouse/WarehouseGoodsChannelLog/status',[], []);
//创建人
think\Route::get('/warehouse-goods-channel-log/creator$', 'warehouse/WarehouseGoodsChannelLog/creator',[], []);
//获取所有审核人
think\Route::get('/warehouse-goods-channel-log/auditor$', 'warehouse/WarehouseGoodsChannelLog/auditor',[], []);
//更改审批人
think\Route::post('/warehouse-goods-channel-log/changeAuditor$', 'warehouse/WarehouseGoodsChannelLog/changeAuditor',[], []);
//批量更改审批人
think\Route::post('/warehouse-goods-channel-log/multi-changeAuditor$', 'warehouse/WarehouseGoodsChannelLog/multiChangeAuditor',[], []);
//获取该订单下平台审批人
think\Route::get('/warehouse-goods-channel-log/verifier$', 'warehouse/WarehouseGoodsChannelLog/verifier',[], []);
//获取所有平台所有审批人
think\Route::get('/warehouse-goods-channel-log/get-all-verifier$', 'warehouse/WarehouseGoodsChannelLog/getAllVerifier',[], []);

//控制器：app\listing\controller\Amazon
//亚马逊在线listing修改日志
think\Route::get('/listing/amazon/action-logs$', 'listing/Amazon/actionLogs',[], []);
//修改Listing日志
think\Route::post('/listing/amazon/edit-listing$', 'listing/Amazon/editListing',[], []);
//亚马逊批量修改销售价
think\Route::post('/listing/amazon/batch-edit-price$', 'listing/Amazon/batchEditPrice',[], []);

//控制器：app\warehouse\controller\Stocking
//备货申请列表
think\Route::get('/stocking/apply-list$', 'warehouse/Stocking/applyList',[], []);
//批量确认备货申请
think\Route::put('/stocking/batch/sure$', 'warehouse/Stocking/batchSure',[], []);
//获取可合并备货计划
think\Route::get('/stocking/related-plan$', 'warehouse/Stocking/relatedPlan',[], []);
//批量删除
think\Route::delete('/stocking/batch/delete$', 'warehouse/Stocking/batchDelete',[], []);
//状态信息
think\Route::get('/stocking/status$', 'warehouse/Stocking/status',[], []);
//备货计划列表
think\Route::get('/stocking$', 'warehouse/Stocking/index',[], []);
//备货计划详情
think\Route::get('/stocking/:id$', 'warehouse/Stocking/read',[], ['id'=>'(\d+)']);
//提交备货计划
think\Route::put('/stocking/batch/commit$', 'warehouse/Stocking/batchCommit',[], []);
//修改备货计划SKU数量
think\Route::PUT('/stocking/:id$', 'warehouse/Stocking/update',[], ['id'=>'(\d+)']);
//备注备货计划
think\Route::put('/stocking/:id/remark$', 'warehouse/Stocking/remark',[], []);
//作废备货计划
think\Route::delete('/stocking/:id$', 'warehouse/Stocking/cancel',[], []);
//审核备货计划
think\Route::put('/stocking/:id/audit$', 'warehouse/Stocking/audit',[], []);
//审核日志
think\Route::get('/stocking/:id/audit-log$', 'warehouse/Stocking/auditLog',[], []);
//新建采购计划列表
think\Route::get('/stocking/sku-list$', 'warehouse/Stocking/skuList',[], []);
//删除备货计划SKU
think\Route::delete('/stocking/:id/sku/:sku_id$', 'warehouse/Stocking/skuDelete',[], []);
//备货申请表选择列表
think\Route::get('/stocking/:sku_id/choose-list$', 'warehouse/Stocking/chooseList',[], []);
//保存备货计划
think\Route::post('/stocking/save-plan$', 'warehouse/Stocking/savePlan',[], []);
//根据备货单号获取备货计划列表
think\Route::get('/stocking/list-by-code$', 'warehouse/Stocking/listByCode',[], []);
//开发状态信息
think\Route::get('/stocking/development-status$', 'warehouse/Stocking/developmentStatus',[], []);
//采购状态信息
think\Route::get('/stocking/purchase-status$', 'warehouse/Stocking/purchaseStatus',[], []);
//批量释放库存
think\Route::post('/stocking/batch-release$', 'warehouse/Stocking/batchRelease',[], []);
//excel字段信息
think\Route::get('/stocking/export-title$', 'warehouse/Stocking/title',[], []);
//导出excel
think\Route::post('/stocking/export$', 'warehouse/Stocking/export',[], []);

//控制器：app\warehouse\controller\Barcode
//条码查询
think\Route::post('/barcode/datas$', 'warehouse/Barcode/datas',[], []);

//控制器：app\warehouse\controller\RebackShelves
//退回待上架
think\Route::get('/reback-shelves$', 'warehouse/RebackShelves/index',[], []);
//批量退回待上架
think\Route::post('/reback-shelves/batch/save$', 'warehouse/RebackShelves/batchSave',[], []);

//控制器：app\purchase\controller\PurchaseParcelsAudit
//采购拆包审核
think\Route::get('/purchase-parcels-audit$', 'purchase/PurchaseParcelsAudit/index',[], []);
//采购拆包审核
think\Route::post('/purchase-parcels-audit$', 'purchase/PurchaseParcelsAudit/save',[], []);

//控制器：app\publish\controller\EbayCategorySearch
//关键字分类搜索
think\Route::POST('/ebay-category-search$', 'publish/EbayCategorySearch/index',[], []);

//控制器：app\publish\controller\Wangxiaowang
//旺销王-热词搜索
think\Route::POST('/alihelp-hot$', 'publish/Wangxiaowang/hotQuery',[], []);
//旺销王-热词语言选项
think\Route::GET('/alihelp-hotlang$', 'publish/Wangxiaowang/hotlangList',[], []);
//旺销王-直通车搜索
think\Route::POST('/alihelp-bcar$', 'publish/Wangxiaowang/bcarQuery',[], []);

//控制器：app\index\controller\JoomAccount
//joom帐号列表
think\Route::GET('/joom-account$', 'index/JoomAccount/index',[], []);
//保存新建的资源
think\Route::POST('/joom-account$', 'index/JoomAccount/save',[], []);
//显示指定的资源
think\Route::GET('/joom-account/:id$', 'index/JoomAccount/read',[], []);
//显示指定的资源
think\Route::GET('/joom-account/:id/edit$', 'index/JoomAccount/edit',[], []);
//保存更新的资源
think\Route::PUT('/joom-account/:id$', 'index/JoomAccount/update',[], []);
//JOOM账号停用，启用
think\Route::POST('/joom-account/status$', 'index/JoomAccount/changeStatus',[], []);
//批量开启
think\Route::post('/joom-account/batch-set$', 'index/JoomAccount/batchSet',[], []);

//控制器：app\index\controller\JoomShop
//joom帐号列表
think\Route::GET('/joom-shop$', 'index/JoomShop/index',[], []);
//拉取帐号对应的店铺数量；
think\Route::GET('/joom-shop/accounts$', 'index/JoomShop/accounts',[], []);
//保存新建的资源
think\Route::POST('/joom-shop$', 'index/JoomShop/save',[], []);
//显示指定的资源
think\Route::GET('/joom-shop/:id$', 'index/JoomShop/read',[], []);
//显示编辑资源表单页.
think\Route::GET('/joom-shop/:id/edit$', 'index/JoomShop/edit',[], []);
//保存更新的资源
think\Route::PUT('/joom-shop/:id$', 'index/JoomShop/update',[], []);
//joom批量设置抓取参数；
think\Route::post('/joom-shop/set$', 'index/JoomShop/batchSet',[], []);
//JOOM店铺停用，启用
think\Route::POST('/joom-shop/status$', 'index/JoomShop/changeStates',[], []);
//joom获取授权码code
think\Route::post('/joom-shop/authorCode$', 'index/JoomShop/authorCode',[], []);
//joom获取Token
think\Route::post('/joom-shop/token$', 'index/JoomShop/token',[], []);
//joom打开授权页面
think\Route::post('/joom-shop/authorization$', 'index/JoomShop/authorization',[], []);

//控制器：app\purchase\controller\PurchaseParcelsRecords
//接收未审核列表
think\Route::get('/purchase-parcels-records$', 'purchase/PurchaseParcelsRecords/index',[], []);
//批量删除未审核包裹明细
think\Route::post('/purchase-parcels-records/batchDelete$', 'purchase/PurchaseParcelsRecords/batchDelete',[], []);

//控制器：app\publish\controller\JoomCategory
//帐号店铺分类列表
think\Route::GET('/joom-category$', 'publish/JoomCategory/index',[], []);
//返回帐号店铺分类ID数组
think\Route::POST('/joom-category/getcategory$', 'publish/JoomCategory/getcategory',[], []);
//拿取Joom帐号
think\Route::GET('/joom-category/accounts$', 'publish/JoomCategory/accounts',[], []);
//拿取Joom帐号对应的店铺
think\Route::GET('/joom-category/shops$', 'publish/JoomCategory/shops',[], []);
//拿取商品分类
think\Route::GET('/joom-category/category$', 'publish/JoomCategory/category',[], []);
//设置账号店铺分类；
think\Route::POST('/joom-category$', 'publish/JoomCategory/set',[], []);
//设置账号店铺分类；
think\Route::POST('/joom-category/del$', 'publish/JoomCategory/del',[], []);
//根据产品ID返回能刊登的店铺；
think\Route::GET('/joom-category/checkshops$', 'publish/JoomCategory/checkshops',[], []);

//控制器：app\publish\controller\JoomListing
//JoomListing在售下架列表
think\Route::GET('/joomlisting$', 'publish/JoomListing/index',[], []);
//获取JoomListing列表里variant的数据；
think\Route::GET('/joomlisting/variant$', 'publish/JoomListing/variant',[], []);
//获取Joomlisting销售员列表
think\Route::GET('/joomlisting/users$', 'publish/JoomListing/users',[], []);
//Joomlisting批量同步更新
think\Route::POST('/joomlisting/sync$', 'publish/JoomListing/sync',[], []);
//产品上架和批量上架接口
think\Route::POST('/joomlisting/enable$', 'publish/JoomListing/enable',[], []);
//产品下架和批量下架接口
think\Route::POST('/joomlisting/disable$', 'publish/JoomListing/disable',[], []);
//变体上架和批量上架接口
think\Route::POST('/joomlisting/variantEnable$', 'publish/JoomListing/variantEnable',[], []);
//变体下架和批量下架接口
think\Route::POST('/joomlisting/variantDisable$', 'publish/JoomListing/variantDisable',[], []);
//获取Joomlisting操作日志
think\Route::GET('/joomlisting/logs$', 'publish/JoomListing/logs',[], []);
//获取Joom刊登记录
think\Route::GET('/joomlisting/record$', 'publish/JoomListing/record',[], []);
//删除Joom刊登出错的数据
think\Route::GET('/joomlisting/delrecord$', 'publish/JoomListing/delrecord',[], []);
//Joom记录里批量刊登数据
think\Route::GET('/joomlisting/publish$', 'publish/JoomListing/publish',[], []);

//控制器：app\publish\controller\JoomTagSearch
//Joom关键字标签搜索
think\Route::GET('/joomtag-search$', 'publish/JoomTagSearch/index',[], []);

//控制器：app\index\controller\Ali1688Account
//1688账号列表
think\Route::get('/ali1688-account$', 'index/Ali1688Account/index',[], []);
//查看
think\Route::get('/ali1688-account/:id$', 'index/Ali1688Account/read',[], ['id'=>'(\d+)']);
//新增
think\Route::post('/ali1688-account$', 'index/Ali1688Account/save',[], []);
//更新
think\Route::put('/ali1688-account/:id$', 'index/Ali1688Account/update',[], ['id'=>'(\d+)']);
//启用停用
think\Route::post('/ali1688-account/states$', 'index/Ali1688Account/isInvalid',[], []);
//获取授
think\Route::post('/ali1688-account/getAuthorCode$', 'index/Ali1688Account/getAuthorCode',[], []);
//获取token
think\Route::post('/ali1688-account/getToken$', 'index/Ali1688Account/getToken',[], []);
//批量开启
think\Route::post('/ali1688-account/batch-set$', 'index/Ali1688Account/batchSet',[], []);

//控制器：app\warehouse\controller\PackageNotCollection
//未集包列表
think\Route::get('/package-not-collection$', 'warehouse/PackageNotCollection/index',[], []);
//获取运输方式
think\Route::get('/package-not-collection/shipping$', 'warehouse/PackageNotCollection/shipping',[], []);
//退回到待生成拣货单
think\Route::post('/package-not-collection/back$', 'warehouse/PackageNotCollection/back',[], []);

//控制器：app\api\controller\EbayNotification
//ebay 接收接口
think\Route::POST('/api/ebay/notification$', 'api/EbayNotification/item',[], []);

//控制器：app\warehouse\controller\WaitForPacking
//等待生成拣货单包裹列表
think\Route::get('/wait-for-packing$', 'warehouse/WaitForPacking/index',[], []);
//获取运输方式
think\Route::get('/wait-for-packing/shipping$', 'warehouse/WaitForPacking/shipping',[], []);

//控制器：app\warehouse\controller\WaitForMakePicking
//等待生成拣货单包裹列表
think\Route::get('/wait-for-make-picking$', 'warehouse/WaitForMakePicking/index',[], []);
//等待生成拣货单包裹sku列表
think\Route::get('/wait-for-make-picking/sku$', 'warehouse/WaitForMakePicking/sku',[], []);
//获取运输方式
think\Route::get('/wait-for-make-picking/shipping$', 'warehouse/WaitForMakePicking/shipping',[], []);
//配货未符合生成拣货单包裹列表
think\Route::get('/wait-for-make-picking/not-conforming$', 'warehouse/WaitForMakePicking/notConforming',[], []);

//控制器：app\internalletter\controller\InternalLetter
//发送站内信
think\Route::post('/internal-letters$', 'internalletter/InternalLetter/sendLetter',[], []);
//发钉钉工作消息
think\Route::get('/internal-letters/message$', 'internalletter/InternalLetter/message',[], []);
//保存到草稿箱
think\Route::post('/internal-letters/draftbox$', 'internalletter/InternalLetter/saveToDraftbox',[], []);
//收件箱
think\Route::get('/internal-letters/received-letters$', 'internalletter/InternalLetter/receivedLetters',[], []);
//发件箱
think\Route::get('/internal-letters/sent-letter$', 'internalletter/InternalLetter/sentLetters',[], []);
//草稿箱
think\Route::get('/internal-letters/draft$', 'internalletter/InternalLetter/draft',[], []);
//草稿编辑
think\Route::get('/internal-letters/draft-edit$', 'internalletter/InternalLetter/draftEdit',[], []);
//草稿箱批量发送
think\Route::post('/internal-letters/batch-send$', 'internalletter/InternalLetter/batchSend',[], []);
//草稿箱批量删除
think\Route::delete('/internal-letters/draft-delete$', 'internalletter/InternalLetter/draftDelete',[], []);
//看收信
think\Route::get('/internal-letters/view-letter$', 'internalletter/InternalLetter/viewReceivedLetter',[], []);
//看发信
think\Route::get('/internal-letters/view-sent-letter$', 'internalletter/InternalLetter/viewSentLetter',[], []);
//设置已读
think\Route::put('/internal-letters/read$', 'internalletter/InternalLetter/setRead',[], []);
//全部已读
think\Route::put('/internal-letters/all-read$', 'internalletter/InternalLetter/setAllRead',[], []);
//看草稿
think\Route::get('/internal-letters/view-draft$', 'internalletter/InternalLetter/viewDraft',[], []);
//删除收信
think\Route::delete('/internal-letters/delete-received-letters$', 'internalletter/InternalLetter/deleteReceivedLetters',[], []);
//删除发信
think\Route::post('/internal-letters/delet-sent-letters$', 'internalletter/InternalLetter/deletSentLetters',[], []);
//获取所有站内信类型
think\Route::get('/internal-letters/type$', 'internalletter/InternalLetter/type',[], []);
//获取所有用户信息
think\Route::get('/internal-letters/user-info$', 'internalletter/InternalLetter/userInfo',[], []);
//下载附件
think\Route::get('/internal-letters/attachment$', 'internalletter/InternalLetter/attachment',[], []);
//新站内信通知
think\Route::get('/internal-letters/notification$', 'internalletter/InternalLetter/notification',[], []);
//发送钉钉群消息
think\Route::post('/internal-letters/chat$', 'internalletter/InternalLetter/send_chat_message',[], []);
//保存联系人模板
think\Route::post('/internal-letters/templates$', 'internalletter/InternalLetter/saveTemplate',[], []);
//删除联系人模板
think\Route::delete('/internal-letters/templates$', 'internalletter/InternalLetter/deleteTemplate',[], []);
//获取联系人模板
think\Route::get('/internal-letters/templates$', 'internalletter/InternalLetter/getTemplate',[], []);
//获取联系人模板详情
think\Route::get('/internal-letters/templates—detail$', 'internalletter/InternalLetter/getTemplateDetail',[], []);
//搜索已添加用户
think\Route::get('/internal-letters/user-templates$', 'internalletter/InternalLetter/searchUserInTemplate',[], []);

//控制器：app\publish\controller\JoomAttr
//Joom商品颜色列表
think\Route::GET('/joomattr/color$', 'publish/JoomAttr/index',[], []);
//Joom商品尺寸列表
think\Route::GET('/joomattr/size$', 'publish/JoomAttr/getSize',[], []);

//控制器：app\publish\controller\Joom
//joom敏感货
think\Route::GET('/publish/joom/dangerous-kind$', 'publish/Joom/dangerousKind',[], []);
//joom导出
think\Route::post('/publish/joom/download$', 'publish/Joom/download',[], []);
//获取joom待刊登商品列表
think\Route::get('/publish/joom/productList$', 'publish/Joom/productList',[], []);
//joom获取商品数据
think\Route::get('/publish/joom/getData$', 'publish/Joom/getData',[], []);
//joom编辑修改
think\Route::get('/publish/joom/edit/id/:id/status/:status$', 'publish/Joom/edit',[], ['id'=>'(\d+)', 'status'=>'(\w+)']);
//joom新增刊登提交数据
think\Route::post('/publish/joom/add$', 'publish/Joom/add',[], []);
//joom更新刊登提交数据
think\Route::post('/publish/joom/update$', 'publish/Joom/update',[], []);

//控制器：app\publish\controller\AmazonTemplate
//amazon产品模板列表
think\Route::GET('/amazon-template/product$', 'publish/AmazonTemplate/product',[], []);
//amazon分类模板列表
think\Route::GET('/amazon-template/category$', 'publish/AmazonTemplate/category',[], []);
//amazon模板创建人和站点
think\Route::GET('/amazon-template/:type/creator$', 'publish/AmazonTemplate/creator',[], ['type'=>'(\d+)']);
//amazon读取模板详情
think\Route::GET('/amazon-template/:id$', 'publish/AmazonTemplate/read',[], ['id'=>'(\d+)']);
//amazon编辑模板详情
think\Route::GET('/amazon-template/:id/edit$', 'publish/AmazonTemplate/edit',[], ['id'=>'(\d+)']);
//amazon启用停用模板
think\Route::GET('/amazon-template/status/:id/:enable$', 'publish/AmazonTemplate/status',[], ['id'=>'(\d+)', 'enable'=>'(\d+)']);
//amazon更新模板详情
think\Route::PUT('/amazon-template$', 'publish/AmazonTemplate/update',[], []);
//amazon新增模板
think\Route::POST('/amazon-template$', 'publish/AmazonTemplate/save',[], []);
//amazon产品元素列表
think\Route::GET('/amazon-template/productbase$', 'publish/AmazonTemplate/productbase',[], []);
//amazon分类列表
think\Route::GET('/amazon-template/categorybase/:site$', 'publish/AmazonTemplate/categorybase',[], ['site'=>'(\d+)']);
//amazon分类下所属元素列表
think\Route::GET('/amazon-template/categoryele$', 'publish/AmazonTemplate/categoryRelation',[], []);
//amazon批量删除模板
think\Route::GET('/amazon-template/del$', 'publish/AmazonTemplate/delete',[], []);
//amazon更新数据；
think\Route::GET('/amazon-template/update-old-data$', 'publish/AmazonTemplate/updateOldData',[], []);

//控制器：app\publish\controller\Collect
//速卖通部门所有员工
think\Route::get('/aliexpress-users$', 'publish/Collect/aliexpressUsers',[], []);
//刊登数据采集列表
think\Route::GET('/publish-collect-index$', 'publish/Collect/index',[], []);
//添加采集
think\Route::post('/publish-collect-add$', 'publish/Collect/add',[], []);
//认领
think\Route::POST('/publish-collect-claim$', 'publish/Collect/claim',[], []);
//绑定本地商品
think\Route::POST('/publish-collect-bind-goods$', 'publish/Collect/bind',[], []);
//刊登采集删除
think\Route::POST('/publish-collect-delete$', 'publish/Collect/delete',[], []);

//控制器：app\warehouse\controller\Report
//仓上架统计
think\Route::get('/warehouse/report/shelf$', 'warehouse/Report/shelf',[], []);
//贴标统计
think\Route::get('/warehouse/report/label$', 'warehouse/Report/label',[], []);
//下架统计
think\Route::get('/warehouse/report/picking$', 'warehouse/Report/picking',[], []);
//集包统计
think\Route::get('/warehouse/report/collection$', 'warehouse/Report/collection',[], []);
//分拣统计
think\Route::get('/warehouse/report/sorting$', 'warehouse/Report/sorting',[], []);
//打包统计
think\Route::get('/warehouse/report/packing$', 'warehouse/Report/packing',[], []);
//拆包统计
think\Route::get('/warehouse/report/unpack$', 'warehouse/Report/unpack',[], []);
//拆包入库统计
think\Route::get('/warehouse/report/unpack-store$', 'warehouse/Report/unpackStore',[], []);
//出库交接统计
think\Route::get('/warehouse/report/Out-transfer$', 'warehouse/Report/OutTransfer',[], []);
//今日看板
think\Route::get('/warehouse/report/today$', 'warehouse/Report/today',[], []);
//今日看板仓库统计one
think\Route::get('/warehouse/report/warehouse-statistics-one$', 'warehouse/Report/warehouseStatisticsOne',[], []);
//今日看板仓库统计two
think\Route::get('/warehouse/report/warehouse-statistics-two$', 'warehouse/Report/warehouseStatisticsTwo',[], []);
//仓库绩效统计
think\Route::get('/warehouse/report/capacity-statistics$', 'warehouse/Report/capacityStatistics',[], []);
//报表导出
think\Route::post('/warehouse/report/export$', 'warehouse/Report/export',[], []);

//控制器：app\order\controller\StockOrder
//缺货订单列表
think\Route::get('/stock-orders$', 'order/StockOrder/index',[], []);
//execl字段信息
think\Route::get('/stock-orders/export-title$', 'order/StockOrder/title',[], []);
//导出execl
think\Route::post('/stock-orders/export$', 'order/StockOrder/export',[], []);

//控制器：app\order\controller\ProvidersException
//物流下单异常包裹列表
think\Route::get('/providers-exception$', 'order/ProvidersException/index',[], []);
//获取异常总条数
think\Route::get('/providers-exception/total$', 'order/ProvidersException/total',[], []);
//批量重跑申报规则
think\Route::post('/providers-exception/batch/running-declare$', 'order/ProvidersException/runningDeclareQueue',[], []);
//execl字段信息
think\Route::get('/providers-exception/export-title$', 'order/ProvidersException/title',[], []);
//导出
think\Route::post('/providers-exception/export$', 'order/ProvidersException/export',[], []);
//批量作废
think\Route::post('/providers-exception/batch/invalid$', 'order/ProvidersException/batchInvalid',[], []);
//批量更换包裹号
think\Route::post('/providers-exception/batch/changePackageNumber$', 'order/ProvidersException/batch',[], []);

//控制器：app\publish\controller\Pandao
//Pandao刊登记录提交刊登
think\Route::post('/publish/pandao/push-queue$', 'publish/Pandao/pushQueue',[], []);
//Pandao操作日志
think\Route::GET('/publish/pandao/logs$', 'publish/Pandao/logs',[], []);
//Pandao同步listing
think\Route::POST('/publish/pandao/rsync-product$', 'publish/Pandao/rsyncProduct',[], []);
//Pandao批量下架
think\Route::POST('/publish/pandao/batch-disable$', 'publish/Pandao/batchDisable',[], []);
//Pandao批量上架
think\Route::POST('/publish/pandao/batch-enable$', 'publish/Pandao/batchEnable',[], []);
//Pandao删除刊登记录
think\Route::delete('/publish/pandao/delete$', 'publish/Pandao/delete',[], []);
//Pandao编辑修改获取数据
think\Route::get('/publish/pandao/edit/id/:id/status/:status$', 'publish/Pandao/edit',[], ['id'=>'(\d+)', 'status'=>'(\w+)']);
//Pandao更新修改了的数据
think\Route::post('/publish/pandao/update$', 'publish/Pandao/update',[], []);
//pandao获取商品数据
think\Route::get('/publish/pandao/getdata$', 'publish/Pandao/getData',[], []);
//Pandao新增刊登
think\Route::post('/publish/pandao/add$', 'publish/Pandao/add',[], []);
//Pandao销售人员列表
think\Route::GET('/pandao-sellers$', 'publish/Pandao/sellers',[], []);
//Pandao在售listing
think\Route::GET('/pandao-on-selling$', 'publish/Pandao/index',[], []);
//Pandao停售listing
think\Route::GET('/pandao-sold-out$', 'publish/Pandao/soldOut',[], []);
//Pandao刊登记录
think\Route::GET('/pandao-publish-record$', 'publish/Pandao/records',[], []);
//Pandao待刊登商品列表
think\Route::GET('/publish/pandao/wait-upload$', 'publish/Pandao/waitUpload',[], []);
//获取刊登账号
think\Route::GET('/publish/pandao/accounts$', 'publish/Pandao/getPublishAccount',[], []);

//控制器：app\index\controller\PandaoAccount
//pandao账号列表
think\Route::GET('/pandao-account$', 'index/PandaoAccount/index',[], []);
//添加账号
think\Route::POST('/pandao-account/add$', 'index/PandaoAccount/add',[], []);
// pandao账号授权
think\Route::post('/pandao-account/authorization$', 'index/PandaoAccount/authorization',[], []);
//查看账号
think\Route::get('/pandao-account/:id$', 'index/PandaoAccount/read',[], []);
//编辑账号
think\Route::get('/pandao-account/:id/edit$', 'index/PandaoAccount/edit',[], []);
//更新账号
think\Route::POST('/pandao-account/update$', 'index/PandaoAccount/update',[], []);
//停用，启用账号
think\Route::post('/pandao-account/states$', 'index/PandaoAccount/changeStatus',[], []);
//批量开启
think\Route::post('/pandao-account/batch-set$', 'index/PandaoAccount/batchSet',[], []);

//控制器：app\index\controller\ExportTemplate
//获取我的模板
think\Route::get('/export-template$', 'index/ExportTemplate/index',[], []);
//获取导出模板详情
think\Route::get('/export-template/:id$', 'index/ExportTemplate/read',[], ['id'=>'(\d+)']);
//保存模板
think\Route::post('/export-template$', 'index/ExportTemplate/save',[], []);
//删除导出模板
think\Route::DELETE('/export-template/:id$', 'index/ExportTemplate/delete',[], ['id'=>'(\d+)']);

//控制器：app\warehouse\controller\RebackShelvesOrder
//退货上架单列表
think\Route::get('/reback-shelves-order$', 'warehouse/RebackShelvesOrder/index',[], []);
//生成上架单
think\Route::post('/reback-shelves-order$', 'warehouse/RebackShelvesOrder/create',[], []);
//验证退货上架数量
think\Route::get('/reback-shelves-order/quantity$', 'warehouse/RebackShelvesOrder/quantity',[], []);
//作废退货上架单
think\Route::delete('/reback-shelves-order$', 'warehouse/RebackShelvesOrder/delete',[], []);
//查看退货上架单
think\Route::get('/reback-shelves-order/:id$', 'warehouse/RebackShelvesOrder/read',[], ['id'=>'(\d+)']);
//操作退货上架
think\Route::put('/reback-shelves-order/:id$', 'warehouse/RebackShelvesOrder/update',[], ['id'=>'(\d+)']);
//完成退货上架单
think\Route::put('/reback-shelves-order/finish$', 'warehouse/RebackShelvesOrder/finish',[], []);
//强制完成退货上架单
think\Route::put('/reback-shelves-order/force$', 'warehouse/RebackShelvesOrder/force',[], []);

//控制器：app\index\controller\ShopeeAccount
//获取shopee账户列表
think\Route::get('/shopee-account$', 'index/ShopeeAccount/index',[], []);
//获取shopee账户详情
think\Route::get('/shopee-account/:id$', 'index/ShopeeAccount/read',[], ['id'=>'(\d+)']);
//保存shopee账户详情
think\Route::post('/shopee-account$', 'index/ShopeeAccount/save',[], []);
//保存shopee账户授权
think\Route::put('/shopee-account/save-token$', 'index/ShopeeAccount/saveToken',[], []);
//系统状态切换
think\Route::post('/shopee-account/change-status$', 'index/ShopeeAccount/changeStatus',[], []);
//获取站点
think\Route::get('/shopee-account/site$', 'index/ShopeeAccount/getSite',[], []);
//获取账号
think\Route::get('/shopee-account/account$', 'index/ShopeeAccount/getAccount',[], []);
//批量开启
think\Route::post('/shopee-account/batch-set$', 'index/ShopeeAccount/batchSet',[], []);

//控制器：app\customerservice\controller\EmailAccount
//获取邮箱账号列表
think\Route::GET('/email-account$', 'customerservice/EmailAccount/index',[], []);
//查看邮箱账号
think\Route::GET('/email-account/:id$', 'customerservice/EmailAccount/read',[], []);
//添加邮箱账号
think\Route::post('/email-account$', 'customerservice/EmailAccount/create',[], []);
//添加邮箱账号
think\Route::put('/email-account/:email_account_id$', 'customerservice/EmailAccount/update',[], []);
//删除指定邮箱账号
think\Route::Delete('/email-account$', 'customerservice/EmailAccount/delete',[], []);
//获取指定邮箱的log
think\Route::get('/email-account/log/:email_account_id$', 'customerservice/EmailAccount/getEmailAccountLog',[], []);
//设置邮箱是否启用
think\Route::put('/email-account/:email_account_id/enabled$', 'customerservice/EmailAccount/enableAccount',[], []);
//不过滤获取平台/站点账号简称
think\Route::get('/email-account/account$', 'customerservice/EmailAccount/account',[], []);

//控制器：app\customerservice\controller\EbaySentEmail
//查询Ebay邮件列表
think\Route::get('/ebay-emails/sent-list$', 'customerservice/EbaySentEmail/index',[], []);
//Ebay发送邮件
think\Route::post('/ebay-emails/send$', 'customerservice/EbaySentEmail/create',[], []);
//回复Ebay邮件
think\Route::post('/ebay-emails/reply$', 'customerservice/EbaySentEmail/replyEmail',[], []);
//Ebay失败邮件重新发送
think\Route::post('/ebay-emails/resend/:mail_id$', 'customerservice/EbaySentEmail/reSendMail',[], []);
//Ebay单号获取帐号邮箱
think\Route::GET('/ebay-emails/getBuyerInfo$', 'customerservice/EbaySentEmail/getBuyerInfo',[], []);
//获取单账号客服列表
think\Route::get('/ebay-emails/account/customers$', 'customerservice/EbaySentEmail/getAmazonAccountCustomerList',[], []);

//控制器：app\customerservice\controller\MessageTransfer
//站内信待处理列表
think\Route::GET('/message-transfer$', 'customerservice/MessageTransfer/index',[], []);
//帐号未处理信息条数；
think\Route::GET('/message-transfer/account-total$', 'customerservice/MessageTransfer/accountMessageTotal',[], []);
//转发站内信
think\Route::post('/message-transfer/transfer$', 'customerservice/MessageTransfer/transfer',[], []);
//转派记录
think\Route::get('/message-transfer/record$', 'customerservice/MessageTransfer/record',[], []);
//转派操作人
think\Route::get('/message-transfer/creator$', 'customerservice/MessageTransfer/creator',[], []);

//控制器：app\index\controller\LazadaAccount
//显示资源列表
think\Route::get('/lazada-account$', 'index/LazadaAccount/index',[], []);
//保存新建的资源
think\Route::post('/lazada-account$', 'index/LazadaAccount/save',[], []);
//显示指定的资源
think\Route::get('/lazada-account/:id$', 'index/LazadaAccount/read',[], ['id'=>'(\d+)']);
//显示编辑资源表单页.
think\Route::GET('/lazada-account/:id/edit$', 'index/LazadaAccount/edit',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/lazada-account/:id$', 'index/LazadaAccount/update',[], ['id'=>'(\d+)']);
//停用，启用账号
think\Route::post('/lazada-account/states$', 'index/LazadaAccount/changeStates',[], []);
//获取授权码
think\Route::post('/lazada-account/authorcode$', 'index/LazadaAccount/authorcode',[], []);
//查询lazada账号
think\Route::get('/lazada-account/query$', 'index/LazadaAccount/query',[], []);
//获取Token
think\Route::post('/lazada-account/token$', 'index/LazadaAccount/token',[], []);
//获取Token
think\Route::GET('/lazada-account/refresh_token/:id$', 'index/LazadaAccount/refresh_token',[], []);
//授权页面
think\Route::post('/lazada-account/authorization$', 'index/LazadaAccount/authorization',[], []);
//获取Lazada站点
think\Route::get('/lazada/site$', 'index/LazadaAccount/site',[], []);
//批量修改账号的抓取状态
think\Route::post('/lazada-account/update_download$', 'index/LazadaAccount/update_download',[], []);

//控制器：app\api\controller\AccountHealt
//wish接收帐号健康数据
think\Route::POST('/api/health-receive/wish/:id$', 'api/AccountHealt/wish',[], []);
//速卖通接收帐号健康数据
think\Route::POST('/api/health-receive/aliexpress/:id$', 'api/AccountHealt/aliexpress',[], []);
//ebay接收帐号健康数据
think\Route::POST('/api/health-receive/ebay/$', 'api/AccountHealt/ebay',[], []);
//amazon接收帐号健康数据
think\Route::POST('/api/health-receive/amazon/:id$', 'api/AccountHealt/amazon',[], []);

//控制器：app\index\controller\WishAccountHealth
//查看列表
think\Route::GET('/wish-account-health$', 'index/WishAccountHealth/index',[], []);
//帐号筛选列表
think\Route::GET('/wish-account-health/account$', 'index/WishAccountHealth/account',[], []);
//导出列表
think\Route::GET('/wish-account-health/export$', 'index/WishAccountHealth/export',[], []);
//查看历史数据；
think\Route::GET('/wish-account-health/:wish_account_id/history$', 'index/WishAccountHealth/history',[], []);
//查看付款记录；
think\Route::GET('/wish-account-health/:wish_account_id/payment$', 'index/WishAccountHealth/payment',[], []);
//批量设置监控值
think\Route::post('/wish-account-health$', 'index/WishAccountHealth/save',[], []);
//单个设置监控值
think\Route::PUT('/wish-account-health$', 'index/WishAccountHealth/editGoal',[], []);
//立即抓取
think\Route::POST('/wish-account-health/repitle$', 'index/WishAccountHealth/repitle',[], []);
//读取wish帐号目标率
think\Route::GET('/wish-account-health/:wish_account_id/goal$', 'index/WishAccountHealth/goal',[], []);

//控制器：app\index\controller\AliexpressAccountHealth
//查看列表
think\Route::GET('/aliexpress-account-health$', 'index/AliexpressAccountHealth/index',[], []);
//帐号筛选列表
think\Route::GET('/aliexpress-account-health/account$', 'index/AliexpressAccountHealth/account',[], []);
//导出列表
think\Route::GET('/aliexpress-account-health/export$', 'index/AliexpressAccountHealth/export',[], []);
//查看历史数据；
think\Route::GET('/aliexpress-account-health/:account_id/history$', 'index/AliexpressAccountHealth/history',[], []);
//查看付款记录；
think\Route::GET('/aliexpress-account-health/:account_id/:type/payment$', 'index/AliexpressAccountHealth/payment',[], []);
//批量设置监控值
think\Route::post('/aliexpress-account-health$', 'index/AliexpressAccountHealth/save',[], []);
//单个设置监控值
think\Route::PUT('/aliexpress-account-health$', 'index/AliexpressAccountHealth/editGoal',[], []);
//立即抓取
think\Route::POST('/aliexpress-account-health/repitle$', 'index/AliexpressAccountHealth/repitle',[], []);
//读取aliexpress帐号目标率
think\Route::GET('/aliexpress-account-health/:account_id/goal$', 'index/AliexpressAccountHealth/goal',[], []);

//控制器：app\warehouse\controller\WarehouseGoodsForecast
//产品预报列表
think\Route::get('/warehouse-goods-forecast$', 'warehouse/WarehouseGoodsForecast/index',[], []);
//新增产品预报
think\Route::post('/warehouse-goods-forecast$', 'warehouse/WarehouseGoodsForecast/save',[], []);
//分区详情
think\Route::get('/warehouse-goods-forecast/:id$', 'warehouse/WarehouseGoodsForecast/read',[], ['id'=>'(\d+)']);
//第三方产品分类
think\Route::get('/warehouse-goods-forecast/category$', 'warehouse/WarehouseGoodsForecast/category',[], []);
//预报状态
think\Route::get('/warehouse-goods-forecast/status$', 'warehouse/WarehouseGoodsForecast/status',[], []);
//选择添加sku
think\Route::get('/warehouse-goods-forecast/get-goods$', 'warehouse/WarehouseGoodsForecast/getGoods',[], []);
//关联
think\Route::post('/warehouse-goods-forecast/relate-sku$', 'warehouse/WarehouseGoodsForecast/relate',[], []);

//控制器：app\order\controller\JoomOrder
//订单列表
think\Route::GET('/joom-orders$', 'order/JoomOrder/index',[], []);
//查看
think\Route::GET('/joom-orders/:id$', 'order/JoomOrder/read',[], ['id'=>'(\d+)']);
//取订单各状态的总数
think\Route::GET('/joom-orders/status-count$', 'order/JoomOrder/statusCount',[], []);
//取账户
think\Route::GET('/joom-orders/accounts$', 'order/JoomOrder/accounts',[], []);
//取店铺列表
think\Route::POST('/joom-orders/stores$', 'order/JoomOrder/stores',[], []);
//获取账号列表
think\Route::POST('/joom-orders/account-names$', 'order/JoomOrder/accountName',[], []);
//检查订单是否存在
think\Route::post('/joom-orders/check$', 'order/JoomOrder/check',[], []);
//joom订单导出
think\Route::GET('/joom-orders/export$', 'order/JoomOrder/export',[], []);

//控制器：app\order\controller\PandaoOrder
//订单列表
think\Route::GET('/pandao-orders$', 'order/PandaoOrder/index',[], []);
//查看
think\Route::GET('/pandao-orders/:id$', 'order/PandaoOrder/read',[], ['id'=>'(\d+)']);
//取订单各状态的总数
think\Route::GET('/pandao-orders/status-count$', 'order/PandaoOrder/statusCount',[], []);
//取账户
think\Route::GET('/pandao-orders/accounts$', 'order/PandaoOrder/accounts',[], []);
//检查订单是否存在
think\Route::post('/pandao-orders/check$', 'order/PandaoOrder/check',[], []);
//mymall订单导出
think\Route::Post('/pandao-orders/export$', 'order/PandaoOrder/export',[], []);
//获取所有导出字段
think\Route::get('/pandao-orders/export-fields$', 'order/PandaoOrder/getExportFields',[], []);

//控制器：app\order\controller\PaytmOrder
//订单列表
think\Route::GET('/paytm-orders$', 'order/PaytmOrder/index',[], []);
//查看
think\Route::GET('/paytm-orders/:id$', 'order/PaytmOrder/read',[], ['id'=>'(\d+)']);
//取订单各状态的总数
think\Route::GET('/paytm-orders/status-count$', 'order/PaytmOrder/statusCount',[], []);
//取账户
think\Route::GET('/paytm-orders/accounts$', 'order/PaytmOrder/accounts',[], []);
//检查订单是否存在
think\Route::post('/paytm-orders/check$', 'order/PaytmOrder/check',[], []);

//控制器：app\order\controller\Shopee
//Shopee列表
think\Route::get('/shopee-order$', 'order/Shopee/index',[], []);
//Shopee获取数量
think\Route::get('/shopee-order/get-count$', 'order/Shopee/getCount',[], []);
//Shopee详情
think\Route::get('/shopee-order/:id$', 'order/Shopee/read',[], ['id'=>'(\d+)']);
//下载订单
think\Route::get('/shopee-order/ref-one$', 'order/Shopee/refOne',[], []);
//检测shopee单号是否已经存在
think\Route::post('/shopee-order/check-order-sn$', 'order/Shopee/checkOrderSn',[], []);
//shopee测试推送到系统订单
think\Route::get('/shopee-order/self-push$', 'order/Shopee/selfHelpPush',[], []);

//控制器：app\order\controller\Lazada
//显示资源列表
think\Route::get('/lazada-orders$', 'order/Lazada/index',[], []);
//显示指定的资源
think\Route::get('/lazada-orders/:id$', 'order/Lazada/read',[], ['id'=>'(\w+)']);
//获取状态列表
think\Route::get('/lazada-orders/status$', 'order/Lazada/status',[], []);
//审核平台订单
think\Route::post('/lazada-orders/check$', 'order/Lazada/check',[], []);
// 单独漏掉的lazada订单
think\Route::get('/lazada-orders/getOneOrder$', 'order/Lazada/getOneOrder',[], []);
// 按账号单独拉取漏掉的lazada订单
think\Route::post('/lazada-orders/getAllOrder$', 'order/Lazada/getAllOrder',[], []);
// 单独漏掉的lazada订单
think\Route::get('/lazada-orders/toLocal$', 'order/Lazada/toLocal',[], []);
// 删除lazada订单
think\Route::get('/lazada-orders/delete-id$', 'order/Lazada/deleteId',[], []);
//lazada订单导出
think\Route::Post('/lazada-orders/export$', 'order/Lazada/export',[], []);
//获取所有导出字段
think\Route::get('/lazada-orders/export-fields$', 'order/Lazada/getExportFields',[], []);

//控制器：app\index\controller\PaytmAccount
//paytm账号列表
think\Route::GET('/paytm-account$', 'index/PaytmAccount/index',[], []);
//添加账号
think\Route::POST('/paytm-account$', 'index/PaytmAccount/add',[], []);
//更新账号
think\Route::PUT('/paytm-account$', 'index/PaytmAccount/update',[], []);
//查看账号
think\Route::GET('/paytm-account/:id$', 'index/PaytmAccount/read',[], []);
//获取订单授权信息
think\Route::GET('/paytm-account/token/:id$', 'index/PaytmAccount/getToken',[], []);
// paytm订单账号授权
think\Route::PUT('/paytm-account/token$', 'index/PaytmAccount/updaeToken',[], []);
// paytm商品账号授权
think\Route::PUT('/paytm-account/tokencat$', 'index/PaytmAccount/updaeTokenCat',[], []);
//停用，启用账号
think\Route::post('/paytm-account/states$', 'index/PaytmAccount/changeStatus',[], []);
//批量开启
think\Route::post('/paytm-account/batch-set$', 'index/PaytmAccount/batchSet',[], []);

//控制器：app\publish\controller\Shopee
//shopee批量修改
think\Route::post('/shopee-batch-setting$', 'publish/Shopee/batchSetting',[], []);
//shopee编辑折扣折扣
think\Route::GET('/shopee-discount-edit$', 'publish/Shopee/editDiscount',[], []);
//shopee添加折扣
think\Route::post('/shopee-discount-add$', 'publish/Shopee/addDiscount',[], []);
//shopee折扣列表
think\Route::get('/shopee-discount$', 'publish/Shopee/discount',[], []);
//shopee刊登记录提交刊登
think\Route::post('/publish/shopee/push-queue$', 'publish/Shopee/pushQueue',[], []);
//shopee操作日志
think\Route::GET('/publish/shopee/logs$', 'publish/Shopee/logs',[], []);
//shopee同步listing
think\Route::POST('/publish/shopee/rsync-product$', 'publish/Shopee/rsyncProduct',[], []);
//shopee批量下架
think\Route::PUT('/shopee/del-item/batch$', 'publish/Shopee/delItem',[], []);
//shopee批量上架
think\Route::POST('/publish/shopee/batch-enable$', 'publish/Shopee/batchEnable',[], []);
//shopee删除刊登记录
think\Route::delete('/publish/shopee/delete$', 'publish/Shopee/delete',[], []);
//shopee编辑修改获取数据
think\Route::get('/shopee/:id/:status$', 'publish/Shopee/edit',[], ['id'=>'(\d+)', 'status'=>'(\w+)']);
//shopee更新修改了的数据
think\Route::post('/publish/shopee/update$', 'publish/Shopee/update',[], []);
//shopee获取商品数据
think\Route::get('/publish/shopee/getdata$', 'publish/Shopee/getData',[], []);
//shopee新增刊登
think\Route::post('/publish/shopee/add$', 'publish/Shopee/add',[], []);
//shopee销售人员列表
think\Route::GET('/shopee-sellers$', 'publish/Shopee/sellers',[], []);
//shopee当前登录用户管理账号
think\Route::GET('/publish/shopee/accounts$', 'publish/Shopee/getAccounts',[], []);
//shopee在售listing
think\Route::GET('/shopee-on-selling$', 'publish/Shopee/index',[], []);
//shopee停售listing
think\Route::GET('/shopee-stop-selling$', 'publish/Shopee/stopSelling',[], []);
//shopee停售listing
think\Route::GET('/shopee-sold-out$', 'publish/Shopee/soldOut',[], []);
//shopee刊登记录
think\Route::GET('/shopee-publish-record$', 'publish/Shopee/records',[], []);
//shopee待刊登商品列表
think\Route::GET('/publish/shopee/wait-upload$', 'publish/Shopee/waitUpload',[], []);
//shopee分类
think\Route::GET('/publish/shopee/category$', 'publish/Shopee/category',[], []);
//shopee分类属性
think\Route::GET('/publish/shopee/attribute$', 'publish/Shopee/attribute',[], []);
//shopee物流信息
think\Route::GET('/publish/shopee/logistics$', 'publish/Shopee/logistics',[], []);
//同步账号物流设置
think\Route::PUT('/shopee/:account_id/sync-logistics$', 'publish/Shopee/syncAccountLogistics',[], ['account_id'=>'(\d+)']);

//控制器：app\order\controller\WalmartOrder
//订单列表
think\Route::GET('/walmart-orders$', 'order/WalmartOrder/index',[], []);
//查看
think\Route::GET('/walmart-orders/:id$', 'order/WalmartOrder/read',[], ['id'=>'(\d+)']);
//取订单各状态的总数
think\Route::GET('/walmart-orders/status-count$', 'order/WalmartOrder/statusCount',[], []);
//检查订单是否存在
think\Route::post('/walmart-orders/check$', 'order/WalmartOrder/check',[], []);

//控制器：app\index\controller\WalmartAccount
//walmart账号列表
think\Route::GET('/walmart-account$', 'index/WalmartAccount/index',[], []);
//添加账号
think\Route::POST('/walmart-account$', 'index/WalmartAccount/add',[], []);
//更新账号
think\Route::PUT('/walmart-account$', 'index/WalmartAccount/update',[], []);
//查看账号
think\Route::GET('/walmart-account/:id$', 'index/WalmartAccount/read',[], []);
//获取walmart站点
think\Route::get('/walmart/site$', 'index/WalmartAccount/site',[], []);
//获取订单授权信息
think\Route::GET('/walmart-account/token/:id$', 'index/WalmartAccount/getToken',[], []);
// walmart订单账号授权
think\Route::PUT('/walmart-account/token$', 'index/WalmartAccount/updaeToken',[], []);
//停用，启用账号
think\Route::post('/walmart-account/states$', 'index/WalmartAccount/changeStatus',[], []);
//批量开启
think\Route::post('/walmart-account/batch-set$', 'index/WalmartAccount/batchSet',[], []);

//控制器：app\publish\controller\AmazonPublishDraft
//amazon刊登草稿箱列表；
think\Route::GET('/publish/amazon/draft$', 'publish/AmazonPublishDraft/index',[], []);
//amazon刊登草稿去编辑；
think\Route::GET('/publish/amazon/:id/draft$', 'publish/AmazonPublishDraft/edit',[], []);
//amazon刊登草稿保存；
think\Route::POST('/publish/amazon/draft$', 'publish/AmazonPublishDraft/save',[], []);
//amazon刊登草稿更新；
think\Route::PUT('/publish/amazon/draft$', 'publish/AmazonPublishDraft/update',[], []);
//amazon刊登草稿删除；
think\Route::DELETE('/publish/amazon/draft$', 'publish/AmazonPublishDraft/delete',[], []);

//控制器：app\publish\controller\Export
//SPU在各个平台的已刊登数量报表导出
think\Route::post('/publish-time-statistic-export$', 'publish/Export/statisticPublishTimeExport',[], []);
//SPU在各个平台的已刊登数量
think\Route::GET('/publish-time-statistic$', 'publish/Export/statisticPublishTime',[], []);
//刊登报表导出
think\Route::post('/publish-statistic-export$', 'publish/Export/statisticExport',[], []);
//spu刊登统计
think\Route::GET('/publish-statistic$', 'publish/Export/statistic',[], []);
//刊登部分导出
think\Route::post('/publish-export$', 'publish/Export/download',[], []);
//刊登全部导出
think\Route::post('/publish-export-all$', 'publish/Export/downloadAll',[], []);
//刊登报表导出字段
think\Route::get('/publish-export-fields$', 'publish/Export/fields',[], []);

//控制器：app\warehouse\controller\StockLack
//缺货列表
think\Route::get('/stock-lack$', 'warehouse/StockLack/index',[], []);
//导出
think\Route::post('/stock-lack/export$', 'warehouse/StockLack/export',[], []);

//控制器：app\order\controller\CheckOrder
//Excel检查订单
think\Route::POST('/check-orders/:type$', 'order/CheckOrder/check',[], []);

//控制器：app\index\controller\JumiaAccount
//jumia账号列表
think\Route::GET('/jumia-account$', 'index/JumiaAccount/index',[], []);
//添加账号
think\Route::POST('/jumia-account$', 'index/JumiaAccount/add',[], []);
//更新账号
think\Route::PUT('/jumia-account$', 'index/JumiaAccount/update',[], []);
//保存授权信息
think\Route::put('/jumia-account/save-token$', 'index/JumiaAccount/saveToken',[], []);
//查看账号
think\Route::GET('/jumia-account/:id$', 'index/JumiaAccount/read',[], []);
//停用，启用账号
think\Route::post('/jumia-account/states$', 'index/JumiaAccount/changeStatus',[], []);
//批量开启
think\Route::post('/jumia-account/batch-set$', 'index/JumiaAccount/batchSet',[], []);

//控制器：app\order\controller\JumiaOrder
//订单列表
think\Route::GET('/jumia-orders$', 'order/JumiaOrder/index',[], []);
//查看
think\Route::GET('/jumia-orders/:id$', 'order/JumiaOrder/read',[], ['id'=>'(\d+)']);
//取订单各状态的总数
think\Route::GET('/jumia-orders/status-count$', 'order/JumiaOrder/statusCount',[], []);
//检查订单是否存在
think\Route::post('/jumia-orders/check$', 'order/JumiaOrder/check',[], []);

//控制器：app\goods\controller\Download
//导出商品到shopee平台
think\Route::POST('/goods/download/shopee$', 'goods/Download/index',[], []);
//导出商品到discount平台
think\Route::POST('/goods/download/discount$', 'goods/Download/discount',[], []);
//导出商品到walmart平台
think\Route::POST('/goods/download/walmart$', 'goods/Download/walmart',[], []);
//导出商品到lazada平台
think\Route::POST('/goods/download/lazada$', 'goods/Download/lazada',[], []);

//控制器：app\index\controller\CdAccount
//cd账号列表
think\Route::GET('/cd-account$', 'index/CdAccount/index',[], []);
//添加账号
think\Route::POST('/cd-account$', 'index/CdAccount/add',[], []);
//更新账号
think\Route::PUT('/cd-account$', 'index/CdAccount/update',[], []);
//查看账号
think\Route::GET('/cd-account/:id$', 'index/CdAccount/read',[], []);
//获取订单授权信息
think\Route::GET('/cd-account/token/:id$', 'index/CdAccount/getToken',[], []);
// cd订单账号授权
think\Route::PUT('/cd-account/token$', 'index/CdAccount/updaeToken',[], []);
//停用，启用账号
think\Route::post('/cd-account/states$', 'index/CdAccount/changeStatus',[], []);
//验证账号
think\Route::post('/cd-account/check$', 'index/CdAccount/check',[], []);
//批量开启
think\Route::post('/cd-account/batch-set$', 'index/CdAccount/batchSet',[], []);

//控制器：app\order\controller\CdOrder
//订单列表
think\Route::GET('/cd-orders$', 'order/CdOrder/index',[], []);
//查看
think\Route::GET('/cd-orders/:id$', 'order/CdOrder/read',[], ['id'=>'(\d+)']);
//取订单各状态的总数
think\Route::GET('/cd-orders/status-count$', 'order/CdOrder/statusCount',[], []);
//取账户
think\Route::GET('/cd-orders/accounts$', 'order/CdOrder/accounts',[], []);
//检查订单是否存在
think\Route::post('/cd-orders/check$', 'order/CdOrder/check',[], []);

//控制器：app\index\controller\EbayAccountHealth
//查看列表
think\Route::GET('/ebay-account-health$', 'index/EbayAccountHealth/getLists',[], []);
//获取指定设置
think\Route::GET('/ebay-account-health/setting/:account_id/:region$', 'index/EbayAccountHealth/getAccountHealthSetting',[], []);
//设置监测阈值
think\Route::POST('/ebay-account-health/setting/batch$', 'index/EbayAccountHealth/setAccountHealthSetting',[], []);
//立即执行一次抓取
think\Route::POST('/ebay-account-health/sync/batch$', 'index/EbayAccountHealth/syncImmediately',[], []);
//导出数据
think\Route::GET('/ebay-account-health/export$', 'index/EbayAccountHealth/export',[], []);
//获取有权限的账号
think\Route::GET('/ebay-account-health/accounts$', 'index/EbayAccountHealth/getEbayHealthAccount',[], []);

//控制器：app\report\controller\ExpressConfirm
//快递确认单列表
think\Route::get('/report/express-confirm$', 'report/ExpressConfirm/index',[], []);
//execl字段信息
think\Route::get('/report/express-confirm/export-title$', 'report/ExpressConfirm/title',[], []);
//导出
think\Route::post('/report/express-confirm/export$', 'report/ExpressConfirm/export',[], []);
//汇总导出
think\Route::post('/report/express-confirm/exports$', 'report/ExpressConfirm/exports',[], []);

//控制器：app\index\controller\AccountApply
//显示资源列表
think\Route::get('/account-apply$', 'index/AccountApply/index',[], []);
//保存新建的资源
think\Route::post('/account-apply$', 'index/AccountApply/save',[], []);
//显示指定的资源
think\Route::get('/account-apply/:id$', 'index/AccountApply/read',[], ['id'=>'(\d+)']);
//显示编辑资源表单页.
think\Route::GET('/account-apply/:id/edit$', 'index/AccountApply/edit',[], ['id'=>'(\d+)']);
//保存更新[基本资料]
think\Route::PUT('/account-apply/:id$', 'index/AccountApply/update',[], ['id'=>'(\d+)']);
//保存更新[注册信息]
think\Route::put('/account-apply/:id/register$', 'index/AccountApply/updateRegister',[], []);
//保存更新[审核]
think\Route::put('/account-apply/:id/audit$', 'index/AccountApply/updateAudit',[], []);
//保存更新[作废]
think\Route::put('/account-apply/:id/cancellation$', 'index/AccountApply/cancellation',[], []);
//保存更新[注册结果]
think\Route::put('/account-apply/:id/result$', 'index/AccountApply/updateResult',[], []);
//更改账号状态
think\Route::post('/account-apply/batch/:type$', 'index/AccountApply/batch',[], []);
//显示密码
think\Route::get('/account-apply/password$', 'index/AccountApply/show',[], []);
//服务器已绑定的账号列表
think\Route::get('/account-apply/already-bind$', 'index/AccountApply/alreadyBind',[], []);
//自动识别图片
think\Route::get('/account-apply/automatic$', 'index/AccountApply/automatic',[], []);
//日志
think\Route::get('/account-apply/:id/log$', 'index/AccountApply/log',[], []);
//读取运营负责人
think\Route::get('/account-apply/user$', 'index/AccountApply/getUser',[], []);
//获取状态
think\Route::get('/account-apply/status$', 'index/AccountApply/status',[], []);

//控制器：app\publish\controller\EbayCtrl
//获取推荐的分类
think\Route::GET('/ebay/suggested-categories$', 'publish/EbayCtrl/getSuggestedCategories',[], []);
//获取范本/listing店铺分类
think\Route::GET('/ebay/dl-store-category/batch$', 'publish/EbayCtrl/getDLStoreCategory',[], []);
//获取指定账号指定店铺分类的分类链
think\Route::GET('/ebay/store-category-chain/:store_category_id/:account_id$', 'publish/EbayCtrl/getStoreCategoryChain',[], []);
//更新listing店铺分类
think\Route::POST('/ebay/listing-store-category/batch$', 'publish/EbayCtrl/updateListingStoreCategory',[], []);
//批量修改范本店铺分类
think\Route::PUT('/ebay/draft-store-category/batch$', 'publish/EbayCtrl/changeDraftStoreCategory',[], []);
//批量获取listing/范本主图
think\Route::GET('/ebay/dl-main-imgs/batch$', 'publish/EbayCtrl/getDLMainImgs',[], []);
//批量在线更新listing主图
think\Route::POST('/ebay/listing-main-imgs/batch$', 'publish/EbayCtrl/updateListingMainImgs',[], []);
//批量切换站点设置账号
think\Route::POST('/ebay/change-site/batch$', 'publish/EbayCtrl/changeSite',[], []);
//批量将listing成本价更改为调整后的价格
think\Route::PUT('/ebay/adjust-price/batch$', 'publish/EbayCtrl/costPriceToAdjustedPrice',[], []);
//批量修改范本拍卖刊登天数
think\Route::PUT('/ebay/d-chinese-listing-duration/batch$', 'publish/EbayCtrl/DChineseListingDuration',[], []);
//翻译
think\Route::POST('/ebay/translate/batch$', 'publish/EbayCtrl/translate',[], []);
//获取标题库列表
think\Route::get('/publish/ebay/titles$', 'publish/EbayCtrl/titles',[], []);
//获取指定商品标题库详情
think\Route::get('/publish/ebay/titles/:goods_id$', 'publish/EbayCtrl/titleDetail',[], []);
//批量获取商品标题库详情
think\Route::get('/publish/ebay/titles/batch$', 'publish/EbayCtrl/titleDetails',[], []);
//保存单条标题库详情
think\Route::put('/publish/ebay/titles/:goods_id$', 'publish/EbayCtrl/saveTitleDetail',[], []);
//批量保存商品标题库详情
think\Route::put('/publish/ebay/titles/batch$', 'publish/EbayCtrl/saveTitleDetails',[], []);
//对范本标题随机排序
think\Route::put('/publish/ebay/draft-title/random$', 'publish/EbayCtrl/randomDraftTitle',[], []);
//复制listing并更改账号
think\Route::post('/publish/ebay/copy-listing$', 'publish/EbayCtrl/cpListings',[], []);
//批量检测刊登
think\Route::post('/publish-ebay/check-publish/batch$', 'publish/EbayCtrl/checkPublishFee',[], []);
//批量删除
think\Route::delete('/publish-ebay/delete-listing/batch$', 'publish/EbayCtrl/delListings',[], []);
//一键展开变体
think\Route::get('/publish-ebay/spread-variants/batch$', 'publish/EbayCtrl/spreadVariants',[], []);
//队列刊登
think\Route::post('/publish-ebay/publish-queue/batch$', 'publish/EbayCtrl/addPublishQueue',[], []);
//批量设置账号
think\Route::post('/publish-ebay/listing-account/batch$', 'publish/EbayCtrl/setAccount',[], []);
//批量修改一口价及可售量
think\Route::post('/publish-ebay/fixed-price-qty/batch$', 'publish/EbayCtrl/setFixedPriceQty',[], []);
//批量修改拍卖价
think\Route::post('/publish-ebay/chinese-price/batch$', 'publish/EbayCtrl/setChinesePrice',[], []);
//批量修改标题
think\Route::post('/publish-ebay/listing-title/batch$', 'publish/EbayCtrl/setTitle',[], []);
//批量修改商店分类
think\Route::post('/publish-ebay/listing-store-category/batch$', 'publish/EbayCtrl/setStoreCategory',[], []);
//批量获取刊登图
think\Route::get('/publish-ebay/publish-imgs/batch$', 'publish/EbayCtrl/getPublishImgs',[], []);
//批量设置刊登图
think\Route::post('/publish-ebay/publish-imgs/batch$', 'publish/EbayCtrl/setPublishImgs',[], []);
//批量设置平台分类属性
think\Route::post('/publish-ebay/specifics/batch$', 'publish/EbayCtrl/setSpecifics',[], []);
//批量设置一口价刊登天数
think\Route::post('/publish-ebay/listing-duration/batch$', 'publish/EbayCtrl/setListingDuration',[], []);
//批量应用公共模块
think\Route::post('/publish-ebay/apply-common-module/batch$', 'publish/EbayCtrl/applyCommonModule',[], []);
//立即刊登保存
think\Route::post('/publish-ebay/publish-immediately-save$', 'publish/EbayCtrl/publishImmediatelySave',[], []);
//立即刊登
think\Route::post('/publish-ebay/publish-immediately$', 'publish/EbayCtrl/publishImmediately',[], []);
//立即刊登结果查询
think\Route::get('/publish-ebay/publish-immediately-result$', 'publish/EbayCtrl/publishImmediatelyResult',[], []);
//批量设置自动补货
think\Route::post('/publish-ebay/replenish/batch$', 'publish/EbayCtrl/replenish',[], []);
//获取标题库关键词库
think\Route::get('/title/suggest-word$', 'publish/EbayCtrl/getSuggestWord',[], []);
//通过导入方式在线更新listing
think\Route::post('/publish-ebay/update-listing/import$', 'publish/EbayCtrl/updateListingImport',[], []);
//拉取指定item id的listing
think\Route::post('/publish-ebay/pull-listing$', 'publish/EbayCtrl/pullListingByItemId',[], []);
//设置虚拟仓发货
think\Route::post('/publish-ebay/virtual-send$', 'publish/EbayCtrl/setIsVirtualSend',[], []);
//在线listing数据导出
think\Route::get('/publish-ebay/online-export$', 'publish/EbayCtrl/onlineExport',[], []);
//取消定时或队列刊登
think\Route::post('/publish-ebay/cancel-queue-publish$', 'publish/EbayCtrl/cancelQueuePublish',[], []);
//修改在线数据导出
think\Route::get('/publish-ebay/online-export-modify$', 'publish/EbayCtrl/onlineExportModify',[], []);
//获取范本信息
think\Route::get('/publish-ebay/draft$', 'publish/EbayCtrl/getSiteDraftInfo',[], []);
//设置范本
think\Route::post('/publish-ebay/draft$', 'publish/EbayCtrl/setDraft',[], []);
//范本列表
think\Route::get('/publish-ebay/drafts$', 'publish/EbayCtrl/drafts',[], []);
//ebay测试
think\Route::POST('/ebay/test$', 'publish/EbayCtrl/test',[], []);
//复制范本转站点账号
think\Route::POST('/publish-ebay/change-site-from-draft/batch$', 'publish/EbayCtrl/changeSiteFromDraft',[], []);
//在线spu统计导出
think\Route::get('/publish-ebay/online-spu/export$', 'publish/EbayCtrl/onlineSpuStatisticExport',[], []);

//控制器：app\publish\controller\AmazonPublishDoc
//amazon范本列表；
think\Route::GET('/publish/amazon/doc$', 'publish/AmazonPublishDoc/index',[], []);
//amazon未写范本列表；
think\Route::GET('/publish/amazon/undoc$', 'publish/AmazonPublishDoc/undoc',[], []);
//amazon范本创建人；
think\Route::GET('/publish/amazon/doc-creator$', 'publish/AmazonPublishDoc/creator',[], []);
//amazon范本删除；
think\Route::GET('/publish/amazon/doc-del$', 'publish/AmazonPublishDoc/delete',[], []);
//amazon范本新增编辑基础；
think\Route::GET('/publish/amazon/doc-base-field$', 'publish/AmazonPublishDoc/baseField',[], []);
//amazon范本新增；
think\Route::GET('/publish/amazon/doc-site-field$', 'publish/AmazonPublishDoc/getField',[], []);
//amazon范本编辑和复制；
think\Route::GET('/publish/amazon/doc-edit-field$', 'publish/AmazonPublishDoc/editField',[], []);
//amazon范本保存；
think\Route::POST('/publish/amazon/doc-save$', 'publish/AmazonPublishDoc/save',[], []);

//控制器：app\progress\controller\Progress
//需求管理首页
think\Route::GET('/progress$', 'progress/Progress/index',[], []);
//新增需求
think\Route::POST('/progress/add$', 'progress/Progress/add',[], []);
//更新需求
think\Route::POST('/progress-update$', 'progress/Progress/update',[], []);
//更新需求状态
think\Route::POST('/progress/update-status$', 'progress/Progress/updateStatus',[], []);
//需求删除
think\Route::DELETE('/progress-delete$', 'progress/Progress/delete',[], []);
//需求管理获取用户角色
think\Route::GET('/progress-permission$', 'progress/Progress/permission',[], []);

//控制器：app\warehouse\controller\PickingException
//拣货异常列表
think\Route::get('/pickings-exception/exception$', 'warehouse/PickingException/exception',[], []);
//拣货异常详情
think\Route::get('/pickings-exception/exception-detail$', 'warehouse/PickingException/exceptionDetail',[], []);
//异常拣货批量处理
think\Route::post('/pickings-exception/batch-processing$', 'warehouse/PickingException/batchProcessing',[], []);
//异常拣货单sku 创建盘点单
think\Route::post('/pickings-exception/goods-check$', 'warehouse/PickingException/goodsCheck',[], []);

//控制器：app\warehouse\controller\Collector
//揽收商列表
think\Route::get('/collector$', 'warehouse/Collector/index',[], []);
//添加物流商信息
think\Route::post('/collector$', 'warehouse/Collector/save',[], []);
//显示指定物流商资源
think\Route::get('/collector/:id$', 'warehouse/Collector/read',[], ['id'=>'(\d+)']);
//保存更新的分区
think\Route::PUT('/collector/:id$', 'warehouse/Collector/update',[], ['id'=>'(\d+)']);
//状态更新
think\Route::put('/collector/:id/status$', 'warehouse/Collector/changeStatus',[], ['id'=>'(\d+)']);
//邮寄方式列表
think\Route::get('/collector/:id/shipping-lists$', 'warehouse/Collector/shippingList',[], ['id'=>'(\d+)']);
//邮寄方式列表
think\Route::get('/collector/list$', 'warehouse/Collector/lists',[], []);

//控制器：app\index\controller\VirtualUser
//显示资源列表
think\Route::get('/virtual-user$', 'index/VirtualUser/index',[], []);
//登录
think\Route::post('/virtual-user/login$', 'index/VirtualUser/login',[], []);
//退出
think\Route::post('/virtual-user/quit$', 'index/VirtualUser/quit',[], []);
//获取登录信息
think\Route::get('/virtual-user/info$', 'index/VirtualUser/info',[], []);
//获取国家信息
think\Route::get('/virtual-user/country$', 'index/VirtualUser/country',[], []);
//获取平台信息
think\Route::get('/virtual-user/channel$', 'index/VirtualUser/channel',[], []);
//获取验证码
think\Route::get('/virtual-user/code$', 'index/VirtualUser/captcha',[], []);
//注册
think\Route::POST('/virtual-user/register$', 'index/VirtualUser/register',[], []);
//刷单任务列表
think\Route::get('/virtual-user/list$', 'index/VirtualUser/missionList',[], []);
//刷单任务状态列表
think\Route::get('/virtual-user/status$', 'index/VirtualUser/missionStatus',[], []);
//刷单任务处理
think\Route::post('/virtual-user/dispose$', 'index/VirtualUser/dispose',[], []);
//刷单任务回评
think\Route::post('/virtual-user/review$', 'index/VirtualUser/review',[], []);
//用户详细信息
think\Route::get('/virtual-user/user-info$', 'index/VirtualUser/userInfo',[], []);
//更新用户密码
think\Route::post('/virtual-user/user-save$', 'index/VirtualUser/userSave',[], []);
//更新用户信息
think\Route::POST('/virtual-user/editor$', 'index/VirtualUser/editor',[], []);
//货币类型
think\Route::get('/virtual-user/currency$', 'index/VirtualUser/currency',[], []);
//关于我们
think\Route::get('/virtual-user/about-us$', 'index/VirtualUser/aboutUs',[], []);

//控制器：app\index\controller\ServerNetwork
//服务器使用记录
think\Route::get('/server-network$', 'index/ServerNetwork/index',[], []);
//保存服务器信息
think\Route::post('/server-network$', 'index/ServerNetwork/save',[], []);

//控制器：app\order\controller\VirtualRule
//显示资源列表
think\Route::get('/virtual-rules$', 'order/VirtualRule/index',[], []);
//显示指定的资源
think\Route::get('/virtual-rules/:id$', 'order/VirtualRule/read',[], ['id'=>'(\d+)']);
//编辑指定的资源
think\Route::GET('/virtual-rules/:id/edit$', 'order/VirtualRule/edit',[], ['id'=>'(\d+)']);
//保存的资源
think\Route::post('/virtual-rules$', 'order/VirtualRule/save',[], []);
//保存更新的资源
think\Route::PUT('/virtual-rules/:id$', 'order/VirtualRule/update',[], ['id'=>'(\d+)']);
//删除指定资源
think\Route::DELETE('/virtual-rules/:id$', 'order/VirtualRule/delete',[], ['id'=>'(\d+)']);
//更改规则状态
think\Route::post('/virtual-rules/status$', 'order/VirtualRule/changeStatus',[], []);
//获取资源
think\Route::post('/virtual-rules/resources$', 'order/VirtualRule/resources',[], []);
//获取发货仓库信息
think\Route::get('/virtual-rules/warehouse$', 'order/VirtualRule/warehouse',[], []);
//获取运输方式
think\Route::get('/virtual-rules/shipping$', 'order/VirtualRule/shipping',[], []);
//获取订单自动处理方法
think\Route::get('/virtual-rules/action$', 'order/VirtualRule/getAction',[], []);
//保存排序值
think\Route::post('/virtual-rules/sort$', 'order/VirtualRule/changeSort',[], []);
//规则复制
think\Route::post('/virtual-rules/copy$', 'order/VirtualRule/virtualRuleCopy',[], []);
//规则日志
think\Route::get('/virtual-rules/:virtualRule_id/log$', 'order/VirtualRule/log',[], []);
//拉取平台数据
think\Route::get('/virtual-rules/channel$', 'order/VirtualRule/channel',[], []);
//拉取创建人数据
think\Route::get('/virtual-rules/creator$', 'order/VirtualRule/creator',[], []);

//控制器：app\order\controller\VirtualRuleItem
//显示资源列表
think\Route::get('/virtual-rule-items$', 'order/VirtualRuleItem/index',[], []);

//控制器：app\index\controller\AmazonAccountHealth
//查看列表
think\Route::GET('/amazon-account-health$', 'index/AmazonAccountHealth/index',[], []);
//帐号筛选列表
think\Route::GET('/amazon-account-health/account$', 'index/AmazonAccountHealth/account',[], []);
//导出列表
think\Route::GET('/amazon-account-health/export$', 'index/AmazonAccountHealth/export',[], []);
//查看历史数据；
think\Route::GET('/amazon-account-health/:amazon_account_id/history$', 'index/AmazonAccountHealth/history',[], []);
//批量设置监控值
think\Route::post('/amazon-account-health$', 'index/AmazonAccountHealth/save',[], []);
//单个设置监控值
think\Route::PUT('/amazon-account-health$', 'index/AmazonAccountHealth/editGoal',[], []);
//立即抓取
think\Route::POST('/amazon-account-health/repitle$', 'index/AmazonAccountHealth/repitle',[], []);
//读取amazon帐号目标率
think\Route::GET('/amazon-account-health/:amazon_account_id/goal$', 'index/AmazonAccountHealth/goal',[], []);
//读取amazon帐余额统计
think\Route::GET('/amazon-account-health/balance$', 'index/AmazonAccountHealth/balance',[], []);
//读取amazon帐余额详情
think\Route::GET('/amazon-account-health/balance-details$', 'index/AmazonAccountHealth/balanceDetails',[], []);

//控制器：app\warehouse\controller\StockRule
//显示资源列表
think\Route::get('/stock-rules$', 'warehouse/StockRule/index',[], []);
//查看资源
think\Route::get('/stock-rules/:id$', 'warehouse/StockRule/read',[], ['id'=>'(\d+)']);
//显示编辑资源表单页
think\Route::GET('/stock-rules/:id/edit$', 'warehouse/StockRule/edit',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/stock-rules/:id$', 'warehouse/StockRule/update',[], ['id'=>'(\d+)']);
//保存资源
think\Route::post('/stock-rules$', 'warehouse/StockRule/save',[], []);
//保存默认规则
think\Route::post('/stock-rules/default$', 'warehouse/StockRule/saveDefaultRule',[], []);
//获取默认规则
think\Route::get('/stock-rules/default$', 'warehouse/StockRule/getDefaultRule',[], []);
//删除指定资源
think\Route::DELETE('/stock-rules/:id$', 'warehouse/StockRule/delete',[], ['id'=>'(\d+)']);
//更改规则状态
think\Route::post('/stock-rules/:id/status/:value$', 'warehouse/StockRule/status',[], ['id'=>'(\d+)', 'value'=>'(\d+)']);
//获取资源
think\Route::post('/stock-rules/resources$', 'warehouse/StockRule/resources',[], []);
//保存排序值
think\Route::post('/stock-rules/sort$', 'warehouse/StockRule/sort',[], []);
//获取审批人资源
think\Route::get('/stock-rules/approve_level$', 'warehouse/StockRule/getApproveLevel',[], []);

//控制器：app\warehouse\controller\StockRuleItem
//显示资源列表
think\Route::get('/stock-rules-items$', 'warehouse/StockRuleItem/index',[], []);

//控制器：app\warehouse\controller\AllocationBoxClass
//显示箱子列表
think\Route::get('/allocation-box$', 'warehouse/AllocationBoxClass/index',[], []);
//保存新建的箱子
think\Route::post('/allocation-box$', 'warehouse/AllocationBoxClass/save',[], []);
//显示指定的资源
think\Route::get('/allocation-box/:id$', 'warehouse/AllocationBoxClass/read',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/allocation-box/:id$', 'warehouse/AllocationBoxClass/update',[], ['id'=>'(\d+)']);
//删除指定箱子
think\Route::DELETE('/allocation-box/:id$', 'warehouse/AllocationBoxClass/delete',[], ['id'=>'(\d+)']);
//状态更新
think\Route::put('/allocation-box/:id/status/$', 'warehouse/AllocationBoxClass/changeStatus',[], ['id'=>'(\d+)']);

//控制器：app\report\controller\CustomerMessage
//列表详情
think\Route::get('/report/customer-message$', 'report/CustomerMessage/index',[], []);
//导出
think\Route::post('/report/customer-message/export$', 'report/CustomerMessage/applyExport',[], []);
//客服账号列表
think\Route::GET('/report/customer-message/customer$', 'report/CustomerMessage/getCustomer',[], []);

//控制器：app\index\controller\ZoodmallAccount
//zoodmall账号列表
think\Route::GET('/zoodmall-account$', 'index/ZoodmallAccount/index',[], []);
//添加账号
think\Route::POST('/zoodmall-account$', 'index/ZoodmallAccount/add',[], []);
//更新账号
think\Route::PUT('/zoodmall-account$', 'index/ZoodmallAccount/update',[], []);
//查看账号
think\Route::GET('/zoodmall-account/:id$', 'index/ZoodmallAccount/read',[], []);
//获取订单授权信息
think\Route::GET('/zoodmall-account/token/:id$', 'index/ZoodmallAccount/getToken',[], []);
// zoodmall订单账号授权
think\Route::PUT('/zoodmall-account/token$', 'index/ZoodmallAccount/updaeToken',[], []);
//停用，启用账号
think\Route::post('/zoodmall-account/states$', 'index/ZoodmallAccount/changeStatus',[], []);
//批量开启
think\Route::post('/zoodmall-account/batch-set$', 'index/ZoodmallAccount/batchSet',[], []);

//控制器：app\index\controller\VovaAccount
//vova账号列表
think\Route::GET('/vova-account$', 'index/VovaAccount/index',[], []);
//添加账号
think\Route::POST('/vova-account$', 'index/VovaAccount/add',[], []);
//更新账号
think\Route::PUT('/vova-account$', 'index/VovaAccount/update',[], []);
//查看账号
think\Route::GET('/vova-account/:id$', 'index/VovaAccount/read',[], []);
//获取订单授权信息
think\Route::GET('/vova-account/token/:id$', 'index/VovaAccount/getToken',[], []);
// vova订单账号授权
think\Route::PUT('/vova-account/token$', 'index/VovaAccount/updateToken',[], []);
//停用，启用账号
think\Route::post('/vova-account/states$', 'index/VovaAccount/changeStatus',[], []);
//批量开启
think\Route::post('/vova-account/batch-set$', 'index/VovaAccount/batchSet',[], []);

//控制器：app\order\controller\VovaOrder
//订单列表
think\Route::GET('/vova-orders$', 'order/VovaOrder/index',[], []);
//查看
think\Route::GET('/vova-orders/:id$', 'order/VovaOrder/read',[], ['id'=>'(\d+)']);
//取订单各状态的总数
think\Route::GET('/vova-orders/status-count$', 'order/VovaOrder/statusCount',[], []);
//检查订单是否存在
think\Route::post('/vova-orders/check$', 'order/VovaOrder/check',[], []);
// 单独漏掉的vova订单
think\Route::get('/vova-orders/getoneorder$', 'order/VovaOrder/getOneOrder',[], []);
// vova物流对应的carrier_id
think\Route::get('/vova-orders/get-press$', 'order/VovaOrder/getVovaPress',[], []);
//Vova订单导出
think\Route::Post('/vova-orders/export$', 'order/VovaOrder/export',[], []);
//获取所有导出字段
think\Route::get('/vova-orders/export-fields$', 'order/VovaOrder/getExportFields',[], []);

//控制器：app\index\controller\PddAccount
//显示资源列表
think\Route::get('/pdd-account$', 'index/PddAccount/index',[], []);
//添加账号
think\Route::POST('/pdd-account$', 'index/PddAccount/add',[], []);
//显示指定的资源
think\Route::GET('/pdd-account/:id$', 'index/PddAccount/read',[], []);
//更新账号
think\Route::PUT('/pdd-account$', 'index/PddAccount/update',[], []);
//停用，启用账号
think\Route::post('/pdd-account/states$', 'index/PddAccount/changeStatus',[], []);
//获取授权码
think\Route::post('/pdd-account/authorcode$', 'index/PddAccount/authorcode',[], []);
//查询pdd账号
think\Route::get('/pdd-account/query$', 'index/PddAccount/query',[], []);
//获取Token
think\Route::post('/pdd-account/token$', 'index/PddAccount/token',[], []);
//获取Token
think\Route::GET('/pdd-account/refresh_token/:id$', 'index/PddAccount/refresh_token',[], []);
//授权页面
think\Route::post('/pdd-account/authorization$', 'index/PddAccount/authorization',[], []);
//批量修改账号的抓取状态
think\Route::post('/pdd-account/update_download$', 'index/PddAccount/update_download',[], []);

//控制器：app\order\controller\PddOrder
//订单列表
think\Route::GET('/pdd-order$', 'order/PddOrder/index',[], []);
//查看
think\Route::GET('/pdd-order/:id$', 'order/PddOrder/read',[], ['id'=>'(\d+)']);
//订单状态
think\Route::GET('/pdd-order/status-count$', 'order/PddOrder/status',[], []);
//检查订单是否存在
think\Route::post('/pdd-order/check$', 'order/PddOrder/check',[], []);
// pdd 物流对应的carrier_id
think\Route::get('/getpddpress$', 'order/PddOrder/getPddPress',[], []);
// 按账号单独拉取漏掉的pdd订单
think\Route::post('/pdd-orders/getorders$', 'order/PddOrder/getAllOrder',[], []);

//控制器：app\index\controller\UmkaAccount
//显示资源列表
think\Route::get('/umka-account$', 'index/UmkaAccount/index',[], []);
//添加账号
think\Route::POST('/umka-account$', 'index/UmkaAccount/add',[], []);
//显示指定的资源
think\Route::GET('/umka-account/:id$', 'index/UmkaAccount/read',[], []);
//更新账号
think\Route::PUT('/umka-account$', 'index/UmkaAccount/update',[], []);
//停用，启用账号
think\Route::post('/umka-account/states$', 'index/UmkaAccount/changeStatus',[], []);
//查询umka账号
think\Route::get('/umka-account/query$', 'index/UmkaAccount/query',[], []);
//获取Token
think\Route::post('/umka-account/token$', 'index/UmkaAccount/token',[], []);
//获取Token
think\Route::GET('/umka-account/refresh_token/:id$', 'index/UmkaAccount/refresh_token',[], []);
//授权页面
think\Route::post('/umka-account/authorization$', 'index/UmkaAccount/authorization',[], []);
//批量修改账号的抓取状态
think\Route::post('/umka-account/update_download$', 'index/UmkaAccount/update_download',[], []);

//控制器：app\order\controller\UmkaOrder
//订单列表
think\Route::GET('/umka-order$', 'order/UmkaOrder/index',[], []);
//查看
think\Route::GET('/umka-order/:id$', 'order/UmkaOrder/read',[], ['id'=>'(\d+)']);
//订单状态
think\Route::GET('/umka-order/status-count$', 'order/UmkaOrder/status',[], []);
//检查订单是否存在
think\Route::post('/umka-order/check$', 'order/UmkaOrder/check',[], []);
// 按账号单独拉取漏掉的umka订单
think\Route::post('/umka-order/getorders$', 'order/UmkaOrder/getAllOrder',[], []);
// umka 物流对应的carrier_id
think\Route::get('/get-press$', 'order/UmkaOrder/getUmkaPress',[], []);

//控制器：app\order\controller\ZoodmallOrder
//订单列表
think\Route::GET('/zoodmall-orders$', 'order/ZoodmallOrder/index',[], []);
//查看
think\Route::GET('/zoodmall-orders/:id$', 'order/ZoodmallOrder/read',[], ['id'=>'(\d+)']);
//取订单各状态的总数
think\Route::GET('/zoodmall-orders/status-count$', 'order/ZoodmallOrder/statusCount',[], []);
//检查订单是否存在
think\Route::post('/zoodmall-orders/check$', 'order/ZoodmallOrder/check',[], []);

//控制器：app\purchase\controller\PurchaseParcelsBox
//创建卡板
think\Route::POST('/purchase-parcels-box$', 'purchase/PurchaseParcelsBox/createParcelsBox',[], []);
//结束卡板
think\Route::PUT('/purchase-parcels-box/:id$', 'purchase/PurchaseParcelsBox/finishParcelsBox',[], []);
//批量删除卡板
think\Route::DELETE('/purchase-parcels-box/batch$', 'purchase/PurchaseParcelsBox/deleteParcelsBox',[], []);
//卡板管理
think\Route::GET('/purchase-parcels-box$', 'purchase/PurchaseParcelsBox/getParcelsBoxes',[], []);
//卡板状态
think\Route::GET('/purchase-parcel-box/status$', 'purchase/PurchaseParcelsBox/getStatus',[], []);
//卡板详情
think\Route::GET('/purchase-parcels-box/:id/parcel$', 'purchase/PurchaseParcelsBox/getBoxDetail',[], []);
//卡板日志
think\Route::GET('/purchase-parcels-box/:id/log$', 'purchase/PurchaseParcelsBox/getBoxLog',[], []);
//批量打印
think\Route::POST('/purchase-parcels-box/batch/print$', 'purchase/PurchaseParcelsBox/batchPrintLabel',[], []);
//扫描卡板
think\Route::PUT('/purchase-parcels-box/:id/scanning$', 'purchase/PurchaseParcelsBox/scanningParcelsBox',[], []);
//批量打板完成
think\Route::PUT('/purchase-parcels-box/batch/finish$', 'purchase/PurchaseParcelsBox/batchChangeEndStatus',[], []);
//拆板负责人
think\Route::GET('/purchase-parcels-box/unpack-name$', 'purchase/PurchaseParcelsBox/unpackedNameList',[], []);
//批量拆板完成（修改状态为强制完成）
think\Route::PUT('/purchase-parcels-box/batch/force$', 'purchase/PurchaseParcelsBox/batchSetUnpackToForce',[], []);

//控制器：app\api\controller\YksPurchase
//有棵树公用api接口
think\Route::post('/api/yks/index$', 'api/YksPurchase/index',[], []);
//测试推送有棵树
think\Route::post('/api/yks/push-test123$', 'api/YksPurchase/pushTest',[], []);

//控制器：app\report\controller\PublishByPicking
//列表详情
think\Route::get('/report/publish-by-picking$', 'report/PublishByPicking/index',[], []);
//sup详情
think\Route::get('/report/publish-by-picking/sup$', 'report/PublishByPicking/sup',[], []);
//导出
think\Route::post('/report/publish-by-picking/export$', 'report/PublishByPicking/applyExport',[], []);

//控制器：app\report\controller\PublishByShelf
//列表详情
think\Route::get('/report/publish-by-shelf$', 'report/PublishByShelf/index',[], []);
//sup详情
think\Route::get('/report/publish-by-shelf/sup$', 'report/PublishByShelf/sup',[], []);
//导出
think\Route::post('/report/publish-by-shelf/export$', 'report/PublishByShelf/applyExport',[], []);
//获取spu刊登统计列表
think\Route::get('/report/publish-by-shelf/spu$', 'report/PublishByShelf/spuStatistic',[], []);
//获取刊登的账号刊登次数
think\Route::get('/report/publish-by-shelf/spu/account-detail$', 'report/PublishByShelf/getAccountDetail',[], []);

//控制器：app\report\controller\FirstOrderSkuList
//首次出单列表
think\Route::get('/first-order$', 'report/FirstOrderSkuList/index',[], []);
//execl字段信息
think\Route::get('/first-order/export-title$', 'report/FirstOrderSkuList/title',[], []);
//导出
think\Route::post('/first-order/export$', 'report/FirstOrderSkuList/export',[], []);

//控制器：app\index\controller\YandexAccount
//yandex账号列表
think\Route::GET('/yandex-account$', 'index/YandexAccount/index',[], []);
//添加账号
think\Route::POST('/yandex-account$', 'index/YandexAccount/add',[], []);
//更新账号
think\Route::PUT('/yandex-account$', 'index/YandexAccount/update',[], []);
//查看账号
think\Route::GET('/yandex-account/:id$', 'index/YandexAccount/read',[], []);
//获取订单授权信息
think\Route::GET('/yandex-account/token/:id$', 'index/YandexAccount/getToken',[], []);
// yandex订单账号授权
think\Route::PUT('/yandex-account/token$', 'index/YandexAccount/updaeToken',[], []);
//停用，启用账号
think\Route::post('/yandex-account/states$', 'index/YandexAccount/changeStatus',[], []);
//批量开启
think\Route::post('/yandex-account/batch-set$', 'index/YandexAccount/batchSet',[], []);

//控制器：app\order\controller\YandexOrder
//订单列表
think\Route::GET('/yandex-orders$', 'order/YandexOrder/index',[], []);
//查看
think\Route::GET('/yandex-orders/:id$', 'order/YandexOrder/read',[], ['id'=>'(\d+)']);
//取订单各状态的总数
think\Route::GET('/yandex-orders/status-count$', 'order/YandexOrder/statusCount',[], []);
//检查订单是否存在
think\Route::post('/yandex-orders/check$', 'order/YandexOrder/check',[], []);

//控制器：app\purchase\controller\VirtualFinancePurchase
//虚拟付款记录列表
think\Route::get('/virtual-finance-purchase$', 'purchase/VirtualFinancePurchase/getFinanceList',[], []);
//虚拟付款申请详情
think\Route::GET('/virtual-finance-purchase/:id$', 'purchase/VirtualFinancePurchase/getFinanceDetail',[], []);
//审核虚拟付款申请
think\Route::GET('/virtual-finance-purchase/:id/review$', 'purchase/VirtualFinancePurchase/getFinanceReview',[], []);
//审核是否通过虚拟付款申请
think\Route::PUT('/virtual-finance-purchase/:id/review$', 'purchase/VirtualFinancePurchase/setFinanceReview',[], []);
//批量审核虚拟付款申请
think\Route::PUT('/virtual-finance-purchase/batch/review$', 'purchase/VirtualFinancePurchase/batchSetFinanceReview',[], []);
//获取虚拟采购单审核列表
think\Route::get('/virtual-finance-purchase/review-status$', 'purchase/VirtualFinancePurchase/getFinanceReviewStatus',[], []);
//推送有棵树
think\Route::post('/virtual-finance-purchase/push-yks$', 'purchase/VirtualFinancePurchase/pushYks',[], []);
//虚拟采购单导出
think\Route::POST('/virtual-finance-purchase/export$', 'purchase/VirtualFinancePurchase/export',[], []);
//虚拟采购单导出字段
think\Route::GET('/virtual-finance-purchase/export-fields$', 'purchase/VirtualFinancePurchase/getExportFields',[], []);
//计算虚拟付款记录的应付款总金额和已付款总金额
think\Route::GET('/virtual-finance-purchase/total-amount$', 'purchase/VirtualFinancePurchase/calculatingMoney',[], []);
//导出订购单
think\Route::post('/virtual-finance-purchase/export-purchase$', 'purchase/VirtualFinancePurchase/exportPurchase',[], []);
//预览订购单
think\Route::post('/virtual-finance-purchase/read-purchase$', 'purchase/VirtualFinancePurchase/readPurchase',[], []);
//预览收货单
think\Route::post('/virtual-finance-purchase/read-receipt$', 'purchase/VirtualFinancePurchase/readReceipt',[], []);
//预览入库单
think\Route::post('/virtual-finance-purchase/read-in-stock$', 'purchase/VirtualFinancePurchase/readInStock',[], []);
//预览送货单
think\Route::post('/virtual-finance-purchase/read-deliver$', 'purchase/VirtualFinancePurchase/readDeliver',[], []);
//预览发票
think\Route::post('/virtual-finance-purchase/read-invoice$', 'purchase/VirtualFinancePurchase/readInvoice',[], []);
//导出收货单
think\Route::post('/virtual-finance-purchase/export-Receipt$', 'purchase/VirtualFinancePurchase/exportReceipt',[], []);
//导出入库单
think\Route::post('/virtual-finance-purchase/export-in-stock$', 'purchase/VirtualFinancePurchase/exportInStock',[], []);
//导出送货单
think\Route::post('/virtual-finance-purchase/export-deliver$', 'purchase/VirtualFinancePurchase/exportDeliver',[], []);
//导出发票
think\Route::post('/virtual-finance-purchase/export-invoice$', 'purchase/VirtualFinancePurchase/exportInvoice',[], []);

//控制器：app\purchase\controller\VirtualPurchaseOrder
//虚拟采购单列表
think\Route::GET('/virtual-purchase-order$', 'purchase/VirtualPurchaseOrder/getVirtualPurchaseOrderList',[], []);
//虚拟采购单详情
think\Route::GET('/virtual-purchase-order/:id$', 'purchase/VirtualPurchaseOrder/getOrderById',[], []);
//虚拟采购单商品详情
think\Route::GET('/virtual-purchase-order/:id/detail$', 'purchase/VirtualPurchaseOrder/getDetail',[], []);
//批量生成虚拟采购单
think\Route::post('/virtual-purchase-order/create$', 'purchase/VirtualPurchaseOrder/createVirtualOrder',[], []);
//批量生成虚拟付款申请
think\Route::post('/virtual-purchase-order/create-finance$', 'purchase/VirtualPurchaseOrder/createVirtualFinance',[], []);
//推送有棵树
think\Route::post('/virtual-purchase-order/push-yks$', 'purchase/VirtualPurchaseOrder/pushYks',[], []);
//虚拟采购单导出
think\Route::POST('/virtual-purchase-order/export$', 'purchase/VirtualPurchaseOrder/export',[], []);
//虚拟采购单导出字段
think\Route::GET('/virtual-purchase-order/export-fields$', 'purchase/VirtualPurchaseOrder/getExportFields',[], []);
//计算虚拟采购单的应付款总金额和已付款总金额
think\Route::GET('/virtual-purchase-order/total-amount$', 'purchase/VirtualPurchaseOrder/calculatingMoney',[], []);

//控制器：app\warehouse\controller\WishCarrier
//列表
think\Route::get('/wish-carrier$', 'warehouse/WishCarrier/index',[], []);
//添加
think\Route::post('/wish-carrier$', 'warehouse/WishCarrier/save',[], []);
//显示指定物流商资源
think\Route::get('/wish-carrier/:id$', 'warehouse/WishCarrier/read',[], ['id'=>'(\d+)']);
//更新物流商信息
think\Route::PUT('/wish-carrier/:id$', 'warehouse/WishCarrier/update',[], ['id'=>'(\d+)']);
//获取Wish邮授权url
think\Route::get('/wish-carrier/wishpost-url$', 'warehouse/WishCarrier/getWishAuthUrl',[], []);
//wish授权
think\Route::post('/wish-carrier/wish-authors$', 'warehouse/WishCarrier/wishAuthors',[], []);
//获取wish绑定账号信息
think\Route::get('/wish-carrier/:id/account-list$', 'warehouse/WishCarrier/getWishAccount',[], ['id'=>'(\d+)']);
//wish绑定账号
think\Route::post('/wish-carrier/:id/account-bind$', 'warehouse/WishCarrier/bindAccount',[], ['id'=>'(\d+)']);
//wish绑定账号解绑
think\Route::post('/wish-carrier/:id/account-unbind$', 'warehouse/WishCarrier/unBindAccount',[], ['id'=>'(\d+)']);
//获取绑定日志
think\Route::get('/wish-carrier/:id/bind-log$', 'warehouse/WishCarrier/getBindLog',[], ['id'=>'(\d+)']);
//获取wish账号信息
think\Route::get('/wish-carrier/account$', 'warehouse/WishCarrier/getAccount',[], []);

//控制器：app\report\controller\PublishByTime
//列表详情
think\Route::get('/report/publish-by-times$', 'report/PublishByTime/index',[], []);
//平台列表数据
think\Route::get('/report/publish-by-times/channel$', 'report/PublishByTime/channel',[], []);
//账号详情数据
think\Route::get('/report/publish-by-times/shelf$', 'report/PublishByTime/shelf',[], []);
//导出
think\Route::post('/report/publish-by-times/export$', 'report/PublishByTime/applyExport',[], []);

//控制器：app\customerservice\controller\EbayEmail
//收件箱
think\Route::get('/ebay-emails$', 'customerservice/EbayEmail/index',[], []);
//侵权邮件收件箱
think\Route::get('/ebay-emails/infringement-box$', 'customerservice/EbayEmail/infringementBox',[], []);
//发件箱
think\Route::get('/ebay-emails/outbox$', 'customerservice/EbayEmail/outbox',[], []);
//转到收件箱
think\Route::put('/ebay-emails/turn-inbox$', 'customerservice/EbayEmail/turnToInbox',[], []);
//垃圾箱
think\Route::get('/ebay-emails/trashbox$', 'customerservice/EbayEmail/trashBox',[], []);
//发邮件
think\Route::post('/ebay-emails/send$', 'customerservice/EbayEmail/send',[], []);
//收取指定平台账号的邮件
think\Route::get('/ebay-emails/email-account/receive/:account_id$', 'customerservice/EbayEmail/receiveEmails',[], []);
//标记已读
think\Route::put('/ebay-emails/read$', 'customerservice/EbayEmail/markRead',[], []);
//标记未读
think\Route::put('/ebay-emails/unread$', 'customerservice/EbayEmail/markUnRead',[], []);
//标记未读
think\Route::put('/ebay-emails/trash$', 'customerservice/EbayEmail/markTrash',[], []);
//获取客服对应的账号
think\Route::GET('/ebay-emails/account$', 'customerservice/EbayEmail/getEbayAccountMessageTotal',[], []);
//回复或转发邮件
think\Route::post('/ebay-emails/reply$', 'customerservice/EbayEmail/replyEmail',[], []);
//失败邮件重新发送
think\Route::post('/ebay-emails/resend$', 'customerservice/EbayEmail/reSendMail',[], []);
//收件人邮件列表
think\Route::get('/ebay-emails/receiver-mailAddr$', 'customerservice/EbayEmail/ReceiverMailsAddr',[], []);
//发件人邮件列表
think\Route::get('/ebay-emails/send-mailAddr$', 'customerservice/EbayEmail/SenderMailsAddr',[], []);
//未读邮件数
think\Route::get('/ebay-emails/unread$', 'customerservice/EbayEmail/unreadAmount',[], []);
//标记置顶
think\Route::put('/ebay-emails/top$', 'customerservice/EbayEmail/markTop',[], []);
//取消置顶
think\Route::put('/ebay-emails/cancel-top$', 'customerservice/EbayEmail/cancelTop',[], []);

//控制器：app\warehouse\controller\LocalStocking
//获取活动备货状态
think\Route::get('/local-stocking/status$', 'warehouse/LocalStocking/getStatus',[], []);
//获取活动备货列表
think\Route::get('/local-stocking$', 'warehouse/LocalStocking/index',[], []);
//创建活动备货申请
think\Route::post('/local-stocking$', 'warehouse/LocalStocking/save',[], []);
//查看活动备货详情
think\Route::get('/local-stocking/:id$', 'warehouse/LocalStocking/read',[], ['id'=>'(\d+)']);
//审核活动备货申请
think\Route::post('/local-stocking/adopt/:id$', 'warehouse/LocalStocking/adopt',[], []);
//导入商品信息
think\Route::post('/local-stocking/import-goods$', 'warehouse/LocalStocking/importGoods',[], []);

//控制器：app\publish\controller\WishExpress
//添加物流模版
think\Route::post('/publish/wish-express/add-template$', 'publish/WishExpress/addTemplate',[], []);
//编辑物流模版
think\Route::post('/publish/wish-express/edit-template$', 'publish/WishExpress/editTemplate',[], []);
//批量删除模版
think\Route::delete('/publish/wish-express/batch-delete$', 'publish/WishExpress/batchDelTemplate',[], []);
//wish物流价格模版列表
think\Route::get('/publish/wish-express/lists$', 'publish/WishExpress/templateList',[], []);
//获取模版详情
think\Route::get('/publish/wish-express/detail$', 'publish/WishExpress/getDetail',[], []);

//控制器：app\order\controller\PackageError
//物流异常包裹解决方案列表
think\Route::get('/packages-error$', 'order/PackageError/index',[], []);
//添加下单异常错误信息
think\Route::POST('/packages-error/$', 'order/PackageError/add',[], []);
//更新异常错误信息
think\Route::PUT('/packages-error/$', 'order/PackageError/update',[], []);
//获取创建人信息
think\Route::get('/packages-error/developers$', 'order/PackageError/getDeveloperId',[], []);
//获取更新人信息
think\Route::get('/packages-error/updaters$', 'order/PackageError/getUpdaterId',[], []);
//获取下单报错信息
think\Route::get('/packages-error/error$', 'order/PackageError/errorInfo',[], []);

//控制器：app\index\controller\ChannelNode
//平台自动登录列表
think\Route::get('/channel-node$', 'index/ChannelNode/index',[], []);
//获取平台自动登录信息
think\Route::GET('/channel-node/:id/edit$', 'index/ChannelNode/edit',[], ['id'=>'(\d+)']);
//保存平台自动登录信息
think\Route::post('/channel-node$', 'index/ChannelNode/save',[], []);
//更新平台自动登录信息
think\Route::PUT('/channel-node/:id$', 'index/ChannelNode/update',[], ['id'=>'(\d+)']);
//删除
think\Route::delete('/channel-node/:id$', 'index/ChannelNode/delete',[], []);
//节点类型
think\Route::get('/channel-node/node-type$', 'index/ChannelNode/nodeTpye',[], []);

//控制器：app\report\controller\SkuSalesDynamic
//列表详情
think\Route::get('/report/sku-sales-dynamic$', 'report/SkuSalesDynamic/index',[], []);
//execl字段信息
think\Route::get('/report/sku-sales-dynamic/export-title$', 'report/SkuSalesDynamic/title',[], []);
//导出execl
think\Route::post('/report/sku-sales-dynamic/export$', 'report/SkuSalesDynamic/export',[], []);

//控制器：app\order\controller\Fbp
//fbp订单列表
think\Route::get('/fbp-orders$', 'order/Fbp/index',[], []);
//fbp销售额统计
think\Route::get('/fbp-orders/report$', 'order/Fbp/report',[], []);
//获取所有导出字段
think\Route::get('/fbp-orders/export-fields$', 'order/Fbp/getExportFields',[], []);
//fbp订单导出
think\Route::post('/fbp-orders/export$', 'order/Fbp/export',[], []);

//控制器：app\index\controller\EmailServer
//平台自动登录列表
think\Route::get('/email-server$', 'index/EmailServer/index',[], []);
//获取平台自动登录信息
think\Route::GET('/email-server/:id/edit$', 'index/EmailServer/edit',[], ['id'=>'(\d+)']);
//保存平台自动登录信息
think\Route::post('/email-server$', 'index/EmailServer/save',[], []);
//更新平台自动登录信息
think\Route::PUT('/email-server/:id$', 'index/EmailServer/update',[], ['id'=>'(\d+)']);
//删除
think\Route::delete('/email-server/:id/:account_id$', 'index/EmailServer/delete',[], []);

//控制器：app\index\controller\FbpAccount
//fbp账号列表
think\Route::GET('/fbp-account$', 'index/FbpAccount/index',[], []);
//添加账号
think\Route::POST('/fbp-account$', 'index/FbpAccount/add',[], []);
//更新账号
think\Route::PUT('/fbp-account$', 'index/FbpAccount/update',[], []);
//查看账号
think\Route::GET('/fbp-account/:id$', 'index/FbpAccount/read',[], []);
//获取订单授权信息
think\Route::GET('/fbp-account/token/:id$', 'index/FbpAccount/getToken',[], []);
// fbp订单账号授权
think\Route::PUT('/fbp-account/token$', 'index/FbpAccount/updaeToken',[], []);
// fbp商品账号授权
think\Route::PUT('/fbp-account/tokencat$', 'index/FbpAccount/updaeTokenCat',[], []);
//停用，启用账号
think\Route::post('/fbp-account/states$', 'index/FbpAccount/changeStatus',[], []);
//批量开启
think\Route::post('/fbp-account/batch-set$', 'index/FbpAccount/batchSet',[], []);

//控制器：app\report\controller\MonthlyTargetUser
//列表详情
think\Route::get('/monthly-target-user$', 'report/MonthlyTargetUser/index',[], []);
//保存成员
think\Route::post('/monthly-target-user/add$', 'report/MonthlyTargetUser/add',[], []);
//保存更新的资源
think\Route::PUT('/monthly-target-user/:id$', 'report/MonthlyTargetUser/update',[], ['id'=>'(\d+)']);
//删除绑定关系
think\Route::delete('/:id$', 'report/MonthlyTargetUser/delete',[], []);
//目标成员管理[销售]获取
think\Route::get('/monthly-target-user/:id$', 'report/MonthlyTargetUser/read',[], ['id'=>'(\d+)']);
//目标成员管理[销售]编辑
think\Route::GET('/monthly-target-user/:id/edit$', 'report/MonthlyTargetUser/edit',[], ['id'=>'(\d+)']);
//拉取部门以及上级信息
think\Route::get('/monthly-target-user/get-department$', 'report/MonthlyTargetUser/getDepartment',[], []);

//控制器：app\report\controller\MonthlyTargetDepartment
//目标部门管理[销售]列表
think\Route::get('/monthly-target-department$', 'report/MonthlyTargetDepartment/index',[], []);
//目标部门管理[销售]添加
think\Route::post('/monthly-target-department$', 'report/MonthlyTargetDepartment/save',[], []);
//目标部门管理[销售]获取
think\Route::get('/monthly-target-department/:id$', 'report/MonthlyTargetDepartment/read',[], ['id'=>'(\d+)']);
//目标部门管理[销售]编辑
think\Route::GET('/monthly-target-department/:id/edit$', 'report/MonthlyTargetDepartment/edit',[], ['id'=>'(\d+)']);
//目标部门管理[销售]更新
think\Route::PUT('/monthly-target-department/:id$', 'report/MonthlyTargetDepartment/update',[], ['id'=>'(\d+)']);
//目标部门管理[销售]删除
think\Route::DELETE('/monthly-target-department/:id$', 'report/MonthlyTargetDepartment/delete',[], ['id'=>'(\d+)']);
//停用，启用账号
think\Route::get('/monthly-target-department/change-status$', 'report/MonthlyTargetDepartment/changeStatus',[], []);
//获取所有部门
think\Route::get('/monthly-target-department/get-department$', 'report/MonthlyTargetDepartment/getDepartment',[], []);
//部门类型
think\Route::get('/monthly-target-department/type$', 'report/MonthlyTargetDepartment/type',[], []);

//控制器：app\report\controller\MonthlyTargetAmount
//列表详情
think\Route::get('/monthly-target-amount$', 'report/MonthlyTargetAmount/index',[], []);
//首页简报
think\Route::get('/monthly-target-amount/all-target$', 'report/MonthlyTargetAmount/getAllDeparment',[], []);
//下载部门与成员组成表
think\Route::post('/monthly-target-amount/export$', 'report/MonthlyTargetAmount/applyExport',[], []);
//下载月度目标报表
think\Route::post('/monthly-target-amount/export-monthly$', 'report/MonthlyTargetAmount/applyExportMonthly',[], []);
//导入成员考核目标
think\Route::post('/monthly-target-amount/import$', 'report/MonthlyTargetAmount/import',[], []);
//保存导入成员考核目标
think\Route::post('/monthly-target-amount/save-import$', 'report/MonthlyTargetAmount/saveImport',[], []);
//重新计算部门人数与平台账号数
think\Route::post('/monthly-target-amount/recalculate$', 'report/MonthlyTargetAmount/recalculate',[], []);

//控制器：app\order\controller\VirtualRefund
//虚拟订单返款申请
think\Route::post('/virtual-refund$', 'order/VirtualRefund/save',[], []);
//虚拟订单返款列表
think\Route::get('/virtual-refund$', 'order/VirtualRefund/index',[], []);
//首次返款申请单
think\Route::get('/virtual-refund/get-task$', 'order/VirtualRefund/getTask',[], []);
//查看返款申请单
think\Route::get('/virtual-refund/:id$', 'order/VirtualRefund/read',[], ['id'=>'(\d+)']);
//提交/重新返款申请单
think\Route::post('/virtual-refund/add-refund$', 'order/VirtualRefund/addRefund',[], []);
//标记审核状态
think\Route::post('/virtual-refund/approval$', 'order/VirtualRefund/approval',[], []);
//批量标记审核状态
think\Route::post('/virtual-refund/batch/approval$', 'order/VirtualRefund/batchApproval',[], []);
//批量标记返款状态
think\Route::post('/virtual-refund/batch/refund$', 'order/VirtualRefund/batchRefund',[], []);
//导出execl
think\Route::post('/virtual-refund/export$', 'order/VirtualRefund/applyExport',[], []);
//execl字段信息
think\Route::get('/virtual-refund/export-title$', 'order/VirtualRefund/title',[], []);

//控制器：app\index\controller\WishShippingRate
//显示资源列表
think\Route::get('/wish-shipping-rate$', 'index/WishShippingRate/index',[], []);
//显示指定的资源
think\Route::get('/wish-shipping-rate/:id$', 'index/WishShippingRate/read',[], ['id'=>'(\d+)']);
//显示编辑资源表单页.
think\Route::GET('/wish-shipping-rate/:id/edit$', 'index/WishShippingRate/edit',[], ['id'=>'(\d+)']);
//保存更新的资源
think\Route::PUT('/wish-shipping-rate/:id$', 'index/WishShippingRate/update',[], ['id'=>'(\d+)']);
//计算订单占比
think\Route::post('/wish-shipping-rate/order-rate$', 'index/WishShippingRate/orderRate',[], []);
//计算重量运费
think\Route::post('/wish-shipping-rate/shipping-charge$', 'index/WishShippingRate/addShippingCharge',[], []);
//wish重量与费用列表
think\Route::get('/wish-shipping-rate/weight-list$', 'index/WishShippingRate/weightList',[], []);

//控制器：app\customerservice\controller\AfterSaleRule
//售后单规则列表
think\Route::GET('/after-sale-rules$', 'customerservice/AfterSaleRule/index',[], []);
//规则详情
think\Route::GET('/after-sale-rules/:id$', 'customerservice/AfterSaleRule/read',[], []);
//新增订单
think\Route::POST('/after-sale-rules$', 'customerservice/AfterSaleRule/save',[], []);
//更新规则
think\Route::PUT('/after-sale-rules/:id$', 'customerservice/AfterSaleRule/update',[], ['id'=>'(\d+)']);
//删除规则
think\Route::delete('/after-sale-rules/:id$', 'customerservice/AfterSaleRule/delete',[], ['id'=>'(\d+)']);
//修改规则状态
think\Route::post('/after-sale-rules/status$', 'customerservice/AfterSaleRule/changeStatus',[], []);
//保存排序值
think\Route::post('/after-sale-rules/sort$', 'customerservice/AfterSaleRule/changeSort',[], []);
//获取售后单规则
think\Route::GET('/after-sale-rules/rule-item$', 'customerservice/AfterSaleRule/ruleItem',[], []);
//平台列表
think\Route::get('/after-sale-rules/channel$', 'customerservice/AfterSaleRule/channelList',[], []);

//控制器：app\order\controller\PackageException
//异常包裹列表
think\Route::get('/package-exception$', 'order/PackageException/index',[], []);
//execl字段信息
think\Route::get('/package-exception/export-title$', 'order/PackageException/title',[], []);
//获取异常包裹状态
think\Route::get('/package-exception/status$', 'order/PackageException/status',[], []);
//导出
think\Route::post('/package-exception/export$', 'order/PackageException/export',[], []);

//控制器：app\index\controller\AccountCompany
//平台公司资料列表
think\Route::get('/account-company$', 'index/AccountCompany/index',[], []);
//显示指定的资源
think\Route::get('/account-company/:id$', 'index/AccountCompany/read',[], ['id'=>'(\d+)']);
//获取平台公司资料信息
think\Route::GET('/account-company/:id/edit$', 'index/AccountCompany/edit',[], ['id'=>'(\d+)']);
//保存平台公司资料信息
think\Route::post('/account-company$', 'index/AccountCompany/save',[], []);
//更新平台公司资料信息[公司资料]
think\Route::PUT('/account-company/:id$', 'index/AccountCompany/update',[], ['id'=>'(\d+)']);
//更新平台公司资料信息[账号信息]
think\Route::PUT('/account-company/:id/account$', 'index/AccountCompany/updateAccount',[], []);
//更新平台公司资料信息[VAT]
think\Route::PUT('/account-company/:id/vat$', 'index/AccountCompany/updateVat',[], []);
//删除
think\Route::delete('/:id$', 'index/AccountCompany/delete',[], []);
//日志
think\Route::get('/account-company/:id/log$', 'index/AccountCompany/log',[], []);
//拉取公司名称列表
think\Route::get('/account-company/company$', 'index/AccountCompany/company',[], []);
//修改状态
think\Route::post('/account-company/:id/status$', 'index/AccountCompany/changeStatus',[], []);
//公司类型
think\Route::get('/account-company/type$', 'index/AccountCompany/type',[], []);
//资料来源
think\Route::get('/account-company/source$', 'index/AccountCompany/source',[], []);

//控制器：app\report\controller\DevelopMonthlyTargetUser
//列表详情
think\Route::get('/develop-monthly-target-user$', 'report/DevelopMonthlyTargetUser/index',[], []);
//保存成员
think\Route::post('/develop-monthly-target-user/add$', 'report/DevelopMonthlyTargetUser/add',[], []);
//保存更新的资源
think\Route::PUT('/develop-monthly-target-user/:id$', 'report/DevelopMonthlyTargetUser/update',[], ['id'=>'(\d+)']);
//目标成员管理[开发]获取
think\Route::get('/develop-monthly-target-user/:id$', 'report/DevelopMonthlyTargetUser/read',[], ['id'=>'(\d+)']);
//目标成员管理[开发]编辑
think\Route::GET('/develop-monthly-target-user/:id/edit$', 'report/DevelopMonthlyTargetUser/edit',[], ['id'=>'(\d+)']);
//拉取部门以及上级信息
think\Route::get('/develop-monthly-target-user/get-department$', 'report/DevelopMonthlyTargetUser/getDepartment',[], []);
//删除绑定关系
think\Route::delete('/:id$', 'report/DevelopMonthlyTargetUser/delete',[], []);

//控制器：app\report\controller\DevelopMonthlyTargetDepartment
//目标部门管理[开发]列表
think\Route::get('/develop-monthly-target-department$', 'report/DevelopMonthlyTargetDepartment/index',[], []);
//目标部门管理[开发]添加
think\Route::post('/develop-monthly-target-department$', 'report/DevelopMonthlyTargetDepartment/save',[], []);
//目标部门管理[开发]获取
think\Route::get('/develop-monthly-target-department/:id$', 'report/DevelopMonthlyTargetDepartment/read',[], ['id'=>'(\d+)']);
//目标部门管理[开发]编辑
think\Route::GET('/develop-monthly-target-department/:id/edit$', 'report/DevelopMonthlyTargetDepartment/edit',[], ['id'=>'(\d+)']);
//目标部门管理[开发]更新
think\Route::PUT('/develop-monthly-target-department/:id$', 'report/DevelopMonthlyTargetDepartment/update',[], ['id'=>'(\d+)']);
//目标部门管理[开发]删除
think\Route::DELETE('/develop-monthly-target-department/:id$', 'report/DevelopMonthlyTargetDepartment/delete',[], ['id'=>'(\d+)']);
//停用，启用账号
think\Route::get('/develop-monthly-target-department/change-status$', 'report/DevelopMonthlyTargetDepartment/changeStatus',[], []);
//获取所有部门
think\Route::get('/develop-monthly-target-department/get-department$', 'report/DevelopMonthlyTargetDepartment/getDepartment',[], []);
//部门类型
think\Route::get('/develop-monthly-target-department/type$', 'report/DevelopMonthlyTargetDepartment/type',[], []);

//控制器：app\report\controller\DevelopMonthlyTargetAmount
//列表详情
think\Route::get('/develop-monthly-target-amount$', 'report/DevelopMonthlyTargetAmount/index',[], []);
//首页简报
think\Route::get('/develop-monthly-target-amount/all-target$', 'report/DevelopMonthlyTargetAmount/getAllDeparment',[], []);
//下载部门与成员组成表
think\Route::post('/develop-monthly-target-amount/export$', 'report/DevelopMonthlyTargetAmount/applyExport',[], []);
//下载月度目标报表
think\Route::post('/develop-monthly-target-amount/export-monthly$', 'report/DevelopMonthlyTargetAmount/applyExportMonthly',[], []);
//导入成员考核目标
think\Route::post('/develop-monthly-target-amount/import$', 'report/DevelopMonthlyTargetAmount/import',[], []);
//保存导入成员考核目标
think\Route::post('/develop-monthly-target-amount/save-import$', 'report/DevelopMonthlyTargetAmount/saveImport',[], []);
//重新计算部门人数
think\Route::post('/develop-monthly-target-amount/recalculate$', 'report/DevelopMonthlyTargetAmount/recalculate',[], []);

//控制器：app\warehouse\controller\PickingManage
//调拨拣货单列表
think\Route::get('/pickings-manage$', 'warehouse/PickingManage/index',[], []);
//查看
think\Route::get('/pickings-manage/:id$', 'warehouse/PickingManage/read',[], ['id'=>'(\d+)']);
//拣货单详情
think\Route::get('/pickings-manage/:id/detail$', 'warehouse/PickingManage/detail',[], []);
//查看拣货单周转箱信息
think\Route::get('/pickings-manage/:id/turnover$', 'warehouse/PickingManage/turnover',[], []);
//正在拣货
think\Route::post('/pickings-manage/:id/marking$', 'warehouse/PickingManage/markingIsPicking',[], []);
//打印拣货单
think\Route::get('/pickings-manage/:id/print$', 'warehouse/PickingManage/printOrder',[], []);
//拣货单操作日志
think\Route::get('/pickings-manage/:id/log$', 'warehouse/PickingManage/log',[], []);
//作废
think\Route::post('/pickings-manage/:id/invalid$', 'warehouse/PickingManage/invalid',[], []);
//获取调拨拣货单状态
think\Route::get('/pickings-manage/status$', 'warehouse/PickingManage/getStatusList',[], []);
//下架完成拣货
think\Route::post('/pickings-manage/:id/complete$', 'warehouse/PickingManage/complete',[], []);
//单个SKU下架
think\Route::post('/pickings-manage/:id/off$', 'warehouse/PickingManage/off',[], []);
//打印商品条码
think\Route::get('/pickings-manage/:id/print-barcode$', 'warehouse/PickingManage/printBarcode',[], []);

//控制器：app\publish\controller\AmazonNotice
//亚马逊账号通知信息
think\Route::get('/publish/amazon-notice/notice-info$', 'publish/AmazonNotice/noticeInfo',[], []);
//亚马逊账号通知设置
think\Route::Post('/publish/amazon-notice/set-notice$', 'publish/AmazonNotice/setNotice',[], []);
//亚马逊账号通知测试
think\Route::get('/publish/amazon-notice/notice-ceshi$', 'publish/AmazonNotice/noticeCeShi',[], []);
//亚马逊账号通知消息
think\Route::post('/publish/amazon-notice/check-notice$', 'publish/AmazonNotice/checkNotice',[], []);

//控制器：app\customerservice\controller\PaypalDispute
//paypal纠纷列表
think\Route::GET('/paypal-dispute$', 'customerservice/PaypalDispute/index',[], []);
//paypal纠纷统计
think\Route::get('/paypal-dispute/statistics$', 'customerservice/PaypalDispute/statistics',[], []);
//paypal更新纠纷
think\Route::put('/paypal-dispute/:id$', 'customerservice/PaypalDispute/update',[], []);
//paypal帐号筛选
think\Route::get('/paypal-dispute/accounts$', 'customerservice/PaypalDispute/accounts',[], []);
//查看paypal纠纷详情
think\Route::get('/paypal-dispute/:id/read$', 'customerservice/PaypalDispute/read',[], []);
//处理paypal纠纷详情
think\Route::get('/paypal-dispute/:id$', 'customerservice/PaypalDispute/detail',[], []);
//paypal处理纠纷
think\Route::post('/paypal-dispute/:type$', 'customerservice/PaypalDispute/operate',[], []);
//paypal纠纷添加新地址
think\Route::POST('/paypal-dispute/address$', 'customerservice/PaypalDispute/saveAddress',[], []);
//paypal纠纷拿取地址
think\Route::get('/paypal-dispute/:aid/address$', 'customerservice/PaypalDispute/getAddress',[], []);
//paypal纠纷拿给客户付款订单
think\Route::get('/paypal-dispute/:id/refund_order$', 'customerservice/PaypalDispute/refundOrder',[], []);
//paypal纠纷物流选取；
think\Route::get('/paypal-dispute/carriers$', 'customerservice/PaypalDispute/carriers',[], []);
//paypal纠纷同意赔偿原因；
think\Route::get('/paypal-dispute/accept_reason$', 'customerservice/PaypalDispute/acceptReason',[], []);

//控制器：app\warehouse\controller\ReturnWaitShelf
//列表
think\Route::get('/return-wait-shelf$', 'warehouse/ReturnWaitShelf/index',[], []);
//待入库详情
think\Route::get('/return-wait-shelf/:id/detail$', 'warehouse/ReturnWaitShelf/detail',[], []);
//批量重返上架
think\Route::post('/return-wait-shelf/batch/save$', 'warehouse/ReturnWaitShelf/batchSave',[], []);

//控制器：app\report\controller\AmazonSettlementReport
//Amazon结算报告列表
think\Route::GET('/report/amazon-settlement/summary$', 'report/AmazonSettlementReport/summary',[], []);
//Amazon结算报告列表详情
think\Route::GET('/report/amazon-settlement/summary-detail$', 'report/AmazonSettlementReport/detail',[], []);
// Amazon结算报告导出
think\Route::POST('/report/amazon-settlement/summary-export$', 'report/AmazonSettlementReport/export',[], []);
//获取可供选择的导出字段
think\Route::get('/report/amazon-settlement/export-field$', 'report/AmazonSettlementReport/getExportField',[], []);
//检查结算报告缺失
think\Route::GET('/report/amazon-settlement/check-report$', 'report/AmazonSettlementReport/checkReport',[], []);
//更新report-summary
think\Route::get('/report/amazon-settlement/update-summary$', 'report/AmazonSettlementReport/updateSummary',[], []);
//修复report错误数据
think\Route::get('/report/amazon-settlement/repair$', 'report/AmazonSettlementReport/repair',[], []);
//页面获取账号分页
think\Route::get('/report/amazon-settlement/account$', 'report/AmazonSettlementReport/getAccount',[], []);

//控制器：app\warehouse\controller\PackingManage
//包装作业列表
think\Route::get('/packing-manage$', 'warehouse/PackingManage/index',[], []);
//包装开始
think\Route::post('/packing-manage/start-packing$', 'warehouse/PackingManage/startPacking',[], []);
//添加调拨箱
think\Route::post('/packing-manage$', 'warehouse/PackingManage/save',[], []);
//扫描SKU
think\Route::post('/packing-manage/insert-pack-box$', 'warehouse/PackingManage/insertPackBox',[], []);
//修改sku数量
think\Route::post('/packing-manage/change-quantity$', 'warehouse/PackingManage/changeQuantity',[], []);
//包装完成
think\Route::post('/packing-manage/packing-finish$', 'warehouse/PackingManage/packingFinish',[], []);
//删除调拨箱详情
think\Route::post('/packing-manage/delete-box-detail$', 'warehouse/PackingManage/deleteBoxDetail',[], []);
//修改调拨箱尺寸
think\Route::post('/packing-manage/modify-size$', 'warehouse/PackingManage/modifySize',[], []);

//控制器：app\index\controller\Software
//显示资源列表
think\Route::get('/software$', 'index/Software/index',[], []);
//保存新建的资源
think\Route::post('/software$', 'index/Software/save',[], []);
//更改账号状态
think\Route::post('/software/batch/:type$', 'index/Software/batch',[], []);
//修改状态
think\Route::post('/software/:id/status$', 'index/Software/changeStatus',[], []);
//获取状态
think\Route::get('/software/type$', 'index/Software/type',[], []);
//删除软件
think\Route::DELETE('/software/:id$', 'index/Software/delete',[], ['id'=>'(\d+)']);
//发布软件版本
think\Route::post('/software/:id/version$', 'index/Software/sendVersion',[], []);
//历史版本
think\Route::get('/software/:id/version$', 'index/Software/getVersion',[], []);

//控制器：app\index\controller\ServerSoftware
//显示资源列表
think\Route::get('/server-software$', 'index/ServerSoftware/index',[], []);
//批量操作【更新客户端版本】
think\Route::post('/server-software/batch/:type$', 'index/ServerSoftware/batch',[], []);
//修改状态
think\Route::post('/server-software/:id/status$', 'index/ServerSoftware/changeStatus',[], []);

//控制器：app\finance\controller\WishSettlement
//wish结算报告列表
think\Route::get('/wish-settlement/index_settle$', 'finance/WishSettlement/indexSettle',[], []);
//wish结算报告导出
think\Route::post('/wish-settlement/export$', 'finance/WishSettlement/export',[], []);
//wish汇总结算报告导出
think\Route::post('/wish-settlement/export-sum$', 'finance/WishSettlement/exportSum',[], []);

//控制器：app\warehouse\controller\AllocationShipping
//列表
think\Route::get('/allocation-shipping$', 'warehouse/AllocationShipping/index',[], []);
//箱子出库交接
think\Route::post('/allocation-shipping/deliver$', 'warehouse/AllocationShipping/boxDeliver',[], []);
//箱子出库交接
think\Route::post('/allocation-shipping/batch-deliver$', 'warehouse/AllocationShipping/batchBoxDeliver',[], []);
//批量出库详情
think\Route::get('/allocation-shipping/detail$', 'warehouse/AllocationShipping/detail',[], []);
//强制交货完成
think\Route::post('/allocation-shipping/force-deliver$', 'warehouse/AllocationShipping/forceDeliver',[], []);

//控制器：app\warehouse\controller\AllocationLogistics
//列表
think\Route::get('/allocation-logistics$', 'warehouse/AllocationLogistics/index',[], []);
//上传物流信息
think\Route::put('/allocation-logistics/upload$', 'warehouse/AllocationLogistics/uploadLogistics',[], []);
//导出装箱清单
think\Route::post('/allocation-logistics/export-list$', 'warehouse/AllocationLogistics/exportList',[], []);
//导入运费
think\Route::post('/allocation-logistics/import-tracking$', 'warehouse/AllocationLogistics/importTracking',[], []);

//控制器：app\warehouse\controller\ShippingAddress
//物流地址设置列表
think\Route::get('/shipping-address$', 'warehouse/ShippingAddress/index',[], []);
//查看地址
think\Route::get('/shipping-address/:id$', 'warehouse/ShippingAddress/read',[], ['id'=>'(\d+)']);
//保存地址
think\Route::post('/shipping-address$', 'warehouse/ShippingAddress/save',[], []);
//更新地址
think\Route::PUT('/shipping-address/:id$', 'warehouse/ShippingAddress/update',[], ['id'=>'(\d+)']);
//更新地址
think\Route::DELETE('/shipping-address/:id$', 'warehouse/ShippingAddress/delete',[], ['id'=>'(\d+)']);

//控制器：app\purchase\controller\SupplierDiscussRecord
//供应商洽谈记录列表
think\Route::get('/supplier-discuss-record$', 'purchase/SupplierDiscussRecord/index',[], []);
//添加供应商洽谈记录
think\Route::post('/supplier-discuss-record$', 'purchase/SupplierDiscussRecord/save',[], []);
//查看资源
think\Route::get('/supplier-discuss-record/:id$', 'purchase/SupplierDiscussRecord/read',[], ['id'=>'(\d+)']);
//获取信息
think\Route::get('/supplier-discuss-record/:type/info$', 'purchase/SupplierDiscussRecord/info',[], ['type'=>'(\w+)']);

//控制器：app\warehouse\controller\DefectiveGoodsDeclare
//显示次品列表
think\Route::get('/defective-goods-declare$', 'warehouse/DefectiveGoodsDeclare/index',[], []);
//新增
think\Route::post('/defective-goods-declare/create$', 'warehouse/DefectiveGoodsDeclare/create',[], []);
//查看审核列表
think\Route::get('/defective-goods-declare/:id$', 'warehouse/DefectiveGoodsDeclare/read',[], ['id'=>'(\d+)']);
//申报状态
think\Route::get('/defective-goods-declare/status$', 'warehouse/DefectiveGoodsDeclare/status',[], []);
//审核是否通过
think\Route::post('/defective-goods-declare/check$', 'warehouse/DefectiveGoodsDeclare/check',[], []);

//控制器：app\index\controller\Phone
//手机号管理列表
think\Route::get('/phone$', 'index/Phone/index',[], []);
//手机号管理获取
think\Route::get('/phone/:id$', 'index/Phone/read',[], ['id'=>'(\d+)']);
//手机号管理添加
think\Route::post('/phone$', 'index/Phone/save',[], []);
//切换状态
think\Route::put('/phone/:id/status$', 'index/Phone/changeStatus',[], ['id'=>'(\d+)']);
//获取可用手机号列表
think\Route::get('/phone/can-use$', 'index/Phone/getCanUsePhoneList',[], []);
//获取邮箱可用手机号列表
think\Route::get('/phone/email-use$', 'index/Phone/getCanUserEmailPhone',[], []);
//获取关联的帐号
think\Route::get('/phone/:id/accounts$', 'index/Phone/accounts',[], ['id'=>'(\d+)']);

//控制器：app\report\controller\WarehousePackage
//仓库统计
think\Route::get('/report/warehouse-package$', 'report/WarehousePackage/index',[], []);
//未操作包裹详情
think\Route::get('/report/warehouse-package/unpacked-detail$', 'report/WarehousePackage/unpackedDetail',[], []);
//未发货记录
think\Route::get('/report/warehouse-package/log-unfilled$', 'report/WarehousePackage/logUnfilled',[], []);
//未发货记录详情
think\Route::get('/report/warehouse-package/log-unfilled-details$', 'report/WarehousePackage/logUnfilledDetails',[], []);
//已发货记录
think\Route::get('/report/warehouse-package/log-shipped$', 'report/WarehousePackage/logShipped',[], []);
//已发货记录详情
think\Route::get('/report/warehouse-package/log-shipped-details$', 'report/WarehousePackage/logShippedDetails',[], []);
//未拆包记录
think\Route::get('/report/warehouse-package/log-not-opened$', 'report/WarehousePackage/logNotOpen',[], []);
//缺货记录
think\Route::get('/report/warehouse-package/log-stock$', 'report/WarehousePackage/logStock',[], []);
//缺货记录详情
think\Route::get('/report/warehouse-package/log-stock-details$', 'report/WarehousePackage/logStockDetails',[], []);
//仓库列表
think\Route::get('/report/warehouse-package/warehouse$', 'report/WarehousePackage/warehouse',[], []);
//手动跑任务
think\Route::get('/report/warehouse-package/manual$', 'report/WarehousePackage/manualRunTask',[], []);

//控制器：app\warehouse\controller\StockingAdvice
//备货建议列表
think\Route::get('/stocking-advice$', 'warehouse/StockingAdvice/index',[], []);
//备货建议详情
think\Route::get('/stocking-advice/:id$', 'warehouse/StockingAdvice/read',[], ['id'=>'(\d+)']);
//获取备货数量
think\Route::get('/stocking-advice/stocking-quantity$', 'warehouse/StockingAdvice/stockingQuantity',[], []);
//状态信息
think\Route::get('/stocking-advice/status$', 'warehouse/StockingAdvice/status',[], []);
//最小起订量
think\Route::get('/stocking-advice/min-quantity$', 'warehouse/StockingAdvice/MinQuantity',[], []);
//分配详情接口
think\Route::get('/stocking-advice/:id/distribution-details$', 'warehouse/StockingAdvice/distributionDetails',[], []);
//开发审核
think\Route::put('/stocking-advice/develop-review$', 'warehouse/StockingAdvice/developReview',[], []);
//开发批量审批
think\Route::put('/stocking-advice/batch-develop-review$', 'warehouse/StockingAdvice/batchDevelopReview',[], []);
//批量驳回接口
think\Route::put('/stocking-advice/batch-develop-reject$', 'warehouse/StockingAdvice/batchDevelopReject',[], []);
//采购批量审核接口
think\Route::put('/stocking-advice/develop-processing-plan$', 'warehouse/StockingAdvice/developProcessingPlan',[], []);
//excel字段信息
think\Route::get('/stocking-advice/export-title$', 'warehouse/StockingAdvice/title',[], []);
//导出excel
think\Route::post('/stocking-advice/export$', 'warehouse/StockingAdvice/export',[], []);
//变更供应商
think\Route::put('/stocking-advice/supplier$', 'warehouse/StockingAdvice/supplier',[], []);

//控制器：app\index\controller\Email
//新建邮箱
think\Route::post('/email$', 'index/Email/save',[], []);
//修改邮箱号
think\Route::PUT('/email/:id$', 'index/Email/update',[], ['id'=>'(\d+)']);
//邮箱号列表
think\Route::get('/email$', 'index/Email/index',[], []);
//邮箱号详情
think\Route::get('/email/:id$', 'index/Email/read',[], ['id'=>'(\d+)']);
//查看密码
think\Route::get('/email/:id/password$', 'index/Email/viewPassword',[], ['id'=>'(\d+)']);
//批量去除错误信息
think\Route::put('/email/batch/error-msg$', 'index/Email/clearError',[], []);
//获取可用邮箱列表
think\Route::get('/email/available-list$', 'index/Email/getCanUseEmail',[], []);
//获取已注册帐号的邮箱
think\Route::get('/email/used-list$', 'index/Email/getUsedEmail',[], []);

//控制器：app\index\controller\Postoffice
//邮局信息列表
think\Route::get('/postoffice$', 'index/Postoffice/index',[], []);
//获取单条邮局详情
think\Route::get('/postoffice/:id$', 'index/Postoffice/read',[], ['id'=>'(\d+)']);
//新增邮局信息
think\Route::post('/postoffice$', 'index/Postoffice/save',[], []);
//修改邮局信息
think\Route::PUT('/postoffice/:id$', 'index/Postoffice/update',[], ['id'=>'(\d+)']);
//切换状态
think\Route::put('/postoffice/:id/status$', 'index/Postoffice/changeStatus',[], ['id'=>'(\d+)']);
//获取可用邮局列表
think\Route::get('/postoffice/available-list$', 'index/Postoffice/getCanUsePost',[], []);

//控制器：app\finance\controller\BankAccount
//新增银行账户
think\Route::post('/bank-account$', 'finance/BankAccount/save',[], []);
//银行账户列表
think\Route::get('/bank-account$', 'finance/BankAccount/index',[], []);
//银行账户信息
think\Route::get('/bank-account/:id$', 'finance/BankAccount/read',[], ['id'=>'(\d+)']);
//更新银行记录
think\Route::PUT('/bank-account/:id$', 'finance/BankAccount/update',[], ['id'=>'(\d+)']);
//导出csv
think\Route::post('/bank-account/export$', 'finance/BankAccount/export',[], []);
//获取银行信息
think\Route::get('/bank-account/bank$', 'finance/BankAccount/bank',[], []);
//城市信息列表
think\Route::get('/bank-account/cities$', 'finance/BankAccount/city',[], []);
//省份信息列表
think\Route::get('/bank-account/provinces$', 'finance/BankAccount/province',[], []);

//控制器：app\warehouse\controller\ReturnWaitShelves
//列表
think\Route::get('/return-wait-shelves$', 'warehouse/ReturnWaitShelves/index',[], []);
//待入库详情
think\Route::get('/return-wait-shelves/:id/detail$', 'warehouse/ReturnWaitShelves/detail',[], []);
//批量重返上架
think\Route::post('/return-wait-shelves/batch/save$', 'warehouse/ReturnWaitShelves/batchSave',[], []);
//待入库详情
think\Route::get('/return-wait-shelves/status$', 'warehouse/ReturnWaitShelves/getStatus',[], []);

//控制器：app\index\controller\DarazAccount
//保存新建资源
think\Route::POST('/daraz-account$', 'index/DarazAccount/save',[], []);
//获取daraz站点
think\Route::get('/daraz-account/sites$', 'index/DarazAccount/getSites',[], []);
//显示指定的资源
think\Route::GET('/daraz-account/read$', 'index/DarazAccount/read',[], []);
//Daraz账号管理列表
think\Route::GET('/daraz-account$', 'index/DarazAccount/index',[], []);
//保存更新的资源
think\Route::PUT('/daraz-account/:id$', 'index/DarazAccount/update',[], []);
//保存daraz账户授权
think\Route::put('/daraz-account/authorization$', 'index/DarazAccount/authorization',[], []);
//系统状态切换
think\Route::post('/daraz-account/change-status$', 'index/DarazAccount/changeStatus',[], []);

//控制器：app\index\controller\RegisterCompany
//注册公司管理列表
think\Route::get('/register-company$', 'index/RegisterCompany/index',[], []);
//添加法人信息
think\Route::post('/register-company/legal-info$', 'index/RegisterCompany/saveLegalInfo',[], []);
//更新法人信息
think\Route::put('/register-company/:id/legal-info$', 'index/RegisterCompany/updateLegalInfo',[], ['id'=>'(\d+)']);
//获取法人信息详情
think\Route::get('/register-company/:id/legal-info$', 'index/RegisterCompany/getLegalInfo',[], ['id'=>'(\d+)']);
//状态列表
think\Route::get('/register-company/status$', 'index/RegisterCompany/getStatus',[], []);
//保存公司信息
think\Route::put('/register-company/:id/company-info$', 'index/RegisterCompany/saveCompanyInfo',[], ['id'=>'(\d+)']);
//获取公司信息
think\Route::get('/register-company/:id/company-info$', 'index/RegisterCompany/getCompanyInfo',[], []);
//上传营业执照
think\Route::put('/register-company/:id/charter$', 'index/RegisterCompany/saveCharter',[], []);
//保存结账信息
think\Route::put('/register-company/:id/settlement$', 'index/RegisterCompany/saveSettlement',[], []);
//获取操作日志信息
think\Route::get('/register-company/:id/logs$', 'index/RegisterCompany/logs',[], ['id'=>'(\d+)']);
//获取结账信息
think\Route::get('/register-company/:id/settlement$', 'index/RegisterCompany/getSettlement',[], []);
//获取营业执照
think\Route::get('/register-company/:id/charter$', 'index/RegisterCompany/getCharter',[], []);

//控制器：app\customerservice\controller\KeywordsManage
//关键词列表
think\Route::GET('/keywords-manage$', 'customerservice/KeywordsManage/index',[], []);
//显示一条记录
think\Route::GET('/keywords-manage/view$', 'customerservice/KeywordsManage/view',[], []);
//增加一条记录
think\Route::POST('/keywords-manage/add$', 'customerservice/KeywordsManage/addKeyword',[], []);
//删除一条记录
think\Route::DELETE('/keywords-manage/delete$', 'customerservice/KeywordsManage/deleteKeyword',[], []);
//关键词类型
think\Route::GET('/keywords-manage/type$', 'customerservice/KeywordsManage/allType',[], []);
//渠道
think\Route::GET('/keywords-manage/channel$', 'customerservice/KeywordsManage/channels',[], []);
//根据权限过滤渠道
think\Route::GET('/keywords-manage/permissioned-channel$', 'customerservice/KeywordsManage/permissionedChannel',[], []);
//关键词启用状态
think\Route::PUT('/keywords-manage/keyword-status$', 'customerservice/KeywordsManage/keywordStatus',[], []);

//控制器：app\customerservice\controller\KeywordsRecord
//关键词抓取记录列表
think\Route::GET('/keywords-list$', 'customerservice/KeywordsRecord/index',[], []);
//增加关键词抓取记录
think\Route::POST('/keywords-list/add$', 'customerservice/KeywordsRecord/addKeyword',[], []);
//查看消息
think\Route::GET('/keywords-list/view$', 'customerservice/KeywordsRecord/viewMessage',[], []);
//关键词类型
think\Route::GET('/keywords-list/type$', 'customerservice/KeywordsRecord/allType',[], []);
//获取渠道
think\Route::GET('/keywords-list/channel$', 'customerservice/KeywordsRecord/channels',[], []);
//获取ebay账号
think\Route::GET('/keywords-list/ebay-account$', 'customerservice/KeywordsRecord/getEbayAccount',[], []);
//获取amazon账号
think\Route::GET('/keywords-list/amazon-account$', 'customerservice/KeywordsRecord/getAmazonAccount',[], []);
//获取aliexpress账号
think\Route::GET('/keywords-list/aliexpress-account$', 'customerservice/KeywordsRecord/getAliexpressAccount',[], []);

//控制器：app\publish\controller\AmazonShippingGroupName
//amazon运费模板名列表
think\Route::get('/publish/amazon-shipping-group-name$', 'publish/AmazonShippingGroupName/index',[], []);
//帐号运费模板名
think\Route::get('/publish/amazon-shipping-group-name/:account_id/read$', 'publish/AmazonShippingGroupName/read',[], []);
//添加模板名
think\Route::post('/publish/amazon-shipping-group-name$', 'publish/AmazonShippingGroupName/add',[], []);
//修改模板名
think\Route::put('/publish/amazon-shipping-group-name$', 'publish/AmazonShippingGroupName/update',[], []);
//删除模板名
think\Route::delete('/publish/amazon-shipping-group-name$', 'publish/AmazonShippingGroupName/delete',[], []);

//控制器：app\order\controller\VirtualTracking
//虚拟订单列表
think\Route::get('/virtual-tracking$', 'order/VirtualTracking/index',[], []);
//生成虚拟跟踪号
think\Route::post('/virtual-tracking/:id/virtual-number$', 'order/VirtualTracking/getShippingNumber',[], []);
//保存虚拟跟踪号
think\Route::put('/virtual-tracking/:id/virtual-number$', 'order/VirtualTracking/saveShippingNumber',[], []);
//批量生成虚拟跟踪号
think\Route::post('/virtual-tracking/batch/virtual-number$', 'order/VirtualTracking/batchShippingNumber',[], []);
//批量保存虚拟跟踪号
think\Route::put('/virtual-tracking/batch/virtual-number$', 'order/VirtualTracking/batchSaveShippingNumber',[], []);
//批量标记处理
think\Route::put('/virtual-tracking/batch/dispose$', 'order/VirtualTracking/batchDispose',[], []);
//导出execl
think\Route::post('/virtual-tracking/export$', 'order/VirtualTracking/export',[], []);
//execl字段信息
think\Route::get('/virtual-tracking/title$', 'order/VirtualTracking/title',[], []);

//控制器：app\publish\controller\EbayBestOffer
//获取best offers列表
think\Route::GET('/ebay/best-offers$', 'publish/EbayBestOffer/index',[], []);
//同步best offer
think\Route::post('/ebay/best-offers/sync$', 'publish/EbayBestOffer/sync',[], []);
//删除best offer
think\Route::delete('/ebay/best-offers/batch$', 'publish/EbayBestOffer/del',[], []);
//处理best offer
think\Route::post('/ebay/best-offers/batch$', 'publish/EbayBestOffer/deal',[], []);

//控制器：app\order\controller\DarazOrder
//订单列表
think\Route::GET('/daraz-orders$', 'order/DarazOrder/index',[], []);
//显示指定的资源
think\Route::get('/daraz-orders/:id$', 'order/DarazOrder/read',[], ['id'=>'(\d+)']);
//获取状态列表
think\Route::get('/daraz-orders/status$', 'order/DarazOrder/status',[], []);
//添加物流商
think\Route::POST('/daraz-orders/add-carrier$', 'order/DarazOrder/addCarrier',[], []);

//控制器：app\index\controller\CreditCard
//信用卡账号列表
think\Route::GET('/credit-card$', 'index/CreditCard/index',[], []);
//新增信用卡记录
think\Route::POST('/credit-card$', 'index/CreditCard/save',[], []);
//显示信用卡详细.
think\Route::GET('/credit-card/:id/edit$', 'index/CreditCard/edit',[], []);
//修改信用卡记录
think\Route::POST('/credit-card/:id/update$', 'index/CreditCard/update',[], []);
//删除信用卡记录
think\Route::delete('/credit-card/:id/delete$', 'index/CreditCard/delete',[], []);
//查询信用卡类别列表
think\Route::get('/credit-card/category$', 'index/CreditCard/categoryList',[], []);

//控制器：app\index\controller\ChannelDistribution
//获取展示的产品状态
think\Route::get('/channel-distribution/status$', 'index/ChannelDistribution/getStatus',[], []);
//获取一级分类
think\Route::get('/channel-distribution/first-categories$', 'index/ChannelDistribution/getFirstCategories',[], []);
//获取站点
think\Route::get('/channel-distribution/:id/sites$', 'index/ChannelDistribution/getSites',[], []);
//获取平台帐号
think\Route::get('/channel-distribution/:id/accounts$', 'index/ChannelDistribution/getAccounts',[], []);
//获取平台部门
think\Route::get('/channel-distribution/:id/departments$', 'index/ChannelDistribution/getDepartments',[], []);
//获取受限职位
think\Route::get('/channel-distribution/positions$', 'index/ChannelDistribution/getPositions',[], []);
//整个保存
think\Route::PUT('/channel-distribution/:id$', 'index/ChannelDistribution/update',[], ['id'=>'(\d+)']);

//测试
think\Route::get('/publish/test/test$', 'publish/test/test',[], []);

think\Route::get('/publish/lazada/listing/sellers$', 'publish/LazadaListing/sellers',[], []);
think\Route::get('/publish/lazada/listing/accounts$', 'publish/LazadaListing/accounts',[], []);
think\Route::get('/publish/lazada/listing$', 'publish/LazadaListing/index',[], []);
think\Route::get('/publish/lazada/listing/:id/edit$', 'publish/LazadaListing/edit',[], []);
think\Route::get('/publish/lazada/listing/choice-accounts', 'publish/LazadaListing/choiceAccounts',[], []);
think\Route::GET('/publish/lazada/unpublishedList', 'publish/LazadaPublish/unpublishedList',[], []);
think\Route::GET('/publish/lazada/productInfo', 'publish/LazadaPublish/GetProductInfo',[], []);
think\Route::GET('/publish/lazada/test', 'publish/LazadaPublish/test',[], []);
think\Route::POST('/publish/lazada/productsDel', 'publish/LazadaPublish/productsDel',[], []);
think\Route::GET('/publish/lazada/showLogs', 'publish/LazadaPublish/showLogs',[], []);
think\Route::get('/publish/shopee/test', 'publish/Shopee/test',[], []);
think\Route::get('/publish/common/test', 'publish/Common/getPublishedDaily',[], []);

