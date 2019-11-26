<?php

namespace app\admin\controller;

use app\admin\model\LibraryHaveAttributeValue;
use think\Controller;

use think\Db;
use think\Request;
use app\admin\model\Library as LibraryModel;
use app\common\controller\Library as CommonLibraryModel;
use app\admin\model\UserBuyHistory as UserBuyHistoryModel;
use think\Validate;

class Library extends Base

{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $libraries = (new LibraryModel)->where('is_delete', 0)
            ->where('status', '<>', -1)->order('status asc,is_classics desc,create_time desc')
            ->paginate(15);
        $this->assign('libraries', $libraries);
        return $this->fetch();

    }

    public function show($id)
    {
        try {
        $library = LibraryModel::with(['cate', 'attribute' => function ($query) {
            $query->field('id,attr_value_id,library_id')->with(['attributeValue' => function ($query) {
                $query->field('id,value');
            }]);
        }])->get($id);

//            dump($library);die;
        if (!$library) {
            $this->assign('is_exist', '未找到数据，请刷新页面确认当前数据是否以删除');
        }
        $this->assign('library', $library);
        return $this->fetch();
        } catch (\Exception $e) {
            $this->assign('is_exist', $e->getMessage());
            return $this->fetch();
        }


    }

    public function userShow($id)
    {
        try {
            $user = \app\admin\model\User::where('id', $id)->find();
            if (!$user) {
                $this->assign('is_exist', '未找到数据，请刷新页面确认当前数据是否以删除');
            }
            $this->assign('user', $user);
            return $this->fetch();
        } catch (\Exception $e) {
            $this->assign('is_exist', $e->getMessage());
            return $this->fetch();
        }
    }

    public function buyHistory()
    {
        $history = UserBuyHistoryModel::where(['history.type'=>1])
            ->alias('history')
            ->join('library library','library.id = history.buy_id')
            ->join('user user','user.id = history.user_id')
            ->field('history.integral,history.create_time')
            ->field('user.nickname,user.id user_id,user.avatar_url')
            ->field('library.name,library.library_pic,library.id library_id')
            ->paginate(15);

        $this->assign('history',$history);
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */

    public function add()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @param  int $id
     */
    public function read($id)
    {


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
     * <<<<<<< HEAD
     * 删除指定资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function delete(Request $request)
    {
        $id = $request->only('id');
        Db::startTrans();
        try {
            //如果是通过状态删除的 才能够减少统计数量
            $status = (new LibraryModel())->where(['id'=>$id['id']])->value('status');
            if($status == 1){
                (new CommonLibraryModel())->setAboutSum($id['id'],0);
            }
            $del = (new LibraryModel())->update(['id' => $id['id'], 'is_delete' => time()]);
            //删除标签
            $del_value = Db::name('library_have_attribute_value')->where('library_id', $id['id'])->delete();
            //删除审核原因
            $verify = Db::name('library_check_history')->where('library_id', $id['id'])->delete();
            //分类下
            //TODO::删除远程文件
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 审核显示
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function verify($id)
    {
        try {
            $id = explode(',',$id);
            $library = LibraryModel::where('id', $id[0])->find();
            if (!$library) {
                $this->assign('is_exist', '未找到数据，请刷新页面确认当前数据是否以删除');
                return $this->fetch();
            }
            $this->assign('status', $library->status);
            $this->assign('id', $id);
//            $this->assign('name', $library->name);
            return $this->fetch();
        } catch (\Exception $e) {
            $this->assign('is_exist', $e->getMessage());
            return $this->fetch();
        }
    }

    /**
     * 审核操作
     * 根据状态判断如果是未通过状态，将原因添加到记录表，否则不做修改或删除原因
     * @param Request $request
     * @return \think\response\Json
     */
    public function verifySave(Request $request)
    {

        $form = $request->only('id,status,because,name');

        $id = explode(',',$form['id']);

        if ($form['status'] == -1) {
            $rule = [
                'because' => 'require|min:2|max:40',
            ];
            $message = [
                'because.require' => '请填写原因',
                'because.min' => '不同过原因不能低于2个字符',
                'because.max' => '字符不能超过40个字'
            ];

            $validate = new Validate($rule, $message);
            if (!$validate->check($form)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
        }
        Db::startTrans();
        try {
            foreach ($id as $key => $value){
                $form['id'] = $value;

                $old_data = LibraryModel::find($form['id'])->getData();
                LibraryModel::update(['id' => $form['id'], 'status' => $form['status']]);
                if ($form['status'] == 1 && $old_data['status'] != 1){
                    (new CommonLibraryModel())->setAboutSum($form['id'],1);
                }elseif ($form['status'] != 1 && $old_data['status'] == 1){
                    (new CommonLibraryModel())->setAboutSum($form['id'],0);
                }
                if ($form['status'] == -1) {
                    Db::name('library_check_history')->where('library_id', $form['id'])->delete();

                    $check = Db::name('library_check_history')->insert([
                        'library_id' => $form['id'],
                        'because' => $form['because'],
                        'manager_name' => session('admin')->user_name,
                        'library_name' => $form['name'],
                        'create_time' => time(),
                    ]);
                    if (!$check) {
                        Db::rollback();
                        dump(1);
                        return json(['code' => 0, 'msg' => '审核失败']);
                    }
                } else {
                    $check = Db::name('library_check_history')->where('library_id', $form['id'])->delete();
                }
            }

            Db::commit();
            return json(['code' => 1, 'msg' => '审核成功']);
        } catch (\Exception $e) {
            Db::rollback();

            return json(['code' => 0, 'msg' => '系统错误']);
        }
    }


    public function classics(Request $request)
    {

        $param = $request->get();
        if ($param['classics']) {
            $is_num = 1;
        } else {
            $is_num = 0;
        }
        try {
            $classics = Db::name('library')->where('id', $param['id'])->update(['is_classics' => $is_num]);
            if ($classics) {
                return json(['code' => 1, 'msg' => '操作成功']);
            }
            return json(['code' => 0, 'msg' => '失败']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '出错啦']);
        }
    }


}
