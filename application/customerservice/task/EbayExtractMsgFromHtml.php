<?php
namespace app\customerservice\task;

use app\index\service\AbsTasker;
use think\Db;
use think\Exception;
use app\common\model\ebay\EbayMessage as EbayMessageModel;
use app\common\model\ebay\EbayMessageBody as EbayMessageBodyModel;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\EbayExtractMsgFromHtmlQueue;
use app\customerservice\queue\EbayUpdateTransactionIdQueue;

class EbayExtractMsgFromHtml extends AbsTasker
{

    public function getName()
    {
        return "Ebay提取站内信内容";
    }

    public function getDesc()
    {
        return "从下载的html中获取Ebay站内信交易号和卖家发件箱内容,更新交易ID";
    }

    public function getCreator()
    {
        return "冬-停用";
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        //$this->matchText();
        $this->matchTransactionId();
        return true;
    }

    /**
     * 正则匹配html中卖家留言
     *
     * @return boolean
     */
    function matchText()
    {
        $queue = new UniqueQueuer(EbayExtractMsgFromHtmlQueue::class);
        $where['extract_html_status'] = 0;
        $where['message_text'] = '';

        $ebayMessageModel = new EbayMessageModel();
        $max = 20;
        $limit = 1000;
        $i = 0;
        $handleIds = [];

        while ($max) {
            $ids = $ebayMessageModel->where($where)->order('id', 'asc')->limit($i * $limit, $limit)->field('id')->column('id');

            if (empty($ids)) {
                break;
            }
            $handleIds = array_merge($handleIds, $ids);
            if (count($ids) < $limit) {
                break;
            }
            //自增进行下一页查询；
            $i++;
            $max--;
        }
        foreach ($handleIds as $id) {
            $queue->push($id);
        }
        return true;
    }

    /**
     * 从html中提取交易ID ， transaction id
     *
     * @return boolean
     */
    function matchTransactionId()
    {
        $queue = new UniqueQueuer(EbayUpdateTransactionIdQueue::class);
        $where['check_transaction'] = 1;
        $ebayMessageBodyModel = new EbayMessageBodyModel();

        $handleIds = [];
        $limit = 100;
        $i = 0;
        $max = 500;
        while ($max) {
            $ids = $ebayMessageBodyModel->where($where)
                ->field('id')
                ->order('id', 'asc')
                ->limit($i * $limit, $limit)
                ->column('id');
            if(empty($ids)) {
                break;
            }
            $handleIds[] = ['start' => $ids[0], 'limit' => $limit];
            if (count($ids) < $limit) {
                break;
            }
            $i++;
            $max--;
        }

        foreach ($handleIds as $id) {
            $queue->push($id);
        }
        return true;
    }
}