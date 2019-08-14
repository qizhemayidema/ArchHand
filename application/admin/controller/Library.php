<?php

namespace app\admin\controller;

use think\Controller;

use think\Db;
use think\Request;
use app\admin\model\Library as LibraryModel;
use think\Validate;

class Library extends Base

{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {

        $libraries = (new LibraryModel)->where('is_delete', 0)->paginate(15);
        $this->assign('libraries', $libraries);
        return $this->fetch();

    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */

    public function add()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @param  int $id
     */
    public function read($id)
    {


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

        //
    }

    /**
     * <<<<<<< HEAD
     * 删除指定资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function delete(Request $request)
    {
        $id = $request->only('id');
        Db::startTrans();
        try {
            $del = (new LibraryModel())->update(['id' => $id['id'], 'is_delete' => time()]);
            //删除标签
            $del_value = Db::name('library_have_attribute_value')->where('library_id', $id['id'])->delete();
            //删除审核原因
            $verify = Db::name('library_check_history')->where('library_id', $id['id'])->delete();
            //TODO::删除远程文件
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 审核显示
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function verify($id)
    {

        $library = LibraryModel::where('id', $id)->find();
        if (!$library) {
            $this->assign('is_exist', '未找到数据，请刷新页面确认当前数据是否以删除');
            return $this->fetch();
        }
        $this->assign('status', $library->status);
        $this->assign('id', $library->id);
        $this->assign('name', $library->name);
        return $this->fetch();
    }

    /**
     * 审核操作
     * 根据状态判断如果是未通过状态，将原因添加到记录表，否则不做修改或删除原因
     * @param Request $request
     * @return \think\response\Json
     */
    public function verifySave(Request $request)
    {

        $form = $request->only('id,status,because,name');
        if ($form['status'] == -1) {
            $rule = [
                'because' => 'require|min:2|max:40',
            ];
            $message = [
                'because.require' => '请填写原因',
                'because.min' => '不同过原因不能低于2个字符',
                'because.max' => '字符不能超过40个字'
            ];

            $validate = new Validate($rule, $message);
            if (!$validate->check($form)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
        }

        Db::startTrans();
        try {
            $library = LibraryModel::update(['id' => $form['id'], 'status' => $form['status']]);
            if ($library) {
                if ($form['status'] == -1) {
                    $check = Db::name('library_check_history')->insert([
                        'library_id' => $form['id'],
                        'because' => $form['because'],
                        'manager_id' => session('admin')->id,
                        'library_name' => $form['name'],
                        'create_time' => time(),
                    ]);
                    if (!$check) {
                        Db::rollback();
                        return json(['code' => 0, 'msg' => '审核失败']);
                    }
                } else {
                    $check = Db::name('library_check_history')->where('library_id', $form['id'])->delete();
                    if ($check) {
                        Db::commit();
                        return json(['code' => 1, 'msg' => '审核成功']);
                    }
                    Db::rollback();
                    return json(['code' => 0, 'msg' => '审核失败']);
                }
                Db::commit();
                return json(['code' => 1, 'msg' => '审核成功']);
            }
        } catch (\Exception $e) {
            Db::rollback();

            return json(['code' => 0, 'msg' => '系统错误']);
        }
    }
}
