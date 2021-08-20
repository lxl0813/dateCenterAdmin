<?php


namespace app\model;


use think\Model;

class QuotedGoodsAttrModel extends Model
{
    //报价产品属性表
    //指定连接的数据库
    protected $connection   =   'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name         =   'quoted_goods_attr';
}