<?php


namespace app\model;
use think\Model;

class DataScreenPlatformModelsFieldValueModel extends Model
{

    //数据大屏平台模块字段值表
    //指定连接的数据库
    protected $connection   =   'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name         =   'data_screen_platform_models_field_value';

}