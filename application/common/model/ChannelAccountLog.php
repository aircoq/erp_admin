<?php
namespace app\common\model;

use think\Model;

class ChannelAccountLog extends Model
{
    const INSERT = 0;
    const UPDATE = 1;
    const DELETE = 2;

    const LOG_TYPE = [
        self::INSERT => '插入',
        self::UPDATE => '编辑',
        self::DELETE => '删除',
    ];

    const LOG_CONF = [
        'site_status' => [
            'name' => '账号状态',
            'type' => 'list',
            'value' => [
                0 => '未分配',
                1 => '运营中',
                2 => '回收中',
                3 => '冻结中',
                4 => '申诉中',
                5 => '已回收',
                6 => '已作废',
            ],
        ],
    ];

    protected function initialize()
    {
        parent::initialize();
    }

    public static function addLog($data = [])
    {
        return (new ChannelAccountLog)->allowField(true)->isUpdate(false)->save($data);
    }

    public function getLog(int $channel_id = 0, int $account_id = 0, $field = true, int $page = 1, int $pageSize = 10): array
    {
        $field === true and $field = 'operator_id,operator,remark,create_time';
        return $this
            ->field($field)
            ->where([
                'channel_id' => $channel_id,
                'account_id' => $account_id,
            ])
            ->page($page, $pageSize)
            ->order('id DESC')
            ->select()
            ?? [];
    }

    /**
     * 格式化备注信息
     */
    public static function getRemark(array $conf = [], int $type = 0, $key = '', $new_v = '', $old_v = ''): string
    {
        /**
         * 使用公共字段
         */
        if (isset(self::LOG_CONF[$key])) {
            $conf = self::LOG_CONF[$key];
        }
        $ref = '';
        if (self::UPDATE == $type) {
            $ref = self::LOG_TYPE[$type] . $conf['name'];
            if ($conf['type'] == 'list') {
                $ref .= '，【' . $conf['value'][$old_v] . '】更改为【' . $conf['value'][$new_v] . '】';
            } else if ($conf['type'] == 'time') {
                $ref .= '，【' . self::formatTime($old_v) . '】更改为【' . self::formatTime($new_v) . '】';
            } else if ($conf['type'] == 'key') {
                $ref .= '，【' . self::formatKey($old_v) . '】更改为【' . self::formatKey($new_v) . '】';
            } else {
                $ref .= '，【' . $old_v . '】更改为【' . $new_v . '】';
            }
        } else {
            $val = self::INSERT == $type ? $new_v : $old_v;
            if ($conf['type'] == 'key') {
                $val = self::formatKey($val);
            }
            $ref .= '[' . $key . '] = ' . $val;
        }
        return $ref;
    }

    public static function formatKey($key = ''): string
    {
        return $key && is_string($key)
        ? substr($key, 0, 2) . "****" . substr($key, -2, 2)
        : '';
    }

    /**
     * 格式化时间
     */
    public static function formatTime(int $time = 0): string
    {
        $ref = '';
        if ($hour = floor($time / 60)) {
            $ref .= $hour . '小时';
            $minutes = $time % 60 and $ref .= $minutes . '分';
        } else {
            $minutes = $time % 60;
            $ref .= $minutes ? $minutes . '分' : '未启用';
        }
        return $ref;
    }
}
