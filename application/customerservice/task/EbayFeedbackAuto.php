<?php
namespace app\customerservice\task;


use app\common\model\ebay\EbayFeedback;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\EbayFeedbackLeftQueue;
use app\index\service\AbsTasker;
use think\Exception;
use app\common\exception\TaskException;

class EbayFeedbackAuto extends AbsTasker{
    public function getName()
    {
        return "Ebay批量回评";
    }

    public function getDesc()
    {
        return "Ebay批量回评";
    }

    public function getCreator()
    {
        return "冬";
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        try {
            $feedbackModel = new EbayFeedback();
            $all = [];
            $limit = 1000;
            $start = 0;
            //回评中；
            $where = ['status' => EbayFeedback::PADDING_EVALUATE, 'item_id' => ['>', 0], 'transaction_id' => ['>', 0]];
            while(true) {
                $ids = $feedbackModel->where($where)->limit($start * $limit, $limit)->column('id');
                if (empty($ids)) {
                    break;
                }
                $all = array_merge($all, $ids);
                if (count($ids) < $limit) {
                    break;
                }
                $start++;
            }

            $queue = new UniqueQueuer(EbayFeedbackLeftQueue::class);
            if(!empty($all)){
                foreach($all as $id){
                    $queue->push($id);
                }
            }
            return true;
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }
     
}