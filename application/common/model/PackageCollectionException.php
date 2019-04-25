<?php


namespace app\common\model;


use erp\ErpModel;
use app\common\cache\Cache;

class PackageCollectionException extends ErpModel
{
    /**
     * 重量异常
     */
    const TYPE_WEIGHT = 1;

    /**
     *物流方式不匹配
     */
    const TYPE_ERROR_SHIPPING = 2;

    /**
     * 包裹被取消
     */
    const TYPE_CANCEL = 3;

    /**
     * 面单异常
     */
    const TYPE_ERROR_LABEL = 4;
    /**
     * 包裹失效
     */
    const TYPE_STOP = 5;
    /**
     * 渠道超重
     */
    const TYPE_SHIPPING_WEIGHT = 6;
    /**
     * 地区不可达
     */
    const TYPE_COUNTRY_CANNOT = 7;
    /**
     *人工加入
     */
    const TYPE_SELF = 8;
    /**
     * 物流方式停用
     */
    const TYPE_TIMEOUT = 9;
    /**
     * 超尺寸异常
     */
    const TYPE_SIZE = 10;

    /**
     * 待处理
     */
    const STATUS_WAIT = 1;

    /**
     * 已处理
     */
    const STATUS_DONE = 2;

    /**
     * 类型文本
     */
    const TYPE_TXT = [
        self::TYPE_WEIGHT => '重量异常',
        self::TYPE_ERROR_SHIPPING => '物流渠道不匹配',
        self::TYPE_CANCEL => '包裹取消',
        self::TYPE_ERROR_LABEL => '面单异常',
        self::TYPE_STOP => '包裹失效',
        self::TYPE_SHIPPING_WEIGHT => '渠道超重',
        self::TYPE_COUNTRY_CANNOT => '地区不可达',
        self::TYPE_SELF => '人工加入',
        self::TYPE_TIMEOUT => '物流方式停用',
        self::TYPE_SIZE => '超尺寸异常'
    ];
    /**
     * 状态文本
     */
    const STATUS_TXT = [
        self::STATUS_WAIT => '待处理',
        self::STATUS_DONE => '已处理'
    ];
    public const WAY_CANCEL = 0;//拆包
    public const WAY_SET_WEIGHT = 1;//设置预估重量
    public const WAY_RE_PACKAGE = 2;//重新集包
    public const WAY_RE_PRINT = 3;//重打面单
    public const WAY_RE_ORDER = 4;//重新下单
    public const WAY_RE_CHANGE = 5;//更改物流方式
    public const WAY_RE_SEND = 6;//发货


    //重量异常-包裹作废
    const METHOD_WEIGHT_CANCEL = self::TYPE_WEIGHT . self::WAY_CANCEL;

    //重量异常-设置预估重量
    const METHOD_WEIGHT_CHANGE_WEIGHT = self::TYPE_WEIGHT . self::WAY_SET_WEIGHT;

    //物流方式错误-包裹作废
    const METHOD_SHIPPING_CANCEL = self::TYPE_ERROR_SHIPPING . self::WAY_CANCEL;

    //物流方式错误-重新集包
    const METHOD_SHIPPING_RESET = self::TYPE_ERROR_SHIPPING . self::WAY_RE_PACKAGE;

    //包裹取消-作废
    const METHOD_CANCEL_CANCEL = self::TYPE_CANCEL . self::WAY_CANCEL;

    //面单异常-作废
    const METHOD_ERROR_CANCEL = self::TYPE_ERROR_LABEL . self::WAY_CANCEL;//40;

    //面单异常-重打面单
    const METHOD_ERROR_RESET = self::TYPE_ERROR_LABEL . self::WAY_RE_PRINT;//41;
    //包裹超时-包裹作废
    const METHOD_STOP_CANCEL = self::TYPE_STOP . self::WAY_CANCEL;//50;
    //包裹超时-重新集包
    const METHOD_TIMEOUT_RESET = self::TYPE_STOP . self::WAY_RE_PACKAGE;//51;

    //包裹超时-重新下单
    const METHOD_TIMEOUT_RESET_ORDER = self::TYPE_STOP . self::WAY_RE_ORDER; //52;

    //包裹超时-更改物流方式
    const METHOD_TIMEOUT_CHANGE = self::TYPE_STOP . self::WAY_RE_CHANGE;//53;

    //渠道超重-包裹作废
    const METHOD_HEAVY_CANCEL = self::TYPE_SHIPPING_WEIGHT . self::WAY_CANCEL; //60;

    //渠道超重-重新集包
    const METHOD_HEAVY_RESET = self::TYPE_SHIPPING_WEIGHT . self::WAY_RE_PACKAGE;//61;

    //渠道超重-重打面单
    const METHOD_HEAVY_RESET_PACKAGE = self::TYPE_SHIPPING_WEIGHT . self::WAY_RE_PRINT;//62;

    //渠道超重-改邮寄方式
    const METHOD_HEAVY_CHANGE = self::TYPE_SHIPPING_WEIGHT . self::WAY_RE_CHANGE;//63;

    //地区不可达-包裹作废
    const METHOD_CANNOT_CANCEL = self::TYPE_COUNTRY_CANNOT . self::WAY_CANCEL; //70;

    //地区不可达-重新集包
    const METHOD_CANNOT_RESET = self::TYPE_COUNTRY_CANNOT . self::WAY_RE_PACKAGE;//71;

    //人工录入-包裹作废
    const METHOD_SELF_CANCEL = self::TYPE_SELF . self::WAY_CANCEL;//80;

    //人工录入-设置预估重量
    const METHOD_SELF_CHANGE_WEIGHT = self::TYPE_SELF . self::WAY_SET_WEIGHT;//81;

    //人工录入-重新集包
    const METHOD_SELF_RESET = self::TYPE_SELF . self::WAY_RE_PACKAGE;//82;

    //人工录入-重打面单
    const METHOD_SELF_RESET_LABEL = self::TYPE_SELF . self::WAY_RE_PRINT; //83;
    //人工录入-发货
    const METHOD_SEND = self::TYPE_SELF . self::WAY_RE_SEND;//84;
    //物流停用，重返上架
    const METHOD_TIMEOUT_CANCEL = self::TYPE_TIMEOUT . self::WAY_CANCEL;//90;
    /**
     * 超尺寸异常-作废
     */
    const METHOD_SIZE_CANCEL = self::TYPE_SIZE . self::WAY_CANCEL;//100;
    /**
     * 超尺寸异常-重打面单
     */
    const METHOD_SIZE_RESET_LABEL = 101;

    const METHOD_TEXT = [
        self::METHOD_WEIGHT_CANCEL => '重返上架',
        self::METHOD_WEIGHT_CHANGE_WEIGHT => '设置预估重量',
        self::METHOD_SHIPPING_CANCEL => '重返上架',
        self::METHOD_SHIPPING_RESET => '重新集包',
        self::METHOD_ERROR_CANCEL => '重返上架',
        self::METHOD_CANCEL_CANCEL => '重返上架',
        self::METHOD_ERROR_RESET => '重打面单',
        self::METHOD_STOP_CANCEL => '重返上架',
        self::METHOD_TIMEOUT_RESET => '重新集包',
        self::METHOD_TIMEOUT_RESET_ORDER => '重新下单',
        self::METHOD_TIMEOUT_CHANGE => '更改物流方式',
        self::METHOD_HEAVY_CANCEL => '重返上架',
        self::METHOD_HEAVY_RESET_PACKAGE => '重打面单',
        self::METHOD_HEAVY_RESET => '重新集包',
        self::METHOD_HEAVY_CHANGE => '改邮寄方式',
        self::METHOD_CANNOT_CANCEL => '重返上架',
        self::METHOD_CANNOT_RESET => '重新集包',
        self::METHOD_SELF_CANCEL => '重返上架',
        self::METHOD_SELF_CHANGE_WEIGHT => '设置预估重量',
        self::METHOD_SELF_RESET => '重新集包',
        self::METHOD_SELF_RESET_LABEL => '重打面单',
        self::METHOD_SEND => '发货',
        self::METHOD_TIMEOUT_CANCEL => '重返上架',
        self::METHOD_SIZE_CANCEL => '重返上架',
        self::METHOD_SIZE_RESET_LABEL => '重打面单',

    ];

    public const METHOD_INFO = [
        self::WAY_CANCEL => [
            'code' => 'package_cancel',
            'title' => '拆包',
            'url' => 'put|package-collection/problem/:id/package-cancel',
            'type' => 'button',
            'use_type' => [
                PackageCollectionException::TYPE_WEIGHT,
                PackageCollectionException::TYPE_SHIPPING_WEIGHT,
                PackageCollectionException::TYPE_COUNTRY_CANNOT,
                PackageCollectionException::TYPE_TIMEOUT,
                PackageCollectionException::TYPE_SIZE
            ]
        ],
        self::WAY_SET_WEIGHT => [
            'code' => 'set_weight',
            'url' => 'put|package-collection/problem/:package_id/estimated-weight',
            'title' => '设置预估重量',
            'type' => 'button',
            'param' => ['estimated_weight' => 0],
            'use_type' => []


        ],
        self::WAY_RE_PACKAGE => [
            'code' => 're_package',
            'title' => '重新集包',
            'url' => 'post|package-collection/reset-collection',
            'param' => [
                'id' => 0
            ],
            'type' => 'button',
            'use_type' => []
        ],
        self::WAY_RE_PRINT => [
            'code' => 'print',
            'title' => '重打面单',
            'url' => 'post|delivery-check/:package_id/print',
            'callback' => 'post|package-collection/problem/print-callback',
            'callback_param' => [
                'package_id' => 0
            ],
            'type' => 'auto_print',
            'use_type' => []

        ],
        self::WAY_RE_ORDER => [
            'code' => 're_order',
            'title' => '重新下单',
            'url' => 'post|package-collection/problem/continue-order',
            'param' => [
                'package_id' => 0
            ],
            'type' => 'button',
            'use_type' => []
        ],
        self::WAY_RE_CHANGE => [
            'code' => 're_change',
            'title' => '更改物流方式',
            'url' => 'post|package-collection/problem/change-shipping',
            'param' => [
                'package_id' => 0
            ],
            'type' => 'button',
            'use_type' => []
        ],
        self::WAY_RE_SEND => [
            'code' => 'send',
            'title' => '发货',
            'url' => 'post|package-collection/self-do',
            'param' => [
                'id' => 0
            ],
            'type' => 'button',
            'use_type' => []
        ]
    ];

    public function getExceptionTypeTxtAttr($value, $data)
    {
        return isset(self::TYPE_TXT[$data['exception_type']]) ? self::TYPE_TXT[$data['exception_type']] : '';
    }

    public function getStatusTxtAttr($value, $data)
    {
        return isset(self::STATUS_TXT[$data['status']]) ? self::STATUS_TXT[$data['status']] : '';
    }

    public function getCreatorAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['creator_id']);
        return $user ? $user['realname'] : '';
    }

    public function getHandlerAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['handler_id']);
        return $user ? $user['realname'] : '';
    }

    public function getMethodTxtAttr($value, $data)
    {
        return self::METHOD_TEXT[$data['method']] ?? '';
    }

    public function getRemainingTimeAttr($value, $data)
    {
        $now = time();
        if ($now - $data['create_time'] > 86400) {
            return '已超时';
        }
        $inc = ($data['create_time'] + 86400) - $now;
        $hour = floor($inc / 3600);
        $minute = floor(($inc - 3600 * $hour) / 60);
        $second = floor((($inc - 3600 * $hour) - 60 * $minute) % 60);
        $result = [];
        if ($hour) {
            $result[] = $hour;
        }
        if ($minute) {
            $result[] = $minute;
        }
        if ($second) {
            $result[] = $second;
        }
        return implode(':', $result);
    }


}