<?php


namespace app\model;


use think\Model;

class FinanceSubjectTypeModel extends Model
{
    //财务科目分类
    //指定连接的数据库
    protected $connection = 'financeMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'rl_finance_subject_type';
}