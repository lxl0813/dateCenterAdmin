<?php


namespace app\model;


use think\Db;
use think\Model;

class AdminRolesModel extends Model
{
    //管理员角色表

    //指定连接的数据库
    protected $connection = 'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'admin_roles';


}