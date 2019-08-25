<?php

namespace app\api\controller;

use think\Exception;
use think\Request;
use think\Validate;
use app\api\model\ForumPlate as PlateModel;
use app\api\model\ForumCategory as CateModel;
use app\api\model\Forum as ForumModel;
use app\admin\model\ForumManager as ForumManagerModel;
use app\api\model\User as UserModel;
use app\api\model\ForumComment as ForumCommentModel;
use app\api\model\UserCollect as UserCollectModel;
use app\api\model\ForumLikeHistory as LikeHistoryModel;
use app\api\model\ForumCommentLikeHistory as CommentLikeHistoryModel;
use app\api\model\UserIntegralHistory as UserIntegralHistoryModel;

class Forum extends Base
{
    /**
     * 获取所有分类
     * @return \think\response\Json
     */
    public function getAllPlate()
    {
        return json(['code'=>1,'data'=>(new PlateModel())->getList()]);
    }

    public function getCate()
    {
        $data = (new CateModel())->field('id,cate_name')->select();

        return json(['code'=>1,'data'=>$data]);
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
        if(!$cate_id) return json(['code'=>0,'msg'=>'请携带cate_id']);
        $data = (new PlateModel())->where(['cate_id'=>$cate_id,'is_delete'=>0])->field('id,plate_name')->select();

        return json(['code'=>1,'data'=>$data]);
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
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        //查询所属分类id
        $plateInfo = (new PlateModel())->find($data['plate_id']);
        if (!$plateInfo || $plateInfo['is_delete'] == 1)
            return json(['code'=>0,'msg'=>'所选板块不存在']);

        $result = [];

        if (isset($data['is_original'])){
            $result['is_original'] = 1;
        }
//        if (!file_exists('.' . $data['pic'])){
//            return json(['code'=>0,'msg'=>'封面不存在']);
//        }
        //加载默认配置
        $config = \HTMLPurifier_Config::createDefault();
        //实例化对象
        $purifier = new \HTMLPurifier($config);
        //过滤
        $result['content'] = $purifier->purify($data['content']);

        $result['cate_id'] = $plateInfo['cate_id'];
        $result['plate_id'] = htmlentities($plateInfo['id']);
        $result['name'] = htmlentities($data['name']);
        $result['pic'] = $data['pic'];
        $result['tag_str'] = htmlentities($data['tag_str']);
        $result['user_id'] = $user_info['id'];
        $result['create_time'] = time();
        $result['desc'] = htmlentities($data['desc']);

        $forumModel = new ForumModel();
        $forumModel->startTrans();
        try{
            (new ForumModel())->insert($result);
            (new PlateModel())->where(['id'=>$plateInfo['id']])->setInc('forum_num');
            $forumModel->commit();
        }catch (\Exception $e){
            $forumModel->rollback();
            return json(['code'=>0,'msg'=>'操作失败']);
        }
        return json(['code'=>1,'msg'=>'success']);
    }

    /**
     * 列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function plate(Request $request)
    {
        $data =$request->post();

        $rules = [
            'type'      => 'number',
            'plate_id'  => 'require|number',
            'page'      => 'require|number',
            'page_length' => 'require|number',
        ];


        $messages = [
            'type.number'       => 'error',
            'plate_id.require'  => '必须选择板块',
            'plate_id.number'      => 'error',
            'page.require'      => '必须携带page',
            'page.number'      => 'page 必须为数字',
            'page_length.require'      => '必须携带page_length',
            'page_length.number'      => 'page_length 必须为数字',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        $type_arr = [0,1,2,3,4];
        if (isset($data['type']) && !in_array($data['type'],$type_arr)){
            return json(['code'=>0,'msg'=>'error2']);
        }
        $plate_info = new PlateModel();
        $forum_list = new ForumModel();
        $forum_list_count = 0;
        $manager_list = new ForumManagerModel();
        $new_list = new ForumModel();
        try{
            //所属 分类 和 板块
            $plate_info = $plate_info->where(['id'=>$data['plate_id'],'is_delete'=>0])
                ->field('id,cate_id,plate_name,plate_img,forum_num,comment_num')->find();
            $plate_info['cate_name'] = (new CateModel())->where(['id'=>$plate_info['id']])->value('cate_name');

            //帖子列表
            if (!isset($data['type'])) $data['type'] = 0;
            $forum_list = $forum_list->alias('forum')
                ->join('user','forum.user_id = user.id')
                ->field('forum.name,user.nickname,forum.is_classics,forum.is_top,forum.create_time,forum.comment_num,forum.see_num')
                ->field('forum.id,forum.desc')
                ->where(['forum.is_delete'=>0]);
            switch ($data['type']){
                case 0 :
                    $forum_list = $forum_list->order('forum.is_top','desc')
                        ->order('forum.is_classics','desc')
                        ->order('see_num','desc');
                    break;
                case 1 :
                    $forum_list = $forum_list->order('forum.create_time','desc');
                    break;
                case 2 :
                    $forum_list = $forum_list->order('forum.see_num','desc');
                    break;
                case 3 :
                    $forum_list = $forum_list->where(['forum.is_classics'=>1])->order('forum.create_time','desc');
                    break;
                case 4 :
                    $forum_list = $forum_list->where(['forum.is_original'=>1])->order('forum.create_time','desc');
                    break;
            }

            $forum_list_count = $forum_list->count();
            $start_page = $data['page'] * $data['page_length'] - $data['page_length'];

            $forum_list = $forum_list->limit($start_page,$data['page_length'])->select();
            //管理团队
            $manager_list = $manager_list->alias('manager')
                ->join('user user','user.id = manager.user_id')
                ->leftJoin('forum_manager_role role','role.id = manager.role_id')
                ->where(['manager.plate_id'=>$data['plate_id']])
                ->field('user.nickname,user.avatar_url,role.role_name')
                ->order('manager.role_id')
                ->order('manager.id','desc')
                ->select();
            //最新帖子
            $new_list = $new_list->where(['is_delete'=>0])->order('create_time','desc')->limit(5)->select();

        }catch (Exception $e){
            return json(['code'=>0,'msg'=>$e->getMessage()]);
        }

        return json(['code'=>1,'data'=>[
            'plate_info'    => $plate_info,
            'forum_list'    => $forum_list,
            'forum_list_count' => $forum_list_count,
            'manager_list'  => $manager_list,
            'new_list'      => $new_list,
        ]]);

    }

    /**
     * 详情
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function info(Request $request)
    {
        $data = $request->post();
        $rules = [
            'forum_id'  => 'require',
            'page'      => 'require|number',
            'page_length' => 'require|number',
        ];
        $validate = new Validate($rules);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }
        $forum_info = (new ForumModel())->where(['id'=>$data['forum_id'],'is_delete'=>0])->find();
        if (!$forum_info) return json(['code'=>0,'msg'=>'不存在']);
        $forum_info->setInc('see_num');
        $is_manager = false;
        //是否板块管理者
        if($request->param('token')){
            $user_info = $this->userInfo;
            //只查看 是否是管理组成员 给个flag
            $temp = (new ForumManagerModel())->where(['plate_id'=>$forum_info['plate_id'],'user_id'=>$user_info])->find();
            if ($temp){
                $is_manager = true;
            }
        }
        //文章内容
        $forum = [
            'id'        => $forum_info['id'],
            'name'      => $forum_info['name'],
            'is_classics' => $forum_info['is_classics'],
            'is_top'      => $forum_info['is_top'],
            'is_original' => $forum_info['is_original'],
            'tag_str'     => $forum_info['tag_str'],
            'content'     => $forum_info['content'],
            'collect_num'   => $forum_info['collect_num'],
            'comment_num'   => $forum_info['collect_num'],
            'create_time'   => $forum_info['create_time'],
            'user_name'     => (new UserModel())->where(['id'=>$forum_info['user_id']])->value('nickname'),
        ];

        //评论
        $comment = (new ForumCommentModel())->alias('comment')
            ->join('user user','user.id = comment.user_id')
            ->where(['comment.status'=>1])
            ->where(['comment.forum_id'=>$forum['id']])
            ->order('comment.create_time')
            ->field('user.avatar_url,user.nickname,comment.content,comment.create_time,comment.like_num');
        $comment_count = $comment->count();

        $start = $data['page'] * $data['page_length'] - $data['page_length'];
        $comment = $comment->order($start,$data['page_length'])->select();

        //热门
        $hot_forum = (new ForumModel())->where(['is_delete'=>0])->order('see_num','desc')
            ->field('name,pic,id')->limit(8)->select();

        return json(['code'=>1,'data'=>[
            'forum' => $forum,
            'comment' => $comment,
            'comment_count' => $comment_count,
            'hot_forum' =>$hot_forum,
            'is_manager' => $is_manager,
        ]]);
    }

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
        $likeHistoryModel->startTrans();
        try{
            if ($like){
                $like->delete();
                if ($forum_info['like_num'] > 0){
                    $forum_info->setDec('like_num');
                }
            }else{
                (new LikeHistoryModel())->insert([
                    'user_id' => $user_info['id'],
                    'forum_id' =>  $data['forum_id'],
                    'create_time' => time(),
                ]);
                $forum_info->setInc('like_num');
            }
            $likeHistoryModel->commit();
        }catch (\Exception $e){
            $likeHistoryModel->rollback();
            return json(['code'=>0,'msg'=>'操作失败']);
        }

        return json(['code'=>1,'msg'=>'success']);
    }

    //点赞评论
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
        $commentLikeHistoryModel->startTrans();
        try{
            if ($like){
                $like->delete();
                if ($comment_info['like_num'] > 0){
                    $comment_info->setDec('like_num');
                }
            }else{
                (new CommentLikeHistoryModel())->insert([
                    'user_id' => $user_info['id'],
                    'comment_id' =>  $data['forum_comment_id'],
                ]);
                $comment_info->setInc('like_num');
            }
            $commentLikeHistoryModel->commit();
        }catch (\Exception $e){
            $commentLikeHistoryModel->rollback();
            return json(['code'=>0,'msg'=>'操作失败']);
        }

        return json(['code'=>1,'msg'=>'success']);
    }


    //评论
    public function comment(Request $request){

        $data = $request->post();

        $rules = [
            'forum_id'  => 'require|number',
            'comment'   => 'require',
        ];

        $messages = [
            'comment.require'   => '评论不能为空'
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

        return json(['code'=>0,'msg'=>'success']);

    }


}
