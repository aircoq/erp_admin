<?php
namespace app\api\service;

use app\common\cache\Cache;
use app\common\model\OrderPackage;
use app\order\service\PackageService;
use app\warehouse\service\PackageCollection;
use think\Exception;

/** 包裹类
 * Created by PhpStorm.
 * User: Phill
 * Date: 2018/5/10
 * Time: 9:48
 */
class Package extends Base
{
    /**
     * 检查包裹是否已超重  【is_over 0 正常  1 超重 2 低重】
     * @throws Exception
     */
    public function checkIsOverWeight()
    {
        $package_id = $this->requestData['package_id'];
        $weight = $this->requestData['weight'];
        Cache::handler()->hSet('hash:checkIsOverWeight:log:' . date('Y-m-d', time()), date('H', time()), json_encode(['package_id' => $package_id, 'weight' => $weight]));
        try {
            $packageInfo = (new PackageService())->getPackageInfoById($package_id);
            $result = (new PackageCollection())->getOverweight($weight, $packageInfo[$package_id]['estimated_weight']);
            $this->retData['is_over'] = $result;
            $this->retData['estimated_weight'] = $packageInfo[$package_id]['estimated_weight'];
            $this->retData['package_weight'] = $weight;
            (new OrderPackage())->where(['id' => $package_id])->update(['package_weight' => $weight]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}