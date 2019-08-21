<?php

namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Request;
use think\Validate;
use app\api\model\Classes as ClassModel;
use app\api\model\ClassCategory as CateModel;

class Classes extends Base
{
    //课程的搜索
    public function search(Request $request)
    {
        $data = $request->post();
        $rules  = [
            'keyword'   => 'require',
            'page'      => 'require',
            'page_length' => 'require',
        ];

        $messages = [
            'keyword.require'   => '请输入关键字',
            'page.require'      => '必须携带参数 page',
            'page_length'       => '必须携带参数 page_length',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>'0','msg'=>$validate->getError()]);
        }
        $start = $data['page'] * $data['page_length'] - $data['page_length'];
        $result = (new ClassModel())->where('name','like',"%{$data['keyword']}%")
                                ->where(['is_delete'=>0])
                                ->field('name,see_num,learn_num,desc,class_pic,free_chapter,chapter_sum,integral')
                                ->order('id','desc')
                                ->limit($start,$data['page_length'])
                                ->select();
        return json(['code'=>1,'data'=>$result]);
    }

    //课程列表展示
    public function list()
    {
        $cateModel = new CateModel();
        $classModel = new ClassModel();
        $cateInfo = $cateModel->select()->toArray();

        foreach ($cateInfo as $key => $value){
            $cateInfo[$key]['class'] = $classModel->where(['is_delete'=>0])
                                                ->where(['cate_id'=>$value['id']])
                                                ->order('id','desc')
                                                ->field('id,name,class_pic')
                                                ->limit(8)
                                                ->select()->toArray();
        }

        return json(['code'=>1,'data'=>$cateInfo]);
    }

    //课程列表更多
    public function listMore()
    {

    }

    //课程详情页面
    public function info()
    {

    }
}
