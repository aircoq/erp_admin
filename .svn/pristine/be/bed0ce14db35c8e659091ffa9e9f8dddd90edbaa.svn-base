<?php
namespace app\customerservice\task;

use app\index\service\AbsTasker;
use app\customerservice\service\aliexpress\AliEvaluateHelp;
use app\common\exception\TaskException;
use think\Exception;

class AutoEvaluate extends AbsTasker
{
    public function getCreator() {
        return '龙志军';
    }
    
    public function getDesc() {
        return 'Aliexpress处理批量回评';
    }
    
    public function getName() {
        return 'Aliexpress处理批量回评';
    }
    
    public function getParamRule() {
        return [];
    }
    
    public function execute() {       
        try {
            $help = new AliEvaluateHelp();
            $page = 1;
            $result = [];
            do{
                $list = $result = $help->getEvaluateByStatus('',$page);
                if(!empty($list)){
                    foreach($list as $item){
                        $help->evaluate($item, $item['score'], $item['evaluate_content']);
                    }
                }
                $page++;
            }while(!empty($result));


        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }             
    }
}

