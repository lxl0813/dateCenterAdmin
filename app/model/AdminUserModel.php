<?php
namespace app\model;

use think\Model;

class AdminUserModel extends Model
{
    //管理员用户表

    //指定连接的数据库
    protected $connection = 'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'admin_user';

    //admin账号搜索器
    public function searchAdminAccountAttr($query,$value)
    {
        $query->where('admin_account',$value);
    }

    //核验
}