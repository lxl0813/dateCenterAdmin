<?php


namespace app\model;

use think\Model;

class SharesTradersNums extends Model
{
    //股票交易手数表

    //指定连接的数据库
    protected $connection = 'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'shares_traders_nums';
}