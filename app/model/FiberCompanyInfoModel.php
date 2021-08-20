<?php


namespace app\model;


use think\Model;

class FiberCompanyInfoModel extends Model
{
    //注册资金范围表

    //指定连接的数据库
    protected $connection   =   'fiberMysql';
    //模型设置后缀后，指定数据表
    protected $name         =   'fiber_company_info';
}