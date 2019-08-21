<?php

namespace app\api\controller;

use app\api\model\ClassesComment as ClassCommentModel;
use app\api\model\Library as LibraryModel;
use app\api\model\LibraryComment as LibraryCommentModel;
use app\api\model\Classes as ClassesModel;
use think\Controller;
use think\Exception;
use think\Request;

class Comment extends Base
{


    public function removeLibrary()
    {
        $comment_id = request()->post('comment_id');
        if (!$comment_id){ return json(['code'=>0,'msg'=>'缺少comment_id']);}
        $commentModel = new LibraryCommentModel();
        try{
            $commentInfo = $commentModel->find($comment_id);
            if($commentInfo['user_id'] != $this->userInfo['id']) throw new Exception('只能删除自己的评论');
            $res = $commentModel->where(['id'=>$comment_id,'user_id'=>$this->userInfo['id'],'status'=>1])->delete();
            if(!$res) throw new Exception('删除失败');
            (new LibraryModel())->where(['id'=>$commentInfo['library_id']])->setDec('comment_num');
        }catch (Exception $e){
            return json(['code'=>0,'msg'=>'删除失败']);
        }

        return json(['code'=>1,'msg'=>'success']);
    }

    public function removeClass()
    {
        $comment_id = request()->post('comment_id');
        if (!$comment_id){ return json(['code'=>0,'msg'=>'缺少comment_id']);}
        $commentModel = new ClassCommentModel();
        try{
            $commentInfo = $commentModel->find($comment_id);
            if($commentInfo['user_id'] != $this->userInfo['id']) throw new Exception('只能删除自己的评论');
            $res = $commentModel->where(['id'=>$comment_id,'user_id'=>$this->userInfo['id'],'status'=>1])->delete();
            if(!$res) throw new Exception('删除失败');
//            (new ClassesModel())->where(['id'=>$commentInfo['class_id']])->setDec('comment_num');
        }catch (Exception $e){
            return json(['code'=>0,'msg'=>'删除失败']);
        }

        return json(['code'=>1,'msg'=>'success']);
    }


}
