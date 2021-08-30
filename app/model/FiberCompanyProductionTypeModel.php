<?php


namespace app\model;


use think\Model;

class FiberCompanyProductionTypeModel extends Model
{
    //企业生产类型表

    //指定连接的数据库
    protected $connection = 'fiberMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'fiber_company_production_type';

    //化纤企业类型单表查询搜索器
    public function searchFiberCompanyProductionTypeAttr($query, $value, $data)
    {
        $query->where($value)->order('order');
    }


}