<?php

namespace app\api\controller;

use think\Controller;
use think\Exception;
use think\Request;
use app\api\model\UserCollect as UserCollectModel;
use app\api\model\Classes as ClassModel;
use app\api\model\Library as LibraryModel;

class Collect extends Base
{
    //删除一个收藏课程的记录  1
    public function removeClass(Request $request)
    {
        $class_id = $request->post('class_id');
        $user_id = $this->userInfo['id'];
        $classModel = new ClassModel();
        $classModel->startTrans();
        try {
            (new UserCollectModel())->where(['user_id' => $user_id, 'type' => 1, 'collect_id' => $class_id])->delete();
            (new ClassModel())->where(['id' => $class_id])->setDec('collect_num');
            $classModel->commit();
        } catch (Exception $e) {
            $classModel->rollback();
            return json(['code' => 0, 'msg' => '操作失败,请刷新后重试']);
        }
        return json(['code' => 1, 'msg' => 'success']);

    }

    //删除一个收藏文库的记录   2
    public function removeLibrary(Request $request)
    {
        $lib_id = $request->post('library_id');
        $user_id = $this->userInfo['id'];
        $lib_model = new LibraryModel();
        $lib_model->startTrans();
        try {
            (new UserCollectModel())->where(['user_id' => $user_id, 'type' => 2, 'collect_id' => $lib_id])->delete();
            $lib_model->where(['id' => $lib_id])->setDec('collect_num');
            $lib_model->commit();
        } catch (Exception $e) {
            $lib_model->rollback();
            return json(['code' => 0, 'msg' => '操作失败,请刷新后重试']);
        }
        return json(['code' => 1, 'msg' => 'success']);
    }

}
