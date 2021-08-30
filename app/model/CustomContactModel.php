<?php


namespace app\model;


use think\Model;

class CustomContactModel extends Model
{
    //客户联系记录表

    //指定连接的数据库
    protected $connection = 'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'custom_contact';


    /**
     * 漏斗状态获取器
     * @param $value
     * @param $data
     * @return string
     */
//    public function getStatusAttr($value,$data)
//    {
//        $status = [1=>'商机',2=>'意向',3=>'立项',4=>'认可',5=>'谈判',6=>'成交'];
//        return $status['status'];
//    }


    /**
     * 联系方式获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getContactTypeStatusAttr($value, $data)
    {
        $status = [1 => '电话', 2 => '见面', 3 => '微信', 4 => 'QQ', 5 => '邮件', 6 => '其他'];
        return $status[$data['status']];
    }
}