<?php
namespace app;
use think\facade\Cookie;
use think\Response;

trait ResultTrait
{
    /**
     * @param string $message   返回信息
     * @param array $data       返回数据
     * @param string $code      返回状态码
     * @param string $success   返回是否成功
     */
    public function resultSuccess($message="成功",$data=[],$code='200',$success="success")
    {
        //$data   =   json_encode(["code"=>$code,"success"=>$success,"message"=>$message,"data"=>$data]);
        //dump($data);exit;
        //Response::create($data, 'json',200)->header(["content-type:text/html; charset=utf-8"]);
        echo  json_encode(["code"=>$code,"success"=>$success,"message"=>$message,"data"=>$data],200);return;
    }


    /**
     * @param string $message   返回信息
     * @param string $code      返回状态码
     * @param string $success   返回是否成功
     */
    public function resultError($message="失败",$code='500',$success="fail")
    {
        //$data   =   ["code"=>$code,"success"=>$success,"message"=>$message];
        //Response::create($data, 'json')->code(500)->header(["content-type:text/html; charset=utf-8"]);
        echo  json_encode(["code"=>$code,"success"=>$success,"message"=>$message],500);return;
    }
}