<?php


namespace app\controller;

use app\Request;
use think\facade\Db;



class ApiController
{

     public function __construct()
     {
         header('Access-Control-Allow-Origin: *');
         header('Access-Control-Max-Age: 1800');
         header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE');
         header('Access-Control-Allow-Headers: Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With, Token');
         if (strtoupper(\request()->method()) == "OPTIONS") {
             exit;return Response::create()->send();
         }
     }

    private $address = '支塘镇';
    public function demoCompanySearch(Request $request)
    {
        //echo 123;exit;
        $address = $request->get('address');
        if ($address == "") {
            $address = $this->address;
        }
        $fiber_company = Db::connect('companyMysql')
            ->name('fiber_industry_company')
            ->where('company_address', 'like', '%' . $address . '%')
            ->field('company_name,company_address,register_capital,lng,lat')
            ->select()->toArray();

        $non_company = Db::connect('nonMysql')
            ->name('non_company_info')
            ->where('company_address', 'like', '%' . $address . '%')
            ->field('company_name,company_address,register_capital,lng,lat')
            ->select();
        $company_list = array_merge($fiber_company, $non_company);
        foreach ($company_list as $key => $val) {
            if ($val['register_capital'] <= 100) {
                $company_list[$key]['level'] = 'E';
            } elseif ( $val['register_capital'] > 100 && $val['register_capital'] <= 200){
                $company_list[$key]['level'] = 'D';
            }elseif ( $val['register_capital'] > 200 && $val['register_capital'] <= 500){
                $company_list[$key]['level'] = 'C';
            }elseif ( $val['register_capital'] > 500 && $val['register_capital'] <= 1000){
                $company_list[$key]['level'] = 'B';
            }elseif ( $val['register_capital'] > 1000 ){
                $company_list[$key]['level'] = 'A';
            }
        }
        return json(['code'=>200,'msg'=>'查询成功','data'=>$company_list]);


    }
}