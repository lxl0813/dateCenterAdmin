<?php


namespace app\validate;


use think\Validate;

class SystemAdminAddValidate extends Validate
{
    protected $rule = [
        'admin_account'     =>  'require|alphaDash|length:6,16',
        'admin_name'        =>  'require|chs',
        'password'          =>  'require|alphaDash|length:6,16',
        'rpassword'         =>  'require|confirm:password',
        'admin_sex'         =>  'require|number',
        'admin_mobile'      =>  'require|mobile',
        'admin_email'       =>  'require|email',
        'is_admin'          =>  'number',
        'admin_birthday'    =>  'require|date',
        'admin_head_img'    =>  'fileSize:1024000|fileExt:jpg,png'
    ];

    protected $message  =   [
        'admin_account.require'     =>  '管理员账号必须！',
        'admin_account.alphaDash'   =>  '账号格式错误，为字母和数字，下划线_及破折号-',
        'admin_account.length'      =>  '账号长度在6-16位！',
        'admin_name.require'        =>  '管理员姓名必须！',
        'admin_name.chs'            =>  '管理员姓名必须中文！',
        'password.require'          =>  '密码必须！',
        'password.alphaDash'        =>  '密码格式错误，为字母和数字，下划线_及破折号-',
        'password.length'           =>  '密码长度必须6-16位！',
        'rpassword.require'         =>  '重复密码必须！',
        'admin_sex.require'         =>  '管理员性别必须！',
        'admin_sex.number'          =>  '管理员性别选择必须纯数字！',
        'admin_mobile.require'      =>  '管理员手机必须！',
        'admin_mobile.mobile'       =>  '管理员手机格式错误！',
        'admin_email.require'       =>  '管理员邮箱必须！',
        'admin_email.email'         =>  '管理员邮箱格式错误！',
        'is_admin.number'           =>  '超级管理员选择必须纯数字',
        'admin_birthday.require'    =>  '管理员生日必须！',
        'admin_birthday.date'       =>  '生日日期格式错误！',
    ];
}