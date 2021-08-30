<?php


namespace app\model;


use think\db\Query;
use think\Model;

class SharesDatasModel extends Model
{
    //股票数据表

    //指定连接的数据库
    protected $connection = 'sharesMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'shares_datas';

    //日期范围搜索器
    public function searchTimeAttr($query, $value)
    {
        $query->where('shares_id', $value);
    }

    //A概率搜素器
    public function searchAprobabilityAttr($query, $value)
    {
        $query->where('a_probability', '>', $value);
    }

    //Y概率搜索器
    public function searchYprobabilityAttr($query, $value)
    {
        $query->where('y_probability', '>', $value);
    }

    //X概率搜索器
    public function searchXprobabilityAttr($query, $value)
    {
        $query->where('x_probability', '>', $value);
    }
}