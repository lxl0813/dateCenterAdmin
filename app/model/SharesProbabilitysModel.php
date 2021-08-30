<?php


namespace app\model;


use think\Model;

class SharesProbabilitysModel extends Model
{
    //股票概率表

    //指定连接的数据库
    protected $connection = 'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'shares_probabilitys';

}