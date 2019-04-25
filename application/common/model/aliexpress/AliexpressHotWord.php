<?php

/**
 * Description of AliexpressWindow
 * @datetime 2017-7-5  15:35:55
 * @author joy
 */

namespace app\common\model\aliexpress;

use think\Model;
use think\Exception;


class AliexpressHotWord extends Model{

    protected $resultSetType = 'collection';

    public  function initialize() {
        parent::initialize();
    }

    public function saveData($data)
    {
        if(!is_array($data)) {
            return false;
        }
        foreach($data as $val) {
            try {
                if(!empty($val['id'])) {
                    $this->update($val, ['id' => $val['id']]);
                } else {
                    $this->allowField(true)->insert($val);
                }
            } catch(Exception $e) {
                var_dump($e->getMessage());
                throw new Exception($e->getMessage());
            }
        }
        return true;
    }
}
