<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | File  : EbayReturnQueue.php
// +----------------------------------------------------------------------
// | Author: tanbin
// +----------------------------------------------------------------------
// | Date  : 2017-09-30
// +----------------------------------------------------------------------

namespace  app\customerservice\queue;

use app\common\cache\Cache;
use app\common\model\ebay\EbayRequestExtend;
use app\common\service\SwooleQueueJob;
use service\ebay\EbayPostorderApi;
use think\Exception;


class EbayReturnFilesQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "下载ebay纠纷订单退款图片队列";
    }

    public function getDesc(): string
    {
        return "下载ebay纠纷订单退款图片队列";
    }

    public function getAuthor(): string
    {
        return "冬";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }

    private $imgDir = 'download/ebay_return';

    public function execute()
    {
        try {
            set_time_limit(0);
            if (empty($this->params['account_id']) || empty($this->params['return_id'])) {
                return;
            }

            $account_id = $this->params['account_id'];
            $return_id = $this->params['return_id'];
            $account = Cache::store('EbayAccount')->getTableRecord($account_id);
            if (empty($account)) {
                return;
            }
            $config = [
                'userToken' => $account['token'],
                'account_id' => $account['id'],
                'account_name' => $account['account_name'],

                //开发者帐号相关信息；
                'devID' => $account['dev_id'],
                'appID' => $account['app_id'],
                'certID' => $account['cert_id'],
            ];
            $ebayApi = new EbayPostorderApi($config);
            $files = $ebayApi->getReturnFile($return_id);

            // 保存扩展信息
            if (!empty($files['files'])) {
                $ebayRequestExtendModel = new EbayRequestExtend();
                foreach ($files['files'] as $file) {
                    $info = $ebayRequestExtendModel->where(['file_id'=>$file['fileId'], 'request_id' => $return_id])->field('id,extend_value')->find();
                    if (!empty($info)) {
                        $tempfile = (strpos($info['extend_value'], $this->imgDir) !== false) ?
                            $info['extend_value'] : $this->imgDir. '/'. $info['extend_value'];
                        $fullPath = ROOT_PATH . 'public/'. $tempfile;
                        if (file_exists($fullPath)) {
                            continue;
                        }
                    }
                    $fileName = 'ebay-return-'. $return_id. '_'. $file['fileId']. '_'. $file['fileName'];
                    $extend_data = [
                        'file_id' => $file['fileId'],
                        'request_id' => $return_id,
                        'account_id' => $account_id,
                        'extend_key' => 'return_file',
                        'extend_value' => $fileName,
                        'initiates_time' => strtotime($file['creationDate']['value']),
                        'created_time' => time(),
                        'update_time' => time()
                    ];

                    //下载成功，则存数据；
                    if ($this->stream2Image($file['fileData'], $fileName)) {
                        if (empty($info)) {
                            $ebayRequestExtendModel->insert($extend_data);
                        } else {
                            unset($extend_data['created_time']);
                            $ebayRequestExtendModel->update($extend_data, ['id' => $info['id']]);
                        }
                    }
                }
            }
        }catch (\Exception $ex){
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * 64base 转 图片
     *
     * @param string $data
     * @param string $save_name
     * @throws Exception
     */
    public function stream2Image($data, $save_name)
    {
        //header('Content:image/png');
        // 数据流不为空，则进行保存操作
        if (!empty($data)) {
            $data = base64_decode($data); // 解码
            $base_path = ROOT_PATH . 'public/'. $this->imgDir;

            if (!is_dir($base_path) && !mkdir($base_path, 0777, true)) {
                throw new Exception('目录创建不成功');
            }
            // $save_name = 'ebay-return-'.date('YmdHis').'.jpg';
            $full_path = $base_path . '/' . $save_name;
            // 创建并写入数据流，然后保存文件
            if ($fp = fopen($full_path, 'w')) {
                fwrite($fp, $data);
                fclose($fp);
                return true;
            } else {
                return false;
            }
        } else {
            // 没有接收到数据流
            return false;
        }
    }
}