<?php

namespace app\common\model\aliexpress;

use app\common\traits\ModelFilter;
use think\db\Query;
use erp\ErpModel;
use think\Model;

class AliexpressSendMsg extends ErpModel
{
    use ModelFilter;
    private $filterAccount = [];
    protected $autoWriteTimestamp = true;
    protected $dateFormat = false;

    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 0;
    const SEND_STATUS = [
        self::STATUS_SUCCESS=>'发送成功',
        self::STATUS_FAIL=>'发送失败'
    ];

    public function msgRelation()
    {
        return $this->hasOne('AliexpressMsgRelation');
    }

    /**
     * 调用AliexpressAccount过滤
     * @param Query $query
     * @param $params
     */
    public function scopeAliexpressAccount(Query $query, $params)
    {
        $this->filterAccount = array_merge($params, $this->filterAccount);
        if(!empty($params))
        {
            $query->where('__TABLE__.aliexpress_account_id', 'in', $this->filterAccount);
        }
    }
}
