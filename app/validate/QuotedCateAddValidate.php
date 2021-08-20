<?php


namespace app\validate;


use think\Validate;

class QuotedCateAddValidate extends Validate
{
    protected $rule = [
        'cate_id'   =>  'require',
        'cate_name' =>  'require',
    ];

    protected $message  =   [
        'cate_id.require'       => '父级ID不能为空！',
        'cate_name.require'     => '分类名称不能为空！',
    ];
}