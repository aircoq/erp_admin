<?php
/**
 * Created by PhpStorm.
 * User: Reece
 * Date: 2019/1/15
 * Time: 11:36
 */
namespace app\common\service;

class HtmlTemplate
{
    public static function financeDownInvoice($data)
    {
        $html = <<<EOF
<html>
<style>
    table th{
        text-align: left;
    }
    table span{
        font-weight: bold;
    }
    .po th{
        text-align: center;
    }
    .po td{
        text-align: center;
    }
</style>
<body>
<table cellpadding="5px" cellspacing="0" border="1" style="border-bottom: 1px solid white;table-layout: fixed;width: 100%">
    <tr>
        <th colspan="4" style="text-align: center;">Commercial Invoice<br/>
            商业发票
        </th>
    </tr>

    <tr>
        <td width="25%"></td><td width="25%"></td><th width="25%">Invoice date：<br/>发票开具日期：
    </th><td width="25%">{$data['create_time_date']}</td></tr>
    <tr>
        <th>Payment Application No:<br/>
            付款申请单号：
        </th><td>{$data['id']}</td><th>Statement Cycle<br/>
        结算周期
    </th><td>{$data['balance_cycle']}
    </td>
    </tr>
    <tr>
        <th colspan="2">Shipper(complete name and address)<br/>
            发货人（全称和地址）
        </th><th colspan="2">Consignee（complete name and address)<br/>
        收货人（全称和地址）
    </th>
    </tr>
    <tr>
        <td colspan="2"><span>Company:</span><br/>
            {$data['supplier']['company_name']}
        </td>
        <td colspan="2"><span>Company:</span><br/>
            {$data['supplier']['address']}
        </td>
    </tr>
    <tr>
        <td colspan="2"><span>Add:</span><br/>
            JUNDA INDUSTRY AND TRADE LTD
        </td><td colspan="2"><span>Add:</span><br/>ROOM 1405, 14/F., LUCKY CENTRE, 165-171 WANCHAI ROAD, WANCHAI, HONG KONG
    </td>
    </tr>
    <tr>
        <th>Deposit Bank of the Shipper<br/>
            发货人开户银行
        </th>
        <td colspan="3">{$data['supplier']['accounts_bank']}</td>
    </tr>
    <tr>
        <th>SWIFT ADDRESS</th><td colspan="3">{$data['supplier']['swift_address']}</td>
    </tr>
    <tr>
        <th>CNAPS</th><td colspan="3">{$data['supplier']['cnaps']}</td>
    </tr>
    <tr>
        <th>Address of Deposit Bank<br/>
            发货人开户银行地址
        </th><td colspan="3">{$data['supplier']['bank_address']}</td>
    </tr>
    <tr>
        <th>Account name of the Shipper<br/>
            发货人收款账户名
        </th><td colspan="3">{$data['supplier']['accounts_name']}</td>
    </tr>
    <tr>
        <th>Account of the Shipper<br/>
            收款人账号
        </th><td colspan="3">{$data['supplier']['accounts']}</td>
    </tr>
</table>
<table cellpadding="5px" cellspacing="0" border="1" style="border-bottom: 1px solid white;border-top: 1px solid white;width: 100%">
    <tr class="po">
    <th>Serial number<br/>
        序号
    </th>
    <th>Purchase plan No<br/>
        采购计划编号
    </th>
    <th>Purchase Order Number of Goods<br/>
        货物采购单号
    </th>
    <th>
        Date<br/>
        日期
    </th>
    <th>
        Freight<br/>
        运费
    </th>
    <th>Total Value({$data['currency_code']})<br/>
        总价
    </th>
    </tr>
    <!--采购单列表-->
        <tr class="po"><td colspan="4" style="border-left: 1px solid white;border-right: 1px solid white;border-bottom: 1px solid white;"></td><th style="text-align: center;border-left: 1px solid white;border-bottom: 1px solid white;">Total Invoice Value :<br/>
        发票总金额
    </th><td style="border: 1px solid black;"><br/><br/>{$data['amount']}</td></tr>
</table>
<br/><br/><br/><br/>
<div style="font-weight: bold">Shipper's Signature & Stamp<br/>发货人签字、盖章</div>
</body>
</html>
EOF;
        $poList = '';
        foreach($data['detail'] as $k=>$v){
            $num = $k+1;
            $poList .= '<tr class="po">';
            $poList .= "<td>$num</td><td>{$v['purchase_plan_id']}</td><td>{$v['purchase_order_id']}</td><td>{$v['finish_time']}</td><td>{$v['shipping_cost']}</td><td>{$v['amount']}</td>";
            $poList .= '</tr>';
        }
        $html = str_replace('<!--采购单列表-->', $poList, $html);
        return $html;
    }
}