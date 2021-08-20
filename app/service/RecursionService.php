<?php


namespace app\service;


use app\model\FinanceSubjectModel;

class RecursionService
{
    //根据pid，将分类按等级处理成数组,不是父子结构，由level区分级别
    public function getCateOrder($data,$pid=0,$level=0){
        $cateOrder=[];
        foreach($data as $k=>$v){
            if($v['ParentCode']==$pid){
                $v['level']=$level;
                $cateOrder[]=$v;
                $cateOrder=array_merge($cateOrder,$this->getCateOrder($data,$v['Code'],$level+1));
            }
        }
        return $cateOrder;
    }

    //省市树状模式
    public function getMenuTree($menu,$pid=0){
        $menuTree=[];
        foreach($menu as $key=>$val){
            if($val["parents_id"]==$pid){
                if($son=$this->getMenuTree($menu,$val['subject_type_id'])){
                    $val["son"]=$son;
                }
                $menuTree[]=$val;
            }
        }
        return $menuTree;
    }

    /**
     * 科目类型递归
     * @param $data
     * @param int $pid
     * @param int $level
     * @return array
     */
    public function getRecursion($data,$pid=0,$level=0)
    {
        $cateOrder=[];
        foreach($data as $k=>$v){
            if($v["parents_id"]==$pid){
                $v['level']=$level;
                $cateOrder[]=$v;
                $cateOrder=array_merge($cateOrder,$this->getRecursion($data,$v['subject_type_id'],$level+1));
            }
        }
        return $cateOrder;
    }

    /**
     * 科目递归
     * @param $data
     * @param int $pid
     * @return array
     */
    public function getSubjectTree($data,$pid=0)
    {
        $cateOrder=[];
        foreach($data as $k=>$v){
            if($v["parents_id"]==$pid){
                $cateOrder[]=$v;
                $cateOrder=array_merge($cateOrder,$this->getSubjectTree($data,$v['subject_id']));
            }
        }
        return $cateOrder;
    }

    /**
     * 往上级查询递归
     * @param $level
     * @param $parents_id
     * @param $subject
     * @param int $i
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function digui($level,$parents_id,$subject,$i=1)
    {
        if($i<$level){
            $res        =   FinanceSubjectModel::where('subject_id',$parents_id)->find()->toArray();
            if($res['parents_id']==0 && $res['subject_suffix_code']==""){
                $subject    =   $res['subject_code']." ".$subject;
            }else{
                $subject    =   $res['subject_suffix_code']." ".$subject;
            }
            $subject    =   $this->digui($level,$res['parents_id'],$subject,$i+1);
        }
        return $subject;
    }

    /**
     *科目父子结构递归
     */
    public function subject_tree($subject_list , $pid=0)
    {
        $subjectTree=[];
        foreach($subject_list as $key=>$val){
            if($val["parents_id"]==$pid){
                if($son=$this->subject_tree($subject_list,$val['id'])){
                    $val["children"]=   $son;
                }
                $subjectTree[]=$val;
            }
        }
        return $subjectTree;
    }


    /**
     * 权限递归
     */
    public function nodes_recursion($nodes_list,$pid=0)
    {
        $nodes_arr=[];
        foreach($nodes_list as $k=>$v){
            if($v["parents_id"]==$pid){
                $nodes_arr[]=$v;
                $nodes_arr=array_merge($nodes_arr,$this->getSubjectTree($nodes_list,$v['id']));
            }
        }
        return $nodes_arr;
    }


    public function nodes_recursion_son($nodes_list,$pid=0)
    {
        $nodes_arr=[];
        foreach($nodes_list as $key=>$val){
            if($val["parents_id"]==$pid){
                if($son=$this->subject_tree($nodes_list,$val['id'])){
                    $val["children"]=   $son;
                }
                $nodes_arr[]=$val;
            }
        }
        return $nodes_arr;
    }


    /**
     * 部门递归/报价产品分类递归
     * @param $data
     * @param int $pid
     * @return array
     */
    public static function getDepartmentTree($data,$pid=0)
    {
        $cateOrder=[];
        foreach($data as $k=>$v){
            if($v["parents_id"]==$pid){
                $cateOrder[]=$v;
                $cateOrder=array_merge($cateOrder,self::getDepartmentTree($data,$v['id']));
            }
        }
        return $cateOrder;
    }

}