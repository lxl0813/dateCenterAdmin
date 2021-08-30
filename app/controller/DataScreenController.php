<?php


namespace app\controller;


use app\model\DataScreenPlatformModel;
use app\model\DataScreenPlatformModelsFieldModel;
use app\model\DataScreenPlatformModelsFieldValueModel;
use app\model\DataScreenPlatformModelsModel;
use app\model\DValueModel;
use app\model\SystemSettingsModel;
use app\Request;
use think\App;
use think\facade\Cookie;
use think\facade\Db;
use think\facade\Log;

class DataScreenController extends RbacController
{
    //数据大屏列表
    public function data_screen_list()
    {
        return view();
    }


    //数据录入列表页面
    public function data_entry_list_view()
    {
//        $a=[];
//        $b=null;
//        $c=[];
//        foreach ($a as $k=>$v)
//        {
//            if($b!=null){
//                $c[]=sprintf("%.2f",($v-$b)/(($v+$b)/2));
//                $b=$v;
//            }else{
//                $b=$v;
//            }
//        }
//        var_dump(sprintf("%.2f",array_sum($c)/count($c)));exit;


        //根据当前管理员获取当前管理员所管理的模块，找对应的平台
        $where = [
            'admin_id' => json_decode(Cookie::get('DATACENTER_ADMIN'), true)['id'],
        ];
        $model = DataScreenPlatformModelsModel::where($where)->field('platform_id,platform_model_name')->select()->toArray();

        $platform_id = array_unique(array_column($model, 'platform_id'));

        $platform = DataScreenPlatformModel::where('id', 'in', $platform_id)->order('order')->select()->toArray();

        foreach ($model as $key => $item) {
            foreach ($platform as $key1 => $item1) {
                if ($item['platform_id'] == $item1['id']) {
                    $platform[$key1]['model'][] = $item['platform_model_name'];
                }
            }
        }

        return view('', ['platform' => $platform]);
    }

    //数据录入页面
    public function data_entry_view(Request $request)
    {
        $platform_id = $request->get('platform_id');
        $platform['platform_name'] = DataScreenPlatformModel::find($platform_id)->toArray();
        $platform['platform_model'] = DataScreenPlatformModelsModel::where(['platform_id' => $platform_id, 'admin_id' => json_decode(Cookie::get('DATACENTER_ADMIN'), true)['id']])->select()->toArray();
        return view('', ['platform' => $platform]);
    }


    //数据导入页面
    public function data_import_view()
    {
        return view();
    }

    //数据导入页面
    public function data_import_do(Request $request)
    {

    }


    //平台模块寻找字段
    public function model_find_field(Request $request)
    {
        $platform_model_id = $request->post('platform_model_id', '');
        if ($platform_model_id) {
            $where = [
                'platform_model_id' => $platform_model_id,
                'status' => 1
            ];
            $platform_model_field = DataScreenPlatformModelsFieldModel::where($where)->field('id,platform_model_field_name')->select()->toArray();
            if ($platform_model_field) {
                $this->resultSuccess('成功', $platform_model_field);
            } else {
                $this->resultError('该模块下没有可用的字段');
            }
        } else {
            $this->resultError('模块ID不存在！');
        }
    }


    //数据大屏数据录入

    /**
     * @param int $platform_id 平台ID
     * @param string $platform_name 平台名称
     * @param int $platform_model_id 平台模块ID
     * @param string $platform_model_id 平台模块ID
     * @param string $platform_model_name 平台模块名称
     * @param array $platform_model_field_value 平台模块字段和字段对应值
     */
    public function data_entry(Request $request)
    {
        $platform_id = $request->post('platform_id', '');
        $platform_name = $request->post('platform_name', '');
        $platform_model_id = $request->post('platform_model_id', '');
        $platform_model_name = $request->post('platform_model_name', '');
        $platform_model_field_value = $request->post('platform_model_field_value/a', '');
        $create_time = $request->post('create_time', '');
        if ($platform_model_field_value == "") {
            $this->resultError('字段数据不存在！请确认是否填写！');
            return;
        }
        if ($platform_model_id == "" || $platform_model_name == "" || $platform_model_id == 0) {
            $this->resultError('请选择对应模块！');
            return;
        }
        $create_by = json_decode(Cookie::get('DATACENTER_ADMIN'), true)['id'];
        foreach ($platform_model_field_value as $key => $item) {
            $value[$key]['platform_id'] = $platform_id;
            $value[$key]['platform_name'] = $platform_name;
            $value[$key]['platform_model_id'] = $platform_model_id;
            $value[$key]['platform_model_name'] = $platform_model_name;
            $field = explode("-", $item);
            $value[$key]['platform_model_field_id'] = $field[0];
            $value[$key]['platform_model_field_name'] = DataScreenPlatformModelsFieldModel::where('id', $field[0])->value('platform_model_field_name');
            $value[$key]['platform_model_field_value'] = $field[1];
            $value[$key]['create_time'] = $create_time;
            $value[$key]['update_time'] = $create_time;
            $value[$key]['create_by'] = $create_by;
        }
        try {
            $sql = new DataScreenPlatformModelsFieldValueModel();
            $result = $sql->saveAll($value);
            if ($result) {
                $this->resultSuccess('添加成功！');
                return;
            } else {
                $this->resultError();
                return;
            }
        } catch (\Exception $e) {
            $this->resultError($e->getMessage());
            return;
        }
    }

    /**
     * 平台模块数据列表
     */
    public function platform_model_data_list(Request $request)
    {
        $platform_id = $request->get('platform_id');
        $platform_model_name = $request->get("platform_model_id", '');
        $start_time = $request->get('start', '');
        $end_time = $request->get('end', '');
        $queryWhere = [];
        if ($platform_id) {
            $queryWhere['platform_id'] = array('eq', $platform_id);
        }

        if ($platform_model_name) {
            $queryWhere['platform_model_name'] = array('eq', $platform_model_name);
        }
        if ($start_time && $end_time) {
            $queryWhere['create_time'] = array('between', array($start_time, $end_time));
        } elseif ($start_time && !$end_time) {
            $queryWhere['create_time'] = array('between', array($start_time, date('Y-m-d', time())));
        } elseif (!$start_time && $end_time) {
            $queryWhere['create_time'] = array('elt', $end_time);
        }
        if (!$platform_model_name && !$start_time && !$end_time) {
            $list_page = [];
        } else {
            $page = SystemSettingsModel::where('system_name', '分页设置')->value('system_value');
            $list = DataScreenPlatformModelsFieldValueModel::where($queryWhere)->order('create_time')->select()->toArray();
            $list = $this->uniquArr($list, 'create_time');
            $id = array_column($list, 'id');
            $list_page = DataScreenPlatformModelsFieldValueModel::where('id', 'in', $id)->order('create_time')->paginate(['list_rows' => $page, 'query' => request()->param()]);
        }
        $admin_id = json_decode(Cookie::get('DATACENTER_ADMIN'), true)['id'];


        $platform = DataScreenPlatformModel::select()->toArray();
        return view('', ['platform' => $platform, 'list_page' => $list_page]);
    }


    //模块字段数据删除
    public function platform_model_field_value_delete(Request $request)
    {
        $time = $request->post('time', '');
        $platform_id = $request->post('platform_id', '');
        $platform_model_id = $request->post('platform_model_id', '');
        $queryWhere = [
            'create_time' => $time,
            'platform_id' => $platform_id,
            'platform_model_id' => $platform_model_id
        ];
        try {
            DataScreenPlatformModelsFieldValueModel::where($queryWhere)->delete();
        } catch (\Exception $e) {
            $this->resultError($e->getMessage());
            return;
        }
        $this->resultSuccess('删除成功!');
    }


    //公式初始值
    public function formula_init_value_list()
    {
        $field = DataScreenPlatformModelsFieldModel::select();
        return view('', ['field' => $field]);
    }

    //公式初始值提交
    public function formula_init_value_do(Request $request)
    {
        $value = $request->post('formula_init_value');
        $id = $request->post('id');
        $result = DataScreenPlatformModelsFieldModel::where('id', $id)->update(['formula_init_value' => $value, 'formula_init_value_status' => 2]);
        if ($result) {
            $this->resultSuccess('编辑成功!');
        } else {
            $this->resultError('编辑失败!');
        }
    }

    //d值配置
    public function d_value_configure()
    {
        $d = DValueModel::select();
        return view('', ['d' => $d]);
    }

    //d值添加
    public function d_value_configure_add(Request $request)
    {
        $d_value = $request->post('d_value');
        $data = [
            'd_value' => $d_value,
            'create_time' => date('Y-m-d H:i:s', time()),
            'update_time' => date('Y-m-d H:i:s', time()),
        ];
        $result = DValueModel::insert($data);
        if ($result) {
            $this->resultSuccess('添加成功!');
        } else {
            $this->resultError('添加失败!');
        }
    }

    //d值配置提交
    public function d_value_configure_do(Request $request)
    {
        $data = $request->post();
        $result = DValueModel::update($data);
        if ($result) {
            $this->resultSuccess('编辑成功!');
        } else {
            $this->resultError('编辑失败!');
        }
    }


}