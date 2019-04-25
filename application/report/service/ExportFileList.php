<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | File  : ProfitStatement.php
// +----------------------------------------------------------------------
// | Author: LiuLianSen <3024046831@qq.com>
// +----------------------------------------------------------------------
// | Date  : 2017-08-07
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
namespace app\report\service;

use app\report\model\ReportExportFiles;
use erp\AbsServer;
use think\Exception;
use think\Validate;
use app\common\traits\User;

class ExportFileList extends AbsServer
{
    use User;

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function getExportList(array $params)
    {
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $pageSize = isset($params['pageSize']) ? intval($params['pageSize']) : 10;
        $sortField = isset($params['sort_field']) ? trim($params['sort_field']) : 'id';
        $sortType = isset($params['sort_type']) ? trim($params['sort_type']) : 'DESC';
        if (!in_array($sortType, ['ASC', 'DESC'])) $sortType = 'DESC';
        $where = $this->getSearchCondition($params);
        $listModel = new ReportExportFiles();
        $count = $listModel->where($where)->count();
        $records = $listModel->with('user')
            ->where($where)
            ->order($sortField, $sortType)
            ->page($page, $pageSize)->select();

        $data = [];
        foreach ($records as $record) {
            $data[] = array_merge($record->getData(), ['status_text' => $record->status_text]);
        }
        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'data' => $data
        ];
    }


    /**
     * 获取查询查询
     * @param $params
     * @return array
     * @throws Exception
     */
    protected function getSearchCondition($params)
    {
        $condition = [];
        $export_files_id = isset($params['export_files_id']) ? trim($params['export_files_id']) : '';
        if ($export_files_id != '') {  //导出报表编号
            $condition['id'] = $export_files_id;
        }
        $is_admin = $this->isAdmin($params['user_id']);
        if (!$is_admin) {
            $condition['applicant_id'] = trim($params['user_id']);
        } else {
             if (param($params,'applicant_id')) {
                $condition['applicant_id'] = trim($params['applicant_id']);
            }
        }
        $fileNameLike = isset($params['file_name']) ? trim($params['file_name']) : '';
        if ($fileNameLike) {
            $condition['export_file_name'] = ['LIKE', '%' . $fileNameLike . '%'];
        }
        $status = isset($params['status']) ? trim($params['status']) : '';
        if ($status !== '' && in_array($status, [0, 1, 2])) {
            $condition['status'] = $status;
        }

        //买家留评价时间
        $b_time = !empty(param($params, 'time_start')) ? $params['time_start'] . ' 00:00:00' : '';
        $e_time = !empty(param($params, 'time_end')) ? $params['time_end'] . ' 23:59:59' : '';

        if ($b_time) {
            if (Validate::dateFormat($b_time, 'Y-m-d H:i:s')) {
                $b_time = strtotime($b_time);
            } else {
                throw new Exception('导出申请起始日期格式错误(格式如:2017-01-01)', 400);
            }
        }

        if ($e_time) {
            if (Validate::dateFormat($e_time, 'Y-m-d H:i:s')) {
                $e_time = strtotime($e_time);
            } else {
                throw new Exception('导出申请截止日期格式错误(格式如:2017-01-01)', 400);
            }
        }

        if ($b_time && $e_time) {
            $condition['apply_time'] = ['BETWEEN', [$b_time, $e_time]];
        } elseif ($b_time) {
            $condition['apply_time'] = ['EGT', $b_time];
        } elseif ($e_time) {
            $condition['apply_time'] = ['ELT', $e_time];
        }
        return $condition;
    }

    /**
     * 删除报表
     * @param $id
     */
    public function deletes($id)
    {
        $where['id'] = $id;
        $model = new ReportExportFiles();
        $downloadUrl = $model->where($where)->column('download_url');
        $model->where($where)->delete();
        if ($downloadUrl) {
            @unlink(ROOT_PATH . '/public' . $downloadUrl);
        }
        return true;
    }
}