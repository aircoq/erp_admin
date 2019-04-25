<?php
// +----------------------------------------------------------------------
// | 客服发送邮件功能控制器
// +----------------------------------------------------------------------
// | File  : EbayEmail.php
// +----------------------------------------------------------------------
// | Author: LiuLianSen <3024046831@qq.com>
// +----------------------------------------------------------------------
// | Date  : 2017-07-18
// +----------------------------------------------------------------------
namespace app\customerservice\controller;


use app\common\controller\Base;
use app\common\model\User;
use app\common\service\ChannelAccountConst;
use think\Exception;
use think\Request;
use app\customerservice\service\EbayEmail as EbayEmailServ;

/**
 * @module 客服管理
 * @title Ebay发送售后邮件
 * @author 冬
 * @url /ebay-emails/sent-list
 */
class EbaySentEmail extends Base
{

    /**
     * @var EbayEmailServ
     */
    protected $defServ = null;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->defServ = new EbayEmailServ();
    }


    /**
     * @title 查询Ebay邮件列表
     * @url /ebay-emails/sent-list
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        return json($this->defServ->searchSentEmail($request->param()));
    }

    /**
     * @title Ebay发送邮件
     * @url /ebay-emails/send
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function create(Request $request)
    {
        try {
            $this->defServ->send($request->post());
            return json(['message' => '发送成功']);
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            return json(['message' => $msg], 400);
        }
    }

    /**
     * @title 回复Ebay邮件
     * @url /ebay-emails/reply
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function replyEmail(Request $request)
    {
        try {
            $this->defServ->replyEmail($request->param());
            return json(['message' => '回复成功']);
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            $code = $ex->getCode();
            if ($code == 0) {
                $msg = '程序内部错误';
                $code = 500;
            }
            return json(['message' => $msg], $code);
        }
    }

    /**
     * @title Ebay失败邮件重新发送
     * @url /ebay-emails/resend/:mail_id
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function reSendMail(Request $request)
    {
        try {
            $this->defServ->reSendMail($request->param());
            return json(['message' => '发送成功']);
        } catch (Exception $ex) {
            $code = $ex->getCode();
            $message = $ex->getMessage();
            if ($code == 0) {
                $code = 500;
                $message = '程序内部错误';
            }
            return json(['message' => $message], $code);
        }
    }

    /**
     * @title Ebay单号获取帐号邮箱
     * @url /ebay-emails/getBuyerInfo
     * @method GET
     * @param Request $request
     * @return \think\response\Json
     */
    public function getBuyerInfo(Request $request) {
        try {
            $data = $this->defServ->getBuyerInfoByOrderNo($request->get());
            return json(['data' => $data]);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
            return json(['message' => $message], 400);
        }
    }


    /**
     * @title 获取单账号客服列表
     * @url /ebay-emails/account/customers
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function getAmazonAccountCustomerList(Request $request)
    {
        try{
            $accountId = $request->get('account_id', 0);
            if(empty($accountId)){
                throw new Exception('平台账号id未设置',400);
            }

            $data = $this->defServ->getCustormer($accountId);
            return json(['data'=>$data]);
        }catch (Exception $ex){
            $code = $ex->getCode();
            return json(['message'=>$ex->getMessage()],$code);
        }
    }
}
