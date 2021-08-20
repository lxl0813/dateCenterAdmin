<?php


namespace app\controller;

use app\model\SharesBasicFundsModel;
use app\model\SharesProbabilitysModel;
use app\model\SharesDatasModel;
use app\model\SharesModel;
use app\model\SharesTradersNums;
use app\model\SystemSettingsModel;
use app\Request;
use app\service\ExcelService;
use app\service\SharesCountService;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Filesystem;

class SharesDataController extends RbacController
{
    //股票数据列表页面
    public function shares_list(Request $request)
    {
        $start_time    =   $request->get('start');
        $end_time      =   $request->get('end')?$request->get('end'): date("Y-m-d",time());
        //没有查询条件下
        if((!$start_time && !$end_time) || !$start_time){
            //分页查询。每页30条数据
            $shares_result  =   Db::table('shares')->where('status',1)->order('create_time desc')->paginate(1);
            return view('',['shares'=>$shares_result]);
        }
        $shares_result  =   Db::table('shares')->where('create_time','between',[$start_time,$end_time])->where('status',1)->order('create_time desc')->paginate(1);
        return view('',['shares'=>$shares_result]);
    }


    //股票数据导入
    public function shares_import()
    {
        //添加导入页面
        return view();
    }


    //股票信息上传读取入库
    public function shares_import_do(Request $request)
    {
        $create_time    =   $request->post('create_time');
        if(!$create_time){
            $this->resultSuccess('请选择时间！');return;
        }
        $files = request()->file('file');
        try {
            //文件验证
            validate(['file' => 'fileSize:1024000|fileExt:xlsx,xls'])->check(['file'=>$files]);
            $savename   =   Filesystem::disk('public')->putFile( 'shares_base_import', $files);
            if(!$savename){
                $this->resultError('文件导入失败！请重试！');return;
            }
        } catch (ValidateException $e) {
            $this->resultError($e->getMessage());return;
        }
        //开始读取excel文件内容
        $excel_path     =   '.'.Filesystem::getDiskConfig('public','url').'/'.str_replace('\\','/',$savename);
        $excel          =   new ExcelService();
        $excel_to_mysql =   $excel->shares_excel_read_to_mysql($excel_path,$create_time);
        if(true===$excel_to_mysql){
            $this->resultSuccess('文件导入成功！');return;
        }else{
            $this->resultError($excel_to_mysql);return;
        }
    }


    //股票数据删除
    public function shares_data_delete(Request $request)
    {
        $shares_id                =   $request->post('id');
        if(!$shares_id){
            $this->resultError('ID获取失败！');return;
        }
        try {
            $shares                 =   SharesModel::find($shares_id);
            $shares->status         =   2;
            $shares->delete_time    =   date("Y-m-d",strtotime('+30 day',time()));
            $shares->save();
            $this->resultSuccess("该日股票信息删除成功！");return;
        }catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }
    }


    //股票数据导出
    public function shares_excel_export(Request $request)
    {
        $shares_id    =   $request->post('shares_id');
        //excel表数据
        $result     =   SharesDatasModel::where('shares_id',$shares_id)->withoutField('shares_id,create_by,create_account,create_admin')->select()->toArray();
        foreach ($result as $key=>$item)
        {
            $result[$key]['a_probability']=($item['a_probability']*100).'%';
            $result[$key]['y_probability']=($item['y_probability']*100).'%';
            $result[$key]['x_probability']=($item['x_probability']*100).'%';
        }
        //excel文件名称
        $file_name  =   $result[0]['create_time']."股票信息";
        $excel      =   new ExcelService();
        $excel_url  =   $excel->shares_excel_export($result,$file_name);
        if(false===$excel_url){
            $this->resultError($excel_url);
        }else{
            $this->resultSuccess('导出成功！',$excel_url);
        }
    }


    //股票详情页
    public function shares_details(Request $request)
    {
        $shares_id      =   $request->param('shares_id');
        //取交易手数
        $traders_nums   =   SharesTradersNums::select();
        //取概率
        $probability['a_probability']    =   SharesProbabilitysModel::where('type',1)->order('order')->select()->toArray();
        $probability['y_probability']    =   SharesProbabilitysModel::where('type',2)->order('order')->select()->toArray();
        $probability['x_probability']    =   SharesProbabilitysModel::where('type',3)->order('order')->select()->toArray();
        //股票基础资金量
        $basic_funds                     =   SharesBasicFundsModel::order('order')->select();
        //取该日股票数据
        $shares_datas   =   SharesDatasModel::where('shares_id',$request->param('shares_id'))->select()->toArray();
        return view('',['traders_nums'=>$traders_nums,'basic_funds'=>$basic_funds,'shares_id'=>$shares_id,'shares_datas'=>$shares_datas,'probability'=>$probability]);
    }


    //股票计算生成excel
    /**
     * @param int     $traders_nums     交易手数
     * @param string  $a_probability    A概率
     * @param string  $y_probability    Y概率
     * @param string  $x_probability    X概率
     * @param int     $is_cv            是否计算CV值
     * @param int     $shares_id        股票日期ID
     */
    public function shares_count_to_excel(Request $request)
    {
        $traders_nums   =   $request->post('traders_nums',"");
        $a_probability  =   $request->post('a_probability',"");
        $y_probability  =   $request->post('y_probability',"");
        $x_probability  =   $request->post('x_probability',"");
        $is_cv          =   $request->post('is_cv',"");
        $basic_funds    =   $request->post('basic_funds',"");
        $shares_id      =   $request->post('shares_id');
        if($traders_nums==""){
            $traders_nums   =   (int)Db::connect('dataMysql')->name('system_settings')->where('system_name','股票交易手数')->value('system_value');
        }else{
            $traders_nums   =   (int)$traders_nums;
        }
        if($a_probability==""){
            $a_probability  =   (float)Db::connect('dataMysql')->name('system_settings')->where('system_name','股票A概率')->value('system_value')/100;
        }else{
            $a_probability  =   (float)$a_probability/100;
        }
        if($y_probability==""){
            $y_probability  =   (float)Db::connect('dataMysql')->name('system_settings')->where('system_name','股票Y概率')->value('system_value')/100;
        }else{
            $y_probability  =   (float)$y_probability/100;
        }
        if($x_probability==""){
            $x_probability  =   (float)Db::connect('dataMysql')->name('system_settings')->where('system_name','股票X概率')->value('system_value')/100;
        }else{
            $x_probability  =   (float)$x_probability/100;
        }
        if($is_cv==""){
            $is_cv          =   Db::connect('dataMysql')->name('system_settings')->where('system_name','股票CV值计算')->value('system_value');
        }
        if($basic_funds==""){
            $basic_funds    =   (int)Db::connect('dataMysql')->name('system_settings')->where('system_name','股票基础资金量')->value('system_value');
        }else{
            $basic_funds=(int)$basic_funds;
        }

        //获取该日股票基础数据（sheet1）
        $shares_base       =   SharesDatasModel::where('shares_id',$shares_id)->select()->toArray();

        //选出符合A概率、Y概率、X概率的数据，在基本数据的基础上，根据交易手数，计算：交易值、交易毛利预测、印花税、交易净利、基础资金量、交易净值、日盈利。
        $shares_filter_datas    =   SharesDatasModel::withSearch(['time','Aprobability','Yprobability','Xprobability'],[
                                                                    'time'		    =>	$shares_id,
                                                                    'Aprobability'	=>  $a_probability,
                                                                    'Yprobability'	=>	$y_probability,
                                                                    'Xprobability'  =>  $x_probability,
                                                      ])->select()->toArray();
        if($shares_filter_datas){
            //获取系统配置的股票印花税
            $stamp_duty             =   SystemSettingsModel::where('system_name','股票印花税')->value('system_value');
            //获取系统配置的日盈利下限
            $daily_profit_floor     =   SystemSettingsModel::where('system_name','股票日盈利下限')->value('system_value');
            //调用服务层进行计算
            $shares_count           =   new SharesCountService();
            //更多股票信息计算（sheet2）
            $shares_more    =   $shares_count->shares_more_count($traders_nums,$shares_filter_datas,$stamp_duty,$basic_funds,$daily_profit_floor);
            //判断是否需要计算CV值
            if($is_cv==1){
                //获取CV值系统配置的数值
                $cv_range           =   Db::connect('dataMysql')->name('system_settings')->where('system_name','股票CV值计算范围')->value('system_value');
                $cv_weight          =   Db::connect('dataMysql')->name('system_settings')->where('system_name','股票CV权重')->value('system_value');
                //CV数据（sheet3）
                $shares_cv          =   $shares_count->shares_cv_count($shares_more,$cv_range,$shares_id,$cv_weight,$traders_nums,$stamp_duty,$basic_funds);
            }else{
                $shares_cv          =   2;
            }
        }else{
            $shares_more            =   [];
            $shares_cv              =   [];
        }
        //处理好数据后，调用excel服务层，进行写表
        $excel    =    new ExcelService();
        $shares_detail_url          =     $excel->shares_details_export($shares_base,$shares_more,$shares_cv);
        if(false===$shares_detail_url){
            $this->resultError($shares_detail_url);
        }else{
            $this->resultSuccess('导出成功！',$shares_detail_url);
        }
    }
}