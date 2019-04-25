<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 2018/12/12
 * Time: 20:11
 */

namespace app\common\exception;


class QueueAfterDoException extends QueueException
{
    /**
     * @var int
     */
    private $afterDo;

    /**
     * QueueAfterDoException constructor.
     * @param $message string 延迟原因
     * @param $afterDo int 延迟多少毫秒后重试
     */
    public function __construct($message, $afterDo)
    {
        parent::__construct($message);
        $this->afterDo = $afterDo;
    }

    /**
     * @return mixed
     */
    public function getAfterDo()
    {
        return $this->afterDo;
    }
}
