<?php
/**
 * Created by PhpStorm.
 * User: TOM
 * Date: 2017/8/18
 * Time: 10:56
 */

namespace app\api\service;

use app\api\help\ProductHelp;
use app\common\cache\Cache;
use think\Exception;
use think\exception\ErrorException;

/**
 * @desc 产品对外相关接口服务
 * Class Product
 * @package app\api\service
 */
class Product extends Base
{
    /**
     * 接收OA产品数据
     * @return array
     */
    public function receiveProduct()
    {
        try {
            $postData = $this->requestData;
            if (!isset($postData['json_content'])) {
                $this->retData = [
                    'postData' => $postData,
                    'status' => 0,
                    'message' => '参数错误'
                ];
            } else {
                $productData = json_decode($postData['json_content'], true);
                if (!is_array($productData)) {
                    $this->retData = [
                        'postData' => $postData,
                        'status' => 0,
                        'message' => '数据格式错误'
                    ];
                } else {
                    $help = new ProductHelp();
                    $result = $help->addProduct($productData);
                    $this->retData['postData'] = $postData;
                    $this->retData['response'] = $result;
                }
            }
        } catch (ErrorException $errorException) {
            $this->retData = [
                'postData' => $postData,
                'status' => 0,
                'message' => $errorException->getMessage() . $errorException->getFile() . $errorException->getLine()
            ];
        } catch (Exception $exception) {
            $this->retData = [
                'postData' => $postData,
                'status' => 0,
                'message' => $exception->getMessage() . $exception->getFile() . $exception->getLine()
            ];
        }
        return $this->retData;
    }

    /**
     * 接受OA 数据更新sku的上下架信息
     * @author starzhan <397041849@qq.com>
     */
    public function updateGoodsStatus()
    {
        try {
            $postData = $this->requestData;
            if (!isset($postData['json_content'])) {
                $this->retData = [
                    'postData' => $postData,
                    'status' => 0,
                    'message' => 'json_content为空'
                ];
            } else {
                $productData = json_decode($postData['json_content'], true);
                if (!is_array($productData)) {
                    $this->retData = [
                        'postData' => $postData,
                        'status' => 0,
                        'message' => '数据格式错误'
                    ];
                } else {
                    $help = new ProductHelp();
                    $result = $help->updateGoodPlatformSale($productData);
                    $this->retData['postData'] = $postData;
                    $this->retData['response'] = $result;
                }
            }

        } catch (ErrorException $errorException) {
            $this->retData = [
                'postData' => $postData,
                'status' => 0,
                'message' => $errorException->getMessage() . $errorException->getFile() . $errorException->getLine()
            ];
        } catch (Exception $exception) {
            $this->retData = [
                'postData' => $postData,
                'status' => 0,
                'message' => $exception->getMessage() . $exception->getFile() . $exception->getLine()
            ];
        }
        return $this->retData;
    }

    public function getProducts()
    {
        $params = $_GET;
        $this->retData['params'] = $params;
        $this->retData['response'] = [
            [
                'product_name' => 'test1',
                'spu' => 'spu1',
            ],
            [
                'product_name' => 'test2',
                'spu' => 'spu2',
            ],
        ];
        return $this->retData;
    }

    /**
     * 更新OA产品价格数据
     */
    public function updateGoodsPrice()
    {
        try {
            $postData = $this->requestData;
            if (!isset($postData['json_content'])) {
                $this->retData = [
                    'postData' => $postData,
                    'status' => 0,
                    'message' => '参数错误'
                ];
            } else {
                $productData = json_decode($postData['json_content'], true);
                if (!is_array($productData)) {
                    $this->retData = [
                        'postData' => $postData,
                        'status' => 0,
                        'message' => '数据格式错误'
                    ];
                } else {
                    $help = new ProductHelp();
                    $result = $help->updateProduct($productData);
                    $this->retData['postData'] = $postData;
                    $this->retData['response'] = $result;
                }
            }
        } catch (ErrorException $errorException) {
            $this->retData = [
                'postData' => $postData,
                'status' => 0,
                'message' => $errorException->getMessage() . $errorException->getFile() . $errorException->getLine()
            ];
        } catch (Exception $exception) {
            $this->retData = [
                'postData' => $postData,
                'status' => 0,
                'message' => $exception->getMessage() . $exception->getFile() . $exception->getLine()
            ];
        }
        return $this->retData;
    }

    /**
     * 更新OA产品信息
     */
    public function updateGoodsInfo()
    {
        try {
            $postData = $this->requestData;
            if (!isset($postData['json_content'])) {
                $this->retData = [
                    'postData' => $postData,
                    'status' => 0,
                    'message' => '参数错误'
                ];
            } else {
                $productData = json_decode($postData['json_content'], true);
                if (!is_array($productData)) {
                    $this->retData = [
                        'postData' => $postData,
                        'status' => 0,
                        'message' => '数据格式错误'
                    ];
                } else {
                    $help = new ProductHelp();
                    $result = $help->updateProductInfo($productData);
                    $this->retData['postData'] = $postData;
                    $this->retData['response'] = $result;
                }
            }
        } catch (ErrorException $errorException) {
            $this->retData = [
                'postData' => $postData,
                'status' => 0,
                'message' => $errorException->getMessage() . $errorException->getFile() . $errorException->getLine()
            ];
        } catch (Exception $exception) {
            $this->retData = [
                'postData' => $postData,
                'status' => 0,
                'message' => $exception->getMessage() . $exception->getFile() . $exception->getLine()
            ];
        }
        return $this->retData;
    }

    public function updateSalesStatus()
    {
        try {
            $postData = $this->requestData;
            if (!isset($postData['json_content'])) {
                $this->retData = [
                    'postData' => $postData,
                    'status' => 0,
                    'message' => '参数错误'
                ];
            } else {
                $productData = json_decode($postData['json_content'], true);
                if (!is_array($productData)) {
                    $this->retData = [
                        'postData' => $postData,
                        'status' => 0,
                        'message' => '数据格式错误'
                    ];
                } else {
                    $help = new ProductHelp();
                    $result = $help->updateSalesStatus($productData);
                    $this->retData['postData'] = $postData;
                    $this->retData['response'] = $result;
                }
            }
        } catch (ErrorException $errorException) {
            $this->retData = [
                'postData' => $postData,
                'status' => 0,
                'message' => $errorException->getMessage() . $errorException->getFile() . $errorException->getLine()
            ];
        } catch (Exception $exception) {
            $this->retData = [
                'postData' => $postData,
                'status' => 0,
                'message' => $exception->getMessage() . $exception->getFile() . $exception->getLine()
            ];
        }
        return $this->retData;
    }

    public function addSku()
    {
        try {
            $postData = $this->requestData;
            if (!isset($postData['json_content'])) {
                $this->retData = [
                    'postData' => $postData,
                    'status' => 0,
                    'message' => '参数错误'
                ];
            } else {
                $productData = json_decode($postData['json_content'], true);
                $help = new ProductHelp();
                $result = $help->addSKu($productData);
                $this->retData['postData'] = $postData;
                $this->retData['response'] = $result;
                return $this->retData;
            }
        } catch (Exception $exception) {
            $this->retData = [
                'postData' => $postData,
                'status' => 0,
                'response' => [
                    'success' => false,
                    'error_msg' => $exception->getMessage()

                ],
                'message' => ''
            ];
        }
        return $this->retData;
    }

    public function saveDescription()
    {
        try {
            $spu = '';
            $postData = $this->requestData;
          //  Cache::store('LogisticsLog')->setProductLog('saveDescription:postData', $postData);
            if (!isset($postData['json_content'])) {
                $this->retData = [
                    'postData' => $postData,
                    'status' => 0,
                    'message' => '参数错误'
                ];
            } else {
                $productData = json_decode($postData['json_content'], true);
                $help = new ProductHelp();
                $result = $help->saveDescription($productData);
                $this->retData['postData'] = $postData;
                $this->retData['response'] = $result;
                return $this->retData;
            }
        } catch (Exception $exception) {

            $this->retData = [
                'postData' => $postData,
                'status' => 0,
                'response' => [
                    'success' => false,
                    'error_msg' => $exception->getMessage(),
                    'error_file' => $exception->getFile(),
                    'error_line' => $exception->getLine()


                ],
                'message' => ''
            ];
        }
        $cacheKey = 'saveDescription:result';
        if ($spu) {
            $cacheKey .= ":" . $spu;
        }
       // Cache::store('LogisticsLog')->setProductLog($cacheKey, $this->retData);
        return $this->retData;
    }

    public function getGoodsLang()
    {
        $postData = $this->requestData;
        try {
            if (!isset($postData['spu'])) {
                throw new Exception('spu不能为空');
            }
            $lang_id = $postData['lang_id'] ?? 0;
            $help = new ProductHelp();
            $result = $help->getGoodsLang($postData['spu'], $lang_id);
            $this->retData['postData'] = $postData;
            $this->retData['data'] = $result;
            return $this->retData;

        } catch (Exception $exception) {
            $this->retData = [
                'postData' => $postData,
                'status' => 0,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage()
            ];
            return $this->retData;
        }

    }

    public function getCategory(){
        $help = new ProductHelp();
        $this->retData['data']  = $help->getCategory();
        return $this->retData;
    }
    public function getAttr(){
        $help = new ProductHelp();
        $this->retData['data']  = $help->getAttr();
        return $this->retData;
    }
}