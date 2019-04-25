<?php
namespace app\customerservice\service;

use app\common\exception\JsonErrorException;
use app\common\model\AfterServiceReason;
use app\common\model\AfterSaleService;
use app\customerservice\validate\SaleReasonValidate;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/4/1
 * Time: 10:24
 */
class SaleReasonService
{
    protected $afterServiceReasonModel;  //售后原因模型
    protected $validate;

    /** 构造函数
     * SaleReasonHelp constructor.
     */
    public function __construct()
    {
        if (is_null($this->afterServiceReasonModel)) {
            $this->afterServiceReasonModel = new AfterServiceReason();
        }
        $this->validate = new SaleReasonValidate();
    }

    /** 售后选择的原因
     * @return array
     */
    public function reason()
    {
        $message = $this->afterServiceReasonModel->field('id,code,remark')->order('sort asc,code asc')->select();
        return $message;
    }

    /**
     * 获取原因
     * @param $id
     * @return mixed|string
     */
    public function getReason($id)
    {
        $message = $this->afterServiceReasonModel->field('id,code,remark')->where(['id' => $id])->find();
        return $message['remark'] ?? '';
    }

    /** 新增售后原因
     * @param $remark
     * @param $operator
     * @return array
     */
    public function addReason($remark, $operator)
    {
        $where['remark'] = ['=',$remark];
        $reason = $this->afterServiceReasonModel->where('sort != 9999')->order('sort desc')->limit(1)->find();
        $data['sort'] = 0;
        if(empty($reason)){
            $data['sort'] = $reason['sort'] + 1;
        }
        $data['code'] = $remark;
        $data['remark'] = $remark;
        $data['creator_id'] = $operator;
        $data['create_time'] = time();
        if(!$this->validate->check($data)){
            throw new JsonErrorException($this->validate->getError(),400);
        }
        $this->afterServiceReasonModel->allowField(true)->save($data);
        return ['message' => '新增成功','code' => 200];
    }

    /** 删除原因
     * @param $id
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function delReason($id)
    {
        if(!$this->afterServiceReasonModel->isHas($id)){
            return json(['message' => '该记录不存在'],500);
        }
        $afterSaleModel = new AfterSaleService();
        $where['reason'] = ['=',$id];
        $saleInfo = $afterSaleModel->where($where)->select();
        if(!empty($saleInfo)){
            return json(['message' => '该原因已被使用，不可删除！'],500);
        }
        $this->afterServiceReasonModel->where(['id' => $id])->delete();
        return json(['message' => '操作成功'],200);
    }
}