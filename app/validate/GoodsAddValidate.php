<?php


namespace app\validate;


use think\Validate;

class GoodsAddValidate extends Validate
{
    protected $rule = [
        'cate_id'       =>  'require',
        'goods_name'    =>  'require',
        'goods_price'   =>  'require',
        'attr_id'       =>  'require',
        'unit'          =>  'require'
    ];

    protected $message  =   [
        'cate_id.require'       =>  '分类ID不能为空！',
        'goods_name.require'    =>  '商品名称不能为空！',
        'goods_price.require'   =>  '商品价格不能为空！',
        'attr_id.require'       =>  '商品属性不能为空！',
        'unit.require'          =>  '单位不能为空！'
        ];
}