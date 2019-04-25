<?php


namespace app\index\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use think\Exception;

class ExportDownQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "导出文件下载队列";
    }

    public function getDesc(): string
    {
        return "导出文件下载队列";
    }

    public function getAuthor(): string
    {
        return "詹老师";
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 4;
    }

    public function execute()
    {
        try {
            $data = $this->params;
            if (!isset($data['class']) || !$data['class']) {
                throw new Exception('class不能为空');
            }
            if (!isset($data['fun']) || !$data['fun']) {
                throw new Exception('fun不能为空');
            }
            $className = $data['class'];
            $fun = $data['fun'];
            $class = new $className();
            $class->$fun($data);

        } catch (Exception $ex) {
            throw new QueueException($ex->getMessage());
        }
    }
}