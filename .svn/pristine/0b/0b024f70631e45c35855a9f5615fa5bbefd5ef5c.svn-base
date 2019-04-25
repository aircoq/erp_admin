# Swagger\Client\OpenApiApi

All URIs are relative to *https://open.edisebay.com*

Method | HTTP request | Description
------------- | ------------- | -------------
[**addAddressPreference**](OpenApiApi.md#addAddressPreference) | **POST** /v1/api/AddAddressPreference | 新增地址信息
[**addConsignPreference**](OpenApiApi.md#addConsignPreference) | **POST** /v1/api/AddConsignPreference | 新增交运偏好
[**addPackage**](OpenApiApi.md#addPackage) | **POST** /v1/api/AddPackage | 指定物流服务上传包裹
[**addPackageWithoutService**](OpenApiApi.md#addPackageWithoutService) | **POST** /v1/api/AddPackageWithoutService | 无指定物流服务上传包裹
[**assignService**](OpenApiApi.md#assignService) | **POST** /v1/api/AssignService | 指定包裹物流服务
[**cancelPackages**](OpenApiApi.md#cancelPackages) | **POST** /v1/api/CancelPackages | 取消包裹信息
[**confirmPackages**](OpenApiApi.md#confirmPackages) | **POST** /v1/api/ConfirmPackages | 确认并交运包裹信息
[**deletePackages**](OpenApiApi.md#deletePackages) | **POST** /v1/api/DeletePackages | 删除包裹信息
[**fetchToken**](OpenApiApi.md#fetchToken) | **POST** /v1/api/FetchToken | 登录认证
[**getActualCost**](OpenApiApi.md#getActualCost) | **POST** /v1/api/GetActualCost | 获取包裹实际运费
[**getAddressPreferenceList**](OpenApiApi.md#getAddressPreferenceList) | **POST** /v1/api/GetAddressPreferenceList | 获取地址信息列表
[**getConsignPreferenceList**](OpenApiApi.md#getConsignPreferenceList) | **POST** /v1/api/GetConsignPreferenceList | 获取交运偏好列表
[**getDropoffSiteList**](OpenApiApi.md#getDropoffSiteList) | **POST** /v1/api/GetDropoffSiteList | 获取自送站点列表
[**getHandoverSheet**](OpenApiApi.md#getHandoverSheet) | **POST** /v1/api/GetHandoverSheet | 获取交接单打印详情
[**getItemPackageId**](OpenApiApi.md#getItemPackageId) | **POST** /v1/api/GetItemPackageId | 查询物品包裹ID
[**getLabel**](OpenApiApi.md#getLabel) | **POST** /v1/api/GetLabel | 获取面单打印详情
[**getPackageDetail**](OpenApiApi.md#getPackageDetail) | **POST** /v1/api/GetPackageDetail | 获取包裹详情
[**getPackageStatus**](OpenApiApi.md#getPackageStatus) | **POST** /v1/api/GetPackageStatus | 获取包裹状态
[**getServiceList**](OpenApiApi.md#getServiceList) | **POST** /v1/api/GetServiceList | 获取物流服务列表
[**getTrackingDetail**](OpenApiApi.md#getTrackingDetail) | **POST** /v1/api/GetTrackingDetail | 获取包裹物流跟踪信息
[**recreatePackage**](OpenApiApi.md#recreatePackage) | **POST** /v1/api/RecreatePackage | 重新发货
[**updateAddressPreference**](OpenApiApi.md#updateAddressPreference) | **POST** /v1/api/UpdateAddressPreference | 更新地址信息
[**updateConsignPreference**](OpenApiApi.md#updateConsignPreference) | **POST** /v1/api/UpdateConsignPreference | 更新交运偏好


# **addAddressPreference**
> \Swagger\Client\Model\AddAddressPreferenceResponses addAddressPreference($authorization, $add_address_preference_request)

新增地址信息

用于根据ItemID及TransactionID查询一个物品的包裹跟踪号。如果该订单使用过重新发货，则只返回最近获取的跟踪号。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$add_address_preference_request = new \Swagger\Client\Model\AddAddressPreferenceRequest(); // \Swagger\Client\Model\AddAddressPreferenceRequest | addAddressPreferenceRequest

try {
    $result = $apiInstance->addAddressPreference($authorization, $add_address_preference_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->addAddressPreference: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **add_address_preference_request** | [**\Swagger\Client\Model\AddAddressPreferenceRequest**](../Model/AddAddressPreferenceRequest.md)| addAddressPreferenceRequest |

### Return type

[**\Swagger\Client\Model\AddAddressPreferenceResponses**](../Model/AddAddressPreferenceResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **addConsignPreference**
> \Swagger\Client\Model\AddConsignPreferenceResponses addConsignPreference($authorization, $add_consign_preference_request)

新增交运偏好

用于保存用户交运信息预设，交运方式可选择上门揽收或卖家自送，上门揽收预设包括揽收地址和揽收时间段等信息，卖家自送预设包括自送站点等信息。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$add_consign_preference_request = new \Swagger\Client\Model\AddConsignPreferenceRequest(); // \Swagger\Client\Model\AddConsignPreferenceRequest | addConsignPreferenceRequest

try {
    $result = $apiInstance->addConsignPreference($authorization, $add_consign_preference_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->addConsignPreference: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **add_consign_preference_request** | [**\Swagger\Client\Model\AddConsignPreferenceRequest**](../Model/AddConsignPreferenceRequest.md)| addConsignPreferenceRequest |

### Return type

[**\Swagger\Client\Model\AddConsignPreferenceResponses**](../Model/AddConsignPreferenceResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **addPackage**
> \Swagger\Client\Model\AddPackageResponses addPackage($authorization, $add_package_request)

指定物流服务上传包裹

eBay国际物流平台用户通过调用该方法上传需要发货的包裹信息，eBay国际物流平台返回申请到的包裹追踪号。 每次呼叫只限上传一个包裹，一个包裹可以包含多个物品。每个物品都必须为在eBay 成交的物品。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$add_package_request = new \Swagger\Client\Model\AddPackageRequest(); // \Swagger\Client\Model\AddPackageRequest | addPackageRequest

try {
    $result = $apiInstance->addPackage($authorization, $add_package_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->addPackage: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **add_package_request** | [**\Swagger\Client\Model\AddPackageRequest**](../Model/AddPackageRequest.md)| addPackageRequest |

### Return type

[**\Swagger\Client\Model\AddPackageResponses**](../Model/AddPackageResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **addPackageWithoutService**
> \Swagger\Client\Model\AddPackageWithoutServiceResponses addPackageWithoutService($authorization, $add_package_without_service_request)

无指定物流服务上传包裹

eBay国际物流平台用户通过调用该方法上传需要发货的包裹信息，eBay国际物流平台将返回可用的物流服务和预估运费。 每次呼叫只限上传一个包裹，一个包裹可以包含多个物品。每个物品都必须为在eBay 成交的物品。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$add_package_without_service_request = new \Swagger\Client\Model\AddPackageWithoutServiceRequest(); // \Swagger\Client\Model\AddPackageWithoutServiceRequest | addPackageWithoutServiceRequest

try {
    $result = $apiInstance->addPackageWithoutService($authorization, $add_package_without_service_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->addPackageWithoutService: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **add_package_without_service_request** | [**\Swagger\Client\Model\AddPackageWithoutServiceRequest**](../Model/AddPackageWithoutServiceRequest.md)| addPackageWithoutServiceRequest |

### Return type

[**\Swagger\Client\Model\AddPackageWithoutServiceResponses**](../Model/AddPackageWithoutServiceResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **assignService**
> \Swagger\Client\Model\AssignServiceResponses assignService($authorization, $assign_service_request)

指定包裹物流服务

批量对eBay国际物流平台中已存在的包裹ID，指定物流服务，返回物流单号。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$assign_service_request = new \Swagger\Client\Model\AssignServiceRequest(); // \Swagger\Client\Model\AssignServiceRequest | assignServiceRequest

try {
    $result = $apiInstance->assignService($authorization, $assign_service_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->assignService: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **assign_service_request** | [**\Swagger\Client\Model\AssignServiceRequest**](../Model/AssignServiceRequest.md)| assignServiceRequest |

### Return type

[**\Swagger\Client\Model\AssignServiceResponses**](../Model/AssignServiceResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **cancelPackages**
> \Swagger\Client\Model\CancelPackagesResponses cancelPackages($authorization, $cancel_packages_request)

取消包裹信息

用于取消订单，只有状态为待交运、待取件、运输中的包裹支持取消，其中取消运输中的包裹需要物流商确认，取消成功的包裹会回到待申请运单号状态。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$cancel_packages_request = new \Swagger\Client\Model\CancelPackagesRequest(); // \Swagger\Client\Model\CancelPackagesRequest | cancelPackagesRequest

try {
    $result = $apiInstance->cancelPackages($authorization, $cancel_packages_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->cancelPackages: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **cancel_packages_request** | [**\Swagger\Client\Model\CancelPackagesRequest**](../Model/CancelPackagesRequest.md)| cancelPackagesRequest |

### Return type

[**\Swagger\Client\Model\CancelPackagesResponses**](../Model/CancelPackagesResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **confirmPackages**
> \Swagger\Client\Model\ConfirmPackagesResponses confirmPackages($authorization, $confirm_packages_request)

确认并交运包裹信息

用于确认订单，确认订单成功之后，订单会上传到物流商的系统当中。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$confirm_packages_request = new \Swagger\Client\Model\ConfirmPackagesRequest(); // \Swagger\Client\Model\ConfirmPackagesRequest | confirmPackagesRequest

try {
    $result = $apiInstance->confirmPackages($authorization, $confirm_packages_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->confirmPackages: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **confirm_packages_request** | [**\Swagger\Client\Model\ConfirmPackagesRequest**](../Model/ConfirmPackagesRequest.md)| confirmPackagesRequest |

### Return type

[**\Swagger\Client\Model\ConfirmPackagesResponses**](../Model/ConfirmPackagesResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **deletePackages**
> \Swagger\Client\Model\DeletePackagesResponses deletePackages($authorization, $cancel_packages_request)

删除包裹信息

用于删除没有申请运单号的订单，申请运单号成功的订单需要取消后才能删除。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$cancel_packages_request = new \Swagger\Client\Model\DeletePackagesRequest(); // \Swagger\Client\Model\DeletePackagesRequest | cancelPackagesRequest

try {
    $result = $apiInstance->deletePackages($authorization, $cancel_packages_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->deletePackages: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **cancel_packages_request** | [**\Swagger\Client\Model\DeletePackagesRequest**](../Model/DeletePackagesRequest.md)| cancelPackagesRequest |

### Return type

[**\Swagger\Client\Model\DeletePackagesResponses**](../Model/DeletePackagesResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **fetchToken**
> \Swagger\Client\Model\FetchTokenResponses fetchToken($authorization)

登录认证

使用Basic认证

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 注意：调用SDK时,需传入三个参数(url,devId,secret)

try {
    $result = $apiInstance->fetchToken($authorization);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->fetchToken: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 注意：调用SDK时,需传入三个参数(url,devId,secret) |

### Return type

[**\Swagger\Client\Model\FetchTokenResponses**](../Model/FetchTokenResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getActualCost**
> \Swagger\Client\Model\GetActualCostResponses getActualCost($authorization, $get_actual_cost_request)

获取包裹实际运费

获取账户明细流水（按包裹查实际结算费用，支持批量）。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$get_actual_cost_request = new \Swagger\Client\Model\GetActualCostRequest(); // \Swagger\Client\Model\GetActualCostRequest | getActualCostRequest

try {
    $result = $apiInstance->getActualCost($authorization, $get_actual_cost_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->getActualCost: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **get_actual_cost_request** | [**\Swagger\Client\Model\GetActualCostRequest**](../Model/GetActualCostRequest.md)| getActualCostRequest |

### Return type

[**\Swagger\Client\Model\GetActualCostResponses**](../Model/GetActualCostResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getAddressPreferenceList**
> \Swagger\Client\Model\GetAddressPreferenceListResponses getAddressPreferenceList($authorization, $request)

获取地址信息列表

获取已保存的地址信息列表。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$request = new \Swagger\Client\Model\GetAddressPreferenceListRequest(); // \Swagger\Client\Model\GetAddressPreferenceListRequest | request

try {
    $result = $apiInstance->getAddressPreferenceList($authorization, $request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->getAddressPreferenceList: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **request** | [**\Swagger\Client\Model\GetAddressPreferenceListRequest**](../Model/GetAddressPreferenceListRequest.md)| request |

### Return type

[**\Swagger\Client\Model\GetAddressPreferenceListResponses**](../Model/GetAddressPreferenceListResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getConsignPreferenceList**
> \Swagger\Client\Model\GetConsignPreferenceListResponses getConsignPreferenceList($authorization, $get_consign_preference_list_request)

获取交运偏好列表

获取用户的交运偏好信息。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$get_consign_preference_list_request = new \Swagger\Client\Model\GetConsignPreferenceListRequest(); // \Swagger\Client\Model\GetConsignPreferenceListRequest | getConsignPreferenceListRequest

try {
    $result = $apiInstance->getConsignPreferenceList($authorization, $get_consign_preference_list_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->getConsignPreferenceList: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **get_consign_preference_list_request** | [**\Swagger\Client\Model\GetConsignPreferenceListRequest**](../Model/GetConsignPreferenceListRequest.md)| getConsignPreferenceListRequest |

### Return type

[**\Swagger\Client\Model\GetConsignPreferenceListResponses**](../Model/GetConsignPreferenceListResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getDropoffSiteList**
> \Swagger\Client\Model\GetDropoffSiteListResponses getDropoffSiteList($authorization, $get_dropoff_site_list_request)

获取自送站点列表

获取所有自送站点信息列表。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$get_dropoff_site_list_request = new \Swagger\Client\Model\GetDropoffSiteListRequest(); // \Swagger\Client\Model\GetDropoffSiteListRequest | getDropoffSiteListRequest

try {
    $result = $apiInstance->getDropoffSiteList($authorization, $get_dropoff_site_list_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->getDropoffSiteList: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **get_dropoff_site_list_request** | [**\Swagger\Client\Model\GetDropoffSiteListRequest**](../Model/GetDropoffSiteListRequest.md)| getDropoffSiteListRequest |

### Return type

[**\Swagger\Client\Model\GetDropoffSiteListResponses**](../Model/GetDropoffSiteListResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getHandoverSheet**
> \Swagger\Client\Model\GetHandoverSheetResponses getHandoverSheet($authorization, $get_handover_sheet_request)

获取交接单打印详情

获取交接单信息，用于打印。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$get_handover_sheet_request = new \Swagger\Client\Model\GetHandoverSheetRequest(); // \Swagger\Client\Model\GetHandoverSheetRequest | getHandoverSheetRequest

try {
    $result = $apiInstance->getHandoverSheet($authorization, $get_handover_sheet_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->getHandoverSheet: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **get_handover_sheet_request** | [**\Swagger\Client\Model\GetHandoverSheetRequest**](../Model/GetHandoverSheetRequest.md)| getHandoverSheetRequest |

### Return type

[**\Swagger\Client\Model\GetHandoverSheetResponses**](../Model/GetHandoverSheetResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getItemPackageId**
> \Swagger\Client\Model\GetItemPackageIdResponses getItemPackageId($authorization, $get_item_package_id_request)

查询物品包裹ID

用于根据ItemID及TransactionID查询一个物品的包裹跟踪号。如果该订单使用过重新发货，则只返回最近获取的跟踪号。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$get_item_package_id_request = new \Swagger\Client\Model\GetItemPackageIdRequest(); // \Swagger\Client\Model\GetItemPackageIdRequest | getItemPackageIdRequest

try {
    $result = $apiInstance->getItemPackageId($authorization, $get_item_package_id_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->getItemPackageId: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **get_item_package_id_request** | [**\Swagger\Client\Model\GetItemPackageIdRequest**](../Model/GetItemPackageIdRequest.md)| getItemPackageIdRequest |

### Return type

[**\Swagger\Client\Model\GetItemPackageIdResponses**](../Model/GetItemPackageIdResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getLabel**
> \Swagger\Client\Model\GetLabelResponses getLabel($authorization, $get_label_request)

获取面单打印详情

用于打印详情单，呼叫成功后会返回标签流。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$get_label_request = new \Swagger\Client\Model\GetLabelRequest(); // \Swagger\Client\Model\GetLabelRequest | getLabelRequest

try {
    $result = $apiInstance->getLabel($authorization, $get_label_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->getLabel: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **get_label_request** | [**\Swagger\Client\Model\GetLabelRequest**](../Model/GetLabelRequest.md)| getLabelRequest |

### Return type

[**\Swagger\Client\Model\GetLabelResponses**](../Model/GetLabelResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getPackageDetail**
> \Swagger\Client\Model\GetPackageDetailResponses getPackageDetail($authorization, $get_package_detail_request)

获取包裹详情

用于获取包裹的详细信息。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$get_package_detail_request = new \Swagger\Client\Model\GetPackageDetailRequest(); // \Swagger\Client\Model\GetPackageDetailRequest | getPackageDetailRequest

try {
    $result = $apiInstance->getPackageDetail($authorization, $get_package_detail_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->getPackageDetail: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **get_package_detail_request** | [**\Swagger\Client\Model\GetPackageDetailRequest**](../Model/GetPackageDetailRequest.md)| getPackageDetailRequest |

### Return type

[**\Swagger\Client\Model\GetPackageDetailResponses**](../Model/GetPackageDetailResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getPackageStatus**
> \Swagger\Client\Model\GetPackageStatusResponses getPackageStatus($authorization, $get_package_status_request)

获取包裹状态

用于获取包裹的详细信息。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$get_package_status_request = new \Swagger\Client\Model\GetPackageStatusRequest(); // \Swagger\Client\Model\GetPackageStatusRequest | getPackageStatusRequest

try {
    $result = $apiInstance->getPackageStatus($authorization, $get_package_status_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->getPackageStatus: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **get_package_status_request** | [**\Swagger\Client\Model\GetPackageStatusRequest**](../Model/GetPackageStatusRequest.md)| getPackageStatusRequest |

### Return type

[**\Swagger\Client\Model\GetPackageStatusResponses**](../Model/GetPackageStatusResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getServiceList**
> \Swagger\Client\Model\GetServiceListResponses getServiceList($authorization, $get_service_list_request)

获取物流服务列表

获取所有的物流服务列表。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$get_service_list_request = new \Swagger\Client\Model\GetServiceListRequest(); // \Swagger\Client\Model\GetServiceListRequest | getServiceListRequest

try {
    $result = $apiInstance->getServiceList($authorization, $get_service_list_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->getServiceList: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **get_service_list_request** | [**\Swagger\Client\Model\GetServiceListRequest**](../Model/GetServiceListRequest.md)| getServiceListRequest |

### Return type

[**\Swagger\Client\Model\GetServiceListResponses**](../Model/GetServiceListResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getTrackingDetail**
> \Swagger\Client\Model\GetTrackingDetailResponses getTrackingDetail($authorization, $get_tracking_detail_request)

获取包裹物流跟踪信息

获取包裹的物流跟踪信息。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$get_tracking_detail_request = new \Swagger\Client\Model\GetTrackingDetailRequest(); // \Swagger\Client\Model\GetTrackingDetailRequest | getTrackingDetailRequest

try {
    $result = $apiInstance->getTrackingDetail($authorization, $get_tracking_detail_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->getTrackingDetail: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **get_tracking_detail_request** | [**\Swagger\Client\Model\GetTrackingDetailRequest**](../Model/GetTrackingDetailRequest.md)| getTrackingDetailRequest |

### Return type

[**\Swagger\Client\Model\GetTrackingDetailResponses**](../Model/GetTrackingDetailResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **recreatePackage**
> \Swagger\Client\Model\RecreatePackageResponses recreatePackage($authorization, $recreate_package_request)

重新发货

用于重新发货。重新发货成功之后会返回一个新的跟踪号，并且订单会移动到待交运文件夹里面。只有交运后的订单才能使用此功能。所有包裹，最多重发三次。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$recreate_package_request = new \Swagger\Client\Model\RecreatePackageRequest(); // \Swagger\Client\Model\RecreatePackageRequest | recreatePackageRequest

try {
    $result = $apiInstance->recreatePackage($authorization, $recreate_package_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->recreatePackage: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **recreate_package_request** | [**\Swagger\Client\Model\RecreatePackageRequest**](../Model/RecreatePackageRequest.md)| recreatePackageRequest |

### Return type

[**\Swagger\Client\Model\RecreatePackageResponses**](../Model/RecreatePackageResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **updateAddressPreference**
> \Swagger\Client\Model\UpdateAddressPreferenceResponses updateAddressPreference($authorization, $update_address_preference_request)

更新地址信息

根据地址ID，更新地址信息，包括发货地址和退货地址。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$update_address_preference_request = new \Swagger\Client\Model\UpdateAddressPreferenceRequest(); // \Swagger\Client\Model\UpdateAddressPreferenceRequest | updateAddressPreferenceRequest

try {
    $result = $apiInstance->updateAddressPreference($authorization, $update_address_preference_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->updateAddressPreference: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **update_address_preference_request** | [**\Swagger\Client\Model\UpdateAddressPreferenceRequest**](../Model/UpdateAddressPreferenceRequest.md)| updateAddressPreferenceRequest |

### Return type

[**\Swagger\Client\Model\UpdateAddressPreferenceResponses**](../Model/UpdateAddressPreferenceResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **updateConsignPreference**
> \Swagger\Client\Model\UpdateConsignPreferenceResponses updateConsignPreference($authorization, $update_consign_preference_request)

更新交运偏好

更新用户交运偏好信息。

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$apiInstance = new Swagger\Client\Api\OpenApiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$authorization = "authorization_example"; // string | 调用登录认证接口取得的token值
$update_consign_preference_request = new \Swagger\Client\Model\UpdateConsignPreferenceRequest(); // \Swagger\Client\Model\UpdateConsignPreferenceRequest | updateConsignPreferenceRequest

try {
    $result = $apiInstance->updateConsignPreference($authorization, $update_consign_preference_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OpenApiApi->updateConsignPreference: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **authorization** | **string**| 调用登录认证接口取得的token值 |
 **update_consign_preference_request** | [**\Swagger\Client\Model\UpdateConsignPreferenceRequest**](../Model/UpdateConsignPreferenceRequest.md)| updateConsignPreferenceRequest |

### Return type

[**\Swagger\Client\Model\UpdateConsignPreferenceResponses**](../Model/UpdateConsignPreferenceResponses.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

