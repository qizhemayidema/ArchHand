<?php

namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;
use app\api\model\Classes as ClassModel;
use app\api\model\ClassesChapter as ChapterModel;
use app\api\model\ClassesCategory as CateModel;
use app\api\model\UserCollect as CollectModel;
use app\api\model\UserBuyHistory as UserBuyHistoryModel;
use app\api\model\Vip as VipModel;


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
    public function listMore(Request $request)
    {
        $data = $request->post();
        $rules = [
            'cate_id'       => 'require',
            'page'          => 'require|number',
            'page_length'   => 'require|number',
        ];
        $messages = [
            'cate_id.require'   => '必须携带 参数 cate_id',
            'page.require'      => '必须携带 参数 page',
            'page.number'       => 'page必须为数字',
            'page_length.require' => '必须携带 参数 page_length',
            'page_length.number' => 'page_length 必须为数字',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $classModel = new ClassModel();
        $start = $data['page'] * $data['page_length'] - $data['page_length'];
        $classModel = $classModel->where(['is_delete'=>0])
            ->where(['cate_id'=>$data['cate_id']]);
        $count = $classModel->count();

        $result = $classModel
            ->order('id','desc')
            ->field('id,name,class_pic')
            ->limit($start,$data['page_length'])
            ->select()->toArray();


        $cate_name = (new CateModel())->where(['id'=>$data['cate_id']])->value('cate_name');

        return json(['code'=>1,'data'=>$result,'count'=>$count,'cate_name'=>$cate_name]);
    }

    //课程详情页面
    public function info(Request $request)
    {
        $data = $request->post();
        $rules = [
            'class_id'      => 'require',
            'chapter_page'  => 'require',
            'chapter_page_length'   => 'require',
        ];

        $messages = [
            'class_id.require' => '请携带 class_id',
            'chapter_page.require' => '请携带 chapter_page',
            'chapter_page_length'   => '请携带 chapter_page_length',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        try{
            //获取课程基本信息
            $classInfo = (new ClassModel())->where(['id'=>$data['class_id'],'is_delete'=>0])
                ->field('name,see_num,learn_num,desc,class_pic,free_chapter,chapter_sum,integral')
                ->find();
            //获取课程视频列表信息
            $chapterInfo = (new ChapterModel())->where(['class_id'=>$data['class_id']])
                ->field('title,pic,chapter_num,source_url')
                ->order('chapter_num')
                ->select()->toArray();
            //如果用户登陆,获取是否收藏的信息  并且获取当前用户会员价格
            $classInfo['is_collect'] = false;
            if ($request->param('token')){
                $classInfo['is_collect'] = (new CollectModel())->where(['type'=>1,'user_id'=>$this->userInfo['id'],'collect_id'=>$data['class_id']])
                    ->value('id') ? true : false;
                if ($this->userInfo['vip_id'] != 0){
                    $vipInfo = (new VipModel())->where(['id'=>$this->userInfo['vip_id']])->find();
                    $classInfo['vip_integral'] = floor( $classInfo['integral'] * $vipInfo['discount']);
                    $classInfo['vip_discount'] = $vipInfo['discount'];
                }
            }
            foreach ($chapterInfo as $key => $value){
                if ($key + 1 > $classInfo['free_chapter']){
                    unset($chapterInfo[$key]['source_url']);
                }
            }
            //免费的能看
            $result = [
                'class_info' => $classInfo,
                'chapter_info' => $chapterInfo,
            ];

            return json(['code'=>1,'data'=>$result]);
        }catch (Exception $e){
            return json(['code'=>0,'msg'=>'获取失败']);
        }
    }

    //播放
    public function seeVideo(Request $request)
    {
        $data = $request->post();
        $rules = [
            'chapter_id'    => 'require',
        ];
        $messages = [
            'chapter.id.require'    => '缺少 chapter_id',
        ];
        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        $user_info = $this->userInfo;
        try{
            //搜索文库的信息
            $chapter_info = (new ChapterModel())->where(['id'=>$data['chapter_id']])->find();
            $class_id = $chapter_info['class_id'];
            $classInfo = (new ClassModel())->find($class_id);
            if ($chapter_info['chapter_num'] <= $classInfo['free_chapter']){
                return json(['code'=>1,'msg'=>'success','url'=>$classInfo['source_url']]);
            }
            //查看用户是否购买过
            $buy = new UserBuyHistoryModel();
            $res = $buy->where(['user_id'=>$user_info['id'],'type'=>2,'buy_id'=>$class_id])->find();
            if (!$res){
                return json(['code'=>0,'msg'=>'请购买观看~']);
            }
            return json(['code'=>1,'msg'=>'success','url'=>$classInfo['source_url']]);
        }catch(Exception $e){
            return json(['code'=>0,'msg'=>'播放出错,请联系网站管理员']);
        }
    }

    //购买课程
    public function buy(Request $request)
    {
        $data = $request->post();
        $rules = [
            'class_id'  => 'require',
        ];
        $messages = [
            'class_id.require'  => '必须携带 class_id',
        ];
        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $user_info = $this->userInfo;
        $buy = new UserBuyHistoryModel();
        $classModel = new ClassModel();
        $vipModel = new VipModel;
        $buy->startTrans();
        try{
            //课程是否存在
            $classInfo = $classModel->where(['id' => $data['class_id'],'is_delete'=>0])->find();
            //用户是否购买过
            $user_buy_history = $buy->where(['user_id' => $user_info['id'],'type'=>2,'buy_id'=>$data['class_id']])->find();
            if ($user_buy_history){
                return json(['code'=>0,'msg'=>'不能重复购买哦~']);
            }
            //检查用户账户余额
            $user_integral = $user_info['integral'];
            //计算实际付款金额
            $pay_integral = $classInfo['integral'];
            if ($user_info['vip_id'] != 0){
                $vip_info = $vipModel->where(['id'=>$user_info['vip_id']])->find();
                $pay_integral = floor($pay_integral * $vip_info['discount']);
            }
            if ($user_integral < $pay_integral){
                return json(['code'=>0,'msg'=>'您的筑手币不足,请及时充值']);
            }
            //用户购买记录表
            $buy->insert([
                'type'  => 2,
                'buy_id'=> $data['class_id'],
                'user_id' => $user_info['id'],
                'integral'=> $pay_integral,
                'create_time' => time(),
            ]);
            //用户积分变动表   用户自己的表 金额减少
            $this->addUserIntegralHistory(3,$pay_integral);
            $buy->commit();
        }catch (Exception $e){
            $buy->rollback();
            return json(['code'=>0,'msg'=>'购买失败,请刷新后重试']);
//            return json(['code'=>0,'msg'=>$e->getMessage()]);
        }

        return json(['code'=>1,'msg'=>'success']);
    }

    //添加收藏
    public function collect(Request $request)
    {
        $data = $request->post();
        $rules = [
            'class_id'  => 'require',
        ];
        $messages = [
            'class_id.require'  => '必须携带 class_id',
        ];
        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        $collectModel = new CollectModel();
        if ($collectModel->where(['type'=>1,'collect_id'=>$data['class_id']])->find()){
            return json(['code'=>0,'msg'=>'收藏过就不能再次收藏了哦']);
        }
        $collectModel->startTrans();
        try{
            $collectModel->insert([
                'type'  => 1,
                'collect_id' => $data['class_id'],
                'user_id' => $this->userInfo['id'],
                'create_time' => time(),
            ]);
            (new ClassModel())->where(['id'=>$data['class_id']])->setInc('collect_num');
            $collectModel->commit();
        }catch (Exception $e){
            $collectModel->rollback();
            return json(['code'=>0,'msg'=>'操作失误,请刷新后重试']);
        }
        return json(['code'=>1,'msg'=>'success']);
    }
}
