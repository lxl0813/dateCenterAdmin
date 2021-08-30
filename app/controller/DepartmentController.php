<?php


namespace app\controller;


use app\model\DepartmentModel;
use app\model\StationModel;
use app\Request;
use app\service\RecursionService;
use think\facade\Cookie;

class DepartmentController extends RbacController
{
    /**
     * 部门列表
     */
    public function department_list(Request $request)
    {
        $params = $request->param();
        if ($params) {
            $params['create_time'] = date("Y-m-d H:i:s", time());
            $params['update_time'] = date("Y-m-d H:i:s", time());
            $params['create_by'] = json_decode(Cookie::get('DATACENTER_ADMIN'), true)['id'];
            $params['order'] = (new DepartmentModel())->order('order desc')->limit(1)->value('order') + 1;
            (new DepartmentModel())->insert($params);
        }
        $department_list = DepartmentModel::selects(null, "order");
        $department_list = RecursionService::getDepartmentTree($department_list);
        return view('', ['department_list' => $department_list]);
    }


    /**
     * 子部门添加
     */
    public function department_add(Request $request)
    {
        try {
            $params = $request->param();
            $params['create_time'] = date("Y-m-d H:i:s", time());
            $params['update_time'] = date("Y-m-d H:i:s", time());
            $params['create_by'] = json_decode(Cookie::get('DATACENTER_ADMIN'), true)['id'];
            $where = ['id' => $params['parents_id']];
            $params['level'] = (DepartmentModel::finds($where)['level']) + 1;
            $params['order'] = (new DepartmentModel())->where(['parents_id' => $params['parents_id']])->order('order desc')->limit(1)->value('order') + 1;
            $result = (new DepartmentModel())->insert($params);
            if ($result) {
                $this->resultSuccess();
            } else {
                $this->resultError();
            }
        } catch (\ErrorException $exception) {
            echo $exception->getMessage();
            exit;
        }
    }


    /**
     * 部门删除
     */
    public function department_delete(Request $request)
    {
        $params = $request->param();
        if (DepartmentModel::where(['parents_id' => $params['id']])->find()) {
            return $this->resultError('存在下级部门，不能删除！');
        } else {
            try {
                DepartmentModel::where($params)->delete();
                $this->resultSuccess();
            } catch (\ErrorException $exception) {
                $this->resultError($exception->getMessage());
            }
        }
    }


    /**
     * 部门修改
     */
    public function department_update(Request $request)
    {
        $param = $request->param();
        try {
            (new DepartmentModel())->save($param);
            $this->resultSuccess();
        } catch (\ErrorException $exception) {
            $this->resultError($exception->getMessage());
        }
    }


    /**
     * 岗位列表
     */
    public function department_station_list(Request $request)
    {
        $param = $request->param();
        $station_list = StationModel::where($param)->paginate(30)->each(function ($k, $v) {
            $k->department_name = (new DepartmentModel())->where(['id' => $k['department_id']])->value('department_name');
            $k->create_time = date('Y-m-d H:i:s', $k['create_time']);
        });
        return view('', ['station_list' => $station_list]);
    }


    /**
     * 岗位添加
     */
    public function department_station_add(Request $request)
    {
        if ($request->isGet()) {
            //查询部门
            $department_list = RecursionService::getDepartmentTree(DepartmentModel::selects());
            return view('', ['department_list' => $department_list]);
        }

        if ($request->isPost()) {
            $param = $request->param();
            $param['create_time'] = time();
            $param['update_time'] = time();
            $param['create_by'] = json_decode(Cookie::get('DATACENTER_ADMIN'), true)['id'];
            $result = (new StationModel())->insert($param);
            if ($result) {
                $this->resultSuccess();
            } else {
                $this->resultError();
            }
        }
    }


    /**
     * 岗位删除
     */
    public function department_station_delete(Request $request)
    {
        $param = $request->param();
        try {
            (new StationModel())->where($param)->delete();
            $this->resultSuccess();
        } catch (\ErrorException $exception) {
            $this->resultError($exception->getMessage());
        }
    }


    /**
     * 岗位修改
     */
    public function department_station_update(Request $request)
    {
        if ($request->isGet()) {
            $param = $request->param();
            $station = (new StationModel())->where($param)->find();
            $department_list = RecursionService::getDepartmentTree(DepartmentModel::selects());
            return view('', ['station' => $station, 'department_list' => $department_list]);
        }

        if ($request->isPost()) {
            $param = $request->param();
            $param['create_by'] = json_decode(Cookie::get('DATACENTER_ADMIN'), true)['id'];
            $param['update_time'] = time();
            try {
                StationModel::update($param);
                $this->resultSuccess();
            } catch (\ErrorException $exception) {
                $this->resultError($exception->getMessage());
            }
        }
    }
}