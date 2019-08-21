<?php

namespace app\api\controller;

use think\Controller;


use think\Db;
use think\Request;
use app\api\model\LibraryHaveAttributeValue as LibraryHaveAttributeValueModel;
use app\api\model\Library as LibraryModel;
use app\api\validate\Library as LibraryValidate;
use Upyun\Upyun;
use Upyun\Config;
use Upyun\Signature;
use Upyun\Util;


class Library extends Base
{

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index(Request $request)
    {
        //SELECT library_id FROM zhu_library_have_attribute_value
        //WHERE attr_value IN (1,2) GROUP BY library_id HAVING COUNT(attr_value) = 2 ORDER BY library_id DESC LIMIT 0,1
        //分类 ID
        $cate = $request->get('cate');
        if ($cate) {
            $cate_field = 'cate_id';
        } else {
            $cate_field = '';
        }
        //属性ID 以逗号分隔
        $attr = $request->get('attr');
        //筛选
        $filtrate = $request->get('filtrate');
        if ($filtrate == 1) {
            //原创
            $filtrate = 'is_original';
        } else if ($filtrate == 2) {
            //精华
            $filtrate = 'is_classics';
        }
        try {
            if (!$attr) {
                $library = LibraryModel::field('id,library_pic,name')->where('is_delete', 0)
                    ->where('status', 1)->where($cate_field, $cate)->where($filtrate, 1)->order('create_time desc')->paginate(16);
            } else {
                $attr_value = explode(',', $attr);
                $length = count($attr_value);
                $library = LibraryModel::field('id,library_pic,name')->where('id', 'in', function ($query) use ($attr_value, $length) {
                    //查询出拥有特定属性的云库ID
                    $library_id = $query->name('library_have_attribute_value')->field('library_id')->where('attr_value', 'in', $attr_value)
                        ->group('library_id')->having('count(attr_value)=' . $length);
                })->where('is_delete', 0)->where('status', 1)->where($filtrate, 1)->order('create_time desc')->paginate(16);
            }

            return json(['code' => 1, 'msg' => '查询成功', 'data' => $library], 200);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '查询失败'], 400);
        }

    }

    public function show(Request $request)
    {
        //获取用户信息
        $user = $this->userInfo;

        if (!$id = $request->get('id')) {
            return json(['code' => 0, 'msg' => '参数错误'], 400);
        }

        try {
            //name user_id name_status create_time see_num integral source_url gehsi size desc
            //like_num collect_num comment_num is_classics is_official
            $library = LibraryModel::field('id,name,user_id,name_status,create_time,see_num,integral,suffix,data_size,desc,
        like_num,collect_num,comment_num,is_classics,is_official')
                ->where('id', $id)->where('is_delete', 0)->where('status', 1)->find();

            if ($library) {
                if ($library->name_status == 0) {
                    $library->nickname = '匿名';
                } else {
                    $library->nickname = Db::name('user')->where('id', $library->user_id)->value('nickname');
                }
                //查询用户点赞和收藏
                $library->like = Db::name('library_like_history')->where('library_id', $library->id)->where('user_id', $user['id'])->count('library_id');
                $library->collect = Db::name('user_collect')->where('type', 2)->where('user_id', $user['id'])->where('collect_id', $library->id)->count('id');

                //查询热门案例
                $hot = LibraryModel::field('id,name,library_pic')->where('is_delete', 0)->where('status', 1)
                    ->order('collect_num desc,like_num desc,comment_num desc,see_num desc')->limit(0, 10)->select();
            } else {
                return json(['code' => 0, 'msg' => '查询数据不存在']);
            }
//            print_r($library);die;
            $data = ['library' => $library, 'hot' => $hot];
//            return response($data);
            return json(['code' => 1, 'msg' => '查询成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '查询失败'], 400);
        }

    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $user = $this->userInfo;
        $data = $request->post();
        $data['user_id']=$user['id'];
        $validate = new LibraryValidate();
        if(!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $config = \HTMLPurifier_Config::createDefault();
        $purifier = new \HTMLPurifier($config);
        $data['desc'] = $purifier->purify($data['desc']);
        $data['data_size'] = round($data['data_size']/1024/1024,2);
        dump($data['data_size']);


    }


    /**
     * 保存更新的资源
     *
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }


    public function like(Request $request)
    {
        $user = $this->userInfo;
        $library_id = $request->post('library_id');

        Db::startTrans();
        try {
            if ($library_id) {
                $library_like_user = Db::name('library_like_history')
                    ->where('library_id', $library_id)->where('user_id', $user['id'])
                    ->count('library_id');

                if ($library_like_user) {
                    return json(['code' => 0, 'msg' => '当前云库以点赞，不能重复点赞']);
                }

                $library = (new LibraryModel())->where('id', $library_id)->find();
                if (!$library) {
                    return json(['code' => 0, 'msg' => '数据走丢啦，刷新后试试吧']);
                }
                $library->like_num = $library->like_num + 1;
                $library->save();

                $library_like_user = Db::name('library_like_history')->insert(['library_id' => $library->id, 'user_id' => $user['id']]);

                if ($library_like_user) {
                    Db::commit();
                    return json(['code' => 1, 'msg' => '点赞成功'], 200);
                } else {
                    return json(['code' => 0, 'msg' => '点赞失败'], 400);
                }
            } else {
                return json(['code' => 0, 'msg' => '缺少必要参数']);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '点赞失败'], 400);
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
            'path'  => $real_path,
        ));
    }
}
