<?php


namespace app\validate;


use think\Validate;

class CustomAddValidate extends Validate
{
    protected $rule = [
        'custom_name'   =>  'require',
        'add_time'      =>  'require',
        'status'        =>  'require',
        'address'       =>  'require',
        'contact_name'  =>  'require',
        'contact_phone' =>  'require'
    ];

    protected $message  =   [
        'custom_name.require'   => '客户名称不能为空！',
        'add_time.require'      => '添加日期不能为空！',
        'status.require'        => '客户类型不能为空！',
        'address.require'       => '地址不能为空！',
        'contact_name.require'  => '联系人姓名不能为空！',
        'contact_phone.require' => '联系人电话不能为空！',
    ];
}