<?php


namespace app\service;


use app\model\SharesDatasModel;
use app\model\SharesModel;
use think\facade\Db;

class SharesCountService
{
    /**
     * @param int       $traders_nums          交易手数
     * @param array     $shares_filter_datas   股票结果集
     * @param float     $stamp_duty            股票印花税
     * @param int       $basic_funds           股票基础资金量
     * @param string    $daily_profit_floor
     * 股票基本信息之外更多信息计算
     */
    public function shares_more_count($traders_nums,$shares_filter_datas,$stamp_duty,$basic_funds,$daily_profit_floor)
    {
        //选出符合A概率、Y概率、X概率的数据，在基本数据的基础上，根据交易手数，计算：交易值、交易毛利预测、印花税、交易净利、基础资金量、交易净值、日盈利。
        foreach ($shares_filter_datas as  $key=>$item)
        {
            if($item['x_avg']==0){
                unset($shares_filter_datas[$key]);
            }else{
                //根据交易手数计算交易值
                $shares_filter_datas[$key]['traders_nums']          =   $traders_nums;
                //交易值
                $transaction_value                                  =   $traders_nums*100*$item['x_avg'];
                $shares_filter_datas[$key]['transaction_value']     =   $transaction_value;
                //交易毛利预测
                $trading_margin                                     =   ($traders_nums*100*$item['x_sqrt'])/2;
                $shares_filter_datas[$key]['trading_margin']        =   $trading_margin;
                //印花税
                $shares_stamp_duty                                  =   $transaction_value*$stamp_duty;
                $shares_filter_datas[$key]['shares_stamp_duty']     =   $shares_stamp_duty;
                //交易净利
                $trading_net_profit                                 =   $trading_margin-$shares_stamp_duty;
                $shares_filter_datas[$key]['trading_net_profit']    =   $trading_net_profit;
                //基础资金量
                $shares_filter_datas[$key]['basic_funds']           =   $basic_funds;
                //交易净值
                $transaction_net_value                              =   ($basic_funds/$transaction_value)*$trading_net_profit;
                $shares_filter_datas[$key]['transaction_net_value'] =   $transaction_net_value;
                //日盈利
                $daily_profit                                       =   $transaction_net_value/$basic_funds;
                if($daily_profit<$daily_profit_floor){
                    unset($shares_filter_datas[$key]);
                }else{
                    $shares_filter_datas[$key]['daily_profit']          =   sprintf("%01.2f",$daily_profit*100)."%";
                }
            }
        }
        //根据日盈利进行排序
        $last_names = array_column($shares_filter_datas,'daily_profit');
        array_multisort($last_names,SORT_DESC,$shares_filter_datas);
        return $shares_filter_datas;
    }


    /**
     * @param array     $shares_more    股票数据集
     * @param string    $cv_range               CV值计算范围
     * @param string    $shares_id              股票日期ID
     * @param string    $cv_weight              CV权重
     * @param int       $traders_nums          交易手数
     * @param float     $stamp_duty            股票印花税
     * @param int       $basic_funds           股票基础资金量
     * 股票cv计算
     */
    public function shares_cv_count($shares_more , $cv_range , $shares_id , $cv_weight ,$traders_nums , $stamp_duty , $basic_funds)
    {
        foreach ($shares_more as $key=>$item)
        {
            $new_shares_datas[$key]['shares_code']   =  $item['shares_code'];
            $new_shares_datas[$key]['shares_name']   =  $item['shares_name'];
            $new_shares_datas[$key]['daily_profit']  =  $item['daily_profit'];
        }
        unset($shares_more);
        //循环过滤后的新股票数据，查询每支股的近CV权重的天数的数据，然后计算，得出所有股的日盈利，然后计算CV值。
        $where          =   SharesModel::where('id',$shares_id)->value('create_time');
        $create_time    =   SharesModel::where('create_time','<=',$where)->order('create_time')->limit($cv_range)->column('id,create_time');
        //查询对应股票的基础数据
        foreach ($new_shares_datas as $key=>$item)
        {
            foreach ($create_time as $key1=>$item1)
            {
                $shares_data_where  =   [
                    'shares_code'   =>  $item['shares_code'],
                    'shares_name'   =>  $item['shares_name'],
                    'shares_id'     =>  $item1['id'],
                ];
                $sql    =    SharesDatasModel::where($shares_data_where)->find();
                if($sql){
                    $shares_datas[$key][]=$sql->toArray();
                }else{
                    $shares_datas[$key][]=[];
                }
            }
        }
        //根据基础数据，计算日盈利
        foreach($shares_datas as $key=>$item)
        {
            foreach ($item as $key1=>$item1)
            {
                if(empty($item1)){
                    $shares_datas[$key][$key1]=[];
                }else{
                    //根据交易手数计算交易值
                    //交易值
                    $transaction_value                                  =   $traders_nums*100*$item1['x_avg'];
                    //交易毛利预测
                    $trading_margin                                     =   ($traders_nums*100*$item1['x_sqrt'])/2;
                    //印花税
                    $shares_stamp_duty                                  =   $transaction_value*$stamp_duty;
                    //交易净利
                    $trading_net_profit                                 =   $trading_margin-$shares_stamp_duty;
                    //基础资金量
                    $shares_datas[$key][$key1]['basic_funds']           =   $basic_funds;
                    //交易净值
                    $transaction_net_value                              =   ($basic_funds/$transaction_value)*$trading_net_profit;
                    //日盈利
                    $daily_profit                                       =   $transaction_net_value/$basic_funds;
                    $shares_datas[$key][$key1]['daily_profit']          =   sprintf("%01.2f",$daily_profit*100)."%";
                    unset($shares_datas[$key][$key1]['a_probability']);
                    unset($shares_datas[$key][$key1]['y_probability']);
                    unset($shares_datas[$key][$key1]['x_probability']);
                    unset($shares_datas[$key][$key1]['a_sqrt']);
                    unset($shares_datas[$key][$key1]['y_sqrt']);
                    unset($shares_datas[$key][$key1]['a_avg']);
                    unset($shares_datas[$key][$key1]['y_avg']);
                    unset($shares_datas[$key][$key1]['x_sqrt']);
                    unset($shares_datas[$key][$key1]['x_avg']);
                    unset($shares_datas[$key][$key1]['create_by']);
                    unset($shares_datas[$key][$key1]['create_account']);
                    unset($shares_datas[$key][$key1]['create_admin']);
                }
            }
        }
        //循环，然后根据CV权重进行分组，CV权重不得大于CV_range,如果大于，则CV权重使用cv_range
        foreach ($shares_datas as $key=>$item)
        {
            //判断cv权重和range的大小
            if($cv_weight>$cv_range){
                $cv_weight=$cv_range;
            }
            $shares_combination    =    $this->combination($item,$cv_weight);
            //对返回的结果进行CV计算
            $cv                    =    $this->cv_count($shares_combination);
            //计算数组item长度
            $arr=[];
            for ($i=0;$i<=count($cv)-1;$i++){
                if($i==0){
                    for ($s=0;$s<=$cv_weight-1;$s++){
                        $arr[]     =   $item[$s];
                    }
                    $arr[]         =    $cv[$i];
                }elseif($i==1){
                    $arr[]         =    $item[$cv_weight];
                    $arr[]         =    $cv[$i];
                }else{
                    $arr[]         =    $item[$cv_weight-1+$i];
                    $arr[]         =    $cv[$i];
                }
            }
            $shares_datas[$key]    =    $arr;
            unset($arr);
        }
        foreach ($shares_datas as $key=>$item)
        {
            foreach ($item as $key1=>$item1)
            {
                if(is_array($item1))
                {
                    if(empty($item1)){
                        $shares_cv['data'][$key]['daily_profit']    =    "";
                    }else{
                        $shares_cv['data'][$key]['id']              =    $item1['id'];
                        $shares_cv['data'][$key]['shares_code']     =    $item1['shares_code'];
                        $shares_cv['data'][$key]['shares_name']     =    $item1['shares_name'];
                        $shares_cv['data'][$key][]                  =    $item1['daily_profit'];
                    }
                }else{
                    $shares_cv['data'][$key][]                      =    $item1;
                }
            }
        }
        $shares_cv['title']=$shares_datas;
        return $shares_cv;
    }




    /**
     * @param array $array      待组合的数组
     * @param int   $value      组合要求
     */
    public function combination($array,$cv_weight)
    {
        //计算数组长度
        $array_count    =   count($array);
        //定义一个空数组
        $content        =   [];
        for ($s=0;$s<=$array_count-1;$s++){
            if(isset($array[$s+$cv_weight-1])){
                //for循环CV权重
                for($i=0;$i<=$cv_weight-1;$i++){
                     $content[$s][]     =   (float)$array[$s+$i]['daily_profit']/100;
                }
            }
        }
        return $content;
    }

    /**
     * @param array $array 数组
     */
    public function cv_count(array $array)
    {
        foreach ($array as $key=>$item)
        {
            //数组长度即cv权重
            $count  =   count($item);
            //计算平均值
            $avg    =   array_sum($item)/$count;
            //计算标准偏差
            $one    =   1/($count-1);
            for ($i=0;$i<=$count-1;$i++)
            {
                $pow_data[]   =   pow($item[$i]-$avg,2);
            }
            $cv[$key] =   sqrt($one*array_sum($pow_data));
        }
        return $cv;
    }
}