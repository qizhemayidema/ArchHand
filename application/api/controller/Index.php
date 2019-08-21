<?php

namespace app\api\controller;

use think\Controller;
use think\Request;
use app\api\model\Library;
use app\api\model\Classes;

class Index extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        try {
            //TODO::查询今日力推
            //TODO::查询最新分享

            $library = Library::field('id,name,library_pic')->where('status', 1)->where('is_delete', 0)
                ->order('create_time desc')->limit(0, 8)->select();

            $classes = Classes::field('id,name,class_pic')->where('is_delete',0)
                ->order('create_time desc')->limit(0, 8)->select();

            $data = ['library' => $library, 'classes' => $classes];
            return json(['code' => 1, 'msg' => '查询成功', 'data' => $data]);
        }catch(\Exception $e){
            return json(['code'=>0,'msg'=>'查询失败']);
        }
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
