<?php
namespace app\api\config;
/**
 * Created by PhpStorm.
 * User: Phill
 * Date: 2017/4/6
 * Time: 10:05
 */

/**
 *  api 接口URL构造
 *  name: 接口名称
 *  sign: 签名验证 0:不验证  1：验证
 *  make: 接口权限 1：公开  2：登录
 *  status: 接口状态 1：开放  -1：关闭
 *  logs: 0: 不记录  1： 记录日志
 *  mark: false 不需要版本校验   true 检验版本
 *  visit:  每分钟最大访问量
 */
$apiUrl['goods']   = ['method' => 'getGoodsId', 'sign' => 1, 'logs' => 1, 'service' => 'goods', 'visit' => 20, 'status' => 1, 'mark' => false, 'make' => 1];
$apiUrl['install'] = ['name'=>'在线安装app', 'service' =>'install',	'method' => 'test',	'sign' => 1, 'make'=>2, 'status'=>1, 'mark' => false,'visit' => 10];
$apiUrl['auth'] = ['name'=>'阿里巴巴授权接口', 'service' =>'auth','method' => 'index','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['getToken'] = ['name'=>'登录接口获取token', 'service' =>'Server','method' => 'getToken','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['serverAccountsNew'] = ['name'=>'获取服务器渠道账号信息新', 'service' =>'Server','method' => 'serverAccountsNew','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['serverAccounts'] = ['name'=>'获取服务器渠道账号信息', 'service' =>'Server','method' => 'serverAccounts','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['getAllChannelNodeUrl'] = ['name'=>'获取登录节点表地址信息', 'service' =>'Server','method' => 'getAllChannelNodeUrl','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['userServer'] = ['name'=>'获取用户已授权的服务器信息', 'service' =>'Server','method' => 'userServer','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['domainServer'] = ['name'=>'通过域名获取服务器信息', 'service' =>'Server','method' => 'domainServer','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['serverLog'] = ['name'=>'记录访问日志', 'service' =>'Server','method' => 'log','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['receiveProduct'] = ['name'=>'接收产品数据', 'service' =>'Product','method' => 'receiveProduct','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['getProducts'] = ['name'=>'获取产品数据', 'service' =>'Product','method' => 'getProducts','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['receiveSupplier'] = ['name'=>'接收供应商数据', 'service' =>'Supplier','method' => 'receiveSupplier','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['updateGoodsPrice'] = ['name'=>'修改产品数据', 'service' =>'Product','method' => 'updateGoodsPrice','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['updateGoodsStatus'] = ['name'=>'修改产品[sku]上下架情况', 'service' =>'Product','method' => 'updateGoodsStatus','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['updateGoodsInfo'] = ['name'=>'修改商品数据', 'service' =>'Product','method' => 'updateGoodsInfo','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['updateSupplier'] = ['name'=>'修改供应商信息', 'service' =>'Supplier','method' => 'updateSupplier','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['upgrade'] = ['name'=>'客户端升级', 'service' =>'Server','method' => 'upgrade','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['goodsDeclare'] = ['name'=>'产品申报价信息', 'service' =>'goods','method' => 'declareInfo','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['goodsDeclareDetail'] = ['name'=>'产品申报详情', 'service' =>'goods','method' => 'detail','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['printCallback'] = ['name'=>'打印回调', 'service' =>'printer','method' => 'callBack','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['codeTag'] = ['name'=>'条形码图片', 'service' =>'Barcode','method' => 'codeTag','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['testShipping'] = ['name'=>'测试展示面单', 'service' =>'Barcode','method' => 'testShipping','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['updateSalesStatus'] = ['name'=>'更改sku上下架接口', 'service' =>'Product','method' => 'updateSalesStatus','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['tracking'] = ['name'=>'跟踪号物流信息更新', 'service' =>'ShippingTracking','method' => 'tracking','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['addSku'] = ['name'=>'新增sku', 'service' =>'Product','method' => 'addSku','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['notice'] = ['name'=>'人员变更通知', 'service' =>'Server','method' => 'changeNotice','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['addUserByOA'] = ['name'=>'新增人员', 'service' =>'Server','method' => 'addUser','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['checkIsOverWeight'] = ['name'=>'检查包裹是否已超重', 'service' =>'Package','method' => 'checkIsOverWeight','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['saveDescription'] = ['name'=>'保存多语言', 'service' =>'Product','method' => 'saveDescription','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['orderReceive'] = ['name'=>'订单接收', 'service' =>'Order','method' => 'receive','sign' => 1, 'make'=>1, 'status'=>1, 'mark' => false,'visit' => 1000];
$apiUrl['orderCancel'] = ['name'=>'订单取消', 'service' =>'Order','method' => 'cancel','sign' => 1, 'make'=>1, 'status'=>1, 'mark' => false,'visit' => 100];
$apiUrl['orderShippingInfo'] = ['name'=>'订单取消', 'service' =>'Order','method' => 'shipping','sign' => 1, 'make'=>1, 'status'=>1, 'mark' => false,'visit' => 100];
$apiUrl['orderSpeedInfo'] = ['name'=>'订单进度信息', 'service' =>'Order','method' => 'speed','sign' => 1, 'make'=>1, 'status'=>1, 'mark' => false,'visit' => 100];
$apiUrl['orderUpdateShipping'] = ['name'=>'订单物流方式更改', 'service' =>'Order','method' => 'updateShipping','sign' => 1, 'make'=>1, 'status'=>1, 'mark' => false,'visit' => 100];
$apiUrl['orderUpdateAddress'] = ['name'=>'订单收件人信息更改', 'service' =>'Order','method' => 'updateAddress','sign' => 1, 'make'=>1, 'status'=>1, 'mark' => false,'visit' => 100];
$apiUrl['getGoodsLang'] = ['name'=>'获取商品多语言信息', 'service' =>'Product','method' => 'getGoodsLang','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false,'visit' => 100];
$apiUrl['getWarehouse'] = ['name'=>'获取仓库列表', 'service' =>'Shipping','method' => 'getWarehouse','sign' => 1, 'make'=>1, 'status'=>1, 'mark' => false,'visit' => 100];
$apiUrl['getCarrier'] = ['name'=>'获取物流商列表', 'service' =>'Shipping','method' => 'getCarrier','sign' => 1, 'make'=>1, 'status'=>1, 'mark' => false,'visit' => 100];
$apiUrl['getWarehouseShipping'] = ['name'=>'仓库支持物流渠道', 'service' =>'Shipping','method' => 'getWarehouseShipping','sign' => 1, 'make'=>1, 'status'=>1, 'mark' => false,'visit' => 100];
$apiUrl['trial'] = ['name'=>'运费试算', 'service' =>'Shipping','method' => 'trial','sign' => 1, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['getInventory'] = ['name'=>'获取库存', 'service' =>'Shipping','method' => 'getInventory','sign' => 1, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['getCategory'] = ['name'=>'获取分类', 'service' =>'Product','method' => 'getCategory','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['getAttr'] = ['name'=>'获取属性', 'service' =>'Product','method' => 'getAttr','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['writeBackTracking'] = ['name'=>'回写跟踪号', 'service' =>'Shipping','method' => 'writeBackTracking','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['reporting'] = ['name'=>'服务器定时上报', 'service' =>'Server','method' => 'reporting','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['cancelLogistics'] = ['name'=>'分销外海取消物流', 'service' =>'Shipping','method' => 'cancelLogistics','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['getTokenDingTalk'] = ['name'=>'通过钉钉工号生成token', 'service' =>'Dingtalk','method' => 'getTokenDingTalk','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['recordUserAgent'] = ['name'=>'记录用户代理信息', 'service' =>'Server','method' => 'recordUserAgent','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['superCode'] = ['name'=>'超级浏览器获取code', 'service' =>'SuperBrowser','method' => 'code','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['superLogin'] = ['name'=>'超级浏览器登录', 'service' =>'SuperBrowser','method' => 'login','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['superShops'] = ['name'=>'超级浏览器获取店铺列表', 'service' =>'SuperBrowser','method' => 'shopList','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['superShopDetail'] = ['name'=>'超级浏览器获取店铺列表', 'service' =>'SuperBrowser','method' => 'shopDetail','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['superRecord'] = ['name'=>'超级浏览器回写cookie', 'service' =>'SuperBrowser','method' => 'record','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['superBalance'] = ['name'=>'超级浏览器余额提醒', 'service' =>'SuperBrowser','method' => 'balance','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['returnAfterSale'] = ['name'=>'新增退货售后单', 'service' =>'OrderSale','method' => 'returnAfterSale','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['download_order_callback'] = ['name'=>'帐号订单下载回调', 'service' =>'Account','method' => 'downLoadCallback','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['cancelAfterSale'] = ['name'=>'取消退货售后单', 'service' =>'OrderSale','method' => 'cancelAfterSale','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['serverReport'] = ['name'=>'上报服务器', 'service' =>'Server','method' => 'report','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];


//代理自动登录接口
//$apiUrl['automationLoginUser'] = ['name'=>'服务器用户登录', 'service' =>'Automation','method' => 'loginUser','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['automationServerUser'] = ['name'=>'域名用户登录', 'service' =>'Automation','method' => 'loginServerUser','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['automationLoginCode'] = ['name'=>'验证码登录', 'service' =>'Automation','method' => 'loginCode','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['automationCode'] = ['name'=>'获取codes验证码', 'service' =>'Automation','method' => 'code','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['automationList'] = ['name'=>'拉取账号信息', 'service' =>'Automation','method' => 'shopList','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['automationListNew'] = ['name'=>'拉取账号信息', 'service' =>'Automation','method' => 'shopListNew','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['automationIp'] = ['name'=>'拉取回源IP', 'service' =>'Automation','method' => 'infoIp','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['automationInfo'] = ['name'=>'获取登录信息', 'service' =>'Automation','method' => 'shopDetail','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['automationSetCookie'] = ['name'=>'回写cookie信息', 'service' =>'Automation','method' => 'record','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['automationSetUa'] = ['name'=>'回写UA信息', 'service' =>'Automation','method' => 'recordUa','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['automationPhoneCode'] = ['name'=>'获取手机验证码', 'service' =>'Automation','method' => 'phoneCode','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['automationProxy'] = ['name'=>'拉取账号代理信息', 'service' =>'Automation','method' => 'shopProxy','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['serverRedisData'] = ['name'=>'上传服务器缓存', 'service' =>'Automation','method' => 'serverRedisData','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['automationCleanCookie'] = ['name'=>'清除cookies信息', 'service' =>'Automation','method' => 'cleanCookies','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];

$apiUrl['serverDNS'] = ['name'=>'获取服务器DNS信息', 'service' =>'Server','method' => 'serverDNS','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['serverUserUpdate'] = ['name'=>'创建服务器用户回调', 'service' =>'Server','method' => 'serverUserUpdate','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$apiUrl['serverPhoneCode'] = ['name'=>'获取服务器DNS信息', 'service' =>'Server','method' => 'phoneCode','sign' => 0, 'make'=>1, 'status'=>1, 'mark' => false];
$data['api_setting'] = $apiUrl;
return $data;