<?php
namespace app\carrier\service;

class CarrierWinit extends \app\carrier\service\CarrierWinitBase
{
    const API_VERSION = 'v1';

    /**
     * @param array $config Configuration option values.
     */
    public function __construct(array $config = [])
    {
        parent::__construct('http://api.winit.com.cn/ADInterface/api', 'http://erp.sandbox.winit.com.cn/ADInterface/api', $config);
    }

    /**
     * @param \app\carrier\type\\winit\GetTokenRequestType $request
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getToken(\app\carrier\type\winit\GetTokenRequestType $request)
    {
        return $this->callOperationAsync($request);
    }

    /**
     * @param \app\carrier\type\winit\BaseRequestType $request
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getCategories(\app\carrier\type\winit\BaseRequestType $request)
    {
        return $this->callOperationAsync($request);
    }

    /**
     * @param \app\carrier\type\winit\BaseRequestType $request
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getWarehouse(\app\carrier\type\winit\BaseRequestType $request)
    {
        return $this->callOperationAsync($request);
    }
    
    /**
     * @param \app\carrier\type\winit\BaseRequestType $request
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function queryDeliveryWay(\app\carrier\type\winit\BaseRequestType $request)
    {
        return $this->callOperationAsync($request);
    }
    
    /**
     * @param \app\carrier\type\winit\BaseRequestType $request
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function queryWarehouseStorage(\app\carrier\type\winit\BaseRequestType $request)
    {
        return $this->callOperationAsync($request);
    }
    
    /**
     * @param \app\carrier\type\winit\BaseRequestType $request
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getItemInformation(\app\carrier\type\winit\BaseRequestType $request)
    {
        return $this->callOperationAsync($request);
    }
    
    /**
     * @param \app\carrier\type\winit\BaseRequestType $request
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function createOutboundInfo(\app\carrier\type\winit\BaseRequestType $request)
    {
        return $this->callOperationAsync($request);
    }
    
    /**
     * @param \app\carrier\type\winit\BaseRequestType $request
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getHttpRequest(\app\carrier\type\winit\BaseRequestType $request)
    {
        return $this->callOperationAsync($request);
    }
}


