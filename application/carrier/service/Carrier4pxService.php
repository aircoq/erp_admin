<?php
namespace app\carrier\service;

class Carrier4pxService extends \app\carrier\service\Carrier4pxBaseService
{
    const API_VERSION = 'v1';

    /**
     * @param array $config Configuration option values.
     */
    public function __construct(array $config = [])
    {
        parent::__construct('http://openapi.4px.com', 'http://apisandbox.4px.com', $config);
    }

    /**
     * @param \app\carrier\type\epx\GetOrderCarrierRequestType $request
     * @return \app\carrier\type\GetOrderCarrierResponseType
     */
    public function getOrderCarrier(\app\carrier\type\epx\GetOrderCarrierRequestType $request)
    {
        return $this->callOperationAsync(
            'getOrderCarrier',
            $request,
            'api/service/woms/order/getOrderCarrier'
        );
    }
    
    /**
     * @param \app\carrier\type\CreateDeliveryOrderRequestType $request
     * @return \app\carrier\type\CreateDeliveryOrderResponseType
     */
    public function createDeliveryOrder(\app\carrier\type\epx\CreateDeliveryOrderRequestType $request)
    {
        return $this->createDeliveryOrderAsync($request);
    }

    /**
     * @param \app\carrier\type\CreateDeliveryOrderRequestType $request
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function createDeliveryOrderAsync(\app\carrier\type\epx\CreateDeliveryOrderRequestType $request)
    {
        return $this->callOperationAsync(
            'CreateDeliveryOrder',
            $request,
            'api/service/woms/order/createDeliveryOrder'
        );
    }
    
     /**
     * @param \app\carrier\type\GetItemListRequestType $request
     * @return \app\carrier\type\CreateDeliveryOrderResponseType
     */
    public function getItemList(\app\carrier\type\epx\GetItemListRequestType $request)
    {
        return $this->callOperationAsync(
            'GetItemListRequestType',
            $request,
            'api/service/woms/item/getItemList'
        );
    }
    
    /**
     * @param \app\carrier\type\GetItemCategoryRequestType $request
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getItemCategory(\app\carrier\type\epx\GetItemCategoryRequestType $request)
    {
        return $this->callOperationAsync(
            'GetItemCategory',
            $request,
            'api/service/woms/item/getItemCategory'
        );
    }
       
    /**
     * @param \app\carrier\type\epx\GetInventoryRequestType $request
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getInventory(\app\carrier\type\epx\GetInventoryRequestType $request)
    {
        return $this->callOperationAsync(
            'GetInventory',
            $request,
            'api/service/woms/item/getInventory'
        );
    }
    
    /**
     * @param \app\carrier\type\epx\GetDeliveryOrderRequestType $request
     * @return object
     */
    public function getDeliveryOrder(\app\carrier\type\epx\GetDeliveryOrderRequestType $request)
    {
        return $this->callOperationAsync(
            'GetDeliveryOrder',
            $request,
            'api/service/woms/order/getDeliveryOrder'
        );
    }
    
    
    /**
     * @param \app\carrier\type\epx\CancelDeliveryOrderRequestType $request
     * @return object
     */
    public function cancelDeliveryOrder(\app\carrier\type\epx\CancelDeliveryOrderRequestType $request)
    {
        return $this->callOperationAsync(
            'CancelDeliveryOrder',
            $request,
            'api/service/woms/order/cancelDeliveryOrder'
        );
    }
       
    /**
     * @param \app\carrier\type\epx\GetDeliveryOrderListRequestType $request
     * @return object
     */
    public function getDeliveryOrderList(\app\carrier\type\epx\GetDeliveryOrderListRequestType $request)
    {
        return $this->callOperationAsync(
            'GetDeliveryOrderList',
            $request,
            'api/service/woms/order/getDeliveryOrderList'
        );
    }
    
    /**
     * @param \app\carrier\type\epx\GetOrderFeeRequestType $request
     * @return object
     */
    public function getOrderFee(\app\carrier\type\epx\GetOrderFeeRequestType $request)
    {
        return $this->callOperationAsync(
            'GetOrderFee',
            $request,
            'api/service/woms/order/GetOrderFee'
        );
    }
}


