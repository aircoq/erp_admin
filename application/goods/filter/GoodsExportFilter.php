<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/10/10
 * Time: 16:33
 */

namespace app\goods\filter;
use app\common\service\Common;
use app\common\traits\User;
use app\common\filter\BaseFilter;
use app\goods\service\GoodsImport;


class GoodsExportFilter extends BaseFilter
{
    use User;
    protected $scope = 'ExportTemplate';

    public static function getName(): string
    {
        return '商品导出字段权限过滤';
    }

    public static function config(): array
    {
        $GoodsImport = new GoodsImport();
        $tmp = $GoodsImport->getBaseField();
        $options = [];
        foreach ($tmp as $v){
            $row = [];
            $row['value'] = $v['key'];
            $row['label'] = $v['title'];
            $options[] = $row;
        }
        return [
            'key' => 'type',
            'type' => static::TYPE_MULTIPLE_SELECT,
            'options' => $options
        ];
    }

    public function generate()
    {
        $type = $this->getConfig();
        $result = [];
        if($type){
            $userInfo = Common::getUserInfo();
            if(in_array(0,$type)){
                $result[] = $userInfo['user_id'];
            }
            IF(in_array(1,$type)){
                $users = $this->getUnderlingInfo($userInfo['user_id']);
                $result = array_merge($result,$users);
            }
        }

        return $result;
    }
}