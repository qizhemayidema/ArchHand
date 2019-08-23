<?php

namespace app\admin\controller;

use app\admin\model\Vip;
use think\Controller;
use think\Request;
use app\admin\validate\Vip as VipValidate;

class Member extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $vip = (new Vip())->paginate(15);

        $this->assign('vip', $vip);
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function add()
    {
        return $this->fetch('member/add_edit');
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {

        $form = $request->only('id,price,discount,vip_name,desc');

//        dump($form);die;
        $validate = new VipValidate();
        if (!$validate->check($form)) {
            return jsone(0, $validate->getError());
        }
        if (!$form['desc']) {
            //加载默认配置
            $config = \HTMLPurifier_Config::createDefault();
            //实例化对象
            $purifier = new \HTMLPurifier($config);
            //过滤
            $form['desc'] = $purifier->purify($form['desc']);
        }

        try {
            $vip = Vip::create($form);
            if ($vip) {
                return jsone(1, '创建成功');
            }
        } catch (\Exception $e) {
            return jsone(0, $e->getMessage());
        }
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        try {
            $vip = Vip::where('id', $id)->find();

            if (!$vip) {
                $this->assign('is_exist', '未找到数据，请刷新页面确认当前数据是否以删除');
                return $this->fetch('member/add_edit');
            }
            $this->assign('vip', $vip);
            return $this->fetch('member/add_edit');
        } catch (\Exception $e) {
            $this->assign('is_exist', $e->getMessage());
            return $this->fetch('member/add_edit');
        }
    }


    /**
     * 保存更新的资源
     *
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $form = $request->only('id,price,discount,vip_name,desc');

        $validate = new VipValidate();
        if (!$validate->check($form)) {
            return jsone(0, $validate->getError());
        }
        if (!$form['desc']) {
            //加载默认配置
            $config = \HTMLPurifier_Config::createDefault();
            //实例化对象
            $purifier = new \HTMLPurifier($config);
            //过滤
            $form['desc'] = $purifier->purify($form['desc']);
        }
        $vip = Vip::update($form);
        if ($vip) {
            return jsone(1, '编辑成功');
        }
        return jsone(0, '编辑失败');
    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {

        try {
            $user = \app\admin\model\User::where('vip_id', $id)->count('id');
            if ($user) {
                return json(['code' => 0, 'msg' => '当前VIP禁止删除']);
            }
            $vip = Vip::destroy($id);
            return json(['code'=>1,'msg'=>'删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '系统错误'], 500);
        }
    }
}
