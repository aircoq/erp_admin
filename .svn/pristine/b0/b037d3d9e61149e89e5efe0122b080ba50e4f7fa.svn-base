<?php
/**
 * Created by PhpStorm.
 * User: Yu
 * Date: 2019/4/24
 * Time: 11:29
 */

namespace app\goods\controller;

use app\api\controller\Get;
use app\common\service\UniqueQueuer;
use app\goods\queue\GoodsWinitLianQueue;
use app\goods\service\GoodsWinitLian;
use function PHPSTORM_META\type;
use think\Controller;
use think\Exception;
use Zend\Validator\Date;

/**
 * @module 商品系统
 * @title 万邑链商品
 * @url /goods-winit-lian
 * @author Yuchanglong
 * Class Goods
 * @package app\goods\controller
 */
class WinitLianGoods extends Controller
{
    /**
     * @var GoodsWinitLian
     */
    private $winiService;//service
    public $params;//获取列表的参数

    public function __construct()
    {
        parent::__construct();
        $this->winiService = new GoodsWinitLian();
        $this -> params = [
            'updateStartDate' => date('Y-m-d',strtotime('-1 day')),
            'updateEndDate' => date('Y-m-d')
        ];
    }

    /**
     * @title 推送更新所有的商品
     * @method get
     * @url all-goods
     */
    public function pushAllGoods()
    {
        $param = $this->request->param();
        $this->params = [
            'updateStartDate' =>$param['start_date']??'',
            'updateEndDate' => $param['end_date']??''
        ];
        $this->handleGoodsList($this->params);
    }

    /**
     * @title 推送更新前一天的商品
     * @method get
     * @url last-day-goods
     */
    public function pushLastDayGoods()
    {
        $this->handleGoodsList($this->params);
    }


    public function handleGoodsList(array $params)
    {
        try {
            $queue = new UniqueQueuer(GoodsWinitLianQueue::class);
            $queue->push($params);

        }catch (Exception $ex){
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 显示列表
     * @method get
     * @url goods-list
     * @throws \think\Exception
    */
    public function showGoodsList()
    {
        ini_set('max_execution_time', '0');
//        var_dump($this->winiService->getWarehouseIds());
        $data = $this->winiService->getGoodsList($this->winiService->getConf(552));
        $this->winiService->dataHandle($data,552);
    }
}