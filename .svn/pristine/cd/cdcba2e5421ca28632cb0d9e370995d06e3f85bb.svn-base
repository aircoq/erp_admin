<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/9/28
 * Time: 10:55
 */

namespace app\common\validate;


use \think\Validate;

class GoodsCategotyMap extends Validate
{
    protected $rule = [
        ['goods_id', 'require|number', '产品id不能为空|产品id为整数'],
        ['channel_id', 'require|number|checkChannel', '渠道ID不能为空|渠道id为数字|无站点平台，同一个平台只能添加一个分类，请选择其他平台'],
        ['site_id','require|number|checkSite','站点id不能为空|站点id为整数|同一个平台的站点只能添加一个分类，请选择其他站点'],
        ['channel_category_id', 'require|number', '帐号ID不能为空!|帐号ID为整数']
    ];
    private $baseSite = [];
    protected function checkSite($site_id,$rule='',$data){
        if($site_id>0){
            if(isset($this->baseSite[$data['channel_id']])){
                $siteData = $this->baseSite[$data['channel_id']];
                if(isset($siteData[$site_id])){
                    return false;
                }else{
                    $this->baseSite[$data['channel_id']][$site_id] = true;
                    return true;
                }
            }
        }
        return true;
    }
    private $baseChannel = [];
    protected function checkChannel($channel_id,$rule,$data){
        if(!$data['site_id']){
            if(isset($this->baseChannel[$channel_id])){
                return false;
            }else{
                $this->baseChannel[$channel_id] = true;
                return true;
            }
        }
        return true;
    }
}