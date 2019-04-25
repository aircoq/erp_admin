<?php
namespace app\report\filter;

use app\common\filter\BaseFilter;
use app\common\model\Goods;
use app\common\service\Common;
use app\common\traits\User;
use app\common\model\User as ModelUser;

/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2019/4/15
 * Time: 16:45
 */
class GoodsAnalysisByDeveloperFilter extends BaseFilter
{
    use User;
    protected $scope = 'Developer';

    public static function getName(): string
    {
        return '通过开发员过滤商品销量分析';
    }

    public static function config(): array
    {
        $options = ModelUser::where('status',1)->where('job', 'development')->field('id as value, realname as label')->select();
        return [
            'key' => 'type',
            'type' => static::TYPE_SELECT,
            'options' => $options
        ];
    }

    public function generate()
    {
        $userInfo = Common::getUserInfo();
        //获取自己和下级用户
        $userList = $this->getUnderlingInfo($userInfo['user_id']);
        //查询当前开发员对应的goods_id
        $model = new Goods();
        $goodsIds = [];
        $goodsInfo = $model::where(['developer_id' => ['in', $userList]])->field('id')->select();
        if ($goodsInfo) {
            $goodsIds = array_column($goodsInfo, 'id');
        }
        return $goodsIds;
    }
}