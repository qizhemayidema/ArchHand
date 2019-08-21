<?php

namespace app\api\controller;

use think\Controller;
use think\Request;
use think\Validate;
use app\api\model\Library as LibraryModel;
use app\api\model\LibraryComment as LibraryCommentModel;
use app\api\model\UserDownloadLibraryHistory as UDLHModel;
use app\api\model\UserCollect as UserCollectModel;
use app\api\model\UserBuyHistory as UserBuyHistoryModel;

class MyLibrary extends Base
{
    //我的发布
    public function myPublish(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page'      => 'require',
            'page_length' => 'require',
            'status'    => 'require|integer',
        ];

        $messages = [
            'page.require'        => 'page必须携带',
            'page_length.require' => 'page_length必须携带',
            'status.require'      => 'status必须携带',
            'status.integer'       => 'status 必须为数字',
        ];
        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        $user_id = $this->userInfo['id'];
        $start = $data['page'] * $data['page_length'] - $data['page_length'];

        //如果是未通过 说明未通过原因
        if ($data['status'] == -1){
            $list = (new LibraryModel())->alias('library')
                    ->join('library_check_history history','history.library_id = library.id')
                    ->where(['library.user_id'=>$user_id,'library.status'=>$data['status']])
                ->field('library.name,library.see_num,library.name_status,library.library_pic,library.like_num,library.collect_num,library.comment_num,library.create_time,library.is_original,library.is_classics')
                ->field('history.because')
                ->where(['library.is_delete'=>1])
                ->order('library.id','desc')
                ->limit($start,$data['page_length'])
                ->select();
        }else{
            $list = (new LibraryModel())->where(['user_id'=>$user_id,'status'=>$data['status']])
                ->field('name,see_num,name_status,library_pic,like_num,collect_num,comment_num,create_time,is_original,is_classics')
                ->where(['is_delete'=>1])
                ->order('id','desc')
                ->limit($start,$data['page_length'])
                ->select();
        }

        $count = (new LibraryModel())->where(['user_id'=>$user_id,'status'=>$data['status'],'is_delete'=>1])->count();


        return json(['code'=>1,'data'=>$list,'count'=>$count]);
    }

    //我的评论
    public function myComment(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page'      => 'require',
            'page_length' => 'require',
        ];

        $messages = [
            'page.require'        => 'page必须携带',
            'page_length.require' => 'page_length必须携带',
        ];
        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        $start = $data['page'] * $data['page_length'] - $data['page_length'];
        $comment = (new LibraryCommentModel())->where(['user_id'=>$this->userInfo['id']]);

        $commentList = $comment->field('comment,status,create_time')->order('id','desc')
                        ->limit($start,$data['page_length'])
                        ->select();

        $count = $comment->count();

        return json(['code'=>1,'data'=>$commentList,'count'=>$count]);

    }

    //我的下载
    public function myDownload(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page'      => 'require',
            'page_length' => 'require',
        ];

        $messages = [
            'page.require'        => 'page必须携带',
            'page_length.require' => 'page_length必须携带',
        ];
        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $start = $data['page'] * $data['page_length'] - $data['page_length'];

        $download = (new UDLHModel())->alias('download')->where(['download.user_id'=>$this->userInfo['id']]);
        $count = $download->count();
        $downloadList = $download->join('library library','download.library_id = library.id')
                        ->field('library.name,library.id,download.create_time')
                        ->order('download.id','desc')
                        ->limit($start,$data['page_length'])
                        ->select();

        return json(['code'=>1,'data'=>$downloadList,'count'=>$count]);


    }

    //我的收藏记录
    public function myCollect(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page'      => 'require',
            'page_length' => 'require',
        ];

        $messages = [
            'page.require'        => 'page必须携带',
            'page_length.require' => 'page_length必须携带',
        ];
        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $start = $data['page'] * $data['page_length'] - $data['page_length'];

        $collect = (new UserCollectModel())->alias('collect')->where(['collect.user_id'=>$this->userInfo['id']])
                ->where(['type'=>'2']);
        $count = $collect->count();
        $collectList = $collect->join('library library','collect.collect_id = library.id')
            ->field('library.name,library.id,collect.create_time')
            ->order('collect.id','desc')
            ->limit($start,$data['page_length'])
            ->select();
        return json(['code'=>1,'data'=>$collectList,'count'=>$count]);
    }

    //我的购买记录
    public function myBuy(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page'      => 'require',
            'page_length' => 'require',
        ];

        $messages = [
            'page.require'        => 'page必须携带',
            'page_length.require' => 'page_length必须携带',
        ];
        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        $start = $data['page'] * $data['page_length'] - $data['page_length'];
        $userBuy = (new UserBuyHistoryModel())->alias('buy')
            ->where(['buy.type'=>1])
            ->where(['buy.user_id'=>$this->userInfo['id']]);
        $count =$userBuy->count();
        $buyInfo = $userBuy->join('library library','library.id = buy.buy_id')
            ->field('library.id library_id,library.name,library.library_pic')
            ->field('buy.integral,buy.create_time')
            ->order('buy.id','desc')
            ->limit($start,$data['page_length'])
            ->select();

        return json(['code'=>1,'data'=>$buyInfo,'count'=>$count]);

    }
}
