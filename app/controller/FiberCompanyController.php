<?php


namespace app\controller;


use app\model\CommonCityModel;
use app\model\CompanyRegistNumsModel;
use app\model\CompanyTypeModel;
use app\model\FiberCompanyDetailTitleModel;
use app\model\FiberIndustryCompanyModel;
use app\model\FiberCompanyProductionTypeModel;
use app\model\SystemSettingsModel;
use app\Request;
use app\service\ExcelService;
use app\service\PdfService;
use app\service\RandomStringService;
use app\service\RecursionService;
use think\Db;
use think\exception\ValidateException;
use think\facade\Cookie;
use think\facade\Filesystem;
use think\facade\View;

class FiberCompanyController extends RbacController
{
    /**化纤企业列表
     * @return \think\response\View
     *
     */
    public function fiber_company_list(Request $request)
    {
        //获取查询条件，如果全部为空，则利用默认条件进行查询分页
        $company_name = $request->get("company_name", "");
        $credit_code = $request->get("credit_code", "");
        $production_type_id = $request->get("production_type_id", "");
        $regist_nums = $request->get("regist_nums", "");
        $city = $request->get("city", "");
        $legal_person = $request->get("legal_person", "");
        //查询条件
        $queryWhere = [];
        if ($company_name) {
            $queryWhere['company_name'] = array('eq', $company_name);
            View::assign('company_name', $company_name);
        }
        if ($credit_code) {
            $queryWhere['credit_code'] = array('eq', $credit_code);
            View::assign('credit_code', $credit_code);
        }
        if ($production_type_id) {
            $queryWhere['production_type_id'] = array('eq', $production_type_id);
            View::assign('production_type_id', $production_type_id);
        }
        if ($credit_code) {
            $queryWhere['legal_person'] = array('eq', $legal_person);
            View::assign('legal_person', $credit_code);
        }
        if ($regist_nums) {
            $regist_nums_value = CompanyRegistNumsModel::find($regist_nums)->toArray();
            $queryWhere[] = ['register_capi', 'between', [$regist_nums_value['start_num'], $regist_nums_value['end_num']]];
            View::assign('regist_nums', $regist_nums);
        }
        if ($city) {
            $city_data = CommonCityModel::find($city);
            if ($city_data['AreaLevel'] === 1) {
                $queryWhere['province'] = array('eq', $city_data['AreaName']);
            } elseif ($city_data['AreaLevel'] === 2) {
                $queryWhere['city'] = array('eq', $city_data['AreaName']);
            } else {
                $queryWhere['area'] = array('eq', $city_data['AreaName']);
            }
            View::assign('city', $city);
        }
        //进行分页查询\获取分页配置
        $page = SystemSettingsModel::where('system_name', '分页设置')->value('system_value');
        $fiber_company = FiberIndustryCompanyModel::where($queryWhere)->paginate(['list_rows' => $page, 'query' => request()->param()]);
        //获取企业生产类型
        $product_type = CompanyTypeModel::where(['parent_id'=>2])->select()->toArray();
        //读取注册资金范围
        $regist_nums = CompanyRegistNumsModel::order('order')->select()->toArray();
        //读取配置
        $citySystem = SystemSettingsModel::where('system_name', '省市联动级别')->value('system_value');
        //获取省市联动
        $cityModel = new CommonCityModel();
        $city = $cityModel->where('area_level', '<=', $citySystem)->select()->toArray();
        $tree = new RecursionService();
        $cityTree = $tree->getCateOrder($city);
        View::assign('product_type', $product_type);
        View::assign('city_tree', $cityTree);
        View::assign('regist_nums', $regist_nums);
        View::assign('fiber_company', $fiber_company);
        return View::fetch();
    }


    /**化纤企业excel上传
     * @param
     */
    public function fiber_company_import(Request $request)
    {
        //获取企业生产类型
        $product_type = FiberCompanyProductionTypeModel::withSearch(['fiber_company_production_type'], ['status' => 1])->select()->toArray();
        return view('', ['product_type' => $product_type]);
    }

    public function fiber_company_import_do(Request $request)
    {
        echo 123;
        exit;
        $keywords = $request->post('product_type');
        $product_type_id = $request->post('product_type_id');
        $files = request()->file('file');
        try {
            //文件验证
            validate(['file' => 'fileSize:20480000|fileExt:xlsx,xls'])->check(['file' => $files]);
            $savename = Filesystem::disk('public')->putFile('shares_base_import', $files);
            if (!$savename) {
                $this->resultError('文件导入失败！请重试！');
                return;
            }
        } catch (ValidateException $e) {
            $this->resultError($e->getMessage());
            return;
        }
        //开始读取excel文件内容
        $excel_path = '.' . Filesystem::getDiskConfig('public', 'url') . '/' . str_replace('\\', '/', $savename);
        $excel = new ExcelService();
        $excel_to_mysql = $excel->shares_excel_read_to_mysql($excel_path, $create_time);
        if (true === $excel_to_mysql) {
            $this->resultSuccess('文件导入成功！');
            return;
        } else {
            $this->resultError($excel_to_mysql);
            return;
        }
    }


    //化纤企业详情设置
    public function fiber_company_detail_set_list()
    {
        $detail = FiberCompanyDetailTitleModel::select();
        return view('', ['detail' => $detail]);
    }

    //企业详情标题添加
    public function company_title_add_view()
    {
        return \view();
    }

    //企业详情标题添加
    public function company_title_add_do(Request $request)
    {
        $title = $request->post('title', '');
        $input_action = $request->post('input_action', '');
        if ($title == "") {
            $this->resultError('企业详情标题不存在！');
        }
        if ($input_action == "") {
            $this->resultError('请选择录入方式！');
        }
        $value = [
            'company_detail_name' => $title,
            'company_detail_input_action' => $input_action,
            'create_time' => date('Y-m-d H:i:s', time()),
            'update_time' => date('Y-m-d H:i:s', time()),
            'create_by' => json_decode(Cookie::get('DATACENTER_ADMIN'), true)['id'],
        ];
        try {
            FiberCompanyDetailTitleModel::insert($value);
        } catch (\Exception $e) {
            return $this->resultError($e->getMessage());
            return;
        }
        return $this->resultSuccess();
    }


    /**
     * 化纤企业添加
     */
    public function fiber_company_add()
    {
        $company_type = CompanyTypeModel::where('parent_id',2)->select()->toArray();
        return \view('', ['com_pro_type' => $company_type]);
    }


    /**
     *化纤企业添加执行
     */
    public function fiber_company_add_do(Request $request)
    {
        $company_info = $request->post();
        try {
            FiberIndustryCompanyModel::insert($company_info);
            $this->resultSuccess('添加成功！');
        } catch (\ErrorException $e) {
            $this->resultError($e->getMessage());
        }
    }


    /**
     * 化纤企业编辑页面
     */
    public function fiber_company_edit(Request $request)
    {
        //缺少企业信息修改页面
        $company_id = $request->get('id', '');
        //取企业生产分类
        $company_type = CompanyTypeModel::where('parent_id',2)->select()->toArray();
        $company_info = FiberIndustryCompanyModel::find($company_id)->toArray();
        return \view('', ['company_info' => $company_info, 'com_pro_type' => $company_type]);
    }


    /**
     * 化纤企业编辑执行
     */
    public function fiber_company_edit_do(Request $request)
    {
        $company_info = $request->post();
        try {
            FiberIndustryCompanyModel::insert($company_info);
            $this->resultSuccess('添加成功！');
        } catch (\ErrorException $e) {
            $this->resultError($e->getMessage());
        }
    }


    /**
     * 企业导出
     */
    public function company_output(Request $request)
    {
        $data = json_decode($request->post('data'), true);
        //查询条件
        $queryWhere = [];
        if (!empty($data['company_name'])) {
            $queryWhere['company_name'] = array('eq', $data['company_name']);
        }
        if (!empty($data['credit_code'])) {
            $queryWhere['credit_code'] = array('eq', $data['credit_code']);
        }
        if (!empty($data['production_type_id'])) {
            $queryWhere['production_type_id'] = array('eq', $data['production_type_id']);
        }
        if (!empty($data['legal_person'])) {
            $queryWhere['legal_person'] = array('eq', $data['legal_person']);
        }
        if (!empty($data['regist_nums'])) {
            $data['start_num'] = explode('----', $data['regist_nums'])[0];
            $data['end_num'] = explode('----', $data['regist_nums'])[1];
            $queryWhere[] = ['register_capi', 'between', [$data['start_num'], $data['end_num']]];
        }
        if (!empty($data['city'])) {
            $city_data = CommonCityModel::find($data['city']);
            if ($city_data['AreaLevel'] === 1) {
                $queryWhere['province'] = array('eq', $city_data['AreaName']);
            } elseif ($city_data['AreaLevel'] === 2) {
                $queryWhere['city'] = array('eq', $city_data['AreaName']);
            } else {
                $queryWhere['area'] = array('eq', $city_data['AreaName']);
            }
        }
        //查询字段
        $field = "company_name,legal_person,register_capi,phone,more_phone,email,more_email";
        if ($data['action'] == 1) {
            if (empty($data['output_num'])) {
                $company_list = FiberIndustryCompanyModel::where($queryWhere)->field($field)->limit(500)->orderRaw("rand()")->select()->toArray();
            } else {
                $company_list = FiberIndustryCompanyModel::where($queryWhere)->field($field)->limit($data['output_num'])->orderRaw("rand()")->select()->toArray();
            }
        } else {
            if (empty($data['output_num'])) {
                $company_list = FiberIndustryCompanyModel::where($queryWhere)->field($field)->limit(500)->select()->toArray();
            } else {
                $company_list = FiberIndustryCompanyModel::where($queryWhere)->field($field)->limit($data['output_num'])->select()->toArray();
            }
        }
        $PDF = new PdfService();
        $PDF->company_pdf($company_list);
    }

}