<?php

namespace app\admin\controller;

use think\Cache;
use think\Controller;
use think\Request;
use app\admin\model\ForumCategory as CateModel;
use app\admin\model\ForumPlate as PlateModel;
use think\Validate;

class ForumCategory extends Base
{
    public function index()
    {
        $cateList = (new CateModel())->select()->toArray();

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

        $classCateModel = new CateModel();


        $classCateModel->insert([
            'cate_name' => $data['cate_name'],
        ]);
        (new PlateModel())->clearCache();
        return json(['code'=>1,'msg'=>'success']);
    }

    public function read(Request $request)
    {
        $id = $request->param('id');
        //查询所有一级分类


        $cateInfo = (new CateModel())->find($id);

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

        $classCateModel = new CateModel();

        $classCateModel->where(['id'=>$data['id']])->update([
            'cate_name' => $data['cate_name'],
        ]);
        (new PlateModel())->clearCache();

        return json(['code'=>1,'msg'=>'success']);

    }

    public function delete(Request $request)
    {
        $id = $request->param('id');
        //判断他下面是否有板块
        if ((new PlateModel())->where(['cate_id'=>$id,'is_delete'=>0])->find()){
            return json(['code'=>0,'msg'=>'请确认该分类下没有任何板块']);
        }

        //删除
        (new CateModel())->where(['id'=>$id])->delete();
        (new PlateModel())->clearCache();

        return json(['code'=>1,'msg'=>'success']);
    }
}
