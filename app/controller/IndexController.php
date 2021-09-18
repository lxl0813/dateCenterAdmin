<?php

namespace app\controller;

use app\validate\DataCenterLoginInValidate;
use think\App;
use think\facade\Cookie;
use think\facade\Db;
use think\facade\Log;

class IndexController extends RbacController
{
    protected $admin_account;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->admin_account = json_decode(Cookie::get('DATACENTER_ADMIN'), true);
    }

    //首页框架
    public function index()
    {

        return view('', ['admin' => $this->admin_account, 'menu_list' => $this->menu_list]);
    }

    //欢迎页面
    public function welcome()
    {
        $count['fiber']['title'] = '化纤行业库';
        $count['fiber']['count'] = $this->fiber_data();
        $count['non']['title'] = '无纺行业库';
        $count['non']['count'] = $this->nonwoven_data();
        $count['products']['title'] = '制品行业库';
        $count['products']['count'] = $this->product_data();
        return view('', ['admin' => $this->admin_account, 'count' => $count]);
    }

    //化纤企业数据
    public function fiber_data()
    {
        return Db::connect('companyMysql')->name('fiber_industry_company')->count();
    }

    //无纺企业数据
    public function nonwoven_data()
    {
        return Db::connect('companyMysql')->name('non_industry_company')->count();
    }

    //制品企业数据
    public function product_data()
    {
        return Db::connect('companyMysql')->name('products_industry_company')->count();
    }




}
