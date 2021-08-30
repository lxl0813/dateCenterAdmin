<?php


namespace app\model;


use think\Model;

class SharesLogsModel extends Model
{
    //股票日志表

    //指定连接的数据库
    protected $connection = 'sharesMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'shares_logs';
}