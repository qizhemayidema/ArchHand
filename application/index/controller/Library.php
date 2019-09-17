<?php

namespace app\index\controller;

use think\facade\Cache;
use think\exception\HttpException;
use think\facade\Session;
use think\Request;
use app\index\model\LibraryCategory as LibraryCategoryModel;
use app\index\model\UserBuyHistory;
use app\index\model\UserDownloadLibraryHistory;
use app\index\model\UserBuyHistory as UserBuyHistoryModel;
use app\index\model\Vip;
use app\common\controller\UploadPic;
use think\Db;
use think\facade\Env;
use app\index\model\LibraryHaveAttributeValue as LibraryHaveAttributeValueModel;
use app\index\model\Library as LibraryModel;
use app\index\model\LibraryComment as LibraryCommentModel;
use app\index\model\Store as StoreModel;
use app\index\validate\Library as LibraryValidate;
use app\index\validate\LibraryComment as LibraryCommentValidate;
use think\Validate;
use Upyun\Upyun;
use Upyun\Config;
use Upyun\Signature;
use Upyun\Util;

class Library extends Base
{

    public $listPageLength = 16;
    public $commentPageLength = 10;

    /**
     * 云库首页
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $cate = (new LibraryCategoryModel())->getCate();

//        if (count($cate) > 0) {
//            $cate_id = $cate[0]['id'];
//        } else {
//            $cate_id = 0;
//        }
        $search = $request->get('search') ?? '';
        //分类 ID
        $library = LibraryModel::field('id,library_pic,name')->where('is_delete', 0)
//            ->where(['cate_id' => $cate_id])
            ->where('status', 1);
        if ($search){
            $library = $library->where('name', 'like', '%' . $search . '%');
        }
        $library = $library
            ->order('is_official desc,create_time desc')
            ->field('is_official,name,library_pic,id');
        $count = $library->count();
        $library = $library->limit(0, $this->listPageLength)->select()->toArray();

        $this->assign('library', $library);
        $this->assign('cate', $cate);
        $this->assign('library_count', $count);
        $this->assign('page_length', $this->listPageLength);
        $this->assign('search',$search);
        return $this->fetch();
    }

    /**
     * 获取筛选后的列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function getList(Request $request)
    {
        //SELECT library_id FROM zhu_library_have_attribute_value
        //WHERE attr_value IN (1,2) GROUP BY library_id HAVING COUNT(attr_value) = 2 ORDER BY library_id DESC LIMIT 0,1
        //分类 ID
        $pageSize = $this->listPageLength;

        $cate = $request->post('cate_id');

        $store_id = $request->post('store_id');

        $search = $request->post('search');
        //属性ID 以逗号分隔
        $attr = $request->post('attr_ids');
        //筛选
        $filtrate = $request->post('filtrate');

        $page = $request->post('page');

        $start = $page * $pageSize - $pageSize;

        $count = 0;
        $length = 0;
        if ($attr) {
            //筛选属性
            $array_attr = explode(',', $attr);
            $attr_value = [];

            foreach ($array_attr as $value) {
                if ($value > 0) {
                    $attr_value[] = $value;
                }
            }
            $length = count($attr_value);
        }

        if ($filtrate == 1) {
            //原创
            $filtrate = 'is_original';
        } else if ($filtrate == 2) {
            //精华
            $filtrate = 'is_classics';
        } else {
            $filtrate = 0;
        }
        try {
            $library = LibraryModel::field('id,library_pic,name,is_official')->where('is_delete', 0)
                ->where('status', 1);
            if ($store_id){
                $library = $library->where(['store_id'=>$store_id]);
            }
            if ($search) {
                $library = $library->where('name', 'like', '%' . $search . '%');
            }
            if ($length) {
                $library = $library->where('id', 'in', function ($query) use ($attr_value, $length) {
                    //查询出拥有特定属性的云库ID
                    $query->name('library_have_attribute_value')->field('library_id')
                        ->where('attr_value_id', 'in', $attr_value)->group('library_id')->having('count(attr_value_id)=' . $length);
                });
            }
            if ($cate){
                $library = $library->where(['cate_id' => $cate]);
            }
            if ($filtrate) {
                $library = $library->where($filtrate, 1);
            }
            $count = $library->count();
            $library = $library->order('is_official desc,create_time desc')->limit($start, $pageSize)->select();
            $this->assign('library', $library);
            return json(['code' => 1, 'msg' => '查询成功', 'data' => $this->fetch('library/index_list'), 'count' => $count, 'page_length' => $pageSize], 200);

        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => $e->getMessage()], 500);
        }

    }

    /**
     * 详情页
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function info(Request $request)
    {
        //获取用户信息
        $id = $request->param('library_id');

        //name user_id name_status create_time see_num integral source_url gehsi size desc
        //like_num collect_num comment_num is_classics is_official
        $library = LibraryModel::field('library_pic,id,store_id,name,user_id,name_status,create_time,see_num,integral,suffix,data_size,desc,
        like_num,collect_num,comment_num,is_classics,is_official,is_original')
            ->where('id', $id)->where('is_delete', 0)->where('status', 1)->find();
        if ($library) {
            $library->setInc('see_num');
            if ($library->name_status == 0) {
                $library->nickname = '匿名';
            } else {
                $library->nickname = Db::name('user')->where('id', $library->user_id)->value('nickname');
            }
            if (Session::has($this->userInfoSessionPath)) {
                //查询用户点赞和收藏
                $library->like = Db::name('library_like_history')->where('library_id', $library->id)
                    ->where('user_id', $this->userInfo['id'])->count('library_id');
                $library->collect = Db::name('user_collect')->where('type', 2)
                    ->where('user_id', $this->userInfo['id'])->where('collect_id', $library->id)->count('id');
            } else {
                $library->like = 0;
                $library->collect = 0;
            }
            //查询热门案例
            $hot = LibraryModel::field('id,name,library_pic')->where('is_delete', 0)
                ->where('status', 1)
                ->order('collect_num desc,like_num desc,comment_num desc,see_num desc create_time asc')
                ->limit(0, 8)->select();
        } else {
            throw new HttpException(404);
        }
        //评论
        $comment = (new LibraryCommentModel())->alias('comment')
            ->join('user user', 'user.id = comment.user_id')
            ->where(['comment.status' => 1])
            ->where(['comment.library_id' => $id]);
        if (Session::has($this->userInfoSessionPath)) {
            $comment = $comment->leftJoin('library_comment_like_history like', 'like.comment_id = comment.id and like.user_id = ' . $this->userInfo['id'])
                ->field('like.comment_id is_like');
        }
        $comment = $comment->order('comment.create_time')
            ->field('comment.id,user.avatar_url,user.nickname,comment.comment,comment.create_time,comment.like_num');
        $comment_count = $comment->count();

        $comment = $comment->limit(0, $this->commentPageLength)->select();

        //查找店铺的信息  id 和 background 和 名称 和 logo
        $storeModel = new StoreModel();
        $store = $storeModel->where(['id'=>$library['store_id']])->field('store_name,store_logo,id,store_background,is_official')->find();

        $this->assign('library', $library);
        $this->assign('hot', $hot);
        $this->assign('comment_count', $comment_count);
        $this->assign('comment', $comment);
        $this->assign('store',$store);
        $this->assign('comment_page_length',$this->commentPageLength);


        return $this->fetch();


        $data = ['library' => $library, 'hot' => $hot];
//            return response($data);

    }

    /**
     * 添加页面
     * @return mixed
     */
    public function add()
    {
        //获取分类
        $cate = (new LibraryCategoryModel())->getCate();
        $attr = $cate[0]['attribute'] ?? [];

        $this->assign('cate', $cate);
        $this->assign('attr', $attr);

        return $this->fetch();
    }

    /**
     * 添加动作
     * @param Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        $user = $this->userInfo;
        $data = $request->post();

        if (!$data['library_pic']) {
            return json(['code' => 0, 'msg' => '封面图必须上传'], 422);
        }
        //上传图片
        $image_base64 = $data['library_pic'];
        $library_pic_path = (new UploadPic())->uploadBase64Pic($image_base64, 'library/' . $user['id'] . '/');
        if ($library_pic_path['code'] == 0) {
            return json(['code' => 0, 'msg' => '图片上传失败']);
        }
        $data['library_pic'] = $library_pic_path['msg'];
//
        $data['user_id'] = $user['id'];
        $validate = new LibraryValidate();
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        $config = \HTMLPurifier_Config::createDefault();
        $purifier = new \HTMLPurifier($config);
        $data['desc'] = $purifier->purify($data['desc']);
        $data['data_size'] = round($data['data_size'] / 1024 / 1024, 2);
        $data['is_official'] = $user->type == 2 ? 1 : 0;
        $data['create_time'] = time();
        $data['store_id'] = $this->userInfo['store_id'];

        $rules = ['__token__'=>'require|token'];
        $messages = ['__token__.token'];
        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>'不能重复提交']);
        }
        Db::startTrans();
        try {

            $sql = date('Ymd', time()) . "-FROM_UNIXTIME(create_time,'%Y%m%d')=0";

            $library_integral_count = Db::name('library')
                ->where('user_id', $user['id'])
                ->whereRaw($sql)->count('create_time');

            $library_integral_count = $library_integral_count ?: 0;

            $exist = '';
            //判断当日可获得积分次数，
            if ($this->getConfig('issue_integral_count') - $library_integral_count > 0) {
                $integral = $this->getConfig('issue_integral');
                $this->updateUserIntegral(10, $integral);
                $exist = '，助手币加' . $integral;
            }

            $library = LibraryModel::create($data);
            //转换属性
            $have_attr_value = [];
            $value_id = [];
//        dump($data['attr_value_ids']);die;
            if (isset($data['attr_val_ids'])) {
                foreach ($data['attr_val_ids'] as $k => $v) {
                    foreach ($v as $item) {
                        $value_id[] = $item;
                        $have_attr_value[] = [
                            'attr_id' => $k,
                            'attr_value_id' => $item,
                            'library_id' => $library['id'],
                        ];
                    }
                }
            }


            //批量插入属性
            $library_have_attribute_value = (new LibraryHaveAttributeValueModel())->saveAll($have_attr_value);
            if (!$library_have_attribute_value) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '发布失败'], 200);
            }
            
            Db::commit();
            return json(['code' => 1, 'msg' => '发布成功' . $exist], 201);

        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => $e->getMessage()], 500);
        }
    }

    public function like(Request $request)
    {
        $user = $this->userInfo;
        $library_id = $request->post('library_id');
        if (!$library_id) {
            return json(['code' => 0, 'msg' => '缺少必要参数'], 422);
        }
        Db::startTrans();
        try {
            if ($library_id) {
                $library_like_user = Db::name('library_like_history')
                    ->where('library_id', $library_id)->where('user_id', $user['id'])
                    ->count('library_id');

                if ($library_like_user) {
                    return json(['code'=>0,'msg'=>'您已经点过赞了,不能重复点赞哦~']);

                }

                $library = (new LibraryModel())->where('id', $library_id)->find();
                if (!$library) {
                    return json(['code' => 0, 'msg' => '数据走丢啦，刷新后试试吧'], 200);
                }
                $library->like_num = $library->like_num + 1;
                $library->save();

                $library_like_user = Db::name('library_like_history')->insert(['library_id' => $library->id, 'user_id' => $user['id']]);

                if ($library_like_user) {
                    Db::commit();
                    return json(['code' => 1, 'msg' => '点赞成功'], 200);
                } else {
                    return json(['code' => 0, 'msg' => '点赞失败'], 200);
                }
            } else {
                return json(['code' => 0, 'msg' => '缺少必要参数']);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '点赞失败']);
        }


    }

    public function collect(Request $request)
    {
        $user = $this->userInfo;
        $library_id = $request->post('library_id');
        if (!$library_id) {
            return json(['code' => 0, 'msg' => '缺少必要参数'], 422);
        }
        Db::startTrans();
        try {
            $user_collect = Db::name('user_collect')->where('type', 2)
                ->where('collect_id', $library_id)->where(['user_id'=>$user['id']])->find();

            $library = (new LibraryModel())->where('id', $library_id)->find();
            if (!$library) {
                return json(['code' => 0, 'msg' => '数据丢失了，刷新后试试吧'], 200);
            }

            if ($user_collect) {
                if ($library->collect_num < 0){
                    $library->collect_num = 1;
                }
                $library->collect_num = $library->collect_num - 1;
                $library->save();

                Db::name('user_collect')->where('type', 2)
                    ->where('collect_id', $library_id)->where(['user_id'=>$user['id']])->delete();
            }else{

                $library->collect_num = $library->collect_num + 1;
                $library->save();

                Db::name('user_collect')->insert([
                    'type' => 2, 'user_id' => $user['id'], 'collect_id' => $library_id, 'create_time' => time()
                ]);
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '收藏失败'], 200);
        }
        return json(['code'=>1,'msg'=>'success']);
    }

    /**
     * 评论接口
     * @param Request $request
     * @return \think\response\Json
     */
    public function comment(Request $request)
    {
        $data = $request->post();

        $user = $this->userInfo;

        $data['user_id'] = $user['id'];
        $validate = new LibraryCommentValidate();
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        //判断是否购买过
        $libraryAuther = (new LibraryModel())->where(['id'=>$data['library_id']])->value('user_id');
        $isBuy = (new UserBuyHistoryModel())->where(['type'=>1,'user_id'=>$user['id'],'buy_id'=>$data['library_id']])->find();
        if (!$isBuy && $libraryAuther != $user['id']){
            return json(['code'=>0,'msg'=>'购买后才能评论~']);
        }
        //加载默认配置
        $config =  \HTMLPurifier_Config::createDefault();
        //实例化对象
        $purifier = new \HTMLPurifier($config);
        //过滤
        $data['comment'] = $purifier->purify($data['comment']);

        $data['create_time'] = time();
        Db::startTrans();
        try {

            $sql = date('Ymd',time())."-FROM_UNIXTIME(create_time,'%Y%m%d')=0";

            $comment_integral_count = Db::name('library_comment')
                ->where('user_id',$user['id'])
                ->whereRaw($sql)->count('create_time');

            $comment_integral_count = $comment_integral_count?:0;

            $exist = '';
            //判断当日可获得积分次数，
            if($this->getConfig('comment_integral_count')-$comment_integral_count>0){
                $integral = $this->getConfig('comment_integral');
                $this->updateUserIntegral(5, $integral);
                $exist = '，助手币加'.$integral;
            }

            $comment = LibraryCommentModel::create($data, ['user_id', 'library_id', 'comment', 'create_time']);
            if ($comment) {
                $library = (new LibraryModel())->where('id', $data['library_id'])->find();
                $library->comment_num = $library->comment_num + 1;
                $library->save();


                Db::commit();
                return json(['code' => 1, 'msg' => '发布成功']);
            } else {
                Db::rollback();
                return json(['code' => 0, 'msg' => '评论失败']);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '评论失败']);
        }
    }

    /**
     * 获取评论列表
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getComment(Request $request)
    {
        $page = $request->post('page') ?? 1;
        $library_id = $request->post('library_id');
        $comment = (new LibraryCommentModel())->alias('comment')
            ->join('user user', 'user.id = comment.user_id')
            ->where(['comment.status' => 1])
            ->where(['comment.library_id' => $library_id]);
        if (Session::has($this->userInfoSessionPath)) {
            $comment = $comment->leftJoin('library_comment_like_history like', 'like.comment_id = comment.id and like.user_id = ' . $this->userInfo['id'])
                ->field('like.comment_id is_like');
        }
        $start = $page * $this->commentPageLength - $this->commentPageLength;
        $comment = $comment->order('comment.create_time')
                    ->field('comment.id,user.avatar_url,user.nickname,comment.comment,comment.create_time,comment.like_num')
                    ->limit($start, $this->commentPageLength)->select();

        $this->assign('comment',$comment);
        $this->assign('floor_start',$start);

        return json(['code'=>1,'data'=>$this->fetch('library/comment_list')]);
    }

    /**
     * 给评论点赞
     * @param Request $request
     * @return \think\response\Json
     */
    public function likeComment(Request $request)
    {
        $user = $this->userInfo;
        $comment_id = $request->post('library_comment_id');
        Db::startTrans();
        try {
            if ($comment_id) {
                $comment_like_user = Db::name('library_comment_like_history')
                    ->where('comment_id',$comment_id)->where('user_id',$user['id'])
                    ->count('comment_id');

                if($comment_like_user){

                    return json(['code'=>0,'msg'=>'您已经点过赞了,不能重复点赞哦~']);
                }

                $comment = (new LibraryCommentModel())->where('id', $comment_id)->find();
                if(!$comment){
                    return json(['code'=>0,'msg'=>'数据走丢啦，刷新后试试吧']);
                }
                $comment->like_num = $comment->like_num + 1;
                $comment->save();

                 Db::name('library_comment_like_history')->insert(['comment_id' => $comment->id, 'user_id' => $user['id']]);

            }else{
                return json(['code'=>0,'msg'=>'缺少必要参数'],422);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '点赞失败'], 500);
        }
        return json(['code'=>1,'msg'=>'success']);
    }


    /**
     * 获取一级分类下属性
     * @param Request $request
     * @return \think\response\Json
     */
    public function getAttrValues(Request $request)
    {
        $cate_id = $request->post('cate_id');

        $cate = (new LibraryCategoryModel())->getCate();

        $attr = [];

        foreach ($cate as $key => $value) {
            if ($value['id'] == $cate_id) {
                $attr = $value['attribute'];
                break;
            }
        }

        $this->assign('attr', $attr);
        $html = $this->fetch('library/index_attr_list');
        return json(['code' => 1, 'data' => $attr]);
    }

    /**
     * 富文本图片上传
     * @return \think\response\Json
     */
    public function uploadContentPic()
    {
        $this->getUserInfo();
        $user_id = $this->userInfo['id'];
        $path = 'library/' . $user_id . '/';
        $upload = (new UploadPic())->uploadOnePic($path,'file');

        $upload = $upload->getData();
        if ($upload['code'] == 1) {
            return json(['success' => true, 'msg' => '图片上传成功', 'file_path' => $upload['msg']]);
        } else {
            return json(['success' => false, 'msg' => $upload['msg'], 'file_path' => '']);
        }
    }

    /**
     * 又拍云 文库上传接口
     * @throws \Exception
     */
    public function uploadVideo()
    {
        $config = new Config(env('UPYUN.SERVICE_NAME'), env('UPYUN.USERNAME'), env('UPYUN.PASSWORD'));
        $config->setFormApiKey(env('UPYUN.API_KEY'));

        //创建目录
        $path = 'library/' . $this->userInfo['id'] . '/';
        (new Upyun($config))->createDir($path);

        $real_path = $path . $_POST['save_file_name'];

        $data['save-key'] = $real_path;

        $data['expiration'] = time() + 120;
        $data['bucket'] = env('UPYUN.SERVICE_NAME');
        $policy = Util::base64Json($data);
        $method = 'POST';
        $uri = '/' . $data['bucket'];
        $signature = Signature::getBodySignature($config, $method, $uri, null, $policy);
        echo json_encode(array(
            'policy' => $policy,
            'authorization' => $signature,
            'service_name' => env('UPYUN.SERVICE_NAME'),
            'path' => $real_path,
        ));
    }

    public function buy(Request $request)
    {
        $user = $this->userInfo;
        $id = $request->post('library_id');
        if (!$id) {
            return json(['code' => 0, 'msg' => '缺少必要参数'], 422);
        }
        Db::startTrans();
        try {
            //查找云库
            $library = LibraryModel::where('id', $id)->where('is_delete', 0)->where('status', 1)->find();

            if (!$library) {
                return json(['code' => 0, 'msg' => '云库不存在'], 200);
            }

            //查找是否有文件
            $config = new Config(env('UPYUN.SERVICE_NAME'), env('UPYUN.USERNAME'), env('UPYUN.PASSWORD'));
            $upyun = (new Upyun($config))->has($library['source_url']);
            if (!$upyun) {
                return json(['code' => 0, 'msg' => '当前文件已经删除啦'], 200);
            }

            if ($library->user_id == $user['id']) {
                UserDownloadLibraryHistory::create(['library_id' => $id, 'user_id' => $user['id'], 'create_time' => time()]);
                Db::commit();
                return json(['code' => 1, 'msg' => '查询成功', 'buy' => 3, 'data' => ['source_url' => Env::get('UPYUN.CDN_URL') . $library['source_url']]]);
            }

            //判断是否以购买过
            $is_user_buy_history = UserBuyHistory::where('type', 1)->where('buy_id', $library['id'])->where('user_id', $user['id'])->find();
            if ($is_user_buy_history) {
                UserDownloadLibraryHistory::create(['library_id' => $id, 'user_id' => $user['id'], 'create_time' => time()]);
                Db::commit();
                return json(['code' => 1, 'msg' => '以购买过', 'buy' => 1, 'data' => ['source_url' => Env::get('UPYUN.CDN_URL') . $library['source_url']]]);
            }
            //判断积分
            if ($user['vip_id']) {
                $vip = Vip::where('id', $user['vip_id'])->find();
                //计算价格
                $discount_integral = floor($library['integral'] * ($vip['discount'] / 10));
                if ($discount_integral <= 0) {
                    $discount_integral = 1;
                }

            } else {
                $discount_integral = $library['integral'];
            }
            $time = time();
            if (($user['integral'] - $discount_integral) >= 0) {
                //扣除用户积分 记录积分变动
                $this->updateUserIntegral(4, $discount_integral);
                //更新购买记录表
                $user_buy_history = UserBuyHistory::create([
                    'type' => 1,
                    'buy_id' => $library['id'],
                    'user_id' => $user['id'],
                    'integral' => $discount_integral,
                    'create_time' => $time,
                ]);

                //文库主人
                $user_vendor = \app\api\model\User::where('id', $library['user_id'])->find();
                //计算手续费
                $user_vendor_discount_integral = floor($discount_integral - $this->getConfig('service_charge_integral') / 100 * $discount_integral);
                if ($user_vendor_discount_integral <= 0) {
                    $user_vendor_discount_integral = 1;
                }

                //文库主人加积分
                $user_vendor_integral = $this->updateUserIntegral(9, $user_vendor_discount_integral, $library['user_id']);
                UserDownloadLibraryHistory::create(['library_id' => $id, 'user_id' => $user['id'], 'create_time' => time()]);
                Db::commit();
                return json(['code' => 1, 'msg' => '购买成功', 'buy' => 2, 'data' => ['source_url' => Env::get('UPYUN.CDN_URL') . $library['source_url']]], 200);
            } else {
                Db::rollback();
                return json(['code' => 0, 'msg' => '当前筑手币不足,请及时充值~']);
            }
//            Db::rollback();
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '购买失败'], 500);
        }
    }
}
