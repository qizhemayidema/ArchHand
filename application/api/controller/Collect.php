<?php

namespace app\api\controller;

use think\Controller;
use think\Request;
use app\api\model\UserCollect as UserCollectModel;

class Collect extends Base
{
    //删除一个收藏课程的记录  1
    public function removeClass(Request $request)
    {
        $class_id = $request->post('class_id');
        $user_id = $this->userInfo['id'];
        (new UserCollectModel())->where(['user_id'=>$user_id,'type'=>1,'collect_id'=>$class_id])->delete();

        return json(['code'=>1,'msg'=>'success']);

    }

    //删除一个收藏文库的记录   2
    public function removeLibrary(Request $request)
    {
        $class_id = $request->post('library_id');
        $user_id = $this->userInfo['id'];
        (new UserCollectModel())->where(['user_id'=>$user_id,'type'=>2,'collect_id'=>$class_id])->delete();

        return json(['code'=>1,'msg'=>'success']);
    }
}
