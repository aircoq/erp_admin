<?php
namespace app\common\service;
/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2018/11/22
 * Time: 10:28
 */
class AllocationPickingType
{
    #作废
    const Invalid = 0;
    #等待拣货
    const WaitingForPicking = 1;
    #正在拣货
    const PickingGoods = 2;
    #拣货完成
    const PickingCompleted = 3;
    #正在打包
    const Packing = 4;
    #打包完成
    const PackingCompleted = 5;
}