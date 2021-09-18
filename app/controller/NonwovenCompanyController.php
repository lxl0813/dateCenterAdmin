<?php


namespace app\controller;


use app\model\CommonCityModel;
use app\model\CompanyRegistNumsModel;
use app\model\FiberIndustryCompanyModel;
use app\model\FiberCompanyProductionTypeModel;
use app\model\SystemSettingsModel;
use app\Request;
use app\service\RecursionService;
use think\facade\Db;

class NonwovenCompanyController extends RbacController
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
        $company_production_type = $request->get("company_production_type", "");
        $regist_nums = $request->get("regist_nums", "");
        $city = $request->get("city", "");
        $oper_name = $request->get("oper_name", "");
        //查询条件
        $queryWhere = [];
        if ($company_name) {
            $queryWhere['company_name'] = array('eq', $company_name);
        }
        if ($credit_code) {
            $queryWhere['credit_code'] = array('eq', $credit_code);
        }
        if ($company_production_type) {
            $queryWhere['company_production_type'] = array('eq', $company_production_type);
        }
        if ($oper_name) {
            $queryWhere['oper_name'] = array('eq', $oper_name);
        }
        if ($regist_nums) {
            $regist_nums_value = CompanyRegistNumsModel::find($regist_nums);
            $queryWhere['register_capi'] = array('egt', $regist_nums_value['start_num']);
            $queryWhere['register_capi'] = array('elt', $regist_nums_value['end_num']);
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
        }
        //进行分页查询
        //获取分页配置
        $page = SystemSettingsModel::where('system_name', '分页设置')->value('system_value');
        $non_company = FiberIndustryCompanyModel::where($queryWhere)->paginate($page);


        //获取企业生产类型
        $product_type = FiberCompanyProductionTypeModel::withSearch(['non_company_production_type'], ['status' => 1])->select()->toArray();
        //读取注册资金范围
        $regist_nums = CompanyRegistNumsModel::order('order')->select()->toArray();
        //读取配置
        $citySystem = SystemSettingsModel::where('system_name', '省市联动级别')->value('system_value');
        //获取省市联动
        $cityModel = new CommonCityModel();
        $city = $cityModel->where('AreaLevel', '<=', $citySystem)->select()->toArray();
        $tree = new RecursionService();
        $cityTree = $tree->getCateOrder($city);

        return view('', ['product_type' => $product_type, 'city_tree' => $cityTree, 'regist_nums' => $regist_nums, 'non_company' => $non_company]);
    }


    /**化纤企业excel上传
     * @param
     */
    public function fiber_company_import(Request $request)
    {
        //获取企业生产类型
        $product_type = FiberCompanyProductionTypeModel::withSearch(['fiber_company_production_type'],
            ['status' => 1]
        )->select()->toArray();
        return view('', ['product_type' => $product_type]);
    }


}