<?php


namespace app\model;


use think\Model;

class CustomModel extends Model
{
    //客户表

    //指定连接的数据库
    protected $connection = 'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'custom';

    /**
     * json字段
     * @var string[]
     */
    protected $json = ['contact_action'];


    /**
     * 设置json字段返回格式（数组）
     * @var bool  关闭
     */
    protected $jsonAssoc = true;


    /**
     * 模型状态修改
     * @param $value
     * @return string
     */
    public function getStatusAttr($value)
    {
        $status = [1 => '采购', 2 => '供应'];
        return $status[$value];
    }


}