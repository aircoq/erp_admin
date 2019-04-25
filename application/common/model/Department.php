<?php
namespace app\common\model;
use traits\model\SoftDelete;
use think\Model;
use app\common\cache\Cache;

/**
 * Created by Netbeans.
 * User: empty
 * Date: 2016/12/23
 * Time: 12:03
 */
class Department extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }
    
    public function isHas($id, $name,$pid)
    {
        $where['id'] = ['<>',$id];
        $where['pid'] = ['=',$pid];
        return $this->where($where)->where('name', $name)->find();
    }
    /**
     * @var array 临时存放channel变量
     */
    private $tmpChannel = [];

    /**
     * @title 返回渠道配置
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public function getChannel()
    {
        if ($this->tmpChannel === []) {
            $this->tmpChannel = cache::store('channel')->getChannel();
        }
        return $this->tmpChannel;
    }

    public function getChannelNameAttr($value,$data){
        $channel = $this->getChannel();
        foreach ($channel as $channel_name=>$v){
            if($v['id']==$data['channel_id']){
                return $channel_name;
            }
        }
        return '';
    }


    /**
     * @title 返回部门列表，所属平台title
     * @param array $data
     * @return string
     */
    public function getChannelTitle($data)
    {
        $channel = $this->getChannel();
        foreach ($channel as $channel_name => $v) {
            if ($v['id'] == $data['channel_id']) {
                return $v['title'];
            }
        }
        return '';
    }
}