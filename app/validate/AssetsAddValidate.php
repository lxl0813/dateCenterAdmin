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

        //物品资产添加
        "article_assets_name" => "require",
        "article_assets_model" => "require",
        "pur_unit_price" => "require",
        "sales_unit_price" => "require",
        "unit" => "require",
        "add_time" => "require",
        "handler_id" => "require",


        //物品资产添加时要加入流水时需要的验证
        "custom_id" => "require",
        "custom_bank_id" => "require",
        "subject_id" => "require",
        "subject_name" => "require",
        "bank_id" => "require",

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

        //物品资产
        "article_assets_name.require" => "物品资产名称不能为空",
        "article_assets_model.require" => "物品型号不能为空",
        "pur_unit_price" => "采购单价不能为空",
        "sales_unit_price" => "销售单价不能为空",
        "unit.require" => "物品单位不能为空",
        "add_time.require" => "入库时间不能为空",
        "handler_id" => "经办人不能为空",

        //物品资产添加时加入流水单
        "custom_id.require" => "客户公司不能为空",
        "custom_bank_id.require" => "客户银行不能为空",
        "subject_id.require" => "科目不能为空",
        "subject_name.require" => "科目名称不能为空",
        "bank_id.require" => "我方支付银行不能为空",
    ];


    /**
     * 验证场景
     * @var \string[][]
     */
    protected $scene = [
        'fixed_assets_add' => ['fixed_assets_name', 'purchase_time', 'purchase_money', 'use_life', 'residuals_rate'],
        'fixed_assets_use' => ['use_id', 'use_time', 'id', 'status'],
        'article_assets_add' => ['article_assets_name', 'article_assets_model', 'pur_unit_price', 'sales_unit_price', 'unit', 'add_time', 'handler_id'],
        'article_assets_water' => ['custom_id','custom_bank_id','subject_id','bank_id'],
    ];
}