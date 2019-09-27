<?php

namespace app\index\controller;

use app\index\model\Library;
use app\index\model\Classes;

class Index extends Base
{
    /**
     * 首页
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {

        $official_library = Library::field('id,name,library_pic,is_official')->where('status', 1)->where('is_delete', 0)
            ->order('create_time desc')->where(['is_official' => 1])->limit(0, 8)->select()->toArray();

        $library = Library::field('id,name,library_pic,is_official')->where('status', 1)->where('is_delete', 0)
            ->order('create_time desc')->where(['is_official' => 0])->limit(0, 8)->select()->toArray();

        $classes = Classes::field('id,name,class_pic')->where('is_delete', 0)
            ->order('create_time desc')->limit(0, 8)->select()->toArray();

        $this->assign('official_library', $official_library);    //官方发布的
        $this->assign('library', $library);                      //普通用户发布的
        $this->assign('classes', $classes);                      //课程
        $this->assign('today_recommend', $this->getConfig('today_recommend'));      //今日力推
        $this->assign('partner',$this->getConfig('partner'));                       //合作方
        $this->assign('announcement',$this->getConfig('announcement'));             //公告
        $this->assign('banner',$this->getConfig('image.1.url'));
        return $this->fetch();

    }


}
