<?php
namespace app\common\model;

use think\Model;
use think\Db;

class LogExportDownloadFiles extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_time';
    protected $updateTime = 'updated_time';
    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /**
     * 封装保存文件导出记录方法
     *
     */
    public function saveExportLog($fileInfo)
    {
        $fileInfo['download_file_name'] = $fileInfo['file_name'].'.'.$fileInfo['file_extension'];
        $fileInfo['saved_path'] = ROOT_PATH . 'public' . DS . 'download' . DS . $fileInfo['path'].DS.$fileInfo['download_file_name'];
        return $this->allowField(true)->isUpdate(false)->save($fileInfo);
    }
   

}