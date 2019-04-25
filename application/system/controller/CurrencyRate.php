<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 16-12-24
 * Time: 下午3:35
 */

namespace app\system\controller;


use app\common\controller\Base;
use app\common\model\Currency;
use app\common\model\system\CurrencyRate as CurrencyRateModel;
use think\Request;

/**
 * @node 汇率
 * @package app\system\controller
 */
class CurrencyRate extends Base
{
    /**
     * @node 汇率列表
     * @return \think\response\Json
     */
    public function index()
    {
        $rates = CurrencyRateModel::all(['status'=>1]);
        return json($rates, 200);
    }

    /**
     * @node 添加汇率
     * @return \think\response\Json
     */
    public function add_list()
    {
        $rates = CurrencyRateModel::all(['status'=>0]);
        return json($rates, 200);
    }

    public function currency()
    {
        $currencys = Currency::all();
        return json($currencys, 200);
    }


    public function add(Request $request)
    {
        $post = $request->post();
        $ids = explode(',',$post['ids']);
        foreach ($ids as $id){
            $model = CurrencyRateModel::find(['cur_id'=>$id]);
            if($model) {
                $model->status = 1;
                $model->save();
            }else{
                $rate = new CurrencyRateModel();
                $rate->cur_id = $id;
                $rate->status = 1;
                $rate->save();
            }
        }
        return json(['message'=>'新增成功']);
    }

    public function remove($id)
    {
        $rate = CurrencyRateModel::get($id);
        if($rate){
            $rate->status = 0;
            $rate->save();
            return json(['message'=>'移除成功']);
        }else{
            return json(['message'=>'不存在记录']);
        }
    }

    public function change($id)
    {
        $rate = CurrencyRateModel::get($id);
        if($rate){
            $request = Request::instance();
            $put = $request->put();
            $rate->cur_id = $put['cur_id'];
            $rate->rate = $put['FinanceRate'];
            $rate->update();
            return json(['message'=>'删除成功']);
        }else{
            return json(['message'=>'不存在记录']);
        }
    }
}