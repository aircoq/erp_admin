<?php


namespace app\common\model\account;

use app\common\model\Email;
use app\common\model\AccountApply;
use think\Model;
use app\common\service\Encryption;

class AccountApplyDetail extends Model
{

    public function setPasswordAttr($value)
    {
        $Encryption = new Encryption();
        return $Encryption->encrypt($value);
    }

    public function getPasswordAttr($value){
        $Encryption = new Encryption();
        return $Encryption->decrypt($value);
    }

    public function email(){
        return $this->hasOne(Email::class,'id','email_id');
    }

    public function getEmailTxtAttr($value,$data){
        $result = '';
        if (isset($data['id'])) {
            $ApplyData = [
                'id' => $data['id'],
                'email_id' => $data['email_id'],
            ];
            $AccountApplyDetail = new self($ApplyData);

            $result = $AccountApplyDetail->email ? $AccountApplyDetail->email['email'] : '';
        }
        return $result;
    }

    public function collection(){
        return $this->hasMany(AccountApplyDetailCollection::class, 'account_apply_detail_id', 'id');
    }

    public function creditCard(){

        return $this->hasOne(CreditCard::class,'id','credit_card_id');
    }

    public function getCreditCardTxtAttr($value,$data){
        $result = '';
        if (isset($data['id'])) {
            $ApplyData = [
                'id' => $data['id'],
                'credit_card_id' => $data['credit_card_id'],
            ];
            $AccountApplyDetail = new self($ApplyData);

            $result = $AccountApplyDetail->creditCard ? $AccountApplyDetail->creditCard['card_number'] : '';
        }
        return $result;
    }
    public function getPhoneAttr($value,$data){
        $result = '';
        if(isset($data['account_apply_id'])){

            $ApplyData = [
                'id'=>$data['account_apply_id'],
                'phone_id'=>$data['phone_id'],
            ];
            $AccountApply = new AccountApply($ApplyData);
            $result = $AccountApply->phone?$AccountApply->phone->phone:'';
        }
        return $result;
    }

    public function getServerAttr($value,$data){
        $result = '';
        if(isset($data['account_apply_id'])){

            $ApplyData = [
                'id'=>$data['account_apply_id'],
                'server_id'=>$data['server_id'],
            ];
            $AccountApply = new AccountApply($ApplyData);
            $result = $AccountApply->server?$AccountApply->server['name']:'';
        }
        return $result;
    }

    public function getCompanyAttr($value,$data){
        $result = '';
        if(isset($data['account_apply_id'])){

            $ApplyData = [
                'id'=>$data['account_apply_id'],
                'company_id'=>$data['company_id'],
            ];
            $AccountApply = new AccountApply($ApplyData);
            $result = $AccountApply->companyRelation?$AccountApply->companyRelation->company:'';
        }
        return $result;
    }


}