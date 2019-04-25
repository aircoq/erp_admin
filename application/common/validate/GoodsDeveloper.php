<?php


namespace app\common\validate;

use app\common\cache\Cache;
use think\Validate;

class GoodsDeveloper extends Validate
{
    protected $rule = [
        'developer_id' => 'require',
        'grapher' => 'require|number',
        'designer_master' => 'require|number',
        'translator' => 'require',
        'create_time' => 'require'
    ];

    protected $message = [
        'developer_id.require' => '开发者不能为空',
        'developer_id.number' => '开发者为整形',
        'grapher.require' => '摄影师不能为空',
        'grapher.number' => '摄影师为整形',
        'translator.require' => '翻译信息不能为空',
        'designer_master.require' => '美工组长不能为空',
        'designer_master.number' => '美工组长为整形',
        'create_time.require' => '创建时间不能为空',
    ];

    protected $scene = [
        'insert' => [
            'developer_id' => 'require|number',
            'grapher' => 'require|number',
            'designer_master' => 'number',
            'translator' => 'require|checkTranslator',
            'create_time' => 'require'
        ],
        'edit' => [
            'developer_id' => 'number',
            'grapher' => 'number',
            'designer_master' => 'number',
            'translator' => 'checkTranslator',
        ]
    ];

    protected function checkTranslator($value, $rule, $data)
    {
        $json = json_decode($value, true);
        foreach ($json as $k => $v) {
            if (empty($k)) {
                return '语言不能为空';
            }
            if (empty($v['translator'])) {

                return $this->getLangNameByCode($k).'翻译员不能为空';
            }
        }
        return true;
    }

    private function getLangNameByCode($code)
    {
        $aLang = Cache::store('Lang')->getLang();
        foreach ($aLang as $lang){
            if($lang['code'] == $code){
                return $lang['name'];
            }
        }
        return '';
    }
}