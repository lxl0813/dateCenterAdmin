<?php


namespace app\model;


use think\Model;

class CommonCityModel extends Model
{
    //省市表
    //指定连接的数据库
    protected $connection = 'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'common_city';


}