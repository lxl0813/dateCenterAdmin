<?php


namespace app\model;


use think\Model;

class QuotedCateModel extends Model
{
    //报价产品分类表
    //指定连接的数据库
    protected $connection = 'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'quoted_cate';
}