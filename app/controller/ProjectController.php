<?php


namespace app\controller;


use app\model\ProjectModel;
use app\Request;
use think\App;
use think\facade\Cookie;
use think\facade\Db;

class ProjectController extends RbacController
{
    //项目Model
    private $Project;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->Project = new ProjectModel();
    }

    /**
     * 项目列表
     *
     */
    public function project_list(Request $request)
    {
        $param = $request->param();
        $where = [];
        if ($param) {
            if (!empty($param['project_name'])) {
                $where[] = array('project_name', 'like', '%' . $param['project_name'] . '%');
            }
            if (!empty($param['company_name'])) {
                $where[] = array('company_name', 'like', '%' . $param['company_name'] . '%');
            }
            if (!empty($param['project_type'])) {
                $where[] = array('project_type', '=', $param['project_type']);
            }
            if (!empty($param['start_time']) && !empty($param['end_time'])) {
                $where[] = array('start_time', '>=', strtotime($param['start_time']));
                $where[] = array('end_time', '<=', strtotime($param['end_time']));
            }
            if (!empty($param['start_time']) && empty($param['end_time'])) {
                $where[] = array('start_time', '>=', strtotime($param['start_time']));
            }
            if (empty($param['start_time']) && !empty($param['end_time'])) {
                $where[] = array('end_time', '<=', strtotime($param['end_time']));
            }
        }
        $page = 30;
        //查询所有项目
        $project_list = $this->Project->where($where)->paginate($page)->each(function ($k) {

            //日期格式转换
            $k->start_time = date("Y-m-d", $k['start_time']);
            $k->end_time = date("Y-m-d", $k['end_time']);

            //项目类型判断
            if ($k['project_type'] == 1) $k->project_type = "销售";
            if ($k['project_type'] == 2) $k->project_type = "采购";
            if ($k['project_type'] == 3) $k->project_type = "自有";
            $project_water = Db::connect('financeMysql')->name('rl_finance_water')->where(['project_id' => $k['id']])->select()->toArray();
            //判断项目是否产生流水账单
            if ($project_water) {
                if (empty($project_water['water_pay'])) {
                    //实收
                    $k->received_money = array_sum(array_column($project_water, 'water_income'));
                    //应收
                    $k->receivable_money = ($k['project_money'] - $k->received_money * 100) / 100;
                    //实付
                    $k->paid_money = 0;
                    //应付
                    $k->deal_money = 0;
                } else {
                    //实收
                    $k->received_money = 0;
                    //应收
                    $k->receivable_money = 0;
                    //实付
                    $k->paid_money = array_sum(array_column($project_water, 'water_pay'));
                    //应付
                    $k->deal_money = ($k['project_money'] - $k->paid_money * 100) / 100;
                }
            } else {
                //实付
                $k->received_money = 0;
                //应付
                $k->receivable_money = 0;
                //实收
                $k->paid_money = 0;
                //应收
                $k->deal_money = 0;
            }
            //项目金额单位转换成元
            $k->project_money = $k['project_money'] / 100;


        });
        return view('', ['project_list' => $project_list]);
    }

    /**
     * 项目详情
     * @param int $id 项目ID
     */
    public function project_detail(Request $request)
    {
        $id = (int)$request->param('id');
        $project_arr = $this->Project->where('id', $id)->find()->toArray();
        $project_arr['project_water'] = Db::connect('financeMysql')->name('rl_finance_water')->where('project_id', $id)->select()->toArray();

        if (!empty($project_arr['project_water'])) {
            if (empty($project_arr['project_water']['water_pay'])) {
                //已收金额
                $project_arr['received_money'] = array_sum(array_column($project_arr['project_water'], 'water_income'));
                //应收金额
                $project_arr['receivable_money'] = ($project_arr['project_money'] - $project_arr['received_money'] * 100) / 100;
            } else {
                //已付金额
                $project_arr['paid_money'] = array_sum(array_column($project_arr['project_water'], 'water_pay'));
                //应付金额
                $project_arr['deal_money'] = ($project_arr['project_money'] - $project_arr['paid_money'] * 100) / 100;
            }
        }

        return view('', ['project_arr' => $project_arr]);
    }


    /**
     * @param Request $request
     */
    public function project_add(Request $request)
    {
        if ($request->isGet()) {
            return view();
        }

        if ($request->isPost()) {
            $param = $request->param();
            $param['project_money'] = $param['project_money'] * 100;
            $param['create_time'] = time();
            $param['update_time'] = time();
            $param['start_time'] = strtotime($param['start_time']);
            $param['end_time'] = strtotime($param['end_time']);
            $param['create_by'] = json_decode(Cookie::get('DATACENTER_ADMIN'), true)['id'];
            try {
                $this->Project->insert($param);
                $this->resultSuccess();
            } catch (\ErrorException $exception) {
                $this->resultError($exception->getMessage());
            }
        }
    }

}