<?php


namespace app\model;


use think\Model;

class DataScreenPlatformModel extends Model
{
    //数据大屏平台表
    //指定连接的数据库
    protected $connection = 'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'data_screen_platform';

    //关联模块表
    public function platformToModels()
    {
        return $this->hasMany(DataScreenPlatformModelsModel::class, 'platform_id');
    }

    //获取器
    public function getStatusAttr($value)
    {
        $status = ['1' => '正常', '2' => '禁止'];
        return $status[$value];
    }
}