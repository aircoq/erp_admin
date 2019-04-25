<?php


namespace app\api\help;

use app\order\service\OrderHelp;
use app\order\service\PackageService;
use think\Exception;
use app\warehouse\service\PickingPackageService;

class PackageHelp
{
    /**
     * 设置为已打印
     * @param $packageId
     * @param int $packager_id
     * @param bool|false $is_default 【是否为打印默认面单】
     * @param $is_in_pack
     * @return array
     * @throws Exception
     */
    public function setPrint($packageId, $packager_id = 0, $is_default = 0, $is_in_pack)
    {
        $result = ['success' => true];
        $vPackageId = $packageId['identification'];
        if ($packageId['status'] == 0) {
            $PackageService = new PackageService();
            $aPackage = $PackageService->getPackageInfoById($vPackageId);
            if (!$aPackage) {
                throw new Exception('该包裹不存在');
            }
            $PickingPackageService = new PickingPackageService();
            $PickingPackageService->signPrint($vPackageId, 0, $packager_id, $is_default, $is_in_pack);
            return $result;
        }
        return ['success' => false, 'err_msg' => '打印失败'];
    }

    /**
     * 设置发票打印
     * @param $data
     * @param int $packager_id
     * @return array
     */
    public function setPrintInvoice($data, $packager_id = 0)
    {
        $result = ['success' => true];
        $package_id = $data['identification'];
        if ($data['status'] == 0) {
            (new OrderHelp())->signInvoice($package_id, $packager_id);
            return $result;
        }
        return ['success' => false, 'err_msg' => '打印失败'];
    }
}