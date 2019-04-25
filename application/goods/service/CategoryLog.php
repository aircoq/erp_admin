<?php


namespace app\goods\service;

use think\Exception;
use app\common\model\CategoryLog as ModelCategoryLog;
use app\common\cache\Cache;

class CategoryLog
{
    const SCHEMA = [
        'code' => '分类编码',
        'pid' => '上级分类',
        'name' => '分类名称',
        'title' => '分类标题',
        'icon' => '分类图标',
        'keywords' => '关键词',
        'description' => '分类描述',
        'url' => '链接地址',
        'view' => '分类点击数',
        'setting' => '相关配置信息',
        'page_rows' => '每页记录数',
        'sort' => '排序',
        'letter' => '栏目拼音',
        'status' => '状态',
        'permission' => '用户权限列表',
        'ch_customs_title' => '中文报关名称',
        'en_customs_title' => '英文报关名称',
        'sequence' => 'spu使用序列号',
        'developer_id' => '开发员',
        'purchaser_id' => '采购员',
        'sku_checking_id' => 'SKU查重员',
    ];
    static $logData = [];

    public static function add($name)
    {
        $list = [];
        $list['val'] = $name;
        $list['type'] = 'category';
        $list['data'] = [];
        $list['exec'] = 'add';
        self::$logData[] = $list;
    }

    public static function mdf($name, $old, $new)
    {
        $data = self::mdfData($old, $new);

        $info = [];
        foreach ($data as $key) {
            $row = [];
            $row['old'] = $old[$key];
            $row['new'] = $new[$key];
            $info[$key] = $row;
        }
        self::mdfSpuItem($name, $info);
    }

    private static function mdfSpuItem($name, $info)
    {
        $list = [];
        $list['type'] = 'category';
        $list['val'] = $name;
        $list['data'] = $info;
        $list['exec'] = 'mdf';
        self::$logData[] = $list;

    }

    private static function mdfData($old, $new)
    {
        $data = [];
        foreach ($new as $key => $v) {
            if (in_array($key, array_keys(self::SCHEMA))) {
                if ($v != $old[$key]) {
                    $data[] = $key;
                }
            }
        }
        return $data;
    }

    private static function getTitle($list)
    {
        if (in_array($list['type'], ['category'])) {
            return $list['type'] . ":{$list['val']};";
        }
        return '';
    }

    private static function getText()
    {
        $ret = self::$logData;
        $tmp = [];
        foreach ($ret as $list) {
            $result = '';
            if ($list['exec'] == 'mdf') {
                if (!$list['data']) {
                    continue;
                }
                $exec = '修改';
                $title = self::getTitle($list);
                $result .= $exec . $title;
                $arr_temp = [];
                foreach ($list['data'] as $key => $row) {
                    $str = '';
                    if ($list['type'] == 'category') {
                        $keyName = self::SCHEMA[$key];
                        $str .= $keyName . ":";
                    }
                    $strFun = $key . "Text";
                    if (in_array($strFun, get_class_methods(self::class))) {
                        $str .= self::$strFun($row);
                    } else {
                        $str .= self::otherText($row);
                    }
                    $arr_temp[] = $str;
                }
                $result .= implode(";", $arr_temp);

            } else if ($list['exec'] == 'add') {
                $exec = '新增';
                $result .= $exec . $list['type'] . ":{$list['val']};";
            } else if ($list['exec'] == 'del') {
                $exec = '删除';
                $result .= $exec . $list['type'] . ":{$list['val']};";
            } else {
                throw new Exception('无此操作' . $list['exec']);
            }
            $tmp[] = $result;
        }
        return $tmp;
    }

    private static function purchaser_idText($row)
    {
        $userCache = Cache::store('user');
        if(!$row['old']){
            $old = '空';
        }else{
            $old = $userCache->getOneUserRealname($row['old']);
        }
        $new = $userCache->getOneUserRealname($row['new']);
        return "{$old} => {$new}";
    }

    private static function sku_checking_idText($row)
    {
        $userCache = Cache::store('user');
        if(!$row['old']){
            $old = '空';
        }else{
            $old = $userCache->getOneUserRealname($row['old']);
        }
        $new = $userCache->getOneUserRealname($row['new']);
        return "{$old} => {$new}";
    }

    private static function otherText($row)
    {
        return "{$row['old']} => {$row['new']}";
    }

    public static function save($user_id, $category_id, $resource = '', $type = 1)
    {
        $texts = self::getText();
        if ($texts) {
            foreach ($texts as $text) {
                $data = [];
                $data['category_id'] = $category_id;
                $data['operator_id'] = $user_id;
                $data['remark'] = $resource . $text;
                $data['type'] = $type;
                $data['create_time'] = time();
                $ModelCategory = new ModelCategoryLog();
                $ModelCategory->allowField(true)->isUpdate(false)->save($data);
            }
        }
        self::$logData = [];
    }

    public function getLog($id)
    {
        $lists = ModelCategoryLog::where(['category_id' => $id])->select();
        $userCache = Cache::store('user');
        foreach ($lists as &$list) {
            $list['operator'] = $userCache->getOneUser($list['operator_id'])['realname'];
        }
        return $lists;
    }
}