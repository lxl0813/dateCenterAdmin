<?php


namespace app\model;


use think\Model;

class SystemSettingsModel extends Model
{
    //系统配置表

    //指定连接的数据库
    protected $connection   =   'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name         =   'system_settings';

    //数据库系统配置
    public  function searchSystemAttr($query,$value)
    {
        $query->where('system_name',$value);
    }
}