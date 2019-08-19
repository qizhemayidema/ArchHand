<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\admin\model\ClassesTag as ClassTagModel;
use app\http\middleware\LoginCheck;

class ClassesTagApi extends Controller
{
    protected $middleware = [LoginCheck::class];

    public function getTagForCate(Request $request)
    {
        $cate_id = $request->post('cate_id');

        return json(['data'=>(new ClassTagModel())->where(['cate_id'=>$cate_id])->select()->toArray()]);

    }
}
