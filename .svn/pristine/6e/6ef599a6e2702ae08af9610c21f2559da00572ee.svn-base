<?php


namespace app\common\validate;


use app\goods\service\TortImport;
use think\Exception;
use think\Validate;
use app\common\model\GoodsTortDescription as GoodsTortDescriptionModel;
use app\common\model\Goods;

class GoodsTortDescription extends Validate
{

    protected $rule = [
        'id' => 'require',
        'spu' => 'require',
        'goods_id' => 'require',
        'channel_id' => 'require',
        'account_id' => 'require',
        'site_code' => 'require',
        'remark' => 'require',
        'tort_type' => 'require',
        'tort_time' => 'require',
        'create_id' => 'require',
        'create_time' => 'require',
        'email_img' => 'checkEmailImage'
    ];
    protected $message = [
        'id.require' => 'id不能为空',
        'id.isPositiveInteger' => 'id须为正整数',
        'id.checkRecordIsExist' => '记录不存在',
        'spu.require' => 'SPU不能为空',
        'spu.checkGoodsSpu' => 'SPU不存在',
        'goods_id.require' => 'goods_id不能为空',
        'goods_id.isPositiveInteger' => '商品id必须是正整数',
        'channel_id.require' => '平台不能为空',
        'channel_id.isPositiveInteger' => '平台id须为正整数',
        'account_id.require' => '帐号不能为空',
        'account_id.isPositiveInteger' => '帐号id须为正整数',
        'site_code.require' => '站点不能为空',
        'tort_type.require' => '侵权类型不能为空',
        'tort_type.checkTortType' => '请选择正确的侵权类型',
        'tort_type.checkRecordUnique' => '侵权记录已存在',
        'remark.require' => '侵权描述不能为空',
        'tort_time.require' => '侵权时间不能为空',
        'tort_time.number' => '侵权时间须为整形',
        'create_id.require' => '创建人不能为空',
        'create_id.number' => '创建人须为整形',
        'create_time.require' => '创建时间不能为空',
        'create_time.number' => '创建时间须为整形',
        'email_img.checkEmailImage' => '侵权邮件上传图片参数有误'
    ];

    protected $scene = [
        //商品详情  侵权记录的新增
        'insert' => [
            'goods_id' => 'require|isPositiveInteger',
            'channel_id' => 'require|isPositiveInteger',
            'account_id' => 'require|isPositiveInteger',
//            'site_code'=>'require',
            'tort_type' => 'require|checkTortType|checkRecordUnique',
            'remark' => 'require',
            'tort_time' => 'require|number',
            'create_time' => 'require|number',
        ],
        //商品详情  侵权记录的修改
        'edit' => [
            'id' => 'require|isPositiveInteger|checkRecordIsExist',
            'goods_id' => 'require|isPositiveInteger',
            'channel_id' => 'require|isPositiveInteger',
            'account_id' => 'require|isPositiveInteger',
            'tort_type' => 'require|checkTortType|checkRecordUnique',
            'remark' => 'require',
            'tort_time' => 'require|number'
        ],
        //侵权列表  侵权记录新增
        'create' => [
            'spu' => 'require|checkGoodsSpu',
            'channel_id' => 'require|isPositiveInteger',
            'account_id' => 'require|isPositiveInteger',
            'tort_type' => 'require|checkTortType|checkRecordUnique',
            'remark' => 'require',
            'tort_time' => 'require|number',
            'create_time' => 'require|number',
        ],
        //侵权列表  编辑时显示
        'show' => [
            'id' => 'require|isPositiveInteger|checkRecordIsExist',
        ],
        //侵权列表  侵权记录修改
        'update' => [
            'id' => 'require|isPositiveInteger|checkRecordIsExist',
            'spu' => 'require|checkGoodsSpu',
            'channel_id' => 'require|isPositiveInteger',
            'account_id' => 'require|isPositiveInteger',
            'tort_type' => 'require|checkTortType|checkRecordUnique',
            'remark' => 'require',
            'tort_time' => 'require|number'
        ],
        'email' => [
            'email_img' => 'checkEmailImage'
        ]
    ];

    /**
     * 验证正整数
     * @param $value
     * @param string $rule
     * @param string $data
     * @param string $field
     * @return bool
     */
    protected function isPositiveInteger($value, $rule = '', $data = '', $field = '')
    {
        if (is_numeric($value) && is_int($value + 0) && ($value + 0) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证spu是否存在
     * @param $value
     * @return bool
     * @throws
     */
    protected function checkGoodsSpu($value)
    {
        $row = Goods::get(['spu' => $value]);
        return is_null($row) ? false : true;
    }

    /**
     * 侵权记录验证唯一性
     * 同SPU、同平台、同站点、同账号、同侵权类型不可重复添加，无需进行侵权描述的验证
     * @param $value
     * @param string $rule
     * @param $data
     * @param string $field
     * @return bool|string
     */
    protected function checkRecordUnique($value, $rule = '', $data, $field = '')
    {
        $where = [];
        if (array_key_exists('goods_id', $data)) {
            $goodsId = $data['goods_id'];
        } elseif (array_key_exists('spu', $data)) {
            $goods = Goods::get(['spu' => $data['spu']]);
            $goodsId = $goods->id;
        } else {
            throw new Exception('无效的商品标识', 400);
        }

        $channelId = $data['channel_id'];
        $accountId = $data['account_id'];
        $siteCode = $data['site_code'] ?? '';
        if (array_key_exists('id', $data) && $pk = $data['id']) {
            $where[] = ['id', '<>', $pk];
        }
        $where[] = ['goods_id', '=', $goodsId];
        $where[] = ['channel_id', '=', $channelId];
        $where[] = ['account_id', '=', $accountId];
        $where[] = ['site_code', '=', $siteCode];
        if (array_key_exists($value, TortImport::TYPE) && $tortType = TortImport::TYPE[$value]) {
            $where[] = ['tort_type', '=', $tortType];
        }
        $where = function ($query) use ($where) {
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    call_user_func_array([$query, 'where'], $v);
                } else {
                    $query->where($v);
                }
            }
        };
        $row = GoodsTortDescriptionModel::where($where)->find();
        return is_null($row) ? true : false;
    }

    protected function checkTortType($value)
    {
        if (array_key_exists($value, TortImport::TYPE)) {
            return true;
        }
        return false;
    }

    protected function checkRecordIsExist($value)
    {
        $row = GoodsTortDescriptionModel::get($value);
        return is_null($row) ? false : true;
    }

    protected function checkEmailImage($value)
    {
        if (empty($value)) {
            return false;
        }
        if (!is_array($value)) {
            $value = json_decode($value, true);
        }
        foreach ($value as $v) {
            if (!isset($v['path']) || !isset($v['unique_code'])) {
                return false;
            }
            $fileExtension = pathinfo($v['path'], PATHINFO_EXTENSION);
            if (!in_array($fileExtension, TortImport::EMAIL_CONTENT_IMAGE_TYPE)) {
                return false;
            }
        }
        return true;
    }
}