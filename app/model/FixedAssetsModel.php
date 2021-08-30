<?php


namespace app\model;


use think\Model;

class FixedAssetsModel extends Model
{
    //资产表角色表

    //指定连接的数据库
    protected $connection = 'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'fixed_assets';


}