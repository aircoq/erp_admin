<?php

namespace app\common\model;

use erp\ErpModel;
use think\Db;
use think\Exception;
use app\common\cache\Cache;
use app\common\traits\ModelFilter;
use think\db\Query;

/**
 * Created by tanbin.
 * User: XPDN
 * Date: 2017/06/20
 * Time: 9:13
 */
class Allocation extends ErpModel
{
    const STATUS_0 = 0;
    const STATUS_1 = 1;
    const STATUS_2 = 2;
    const STATUS_3 = 3;
    const STATUS_4 = 4;
    const STATUS_5 = 5;


   /* const STATUS_1001 = 1001;
    const STATUS_1002 = 1002;
    const STATUS_1003 = 1003;
    const STATUS_1004 = 1004;
    const STATUS_1005 = 1005;
    const STATUS_1006 = 1006;
    const STATUS_1010 = 1010;*/


//    const STATUS = [
//        self::STATUS_0 => '草稿',
//        self::STATUS_1 => '未调拔，已推送',
//        self::STATUS_2 => '调拨在途',
//        self::STATUS_3 => '调拨完成',
//        self::STATUS_4 => '部分到货',
//        self::STATUS_5 => '不等待剩余的完结',
//        self::STATUS_10 => '已作废',
//    ];

    /*const STATUS_MAP = [
        self::STATUS_1001 => self::STATUS_0,
        self::STATUS_1002 => self::STATUS_1,
        self::STATUS_1003 => self::STATUS_2,
        self::STATUS_1004 => self::STATUS_3,
        self::STATUS_1005 => self::STATUS_4,
        self::STATUS_1006 => self::STATUS_5,
        self::STATUS_1010 => self::STATUS_10,
    ];*/

    const STATUS_UN_AUDIT = 0;//未审核
    const STATUS_SUCCESS_AUDIT = 1;//审核通过
    const STATUS_FAILD_AUDIT = 2;//审核不通过
    const STATUS_ON_WAY = 3;//在途
    const STATUS_PART_IN = 4;//部分入库
    const STATUS_ALL_IN = 5;//全部入库
    const STATUS_PICKING = 6;//捡货中
    const STATUS_PINGCKING_FINISH= 7;//捡货完成
    const STATUS_PACKING= 8;//包装中
    const STATUS_PACKING_FINISH = 9;//包装完成
    const STATUS_UPLOAD_LOGISTICS = 10;//已上传物流
    const STATUS_PART_OUT = 11;// 部分发货
    const STATUS_FORECAST_FAIL = 12;//预报失败
    const STATUS_CANCEL = -1;// 作废
    const STATUS_TXT = [
        self::STATUS_UN_AUDIT => '未审核',
        self::STATUS_SUCCESS_AUDIT => '审核通过',
        self::STATUS_FAILD_AUDIT => '审核不通过',
        self::STATUS_PICKING => '拣货中',
        self::STATUS_PINGCKING_FINISH => '拣货完成',
        self::STATUS_PACKING => '包装中',
        self::STATUS_FORECAST_FAIL => '预报失败',
        self::STATUS_PACKING_FINISH => '包装完成',
        self::STATUS_UPLOAD_LOGISTICS => '已上传物流',
        self::STATUS_PART_OUT => '部分发货',
        self::STATUS_ON_WAY => '已在途',
        self::STATUS_PART_IN => '部分入库',
        self::STATUS_ALL_IN => '全部入库',
        self::STATUS_CANCEL => '作废',

    ];

    //数据过滤器
    use ModelFilter;
    public function scopeAllocation(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.create_id', 'in', $params);
        }
    }
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }


    /**
     * allocation 和 allocation_detail 关联添加
     * @param array $data
     * @throws Exception
     */
    public function add($data = [])
    {
        if (!empty($data['datas']) && !empty($data['detail'])) {
            Db::startTrans();
            try {
                $result = $this->allowField(true)->save($data['datas']);
                $insert_id = $this->getLastInsID();
                $this->detail()->saveAll($data['detail']);
                Db::commit();

                //存值
                if ($insert_id) {
                    Cache::store('Allocation')->allocationOrderCode($data['datas']['order_code'], ['id' => $insert_id]);
                }
                return true;
            } catch (Exception $ex) {
                Db::rollback();
                throw new Exception($ex->getMessage());
            }
        }
        return false;
    }

    /**
     * allocation 和 allocation_detail 关联修改
     * @param array $data
     * @throws Exception
     */

    public function map_update($data = [])
    {
        if (!empty($data['datas'])) {
            Db::startTrans();
            try {
                //更新主表
                $this->allowField(true)->isUpdate(true)->save($data['datas']);

                if (!empty($data['detail'])) {
                    //筛选出删除的id
                    $detail_list = AllocationDetail::field('id,allocation_id,sku_id,delete')->where(['allocation_id' => $data['datas']['id']])->select();
                    if (empty($detail_list)) {
                        //直接新增所有的
                        $this->detail()->saveAll($data['detail']);
                    } else {
                        foreach ($detail_list as $detail) {
                            if (isset($data['detail'][$detail['sku_id']])) {
                                //更新
                                $data['detail'][$detail['sku_id']]['delete'] = 0;//以前是删除的重新添加
                                AllocationDetail::update($data['detail'][$detail['sku_id']], ['id' => $detail['id']]);
                                unset($data['detail'][$detail['sku_id']]);
                            } else {
                                if ($detail['delete'] == 1) {
                                    continue;
                                }
                                //删除（更改删除状态）
                                AllocationDetail::update(['delete' => 1], ['id' => $detail['id']]);
                            }

                        }
                        if ($data['detail']) {
                            //剩余的都是新增数据
                            $this->detail()->saveAll($data['detail']);

                        }

                    }
                }
                Db::commit();
                return true;
            } catch (Exception $ex) {
                Db::rollback();
                throw new Exception($ex->getMessage());
            }
        }

        return false;
    }

    /**
     * 关联关系
     */
    public function detail()
    {
        return $this->hasMany('AllocationDetail', 'allocation_id', 'id');
    }

    /** 检测是否存在
     * @param int $id
     * @return bool
     */
    public function isHas($id = 0)
    {
        $result = $this->where(['id' => $id])->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return true;
    }


    public function getOutWarehouseAttr($value, $data)
    {
        if ($data['out_warehouse_id']) {
            $tmp = Cache::store('warehouse')->getWarehouse($data['out_warehouse_id']);
            return $tmp ? $tmp['name'] : '';
        }
        return '';
    }

    public function getInWarehouseAttr($value, $data)
    {
        if ($data['in_warehouse_id']) {
            $tmp = Cache::store('warehouse')->getWarehouse($data['in_warehouse_id']);
            return $tmp ? $tmp['name'] : '';
        }
        return '';
    }

    public function getStatusTxtAttr($value, $data)
    {
        $status_txt = self::STATUS_TXT;
        return isset($status_txt[$data['status']]) ? $status_txt[$data['status']] : '';
    }

    public function getCreateAttr($value, $data)
    {
        if ($data['create_id']) {
            $user = Cache::store('user')->getOneUser($data['create_id']);
            if ($user) {
                return $user['realname'];
            }
        }
        return '';
    }

    public function getUpdateAttr($value, $data)
    {
        if ($data['update_id']) {
            $user = Cache::store('user')->getOneUser($data['update_id']);
            if ($user) {
                return $user['realname'];
            }
        }
        return '';
    }

    public function getAuditorAttr($value, $data)
    {
        if ($data['auditor_id']) {
            $user = Cache::store('user')->getOneUser($data['auditor_id']);
            if ($user) {
                return $user['realname'];
            }
        }
        return '';
    }

    public function getShippingFeeAttr($value, $data)
    {
        if ($data['shipping_fee'] != 0) {
            return $data['shipping_fee'];
        }
        if (in_array($data['status'], [self::STATUS_UN_AUDIT, self::STATUS_SUCCESS_AUDIT, self::STATUS_FAILD_AUDIT
            , self::STATUS_PICKING, self::STATUS_PINGCKING_FINISH, self::STATUS_PACKING, self::STATUS_FORECAST_FAIL,
            self::STATUS_PACKING_FINISH, self::STATUS_CANCEL])) {
            return '';
        }
        return $data['shipping_fee'];

    }
}