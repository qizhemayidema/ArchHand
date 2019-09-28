<?php

namespace app\index\controller;

use app\index\model\LibraryAttributeValue;
use app\index\model\LibraryHaveAttributeValue as LibraryHaveAttributeValueModel;
use app\index\model\LibraryCategory as LibraryCategoryModel;
use app\api\validate\Library as LibraryValidate;
use app\common\controller\Library as CommonLibraryModel;
use think\Request;
use think\Validate;
use app\index\model\Library as LibraryModel;
use app\index\model\LibraryComment as LibraryCommentModel;
use app\index\model\UserDownloadLibraryHistory as UDLHModel;
use app\index\model\UserCollect as UserCollectModel;
use app\index\model\UserBuyHistory as UserBuyHistoryModel;
use app\index\model\Store as StoreModel;
use Upyun\Upyun;
use Upyun\Config;
use Upyun\Signature;
use Upyun\Util;
use think\Db;

class MyLibrary extends Base
{
    public $commonPageLength = 10;

    public function index()
    {
        $user_info = $this->userInfo;
        //我的发布
        $libraryData = $this->getMyPublishList();
        $library = $libraryData['data'];
        $libraryCount = $libraryData['count'];
        //我的评论
        $commentData = $this->getMyCommentList();
        $comment = $commentData['data'];
        $commentCount = $commentData['count'];
//        print_r($comment);die;
        //我的收藏
        $collectData = $this->getMyCollectList();
        $collect = $collectData['data'];
        $collectCount = $collectData['count'];
        //我的购买
        $buyData = $this->getMyBuyList();
        $buy = $buyData['data'];
        $buyCount = $buyData['count'];


        //下载记录
        $downloadData = $this->getMyDownloadList();
        $download = $downloadData['data'];
        $downloadCount = $downloadData['count'];

        //查找店铺信息
        $store = (new StoreModel())->where(['id'=>$user_info['store_id']])->find();



        $this->assign('library', $library);
        $this->assign('library_count', $libraryCount);
        $this->assign('comment', $comment);
        $this->assign('comment_count', $commentCount);
        $this->assign('collect', $collect);
        $this->assign('collect_count', $collectCount);
        $this->assign('buy', $buy);
        $this->assign('buy_count', $buyCount);
        $this->assign('download', $download);
        $this->assign('download_count', $downloadCount);
        $this->assign('page_length', $this->commonPageLength);
        $this->assign('store',$store);
        return $this->fetch();
    }

    //我的发布
    public function getMyPublishList()
    {
        $request = request();
        $page = $request->post('page') ?? 1;
        $start = $page * $this->commonPageLength - $this->commonPageLength;

        if ($request->isAjax()) {
            $data = $request->post();
            $rules = [
                'page' => 'require',
            ];

            $messages = [
                'page.require' => 'page必须携带',
            ];
            $validate = new Validate($rules, $messages);
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
        }

        $library = (new LibraryModel())->alias('library')
            ->leftjoin('library_check_history history', 'history.library_id = library.id')
            ->where(['library.user_id' => $this->userInfo['id'], 'library.is_delete' => 0])
            ->field('library.id,library.status,library.name,library.see_num,library.name_status,library.library_pic,library.like_num,library.collect_num,library.comment_num,library.create_time,library.is_original,library.is_classics')
            ->field('history.because')
            ->order('library.create_time', 'desc')
            ->limit($start, $this->commonPageLength)
            ->select()->toArray();
        $libraryCount = 0;
        if ($request->isGet()) {
            $libraryCount = (new LibraryModel())->alias('library')
                ->leftjoin('library_check_history history', 'history.library_id = library.id')
                ->where(['library.user_id' => $this->userInfo['id'], 'library.is_delete' => 0])
                ->count();
        }
        if ($request->isAjax()) {
            //返回列表页
            $this->assign('library', $library);
            return json(['code' => 1, 'data' => $this->fetch('my_library/publish_list')]);
        } else {
            return ['data' => $library, 'count' => $libraryCount];
        }
    }

    //我的评论
    public function getMyCommentList()
    {
        $request = request();
        $page = $request->post('page') ?? 1;
        $start = $page * $this->commonPageLength - $this->commonPageLength;

        if ($request->isAjax()) {
            $data = $request->post();
            $rules = [
                'page' => 'require',
            ];

            $messages = [
                'page.require' => 'page必须携带',
            ];
            $validate = new Validate($rules, $messages);
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
        }

        $comment = (new LibraryCommentModel())->alias('comment')
            ->join('library', 'library.id = comment.library_id')->where(['comment.user_id' => $this->userInfo['id'],'comment.status'=>1])
            ->field('comment.like_num,library.id library_id,library.name,comment.id comment_id,comment.comment,comment.status,comment.create_time')
            ->order('comment.id', 'desc')
            ->limit($start, $this->commonPageLength)
            ->select();
        $commentCount = 0;
        if ($request->isGet()) {
            $commentCount = (new LibraryCommentModel())->alias('comment')
                ->join('library', 'library.id = comment.library_id')->where(['comment.user_id' => $this->userInfo['id'],'comment.status'=>1])
                ->count();
        }


        if ($request->isAjax()) {
            //返回列表页
            $this->assign('comment', $comment);
            return json(['code' => 1, 'data' => $this->fetch('my_library/comment_list')]);
        } else {
            return ['data' => $comment, 'count' => $commentCount];
        }
    }

    //我的下载
    public function getMyDownloadList()
    {
        $request = request();
        $page = $request->post('page') ?? 1;
        $start = $page * $this->commonPageLength - $this->commonPageLength;

        if ($request->isAjax()) {
            $data = $request->post();
            $rules = [
                'page' => 'require',
            ];

            $messages = [
                'page.require' => 'page必须携带',
            ];
            $validate = new Validate($rules, $messages);
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
        }


        $download = (new UDLHModel())->alias('download')->where(['download.user_id' => $this->userInfo['id']])
            ->join('library library', 'download.library_id = library.id')
            ->where(['download.user_id' => $this->userInfo['id']])
            ->field('library.name,library.id,download.create_time')
            ->order('download.id', 'desc')
            ->limit($start, $this->commonPageLength)
            ->select();
        $count = 0;
        if ($request->isGet()) {
            $count = (new UDLHModel())->alias('download')->where(['download.user_id' => $this->userInfo['id']])->count();
        }

        if ($request->isAjax()) {
            //返回列表页
            $this->assign('download', $download);
            return json(['code' => 1, 'data' => $this->fetch('my_library/download_list')]);
        } else {
            return ['data' => $download, 'count' => $count];
        }
    }

    //我的收藏记录
    public function getMyCollectList()
    {
        $request = request();
        $page = $request->post('page') ?? 1;
        $start = $page * $this->commonPageLength - $this->commonPageLength;

        if ($request->isAjax()) {
            $data = $request->post();
            $rules = [
                'page' => 'require',
            ];

            $messages = [
                'page.require' => 'page必须携带',
            ];
            $validate = new Validate($rules, $messages);
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
        }

        $collectList = (new UserCollectModel())->alias('collect')->where(['collect.user_id' => $this->userInfo['id']])
            ->where(['type' => '2'])->join('library library', 'collect.collect_id = library.id')
            ->field('library.id,library.library_pic,library.id library_id,library.name,collect.create_time collect_time')
            ->order('collect.id', 'desc')
            ->limit($start, $this->commonPageLength)
            ->select();
        $count = 0;
        if ($request->isGet()) {
            $count = (new UserCollectModel())->alias('collect')->where(['collect.user_id' => $this->userInfo['id']])
                ->where(['type' => '2'])->count();
        }

        if ($request->isAjax()) {
            //返回列表页
            $this->assign('collect', $collectList);
            return json(['code' => 1, 'data' => $this->fetch('my_library/collect_list')]);
        } else {
            return ['data' => $collectList, 'count' => $count];
        }
    }

    //我的购买记录
    public function getMyBuyList()
    {
        $request = request();
        $page = $request->post('page') ?? 1;
        $start = $page * $this->commonPageLength - $this->commonPageLength;

        if ($request->isAjax()) {
            $data = $request->post();
            $rules = [
                'page' => 'require',
            ];

            $messages = [
                'page.require' => 'page必须携带',
            ];
            $validate = new Validate($rules, $messages);
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
        }

        $buyInfo = (new UserBuyHistoryModel())->alias('buy')
            ->where(['buy.type' => 1])
            ->where(['buy.user_id' => $this->userInfo['id']])
            ->join('library library', 'library.id = buy.buy_id')
            ->field('library.id library_id,library.name library_name,library.library_pic')
            ->field('buy.integral,buy.create_time buy_time')
            ->order('buy.id', 'desc')
            ->limit($start, $this->commonPageLength)
            ->select();
        $count = 0;
        if ($request->isGet()) {
            $count = (new UserBuyHistoryModel())->alias('buy')
                ->where(['buy.type' => 1])
                ->where(['buy.user_id' => $this->userInfo['id']])
                ->count();
        }

        if ($request->isAjax()) {
            //返回列表页
            $this->assign('buy', $buyInfo);
            return json(['code' => 1, 'data' => $this->fetch('my_library/buy_list')]);
        } else {
            return ['data' => $buyInfo, 'count' => $count];
        }
    }

    //删除 自己发布的云库
    public function delLibrary(Request $request)
    {
        $user = $this->userInfo;
        $id = $request->post('library_id');
        if (!$id) {
            return json(['code' => 0, 'msg' => '缺少必要参数']);
        }
        Db::startTrans();
        try {
            //查询云库是否存在
            $library = LibraryModel::where('id', $id)->where('is_delete', 0)->find();
            if (!$library) {
                return json(['code' => 0, 'msg' => '当前云库内容不存在'], 200);
            }

            //删除又拍云
//            $config = new Config(env('UPYUN.SERVICE_NAME'), env('UPYUN.USERNAME'), env('UPYUN.PASSWORD'));
//            $upyun = new Upyun($config);
//            $has = $upyun->has($library['source_url']);
//            if ($has) {
//                //删除
//                $del = $upyun->delete($library['source_url']);
//                if (!$del) {
//                    return json(['code' => 0, 'msg' => '删除失败'], 400);
//                }
//            }
            if($library['status'] == 1){
                (new CommonLibraryModel())->setAboutSum($id,0);
            }

            //获取属性
            $have_attribute_value = LibraryHaveAttributeValueModel::where('library_id', $library['id'])->all();
            foreach ($have_attribute_value as $v) {
                $value_ids [] = $v['attr_value_id'];
                $ids[] = $v['id'];
            }

            //删除属性
            if ($ids)
                $have_attribute_value = LibraryHaveAttributeValueModel::destroy($ids);

            //删除云库
            $library = $library->save(['source_url' => '以删除', 'is_delete' => time()]);
            if ($library) {
                Db::commit();

                return json(['code' => 1, 'msg' => '删除成功'], 200);
            } else {
                Db::rollback();
                return json(['code' => 0, 'msg' => '未删除'], 200);
            }
//            Db::rollback();
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '删除失败'], 200);
        }
    }


}
