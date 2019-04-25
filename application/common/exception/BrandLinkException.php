<?php


namespace app\common\exception;


use app\common\model\GoodsBrandLinkSku;
use think\Exception;

//品连异常类
class BrandLinkException extends Exception
{
    public function __construct($message = "", $code = 400, Throwable $previous = null)
    {
        if (is_string($message)) {
            parent::__construct($message, $code);
        } elseif (is_array($message)) {
            $skuId = $message['sku_id'];
            $log = $message['log'];
            $pushStatus = $message['push_status'];
            $bSku = GoodsBrandLinkSku::get(['sku_id' => $skuId]);
            $bSku->save([
                'push_status' => $pushStatus,
                'log' => $log,
                'push_time' => time()
            ]);
            parent::__construct($log, $code);
        }
    }
}