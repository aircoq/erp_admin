<?php
namespace app\common\model;

use app\common\service\OrderStatusConst;
use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;
use think\Model;
use think\Db;
use app\common\traits\BaseModel;
use app\common\traits\OrderStatus;


/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class Order extends ErpModel
{
    use BaseModel;
    use OrderStatus;
    use ModelFilter;

    private $channel = 0;

    public function scopeOrder(Query $query, $params)
    {
        $query->where('__TABLE__.channel_account', 'in', $params);
    }

    public function scopeChannel(Query $query, $params)
    {
        $query->where('__TABLE__.channel_id', 'in', $params);
    }

    public function scopeSeller(Query $query, $params)
    {
        $query->where('__TABLE__.seller_id', 'in', $params);
    }

    /**
     * 订单
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
        $this->query('set names utf8mb4');
    }

    /** 查看是否存在
     * @param $id
     * @return bool
     */
    public function isHas($id)
    {
        $result = $this->where(['id' => $id])->find();
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    /** 检查订单是否存在
     * @param array $data
     * @return bool
     */
    public function checkOrder(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    /** 根据十进制状态码查询
     * @param $code
     * @return string
     */
    public function PCondition($code)
    {
        $condition = " status | 0 = " . $code;
        return $condition;
    }

    /** 获取订单所有的错误信息
     * @param int $status
     * @param string $note 留言信息
     * @return array
     */
    public function getErrorInfo($status = 0, $note = '')
    {
        $new_list = [];
        if (!empty($status)) {
            $code = decbin($status);
            $processModel = new OrderProcess();
            $length = strlen($code);
            $code_prefix = bindec(substr($code, 0, $length - 17));
            $code_suffix = bindec(substr($code, $length - 9, $length - 1));
            $condition = "code_prefix = " . $code_prefix . " and  code_suffix & " . $code_suffix . " = code_suffix and type = 0 and code_suffix !=0";
            $result = $processModel->where($condition)->select();
            if (!empty($result)) {
                foreach ($result as $k => $v) {
                    $is_ok = true;
                    $code = $v['code_prefix'] . '_' . $v['code_suffix'];
                    $temp['code'] = $code;
                    $temp['remark'] = $v['remark'];
                    $temp['message'] = '';
                    switch ($code) {
                        case '0_1':
                            $temp['message'] = '有留言未处理';
                            break;
                        case '0_2':
                            $temp['message'] = '地址信息错误';
                            break;
                        case '0_4':
                            $temp['message'] = '存在商品无法关联';
                            break;
                        case '0_8':
                            $temp['message'] = '无法分配仓库';
                            break;
                        case '0_16':
                            $temp['message'] = '邮寄方式有误';
                            break;
                        case '0_32':
                            $temp['message'] = '';
                            break;
                        case '0_64':
                            $temp['message'] = '缺货(停售)';
                            break;
                        case '0_128':
                            $temp['message'] = '订单已过期';
                            break;
                        case '0_256':
                            $temp['message'] = $note;
                            break;
                        case '0_512':
                            $temp['message'] = '';
                            break;
                        case '16_1':
                            $temp['message'] = $v['remark'];
                            break;
                        default:
                            $is_ok = false;
                            break;
                    }
                    if ($is_ok) {
                        array_push($new_list, $temp);
                    }
                }
            } else {
                $code_prefix = bindec(substr($code, 0, $length - 21));
                if ($code_prefix == 1) {
                    $temp['code'] = '16_1';
                    $temp['remark'] = '缺货';
                    $temp['message'] = '缺货';
                    array_push($new_list, $temp);
                }
            }
        } else {
            $temp['code'] = '0_0';
            $temp['remark'] = '未付款';
            $temp['message'] = '订单未付款';
            array_push($new_list, $temp);
        }
        return $new_list;
    }

    /** 查询已付款的订单
     * @return string
     */
    public function isPayment()
    {
        $sql = '(a.status >> 16) & 1 = 1';
        return $sql;
    }

    /** 查询是否为缺货单
     * @param $status
     * @return bool
     */
    public function isOos($status)
    {
        $code = decbin($status);
        $length = strlen($code);
        $code_prefix = bindec(substr($code, 0, $length - 21));
        if ($status != OrderStatusConst::SaidInvalid && ($code_prefix & 1) == 1) {
            return true;
        }
        return false;
    }

    /** 查询是否已配货
     * @param $status
     * @return bool
     */
    public function isDistribution($status)
    {
        $code = decbin($status);
        $length = strlen($code);
        $code_prefix = bindec(substr($code, 0, $length - 17));
        if ($status != OrderStatusConst::SaidInvalid && ($code_prefix & 1) == 1) {
            return true;
        }
        return false;
    }

    /** 获取状态名
     * @param $value
     * @return mixed|string
     */
    public function getStatus($value)
    {
        $orderProcess = new OrderProcess();
        return $orderProcess->getStatusName($value);
    }

    /** 获取地址
     * @return \think\model\Relation
     */
    public function address()
    {
        return $this->hasOne(OrderAddress::class, 'order_id', 'id')->field('*');
    }


    public function setChannel($channelID)
    {
        $this->channel = $channelID;
        return $this;
    }

    /** 订单id转字符串
     * @param $value
     * @return string
     */
    public function getIdAttr($value)
    {
        if (is_numeric($value)) {
            $value = $value . '';
        }
        return $value;
    }

    /** 获取包裹信息
     * @param string $field
     * @return mixed
     */
    public function package($field = 'id,order_id,warehouse_id,shipping_id,shipping_name,shipping_number')
    {
        return $this->hasMany(OrderPackage::class, 'order_id', 'id', [],
            'left')->field($field);
    }

    /** 获取详情信息
     * @param string $field
     * @return mixed
     */
    public function detail($field = '*')
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'id', [],
            'left')->field($field);
    }

    /** 获取来源详情信息
     * @param string $field
     * @return mixed
     */
    public function source($field = '*')
    {
        return $this->hasMany(OrderSourceDetail::class, 'order_id', 'id', [],
            'left')->field($field);
    }

    /** 获取缺货信息
     * @param string $field
     * @return mixed
     */
    public function oos($field = '*')
    {
        return $this->hasMany(OrderOos::class, 'order_id', 'id', [],
            'left')->field($field);
    }

    /** 创建时间获取器
     * @param $value
     * @return int
     */
    public function getCreateTimeAttr($value)
    {
        if (is_numeric($value)) {
            return $value;
        } else {
            return strtotime($value);
        }
    }

    /** 更新时间获取器
     * @param $value
     * @return int
     */
    public function getUpdateTimeAttr($value)
    {
        if (is_numeric($value)) {
            return $value;
        } else {
            return strtotime($value);
        }
    }

    /** 获取同步状态的说明
     * @param $status
     * @return string
     */
    public function getSynchronizeRemark($status)
    {
        $remark = ['未同步', '同步失败', '跟踪号更新', '同步中', '忽略', '同步成功','已标记未同步跟踪号'];
        if (isset($remark[$status])) {
            return $remark[$status];
        }
        return '';
    }
}