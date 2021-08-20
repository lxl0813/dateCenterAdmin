<?php


namespace app\controller;


use app\model\DataScreenPlatformModelsFieldModel;
use app\model\DataScreenPlatformModelsFieldValueModel;
use app\model\DValueModel;
use app\model\FieldValueModel;
use app\model\SystemLogsModel;

class FormulaTaskController
{
    public function formula_task_plan()
    {
		
        //查询所有值并计算
        $this->data_foreach($this->data_all_get());
    }

    //获取公式初始值
    public function formula_init_value_get($item)
    {
        if($item['formula_init_value_status']==1){
            $where  =   [
                'platform_id'               =>  $item['platform_id'],
                'platform_model_id'         =>  $item['platform_model_id'],
                'platform_model_field_id'   =>  $item['id'],
            ];
            $formula_init_value =   DataScreenPlatformModelsFieldValueModel::where($where)->order('create_time desc')->value('platform_model_field_value');
        }else{
            $formula_init_value =   $item['formula_init_value'];
        }
        return $formula_init_value;
    }

    //获取配置使用的d值
    public function d_value_get()
    {
        $d_value    =   DValueModel::where('status',1)->value('d_value');
        if($d_value){
            return $d_value;
        }else{
            return 'd值未配置！请配置d值';
        }
    }

    //获取无风险利率
    public function risk_free_rate_get($item)
    {
        $risk_free_rate =   DataScreenPlatformModelsFieldModel::where('id',$item['id'])->value('risk_free_rate');
        return $risk_free_rate;
    }

    //获取波动率
    public function volatility_get($item)
    {
        $volatility  =   DataScreenPlatformModelsFieldModel::where('id',$item['id'])->value('volatility');
        return $volatility;
    }

    //获取所有值
    public function data_all_get()
    {
        $data_all   =   DataScreenPlatformModelsFieldModel::where('platform_id',1)->select()->toArray();
        return $data_all;
    }

    //循环所有数据，依次计算
    public function data_foreach($data_all)
    {
		set_time_limit(0);
        foreach ($data_all as $key=>$item)
        {
            $X  =   $this->data_formula($item);
			$datetime =	date('Y-m-d H:i:s',time());
			$date     =	date('Y-m-d',time());
            $new_formula_data[$key]   =   [
                'platform_id'                   =>  $item['platform_id'],
                'platform_name'                 =>  $item['platform_name'],
                'platform_model_id'             =>  $item['platform_model_id'],
                'platform_model_name'           =>  $item['platform_model_name'],
                'platform_model_field_id'       =>  $item['id'],
                'platform_model_field_name'     =>  $item['platform_model_field_name'],
                'platform_model_field_value'    =>  $X,
                'create_time'                   =>  $datetime,
                'update_time'                   =>  $datetime,
                'create_by'                     =>  2,
				'date'							=>	$date,
            ];
		
			if($item['formula_init_value_status']==2){
			    $change_status[$key]  =   [
			        'id'						=>  $item['id'],
					'formula_init_value_status'	=>1
			    ];
			}
        }
		unset($data_all);
		
		if(isset($change_status)){
			DataScreenPlatformModelsFieldModel::update($change_status);
		}
		
		$this->data_insert($new_formula_data);
    }

    //数据计算
    public function data_formula($item)
    {
		set_time_limit(0);
        $T          =   number_format(sprintf('%.10f',1/365/24/6),10);
        //1、获取无风险利率
        $r          =   $this->risk_free_rate_get($item);

        //2、获取配置使用的d值
        $d          =   $this->d_value_get();

        //3、查询公式初始值（第一次计算时或者更改时使用，其他时候使用最新值，所以要判断是否是第一次或者更改）
        $S          =   $this->formula_init_value_get($item);

        //4、获取波动率
        $e          =   $this->volatility_get($item);

        //5、根据值，带入公式，计算。 pow(x,y) 计算x的y次方，sqrt(x) 计算x的平方根 log(x) 自然对数函数  exp(x) 反自然对数函数
        $top        =   number_format(sprintf("%.10f",$e*sqrt($T)*$d),10);

        $top2       =   $top-(number_format(sprintf("%.10f",($r+pow($e,2)/2)*$T),10));

        //6、取值圆周率，通过圆周率的每一个数字的奇偶，来计算波动。
        $pai        =   "1415926535897932384626433832795028841971693993751058209749445923078164062862089986280348253421170679";

        //7、拆分数组
        $rand_sum   =   str_split($pai);

        //8、随机获取一个元素
        $sum        =   $rand_sum[array_rand($rand_sum)];

        //根据获取的元素奇偶，进行计算。
        if($sum%2==0){
            $X      =   (int)ceil($S*exp($top2));
        }else{
            $X      =   (int)ceil($S/exp($top2));
        }
		
        return $X;
    }

    //批量入库
    public function data_insert($new_formula_data)
    {
        DataScreenPlatformModelsFieldValueModel::insertAll($new_formula_data);
		FieldValueModel::insertAll($new_formula_data);
    }
}