<?php


namespace app\controller;

use app\BaseController;
use app\model\AdminRolesModel;
use app\model\AdminUserModel;
use app\model\NodesModel;
use app\model\RolesNodesModel;
use app\ResultTrait;
use app\service\RecursionService;
use think\App;
use think\facade\Cookie;

class RbacController extends BaseController
{
    use ResultTrait;

    protected $menu_list;

    public function __construct(App $app)
    {
        parent::__construct($app);
        //管理员权限识别
        if (!$this->checkNode()) {
            if (request()->isAjax()) {
                return $this->resultError('对不起！您没有操作权限！');
            } else {
                return \redirect('/Rbac/rbac_error')->send();
            }
        }
        $this->menu_list = $this->getMenu();
    }


    public function rbac_error()
    {
        return view();
    }

    /**
     * @return bool
     * User：李小龙
     *Date：2020/4/26
     * Time：14:07
     */

    //查询改管理员是否拥有所要前往页面的权限；
    public function checkNode()
    {
        //判断当前用户是否超级管理员 是否是配置文件里的超级管理员，false继续查询权限，true不需要
        $admin_account = json_decode(Cookie::get("DATACENTER_ADMIN"), true);
        $is_admin = AdminUserModel::where('is_admin', $admin_account['id'])->value('is_admin');
        if ($is_admin == 1) {
            return true;
        }
        //获取要去往的控制器和方法(转换成开头大写的格式)，判断该控制器是否是不需要权限的
        $access = ucfirst(strtolower(request()->controller())) . "/" . ucfirst(strtolower(request()->action()));
        //判断所要前往的路由是否在配置文件中；
        $config_url = config("app.no_check_action");
        if (in_array($access, $config_url)) {
            return true;
        };
        //获取当前登录管理员的权限
        $myNode = $this->getAdminNodeId($admin_account["id"]);
        if (in_array($access, $myNode)) {
            return true;
        } else {
            return false;
        }
    }

    //获取管理员权限
    public function getAdminNodeId($admin_id)
    {
        $adminRoleModel = new AdminRolesModel();
        $role_id = $adminRoleModel->where("admin_id", $admin_id)->column("role_id");
        $roleNodeModel = new RolesNodesModel();
        $nodeModel = new NodesModel();
        $role = $roleNodeModel->where('roles_id', 'in', $role_id)->select()->toArray();
        $myNode = [];
        foreach ($role as $key => $val) {
            $myNode[] = $nodeModel->where('id', $val['nodes_id'])->find()->toArray();
        }
        $myAccess = [];
        foreach ($myNode as $key => $val) {
            array_push($myAccess, ucfirst(strtolower($val["node_controller"])) . "/" . ucfirst(strtolower($val["node_action"])));
        }
        $myAccess = array_unique($myAccess);
        return $myAccess;
    }

    //取左侧菜单
    public function getMenu()
    {
        $admin_account = json_decode(Cookie::get("DATACENTER_ADMIN"), true);
        $is_admin = AdminUserModel::where('is_admin', 1)->column('id');
        if (in_array($admin_account["id"], $is_admin)) {
            //取所有需要在左侧导航栏展示的权限
            $menu = (new NodesModel())->where("is_nav", 1)->select()->toArray();
            foreach ($menu as $k => $v) {
                $menu[$k]['url'] = $v['node_controller'] . "/" . $v['node_action'];
            }
        } else {
            //查询管理员的所拥有角色
            $role_id = (new AdminRolesModel())->where('admin_id', $admin_account["id"])->column('role_id');
            $node_id = (new RolesNodesModel())->where('roles_id', 'in', $role_id)->column('nodes_id');
            $menu = (new NodesModel())->where('id', 'in', $node_id)->where('is_nav', '=', 1)->where('status', '=', 1)->field('node_controller,node_action,node_name,id,parents_id,icon_code')->select()->toArray();
            foreach ($menu as $k => $v) {
                $menu[$k]['url'] = $v['node_controller'] . "/" . $v['node_action'];
            }
        }
        //对菜单进行递归处理
        $recursion = new RecursionService();
        $menu_list = $recursion->nodes_recursion_son($menu);
        return $menu_list;
    }
}