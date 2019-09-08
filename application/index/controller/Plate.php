<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Validate;
use indexPage\Page;

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

/**
 * 社区板块
 * Class Forum
 * @package app\index\controller
 */
class Plate extends Base
{

    public $plateListPageLength = 12;   //列表页每页数量

    /**
     * 列表页面
     * plate_id     板块id
     * type         列表类型
     * @param Request $request
     * @return mixed|\think\response\Json|void
     */
    public function index(Request $request)
    {
        $plate_id = $request->param('plate_id');

        $type = $request->param('type') ?? 0;       //所选分类 列表 如 热门 原创 精华


        $type_arr = [0, 1, 2, 3, 4];
        if (!in_array($type, $type_arr)) {
            return $this->redirect(url('index/Index/index'));
        }
        $plate_info = new PlateModel();
        $forum_list = new ForumModel();
        $manager_list = new ForumManagerModel();
        $new_list = new ForumModel();
        $forum_list_count = 0 ;
        try {
            //所属 分类 和 板块
            $plate_info = $plate_info->where(['id' => $plate_id, 'is_delete' => 0])
                ->field('id,cate_id,plate_name,plate_img,forum_num,comment_num')->find();
            $plate_info['cate_name'] = (new CateModel())->where(['id' => $plate_info['cate_id']])->value('cate_name');

            //帖子列表
            $forum_list = $forum_list->alias('forum')
                ->join('user', 'forum.user_id = user.id')
                ->field('forum.pic,forum.name,user.nickname,forum.is_classics,forum.is_top,forum.create_time,forum.comment_num,forum.see_num')
                ->field('forum.id,forum.desc')
                ->where(['forum.is_delete' => 0])
                ->where(['forum.plate_id'=>$plate_id]);
            switch ($type) {
                case 0 :
                    $forum_list = $forum_list->order('forum.is_top', 'desc')
                        ->order('forum.is_classics', 'desc')
                        ->order('see_num', 'desc');
                    break;
                case 1 :
                    $forum_list = $forum_list->order('forum.create_time', 'desc');
                    break;
                case 2 :
                    $forum_list = $forum_list->order('forum.see_num', 'desc');
                    break;
                case 3 :
                    $forum_list = $forum_list->where(['forum.is_classics' => 1])->order('forum.create_time', 'desc');
                    break;
                case 4 :
                    $forum_list = $forum_list->where(['forum.is_original' => 1])->order('forum.create_time', 'desc');
                    break;
            }

            $start_page = 0;
            $forum_list_count = $forum_list->count();

            $forum_list = $forum_list->limit($start_page, $this->plateListPageLength)->select();

            //管理团队
            $manager_list = $manager_list->alias('manager')
                ->join('user user', 'user.id = manager.user_id')
                ->leftJoin('forum_manager_role role', 'role.id = manager.role_id')
                ->where(['manager.plate_id' => $plate_id])
                ->field('user.nickname,user.avatar_url,role.role_name,manager.role_id')
                ->order('manager.role_id')
                ->order('manager.id', 'desc')
                ->select();
            //最新帖子
            $new_list = $new_list->where(['is_delete' => 0])->field('id,name')->order('create_time', 'desc')->limit(5)->select();

        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }

        $this->assign('plate_info', $plate_info);
        $this->assign('forum_list', $forum_list);
        $this->assign('manager_list', $manager_list);
        $this->assign('new_list', $new_list);
        $this->assign('plate_type', $type);
        $this->assign('forum_list_count', $forum_list_count);
        $this->assign('page_length', $this->plateListPageLength);
        $this->assign('plate_id', $plate_id);
//        print_r($forum_list);die;
//        var_dump([
//            'plate_info'    => $plate_info,
//            'forum_list'    => $forum_list,
//            'manager_list'  => $manager_list,
//            'new_list'      => $new_list,
//        ]);
//        die;
        return $this->fetch();
    }

    /**
     * 获取列表接口
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList(Request $request)
    {
        $page = $request->post('page') ?? 1;
        $type = $request->post('plate_type') ?? 0;
        $plate_id = $request->post('plate_id');
        $type_arr = [0, 1, 2, 3, 4];
        if (!in_array($type, $type_arr)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        //帖子列表
        $forum_list = new ForumModel();
        $forum_list = $forum_list->alias('forum')
            ->join('user', 'forum.user_id = user.id')
            ->field('forum.pic,forum.name,user.nickname,forum.is_classics,forum.is_top,forum.create_time,forum.comment_num,forum.see_num')
            ->field('forum.id,forum.desc')
            ->where(['forum.is_delete' => 0])
            ->where(['forum.plate_id'=>$plate_id]);
        switch ($type) {
            case 0 :
                $forum_list = $forum_list->order('forum.is_top', 'desc')
                    ->order('forum.is_classics', 'desc')
                    ->order('see_num', 'desc');
                break;
            case 1 :
                $forum_list = $forum_list->order('forum.create_time', 'desc');
                break;
            case 2 :
                $forum_list = $forum_list->order('forum.see_num', 'desc');
                break;
            case 3 :
                $forum_list = $forum_list->where(['forum.is_classics' => 1])->order('forum.create_time', 'desc');
                break;
            case 4 :
                $forum_list = $forum_list->where(['forum.is_original' => 1])->order('forum.create_time', 'desc');
                break;
        }
        $start = $page * $this->plateListPageLength - $this->plateListPageLength;

        $forum_list = $forum_list->limit($start, $this->plateListPageLength)->select();

        $this->assign('forum_list', $forum_list);

        return json(['code' => 1, 'data' => $this->fetch('plate/forum_list')]);

    }
}
