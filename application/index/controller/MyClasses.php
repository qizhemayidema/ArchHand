<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\UserCollect as UserCollectModel;
use app\index\model\UserBuyHistory as UserBuyHistoryModel;
use app\index\model\ClassesComment as ClassCommentModel;
use think\Validate;

class MyClasses extends Base
{

    public $pageLength = 10;

    public function index()
    {
        $userBuy = (new UserBuyHistoryModel())->alias('buy')
            ->where(['buy.type'=>2])
            ->where(['buy.user_id'=>$this->userInfo['id']]);
        $buyCount =$userBuy->count();
        $buyInfo = $userBuy->join('class class','class.id = buy.buy_id')
            ->field('class.id class_id,class.name,class.class_pic,class.free_chapter,class.chapter_sum')
            ->field('buy.integral,buy.create_time')
            ->order('buy.id','desc')
            ->limit(0,$this->pageLength)
            ->select();

        $userCollect = (new UserCollectModel())->alias('collect')
            ->where(['collect.type'=>1])
            ->where(['collect.user_id'=>$this->userInfo['id']]);
        $collectCount =$userCollect->count();
        $collectInfo = $userCollect->join('class class','class.id = collect.collect_id')
            ->field('class.id class_id,class.name,class.class_pic,class.free_chapter,class.chapter_sum')
            ->field('collect.create_time')
            ->order('collect.id','desc')
            ->limit(0,$this->pageLength)
            ->select();

        $this->assign('buy_count',$buyCount);
        $this->assign('buy_page_length',1);
        $this->assign('buy_info',$buyInfo);
        $this->assign('collect_count',$collectCount);
        $this->assign('collect_page_length',1);
        $this->assign('collect_info',$collectInfo);
        $this->assign('page_length',$this->pageLength);

        return $this->fetch();
    }

    //我的购买记录
    public function getBuyList(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page'      => 'require',
        ];

        $messages = [
            'page.require'        => 'page必须携带',
        ];
        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        $start = $data['page'] * $this->pageLength - $this->pageLength;
        $userBuy = (new UserBuyHistoryModel())->alias('buy')
            ->where(['buy.type'=>2])
            ->where(['buy.user_id'=>$this->userInfo['id']]);
        $count =$userBuy->count();
        $buyInfo = $userBuy->join('class class','class.id = buy.buy_id')
            ->field('class.id class_id,class.name,class.class_pic,class.free_chapter,class.chapter_sum')
            ->field('buy.integral,buy.create_time')
            ->order('buy.id','desc')
            ->limit($start,$this->pageLength)
            ->select();

        $this->assign('buy_info',$buyInfo);


        return json(['code'=>1,'data'=>$this->fetch('my_classes/buy_list'),'count'=>$count]);

    }

    //我的收藏记录
    public function getCollectList(Request $request)
    {

        $data = $request->post();
        $rules = [
            'page'      => 'require',
        ];

        $messages = [
            'page.require'        => 'page必须携带',
        ];
        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        $start = $data['page'] * $this->pageLength - $this->pageLength;
        $userCollect = (new UserCollectModel())->alias('collect')
            ->where(['collect.type'=>1])
            ->where(['collect.user_id'=>$this->userInfo['id']]);
        $count =$userCollect->count();
        $collectInfo = $userCollect->join('class class','class.id = collect.collect_id')
            ->field('class.id class_id,class.name,class.class_pic,class.free_chapter,class.chapter_sum')
            ->field('collect.create_time')
            ->order('collect.id','desc')
            ->limit($start,$this->pageLength)
            ->select();

        $this->assign('collect_info',$collectInfo);

        return json(['code'=>1,'data'=>$this->fetch('my_classes/collect_list'),'count'=>$count]);



    }
}
