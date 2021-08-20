<?php


namespace app\model;


use think\Model;

class DataScreenPlatformModelsModel extends Model
{
    //数据大屏平台模块表

    //指定连接的数据库
    protected $connection   =   'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name         =   'data_screen_platform_models';

    //反关联
    public function modelsToPlatform()
    {
        return $this->belongsTo(DataScreenPlatformModel::class);
    }
}