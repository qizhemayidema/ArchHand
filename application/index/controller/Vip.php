<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\Vip as VipModel;

class Vip extends Base
{
    public function index()
    {
        $info = (new VipModel())->order('price','desc')->select();

        $this->assign('vip',$info);
        $this->assign('banner',$this->getConfig('image.5.url'));
        return $this->fetch();
    }
}
