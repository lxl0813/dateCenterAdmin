<?php


namespace app\model;


use think\Model;

class FiberCompanyDetailTitleModel extends Model
{
    //化纤企业详情标题表

    //指定连接的数据库
    protected $connection = 'fiberMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'fiber_company_detail_title';
}