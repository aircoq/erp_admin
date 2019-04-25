<?php
namespace app\common\model\ebay;

use app\common\cache\Cache;
use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\Db;
use think\db\Query;
use think\Exception;
use app\customerservice\service\EbayFeedbackHelp;

class EbayFeedback extends ErpModel
{

    use ModelFilter;

    private $filterAccount = [];

    /**
     * 调用EbayAccountFilter过滤
     * @param Query $query
     * @param $params
     */
    public function scopeEbayAccount(Query $query, $params)
    {
        $this->filterAccount = array_merge($params[0], $this->filterAccount);
        if(!empty($this->filterAccount))
        {
            $query->where('__TABLE__.account_id', 'in', $this->filterAccount);
        }
    }

    /**
     * 调用EbayDepartmentFilter过滤
     * @param Query $query
     * @param $params
     */
    public function scopeDepartment(Query $query, $params)
    {
        $this->filterAccount = array_merge($params, $this->filterAccount);
        if(!empty($this->filterAccount))
        {
            $query->where('__TABLE__.account_id', 'in', $this->filterAccount);
        }
    }

    // 评价类型 Positive Neutral Negative
    const POSITIVE = 1;

    const NEUTRAL = 2;

    const NEGATIVE = 3;

    static $COMMENT_TYPE = [
        self::POSITIVE => '好评',
        self::NEUTRAL => '中评',
        self::NEGATIVE => '差评'
    ];
    
    // 需要处理(跟进)状态
    static $HANDEL_STATUS = [
        0 => '-',
        1 => '待处理',
        2 => '已处理'
    ];
    
    // 需要处理(跟进)状态
    const WAIT_EVALUATE = 0;

    const FINSH_EVALUATE = 1;

    const PADDING_EVALUATE = 2;

    const FAIL_EVALUATE = 3;

    static $STATUS = [
        self::WAIT_EVALUATE => '未回评',
        self::FINSH_EVALUATE => '已回评',
        self::PADDING_EVALUATE => '回评中',
        self::FAIL_EVALUATE => '回评失败'
    ];

    /**
     * 初始化
     * 
     * @return [type] [description]
     */
    protected function initialize()
    {
        // 需要调用 mdoel 的 initialize 方法
        parent::initialize();
        $this->query('set names utf8mb4');
    }

    /**
     * 新增
     * @param array $data
     *            [description]
     */
    public function add(array $data)
    {
        $ebayFeedbackHelp = new EbayFeedbackHelp();

        if (isset($data['transaction_id'])) {
            try {
                // 检查是否已存在
                if (empty($data['id'])) {
                    unset($data['id']);
                    $lock = Cache::store('Lock');
                    $where = [
                        'account_id' => $data['account_id'],
                        'item_id' => $data['item_id'],
                        'transaction_id' => $data['transaction_id'],
                    ];

                    //下面一段加锁执行；
                    if ($lock->lockParams($where)) {
                        try {
                            $old_id = $this->where($where)->value('id');
                            if (empty($old_id)) {
                                $feedback_id = $this->insertGetId($data);
                                $ebayFeedbackHelp->trigger_assessment_event($data, $feedback_id);
                            } else {
                                $this->update($data, ['id' => $old_id]);
                                $ebayFeedbackHelp->trigger_assessment_event($data, $old_id);
                            }
                            $lock->unlockParams($where);
                        } catch (Exception $e) {
                            $lock->unlockParams($where);
                        }
                    }
                } else {
                    $this->update($data, ['id' => $data['id']]);
                    $ebayFeedbackHelp->trigger_assessment_event($data, $data['id']);
                }

                return true;
            } catch (Exception $ex) {
               throw new Exception('ebay评价添加异常 ' . $ex->getMessage());
            }
        }
        return true;
    }

    /**
     * 批量新增
     * 
     * @param array $data
     *            [description]
     */
    public function addAll(array $data)
    {
        foreach ($data as $key => $value) {
            $this->add($value);
        }
    }

    /**
     * 修改数据
     * 
     * @param array $data
     *            [description]
     * @return [type] [description]
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }

    /**
     * 批量修改
     * 
     * @param array $data
     *            [description]
     * @return [type] [description]
     */
    public function editAll(array $data)
    {
        return $this->save($data);
    }

    /**
     * 检测数据是否存在
     * 
     * @param int $id            
     * @return bool
     */
    public function isHas($id = 0)
    {
        $result = $this->where([
            'id' => $id
        ])->find();
        if (empty($result)) { // 不存在
            return false;
        }
        return true;
    }
}