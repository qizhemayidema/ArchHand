<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\admin\model\ClassesCategory as ClassCateModel;
use app\admin\model\ClassesCategoryCount as ClassCateCountModel;
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
        //查询所有一级分类

        $cateList = (new ClassCateModel())->where(['p_id'=>0])->select();

        $this->assign('cate_info',$cateList);
        return $this->fetch();
    }

    public function save(Request $request)
    {
        $data = $request->post();

        $rules = [
            'p_id'      => 'require',
            'cate_name' => 'require',
        ];

        $messages = [
            'p_id.require'      => '必须选择所属分类',
            'cate_name.require' => '必须填写分类名称',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $idsString = '-';
        if ($data['p_id'] != 0){
            $idsString .= $data['p_id'];
        }

        $classCateModel = new ClassCateModel();


        $classCateModel->insert([
            'p_id'      => $data['p_id'],
            'cate_name' => $data['cate_name'],
            'ids_string'=> $idsString,
        ]);

        $cate_id = $classCateModel->getLastInsID();

        if ($data['p_id'] != 0){
            (new ClassCateCountModel())->insert([
                'cate_id'   => $cate_id,
            ]);
        }

        return json(['code'=>1,'msg'=>'success']);
    }

    public function read(Request $request)
    {
        $id = $request->param('id');
        //查询所有一级分类

        $cateList = (new ClassCateModel())->where(['p_id'=>0])->select();

        $cateInfo = (new ClassCateModel())->find($id);

        $this->assign('cate_list',$cateList);

        $this->assign('cate_info',$cateInfo);

        return $this->fetch();

    }

    public function update(Request $request)
    {

        $data = $request->post();

        $rules = [
            'id'        => 'require',
            'p_id'      => 'require',
            'cate_name' => 'require',
        ];

        $messages = [
            'id.require'        => 'error',
            'p_id.require'      => '必须选择所属分类',
            'cate_name.require' => '必须填写分类名称',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $cate_info = (new ClassCateModel())->find($data['id']);


        //如果修改的是一级分类 则不变夫级id
        if ($cate_info['p_id'] == 0){
            $data['p_id'] = 0;
        }else{
            //如果修改的是二级分类 夫级id只能是1级的1
            $parent_cate_info = (new ClassCateModel())->find($data['p_id']);
            if ($parent_cate_info['p_id'] != 0){
                return json(['code'=>0,'msg'=>'二级分类只能选择在一级分类下']);
            }
        }


        $idsString = '-';
        if ($data['p_id'] != 0){
            $idsString .= $data['p_id'];
        }

        $classCateModel = new ClassCateModel();

        $classCateModel->where(['id'=>$data['id']])->update([
            'p_id'      => $data['p_id'],
            'cate_name' => $data['cate_name'],
            'ids_string'=> $idsString,
        ]);

        return json(['code'=>1,'msg'=>'success']);

    }

    public function delete(Request $request)
    {
        $id = $request->param('id');
        //判断他下面是否有分类
        if ((new ClassCateModel())->where(['p_id'=>$id])->find()){
            return json(['code'=>0,'msg'=>'请确认该分类下没有子分类']);
        }
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
