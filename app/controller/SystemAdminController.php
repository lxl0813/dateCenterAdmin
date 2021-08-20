<?php


namespace app\controller;


use app\model\AdminRolesModel;
use app\model\AdminUserModel;
use app\model\RolesModel;
use app\model\RolesNodesModel;
use app\model\SystemSettingsModel;
use app\Request;
use app\service\ExcelService;
use app\validate\SystemAdminAddValidate;
use think\App;
use think\exception\ValidateException;
use think\facade\Cookie;
use think\facade\Db;
use think\facade\Filesystem;

class SystemAdminController extends RbacController
{
    protected $admin_account;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->admin_account  =   json_decode(Cookie::get('DATACENTER_ADMIN'),true);
    }

    /**
     * 系统管理员列表
     * @param Request $request
     */
    public function system_admin_list(Request $request)
    {
        $admin_sex      =   $request->get('admin_sex','');
        $admin_status   =   $request->get('admin_status','');
        $map            =   [];
        if($admin_sex){
            $map['admin_sex']       =   ['admin_sex','=',$admin_sex];
        }
        if($admin_status){
            $map['admin_status']    =   ['admin_status','=',$admin_status];
        }
        $page               =   SystemSettingsModel::where('system_name','分页设置')->value('system_value');
        $system_admin_list  =   AdminUserModel::where($map)->paginate(['list_rows'=>$page,'query'=>request()->param()])->each(function ($key,$item){
            if($key['admin_sex']==1){
                $key['admin_sex'] =   '男';
            }elseif($key['admin_sex']==2){
                $key['admin_sex'] =   '女';
            };
            if($key['is_admin']==1){
                $key['role']    =   ['超级管理员'];
            }else{
                $key['role']    =   AdminRolesModel::where('admin_id',$key['id'])->column('role_name');
            }
            $key['admin_birthday']  =   date('Y',time()) -date('Y',strtotime($key['admin_birthday']));
        });
        return view('',['system_admin_list'=>$system_admin_list]);
    }




    /**
     * 系统管理员添加
     * @param Request $request
     */
    public function system_admin_add(Request $request)
    {
        $role_list   =   RolesModel::select()->toArray();
        return view('',['role_list'=>$role_list]);
    }


    /**
     * 管理员添加执行
     * @param Request $request
     */
    public function system_admin_add_do(Request $request)
    {
        $form_data              =   $request->post();
        $form_data['file']      =   $request->file('admin_head_img');
        $form_data['admin_sex'] =   (int)$form_data['admin_sex'];
        $form_data['is_admin'] =   (int)$form_data['is_admin'];
        if($form_data['is_admin']==1&&isset($form_data['role_list'])){
            unset($form_data['role_list']);
        }
        if($form_data['admin_job']==""){
            unset($form_data['admin_job']);
        }
        if($form_data['admin_class']==""){
            unset($form_data['admin_class']);
        }
        try {
            validate(SystemAdminAddValidate::class)->batch(true)->check($form_data);
            if($form_data['is_admin']==2 && $form_data['role_list']=="") {
                throw new \think\Exception('角色必须进行填写');return;
            }elseif($form_data['is_admin']==1&&isset($form_data['role_list'])){
                throw new \think\Exception('超级管理员和角色只能选择一个进行填写');return;
            }
            $savename   =   Filesystem::disk('public')->putFile( 'admin_header_img', $form_data['file']);
            if(!$savename){
                throw new \think\Exception('头像上传失败！请重试！');return;
            }
            $form_data['admin_head_img']     =   Filesystem::getDiskConfig('public','url').'/'.str_replace('\\','/',$savename);
            unset($form_data['file'],$form_data['rpassword']);
            $form_data['create_time']   =   date('Y-m-d H:i:s',time());
            $form_data['update_time']   =   date('Y-m-d H:i:s',time());
            $form_data['password']      =   password_hash($form_data['password'],PASSWORD_DEFAULT);

        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            $this->resultError($e->getMessage());return;
        } catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }

        if($form_data['is_admin']==1){
            if(AdminUserModel::insert($form_data)){
                $this->resultSuccess('新管理员添加成功！');
            }else{
                unlink(".".$form_data['admin_head_img']);
            }
        }else{
            $role   =   explode(',',$form_data['role_list']);unset($form_data['role_list']);
            Db::startTrans();
            try {
                $admin_id   =   Db::connect('dataMysql')->table('rld_admin_user')->insertGetId($form_data);
                foreach ($role as $key=>$item){
                    $role_data[]  =   [
                        'admin_id'      =>  $admin_id,
                        'admin_account' =>  $form_data['admin_account'],
                        'role_id'       =>  $item,
                        'role_name'     =>  RolesModel::where('id',$item)->value('role_name'),
                        'create_time'   =>  date('Y-m-d H:i:s',time()),
                        'update_time'   =>  date('Y-m-d H:i:s',time()),
                        'create_by'     =>  $this->admin_account['id'],
                    ];
                }
                Db::connect('dataMysql')->table('rld_admin_roles')->insertAll($role_data);
                Db::commit();
            } catch (\Exception $e){
                Db::rollback();
                $this->resultError($e->getMessage());return;
            }
            $this->resultSuccess('管理员添加成功！');return;
        }
    }



    /**
     * 系统管理员删除
     * @param Request $request
     */
    public function system_admin_delete(Request $request)
    {
        $admin_id   =   $request->post('id','');
        if($admin_id==""){
            $this->resultSuccess('管理员ID为获取！');return;
        }
        if($admin_id==1){
            $this->resultError('不能删除超级管理员！');return;
        }
        try {
            $delete_res =   AdminUserModel::where('id',$admin_id)->delete();
            if($delete_res){
                $this->resultSuccess('删除成功！');return;
            }else{
                $this->resultError('删除失败！');return;
            }
        }catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }

    }

    /**
     * 系统管理员编辑
     * @param Request $request
     */
    public function system_admin_edit(Request $request)
    {
        $admin_id                   =   $request->get('id','');
        $admin_info                 =   AdminUserModel::where('id',$admin_id)->find();
        $admin_info['admin_role']   =   AdminRolesModel::where('admin_id',$admin_id)->select();
        $roles                      =   RolesModel::select();

        return view('',['admin_info'=>$admin_info,'roles'=>$roles]);
    }


    /**
     * 管理员状态修改
     */
    public function system_admin_status_edit(Request $request)
    {
        $admin_status   =   $request->post('admin_status','');
        $id             =   $request->post('id','');
        if($id==""){
            $this->resultError('管理员ID未获取');return;
        }
        if($admin_status==""){
            $this->resultError('管理员状态未获取');return;
        }
        try {
            AdminUserModel::where('id',$id)->update(['admin_status'=>$admin_status]);
        }catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }
        $this->resultSuccess('管理员状态修改成功！');return;
    }


    /**
     * 系统管理员编辑执行
     */
    public function system_admin_edit_do()
    {

    }




}