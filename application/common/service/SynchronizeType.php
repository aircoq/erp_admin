<?php
namespace app\common\service;

/** 同步状态
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/8/14
 * Time: 19:51
 */
class SynchronizeType
{
    const UnSynchronized = 0;   //未同步
    const SynchronizationFailure = 1;  //同步失败
    const TrackingNumberUpdate = 2;    //跟踪号更新
    const InSync = 3;    //同步中
    const Ignore = 4;    //忽略
    const SyncSuccess = 5;  //同步成功
    const Hasunsynchronized = 6;  //已标记未同步跟踪号
}