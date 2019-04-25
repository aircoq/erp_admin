<?php

namespace app\common\service;

use app\common\model\McaNode;
use erp\ErpRbac;
use think\Request;

/**
 * 获取过滤器内容
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/6/8
 * Time: 10:24
 */
class Filter
{
    protected $object;
    protected $route;
    protected $controller;
    protected $isEffective = false;
    protected $filterContent;
    protected $userId = NULL;

    public function __construct($object, $is_full_route = false, $controller = null)
    {
        $this->object = $object;
        $request = Request::instance();
        $this->controller = $controller;
        $this->route = $request->routeInfo();
        $this->filterContent($is_full_route);
    }

    /**
     * 解析
     * @param $is_full_route
     */
    private function filterContent($is_full_route)
    {
        $access = ErpRbac::getRbac();
        if (!empty($this->route)) {
            if (empty($this->controller)) {
                if ($is_full_route) {
                    $request_router = "/" . implode('/', $this->route['rule']);
                } else {
                    $request_router = "/" . reset($this->route['rule']);
                }
                $nodeId = McaNode::node_id('get', $request_router);
            } else {
                $this->controller = $this->controller . '::index';
                $nodeId = (new McaNode())->where(['name' => $this->controller])->value('id');
            }
            $filters = $access->getFilters($nodeId);
            foreach ($filters as $filter => $params) {
                if ($this->object == $filter) {
                    $filter = new $filter($params);
                    if($this->userId){
                        $this->filterContent = $filter->generate($this->userId);
                    }else{
                        $this->filterContent = $filter->generate();
                    }
                    $this->isEffective = true;
                    break;
                }
            }
        }
    }

    /**
     * 获取过滤器内容
     * @return mixed
     */
    public function getFilterContent()
    {
        return $this->filterContent;
    }

    /**
     * 过滤器是否有效
     * @return bool  【true  有效  false 无效】
     */
    public function filterIsEffective()
    {
        return $this->isEffective;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($user_id)
    {
        $this->userId = $user_id;
    }
}