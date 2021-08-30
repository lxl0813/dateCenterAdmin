<?php


namespace app\controller;


use app\model\AdminRolesModel;
use app\model\AdminUserModel;
use app\model\CustomBankInfoModel;
use app\model\CustomContactModel;
use app\model\CustomModel;
use app\model\NodesModel;
use app\model\RolesNodesModel;
use app\model\SystemSettingsModel;
use app\Request;
use app\validate\CustomAddValidate;
use think\App;
use think\facade\Cookie;

/**
 * Class CustomController
 * @package app\controller
 */
class CustomController extends RbacController
{
    /**
     * 客户表model
     * @var $CustomModel
     */
    private $CustomModel;


    /**
     * 客户联系记录表model
     * @var $CustomContactModel
     */
    private $CustomContactModel;


    /**
     * Cookie
     * @var $Cookie
     */
    private $Cookie;


    /**
     * @var mixed
     */
    private $Page;

    /**
     * CustomController constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->CustomModel = new CustomModel();
        $this->CustomContactModel = new CustomContactModel();
        $this->Cookie = json_decode(Cookie::get('DATACENTER_ADMIN'), true);
        $this->Page = SystemSettingsModel::where('system_name', '分页设置')->value('system_value');
    }

    /**
     * 客户列表
     * @param Request $request
     */
    public function custom_list(Request $request)
    {
        $param = $request->param();
        $where = [];
        if ($param) {
            if (!empty($param['custom_name'])) {
                //客户名称添加了普通索引，右模糊匹配
                $where[] = array('custom_name', 'like', $param['custom_name'] . '%');
            }
            if (!empty($param['custom_action'])) {
                $where[] = array('custom_action', '=', $param['custom_action']);
            }
            if (!empty($param['status'])) {
                $where[] = array('status', '=', $param['status']);
            }
        }
        $custom_list = $this->CustomModel->where($where)->paginate($this->Page)->each(function ($k) {
            if (!empty($k['admin_id'])) {
                $k->admin_account = AdminUserModel::where('id', $k['admin_id'])->field('admin_account,department_name,station_name')->find();
            } else {
                $k->admin_account = [];
            }
        });
        return view('', ['custom_list' => $custom_list]);
    }

    /**
     * 客户添加
     * @param Request $request
     * @return \think\response\View
     */
    public function custom_add(Request $request)
    {
        if ($request->isGet()) {
            return view();
        }

        if ($request->isPost()) {
            $param = $request->param();
            validate(CustomAddValidate::class)->batch(true)->check($param);
            $param['contact_action'][] = ['contact_name' => $param['contact_name'], 'contact_phone' => $param['contact_phone']];
            $param['create_time'] = $param['update_time'] = time();
            $param['create_by'] = $this->Cookie['id'];
            unset($param['contact_name'], $param['contact_phone']);
            try {
                $this->CustomModel->save($param);
                $this->resultSuccess();
            } catch (\Exception $exception) {
                $this->resultError($exception->getMessage());
            }
        }
    }


    /**
     * 客户分配
     * @param Request $request
     */
    public function custom_distribution(Request $request)
    {
        if ($request->isGet()) {
            $id = $request->param();
            $nodes = NodesModel::where(['node_controller' => 'Custom', 'node_action' => null])->value('id');
            $rolesNodes = RolesNodesModel::where('nodes_id', $nodes)->value('roles_id');
            $admin = AdminRolesModel::where('role_id', $rolesNodes)->column('admin_id');
            $admin_list = AdminUserModel::where('id', 'in', $admin)->field('id,admin_account,station_name')->select();;
            $custom_info = CustomModel::where($id)->find();
            return view('', ['admin_list' => $admin_list, 'custom_info' => $custom_info]);
        }

        if ($request->isPost()) {
            $param = $request->param();
            try {
                $this->CustomModel->where('id', $param['id'])->save(['admin_id' => $param['admin_id']]);
                $this->resultSuccess();
            } catch (\Exception $exception) {
                $this->resultError($exception->getMessage());
            }
        }
    }


    /**
     * 客户联系列表
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DbException
     */
    public function custom_contact_note(Request $request)
    {
        if ($request->isGet()) {
            $param = $request->param();
            $custom_name = "";
            $custom_contact_list = $this->CustomContactModel->where($param)->paginate($this->Page)->each(function ($k) use (&$custom_name) {
                $k->admin_account = AdminUserModel::where('id', $k['admin_id'])->value('admin_account');
                if ($custom_name == "") {
                    $custom_name = $this->CustomModel->where('id', $k['custom_id'])->value('custom_name');
                }
                $k->custom_name = $custom_name;
                //状态获取器
                $k->contact_type = $k->contact_type_status;
            });
            return view('', ['custom_contact_list' => $custom_contact_list, 'custom_id' => $param['custom_id']]);
        }

        if ($request->isPost()) {

        }
    }


    /**
     * 客户联系记录添加
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function custom_contact_add(Request $request)
    {
        if ($request->isGet()) {
            $custom_id = $request->param('custom_id');
            //查询客户联系人
            $contact_action = $this->CustomModel->where('id', $custom_id)->find()->contact_action;
            return view('', ['custom_id' => $custom_id, 'contact_action' => $contact_action]);
        }

        if ($request->isPost()) {
            $param = $request->param();
            $admin_id = $this->CustomModel->where('id', $param['custom_id'])->value('admin_id');
            if ($admin_id == 0) {
                $this->resultError('该客户暂未分配，请分配后操作！');
                return;
            }
            $param['admin_id'] = CustomModel::where('id', $param['custom_id'])->value('admin_id');
            $param['create_time'] = date('Y-m-d H:i:s', time());
            try {
                $this->CustomContactModel->save($param);
                $this->resultSuccess();
            } catch (\Exception $exception) {
                $this->resultError($exception->getMessage());
            }
        }
    }


    /**
     * 客户银行信息列表
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function custom_bank_list(Request $request)
    {
        $param = $request->param();
        $custom_bank = (new CustomBankInfoModel())->where($param)->select()->each(function ($k){
            $k->custom_name = $this->CustomModel->where('id',$k['custom_id'])->value('custom_name');
        });
        return view('', ['custom_bank_list' => $custom_bank,'custom_id'=>$param['custom_id']]);
    }


    /**
     * 客户银行添加
     * @param Request $request
     * @return \think\response\View
     */
    public function custom_bank_add(Request $request)
    {
        if ($request->isGet()) {
            $param = $request->param();
            return view('',['custom_id'=>$param['custom_id']]);
        }

        if ($request->isPost()) {
            $param = $request->param();
            $param['create_by'] = $this->Cookie['id'];
            $param['create_time'] = time();
            $param['update_time'] = time();
            $result = (new CustomBankInfoModel())->insert($param);
            if ($result) {
                $this->resultSuccess();
            }else{
                $this->resultError();
            }
        }
    }


    /**
     * 客户银行信息删除
     * @param Request $request
     */
    public function custom_bank_delete(Request $request)
    {
        $param = $request->param();
        try{
            (new CustomBankInfoModel())->where($param)->delete();
            $this->resultSuccess();
        }catch(\Exception $exception){
            $this->resultError($exception->getMessage());
        }
    }

    /**
     * 客户查找银行信息
     * @param Request $request
     */
    public function custom_to_bank(Request $request)
    {
        $param = $request->param();
        $custom_bank = (new CustomBankInfoModel())->where($param)->select()->toArray();
        $this->resultSuccess('成功！',['custom_bank'=>$custom_bank]);
    }


}