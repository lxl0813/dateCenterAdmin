<?php
namespace app\validate;

use think\Validate;

class FinanceWaterBaseAddValidate extends Validate
{
    protected $rule = [
        'create_time'       =>  'require|date',
        'company_id'        =>  'require',
        'subject_id'        =>  'require|integer',
        'income_pay'        =>  'require|integer',
        'income_pay_value'  =>  'require|float',
        'account_balance'   =>  'require|float'
    ];

    protected $message  =   [
        'create_time.require'       =>  '请选择日期！',
        'create_time.date'          =>  '日期格式错误！',
        'company_name.require'      =>  '请选择公司！',
        'subject_id.require'        =>  '请选择科目！',
        'subject_id.integer'        =>  '科目格式错误！',
        'income_pay.require'        =>  '请选择收支类型！',
        'income_pay.integer'        =>  '收支类型格式错误！',
        'income_pay_value.require'  =>  '请输入收支金额！',
        'account_balance.require'   =>  '账户余额不能为空！',
        'account_balance.float'     =>  '账户余额格式错误！',
    ];
}