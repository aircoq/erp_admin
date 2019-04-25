<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/24
 * Time: 9:31
 */

namespace app\publish\queue;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\publish\service\AliexpressTaskHelper;
use app\common\model\aliexpress\AliexpressProductInfo;
use app\common\model\aliexpress\AliexpressProduct;
use app\publish\service\AliexpressService;
use think\Exception;

class AliexpressPublishSyncDetailQueue extends SwooleQueueJob {
    protected static $priority=self::PRIORITY_HEIGHT;

    protected $failExpire = 600;

    protected $maxFailPushCount = 3;

    public static function swooleTaskMaxNumber():int
    {
        return 30;
    }

    public function getName():string
    {
        return '速卖通刊登详情同步队列';
    }
    public function getDesc():string
    {
        return '速卖通刊登详情同步队列';
    }
    public function getAuthor():string
    {
        return 'hao';
    }

    public  function execute()
    {
        set_time_limit(0);
        try{
            $params = $this->params;
            if($params)
            {
                $module_contents = '';
                $template_where = [];
                if(isset($params['custom_template_id'])) {

                    $custom_template_id = $params['custom_template_id'];


                    $dear_friend = '';
                    $thanks_for = '';
                    $fonts = '';
                    $understand = '';
                    switch ($custom_template_id) {
                        case 47728563:
                            $module_contents = '<img src="http://ae01.alicdn.com/kf/HTB1KAtva4_rK1RkHFqDq6yJAFXab.jpg">';break;
                        case 27558774:
                            $dear_friend = '<p>Dear Friends：<br/></p>';
                            $thanks_for = '<p>Thanks for you support in 2018，and<b>&nbsp;<font color="#ff0000">CHINESE NEW YEAR</font></b><font color="#ff0000">&nbsp;</font>coming,We will Vacation from&nbsp;Feb.3rd,2019 to Feb.9th.2019.</p>';
                            $fonts = '<p><b><font color="#ff0000">During those days.You can make an order as usual.</font></b>The message we will reply you after holiday.</p>';
                            $understand = '<p>Thanks for your understand.</p>';
                            $module_contents = '<p>Best with for you and your family/Friends.<img src="http://ae01.alicdn.com/kf/HTB1Ldxga2vsK1RjSspdq6AZepXa5.jpg" style="font-size: 1.2rem;"/></p>';
                            break;
                        case 275587741:
                            $module_contents = '<p>	Dear Friends： </p><p>	Thanks for you support in 2018，and<b>&nbsp;<font color="#ff0000">CHINESE NEW YEAR</font></b><font color="#ff0000">&nbsp;</font>coming,We will Vacation from&nbsp;Feb.3rd,2019 to Feb.9th.2019. </p><p>	<b><font color="#ff0000">During those days.You can make an order as usual.</font></b>The message we will reply you after holiday. </p><p>	Thanks for your understand. </p><p>	Best with for you and your family/Friends.<img src="http://ae01.alicdn.com/kf/HTB1Ldxga2vsK1RjSspdq6AZepXa5.jpg" style="font-size: 1.2rem;"/></p>';
                            break;
                        case 275587742:
                            $dear_friend = '<p> Dear Friends： </p>';
                            $thanks_for = '<p> Thanks for you support in 2018，and<b>&nbsp;<font color="#ff0000">CHINESE NEW YEAR</font></b><font color="#ff0000">&nbsp;</font>coming,We will Vacation from&nbsp;Feb.3rd,2019 to Feb.9th.2019. </p>';
                            $fonts = '<p> <b><font color="#ff0000">During those days.You can make an order as usual.</font></b>The message we will reply you after holiday. </p>';
                            $understand = '<p> Thanks for your understand. </p>';
                            $module_contents = '<p> Best with for you and your family/Friends.<img src="http://ae01.alicdn.com/kf/HTB1Ldxga2vsK1RjSspdq6AZepXa5.jpg" style="font-size: 1.2rem;"/></p>';
                            break;
                            break;
                        case 275587743:
                            $dear_friend = '<p>Dear Friends：</p>';
                            $thanks_for = '<p>Thanks for you support in 2018，and<b>&nbsp;<font color="#ff0000">CHINESE NEW YEAR</font>&nbsp;</b>coming,We will Vacation from&nbsp;Feb.3rd,2019 to Feb.9th.2019.</p>';
                            $fonts = '<p>During those days.<font color="#ff0000"><b style="background-color: rgb(255, 255, 255);">You can make an order as usual</b>.</font>The message we will reply you after holiday.</p>';
                            $understand = '<p>Thanks for your understand.</p> ';
                            $module_contents = '<p>Best with for you and your family/Friends.</p> 
<p><br /></p> 
<p><img src="http://ae01.alicdn.com/kf/HTB1sUNja0jvK1RjSspiq6AEqXXah.jpg" /><br /></p>';
                            break;
                        case 275587744:
                            $dear_friend = '<p><img src="http://ae01.alicdn.com/kf/HTB1sUNja0jvK1RjSspiq6AEqXXah.jpg" /><br /></p>';
                            $thanks_for = '<p><font size="3">Dear Friends：</font></p>';
                            $fonts = '<p><font size="3">Thanks for you support in 2018，and <font color="#ff0000">CHINESE NEW YEAR </font>coming,We will Vacation from <font color="#ff0000">Feb.3rd,2019 to Feb.9th.2019</font>.</font></p>';
                            $understand = '<p><font size="3">Thanks for your understand.</font></p> 
<p><font size="3">Best with for you and your family/Friends.</font></p>';
                            $module_contents = '<p><font size="3"><u>During those days.You can make an order as usual.</u>The message we will reply you after holiday.</font></p>';
                            break;
                        case 275587745:
                            $dear_friend = '<p> <img src="http://ae01.alicdn.com/kf/HTB1sUNja0jvK1RjSspiq6AEqXXah.jpg" /></p>';
                            $thanks_for = '<p> <font size="3">Dear Friends：</font></p>';
                            $fonts = '<p> <font size="3">Thanks for you support in 2018，and <font color="#ff0000">CHINESE NEW YEAR </font>coming,We will Vacation from <font color="#ff0000">Feb.3rd,2019 to Feb.9th.2019</font>.</font></p>';
                            $understand = '<p> <font size="3"><u>During those days.You can make an order as usual.</u>The message we will reply you after holiday.</font></p>';
                            $module_contents = '<p> <font size="3">Thanks for your understand.</font></p> 
<p> <font size="3">Best with for you and your family/Friends.</font></p>';
                            break;
                        case 275587746:
                            $dear_friend = '<p>	<img src="http://ae01.alicdn.com/kf/HTB1sUNja0jvK1RjSspiq6AEqXXah.jpg"></p>';
                            $thanks_for = '<p>	<font size="3">Dear Friends&#65306;</font></p>';
                            $fonts = '<p>	<font size="3">Thanks for you support in 2018&#65292;and <font color="#ff0000">CHINESE NEW YEAR </font>coming,We will Vacation from <font color="#ff0000">Feb.3rd,2019 to Feb.9th.2019</font>.</font></p><p>';
                            $understand = '<font size="3"><u>During those days.You can make an order as usual.</u>The message we will reply you after holiday.</font></p>';
                            $module_contents = '<p>	<font size="3">Thanks for your understand.</font></p><p>	<font size="3">Best with for you and your family/Friends.</font></p><p>	&nbsp; </p>';
                            break;
                        case 275587747:
                            $dear_friend = '<p style="margin: 0px; padding: 0px;"> <img height="207" src="http://ae01.alicdn.com/kf/HTB1sUNja0jvK1RjSspiq6AEqXXah.jpg" style="border: none;" width="586" /> </p>';
                            $thanks_for = '<p style="margin: 0px; padding: 0px;"> <font size="3">Dear Friends：</font> </p>';
                            $fonts = '<p style="margin: 0px; padding: 0px;"> <font size="3">Thanks for you support in 2018，and&nbsp;<font color="#ff0000">CHINESE NEW YEAR&nbsp;</font>coming,We will Vacation from&nbsp;<font color="#ff0000">Feb.3rd,2019 to Feb.9th.2019</font>.</font> </p>';
                            $understand = '<p style="margin: 0px; padding: 0px;"> <font size="3"><u>During those days.You can make an order as usual.</u>The message we will reply you after holiday.</font> </p>';
                            $module_contents = '<p style="margin: 0px; padding: 0px;"> <font size="3">Thanks for your understand.</font> </p> 
     <p style="margin: 0px; padding: 0px;"> <font size="3">Best with for you and your family/Friends.</font> </p>';
                            break;
                        case 275587748:
                            $dear_friend = '<p style="margin: 0px; padding: 0px;"> <img height="207" src="http://ae01.alicdn.com/kf/HTB1sUNja0jvK1RjSspiq6AEqXXah.jpg" style="border: none;" width="586" /> </p>';
                            $thanks_for = '<p style="margin: 0px; padding: 0px;"> <font size="3">Dear Friends：</font> </p>';
                            $fonts = '<p style="margin: 0px; padding: 0px;"> <font size="3">Thanks for you support in 2018，and&nbsp;<font color="#ff0000">CHINESE NEW YEAR&nbsp;</font>coming,We will Vacation from&nbsp;<font color="#ff0000">Feb.3rd,2019 to Feb.9th.2019</font>.</font> </p>';
                            $understand = '<p style="margin: 0px; padding: 0px;"> <font size="3"><u>During those days.You can make an order as usual.</u>The message we will reply you after holiday.</font> </p>';
                            $module_contents = '<p style="margin: 0px; padding: 0px;"> <font size="3">Thanks for your understand.</font> </p> 
     <p style="margin: 0px; padding: 0px;"> <font size="3">Best with for you and your family/Friends.</font> </p>';
                            break;
                        case 275587749:
                            $dear_friend = '<p><img src="http://ae01.alicdn.com/kf/HTB1MDR0a0jvK1RjSspiq6AEqXXav.jpg" /><br /></p>';
                            $thanks_for = '<p style="text-align: center;"><font size="4">Dear Friends：</font></p>';
                            $fonts = '<p style="text-align: center;"><font size="4">Thanks for you support in 2018，and&nbsp;CHINESE NEW YEAR&nbsp;coming,We will Vacation from&nbsp;<font color="#ff0000">Feb.3rd,2019 to Feb.9th.2019</font>.</font></p> 
<p style="text-align: center;"><font size="4">During those days.<font color="#ff0000">You can make an order as usual</font>.The message we will reply you after holiday.</font></p>';
                            $understand = '<p style="text-align: center;"><font size="4">Thanks for your understand.</font></p>';
                            $module_contents = '<p style="text-align: center; "><font size="4">Best with for you and your family/Friends.</font></p>';
                            break;
                        case 275587750:
                            $dear_friend = '<p> <img src="http://ae01.alicdn.com/kf/HTB1MDR0a0jvK1RjSspiq6AEqXXav.jpg" /></p>';
                            $thanks_for = '<p style="text-align:center;"> <span style="font-size:medium;">Dear Friends：</span></p>';
                            $fonts = '<p style="text-align:center;"> <span style="font-size:medium;">Thanks for you support in 2018，and&nbsp;CHINESE NEW YEAR&nbsp;coming,We will Vacation from&nbsp;<span style="color:#ff0000;">Feb.3rd,2019 to Feb.9th.2019</span>.</span></p>';
                            $understand = '<p style="text-align:center;"> <span style="font-size:medium;">During those days.<span style="color:#ff0000;">You can make an order as usual</span>.The message we will reply you after holiday.</span></p>';
                            $module_contents = '<p style="text-align:center;"> <span style="font-size:medium;">Thanks for your understand.</span></p> 
<p> &nbsp; </p> 
<p style="text-align:center;"> <span style="font-size:medium;">Best with for you and your family/Friends.</span></p>';
                            break;
                        case 275587751:
                            $dear_friend = '<p>	<img src="http://ae01.alicdn.com/kf/HTB1MDR0a0jvK1RjSspiq6AEqXXav.jpg"></p>';
                            $thanks_for = '<p style="text-align:center;">	<span style="font-size:medium;">Dear Friends&#65306;</span></p>';
                            $fonts = '<p style="text-align:center;">	<span style="font-size:medium;">Thanks for you support in 2018&#65292;and&nbsp;CHINESE NEW YEAR&nbsp;coming,We will Vacation from&nbsp;<span style="color:#ff0000;">Feb.3rd,2019 to Feb.9th.2019</span>.</span></p>';
                            $understand = '<p style="text-align:center;"><span style="font-size:medium;">Thanks for your understand.</span></p>';
                            $module_contents = '<p style="text-align:center;">
    <span style="font-size:medium;">Best with for you and your family/Friends.</span>
</p>';
                            break;
                        case 275587752:
                            $module_contents = '<p><img src="http://ae01.alicdn.com/kf/HTB1zzNva6zuK1RjSspeq6ziHVXag.jpg" /><br /></p>';
                            break;
                        case 275587753:
                            $module_contents = '<p> <img src="http://ae01.alicdn.com/kf/HTB1zzNva6zuK1RjSspeq6ziHVXag.jpg" /></p>';
                            break;
                        case 275587754:
                            $module_contents = '<p><img src="http://ae01.alicdn.com/kf/HTB1zzNva6zuK1RjSspeq6ziHVXag.jpg"><br></p>';
                            break;
                    }
                }


                $productModel = new AliexpressProduct();

                $list = $productModel->alias('p')->field('p.id,p.product_id, a.detail, p.account_id,c.access_token, c.code, c.refresh_token, c.client_secret, c.client_id')->join('aliexpress_product_info a','p.product_id = a.product_id','left')->join('aliexpress_account c','p.account_id = c.id','left')->where('p.product_id','=', $params['product_id'])->find();

                if(empty($list)) {
                    return;
                }



                $val = $list->toArray();

                $productInfoModel = new AliexpressProductInfo();
                $detail = $val['detail'];

                if($dear_friend) {
                    $detail = str_replace($dear_friend,'',$detail);
                }

                if($thanks_for) {
                    $detail = str_replace($thanks_for,'',$detail);
                }

                if($fonts) {
                    $detail = str_replace($fonts,'',$detail);
                }

                if($understand) {
                    $detail = str_replace($understand,'',$detail);
                }

                if($module_contents) {
                    $detail = str_replace($module_contents,'',$detail);
                }

                $detail = trim($detail);
             
                $productId = $val['product_id'];
                $post['productId']= $productId;

                $post['fiedName']='detail';
                $post['fiedvalue']=$detail;

                $post['module']='product';
                $post['class']='product';
                $post['action']='editsimpleproductfiled';
                $post['product_id']=$productId;

                $account = ['id' => $val['account_id'], 'access_token' => $val['access_token'],'code' => $val['code'], 'refresh_token' => $val['refresh_token'],'client_secret' => $val['client_secret'],'client_id' => $val['client_id']];

                $params=array_merge($account ,$post);
                $response = AliexpressService::execute(snakeArray($params));

                if(isset($response['success']) && $response['success'])
                {
                    $log['status']=1;
                    $log['message']="";
                    $productModel->update(['custom_template_id' => 0],['product_id'=>$val['product_id']]);

                    $productModel->update(['custom_template_postion' => ''], ['product_id'=>$val['product_id']]);

                }else{
                    $log['status']=2;
                }

                $productInfoModel->update(['detail' => $detail], ['product_id' => $val['product_id']]);
            }

            return true;
        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }catch (\Throwable $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
}