<?php
namespace  app\customerservice\queue;

use app\common\service\SwooleQueueJob;
use imap\EmailAccount;
use app\customerservice\service\EbayEmail;
use Exception;
use app\index\service\Email;
use app\common\model\EmailServer as EmailServiceMode;
use think\Db;
use app\common\service\Encryption;
use app\common\model\ebay\EbayAccount;
use app\common\model\Postoffice as ModelPostoffice;

class EbayEmailReceiveQueue extends SwooleQueueJob
{
  
    public function getName(): string
    {
        return "下载Ebay邮箱邮件";
    }

    public function getDesc(): string
    {
        return "下载Ebay邮箱邮件";
    }

    public function getAuthor(): string
    {
        return "denghaibo";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    private function record_error($email_account,$msg)
    {
        $email = new Email();

        $patten = '/LOGIN failed|Can not authenticate to IMAP server/i';
        if(preg_match($patten, $msg,$match)){
            $email->errorlog($email_account,$match[0] . ' ip is: ' . '14.118.130.x or 211.154.141.65 ');
        }
    }

    public function execute()
    {
//        if(filter_var($this->params, FILTER_VALIDATE_EMAIL) === false) {
//            return;
//        }


        $email = [];

        try{
            set_time_limit(0);
            $serv = new EbayEmail();
            $encryption = new Encryption();
            $ebayAccountModel = new EbayAccount();

            if (empty($this->params)) {
                return false;
            }

            $email = Db::table('account')->where('a.id',$this->params['id'])->alias('a')
                ->join('email e','e.id=a.email_id','LEFT')
                ->field('a.account_code, e.email, e.password, e.post_id')->find();
            $email['account_id'] = $ebayAccountModel->where('code', $email['account_code'])->value('id');
            $email['email_password'] = $encryption->decrypt($email['password']); //密码解密

            $imap_data = ModelPostoffice::where([ 'id' => $email['post_id'] ])->find();
            if (empty($imap_data))
            {
                return false;
            }

            $accountObj = new EmailAccount(
                $email['account_id'],
                $email['email'],
                $email['email_password'],
                $imap_data['imap_url'],
                $imap_data['imap_port'],
                $imap_data['smtp_url'],
                $imap_data['smtp_port'],
                'Ebay'
            );
            $serv->receiveEmail($accountObj, $email['account_id']);
            return true;
        } catch (Exception $e){
            $this->record_error($email['email'],$e->getMessage());
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }
}