<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-4
 * Time: 下午2:12
 */

namespace app\common\annotations;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 */
class QueueType
{
    /**
     * @var string
     * @Enum({"LAZY", "EAGER", "EXTRA_LAZY"})
     */
    public $number = 'LAZY';
    /**
     * @var array
     */
    public $value;
}