<?php


namespace app\controller;


use app\model\QuotedAttrModel;
use app\model\QuotedCateModel;
use app\model\QuotedGoodsAttrModel;
use app\model\QuotedGoodsModel;
use app\Request;
use app\service\RecursionService;
use app\validate\GoodsAddValidate;
use app\validate\QuotedCateAddValidate;
use think\App;
use think\facade\Cookie;
use think\facade\Db;
use think\Validate;

header("content-type:text/html; charset=utf-8");

class QuotedController extends RbacController
{
    //报价分类模型
    private $QuotedCateModel;

    //报价属性模型
    private $QuotedAttrModel;

    //商品属性模型
    private $QuotedGoodsAttrModel;

    //商品模型
    private $QuotedGoodsModel;


    //Cookie
    private $Cookie;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->QuotedCateModel = new QuotedCateModel();
        $this->QuotedAttrModel = new QuotedAttrModel();
        $this->QuotedGoodsAttrModel = new QuotedGoodsAttrModel();
        $this->QuotedGoodsModel = new QuotedGoodsModel();
        $this->Cookie = json_decode(Cookie::get('DATACENTER_ADMIN'), true);
    }

    /**
     * 报价分类列表
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function quoted_cate_list(Request $request)
    {
        $data = $request->param();
        if ($data) {
            $data['create_time'] = date("Y-m-d H:i:s", time());
            $data['update_time'] = date("Y-m-d H:i:s", time());
            $data['create_by'] = $this->Cookie['id'];
            $data['order'] = $this->QuotedCateModel->order('order desc')->value('order') + 1;
            $this->QuotedCateModel->insert($data);
        }
        $cate_arr = $this->QuotedCateModel->select()->toArray();
        if ($cate_arr) $cate_arr = RecursionService::getDepartmentTree($cate_arr);
        else $cate_arr = [];
        return view('', ['cate_arr' => $cate_arr]);
    }


    /**
     * 子分类添加
     * @param Request $request
     */
    public function quoted_cate_add(Request $request)
    {
        $data = $request->param();
        try {
            validate(QuotedCateAddValidate::class)->batch(true)->check($data);
        } catch (\ErrorException $exception) {
            $this->resultError($exception->getMessage());
        }
        $data['create_time'] = date("Y-m-d H:i:s", time());
        $data['update_time'] = date("Y-m-d H:i:s", time());
        $data['create_by'] = $this->Cookie['id'];
        $data['order'] = $this->QuotedCateModel->order('order desc')->value('order') + 1;
        $data['parents_id'] = $data['cate_id'];
        unset($data['cate_id']);
        $result = $this->QuotedCateModel->insert($data);
        if ($result) $this->resultSuccess();
        else $this->resultError();
    }

    /**
     * 分类删除
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function quoted_cate_delete(Request $request)
    {
        $data = $request->param();
        if (!$data) return $this->resultError('分类ID不能为空！');
        $result = $this->QuotedCateModel->where('parents_id', $data['cate_id'])->find();
        if ($result) return $this->resultSuccess('包含子分类，不能删除！');
        $result = $this->QuotedCateModel->where('id', $data['cate_id'])->delete();
        if ($result) return $this->resultSuccess();
        else return $this->resultError();
    }


    /**
     * 分类修改
     * @param Request $request
     * @return
     */
    public function quoted_cate_edit(Request $request)
    {
        $data = $request->param();
        try {
            validate(QuotedCateAddValidate::class)->batch(true)->check($data);
        } catch (\ErrorException $exception) {
            $this->resultError($exception->getMessage());
        }
        $data['update_time'] = date("Y-m-d H:i:s", time());
        $where['id'] = $data['cate_id'];
        unset($data['cate_id']);
        $result = $this->QuotedCateModel->where($where)->update($data);
        if ($result) return $this->resultSuccess();
        else return $this->resultError();
    }


    /**
     * 属性列表
     * @param Request $request
     */
    public function quoted_attr_list(Request $request)
    {
        if ($request->isGet()) {
            $attr_arr = $this->QuotedAttrModel->select()->toArray();
            if (!empty($attr_arr)) {
                //数组分组
                foreach ($attr_arr as $key => $item) {
                    $result[$item['attr_name']][] = $item;
                }
                $ret = array();
                //这里把简直转成了数字的，方便同意处理
                foreach ($result as $key => $value) {
                    array_push($ret, $value);
                }
                unset($result);
                foreach ($ret as $key => $item) {
                    $result[$key]['attr_name'] = $item[0]['attr_name'];
                    $result[$key]['cate_name'] = $item[0]['cate_name'];
                    $result[$key]['cate_id'] = $item[0]['cate_id'];
                    $result[$key]['value'] = $item;
                }
            } else {
                $result = [];
            }

            return view('', ['attr_arr' => $result]);
        }

        if ($request->isPost()) {

        }
    }


    /**
     * 属性添加
     * @param Request $request
     */
    public function quoted_attr_add(Request $request)
    {
        if ($request->isGet()) {
            //查询cate
            $cate_arr = RecursionService::getDepartmentTree($this->QuotedCateModel->select()->toArray());
            return view('', ['cate_arr' => $cate_arr]);
        }

        if ($request->isPost()) {
            $data = $request->param();
            foreach ($data['attr_value'] as $key => $item) {
                $attr_arr[$key]['cate_id'] = $data['cate_id'];
                $attr_arr[$key]['cate_name'] = $data['cate_name'];
                $attr_arr[$key]['attr_name'] = $data['attr_name'];
                $attr_arr[$key]['attr_value'] = $item;
                $attr_arr[$key]['create_time'] = time();
                $attr_arr[$key]['update_time'] = time();
                $attr_arr[$key]['create_by'] = $this->Cookie['id'];
            }
            try {
                $result = $this->QuotedAttrModel->saveAll($attr_arr);
                $this->resultSuccess();
            } catch (\ErrorException $exception) {
                $this->resultError($exception->getMessage());
            }

        }
    }


    /**
     * 属性删除
     * @param Request $request
     */
    public function quoted_attr_delete(Request $request)
    {
        $data = $request->param();
    }


    /**
     * 属性修改
     * @param Request $request
     */
    public function quoted_attr_edit(Request $request)
    {
        //修改页面
        if ($request->isGet()) {
            //查询修改属性信息
            $data = $request->param();
            $result = $this->QuotedAttrModel->where($data)->select()->toArray();
            foreach ($result as $key => $item) {
                $attr_arr['attr_name'] = $item['attr_name'];
                $attr_arr['cate_name'] = $item['cate_name'];
                $attr_arr['cate_id'] = $item['cate_id'];
                $attr_arr['value'][] = $item;
            }
            //查询cate
            $cate_arr = RecursionService::getDepartmentTree($this->QuotedCateModel->select()->toArray());
            return view('', ['attr_arr' => $attr_arr, 'cate_arr' => $cate_arr]);
        }

        //修改提交
        if ($request->isPost()) {
            $data = $request->param();
            foreach ($data['attr_value'] as $key => $item) {
                [$id, $attr_value] = explode('#', $item);
                $attr_arr[$key]['id'] = $id;
                $attr_arr[$key]['cate_id'] = $data['cate_id'];
                $attr_arr[$key]['cate_name'] = $data['cate_name'];
                $attr_arr[$key]['attr_name'] = $data['attr_name'];
                $attr_arr[$key]['attr_value'] = $attr_value;
                $attr_arr[$key]['update_time'] = time();
            }
            $result = $this->QuotedAttrModel->saveAll($attr_arr);
            if ($result) {
                $this->resultSuccess();
            } else {
                $this->resultError();
            }
        }
    }


    /**
     * 商品列表
     * @param Request $request
     */
    public function quoted_goods_list(Request $request)
    {
        $data = $request->param();
        //查询分类
        $cate_list = RecursionService::getDepartmentTree($this->QuotedCateModel->select()->toArray());
        $queryWhere = [];
        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            $queryWhere[] = array('create_time', 'between', [strtotime($data['start_time']), strtotime($data['end_time'])]);
        }
        if (empty($data['start_time']) && !empty($data['end_time'])) {
            $queryWhere[] = array('create_time', '<=', strtotime($data['end_time']));
        }
        if (!empty($data['start_time']) && empty($data['end_time'])) {
            $queryWhere[] = array('create_time', '>=', strtotime($data['start_time']));
        }
        if (!empty($data['goods_name'])) {
            $queryWhere[] = array('goods_name', 'like', '%' . $data['goods_name'] . '%');
        }
        if (!empty($data['cate_id'])) {
            $queryWhere[] = array('cate_id', '=', $data['cate_id']);
        }
        $goods_list = $this->QuotedGoodsModel->where($queryWhere)->order('create_time')->paginate(30)->each(function ($k, $v) {
            $attr_id = $this->QuotedGoodsAttrModel->where('id', $k['id'])->field('attr_id,unit')->find();
            $k->attr = $this->QuotedAttrModel->where('id', 'in', json_decode($attr_id['attr_id']))->select()->toArray();
            $k->unit = $attr_id['unit'];
            $k->goods_price = $k->goods_price / 100;
        });
        return view('', ['goods_list' => $goods_list, 'cate_list' => $cate_list]);
    }

    /**
     * 商品报价添加
     * @param Request $request
     */
    public function quoted_goods_add(Request $request)
    {
        if ($request->isGet()) {
            //查询分类
            $cate_list = RecursionService::getDepartmentTree($this->QuotedCateModel->select()->toArray());
            return view('', ['cate_list' => $cate_list]);
        }

        if ($request->isPost()) {
            $data = $request->param();
            try {
                validate(GoodsAddValidate::class)->batch(true)->check($data);
            } catch (\ErrorException $exception) {
                $this->resultError($exception->getMessage());
                exit;
            }
            $goods_arr['goods_name'] = $data['goods_name'];
            $goods_arr['goods_price'] = $data['goods_price'] * 100;
            $goods_arr['cate_id'] = $data['cate_id'];
            $goods_arr['cate_name'] = $data['cate_name'];
            $goods_arr['goods_con'] = !empty($data['goods_con']) ?: null;
            $goods_arr['create_time'] = time();
            $goods_arr['update_time'] = time();
            $goods_arr['create_by'] = $this->Cookie['id'];
            $goods_attr['unit'] = $data['unit'];
            $goods_attr['create_time'] = time();
            $goods_attr['update_time'] = time();
            $goods_attr['attr_id'] = json_encode($data['attr_id']);
            // 启动事务
            Db::startTrans();
            try {
                $id = $this->QuotedGoodsModel->insertGetId($goods_arr);
                $goods_attr['goods_id'] = $id;
                $this->QuotedGoodsAttrModel->insert($goods_attr);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->resultError($e->getMessage());
                exit;
            }
            $this->resultSuccess();
        }
    }

    /**
     * 通过分类查询属性
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function quoted_cate_attr(Request $request)
    {
        $cate_id = $request->param();
        $attr_list = $this->QuotedAttrModel->where(['cate_id' => $cate_id['cate_id']])->select()->toArray();
        $this->resultSuccess('成功', $attr_list);
    }


    /**
     * 商品删除
     * @param Request $request
     */
    public function quoted_goods_delete(Request $request)
    {
        $data = $request;
    }

    /**
     * 商品修改
     * @param Request $request
     */
    public function quoted_goods_edit(Request $request)
    {
        if ($request->isGet()) {
            $data = $request->param();
            //查询分类
            $cate_list = RecursionService::getDepartmentTree($this->QuotedCateModel->select()->toArray());
            //商品信息
            $goods_arr = $this->QuotedGoodsModel->where($data)->find();
            $attr_id = $this->QuotedGoodsAttrModel->where($data)->field('attr_id,unit')->find();
            $goods_arr['unit'] = $attr_id['unit'];
            $goods_arr['attr'] = $this->QuotedAttrModel->where('id', 'in', json_decode($attr_id['attr_id']))->select();
            return view('', ['cate_list' => $cate_list, 'goods_arr' => $goods_arr]);
        }

        if ($request->isPost()) {
            $data = $request->param();
            try {
                validate(GoodsAddValidate::class)->batch(true)->check($data);
            } catch (\ErrorException $exception) {
                $this->resultError($exception->getMessage());
                exit;
            }
            $goods_arr['goods_name'] = $data['goods_name'];
            $goods_arr['goods_price'] = $data['goods_price'] * 100;
            $goods_arr['cate_id'] = $data['cate_id'];
            $goods_arr['cate_name'] = $data['cate_name'];
            $goods_arr['goods_con'] = !empty($data['goods_con']) ?: null;
            $goods_arr['create_time'] = time();
            $goods_arr['update_time'] = time();
            $goods_arr['create_by'] = $this->Cookie['id'];
            $goods_attr['unit'] = $data['unit'];
            $goods_attr['create_time'] = time();
            $goods_attr['update_time'] = time();
            $goods_attr['attr_id'] = json_encode($data['attr_id']);
            // 启动事务
            Db::startTrans();
            try {
                $id = $this->QuotedGoodsModel->insertGetId($goods_arr);
                $goods_attr['goods_id'] = $id;
                $this->QuotedGoodsAttrModel->insert($goods_attr);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->resultError($e->getMessage());
                exit;
            }
            $this->resultSuccess();
        }
    }


}