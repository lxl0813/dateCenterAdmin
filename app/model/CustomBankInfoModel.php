<?php


namespace app\model;


use think\Model;

class CustomBankInfoModel extends Model
{
    //客户银行信息记录表

    //指定连接的数据库
    protected $connection = 'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'custom_bank_info';

}