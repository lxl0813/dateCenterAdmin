<?php


namespace app\controller;


use app\model\AdminUserModel;
use app\model\ArticleAssetsModel;
use app\model\CustomBankInfoModel;
use app\model\CustomModel;
use app\model\DepartmentModel;
use app\model\FinanceCompanyBankModel;
use app\model\FinanceWaterModel;
use app\model\FixedAssetsModel;
use app\model\FixedAssetsUseModel;
use app\model\ProjectModel;
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
     * @var $articleAssetsModel
     */
    private $articleAssetsModel;


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

        $this->articleAssetsModel = new ArticleAssetsModel();

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
            if ($param['status'] == 1) {
                $status['status'] = 2;
                $status['use_id'] = $param['use_id'];
                $status['use_name'] = (new AdminUserModel())->where('id', $param['use_id'])->value('admin_name');
            } else {
                $status['status'] = 1;
                $status['use_id'] = null;
                $status['use_name'] = null;
            }
            //$param['status'] == 1 ? $status['status'] = 2 : $status['status'] = 1;
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
            if ($fixed_assets_name) {
                $k->fixed_assets_name = $fixed_assets_name;
            } else {
                $k->fixed_assets_name = $this->fixedAssetsModel->where('id', $param['fixed_assets_id'])->value('fixed_assets_name');
            }
            $k->use_name = $this->adminUser->where('id', $k['use_id'])->value('admin_name');
        });
        return view('', ['fixed_assets_info' => $fixed_assets_info]);
    }



    /* 物品资产管理 */

    /**
     * @param Request $request
     */
    public function article_assets_list(Request $request)
    {
        if ($request->isGet()) {
            $where = [];
            $article_assets_list = $this->articleAssetsModel->where($where)->paginate($this->Page);
            //查询项目

            return view('', ['article_assets_list' => $article_assets_list]);
        }
        if ($request->isPost()) {

        }

    }


    /**
     * @param Request $request
     * @return \think\response\View|void
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function article_assets_add(Request $request)
    {
        if ($request->isGet()) {
            $project_list = (new ProjectModel())->where('status', 1)->select()->toArray();
            //查询部门
            $department_list = (new DepartmentModel())->where('level', '<>', 1)->where('status', 1)->select()->toArray();
            //查询公司
            $custom_lidt = (new CustomModel())->select()->toArray();
            //人员查询
            $admin_list = (new AdminUserModel())->where('admin_status', 1)->select()->toArray();
            $bank = (new FinanceCompanyBankModel())->select()->toArray();
            return view('', ['project_list' => $project_list, 'department_list' => $department_list, 'custom_list' => $custom_lidt, 'admin_list' => $admin_list, 'bank' => $bank]);
        }

        if ($request->isPost()) {
            $param = $request->param();
            try {
                validate(AssetsAddValidate::class)->scene('article_assets_add')->batch(true)->check($param);
            } catch (\Exception $exception) {
                $this->resultError($exception->getMessage());
            }
            $article = $this->articleAssetsModel->where(
                [
                    'article_assets_name' => $param['article_assets_name'],
                    'article_assets_model' => $param['article_assets_model']
                ])->find();
            if ($article) {
                $this->resultSuccess('该物品已存在，请找到该物品后，针对该物品进行入库操作！');
                return;
            }
            $param['pur_unit_price'] = $param['pur_unit_price'] * 100;
            $param['sales_unit_price'] = $param['sales_unit_price'] * 100;
            $article_assets_info['article_assets_num'] = $param['stock'];
            $article_assets_info['handler_id'] = $param['handler_id'];
            unset($param['handler_id']);
            $article_assets_info['create_by'] = $param['create_by'] = $this->Cookie['id'];
            $article_assets_info['create_time'] = time();
            $article_assets_info['add_time'] = $param['add_time'];
            unset($param['add_time']);
            $article_assets_info['company_id'] = $param['custom_id'] ?: null;
            $article_assets_info['department_id'] = $param['department_id'] ?: null;
            unset($param['department_id']);
            $article_assets_info['project_id'] = $param['project_id'] ?: null;
            unset($param['project_id']);
            $article_assets_info['remark'] = $param['remark'] ?: null;
            unset($param['remark']);


            //是否添加账单判断处理
            if ($param['pay'] == 1) {
                try {
                    validate(AssetsAddValidate::class)->scene('article_assets_water')->batch(true)->check($param);
                } catch (\Exception $exception) {
                    $this->resultError($exception->getMessage());
                }
                $water['water_info'] = $param['article_assets_name'] . $param['article_assets_model'] . "购买" . $param['stock'] . $param['unit'];
                $water['water_pay'] = $param['pur_unit_price'] * $param['stock'] / 100;
                //查询该银行账户流水表余额
                $balance = (new FinanceWaterModel())->where('bank_id', $param['bank_id'])->order('create_time desc')->limit(1)->value('account_balance');
                //如果没有，查询银行账户余额
                if (!$balance) {
                    $water['account_balance'] = (((new FinanceCompanyBankModel())->where('bank_id', $param['bank_id'])->value('account_balance') * 100) - ($water['water_pay'] * 100)) / 100;
                } else {
                    $water['account_balance'] = ($balance * 100 - $water['water_pay'] * 100) / 100;
                }
                $water['other_account'] = (new CustomModel())->where('id', $param['custom_id'])->value('custom_name');
                $water['other_open_ac_mec'] = (new CustomBankInfoModel())->where('id', $param['custom_bank_id'])->value('custom_open_ac_mec');
                $water['status'] = 1;
                $water['create_time'] = date("Y-m-d", $article_assets_info['create_time']);
                $water['update_time'] = $water['create_time'];
                $water['create_by'] = $this->Cookie['id'];
                $water['subject_id'] = $param['subject_id'];
                $water['create_admin'] = $this->Cookie['admin_name'];
                $water['add_time'] = date("Y-m-d H:i:s", time());
                $water['subject_name'] = $param['subject_name'];
                $water['bank_id'] = $param['bank_id'];
                $water['bank_name'] = (new FinanceCompanyBankModel())->where('bank_id', $water['bank_id'])->value('bank_name');
                $water['project_id'] = $article_assets_info['project_id'];
                $water['department_id'] = $article_assets_info['department_id'];
                $water['handler_id'] = $article_assets_info['handler_id'];
            }
            unset($param['pay']);
            unset($param['custom_bank_id']);
            unset($param['custom_id']);
            unset($param['subject_id']);
            unset($param['subject_name']);
            unset($param['bank_id']);
            //附件上传
            $savename = Filesystem::disk('public')->putFile('assets_file', $request->file('file'));
            if (!$savename) {
                throw new \think\Exception('图片上传失败！请重试！');
                return;
            }
            $article_assets_info['annex'] = Filesystem::getDiskConfig('public', 'url') . '/' . str_replace('\\', '/', $savename);

            //dump($param);dump($article_assets_info);exit;

            Db::startTrans();
            try {
                $article_assets_info['article_assets_id'] = Db::connect('dataMysql')->name('article_assets')->insertGetId($param);
                Db::connect('dataMysql')->name('article_assets_inout')->insert($article_assets_info);
                // 提交事务
                Db::commit();
            } catch (\Exception $exception) {
                // 回滚事务
                Db::rollback();
                unlink("." . $article_assets_info['annex']);
                $this->resultError($exception->getMessage());
                return;
            }

            if (isset($water)) {
                Db::startTrans();
                try {
                    Db::connect("financeMysql")->table('rl_finance_water')->insert($water);
                    Db::connect("financeMysql")->table('rl_finance_company_bank')->where('bank_id', $water['bank_id'])->update(['account_balance' => $water['account_balance']]);
                    // 提交事务
                    Db::commit();
                    $this->resultSuccess();
                    return;
                } catch (\Exception $exception) {
                    // 回滚事务
                    Db::rollback();
                    $this->resultError($exception->getMessage());
                    return;
                }
            }
            $this->resultSuccess();
        }
    }


    /**
     * @param Request $request
     */
    public function article_assets_use(Request $request)
    {
        if ($request->isGet()) {
            $param = $request->param();
            $article_assets = $this->articleAssetsModel->where($param)->find();
            $project_list = (new ProjectModel())->where('status', 1)->select()->toArray();
            //查询部门
            $department_list = (new DepartmentModel())->where('level', '<>', 1)->where('status', 1)->select()->toArray();
            //查询公司
            $custom_lidt = (new CustomModel())->select()->toArray();
            //人员查询
            $admin_list = (new AdminUserModel())->where('admin_status', 1)->select()->toArray();
            $bank = (new FinanceCompanyBankModel())->select()->toArray();
            return view('', ['bank'=>$bank , 'article_assets' => $article_assets, 'project_list' => $project_list, 'department_list' => $department_list, 'custom_list' => $custom_lidt, 'admin_list' => $admin_list]);
        }

        if ($request->isPost()) {
            $param = $request->param();
//            try {
//                validate(AssetsAddValidate::class)->scene('article_assets_use')->batch(true)->check($param);
//            } catch (\Exception $exception) {
//                $this->resultError($exception->getMessage());
//            }
            $article = $this->articleAssetsModel->where(
                [
                    'article_assets_name' => $param['article_assets_name'],
                    'article_assets_model' => $param['article_assets_model'],
                ])->field('stock,id')->find();

            if ($param['article_assets_num'] > $article['stock']) {
               return $this->resultError('物品库存不足！请减少领用量');
            }
            $param['pur_unit_price'] = $param['pur_unit_price'] * 100;
            $param['sales_unit_price'] = $param['sales_unit_price'] * 100;
            $article_assets_info['article_assets_num'] = $param['article_assets_num'];
            $article_assets_info['handler_id'] = $param['handler_id'];
            unset($param['handler_id']);
            $article_assets_info['create_by'] = $param['create_by'] = $this->Cookie['id'];
            $article_assets_info['create_time'] = time();
            $article_assets_info['add_time'] = $param['add_time'];
            unset($param['add_time']);
            $article_assets_info['company_id'] = $param['custom_id'] ?: null;
            $article_assets_info['department_id'] = $param['department_id'] ?: null;
            unset($param['department_id']);
            $article_assets_info['project_id'] = $param['project_id'] ?: null;
            unset($param['project_id']);
            $article_assets_info['remark'] = $param['remark'] ?: null;
            unset($param['remark']);
            $article_assets_info['status'] = 2;
            $article_assets_info['article_assets_id'] = $article['id'];

            //dump($article_assets_info);exit;
            if ($param['pay'] == 1) {
//                try {
//                    validate(AssetsAddValidate::class)->scene('article_assets_water')->batch(true)->check($param);
//                } catch (\Exception $exception) {
//                    $this->resultError($exception->getMessage());
//                }
                $water['water_info'] = $param['article_assets_name'] . $param['article_assets_model'] . "销售" . $param['article_assets_num'] . $param['unit'];
                $water['water_income'] = $param['sales_unit_price'] * $param['article_assets_num'] / 100;
                //查询该银行账户流水表余额
                $balance = (new FinanceWaterModel())->where('bank_id', $param['bank_id'])->order('create_time desc')->limit(1)->value('account_balance');
                //如果没有，查询银行账户余额
                if (!$balance) {
                    $water['account_balance'] = (((new FinanceCompanyBankModel())->where('bank_id', $param['bank_id'])->value('account_balance') * 100) + ($water['water_income'] * 100)) / 100;
                } else {
                    $water['account_balance'] = ($balance * 100 + $water['water_income'] * 100) / 100;
                }
                $water['other_account'] = (new CustomModel())->where('id', $param['custom_id'])->value('custom_name');
                $water['other_open_ac_mec'] = (new CustomBankInfoModel())->where('id', $param['custom_bank_id'])->value('custom_open_ac_mec');
                $water['status'] = 1;
                $water['create_time'] = date("Y-m-d", $article_assets_info['create_time']);
                $water['update_time'] = $water['create_time'];
                $water['create_by'] = $this->Cookie['id'];
                $water['subject_id'] = $param['subject_id'];
                $water['create_admin'] = $this->Cookie['admin_name'];
                $water['add_time'] = date("Y-m-d H:i:s", time());
                $water['subject_name'] = $param['subject_name'];
                $water['bank_id'] = $param['bank_id'];
                $water['bank_name'] = (new FinanceCompanyBankModel())->where('bank_id', $water['bank_id'])->value('bank_name');
                $water['project_id'] = $article_assets_info['project_id'];
                $water['department_id'] = $article_assets_info['department_id'];
                $water['handler_id'] = $article_assets_info['handler_id'];
            }
            unset($param['pay']);
            unset($param['custom_bank_id']);
            unset($param['custom_id']);
            unset($param['subject_id']);
            unset($param['subject_name']);
            unset($param['bank_id']);

            //dump($param);dump($article_assets_info);dump($water);exit;
            //附件上传
            $savename = Filesystem::disk('public')->putFile('assets_file', $request->file('file'));
            if (!$savename) {
                throw new \think\Exception('图片上传失败！请重试！');
            }
            $article_assets_info['annex'] = Filesystem::getDiskConfig('public', 'url') . '/' . str_replace('\\', '/', $savename);
            Db::startTrans();
            try {
                Db::connect('dataMysql')->name('article_assets_inout')->insert($article_assets_info);
                Db::connect('dataMysql')->name('article_assets')->where([
                    'article_assets_name' => $param['article_assets_name'],
                    'article_assets_model' => $param['article_assets_model'],
                ])->update(['stock' => $article['stock']-$param['article_assets_num']]);
                // 提交事务
                Db::commit();
            } catch (\Exception $exception) {
                // 回滚事务
                Db::rollback();
                unlink("." . $article_assets_info['annex']);
                return $this->resultError($exception->getMessage());
            }

            if (isset($water)) {
                Db::startTrans();
                try {
                    Db::connect("financeMysql")->table('rl_finance_water')->insert($water);
                    Db::connect("financeMysql")->table('rl_finance_company_bank')->where('bank_id', $water['bank_id'])->update(['account_balance' => $water['account_balance']]);
                    // 提交事务
                    Db::commit();
                    return $this->resultSuccess();
                } catch (\Exception $exception) {
                    // 回滚事务
                    Db::rollback();
                    return $this->resultError($exception->getMessage());
                }
            }
            return $this->resultSuccess();
        }
    }


}