<?php
namespace app\common\service;
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/8/4
 * Time: 16:40
 */
class OrderType
{
    const SystemOrder = 0;   //渠道订单
    const ManualOrder = 1;  //手工订单
    const BrushOrder = 2;    //刷单

    const OnlyOrder = 0;      //独立单
    const MergeOrder = 1;     // 合并订单
    const MorePackage = 2;    //多包裹

    const ChannelVirtual = 10000;  //虚拟列平台系数的值

    const SpeedDistribution = 'distribution';
    const SpeedPackageUploadStatus = 'package_upload_status';
    const SpeedPackageConfirmStatus = 'package_confirm_status';
    const SpeedPackingTime = 'packing_time';
    const SpeedShippingTime = 'shipping_time';
}