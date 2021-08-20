<?php

namespace app\validate;

use think\Validate;

class DataCenterLoginInValidate extends Validate
{
    protected $rule = [
        'admin_account' => 'require|max:25',
        'password' => 'require|alphaDash',
    ];

    protected $message = [
        'admin_account.require' => '请填写用户名！',
        'password.require' => '请填写密码！',
        'password.alphaDash' => '字母和数字，下划线_及破折号-',
    ];
}