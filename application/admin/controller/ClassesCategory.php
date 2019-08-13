<?php

namespace app\admin\controller;

use think\Request;
use app\admin\model\ClassesCategory as ClassCateModel;
use app\admin\model\Classes as ClassModel;
use think\Validate;

class ClassesCategory extends Base
{
    public function index()
    {
        $cateList = (new ClassCateModel())->getCategoryList();

        $this->assign('cate_list',$cateList);

        return $this->fetch();
    }


    public function add()
    {
        return $this->fetch();
    }

    public function save(Request $request)
    {
        $data = $request->post();

        $rules = [
            'cate_name' => 'require',
        ];

        $messages = [
            'cate_name.require' => '必须填写分类名称',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $classCateModel = new ClassCateModel();


        $classCateModel->insert([
            'cate_name' => $data['cate_name'],
        ]);

        return json(['code'=>1,'msg'=>'success']);
    }

    public function read(Request $request)
    {
        $id = $request->param('id');
        //查询所有一级分类


        $cateInfo = (new ClassCateModel())->find($id);

        $this->assign('cate_info',$cateInfo);

        return $this->fetch();

    }

    public function update(Request $request)
    {

        $data = $request->post();

        $rules = [
            'id'        => 'require',
            'cate_name' => 'require',
        ];

        $messages = [
            'id.require'        => 'error',
            'cate_name.require' => '必须填写分类名称',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $classCateModel = new ClassCateModel();

        $classCateModel->where(['id'=>$data['id']])->update([
            'cate_name' => $data['cate_name'],
        ]);

        return json(['code'=>1,'msg'=>'success']);

    }

    public function delete(Request $request)
    {
        $id = $request->param('id');
        //判断他下面是否有课程
        if ((new ClassModel())->where(['cate_id'=>$id])->find()){
            return json(['code'=>0,'msg'=>'请确认该分类下没有课程']);
        }
        //判断分类有没有标签
        if ((new \app\admin\model\ClassesTag())->where(['cate_id'=>$id])->find()){
            return json(['code'=>0,'msg'=>'请确认该分类下标签']);
        }
        //删除
        (new ClassCateModel())->where(['id'=>$id])->delete();

        return json(['code'=>1,'msg'=>'success']);
    }
}
