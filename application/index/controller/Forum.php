<?php

namespace app\index\controller;

use app\common\model\Common;
use think\Exception;
use think\facade\Session;
use think\Request;
use think\Validate;
use app\index\model\ForumPlate as PlateModel;
use app\index\model\ForumCategory as CateModel;
use app\index\model\Forum as ForumModel;
use app\index\model\ForumManager as ForumManagerModel;
use app\index\model\User as UserModel;
use app\index\model\ForumComment as ForumCommentModel;
use app\index\model\UserCollect as UserCollectModel;
use app\index\model\ForumLikeHistory as LikeHistoryModel;
use app\index\model\ForumCommentLikeHistory as CommentLikeHistoryModel;
use app\index\model\UserIntegralHistory as UserIntegralHistoryModel;
use app\index\model\ForumApplyForManager as ApplyModel;
use app\common\controller\UploadPic as UploadPic;


class Forum extends Base
{

    public $commentPageLength = 8;

    //发布页面
    public function add()
    {

        $cate = $plate = [];
        $this->assign('cate', $cate);
        $this->assign('plate', $plate);
        return $this->fetch();
    }

    /**
     * 发布一个帖子
     * @param Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {


        $data =$request->post();
        $user_info = $this->userInfo;

        $rules = [
            'plate_id' => 'require|number',
            'name'      => 'require|max:50',
            'pic'       => 'require',
            'tag_str'   => 'max:250',
            'content'   => 'require',
            'is_original' => 'number',
            'desc'      => 'require|max:100',
            '__token__'     => 'token'

        ];

        $messages = [
            'plate_id.require'  => '必须选择板块',
            'name.require'      => '必须填写标题',
            'name.max'          => '标题最大长度50字',
            'pic.require'       => '封面必须上传',
            'tag_str.max'       => '标签最大长度为250字',
            'content.require'   => '内容必须填写',
            'desc.require'      => '介绍必须填写',
            'desc.max'          => '介绍最大长度为100字',
            'is_original.number'=> '非法',
            '__token__.token'   => '请刷新页面后重新提交'
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        //查询所属分类id
        $plateInfo = (new PlateModel())->find($data['plate_id']);
        if (!$plateInfo || $plateInfo['is_delete'] == 1)
            return json(['code'=>0,'msg'=>'所选板块不存在']);

        $expTagStr = explode('，',$data['tag_str']);
        if (count($expTagStr) > 5){
            return json(['code'=>0,'msg'=>'标签最多只能有五个']);
        }

        foreach ($expTagStr as $key => $value){
            if (strlen($value) > 30){
                return json(['code'=>0,'msg'=>'每个标签最多长度为30']);
            }
        }

        $result = [];

        if (!in_array($data['is_original'],[0,1])){
            return json(['code'=>0,'msg'=>'非法']);
        }
//        if (!file_exists('.' . $data['pic'])){
//            return json(['code'=>0,'msg'=>'封面不存在']);
//        }
        $pic_temp = (new UploadPic())->uploadBase64Pic($data['pic'],'forum/'.$user_info['id'].'/');
        if ($pic_temp['code'] == 0){
            return json(['code'=>0,'msg'=>'上传失败']);
        }

        //加载默认配置
        $config = \HTMLPurifier_Config::createDefault();
        //实例化对象
        $purifier = new \HTMLPurifier($config);
        //过滤
        $result['content'] = $purifier->purify($data['content']);

        $result['cate_id'] = $plateInfo['cate_id'];
        $result['plate_id'] = htmlentities($plateInfo['id']);
        $result['name'] = htmlentities($data['name']);
        $result['pic'] = $pic_temp['msg'];
        $result['tag_str'] = htmlentities($data['tag_str']);
        $result['user_id'] = $user_info['id'];
        $result['create_time'] = time();
        $result['desc'] = htmlentities($data['desc']);

        $forumModel = new ForumModel();
        $forumModel->startTrans();
        try{
            $forumModel->insert($result);
            (new PlateModel())->where(['id'=>$plateInfo['id']])->setInc('forum_num');

            //判断是否加筑手币
            $today = strtotime(date('Y-m-d',time()));

            $count = $this->getConfig('issue_integral_count');
            $onceIntegral = $this->getConfig('issue_integral');

            $today_count = (new UserIntegralHistoryModel())
                ->where(['user_id' => $user_info['id'], 'type' => 8])
                ->where('create_time', '>', $today)
                ->limit($count)->count();

            if ($today_count != $count){
                //如果加 筑手币  需要录入积分变动记录
                $this->addUserIntegralHistory(8,$onceIntegral);
            }

            $forumModel->commit();
        }catch (\Exception $e){
            $forumModel->rollback();
            return json(['code'=>0,'msg'=>$e->getMessage().$e->getLine()]);
        }
        return json(['code'=>1,'msg'=>'success']);
    }

    /**
     * 社区详情页
     * @param Request $request
     */
    public function info(Request $request)
    {
        $forum_id = $request->param('forum_id') ?? 1;

        $forum_info = (new ForumModel())->alias('forum')
            ->where(['id'=>$forum_id,'is_delete'=>0])->find();
        if (!$forum_info) return json(['code'=>0,'msg'=>'该帖子不存在']);
        $forum_info->setInc('see_num');


        $is_collect = false;
        $is_like = false;
        $is_manager = false;
        //是否板块管理者
        if(Session::has($this->userInfoSessionPath)){
            $user_info = $this->userInfo;
            //只查看 是否是管理组成员 给个flag
            if ((new ForumManagerModel())->where(['plate_id'=>$forum_info['plate_id'],'user_id'=>$user_info])->find()){
                $is_manager = true;
            }
            //查询是否点赞 和 是否 收藏
            if ((new UserCollectModel())->where(['user_id'=>$user_info['id'],'type'=>3,'collect_id'=>$forum_info['id']])->find()){
                $is_collect = true;
            }
            if ((new LikeHistoryModel())->where(['user_id'=>$user_info['id'],'forum_id'=>$forum_info['id']])->find()){
                $is_like = true;
            }
        }
        //文章内容
        $forum = [
            'id'        => $forum_info['id'],
            'name'      => $forum_info['name'],
            'is_classics' => $forum_info['is_classics'],
            'pic'         => $forum_info['pic'],
            'desc'        => $forum_info['desc'],
            'is_top'      => $forum_info['is_top'],
            'is_original' => $forum_info['is_original'],
            'tag_str'     => $forum_info['tag_str'],
            'content'     => $forum_info['content'],
            'collect_num'   => $forum_info['collect_num'],
            'comment_num'   => $forum_info['comment_num'],
            'like_num'      => $forum_info['like_num'],
            'see_num'       => $forum_info['see_num'],
            'create_time'   => $forum_info['create_time'],
            'user_name'     => (new UserModel())->where(['id'=>$forum_info['user_id']])->value('nickname'),
            'is_like'       => $is_like,
            'is_collect'    => $is_collect,
        ];

        //评论
        $comment = (new ForumCommentModel())->alias('comment')
            ->join('user user','user.id = comment.user_id')
            ->where(['comment.status'=>1])
            ->where(['comment.forum_id'=>$forum['id']]);
        if (Session::has($this->userInfoSessionPath)){
            $comment = $comment->leftJoin('forum_comment_like_history like','like.comment_id = comment.id and like.user_id = '.$user_info['id'])
                ->field('like.comment_id is_like');
        }
         $comment = $comment->order('comment.create_time')
            ->field('comment.id,user.avatar_url,user.nickname,comment.content,comment.create_time,comment.like_num');
        $comment_count = $comment->count();

        $comment = $comment->limit(0,$this->commentPageLength)->select();

        //热门
        $hot_forum = (new ForumModel())->where(['is_delete'=>0])->order('see_num','desc')
            ->field('name,pic,id')->limit(8)->select();

        $this->assign('forum',$forum);
        $this->assign('comment',$comment);
        $this->assign('comment_count',$comment_count);
        $this->assign('hot_forum',$hot_forum);
        $this->assign('is_manager',$is_manager);
        $this->assign('page_length',$this->commentPageLength);

//        echo "<pre>";
////        print_r($forum);
////        print_r($comment);
////        print_r($comment_count);
//        print_r($hot_forum);
//        print_r($is_manager);
//        die;

//        return json(['code'=>1,'data'=>[
//            'forum' => $forum,
//            'comment' => $comment,
//            'comment_count' => $comment_count,
//            'hot_forum' =>$hot_forum,
//            'is_manager' => $is_manager,
//        ]]);

        return $this->fetch();
    }

    /**
     * 获取社区详情评论
     * @param Request $request
     * @return \think\response\Json
     */
    public function getComment(Request $request)
    {
        $data = $request->post();
        $rules = [
            'forum_id' => 'require|number',
            'page'  => 'require|number',
        ];
        $messages = [
            'forum_id.require' => 'require',
            'page.require'  => 'require',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>'error']);
        }
        $page = $data['page'];
        $forum_id = $data['forum_id'];

        //评论
        $start = $page * $this->commentPageLength - $this->commentPageLength;
        try{
            $comment = (new ForumCommentModel())->alias('comment')
                ->join('user user','user.id = comment.user_id')
                ->where(['comment.status'=>1])
                ->where(['comment.forum_id'=>$forum_id]);
            if (Session::has($this->userInfoSessionPath)){
                $comment = $comment->leftJoin('forum_comment_like_history like','like.comment_id = comment.id and like.user_id = '.$this->userInfo['id'])
                    ->field('like.comment_id is_like');
            }
            $comment = $comment->order('comment.create_time')
                ->field('comment.id,user.avatar_url,user.nickname,comment.content,comment.create_time,comment.like_num')
                ->limit($start,$this->commentPageLength)->select();
        }catch (\Exception $e){
            $comment = [];
        }
        $this->assign('comment',$comment);
        $this->assign('floor_start',$start);

        return json(['code'=>1,'html'=>$this->fetch('forum/comment_list')]);
    }

    /**
     * 申请加入管理团队
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function joinInManager(Request $request)
    {

        $data = $request->post();

        $rules = [
            'plate_id'  => 'require|number',
            'content'   => 'require|max:300',
        ];

        $messages = [
            'content.require'   => '原因不能为空',
            'content.max'       => '原因最大长度为300',
        ];

        $validate = new Validate($rules,$messages);

        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $user_info = $this->userInfo;
        //判断模块是否存在
        $plateInfo = (new PlateModel())->where(['id'=>$data['plate_id'],'is_delete'=>0])->find();
        if(!$plateInfo){
            return json(['code'=>0,'msg'=>'该板块不存在']);
        }
        if ($plateInfo['is_allow_join_manager'] == 0){
            return json(['code'=>0,'msg'=>'该板块暂时关闭申请功能~']);
        }

        //判断是否为此板块管理员
        $manager = (new ForumManagerModel())->where(['user_id'=>$user_info['id'],'plate_id'=>$data['plate_id']])->find();
        if ($manager){
            return json(['code'=>0,'msg'=>'您已经存在于管理团队中']);
        }

        //如果以前申请过 还未处理 不让他申请
        $status = (new ApplyModel())->where(['user_id'=>$user_info['id'],'plate_id'=>$data['plate_id'],'status'=>0])->find();
        if ($status){
            return json(['code'=>0,'msg'=>'您之前申请的还被未处理,请耐心等待']);
        }
        //入库
        $result = [
            'plate_id'  => $data['plate_id'],
            'user_id'   => $user_info['id'],
            'apply_for_desc' => $data['content'],
            'status'    => 0,
            'create_time' => time(),
        ];

        (new ApplyModel())->insert($result);
        return json(['code'=>1,'msg'=>'申请成功,请耐心等待审核结果']);
    }

    /**
     * 评论接口
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function comment(Request $request)
    {
        $data = $request->post();

        $rules = [
            'forum_id'  => 'require|number',
            'comment'   => 'require',
            'captcha'   => 'captcha',
        ];

        $messages = [
            'comment.require'   => '评论不能为空',
            'captcha.captcha'   => '验证码不正确',
        ];

        $validate = new Validate($rules,$messages);

        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $user_info = $this->userInfo;

        //判断帖子是否存在
        $forumInfo = (new ForumModel())->where(['id'=>$data['forum_id'],'is_delete'=>0])->find();
        if (!$forumInfo){
            return json(['code'=>0,'msg'=>'评论的帖子不存在']);
        }

        //加载默认配置
        $config = \HTMLPurifier_Config::createDefault();
//       //设置白名单
//        $config->set('HTML.Allowed','p');
//       //实例化对象
        $purifier = new \HTMLPurifier($config);
        $data['comment'] = $purifier->purify($data['comment']);

        $forumModel = new ForumModel();
        $forumModel->startTrans();

        try{
            //入表
            $forumComment = [
                'user_id' => $user_info['id'],
                'forum_id' => $data['forum_id'],
                'content'   => $data['comment'],
                'create_time' => time(),
            ];

            (new ForumCommentModel())->insert($forumComment);

            //判断是否加筑手币
            $today = strtotime(date('Y-m-d',time()));
            $count = $this->getConfig('comment_integral_count');
            $onceIntegral = $this->getConfig('comment_integral');

            $today_count = (new UserIntegralHistoryModel())
                ->where(['user_id' => $user_info['id'], 'type' => 7])
                ->where('create_time', '>', $today)
                ->limit($count)->count();

            if ($today_count != $count){
                //如果加 筑手币  需要录入积分变动记录
                $this->addUserIntegralHistory(7,$onceIntegral);
            }

            //帖子 评论数量 + 1
            $forumInfo->setInc('comment_num');

            //板块评论数量 + 1
            (new PlateModel())->where(['id'=>$forumInfo['plate_id']])->setInc('comment_num');

            $forumModel->commit();
        }catch (\Exception $e){
            $forumModel->rollback();
            return json(['code'=>0,'msg'=>'操作失败']);
        }
        return json(['code'=>1,'msg'=>'success']);
    }

    /**
     * 给评论点赞
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function likeComment(Request $request)
    {
        $data = $request->post();

        $rules = [
            'forum_comment_id'  => 'require|number',
        ];

        $validate = new Validate($rules);

        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $user_info = $this->userInfo;

        $commentLikeHistoryModel = new CommentLikeHistoryModel();
        $comment_info =(new ForumCommentModel())->find($data['forum_comment_id']);
        if (!$comment_info){
            return json(['code'=>0,'msg'=>'该评论不存在']);
        }
        $like = $commentLikeHistoryModel
            ->where(['comment_id'=>$data['forum_comment_id'],'user_id'=>$user_info['id']])
            ->find();
        if ($like){
            return json(['code'=>0,'msg'=>'您已经点过赞了,不能重复点赞哦~']);
        }
        $commentLikeHistoryModel->startTrans();
        try{
            (new CommentLikeHistoryModel())->insert([
                'user_id' => $user_info['id'],
                'comment_id' =>  $data['forum_comment_id'],
            ]);
            $comment_info->setInc('like_num');
            $commentLikeHistoryModel->commit();
        }catch (\Exception $e){
            $commentLikeHistoryModel->rollback();
            return json(['code'=>0,'msg'=>'操作失败']);
        }

        return json(['code'=>1,'msg'=>'success']);
    }

/*********************************************************************************/
    //收藏
    public function collect(Request $request)
    {
        $data = $request->post();

        $rules = [
            'forum_id'  => 'require|number',
        ];

        $validate = new Validate($rules);

        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $user_info = $this->userInfo;

        $userCollectModel = new UserCollectModel();
        $forum_info = (new ForumModel())->where(['is_delete'=>0,'id'=>$data['forum_id']])
            ->field('id,collect_num')->find();
        if (!$forum_info){
            return json(['code'=>0,'msg'=>'该帖不存在']);
        }
        $collect = $userCollectModel
            ->where(['user_id'=>$user_info['id'],'collect_id'=>$data['forum_id'],'type'=>3])
            ->find();
        $userCollectModel->startTrans();
        try{
            if ($collect){
                $collect->delete();
                if ($forum_info['collect_num'] > 0){
                    $forum_info->setDec('collect_num');
                }
            }else{
                $userCollectModel->insert([
                    'type'  => 3,
                    'user_id' => $user_info['id'],
                    'collect_id' =>  $data['forum_id'],
                    'create_time' => time(),
                ]);
                $forum_info->setInc('collect_num');
            }
            $userCollectModel->commit();
        }catch (\Exception $e){
            $userCollectModel->rollback();
            return json(['code'=>0,'msg'=>'操作失败']);
        }
        return json(['code'=>1,'msg'=>'success']);
    }

    //点赞
    public function like(Request $request)
    {
        $data = $request->post();

        $rules = [
            'forum_id'  => 'require|number',
        ];

        $validate = new Validate($rules);

        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $user_info = $this->userInfo;

        $likeHistoryModel = new LikeHistoryModel();
        $forum_info = (new ForumModel())->where(['is_delete'=>0,'id'=>$data['forum_id']])
            ->field('id,like_num')->find();
        if (!$forum_info){
            return json(['code'=>0,'msg'=>'该帖不存在']);
        }
        $like = $likeHistoryModel
            ->where(['user_id'=>$user_info['id'],'forum_id'=>$data['forum_id']])
            ->find();
        if ($like){
            return json(['code'=>0,'msg'=>'您已经点过赞了,不能重复点赞哦~']);
        }
        $likeHistoryModel->startTrans();
        try{
            (new LikeHistoryModel())->insert([
                'user_id' => $user_info['id'],
                'forum_id' =>  $data['forum_id'],
                'create_time' => time(),
            ]);
            $forum_info->setInc('like_num');
            $likeHistoryModel->commit();
        }catch (\Exception $e){
            $likeHistoryModel->rollback();
            return json(['code'=>0,'msg'=>'操作失败']);
        }

        return json(['code'=>1,'msg'=>'success']);
    }

    /**
     * 获取所有分类
     * @return \think\response\Json
     */
    public function getAllPlate()
    {
        return json(['code' => 1, 'data' => (new PlateModel())->getList()]);
    }

    /**
     * 获取某个分类下的板块
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPlateForCate(Request $request)
    {
        $cate_id = $request->post('cate_id');
        if (!$cate_id) return json(['code' => 0, 'msg' => '请携带cate_id']);
        $data = (new PlateModel())->where(['cate_id' => $cate_id, 'is_delete' => 0])->field('id,plate_name')->select();

        return json(['code' => 1, 'data' => $data]);
    }

    /**
     * 富文本图片上传
     * @return \think\response\Json
     */
    public function uploadContentPic()
    {
        $this->getUserInfo();
        $user_id = $this->userInfo['id'];
        $path = 'forum/'.$user_id.'/';
        $upload = (new UploadPic())->uploadOnePic($path,'file_path');

        $upload = $upload->getData();
        if ($upload['code'] == 1) {
            return json(['success' => true, 'msg' => '图片上传成功', 'file_path' => $upload['msg']]);
        } else {
            return json(['success' => false, 'msg' => $upload['msg'], 'file_path' => '']);
        }
    }
}
