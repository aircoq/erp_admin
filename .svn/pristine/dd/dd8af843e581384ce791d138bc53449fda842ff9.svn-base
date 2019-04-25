<?php
namespace app\api\service;

use app\api\help\PackageHelp;
use app\common\cache\Cache;
use think\Exception;
use app\purchase\service\PurchaseParcelsBoxService;
use \app\warehouse\service\Allocation;

/** 打印机
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/12/12
 * Time: 20:54
 */
class Printer extends Base
{
    /**
     * 打印统一回调函数  [identification  标识，message 打印信息，status  状态（0-打印成功  1-打印失败）]
     * @return array
     */
    public function callBack()
    {
        $callBackData = $this->requestData['callback_data'];     //[ status 0-成功  1-失败]
        $packager_id = $_GET['packager_id']??0;
        $is_in_pack = $_GET['is_in_pack']??1;
        $boxer_id = $_GET['boxer_id'] ?? 0;
        $printer_id = $_GET['printer_id'] ?? 0;
        $callBackData = json_decode($callBackData, true);
        //Cache::handler()->hSet('hash:print:raw:log:' . date('Y-m-d', time()) . ':' . date('H', time()), time(), json_encode(['data' => $callBackData, 'in_pack' => $is_in_pack, 'packager' => $packager_id], JSON_UNESCAPED_UNICODE));
        try {
            foreach ($callBackData as $call => $callData) {
                $identificationArr = explode(',', $callData['identification']);
                foreach ($identificationArr as $key => $value) {
                    $type = substr($value, 0, 1);
                    $callData['identification'] = substr($value, 1);
                    if (empty($callData['identification'])) {
                        continue;
                    }
                    switch ($type) {
                        case 'P':   //包裹
                            $help = new PackageHelp();
                            $help->setPrint($callData, $packager_id, 0, $is_in_pack);
                            break;
                        case 'R':   //重发单
                            break;
                        case 'F':  //发票
                            $help = new PackageHelp();
                            $help->setPrintInvoice($callData, $packager_id);
                            break;
                        case 'N':
                            //默认面单
                            $help = new PackageHelp();
                            $help->setPrint($callData, $packager_id, 1, $is_in_pack);
                            break;
                        case 'L'://面单标签
                            $help = new PackageHelp();
                            $help->setPrint($callData, $packager_id, 2, $is_in_pack);
                            break;
                        case 'B': // 卡板标签
                            $help = new PurchaseParcelsBoxService();
                            $help->setPrint($callData, $boxer_id);
                            break;
                        case 'A': // 条形码
                            $help = new Allocation();
                            $help->setPrint($callData, $printer_id);
                            break;
                    }
                    //记录日志
                    //Cache::handler()->hSet('hash:print:log:' . date('Y-m-d', time()) . ':' . date('H', time()), $callData['identification'], json_encode(['type' => $type, 'message' => $callData['message'], 'status' => $callData['status'], 'time' => date('Y-m-d H:i:s', time())], JSON_UNESCAPED_UNICODE));
                }
            }
            $this->retData['message'] = 'success';
        } catch (Exception $e) {
            // halt($e->getFile().$e->getLine());
            $this->retData['message'] = 'fail';
            //记录日志
            //Cache::handler()->hSet('hash:print:error:' . date('Y-m-d', time()), date('H', time()), json_encode(['data' => $callBackData, 'message' => $e->getMessage() . $e->getFile() . $e->getLine()], JSON_UNESCAPED_UNICODE));
        }
        return $this->retData;
    }

}