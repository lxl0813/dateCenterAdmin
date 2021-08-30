<?php


namespace app\controller;

use app\model\AdminUserModel;
use app\ResultTrait;
use app\validate\DataCenterLoginInValidate;
use app\validate\PersonalInformaionEditValidate;
use app\validate\SystemAdminAddValidate;
use think\exception\ValidateException;
use think\facade\Cookie;
use think\facade\Filesystem;
use think\facade\Log;
use think\Request;

class AuthController
{
    //使用返回trait类；
    use ResultTrait;

    //数据中心登录页面
    public function data_center_login(Request $request){
        return view();
    }

    //数据中心切换账号页面
    public function data_center_relogin(Request $request){
        return view();
    }

    public function data_center_login_in(Request $request){
         //获取Token令牌
         $check = $request->checkToken('__token__');
         //令牌验证
         if(false === $check) {
             return $this->resultError('Token令牌失效！请刷新页面再次尝试！');
         }
        $data= json_decode($request->post('form_data'),true);

        try {
            //表单内容验证
            validate(DataCenterLoginInValidate::class)->batch(true)->check($data);
            //模型管理员账号搜索器
            $admin_account  =   AdminUserModel::withSearch(['admin_account'],['admin_account'=>$data['admin_account']])->find();
            //验证账号是否存在以及账号状态
            if(!$admin_account){
                return $this->resultError('该账户不存在！');
            }else{
                if($admin_account['admin_status']===2){
                    return $this->resultError('该账号已被冻结！');
                }else{
                    //核验密码
                    if(password_verify($data['password'],$admin_account['password'])){
                        //登录，存cookie。
                        $auth   =   [
                            'id'            =>  $admin_account['id'],
                            'admin_account' =>  $admin_account['admin_account'],
                            'admin_name'    =>  $admin_account['admin_name'],
                            'admin_head_img'=>  $admin_account['admin_head_img'],
                        ];
                        Cookie::set("DATACENTER_ADMIN",json_encode($auth));
                        //修改上次登录时间
                        AdminUserModel::where('id',$admin_account['id'])->update(['last_login_time'=>time()]);
                        return $this->resultSuccess('登录成功！');
                    }else{
                        return $this->resultError('密码错误！');
                    }
                }
            }
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            return $this->resultError($e->getError());
        }
    }

    //退出
    public function system_admin_login_out(){
        Cookie::delete("DATACENTER_ADMIN");
        return redirect('/Auth/data_center_login');
    }

    /**
     * 管理员个人信息
     */
    public function personal_information()
    {
        $admin_id   =   json_decode(Cookie::get('DATACENTER_ADMIN'),true)['id'];
        $personal_information   =   AdminUserModel::find($admin_id)->toArray();
        return view('',['personal_information'=>$personal_information]);
    }

    /**
     * 管理员个人信息修改执行
     */
    public function personal_information_edit(Request $request)
    {
        $admin_info =   $request->post();
        //表单验证
        try {
            validate(PersonalInformaionEditValidate::class)->batch(true)->check($admin_info);
            $map['id']  =   $admin_info['admin_id'];
            unset($admin_info['admin_id']);
            $update_res =   AdminUserModel::where($map)->update($admin_info);
            if($update_res){
                $this->resultSuccess('个人信息修改成功！');return;
            }else{
                $this->resultError('个人信息修改失败！');return;
            }
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            $this->resultError($e->getMessage());return;
        } catch (\Exception $e) {
            $this->resultError($e->getMessage());return;
        }
    }

    /**
     * 管理员头像更换
     */
    public function admin_head_img_edit(Request $request)
    {
        $admin_id       =   $request->post('admin_id','');
        $admin_head_img =   $request->file('file');
        try {
            //文件验证
            validate(['file' => 'fileSize:1024000|fileExt:png,jpg,jpeg,pdf'])->check(['file'=>$admin_head_img]);
            $savename   =   Filesystem::disk('public')->putFile( 'admin_header_img', $admin_head_img);
            if(!$savename){
                $this->resultError('文件导入失败！请重试！');return;
            }
        } catch (ValidateException $e) {
            $this->resultError($e->getMessage());return;
        }
        $path     =   Filesystem::getDiskConfig('public','url').'/'.str_replace('\\','/',$savename);
        try {
            //删除原头像文件
            $head_img_url   =   AdminUserModel::find($admin_id)->toArray()['admin_head_img']?AdminUserModel::find($admin_id)->toArray()['admin_head_img']:false;
            //有旧头像
            if($head_img_url){
                @unlink(".".$head_img_url);
                if(AdminUserModel::where('id',$admin_id)->update(['admin_head_img'=>$path])){
                    $this->resultSuccess('头像修改成功！');return;
                }else{
                    $this->resultError('头像修改失败！');return;
                }
            }else{
                if(AdminUserModel::where('id',$admin_id)->update(['admin_head_img'=>$path])){
                    $this->resultSuccess('头像修改成功！');return;
                }else{
                    $this->resultError('头像修改失败！');return;
                }
            }
        }catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }
    }

}