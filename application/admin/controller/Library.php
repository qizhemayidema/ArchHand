<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\admin\model\Library as LibraryModel;

class Library  extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //
        $libraries = (new LibraryModel)->paginate(15);
        $this->assign('libraries',$libraries);
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

    public function  verify($id){

        $library = LibraryModel::where('id',$id)->find();

        if(!$library){
            $this->assign('is_exist','未找到数据，请刷新页面确认当前数据是否以删除');
            return $this->fetch();
        }
        $this->assign('status',$library->status);
        $this->assign('id',$library->id);
        return $this->fetch();
    }

    public function verifySave(Request $request){
        $form = $request->only('id,status,because');
        if($form['status']==-1){
            if(!$form['because']){
                return ;
            }
        }
        dump($form);die;
    }
}
