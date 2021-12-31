<?php


namespace app\model;


use think\Model;

class NonIndustryCompanyModel extends Model
{
    //指定连接的数据库
    protected $connection = 'companyMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'non_industry_company';

    /**
     * 漏斗状态获取器
     * @param $value
     * @return string
     */
    public function getRegisterStatusAttr($value)
    {
        $status = [1=>'存续',2=>'注销'];
        return $status[$value];
    }

}