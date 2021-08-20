<?php


namespace app\model;


use think\Model;

class SharesBasicFundsModel extends Model
{
    //股票基本资金表

    //指定连接的数据库
    protected $connection   =   'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name         =   'shares_basic_funds';
}