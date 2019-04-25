<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-6
 * Time: 上午9:31
 */

use app\index\service\TaskScheduler;
use app\index\service\TaskTimer;
use \swoole\TaskProcess;

TaskProcess::listenTask(\app\warehouse\task\LogisticsUpload::class);
TaskProcess::listenTask(\app\warehouse\task\LogisticsDelivery::class);
TaskProcess::listenTask(\app\order\task\WmsOrderSync::class);
TaskProcess::listenTask(\app\warehouse\task\AllocationInventory::class);



//TaskScheduler::listen('stockout', [], new \app\warehouse\task\StockOutLog());
