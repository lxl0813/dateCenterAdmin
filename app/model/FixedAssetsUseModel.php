<?php


namespace app\model;


use think\Model;

class FixedAssetsUseModel extends Model
{

    //资产表角色表

    //指定连接的数据库
    protected $connection = 'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'fixed_assets_use';


    /**
     * @param $value
     * @return string
     */
    public function getStatusAttr($value)
    {
        $status = [1 => '领用', 2 => '归还'];
        return $status[$value];
    }
}