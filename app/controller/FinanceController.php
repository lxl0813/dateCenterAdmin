<?php


namespace app\controller;

use app\model\FinanceAttachlistModel;
use app\model\FinanceCompanyBankModel;
use app\model\FinanceCompanyModel;
use app\model\FinanceSubjectModel;
use app\model\FinanceSubjectTypeModel;
use app\model\FinanceWaterModel;
use app\model\SystemSettingsModel;
use app\Request;
use app\service\ExcelService;
use app\service\RecursionService;
use app\validate\FinanceWaterBaseAddValidate;
use think\exception\ValidateException;
use think\facade\Cookie;
use think\facade\Db;
use think\facade\Filesystem;

class FinanceController extends RbacController
{
    /**
     * 记账公司列表
     * @param Request $request
     * @return \think\response\View
     */
    public function finance_company_list(Request $request)
    {
        $finance_company_list    =   FinanceCompanyModel::select()->toArray();
		
		foreach($finance_company_list as $k=>$v){
			$bank=FinanceCompanyBankModel::where('company_id',$v['company_id'])->select()->toArray();
			foreach($bank as $k1=>$v1){
				$finance_company_list[$k]['bank_name'][] =	$v1['bank_name'];
				$finance_company_list[$k]['bank_account_name'][] = $v1['bank_account_name'];
				$finance_company_list[$k]['bank_account'][] = $v1['bank_account'];
			}
		}
		
        return view('',['finance_company_list'=>$finance_company_list]);
    }

    /**
     * 记账公司添加页面
     */
    public function finance_company_add()
    {
        return view();
    }

    /**
     * 记账公司添加执行
     * @param Request $request
     */
    public function finance_company_add_do(Request $request)
    {
        $company    =   $request->post();
        try {
            $add_res    =   FinanceCompanyModel::insert($company);
            if($add_res){
                $this->resultSuccess('添加成功！');return;
            }
        }catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }
    }

    /**
     * 公司银行添加
     * @param Request $request
     * @return \think\response\View
     */
	public function finance_company_bank_add(Request $request)
	{
		$company    =   $request->get('company_id');
	    return view('',['company_id'=>$company]);
	}

    /**
     * 公司银行添加执行
     * @param Request $request
     */
	public function finance_company_bank_add_do(Request $request)
	{
	    $company    =   $request->post();
	    try {
	        $add_res    =   FinanceCompanyBankModel::insert($company);
	        if($add_res){
	            $this->resultSuccess('添加成功！');return;
	        }
	    }catch (\Exception $e){
	        $this->resultError($e->getMessage());return;
	    }
	}

    /**
     * 记账公司删除
     * @param Request $request
     * @param int $company_id   公司ID
     */
    public function finance_company_delete(Request $request)
    {
        $company_id['company_id'] =   $request->post('company_id','');
        if(empty($company_id)){
            $this->resultError('未获取到公司ID');
        }else{
            try {
                $delete_res =   FinanceCompanyModel::where($company_id)->delete();
                if($delete_res){
                    $this->resultSuccess('删除成功！');return;
                }
            }catch (\Exception $e){
                $this->resultError($e->getMessage());return;
            }
        }
    }

    /**
     * 记账公司编辑页面
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function finance_company_edit(Request $request)
    {
        $company_id['company_id']         =   $request->get('company_id','');
        $finance_company    =   FinanceCompanyModel::where($company_id)->find();
        return view('',['finance_company'=>$finance_company]);
    }

    /**
     * 记账公司修改执行
     * @param Request $request
     */
    public function finance_company_edit_do(Request $request)
    {
        $check = $request->checkToken('__token__');
        if(false === $check) {
            return $this->resultError('Token令牌失效！');return;
        }
        $company        =   $request->post();
        try {
            $sql                            =   FinanceCompanyModel::where('company_id',$company['company_id'])->find();
            $sql->company_name              =   $company['company_name'];
            $sql->company_mobile            =   $company['company_mobile'];
            $sql->company_address           =   $company['company_address'];
            $sql->company_bank_account      =   $company['company_bank_account'];
            $sql->company_bank_account_name =   $company['company_bank_account_name'];
            $sql->company_bank_deposit      =   $company['company_bank_deposit'];
            $sql->company_credit_code       =   $company['company_credit_code'];
            $save_res   =   $sql->save();
            if($save_res){
                $this->resultSuccess('修改成功！');return;
            }
        }catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }
    }


    /**
     * 科目分类列表
     */
    public function finance_subject_type_list()
    {
        $subject_type       =   FinanceSubjectTypeModel::select()->toArray();
        $recursion          =   new RecursionService();
        $subject_type_list  =   $recursion->getRecursion($subject_type);
        return view('',['subject_type_list'=>$subject_type_list]);
    }

    /**
     * 科目类别名称编辑
     * @param Request $request
     */
    public function finance_subject_type_edit(Request $request)
    {
        $subject_type_id['subject_type_id']         =   intval($request->post('subject_type_id'));
        $subject_type_name['subject_type_name']     =   $request->post('subject_type_name');
        if(!$subject_type_id || !$subject_type_name){
            $this->resultError('科目类别ID或者科目类别名称缺失！');
        }
        try {
            $subject_type_model     =   new FinanceSubjectTypeModel();
            $edit_res               =   $subject_type_model->update($subject_type_name,$subject_type_id);
            if($edit_res){
                $this->resultSuccess('修改成功！');return;
            }else{
                $this->resultError('修改失败！');return;
            }
        }catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }
    }


    /**
     * 科目类别子类添加
     * @param Request $request
     */
    public function finance_subject_type_add_son_type(Request $request)
    {
        $subject_type_id    =   intval($request->post('subject_type_id'));
        $subject_type_name  =   $request->post('subject_type_name');
        if(!$subject_type_id || !$subject_type_name){
            $this->resultError('科目类别ID或者科目类别名称缺失！');
        }
        $data   =   [
                        'subject_type_name' =>  $subject_type_name,
                        'parents_id'        =>  $subject_type_id,
                        'create_time'       =>  date('Y-m-d H:i:s',time()),
                        'create_time'       =>  date('Y-m-d H:i:s',time()),
                        'create_by'         =>  json_decode(Cookie::get('DATACENTER_ADMIN'),true)['id'],
                        'order'             =>  1
                    ];
        try {
            $subject_type_model     =   new FinanceSubjectTypeModel();
            $edit_res               =   $subject_type_model->insert($data);
            if($edit_res){
                $this->resultSuccess('添加成功！');return;
            }else{
                $this->resultError('添加失败！');return;
            }
        }catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }
    }

    /**
     * 科目类别删除
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function finance_subject_type_delete(Request $request)
    {
        $subject_type_id    =   intval($request->post('subject_type_id'));
        if(!$subject_type_id){
            $this->resultError('科目ID不存在！');return;
        }
        $where      =   ['parents_id'=>$subject_type_id];
        $find_res   =   FinanceSubjectTypeModel::where($where)->find();
        if($find_res){
            $this->resultError('该科目类别下存在子科目类被，不建议删除！');return;
        }else{
            $subject_res    =   FinanceSubjectModel::where(['subject_type_id'=>$subject_type_id])->find();
            if($subject_res){
                $this->resultError('该科目类别下存在科目，不建议删除！');return;
            }else{
                $subject_res    =   FinanceWaterModel::where(['subject_id'=>$subject_type_id])->find();
                if($subject_res){
                    $this->resultError('该科目类别下存在流水，不建议删除！');return;
                }else{
                    try {
                        $del_res    =   FinanceSubjectTypeModel::where(['subject_type_id'=>$subject_type_id])->delete();
                        if($del_res){
                            $this->resultSuccess('删除成功！');return;
                        }else{
                            $this->resultError('删除失败！');return;
                        }
                    }catch (\Exception $e){
                        $this->resultError($e->getMessage());return;
                    }
                }
            }
        }
    }

    /**
     * 科目列表
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function finance_subject_list(Request $request)
    {
        $subject_list       =   FinanceSubjectModel::select()->toArray();
        foreach ($subject_list as $key=>$item)
        {
            if($item['parents_id']!=0){
                $subject_list[$key]['subject_code']   =   (new RecursionService())->digui($item['level'],$item['parents_id'],$item['subject_suffix_code']);
            }
        }
        $recursion          =   new RecursionService();
        $subject_tree_list  =   $recursion->getSubjectTree($subject_list);
        $subject_type_list  =   FinanceSubjectTypeModel::select();
        return view('',['subject_tree_list'=>$subject_tree_list,'subject_type_list'=>$subject_type_list]);
    }

/**
     * 一级科目添加
     * @param Request $request
     */
    public function finance_subject_add_do(Request $request)
    {
        $subject    =   $request->post();
        $subject['create_time'] =   date("Y-m-d H:i:s",time());
        $subject['update_time'] =   date("Y-m-d H:i:s",time());
        $subject['parents_id']  =   0;
        $subject['create_by']   =   json_decode(Cookie::get('DATACENTER_ADMIN'),true)['id'];
        $subject['level']       =   1;
        try {
            FinanceSubjectModel::insert($subject);
            return $this->resultSuccess('添加成功！');
        }catch (\ErrorException $exception){
            return $this->resultError($exception->getMessage());
        }
    }

    /**
     * 子科目添加
     */
    public function finance_son_subject_add_do(Request $request)
    {
        $subject=   $request->post();
        //查询父级分类信息
        $father_subject =   FinanceSubjectModel::where('subject_id',$subject['subject_id'])->find();
        $subject['subject_code']    =   $father_subject['subject_code'];
        $subject['subject_suffix_code'] =   FinanceSubjectModel::where('parents_id',$father_subject['subject_id'])->order('subject_suffix_code desc')->max('subject_suffix_code')+1;
        $subject['subject_full_name']   =   $subject['subject_name'];
        $subject['create_time']     =   date("Y-m-d H:i:s",time());
        $subject['update_time']     =   date("Y-m-d H:i:s",time());
        $subject['parents_id']      =   $subject['subject_id'];
        $subject['subject_type_id'] =   $father_subject['subject_type_id'];
        unset($subject['subject_id']);
        $subject['create_by']       =   json_decode(Cookie::get('DATACENTER_ADMIN'),true)['id'];
        $subject['level']           =   $father_subject['level']+1;
        try {
            FinanceSubjectModel::insert($subject);
            return $this->resultSuccess('添加成功！');
        }catch (\ErrorException $exception){
            return $this->resultError($exception->getMessage());
        }
    }
	
	/**
	     * 科目删除
	     */
	    public function finance_subject_delete(Request $request)
	    {
	        $subject_id    =   $request->post('subject_id');
	        $subject_list   =   FinanceSubjectModel::where('parents_id',$subject_id)->find();
	        if($subject_list){
	            return $this->resultSuccess('该科目下面存在子科目，不建议删除！');
	        }
	
	        $delete=FinanceSubjectModel::where('subject_id',$subject_id)->delete();
	        if($delete)
	        {
	            return $this->resultSuccess('删除成功！');
	        }else{
	            return $this->resultError('删除失败！');
	        }
	    }

    /**
     * 流水列表
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DbException
     */
    public function finance_water_list(Request $request)
    {
        $start_time     =   $request->get('start_time','');
        $end_time       =   $request->get('end_time')?$request->get('end_time'):date('Y-m-d',time());
        $status         =   $request->get('status','');
        $income_pay     =   $request->get('income_pay','');
        $create_by      =   $request->get('create_by','');
        $other_account  =   $request->get('other_account','');
        $company_id     =   $request->get('company_id','');
        //定义查询条件数组
        $queryWhere     =   [];
        if($start_time && $end_time){
            $queryWhere[]          =   array('create_time','between',[$start_time,$end_time]);
        }
        if(!$start_time && $end_time){
            $queryWhere[]          =   array('create_time','<=',$end_time);
        }
        if($status){
            $queryWhere[]               =   array('status','=',$status);
        }
        if($income_pay){
            if($income_pay==1){
                $queryWhere[]     =   array('water_pay','=',null);
            }else{
                $queryWhere[]     =   array('water_income','=',null);
            }
        }
        if($create_by){
            $queryWhere['create_by']            =   array('create_by','=',$create_by);
        }
        if($other_account){
            $queryWhere['other_account']        =   array('other_account','=',$other_account);
        }
        if($company_id){
            $queryWhere['company_id']           =   array('company_id','=',$company_id);
        }

        $page       =   SystemSettingsModel::where('system_name','分页设置')->value('system_value');
        $water_list =   FinanceWaterModel::where($queryWhere)->order('add_time')->paginate(['list_rows'=>$page,'query'=>request()->param()])->each(function ($key,$item){
                // $sub_id =   FinanceSubjectModel::where('subject_id',$key['subject_id'])->value('parents_id');
                                           
							$key['subject_name']=FinanceSubjectModel::where('subject_id',FinanceSubjectModel::where('subject_id',$key['subject_id'])->value('parents_id'))->value('subject_name').'-'.$key['subject_name'];

							if($key['status']==1){
                                $key['status_value'] =   '基础数据已录';
                            }elseif($key['status']==2){
                                $key['status_value'] =   '缺少银行流水补录';
                            }elseif($key['status']==3){
                                $key['status_value'] =   '待审核';
                            }elseif($key['status']==4){
                                $key['status_value'] =   '已审核';
                            }elseif($key['status']==5){
                                $key['status_value'] =   '已驳回';
                            }elseif($key['status']==6){
                                $key['status_value'] =   '待上传票据';
                            }elseif($key['status']==7){
                                $key['status_value'] =   '已上传票据';
                            }elseif($key['status']==8){
                                $key['status_value'] =   '待上传记账凭证';
                            }elseif($key['status']==9){
                                $key['status_value'] =   '已上传记账凭证';
                            }else{
                                $key['status_value'] =   '结单';
                            }
                        });
        $finance_company    =   FinanceCompanyModel::select();
        return view('',['water_list'=>$water_list,'finance_company'=>$finance_company]);
    }


    /**
     * 流水添加页面
     * @return \think\response\View
     */
    public function finance_water_add()
    {
        $finance_company    =   FinanceCompanyModel::select();
        //科目查询
        return view('',['finance_company'=>$finance_company]);
    }


    /**
     * 科目联动
     * @param Request $request
     */
    public function subject_linkage(Request $request)
    {
        $subject_list   =   FinanceSubjectModel::select()->toArray();
        foreach ($subject_list as $key=>$item)
        {
            $subject_arr[$key]['title']         =    $item['subject_name'];
            $subject_arr[$key]['id']            =    $item['subject_id'];
            $subject_arr[$key]['field']         =    '';
            $subject_arr[$key]['parents_id']    =   $item['parents_id'];
        }
        $recursion      =   new RecursionService();
        $subject_list   =   $recursion->subject_tree($subject_arr);
        $this->resultSuccess('成功',$subject_list);
    }


    /**
     * 科目选择
     * @return \think\response\View
     */
    public function finance_subject_select()
    {
		
        $subject_list   =   FinanceSubjectModel::select()->toArray();
        foreach ($subject_list as $key=>$item)
        {
            $subject_arr[$key]['title']         =    $item['subject_name'];
            $subject_arr[$key]['id']            =    $item['subject_id'];
            $subject_arr[$key]['field']         =    '';
            $subject_arr[$key]['parents_id']    =   $item['parents_id'];
        }
        $recursion      =   new RecursionService();
        $subject_list   =   $recursion->subject_tree($subject_arr);
       
		return view('',['subject_list'=>$subject_list]);
    }


    /**
     * 余额初步计算
     * @param Request $request
     */
        public function finance_account_balance(Request $request)
    {
        $company_id         =   $request->post('company_id','');
        $income_pay         =   (float)$request->post('nums','');
        $income_pay_type    =   $request->post('income_pay_type','');
        $bank_id            =   $request->post('bank_id','');
        if($company_id==""){
            $this->resultError('公司ID不存在！');return;
        }
        if($income_pay==""){
            $this->resultError('收支金额为获取！');return;
        }
        if($income_pay_type==""){
            $this->resultError('收支类型为获取！');return;
        }
        if($bank_id==""){
            $this->resultError('银行账户未获取！');return;
        }

        $account_balance    =   FinanceWaterModel::where('company_id',$company_id)->where('bank_id',$bank_id)->order('add_time desc')->value('account_balance');
        if($account_balance==""){
            $account_balance    =   FinanceCompanyBankModel::where(['company_id'=>$company_id,'bank_id'=>$bank_id])->value('init_account_balance');
        }
        try {
            if($income_pay_type==1){
                //收入
                $new_account_balance    =   number_format($account_balance+$income_pay,2,'.','');
            }else{
                //支出
                $new_account_balance    =   number_format($account_balance-$income_pay,2,'.','');
            }
        }catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }
        $this->resultSuccess('成功',$new_account_balance);
    }


    /**
     * 流水添加执行
     * @param Request $request
     */
    public function finance_water_add_do(Request $request)
    {
        $param  =   $request->post();
        try {
            validate(FinanceWaterBaseAddValidate::class)->batch(true)->check($param);
        }catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }
        $param['update_time']       =   $param['create_time'];
        if(isset($param['income_pay']) && $param['income_pay']==1){
            $param['water_income']  =   $param['income_pay_value'];
            unset($param['income_pay_value']);unset($param['income_pay']);
        }
        if(isset($param['income_pay']) && $param['income_pay']==2){
            $param['water_pay'] =   $param['income_pay_value'];
            unset($param['income_pay_value']);unset($param['income_pay']);
        }
        $param['status']        =   1;
        $param['create_by']     =   json_decode(Cookie::get('DATACENTER_ADMIN'),true)['id'];
        $param['create_admin']  =   json_decode(Cookie::get('DATACENTER_ADMIN'),true)['admin_name'];
        $param['add_time']      =   date('Y-m-d H:i:s',time());
        try {
            $insert_res =   FinanceWaterModel::insert($param);
            if($insert_res){
                $this->resultSuccess();return;
            }else{
                $this->resultError();return;
            }
        }catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }
    }


    /**
     * 流水详情查看
     * @param Request $request
     */
        public function finance_water_check(Request $request)
    {
        $water_id   =   $request->get('water_id');
        $water      =   FinanceWaterModel::where('water_id',$water_id)->find();
        $finance_company    =   FinanceCompanyModel::select();
        return view('',['water'=>$water,'finance_company'=>$finance_company]);
    }


    /**
     * 流水账单编辑页面
     * @param Request $request
     */
    public function finance_water_edit(Request $request)
    {
        $water_id           =   $request->get('water_id');
        $water              =   FinanceWaterModel::where('water_id',$water_id)->find();
        $finance_company    =   FinanceCompanyModel::select();
        $bank               =   FinanceCompanyBankModel::where('company_id',$water['company_id'])->select()->toArray();
        return view('',['water'=>$water,'finance_company'=>$finance_company,'bank'=>$bank]);
    }


    /**
     * 流水账单修改执行
     * @param Request $request
     */
    public function finance_water_edit_do(Request $request)
    {
        $param  =   $request->post();
        try {
            validate(FinanceWaterBaseAddValidate::class)->batch(true)->check($param);
        }catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }
        $where['water_id']          =   $param['water_id'];unset($param['water_id']);
        $param['update_time']       =   date('Y-m-d H:i:s',time());
        $param['update_by']         =   json_decode(Cookie::get('DATACENTER_ADMIN'),true)['id'];
        $param['update_admin']      =   json_decode(Cookie::get('DATACENTER_ADMIN'),true)['admin_name'];
        $company_id                 =   $param['company_id'];                       //目标公司ID
        $bank_id                    =   $param['bank_id'];                          //目标银行ID
        $water                      =   FinanceWaterModel::where($where)->find();
        $add_time                   =   $water['add_time'];                         //本条流水添加时间
        $accountBalance             =   $water['account_balance'];                  //本条流水余额
        $water_income               =   $water['water_income'];                     //本条流水收入
        $water_pay                  =   $water['water_pay'];                        //本条流水支出
        $this_company_id            =   $water['company_id'];                       //本条流水公司ID
        $this_bank_id               =   $water['bank_id'];                          //本条流水银行ID
        //更换公司的情况下
        if($water['company_id']!=$company_id){
            //跟换银行的情况下
            if($water['bank_id']!=$param['bank_id']) {

                //查询目标公司和银行下的上一条流水
                $to_last_water = FinanceWaterModel::where(['company_id' => $company_id, 'bank_id' => $param['bank_id']])->where('add_time', '<', $water['add_time'])->order('add_time desc')->find();
                if (!$to_last_water) {
                    $to_last_water['account_balance'] = FinanceCompanyBankModel::where(['company_id' => $company_id, 'bank_id' => $bank_id])->value('init_account_balance');
                }

                //传的是收入的情况下
                if (isset($param['income_pay']) && $param['income_pay'] == 1) {
                    $param['water_income'] = $param['income_pay_value'];
                    $param['water_pay'] = null;

                    //传收入，本流水也是收入的情况下，只是更改了收入金额的情况下
                    if ($water['water_income'] != "") {

                        //修改本条流水信息
                        $param['account_balance'] = $to_last_water['account_balance'] + $param['income_pay_value'];
                        FinanceWaterModel::where($where)->update($param);

                        //修改要改的公司银行后续流水
                        $toAccountBalance = $param['account_balance'] - $to_last_water['account_balance'];   //目标公司银行后续流水的余额差值
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance+$toAccountBalance WHERE company_id= $company_id and bank_id= $bank_id and add_time > '$add_time'";
                        Db::connect('financeMysql')->execute($sql);

                        //修改现在流水公司银行后续的流水信息
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance-$water_income WHERE company_id= $this_company_id and bank_id= $this_bank_id and add_time > '$add_time'";
                        $result=Db::connect('financeMysql')->execute($sql);
						if($result){
							return $this->resultSuccess('修改成功');return;
						}else{
							return $this->resultError('修改失败');return;
						}
                    }
                    //传收入，本条流水是支出的情况下
                    if ($water['water_pay'] != "") {

                        //修改本条流水信息
                        $param['account_balance'] = $to_last_water['account_balance'] + $param['income_pay_value'];
                        FinanceWaterModel::where($where)->update($param);

                        //修改要改的公司银行后续流水
                        $toAccountBalance = $param['account_balance'] - $to_last_water['account_balance'];   //目标公司银行后续流水的余额差值
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance+$toAccountBalance WHERE company_id= $company_id and bank_id= $bank_id and add_time > '$add_time'";
                        Db::connect('financeMysql')->execute($sql);

                        //修改现在流水公司银行后续的流水信息
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance+$water_pay WHERE company_id= $this_company_id and bank_id= $this_bank_id and add_time > '$add_time'";
                        $result=Db::connect('financeMysql')->execute($sql);
						if($result){
							return $this->resultSuccess('修改成功');return;
						}else{
							return $this->resultError('修改失败');return;
						}
					}

                }

                //传的是支出的情况下
                if (isset($param['income_pay']) && $param['income_pay'] == 2) {
                    $param['water_pay'] = $param['income_pay_value'];
                    $param['water_income'] = null;
                    //传支出，本条流水也是支出的情况下，只是更改了支出金额的情况下
                    if ($water['water_pay'] != "") {
                        //修改本条流水信息
                        $param['account_balance'] = $to_last_water['account_balance'] - $param['income_pay_value'];
                        FinanceWaterModel::where($where)->update($param);

                        //修改要改的公司银行后续流水
                        $toAccountBalance = $param['account_balance'] - $to_last_water['account_balance'];   //目标公司银行后续流水的余额差值
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance-$toAccountBalance WHERE company_id= $company_id and bank_id= $bank_id and add_time > '$add_time'";
                        Db::connect('financeMysql')->execute($sql);

                        //修改现在流水公司银行后续的流水信息
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance+$water_income WHERE company_id= $this_company_id and bank_id= $this_bank_id and add_time > '$add_time'";
                        $result=Db::connect('financeMysql')->execute($sql);
						if($result){
							return $this->resultSuccess('修改成功');return;
						}else{
							return $this->resultError('修改失败');return;
						}
                    }
                    //传支出，本条流水是收入的情况下
                    if ($water['water_income'] != "") {

                        //修改本条流水信息
                        $param['account_balance'] = $to_last_water['account_balance'] + $param['income_pay_value'];
                        FinanceWaterModel::where($where)->update($param);

                        //修改要改的公司银行后续流水
                        $toAccountBalance = $param['account_balance'] - $to_last_water['account_balance'];   //目标公司银行后续流水的余额差值
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance-$toAccountBalance WHERE company_id= $company_id and bank_id= $bank_id and add_time > '$add_time'";
                        Db::connect('financeMysql')->execute($sql);

                        //修改现在流水公司银行后续的流水信息
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance-$water_income WHERE company_id= $this_company_id and bank_id= $this_bank_id and add_time > '$add_time'";
                        $result=Db::connect('financeMysql')->execute($sql);
						if($result){
							return $this->resultSuccess('修改成功');return;
						}else{
							return $this->resultError('修改失败');return;
						}
                    }
                }
            }

        }else{
            //没有跟换公司,只更换银行的情况下
            if($water['bank_id']!=$param['bank_id']){

                //查询目标公司和银行下的上一条流水
                $to_last_water = FinanceWaterModel::where(['company_id' => $company_id, 'bank_id' => $param['bank_id']])->where('add_time', '<', $water['add_time'])->order('add_time desc')->find();
                if (!$to_last_water) {
                    $to_last_water['account_balance'] = FinanceCompanyBankModel::where(['company_id' => $company_id, 'bank_id' => $bank_id])->value('init_account_balance');
                }

                //传的是收入的情况下
                if (isset($param['income_pay']) && $param['income_pay'] == 1) {
                    $param['water_income'] = $param['income_pay_value'];
                    $param['water_pay'] = null;

                    //传收入，本流水也是收入的情况下，只是更改了收入金额的情况下
                    if ($water['water_income'] != "") {
                        //修改本条流水信息
                        $param['account_balance'] = $to_last_water['account_balance'] + $param['income_pay_value'];
                        unset($param['income_pay_value']);unset($param['income_pay']);
                        FinanceWaterModel::where($where)->update($param);
                        //修改要改的公司银行后续流水
                        $toAccountBalance = $param['account_balance'] - $to_last_water['account_balance'];   //目标公司银行后续流水的余额差值
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance+$toAccountBalance WHERE company_id= $company_id and bank_id= $bank_id and add_time > '$add_time'";
                        Db::connect('financeMysql')->execute($sql);
                        //修改现在流水公司银行后续的流水信息
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance-$water_income WHERE company_id= $this_company_id and bank_id= $this_bank_id and add_time > '$add_time'";
                        $result=Db::connect('financeMysql')->execute($sql);
						return $this->resultSuccess('修改成功');return;
						
					}

                    //传收入，本条流水是支出的情况下
                    if ($water['water_pay'] != "") {

                        //修改本条流水信息
                        $param['account_balance'] = $to_last_water['account_balance'] + $param['income_pay_value'];
                        unset($param['income_pay_value']);unset($param['income_pay']);
                        FinanceWaterModel::where($where)->update($param);
                        //修改要改的公司银行后续流水
                        $toAccountBalance = $param['account_balance'] - $to_last_water['account_balance'];   //目标公司银行后续流水的余额差值
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance+$toAccountBalance WHERE company_id= $company_id and bank_id= $bank_id and add_time > '$add_time'";
                        Db::connect('financeMysql')->execute($sql);
                        //修改现在流水公司银行后续的流水信息
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance+$water_pay WHERE company_id= $this_company_id and bank_id= $this_bank_id and add_time > '$add_time'";
                        $result=Db::connect('financeMysql')->execute($sql);
						return $this->resultSuccess('修改成功');return;
						
                    }
                }

                //传的是支出的情况下
                if (isset($param['income_pay']) && $param['income_pay'] == 2) {
                    $param['water_pay'] = $param['income_pay_value'];
                    $param['water_income'] = null;
                    //传支出，本条流水也是支出的情况下，只是更改了支出金额的情况下
                    if ($water['water_pay'] != "") {
                        //修改本条流水信息
                        $param['account_balance'] = $to_last_water['account_balance'] - $param['income_pay_value'];
                        unset($param['income_pay_value']);unset($param['income_pay']);
                        FinanceWaterModel::where($where)->update($param);

                        //修改要改的公司银行后续流水
                        $toAccountBalance = $param['account_balance'] - $to_last_water['account_balance'];   //目标公司银行后续流水的余额差值
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance-$toAccountBalance WHERE company_id= $company_id and bank_id= $bank_id and add_time > '$add_time'";
                        Db::connect('financeMysql')->execute($sql);

                        //修改现在流水公司银行后续的流水信息
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance+$water_income WHERE company_id= $this_company_id and bank_id= $this_bank_id and add_time > '$add_time'";
                        $result=Db::connect('financeMysql')->execute($sql);
						return $this->resultSuccess('修改成功');return;
						
                    }
                    //传支出，本条流水是收入的情况下
                    if ($water['water_income'] != "") {

                        //修改本条流水信息
                        $param['account_balance'] = $to_last_water['account_balance'] + $param['income_pay_value'];
                        unset($param['income_pay_value']);unset($param['income_pay']);
                        FinanceWaterModel::where($where)->update($param);

                        //修改要改的公司银行后续流水
                        $toAccountBalance = $param['account_balance'] - $to_last_water['account_balance'];   //目标公司银行后续流水的余额差值
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance-$toAccountBalance WHERE company_id= $company_id and bank_id= $bank_id and add_time > '$add_time'";
                        Db::connect('financeMysql')->execute($sql);

                        //修改现在流水公司银行后续的流水信息
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance-$water_income WHERE company_id= $this_company_id and bank_id= $this_bank_id and add_time > '$add_time'";
                        $reslut=Db::connect('financeMysql')->execute($sql);
						return $this->resultSuccess('修改成功');return;
						
                    }
                }

            }else{
                //没有跟换银行的情况下
                //传的是收入的情况下
                if (isset($param['income_pay']) && $param['income_pay'] == 1) {
                    $param['water_income'] = $param['income_pay_value'];
                    $param['water_pay'] = null;

                    //传收入，本流水也是收入的情况下，只是更改了收入金额的情况下
                    if ($water['water_income'] != "") {

                        //修改本条流水信息
                        $value  =   $water['water_income'] - $param['water_income'];
                        //如果这个value是负数，代表之前加少了，需要补回来, 如果是正数，则之前多加了，需扣除
                        $param['account_balance']   =   $water['account_balance']-$value;
                        unset($param['income_pay_value']);unset($param['income_pay']);
                        FinanceWaterModel::where($where)->update($param);
						//修改现在流水公司银行后续的流水信息
						if($value!=0){
							$sql = "UPDATE rl_finance_water SET account_balance=account_balance-$value WHERE company_id= $company_id and bank_id= $bank_id and add_time > '$add_time'";
							$result=Db::connect('financeMysql')->execute($sql);
						}
						return $this->resultSuccess('修改成功');return;
						
                    }
                    //传收入，本条流水是支出的情况下
                    if ($water['water_pay'] != "") {
						
                        //修改本条流水信息
                        $param['account_balance']   =   $water['account_balance']+$water['water_pay']+$param['water_income'];
                        $value  =$param['account_balance']-$water['account_balance'];
                        unset($param['income_pay_value']);unset($param['income_pay']);
                        FinanceWaterModel::where($where)->update($param);

                        //修改现在流水公司银行后续的流水信息
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance+$value WHERE company_id= $company_id and bank_id= $bank_id and add_time > '$add_time'";
                        $result=Db::connect('financeMysql')->execute($sql);
						$this->resultSuccess('修改成功');return;
						
                    }

                }

                //传的是支出的情况下
                if (isset($param['income_pay']) && $param['income_pay'] == 2) {
                    $param['water_pay'] = $param['income_pay_value'];
                    $param['water_income'] = null;
                    //传支出，本条流水也是支出的情况下，只是更改了支出金额的情况下
                    if ($water['water_pay'] != "") {
                        //修改本条流水信息
                        $value  =   $water['water_pay'] - $param['water_pay'];
                        //如果value值是负数，代表之前支出少了，现在需要多支出。。如果是正数，代表之前多支出了，需要补回来。
                        $param['account_balance'] = $water['account_balance']+$value;
                        unset($param['income_pay_value']);unset($param['income_pay']);
                        FinanceWaterModel::where($where)->update($param);

                        //修改现在流水公司银行后续的流水信息
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance+$value WHERE company_id= $company_id and bank_id= $bank_id and add_time > '$add_time'";
                        $result=Db::connect('financeMysql')->execute($sql);
						return $this->resultSuccess('修改成功');return;
						
                    }
                    //传支出，本条流水是收入的情况下
                    if ($water['water_income'] != "") {

                        //修改本条流水信息
                        $param['account_balance'] = $water['account_balance']-$water['water_income']-$param['water_income'];
                        $value  =   $water['account_balance']-$param['account_balance'];
                        unset($param['income_pay_value']);unset($param['income_pay']);
                        FinanceWaterModel::where($where)->update($param);

                        //修改现在流水公司银行后续的流水信息
                        $sql = "UPDATE rl_finance_water SET account_balance=account_balance-$value WHERE company_id= $this_company_id and bank_id= $this_bank_id and add_time > '$add_time'";
                        $result=Db::connect('financeMysql')->execute($sql);
						return $this->resultSuccess('修改成功');return;
			
                    }
                }
            }
        }
    }


    /**
     * 流水编辑时的余额计算
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function finance_account_balance_edit(Request $request)
    {
        $company_id         =   $request->post('company_id','');
        $income_pay         =   (float)$request->post('nums','');
        $income_pay_type    =   $request->post('income_pay_type','');
        $water_id           =   $request->post('water_id');
        $bank_id            =   $request->post('bank_id','');
        if($company_id==""){
            $this->resultError('公司ID不存在！');return;
        }
        if($income_pay==""){
            $this->resultError('收支金额未获取！');return;
        }
        if($income_pay_type==""){
            $this->resultError('收支类型未获取！');return;
        }
        if($bank_id==""){
            $this->resultError('银行账户未获取！');return;
        }
        //查询本条流水
        $water          =   FinanceWaterModel::where('water_id',$water_id)->find();
        //查询上一条流水
        $last_water     =   FinanceWaterModel::where('company_id',$company_id)->where('bank_id',$bank_id)->where('add_time','<',$water['add_time'])->order('add_time desc')->find();
        if(!$last_water){
            $account_balance    =   FinanceCompanyBankModel::where('company_id',$company_id)->value('init_account_balance');
        }else{
            $account_balance    =   $last_water['account_balance'];
        }
        try {
            if($income_pay_type==1 ){
                //传递的是收入，但是本条流水是支出，在上一条流水的余额加上传递的收入
                if($water['water_pay']!=""){
                    $new_account_balance    =   number_format($account_balance+$income_pay,2,'.','');
                }
                //传递的是收入，本条流水也是收入，修改金额
                if($water['water_income']!=""){
                    $new_account_balance    =   number_format($account_balance+$income_pay,2,'.','');
                }
            }else{
                //传递的是支出，本条流水也是支出，修改金额
                if($water['water_pay']!=""){
                    $new_account_balance    =   number_format($account_balance-$income_pay,2,'.','');
                }
                //传递的是支出，本条流水是收入，修改金额
                if($water['water_income']!=""){
                    $new_account_balance    =   number_format($account_balance-$income_pay,2,'.','');
                }
            }
            $this->resultSuccess('成功',$new_account_balance);return;
        }catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }

    }


    /**
     * 根据公司查询对应的银行
     */
    public function company_find_bank(Request $request)
    {
        $company_id =   $request->post('company_id');
        $bank       =   FinanceCompanyBankModel::where('company_id',$company_id)->select();
        $this->resultSuccess('成功',$bank);

    }


    /**
     * 流水单删除
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function finance_water_delete(Request $request)
    {
        $water_id   =   $request->post('water_id','');
        if($water_id==""){
            $this->resultError('未获取到流水ID');return;
        }
        $water      =   FinanceWaterModel::find($water_id);
        $value      =   $water['water_pay'];
        $company_id =   $water['company_id'];
        $bank_id    =   $water['bank_id'];
        $add_time   =   $water['add_time'];
        //本条流水是支出
        if($water['water_pay']){
            //删除本条流水，本且更改本条流水后续的流水金额
            Db::startTrans();
            try {
                //删除本条流水
                FinanceWaterModel::delete($water_id);
                //修改后续流水金额
                $sql = "UPDATE rl_finance_water SET account_balance=account_balance+$value WHERE company_id= $company_id and bank_id= $bank_id and add_time > '$add_time'";
                Db::connect('financeMysql')->query($sql);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->resultError($e->getMessage());return;
            }
        }

        //本条流水是收入
        if($water['water_income']){
            Db::startTrans();
            try {
                //删除本条流水
                FinanceWaterModel::delete($water_id);
                //修改后续流水金额
                $sql = "UPDATE rl_finance_water SET account_balance=account_balance-$value WHERE company_id= $company_id and bank_id= $bank_id and add_time > '$add_time'";
                Db::connect('financeMysql')->query($sql);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->resultError($e->getMessage());return;
            }
        }
        $this->resultSuccess('删除成功！');return;
    }




    /**
     * 附件上传
     */
    public function finance_attach_upload(Request $request)
    {
        $water_id           =   $request->get('water_id');
        //查询流水下的附件
        $attach             =   FinanceAttachlistModel::where('water_id',$water_id)->find();
        if($attach){
            //发票
            $attach_list['invoice']            =   $attach['invoice_url']?explode('&&',$attach['invoice_url']):[];
            //开票通知
            $attach_list['invoice_notice']     =   $attach['invoice_notice_url']?explode('&&',$attach['invoice_notice_url']):[];
            //收据
            $attach_list['receipt']            =   $attach['receipt_url']?explode('&&',$attach['receipt_url']):[];
            //银行回单
            $attach_list['bank_receipt']       =   $attach['bank_receipt_url']?explode('&&',$attach['bank_receipt_url']):[];
            //其他
            $attach_list['more_attach']        =   $attach['more_attach_url']?explode('&&',$attach['more_attach_url']):[];
        }else{
            $attach_list=[];
        }
        return view('',['water_id'=>$water_id,'attach_list'=>$attach_list]);
    }


    /**
     * 附件上传执行
     */
    public function finance_attach_upload_do(Request $request)
    {
        $attach_type    =   $request->post('attach_type','');
        $water_id       =   $request->post('water_id','');
        if($attach_type==""){
            $this->resultError('未识别附件分类');return;
        }
        $file   =   $request->file('file');
        try {
            //文件验证
            validate(['file' => 'fileSize:1024000|fileExt:png,jpg,jpeg,pdf'])->check(['file'=>$file]);
            $savename   =   Filesystem::disk('public')->putFile( 'finance_attach_file', $file);
            if(!$savename){
                $this->resultError('文件导入失败！请重试！');return;
            }
        } catch (ValidateException $e) {
            $this->resultError($e->getMessage());return;
        }
        $path     =   Filesystem::getDiskConfig('public','url').'/'.str_replace('\\','/',$savename);
        $insert['water_id']     =   $water_id;
        $insert[$attach_type]   =   $path;
        $insert['create_time']  =   date('Y-m-d H:i:s',time());
        $insert['update_time']  =   date('Y-m-d H:i:s',time());
        $insert['create_by']    =   json_decode(Cookie::get('DATACENTER_ADMIN'),true)['id'];
        try {
            $find_res   =   FinanceAttachlistModel::where('water_id',$water_id)->find();
            if(!$find_res){
                $insert_res =   FinanceAttachlistModel::insert($insert);
                if($insert_res){
                    $this->resultSuccess('成功！');return;
                }else{
                    unlink('.'.$path);
                    $this->resultError('失败1！');return;
                }
            }else{
                //流水已经产生附件的情况下，判断本类型的附件是否已经上传过，
                if($find_res[$attach_type]){
                    $insert[$attach_type]   =   $find_res[$attach_type]."&&".$insert[$attach_type];
                    $update_res =   FinanceAttachlistModel::where('annex_id',$find_res['annex_id'])->update($insert);
                    if($update_res){
                        $this->resultSuccess('成功！');return;
                    }else{
                        unlink('.'.$path);
                        $this->resultError('失败2！');return;
                    }
                }else{
                    $update_res =   FinanceAttachlistModel::where('annex_id',$find_res['annex_id'])->update($insert);
                    if($update_res){
                        $this->resultSuccess('成功！');return;
                    }else{
                        unlink('.'.$path);
                        $this->resultError('失败2！');return;
                    }
                }
            }
        }catch (\Exception $e){
            $this->resultError($e->getMessage());return;
        }
    }


    /**
     * 流水审核页面
     * @param Request $request
     */
    public function finance_water_examine(Request $request)
    {
        $start_time     =   $request->get('start_time','');
        $end_time       =   $request->get('end_time')?$request->get('end_time'):date('Y-m-d',time());
        $income_pay     =   $request->get('income_pay','');
        $create_by      =   $request->get('create_by','');
        $other_account  =   $request->get('other_account','');
        $company_id     =   $request->get('company_id','');
        //定义查询条件数组
        $queryWhere     =   [];
        if($start_time && $end_time){
            $queryWhere[]          =   array('create_time','between',[$start_time,$end_time]);
        }
        if(!$start_time && $end_time){
            $queryWhere[]          =   array('create_time','<=',$end_time);
        }
        if($income_pay){
            if($income_pay==1){
                $queryWhere[]     =   array('water_pay','=',null);
            }else{
                $queryWhere[]     =   array('water_income','=',null);
            }
        }
        if($create_by){
            $queryWhere['create_by']            =   array('create_by','=',$create_by);
        }
        if($other_account){
            $queryWhere['other_account']        =   array('other_account','=',$other_account);
        }
        if($company_id){
            $queryWhere['company_id']           =   array('company_id','=',$company_id);
        }
        $queryWhere['status']                   =   array('status','=',3);
        $finance_company    =   FinanceCompanyModel::select();
        $page               =   SystemSettingsModel::where('system_name','分页设置')->value('system_value');
        $water_list         =   FinanceWaterModel::where($queryWhere)->order('add_time desc')->paginate(['list_rows'=>$page,'query'=>request()->param()]);
        return view('',['water_list'=>$water_list,'finance_company'=>$finance_company]);
    }

    /**
     * 流水审核执行
     */
    public function finance_water_examine_do()
    {

    }


    /**
     * 分账查询
     * @param Request $request
     */
    public function finance_water_separate_account(Request $request)
    {

        return view();

    }
	
	
	/**
     * 流水导入页面
     */
    public function finance_water_import()
    {
        //查询公司，
        $fnance_company =   FinanceCompanyModel::select()->toArray();
        return view('',['finance_company'=>$fnance_company]);
    }
	
	
	/**
     * 流水导入执行
     */
    public function finance_water_import_do(Request $request)
    {
        $company_water_data  =   $request->post();
        $company_water_data['company_name']         =   FinanceCompanyModel::where('company_id',$company_water_data['company_id'])->value('company_name');
        $company_water_data['company_bank_name']    =   FinanceCompanyBankModel::where('bank_id',$company_water_data['company_bank_id'])->value('bank_name');
        //导入人信息
        $file   =   $request->file('file');
        try {
            //文件验证
            validate(['file' => 'fileSize:10240000|fileExt:xlsx,xls'])->check(['file'=>$file]);
            $savename   =   Filesystem::disk('public')->putFile( 'finance_company_water', $file);
            if(!$savename){
                $this->resultError('文件导入失败！请重试！');return;
            }
        } catch (ValidateException $e) {
            $this->resultError($e->getMessage());return;
        }
        if($company_water_data['company_id']=="")  return $this->resultError('请选择公司和银行');
        if($company_water_data['company_bank_id']=="") return $this->resultError('请选择公司和银行');

        //文件地址
        $path     =   '.'.Filesystem::getDiskConfig('public','url').'/'.str_replace('\\','/',$savename);
        $excel    =   new ExcelService();
        $result   =   $excel->company_water_to_mysql($path,$company_water_data);
        if($result){
            return $this->resultSuccess('导入成功！');
        }else{
            return $this->resultError('导入失败！');
        }
    }



    /**
     * 固定资产列表
     */
    public function fixed_assets_list(Request $request)
    {

    }


    /**
     * 固定资产添加
     */
    public function fixed_assets_add(Request $request)
    {

    }


    /**
     * 固定资产领用
     */
    public function fixed_assets_claim(Request $request)
    {

    }

    /**
     * 固定资产归还
     */
    public function fixed_assets_return(Request $request)
    {

    }







}