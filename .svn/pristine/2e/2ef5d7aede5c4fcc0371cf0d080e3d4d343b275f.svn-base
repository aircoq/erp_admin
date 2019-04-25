<?php
namespace app\internalletter\task;

use app\common\model\InternalLetterText;
use app\index\service\AbsTasker;
use app\common\service\UniqueQueuer;
use app\internalletter\queue\SendDingQueue;
use app\internalletter\service\DingTalkService;
use app\internalletter\service\InternalLetterService;
use app\common\model\InternalLetter;
use think\Exception;
use think\Db;

class CheckDingStatus extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '检查钉钉是否发送成功';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '检查钉钉是否发送成功';
    }

    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return 'denghaibo';
    }

    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [];
    }

    /**
     * 检查
     * @throws Exception
     */
    public  function execute()
    {

        try{
            set_time_limit(0);
            $internalLetterModel = new InternalLetter();
            $internalLetterTextModel = new InternalLetterText();
            $dingTalkService = new DingTalkService();
            $queue = new UniqueQueuer(SendDingQueue::class);

            $where['status'] = array(array('eq',1),array('eq',2),'or');
            $where['count'] = array('lt', 3);
            $where['dingtalk'] = array('eq', 1);
            $where['send_time'] = array('gt',1543219200);

            $letters = $internalLetterModel->where($where)->select();
            if(empty($letters)){
                return true;
            }


            foreach ($letters as $v){

                if(empty($v['task_id'])){
                    $data['id'] = $v['id'];
                    $data['send_to_all'] = 0;
                    $v['count'] = $v['count']+1;
                    $internalLetterModel->where('id', $v['id'])->update(['count'=>$v['count']]);

                    $queue->push($data);
                    continue;
                }

                $res = $dingTalkService->check_ding_status($v['task_id']);

                //status发送状态：1：已发送待检查，2：发送失败，3：发送成功， 4：发送失败且不必重发
                //ask为 0表示其他原因失败，1表示发送成功，2表示发送失败
                if($res['ask'] == 1){
                    $internalLetterModel->where('id',$v['id'])->update(['status'=>3]);
                    continue;
                }else if($res['ask'] == 0){
                    $internalLetterModel->where('id',$v['id'])->update(['status'=>4]);
                    continue;
                }else if($res['ask'] == 2){
                    $result = $internalLetterTextModel->where('id', $v['letter_text_id'])->value('receive_ids');

                    //如果是发送给企业全部用户
                    if(intval($result)===0){

                        $internalLetterModel->where('id', $v['id'])->setField('status', 3);

                        foreach ($res['data'] as $item) {

                            //通过钉钉id，找到对应的站内信letter_id
                            $usr_id = Db::table('user')->where('dingtalk_userid', $item)->value('id');

                            $where['letter_text_id'] = $v['letter_text_id'];
                            $where['receive_id'] = $usr_id;
                            $letter_id = $internalLetterModel->where($where)->value('id');
                            $internalLetterModel->where('id', $letter_id)->update(['status'=>1, 'count'=>1]);

                            $data['id'] = $letter_id;
                            $data['send_to_all'] = 0;

                            $queue->push($data);
                        }
                    }else{
                        $data['id'] = $v['id'];
                        $data['send_to_all'] = 0;

                        $v['count'] = $v['count']+1;
                        $internalLetterModel->where('id', $v['id'])->update(['count'=>$v['count']]);

                        $queue->push($data);
                    }

                }

            }
        }catch (\Exception $e){
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }

    }

}

