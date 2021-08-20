<?php


namespace app\model;
use think\Model;

class SharesModel extends Model
{
    //股票主表

    //指定连接的数据库
    protected $connection   =   'sharesMysql';
    //模型设置后缀后，指定数据表
    protected $name         =   'shares';

    //一对多关联
    public function shares()
    {
        return $this->hasMany(SharesDatasModel::class);
    }
}