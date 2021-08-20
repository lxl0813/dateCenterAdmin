<?php


namespace app\controller;


use app\model\NodesModel;
use app\model\RolesModel;
use app\model\RolesNodesModel;
use app\Request;
use app\service\RecursionService;
use think\App;
use think\facade\Cookie;
use think\facade\Db;

class SystemController extends RbacController
{
    /**
     * 系统角色列表
     * @param Request $request
     */
    public function system_roles_list(Request $request)
    {
        $system_roles_list      =   RolesModel::select()->toArray();
        foreach ($system_roles_list as $key=>$item){
            $rolenode   =   RolesNodesModel::where('roles_id',$item['id'])->select()->toArray();
            foreach ($rolenode as $k=>$v)
            {
                $system_roles_list[$key]['nodes_list'][]   =   NodesModel::where('id',$v['nodes_id'])->find()?NodesModel::where('id',$v['nodes_id'])->find()->toArray():[];
            }
        }
        return view('',['system_roles_list'=>$system_roles_list]);
    }

    /**
     * 系统角色添加
     */
    public function system_roles_add(Request $request)
    {
        //查询权限
        $nodes_list =   NodesModel::select()->toArray();
        return view('',['nodes_list'=>$nodes_list]);
    }


    /**
     * 对权限树
     */
    public function node_tree()
    {
        $nodes_list  =   NodesModel::select()->toArray();
        foreach ($nodes_list as $key=>$item)
        {
            $nodes_arr[$key]['title']         =    $item['node_name']. "【" . $item['node_controller']."/".$item['node_action'] ."】";
            $nodes_arr[$key]['id']            =    $item['id'];
            $nodes_arr[$key]['field']         =    '';
            $nodes_arr[$key]['parents_id']    =    $item['parents_id'];
            $nodes_arr[$key]['disabled']      =    $item['status']==1?false:true;
        }
        $recursion  =   new RecursionService();
        $nodes_list =   $recursion->nodes_recursion_son($nodes_arr);
        $this->resultSuccess('成功',$nodes_list);
    }

    /**
     * 系统角色添加执行
     */
    public function system_roles_add_do(Request $request)
    {
        //获取Token令牌
        $check = $request->checkToken('__token__');
        //令牌验证
        if(false === $check) {
            return $this->resultError('Token令牌失效！');
        }

        $role_name      =   $request->post('role_name','');
        $role_remarks   =   $request->post('role_remarks','');
        $node_list      =   $request->post('node_list','');
        if($role_name==""){
            $this->resultError('角色名称不存在！');
        }
        if($node_list==""){
            $this->resultError('请勾选权限！');
        }
        $node_list  =   explode(',',$node_list);
        $role_insert    =   [
                                'role_name'     =>  $role_name,
                                'role_remarks'  =>  $role_remarks,
                                'create_time'   =>  date('Y-m-d H:i:s',time()),
                                'update_time'   =>  date('Y-m-d H:i:s',time()),
                                'create_by'     =>  json_decode(Cookie::get('DATACENTER_ADMIN'),true)['id'],
                            ];
        // 启动事务
        Db::startTrans();
        try {
            $role_id    =   RolesModel::insertGetId($role_insert);
            foreach ($node_list as $key=>$item)
            {
                $role_node_list[]   =   [
                                            'roles_id'      =>  $role_id,
                                            'nodes_id'      =>  $item,
                                            'create_time'   =>  date('Y-m-d H:i:s',time()),
                                            'update_time'   =>  date('Y-m-d H:i:s',time()),
                                            'create_by'     =>  json_decode(Cookie::get('DATACENTER_ADMIN'),true)['id'],
                                        ];
            }
            RolesNodesModel::insertAll($role_node_list);
            // 提交事务
            Db::commit();
            $this->resultSuccess('角色创建成功！');return;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->resultError($e->getMessage());return;
        }

    }

    /**
     * 系统角色修改
     */
    public function system_roles_edit(Request $request)
    {

    }

    /**
     * 系统角色修改执行
     */
    public function system_roles_edit_do(Request $request)
    {

    }

    /**
     * 系统角色删除
     */
    public function system_roles_delete(Request $request)
    {

    }



    public function system_nodes_list()
    {
        $nodes_list =   NodesModel::select();


    }



}