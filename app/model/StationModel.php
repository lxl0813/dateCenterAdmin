<?php


namespace app\model;


use think\Model;

class StationModel extends Model
{
    //岗位表
    //指定连接的数据库
    protected $connection   =   'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name         =   'station';
}