<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class Supplier extends Validate
{
    protected $rule = [
        ['company_name','require|unique:Supplier','名称不能为空！|名称已存在！'],
        ['code','require|unique:Supplier','统一社会信用代码不能为空|社会信用代码已存在!'],
        ['legal','require','法定代表人不能为空'],
        ['business_license','require','营业执照名称不能为空'],
        ['file','require','营业执照附件不能为空'],
        ['categorys','require','类目不能为空'],
        ['contacts','require','联系人1不能为空'],
        ['contacts_job','require','联系人1职务不能为空'],
        ['mobile','require','联系电话1不能为空'],
        ['address','require','详细地址不能为空'],
        ['new_reason','require','新增理由不能为空'],
//        ['link','require','网站不能为空'],
        ['link','require',' 店铺网址不能为空'],
        ['transaction_type','in:1,2','交易类型仅为【 1-线上交易 2-线下交易】'],
        ['pay_type','in:2,4','支付方式仅为【 2-银行转账 4-支付宝 】'],
        ['balance_type','in:5,6,7,8,9,11,12,13,14,15,16,18,19,20,21,22,23,24,25,26','结算方式仅为【 5-定期结算-周结 6-定期结算-半月结 7-定期结算-30天 8-定期结算-60天 9-定期结算-90天 11-定期结算--阿里30天 12-阿里现结 13-跨境宝-阿里现结 14-跨境宝-阿里30天 15-跨境宝-阿里60天 16-跨境宝-半月结半月付 18-跨境宝-周结周付 19-定期结算-阿里半月结 20-定期结算-阿里周结 21-定期结算-阿里60天 22-阿里45天 23-阿里90天 24-跨境宝-阿里45天 25-跨境宝-阿里90天 26-诚e赊-阿里30天】'],
        ['invoice','in:1,2,3,4,5,6,7,8,9','发票类型仅为【 1-17%增值税专用发票 2-3%增值税普通发票  3-3%普通发票  4-无税 5-17%的增值税普通发票 6-13%的增值税普通发票 7-其他 8-不能开票 9-13%的增值税专用发票 】'],
        ['type','in:0,1,2,3,4,5,6','供应商类型仅为【0-企业（有限责任公司） 1-个人（个人工商户）2-股份有限公司 3-一人有限责任公司 4-个人独资企业 5-自然人独资 6-普通合伙企业】'],
        ['province_id','require','省份ID不能为空'],
        ['city_id','require','城市ID不能为空'],
        ['province_name','require','省份名不能为空'],
        ['city_name','require','城市名不能为空'],
        ['qq','require','qq不能为空'],
        ['status','in:0,1,2','状态值仅为【 0-待审 1-已审 2-禁用 】'],
        ['creator_id','require','创建者不能为空'],
        ['private_accounts','require','对私账号不能为空'],
        ['private_accounts_name','require','对私户名不能为空'],
        ['private_accounts_bank','require','对私账号开户行不能为空'],
        ['delivery_day','require','交货时间不能为空'],
    ];
    protected $scene = [
        'add'   =>  ['company_name','type','invoice','legal','business_license','categorys','contacts','contacts_job','mobile','address','new_reason','qq','province_id','city_id','pay_type','balance_type','file','delivery_day'],
        'api'   =>  ['company_name','code','legal','business_license','contacts','contacts_job','mobile','address','new_reason','link','type','invoice','balance_type','transaction_type','pay_type','province_id','city_id','province_name','city_name','qq','status','creator_id'],
        'apiUpdate'   =>  ['transaction_type','pay_type','balance_type','invoice'],
    ];
}