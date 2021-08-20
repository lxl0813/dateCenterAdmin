<?php


namespace app\model;


use think\Model;

class FinanceCompanyModel extends Model
{
    //财务公司信息
    //指定连接的数据库
    protected $connection   =   'financeMysql';
    //模型设置后缀后，指定数据表
    protected $name         =   'rl_finance_company';
}