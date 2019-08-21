<?php

namespace app\api\controller;

use think\Request;
use app\api\model\UserCollect as UserCollectModel;
use app\api\model\UserBuyHistory as UserBuyHistoryModel;
use app\api\model\ClassesComment as ClassCommentModel;
use think\Validate;


class MyClass extends Base
{
    //我的评论记录
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
        $comment = (new ClassCommentModel())->where(['user_id'=>$this->userInfo['id']]);

        $commentList = $comment->field('comment,status,create_time')->order('id','desc')
            ->limit($start,$data['page_length'])
            ->select();

        $count = $comment->count();

        return json(['code'=>1,'data'=>$commentList,'count'=>$count]);

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
            ->where(['buy.type'=>2])
            ->where(['buy.user_id'=>$this->userInfo['id']]);
        $count =$userBuy->count();
        $buyInfo = $userBuy->join('class class','class.id = buy.buy_id')
            ->field('class.id class_id,class.name,class.class_pic,class.free_chapter,class.chapter_sum')
            ->field('buy.integral,buy.create_time')
            ->order('buy.id','desc')
            ->limit($start,$data['page_length'])
            ->select();

        return json(['code'=>1,'data'=>$buyInfo,'count'=>$count]);

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
        $userCollect = (new UserCollectModel())->alias('collect')
            ->where(['collect.type'=>1])
            ->where(['collect.user_id'=>$this->userInfo['id']]);
        $count =$userCollect->count();
        $collectInfo = $userCollect->join('class class','class.id = collect.collect_id')
            ->field('class.id class_id,class.name,class.class_pic,class.free_chapter,class.chapter_sum')
            ->field('collect.create_time')
            ->order('collect.id','desc')
            ->limit($start,$data['page_length'])
            ->select();

        return json(['code'=>1,'data'=>$collectInfo,'count'=>$count]);

    }


}
