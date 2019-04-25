<?php
namespace app\customerservice\task;


use app\common\model\ebay\EbayMessage;
use app\common\model\ebay\EbayMessageGroup;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\EbayMessageUntreatedTotalQueue;
use app\index\service\AbsTasker;
use app\common\model\ebay\EbayMessageGroup as EbayMessageGroupModel;
use app\common\model\ebay\EbayMessage as EbayMessageModel;

class EbayMsgCount extends AbsTasker{
    public function getName()
    {
        return "Ebay站内信未处理统计更新";
    }

    public function getDesc()
    {
        return "Ebay站内信未处理统计更新";
    }

    public function getCreator()
    {
        return "冬";
    }

    public function getParamRule()
    {
        return [
            'downTime|更新时间' => 'require|select:十二小时到现在:0,1天前到现在:1,3天前到现在:3,5天前到现在:5,10天前到现在:10,20天前到现在:20,30天前到现在:30,40天前到现在:40,50天前到现在:50,60天前到现在:60'
        ];
    }

    public function execute()
    {
        $this->msgCount();
    }
    
    function msgCount(){
        $groupModel = new EbayMessageGroup();

        $down_time = (int)$this->getData('downTime');
        $down_time = empty($down_time)? 0.5 : intval($down_time);
        $start_time = ceil(time() - 86400 * $down_time);

        $queue = new UniqueQueuer(EbayMessageUntreatedTotalQueue::class);
        $start = 0;
        $limit = 100;
        $params = [];
        $where['status'] = ['in', [0, 1]];
        $where['created_time'] = ['>=', $start_time];
        while(true) {
            $data = $groupModel->where($where)->limit($start * $limit, 1)->column('id');
            if (empty($data)) {
                break;
            }
            $start++;
            $params[] = [
                'id' => $data[0],
                'limit' => $limit,
            ];
        }
        foreach ($params as $param) {
            $queue->push($param);
        }
    }
}