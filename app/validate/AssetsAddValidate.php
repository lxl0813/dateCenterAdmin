<?php


namespace app\validate;


use think\Validate;

/**
 * Class AssetsAddValidate
 * @package app\validate
 */
class AssetsAddValidate extends Validate
{
    /**
     * @var string[]
     */
    protected $rule = [
        //固定资产添加
        'fixed_assets_name' => 'require',
        'purchase_time' => 'require|date',
        'purchase_money' => 'require|integer',
        'use_life' => 'require|number',
        'residuals_rate' => 'require|float',

        //资产领用
        'use_id' => 'require',
        'status' => 'require',
        'use_time' => 'require|date',
        'fixed_assets_id' => 'require',
    ];

    /**
     * @var string[]
     */
    protected $message = [
        //添加资产
        'fixed_assets_name.require' => '固定资产名称不能为空！',
        'purchase_time.require' => '固定资产购置日期不能为空！',
        'purchase_time.date' => '固定资产购置日期格式错误，请使用xxxx-xx-xx格式！',
        'purchase_money.require' => '固定资产购置金额不能为空！',
        'purchase_money.integer' => '固定资产购置金额格式错误，请填写数字！',
        'use_life.require' => '使用年限不能为空！',
        'use_life.number' => '使用年限格式错误，请填写正整数！',
        'residuals_rate.require' => '残值率不能为空！',
        'residuals_rate.float' => '残值率格式错误，请填写小数！',

        //固定资产领用
        'use_id.require' => '领用人不能为空',
        'use_time.require' => '领用时间不能为空',
        'use_time.date' => '领用时间格式错误',
        'id' => '固定资产不能为空',
        'status' => '领用状态不能为空',


    ];


    /**
     * 验证场景
     * @var \string[][]
     */
    protected $scene = [
        'fixed_assets_add'  =>  ['fixed_assets_name','purchase_time','purchase_money','use_life','residuals_rate'],
        'fixed_assets_use' => ['use_id','use_time','id','status'],
    ];
}