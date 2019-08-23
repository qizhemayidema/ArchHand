<?php

namespace app\api\controller;

use app\api\model\LibraryAttributeValue;
use app\api\model\UserBuyHistory;
use app\api\model\UserDownloadLibraryHistory;
use app\api\model\UserIntegralHistory;
use app\api\model\Vip;
use think\Controller;


use think\Db;
use think\facade\Env;
use think\Request;
use app\api\model\LibraryHaveAttributeValue as LibraryHaveAttributeValueModel;
use app\api\model\Library as LibraryModel;
use app\api\model\LibraryCategory as LibraryCategoryModel;
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
        $cate = $request->get('cate_id');

        $search = $request->get('search');
        if ($cate) {
            $cate_field = 'cate_id';
            $operator = '=';
        } else {
            $cate_field = $search ? 'name' : '';
            $cate = $cate_field ? '%' . $search . '%' : '';
            $operator = 'like';
        }
        //属性ID 以逗号分隔
        $attr = $request->get('attr_ids');
        //筛选
        $filtrate = $request->get('filtrate');
        if ($filtrate == 1) {
            //原创
            $filtrate = 'is_original';
        } else if ($filtrate == 2) {
            //精华
            $filtrate = 'is_classics';
        }else{
            $filtrate = 0;
        }
        try {
            if (!$attr || $search) {

                $library = LibraryModel::field('id,library_pic,name')->where('is_delete', 0)
                    ->where('status', 1)->where($cate_field, $operator, $cate)->where($filtrate, 1)
                    ->order('create_time desc')->paginate(16);
            } else {
                $attr_value = explode(',', $attr);
                $length = count($attr_value);
                $library = LibraryModel::field('id,library_pic,name')->where('id', 'in', function ($query) use ($attr_value, $length) {
                    //查询出拥有特定属性的云库ID
                    $library_id = $query->name('library_have_attribute_value')->field('library_id')
                        ->where('attr_value', 'in', $attr_value)->group('library_id')->having('count(attr_value)=' . $length);
                })->where('is_delete', 0)->where('status', 1)->where($filtrate, 1)
                    ->order('create_time desc')->paginate(16);
            }

            return json(['code' => 1, 'msg' => '查询成功', 'data' => $library], 200);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '查询失败'], 500);
        }

    }

    public function show(Request $request)
    {
        //获取用户信息
        $user = $this->userInfo;

        if (!$id = $request->post('id')) {
            return json(['code' => 0, 'msg' => '参数错误'], 422);
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
                $library->like = Db::name('library_like_history')->where('library_id', $library->id)
                    ->where('user_id', $user['id'])->count('library_id');
                $library->collect = Db::name('user_collect')->where('type', 2)
                    ->where('user_id', $user['id'])->where('collect_id', $library->id)->count('id');

                //查询热门案例
                $hot = LibraryModel::field('id,name,library_pic')->where('is_delete', 0)
                    ->where('status', 1)
                    ->order('collect_num desc,like_num desc,comment_num desc,see_num desc create_time asc')
                    ->limit(0, 10)->select();
            } else {
                return json(['code' => 0, 'msg' => '查询数据不存在'], 404);
            }
//            print_r($library);die;
            $data = ['library' => $library, 'hot' => $hot];
//            return response($data);
            return json(['code' => 1, 'msg' => '查询成功', 'data' => $data], 200);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '查询失败'], 500);
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

        $data['user_id'] = $user['id'];
        $validate = new LibraryValidate();
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()], 422);
        }

        $config = \HTMLPurifier_Config::createDefault();
        $purifier = new \HTMLPurifier($config);
        $data['desc'] = $purifier->purify($data['desc']);
        $data['data_size'] = round($data['data_size'] / 1024 / 1024, 2);
        $data['is_official'] = $user->type == 2 ? 1 : 0;
        $data['create_time'] = time();

        Db::startTrans();
        try {

            $sql = date('Ymd', time()) . "-FROM_UNIXTIME(create_time,'%Y%m%d')=0";

            $library_integral_count = Db::name('library')
                ->where('user_id', $user['id'])
                ->whereRaw($sql)->count('create_time');

            $library_integral_count = $library_integral_count ?: 0;

            //判断当日可获得积分次数，
            if ($this->getConfig('issue_integral_count') - $library_integral_count > 0) {
                $integral = $this->getConfig('issue_integral');
                $this->addUserIntegralHistory(10, $integral);
            }

            $library = LibraryModel::create($data);
            //转换属性
            $attr_value_ids = json_decode($data['attr_value_ids'], true);
            //提取属性ID和属性值ID
            foreach ($attr_value_ids as $k => $v) {
                foreach ($v as $key => $item) {
                    $value_id[] = $item;
                    $have_attr_value[] = [
                        'attr_id' => $k,
                        'attr_value' => $item,
                        'library_id' => $library['id'],
                    ];
                }
            }
            //批量插入属性
            $library_have_attribute_value = (new LibraryHaveAttributeValueModel())->saveAll($have_attr_value);
            if (!$library_have_attribute_value) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '发布失败'], 417);
            }

            //增加分类数量
            $category = (new LibraryCategoryModel())->where('id', $library['cate_id'])->setInc('count');
            if (!$category) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '发布失败'], 417);
            }

            //批量更新属性文库数量
            $library_attribute_value = (new LibraryAttributeValue())->where('id', 'in', $value_id)->setInc('library_num');

            Db::commit();
            return json(['code' => 1, 'msg' => '发布成功'], 201);

        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '发布失败'], 500);
        }


    }


    /**
     * 保存更新的资源
     *
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request)
    {
        $user = $this->userInfo;
        $data = $request->post();

        if (!array_key_exists('id', $data)) {
            return json(['code' => 0, 'msg' => '缺少必要参数'], 422);
        }

        $data['user_id'] = $user['id'];
        $validate = new LibraryValidate();
        if (!$validate->check($data)) {
            return json(['code' => 0, 'msg' => $validate->getError()], 422);
        }

        if (array_key_exists('desc', $data)) {
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $data['desc'] = $purifier->purify($data['desc']);
        }
        if (array_key_exists('data_size', $data)) {
            $data['data_size'] = round($data['data_size'] / 1024 / 1024, 2);
        }
        Db::startTrans();
        try {

            $library = (new LibraryModel())->where('id', $data['id'])->find();

            if ($library->cate_id != $data['cate_id']) {
                $library_cate = (new LibraryCategoryModel())->where('id', $library->cate_id)
                    ->where('count', '>', 0)->setDec('count');
                $library_cate = (new LibraryCategoryModel())->where('id', $data['cate_id'])->setInc('count');
            }

            //提取属性值
            $attr_value_ids = json_decode($data['attr_value_ids'], true);
            //提取属性ID和属性值ID
            foreach ($attr_value_ids as $k => $v) {
                foreach ($v as $key => $item) {
                    $value_id[] = $item;
                    $have_attr_value[] = [
                        'attr_id' => $k,
                        'attr_value' => $item,
                        'library_id' => $library['id'],
                    ];
                }
            }

            $library_have_attribute_value = LibraryHaveAttributeValueModel::where('library_id', $library->id)->select();

            $count = count($library_have_attribute_value) >= count($have_attr_value) ? count($library_have_attribute_value) : count($have_attr_value);

            for ($i = 0; $i < $count; $i++) {
                if (!empty($have_attr_value[$i]) && !empty($library_have_attribute_value[$i])) {
                    if ($have_attr_value[$i]['attr_value'] == $library_have_attribute_value[$i]['attr_value']) {
                        //相同的干掉，不需要更改
                        unset($have_attr_value[$i]);
                        unset($library_have_attribute_value[$i]);
                    }
                }
            }

            //提取删除ID
            $ids = [];
            foreach ($library_have_attribute_value as $v) {
                $ids[] = $v['id'];
                $attribute_value_ids[] = $v['attr_value'];
            }

            //删除多余属性
            if ($ids) {
                $library_have_attribute_value = LibraryHaveAttributeValueModel::destroy($ids);

                $attribute_value = (new LibraryAttributeValue())->where('id', 'in', $attribute_value_ids)
                    ->where('library_num', '>', 0)->setDec('library_num');
            }
            //更新新属性
            if ($have_attr_value) {
                $library_have_attribute_value = (new LibraryHaveAttributeValueModel())->saveAll($have_attr_value);
                foreach ($have_attr_value as $v) {
                    $att_ids[] = $v['attr_value'];
                }

                $attribute_value = (new LibraryAttributeValue())->where('id', 'in', $att_ids)->setInc('library_num');
            }
            unset($data['token']);
            unset($data['attr_value_ids']);
            $library = (new LibraryModel())->save($data, ['id' => $data['id']]);
            if ($library) {
                Db::commit();
                return json(['code' => 1, 'msg' => '修改成功'], 200);
            } else {
                return json(['code' => 0, 'msg' => '内容未修改'], 304);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '修改失败'], 500);
        }


    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function delete(Request $request)
    {
        $user = $this->userInfo;
        $id = $request->delete('id');
        if (!$id) {
            return json(['code' => 0, 'msg' => '缺少必要参数'], 422);
        }
        Db::startTrans();
        try {
            //查询云库是否存在
            $library = LibraryModel::where('id', $id)->where('is_delete', 0)->where('status', 1)->find();
            if (!$library) {
                return json(['code' => 0, 'msg' => '当前云库内容不存在'], 404);
            }

            //删除又拍云
            $config = new Config(env('UPYUN.SERVICE_NAME'), env('UPYUN.USERNAME'), env('UPYUN.PASSWORD'));
            $upyun = new Upyun($config);
            $has = $upyun->has($library['source_url']);
            if ($has) {
                //删除
                $del = $upyun->delete($library['source_url']);
                if (!$del) {
                    return json(['code' => 0, 'msg' => '删除失败'], 400);
                }
            }

            //分类数量减1
            $cate = LibraryCategoryModel::where('id', $library['cate_id'])
                ->where('count', '>', 0)
                ->where('count', '>', 0)->setDec('count');

            //获取属性
            $have_attribute_value = LibraryHaveAttributeValueModel::where('library_id', $library['id'])->all();
            foreach ($have_attribute_value as $v) {
                $value_ids [] = $v['attr_value'];
                $ids[] = $v['id'];
            }

            //属性数量减1
            if ($value_ids)
                $attribute_value = LibraryAttributeValue::where('library_num', '>', 0)
                    ->where('id', 'in', $value_ids)
                    ->where('library_num', '>', 0)->setDec('library_num');

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
                return json(['code' => 0, 'msg' => '未删除'], 304);
            }
//            Db::rollback();
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '删除失败'], 500);
        }
    }

    /**
     * 点赞
     * @param Request $request
     * @return \think\response\Json
     */
    public function like(Request $request)
    {
        $user = $this->userInfo;
        $library_id = $request->post('id');
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
                    return json(['code' => 0, 'msg' => '当前云库以点赞，不能重复点赞'], 417);
                }

                $library = (new LibraryModel())->where('id', $library_id)->find();
                if (!$library) {
                    return json(['code' => 0, 'msg' => '数据走丢啦，刷新后试试吧'], 404);
                }
                $library->like_num = $library->like_num + 1;
                $library->save();

                $library_like_user = Db::name('library_like_history')->insert(['library_id' => $library->id, 'user_id' => $user['id']]);

                if ($library_like_user) {
                    Db::commit();
                    return json(['code' => 1, 'msg' => '点赞成功'], 200);
                } else {
                    return json(['code' => 0, 'msg' => '点赞失败'], 417);
                }
            } else {
                return json(['code' => 0, 'msg' => '缺少必要参数'], 422);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '点赞失败'], 500);
        }


    }

    /**
     * 购买
     * @param Request $request
     * @return \think\response\Json
     */
    public function buy(Request $request)
    {
        $user = $this->userInfo;
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 0, 'msg' => '缺少必要参数'], 422);
        }
        Db::startTrans();
        try {
            //查找云库
            $library = LibraryModel::where('id', $id)->where('is_delete', 0)->where('status', 1)->find();

            if (!$library) {
                return json(['code' => 0, 'msg' => '云库不存在'], 404);
            }

            //查找是否有文件
            $config = new Config(env('UPYUN.SERVICE_NAME'), env('UPYUN.USERNAME'), env('UPYUN.PASSWORD'));
            $upyun = (new Upyun($config))->has($library['source_url']);
            if(!$upyun){
                return json(['code'=>0,'msg'=>'当前文件已经删除啦'],417);
            }

            //判断是否以购买过
            $is_user_buy_history = UserBuyHistory::where('type', 1)->where('buy_id', $library['id'])->where('user_id', $user['id'])->count('id');
            if ($is_user_buy_history) {
                return json(['code' => 1, 'msg' => '以购买过', 'data' => ['source_url' => Env::get('UPYUN.CDN_URL') . $library['source_url']]]);
            }
            //判断积分
            if ($user['vip_id']) {
                $vip = Vip::where('id', $user['vip_id'])->find();
                if (!$vip) {
                    $discount_integral = $library['integral'];
                }
                //计算价格
                $discount_integral = floor($library['integral'] * ($vip['discount'] / 10));
            } else {
                $discount_integral = $library['integral'];
            }
            $time = time();
            if (($user_integral = $user['integral'] - $discount_integral) >= 0) {
                //扣除用户积分
                $user_buyer = \app\api\model\User::update(['id' => $user['id'], 'integral' => $user_integral]);
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
                //文库主人加积分
                $user_vendor_integral = \app\api\model\User::update(['id' => $user_vendor['id'], 'integral' => $user_vendor['integral'] + $user_vendor_discount_integral]);
                //更新积分变动表 (购买者和文库主人的记录)，购买 4 被下载 9
                $user_history = [
                    ['type' => 4, 'integral' => $discount_integral, 'user_id' => $user['id'], 'create_time' => $time,],
                    ['type' => 9, 'integral' => $user_vendor_discount_integral, 'user_id' => $user_vendor['id'], 'create_time' => $time,]
                ];
                $user_integral_history = (new UserIntegralHistory())->saveAll($user_history);

                Db::commit();
                return json(['code' => 1, 'msg' => '购买成功', 'data' => ['source_url' => Env::get('UPYUN.CDN_URL') . $library['source_url']]],200);
            } else {
                Db::rollback();
                return json(['code' => 0, 'msg' => '当前助手币不足'], 400);
            }
//            Db::rollback();
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '购买失败'], 500);
        }

    }

    /**
     * 下载
     * @param Request $request
     * @return \think\response\Json
     */
    public function download(Request $request)
    {
        $user = $this->userInfo;
        $id = $request->post('id');
        if(!$id){
            return json(['code'=>0,'msg'=>'缺少必要参数'],422);
        }
        $user_buy_history = UserBuyHistory::where('buy_id',$id)->where('user_id',$user['id'])->count('id');
        if(!$user_buy_history){
            return json(['code'=>0,'msg'=>'您还没有购买过，不能下载哦'],400);
        }

        try {
            //增加下载记录
            $download = UserDownloadLibraryHistory::create(['library_id' => $id, 'user_id' => $user['id'], 'create_time' => time()]);
            if ($download) {
                return json(['code' => 1, 'msg' => '记录成功'], 201);
            } else {
                return json(['code' => 0, 'msg' => '记录失败'], 400);
            }
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '记录失败'], 500);
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
}
