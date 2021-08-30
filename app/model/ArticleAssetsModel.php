<?php


namespace app\model;


use think\Model;

class ArticleAssetsModel extends Model
{
    //物品资产表

    //指定连接的数据库
    protected $connection = 'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'article_assets';
}