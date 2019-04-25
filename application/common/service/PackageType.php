<?php
namespace app\common\service;

/** 包裹商品类型
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/7/22
 * Time: 9:51
 */
class PackageType
{
    //单品单件
    const SinglePiece = 1;
    //单品多件
    const SingleMultiplePiece = 2;
    //多品多件
    const MultiMultiplePiece = 4;
    //含备注
    const IncludeRemarks = 8;
    //单品
    const Single = 3;

    #配货类型
    //虚拟仓
    const VirtualWarehouse = 2;
}