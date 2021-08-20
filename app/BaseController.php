<?php
declare (strict_types = 1);

namespace app;

use think\App;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Cookie;
use think\Validate;
header("content-type:text/html; charset=utf-8");
/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
        // 控制器初始化
        $this->initialize();
        if (!Cookie::get('DATACENTER_ADMIN')) {
            throw new HttpResponseException(redirect('/Auth/data_center_login'));
        }
    }

    // 初始化
    protected function initialize()
    {

    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }


    //二维数组根据字段去重
    protected function uniquArr($array,$field){
        $result = array();
        foreach($array as $k=>$val){
            $code = false;
            foreach($result as $_val){
                if($_val[$field] == $val[$field]){
                    $code = true;
                    break;
                }
            }
            if(!$code){
                $result[]=$val;
            }
        }
        return $result;
    }

    //对象转数组
    protected function object_array($object) {
        if (is_object($object)) {
            foreach ($object as $key => $value) {
                $array[$key] = $value;
            }
        }
        else {
            $array = $object;
        }
        return $array;
    }

    /**
     * 重定向
     * @param mixed ...$args
     */
    protected function redirect(...$args){
        throw new HttpResponseException(redirect(...$args));
    }

}
