<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\admin\model\Help as HelpModel;
use app\admin\validate\Help as HelpValidate;

class Help extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {

        $help = HelpModel::field('id,cate_name')->paginate(15);
        $this->assign('help', $help);

        return $this->fetch();
    }

    public function add(Request $request)
    {
        return $this->fetch('help/add_edit');
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $form = $request->post();
        $validate = new HelpValidate();
        if (!$validate->check($form)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        unset($form['id']);
        try {
            $help = HelpModel::create($form);
            if ($help) {
                return json(['code' => 1, 'msg' => '创建成功']);
            } else {
                return json(['code' => 0, 'msg' => '创建失败']);
            }
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '系统错误']);
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
            $help = HelpModel::where('id', $id)->find();
            if (!$help) {
                $this->assign('is_exist', '未找到数据，请刷新页面确认当前数据是否以删除');
            }
            $this->assign('help', $help);
            return $this->fetch('help/add_edit');
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '系统错误']);
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
        $form = $request->post();
        $validate = new HelpValidate();
        if (!$validate->check($form)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
//        dump($form);die;
//        try {
        $help = (new HelpModel())->update($form);
        if ($help) {
            return json(['code' => 1, 'msg' => '修改成功']);
        } else {
            return json(['code' => 0, 'msg' => '修改失败']);
        }
//        } catch (\Exception $e) {
//            return json(['code' => 0, 'msg' => '系统错误']);
//        }

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
            $help = HelpModel::where('id', $id)->count('id');
            if ($help) {
                HelpModel::where('id', $id)->delete();
            } else {
                return json(['code' => 0, 'msg' => '当前信息已经删除，请刷新页面后确认']);
            }
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '系统错误']);
        }
    }
}
