<?php


namespace app\controller;


use app\model\AdminUserModel;
use app\model\FixedAssetsModel;
use app\model\FixedAssetsUseModel;
use app\model\SystemSettingsModel;
use app\Request;
use app\validate\AssetsAddValidate;
use think\App;
use think\facade\Cookie;
use think\facade\Db;
use think\facade\Filesystem;

class AssetsController extends RbacController
{
    /**
     * @var $AssetsModel
     */
    private $fixedAssetsModel;


    /**
     * @var $fixedAssetsUseModel
     */
    private $fixedAssetsUseModel;


    /**
     * @var AdminUserModel
     */
    private $adminUser;


    /**
     * @var $Cookie
     */
    private $Cookie;

    /**
     * @var mixed
     */
    private $Page;


    /**
     * AssetsController constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);

        //AssetsModel模型
        $this->fixedAssetsModel = new FixedAssetsModel();

        $this->fixedAssetsUseModel = new FixedAssetsUseModel();

        $this->adminUser = new AdminUserModel();

        //Cookie
        $this->Cookie = json_decode(Cookie::get('DATACENTER_ADMIN'), true);

        //Page
        $this->Page = SystemSettingsModel::where('system_name', '分页设置')->value('system_value');
    }


    /**
     * 固定资产列表
     * @param Request $request
     */
    public function fixed_assets_list(Request $request)
    {
        $param = $request->param();
        $where = [];
        if ($param) {

        }
        $fixed_assets_list = $this->fixedAssetsModel->where($where)->paginate($this->Page)->each(function ($k) {
            $k->purchase_money = $k['purchase_money'] / 100;
            $k->purchase_time = date('Y-m-d', $k['purchase_time']);
        });
        return view('', ['fixed_assets_list' => $fixed_assets_list]);
    }

    /**
     * 固定资产添加
     * @param Request $request
     * @return \think\response\View|void
     */
    public function fixed_assets_add(Request $request)
    {
        if ($request->isGet()) {
            return view();
        }

        if ($request->isPost()) {
            $param = $request->param();
            $file = $request->file('file');
            $param['create_time'] = time();
            $param['update_time'] = time();
            $param['create_by'] = $this->Cookie['id'];
            $param['purchase_money'] = $param['purchase_money'] * 100;
            try {
                //表单验证
                validate(AssetsAddValidate::class)->batch(true)->check($param);
                //文件上传
                $savename = Filesystem::disk('public')->putFile('assets_file', $file);
                if (!$savename) {
                    throw new \think\Exception('图片上传失败！请重试！');
                    return;
                }
                $param['annex'] = Filesystem::getDiskConfig('public', 'url') . '/' . str_replace('\\', '/', $savename);
                $param['assets_code'] = "GD-" . str_replace("-", "", $param['purchase_time']) . rand(10000, 99999);
                $param['purchase_time'] = strtotime($param['purchase_time']);
                //入库
                $result = $this->fixedAssetsModel->save($param);
                if ($result) {
                    $this->resultSuccess();
                } else {
                    unlink("." . $param['annex']);
                }
            } catch (\Exception $exception) {
                $this->resultError($exception->getMessage());
            }
        }
    }


    /**
     * 固定资产领用
     * @param Request $request
     * @return \think\response\View
     */
    public function fixed_assets_use(Request $request)
    {
        if ($request->isGet()) {
            $param = $request->param();
            //查询人员
            $admin_list = (new AdminUserModel())->where('admin_status', 1)->field('id,admin_name')->select()->toArray();
            return view('', ['param' => $param, 'admin_list' => $admin_list]);
        }

        if ($request->isPost()) {
            $param = $request->param();
            try {
                validate(AssetsAddValidate::class)->scene('fixed_assets_use')->batch(true)->check($param);
            } catch (\Exception $exception) {
                $this->resultError($exception->getMessage());
            }
            if ($param['status']==1) {
                $status['status'] = 2;
                $status['use_id'] = $param['use_id'];
                $status['use_name'] = (new AdminUserModel())->where('id', $param['use_id'])->value('admin_name');
            }else{
                $status['status'] = 1 ;
                $status['use_id'] = null;
                $status['use_name'] = null;
            }
            $param['status'] == 1 ? $status['status'] = 2 : $status['status'] = 1;
            $param['create_by'] = $this->Cookie['id'];
            $param['create_time'] = time();
            //开启事务
            Db::startTrans();
            try {
                Db::connect('dataMysql')->name('fixed_assets_use')->insert($param);
                Db::connect('dataMysql')->name('fixed_assets')->where('id', $param['fixed_assets_id'])->update($status);
                // 提交事务
                Db::commit();
                $this->resultSuccess();
            } catch (\Exception $exception) {
                // 回滚事务
                Db::rollback();
                $this->resultError($exception->getMessage());
            }
        }
    }


    /**
     * 固定资产使用详情
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DbException
     */
    public function fixed_assets_use_info(Request $request)
    {
        $param = $request->param();
        $fixed_assets_name = "";
        $fixed_assets_info = $this->fixedAssetsUseModel->where($param)->order('create_time desc')->paginate($this->Page)->each(function ($k) use (&$fixed_assets_name, $param) {
            if ($fixed_assets_name){
                $k->fixed_assets_name = $fixed_assets_name;
            }else{
                $k->fixed_assets_name = $this->fixedAssetsModel->where('id', $param['fixed_assets_id'])->value('fixed_assets_name');
            }
            $k->use_name = $this->adminUser->where('id',$k['use_id'])->value('admin_name');
        });
        return view('', ['fixed_assets_info' => $fixed_assets_info]);
    }

}