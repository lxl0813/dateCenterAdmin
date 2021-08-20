<?php


namespace app\model;


use think\Model;

class ProjectModel extends Model
{
    //项目表
    //财务公司信息
    //指定连接的数据库
    protected $connection   =   'dataMysql';
    //模型设置前缀后，指定数据表
    protected $name         =   'project';
}