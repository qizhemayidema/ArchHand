<?php

namespace app\admin\controller;

use think\Request;
use app\admin\model\ClassesTag as TagModel;
use app\admin\model\ClassesTagList as TagListModel;
use app\admin\model\ClassesCategory as CateModel;
use think\Validate;

class ClassesTag extends Base
{
    public function index(Request $request)
    {
        $cate_id = $request->param('cate_id') ?? 0;
        $tagInfo = (new CateModel())->alias('cate')
                                ->join('class_tag tag','tag.cate_id = cate.id');
        if ($cate_id){
            $tagInfo = $tagInfo->where(['cate_id'=>$cate_id]);
        }
        $tagInfo = $tagInfo->field('cate.cate_name,tag.id,tag.name,tag.tag_img')
                                ->order('id','desc')
                                ->paginate(20,false,['query'=>$request->param()]);


        $cateInfo = (new CateModel())->select();

        $this->assign('tag_info',$tagInfo);
        $this->assign('cate_id',$cate_id);
        $this->assign('cate_info',$cateInfo);
        return $this->fetch();
    }

    public function add(Request $request)
    {
        $cateList = (new CateModel())->select();

        $this->assign('cate_list',$cateList);

        return $this->fetch();
    }

    public function save(Request $request)
    {
        $data = $request->post();

        $rules = [
            'cate_id'        => 'require',
            'tag_img'       => 'require',
            'name'           => 'require',
        ];

        $messages = [
            'cate_id.require'       => 'error',
            'tag_img.require'      => '图片必须上传',
            'name.require'          => '名称必须填写',
        ];

        $validate = new Validate($rules,$messages);

        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $classTagModel = new TagModel();
        $reData = $classTagModel->where(['cate_id'=>$data['cate_id'],'name'=>$data['name']])->find();
        if($reData){
            return json(['code'=>0,'msg'=>'此标签已经存在']);
        }

        (new TagModel())->insert([
            'cate_id'       => $data['cate_id'],
            'tag_img'      => $data['tag_img'],
            'name'          => $data['name'],
        ]);

        return json(['code'=>1,'msg'=>'success']);
    }

    public function read(Request $request)
    {
        $tag_id = $request->param('tag_id');

        $cateList = (new CateModel())->select();

        $this->assign('cate_list',$cateList);

        $tagInfo = (new TagModel())->find($tag_id);

        $this->assign('tag_info',$tagInfo);

        return $this->fetch();

    }

    public function update(Request $request)
    {
        $data = $request->post();

        $rules = [
            'id'            => 'require',
            'cate_id'        => 'require',
            'tag_img'       => 'require',
            'name'           => 'require',
        ];

        $messages = [
            'id.require'            => 'error',
            'cate_id.require'       => 'error',
            'tag_img.require'      => '图片必须上传',
            'name.require'          => '名称必须填写',
        ];

        $validate = new Validate($rules,$messages);

        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $classTagModel = new TagModel();
        $reData = $classTagModel->where('id','<>',$data['id'])->where(['cate_id'=>$data['cate_id'],'name'=>$data['name']])->find();
        if($reData){
            return json(['code'=>0,'msg'=>'此标签已经存在']);
        }

        (new TagModel())->where(['id'=>$data['id']])->update([
            'cate_id'       => $data['cate_id'],
            'tag_img'      => $data['tag_img'],
            'name'          => $data['name'],
        ]);

        return json(['code'=>1,'msg'=>'success']);
    }

    public function delete(Request $request)
    {
        $id = $request->param('tag_id');

        $tagInfo = (new TagModel())->find($id);
        if(file_exists($tagInfo['tag_img'])){
            unlink('.' . $tagInfo['tag_img']);
        }
        //删除tag表
        (new TagModel())->where(['id'=>$id])->delete();
        //删除课程 tag_list
        (new TagListModel())->where(['tag_id'=>$id])->delete();

        return json(['code'=>1,'msg'=>'success']);
    }

    public function uploadPic()
    {
        $path = 'class_tag/';
        return (new UploadPic())->uploadOnePic($path);
    }
}
