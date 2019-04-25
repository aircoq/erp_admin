<?php

/**
 * Description of HealthDataValidate
 * @datetime 2017-7-10  19:52:44
 * @author joy
 */

namespace app\listing\validate;
use think\Validate;
class HealthDataValidate extends Validate{
    protected $rules = [
        ['account_id','require|number','账号id必填,且为数字'],
        ['username','require','用户名必填'],
        ['password','require','密码必填'],
        ['tfa_token','require','短信验证码必填'],
        ['proxy_ip','require','代理服务器IP必填'],
        ['proxy_port','require','代理服务器端口必填'],
        ['proxy_user','require','代理服务器用户名必填'],
        ['proxy_passwd','require','代理服务器密码必填'],
        ['proxy_protocol','require','代理服务器协议必填'],
        ['counterfeit_rate_aim','require|>=:0|<=:1','仿品率目标值必填,且在0在1之间'],
        ['valid_tracking_rate_aim','require|>=:0|<=:1','有效跟踪率目标值必填,且在0在1之间'],
        ['late_confirmed_fulfillment_rate_aim','require|>=:0|<=:1','延迟发货率目标值必填,且在0在1之间'],
        ['average_rating_aim','require|number','30天平均评分目标值必填,且为数字'],
        ['refund_rate_aim','require|number','63天到93天内的退款率目标值必填,且为数字'],
        ['valid_money_aim','require|number','在途金额目标值必填,且为数字'],
        ['unvalid_money_aim','require|number','待确认配送的金额目标值必填,且为数字']
    ];
    protected $scene = [
        'monitor'=>['account_id','counterfeit_rate_aim','valid_tracking_rate_aim','late_confirmed_fulfillment_rate_aim','average_rating_aim','refund_rate_aim','valid_money_aim','unvalid_money_aim'],
        'auth'  => ['account_id','username','password','tfa_token','proxy_ip','proxy_port','proxy_user','proxy_passwd','proxy_protocol'],
    ];
    
    public  function  checkData($post=array(),$scene)
   {  
        $this->check($post,$this->rules,$scene);    
         
        if($error = $this->getError())
        {
            return $error;
        }            
   }
}
