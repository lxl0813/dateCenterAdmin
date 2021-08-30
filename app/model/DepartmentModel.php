<?php


namespace app\model;


use think\exception\ErrorException;
use think\Model;

class DepartmentModel extends Model
{
    //部门表

    //指定连接的数据库
    protected $connection = 'dataMysql';
    //模型设置后缀后，指定数据表
    protected $name = 'department';

    /**
     * 查询所有部门
     * @param array $where
     * @param string $order
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function selects(array $where = null, string $order = null)
    {
        $result = self::where($where)->order($order)->select()->toArray();
        //dump($result);exit;
        return $result;
    }

    /**
     * @param array $where
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function finds(array $where = null)
    {
        $result = self::where($where)->find();
        return $result;
    }

}