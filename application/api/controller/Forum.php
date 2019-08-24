<?php

namespace app\api\controller;

use think\Controller;
use think\Exception;
use think\Request;
use app\api\model\ForumPlate as PlateModel;
use app\api\model\ForumCategory as CateModel;
use app\api\model\Forum as ForumModel;
use app\admin\model\ForumManager as ForumManagerModel;
use think\Validate;

class Forum extends Base
{
    /**
     * 获取二级分类
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

        (new ForumModel())->insert($result);

        return json(['code'=>1,'msg'=>'success']);
    }


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

    //详情页
    public function info(Request $request)
    {

    }


}
