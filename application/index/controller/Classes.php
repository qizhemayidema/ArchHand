<?php

namespace app\index\controller;


use think\Controller;
use think\Db;
use think\Exception;
use think\facade\Session;
use think\Request;
use think\Validate;
use app\index\model\Classes as ClassModel;
use app\index\model\ClassesChapter as ChapterModel;
use app\index\model\ClassesCategory as CateModel;
use app\index\model\UserCollect as CollectModel;
use app\index\model\UserBuyHistory as UserBuyHistoryModel;
use app\index\model\Vip as VipModel;
use app\index\model\ClassesTagList as TagListModel;
use app\index\model\UserCollect as UserCollectModel;
use app\index\model\ClassesComment as ClassCommentModel;
use app\index\model\Classes as ClassesModel;
use app\index\model\ClassesCommentLikeHistory as CCLHModel;
use app\index\model\UserIntegralHistory;

class Classes extends Base
{

    public $classPageLength = 12;

    /**
     * 首页
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $cateModel = new CateModel();
        $classModel = new ClassModel();
        $cateInfo = $cateModel->select()->toArray();

        foreach ($cateInfo as $key => $value) {
            $cateInfo[$key]['class'] = $classModel->where(['is_delete' => 0])
                ->where(['cate_id' => $value['id']])
                ->order('id', 'desc')
                ->field('id,name,class_pic')
                ->limit(8)
                ->select()->toArray();
        }
        $this->assign('lists', $cateInfo);
        return $this->fetch();
    }

    /**
     * 课程列表页
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function list(Request $request)
    {
        $cate_id = $request->param('cate_id');

        $classModel = new ClassModel();

        $class = $classModel->where(['is_delete' => 0])
            ->where(['cate_id' => $cate_id]);
        $class_count = $class->count();
        $class = $class->order('id', 'desc')
            ->field('id,name,class_pic')
            ->limit(0, $this->classPageLength)
            ->select()->toArray();

        $cate_info = (new CateModel())->where(['id' => $cate_id])->field('id,cate_name')->find();


        $this->assign('class', $class);
        $this->assign('cate_info', $cate_info);
        $this->assign('class_count', $class_count);
        $this->assign('page_length', $this->classPageLength);

        return $this->fetch();
    }

    /**
     * 详情页
     * @param Request $request
     * @return mixed
     */
    public function info(Request $request)
    {
        $class_id = $request->param('class_id');

        try {
            //获取课程基本信息
            $classInfo = (new ClassModel())->where(['id' => $class_id, 'is_delete' => 0])
                ->field('id,name,see_num,learn_num,desc,class_pic,free_chapter,chapter_sum,integral,create_time,collect_num')
                ->find();
            //获取课程视频列表信息
            $chapterInfo = (new ChapterModel())->where(['class_id' => $class_id])
                ->field('id,title,pic,chapter_num')
                ->order('chapter_num')
                ->select()->toArray();
            //获取课程标签
            $taglist = (new TagListModel())->alias('list')
                ->join('class_tag tag', 'list.tag_id = tag.id')
                ->where(['list.class_id' => $class_id])
                ->field('tag.tag_img,tag.name')
                ->select();
            //如果用户登陆,获取是否收藏的信息  并且获取当前用户会员价格
            $classInfo['is_collect'] = false;
            $classInfo['is_buy'] = false;
            if (Session::has($this->userInfoSessionPath)) {
                $classInfo['is_collect'] = (new CollectModel())->where(['type' => 1, 'user_id' => $this->userInfo['id'], 'collect_id' => $class_id])
                    ->value('id') ? true : false;
                if ($this->userInfo['vip_id'] != 0) {
                    $vipInfo = (new VipModel())->where(['id' => $this->userInfo['vip_id']])->find();
                    $classInfo['vip_name'] = $vipInfo['vip_name'];
                    $classInfo['vip_integral'] = floor($classInfo['integral'] * ($vipInfo['discount'] / 10));
                    $classInfo['vip_discount'] = $vipInfo['discount'];
                }
                $classInfo['is_buy'] = (new UserBuyHistoryModel())
                    ->where(['user_id' => $this->userInfo['id'], 'type' => 2, 'buy_id' => $class_id])->find() ? true : false;
            }

            $this->assign('class_info', $classInfo);
            $this->assign('chapter_info', $chapterInfo);
            $this->assign('tag_list', $taglist);
            return $this->fetch();
        } catch (\Exception $e) {
            //
        }
    }

    /**
     * 购买课程
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\exception\PDOException
     */
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
                $pay_integral = floor($pay_integral * ($vip_info['discount'] / 10));
                if ($pay_integral <= 0) $pay_integral = 1;
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
            //增加学习人数
            (new ClassModel())->where(['id'=>$data['class_id']])->setInc('learn_num');
            $buy->commit();
        }catch (\Exception $e){
            $buy->rollback();
            return json(['code'=>0,'msg'=>'购买失败,请刷新后重试']);
//            return json(['code'=>0,'msg'=>$e->getMessage()]);
        }

        return json(['code'=>1,'msg'=>'success','pay_integral'=>$pay_integral]);
    }

    /**
     * 添加收藏 or 删除收藏
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\exception\PDOException
     */
    public function collect(Request $request)
    {
        $user_info = $this->userInfo;
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

        $collectModel->startTrans();
        try{
            if ($collectModel->where(['type'=>1,'collect_id'=>$data['class_id'],'user_id'=>$user_info['id']])->find()){
                (new UserCollectModel())->where(['user_id' => $user_info['id'], 'type' => 1, 'collect_id' => $data['class_id']])->delete();
                if((new ClassModel())->where(['id' => $data['class_id']])->value('collect_num')){
                    (new ClassModel())->where(['id' => $data['class_id']])->setDec('collect_num');
                }
            }else{
                $collectModel->insert([
                    'type'  => 1,
                    'collect_id' => $data['class_id'],
                    'user_id' => $user_info['id'],
                    'create_time' => time(),
                ]);
                (new ClassModel())->where(['id'=>$data['class_id']])->setInc('collect_num');
            }

            $collectModel->commit();
        }catch (\Exception $e){
            $collectModel->rollback();
            return json(['code'=>0,'msg'=>'操作失误,请刷新后重试']);
        }
        return json(['code'=>1,'msg'=>'success']);
    }


    /****************************************************************************/
    /**
     * 播放视频
     * @param Request $request
     * @return \think\response\Json
     */
    public function seeVideo(Request $request)
    {
        $data = $request->post();
        $rules = [
            'chapter_id' => 'require|number',
        ];
        $messages = [
            'chapter.id.require' => '缺少 chapter_id',
        ];
        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $user_info = $this->userInfo;
        try {
            //搜索文库的信息
            $chapter_info = (new ChapterModel())->where(['id' => $data['chapter_id']])->find();
            $class_id = $chapter_info['class_id'];
            $classInfo = (new ClassModel())->find($class_id);

            $this->assign('url', $chapter_info['source_url']);
            $html = $this->fetch('classes/play');

            if ($chapter_info['chapter_num'] > $classInfo['free_chapter']) {
                //查看用户是否购买过
                $buy = new UserBuyHistoryModel();
                $res = $buy->where(['user_id' => $user_info['id'], 'type' => 2, 'buy_id' => $class_id])->find();
                if (!$res) {
                    return json(['code' => 0, 'msg' => '请购买观看~']);
                }
            }
            (new ClassModel())->where(['id' => $class_id])->setInc('see_num');

            return json(['code' => 1, 'msg' => 'success', 'html' => $html,'chapter_name'=>$chapter_info['title']]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '播放出错,请联系网站管理员']);
        }
    }

    /**
     * 列表页翻页接口
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList(Request $request)
    {
        $data = $request->post();

        $rules = [
            'page' => 'require|number',
            'cate_id' => 'require|number',
        ];
        $validate = new Validate($rules);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        $start = $data['page'] * $this->classPageLength - $this->classPageLength;
        $classModel = new ClassModel();
        $class = $classModel->where(['is_delete' => 0])
            ->where(['cate_id' => $data['cate_id']])
            ->order('id', 'desc')
            ->field('id,name,class_pic')
            ->limit($start, $this->classPageLength)
            ->select()->toArray();

        $this->assign('class', $class);

        return json(['code' => 1, 'data' => $this->fetch('classes/class_list')]);

    }


    /****************************评论*********************************************/
    /**
     * 新增评论
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\exception\PDOException
     */
    public function saveComment(Request $request)
    {
        $user_info = $this->userInfo;
        $data = $request->post();
        $commentModel = new ClassCommentModel();
        $rules = [
            'content' => 'require',
            'class_id' => 'require',
        ];

        $messages = [
            'content.require' => '必须携带 content',
            'class_id.require' => '必须携带 class_id',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        //加载默认配置
        $config = \HTMLPurifier_Config::createDefault();
//       //设置白名单
//        $config->set('HTML.Allowed','p');
//       //实例化对象
        $purifier = new \HTMLPurifier($config);
        $data['content'] = $purifier->purify($data['content']);

        $commentModel->startTrans();
        try {
            $commentModel->insert([
                'user_id' => $user_info['id'],
                'create_time' => time(),
                'class_id' => $data['class_id'],
                'comment' => $data['content'],
            ]);
            //判断他是否加筑手币
            $day_count = $this->getConfig('comment_integral_count');
            $integral = $this->getConfig('comment_integral');
            $today_timestamp = strtotime(date('Y-m-d', time()));
            $count = (new UserIntegralHistory())
                ->where(['user_id' => $user_info['id'], 'type' => 6])
                ->where('create_time', '>', $today_timestamp)
                ->limit($day_count)->count();
            if ($count != $day_count) {
                $this->addUserIntegralHistory(6, $integral);
            }
            (new ClassesModel())->where(['id' => $data['class_id']])->setInc('comment_num');

            $commentModel->commit();
        } catch (\Exception $e) {
            $commentModel->rollback();
            return json(['code'=>0,'msg'=>'操作失败']);
        }

        return json(['code' => 1, 'msg' => 'success']);
    }

    /**
     * 获取评论列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function getComment(Request $request)
    {
        $data = $request->post();
        $rules = [
            'page' => 'require',
            'page_length' => 'require',
            'class_id' => 'require',
        ];

        $messages = [
            'page.require' => '参数携带 page',
            'page_length.require' => '参数携带 page_length',
            'class_id.require' => '参数携带 class_id'
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $start = $data['page'] * $data['page_length'] - $data['page_length'];

        $classCommentModel = new ClassCommentModel();
        $likeModel = new CCLHModel();

        $result = $classCommentModel->alias('comment')
            ->join('user user', 'comment.user_id = user.id')
            ->where(['comment.class_id' => $data['class_id'], 'comment.status' => 1])
            ->field('comment.id comment_id,comment.comment,comment.like_num,comment.create_time,user.nickname,user.avatar_url')
            ->order('comment.id', 'desc')->limit($start, $data['page_length'])
            ->select()->toArray();

        if ($request->param('token')) {
            foreach ($result as $key => $value) {
                $result[$key]['is_like'] = $likeModel->where(['comment_id' => $value['comment_id'], 'user_id' => $this->userInfo['id']])->value('comment_id') ? true : false;
            }
        }
        $count = $classCommentModel
            ->where(['class_id' => $data['class_id'], 'status' => 1])
            ->count();


        return json(['code' => 1, 'msg' => 'success', 'data' => $result, 'count' => $count]);
    }

    /**
     * 删除评论
     * @return \think\response\Json
     */
    public function removeComment()
    {
        $comment_id = request()->post('comment_id');
        if (!$comment_id) {
            return json(['code' => 0, 'msg' => '缺少comment_id']);
        }
        $commentModel = new ClassCommentModel();
        $commentModel->startTrans();
        try {
            $commentInfo = $commentModel->find($comment_id);
            if ($commentInfo['user_id'] != $this->userInfo['id']) throw new Exception('只能删除自己的评论');
            $res = $commentModel->where(['id' => $comment_id, 'user_id' => $this->userInfo['id'], 'status' => 1])->delete();
            if (!$res) throw new Exception('删除失败');
            if($commentInfo['comment_num'] > 0){
                (new ClassesModel())->where(['id' => $commentInfo['class_id']])->setDec('comment_num');
            }
            $commentModel->where(['id' => $comment_id])->delete();
            $commentModel->commit();
        } catch (\Exception $e) {
            $commentModel->rollback();
            return json(['code' => 0, 'msg' => '删除失败']);
        }
        return json(['code' => 1, 'msg' => 'success']);
    }


    /**
     * 点赞评论
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function like(Request $request)
    {
        $data = $request->post();
        $user_info = $this->userInfo;
        $rules = [
            'comment_id' => 'require',
        ];
        $messages = [
            'comment_id.require' => '请携带comment_id',
        ];

        $validate = new Validate($rules, $messages);
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }


        $commentModel = new ClassCommentModel();
        $likeModel = new CCLHModel();
        if ($likeModel->where(['comment_id' => $data['comment_id'], 'user_id' => $user_info['id']])->find()) {
            return json(['code' => 0, 'msg' => '您已经点过赞啦,去看看别的评论吧~']);
        }
        $commentModel->startTrans();
        try {
            $commentModel->where(['id' => $data['comment_id']])->setInc('like_num');
            $likeModel->insert([
                'comment_id' => $data['comment_id'],
                'user_id' => $user_info['id'],
            ]);
            $commentModel->commit();
        } catch (\Exception $e) {
            $commentModel->rollback();
            return json(['code' => 0, 'msg' => '操作失误']);
        }
        return json(['code' => 1, 'msg' => 'success']);
    }
}
